<?php
namespace EventEspresso\core\libraries\rest_api\controllers\model;
use EventEspresso\core\libraries\rest_api\controllers\Base as Controller_Base;
if ( !defined( 'EVENT_ESPRESSO_VERSION' ) ) {
	exit( 'No direct script access allowed' );
}

/**
 *
 * Read_Test
 *
 * @package			Event Espresso
 * @subpackage
 * @author				Mike Nelson
 * @group rest_api
 *
 */
class Read_Test extends \EE_UnitTestCase{

	public function setUp() {
		parent::setUp();
		if ( ! class_exists( 'WP_Rest_Request' ) ) {
			$this->markTestSkipped(
				'Test being run on a version of WP that does not have the REST framework installed'
			);
		}
	}

	public function test_explode_and_get_items_prefixed_with__basic(){
		$controller = new Read();
		$controller->set_requested_version( '4.8.29' );
		$this->assertEquals( array(
			'EVT_ID',
			'EVT_name'
		), $controller->explode_and_get_items_prefixed_with( 'EVT_ID,EVT_name', '' ) );
	}

	public function test_explode_and_get_items_prefixed_with__extra_whitespace() {
		$controller = new Read();
		$controller->set_requested_version( '4.8.29' );
		$this->assertEquals( array(
			'EVT_ID',
			'EVT_name',
			'EVT_desc'
		), $controller->explode_and_get_items_prefixed_with( 'EVT_ID , EVT_name , EVT_desc', '' ) );
	}
	public function test_explode_and_get_items_prefixed_with__related_model() {
		$controller = new Read();
		$controller->set_requested_version( '4.8.29' );
		$this->assertEquals( array(), $controller->explode_and_get_items_prefixed_with( 'Registration.*', '' ) );
	}
	public function test_explode_and_get_items_prefixed_with__related_model_all() {
		$controller = new Read();
		$controller->set_requested_version( '4.8.29' );
		$this->assertEquals(
			array(
				'*'
			),
			$controller->explode_and_get_items_prefixed_with( 'Registration.*', 'Registration' )
		);
	}
	public function test_explode_and_get_items_prefixed_with__related_models_but_searching_for_this_one() {
		$controller = new Read();
		$controller->set_requested_version( '4.8.29' );
		$this->assertEquals(
			array(),
			$controller->explode_and_get_items_prefixed_with( 'Registration.REG_ID, Registration.Attendee.ATT_ID', '' )
		);
	}
	public function test_explode_and_get_items_prefixed_with__related_models_but_searching_for_other() {
		$controller = new Read();
		$controller->set_requested_version( '4.8.29' );
		$this->assertEquals(
			array(
				'REG_ID',
				'Attendee.ATT_ID'
			),
			$controller->explode_and_get_items_prefixed_with( 'Registration.REG_ID, Registration.Attendee.ATT_ID', 'Registration' )
		);
	}

	public function test_handle_request_get_one__event_includes() {
		$event = $this->new_model_obj_with_dependencies( 'Event', array( 'status' => 'publish' ) );
		$req = new \WP_REST_Request( 'GET', \EED_Core_Rest_Api::ee_api_namespace . '4.8.29/events/' . $event->ID() );
		$req->set_url_params(
				array(
					'id' => $event->ID()
				)
			);
		$req->set_query_params(
				array(
					'include' =>  'EVT_ID,EVT_name'
				)
			);
		$response = Read::handle_request_get_one( $req );
		$result = $response->get_data();
		$this->assertEquals(
			array (
				'EVT_ID' => $event->ID(),
				'EVT_name' => $event->name()
			),
			$result
		);
	}

