<?php


$force = isset($_GET['force']);

if (isset($_GET['guid'])) {
	checkARLearnForTaskChildren($_GET['guid'], $force);
} else if (isset($_GET['runid'])) {
	checkARLearnForRunId($_GET['runid'], $force);
} else {
	$gamearray = elgg_get_entities(array('type' => 'object', 'subtype' => 'arlearngame', 'limit'=> 0));

	if ($gamearray === FALSE || count($gamearray) == 0) {
		echo 'No game was found in Elgg\'s database.';
	} else {
		echo "[";
		$first = true;
		foreach ($gamearray as $game) {
			if ($first)
				$first = false;
			else
				echo ', ';

			echo $game->guid;
		}
		echo "]";
	}
}

?>
