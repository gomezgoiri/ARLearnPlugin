<?php

global $LOOP_COUNT;

/**
 * Call ARLearn to check if there are new task results.
 *
 * At present it gets the full list and filters out the ones it has already.
 * This will be slow if there are many results.
 * I did try and used a stored date, but stuff got missed out.
 * In the future, some combination of the two, to reduce results, would be good.
 *
 * It makes the call using the group owner user information,
 * but they don't have to be logged in at the time.
 */

function checkARLearnForTaskChildren($group_guid) {

  global $LOOP_COUNT;

  elgg_load_library('elgg:wespot_arlearnservices');

  $gamearray = elgg_get_entities(array('type' => 'object', 'subtype' => 'arlearngame', 'owner_guid' => $group_guid));

  if ($gamearray === FALSE || count($gamearray) == 0) {
    // Don't call ARLEarn if there is no game
    debugWespotARLearn('No game was found in Elgg\'s database.');
  } else {
    $game = $gamearray[0];

    $group = get_entity($group_guid);
    $owner_giud = $group->owner_guid;
    $ownerprovider = elgg_get_plugin_user_setting('provider', $owner_giud, 'elgg_social_login');
    $owneroauth = str_replace("{$ownerprovider}_", '', elgg_get_plugin_user_setting('uid', $owner_giud, 'elgg_social_login'));
    $usertoken = createARLearnUserToken($ownerprovider, $owneroauth);

    if (isset($usertoken) && $usertoken != "") {
      $firstRun = true;
      $fromtime = 0;
      if (isset($game->arlearn_server_time)) {
        $fromtime = $game->arlearn_server_time;
      }
      debugWespotARLearn('usertoken: '.print_r($usertoken, true));
      debugWespotARLearn('fromtime: '.print_r($fromtime, true));

      wespot_arlearn_sync_game_tasks($usertoken, $group, $game, $fromtime);
      getChildrenFromARLearn($usertoken, $group, $game, $fromtime);
    }
  }
}

/**
 * Check if entity exist, no matter if it is enabled or not.
 *    https://community.elgg.org/discussion/view/188888/best-way-to-disable-an-entity-that-you-still-want-to-be-able-to-get
 */
function getExistingEntities($arlearnid) {
  debugWespotARLearn("Looking for existing object with arlearnid ".$arlearnid.".");  

  // Check if entity already exists, no matter if it is enabled or not.
  $access_status = access_get_show_hidden_status();
  access_show_hidden_entities(true);

  $elggresponseArray = elgg_get_entities_from_metadata(array(
    'type' => 'object',
    'subtype' => 'arlearntask',
    'metadata_name' => 'arlearnid',
    'metadata_value' => $arlearnid,
  ));
  // restore previous state
  access_show_hidden_entities($access_status);

  return $elggresponseArray;
}

