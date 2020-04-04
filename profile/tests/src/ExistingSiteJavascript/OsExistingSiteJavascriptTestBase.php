<?php

namespace Drupal\Tests\openscholar\ExistingSiteJavascript;

use Drupal\Tests\openscholar\Traits\ExistingSiteTestTrait;
use Drupal\Tests\openscholar\Traits\OsCleanupClassTestTrait;
use weitzman\DrupalTestTraits\ExistingSiteWebDriverTestBase;

/**
 * OS functional javascript test base.
 */
abstract class OsExistingSiteJavascriptTestBase extends ExistingSiteWebDriverTestBase {

  use ExistingSiteTestTrait;
  use OsCleanupClassTestTrait;

  /**
   * Test group.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $group;

  /**
   * Test group alias.
   *
   * @var string
   */
  protected $groupAlias;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->group = $this->createGroup();
    $this->groupAlias = $this->group->get('path')->first()->getValue()['alias'];
  }

  /**
   * Waits for the given time or until the given JS condition becomes TRUE.
   *
   * Shamelessly copied from DrupalCommerce.
   *
   * @param string $condition
   *   JS condition to wait until it becomes TRUE.
   * @param int $timeout
   *   (Optional) Timeout in milliseconds, defaults to 1000.
   * @param string $message
   *   (optional) A message to display with the assertion. If left blank, a
   *   default message will be displayed.
   *
   * @see \Behat\Mink\Driver\DriverInterface::evaluateScript()
   *
   * @throws \Behat\Mink\Exception\UnsupportedDriverActionException
   * @throws \Behat\Mink\Exception\DriverException
   */
  protected function assertJsCondition($condition, $timeout = 1000, $message = ''): void {
    $message = $message ?: "Javascript condition met:\n" . $condition;
    $result = $this->getSession()->getDriver()->wait($timeout, $condition);
    $this->assertNotEmpty($result, $message);
  }

  /**
   * Waits for jQuery to become active and animations to complete.
   *
   * Shamelessly copied from DrupalCommerce.
   *
   * @throws \Behat\Mink\Exception\UnsupportedDriverActionException
   * @throws \Behat\Mink\Exception\DriverException
   */
  protected function waitForAjaxToFinish(): void {
    $condition = "(typeof jQuery != 'undefined' && 0 === jQuery.active && 0 === jQuery(':animated').length)";
    $this->assertJsCondition($condition, 10000);
  }

  /**
   * Waits for jQuery to dialog is closed.
   *
   * @throws \Behat\Mink\Exception\UnsupportedDriverActionException
   * @throws \Behat\Mink\Exception\DriverException
   */
  protected function waitForDialogClose(): void {
    $condition = "(jQuery('.ui-dialog').length === 0)";
    $this->assertJsCondition($condition, 10000);
  }

  /**
   * Attach Media via tha browser after it is opened.
   *
   * @throws \Behat\Mink\Exception\DriverException
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Behat\Mink\Exception\UnsupportedDriverActionException
   */
  protected function attachMediaViaMediaBrowser():void {
    $this->assertSession()->waitForElementVisible('css', '.media-browser-buttons');
    $this->assertSession()->pageTextContains('Previously uploaded files');
    // Select "Previously uploaded files" tab.
    $this->getSession()->getPage()->find('css', '.media-browser-button--library')->click();
    // Select first media.
    $this->getSession()->getPage()->find('css', '.media-row')->click();
    // Insert to field.
    $this->getSession()->getPage()->pressButton('Insert');
    $this->waitForDialogClose();
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    $this->cleanupEntities = array_reverse($this->cleanupEntities);
    parent::tearDown();

    foreach ($this->cleanUpConfigs as $config_entity) {
      $config_entity->delete();
    }

    $this->cleanUpProperties(self::class);
  }

}
