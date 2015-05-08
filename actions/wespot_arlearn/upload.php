<?php
/**
 * Upload a file or create an item in a collection.
 */


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

function getUserToken($userGuid) {
	$ownerprovider = elgg_get_plugin_user_setting('provider', $userGuid, 'elgg_social_login');
	$owneroauth = str_replace("{$ownerprovider}_", '', elgg_get_plugin_user_setting('uid', $userGuid, 'elgg_social_login'));
	return createARLearnUserToken($ownerprovider, $owneroauth);
}

function getRunIdForGame($gameId) {
	$gamearray = elgg_get_entities_from_metadata(array(
      'type' => 'object',
      'subtype' => 'arlearngame',
      'metadata_name_value_pairs' => array(
          array(
            name => 'arlearn_gameid',
            value => $gameId
          )
       )
    ));
    if (!$gamearray || count($gamearray)!=1) return null;
    return $gamearray[0]->arlearn_runid;
}



$collectionGuid = get_input('collection_guid');
$collection = get_entity($collectionGuid);
$collectionType = $collection->task_type;
$itemValue = null;


$runId = getRunIdForGame($collection->arlearn_gameid);

if ($runId==null) {
	debugWespotARLearn("The game associated with the given gameId ($collection->arlearn_gameid) was not found.");
	register_error("The item could not be uploaded.");
	forward(REFERER);
}

elgg_load_library('elgg:wespot_arlearnservices');
$userGuid = elgg_get_logged_in_user_guid();
$userToken = getUserToken($userGuid);



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
		register_error("The collection only contains items of type '$collectionType' and you tried to upload a '$ftype'.");
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
	$uploadUrl = createFileUploadURL($userToken, $runId, $_FILES['file_to_upload']['name']);
	//system_message($uploadUrl);
	register_error('Files cannot be processed: not yet implemented.');
	forward(REFERER);
} else if (isset($_POST[$collectionType])) { // numeric and text
	$itemValue = $_POST[$collectionType];
	//system_message($itemValue);
} else {
	register_error("The collection only has items of type $collectionType.");
	forward(REFERER);
}


$response = json_decode( createARLearnTask($userToken, $runId, $collection->arlearn_id, $collectionType, $itemValue) );
//system_message(print_r($response, true));

// Process response for task creation in ARLearn
if (isset($response->responseId)) {
	// Successful request
	// Directly add it (don't wait for the collection update)
	elgg_load_library('elgg:wespot_arlearn');
	saveTask($collectionGuid, $response, $userGuid, $runId);
	forward("wespot_arlearn/view/$collectionGuid/$collection->title");
} else {
	// Error field should be defined if the 'responseId' field does not exist.
	// So the following guard is unneeded: if (isset($datareturned->error)
	register_error($response->error);
	forward(REFERER);
}


?>

