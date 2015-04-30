<?php
/**
 * WeSpot specific Library of functions primarily to communicate with the ARLearn services
 *
 * Also has the debug function is here as it was convenient use and library includes.
 */

/** Turn debugging message on and off */
global $debug_wespot_arlearn;
$debug_wespot_arlearn = false;

/** The url for the ARLearn service calls */
global $serviceRootARLearn;
$serviceRootARLearn = elgg_get_plugin_setting('arlearn_url', 'wespot_arlearn'); //'ar-learn.appspot.com/';

/** The ARLearn Elgg App Key required when making certains service calls to ARLearn */
global $weSpotElggARLearnKey;
$weSpotElggARLearnKey = elgg_get_plugin_setting('arlearn_apikey', 'wespot_arlearn'); //"b6e99fd2-0dd3-473d-adee-e096e57c1618";

/**
 * If debugging is turned on, output the given message to the PHP error log.
 */
function debugWespotARLearn($message) {
	global $debug_wespot_arlearn;

	if ($debug_wespot_arlearn) {
		error_log($message);
	}
}

/**
 * Check if a user exists user on ARLearn
 * @param $userprovider The social sign on provider name ('Google/Facebook/LinkedIn/weSPOT' - not case sensitive).
 * @param $oauthid the OAuth id of the user to check.
 * @return true if the user exists on ARLearn, else false
 */
function checkARLearnUserExists($userprovider, $oauthid) {

	global $serviceRootARLearn;

	$usertoken = createARLearnUserToken($userprovider, $oauthid);
	debugWespotARLearn('IN CHECK USER: '.print_r($usertoken, true));

	$url = $serviceRootARLearn.'rest/account/accountDetails';
	debugWespotARLearn('IN CHECK USER URL: '.print_r($url, true));

	$results = callARLearnAPI("GET", $url, "", $usertoken);
	debugWespotARLearn('IN CHECK USER RESULTS: '.print_r($results, true));
	if ($results != false) {
		debugWespotARLearn('CHECK USER: '.print_r($results, true));

		$datareturned = json_decode($results);
		if (!isset($datareturned->error)) {
			return true;
		}
	}

	return false;
}

/**
 * Create a new user on ARLearn
 * @param $userprovider The social sign on provider name ('Google/Facebook/LinkedIn/weSPOT' - not case sensitive).
 * @param $oauthid the OAuth id of the user to create on ARLearn.
 * @param $email the email address of the user to create on ARLearn.
 * @param $name the name of the user to create on ARLearn.
 * @param $pictureurl the url for the picture of the user to create on ARLearn (optional).
 * @return false, if the attempt failed, else the response data from the ARLearn service call (will be a json string).
 */
function createARLearnUser($userprovider, $oauthid, $email, $name, $pictureurl="") {
	global $serviceRootARLearn;

	$providercode = getARLearnProviderCode($userprovider);
	$usertoken = createARLearnUserToken($userprovider, $oauthid);

	$url = $serviceRootARLearn.'rest/account/createAccount';
	$data = '{
		 "type": "org.celstec.arlearn2.beans.account.Account",
		 "localId": "'.addcslashes($oauthid,"\"'\n").'",
		 "accountType": '.addcslashes($providercode,"\"'\n").',
		 "email": "'.addcslashes($email,"\"'\n").'",
		 "name": "'.addcslashes($name,"\"'\n").'",
		 "picture": "'.addcslashes($pictureurl,"\"'\n").'"
	}';
	debugWespotARLearn('ADDING USER: '.print_r($data, true));
	$results = callARLearnAPI("POST", $url, $data);
	return $results;
}

/**
 * Create a new Game on ARLearn
 * @param $usertoken the ARLearn user token to append to the app key when sending the onBehalfOf token (as created with function createARLearnUserToken)
 * @param $name the name to give the Game
 * @return false, if the attempt failed, else the response data from the ARLearn service call (will be a json string).
 */
