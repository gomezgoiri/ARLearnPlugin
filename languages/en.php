<?php
/**
 * ARLearn data collection tasks languages
 *
 * @package wespot_arlearn
 */

$english = array(

	/**
	 * Menu items and titles
	 */

	'wespot_arlearn' => "ARLearn data collection tasks",
	'wespot_arlearn:owner' => "%s's data collection tasks",
	'wespot_arlearn:friends' => "Friends' data collection tasks",
	'wespot_arlearn:all' => "All site data collection tasks",
	'wespot_arlearn:add' => "Add data collection task",

	/**
	 * Using 'group' does not work - has old cached version with task at end
	 * So created a second variable group2 and that works.
	 * No Idea whast is going on here.
	 * It is used in the start.php file in the function wespot_arlearn_owner_block_menu
	 */
	'wespot_arlearn:group' => "ARLearn data collection",
	'groups:enablewespot_arlearn' => 'Enable data collection with the ARLearn mobile app',

	'wespot_arlearn:edit' => "Edit this data collection task",
	'wespot_arlearn:delete' => "Delete this data collection task",
	'wespot_arlearn:history' => "History",
    'wespot_arlearn:download' => "Download",
	'wespot_arlearn:view' => "View data collection task",
	'wespot_arlearn:revision' => "Revision",

	'wespot_arlearn:navigation' => "Navigation",
	'wespot_arlearn:via' => "via tasks",
	'item:object:arlearntask_top' => 'Data collections tasks',
	'item:object:arlearntask' => 'Results',
	'wespot_arlearn:nogroup' => 'This inquiry does not have any data collection tasks yet',
	'wespot_arlearn:more' => 'More data collection tasks',
	'wespot_arlearn:none' => 'No data collection tasks created yet',
	'wespot_arlearn:new' => 'New data collection task',

	/**
	* River
	**/
	'river:create:object:arlearntask' => '%s created a data collection task result %s',
	'river:create:object:arlearntask_top' => '%s created a data collection task %s',
	'river:update:object:arlearntask' => '%s updated a data collection task  result %s',
	'river:update:object:arlearntask_top' => '%s updated a data collection task %s',
	'river:comment:object:arlearntask' => '%s commented on a data collection task result titled %s',
	'river:comment:object:arlearntask_top' => '%s commented on a data collection task titled %s',

	/**
	 * Form fields
	 */
	'wespot_arlearn:title' => 'Task title',
	'wespot_arlearn:description' => 'Task description',
	'wespot_arlearn:tags' => 'Tags',
	'wespot_arlearn:access_id' => 'Read access',
	'wespot_arlearn:write_access_id' => 'Write access',

	/**
	 * Status and error messages
	 */
	'wespot_arlearn:noaccess' => 'No access to task',
	'wespot_arlearn:cantedit' => 'You cannot edit this task',
	'wespot_arlearn:saved' => 'Task saved',
	'wespot_arlearn:notsaved' => 'Task could not be saved',
	'wespot_arlearn:error:no_title' => 'You must specify a title for this task.',
	'wespot_arlearn:error:no_type' => 'You must specify a data type for this task.',
	'wespot_arlearn:error:no_save_game' => 'The data collection option could not be added to the group inquiry at this time. Possibly due to communication errors with the ARLearn services.',
	'wespot_arlearn:error:no_delete_game' => 'The data collection option could not be removed from the group inquiry at this time. Possibly due to communication errors with the ARLearn services.',
	'wespot_arlearn:error:no_save_task' => 'The data collection task could not be saved at this time. Possibly due to communication errors with the ARLearn services.',
	'wespot_arlearn:error:no_save_user' => 'The new user could not be added to the group inquiry at this time. Possibly due to communication errors with the ARLearn services.',
	'wespot_arlearn:delete:success' => 'The task was successfully deleted.',
	'wespot_arlearn:delete:failure' => 'The task could not be deleted.',
	/**
	 * Export Messages
	 */
    'wespot_arlearn:export:csv' => 'export as csv file',     
    'wespot_arlearn:export:rebuild' => 'rebuild the csv file',         
	'wespot_arlearn:export:status:processing' => 'The export is not yet processed. Please try again later.',
    'wespot_arlearn:export:status:failure' => 'The export request has failed. Please try again later.',
    
	/**
	 * Task
	 */
	'wespot_arlearn:strapline' => 'Last updated %s by %s',

	/**
	 * History
	 */
	'wespot_arlearn:revision:subtitle' => 'Revision created %s by %s',

	/**
	 * Widget
	 **/

	'wespot_arlearn:num' => 'Number of tasks to display',
	'wespot_arlearn:widget:description' => "This is a list of ARLearn data collection tasks.",
	'wespot_arlearn:widget:message' => "You can enable ARLearn data collection by editing this inquiry",

	/**
	 * Submenu items
	 */
	'wespot_arlearn:label:view' => "View data collection task",
	'wespot_arlearn:label:edit' => "Edit data collection task",
	'wespot_arlearn:label:history' => "Data Collection Task history",

	/**
	 * Sidebar items
	 */
	'wespot_arlearn:sidebar:this' => "This data collection task",
	'wespot_arlearn:sidebar:children' => "Results",
	'wespot_arlearn:sidebar:parent' => "Task",

	'wespot_arlearn:newchild' => "Create a sub-task",
	'wespot_arlearn:backtoparent' => "Back to '%s'",

	// Left these as Steefan said we may want to instroduce dates against tasks at some point.
	 'wespot_arlearn:start_date' => "Start",
	 'wespot_arlearn:end_date' => "End",

	 'wespot_arlearn:task_type' => 'Data Type',
	 'wespot_arlearn:status' => 'Status',

	 'wespot_arlearn:task_type_'=>"Select a data type...",
	 'wespot_arlearn:task_type_0'=>"Pictures",
	 'wespot_arlearn:task_type_1'=>"Video",
	 'wespot_arlearn:task_type_2'=>"Audio",
	 'wespot_arlearn:task_type_3'=>"Text",
	 'wespot_arlearn:task_type_4'=>"Numeric",

	 'wespot_arlearn:type_1_label'=>" - video recording",
	 'wespot_arlearn:type_2_label'=>" - audio recording",
	 'wespot_arlearn:type_0_label'=>" - photo",
	 'wespot_arlearn:show_results'=>"Show Results",
	 'wespot_arlearn:no_results'=>"No results yet",
	 'wespot_arlearn:num_results'=>"Result",
	 'wespot_arlearn:nums_results'=>"Results",

	 'wespot_arlearn:tasksboard'=>"TasksBoard",
	 'wespot_arlearn:tasksmanage'=>"Manage",
	 'wespot_arlearn:tasksmanageone'=>"Manage a data collection task",

	/**
	 * Plugin Settings
	 */
	 'wespot_arlearn:arlearnurl' => "ARLearn server url (please include a final '/')",
	 'wespot_arlearn:arlearnkey' => "ARLearn API key",
);

add_translation("en", $english);