<?php
/**
 * ARLearn Data Collection Tasks
 */

elgg_register_event_handler('init', 'system', 'wespot_arlearn_init');

/**
 * Initialize the WeSpot ARLearn tasks plugin.
 *
 */
function wespot_arlearn_init() {

	// register a library of helper functions
	elgg_register_library('elgg:wespot_arlearn', elgg_get_plugins_path() . 'wespot_arlearn/lib/wespot_arlearn.php');
	elgg_register_library('elgg:wespot_arlearnservices', elgg_get_plugins_path() . 'wespot_arlearn/lib/arlearnservices.php');

	// ONLY GROUPS SHOULD HAVE ACCESS TO THIS DATA COLLECTION TASKS MODULE
	//$item = new ElggMenuItem('wespot_arlearn', elgg_echo('wespot_arlearn'), 'wespot_arlearn/all');
	//elgg_register_menu_item('site', $item);

	// Register a task handler, so we can have nice URLs
	elgg_register_page_handler('wespot_arlearn', 'wespot_arlearn_page_handler');

	// Register a url handler
	elgg_register_entity_url_handler('object', 'arlearntask_top', 'wespot_arlearn_url');
	elgg_register_entity_url_handler('object', 'arlearntask', 'wespot_arlearn_url');
	elgg_register_annotation_url_handler('arlearntask', 'wespot_arlearn_revision_url');

	// Register some actions (action = what's called by a form)
	$action_base = elgg_get_plugins_path() . 'wespot_arlearn/actions/wespot_arlearn';
	elgg_register_action('wespot_arlearn/edit', "$action_base/edit.php");
	elgg_register_action('wespot_arlearn/delete', "$action_base/delete.php");
	elgg_register_action('wespot_arlearn/upload', "$action_base/upload.php");

	// Extend the main css view
	elgg_extend_view('css/elgg', 'wespot_arlearn/css');

	// Register entity type for search
	elgg_register_entity_type('object', 'arlearntask');
	elgg_register_entity_type('object', 'arlearntask_top');

	// Register granular notification for this type
	register_notification_object('object', 'arlearntask', elgg_echo('wespot_arlearn:new'));
	register_notification_object('object', 'arlearntask_top', elgg_echo('wespot_arlearn:new'));
	elgg_register_plugin_hook_handler('notify:entity:message', 'object', 'task_notify_message');

	// add to groups - needs to be off by default, so when they turn it on, it setups game/group on ARLearn
//	add_group_tool_option('wespot_arlearn', elgg_echo('groups:enablewespot_arlearn'), false);
	elgg_extend_view('groups/tool_latest', 'wespot_arlearn/group_module');

	//add a widget
	//elgg_register_widget_type('wespot_arlearn', elgg_echo('wespot_arlearn'), elgg_echo('wespot_arlearn:widget:description'));
	elgg_register_widget_type('wespot_arlearn', elgg_echo('wespot_arlearn'), elgg_echo('wespot_arlearn:widget:description'), "groups");

	// Language short codes must be of the form "wespot_arlearn:key"
	// where key is the array key below
	elgg_set_config('wespot_arlearn', array(
		'title' => 'text',
		'description' => 'longtext',
		'tags' => 'tags',
		'task_type' => 'task_types',
	));

	// Stefaan said we may want to at least add a start date for tasks at some point
	// so left these here from the original tasks form
	// Just add in above.
	// See also the lib/wespot_arlearn.php prepare forms function.
	//'start_date' => 'date',
	//'end_date' => 'date',

	// menus
//	elgg_register_plugin_hook_handler('register', 'menu:owner_block', 'wespot_arlearn_owner_block_menu');
	elgg_register_plugin_hook_handler('register', 'menu:entity', 'wespot_arlearn_entity_menu_setup');

	// write permission plugin hooks
	elgg_register_plugin_hook_handler('permissions_check', 'object', 'wespot_arlearn_write_permission_check');
	elgg_register_plugin_hook_handler('container_permissions_check', 'object', 'wespot_arlearn_container_permission_check');

	// Access permissions
	//elgg_register_plugin_hook_handler('access:collections:write', 'all', 'wespot_arlearn_write_acl_plugin_hook');
	//elgg_register_plugin_hook_handler('access:collections:read', 'all', 'wespot_arlearn_read_acl_plugin_hook');

	// icon url override
	elgg_register_plugin_hook_handler('entity:icon:url', 'object', 'wespot_arlearn_icon_url_override');

	// register ecml views to parse
	elgg_register_plugin_hook_handler('get_views', 'ecml', 'wespot_arlearn_ecml_views_hook');

	// MB: NEW FOR WESPOT
	// HANDLE GROUP ADD/EDIT AND DELETE TO TALK TO ARLEARN TO SETUP GAMES,RUNS AND PEOPLE
	// This will happen before action completed
	elgg_register_plugin_hook_handler("action", "groups/edit", "groups_edit_task_action_hook");
 	elgg_register_plugin_hook_handler("action", "groups/delete", "groups_delete_task_action_hook");

	// This will happen before action completed
	elgg_register_plugin_hook_handler("action", "groups/join", "wespot_arlearn_group_join_action_hook");
 	elgg_register_plugin_hook_handler("action", "groups/leave", "wespot_arlearn_group_leave_action_hook");

 	// To show errors in file upload forms
 	elgg_register_js('upload', 'mod/wespot_arlearn/js/file.upload.js');

 	// To relayout items in collections (see view.php)
	elgg_register_css('custom_layout', 'mod/wespot_arlearn/css/layout.css');
	elgg_register_js('image_list', 'mod/wespot_arlearn/js/images.js');
	// To define's icons size
	elgg_register_css('icons_size', 'mod/wespot_arlearn/css/icons.css');
	// Fancybox
	elgg_register_css('fancybox', 'mod/wespot_arlearn/css/jquery.fancybox.css');
	elgg_register_js('fancybox', 'mod/wespot_arlearn/js/jquery.fancybox.pack.js');

	// Notifications from ARLearn server
	elgg_register_library('elgg:wespot_msg', elgg_get_plugins_path() . 'wespot_msg/lib/wespot_msg.php');
    elgg_register_library('elgg:wespot_arlearnmsgservices', elgg_get_plugins_path() . 'wespot_msg/lib/arlearnmsgservices.php');
 	elgg_register_js('google_channel', '//talkgadget.google.com/talkgadget/channel.js');
 	elgg_register_js('notifications', 'mod/wespot_arlearn/js/notifications.js');
}


