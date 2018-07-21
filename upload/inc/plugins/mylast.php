<?php

/**
 * MyLast : A Plugin for MyBB to jump to the last post of the user in a thread.
 * 
 * @package MyBB Plugin
 * @author effone <effone@mybb.com>
 * @copyright 2018 MyBB Group <http://mybb.group>
 * @version 1.0.0
 * @license GPL-3.0
 * 
 */

// Disallow direct access to this file for security reasons
if (!defined("IN_MYBB")) {
	die("Direct initialization of this file is not allowed.");
}

// Basic Informations about the Testimonial plugin
function mylast_info()
{
	return array(
		'name' => 'MyLast',
		'description' => 'Add a link to any thread where you have posted to jump to your last post.',
		'website' => 'https://github.com/mybbgroup/mylast',
		'author' => 'effone',
		'authorsite' => 'https://eff.one',
		'version' => '1.0.0',
		'guid' => '9ffad6bb852463a3229108d23e780bee', // Old 16* GUID
		'compatibility' => '18*'
	);
}

// Hooks :o
$plugins->add_hook('showthread_start', 'mylast_generate');
$plugins->add_hook('forumdisplay_thread', 'mylast_threadlink');

function mylast_threadlink()
{
	global $db, $mybb, $lang, $thread, $mylast, $mylast_cl, $mylast_tip;
	$lang->load("mylast");

	$mylast = $mylast_cl = $mylast_tip = "";
	if ($mybb->user['uid'] && $thread['doticon']) {
		$mylast = "<a href='showthread.php?tid=" . $thread['tid'] . "&action=mylastpost'>";
		$mylast_cl = "</a>";
		$mylast_tip = " " . $lang->mylast_tooltip;
	}
}

function mylast_generate()
{
	global $db, $mybb, $lang, $thread, $mylastpostlink, $templates;
	$lang->load("mylast");
	$tid = $thread['tid'];
	
	// Only generate the link if the user appears to be logged in.
	if ($mybb->user['uid']) {
		eval("\$mylastpostlink = \"" . $templates->get("mylast_link") . "\";");

		if ($mybb->input['action'] == "mylastpost") {
			$query = $db->query("
			SELECT pid
			FROM " . TABLE_PREFIX . "posts
			WHERE dateline = ( 
			SELECT MAX( dateline ) 
			FROM " . TABLE_PREFIX . "posts
			WHERE uid =" . (int)$mybb->user['uid'] . " && tid ={$tid} )
			");
			$mylastpost = $db->fetch_array($query);
			$mylastpid = $mylastpost['pid'];

			if (!$mylastpid) {
				error($lang->mylast_nopost);
			}

			redirect('showthread.php?tid=' . $tid . '&pid=' . $mylastpid . '#pid' . $mylastpid, $lang->mylast_redirnote);
		}
	}
}

function mylast_activate()
{
	require_once MYBB_ROOT . "/inc/adminfunctions_templates.php";

	global $db, $mybb, $lang;
	//$lang->load("mylast");

	$template = array(
		"title" => "mylast_link",
		"template" => '
		<li class="mylastpost"><a href="showthread.php?tid={$tid}&action=mylastpost" title="{\$lang->mylast_tooltip}">{\$lang->mylast_linkline}
		</a></li><style type="text/css">ul.thread_tools li.mylastpost {background: url(images/lastpost.gif) no-repeat 0px 0px;}</style>',
		"sid" => -1
	);
	$db->insert_query("templates", $template);

	find_replace_templatesets('showthread', '#{\$addpoll}#', '{\$addpoll}<!-- MyLast -->{$mylastpostlink}<!-- /MyLast -->');
	find_replace_templatesets('forumdisplay_thread', '#width="2%"><span class=#', 'width="2%">{\$mylast}<span class=');
	find_replace_templatesets('forumdisplay_thread', '#</span></td>#', '</span>{\$mylast_cl}</td>');
	find_replace_templatesets('forumdisplay_thread', '#{\$folder_label}#', '{\$folder_label}{\$mylast_tip}');
}

function mylast_deactivate()
{
	require_once MYBB_ROOT . '/inc/adminfunctions_templates.php';

	global $db, $mybb;
	$db->query("DELETE FROM " . TABLE_PREFIX . "templates WHERE title='mylast_link'");

	find_replace_templatesets('showthread', '#\<!--\sMyLast\s--\>(.+)\<!--\s/MyLast\s--\>#is', '', 0);
	find_replace_templatesets('forumdisplay_thread', '#\{\$mylast}#is', '', 0);
	find_replace_templatesets('forumdisplay_thread', '#\{\$mylast_cl}#is', '', 0);
	find_replace_templatesets('forumdisplay_thread', '#\{\$mylast_tip}#is', '', 0);
}