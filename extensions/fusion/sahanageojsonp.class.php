<?php
class SahanaGeoJSONP {
	private $text;
	private $data;

	function __construct ($data) {
		if (is_string($data)) : // Just supplied some JSON or JSONP in a string
			$this->text = $data;
		elseif (is_array($data) and $this->is_jsonp($data)) : // Supplied a HTTP reply
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

} /* class SahanaGeoJSONP */

