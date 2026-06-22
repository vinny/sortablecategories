<?php
/**
*
* Sortable Categories extension for the phpBB Forum Software package.
*
* @copyright (c) 2026 Vinny
* @license GNU General Public License, version 2 (GPL-2.0)
*
*/

namespace vinny\sortablecategories\tests\event;

class mock_template_listener implements \phpbb\template\template
{
	public $assigned_vars = [];

	public function clear_cache() {}
	public function set_filenames(array $filename_array) {}
	public function get_user_style() {}
	public function set_style($style_directories = array('styles')) {}
	public function set_custom_style($names, $paths) {}
	public function destroy() {}
	public function destroy_block_vars($blockname) {}
	public function display($handle) {}
	public function assign_display($handle, $template_var = '', $return_content = true) {}
	public function assign_vars(array $vararray)
	{
		$this->assigned_vars = array_merge($this->assigned_vars, $vararray);
		return true;
	}
	public function assign_var($varname, $varval) {}
	public function append_var($varname, $varval) {}
	public function retrieve_vars(array $vararray) {}
	public function retrieve_var($varname) {}
	public function assign_block_vars($blockname, array $vararray) {}
	public function assign_block_vars_array($blockname, array $block_vars_array) {}
	public function retrieve_block_vars($blockname, array $vararray) {}
	public function alter_block_array($blockname, array $vararray, $key = false, $mode = 'insert') {}
	public function find_key_index($blockname, $key) {}
	public function get_source_file_for_handle($handle) {}
}

class mock_user_listener extends \phpbb\user
{
	public $data = [];
	public $lang_ext_loaded = [];

	public function __construct() {}

	public function add_lang_ext($ext_name, $lang_set, $use_db = false, $use_help = false)
	{
		$this->lang_ext_loaded[] = [$ext_name, $lang_set];
		return;
	}
}

class mock_request_listener implements \phpbb\request\request_interface
{
	public $variables = [];

	public function variable($var_name, $default, $multibyte = false, $super_global = \phpbb\request\request_interface::REQUEST)
	{
		return isset($this->variables[$var_name]) ? $this->variables[$var_name] : $default;
	}

	public function overwrite($var_name, $value, $super_global = \phpbb\request\request_interface::REQUEST) {}
	public function raw_variable($var_name, $default, $super_global = \phpbb\request\request_interface::REQUEST) { return $default; }
	public function server($var_name, $default = '') { return $default; }
	public function header($header_name, $default = '') { return $default; }
	public function is_set_post($name) { return false; }
	public function is_set($var, $super_global = \phpbb\request\request_interface::REQUEST) { return isset($this->variables[$var]); }
	public function is_ajax() { return false; }
	public function is_secure() { return false; }
	public function variable_names($super_global = \phpbb\request\request_interface::REQUEST) { return array_keys($this->variables); }
	public function get_super_global($super_global = \phpbb\request\request_interface::REQUEST) { return $this->variables; }
	public function escape($value, $multibyte) { return $value; }
}

class mock_db_listener extends \phpbb\db\driver\driver
{
	public $queries = [];

	public function sql_server_info($raw = false, $use_cache = true) { return 'mock'; }
	public function sql_fetchrow($query_id = false) { return false; }
	public function sql_last_inserted_id() { return 0; }
	public function sql_query($query = '', $cache_ttl = 0)
	{
		$this->queries[] = $query;
		return true;
	}
	public function sql_connect($sqlserver, $sqluser, $sqlpassword, $database, $port = false, $persistency = false, $new_link = false) { return true; }
	public function sql_freeresult($query_id = false) { return true; }
	public function sql_affectedrows() { return 0; }
	public function sql_escape($msg) { return $msg; }
	public function sql_quote($msg) { return "'" . $msg . "'"; }
}

class mock_controller_helper_listener extends \phpbb\controller\helper
{
	public function __construct() {}
}

