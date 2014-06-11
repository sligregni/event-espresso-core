<?php

if (!defined('EVENT_ESPRESSO_VERSION') )
	exit('NO direct script access allowed');

/**
 * Event Espresso
 *
 * Event Registration and Management Plugin for WordPress
 *
 * @ package		Event Espresso
 * @ author			Seth Shoultes
 * @ copyright		(c) 2008-2011 Event Espresso  All Rights Reserved.
 * @ license		http://eventespresso.com/support/terms-conditions/   * see Plugin Licensing *
 * @ link			http://www.eventespresso.com
 * @ version		4.1
 *
 * ------------------------------------------------------------------------
 *
 * EE_Pending_Approval_message_type
 *
 * Handles frontend registration message types.
 *
 * @package		Event Espresso
 * @subpackage	core/libraries/messages/message_type/EE_Pending_Approval_message_type.class.php
 * @author		Darren Ethier
 *
 * ------------------------------------------------------------------------
 */

class EE_Pending_Approval_message_type extends EE_message_type {

	public function __construct() {
		$this->name = 'pending_approval';
		$this->description = __('This message type is used for recipients who have Pending Payment registration status.', 'event_espresso');
		$this->label = array(
			'singular' => __('registration pending payment', 'event_espresso'),
			'plural' => __('registrations pending payment', 'event_espresso')
			);

		parent::__construct();
	}



	protected function _set_admin_pages() {
		$this->admin_registered_pages = array(
			'events_edit' => TRUE
			);
	}

	/**
	 * This message type doesn't need any settings so we are just setting to empty array.
	 */
	protected function _set_admin_settings_fields() {
		$this->_admin_settings_fields = array();
	}


	protected function _get_admin_content_events_edit_for_messenger( EE_Messenger $messenger ) {
		//this is just a test
		return $this->label['singular'] . ' Message Type for ' . $messenger->name . ' Messenger ';
	}




	protected function _set_data_handler() {
		$this->_data_handler = $this->_data instanceof EE_Registration ? 'REG' : 'Gateways';
		$this->_single_message = $this->_data instanceof EE_Registration ? TRUE : FALSE;
	}



	protected function _get_data_for_context( $context, EE_Registration $registration, $id ) {
		if ( $context  == 'admin' ) {
			//use the registration to get the transaction.
			$transaction = $registration->transaction();

			//bail early if no transaction
			if ( ! $transaction instanceof EE_Transaction ) {
				throw new EE_Error( __('The given registration does not have an associated transaction. Something is wrong.', 'event_espresso' ) );
			}

			$payment = EEM_Payment::instance()->get_one( array( array( 'PAY_ID' => $id, 'TXN_ID' => $transaction->ID() ) ) );

			if ( $payment instanceof EE_Payment && $transaction instanceof EE_Transaction ) {
				return array( $transaction, $payment );
			} else {
				return NULL;
			}
		} else {
			return $registration;
		}
	}


	protected function _get_id_for_msg_url( $context, EE_Registration $registration ) {
		if ( $context == 'admin' ) {
			//there should be a transaction and payment object in the incoming data.
			if ( $this->_data instanceof EE_Messages_incoming_data  ) {
				$payment = $this->_data->payment;

				if ( $payment instanceof EE_Payment ) {
					return $payment->ID();
				}
			}
		}
		return 0;
	}


	protected function _set_default_field_content() {

		$this->_default_field_content = array(
			'subject' => $this->_default_template_field_subject(),
			'content' => $this->_default_template_field_content(),
		);
	}






	protected function _default_template_field_subject() {
		foreach ( $this->_contexts as $context => $details ) {
			$content[$context] = 'Registration Pending Payment';
		};
		return $content;
	}






	protected function _default_template_field_content() {
		$content = file_get_contents( EE_LIBRARIES . 'messages/message_type/assets/defaults/pending_approval-message-type-content.template.php', TRUE );

		foreach ( $this->_contexts as $context => $details ) {
			$tcontent[$context]['main'] = $content;
			$tcontent[$context]['attendee_list'] = file_get_contents( EE_LIBRARIES . 'messages/message_type/assets/defaults/not-approved-registration-message-type-attendee-list.template.php', TRUE );
			$tcontent[$context]['event_list'] = file_get_contents( EE_LIBRARIES . 'messages/message_type/assets/defaults/not-approved-registration-message-type-event-list.template.php', TRUE );
			$tcontent[$context]['ticket_list'] = file_get_contents( EE_LIBRARIES . 'messages/message_type/assets/defaults/not-approved-registration-message-type-ticket-list.template.php', TRUE );
			$tcontent[$context]['datetime_list'] = file_get_contents( EE_LIBRARIES . 'messages/message_type/assets/defaults/not-approved-registration-message-type-datetime-list.template.php', TRUE );
		}


		return $tcontent;
	}






	/**
	 * _set_contexts
	 * This sets up the contexts associated with the message_type
	 *
	 * @access  protected
	 * @return  void
	 */
	protected function _set_contexts() {
		$this->_context_label = array(
			'label' => __('recipient', 'event_espresso'),
			'plural' => __('recipients', 'event_espresso'),
			'description' => __('Recipient\'s are who will receive the template.  You may want different pending approval details sent out depending on who the recipient is.  To "turn off" a recipient from receiving message, simply remove any content from the "to" field in the template.', 'event_espresso')
			);

		$this->_contexts = array(
			'admin' => array(
				'label' => __('Event Admin', 'event_espresso'),
				'description' => __('This template is what event administrators will receive when a message is sent to registrants with the pending payment registration status.', 'event_espresso')
				),
			'primary_attendee' => array(
				'label' => __('Primary Registrant', 'event_espresso'),
				'description' => __('This template is what the primary registrant (the person who completed the initial transaction) will receive on when their registration status is pending payment.', 'event_espresso')
				)
			);

	}



	/**
	 * returns an array of addressee objects for event_admins
	 *
	 * @access protected
	 * @return array array of EE_Messages_Addressee objects
	 */
	protected function _admin_addressees() {
		if ( $this->_single_message )
			return array();
		return parent::_admin_addressees();
	}

} //end EE_Pending_Approval_message_type class
