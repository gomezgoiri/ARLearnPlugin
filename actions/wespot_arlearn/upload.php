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
	register_error(elgg_echo('wespot_arlearn:add:item:failure'));
	forward(REFERER);
}

elgg_load_library('elgg:wespot_arlearnservices');
$userGuid = elgg_get_logged_in_user_guid();
$userToken = getUserToken($userGuid);



if (isset($_FILES['file_to_upload'])) {

	# Check that the file_to_upload field has only be defined for items that are associated with files (e.g., no textual or numeric items).
	$uploadTypes = array('picture', 'video', 'audio');
	if (!in_array($collectionType, $uploadTypes)) {
		register_error(elgg_echo('wespot_arlearn:add:item:incorrect_file_field', array($collectionType)));
		forward(REFERER);
	}

	# Check that the object type is the same as the one the container allows.
	# The form already limits the types of files that can be uploaded, but it is better to double-check it.
	$ftype = $_FILES['file_to_upload']['type'];
	if ( !extensionBelongsToType($collectionType, $ftype) ) {
		register_error(elgg_echo('wespot_arlearn:add:item:incorrect_file_type', array($collectionType, $ftype)));
		forward(REFERER);
	}

	$uploadUrl = createFileUploadURL($userToken, $runId, $_FILES['file_to_upload']['name']);
	$itemValue = uploadFile($uploadUrl, $_FILES['file_to_upload'], $userToken, $runId);
	if (!$itemValue) {
		debugWespotARLearn('Error uploading file to ARLearn server.');
		register_error(elgg_echo('wespot_arlearn:add:item:failure'));
		forward(REFERER);
	}
} else if (isset($_POST[$collectionType])) { // numeric and text
	$itemValue = $_POST[$collectionType];

	if ($collectionType=='numeric' && !is_numeric($itemValue)) {
		register_error(elgg_echo('wespot_arlearn:add:item:numeric:failure', array($itemValue)));
		forward(REFERER);
	}
} else {
	register_error(elgg_echo('wespot_arlearn:add:item:missing_field'));
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