/**
 * Dispatcher for wespot_arlearn.
 * URLs take the form of
 *  All wespot_arlearn:        wespot_arlearn/all
 *  User's wespot_arlearn:     wespot_arlearn/owner/<username>
 *  Friends' wespot_arlearn:   wespot_arlearn/friends/<username>
 *  View task:        wespot_arlearn/view/<guid>/<title>
 *  New task:         wespot_arlearn/add/<guid> (container: user, group, parent)
 *  Edit task:        wespot_arlearn/edit/<guid>
 *  History of task:  wespot_arlearn/history/<guid>
 *  Revision of task: wespot_arlearn/revision/<id>
 *  Group wespot_arlearn:      wespot_arlearn/group/<guid>/all
 *
 * Title is ignored
 *
 * @param array $task
 * @return bool
 */
function wespot_arlearn_page_handler($task) {
	elgg_load_library('elgg:wespot_arlearn');

	if (!isset($task[0])) {
		$task[0] = 'all';
	}

	elgg_push_breadcrumb(elgg_echo('groups'), 'groups/all');
	$group = elgg_get_page_owner_entity();
	if (elgg_instanceof($group, 'group')) {
		$tab_url = '';
		$phase = $_GET['phase'];
		if(!$phase) { $phase = get_entity($task[1])->phase; }
		if($phase) {
			$profiles = elgg_get_entities(array('types' => 'object', 'subtypes' => 'tabbed_profile', 'container_guid' => $group->guid));
			foreach ($profiles as $profile) {
				if($profile->order == $phase) {
					$tab_url = '/tab/' . $profile->guid;
					break;
				}
			}
		}
		elgg_push_breadcrumb($group->name, $group->getURL() . $tab_url);
	}
	
	$base_dir = elgg_get_plugins_path() . 'wespot_arlearn/pages/wespot_arlearn';

	$task_type = $task[0];
	switch ($task_type) {
		case 'update':
			include "$base_dir/update.php";
			break;
		case 'notification':
			include "$base_dir/channel.php";
			break;
		case 'owner':
			include "$base_dir/owner.php";
			break;
		case 'friends':
			include "$base_dir/friends.php";
			break;
		case 'view':
			set_input('guid', $task[1]);
			include "$base_dir/view.php";
			break;
		case 'add':
			set_input('guid', $task[1]);
			include "$base_dir/new.php";
			break;
		case 'add-item':
			set_input('guid', $task[1]);
			include "$base_dir/new-item.php";
			break;
		case 'edit':
			set_input('guid', $task[1]);
			include "$base_dir/edit.php";
			break;
		case 'group':
			include "$base_dir/owner.php";
			break;
		case 'history':
			set_input('guid', $task[1]);
			include "$base_dir/history.php";
			break;
		case 'revision':
			set_input('id', $task[1]);
			include "$base_dir/revision.php";
			break;
		case 'all':
			include "$base_dir/world.php";
			break;
		case 'csv':
			set_input('guid', $task[1]);
            $isRebuildRequested =(isset($task[2]) && $task[2] === 'rebuild')?true:false;
            set_input('isRebuildRequested', $isRebuildRequested);
			include "$base_dir/csv.php";
			break;            
		default:
			return false;
	}
	return true;
}

