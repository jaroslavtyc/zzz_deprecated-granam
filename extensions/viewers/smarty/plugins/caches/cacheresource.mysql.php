<?php
/**
 * Cache Handler API
 * Uses MySQL for saving cached templates. Extremely speeds up clearing,
 * or more precisely deleting cached files.
 *
 * @package Smarty
 * @subpackage Cacher
 */
class Smarty_CacheResource_Mysql extends Smarty_CacheResource_Custom {

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
		$resultValues = DbDriver::fetch('
			SELECT
				modified,
				content
			FROM
				smarty_cache WHERE id = %s', $id
		);
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
	 *  Only implement it if modification times can be accessed faster than loading the complete cached content.}}
	 *
	 * @param string $id unique cache content identifier
	 * @param string $name template name
	 * @param string $cache_id cache id
	 * @param string $compile_id compile id
	 * @return int|bool timestamp (epoch) the template was modified or FAlSE
	*/
	protected function fetchTimestamp($id, $name, $cache_id, $compile_id)
	{
		$modified = DbDriver::fetchSingle('
			SELECT
				modified
			FROM
				smarty_cache WHERE id = %s', $id
		);
		if (empty($modified)) {
			return FALSE;
		}

		return $modified;
	}

	/**
	 * Save content to cache
	 *
	 * @param string		  $id			unique cache content identifier
	 * @param string		  $name		  template name
	 * @param string		  $cache_id   cache id
	 * @param string		  $compile_id compile id
	 * @param int|NULL $exp_time   seconds till expiration or NULL
	 * @param string $content content to cache
	 * @return bool success
	*/
	protected function save(
		$id,
		$name,
		$cache_id,
		$compile_id,
		$exp_time,
		$content
	){
		$insertValues = array(
			 'id' => $id,
			 'name' => $name,
			 'cacheId' => $cache_id,
			 'compileId' => $compile_id,
			 'content' => self::compress($content),
			 'modified' => time(),
		);
		DbDriver::query('
			REPLACE INTO
				smarty_cache', $insertValues
		);

		return (bool)DbDriver::affectedRows();
	}

	/**
	 * Delete content from cache
	 *
	 * @param string $name template name
	 * @param string $cache_id cache id
	 * @param string $compile_id compile id
	 * @param int|NULL $exp_time seconds till expiration time in seconds or NULL
	 * @return int number of deleted cache records
	*/
	protected function delete(
		$name,
		$cache_id,
		$compile_id,
		$exp_time
	) {
		if (is_null($name) && is_null($cache_id) && is_null($compile_id)
		 && is_null($exp_time)) {
			DbDriver::query('
				TRUNCATE TABLE
					smarty_cache
			');

			return -1;
		}

		$deletingQuery = DbDriver::delete()
			->from('smarty_cache');
		if (!is_null($name)) {
			$deletingQuery->where('name = %s', $name);
		}

		if (!is_null($compile_id)) {
			$deletingQuery->where('compileId = %s', $compile_id);
		}

		if (!is_null($exp_time)) {
			//$where[] = "modified < (strftime('%s','now') - datetime(" . intval($exp_time) . ", 'unixepoch'))";
			$deletingQuery->where('modified < %i', time() - intval($exp_time));
		}

		// equal test cache_id and match sub-groups
		if (!is_null($cache_id)) {
			$deletingQuery->where('(cacheId = %s', $cache_id,
				'OR [cacheId] LIKE %s', $cache_id . '%', ')');
		}

		$deletingQuery->execute();

		return DbDriver::affectedRows();
	}

	/**
	 * Encodes given data by zip compression technique
	 *
	 * @param string $data
	 * @return string
	 */
	protected static function compress($data)
	{
		return gzcompress($data);
	}

	/**
	 * Decodes given zip-compressed data to original decompressed data
	 *
	 * @param string $data
	 * @return string
	 */
	protected static function decompress($data)
	{
		return gzuncompress($data);
	}
}