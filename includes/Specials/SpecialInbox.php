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
		$out = $this->getOutput();

		if ( is_numeric( $par ) ) {
			$email = Email::get( $this->getUser()->getEmail(), $par );
			if ( $email ) {
				$out->setArticleBodyOnly( true );
				// todo: mark as read
				$out->addHTML( $email->email_subject );
				$out->addHTML( '<hr />' );
				$headers = array_change_key_case( FormatJson::decode( $email->email_headers, true ) );
				if ( strpos( $headers[ 'content-type' ], 'multipart' ) !== false ) {
					preg_match( '/boundary=\"(.*?)\"/', $headers[ 'content-type' ], $m );
					$boundary = $m[1];
					$parts = explode( '--' . $boundary, $email->email_body );
					$out->addHTML( '<pre>' . quoted_printable_decode( $parts[1] ) . '</pre>' );
					$out->addHTML( '<hr />' );
					$out->addHTML( quoted_printable_decode( $parts[2] ) );
				} elseif ( strpos( $headers[ 'content-type' ], 'text/plain' ) >= 0 ) {
					$out->addHTML( '<pre>' . quoted_printable_decode( $email->email_body ) . '</pre>' );
				} else {
					$out->addHTML( quoted_printable_decode( $email->email_body ) );
				}
			} else {
				parent::execute( $par );
				$out->addHTML( 'email not found' );
			}
		} else {
			parent::execute( $par );
			$emails = Email::getAll( $this->getUser()->getEmail() );
			if ( $emails ) {
				$out->addHTML( Html::openElement( 'table' ) );

				foreach ( $emails as $email ) {
					$out->addHTML( '<tr>' );
					$out->addHTML( '<td>' . $email->email_timestamp . '</td>' );
					$out->addHTML( '<td>' . $email->email_from . '</td>' );
					$out->addHTML(
						'<td>' .
						Html::element(
							'a',
							[
								'href' => SpecialPage::getTitleFor( 'Inbox', $email->email_id )->getLinkURL(),
								'target' => '_blank',
							],
							$email->email_subject
						) .
						'</td>'
					);
					$out->addHTML( '</tr>' );
				}
				$out->addHTML( Html::closeElement( 'table' ) );
			}
		}

	}

}