function createARLearnGame($usertoken, $name) {
	global $serviceRootARLearn;

	// register game on ARLEarn
	$url = $serviceRootARLearn.'rest/myGames';
	$data = '{
		 "type": "org.celstec.arlearn2.beans.game.Game",
		 "title": "'.addcslashes($name,"\"'\n").'",
		 "config": {
			  "type": "org.celstec.arlearn2.beans.game.Config",
			  "mapAvailable": false,
			  "manualItems": [],
			  "locationUpdates": []
		 }
	}';
	debugWespotARLearn('ADDING GAME: '.print_r($data, true));
	$results = callARLearnAPI("POST", $url, $data, $usertoken);
	return $results;
}

/**
 * Update a Game on ARLearn
 * @param $usertoken the ARLearn user token to append to the app key when sending the onBehalfOf token (as created with function createARLearnUserToken)
 * @param $gameid the ARLearn game id of the Game to update
 * @param $name the new name to give the Game
 * @return false, if the attempt failed, else the response data from the ARLearn service call (will be a json string).
 */
function editARLearnGame($usertoken, $gameid, $name) {
	global $serviceRootARLearn;

	// register game on ARLEarn
	$url = $serviceRootARLearn.'rest/myGames';
	$data = '{
		 "type": "org.celstec.arlearn2.beans.game.Game",
		 "gameId": "'.addcslashes($gameid,"\"'\n").'",
		 "title": "'.addcslashes($name,"\"'\n").'",
	}';
	debugWespotARLearn('UPDATING GAME: '.print_r($data, true));
	$results = callARLearnAPI("POST", $url, $data, $usertoken);
	return $results;
}

/**
 * Delet a game Game on ARLearn
 * @param $usertoken the ARLearn user token to append to the app key when sending the onBehalfOf token (as created with function createARLearnUserToken)
 * @param $gameid the ARLearn game id of the game to delete
 * @return false, if the attempt failed, else the response data from the ARLearn service call (will be a json string).
 */
function deleteARLearnGame($usertoken, $gameid) {
	global $serviceRootARLearn;

	$url = $serviceRootARLearn.'/rest/myGames/gameId/'.$gameid;
	$results = callARLearnAPI("DELETE", $url, "", $usertoken);
	return $results;
}

/**
 * Create a new Run on ARLearn
 * @param $usertoken the ARLearn user token to append to the app key when sending the onBehalfOf token (as created with function createARLearnUserToken)
 * @param $gameid the ARLearn Game id to create the Run against.
 * @param $name the name to give this new Run.
 * @return false, if the attempt failed, else the response data from the ARLearn service call (will be a json string).
 */
function createARLearnRun($usertoken, $gameid, $name) {
	global $serviceRootARLearn;

	$url = $serviceRootARLearn.'rest/myRuns';
	$data = '{
		 "type": "org.celstec.arlearn2.beans.run.Run",
		 "gameId": "'.addcslashes($gameid,"\"'\n").'",
		 "title": "'.addcslashes($name,"\"'\n").'"
	}';
	debugWespotARLearn('ADDING RUN: '.print_r($data, true));
	$results = callARLearnAPI("POST", $url, $data, $usertoken);
	return $results;
}

/**
 * Update a Run on ARLearn with a new name
 * @param $usertoken the ARLearn user token to append to the app key when sending the onBehalfOf token (as created with function createARLearnUserToken)
 * @param $gameid the ARLearn Game id of the run to update.
 * @param $runid the ARLearn Run id of the run to update.
 * @param $name the new name to give this Run.
 * @return false, if the attempt failed, else the response data from the ARLearn service call (will be a json string).
 */
function editARLearnRun($usertoken, $gameid, $runid, $name) {
	global $serviceRootARLearn;

	$url = $serviceRootARLearn.'rest/myRuns';
	$data = '{
		 "type": "org.celstec.arlearn2.beans.run.Run",
		 "gameId": "'.addcslashes($gameid,"\"'\n").'",
		 "runId": "'.addcslashes($runid,"\"'\n").'",
		 "title": "'.addcslashes($name,"\"'\n").'"
	}';
	debugWespotARLearn('UPDATING RUN: '.print_r($data, true));
	$results = callARLearnAPI("POST", $url, $data, $usertoken);
	return $results;
}