	public function test_handle_request_get_one__event_includes_two_related_models() {
		$event = $this->new_model_obj_with_dependencies( 'Event', array( 'status' => 'publish' ) );
		$req = new \WP_REST_Request( 'GET', \EED_Core_Rest_Api::ee_api_namespace . '4.8.36/events/' . $event->ID() );
		$req->set_url_params(
				array(
					'id' => $event->ID()
				)
			);
		$req->set_query_params(
				array(
					'include' =>  'Venue,Datetime'
				)
			);
		$response = Read::handle_request_get_one( $req );
		$result = $response->get_data();
		//make sure we still included all the normal event fields
		$this->assertArrayHasKey(
			'EVT_name',
			$result
		);
		$this->assertArrayHasKey(
			'datetimes',
			$result
		);
		$this->assertArrayHasKey(
			'venues',
			$result
		);
	}

	public function test_handle_request_get_one__event_include_non_model_field() {
		$this->set_current_user_to_new();
		$event = $this->new_model_obj_with_dependencies( 'Event' );
		$req = new \WP_REST_Request( 'GET', \EED_Core_Rest_Api::ee_api_namespace . '4.8.29/events/' . $event->ID() );
		$req->set_url_params(
				array(
					'id' => $event->ID()
				)
			);
		$req->set_query_params(
				array(
					'include' =>  'EVT_desc'
				)
			);
		$response = Read::handle_request_get_one( $req );
		$result = $response->get_data();
		$this->assertEquals(
			array (
				'EVT_ID' => $event->ID(),
				'EVT_desc' => array(
					'rendered' => $event->get_pretty( 'EVT_desc' ),
					'raw' => $event->get( 'EVT_desc')
					),
			),
			$result
		);
	}
	public function test_explode_and_get_items_prefixed_with__null() {
		$controller = new Read();
		$controller->set_requested_version( '4.8.29' );
		$this->assertEquals( array('*'), $controller->explode_and_get_items_prefixed_with( '*', '' ) );
	}

	/**
	 * @group 9406
	 */
	public function test_handle_request_get_one__event_calculate_stuff() {
		$this->set_current_user_to_new();
		$limit_on_datetime = 100;
		$limit_on_ticket = 50;
		$event = $this->new_model_obj_with_dependencies( 'Event' );
		$dtt = $this->new_model_obj_with_dependencies(
			'Datetime',
			array(
				'DTT_reg_limit' => $limit_on_datetime,
				'EVT_ID' => $event->ID()
			)
		);
		$tkt = $this->new_model_obj_with_dependencies(
			'Ticket',
			array(
				'TKT_qty' => $limit_on_ticket
			)
		);
		$tkt->_add_relation_to( $dtt, 'Datetime' );
		$reg1 = $this->new_model_obj_with_dependencies(
			'Registration',
			array(
				'EVT_ID' => $event->ID(),
				'TKT_ID' => $tkt->ID(),
				'STS_ID' => \EEM_Registration::status_id_approved
			)
		);
		$req = new \WP_REST_Request( 'GET', \EED_Core_Rest_Api::ee_api_namespace . '4.8.36/events/' . $event->ID() );
		$req->set_url_params(
				array(
					'id' => $event->ID()
				)
			);
		$req->set_query_params(
				array(
					'include' => 'Datetime',
					'calculate' =>  'optimum_sales_at_start,spots_taken,Datetime.registrations_checked_in_count'
				)
			);
		$response = Read::handle_request_get_one( $req );
		$result = $response->get_data();
		$this->assertTrue( isset( $result[ 'EVT_ID' ] ) );
		//check that the requested calculated fields were added.
		//Seeing how these calculated fields just wrap other EE methods (which sould already be tested)
		//the emphasis here is just on whether or not they get included properly, not exhaustively
		//testing the calculations themselves
		$this->assertTrue( isset( $result[ '_calculated_fields' ] ) );

		$this->assertEquals(
			(object) array(
				'optimum_sales_at_start' => min( array( $limit_on_datetime, $limit_on_ticket ) ),
				'spots_taken' => 1
			),
			$result[ '_calculated_fields' ]
		);
		$this->assertTrue( isset( $result[ 'datetimes' ] ) );
		$this->assertTrue( isset( $result[ 'datetimes' ][ 0 ] ) );
		$this->assertTrue( isset( $result[ 'datetimes' ][ 0 ][ '_calculated_fields' ] ) );
		$this->assertEquals(
			(object) array(
				'registrations_checked_in_count' => 0
			),
			$result[ 'datetimes' ][ 0 ][ '_calculated_fields' ]
		);
	}

