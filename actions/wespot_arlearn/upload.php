<?php
/**
 * Upload a file or create an item in a collection.
 */

$collection_guid = get_input('collection_guid');
$collection = get_entity($collection_guid);


function extensionBelongsToType($collection_type, $filetype) {
	$allowedTypes = array();
	if ($collection_type=='picture') {
		array_push($allowedTypes, 'image/jpeg', 'image/gif', 'image/png', 'image/svg+xml', 'image/bmp', 'image/tiff');
	} else if ($collection_type=='video') {
		array_push($allowedTypes, 'video/mp4', 'video/webm', 'video/ogg');
	} else if ($collection_type=='audio') {
		array_push($allowedTypes, 'audio/mpeg', 'audio/ogg', 'audio/wav');
	}
	return in_array($filetype, $allowedTypes);
}



if (isset($_FILES['file_to_upload'])) {
	$collectionType = $collection->task_type;

	# Check that the file_to_upload field has only be defined for items that are associated with files (e.g., no textual or numeric items).
	$uploadTypes = array('picture', 'video', 'audio');
	if (!in_array($collectionType, $uploadTypes)) {
		register_error("You are not expected to upload a file for items of type $collectionType.");
		forward(REFERER);
	}

	# Check that the object type is the same as the one the container allows.
	# The form already limits the types of files that can be uploaded, but it is better to double-check it.
	$ftype = $_FILES['file_to_upload']['type'];
	if ( !extensionBelongsToType($collectionType, $ftype) ) {
		register_error("The collection only contains items of type '$collection->task_type' and you tried to upload a '$ftype'.");
		forward(REFERER);
	}

	//system_message(print_r($_FILES['file_to_upload'], true));
}
?>

