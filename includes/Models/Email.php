<?php

namespace Inbox\Models;

use FormatJson;
use MediaWiki\MediaWikiServices;

class Email {

	private $headers;
	private $from;
	private $subject;
	private $body;
	private $timestamp;

	public function __construct( $headers, $to, $from, $subject, $body, $timestamp = null ) {
		$this->headers = $headers;
		$this->to = $to[ 0 ]->address;
		$this->from = $from->address;
		$this->subject = $subject;
		$this->body = $body;
		$this->timestamp = $timestamp ?: wfTimestampNow();
	}

	public function save() {
		$dbw = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_MASTER );
		$dbw->insert(
			'inbox_email',
			[
				'email_headers' => FormatJson::encode( $this->headers ),
				'email_to' => $this->to,
				'email_from' => $this->from,
				'email_subject' => $this->subject,
				'email_body' => $this->body,
				'email_timestamp' => $this->timestamp,
			],
			__METHOD__
		);
	}

	/**
	 * @param string $emailAddress
	 * @return int|bool
	 */
	public static function getUnreadCount( $emailAddress ) {
		$dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_REPLICA );
		return $dbr->selectField(
			'inbox_email',
			'COUNT(*)',
			[
				'email_to' => $emailAddress,
				'email_read' => 0,
			],
			__METHOD__,
			[ 'GROUP BY' => 'email_to' ]
		);
	}

	public static function getAll( $emailAddress ) {
		$dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_REPLICA );
		return $dbr->select(
			'inbox_email',
			[ 'email_id', 'email_from', 'email_subject', 'email_timestamp', 'email_read' ],
			[ 'email_to' => $emailAddress ]
		);
	}

	public static function get( $emailAddress, $id ) {
		$dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_REPLICA );
		return $dbr->selectRow(
			'inbox_email',
			[ 'email_from', 'email_headers', 'email_subject', 'email_body', 'email_timestamp' ],
			[ 'email_id' => $id, 'email_to' => $emailAddress ]
		);
	}

	public static function markRead( $id ) {
		$dbw = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_MASTER );
		$dbw->update(
			'inbox_email',
			[ 'email_read' => 1 ],
			[ 'email_id' => $id ]
		);
	}
}
