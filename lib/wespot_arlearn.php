<?php

global $LOOP_COUNT;


/**
 * Call ARLearn to check if there are new task results.
 *
 * At present it gets the full list and filters out the ones it has already.
 * This will be slow if there are many results.
 * I did try and used a stored date, but stuff got missed out.
 * In the future, some combination of the two, to reduce results, would be good.
 */
// This method will only be used while we configure and ensure that cron script behaves as expected.
function temporary_patch_while_cron_is_configured() {
  $group_guid = elgg_get_page_owner_guid();
  $gamearray = elgg_get_entities(array(
                  'type' => 'object',
                  'subtype' => 'arlearngame',
                  'owner_guid' => $group_guid
                ));
  checkARLearnForGameEntities($gamearray, false);
}

function checkARLearnForRunId($runid, $forceUpdate=false) {
  $gamearray = elgg_get_entities_from_metadata(array(
                  'type' => 'object',
                  'subtype' => 'arlearngame',
                  'metadata_name_value_pairs' => array(
                      array(
                        name => 'arlearn_runid',
                        value => $runid
                      )
                   )
                ));
  checkARLearnForGameEntities($gamearray, $forceUpdate);  // Just one game per RunId expected, but still...
}

function checkARLearnForInquiry($guid, $forceUpdate=false) {
  checkARLearnForGuid($guid, '', $forceUpdate);
}

function checkARLearnForCollection($guid, $forceUpdate=false) {
  checkARLearnForGuid($guid, 'arlearntask_top', $forceUpdate);
}

function checkARLearnForGame($guid, $forceUpdate=false) {
  checkARLearnForGuid($guid, 'arlearngame', $forceUpdate);
}

// In ARLearn they have (====: equivalent, ===? : Not sure):
//  * games ===? inquiries
//  * runs ===? collections
//  * arlearntask_top === collection
//  * arlearntask === item (picture, text, etc.)
//
// Things I have discovered:
//  * The RunId response from ARLearn contains 'responses'.
//    * In these 'responses' items we have 'generalItemId' and 'responseId'.
//  * arleangame->owner_guid points to a inquiry object
//  * arlearntask_top object (a collection) has:
//    * an arlearn_id which can be matched with the 'generalItemId'
//    * an arlearn_gameid which can be matched with
//  * arlearntask object (an item in the collection) has:
//     * an arlearn_id which can be matched with the 'responseId'.
//     * an runid which can be matched with the runId
//     * parent_guid which points to arlearntask_top
// 
// Things I don't understand (or if I do, they are potential FIXMEs):
//  * runid should be defined in arlearntask_top and not in arlearntask (to avoid unnecesary repetitions)
function checkARLearnForGuid($guid, $elgg_type, $forceUpdate) {
  $obj = get_entity($guid);

  if (!$obj) {
    echo 'No object was found in Elgg\'s database with guid '.$guid.'.';
  } else {
    $subtype = get_subtype_from_id($obj->subtype);
    if ($subtype!=$elgg_type) { // Check to avoid confussions...
      echo 'Sorry, the object with guid '.$guid.' was expected to be a "'.$elgg_type.'".';
    } else {
      if ($subtype=='arlearngame') {
        checkARLearnForGameEntity($obj, $forceUpdate);
      } else if($subtype=='arlearntask_top') {
        $gamearray = elgg_get_entities(array(
          'type' => 'object',
          'subtype' => 'arlearngame',
          'container_guid' => $obj->container_guid
        ));
        // In my dev-site I have seen a 1-1 correspondence between game and collection (through their container inquiry)...
        checkARLearnForGameEntities($gamearray, $forceUpdate);
      } else if($subtype=='') { // Inquiry!
        $gamearray = elgg_get_entities(array(
          'type' => 'object',
          'subtype' => 'arlearngame',
          'container_guid' => $obj->guid
        ));
        // Only one expected, but anyway...
        checkARLearnForGameEntities($gamearray, $forceUpdate);
      }
    }
  }
}

function checkARLearnForGameEntities($gamearray, $forceUpdate) {
  if (!$gamearray || count($gamearray) == 0) {
    debugWespotARLearn('No games were found in Elgg\'s database.');
  } else {
    $debugMsg = 'Updated games:';
    foreach ($gamearray as $game) {
      checkARLearnForGameEntity($game, $forceUpdate);
      $debugMsg .= ' '.$game->guid.' ('.$game->arlearn_runid.')';
    }
    // This function will only be called manually (not by the update script).
    // That's why I am not afraid of making logs grow too much with the following info:
    debugWespotARLearn($debugMsg.'.');
  }
}