/**
 * Override the task url
 *
 * @param ElggObject $entity Page object
 * @return string
 */
function wespot_arlearn_url($entity) {
	$title = elgg_get_friendly_title($entity->title);
	return "wespot_arlearn/view/$entity->guid/$title";
}

/**
 * Override the task annotation url
 *
 * @param ElggAnnotation $annotation
 * @return string
 */
function wespot_arlearn_revision_url($annotation) {
	return "wespot_arlearn/revision/$annotation->id";
}

/**
 * Override the default entity icon for wespot_arlearn
 *
 * @return string Relative URL
 */
 // MB: modified original function to return different icons depending on task type.
function wespot_arlearn_icon_url_override($hook, $type, $returnvalue, $params) {

	$entity = $params['entity'];
	// Now I ignore the $size here
	// Instead, always the same image is returned and its size is adjusted using a CSS class.
	$type = $params['task_type'];
	$img_path = 'mod/wespot_arlearn/images/';
	if (elgg_instanceof($entity, 'object', 'arlearntask_top')) {
		$icons = array(
			'picture'=>'collection_pictures.png',
			'video'=>'collection_videos.png',
			'audio'=>'collection_audios.png',
			'text'=>'collection_texts.png',
			'numeric'=>'collection_numbers.png'
		);
		if (array_key_exists($type, $icons)) {
			return $img_path.$icons[$type];
		}
		return $img_path.'collection.png';		
	} else if (elgg_instanceof($entity, 'object', 'arlearntask')) {
		if ($type == 'picture') {
			return $img_path.'type_photo.png';
		} else if ($type == 'video') {
			return $img_path.'type_video.png';
		} else if ($type == 'audio') {
			return $img_path.'type_audio.png';
		} else if ($type == 'text') {
			return $img_path.'type_text.png';
		} else if ($type == 'numeric') {
			return $img_path.'type_numeric.png';
		}
	}
}

/**
 * Add a menu item to the user ownerblock
 */
function wespot_arlearn_owner_block_menu($hook, $type, $return, $params) {
	if (elgg_instanceof($params['entity'], 'user')) {
		$url = "wespot_arlearn/owner/{$params['entity']->username}";
		$item = new ElggMenuItem('wespot_arlearn', elgg_echo('wespot_arlearn:group'), $url);
		$return[] = $item;
	} else {
		if ($params['entity']->wespot_arlearn_enable != "no") {
			$url = "wespot_arlearn/group/{$params['entity']->guid}/all";
			$item = new ElggMenuItem('wespot_arlearn', elgg_echo('wespot_arlearn:group'), $url);
			$return[] = $item;
		}
	}

	return $return;
}

/**
 * Add links/info to entity menu particular to wespot_arlearn plugin
 */
function wespot_arlearn_entity_menu_setup($hook, $type, $return, $params) {
	if (elgg_in_context('widgets')) {
		return $return;
	}

	$entity = $params['entity'];
	$handler = elgg_extract('handler', $params, false);
	if ($handler != 'wespot_arlearn') {
		return $return;
	}

	$options = array(
		'name' => 'history',
		'text' => elgg_echo('wespot_arlearn:history'),
		'href' => "wespot_arlearn/history/$entity->guid",
		'priority' => 150,
	);
	$return[] = ElggMenuItem::factory($options);

    if (elgg_is_logged_in() && elgg_instanceof($params['entity'], 'object', 'arlearntask_top')) {
        $options = array(
                'name' => 'download_csv',
                'text' => "<span class=\"elgg-icon elgg-icon-download\"></span>",
                'href' => "wespot_arlearn/csv/$entity->guid",
                'priority' => 250,
                'title' => elgg_echo('wespot_arlearn:export:csv')
        );
        $return[] = ElggMenuItem::factory($options);
        
        $options = array(
                'name' => 'rebuild_csv',
                'text' => "<span class=\"elgg-icon elgg-icon-refresh\"></span>",
                'href' => "wespot_arlearn/csv/$entity->guid/rebuild",
                'priority' => 250,
                'title' => elgg_echo('wespot_arlearn:export:rebuild')
        );
        $return[] = ElggMenuItem::factory($options);

        $options = array(
                'name' => 'add_item',
                'text' => "<span class=\"elgg-icon elgg-icon-add\"></span>",
                'href' => "wespot_arlearn/add-item/$entity->guid",
                'priority' => 250,
                'title' => elgg_echo('wespot_arlearn:add:item')
        );
        $return[] = ElggMenuItem::factory($options);
    }
    
	return $return;
}

