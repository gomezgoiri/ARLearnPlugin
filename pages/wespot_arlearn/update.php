<?php


$force = isset($_GET['force']);

if (isset($_GET['guid'])) { // Update function to be called by the script.
	checkARLearnForGame($_GET['guid'], $force);
} else if (isset($_GET['runid'])) { // Intended to ease things when triggering updates manually
	checkARLearnForRunId($_GET['runid'], $force);
} else if (isset($_GET['cguid'])) { // Intended to ease things when triggering updates manually
	checkARLearnForCollection($_GET['cguid'], $force); 
} else if (isset($_GET['iguid'])) { // Intended to ease things when triggering updates manually
	checkARLearnForInquiry($_GET['iguid'], $force); 
} else { // To provide game IDs to the script.
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
