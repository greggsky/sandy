<?php
class OccupySandyDataSource {

	public function __construct () {
		// NOOP.
	}

	public function data ($params) {
		return NULL;
	}

	public function has_cache () {
		return false;
	}

	public function cache () {
		return NULL;
	}

	public function to_table_hash ($data) {
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
		return $ret;
	}

} /* class OccupySandyDataSource */


