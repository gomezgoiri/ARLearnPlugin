<?php
/**
 * View for ARLearn data collection task results object
 *
 * @uses $vars['entity']    The task result object
 * @uses $vars['full_view'] Whether to display the full view
 * @uses $vars['revision']  This parameter not supported by elgg_view_entity()
 */

//echo elgg_view('object/arlearntask_top', $vars);

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
//$task_icon = elgg_view('wespot_arlearn/icon', array('annotation' => $annotation, 'size' => 'small', 'task_type' => $task->task_type));

$size = 'small';
$imagewidth = "300";
if (elgg_in_context('widgets')) {
	$size = 'tiny';
	$imagewidth = "200";
}
$user = get_entity($task->owner_guid);
$task_icon = elgg_view_entity_icon($user, $size, $vars);

if (!elgg_in_context('widgets')) {

	$editor = get_entity($annotation->owner_guid);
	$editor_link = elgg_view('output/url', array(
		'href' => "profile/$editor->username",
		'text' => $editor->name,
		'is_trusted' => true,
	));

	$date = elgg_view_friendly_time($annotation->time_created);
	$editor_text = elgg_echo('wespot_arlearn:strapline', array($date, $editor_link));
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
}

// do not show the metadata and controls in widget view
if ($revision) {
	$metadata = '';
}


/*** SUMMARY SECTION ***/
$summary = "";

$content = elgg_extract('content', $vars, '');

$tags = elgg_extract('tags', $vars, '');
if ($tags === '') {
	$tags = elgg_view('output/tags', array('tags' => $entity->tags));
}

if ($metadata) {
	echo $metadata;
}

$title_link = elgg_extract('title', $vars, '');
if ($title_link === '') {
	if (isset($task->title)) {
		$text = $task->title;
	} else {
		$text = $task->name;
	}

	if ($task->task_type == 'picture') {
		$summary .= '<a target="_blank" href="'.$text.'"><img width="'.$imagewidth.'" border="0" src="'.$text.'" /></a>';
	} else if ($task->task_type == 'video') {
		$summary .= '<a target="_blank" href="'.$text.'">'.$user->name.elgg_echo("wespot_arlearn:type_1_label").'</a>';
	} else if ($task->task_type == 'audio') {
		$summary .= '<a target="_blank" href="'.$text.'">'.$user->name.elgg_echo("wespot_arlearn:type_2_label").'</a>';
	} else {
		$summary .= $text;
	}
}

$summary .= "<div class=\"elgg-subtext\">$subtitle</div>";
$summary .= $tags;

$summary .= elgg_view('object/summary/extend', $vars);

if ($content) {
	$summary .= "<div class=\"elgg-content\">$content</div>";
}

/**** END SUMMARY SECTION *****/


$body = elgg_view('output/longtext', array('value' => $annotation->value));

echo elgg_view('object/elements/full', array(
	'entity' => $task,
	'title' => false,
	'icon' => $task_icon,
	'summary' => $summary,
	'body' => $body,
));
