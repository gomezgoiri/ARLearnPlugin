<?php
/**
 * CSV export ARLearn data collection task
 */
define('CSV_FILE_STATUS_BUILDING', 1);
define('CSV_FILE_STATUS_FINISHED', 2);

$task_guid = get_input('guid');
$task = get_entity($task_guid);

$isRebuildRequested = get_input('isRebuildRequested');

if (!$task) {
    forward(REFERER);	
}

elgg_set_page_owner_guid($task->getContainerGUID());

group_gatekeeper();

if (!isset($task->csv_file_id) || $isRebuildRequested) {
    $response = wespot_arlearn_create_csv_file($task->getContainerGUID());
    if ($response == false) {
        system_messages(elgg_echo('wespot_arlearn:export:status:failure'));
    } else {
        $task->csv_file_id = $response->id;
        $task->save();
        
        $status = $response->status;
        if (CSV_FILE_STATUS_FINISHED == $status) {
            $url = getARLearnCsvFileURL($response->id);
            if (isset($url)) {
                forward($url);
            }        
        } else {
            system_messages(elgg_echo('wespot_arlearn:export:status:processing'));
        }
    }
} else {
    $status = wespot_arlearn_csv_file_status($task->csv_file_id);
    if (CSV_FILE_STATUS_BUILDING == $status) {
        system_messages(elgg_echo('wespot_arlearn:export:status:processing'));
    } else if (CSV_FILE_STATUS_FINISHED == $status) {
        // do download
        $url = getARLearnCsvFileURL($task->csv_file_id);
        if (isset($url)) {
            forward($url);
        }
    }
} 

forward(REFERER);