/**
* Returns a more meaningful message
*
* @param unknown_type $hook
* @param unknown_type $entity_type
* @param unknown_type $returnvalue
* @param unknown_type $params
*/
function task_notify_message($hook, $entity_type, $returnvalue, $params) {
	$entity = $params['entity'];
	$to_entity = $params['to_entity'];
	$method = $params['method'];
	if (($entity instanceof ElggEntity) && (($entity->getSubtype() == 'arlearntask_top') || ($entity->getSubtype() == 'arlearntask'))) {
		$descr = $entity->description;
		$title = $entity->title;
		//@todo why?
		$url = elgg_get_site_url() . "view/" . $entity->guid;
		$owner = $entity->getOwnerEntity();
		return $owner->name . ' ' . elgg_echo("wespot_arlearn:via") . ': ' . $title . "\n\n" . $descr . "\n\n" . $entity->getURL();
	}
	return null;
}

/**
 * Extend permissions checking to extend can-edit for write users.
 *
 * @param unknown_type $hook
 * @param unknown_type $entity_type
 * @param unknown_type $returnvalue
 * @param unknown_type $params
 */
function wespot_arlearn_write_permission_check($hook, $entity_type, $returnvalue, $params) {
	if (elgg_in_context('backend_access')) {
		// When doing changes in the background (e.g., updating content from ARLearn server)
		return true;
	}

	$subtype = $params['entity']->getSubtype();
	if ($subtype=='arlearntask_top') {
		$collection = $params['entity'];
		$user = $params['user'];
		return ($user->guid == $collection->owner_guid);
	} else if ($subtype=='arlearntask') {
		$task = $params['entity'];
		$user = $params['user'];
		$collection = get_entity($task->parent_guid);
		return ($user->guid == $task->owner_guid || $user->guid == $collection->owner_guid);
	}
	return $returnvalue;
}

/**
 * Extend container permissions checking to extend can_write_to_container for write users.
 *
 * @param unknown_type $hook
 * @param unknown_type $entity_type
 * @param unknown_type $returnvalue
 * @param unknown_type $params
 */
function wespot_arlearn_container_permission_check($hook, $entity_type, $returnvalue, $params) {
	if (elgg_in_context('backend_access')) { // Not sure when this function is called, but just in case.
		// When doing changes in the background (e.g., updating content from ARLearn server)
		return true;
	}

	if (elgg_get_context() == 'wespot_arlearn') {
		if (elgg_get_page_owner_guid()) {
			if (can_write_to_container(elgg_get_logged_in_user_guid(), elgg_get_page_owner_guid())) return true;
		}
		if ($task_guid = get_input('task_guid',0)) {
			$entity = get_entity($task_guid);
		} else if ($parent_guid = get_input('parent_guid',0)) {
			$entity = get_entity($parent_guid);
		}
		if ($entity instanceof ElggObject) {
			if (
					can_write_to_container(elgg_get_logged_in_user_guid(), $entity->container_guid)
					|| in_array($entity->write_access_id,get_access_list())
				) {
					return true;
			}
		}
	} // else

	return $returnvalue;
}

/**
 * Return views to parse for wespot_arlearn.
 *
 * @param unknown_type $hook
 * @param unknown_type $entity_type
 * @param unknown_type $return_value
 * @param unknown_type $params
 */
function wespot_arlearn_ecml_views_hook($hook, $entity_type, $return_value, $params) {
	$return_value['object/task'] = elgg_echo('item:object:arlearntask');
	$return_value['object/task_top'] = elgg_echo('item:object:arlearntask_top');

	return $return_value;
}


/************************************/
/** NEW FUNCTIONS ADDED FOR WESPOT **/
/************************************/

/**
 * Intercept the group delete event detect if Data collection module
 * was being used, then sent appropriate setup calls to ARLearn
 *
 */
function groups_delete_task_action_hook($hook, $entity_type, $returnvalue, $params) {
	elgg_load_library('elgg:wespot_arlearnservices');

	$group_guid = (int) get_input('guid');
	if (!$group_guid) {
		// backward compatible
		elgg_deprecated_notice("Use 'guid' for group delete action", 1.8);
		$group_guid = (int)get_input('group_guid');
	}
	$group = get_entity($group_guid);

	if (!$group->canEdit()) {
		return true;
	}

	if (($group) && ($group instanceof ElggGroup)) {
		$gamearray = elgg_get_entities(array('type' => 'object', 'subtype' => 'arlearngame', 'owner_guid' => $group_guid));
		debugWespotARLearn('DELETE GROUP GAME: '.print_r($gamearray, true));

		if ($gamearray === FALSE || count($gamearray) == 0) {
			// Don't delete from ARLEarn if there is no game
		} else {
			$game = $gamearray[0];
			$success = removeARLearnGame($group_guid);
			if ($success) {
				$game->delete();
				return true;
			} else {
				register_error(elgg_echo('wespot_arlearn:error:no_delete_game'));
				return false;
			}
		}
	}
}

