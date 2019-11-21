<?php

namespace Drupal\Tests\os_fullcalendar\ExistingSite;

use Drupal\Component\Datetime\DateTimePlus;

/**
 * Tests upcoming calendar pages.
 *
 * @group other
 * @group kernel
 */
class EventsCalendarTest extends EventTestBase {

  /**
   * Tests upcoming events calendar page.
   */
  public function testUpcomingEventsCalendarView(): void {
    $web_assert = $this->assertSession();

    $start = new DateTimePlus('tomorrow midnight', $this->user->getTimeZone());
    $end = new DateTimePlus('2 day midnight', $this->user->getTimeZone());

    /** @var \Drupal\node\NodeInterface $upcoming_event */
    $upcoming_event = $this->createEvent([
      'title' => 'Upcoming test event',
      'field_recurring_date' => [
        'value' => $start->format("Y-m-d\TH:i:s"),
        'end_value' => $end->format("Y-m-d\TH:i:s"),
        'rrule' => '',
        'timezone' => $this->user->getTimeZone(),
        'infinite' => FALSE,
      ],
      'field_location' => 'London',
      'body' => 'Test body content',
      'status' => TRUE,
    ]);

    $this->group->addContent($upcoming_event, "group_node:{$upcoming_event->bundle()}");
    $this->visit("{$this->group->get('path')->first()->getValue()['alias']}/calendar/upcoming");
    $web_assert->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Upcoming test event');
  }

}
