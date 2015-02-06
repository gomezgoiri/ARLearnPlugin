<?php
/**
 * ARLearn data collection tasks widget edit
 */

// set default value
if (!isset($vars['entity']->wespot_arlearn_num)) {
	$vars['entity']->wespot_arlearn_num = 4;
}

// $widget->access_id = group
//TOO LATE - $vars['show_access'] = false;
//$container_guid = (int)get_input('container_guid');
//$group_guid = $container_guid;
//$group = get_entity($group_guid);
//$widget = $vars['entity'];
//$widget->access_id = $group->group_acl;

$params = array(
	'name' => 'params[wespot_arlearn_num]',
	'value' => $vars['entity']->wespot_arlearn_num,
	'options' => array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10),
);
$dropdown = elgg_view('input/dropdown', $params);

?>
<div>
	<?php echo elgg_echo('wespot_arlearn:num'); ?>:
	<?php echo $dropdown; ?>
</div>
