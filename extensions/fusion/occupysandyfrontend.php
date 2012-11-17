<?php
require_once('occupysandycard.class.php');

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

		$primeClass = reset(explode(" ", $card->get_card_class()));
		get_template_part('card', $primeClass);
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


