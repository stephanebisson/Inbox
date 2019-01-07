<?php

namespace Inbox;

use DatabaseUpdater;
use Inbox\Models\Email;
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
}