function checkARLearnForGameEntity($game, $forceUpdate) {
  elgg_load_library('elgg:wespot_arlearnservices');
  if($forceUpdate) {
    global $debug_wespot_arlearn;
    $debug_wespot_arlearn = true;
  }

  $group = get_entity($game->owner_guid);
  if ($group) {
    $owner_guid = $group->owner_guid;
    $ownerprovider = elgg_get_plugin_user_setting('provider', $owner_guid, 'elgg_social_login');
    $owneroauth = str_replace("{$ownerprovider}_", '', elgg_get_plugin_user_setting('uid', $owner_guid, 'elgg_social_login'));
    
    if ($ownerprovider=='') {
      debugWespotARLearn("Ignoring invalid provider for game with GUID '".$game->guid."' (owner has GUID '".$owner_guid."').");
      return;
    }
    
    $usertoken = createARLearnUserToken($ownerprovider, $owneroauth);
    if (isset($usertoken) && $usertoken != "") {
      $firstRun = true;
      $lastUpdate = 0;
      if (!$forceUpdate && isset($game->arlearn_server_time)) {
        $lastUpdate = $game->arlearn_server_time;
        if (is_array($lastUpdate)) {
          debugWespotARLearn('WARNING: Not sure if having '.count($game->arlearn_server_time).' server_times (game guid: '.$game->guid.') means that the DB has been corrupted (testing?).');
          debugWespotARLearn('This should be automatically fixed in this same request.');
          $lastUpdate = end($lastUpdate);
        }
      }

      global $checking;
      if (!isset($checking)) {
        $checking = array();
      }

      if (!$forceUpdate && isset($checking[$game->guid]) && $checking[$game->guid]) {  // If we are already updating it in another request, avoid another one.
        debugWespotARLearn('An update for game ('.$game->guid.') is already being processed. Ignoring this one.');
      } else {
        $checking[$game->guid] = true;  // FIXME race condition.
        $diff = time() - intval($lastUpdate/1000);  // TODO Requirement: This server should be roughly synchronized with the ARLearn server.
        // Let's avoid to overflow ARLearn server ensuring a maximum update rate of 20 seconds.
        if ($diff>20) {
          wespot_arlearn_sync_game_tasks($usertoken, $group, $game, $lastUpdate);
          getChildrenFromARLearn($usertoken, $group, $game, $lastUpdate);
        } else {
          debugWespotARLearn('Ignoring request: The game was updated too recently.');
        }
        // Finished checking it, let's free some memory.
        unset($checking[$game->guid]);
      }
    }
  }/* else {
    debugWespotARLearn('Game has no owner ('.$game->guid.').');
  }*/
}

function getChildrenFromARLearn($usertoken, $group, $game, $fromtime, $resumptiontoken="") {
  /*
   * This function seems to be recursive so, just in case, we call a function to prevent never ending loops.
   * There will never be more than 200 new results for one task at a moment with people always updating.
   */
  if (hasReachedLimit(10)) {
    return;
  }

  $runid = $game->arlearn_runid;
  $results = getARLearnRunResults($usertoken, $runid, $fromtime, $resumptiontoken);

  if (!$results) {  // $results===false
    debugWespotARLearn('Incorrect HTTP request in getARLearnRunResults:');
    debugWespotARLearn("    usertoken: ".$usertoken);
    debugWespotARLearn("    runid: ".$runid);
    debugWespotARLearn("    fromtime: ".$fromtime);
  } else {
    //debugWespotARLearn('CHILDREN RESULTS LIST: '.print_r($results, true));
    $datareturned = json_decode($results);

    if (isset($datareturned->error)) {
      debugWespotARLearn('ERROR getting ARLearn run for runID '.$runid.' from time '.$fromtime.'.');
    } else {
      $responsesArray = $datareturned->responses;
      if ($responsesArray && count($responsesArray) > 0) {
        // Each JSON response from the ARLearn server is composed by different "sub-responses".
        foreach ($responsesArray as $response) {
          processSubResult($response, $runid);
        }
      }

      // added check to also make sure we don't process the same resumption token twice.
      $arlearnResumptionToken = $datareturned->resumptionToken;
      if (isset($arlearnResumptionToken) && $arlearnResumptionToken!="" && $arlearnResumptionToken!=$resumptiontoken) {
        // Recursive call!
        getChildrenFromARLearn($usertoken, $group, $game, $fromtime, $arlearnResumptionToken);
      } else {
        updateCheckedTime($game, $datareturned->serverTime);
      }
    }
  }
}

/*
 * This function ensures that it will only be called $maximumTimes times.
 * Just in case, to stop never ending loops.
 * There will never be more than 200 new results for one task at a moment with people always updating.
 */
