<?php
// phpcs:ignoreFile
/**
*
* Sortable Categories extension for the phpBB Forum Software package.
*
* @copyright (c) 2026 Vinny
* @license GNU General Public License, version 2 (GPL-2.0)
*
*/

define('IN_PHPBB', true);

// Load phpBB's autoloader if available (relative to board root)
$board_vendor = __DIR__ . '/../../../vendor/autoload.php';
if (file_exists($board_vendor))
{
	require_once $board_vendor;
}

// Define tables if not already defined
if (!defined('FORUMS_TABLE'))
{
	define('FORUMS_TABLE', 'phpbb_forums');
}

// Define helper functions from phpBB core
if (!function_exists('add_form_key'))
{
	function add_form_key($form_name, $token_name = '_token')
	{
	}
}

if (!function_exists('check_form_key'))
{
	function check_form_key($form_name, $token_lifetime = 0)
	{
		return true;
	}
}