	/**
	 * Verifies the format of the response hasn't changed (unless of course we actually
	 * DO change it, in which case this unit test will need to be updated to
	 * include the known modifications).
	 * This helps prevent accidental changes
	 */
	public function test_handle_request_get_one__event() {
		$original_gmt_offset = get_option( 'gmt_offset' );
		\EED_Core_Rest_Api::set_hooks_for_changes();
		//set a weird timezone
		update_option( 'gmt_offset', '-04:30' );
		$this->set_current_user_to_new();
		$current_time_mysql_gmt = current_time( 'Y-m-d\TH:i:s', true );
		$current_time_mysql =  current_time( 'Y-m-d\TH:i:s' );
		//these model objects were instantiated when the tests started, so their
		//default time is actually quite old now (at least a few seconds, possibly a minute or two)
		//so make sure we're creating the event with the CURRENT current time
		$event = $this->new_model_obj_with_dependencies( 'Event'
//			,
//			array(
//				'EVT_created' => $current_time_mysql_gmt,
//				'EVT_modified' => $current_time_mysql_gmt,
//				'EVT_visible_on' => $current_time_mysql_gmt,
//			)
		);
		$req = new \WP_REST_Request( 'GET', \EED_Core_Rest_Api::ee_api_namespace . '4.8.29/events/' . $event->ID() );
		$req->set_url_params(
				array(
					'id' => $event->ID()
				)
			);
		$response = Read::handle_request_get_one( $req );

		$result = $response->get_data();
		$this->assertTrue( is_array( $result ) );
		//compare all the times, realizing that _gmt times should be in UTC, others in the site's timezone
		$this->assertDateWithinOneMinute( $result[ 'EVT_created' ], $current_time_mysql, 'Y-m-d\TH:i:s');
		$this->assertDateWithinOneMinute( $result[ 'EVT_modified' ], $current_time_mysql, 'Y-m-d\TH:i:s');
		$this->assertDateWithinOneMinute( $result[ 'EVT_visible_on' ], $current_time_mysql, 'Y-m-d\TH:i:s');
		$this->assertDateWithinOneMinute( $result[ 'EVT_created_gmt' ], $current_time_mysql_gmt, 'Y-m-d\TH:i:s');
		$this->assertDateWithinOneMinute( $result[ 'EVT_modified_gmt' ], $current_time_mysql_gmt, 'Y-m-d\TH:i:s');
		$this->assertDateWithinOneMinute( $result[ 'EVT_visible_on_gmt' ], $current_time_mysql_gmt, 'Y-m-d\TH:i:s');
		//and let's just double-check the site's timezone isn't UTC, which would make it impossible to know if timezone offsets
		//are being applied properly or not
		$this->assertNotEquals(
			$result[ 'EVT_created' ],
			$result[ 'EVT_created_gmt' ],
			'There is no timezone offset, so its impossible to know if the timezone offset is being applied properly'
		);
		$properties_we_cant_compare_exactly = array(
			'EVT_created',
			'EVT_modified',
			'EVT_visible_on',
			'EVT_created_gmt',
			'EVT_modified_gmt',
			'EVT_visible_on_gmt'
		);
		foreach( $properties_we_cant_compare_exactly as $property_name ) {
			unset( $result[ $property_name ] );
		}
		$event_id = $event->ID();
		$site_url = site_url();
		$this->assertEquals(
			array(
				'EVT_ID'                          => $event->get( 'EVT_ID' ),
				'EVT_name'                        => $event->get( 'EVT_name' ),
				'EVT_desc'                        => array(
					'raw'      => $event->get( 'EVT_desc' ),
					'rendered' => $event->get_pretty( 'EVT_desc' )
				),
				'EVT_slug'                        => $event->get( 'EVT_slug' ),
				'EVT_short_desc'                  => $event->get( 'EVT_short_desc' ),
				'parent'                          => $event->get( 'parent' ),
				'EVT_order'                       => $event->get( 'EVT_order' ),
				'status'                          => array(
					'raw'    => $event->get( 'status' ),
					'pretty' => $event->get_pretty( 'status' )
				),
				'comment_status'                  => $event->get( 'comment_status' ),
				'ping_status'                     => $event->get( 'ping_status' ),
				'EVT_display_desc'                => $event->get( 'EVT_display_desc' ),
				'EVT_display_ticket_selector'     => $event->get( 'EVT_display_ticket_selector' ),
				'EVT_additional_limit'            => $event->get( 'EVT_additional_limit' ),
				'EVT_default_registration_status' => array(
					'raw'    => $event->get( 'EVT_default_registration_status' ),
					'pretty' => $event->get_pretty( 'EVT_default_registration_status' )
				),
				'EVT_member_only'                 => $event->get( 'EVT_member_only' ),
				'EVT_phone'                       => $event->get( 'EVT_phone' ),
				'EVT_allow_overflow'              => $event->get( 'EVT_allow_overflow' ),
				'EVT_external_URL'                => $event->get( 'EVT_external_URL' ),
				'EVT_donations'                   => $event->get( 'EVT_donations' ),
				'featured_image_url'              => null,
				'EVT_timezone_string'             => '',
				'link'                            => get_permalink( $event->ID() ),
				'_links'                          => array(
					'self'                                                  =>
						array(
							0 =>
								array(
									'href' => $site_url . '/?rest_route=/ee/v4.8.29/events/' . $event_id,
								),
						),
					'collection'                                            =>
						array(
							0 =>
								array(
									'href' => $site_url . '/?rest_route=/ee/v4.8.29/events',
								),
						),
					'https://api.eventespresso.com/registrations'           =>
						array(
							0 =>
								array(
									'href'   => $site_url
									            . '/?rest_route=/ee/v4.8.29/events/'
									            . $event_id
									            . '/registrations',
									'single' => false,
								),
						),
					'https://api.eventespresso.com/datetimes'               =>
						array(
							0 =>
								array(
									'href'   => $site_url
									            . '/?rest_route=/ee/v4.8.29/events/'
									            . $event_id
									            . '/datetimes',
									'single' => false,
								),
						),
					'https://api.eventespresso.com/question_groups'         =>
						array(
							0 =>
								array(
									'href'   => $site_url
									            . '/?rest_route=/ee/v4.8.29/events/'
									            . $event_id
									            . '/question_groups',
									'single' => false,
								),
						),
					'https://api.eventespresso.com/venues'                  =>
						array(
							0 =>
								array(
									'href'   => $site_url . '/?rest_route=/ee/v4.8.29/events/' . $event_id . '/venues',
									'single' => false,
								),
						),
					'https://api.eventespresso.com/term_taxonomies'         =>
						array(
							0 =>
								array(
									'href'   => $site_url
									            . '/?rest_route=/ee/v4.8.29/events/'
									            . $event_id
									            . '/term_taxonomies',
									'single' => false,
								),
						),
					'https://api.eventespresso.com/message_template_groups' =>
						array(
							0 =>
								array(
									'href'   => $site_url
									            . '/?rest_route=/ee/v4.8.29/events/'
									            . $event_id
									            . '/message_template_groups',
									'single' => false,
								),
						),
					'https://api.eventespresso.com/attendees'               =>
						array(
							0 =>
								array(
									'href'   => $site_url
									            . '/?rest_route=/ee/v4.8.29/events/'
									            . $event_id
									            . '/attendees',
									'single' => false,
								),
						),
					'https://api.eventespresso.com/wp_user'                 =>
						array(
							0 =>
								array(
									'href'   => $site_url . '/?rest_route=/ee/v4.8.29/events/' . $event_id . '/wp_user',
									'single' => true,
								),
						),
					'https://api.eventespresso.com/post_metas'              =>
						array(
							0 =>
								array(
									'href'   => $site_url
									            . '/?rest_route=/ee/v4.8.29/events/'
									            . $event_id
									            . '/post_metas',
									'single' => false,
								),
						),
					'https://api.eventespresso.com/extra_metas'             =>
						array(
							0 =>
								array(
									'href'   => $site_url
									            . '/?rest_route=/ee/v4.8.29/events/'
									            . $event_id
									            . '/extra_metas',
									'single' => false,
								),
						),
					'https://api.eventespresso.com/change_logs'             =>
						array(
							0 =>
								array(
									'href'   => $site_url
									            . '/?rest_route=/ee/v4.8.29/events/'
									            . $event_id
									            . '/change_logs',
									'single' => false,
								),
						),
					'https://api.eventespresso.com/term_relationships'             =>
						array(
							0 =>
								array(
									'href'   => $site_url
									            . '/?rest_route=/ee/v4.8.29/events/'
									            . $event_id
									            . '/term_relationships',
									'single' => false,
								),
						),
				)
			),
			$result
		);
		// reset timezone
		update_option( 'gmt_offset', $original_gmt_offset );
	}

