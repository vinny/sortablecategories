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

class install_schema extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_table_exists($this->table_prefix . 'sortablecategories_user_order');
	}

	static public function depends_on()
	{
		return ['\phpbb\db\migration\data\v330\v330'];
	}

	public function update_schema()
	{
		return [
			'add_tables' => [
				$this->table_prefix . 'sortablecategories_user_order' => [
					'COLUMNS' => [
						'user_id'			=> ['UINT', 0],
						'category_id'		=> ['UINT', 0],
						'display_order'		=> ['UINT', 0],
					],
					'PRIMARY_KEY' => ['user_id', 'category_id'],
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_tables' => [
				$this->table_prefix . 'sortablecategories_user_order',
			],
		];
	}
}
