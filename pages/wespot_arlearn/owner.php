<?php
/**
 * List a user's or group's ARLearn data collection tasks
 */

$owner = elgg_get_page_owner_entity();
if (!$owner) {
	forward('wespot_arlearn/all');
}

// access check for closed groups
group_gatekeeper();

$title = elgg_echo('wespot_arlearn:owner', array($owner->name));

elgg_push_breadcrumb(elgg_echo('wespot_arlearn:owner', array($owner->name)));

$group = get_entity(elgg_get_page_owner_guid());
//if (elgg_get_logged_in_user_guid() == $group->owner_guid) {
if ($group->canEdit()) {
	elgg_register_title_button();
}

$offset = (int) max(get_input('offset', 0), 0);
$limit = (int) max(get_input('limit', 10), 0);

// If group or user requires different call.
if ($owner instanceof ElggGroup) {
	$content = elgg_list_entities(array(
		'types' => 'object',
		'subtypes' => 'arlearntask_top',
		'container_guid' => $owner->guid,
	    'offset' => $offset,
		'limit' => $limit,
		'full_view' => false,
		'pagination' => true,
	));
} else {
	$content = elgg_list_entities(array(
		'types' => 'object',
		'subtypes' => 'arlearntask_top',
		'owner_guid' => $owner->guid,
	    'offset' => $offset,
		'limit' => $limit,
		'full_view' => false,
		'pagination' => true,
	));
}

if (!$content) {
	$content = '<p>' . elgg_echo('wespot_arlearn:none') . '</p>';
}

//$content = '<p>' . elgg_get_page_owner_guid() . '</p>';

$filter_context = '';
if (elgg_get_page_owner_guid() == elgg_get_logged_in_user_guid()) {
	$filter_context = 'mine';
}

$sidebar .= elgg_view('wespot_arlearn/sidebar');

$params = array(
	'filter_context' => $filter_context,
	'content' => $content,
	'title' => $title,
	'sidebar' => $sidebar,
);

if (elgg_instanceof($owner, 'group')) {
	$params['filter'] = '';
}

$body = elgg_view_layout('content', $params);

echo elgg_view_page($title, $body);