	public function test_handle_request_get_one__registration_include_attendee(){
		$this->set_current_user_to_new();
		$r = $this->new_model_obj_with_dependencies( 'Registration' );
		$req = new \WP_REST_Request(
					'GET',
					\EED_Core_Rest_Api::ee_api_namespace . '4.8.29/registrations/' . $r->ID()
				);
		$req->set_query_params(
				array(
					'include' => 'Attendee.*'
				)
			);
		$req->set_url_params(
				array(
					'id' => $r->ID()
				)
			);
		$response = Read::handle_request_get_one( $req );
		$entity = $response->get_data();
		$this->assertArrayHasKey( 'attendee', $entity );
	}


	public function test_handle_request_get_one__registration_include_answers_and_questions_use_star(){
		$this->set_current_user_to_new();
		$r = $this->new_model_obj_with_dependencies( 'Registration' );
		$this->new_model_obj_with_dependencies( 'Answer', array( 'REG_ID' => $r->ID() ) );
		$req = new \WP_REST_Request( 'GET', \EED_Core_Rest_Api::ee_api_namespace . '4.8.29/registrations/' . $r->ID() );
		$req->set_query_params(
				array(
					'include' => 'Answer.Question.*'
				)
			);
		$req->set_url_params(
				array(
					'id' => $r->ID()
				)
			);
		$response = Read::handle_request_get_one( $req );
		$entity = $response->get_data();
		$this->assertArrayHasKey( 'REG_date', $entity );
		$this->assertArrayHasKey( 'answers', $entity );
		$answers = $entity['answers'];
		foreach( $answers as $answer ) {
			$this->assertArrayHasKey( 'question', $answer );
		}
	}

