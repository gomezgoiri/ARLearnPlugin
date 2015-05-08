<?php
/**
 * Create a new ARLearn item in the collection task
 */

gatekeeper();

$collection_guid = (int) get_input('guid');
$collection = get_entity($collection_guid);


$parent_guid = 0;
$task_owner = '';
if (elgg_instanceof($collection, 'object')) {
	$parent_guid = $collection->getGUID();
	$task_owner = $collection->getContainerEntity();
}

elgg_set_page_owner_guid($task_owner->getGUID());


$container = elgg_get_page_owner_entity();
elgg_push_breadcrumb(elgg_echo('wespot_arlearn:owner', array($container->name)), "wespot_arlearn/group/$container->guid/all");
elgg_push_breadcrumb($collection->title, "wespot_arlearn/view/$collection->guid/$collection->title");
$title = elgg_echo('wespot_arlearn:add:item');
elgg_push_breadcrumb($title);

$vars = array(
	'parent_guid' => $parent_guid,
    'guid' => $collection_guid
);
$content = elgg_view_form('wespot_arlearn/add_item', array(
		'enctype' => 'multipart/form-data',
		'action' => 'action/wespot_arlearn/upload'
	), $vars);

$body = elgg_view_layout('content', array(
	'filter' => '',
	'content' => $content,
	'title' => $title,
));

echo elgg_view_page($title, $body);
