<?php

namespace Drupal\Tests\os_blog\ExistingSite;

use Drupal\Tests\openscholar\ExistingSite\OsExistingSiteTestBase;
use Drupal\vsite_preset\Entity\GroupPreset;

/**
 * BlogPostsByMonthFunctionalTest.
 *
 * @group vsite-preset
 * @group functional
 */
class BlogPostsByMonthFunctionalTest extends OsExistingSiteTestBase {

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
   * Test Default Widget is created and placed in proper context.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testBlogPostsByMonthFunctionalCreation() {

    /** @var \Drupal\vsite_preset\Entity\GroupPreset $preset */
    $preset = GroupPreset::load('minimal');
    $paths = $this->vsitePresetHelper->getCreateFilePaths($preset, $this->group);
    // Retrieve file creation csv source path and call creation method.
    foreach ($paths as $uri) {
      $this->vsitePresetHelper->createDefaultContent($this->group, $uri);
    }

    // Test Negative.
    $this->visitViaVsite('', $this->group);
    $this->assertSession()->pageTextNotContains('BLOG POSTS BY MONTH');

    // Test positive.
    $this->visitViaVsite('blog', $this->group);
    $this->assertSession()->pageTextContains('BLOG POSTS BY MONTH');
    $this->assertSession()->elementExists('css', '.region-sidebar-second .block--type-views');
  }

}
