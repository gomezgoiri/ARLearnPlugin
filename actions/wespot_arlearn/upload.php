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


$collectionType = $collection->task_type;
if (isset($_FILES['file_to_upload'])) {

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
	/*
  	[name] => MyFile.txt (comes from the browser, so treat as tainted)
    [type] => text/plain  (not sure where it gets this from - assume the browser, so treat as tainted)
    [tmp_name] => /tmp/php/php1h4j1o (could be anywhere on your system, depending on your config settings, but the user has no control, so this isn't tainted)
    [error] => UPLOAD_ERR_OK  (= 0)
    [size] => 123   (the size in bytes)
	*/
} else if (isset($_POST[$collectionType])) { // numeric and text
	$value = $_POST[$collectionType];
	system_message($value);
} else {
	register_error("The collection only has items of type $collectionType.");
	forward(REFERER);
}
?>

