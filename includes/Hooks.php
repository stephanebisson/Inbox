<?php

namespace Inbox;

use DatabaseUpdater;
use Inbox\Models\Email;
use MailAddress;
use OutputPage;
use SkinTemplate;
use SpecialPage;
use Title;

class Hooks {

	/**
	 * @param DatabaseUpdater $updater
	 */
	public static function onLoadExtensionSchemaUpdates( DatabaseUpdater $updater ) {
		$updater->addExtensionTable( 'inbox_email', dirname( __DIR__ ) . "/sql/inbox.sql" );
	}

	/**
	 * @param array $headers Associative array of headers for the email
	 * @param MailAddress|array $to To address
	 * @param MailAddress $from From address
	 * @param string $subject Subject of the email
	 * @param string $body Body of the message
	 * @return bool|string|void True or no return value to continue sending email in the
	 *   regular way, or false to skip the regular method of sending mail. Return a string
	 *   to return a php-mail-error message containing the error.
	 */
	public static function onAlternateUserMailer( $headers, $to, $from, $subject, $body ) {
		$email = new Email( $headers, $to, $from, $subject, $body );
		$email->save();
	}

	/**
	 * Handler for PersonalUrls hook.
	 * Add a "Notifications" item to the user toolbar ('personal URLs').
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/PersonalUrls
	 * @param array &$personal_urls Array of URLs to append to.
	 * @param Title &$title Title of page being visited.
	 * @param SkinTemplate $sk
	 * @return bool true in all cases
	 */
	public static function onPersonalUrls( &$personal_urls, &$title, $sk ) {
		$user = $sk->getUser();
		if ( $user->isAnon() || !$user->getEmail() ) {
			return true;
		}

		$unreadCount = Email::getUnreadCount( $user->getEmail() );
		$text = 'Inbox';
		if ( $unreadCount ) {
			$text .= " ($unreadCount)";
		}
		$inboxLink = [
			'href' => SpecialPage::getTitleFor( 'Inbox' )->getLinkURL(),
			'text' => $text,
			'class' => 'tbd',
		];

		$personal_urls = wfArrayInsertAfter( $personal_urls, [ 'inbox' => $inboxLink ], 'userpage' );
	}

	/**
	 * @param array &$modifiedTimes
	 * @param OutputPage $out
	 */
	public static function onOutputPageCheckLastModified( array &$modifiedTimes, OutputPage $out ) {
		$user = $out->getUser();
		if ( $user->isRegistered() ) {
			$newestEmailTimestamp = Email::getNewestEmailTimestamp( $user->getEmail() );
			if ( $newestEmailTimestamp ) {
				$modifiedTimes[ 'inbox-newest-email' ] = $newestEmailTimestamp;
			}
		}
	}

}
