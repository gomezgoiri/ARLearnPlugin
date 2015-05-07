<?php
/**
 * View for an ARLearn data collection task object
 *
 * @package ElggPages
 *
 * @uses $vars['entity']    The task object
 * @uses $vars['full_view'] Whether to display the full view
 * @uses $vars['revision']  This parameter not supported by elgg_view_entity()
 */

elgg_load_library('elgg:wespot_arlearn');
elgg_load_js('elgg:wespot_arlearn');

$full = elgg_extract('full_view', $vars, FALSE);
$task = elgg_extract('entity', $vars, FALSE);
$revision = elgg_extract('revision', $vars, FALSE);

if (!$task) {
	return TRUE;
}

if ($revision) {
	$annotation = $revision;
} else {
	$annotation = $task->getAnnotations('arlearntask', 1, 0, 'desc');
	if ($annotation) {
		$annotation = $annotation[0];
	}
}

$metadata = '';
$size = 'small';
if (elgg_in_context('widgets')) {
	$size = 'tiny';
}

$task_icon = elgg_view('wespot_arlearn/icon', array('annotation' => $annotation, 'size' => $size, 'task_type' => $task->task_type));


$owner = $vars['entity']->getOwnerEntity();
$ownertxt = elgg_echo('unknown');
if ($owner)
	$ownertxt = "<a href=\"" . $owner->getURL() . "\">" . $owner->name ."</a>";
$date = elgg_view_friendly_time($vars['entity']->time_created);
$editor_text = elgg_echo('entity:default:strapline', array($date, $ownertxt));

$tags = elgg_view('output/tags', array('tags' => $task->tags));
$categories = elgg_view('output/categories', $vars);

$comments_count = $task->countComments();
//only display if there are commments
if ($comments_count != 0 && !$revision) {
	$text = elgg_echo("comments") . " ($comments_count)";
	$comments_link = elgg_view('output/url', array(
		'href' => $task->getURL() . '#task-comments',
		'text' => $text,
		'is_trusted' => true,
	));
} else {
	$comments_link = '';
}

$metadata = elgg_view_menu('entity', array(
	'entity' => $vars['entity'],
	'handler' => 'wespot_arlearn',
	'sort_by' => 'priority',
	'class' => 'elgg-menu-hz',
));

$subtitle = "$editor_text $comments_link $categories";

// do not show the metadata and controls in widget view
if ($revision) {
	$metadata = '';
}
// do not show the metadata and controls if you are not the owner.
// need to only show voting possibly? But need to figure out how to override the menu for Results items only
//if ($task->owner_guid != get_loggedin_userid()) {
//	$metadata = '';
//}

if ($full) {
	$body = elgg_view('output/longtext', array('value' => $annotation->value));
	$params = array(
		'entity' => $task,
		'metadata' => $metadata,
		'subtitle' => $subtitle,
		'tags' => $tags,
	);
	$params = $params + $vars;
	$summary = elgg_view('object/elements/summary', $params);

	echo elgg_view('object/elements/full', array(
		'entity' => $task,
		'title' => false,
		'icon' => $task_icon,
		'summary' => $summary,
		'body' => $body,
	));
} else {
	$children = elgg_get_entities_from_metadata(array(
		'type' => 'object',
		'subtype' => 'arlearntask',
		'metadata_name' => 'parent_guid',
		'metadata_value' => $task->getGUID(),
		'limit' => 0,
		'pagination' => true,
	));
	$childrenCount = count($children);

	$children_body .= '<span>'.elgg_echo('item:object:arlearntask').': '.$childrenCount.'</span>';

	$params = array(
		'entity' => $task,
		'metadata' => $metadata,
		'subtitle' => $subtitle,
		'tags' => $tags,
		'content' => $children_body,
	);
	$params = $params + $vars;
	$summary = elgg_view('object/elements/summary', $params);

	echo elgg_view('object/elements/full', array(
		'entity' => $task,
		'title' => false,
		'icon' => $task_icon,
		'summary' => $summary,
		'body' => '',
	));
}