function hasReachedLimit($maximumUpdates) {
  //global $LOOP_COUNT;
  if ($LOOP_COUNT > $maximumUpdates) {
    return true;
  } else {
    $LOOP_COUNT = $LOOP_COUNT+1;
    return false;
  }
}

function updateCheckedTime($game, $newServerTime) {
  // It stores time in game if it is greater than current time.
  $storedTime = $game->arlearn_server_time;
  if (is_array($storedTime)) {  // Due to an error? Check 
    $storedTime = end($storedTime);
  }

  if ($newServerTime > $storedTime) {
    $game->arlearn_server_time = $newServerTime;
    $game->save();
    // There is no need to know when we have checked for game updates.
    //debugWespotARLearn('Game (runId: '.$runid.') updates checked at '.print_r($game->arlearn_server_time, true));
  }
}

function processSubResult($response, $runid) {
  $existingObjects = getExistingEntities($response->responseId);

  if (!$existingObjects or count($existingObjects) == 0) {
    // Save if the object does not already exist in Elgg.
    $userinfo = $response->userEmail;
    $userbits = split(":", $userinfo);
    $userprovidercode = intval($userbits[0]);

    $useroauth = $userbits[1];
    $providername = getElggProviderName($userprovidercode);

    $userGuid = getUserGuid($providername, $useroauth);
    if ($userGuid!=null) {
      $taskid = $response->generalItemId;
      $taskArray = elgg_get_entities_from_metadata(array(
        'type' => 'object',
        'subtype' => 'arlearntask_top',
        'metadata_name' => 'arlearn_id',
        'metadata_value' => $taskid,
      ));

      if ($taskArray) {
        //debugWespotARLearn('PROCESSING RESULT FOR taskArray =: '.print_r($taskArray, true));
        if (count($taskArray)==1) {
          saveTask($taskArray[0]->guid, $response, $userGuid, $runid);
        } else {
          debugWespotARLearn('More than a collection (arlearntask_top object) was found for arlearn_id '.$taskid.'.');
        }
      } else {
        debugWespotARLearn('Collection (arlearntask_top object) not found for arlearn_id '.$taskid.'.');
      }
    }
  } else {
    // We only "update" an existing object to mark that it already exist.
    foreach ($existingObjects as $existingObject) {
      //debugWespotARLearn("It exists, but we should update object with GUID ".$existingObject->guid);
      if ($response->revoked && $existingObject->isEnabled()) {
        // Following this example: http://learn.elgg.org/en/latest/guides/permissions-check.html
        elgg_push_context('backend_access');
        //debugWespotARLearn("Can edit? ".($existingObject->canEdit() ? 'true' : 'false'));
        $existingObject->disable();
        $existingObject->save();
        elgg_pop_context();
        debugWespotARLearn('Element (guid: '.$existingObject->guid.') was disabled (revoked).');
      }
    }
  }
}

function getExistingEntities($arlearnid) {
  /**
  * Checks if an entity exist, no matter if it is enabled or not.
  *    https://community.elgg.org/discussion/view/188888/best-way-to-disable-an-entity-that-you-still-want-to-be-able-to-get
  */
  $previous_status = access_get_show_hidden_status();
  access_show_hidden_entities(true);

  $elggresponseArray = elgg_get_entities_from_metadata(array(
    'type' => 'object',
    'subtype' => 'arlearntask',
    'metadata_name' => 'arlearnid',
    'metadata_value' => $arlearnid,
  ));
  // Restore previous state.
  access_show_hidden_entities($previous_status);

  return $elggresponseArray;
}

function saveTask($collectionGuid, $response, $userGuid, $runid) {
  $titleAndType = extractTitleAndTypeFromResponse($response);

  // Don't save an item with no title.
  if (!$titleAndType) {
    debugWespotARLearn('Item not saved because it has no title.');
    debugWespotARLearn(''.print_r($response, true));
    return;
  }

  elgg_set_ignore_access(true);
  elgg_push_context('backend_access');
  $result = new ElggObject();
  $result->subtype = 'arlearntask';
  $result->owner_guid = $userGuid;
  // FIXME What's group_guid?!
  // $result->container_guid = $group_guid;
  $result->write_access_id = ACCESS_PRIVATE;
  if ($response->revoked) $result->disable();

  //MB: GROUP LEVEL ACCESS ONLY - CHANGED TO PUBLIC FOR NOW
  //$result->access_id=$group->group_acl; //owner group only
  $result->access_id = ACCESS_PUBLIC;
  $result->title = $titleAndType[0];
  $result->parent_guid = $collectionGuid;
  $result->task_type = $titleAndType[1];
  $result->description = '';
  $result->arlearnid = $response->responseId;
  $result->arlearnrunid = $runid;
  $result->save();

  // Now save description as an annotation
  $result->Annotate('arlearntask', '', $result->access_id, $result->owner_guid);
  $result->save();

  $newResult = get_entity($result->guid);
  debugWespotARLearn('New item saved: \n'.print_r($newResult, true));

  // Add to river
  add_to_river('river/object/arlearntask/create', 'create', $userGuid, $result->guid);
  elgg_pop_context();
  elgg_set_ignore_access(false);
}

