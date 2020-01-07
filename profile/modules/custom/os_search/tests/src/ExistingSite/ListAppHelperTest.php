<?php

namespace Drupal\Tests\os_search\ExistingSite;

use Drupal\Tests\openscholar\ExistingSite\OsExistingSiteTestBase;

/**
 * Class ListAppHelperTest.
 *
 * @group kernel
 * @group widgets-4
 * @covers \Drupal\os_search\ListAppsHelper
 */
class ListAppHelperTest extends OsExistingSiteTestBase {

  /**
   * List Apps service.
   *
   * @var \Drupal\os_search\ListAppsHelper
   */
  protected $appHelper;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->appHelper = $this->container->get('os_search.list_app_helper');
  }

  /**
   * Test List App helper service.
   */
  public function test() {

    $app_lists = $this->appHelper->getAppLists();
    // Check blog app.
    $this->assertEquals('Blog', $app_lists['blog']);
    // Check Publications.
    $this->assertEquals('Publication', $app_lists['bibcite_reference']);
    // Check Software.
    $this->assertEquals('Software', $app_lists['software_project']);
    $this->assertEquals('Software', $app_lists['software_release']);
  }

}
