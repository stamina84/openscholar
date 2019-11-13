<?php

namespace Drupal\Tests\os_widgets\Unit;

use Drupal\os_widgets\OsWidgetsContext;
use Drupal\Tests\UnitTestCase;

/**
 * Class OsWidgetsContextTest.
 *
 * @group unit
 * @covers \Drupal\os_widgets\OsWidgetsContext
 */
class OsWidgetsContextTest extends UnitTestCase {

  /**
   * The object we're testing.
   *
   * @var \Drupal\os_widgets\OsWidgetsContext
   */
  protected $osWidgetsContext;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->osWidgetsContext = new OsWidgetsContext();
  }

  /**
   * Test adding new app.
   */
  public function testAddApp() {
    $this->osWidgetsContext->addApp('my_app_id');
    $active_apps = $this->osWidgetsContext->getActiveApps();
    $this->assertSame(['my_app_id'], $active_apps);

    // Add existing one.
    $this->osWidgetsContext->addApp('my_app_id');
    $active_apps = $this->osWidgetsContext->getActiveApps();
    $this->assertSame(['my_app_id'], $active_apps);
  }

  /**
   * Test adding new app.
   */
  public function testResetApp() {
    $this->osWidgetsContext->addApp('my_app_id');
    $this->osWidgetsContext->resetApps();
    $active_apps = $this->osWidgetsContext->getActiveApps();
    $this->assertSame([], $active_apps);
  }

}
