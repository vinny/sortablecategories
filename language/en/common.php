<?php
/**
*
* Sortable Categories extension for the phpBB Forum Software package.
*
* @copyright (c) 2026 Vinny <https://github.com/vinny>
* @license GNU General Public License, version 2 (GPL-2.0)
*
*/

if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

$lang = array_merge($lang, array(
	'SORTABLE_CATEGORIES_DRAG'           => 'Drag to reorder',
	'SORTABLE_CATEGORIES_LOGIN_REQUIRED' => 'Login required',
	'SORTABLE_CATEGORIES_INVALID_TOKEN'  => 'Invalid security token',
	'SORTABLE_CATEGORIES_NO_ORDER'       => 'No order data provided',
));
