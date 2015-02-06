<?php
/**
 * wespot_arlearn plugin settings
 */

// set default value
if (!isset($vars['entity']->arlearn_url)) {
	$vars['entity']->arlearn_url = 'streetlearn.appspot.com/';
}

// set default value
if (!isset($vars['entity']->arlearn_apikey)) {
	$vars['entity']->arlearn_apikey = '';
}

echo '<div>';
echo elgg_echo('wespot_arlearn:arlearnurl');
echo ' ';
echo elgg_view('input/text', array(
	'name' => 'params[arlearn_url]',
	'value' => $vars['entity']->arlearn_url,
));
echo '</div>';

echo '<div>';
echo elgg_echo('wespot_arlearn:arlearnkey');
echo ' ';
echo elgg_view('input/text', array(
	'name' => 'params[arlearn_apikey]',
	'value' => $vars['entity']->arlearn_apikey,
));
echo '</div>';
?>