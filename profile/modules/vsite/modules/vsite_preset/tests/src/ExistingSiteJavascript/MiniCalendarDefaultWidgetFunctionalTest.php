<?php

namespace Drupal\Tests\vsite_preset\ExistingSiteJavascript;

use Drupal\Component\Datetime\DateTimePlus;
use Drupal\Tests\openscholar\ExistingSiteJavascript\OsExistingSiteJavascriptTestBase;
use Drupal\Tests\os_events\Traits\EventTestTrait;
use Drupal\vsite_preset\Entity\GroupPreset;

/**
 * MiniCalendarDefaultWidgetFunctionalTest.
 *
 * @group vsite-preset
 * @group functional-javascript
 */
class MiniCalendarDefaultWidgetFunctionalTest extends OsExistingSiteJavascriptTestBase {
  use EventTestTrait;

  /**
   * Vsite helper service.
   *
   * @var \Drupal\vsite_preset\Helper\VsitePresetHelper
   */
  protected $vsitePresetHelper;

  /**
   * Entity Type Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Array of paths.
   *
   * @var array
   */
  protected $paths;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $vsiteContextManager = $this->container->get('vsite.context_manager');
    $this->vsitePresetHelper = $this->container->get('vsite_preset.preset_helper');
    $vsiteContextManager->activateVsite($this->group);
    /** @var \Drupal\vsite_preset\Entity\GroupPreset $preset */
    $preset = GroupPreset::load('minimal');
    $this->paths = $this->vsitePresetHelper->getCreateFilePaths($preset, $this->group);
  }

  /**
   * Test Default Widget is created and placed in proper context.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testMiniCalendarDefaultWidgetCreation() {

    // Retrieve file creation csv source path and call creation method.
    foreach ($this->paths as $uri) {
      $this->vsitePresetHelper->createDefaultContent($this->group, $uri);
    }

    // Test Negative.
    $this->visitViaVsite('', $this->group);
    $this->assertSession()->elementNotExists('css', 'region-sidebar-second .mini-calendar');

    // Test positive. Need to create an event because Mini calendar View does
    // not appear unless atleast one event content exists in a vsite.
    $start = new DateTimePlus('+1 day', date_default_timezone_get());
    $end = new DateTimePlus('+1 day +5 hours', date_default_timezone_get());
    $event = $this->createEvent([
      'title' => 'Test Event',
      'field_recurring_date' => [
        'value' => $start->format("Y-m-d\TH:i:s"),
        'end_value' => $end->format("Y-m-d\TH:i:s"),
        'rrule' => '',
        'timezone' => date_default_timezone_get(),
        'infinite' => FALSE,
      ],
      'status' => TRUE,
    ]);
    $this->group->addContent($event, 'group_node:events');
    // Test on calendar page.
    $this->visitViaVsite('calendar', $this->group);
    $this->assertSession()->elementExists('css', '.region-sidebar-second .mini-calendar');
    // Test on upcoming page.
    $this->visitViaVsite('calendar/upcoming', $this->group);
    $this->assertSession()->elementExists('css', '.region-sidebar-second .mini-calendar');
  }

}
