<?php
/**
*
* Sortable Categories extension for the phpBB Forum Software package.
*
* @copyright (c) 2026 Vinny
* @license GNU General Public License, version 2 (GPL-2.0)
*
*/

namespace vinny\sortablecategories\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface
{
	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\controller\helper */
	protected $controller_helper;

	/** @var string */
	protected $table_prefix;

	/**
	 * Constructor
	 */
	public function __construct(
		\phpbb\db\driver\driver_interface $db,
		\phpbb\user $user,
		\phpbb\template\template $template,
		\phpbb\controller\helper $controller_helper,
		$table_prefix
	) {
		$this->db = $db;
		$this->user = $user;
		$this->template = $template;
		$this->controller_helper = $controller_helper;
		$this->table_prefix = $table_prefix;
	}

	/**
	 * Assign subscribed events
	 */
	static public function getSubscribedEvents()
	{
		return [
			'core.page_header'           => 'page_header',
			'core.display_forums_before' => 'display_forums_before',
		];
	}

	/**
	 * Pass AJAX endpoint and CSRF hash to the templates
	 */
	public function page_header($event)
	{
		// Only enable sorting parameters for registered users on the index page
		if ($this->user->data['is_registered'])
		{
			$this->user->add_lang_ext('vinny/sortablecategories', 'common');

			$this->template->assign_vars([
				'S_SORTABLE_CATEGORIES_ACTIVE' => true,
				'SORTABLE_CATEGORIES_HASH'   => generate_link_hash('sortablecategories'),
				'SORTABLE_CATEGORIES_URL'    => $this->controller_helper->route('vinny_sortablecategories_save'),
			]);
		}
	}

	/**
	 * Rearrange categories according to user custom order
	 */
	public function display_forums_before($event)
	{
		// Skip sorting for guests, or if there are no forums to sort
		if (!$this->user->data['is_registered'])
		{
			return;
		}

		$forum_rows = $event['forum_rows'];
		if (empty($forum_rows))
		{
			return;
		}

		$user_id = (int) $this->user->data['user_id'];
		$root_data = $event['root_data'];
		$root_id = (int) $root_data['forum_id'];

		$sql = 'SELECT category_id FROM ' . $this->table_prefix . 'users_category_order
			WHERE user_id = ' . (int) $user_id . '
			ORDER BY display_order ASC';
		$result = $this->db->sql_query($sql);
		
		$custom_order = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$custom_order[] = (int) $row['category_id'];
		}
		$this->db->sql_freeresult($result);

		// If user has never sorted any categories, keep default phpBB order
		if (empty($custom_order))
		{
			return;
		}

		$grouped = [];
		$current_cat_id = 0;

		foreach ($forum_rows as $row)
		{
			$forum_id = (int) $row['forum_id'];
			$parent_id = (int) $row['parent_id'];

			if ($parent_id === $root_id)
			{
				// Top-level item (Category or Catless Forum)
				$current_cat_id = $forum_id;
				$grouped[$current_cat_id] = [
					'item'     => $row,
					'children' => []
				];
			}
			else
			{
				// Sub-item (Forum or Link) belonging to a top-level category
				if (isset($grouped[$parent_id]))
				{
					$grouped[$parent_id]['children'][] = $row;
				}
				else
				{
					// Fallback safety if the parent category isn't grouped yet
					if ($current_cat_id > 0 && isset($grouped[$current_cat_id]))
					{
						$grouped[$current_cat_id]['children'][] = $row;
					}
					else
					{
						// Create a fallback bucket
						$grouped[0]['children'][] = $row;
					}
				}
			}
		}

		$sorted_grouped = [];

		// First, place categories matching the custom order
		foreach ($custom_order as $cat_id)
		{
			if (isset($grouped[$cat_id]))
			{
				$sorted_grouped[$cat_id] = $grouped[$cat_id];
				unset($grouped[$cat_id]);
			}
		}

		// Then, append any remaining categories or items
		foreach ($grouped as $cat_id => $data)
		{
			$sorted_grouped[$cat_id] = $data;
		}

		$sorted_rows = [];
		foreach ($sorted_grouped as $data)
		{
			if (isset($data['item']))
			{
				$sorted_rows[] = $data['item'];
			}
			foreach ($data['children'] as $child)
			{
				$sorted_rows[] = $child;
			}
		}

		// Update the event's data set
		$event['forum_rows'] = $sorted_rows;
	}
}
