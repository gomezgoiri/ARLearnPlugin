<?php
/**
 * List all ARLearn data collection wespot_arlearn
 */

$title = elgg_echo('wespot_arlearn:all');

elgg_pop_breadcrumb();
elgg_push_breadcrumb(elgg_echo('wespot_arlearn'));

$group = get_entity(elgg_get_page_owner_guid());
//if (elgg_get_logged_in_user_guid() == $group->owner_guid) {
if ($group->canEdit()) {
	elgg_register_title_button();
}

$content = elgg_list_entities(array(
	'types' => 'object',
	'subtypes' => 'arlearntask_top',
	'full_view' => false,
));

if (!$content) {
	$content = '<p>' . elgg_echo('wespot_arlearn:none') . '</p>';
}

$body = elgg_view_layout('content', array(
	'filter_context' => 'all',
	'content' => $content,
	'title' => $title,
	'sidebar' => elgg_view('wespot_arlearn/sidebar'),
));

echo elgg_view_page($title, $body);
