<?php
/**
 * View a single ARLearn data collection task
 */

$task_guid = get_input('guid');
$task = get_entity($task_guid);
if (!$task) {
	forward();
}

elgg_set_page_owner_guid($task->getContainerGUID());

group_gatekeeper();

$container = elgg_get_page_owner_entity();
if (!$container) {
}

$title = $task->title;

if (elgg_instanceof($container, 'group')) {
	elgg_push_breadcrumb(elgg_echo('wespot_arlearn:owner', array($container->name)), "wespot_arlearn/group/$container->guid/all");
} else {
	elgg_push_breadcrumb(elgg_echo('wespot_arlearn:owner', array($container->name)), "wespot_arlearn/owner/$container->username");
}
wespot_arlearn_prepare_parent_breadcrumbs($task);
elgg_push_breadcrumb($title);

$group = get_entity(elgg_get_page_owner_guid());
//if (elgg_get_logged_in_user_guid() == $group->owner_guid) {
if ($group->canEdit()) {
	elgg_register_title_button();
}

//$content = elgg_view_entity($task, array('full_view' => true));

$task_annotation = $task->getAnnotations('arlearntask_top', 1, 0, 'desc');
if ($task_annotation) {
	$task_annotation = $task_annotation[0];
}

$content = elgg_view('object/arlearntask_top', array(
	'entity' => $task,
	'revision' => $task_annotation,
	'full_view' => true,
));


$children = elgg_get_entities_from_metadata(array(
	'type' => 'object',
	'subtype' => 'arlearntask',
	'metadata_name' => 'parent_guid',
	'metadata_value' => $task_guid,
	'limit' => 0,
));
$childrenCount = count($children);

$content .= '<div style="width:100%;clear:both;float:left;margin-top:10px;font-weight:bold;border-bottom:1px solid gray">'.elgg_echo('item:object:arlearntask').': '.$childrenCount.'</div>';

$content .= elgg_list_entities_from_metadata(array(
	'type' => 'object',
	'subtype' => 'arlearntask',
	'metadata_name' => 'parent_guid',
	'metadata_value' => $task_guid,
	'limit' => 10,
	'pagination' => true,
));

$body = elgg_view_layout('content', array(
	'filter' => '',
	'content' => $content,
	'title' => $title,
	'sidebar' => elgg_view('wespot_arlearn/sidebar/navigation'),
));

echo elgg_view_page($title, $body);