/**
 * Add a user to an ARLearn Run
 * @param $usertoken the ARLearn user token to append to the app key when sending the onBehalfOf token (as created with function createARLearnUserToken)
 * @param $runid the ARLearn Run id to add the user to.
 * @param $provider The social sign on provider name ('Google/Facebook/LinkedIn/weSPOT' - not case sensitive).
 * @param $oauthid the OAuth id of the user to add to the run on ARLearn.
 * @return false, if the attempt failed, else the response data from the ARLearn service call (will be a json string).
 */
function addUserToRun($usertoken, $runid, $provider, $oauthid) {
	global $serviceRootARLearn;

	$providercode = getARLearnProviderCode($provider);
	$url = $serviceRootARLearn.'rest/users';
	$data = '{
		 "type": "org.celstec.arlearn2.beans.run.User",
		 "accountType": "'.$providercode.'",
		 "localId": "'.addcslashes($oauthid,"\"'\n").'",
		 "runId": "'.addcslashes($runid,"\"'\n").'"
	}';
	debugWespotARLearn('ADDING STUDENT: '.print_r($data, true));
	$results = callARLearnAPI("POST", $url, $data, $usertoken);
	return $results;
}

/**
 * Remove a user from an ARLearn Run
 * @param $usertoken the ARLearn user token to append to the app key when sending the onBehalfOf token (as created with function createARLearnUserToken)
 * @param $runid the ARLearn Run id to remove the user from.
 * @param $provider The social sign on provider name ('Google/Facebook/LinkedIn/weSPOT' - not case sensitive).
 * @param $oauthid the OAuth id of the user to remove from the run on ARLearn.
 * @return false, if the attempt failed, else the response data from the ARLearn service call (will be a json string).
 */
function removeUserFromRun($usertoken, $runid, $provider, $oauthid) {
	global $serviceRootARLearn;

	$providercode = getARLearnProviderCode($provider);
	$url = $serviceRootARLearn.'/rest/users/runId/'.$runid.'/email/'.$providercode.":".$oauthid;
	debugWespotARLearn('REMOVING STUDENT FROM RUN: '.print_r($url, true));

	$results = callARLearnAPI("DELETE", $url, "", $usertoken);
	return $results;
}

/**
 * Add or edit a data collection task for the game with the given gameid on behlaf of the user with the given usertoken.
 * @param $usertoken the ARLearn user token to append to the app key when sending the onBehalfOf token (as created with function createARLearnUserToken)
 * @param $gameid the Elgg game id for this task (needed to get the associated 'arlearngame' object which holds the ARLearn Game id.
 * @param $title the title of the task.
 * @param $description the description of the task.
 * @param $tasktype the type of the task (text/video/audio/picture).
 * @param $task_guid the Elgg id of the task (needed to get the associated 'arlearntask' object which holds the ARLearn Generalitem id).
 * @param $gameid the ARLearn Game id the task belongs to.
 * @return false, if the attempt failed, else the response data from the ARLearn service call (will be a json string).
 */