function getUserGuid($providername, $useroauth) {
  $user = getUser($providername, $useroauth);
  if ($user==null) return null;
  return $user->guid;  // else
}

function getUser($providername, $useroauth) {
  $user_uid = $providername . "_" . $useroauth;
  $options = array(
    'type' => 'user',
    'plugin_id' => 'elgg_social_login',
    'plugin_user_setting_name_value_pairs' => array(
      'uid' => $user_uid,
      'provider' => $providername,
    ),
    'plugin_user_setting_name_value_pairs_operator' => 'AND',
    'limit' => 0
  );
  $users = elgg_get_entities_from_plugin_user_settings($options);
  $cusers = count($users);

  if ($cusers==1) {
    return $users[0];
  } elseif ($cusers==0) {
    debugWespotARLearn('No user found for '.$user_uid.'.');
  } else {
    debugWespotARLearn('More than a user was found: '.print_r($users, true));
  }
  return null;
}

function extractTitleAndTypeFromResponse($response) {
  $decodedResponseValue = json_decode($response->responseValue);
  $allresponsevars = get_object_vars($decodedResponseValue);  
  $possibleFields = array('imageUrl', 'videoUrl', 'audioUrl', 'text', 'value');
  $types = array('picture', 'video', 'audio', 'text', 'numeric');
  for ($i = 0; $i < count($possibleFields); $i++) {
    $fieldName = $possibleFields[$i];
    if (array_key_exists($fieldName, $allresponsevars)) {
      return array($allresponsevars[$fieldName], $types[$i]);
    }
  }
  return null;
}

/**
 * Prepare the add/edit form variables
 *
 * @param ElggObject $task
 * @return array
 */
function wespot_arlearn_prepare_form_vars($task = null, $parent_guid = 0) {

  // input names => defaults
  $values = array(
    'title' => '',
    'description' => '',
    'task_type' => '',
    'access_id' => ACCESS_PUBLIC,
    'write_access_id' => ACCESS_PRIVATE,
    'container_guid' => elgg_get_page_owner_guid(),
    'guid' => null,
    'entity' => $task,
    'parent_guid' => $parent_guid,
  );

  // Stefaan said we may want to at least add a start date for tasks at some point
  // so left these here from the original tasks form
  // Just add in above.
  //    'start_date' => '',
  //    'end_date' => '',

  if ($task) {
    foreach (array_keys($values) as $field) {
      if (isset($task->$field)) {
        $values[$field] = $task->$field;
      }
    }
  }

  if (elgg_is_sticky_form('arlearntask')) {
    $sticky_values = elgg_get_sticky_values('arlearntask');
    foreach ($sticky_values as $key => $value) {
      $values[$key] = $value;
    }
  }

  elgg_clear_sticky_form('arlearntask');
  return $values;
}

/**
 * Recurses the task tree and adds the breadcrumbs for all ancestors
 *
 * @param ElggObject $task Page entity
 */

function wespot_arlearn_prepare_parent_breadcrumbs($task) {
  if ($task && $task->parent_guid) {
    $parents = array();
    $parent = get_entity($task->parent_guid);
    while ($parent) {
      array_push($parents, $parent);
      $parent = get_entity($parent->parent_guid);
    }
    while ($parents) {
      $parent = array_pop($parents);
      elgg_push_breadcrumb($parent->title, $parent->getURL());
    }
  }
}

function wespot_arlearn_create_csv_file($group_guid) {

  elgg_load_library('elgg:wespot_arlearnservices');

  $gamearray = elgg_get_entities(array('type' => 'object', 'subtype' => 'arlearngame', 'owner_guid' => $group_guid));

  if ($gamearray === FALSE || count($gamearray) == 0) {
    return false;
  } 
 
  $game = $gamearray[0];
  $runId = $game->arlearn_runid;
  $group = get_entity($group_guid);
  $owner_giud = $group->owner_guid;
  $ownerprovider = elgg_get_plugin_user_setting('provider', $owner_giud, 'elgg_social_login');
  $owneroauth = str_replace("{$ownerprovider}_", '', elgg_get_plugin_user_setting('uid', $owner_giud, 'elgg_social_login'));
  $usertoken = createARLearnUserToken($ownerprovider, $owneroauth); 
  $result = createARLearnCsvFile($usertoken, $runId);

  if ($result == false) {
    return false;
  }

  $json = str_replace("'",'"',$result);
  $datareturned = json_decode($json);
  if (isset($datareturned->error)) {
    return false;
  }  
  return $datareturned;
}

