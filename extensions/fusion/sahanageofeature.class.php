<?php
class SahanaGeoFeature {
	private $data;

	public function __construct ($f) {
		$this->data = $f;
	}

	public function geo_type () {
		return $this->data->geometry->type;
	}

	public function geo_is ($type) {
		return (ucfirst(trim($type))==$this->geo_type());
	}

	public function latitude () {
		$ret = NULL;
		if ($this->geo_is('Point')) :
			$ret = $this->data->geometry->coordinates[1];
		// In theory, we could also check for other geometries,
		// take a center point or whatever, etc.
		endif;
		return $ret;
	}

	public function longitude () {
		$ret = NULL;
		if ($this->geo_is('Point')) :
			$ret = $this->data->geometry->coordinates[0];
		// In theory, we could also check for other geometries,
		// take a center point or whatever, etc.
		endif;
		return $ret;
	}

	public function properties ( $geo = true ) {
		$pp = $this->data->properties;

		if ($geo) :
			// Now let's merge in some geometry.
			$pp->Latitude = $this->latitude();
			$pp->Longitude = $this->longitude();
		endif;

		return $pp;
	}

	public function to_table ( &$cols ) {
		$ret = NULL;
		if (is_object($this->data)) :
			$ret = array();
			$myrow = array();
			foreach ($this->properties() as $prop => $val) :
				// If we have a position from a previous table, use
				// that to normalize positions in returned row
				if (isset($cols[$prop])) :
					$idx = $cols[$prop];
				else :
					$idx = count($cols);
					$cols[$prop] = $idx;
				endif;

				// Populate row
				$myrow[$idx] = $val;
			endforeach;
			
			$idx = (isset($cols[':feature']) ? $cols[':feature'] : count($cols));
			$cols[':feature'] = $idx;
			$myrow[$idx] = $this->data;

			$ret = $myrow;
		else :
			// FIXME: Exception?
		endif;
		return $ret;
	} /* SahanaGeoFeature::to_table () */

}

