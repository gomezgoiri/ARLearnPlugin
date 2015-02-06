<?php
/**
 * Page icon
 *
 * Uses a separate icon view due to dependency on annotation
 *
 * @uses $vars['entity']
 * @uses $vars['annotation']
 */

$annotation = $vars['annotation'];
$task_type = $vars['task_type'];
$entity = get_entity($annotation->entity_guid);

// Get size
if (!in_array($vars['size'], array('small', 'medium', 'large', 'tiny', 'master', 'topbar'))) {
	$vars['size'] = "medium";
}

$size = elgg_strtolower($vars['size']);
$type = $entity->getType();
$params = array(
	'entity' => $entity,
	'size' => $size,
	'task_type' => $task_type,
);

$url = elgg_trigger_plugin_hook('entity:icon:url', $type, $params, null);
if ($url == null) {
	$url = "_graphics/icons/default/$size.png";
}

// Ajout Fx pour traiter la cas ou pas d'annotations (ce qui est curieux en fait)
if ($annotation) {
?>
<a href="<?php echo $annotation->getURL(); ?>">
	<img src="<?php echo elgg_normalize_url($url); ?>" />
</a>
<?php } ?>