<?php

namespace Drupal\Tests\os_software\ExistingSite;

use Drupal\Tests\openscholar\ExistingSite\OsExistingSiteTestBase;
use Drupal\vsite_preset\Entity\GroupPreset;

/**
 * SoftwareReleasesDefaultWidgetFunctionalTest.
 *
 * @group os
 * @group functional
 */
class SoftwareReleasesDefaultWidgetFunctionalTest extends OsExistingSiteTestBase {

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
  public function testSoftwareReleasesDefaultWidgetCreation() {

    // Retrieve file creation csv source path and call creation method.
    foreach ($this->paths as $uri) {
      $this->vsitePresetHelper->createDefaultContent($this->group, $uri);
    }

    // Test Negative.
    $this->visitViaVsite('', $this->group);
    $this->assertSession()->pageTextNotContains('SOFTWARE RELEASES');

    // Test positive.
    $this->visitViaVsite('software', $this->group);
    $this->assertSession()->pageTextContains('SOFTWARE RELEASES');
    $this->assertSession()->elementExists('css', '.block--type-views');
    $this->assertSession()->elementExists('css', '.region-sidebar-second .block--type-views');
  }

}
