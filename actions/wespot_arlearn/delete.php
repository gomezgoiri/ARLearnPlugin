<?php
/**
 * Remove an ARLearn data collection task
 */


function getUserToken() {
	$teacherguid = get_loggedin_userid();
	$teacherprovider = elgg_get_plugin_user_setting('provider', $teacherguid, 'elgg_social_login');
	$teacheroauth = str_replace("{$teacherprovider}_", '', elgg_get_plugin_user_setting('uid', $teacherguid, 'elgg_social_login'));
	return createARLearnUserToken($teacherprovider, $teacheroauth);
}


elgg_load_library('elgg:wespot_arlearnservices');

$guid = get_input('guid');
$obj = get_entity($guid);

if ($obj) {
	if ($obj->canEdit()) {
	    $subtype = get_subtype_from_id($obj->subtype);

	    if ($subtype=='arlearntask_top') {
			$container = get_entity($obj->container_guid);
			$usertoken = getUserToken();

			// Warn ARLearn
			$results = deleteARLearnTaskTop($usertoken, $obj->arlearn_gameid, $obj->arlearn_id);

			if ($results != false) {
				$datareturned = json_decode($results);
				if (!isset($datareturned->error)) {
					if ($obj->delete()) {
						debugWespotARLearn('Collection successfully deleted (guid: '.$guid.').');
						system_message(elgg_echo('wespot_arlearn:delete:success'));
	                    elgg_trigger_event('delete', 'annotation_from_ui', $obj);
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
	    } else if($subtype=='arlearntask') {
			//debugWespotARLearn('DELETE TASK: '.print_r($obj->arlearnid, true));
			$usertoken = getUserToken();

			// Warn ARLearn
			$results = deleteARLearnTask($usertoken, $obj->arlearnid); // Fields in the object: arlearnrunid, arlearnid
			if ($results != false) {
				$datareturned = json_decode($results);
				if (!isset($datareturned->error)) {
					debugWespotARLearn('Revoking item in collection (resultId: '.$obj->arlearnid.').');
			        $obj->disable();
        			$obj->save();
        			system_message(elgg_echo('wespot_arlearn:delete:success'));
        			forward(REFERER);
				}
			}
	    }
	}
}

register_error(elgg_echo('wespot_arlearn:delete:failure'));
forward(REFERER);
