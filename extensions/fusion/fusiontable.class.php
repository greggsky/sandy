<?php
require_once('occupysandydatasource.class.php');

class FusionTable extends OccupySandyDataSource {
	private $apikey;
	private $defaultTable;
	private $apiQs = 0;
	private $cache;

	function __construct ($apikey, $defaultTable = NULL) {
		$this->apikey = $apikey;
		$this->defaultTable = $defaultTable;

		// If this is available . . .
		if ($this->can_cache()) :
			$this->cache = new FusionTableCache;
		else :
			$this->cache = null;
		endif;
	}

	function can_cache () { return class_exists('FusionTableCache'); }
	function has_cache () {	return !is_null($this->cache); }
	function cache () { return $this->cache; }

	function select ($query, $params = array()) {
		$params = wp_parse_args($params, array(
		"fresh" => false,
		));

		$query = urlencode($query);
		$url = 'https://www.googleapis.com/fusiontables/v1/query?sql=' . $query . '&key=' . $this->apikey;

		$resp = NULL;
		if ($this->has_cache() and !$params['fresh']) :
			$resp = $this->cache->get($url, $this->apikey);
		endif;

		if (is_null($resp)) :
			$resp = wp_remote_request($url, array(
			'method' => 'GET',
			));
			$this->apiQs++;

			if ($this->has_cache()) :
				$this->cache->put($url, $this->apikey, $resp);
			endif;
		endif;

		if (is_wp_error($resp)) :
			$ret = $resp;
		elseif (200!=$resp['response']['code']) :
			// Successful HTTP communication, but error code returned by API. Parse out error data.

			$errorMsgs = array();
			if (preg_match('|^application/json|i', $resp['headers']['content-type'])) : // JSON returned
				$data = json_decode($resp['body']);
				if (!is_null($data)) :
					if (isset($data->error) and !is_null($data->error)) :
						if (isset($data->error->errors) and count($data->error->errors) > 0) :
							foreach ($data->error->errors as $err) :
								$errorMsgs[] = (strlen($err->message) > 0 ? $err->message : $err->reason);
							endforeach;
						endif;
					endif;
				endif;
			endif;
			$errorMessage = 'HTTP GET for FusionTable returned '.$resp['response']['code'];
			if (count($errorMsgs) > 0) :
				$errorMessage .= ". API returned: &#8220;".implode("&#8221; / &#8220;", $errorMsgs)."&#8221;";
			endif;
			
			$ret = new WP_Error('fusion-http', $errorMessage, $resp);
		else :
			// HTTP OK, API returned OK
			if (!preg_match('|^application/json|i', $resp['headers']['content-type'])) : // No JSON
				$ret = new WP_Error('fusion-http', 'JSON transmission problem with FusionTable', $resp);
			else :
				$json = $resp['body'];
				$data = json_decode($json);

				// Oh my God this is so horribly ugly.
				if (is_null($data) and preg_match('/NaN/', $json)) :
					$json = preg_replace('/^(\s*)NaN(,?\s*)$/m', '$1null$2', $json);
					$data = json_decode($json);
				endif;

				if (is_null($data)) : // JSON not OK
					$ret = new WP_Error('fusion-json', 'JSON decoding problem with FusionTable', array($json, $resp));

				else : // JSON OK
					$ret = $data;
				endif;
			endif;
		endif;
		return $ret;
	}

	function data ($params = array()) {
		global $wpdb;

		$params = wp_parse_args($params, array(
		"cols" => '*',
		"limit" => null,
		"offset" => null,
		"table" => null,
		"matches" => null,
		"raw" => false,
		"fresh" => false,
		));

		// Relocated this from front-end all the way back to the
		// data source. This limits functionality but nothing was
		// using it that I know of, and this way I don't have to
		// write a full-on SQL expression parser for alternative
		// data sources.
		$whereClause = '';
		if (is_array($params['matches'])) :
			$whereClauses = array();
			foreach ($params['matches'] as $col => $value) :
				if (!is_array($value)) :
					$value = array($value);
				endif;
			
				if (count($value) > 1) :
					$operator = 'IN';
					$operand = "(";
					if (count($value) > 0) :
						$operand .= "'"
						 .implode(
						 	"', '",
							array_map(function ($v) {
						return $GLOBALS['wpdb']->escape(trim($v));
							}, $value))
						. "'";
					endif;
					$operand .= ")";
				else :
					$operator = '=';
					$operand = "'".$wpdb->escape(reset($value))."'";
				endif;
			
				$whereClauses[] = "$col $operator $operand";
			endforeach;
			$whereClause = ' WHERE '.implode(' AND ', $whereClauses);
		endif;             

		$limitClause = '';
		if (is_numeric($params['limit'])) :
			$limitClause = ' LIMIT '.$params['limit'];
		endif;

		if (is_numeric($params['offset'])) :
			$limitClause = ' OFFSET '.$params['offset'].$limitClause;
		endif;

		if (is_string($params['where'])) :
			$whereClause = ' WHERE '.$params['where'];
		endif;
		
		$fromClause = '';
		if (is_null($params['table'])) :
			$fromClause = ' FROM '.$this->defaultTable;
		else :
			$fromClause = ' FROM '.$wpdb->escape($defaultTable);
		endif;

		$data = $this->select('SELECT '.$params['cols'].$fromClause.$whereClause.$limitClause, $params);

		if (is_wp_error($data) or $params['raw']) :
			$ret = $data;
		else :
			$ret = $this->to_table_hash($data);
		endif;
		return $ret;
	}

}
