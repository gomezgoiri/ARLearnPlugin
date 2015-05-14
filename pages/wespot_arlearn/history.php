<?php
/**
 * History of revisions of an ARLearn data collection task
 */

$task_guid = get_input('guid');
$task = get_entity($task_guid);

if (!$task) {
	elgg_load_library('elgg:wespot_arlearnservices');
	debugWespotARLearn("The entity with guid $task_guid could not be retrieved.");
	register_error(elgg_echo('wespot_arlearn:revision:status:failure'));
	forward(REFERER);
}


$container = $task->getContainerEntity();
elgg_set_page_owner_guid($task->getContainerGUID());

if (elgg_instanceof($container, 'group')) {
	elgg_push_breadcrumb(elgg_echo('wespot_arlearn:owner', array($container->name)), "wespot_arlearn/group/$container->guid/all");
} else {
	elgg_push_breadcrumb(elgg_echo('wespot_arlearn:owner', array($container->name)), "wespot_arlearn/owner/$container->username");
}
wespot_arlearn_prepare_parent_breadcrumbs($task);
elgg_push_breadcrumb($task->title, $task->getURL());
elgg_push_breadcrumb(elgg_echo('wespot_arlearn:history'));

$title = $task->title . ": " . elgg_echo('wespot_arlearn:history');

$content = list_annotations($task_guid, 'arlearntask', 20, false);

$body = elgg_view_layout('content', array(
	'filter' => '',
	'content' => $content,
	'title' => $title,
	'sidebar' => elgg_view('wespot_arlearn/sidebar/navigation', array('arlearntask' => $task)),
));

echo elgg_view_page($title, $body);
