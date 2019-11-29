<?php

namespace Drupal\Tests\openscholar\Traits;

use DateInterval;
use DateTime;
use Drupal\group\Entity\GroupInterface;

/**
 * Provides a trait for taxonomy and vocab tests.
 */
trait WidgetsTestTrait {

  /**
   * Create multiple content entities for a vsite.
   *
   * @param \Drupal\group\Entity\GroupInterface $vsite
   *   Current vsite in context.
   *
   * @return array
   *   Created entities.
   *
   * @throws \Exception
   */
  public function createVsiteContent(GroupInterface $vsite) : array {
    $new_datetime = new DateTime();

    $ref1 = $this->createReference([
      'type' => 'artwork',
      'html_title' => 'Publication1',
    ]);
    $date_interval = new DateInterval('P1D');
    $new_datetime->add($date_interval);
    $date = $new_datetime->getTimestamp();
    $ref1->set('created', $date)->save();

    $ref2 = $this->createReference([
      'type' => 'book',
      'html_title' => 'Publication2',
    ]);
    $date_interval = new DateInterval('P2D');
    $new_datetime->add($date_interval);
    $date = $new_datetime->getTimestamp();
    $ref2->set('created', $date)->save();

    $node1 = $this->createNode([
      'type' => 'blog',
      'title' => 'Blog',
    ]);
    $date_interval = new DateInterval('P3D');
    $new_datetime->add($date_interval);
    $date = $new_datetime->getTimestamp();
    $node1->set('created', $date)->save();

    $node2 = $this->createNode([
      'type' => 'news',
      'title' => 'News',
    ]);
    $date_interval = new DateInterval('P4D');
    $new_datetime->add($date_interval);
    $date = $new_datetime->getTimestamp();
    $node2->set('created', $date)->save();

    $vsite->addContent($ref1, 'group_entity:bibcite_reference');
    $vsite->addContent($ref2, 'group_entity:bibcite_reference');
    $vsite->addContent($node1, 'group_node:blog');
    $vsite->addContent($node2, 'group_node:news');

    return [$ref1, $ref2, $node1, $node2];
  }

  /**
   * Creates Events needed for various tests.
   */
  public function createVsiteEvents(GroupInterface $vsite) : array {
    // For case when 30 minutes have not passed.
    $timezone = drupal_get_user_timezone();
    $new_datetime = new DateTime('now', new \DateTimeZone($timezone));
    $date_interval = new DateInterval('PT25M');
    $date_interval->invert = 1;
    $new_datetime->add($date_interval);
    $date = $new_datetime->format("Y-m-d\TH:i:s");
    $eventNode1 = $this->createNode([
      'type' => 'events',
      'status' => 1,
      'field_recurring_date' => [
        'value' => $date,
        'end_value' => $date,
        'timezone' => $timezone,
        'infinite' => 0,
      ],
    ]);

    // For case when 30 minutes have passed.
    $new_datetime = new DateTime('now', new \DateTimeZone($timezone));
    $date_interval = new DateInterval('PT31M');
    $date_interval->invert = 1;
    $new_datetime->add($date_interval);
    $date = $new_datetime->format("Y-m-d\TH:i:s");
    $eventNode2 = $this->createNode([
      'type' => 'events',
      'status' => 1,
      'field_recurring_date' => [
        'value' => $date,
        'end_value' => $date,
        'timezone' => $timezone,
        'infinite' => 0,
      ],
    ]);

    // For when End of Day has passed.
    $new_datetime = new DateTime('now', new \DateTimeZone($timezone));
    $date_interval = new DateInterval('P1D');
    $date_interval->invert = 1;
    $new_datetime->add($date_interval);
    $date = $new_datetime->format("Y-m-d\TH:i:s");
    $end_datetime = new DateTime('now', new \DateTimeZone($timezone));
    $end_date = $end_datetime->add(new DateInterval('P1D'))->format("Y-m-d\TH:i:s");
    $eventNode3 = $this->createNode([
      'type' => 'events',
      'status' => 1,
      'field_recurring_date' => [
        'value' => $date,
        'end_value' => $end_date,
        'timezone' => $timezone,
        'infinite' => 0,
      ],
    ]);

    return [$eventNode1, $eventNode2, $eventNode3];
  }

}
