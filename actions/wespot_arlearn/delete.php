<?php
/**
 * Remove an ARLearn data collection task
 */

elgg_load_library('elgg:wespot_arlearnservices');
global $debug_wespot_arlearn;
    $debug_wespot_arlearn = true;

$guid = get_input('guid');
$task = get_entity($guid);

if ($task) {
	if ($task->canEdit()) {
		$container = get_entity($task->container_guid);

		$teacherguid = get_loggedin_userid();
		$teacherprovider = elgg_get_plugin_user_setting('provider', $teacherguid, 'elgg_social_login');
		$teacheroauth = str_replace("{$teacherprovider}_", '', elgg_get_plugin_user_setting('uid', $teacherguid, 'elgg_social_login'));
		$usertoken = createARLearnUserToken($teacherprovider, $teacheroauth);

		// TELL ARLEARN
		$results = deleteARLearnTaskTop($usertoken, $task->arlearn_gameid, $task->arlearn_id);

		if ($results != false) {
			$datareturned = json_decode($results);
			debugWespotARLearn('DELETE TASK RETURNED: '.print_r($datareturned, true));

			if (!isset($datareturned->error)) {
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
