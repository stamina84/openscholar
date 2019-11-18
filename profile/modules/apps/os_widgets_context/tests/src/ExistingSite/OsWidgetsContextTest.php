<?php

namespace Drupal\Tests\os_widgets\Unit;

use Drupal\Tests\openscholar\ExistingSite\OsExistingSiteTestBase;

/**
 * Class OsWidgetsContextTest.
 *
 * @group kernel
 * @group widgets-1
 * @covers \Drupal\os_widgets_context\OsWidgetsContext
 */
class OsWidgetsContextTest extends OsExistingSiteTestBase {

  /**
   * The object we're testing.
   *
   * @var \Drupal\os_widgets_context\OsWidgetsContext
   */
  protected $osWidgetsContext;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->osWidgetsContext = $this->container->get('os_widgets_context.context');
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