/**
 * Intercept the group edit event detect if Data collection widget
 * enabled or disabled, then sent appropriate setup calls to ARLearn
 * return true if everything went fine, else false.
 */
function groups_edit_task_action_hook($hook, $entity_type, $returnvalue, $params) {

	elgg_load_library('elgg:wespot_arlearnservices');

	$group_guid = (int)get_input('group_guid');
	debugWespotARLearn('IN ADD?EDIT GROUP ID = '.print_r($group_guid, true));

	$tool_options = elgg_get_config('group_tool_options');
	debugWespotARLearn('GROUP TOOLS:params='.print_r($tool_options, true));
	$hasTasksModule = true;
	$isTasksOn = true;
	$taskOptionName = "";
/*
	if ($tool_options) {
		foreach ($tool_options as $group_option) {
			if ($group_option->name == "wespot_arlearn") {
				$hasTasksModule = true;
				$option_toggle_name = $group_option->name . "_enable";
				$taskOptionName = $option_toggle_name;
				$option_default = $group_option->default_on ? 'yes' : 'no';
				$nextOn = get_input($option_toggle_name, $option_default);
				if ($nextOn == 'yes') {
					$isTasksOn = true;
				}
				debugWespotARLearn('MODULE NAME = '.print_r($group_option->name, true));
				debugWespotARLearn('MODULE ON = '.print_r($nextOn, true));
			}
		}
	}
*/
	// EDITS
	if ($group_guid > 0) {
		$group = get_entity($group_guid);
		if ($group_guid && !$group->canEdit()) {
			register_error(elgg_echo("groups:cantedit"));
			forward(REFERER);
		}

		debugWespotARLearn('GROUP GAME OWNER ID=: '.print_r($group_guid, true));
		$gamearray = elgg_get_entities(array('type' => 'object', 'subtype' => 'arlearngame', 'owner_guid' => $group_guid));
		debugWespotARLearn('GROUP GAME: '.print_r($gamearray, true));

		if ($gamearray === FALSE || count($gamearray) == 0) {
			debugWespotARLearn('GAME DOES NOT EXISTS FOR GROUP');
			// This should be a game enable action
			if ($isTasksOn) {
				$success = initARLearnGame($group_guid, $group->name);
				if (!$success) {
					set_input($option_toggle_name, 'no');
					register_error(elgg_echo('wespot_arlearn:error:no_save_game'));
					return false;
				} else {
					return true;
				}
			} else {
				//Do nothing.
				//We don't have a game and the wespot_arlearn block is off, so the setting has not changed
			}
		} else {
			$game = $gamearray[0];
			debugWespotARLearn('GAME EXISTS FOR GROUP');
			debugWespotARLearn('isTasksOn='.print_r($isTasksOn, true));

			if (!$isTasksOn) {
				debugWespotARLearn('ABOUT TO REMOVE GAME FOR ID'.print_r($group_guid, true));

				$success = removeARLearnGame($group_guid);
				if ($success) {
					$game->delete();
					return true;
				} else {
					set_input($option_toggle_name, 'yes');
					register_error(elgg_echo('wespot_arlearn:error:no_delete_game'));
					return false;
				}
			} else {
				// Did they edit the title. If so, updte ARLearn.
				$name = $group->name;
				$newName = htmlspecialchars(get_input('name', '', false), ENT_QUOTES, 'UTF-8');

				debugWespotARLearn('name='.print_r($name, true));
				debugWespotARLearn('newName='.print_r($newName, true));

				if ($name != $newName) {
					debugWespotARLearn('About to update game and run');

					updateARLearnGame($group_guid, $newName, $game->arlearn_gameid, $game->arlearn_runid);
				}
				// Do nothing.
				// We already have a game and the wespot_arlearn block is on, so the setting has not changed
			}
		}

	// ADDS
	} else {
		//it's a new group
		//If the Group/Enquiry module changes it's checks for new groups, this needs to be changed to match

		// Checkes from Group Edit Code
		// Can this person create a new group?
		if ((elgg_get_plugin_setting('limited_groups', 'groups') != 'no')
				&& !$user->isAdmin()) {
			return true;
		}

		$group_name = htmlspecialchars(get_input('name', '', false), ENT_QUOTES, 'UTF-8');
		if ($group_name != "") {
			$user = elgg_get_logged_in_user_entity();
			$group = new ElggGroup();
			$group->access_id = ACCESS_PUBLIC;
			$group->save();

			elgg_set_page_owner_guid($group->guid);
			$group->join($user);
			add_to_river('river/group/create', 'create', $user->guid, $group->guid, $group->access_id);

			$group_guid =  $group->guid;
			debugWespotARLearn('NEW GROUP ID = '.print_r($group_guid, true));
			set_input('group_guid',$group_guid);
			if ($isTasksOn) {
				$success = initARLearnGame($group_guid, $group_name);
				if (!$success) {
					set_input($option_toggle_name, 'no');
					register_error(elgg_echo('wespot_arlearn:error:no_save_game'));
					return false;
				} else {
					return true;
				}
			}
		}
	}
	return true;
}

