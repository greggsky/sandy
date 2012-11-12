<?php
class OccupySandyCard {
	private $row = array();
	private $cols = array();
	private $cardTitle;
	private $cardClasses;
	private $regionClasses;

	function __construct ($row = array(), $cols = array()) {
		$this->row = $row;
		$this->cols = $cols;

		$this->parseData();

		$this->cardClasses = apply_filters('occupysandy_card_classes', $this->cardClasses, $this);
		$this->cardTitle = apply_filters('occupysandy_card_title', $this->cardTitle, $this);
	}

	function parseData () {
		$this->cardClass = array();
		$this->cardTitle = '';

		if ($this->is_distro_center()) :
			$this->cardClasses[] = 'droppoff volunteer hub';
			$this->cardTitle = 'Main Distribution Center';
		endif;
		
		if ($this->is_drop_off()) :
			$this->cardClasses[] = 'dropoff';
			$this->cardTitle = 'Drop-Off '.($this->is_volunteer() ? '+ Volunteer' : 'Only');
		endif;

		if ($this->is_volunteer()) :
			$this->cardClasses[] = 'volunteer';
			
			if (strlen($this->cardTitle) < 1) :
				$this->cardTitle = 'Volunteer Only';
			endif;
		endif;

		if (strlen($this->cardTitle) < 1) :	
			if (strlen($this->field('type')) > 0) :
				$this->cardTitle = $this->field('type');
			else :
				$this->cardTitle = 'Unknown';
			endif;
		endif;

		if (count($this->cardClasses) == 0) :
			$this->cardClasses[] = 'unknown';
		endif;

		
		$this->regionClasses = array();
		$region = $this->field('Region');
		if (strlen($region) > 0) :
			$this->regionClasses[$region] = 'region-'.sanitize_title($region);
		else :
			$this->regionClasses['Other'] = 'region-other';
		endif;
	}

	function columns () {
		return $this->cols;
	}
	function has_field ($i) {
		if (!isset($this->row[$i])) :
			$found = array_search($i, $this->cols);
			if (false !== $found) :
				$i = $found;
			endif;
		endif;
		return (isset($this->row[$i]));
	}

	function field ($i) {
		if (!isset($this->row[$i])) :
			$found = array_search($i, $this->cols);
			if (false !== $found) :
				$i = $found;
			endif;
		endif;

		$ret = NULL;
		if (isset($this->row[$i])) :
			$ret = $this->row[$i];
		endif;
		return $ret;
	}

	function has_type ($what) {
		$type = $this->field('type');
		return (0<preg_match('/\b'.$what.'\b/i', $type));	
	}
	function is_drop_off () {
		return $this->has_type('drop-?off');
	}
	function is_volunteer () {
		return $this->has_type('volunteer');
	}
	function is_distro_center () {
		return $this->has_type('main distribution center');
	}
	function is_other_type () {
		return !($this->is_drop_off() or $this->is_volunteer() or $this->is_distro_center());
	}

	function get_state () {
		global $os_regionToState;

		$ret = $this->field('State');
		if (is_null($ret)) :
			$region = $this->field('Region');
			if (preg_match('/\b(New Jersey|NJ)\b/i', $region)) :
				$ret = 'NJ';
			elseif (!is_null($region) and (strlen($region) > 0)) :
				$ret = 'NY'; // Assume NY if (1) we have a region but (2) it's not in our list.
				$index = strtolower(trim(preg_replace('/\s+/', ' ', $region)));
				if (isset($os_regionToState[$index])) :
					$ret = $os_regionToState[$index];
				endif;
			endif;
		endif;
		return $ret;
	}

	function get_type_classes () { return $this->cardClasses; }
	function get_region_classes () { return $this->regionClasses; }
	function get_card_class () { return implode(" ", array_merge($this->get_type_classes(), $this->get_region_classes())); }
	function get_card_heading () { return $this->cardTitle; }
	function get_title () { return $this->field('Title'); }
	function get_address () { return $this->field('Address'); }
	function get_status () { return $this->field('Status'); }
	function get_times () { return $this->field('DateAndTimes'); }
	function get_contact () { return $this->field('Contact Info'); }
	function get_link () { return $this->field('Link'); }
	function get_description () { return $this->field('Description'); }
	function get_coordinates () {
		return array("lat" => $this->field('Latitude'), "long" => $this->field('Longitude'));
	}
	function get_timestamp ($fmt = 'r') {
		$time = $this->field('Timestamp');
		$ts = strtotime($time);
		if ($ts > 0) : // Can we get a legit Unix-epoch timestamp?
			$ret = date($fmt, $ts);
		else : // Sigh.
			$ret = $time;
		endif;
		return $ret;
	}
}

function get_occupy_sandy_cards ($params = array()) {
	$params = wp_parse_args($params, array(
	"raw" => true,
	));
	$params['raw'] = true; // Required.

	$data = get_occupy_sandy_data($params);
	if (is_wp_error($data)) :
		$ret = $data;
	else :
		$ret = array();
		foreach ($data->rows as $datum) :
			$ret[] = new OccupySandyCard($datum, $data->columns);
		endforeach;
	endif;
	return $ret;
}

function the_occupy_sandy_cards ($params = array()) {
	global $OccupySandyCard;

	$cards = get_occupy_sandy_cards($params);
	foreach ($cards as $card) :
		$OccupySandyCard = $card;

		get_template_part('card', $card->get_card_class());
	endforeach;
}

function get_the_occupy_sandy_card () { global $OccupySandyCard; return $OccupySandyCard; }

function get_occupy_sandy_possible_values_for ($fieldName, $params = array()) {
	$cards = get_occupy_sandy_cards($params);
	$ret = array();
	foreach ($cards as $card) :
		$value = NULL;
		if (method_exists($card, $fieldName)) :
			$value = $card->{$fieldName}();
		endif;

		if (!is_array($value)) :
			if (is_null($value)) :
				$value = array();
			else :
				$value = array($value);
			endif;
		endif;

		if (count($value) == 0) :
			$value[] = $card->field($fieldName);
		endif;

		foreach ($value as $idx => $v) :
			if (!is_numeric($idx)) :
				$i = urlencode($idx) . '/' . urlencode($v);
			else :
				$i = urlencode($v);
			endif;
			if (!isset($ret[$i])) : $ret[$i] = 0; endif;
			$ret[$i] += 1;
		endforeach;
	endforeach;
	return $ret;
}


