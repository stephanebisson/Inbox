<?php

namespace Inbox\Specials;

use FormatJson;
use Html;
use Inbox\Models\Email;
use Sanitizer;
use SpecialPage;

class SpecialInbox extends SpecialPage {

	public function __construct() {
		parent::__construct( 'Inbox' );
	}

	/**
	 * @inheritDoc
	 */
	public function execute( $par ) {
		$this->requireLogin();
		if ( is_numeric( $par ) ) {
			$this->showEmail( $this->getUser()->getEmail(), $par );
		} else {
			$this->showAllEmails( $this->getUser()->getEmail() );
		}

	}

	private function showEmail( $emailAddress, $emailId ) {
		$out = $this->getOutput();
		$email = Email::get( $emailAddress, $emailId );
		if ( $email ) {
			$out->setArticleBodyOnly( true );
			$out->addHTML( $email->email_subject );
			$out->addHTML( '<hr />' );
			$headers = array_change_key_case( FormatJson::decode( $email->email_headers, true ) );
			if ( strpos( $headers[ 'content-type' ], 'multipart' ) !== false ) {
				preg_match( '/boundary=\"(.*?)\"/', $headers[ 'content-type' ], $m );
				$boundary = $m[1];
				$parts = explode( '--' . $boundary, $email->email_body );
				$this->showEmailcontent(  $parts[1], true );
				$out->addHTML( '<hr />' );
				$this->showEmailcontent( $parts[2] );
			} elseif ( strpos( $headers[ 'content-type' ], 'text/plain' ) >= 0 ) {
				$this->showEmailcontent( $email->email_body, true );
			} else {
				$this->showEmailcontent( $email->email_body );
			}

			Email::markRead( $emailId );
		} else {
			parent::execute( $emailId );
			$out->addHTML( 'email not found' );
		}
	}

	private function showEmailcontent( $content, $plainText = false ) {
		$this->getOutput()->addHTML( Html::rawElement(
			$plainText ? 'pre' : 'div',
			[],
			quoted_printable_decode( $content )
		) );
	}

	private function showAllEmails( $emailAddress ) {
		parent::execute( null );
		$emails = Email::getAll( $emailAddress );
		if ( $emails ) {
			$this->getOutput()->addModuleStyles( 'inbox.style' );
			$this->getOutput()->addHTML( Html::rawElement(
				'table',
				[ 'class' => 'email-all mw-datatable' ],
				'<tr><th>From</th><th>Subject</th><th>Time</th></tr>' .
				implode( '', array_map( function ( $email ) {
					return Html::rawElement(
						'tr',
						[ 'class' => [ !$email->email_read ? 'email-unread' : '', 'email-one' ] ],
						Html::element(
							'td',
							[ 'class' => 'email-from' ],
							$email->email_from
						) .
						Html::rawElement(
							'td',
							[ 'class' => 'email-subject' ],
							Html::element(
								'a',
								[ 'href' => SpecialPage::getTitleFor( 'Inbox', $email->email_id )->getLinkURL() ],
								$email->email_subject
							)
						) .
						Html::element(
							'td',
							[ 'class' => 'email-timestamp' ],
							$this->getLanguage()->userTimeAndDate( $email->email_timestamp, $this->getUser() )
						)
					);
				}, iterator_to_array( $emails, false ) )
			) ) );
		}
	}

}