/**
 * Update the game and run name in ARLEarn for the group id and group name given
 * @param $group_giud the unique id off the group
 * @param $group_name the name of the group
 * @param $gameid the ARLearn gameid of the game to update
 * @param $runid the ARLearn runid to of the run to date
 * @return true if all goes well, else false;
 */
function updateARLearnGame($group_guid, $newName, $gameid, $runid) {
	if ($newName == "") {
		return false;
	}

	elgg_load_library('elgg:wespot_arlearnservices');

	$added = false;

	$gamearray = elgg_get_entities(array('type' => 'object', 'subtype' => 'arlearngame', 'owner_guid' => $group_guid));
	debugWespotARLearn('GROUP GAME: '.print_r($gamearray, true));

	if ($gamearray === FALSE || count($gamearray) == 0) {
		// Do nothing
	} else {
		$group = get_entity($group_guid);
		$teacherguid = $group->owner_guid;
		$teacherprovider = elgg_get_plugin_user_setting('provider', $teacherguid, 'elgg_social_login');
		$teacheroauth = str_replace("{$teacherprovider}_", '', elgg_get_plugin_user_setting('uid', $teacherguid, 'elgg_social_login'));
		$usertoken = createARLearnUserToken($teacherprovider, $teacheroauth);

		debugWespotARLearn('USER: '.print_r($usertoken, true));

		$teacher = get_entity(teacherguid);

		// check teacher known to ARLearn
		// Teacher should already have been checked when game created
		/*$exists = checkARLearnUserExists($teacherprovider, $teacheroauth);
		if (!$exists) {
			$results = createARLearnUser($teacherprovider, $teacheroauth, $teacher->email, $teacher->name);
			if ($results != false) {
				debugWespotARLearn('CHECK USER: '.print_r($results, true));
				$datareturned = json_decode($results);
				if (isset($datareturned->error)) {
					return false;
				}
			}
		}*/

		// update game on ARLEarn
		$results = editARLearnGame($usertoken, $gameid, $newName);
		if ($results != false) {
			debugWespotARLearn('UPDATE GAME: '.print_r($results, true));
			$datareturned = json_decode($results);
			if (!isset($datareturned->error)) {
				$results2 = editARLearnRun($usertoken, $gameid, $runid, $newName);
				if ($results2 != false) {
					debugWespotARLearn('UPDATE RUN: '.print_r($results2, true));
					$datareturned2 = json_decode($results2);
					if (!isset($datareturned2->error)) {
						return true;
					}
				}
			}
		}
	}

	return false;
}


/**
 * Intialise a new game and run with ARLEarn for the group given
 * @param $group_giud the unique id off the group
 * @param $group_name the name of the group
 * @return true if all goes well, else false;
 */