function editARLearnTask($usertoken, $gameid, $title, $description, $tasktype, $task_guid) {
	global $serviceRootARLearn;

	$url = $serviceRootARLearn.'/rest/generalItems';
	$data = '{
	  "type": "org.celstec.arlearn2.beans.generalItem.NarratorItem",
	  "gameId": '.addcslashes($gameid,"\"'\n").',';

	if ($task_guid) {
		$task = get_entity($task_guid);
		debugWespotARLearn('TASK: '.print_r($task, true));
		if ($task->arlearn_id)
			$data .= '"id": '.addcslashes($task->arlearn_id,"\"'\n").',';
	}

	debugWespotARLearn('ADDING TASK  TASK_TYPE=: '.print_r($tasktype, true));

	$data .= '"sortKey": 0,
	  "name": "'.addcslashes($title,"\"'\n").'",
	  "description": "'.strip_tags(addcslashes($description,"\"'\n")).'",
	  "richText": "'.addcslashes($description,"\"'\n").'",
	  "openQuestion": {
		  "type": "org.celstec.arlearn2.beans.generalItem.OpenQuestion",';

	if ($tasktype == "text") {
		$data .= '"withAudio": false,
			"withText": true,
			"withValue": false,
			"withPicture": false,
			"withVideo": false }';
	} else if ($tasktype == "picture") {
		$data .= '"withAudio": false,
			"withText": false,
			"withValue": false,
			"withPicture": true,
			"withVideo": false }';
	} else if ($tasktype == "video") {
		$data .= '"withAudio": false,
			"withText": false,
			"withValue": false,
			"withPicture": false,
			"withVideo": true }';
	} else if ($tasktype == "audio") {
		$data .= '"withAudio": true,
			"withText": false,
			"withValue": false,
			"withPicture": false,
			"withVideo": false }';
	} else if ($tasktype == "numeric") {
		$data .= '"withAudio": false,
			"withText": false,
			"withValue": true,
			"withPicture": false,
			"withVideo": false }';
	}

	$data .= '}';
	debugWespotARLearn('ADDING TASK: '.print_r($data, true));

	$results = callARLearnAPI("POST", $url, $data, $usertoken);
	debugWespotARLearn('ADDING TASK RESULTS: '.print_r($results, true));
	return $results;
}

/**
 * Delete the data collection task with the given id number from the game with the given gameid on behlaf of the user with the given usertoekn.
 * @param $usertoken the ARLearn user token to append to the app key when sending the onBehalfOf token (as created with function createARLearnUserToken)
 * @param $gameid the ARLearn Game id to remove the task from.
 * @param $taskid the ARLearn GeneralItem id of the task to delete.
 * @return false, if the attempt failed, else the response data from the ARLearn service call (will be a json string).
 */
function deleteARLearnTask($usertoken, $gameid, $taskid) {
	global $serviceRootARLearn;

	$url = $serviceRootARLearn.'rest/generalItems/gameId/'.$gameid.'/generalItem/'.$taskid;
	$results = callARLearnAPI("DELETE", $url, "", $usertoken);
	return $results;
}

/**
 * Get the Run results for the given runid from the given time stamp.
 * @param $usertoken the ARLearn user token to append to the app key when sending the onBehalfOf token (as created with function createARLearnUserToken)
 * @param $runid the ARLearn Run id to get data from.
 * @param $fromtime the unix timestamp of the time to get data from.
 * @param $resumptiontoken optional. This token is used to call multiple pages of results and must be the token passed from ARLearn.
 * @return false, if the attempt failed, else the response data from the ARLearn service call (will be a json string).
 */
function getARLearnRunResults($usertoken, $runid, $fromtime, $resumptiontoken="") {
	global $serviceRootARLearn;

	//debugWespotARLearn('resumptiontoken in getARLearnRunResults: '.print_r($resumptiontoken, true));

	$url = $serviceRootARLearn.'rest/response/runId/'.$runid.'?from='.$fromtime;
	if ($resumptiontoken != "") {
		$url .= '&resumptionToken='.$resumptiontoken;
	}

	$results = callARLearnAPI("GET", $url, "", $usertoken);
	return $results;
}

/**
 * Makes service calls using Curl for the parameters given
 * @param $method POST/PUT/GET/DELETE
 * @param $url the url to use
 * @param $jsondata, the data to send to the url if any
 * @param $usertoken the ARLearn user token to append to the app key when sending the onBehalfOf token (as created with function createARLearnUserToken)
 * @return false, if the call failed, else the response data from the call (will be a json string).
 */
