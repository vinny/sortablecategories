<?php
/**
*
* Sortable Categories extension for the phpBB Forum Software package.
*
* @copyright (c) 2026 Vinny
* @license GNU General Public License, version 2 (GPL-2.0)
*
*/

namespace vinny\sortablecategories\controller;

use Symfony\Component\HttpFoundation\JsonResponse;

class ajax
{
	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\request\request_interface */
	protected $request;

	/** @var string */
	protected $table_prefix;

	/**
	 * Constructor
	 */
	public function __construct(\phpbb\db\driver\driver_interface $db, \phpbb\user $user, \phpbb\request\request_interface $request, $table_prefix)
	{
		$this->db = $db;
		$this->user = $user;
		$this->request = $request;
		$this->table_prefix = $table_prefix;

		$this->user->add_lang_ext('vinny/sortablecategories', 'common');
	}

	/**
	 * Save AJAX category ordering payload
	 */
	public function save()
	{
		if (!$this->user->data['is_registered'])
		{
			return new JsonResponse([
				'status'  => 'error',
				'message' => $this->user->lang('SORTABLE_CATEGORIES_LOGIN_REQUIRED')
			], 403);
		}

		if (!check_form_key('sortablecategories'))
		{
			return new JsonResponse([
				'status'  => 'error',
				'message' => $this->user->lang('SORTABLE_CATEGORIES_INVALID_TOKEN')
			], 400);
		}

		$order = $this->request->variable('order', [0]);

		if (empty($order))
		{
			return new JsonResponse([
				'status'  => 'error',
				'message' => $this->user->lang('SORTABLE_CATEGORIES_NO_ORDER')
			], 400);
		}

		// Fetch all valid root-level category/forum IDs
		$sql = 'SELECT forum_id FROM ' . FORUMS_TABLE . ' WHERE parent_id = 0';
		$result = $this->db->sql_query($sql);
		$valid_ids = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$valid_ids[] = (int) $row['forum_id'];
		}
		$this->db->sql_freeresult($result);

		// Sanitize, de-duplicate, and validate the input order
		$order = array_map('intval', $order);
		$order = array_unique($order);
		$order = array_intersect($order, $valid_ids);

		if (empty($order))
		{
			return new JsonResponse([
				'status'  => 'error',
				'message' => $this->user->lang('SORTABLE_CATEGORIES_NO_ORDER')
			], 400);
		}

		$user_id = (int) $this->user->data['user_id'];

		// Delete existing ordering database entries for this user
		$sql = 'DELETE FROM ' . $this->table_prefix . 'sortablecategories_user_order
			WHERE user_id = ' . (int) $user_id;
		$this->db->sql_query($sql);

		// Insert updated display orders
		$insert_data = [];
		$index = 0;
		foreach ($order as $category_id)
		{
			$insert_data[] = [
				'user_id'       => $user_id,
				'category_id'   => $category_id,
				'display_order' => $index++,
			];
		}

		if (!empty($insert_data))
		{
			$this->db->sql_multi_insert($this->table_prefix . 'sortablecategories_user_order', $insert_data);
		}

		return new JsonResponse([
			'status' => 'success'
		]);
	}
}
