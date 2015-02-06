<?php
/**
 * Remove an ARLearn data collection task
 */

elgg_load_library('elgg:wespot_arlearnservices');

$guid = get_input('guid');
$task = get_entity($guid);
if ($task) {
	if ($task->canEdit()) {
		$container = get_entity($task->container_guid);

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

		/*if ($task->delete()) {
			system_message(elgg_echo('wespot_arlearn:delete:success'));
			if ($parent) {
				if ($parent = get_entity($parent)) {
					forward($parent->getURL());
				}
			}
			if (elgg_instanceof($container, 'group')) {
				forward("wespot_arlearn/group/$container->guid/all");
			} else {
				forward("wespot_arlearn/owner/$container->username");
			}
		}*/

		// TELL ARLEARN
		$results = deleteARLearnTask($usertoken, $task->arlearn_gameid, $task->arlearn_id);
		debugWespotARLearn('DELETE RESULTS: '.print_r($results, true));

		if ($results != false) {
			debugWespotARLearn('DELETE TASK: '.print_r($results, true));

			$datareturned = json_decode($results);
			debugWespotARLearn('DELETE TASK RETURNED: '.print_r($datareturned, true));

			if (!isset($datareturned->error)) {

				// Bring all child elements forward

				// This is not correct for ARLearn.
				// If task deleted then results should be deleted.
				/*$parent = $task->parent_guid;
				$children = elgg_get_entities_from_metadata(array(
					'metadata_name' => 'parent_guid',
					'metadata_value' => $task->getGUID()
				));
				if ($children) {
					foreach ($children as $child) {
						$child->parent_guid = $parent;
					}
				}*/

				if ($task->delete()) {
					system_message(elgg_echo('wespot_arlearn:delete:success'));
                    elgg_trigger_event('delete', 'annotation_from_ui', $task);
					if ($parent) {
						if ($parent = get_entity($parent)) {
							forward($parent->getURL());
						}
					}
					if (elgg_instanceof($container, 'group')) {
						forward("wespot_arlearn/group/$container->guid/all");
					} else {
						forward("wespot_arlearn/owner/$container->username");
					}
				}
			}
		}
	}
}

register_error(elgg_echo('wespot_arlearn:delete:failure'));
forward(REFERER);