	public function test_handle_request_get_one__registration_include_answers_and_questions(){
		$this->set_current_user_to_new();
		$r = $this->new_model_obj_with_dependencies( 'Registration' );
		$this->new_model_obj_with_dependencies( 'Answer', array( 'REG_ID' => $r->ID() ) );
		$req = new \WP_REST_Request( 'GET', \EED_Core_Rest_Api::ee_api_namespace . '4.8.29/registrations/' . $r->ID() );
		$req->set_query_params(
				array(
					'include' => 'Answer.Question'
				)
			);
		$req->set_url_params(
				array(
					'id' => $r->ID()
				)
			);
		$response = Read::handle_request_get_one( $req );
		$entity = $response->get_data();
		$this->assertArrayHasKey( 'REG_date', $entity );
		$this->assertArrayHasKey( 'answers', $entity );
		$answers = $entity['answers'];
		foreach( $answers as $answer ) {
			$this->assertArrayHasKey( 'question', $answer );
		}
	}

	public function test_handle_request_get_one__registration_include_answers_and_question_bare_min_from_each(){
		$this->set_current_user_to_new();
		$r = $this->new_model_obj_with_dependencies( 'Registration' );
		$this->new_model_obj_with_dependencies( 'Answer', array( 'REG_ID' => $r->ID() ) );
		$req = new \WP_REST_Request( 'GET', \EED_Core_Rest_Api::ee_api_namespace . '4.8.29/registrations/' . $r->ID() );
		$req->set_query_params(
				array(
					'include' => 'Answer.ATT_ID, Answer.Question.QST_ID'
				)
			);
		$req->set_url_params(
				array(
					'id' => $r->ID()
				)
			);
		$response = Read::handle_request_get_one( $req );
		$entity = $response->get_data();
		$this->assertArrayHasKey( 'answers', $entity );
		$answers = $entity['answers'];
		foreach( $answers as $answer ){
			$this->assertArrayHasKey( 'question', $answer );
		}
	}

