<?php
/**
 * Create or edit an ARLearn data collection task
 */

elgg_load_library('elgg:wespot_arlearnservices');

$variables = elgg_get_config('wespot_arlearn');
$input = array();
foreach ($variables as $name => $type) {
	$input[$name] = get_input($name);
	if ($name == 'title') {
		$input[$name] = strip_tags($input[$name]);
	}
	if ($type == 'tags') {
		$input[$name] = string_to_tag_array($input[$name]);
	}
}

// Get guids
$task_guid = (int)get_input('task_guid');
$container_guid = (int)get_input('container_guid');
$parent_guid = (int)get_input('parent_guid');

elgg_make_sticky_form('arlearntask');

if (!$input['title']) {
	register_error(elgg_echo('wespot_arlearn:error:no_title'));
	forward(REFERER);
}

if (!$input['task_type']) {
	register_error(elgg_echo('wespot_arlearn:error:no_type'));
	forward(REFERER);
}

//EDIT
if ($task_guid) {
	$task = get_entity($task_guid);
	if (!$task || !$task->canEdit()) {
		register_error(elgg_echo('wespot_arlearn:error:no_save'));
		forward(REFERER);
	}
	$new_task = false;

//ADD
} else {
	$task = new ElggObject();
	if ($parent_guid) {
		$task->subtype = 'arlearntask';
	} else {
		$task->subtype = 'arlearntask_top';
	}
	$new_task = true;
}

if (sizeof($input) > 0) {
	foreach ($input as $name => $value) {
		$task->$name = $value;
		//echo "$task->$name = $value";
	}
}

$group_guid = $container_guid;
$group = get_entity($group_guid);

// need to add check to make sure user can write to container
$task->container_guid = $container_guid;

// THESE ARE NOT SELECTABLE IN THE FORM, SO SET THEM HERE
$task->write_access_id = ACCESS_PRIVATE;

//MB: GROUP LEVEL ACCESS ONLY - CHANGED TO PUBLIC FOR NOW
//$task->access_id=$group->group_acl; //owner group only
$task->access_id= ACCESS_PUBLIC;

if ($parent_guid) {
	$task->parent_guid = $parent_guid;
}

//SEND TO ARLEARN
$game = elgg_get_entities(array('type' => 'object', 'subtype' => 'arlearngame', 'owner_guid' => $group_guid));
debugWespotARLearn('GAME: '.print_r($game, true));
$gameid = $game[0]->arlearn_gameid;

debugWespotARLearn('GAME ID: '.$gameid);

$teacherguid = get_loggedin_userid();
$teacherprovider = elgg_get_plugin_user_setting('provider', $teacherguid, 'elgg_social_login');
$teacheroauth = str_replace("{$teacherprovider}_", '', elgg_get_plugin_user_setting('uid', $teacherguid, 'elgg_social_login'));
$usertoken = createARLearnUserToken($teacherprovider, $teacheroauth);

// should not need to do this as they should have been checked and added at game creation
/*
$exists = checkARLearnUserExists($teacherprovidercode, $teacheroauth);
if (!$exists) {
	$results = createARLearnUser($teacherprovidercode, $teacheroauth);
	if ($results != false) {
		debugWespotARLearn('CHECK USER: '.print_r($results, true));

		$datareturned = json_decode($results);
		if (isset($datareturned->error)) {
			return false;
		}
	}
}
*/

$results = editARLearnTask($usertoken, $gameid, $input['title'], $input['description'], $input['task_type'], $task_guid);
if ($results != false) {

	debugWespotARLearn('ADD/EDIT TASK: '.print_r($results, true));
	$datareturned = json_decode($results);

	if (!isset($datareturned->error)) {

		$generalitemid = $datareturned->id;
		$task->arlearn_id = $generalitemid;
		$task->arlearn_gameid = $gameid;
		$task->task_type = $input['task_type'];

		if ($task->save()) {

			elgg_clear_sticky_form('arlearntask');

			// Now save description as an annotation
			$task->annotate('arlearntask', $task->description, $task->access_id);

			system_message(elgg_echo('wespot_arlearn:saved'));

			if ($new_task) {
				add_to_river('river/object/arlearntask_top/create', 'create', $task->owner_guid, $task->guid);
			} else {
				add_to_river('river/object/arlearntask_top/create', 'update', $task->owner_guid, $task->guid);
			}

			forward($task->getURL());
		} else {
			register_error(elgg_echo('wespot_arlearn:error:no_save_task'));
			forward(REFERER);
		}
	} else {
		register_error(elgg_echo('wespot_arlearn:error:no_save_task'));
		forward(REFERER);
	}
} else {
	register_error(elgg_echo('wespot_arlearn:error:no_save_task'));
	forward(REFERER);
}


