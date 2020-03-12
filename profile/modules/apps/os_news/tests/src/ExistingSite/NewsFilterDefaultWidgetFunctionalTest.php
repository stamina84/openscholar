<?php

namespace Drupal\Tests\os_news\ExistingSite;

use Drupal\Tests\openscholar\ExistingSite\OsExistingSiteTestBase;
use Drupal\vsite_preset\Entity\GroupPreset;

/**
 * NewsFilterDefaultWidgetFunctionalTest.
 *
 * @group news
 * @group functional
 */
class NewsFilterDefaultWidgetFunctionalTest extends OsExistingSiteTestBase {

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
    $this->vsitePresetHelper = $this->container->get('vsite_preset.preset_helper');
    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->vsiteContextManager->activateVsite($this->group);
    /** @var \Drupal\vsite_preset\Entity\GroupPreset $preset */
    $preset = GroupPreset::load('minimal');
    $this->paths = $this->vsitePresetHelper->getCreateFilePaths($preset, $this->group);
  }

  /**
   * Test Default Widget is created and placed in proper context.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testFilterNewsByYearDefaultWidgetCreation() {

    // Retrieve file creation csv source path and call creation method.
    foreach ($this->paths as $uri) {
      $this->vsitePresetHelper->createDefaultContent($this->group, $uri);
    }

    // Test Negative.
    $this->visitViaVsite('', $this->group);
    $this->assertSession()->pageTextNotContains('FILTER NEWS BY YEAR');

    // Test positive.
    $this->visitViaVsite('news', $this->group);
    $this->assertSession()->pageTextContains('FILTER NEWS BY YEAR');
  }

  /**
   * Test Default Widget Access.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testFilterNewsByYearDefaultWidgetPermissions() {

    // Retrieve file creation csv source path and call creation method.
    foreach ($this->paths as $uri) {
      $this->vsitePresetHelper->createDefaultContent($this->group, $uri);
    }

    $groupAdmin = $this->createUser();
    $this->addGroupAdmin($groupAdmin, $this->group);
    $this->drupalLogin($groupAdmin);

    // Get some data from the newly created widget block for assertions.
    $groupStorage = $this->entityTypeManager->getStorage('group_content');
    $blockArr = $groupStorage->loadByProperties([
      'gid' => $this->group->id(),
      'label' => 'Filter News by Year',
    ]);
    $blockEntity = array_values($blockArr)[0];
    $blockEntityId = $blockEntity->entity_id->target_id;

    // Test Access Denied for vsite admin.
    $this->visitViaVsite("block/$blockEntityId", $this->group);
    $this->assertSession()->statusCodeEquals(403);

    $this->visitViaVsite("block/$blockEntityId/delete", $this->group);
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalLogin($this->createAdminUser());

    // Test Access is Not Denied for site wide Admin user.
    $this->visitViaVsite("block/$blockEntityId", $this->group);
    $this->assertSession()->statusCodeEquals(200);

    $this->visitViaVsite("block/$blockEntityId/delete", $this->group);
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Test Default Widget is created and placed in proper context.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testFilterNewsByMonthDefaultWidgetCreation() {

    // Retrieve file creation csv source path and call creation method.
    foreach ($this->paths as $uri) {
      $this->vsitePresetHelper->createDefaultContent($this->group, $uri);
    }

    // Test Negative.
    $this->visitViaVsite('', $this->group);
    $this->assertSession()->pageTextNotContains('FILTER NEWS BY MONTH');

    // Test positive.
    $this->visitViaVsite('news', $this->group);
    $this->assertSession()->pageTextContains('FILTER NEWS BY MONTH');
  }

}
