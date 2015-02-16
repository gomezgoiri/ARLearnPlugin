<?php
/**
 * ARLearn data collection tasks widget
 */

// MB: added check for new task responses when widget draws.
// Each time the widget is drawn it calls ARLearn to check for new results.
elgg_load_library('elgg:wespot_arlearn');
//temporary_patch_while_cron_is_configured(); // FIXME


$num = (int) $vars['entity']->wespot_arlearn_num;

$options = array(
	'type' => 'object',
	'subtype' => 'arlearntask_top',
	'container_guid' => $vars['entity']->owner_guid,
	'limit' => $num,
	'full_view' => FALSE,
	'pagination' => FALSE,
);
$content = elgg_list_entities($options);
echo $content;

if ($content) {
	$url = "wespot_arlearn/group/" . elgg_get_page_owner_entity()->getGUID();
	$more_link = elgg_view('output/url', array(
		'href' => $url,
		'text' => elgg_echo('wespot_arlearn:more'),
		'is_trusted' => true,
	));
	echo "<span class=\"elgg-widget-more\">$more_link</span>";
} else {
	echo elgg_echo('wespot_arlearn:none');

	$group_guid = elgg_get_page_owner_guid();
	$group = get_entity($group_guid);
	
	if ($group->canEdit()) {
		if ($group->wespot_arlearn_enable != "no") {
			echo "<br><br>";
			echo elgg_view('output/url', array(
				  'href' => "wespot_arlearn/add/".$vars['entity']->owner_guid,
				  'text' => elgg_echo('wespot_arlearn:add'),
				  'is_trusted' => true,
			  ));
		} else {
			echo "<br><br>".elgg_echo('wespot_arlearn:widget:message')." - ";
			echo elgg_view('output/url', array(
				  'href' => "groups/edit/".$group_guid,
				  'text' => elgg_echo('groups:edit'),
				  'is_trusted' => true,
			  ));
		}
	}
}
