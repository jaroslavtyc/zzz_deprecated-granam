<?php
/**
 * Cache Handler API
 * Uses SQLlite for saving cached templates. Extremely speed up clearing,
 * or more precisely deleting cached files
 *
 * @package Smarty
 * @subpackage Cacher
 */
class Smarty_CacheResource_Sqllite extends Smarty_CacheResource_Custom {

	/**
	 * Resource of SQLlite database for caching purpose
	 *
	 */
	protected static $connection;

	public function __destruct()
	{
		if (isset(self::$connection)) {
			self::$connection->close();
		}
	}

	protected static function getConnection()
	{
		if (!isset(self::$connection)) {
			self::$connection = new SQLite3(
				DOCUMENT_ROOT . '/cache/cache.s3db',
				SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE
			);

			self::$connection->query('
				CREATE TABLE IF NOT EXISTS
				cache (
					id VARCHAR(256),
					name VARCHAR(256),
					compile_id VARCHAR(256),
					cache_id VARCHAR(256),
					content TEXT,
					modifyTime INT,
					expirationTime INT,
					PRIMARY KEY (name, compile_id, cache_id)
				)
			');
		}

		return self::$connection;
	}

	/**
	 * Populate Cached Object with meta data from Resource
	 *
	 * @param Smarty_Template_Cached   $cached	cached object
	 * @param Smarty_Internal_Template $_template template object
	 * @return void
	*/
	public function populate(Smarty_Template_Cached $cached, Smarty_Internal_Template $_template)
	{
		$_cache_id = isset($cached->cache_id) ? $cached->cache_id : NULL;
		$_compile_id = isset($cached->compile_id) ? $cached->compile_id : NULL;
		$cached->filepath = sha1($cached->source->filepath . $_cache_id . $_compile_id);
		$this->populateTimestamp($cached);
	}

	/**
	 * Populate Cached Object with timestamp and exists from Resource
	 *
	 * @param Smarty_Template_Cached $source cached object
	 * @return void
	*/
	public function populateTimestamp(Smarty_Template_Cached $cached)
	{
		$mtime = $this->fetchTimestamp($cached->filepath, $cached->source->name, $cached->cache_id, $cached->compile_id);
		if ($mtime !== NULL) {
			$cached->timestamp = $mtime;
			$cached->exists = (bool)$cached->timestamp;

			return;
		}

		$timestamp = NULL;
		$this->fetch($cached->filepath, $cached->source->name, $cached->cache_id, $cached->compile_id, $cached->content, $timestamp);
		$cached->timestamp = isset($timestamp) ? $timestamp : FALSE;
		$cached->exists = (bool)$cached->timestamp;
	}

	/**
	 * Read the cached template and process the header
	 *
	 * @param Smarty_Internal_Template $_template template object
	 * @param Smarty_Template_Cached $cached cached object
	 * @return booelan TRUE or FALSE if the cached content does not exist
	*/
	public function process(Smarty_Internal_Template $_template, Smarty_Template_Cached $cached = NULL)
	{
		if (!$cached) {
			$cached = $_template->cached;
		}

		$content = $cached->content ? $cached->content : NULL;
		$timestamp = $cached->timestamp ? $cached->timestamp : NULL;
		if (is_null($content) || is_null($timestamp)) {
			$this->fetch(
				$_template->cached->filepath,
				$_template->source->name,
				$_template->cache_id,
				$_template->compile_id,
				$content,
				$timestamp
			);
		}

		if (!isset($content)) {
			return FALSE;
		}

		$result = eval('?>' . $content);
		if (FALSE === $result) {
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Write the rendered template output to cache
	 *
	 * @param Smarty_Internal_Template $_template template object
	 * @param string							$content   content to cache
	 * @return boolean success
	*/
	public function writeCachedContent(Smarty_Internal_Template $_template, $content)
	{
		return $this->save(
			$_template->cached->filepath,
			$_template->source->name,
			$_template->cache_id,
			$_template->compile_id,
			$_template->properties['cache_lifetime'],
			$content
		);
	}

	/**
	 * Empty cache
	 *
	 * @param Smarty  $smarty   Smarty object
	 * @param integer $exp_time expiration time (number of seconds, not timestamp)
	 * @return integer number of cache files deleted
	*/
	public function clearAll(Smarty $smarty, $exp_time=NULL)
	{
		$this->cache = array();

		return $this->delete(NULL, NULL, NULL, $exp_time);
	}

	/**
	 * Empty cache for a specific template
	 *
	 * @param Smarty  $smarty Smarty object, required for interface compatibility
	 * @param string  $resource_name template name
	 * @param string  $cache_id cache ID
	 * @param string  $compile_id compile ID
	 * @param integer $exp_time expiration time (number of seconds, not timestamp)
	 * @return int number of cache files deleted
	*/
	public function clear(Smarty $smarty, $resource_name, $cache_id, $compile_id, $exp_time)
	{
		$this->cache = array();

		return $this->delete($resource_name, $cache_id, $compile_id, $exp_time);
	}

	/**
	 * Check if cache is locked for this template
	 *
	 * @param Smarty $smarty Smarty object
	 * @param Smarty_Template_Cached $cached cached object
	 * @return booelan TRUE or FALSE if cache is locked
	*/
	public function hasLock(Smarty $smarty, Smarty_Template_Cached $cached)
	{
		return !empty($cached->lock_lifetime) //lock_lifetime ADDED
			&& $cached->lock_lifetime <= time();
	}

	/**
	 * Lock cache for this template
	 *
	 * @param Smarty $smarty Smarty object
	 * @param Smarty_Template_Cached $cached cached object
	*/
	public function acquireLock(Smarty $smarty, Smarty_Template_Cached $cached)
	{
		$cached->is_locked = TRUE;
		$cached->lock_lifetime = time() + $smarty->locking_timeout; //lock_lifetime ADDED top cached
	}

	/**
	 * Unlock cache for this template
	 *
	 * @param Smarty $smarty Smarty object, required for interface compatibility
	 * @param Smarty_Template_Cached $cached cached object
	 * @return void
	*/
	public function releaseLock(Smarty $smarty, Smarty_Template_Cached $cached)
	{
		$cached->is_locked = FALSE;
		$cached->lock_lifetime = FALSE; //ADDED
	}

	// ----- INNER FACILITIES -----

	/**
	 * Fetch cached content and its modification time from data source
	 *
	 * @param string $id					unique cache content identifier, not used
	 * @param string $name				template name
	 * @param string $cache_id			cache id
	 * @param string $compile_id		compile id
	 * @param string $cache_content	cached content, modified by reference
	 * @param int $mtime cache modification timestamp (unix epoch), modified by
	 *  reference
	 * @return void
	*/
	protected function fetch(
		$id,
		$name,
		$cache_id,
		$compile_id,
		&$cache_content,
		&$mtime
	) {
		$result = self::getConnection()->query('
			SELECT cacheContent, modifyTime
			FROM cache
			WHERE
				name = "' . $name . '"
				AND compile_id = "' . $compile_id . '"
				AND cache_id = "' . $cache_id . '"
		');
		if ($result) {
			$cache_content = $result['cacheContent'];
			$mtime = $result['modifyTime'];
		}
	}

	/**
	 * Fetch cached content's modification timestamp from data source
	 *
	 * {@internal implementing this method is optional.
	 *  Only implement it if modification times can be accessed faster than loading the complete cached content.}}
	 *
	 * @param string $id			unique cache content identifier
	 * @param string $name		  template name
	 * @param string $cache_id   cache id
	 * @param string $compile_id compile id
	 * @return integer|boolean timestamp (epoch) the template was modified, or FALSE if not found
	*/
	protected function fetchTimestamp($id, $name, $cache_id, $compile_id)
	{
		$modifyTime = self::getConnection()->querySingle('
			SELECT modifyTime
			FROM cache
			WHERE
				name = "' . $name . '"
				AND compile_id = "' . $compile_id . '"
				AND cache_id = "' . $cache_id . '"
		');
		if (!$modifyTime) {
			return FALSE;
		}

		return $modifyTime;
	}

	/**
	 * Save content to cache
	 *
	 * @param string		  $id			unique cache content identifier
	 * @param string		  $name		  template name
	 * @param string		  $cache_id   cache id
	 * @param string		  $compile_id compile id
	 * @param integer|NULL $exp_time   seconds till expiration or NULL
	 * @param string $content content to cache
	 * @return boolean success
	*/
	protected function save(
		$id,
		$name,
		$cache_id,
		$compile_id,
		$exp_time,
		$content
	){
		self::getConnection()->query("
			UPDATE cache
			SET content = '" . SQLite3::escapeString($content) . "',
				expirationTime = '" . $exp_time . "'
			WHERE
				name = '" . SQLite3::escapeString($name) . "'
				AND compile_id = '" . SQLite3::escapeString($compile_id) . "'
				AND cache_id = '" . SQLite3::escapeString($cache_id) . "'
		");
		if (self::getConnection()->changes() < 1) {
			self::getConnection()->query("
				INSERT INTO
				cache (
					id,
					name,
					compile_id,
					cache_id,
					content,
					expirationTime
				)
				VALUES (
					'" . SQLite3::escapeString($id) . "',
					'" . SQLite3::escapeString($name) . "',
					'" . SQLite3::escapeString($compile_id) . "',
					'" . SQLite3::escapeString($cache_id) . "',
					'" . SQLite3::escapeString($content) . "',
					'" . $exp_time . "'
				)
			");
		}

		return (bool)self::getConnection()->changes();
	}

	/**
	 * Delete content from cache
	 *
	 * @param string		  $name		  template name
	 * @param string		  $cache_id   cache id
	 * @param string		  $compile_id compile id
	 * @param integer|NULL $exp_time   seconds till expiration time in seconds or NULL
	 * @return integer number of deleted caches
	*/
	protected function delete(
		$name,
		$cache_id,
		$compile_id,
		$exp_time
	) {
		self::getConnection()->query("
			DELETE cache.*
			FROM
				cache
			WHERE
				name = '" . SQLite3::escapeString($name) . "'
				AND compileId = '" . SQLite3::escapeString($compile_id) . "'
				AND cacheId = '" . SQLite3::escapeString($cache_id) . "'
				AND expirationTime <= '" . $exp_time . "'
		");
	}
}