function wespot_arlearn_csv_file_status($csv_file_id) {

  elgg_load_library('elgg:wespot_arlearnservices');
  $result = getARLearnCsvFileStatus($csv_file_id);

  if ($result == false) {
    return false;
  }

  $json = str_replace("'",'"',$result);
  $datareturned = json_decode($json);
  if (isset($datareturned->error)) {
    return false;
  } 

  return $datareturned->status;
}

function wespot_arlearn_upsert_task($data, $game, $group) {
  $is_new = true;
  $elggresponseArray = elgg_get_entities_from_metadata(array(
      'type' => 'object',
      'subtype' => 'arlearntask_top',
      'metadata_name' => 'arlearn_id',
      'metadata_value' => $data->id,
  ));

  if ($elggresponseArray && count($elggresponseArray) > 0) {
    $task = $elggresponseArray[0];
    $is_new = false;
  } else {
    $task = new ElggObject();
    $task->subtype = 'arlearntask_top';
    $task->container_guid = $group->getGUID(); 
    $task->write_access_id = ACCESS_PRIVATE;
    $task->access_id = ACCESS_PUBLIC;

    //MISSING user info in the ARLearn response, so let it be the owner of the group
    $task->owner_guid = $group->getOwnerGUID(); 
    $task->arlearn_id = $data->id;
    $task->arlearn_gameid = $data->gameId;        
  }

  $task->title = $data->name;
  $task->description = $data->richText;
  $task->task_type = '';

  if ($data->openQuestion->withAudio) {
    $task_type = 'audio';
  } 
  else if ($data->openQuestion->withText) {
    $task_type = 'text';  
  }
  else if ($data->openQuestion->withValue) {
    $task_type = 'numeric';
  }
  else if ($data->openQuestion->withPicture) {
    $task_type = 'picture';
  }
  else if ($data->openQuestion->withVideo) {
    $task_type = 'video';
  }
  $task->task_type = $task_type;

  elgg_set_ignore_access(true);

  $task_saved = $task->save();

  elgg_set_ignore_access(false);
  
  if ($task_saved) {
    $task->annotate('arlearntask', $task->description, $task->access_id, $task->owner_guid);
    if ($is_new) {
      add_to_river('river/object/arlearntask_top/create', 'create', $task->owner_guid, $task->guid);
    } else {
      add_to_river('river/object/arlearntask_top/create', 'update', $task->owner_guid, $task->guid);
    }
    return true;
  }
  return false;
}


function wespot_arlearn_sync_game_tasks($usertoken, $group, $game, $fromtime, $resumptiontoken="") {

  $gameid = $game->arlearn_gameid;
  $results = getARLearnGameTasks($usertoken, $gameid, $fromtime, $resumptiontoken);
  $data = ($results != false) ? json_decode($results) : false;
  if ( !$data  || isset($data->error) ) {
    return false;
  }

  $hasUpsert = NULL;
  $tasks = $data->generalItems;
  foreach($tasks as $task) {
    if (!$task->deleted) {
      $status = wespot_arlearn_upsert_task($task, $game, $group);
      if (isset($hasUpsert)) {
        $hasUpsert = ($hasUpsert && $status);
      } else {
        $hasUpsert = $status;
      }
    }
  }

  $arlearnServerTime = $data->serverTime;
  $arlearnResumptionToken = $data->resumptionToken;

  // added check to also make sure we don't process the same resumption token twice.
  if (isset($arlearnResumptionToken) && $arlearnResumptionToken != "" && ($resumptiontoken != $arlearnResumptionToken)) {
    wespot_arlearn_sync_game_tasks($usertoken, $group, $game, $fromtime, $arlearnResumptionToken);
  }    

  if (isset($hasUpsert) && ($hasUpsert == true)) {
    // store time in game if it is greater than current time.
    $storedTime = $game->arlearn_server_time;
    if ($arlearnServerTime > $storedTime) {
      $game->arlearn_server_time = $arlearnServerTime;
      elgg_set_ignore_access(true);
      $game->save();

      elgg_set_ignore_access(false);
    }    
  }
}