function callARLearnAPI($method, $url, $jsondata, $usertoken="") {
	global $weSpotElggARLearnKey;

	debugWespotARLearn("HTTP ".$method."  http://".$url." (body: ".$jsondata.")");

	$curl = curl_init();
	//For the record, when the webserver is behind a proxy we should use:
	//curl_setopt($curl, CURLOPT_PROXY, 'http://wwwcache.open.ac.uk:80');
	//(Problem:
	//   * This configuration is too specific, other plugins might do http requests too.
	//   * I have not found a better way to configure the proxy at PHP level.
	//)
	//(Therefore, the simplest solution is not to run the server behind a web proxy)
    switch ($method) {
        case "POST":
            curl_setopt($curl, CURLOPT_POST, 1);
            break;
        case "PUT":
            curl_setopt($curl, CURLOPT_PUT, 1);
            break;
        case "GET":
            //curl_setopt($curl, CURLOPT_HTTPGET, true);
        	curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
            break;
        case "DELETE":
        	curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
            break;
    }

    // Optional Authentication:
    //curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    //curl_setopt($curl, CURLOPT_USERPWD, "username:password");

	//debugWespotARLearn('CURL ON BEHALF OF USERTOKEN=: '.print_r($usertoken, true));

	if ($usertoken != "") {
		$sendkey = 'onBehalfOf:'.$weSpotElggARLearnKey.$usertoken;
	} else {
		$sendkey = $weSpotElggARLearnKey;
	}

	//debugWespotARLearn('sendkey=: '.print_r($sendkey, true));

    curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $jsondata);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_HTTPHEADER, array(
	    'Content-Type: application/json',
	    'Authorization: '.$sendkey,
	    'Content-Length:'.strlen($jsondata)));

	$response = curl_exec($curl);
	$httpCode = curl_getinfo( $curl, CURLINFO_HTTP_CODE );
	curl_close($curl);

	if($httpCode != 200 || $response === false) {
		return false;
    } else {
		return $response;
	}
}

/**
 * Create the ARLearn user token to append to the Elgg ARLearn app
 * key when creating the onBehalfOf request token.
 * @param $userprovider The social sign on provider name ('Google/Facebook/LinkedIn/weSPOT' - not case sensitive).
 * @param $userOAuth The user's OAuth id for that provider.
 * @return the user token string formatted as required for ARLearn.
 */
function createARLearnUserToken($userprovider, $userOAuth) {
	$providercode = getARLearnProviderCode($userprovider);
	$usertoken = ":".$providercode.":".$userOAuth;
	return $usertoken;
}

/**
 * Get the ARLearn provider code number for the given Elgg provider name (from HybridAuth).
 * If provider not found returns -1
 * @param $provider The social sign on provider name ('Google/Facebook/LinkedIn/Twitter/weSPOT' - case sensitive).
 */
function getARLearnProviderCode($provider) {

	$PROVIDER_FACEBOOK = 'Facebook';
	$PROVIDER_GOOGLE = 'Google';
	$PROVIDER_LINKEDIN = 'LinkedIn';
	$PROVIDER_TWITTER = 'Twitter';
	$PROVIDER_WESPOT = 'weSPOT';

	$ARLEARN_FACEBOOK_CODE = 1;
	$ARLEARN_GOOGLE_CODE = 2;
	$ARLEARN_LINKEDIN_CODE = 3;
	$ARLEARN_TWITTER_CODE = 4;
	$ARLEARN_WESPOT_CODE = 5;

	$arlearncode = -1;
	if (strcmp($provider,$PROVIDER_FACEBOOK) == 0) {
		$arlearncode = $ARLEARN_FACEBOOK_CODE;
	} else if (strcmp($provider,$PROVIDER_GOOGLE) == 0) {
		$arlearncode = $ARLEARN_GOOGLE_CODE;
	} else if (strcmp($provider,$PROVIDER_LINKEDIN) == 0) {
		$arlearncode = $ARLEARN_LINKEDIN_CODE;
	} else if (strcmp($provider,$PROVIDER_TWITTER) == 0) {
		$arlearncode = $ARLEARN_TWITTER_CODE;
	} else if (strcmp($provider,$PROVIDER_WESPOT) == 0) {
		$arlearncode = $ARLEARN_WESPOT_CODE;
	}
	return $arlearncode;
}

/**
 * Get the Elgg Social Sign On provider name (from HybridAuth) associated with the ARLearn provider code number.
 * @param $code the integer number from ARLearn that represents the Social Sign on provider
 * @return the name of the provider ('Google/Facebook/LinkedIn/Twitter/weSPOT'), else ""
 */