	public function test_handle_request_get_one__doesnt_exist(){
		$e = $this->new_model_obj_with_dependencies('Event');
		$non_existent_id = $e->ID() + 100;
		$req = new \WP_REST_Request( 'GET', \EED_Core_Rest_Api::ee_api_namespace . '4.8.29/events/' . $non_existent_id );
		$req->set_url_params(
				array(
					'id' => $non_existent_id
				)
			);
		$response = Read::handle_request_get_one( $req );
		$this->assertInstanceOf( 'WP_REST_Response', $response );
		$this->assertEquals( 404, $response->get_status() );
	}
	public function test_handle_request_get_one__cannot_access(){
		$e = $this->new_model_obj_with_dependencies('Event', array( 'status' => 'draft' ) );
		$req = new \WP_REST_Request( 'GET', \EED_Core_Rest_Api::ee_api_namespace . '4.8.29/events/' . $e->ID() );
		$req->set_url_params(
				array(
					'id' => $e->ID()
				)
			);
		$response = Read::handle_request_get_one( $req );
		$this->assertInstanceOf( 'WP_REST_Response', $response );
		$this->assertEquals( 403, $response->get_status() );
	}

	public function test_handle_request_get_all__not_logged_in(){
		$this->new_model_obj_with_dependencies('Registration');
		$response = Read::handle_request_get_all(
			new \WP_REST_Request( 'GET', \EED_Core_Rest_Api::ee_api_namespace . '4.8.29/registrations' )
		);
		$this->assertInstanceOf( 'WP_REST_Response', $response );
		$this->assertEquals( 403, $response->get_status() );
	}

	/**
	 * @group 9406
	 */
	public function test_handle_request_get_all__set_headers(){
		$datetimes_created = 65;
		$event = $this->new_model_obj_with_dependencies( 'Event',  array( 'status' => \EEM_CPT_Base::post_status_publish ) );
		for( $i=0;$i < $datetimes_created; $i++ ) {
			$this->new_model_obj_with_dependencies( 'Datetime', array( 'EVT_ID' => $event->ID() ) );
		}
   		$this->assertEquals( $datetimes_created, \EEM_Datetime::instance()->count( array( 'caps' => \EEM_Base::caps_read ) ) );
		//request all datetimes
		$response = Read::handle_request_get_all(
			new \WP_REST_Request( 'GET', \EED_Core_Rest_Api::ee_api_namespace . '4.8.34/datetimes' )
		);
		$this->assertInstanceOf( 'WP_REST_Response', $response );
		$headers = $response->get_headers();

		$this->assertArrayHasKey( Controller_Base::header_prefix_for_wp . 'Total', $headers );
		$this->assertArrayHasKey( Controller_Base::header_prefix_for_wp . 'TotalPages', $headers );
		$this->assertEquals( $datetimes_created, $headers[ Controller_Base::header_prefix_for_wp . 'Total' ] );
		$this->assertEquals( ceil( $datetimes_created / 50 ),$headers[ Controller_Base::header_prefix_for_wp . 'TotalPages' ] );
	}

