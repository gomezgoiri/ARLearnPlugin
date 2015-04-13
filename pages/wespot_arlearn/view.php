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

$group = elgg_get_page_owner_entity();  // or get_entity(elgg_get_page_owner_guid());
if ($group && $group->canEdit()) {  // $group is false if it has no owner.
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
	'limit' => 9, // Makes more sense now that there are three elements per row.
	'pagination' => true,
	/*'order_by_metadata' => array('name' => 'elggx_fivestar_average', 'as' => 'integer', 'direction' => 'desc'),
	'order_by_metadata' =>  array(
         array( 'name' => 'elggx_fivestar_average', 'direction' => 'DESC', 'as' => 'integer' ), // this should look like your code right now
         array( 'name' => 'elggx_fivestar_votes', 'direction' => 'DESC', 'as' => 'integer' )
    )*/
));

$body = elgg_view_layout('content', array(
	'filter' => '',
	'content' => $content,
	'title' => $title,
	'sidebar' => elgg_view('wespot_arlearn/sidebar/navigation'),
));


elgg_load_css('custom_layout');
elgg_load_js('image_list');

elgg_load_css('fancybox');
elgg_load_js('fancybox');


elgg_load_js('google_channel');
elgg_load_js('notifications');

elgg_load_library('elgg:wespot_msg');
elgg_load_library('elgg:wespot_arlearnservices');
elgg_load_library('elgg:wespot_arlearnmsgservices');


//$body .= "<script>clientToken=AHRlWrqcO2pV-soMQ_taAYkLehBKQxqbad06_u9ctzxa_zRBhiFyiZE8uUj8dfGmx-c1h-DzHZc-ij310Oe4XGn2pCjur4AD1V1bDv5QebGY1JzXka8FFHo</script>";

$group = elgg_get_page_owner_entity();
if (elgg_is_logged_in() && can_write_to_container(0, $group->getGUID())) {
    $channel_token = wespot_msg_get_channel_token(elgg_get_logged_in_user_entity());
    $body .= "<script>clientToken='$channel_token';</script>";
}

echo elgg_view_page($title, $body);
