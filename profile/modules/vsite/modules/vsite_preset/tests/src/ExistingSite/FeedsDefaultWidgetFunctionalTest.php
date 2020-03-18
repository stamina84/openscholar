<?php

namespace Drupal\Tests\vsite_preset\ExistingSite;

use Drupal\Tests\vsite\ExistingSite\VsiteExistingSiteTestBase;
use Drupal\vsite_preset\Entity\GroupPreset;

/**
 * FeedsDefaultWidgetFunctionalTest.
 *
 * @group vsite-preset
 * @group functional
 */
class FeedsDefaultWidgetFunctionalTest extends VsiteExistingSiteTestBase {

  /**
   * Test group.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $group;

  /**
   * Vsite helper service.
   *
   * @var \Drupal\vsite_preset\Helper\VsitePresetHelper
   */
  protected $vsitePresetHelper;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->vsitePresetHelper = $this->container->get('vsite_preset.preset_helper');
    $this->group = $this->createGroup([
      'type' => 'personal',
      'field_preset' => 'personal',
    ]);
    $this->vsiteContextManager->activateVsite($this->group);
  }

  /**
   * Test Default Widget is created and placed in proper context.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testRecentFeedsBlock() {
    /** @var \Drupal\vsite_preset\Entity\GroupPreset $preset */
    $preset = GroupPreset::load('personal');
    $uriArr = $this->vsitePresetHelper->getCreateFilePaths($preset, $this->group);
    // Test negative.
    $this->visitViaVsite('blog', $this->group);
    $this->assertSession()->elementNotExists('css', '.block--type-rss-feed');
    // Retrieve file creation csv source path.
    foreach ($uriArr as $uri) {
      $this->vsitePresetHelper->createDefaultContent($this->group, $uri);
    }
    // Test positive.
    $this->visitViaVsite('blog', $this->group);
    $this->assertSession()->elementExists('css', '.block--type-rss-feed');
    $this->assertSession()->elementExists('css', '.region-footer-left .block--type-rss-feed');
  }

}
