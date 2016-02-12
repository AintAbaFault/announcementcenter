<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 *
 * @copyright Copyright (c) 2016, Joas Schilling <nickvergessen@owncloud.com>
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\AnnouncementCenter;


use OCP\L10N\IFactory;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;

class NotificationsNotifier implements INotifier {

	/** @var IFactory */
	protected $l10nFactory;

	/** @var Manager */
	protected $manager;

	/**
	 * @param Manager $manager
	 * @param IFactory $l10nFactory
	 */
	public function __construct(Manager $manager, IFactory $l10nFactory) {
		$this->manager = $manager;
		$this->l10nFactory = $l10nFactory;
	}

	/**
	 * @param INotification $notification
	 * @param string $languageCode The code of the language that should be used to prepare the notification
	 * @return INotification
	 * @throws \InvalidArgumentException When the notification was not prepared by a notifier
	 */
	public function prepare(INotification $notification, $languageCode) {
		if ($notification->getApp() !== 'announcementcenter') {
			// Not my app => throw
			throw new \InvalidArgumentException();
		}

		// Read the language from the notification
		$l = $this->l10nFactory->get('announcementcenter', $languageCode);

		switch ($notification->getSubject()) {
			// Deal with known subjects
			case 'announced':
				$params = $notification->getSubjectParameters();

				$announcement = $this->manager->getAnnouncement($notification->getObjectId(), false);
				$params[] = $this->prepareMessage($announcement['subject']);

				$notification->setParsedMessage($this->prepareMessage($announcement['message']))
					->setParsedSubject(
						(string) $l->t('%1$s announced “%2$s”', $params)
					);
				return $notification;

			default:
				// Unknown subject => Unknown notification => throw
				throw new \InvalidArgumentException();
		}
	}

	/**
	 * Prepare message for notification usage
	 *
	 * + Replace line breaks with spaces
	 * + Trim on word end after 100 chars or hard 120 chars
	 *
	 * @param string $message
	 * @return string
	 */
	protected function prepareMessage($message) {
		$message = str_replace("\n", ' ', $message);

		if (isset($message[120])) {
			$findSpace = strpos($message, ' ', 100);
			if ($findSpace !== false && $findSpace < 120) {
				return substr($message, 0, $findSpace) . '…';
			}
			return substr($message, 0, 120) . '…';
		}

		return $message;
	}
}
