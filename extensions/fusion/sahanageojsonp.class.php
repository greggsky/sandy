<?php
require_once('sahanageofeature.class.php');
require_once('occupysandydatasource.class.php');

	class SahanaInvalidQuery extends Exception {
		function __construct ($q, $message) {
			// FIXME: This sucks.
			parent::__construct('Invalid Query to Sahana Data Source: '.$message." / Query: ".json_encode($q));
		}
	}

	class SahanaExpectedFeature extends Exception {
		function __construct ($f) {
			// FIXME: This sucks.
			parent::__construct('Invalid Feature Object: '.json_encode($f));
		}
	}

class SahanaGeoJSONP extends OccupySandyDataSource {
	private $url;
	private $text;
	private $data;

	function __construct ($data) {
		// This could be a URL, an wp_remote_request() return value,
		// or just some JSON/JSONP in a string.

		if (is_string($data)) :
			// See if it parses.
			$this->text = $data;
			$this->parse();

			if (is_null($this->data)) :
				// Does it look like a URL?
				$bits = parse_url($this->text);
				if (isset($bits['scheme'])) :
					$this->text = NULL;
					$this->url = $data;
					$data = wp_remote_request($this->url);
				endif;
			endif;
		endif;

		if (is_null($this->data) and $this->is_jsonp($data)) :
			// Got a HTTP reply from wp_remote_request
			$this->text = $data['body'];
		else :
			$this->text = NULL;
		endif;

		if (!is_null($this->text)) :
			$this->parse();
		endif;
	}

	function is_jsonp ($req) {
		// I'd like to check MIME headers, but Sahana doesn't currently supply them?
		return (
			is_array($req)
			and isset($req['body'])
			and isset($req['response'])
			and isset($req['response']['code'])
			and ($req['response']['code']==200)
		);
	} /* SahanaGeoJSONP::is_jsonp() */

	function parse () {
		// First pass -- just try to parse it.
		$data = json_decode($this->text);

		// If it fails, try looking for JSONP.
		if (is_null($data)) :
			if (preg_match('/^\s*
			[A-Za-z_$][A-Za-z_$0-9]*
			\s* \( \s*
			([^\s].*)
			\s* \) \s* ;? \s* $/ix', $this->text, $m)) :
				// Strip off function name and parens
				$payload = $m[1];
				$data = json_decode($payload);
			endif;
		endif;

		$this->data = $data;
	} /* SahanaGeoJSONP::parse () */

	function is_ok () {
		return !is_null($this->data);
	} /* SahanaGeoJSONP::is_ok () */

	function is_collection () {
		return (
			$this->is_ok()
			and is_object($this->data)
			and isset($this->data->type)
			and ('FeatureCollection'==$this->data->type)
			and isset($this->data->features)
			and is_array($this->data->features)
		);
	} /* SahanaGeoJSONP::is_collection () */

	function get_features () {
		$feat = null;

		if ($this->is_collection()) :
			$feat = $this->data->features;
		endif;

		return $feat;
	} /* SahanaGeoJSONP::get_features () */

	public function data ($params = array()) {
		$params = wp_parse_args($params, array(
		"cols" => '*',
		"limit" => null,
		"offset" => null,
		"table" => null,
		"matches" => null,
		"raw" => false,
		"fresh" => false,
		));

		$data = new stdClass;

		$ff = $this->get_features();
		if (is_array($ff)) :
			if (count($ff) > 0) :
				$cols = array();

				// Initialize.
				$data->kind = 'sahanajsonp#sqlresponse';
				$data->columns = array();
				$data->rows = array();

				foreach ($ff as $f) :
					$feat = new SahanaGeoFeature($f);
					$row = $feat->to_table($cols);
					if (is_array($row)) :
						ksort($row);
						$data->rows[] = $row;
					else :
						throw new SahanaExpectedFeature($f);
					endif;
				endforeach;
				
				$data->columns = array_flip($cols);
				ksort($data->columns);

			endif;
		else :
			// FIXME: Maybe pass back a WP_Error object?
			$data = NULL;
		endif;

		if (is_object($data)) :
			// Apply limit / offset clause
			if (!is_null($params['offset']) or !is_null($params['limit'])) :
				$offset = (is_null($params['offset']) ? 0 : $params['offset']);
				$limit = (is_null($params['limit']) ? count($data->rows) - $offset : $params['limit']);

				$data->rows = array_slice($data->rows, $offset, $limit);
			endif;

			// Apply WHERE filter
			if (is_array($params['matches'])) :
				// Should be an associative array. Column names
				// as the keys; value(s) that MUST be matched as
				// the values. Scalar interpreted as a simple
				// equality match; array interpreted as an
				// element-of match.
				foreach ($params['matches'] as $col => $acceptable) :
					if (!is_array($acceptable)) :
						$acceptable = array($acceptable);
					endif;

					$data->rows = array_filter($data->rows,
					function ($r) use ($col, $acceptable, &$data) {
						// Get index to check
						$idx = array_search($col, $data->columns);

						// If the column doesn't exist, treat
						// the value as NULL.
						if (false === $idx) :
							$cell = NULL;
						else :
							$cell = $r[$idx];	
						endif;

						// Use array_reduce so if we need
						// some sophisticated matching we
						// can code it in easily.
						// Right now we just make it
						// case-insensitive and strip leading
						// and trailing whitespace
						return array_reduce(
							$acceptable,
							function ($running, $v) use ($cell) {
								if (is_null($running)) :
									$running = false;
								endif;

								return (
									$running or
									strtolower(trim($cell)) == strtolower(trim($v))
								);
							}
						);
					});
				endforeach;
			endif;

			// Apply column selection
			if (!is_null($params['cols']) and $params['cols'] != '*') :
				$selected = array_map('trim', preg_split('/\s*,\s*/', $params['cols']));
				
				$indexOf = array_flip($data->columns);
				$columns = array();

				foreach ($selected as $col) :
					if (isset($indexOf[$col])) :
						$columns[$col] = $indexOf[$col];
					else :
						throw new SahanaInvalidQuery($params, "Column does not exist");
					endif;
				endforeach;

				foreach ($data->rows as $idx => $value) :

					$row = array();

					foreach ($columns as $cell) :
						$row[$cell] = (isset($value[$cell]) ? $value[$cell] : NULL);
					endforeach;
					
					$data->rows[$idx] = $row;

				endforeach;
				$data->columns = array_flip($columns);
			endif;
		endif;

		if (is_wp_error($data) or $params['raw']) :
			$data = $data;
		else :
			$data = $this->to_table_hash($data);
		endif;
		return $data;
	} /* SahanaGeoJSONP::data () */

} /* class SahanaGeoJSONP */

