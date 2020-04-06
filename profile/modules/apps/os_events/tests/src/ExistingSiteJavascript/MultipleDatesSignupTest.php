<?php

namespace Drupal\Tests\os_events\ExistingSiteJavascript;

use Drupal\Component\Datetime\DateTimePlus;

/**
 * Class MultipleDatesSignupTest.
 *
 * @group functional-javascript
 * @group events
 * @package Drupal\Tests\os_events\ExistingSiteJavascript
 */
class MultipleDatesSignupTest extends EventsJavascriptTestBase {
  /**
   * Simple user.
   *
   * @var \Drupal\user\Entity\User|false
   */
  protected $simpleUser;

  /**
   * Group administrator.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $groupAdmin;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->simpleUser = $this->createUser();
    $this->entityTypeManager = $this->container->get('entity_type.manager');

    $this->groupAdmin = $this->createUser();
    $this->addGroupAdmin($this->groupAdmin, $this->group);
  }

  /**
   * Test if Select List exists.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testSelectList() {

    $url = $this->createRecurringEvent();
    $this->drupalLogin($this->simpleUser);

    $this->visit($url);

    $web_assert = $this->assertSession();
    $web_assert->statusCodeEquals(200);
    $web_assert->selectExists('rdates');
  }

  /**
   * Test Select List selections change the Signup link via Ajax.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  public function testSelection() {
    $url = $this->createRecurringEvent();
    $this->drupalLogin($this->simpleUser);

    $this->visit($url);

    $web_assert = $this->assertSession();
    $page = $this->getCurrentPage();

    $hrefBefore = $page->findById('events_signup_modal_form')->getAttribute('href');

    $dateTimeObject = new DateTimePlus('+12 day');
    $dateString = $dateTimeObject->format('l, F j, Y');
    $page->selectFieldOption('rdates', $dateString);
    $web_assert->assertWaitOnAjaxRequest();

    $hrefAfter = $page->findById('events_signup_modal_form')->getAttribute('href');
    $this->assertNotEquals($hrefAfter, $hrefBefore);
  }

  /**
   * Tests Registration List view filter.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testRegistrationListFilter() {

    $url = $this->createRecurringEvent();
    $this->visit($url);

    $web_assert = $this->assertSession();
    $page = $this->getCurrentPage();

    $dateTimeObject = new DateTimePlus('+12 day');
    $dateString = $dateTimeObject->format('l, F j, Y');
    $page->selectFieldOption('rdates', $dateString);
    $web_assert->assertWaitOnAjaxRequest();

    $signup_link = $page->findById('events_signup_modal_form');
    $signup_link->click();
    $web_assert->assertWaitOnAjaxRequest();
    $web_assert->waitForElementVisible('css', '#signup-modal-form');

    $edit = [
      'email' => 'test@example.com',
      'full_name' => $this->randomString(),
      'department' => $this->randomString(),
    ];

    $this->submitForm($edit, 'Signup');
    $web_assert->assertWaitOnAjaxRequest();
    $page->clickLink('Manage Registrations');
    $web_assert->waitForElementVisible('css', '.field--type-datetime');
    $page->clickLink('Registrations');
    $web_assert->waitForElementVisible('css', '.view-rng-registrations-node');
    $page->pressButton('Apply');
    $this->assertSession()->pageTextContains('test@example.com');
  }

  /**
   * Test event list should not contains any media link.
   */
  public function testListPageNotContainsMedia() {
    $media = $this->createMedia();
    $this->group->addContent($media, "group_entity:media");

    $assert_session = $this->assertSession();
    $this->drupalLogin($this->groupAdmin);

    // Creating event type node.
    $title = $this->randomMachineName();
    $this->visitViaVsite('node/add/events', $this->group);
    $this->assertSession()->statusCodeEquals(200);

    $this->getSession()->getPage()->fillField('title[0][value]', $title);
    $this->getSession()->getPage()->fillField('field_recurring_date[0][day_start]', date("Y-m-d"));
    $this->getSession()->getPage()->fillField('field_recurring_date[0][is_all_day]', TRUE);
    $this->getSession()->getPage()->fillField('field_recurring_date[0][day_end]', date("Y-m-d"));

    // Attaching media to the event page.
    $assert_session->waitForElementVisible('css', '.media-browser-drop-box');
    $node_upload = $this->getCurrentPage()->find('css', '.field--name-field-attached-media #upmedia');
    $node_upload->click();
    $this->attachMediaViaMediaBrowser();

    // Save event.
    $this->getSession()->getPage()->pressButton('Save');
    $this->assertSession()->statusCodeEquals(200);

    // Assertion to check attached media field on list page.
    $event_with_media = $this->getNodeByTitle($title);
    $this->assertNotEmpty($event_with_media);

    // Redirect to upcoming event list page.
    $this->visitViaVsite('calendar/upcoming', $this->group);
    $this->assertSession()->statusCodeEquals(200);
    $assert_session->elementNotExists('css', '.field--name-field-attached-media');

    // Redirect to past events list page.
    $this->visitViaVsite('calendar/past_events', $this->group);
    $this->assertSession()->statusCodeEquals(200);
    $assert_session->elementNotExists('css', '.field--name-field-attached-media');
  }

}
