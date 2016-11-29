<?php
/**
 * @copyright Copyright (c) 2016 Joas Schilling <coding@schilljs.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\DAV\CalDAV\Activity\Provider;

use OCP\Activity\IEvent;
use OCP\Activity\IEventMerger;
use OCP\Activity\IManager;
use OCP\Activity\IProvider;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;

class Calendar implements IProvider {

	const SUBJECT_ADD = 'calendar_add';
	const SUBJECT_UPDATE = 'calendar_update';
	const SUBJECT_DELETE = 'calendar_delete';
	const SUBJECT_SHARE_USER = 'calendar_user_share';
	const SUBJECT_SHARE_GROUP = 'calendar_group_share';
	const SUBJECT_UNSHARE_USER = 'calendar_user_unshare';
	const SUBJECT_UNSHARE_GROUP = 'calendar_group_unshare';

	/** @var IL10N */
	protected $l;

	/** @var IURLGenerator */
	protected $url;

	/** @var IManager */
	protected $activityManager;

	/** @var IUserManager */
	protected $userManager;

	/** @var IEventMerger */
	protected $eventMerger;

	/** @var string[] cached displayNames - key is the UID and value the displayname */
	protected $displayNames = [];

	/**
	 * @param IL10N $l
	 * @param IURLGenerator $url
	 * @param IManager $activityManager
	 * @param IUserManager $userManager
	 * @param IEventMerger $eventMerger
	 */
	public function __construct(IL10N $l, IURLGenerator $url, IManager $activityManager, IUserManager $userManager, IEventMerger $eventMerger) {
		$this->l = $l;
		$this->url = $url;
		$this->activityManager = $activityManager;
		$this->userManager = $userManager;
		$this->eventMerger = $eventMerger;
	}

	/**
	 * @param IEvent $event
	 * @param IEvent|null $previousEvent
	 * @return IEvent
	 * @throws \InvalidArgumentException
	 * @since 11.0.0
	 */
	public function parse(IEvent $event, IEvent $previousEvent = null) {
		if ($event->getApp() !== 'dav' || $event->getType() !== 'calendar') {
			throw new \InvalidArgumentException();
		}

		$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'places/calendar-dark.svg')));

		if ($event->getSubject() === self::SUBJECT_ADD) {
			$subject = $this->l->t('{actor} created calendar {calendar}');
		} else if ($event->getSubject() === self::SUBJECT_ADD . '_self') {
			$subject = $this->l->t('You created calendar {calendar}');
		} else if ($event->getSubject() === self::SUBJECT_DELETE) {
			$subject = $this->l->t('{actor} deleted calendar {calendar}');
		} else if ($event->getSubject() === self::SUBJECT_DELETE . '_self') {
			$subject = $this->l->t('You deleted calendar {calendar}');
		} else if ($event->getSubject() === self::SUBJECT_UPDATE) {
			$subject = $this->l->t('{actor} updated calendar {calendar}');
		} else if ($event->getSubject() === self::SUBJECT_UPDATE . '_self') {
			$subject = $this->l->t('You updated calendar {calendar}');

		} else if ($event->getSubject() === self::SUBJECT_SHARE_USER) {
			$subject = $this->l->t('{actor} shared calendar {calendar} with you');
		} else if ($event->getSubject() === self::SUBJECT_SHARE_USER . '_you') {
			$subject = $this->l->t('You shared calendar {calendar} with {user}');
		} else if ($event->getSubject() === self::SUBJECT_SHARE_USER . '_by') {
			$subject = $this->l->t('{actor} shared calendar {calendar} with {user}');
		} else if ($event->getSubject() === self::SUBJECT_UNSHARE_USER) {
			$subject = $this->l->t('{actor} unshared calendar {calendar} from you');
		} else if ($event->getSubject() === self::SUBJECT_UNSHARE_USER . '_you') {
			$subject = $this->l->t('You unshared calendar {calendar} from {user}');
		} else if ($event->getSubject() === self::SUBJECT_UNSHARE_USER . '_by') {
			$subject = $this->l->t('{actor} unshared calendar {calendar} from {user}');
		} else if ($event->getSubject() === self::SUBJECT_UNSHARE_USER . '_self') {
			$subject = $this->l->t('{actor} unshared calendar {calendar} from themselves');

		} else if ($event->getSubject() === self::SUBJECT_SHARE_GROUP . '_you') {
			$subject = $this->l->t('You shared calendar {calendar} with group {group}');
		} else if ($event->getSubject() === self::SUBJECT_SHARE_GROUP . '_by') {
			$subject = $this->l->t('{actor} shared calendar {calendar} with group {group}');
		} else if ($event->getSubject() === self::SUBJECT_UNSHARE_GROUP . '_you') {
			$subject = $this->l->t('You unshared calendar {calendar} from group {group}');
		} else if ($event->getSubject() === self::SUBJECT_UNSHARE_GROUP . '_by') {
			$subject = $this->l->t('{actor} unshared calendar {calendar} from group {group}');
		} else {
			throw new \InvalidArgumentException();
		}

		$parsedParameters = $this->getParameters($event);
		$this->setSubjects($event, $subject, $parsedParameters);

		$event = $this->eventMerger->mergeEvents('calendar', $event, $previousEvent);

		if ($event->getChildEvent() === null) {
			if (isset($parsedParameters['user'])) {
				// Couldn't group by calendar, maybe we can group by users
				$event = $this->eventMerger->mergeEvents('user', $event, $previousEvent);
			} else if (isset($parsedParameters['group'])) {
				// Couldn't group by calendar, maybe we can group by groups
				$event = $this->eventMerger->mergeEvents('group', $event, $previousEvent);
			}
		}

		return $event;
	}

	/**
	 * @param IEvent $event
	 * @param string $subject
	 * @param array $parameters
	 */
	protected function setSubjects(IEvent $event, $subject, array $parameters) {
		$placeholders = $replacements = [];
		foreach ($parameters as $placeholder => $parameter) {
			$placeholders[] = '{' . $placeholder . '}';
			$replacements[] = $parameter['name'];
		}

		$event->setParsedSubject(str_replace($placeholders, $replacements, $subject))
			->setRichSubject($subject, $parameters);
	}

	/**
	 * @param IEvent $event
	 * @return array
	 */
	protected function getParameters(IEvent $event) {
		$subject = $event->getSubject();
		$parameters = $event->getSubjectParameters();

		switch ($subject) {
			case self::SUBJECT_ADD:
			case self::SUBJECT_ADD . '_self':
			case self::SUBJECT_DELETE:
			case self::SUBJECT_DELETE . '_self':
			case self::SUBJECT_UPDATE:
			case self::SUBJECT_UPDATE . '_self':
			case self::SUBJECT_SHARE_USER:
			case self::SUBJECT_UNSHARE_USER:
			case self::SUBJECT_UNSHARE_USER . '_self':
				return [
					'actor' => $this->generateUserParameter($parameters[0]),
					'calendar' => $this->generateCalendarParameter($event->getObjectId(), $parameters[1]),
				];
			case self::SUBJECT_SHARE_USER . '_you':
			case self::SUBJECT_UNSHARE_USER . '_you':
				return [
					'user' => $this->generateUserParameter($parameters[0]),
					'calendar' => $this->generateCalendarParameter($event->getObjectId(), $parameters[1]),
				];
			case self::SUBJECT_SHARE_USER . '_by':
			case self::SUBJECT_UNSHARE_USER . '_by':
				return [
					'user' => $this->generateUserParameter($parameters[0]),
					'calendar' => $this->generateCalendarParameter($event->getObjectId(), $parameters[1]),
					'actor' => $this->generateUserParameter($parameters[2]),
				];
			case self::SUBJECT_SHARE_GROUP . '_you':
			case self::SUBJECT_UNSHARE_GROUP . '_you':
				return [
					'group' => $this->generateGroupParameter($parameters[0]),
					'calendar' => $this->generateCalendarParameter($event->getObjectId(), $parameters[1]),
				];
			case self::SUBJECT_SHARE_GROUP . '_by':
			case self::SUBJECT_UNSHARE_GROUP . '_by':
				return [
					'group' => $this->generateGroupParameter($parameters[0]),
					'calendar' => $this->generateCalendarParameter($event->getObjectId(), $parameters[1]),
					'actor' => $this->generateUserParameter($parameters[2]),
				];
		}

		throw new \InvalidArgumentException();
	}

	/**
	 * @param string $id
	 * @return array
	 */
	protected function generateGroupParameter($id) {
		return [
			'type' => 'group',
			'id' => $id,
			'name' => $id,
		];
	}

	/**
	 * @param int $id
	 * @param string $name
	 * @return array
	 */
	protected function generateCalendarParameter($id, $name) {
		return [
			'type' => 'calendar',
			'id' => $id,
			'name' => $name,
		];
	}

	/**
	 * @param string $uid
	 * @return array
	 */
	protected function generateUserParameter($uid) {
		if (!isset($this->displayNames[$uid])) {
			$this->displayNames[$uid] = $this->getDisplayName($uid);
		}

		return [
			'type' => 'user',
			'id' => $uid,
			'name' => $this->displayNames[$uid],
		];
	}

	/**
	 * @param string $uid
	 * @return string
	 */
	protected function getDisplayName($uid) {
		$user = $this->userManager->get($uid);
		if ($user instanceof IUser) {
			return $user->getDisplayName();
		} else {
			return $uid;
		}
	}
}
