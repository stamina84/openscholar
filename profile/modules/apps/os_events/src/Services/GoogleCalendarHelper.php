<?php

namespace Drupal\os_events\Services;

use Drupal\Core\Url;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Class GoogleCalendarHelper.
 *
 * @package Drupal\os_events\Services
 */
class GoogleCalendarHelper {

  /**
   * Base url of google calender.
   */
  const BASE_URL = 'https://calendar.google.com/calendar/render';

  /**
   * Creates add to google calendar link.
   *
   * @param Drupal\Core\Entity\EntityInterface $entity
   *   The event entity.
   *
   * @return Drupal\Core\Url
   *   Google calendar link.
   */
  public function addToCalendarLink(EntityInterface $entity) {

    $startObj = DrupalDateTime::createFromTimestamp(strtotime($entity->get('field_recurring_date')->value));
    $endObj = DrupalDateTime::createFromTimestamp(strtotime($entity->get('field_recurring_date')->end_value));
    $options = [
      'query' => [
        'action' => 'TEMPLATE',
        'text' => $entity->getTitle(),
        'details' => $entity->get('body')->getValue()[0]['value'],
        'location' => $entity->get('field_location')->getValue()[0]['value'],
        'dates' => $startObj->format('Ymd\THis\Z') . '/' . $endObj->format('Ymd\THis\Z'),
      ],
      'attributes' => ['target' => '_blank'],
    ];
    return Url::fromUri(self::BASE_URL, $options);
  }

}
