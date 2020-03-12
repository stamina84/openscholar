<?php

namespace Drupal\Tests\os_profiles\ExistingSite;

use Drupal\Tests\openscholar\ExistingSite\OsExistingSiteTestBase;
use Drupal\vsite_preset\Entity\GroupPreset;

/**
 * FilterByLastNameDefaultWidgetFunctionalTest.
 *
 * @group vsite-preset
 * @group functional
 */
class FilterByLastNameDefaultWidgetFunctionalTest extends OsExistingSiteTestBase {

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
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->vsitePresetHelper = $this->container->get('vsite_preset.preset_helper');
    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->vsiteContextManager->activateVsite($this->group);
  }

  /**
   * Test Default Widget is created and appears on page.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testFilterByLastNameDefaultWidget() {

    /** @var \Drupal\vsite_preset\Entity\GroupPreset $preset */
    $preset = GroupPreset::load('minimal');
    $paths = $this->vsitePresetHelper->getCreateFilePaths($preset, $this->group);
    // Retrieve file creation csv source path and call creation method.
    foreach ($paths as $uri) {
      $this->vsitePresetHelper->createDefaultContent($this->group, $uri);
    }

    // Test Negative.
    $this->visitViaVsite('', $this->group);
    $this->assertSession()->pageTextNotContains('FILTER BY ALPHABETICAL GROUPING OF LAST NAME');

    // Test positive.
    $this->visitViaVsite('people', $this->group);
    $this->assertSession()->pageTextContains('FILTER BY ALPHABETICAL GROUPING OF LAST NAME');
    $this->assertSession()->linkExists('A-E');
    $this->assertSession()->linkExists('F-J');
    $this->assertSession()->linkExists('K-O');
    $this->assertSession()->linkExists('P-T');
    $this->assertSession()->linkExists('U-Z');

    // Test that no exception is thrown when url has multiple arguments
    // separated by commas.
    $this->visitViaVsite('people/glossary/a,b,c,d,e', $this->group);
    $this->assertSession()->statusCodeEquals(200);

  }

}