function initARLearnGame($group_guid, $group_name) {

	if ($group_name == "") {
		return false;
	}

	elgg_load_library('elgg:wespot_arlearnservices');

	$added = false;

	$gamearray = elgg_get_entities(array('type' => 'object', 'subtype' => 'arlearngame', 'owner_guid' => $group_guid));
	debugWespotARLearn('GROUP GAME: '.print_r($gamearray, true));

	if ($gamearray === FALSE || count($gamearray) == 0) {
		$teacherguid = get_loggedin_userid();
		$teacherprovider = elgg_get_plugin_user_setting('provider', $teacherguid, 'elgg_social_login');
		$teacheroauth = str_replace("{$teacherprovider}_", '', elgg_get_plugin_user_setting('uid', $teacherguid, 'elgg_social_login'));
		$usertoken = createARLearnUserToken($teacherprovider, $teacheroauth);

		$teacher = get_entity(teacherguid);

		// check teacher known to ARLearn
		$exists = checkARLearnUserExists($teacherprovider, $teacheroauth);
		if (!$exists) {
			$results = createARLearnUser($teacherprovider, $teacheroauth, $teacher->email, $teacher->name);
			if ($results != false) {
				debugWespotARLearn('CHECK USER: '.print_r($results, true));
				$datareturned = json_decode($results);
				if (isset($datareturned->error)) {
					return false;
				}
			}
		}

		// register game on ARLEarn
		$results = createARLearnGame($usertoken, $group_name);
		if ($results != false) {
			debugWespotARLearn('ADD GAME: '.print_r($results, true));
			$datareturned = json_decode($results);
			if (!isset($datareturned->error)) {
				$gameid = $datareturned->gameId;

				// Register run on ARLEarn
				$results = createARLearnRun($usertoken, $gameid, $group_name);
				if ($results != false) {
					debugWespotARLearn('ADD WIDGET: '.print_r($results, true));

					$datareturned = json_decode($results);
					if (!isset($datareturned->error)) {
						$runid = $datareturned->runId;

						// get a list of students in the group and add all.
						$students = elgg_get_entities_from_relationship(array(
							'relationship' => 'member',
							'relationship_guid' => $group_guid,
							'inverse_relationship' => true,
							'type' => 'user',
						));

						if (isset($students) && count($students) > 0) {
							debugWespotARLearn('STUDENTS LIST: '.print_r($students, true));

							foreach($students as $student) {
								debugWespotARLearn('STUDENT: '.print_r($student, true));

								$studentid = $student->guid;
								$provider = elgg_get_plugin_user_setting('provider', $studentid, 'elgg_social_login');

								debugWespotARLearn('PROVIDER FOR STUDENT: '.print_r($provider, true));

								$oauth = str_replace("{$provider}_", '', elgg_get_plugin_user_setting('uid', $studentid, 'elgg_social_login'));

								if ( (isset($oauth) && $oauth != "") && (isset($provider) && $provider != -1)) {
									// check student exists.
									$exists = checkARLearnUserExists($provider, $oauth);
									$addtorun = false;
									if (!$exists) {
										$results = createARLearnUser($provider, $oauth, $student->email, $student->name);
										if ($results != false) {
											debugWespotARLearn('CREATE USER: '.print_r($results, true));
											$datareturned = json_decode($results);
											if (isset($datareturned->error)) {
												return false;
											} else {
												$addtorun = true;
											}
										}
									} else {
										$addtorun = true;
									}

									// adding student to run.
									if ($addtorun) {
										$results = addUserToRun($usertoken, $runid, $provider, $oauth);
										if ($results != false) {
											debugWespotARLearn('ADD STUDENT: '.print_r($results, true));
											$datareturned = json_decode($results);
											if (isset($datareturned->error)) {
												debugWespotARLearn('STUDENT ADDED: '.print_r($results, true));
												//return false;
											}
										} else {
											debugWespotARLearn('STUDENT NOT ADDED: '.print_r($results, true));
										}
									}
								}
							}
						} else {
							// If we get here it will be a new game not yet saved,
							// so just add the teacher as they will become the first student
							// Already checked if teacher exists
							// adding student to run.
							$results = addUserToRun($usertoken, $runid, $teacherprovider, $teacheroauth);
							if ($results != false) {
								debugWespotARLearn('ADD TEACHER AS STUDENT: '.print_r($results, true));
								$datareturned = json_decode($results);
								if (isset($datareturned->error)) {
									debugWespotARLearn('TEACHER ADDED ERROR: '.print_r($results, true));
									//return false;
								}
							} else {
								debugWespotARLearn('TEACHER NOT ADDED: '.print_r($results, true));
							}
						}

						$object = new ElggObject();
						$object->subtype = "arlearngame";

						//MB: LOGGED IN USER ACCESS ONLY - CHANGED TO PUBLIC FOR NOW
						//$object->access_id = 1; // LOGGED IN USERS
						$object->access_id = ACCESS_PUBLIC;

						$object->arlearn_gameid = $gameid;
						$object->arlearn_runid = $runid;
						$object->owner_guid = $group_guid;
						$object->container_guid = $group_guid;
						$object->save();

						$added = true;

						debugWespotARLearn('ADDING GAME TO GROUP: '.print_r($object, true));
					}
				}
			}
		}
	}

	return $added;
}

/**
 * Delete the ARLearn game that corresponds to the group being deleted or option being switched off.
 * @param $group_guid the group inquiry id of the group to remove the game for
 * @return return true if game deleted from ARLearn, else false.
 */
function removeARLearnGame($group_guid) {
	elgg_load_library('elgg:wespot_arlearnservices');
	debugWespotARLearn('GROUP removeARLearnGame: '.print_r($group_guid, true));

	$deleted = false;

	$gamearray = elgg_get_entities(array('type' => 'object', 'subtype' => 'arlearngame', 'owner_guid' => $group_guid));
	debugWespotARLearn('GROUP GAME: '.print_r($gamearray, true));

	if ($gamearray === FALSE || count($gamearray) == 0) {
		// do nothing.
	} else {
		$game = $gamearray[0];
		$gameid = $game->arlearn_gameid;

		$guid = get_loggedin_userid();
		$provider = elgg_get_plugin_user_setting('provider', $guid, 'elgg_social_login');
		$oauth = str_replace("{$provider}_", '', elgg_get_plugin_user_setting('uid', $guid, 'elgg_social_login'));
		$usertoken = createARLearnUserToken($provider, $oauth);

		$deleted = deleteARLearnGame($usertoken, $gameid);
	}

	return $deleted;
}

/**
 * Update ARLearn when user joins the group inquiry (if group has a game)
 * @return true if the update was successful, or not required, else false.
 */
