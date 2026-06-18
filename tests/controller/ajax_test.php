<?php
/**
*
* Sortable Categories extension for the phpBB Forum Software package.
*
* @copyright (c) 2026 Vinny <https://github.com/vinny>
* @license GNU General Public License, version 2 (GPL-2.0)
*
*/

namespace vinny\sortablecategories\tests\controller;

class mock_language
{
	protected $lang = [
		'SORTABLE_CATEGORIES_LOGIN_REQUIRED' => 'Login required',
		'SORTABLE_CATEGORIES_INVALID_TOKEN' => 'Invalid security token',
		'SORTABLE_CATEGORIES_NO_ORDER' => 'No order data provided',
	];

	public function get_lang_array()
	{
		return $this->lang;
	}

	public function add_lang($lang_set, $ext_name = '')
	{
		return;
	}

	public function lang($key)
	{
		return isset($this->lang[$key]) ? $this->lang[$key] : $key;
	}
}

class mock_user extends \phpbb\user
{
	public $data = [];
	public $session_id = '';
	public $language;
	public function __construct()
	{
		$this->language = new mock_language();
	}
}

class mock_request implements \phpbb\request\request_interface
{
	public $variables = [];
	public $headers = [];

	public function overwrite($var_name, $value, $super_global = \phpbb\request\request_interface::REQUEST) {}

	public function variable($var_name, $default, $multibyte = false, $super_global = \phpbb\request\request_interface::REQUEST)
	{
		return isset($this->variables[$var_name]) ? $this->variables[$var_name] : $default;
	}

	public function raw_variable($var_name, $default, $super_global = \phpbb\request\request_interface::REQUEST)
	{
		return $default;
	}

	public function server($var_name, $default = '')
	{
		return $default;
	}

	public function header($header_name, $default = '')
	{
		return isset($this->headers[$header_name]) ? $this->headers[$header_name] : $default;
	}

	public function is_set_post($name)
	{
		return false;
	}

	public function is_set($var, $super_global = \phpbb\request\request_interface::REQUEST)
	{
		return isset($this->variables[$var]);
	}

	public function is_ajax()
	{
		return false;
	}

	public function is_secure()
	{
		return false;
	}

	public function variable_names($super_global = \phpbb\request\request_interface::REQUEST)
	{
		return array_keys($this->variables);
	}

	public function get_super_global($super_global = \phpbb\request\request_interface::REQUEST)
	{
		return $this->variables;
	}

	public function escape($value, $multibyte)
	{
		return $value;
	}
}

/**
* phpBB AJAX Controller Database Integration Test
*/
class ajax_test extends \phpbb_database_test_case
{
	/** @var string */
	protected $table_prefix;

	/** @var \vinny\sortablecategories\controller\ajax */
	protected $ajax_controller;

	/** @var mock_user */
	protected $user_mock;

	/** @var mock_request */
	protected $request_mock;

	/**
	* Define extensions to load
	*/
	static protected function setup_extensions()
	{
		return array('vinny/sortablecategories');
	}

	/**
	* Define XML database fixtures/dataset
	*/
	public function getDataSet()
	{
		return $this->createXMLDataSet(dirname(__FILE__) . '/fixtures/base_setup.xml');
	}

	/**
	* Set up test case dependencies
	*/
	public function setUp(): void
	{
		parent::setUp();

		global $table_prefix;
		$this->table_prefix = $table_prefix;

		// Use the real DB connection from the test case
		$this->db_mock = $this->new_dbal();
	}

	/**
	* Tear down test case dependencies
	*/
	public function tearDown(): void
	{
		parent::tearDown();
	}

	/**
	* Helper to instantiate controller with mocked user and request objects
	*
	* @param bool $is_registered
	* @param bool $is_bot
	* @param string $request_method
	* @param string $hash
	* @param array $order
	*/
	protected function setup_controller($is_registered, $is_bot, $request_method, $hash, $order)
	{
		// Mock user session
		$this->user_mock = new mock_user();
		$this->user_mock->data = array(
			'user_id'		=> 2,
			'is_registered'	=> $is_registered,
			'is_bot'		=> $is_bot,
			'user_form_salt' => 'some_salt',
		);

		// Mock request object
		$this->request_mock = new mock_request();
		$this->request_mock->headers['REQUEST_METHOD'] = $request_method;
		$this->request_mock->variables['hash'] = $hash;
		$this->request_mock->variables['order'] = $order;

		$this->ajax_controller = new \vinny\sortablecategories\controller\ajax(
			$this->db_mock,
			$this->user_mock,
			$this->request_mock,
			$this->table_prefix
		);
	}

	/**
	* Test saving category order successfully
	*/
	public function test_save_order_success()
	{
		global $user;
		$old_user = $user;

		// Set up controller first so $this->user_mock is instantiated
		$this->setup_controller(true, false, 'POST', '', array(3, 4, 1));

		// Session ID and link hash verification using phpBB core function
		$session_id = 'test_session_id';
		$this->user_mock->session_id = $session_id;
		$user = $this->user_mock;

		$valid_hash = generate_link_hash('sortablecategories');
		$this->request_mock->variables['hash'] = $valid_hash;

		$response = $this->ajax_controller->save();

		// Restore global user
		$user = $old_user;

		$this->assertInstanceOf('\Symfony\Component\HttpFoundation\JsonResponse', $response);
		$this->assertEquals(200, $response->getStatusCode());
		$this->assertEquals('{"status":"success"}', $response->getContent());

		// Verify database entries
		$sql = 'SELECT * FROM ' . $this->table_prefix . 'users_category_order
			WHERE user_id = 2
			ORDER BY display_order ASC';
		$result = $this->db_mock->sql_query($sql);

		$rows = array();
		while ($row = $this->db_mock->sql_fetchrow($result))
		{
			$rows[] = $row;
		}
		$this->db_mock->sql_freeresult($result);

		$this->assertCount(3, $rows);
		$this->assertEquals(3, $rows[0]['category_id']);
		$this->assertEquals(0, $rows[0]['display_order']);
		$this->assertEquals(4, $rows[1]['category_id']);
		$this->assertEquals(1, $rows[1]['display_order']);
		$this->assertEquals(1, $rows[2]['category_id']);
		$this->assertEquals(2, $rows[2]['display_order']);
	}

	/**
	* Test saving category order requires authenticated user
	*/
	public function test_save_order_requires_login()
	{
		$this->setup_controller(false, false, 'POST', 'some_hash', array(3, 4, 1));

		$response = $this->ajax_controller->save();

		$this->assertInstanceOf('\Symfony\Component\HttpFoundation\JsonResponse', $response);
		$this->assertEquals(403, $response->getStatusCode());
		$this->assertEquals('{"status":"error","message":"Login required"}', $response->getContent());
	}
}
