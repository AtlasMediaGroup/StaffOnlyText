<?php
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.");
}

$plugins->add_hook("parse_message", "staff_only_text_run");

function staff_only_text_info()
{
global $mybb;
	return array(
		"name"				=> "Staff Only Text",
		"description"		=> "Staff only viewable text",
		"author"			=> "scripthead",
		"authorsite"		=> "https://scripthead.me",
		"version"			=> "1.0.0",
		"codename"			=> "staff_only_text",
		"compatibility"		=> "*",
	);
}

function staff_only_text_activate()
{
    global $mybb, $db;
    
    $settingGroupId = $db->insert_query('settinggroups', [
        'name'        => 'staff_only_text',
        'title'       => 'Staff Only Text',
        'description' => 'Settings for staff only text',
    ]);

    $settings = [
        [
            'name'        => 'staff_only_text_groups',
            'title'       => 'Staff Groups',
            'description' => 'Groups that can view staff only text',
            'optionscode' => 'groupselect',
            'value'       => '',
        ],
    ];

    $i = 1;

    foreach ($settings as &$row) {
		$row['gid']         = $settingGroupId;
        $row['title']       = $db->escape_string($row['title']);
        $row['description'] = $db->escape_string($row['description']);
        $row['disporder']   = $i++;
    }

    $db->insert_query_multiple('settings', $settings);

    rebuild_settings();
}

function staff_only_text_deactivate()
{
    global $db;

    $settingGroupId = $db->fetch_field(
        $db->simple_select('settinggroups', 'gid', "name='staff_only_text'"),
        'gid'
    );

    $db->delete_query('settinggroups', 'gid=' . (int)$settingGroupId);
    $db->delete_query('settings', 'gid=' . (int)$settingGroupId);

    rebuild_settings();
}

function has_permission()
{
	global $mybb;
 
	$allowed_groups = $mybb->settings['staff_only_text_groups'];
	
	if (empty($allowed_groups))
	{
		return false;
	}
	
	if ($allowed_groups == "-1")
	{
		return true;
	}
	
	$usergroup = $mybb->user['usergroup'];
	$allowed = explode(",", $allowed_groups);
	$groups = array();
	$groups[0] = (int)$usergroup; 
	$add_groups = explode(",", $mybb->user['additionalgroups']);
	$count = 1;
	
	foreach ($add_groups as $new_group)
	{
		$groups[$count] = $new_group;
		$count++;
	}
	
	foreach ($allowed as $allowed_group)
	{
		if (in_array($allowed_group, $groups))
		{
			return true;
		}
	}
	
	return false;
}

function staff_only_text_run(&$message)
{
	while (preg_match('#\[staff\](.*?)\[\/staff\]#si',$message))
	{
		if (has_permission())
		{
			$message = preg_replace('#\[staff\](.*?)\[\/staff\]#si','$1',$message);
		}
		else
		{
			$message = preg_replace('#\[staff\](.*?)\[\/staff\]#si','Only staff may view this text',$message);
		}
	}
	return $message;
}