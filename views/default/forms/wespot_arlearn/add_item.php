<?php
/**
 * Page add item body
 */


$container = get_entity($vars['parent_guid']);

$cats = elgg_view('input/categories', $vars);
if (!empty($cats)) {
	echo $cats;
}


function getAcceptedTypes($task_type) {
	// See: http://www.w3schools.com/tags/att_input_accept.asp
	if($task_type=='picture') return 'image/*';
	if($task_type=='video') return 'video/*';
	if($task_type=='audio') return 'audio/*';
	return '*'; // Unexpected, better to throw an error
}


echo elgg_view('input/file', array(
	'name' => 'file_to_upload',
	'accept' => getAcceptedTypes($container->task_type)
));


echo '<div class="elgg-foot">';
echo elgg_view('input/hidden', array(
	'name' => 'collection_guid',
	'value' => $vars['guid'],
));

echo elgg_view('input/submit', array('value' => elgg_echo('save')));

echo '</div>';