function getElggProviderName($code) {

	$PROVIDER_FACEBOOK = 'Facebook';
	$PROVIDER_GOOGLE = 'Google';
	$PROVIDER_LINKEDIN = 'LinkedIn';
	$PROVIDER_TWITTER = 'Twitter';
	$PROVIDER_WESPOT = 'weSPOT';

	$ARLEARN_FACEBOOK_CODE = 1;
	$ARLEARN_GOOGLE_CODE = 2;
	$ARLEARN_LINKEDIN_CODE = 3;
	$ARLEARN_TWITTER_CODE = 4;
	$ARLEARN_WESPOT_CODE = 5;

	$elggprovider = "";

	if ($code == $ARLEARN_FACEBOOK_CODE) {
		$elggprovider = $PROVIDER_FACEBOOK;
	} else if ($code == $ARLEARN_GOOGLE_CODE) {
		$elggprovider = $PROVIDER_GOOGLE;
	} else if ($code == $ARLEARN_LINKEDIN_CODE) {
		$elggprovider = $PROVIDER_LINKEDIN;
	} else if ($code == $ARLEARN_TWITTER_CODE) {
		$elggprovider = $PROVIDER_TWITTER;
	} else if ($code == $ARLEARN_WESPOT_CODE) {
		$elggprovider = $PROVIDER_WESPOT;
	}
	return $elggprovider;
}

/**
 * Create csv file build task
 * @param $usertoken the ARLearn user token to append to the app key when sending the onBehalfOf token (as created with function createARLearnUserToken)
 * @param $runId the ARLearn Run id of the run to update.
 * @return false, if the attempt failed, else the response data from the ARLearn service call (will be a json string).
 */
function createARLearnCsvFile($usertoken, $runId) {
	global $serviceRootARLearn;

	$url = $serviceRootARLearn.'rest/response/csv/runId/'.$runId.'/build';
	$result = callARLearnAPI("GET", $url, "", $usertoken);
	debugWespotARLearn('Creating csv file build task for runId: '.print_r($result, true));    
	return $result;
}

/**
 * Check for the csv file build task status
 * @param $csv_file_id the ARLearn csv file id
 * @return false, if the attempt failed, else the response data from the ARLearn service call (will be a json string).
 */
function getARLearnCsvFileStatus($csv_file_id) {
	global $serviceRootARLearn;

	$url = $serviceRootARLearn.'rest/response/csv/'.$csv_file_id.'/status';
	$result = callARLearnAPI("GET", $url, "", $usertoken);
    debugWespotARLearn('Checking csv file build task status with id: '.print_r($result, true));
	return $result;
}

/**
 * Check for the csv file build task status
 * @param $csv_file_id the ARLearn csv file id
 * @return false, if the attempt failed, else the response data from the ARLearn service call (will be a json string).
 */
function getARLearnCsvFileURL($csv_file_id) {
	global $serviceRootARLearn;
    
    $url = '';
    if (isset($csv_file_id)) {
        $url = $serviceRootARLearn.'rest/response/csv/'.$csv_file_id;
    }
    return $url;
}

/**
 * Get the Task results for the given gameid from the given time stamp.
 * @param $usertoken the ARLearn user token to append to the app key when sending the onBehalfOf token (as created with function createARLearnUserToken)
 * @param $game the ARLearn Game id to get data from.
 * @param $fromtime the unix timestamp of the time to get data from.
 * @param $resumptiontoken optional. This token is used to call multiple pages of results and must be the token passed from ARLearn.
 * @return false, if the attempt failed, else the response data from the ARLearn service call (will be a json string).
 */
function getARLearnGameTasks($usertoken, $gameid, $fromtime, $resumptiontoken="") {
	global $serviceRootARLearn;

	$url = $serviceRootARLearn.'rest/generalItems/gameId/'.$gameid.'?from='.$fromtime;
	if ($resumptiontoken != "") {
		$url .= '&resumptionToken='.$resumptiontoken;
	}

	$results = callARLearnAPI("GET", $url, "", $usertoken);
	return $results;
}