	/**
	* @param string $role
	* @return \WP_User
	*/
	public function get_wp_user_mock( $role = 'administrator' ) {
	   /** @type \WP_User $user */
	   $user = $this->factory->user->create_and_get();
	   $user->add_role( $role );
	   return $user;
	}

	/**
	 * Creates a new wp user with the specified role and makes them the new current user
	 *
	 * @global \WP_User $current_user
	 * @param string $role
	 * @return \WP_User
	 */
	public function set_current_user_to_new(){
		global $current_user;
		$current_user = $this->wp_admin_with_ee_caps();
		return $current_user;
	}

	/**
	 * @group 24
	 */
	public function test_prepare_rest_query_params_key_for_models() {
		$controller = new Read();
		$this->assertEquals( array(
			'EVT_desc' => 'foobar',
			'OR' => array(
				'EVT_desc*gotcha' => array( 'LIKE', '%foobar%' ),
				'EVT_name' => 'yep',
				'EVT_desc*gotchaagain' => array( 'IN', array( '1', '2' ) )
			)
		),
		$controller->prepare_rest_query_params_key_for_models(
				\EEM_Event::instance(),
				array(
					'EVT_desc' => 'foobar',
					'OR' => array(
						'EVT_desc*gotcha' => array('LIKE', '%foobar%' ),
						'EVT_name' => 'yep',
						'EVT_desc*gotchaagain' => array( 'IN', array( '1', '2' ) ) ) ) ) );
	}

	/**
	 * @group 24
	 * @group 9406
	 */
	public function test_create_model_query_params__4_8_36(){
		$controller = new Read();
		$controller->set_requested_version( '4.8.36' );
		$this->assertEquals( array(
					0 => array(
						'EVT_desc*foobar' => array( 'LIKE', '%frogs%' ),
						'OR*otherfunanimals' => array(
							'EVT_name' => array( 'IN', array( 'chickens', 'cows' ) ),
							'EVT_slug' => 'cowbunga',
							'Datetime.DTT_reg_limit' => EE_INF,
							'Datetime.DTT_reg_limit*1' => EE_INF,
							'Datetime.DTT_EVT_start' => array( '<', strtotime('2016-01-01 00:00:00') ),
						)
					),
					'order_by' => array(
						'EVT_desc' => 'ASC'
					),
					'group_by' => array(
						'EVT_desc'
					),
					'having' => array(
						'EVT_desc' => 'monkey'
					),
					'limit' => 50,
					'caps' => \EEM_Base::caps_read_admin
				),
		$controller->create_model_query_params(
				\EEM_Event::instance(),
				array(
					'where' => array(
						'EVT_desc*foobar' => array( 'LIKE', '%frogs%' ),
						'OR*otherfunanimals' => array(
							'EVT_name' => array( 'IN', array( 'chickens', 'cows' ) ),
							'EVT_slug' => 'cowbunga',
							'Datetime.DTT_reg_limit' => null,
							'Datetime.DTT_reg_limit*1' => '',
							'Datetime.DTT_EVT_start' => array( '<', '2016-01-01T00:00:00' ),
						),

					),
					'order_by' => array(
						'EVT_desc' => 'ASC'
					),
					'group_by' => array(
						'EVT_desc'
					),
					'having' => array(
						'EVT_desc' => 'monkey'
					),
					'caps' => \EEM_Base::caps_read_admin
				) ) );
	}

	/**
	 * @group 9389
	 */
	public function test_handle_request_get_all__automatic_group_by() {
		$request = new \WP_REST_Request( 'GET', \EED_Core_Rest_Api::ee_api_namespace . '4.8.36/question_groups' );
		$request->set_query_params(
			array(
				'where' => array(
					'Question.QST_ID' => array( 'IS_NOT_NULL' )
				),
				'limit' => '2,2'
			)
		);
		$response = Read::handle_request_get_all( $request );
		$this->assertEmpty( $response->get_data() );
	}
}

// End of file Read_Test.php
// Location: testcases/core/libraries/rest_api/controllers/Read_Test.php