<?php
define('FUSIONTABLE_CACHE_WINDOW', 60); // seconds

class FusionTable {
	private $apikey;
	private $defaultTable;
	private $apiQs = 0;

	function __construct ($apikey, $defaultTable = NULL) {
		$this->apikey = $apikey;
		$this->defaultTable = $defaultTable;

		add_action('shutdown', array(&$this, 'shutdown'));

		$this->queryResults = get_option('fusiontables_querycache_'.$this->apikey);
	}

	function shutdown () {
		update_option('fusiontables_querycache_'.$this->apikey, $this->queryResults);
	}

	private $queryResults = array();
	function select ($query, $params = array()) {
		$params = wp_parse_args($params, array(
		"fresh" => false,
		));

		$query = urlencode($query);
		$url = 'https://www.googleapis.com/fusiontables/v1/query?sql=' . $query . '&key=' . $this->apikey;

		$resp = NULL;		
		if (isset($this->queryResults[$url]) and !$params['fresh']) :
			$cache = $this->queryResults[$url];
			if ($cache['baked'] < $cache['stale']) :
				$resp = $cache['bread'];
			endif;
		endif;

		if (is_null($resp)) :
			$resp = wp_remote_request($url, array(
			'method' => 'GET',
			));
			$this->apiQs++;

			$baked = time();

			// What would really be nice would be to use Cache-Control headers to determine settings here
			// but good citizenship in the HTTP commonwealth will have to wait a little bit. Anyway Google
			// FusionTables API sends back Cache-Control headers that indicate they don't really care much
			$this->queryResults[$url] = array(
				"baked" => $baked,
				"stale" => $baked + FUSIONTABLE_CACHE_WINDOW,
				"bread" => $resp
			);
		endif;

		if (is_wp_error($resp)) :
			$ret = $resp;
		elseif (200!=$resp['response']['code']) :
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
		else :  // OK
			if (!preg_match('|^application/json|i', $resp['headers']['content-type'])) : // No JSON
				$ret = new WP_Error('fusion-http', 'JSON transmission problem with FusionTable', $resp);
			else :
				$json = $resp['body'];
				$data = json_decode($json);

				// Oh my God this is so horribly ugly.
				if (is_null($data) and preg_match('/NaN/', $json)) :
					$json = preg_replace('/^(\s*)NaN(,\s*)$/m', '$1null$2', $json);
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
		"where" => null,
		"raw" => false,
		"fresh" => false,
		));

		$limitClause = '';
		if (is_numeric($params['limit'])) :
			$limitClause = ' LIMIT '.$params['limit'];
		endif;

		if (is_numeric($params['offset'])) :
			$limitClause = ' OFFSET '.$params['offset'].$limitClause;
		endif;

		$whereClause = '';
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
			$ret = array();
			foreach ($data->rows as $row) :
				$aRow = array();
				foreach ($row as $idx => $col) :
					$i = $idx;
					if (isset($data->columns[$idx])) :
						$i = $data->columns[$idx];
					endif;
					$aRow[$i] = $col;
				endforeach;
				$ret[] = $aRow;
			endforeach;
		endif;
		return $ret;
	}
}