function wespot_arlearn_group_join_action_hook($hook, $entity_type, $returnvalue, $params) {

	elgg_load_library('elgg:wespot_arlearnservices');

	debugWespotARLearn('GROUP JOIN: '.$params);

	$added = false;
	$group_guid = get_input('group_guid');

	$gamearray = elgg_get_entities(array('type' => 'object', 'subtype' => 'arlearngame', 'owner_guid' => $group_guid));
	debugWespotARLearn('GAME: '.print_r($gamearray, true));
	if ($gamearray === FALSE || count($gamearray) == 0) {
		return true;
	} else {
		$group = get_entity($group_guid);
		debugWespotARLearn('GROUP: '.$group->name);

		$owner_giud = $group->owner_guid;
		$ownerprovider = elgg_get_plugin_user_setting('provider', $owner_giud, 'elgg_social_login');
		$owneroauth = str_replace("{$ownerprovider}_", '', elgg_get_plugin_user_setting('uid', $owner_giud, 'elgg_social_login'));
		$usertoken = createARLearnUserToken($ownerprovider, $owneroauth);

		$game = $gamearray[0];
		$gameid = $game->arlearn_gameid;
		$runid = $game->arlearn_runid;

		$user_guid = get_input('user_guid');
		$user = get_entity($user_guid);
		$provider = elgg_get_plugin_user_setting('provider', $user_guid, 'elgg_social_login');
		$oauth = str_replace("{$provider}_", '', elgg_get_plugin_user_setting('uid', $user_guid, 'elgg_social_login'));

		if ($runid != "" && (isset($oauth) && $oauth != "") && (isset($provider) && $provider != -1)) {
			// check student exists.
			$exists = checkARLearnUserExists($provider, $oauth);
			$addtorun = false;
			if (!$exists) {
				$results = createARLearnUser($provider, $oauth, $user->email, $user->name);
				if ($results != false) {
					debugWespotARLearn('CREATE USER: '.print_r($results, true));
					$datareturned = json_decode($results);
					if (isset($datareturned->error)) {
						return false;
					} else {
						$addtorun = true;
					}
				}
			} else {
				$addtorun = true;
			}

			// adding student to run.
			if ($addtorun && $runid) {
				$results = addUserToRun($usertoken, $runid, $provider, $oauth);
				if ($results != false) {
					debugWespotARLearn('ADD NEW STUDENT: '.print_r($results, true));
					$datareturned = json_decode($results);
					if (isset($datareturned->error)) {
						debugWespotARLearn('NEW STUDENT ADDED: '.print_r($results, true));
						return false;
					}
				} else {
					debugWespotARLearn('STUDENT NOT ADDED: '.print_r($results, true));
					return false;
				}
			} else {
				return false;
			}
		}
	}

	return true;
}

/**
 * Update ARLearn when user leaves the group inquiry (if group has a game)
 * @return true if the update was successful, or not required, else false.
 */
function wespot_arlearn_group_leave_action_hook($hook, $entity_type, $returnvalue, $params) {

	elgg_load_library('elgg:wespot_arlearnservices');

	debugWespotARLearn('GROUP LEAVE: '.$params);

	$group_guid = get_input('group_guid');

	$gamearray = elgg_get_entities(array('type' => 'object', 'subtype' => 'arlearngame', 'owner_guid' => $group_guid));
	debugWespotARLearn('GAME: '.print_r($gamearray, true));

	if ($gamearray === FALSE || count($gamearray) == 0) {
		return true;
	} else {
		$group = get_entity($group_guid);

		$game = $gamearray[0];
		$gameid = $game->arlearn_gameid;
		$runid = $game->arlearn_runid;

		if (isset($runid) && $runid != "") {
			$owner_giud = $group->owner_guid;
			$ownerprovider = elgg_get_plugin_user_setting('provider', $owner_giud, 'elgg_social_login');
			$owneroauth = str_replace("{$ownerprovider}_", '', elgg_get_plugin_user_setting('uid', $owner_giud, 'elgg_social_login'));
			$usertoken = createARLearnUserToken($ownerprovider, $owneroauth);

			if (isset($usertoken) && $usertoken != "") {
				$user_guid = get_input('user_guid');
				$user = get_entity($user_guid);
				$provider = elgg_get_plugin_user_setting('provider', $user_guid, 'elgg_social_login');
				$oauth = str_replace("{$provider}_", '', elgg_get_plugin_user_setting('uid', $user_guid, 'elgg_social_login'));

				if ((isset($oauth) && $oauth != "") && (isset($provider) && $provider != -1)) {
					$result = removeUserFromRun($usertoken, $runid, $provider, $oauth);
					if ($results != false) {
						debugWespotARLearn('REMOVE USER: '.print_r($results, true));

						$datareturned = json_decode($results);
						if (isset($datareturned->error)) {
							return false;
						}
					}
				}
			}
		}
	}
	return true;
}