class listener_test extends \PHPUnit\Framework\TestCase
{
	protected $db;
	protected $user;
	protected $template;
	protected $controller_helper;
	protected $request;
	protected $listener;

	public function setUp(): void
	{
		$this->db = new mock_db_listener();
		$this->user = new mock_user_listener();
		$this->template = new mock_template_listener();
		$this->controller_helper = new mock_controller_helper_listener();
		$this->request = new mock_request_listener();

		$this->listener = new \vinny\sortablecategories\event\listener(
			$this->db,
			$this->user,
			$this->template,
			$this->controller_helper,
			$this->request,
			'phpbb_'
		);
	}

	public function test_getSubscribedEvents()
	{
		$events = $this->listener::getSubscribedEvents();
		$this->assertArrayHasKey('core.index_modify_page_title', $events);
		$this->assertArrayHasKey('core.display_forums_before', $events);
		$this->assertArrayHasKey('core.ucp_prefs_view_data', $events);
		$this->assertArrayHasKey('core.ucp_prefs_view_update_data', $events);
	}

	public function test_ucp_prefs_view_data_get()
	{
		$this->user->data['user_sortable_categories'] = true;

		$event = new \ArrayObject([
			'data' => [],
			'submit' => false,
		], \ArrayObject::ARRAY_AS_PROPS);

		$this->listener->ucp_prefs_view_data($event);

		$data = $event['data'];
		$this->assertTrue($data['user_sortable_categories']);
		$this->assertArrayHasKey('S_SORTABLE_CATEGORIES_ENABLED', $this->template->assigned_vars);
		$this->assertTrue($this->template->assigned_vars['S_SORTABLE_CATEGORIES_ENABLED']);

		// Verify language files are loaded
		$this->assertCount(1, $this->user->lang_ext_loaded);
		$this->assertEquals('vinny/sortablecategories', $this->user->lang_ext_loaded[0][0]);
		$this->assertEquals('common', $this->user->lang_ext_loaded[0][1]);
	}

	public function test_ucp_prefs_view_data_submit()
	{
		$event = new \ArrayObject([
			'data' => [],
			'submit' => true,
		], \ArrayObject::ARRAY_AS_PROPS);

		$this->request->variables['user_sortable_categories'] = false;

		$this->listener->ucp_prefs_view_data($event);

		$data = $event['data'];
		$this->assertFalse($data['user_sortable_categories']);
		$this->assertArrayHasKey('S_SORTABLE_CATEGORIES_ENABLED', $this->template->assigned_vars);
		$this->assertFalse($this->template->assigned_vars['S_SORTABLE_CATEGORIES_ENABLED']);
	}

	public function test_ucp_prefs_view_update_data_no()
	{
		$this->user->data['user_id'] = 42;

		$event = new \ArrayObject([
			'data' => ['user_sortable_categories' => false],
			'sql_ary' => [],
		], \ArrayObject::ARRAY_AS_PROPS);

		$this->listener->ucp_prefs_view_update_data($event);

		$sql_ary = $event['sql_ary'];
		$this->assertEquals(0, $sql_ary['user_sortable_categories']);

		// Verify DELETE query was executed
		$this->assertCount(1, $this->db->queries);
		$this->assertStringContainsString('DELETE FROM phpbb_sortablecategories_user_order', $this->db->queries[0]);
		$this->assertStringContainsString('WHERE user_id = 42', $this->db->queries[0]);
	}

	public function test_ucp_prefs_view_update_data_yes()
	{
		$this->user->data['user_id'] = 42;

		$event = new \ArrayObject([
			'data' => ['user_sortable_categories' => true],
			'sql_ary' => [],
		], \ArrayObject::ARRAY_AS_PROPS);

		$this->listener->ucp_prefs_view_update_data($event);

		$sql_ary = $event['sql_ary'];
		$this->assertEquals(1, $sql_ary['user_sortable_categories']);

		// Verify NO queries executed
		$this->assertCount(0, $this->db->queries);
	}
}
