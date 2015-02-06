<?php

	$result ='';
	$result .= "<select name='task_type' id='task_type' class='elgg-input-dropdown elgg-input-access'>\n";
	$result .= "<option value=''>".elgg_echo("wespot_arlearn:task_type_")."</option>\n";

	if ($vars['value'] == 'picture') {
		$result .= "<option selected value='picture'>".elgg_echo("wespot_arlearn:task_type_0")."</option>\n";
	} else {
		$result .= "<option value='picture'>".elgg_echo("wespot_arlearn:task_type_0")."</option>\n";
	}

	if ($vars['value'] == 'video') {
		$result .= "<option selected value='video'>".elgg_echo("wespot_arlearn:task_type_1")."</option>\n";
	} else {
		$result .= "<option value='video'>".elgg_echo("wespot_arlearn:task_type_1")."</option>\n";
	}

	if ($vars['value'] == 'audio') {
		$result .= "<option selected value='audio'>".elgg_echo("wespot_arlearn:task_type_2")."</option>\n";
	} else {
		$result .= "<option value='audio'>".elgg_echo("wespot_arlearn:task_type_2")."</option>\n";
	}

	if ($vars['value'] == 'text') {
		$result .= "<option selected value='text'>".elgg_echo("wespot_arlearn:task_type_3")."</option>\n";
	} else {
		$result .= "<option value='text'>".elgg_echo("wespot_arlearn:task_type_3")."</option>\n";
	}

	if ($vars['value'] == 'numeric') {
		$result .= "<option selected value='numeric'>".elgg_echo("wespot_arlearn:task_type_4")."</option>\n";
	} else {
		$result .= "<option value='numeric'>".elgg_echo("wespot_arlearn:task_type_4")."</option>\n";
	}

	$result .= "</select>";

	echo $result;
?>