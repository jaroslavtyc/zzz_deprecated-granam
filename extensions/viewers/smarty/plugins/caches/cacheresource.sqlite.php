<?php
/**
 * Cache Handler API
 * Uses SQLlite for saving cached templates. Extremely speed up clearing,
 * or more precisely deleting cached files
 *
 * @package Smarty
 * @subpackage Cacher
 */
class Smarty_CacheResource_Sqlite extends Smarty_CacheResource_Custom {

	/**
	 * The maximum time, in milliseconds, that SQLite will wait for a handle
	 * to become ready for use.
	 */
	const BUSY_TIMEOUT = 1000;

	/**
	 * Resource of SQLlite database for caching purpose
	 *
	 */
	protected static $connection;

	/**
	 * Initialize connection and storage
	 */
	public function __construct()
	{
		self::ensureStorageAndConnect();
	}

	/**
	 * Clearing connection
	 */
	public function __destruct()
	{
		self::disconnect();
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

	// ----- INNER FACILITIES -----

	/**
	 * Fetch cached content and its modification time from data source
	 *
	 * @param string $id unique cache content identifier, not used
	 * @param string $name template name
	 * @param string $cache_id cache id
	 * @param string $compile_id compile id
	 * @param string $cache_content cached content, modified by reference
	 * @param int $modified cache modification timestamp (unix epoch), modified by
	 *  reference
	 * @return void
	*/
	protected function fetch(
		$id,
		$name,
		$cache_id,
		$compile_id,
		&$content,
		&$modified
	) {
		$result = self::$connection->query(
			"SELECT
				content,
				modified
			FROM
				cache WHERE id = '" . self::escapeString($id) . "'",
			SQLITE_ASSOC
		);
		$resultValues = $result->fetchArray(SQLITE_ASSOC);
		if ($resultValues) {
			$content = self::decompress($resultValues['content']);
			$modified = $resultValues['modified'];
		} else {
			$content = NULL;
			$modified = NULL;
		}
	}

	/**
	 * Fetch cached content's modification timestamp from data source
	 *
	 * {@internal implementing this method is optional.
	 *  Only implement it if modification times can be accessed faster than loading
	 *  the complete cached content.}
	 *
	 * @param string $id unique cache content identifier
	 * @param string $name template name
	 * @param string $cache_id cache id
	 * @param string $compile_id compile id
	 * @return int|bool timestamp (epoch) the template was modified or FAlSE
	*/
	protected function fetchTimestamp($id, $name, $cache_id, $compile_id)
	{
		$result = self::$connection->query(
			"SELECT
					modified
				FROM
					cache WHERE id = '" . self::escapeString($id) . "'",
			SQLITE_ASSOC
		);
		$modified = $result->fetchSingle();
		if (empty($modified)) {
			return FALSE;
		}

		return $modified;
	}

	/**
	 * Save content to cache
	 *
	 * @param string $id unique cache content identifier
	 * @param string $name template name
	 * @param string $cacheId cache id
	 * @param string $compileId compile id
	 * @param int|NULL $modified seconds till expiration or NULL
	 * @param string $content content to cache
	 * @return bool success
	*/
	protected function save(
		$id,
		$name,
		$cacheId,
		$compileId,
		$modified,
		$content
	){
		return self::$connection->queryExec("
			INSERT OR REPLACE
				INTO cache (id, name, cacheId, compileId, content, modified)
			VALUES (
				'" . self::escapeString($id) . "',
				'" . self::escapeString(self::compress($name)) . "',
				'" . self::escapeString($cacheId) . "',
				'" . self::escapeString($compileId) . "',
				'" . self::escapeString(self::compress($content)) . "',
				'" . self::escapeString($modified) . "'
			)
		");
	}

	/**
	 * Delete content from cache
	 *
	 * @param string $name template name
	 * @param string $cacheId cache id
	 * @param string $compileId compile id
	 * @param int|NULL $expTime seconds till expiration time in seconds or NULL
	 * @return int number of deleted cache records
	*/
	protected function delete(
		$name,
		$cacheId,
		$compileId,
		$expTime
	) {
		if (is_null($name) && is_null($cacheId) && is_null($compileId)
		 && is_null($expTime)) {
			self::rebuildDatabase();

			return -1;
		}

		$where = array();
		if (!is_null($name)) {
			$where[] = "name = '" . self::escapeString(self::compress($name)) . "'";
		}

		if (!is_null($compileId)) {
			$where[] = "compileId = '" . self::escapeString($compileId) . "'";
		}

		if (!is_null($expTime)) {
			$where[] = "modified < (strftime('%s','now') - datetime(" . intval($expTime) . ", 'unixepoch'))";
		}

		// equal test cache_id and match sub-groups
		if (!is_null($cacheId)) {
			$escapedCacheId = self::escapeString($cacheId);
			$where[] = "cacheId = '" . $escapedCacheId . "'";
			$where[] = "cacheId LIKE '" . $escapedCacheId . "%'";
		}

		self::$connection->queryExec("
			DELETE
			FROM
				cache
			WHERE
				" . implode(' AND ', $where) . "
		");

		return self::$connection->changes();
	}

	/**
	 * Connect to database, create it if not exists.
	 * Create table for caching if not exists
	 *
	 * @return bool
	 */
	protected static function ensureStorageAndConnect()
	{
		if (!file_exists(self::getDatabaseFilename())) {
			$tableNeeded = TRUE;
		}

		if (!self::connect()) {

			return FALSE;
		}

		if (!empty($tableNeeded)) {
			if (!self::ensureStorage()) {

				return FALSE;
			}
		}

		return TRUE;
	}

	/**
	 * Connect to database
	 *
	 * @throws Exception in case of connecting problem
	 * @return bool
	 */
	protected static function connect()
	{
		self::$connection = new SQLiteDatabase(
			self::getDatabaseFilename(),
			0666,
			$errorMessage
		);
		if ($errorMessage) {
			throw new Exception(
				'Connecting to Smarty cache of type SQLite fails with message: ' .
				$errorMessage
			);
		}

		self::$connection->busyTimeout(self::BUSY_TIMEOUT);

		return is_a(self::$connection, 'SQLiteDatabase');
	}

	/**
	 * Truncate connection
	 *
	 * @return bool if disconnection has been processed
	 */
	protected static function disconnect()
	{
		if (isset(self::$connection)) {
			self::$connection = NULL;

			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Build database path and return it
	 *
	 * @return string
	 */
	protected static function getDatabaseFilename()
	{
		return DOCUMENT_ROOT . '/cache/cache.sdb';
	}

	/**
	 * Creates database if not exists, connect to database
	 *
	 * @return void
	 */
	protected static function ensureConnection()
	{
		if (!file_exists(self::getDatabaseFilename())) {
			$tableNeeded = TRUE;
		}

		if (!isset(self::$connection)) {
			self::$connection = new SQLiteDatabase(
				self::getDatabaseFilename(),
				0666,
				$errorMessage
			);
			if ($errorMessage) {
				throw new Exception('Connecting to Smarty cache of type SQLite fails with message: ' .
					$errorMessage);
			}
		}
	}

	/**
	 * Creates table for saving cache content, if not exists
	 *
	 * @return void
	 */
	protected static function ensureStorage()
	{
		self::$connection->queryExec('
			CREATE TABLE
			cache (
				id CHAR(40),
				name VARCHAR(255) NOT NULL,
				cacheId VARCHAR(255) NULL DEFAULT NULL,
				compileId VARCHAR(255) NULL DEFAULT NULL,
				content TEXT,
				modified INTEGER,
				PRIMARY KEY (id)
			)
		');
		$columnIndexes = array('name', 'cacheId', 'compileId' ,'modified');
		foreach ($columnIndexes as $columnIndex) {
			self::$connection->queryExec('
				CREATE INDEX ' . $columnIndex . '
				ON
					cache (' . $columnIndex . ')
			');
		}

		return self::$connection->singleQuery("
			SELECT
				1
			FROM
				sqlite_master
			WHERE
				type='table' AND name='cache'
		");
	}

	/**
	 * Disconnect from database;
	 * delete file with SQLite database;
	 * create databsae again and connect to it
	 *
	 * @throws Exception if any problem occurs
	 * @return bool
	 */
	protected static function rebuildDatabase()
	{
		if (!self::disconnect()) {
			throw new Exception(
				'Disconnect from database to rebuild it has not been successful'
			);
		}

		if (!self::deleteDatabaseFile()) {
			throw new Exception(
				'Deleteing database file to rebuild it has not been successful'
			);
		}

		if (!self::ensureStorageAndConnect()) {
			throw new Exception(
				'Reconnecting to rebuilt database has not been successful'
			);
		}

		return TRUE;
	}

	/**
	 * Delete database file
	 *
	 * @return bool result of deleting database file
	 */
	protected static function deleteDatabaseFile()
	{
		if (file_exists(self::getDatabaseFilename())) {

			return unlink (self::getDatabaseFilename());
		}

		return FALSE;
	}

	/**
	 * Encodes given data by zip compression technique
	 *
	 * @param string $string
	 * @return string
	 */
	protected static function compress($string)
	{
		return gzcompress($string);
	}

	/**
	 * Decodes given zip-compressed data to original decompressed data
	 *
	 * @param string $string
	 * @return string
	 */
	protected static function decompress($string)
	{
		return gzuncompress($string);
	}

	/**
	 * Escapes SQLite special characters
	 *
	 * @param string $string
	 * @return string escaped
	 */
	protected static function escapeString($string)
	{
		return sqlite_escape_string($string);
	}
}