<?php

/**
*
* Sortable Categories extension for the phpBB Forum Software package.
*
* @copyright (c) 2026 Vinny
* @license GNU General Public License, version 2 (GPL-2.0)
*
*/

namespace vinny\sortablecategories\migrations\v100;

class add_user_pref_column extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return [
			'\vinny\sortablecategories\migrations\v100\install_schema',
		];
	}

	public function update_schema()
	{
		return [
			'add_columns' => [
				$this->table_prefix . 'users' => [
					'user_sortable_categories' => ['BOOL', 1],
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_columns' => [
				$this->table_prefix . 'users' => [
					'user_sortable_categories',
				],
			],
		];
	}
}