function getChildrenFromARLearn($usertoken, $group, $game, $fromtime, $resumptiontoken="") {
  global $LOOP_COUNT;

  // Just in case, to stop never ending loops.
 // There will never be more than 200 new results for one task at a moment with people always updating.

  if ($LOOP_COUNT > 10) {
    return;
  }
  $LOOP_COUNT = $LOOP_COUNT+1;

  debugWespotARLearn('resumptiontoken in getChildrenFromARLearn: '.print_r($resumptiontoken, true));

  $gameid = $game->arlearn_gameid;
  $runid = $game->arlearn_runid;

  $results = getARLearnRunResults($usertoken, $runid, $fromtime, $resumptiontoken);

  if ($results != false) {
    debugWespotARLearn('CHILDREN RESULTS LIST: '.print_r($results, true));
    $datareturned = json_decode($results);

    if (isset($datareturned->error)) {
      debugWespotARLearn('ERROR getting ARLearn run for runID '.$runid.' from time '.$fromtime.'.');
      return;
    }

    $responsesArray = $datareturned->responses;
    $arlearnServerTime = $datareturned->serverTime;
    $arlearnResumptionToken = $datareturned->resumptionToken;

    debugWespotARLearn('arlearnServerTime: '.print_r($arlearnServerTime, true));
    debugWespotARLearn('arlearnResumptionToken: '.print_r($arlearnResumptionToken, true));

    if ($responsesArray && count($responsesArray) > 0) {

      foreach ($responsesArray as $response) {
        $taskid = $response->generalItemId;
        $responseid = $response->responseId;

        $existingObjects = getExistingEntities($responseid);

        // Don't save if we already have it
        if (!$existingObjects or count($existingObjects) == 0) {
          $responseValue = $response->responseValue;
          debugWespotARLearn("Receiving info... ");
          $userinfo = $response->userEmail;
          $userbits = split(":", $userinfo);
          $userprovidercode = intval($userbits[0]);

          $useroauth = $userbits[1];
          $providername = getElggProviderName($userprovidercode);

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

          debugWespotARLearn('PROCESSING RESULT FOR TASK USER =: '.print_r($users, true));

          if (count($users) == 1) {
            $user = $users[0];
            $taskArray = elgg_get_entities_from_metadata(array(
              'type' => 'object',
              'subtype' => 'arlearntask_top',
              'metadata_name' => 'arlearn_id',
              'metadata_value' => $taskid,
            ));

            debugWespotARLearn('PROCESSING RESULT FOR taskArray =: '.print_r($taskArray, true));

            if ($taskArray && count($taskArray) > 0) {
              $task = $taskArray[0];
              $task_guid = $task->guid;
              debugWespotARLearn('PROCESSING RESULT FOR task_guid =: '.print_r($task_guid, true));

              $task = get_entity($task_guid);
              $type = $task->task_type;
              $title="";
              $decodedResponseValue = json_decode($responseValue);
              $allresponsevars = get_object_vars($decodedResponseValue);

              debugWespotARLearn('PROCESSING RESULT FOR allresponsevars =: '.print_r($allresponsevars, true));

              foreach ($allresponsevars as $key => $value) {
                //$typename = $key;
                if ($key == 'imageUrl'
                  || $key == 'videoUrl'
                  || $key == 'audioUrl'
                  || $key == 'text') {
                  $title = $value;
                  break;
                }
              }

              debugWespotARLearn('PROCESSING RESULT FOR title =: '.print_r($title, true));

              // Don't save an item with no title.
              if ($title != "") {
                elgg_set_ignore_access(true);  
                $result = new ElggObject();
                $result->subtype = 'arlearntask';
                $result->owner_guid = $user->guid;
                $result->container_guid = $group_guid;
                $result->write_access_id = ACCESS_PRIVATE;
                if ($response->revoked) $result->disable();

                //MB: GROUP LEVEL ACCESS ONLY - CHANGED TO PUBLIC FOR NOW
                //$result->access_id=$group->group_acl; //owner group only
                $result->access_id=ACCESS_PUBLIC;
                $result->task_type = $type;
                $result->title = $title;
                $result->parent_guid = $task_guid;
                $result->description = '';
                $result->arlearnid = $responseid;
                $result->arlearnrunid = $runid;
                $result->save();

                // Now save description as an annotation
                $result->Annotate('arlearntask', '', $result->access_id, $result->owner_guid);
                $result->save();

                $newResult = get_entity($result->guid);
                debugWespotARLearn('newResult =: '.print_r($newResult, true));

                // Add to river
                add_to_river('river/object/arlearntask/create', 'create', $user->guid, $result->guid);
                elgg_set_ignore_access(false);  
              }
            }
          }
        } else {
          foreach ($existingObjects as $existingObject) {
            debugWespotARLearn("It exists, but we should update object with GUID ".$existingObject->guid);
            if ($response->revoked && $existingObject->isEnabled()) {
              // Following this example: http://learn.elgg.org/en/latest/guides/permissions-check.html
              elgg_push_context('backend_access');
              debugWespotARLearn("Can edit? ".($existingObject->canEdit() ? 'true' : 'false'));
              $existingObject->disable();
              $existingObject->save();
              elgg_pop_context();
              debugWespotARLearn('MODIFIED OBJECT =: '.print_r($existingObject, true));
            }
          }
        }
      }

      $arlearnServerTime = $datareturned->serverTime;
      $arlearnResumptionToken = $datareturned->resumptionToken;

      // added check to also make sure we don't process the same resumption token twice.
      if (isset($arlearnResumptionToken) && $arlearnResumptionToken != "" && ($resumptiontoken != $arlearnResumptionToken)) {
        getChildrenFromARLearn($usertoken, $group, $game, $fromtime, $arlearnResumptionToken);
      } else {
        // store time in game if it is greater than current time.
        $storedTime = $game->arlearn_server_time;
        if ($arlearnServerTime > $storedTime) {
          $game->arlearn_server_time = $arlearnServerTime;
          $game->save();

          debugWespotARLearn('GAME time updated: '.print_r($game, true));
          debugWespotARLearn('$game->arlearn_server_time: '.print_r($game->arlearn_server_time, true));
        }
      }
    }
  }
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

