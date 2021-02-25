<?php

namespace Inbox\Models;

use FormatJson;
use MailAddress;
use MediaWiki\MediaWikiServices;
use stdClass;
use Wikimedia\Rdbms\ResultWrapper;

class Email {

	/** @var array */
	private $headers;
	/** @var string */
	private $to;
	/** @var string */
	private $from;
	/** @var string */
	private $subject;
	/** @var string */
	private $body;
	/** @var string */
	private $timestamp;

	/**
	 * @param array $headers Associative array of headers for the email
	 * @param MailAddress|array $to To address
	 * @param MailAddress $from From address
	 * @param string $subject Subject of the email
	 * @param string $body Body of the message
	 * @param string|null $timestamp
	 */
	public function __construct( $headers, $to, $from, $subject, $body, $timestamp = null ) {
		$this->headers = $headers;
		$this->to = $to[ 0 ]->address;
		$this->from = $from->address;
		$this->subject = $subject;
		$this->body = $body;
		$this->timestamp = $timestamp ?: wfTimestampNow();
	}

	/**
	 * @param string $emailAddress
	 * @return string
	 */
	public static function getNewestEmailTimestamp( $emailAddress ) {
		$dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_REPLICA );
		return $dbr->selectField(
			'inbox_email',
			'email_timestamp',
			[ 'email_to' => $emailAddress ],
			__METHOD__,
			[ 'ORDER BY' => [ 'email_timestamp DESC' ], 'limit' => 1 ]
		);
	}

	/**
	 * Save email to DB
	 */
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

	/**
	 * @param string $emailAddress
	 * @return ResultWrapper
	 */
	public static function getAll( $emailAddress ) {
		$dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_REPLICA );
		return $dbr->select(
			'inbox_email',
			[ 'email_id', 'email_from', 'email_subject', 'email_timestamp', 'email_read' ],
			[ 'email_to' => $emailAddress ],
			__METHOD__,
			[ 'ORDER BY' => [ 'email_timestamp DESC' ] ]
		);
	}

	/**
	 * @param string $emailAddress
	 * @param string $id
	 * @return stdClass
	 */
	public static function get( $emailAddress, $id ) {
		$dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_REPLICA );
		return $dbr->selectRow(
			'inbox_email',
			[ 'email_from', 'email_headers', 'email_subject', 'email_body', 'email_timestamp' ],
			[ 'email_id' => $id, 'email_to' => $emailAddress ]
		);
	}

	/**
	 * @param string $id
	 */
	public static function markRead( $id ) {
		$dbw = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_MASTER );
		$dbw->update(
			'inbox_email',
			[ 'email_read' => 1 ],
			[ 'email_id' => $id ]
		);
	}
}
