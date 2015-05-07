<?php
/**
 * Page add item body
 */


$container = get_entity($vars['parent_guid']);
echo print_r($container->task_type, true);


$cats = elgg_view('input/categories', $vars);
if (!empty($cats)) {
	echo $cats;
}


echo elgg_view('input/file', array(
	'name' => 'file_to_upload'
));


echo '<div class="elgg-foot">';
if ($vars['guid']) {
	echo elgg_view('input/hidden', array(
		'name' => 'task_guid',
		'value' => $vars['guid'],
	));
}
echo elgg_view('input/hidden', array(
	'name' => 'container_guid',
	'value' => $vars['container_guid'],
));
if ($vars['parent_guid']) {
	echo elgg_view('input/hidden', array(
		'name' => 'parent_guid',
		'value' => $vars['parent_guid'],
	));
}

echo elgg_view('input/submit', array('value' => elgg_echo('save')));

echo '</div>';