<?php

namespace Drupal\Tests\vsite_preset\ExistingSite;

use Drupal\Tests\vsite\ExistingSite\VsiteExistingSiteTestBase;
use Drupal\vsite_preset\Entity\GroupPreset;

/**
 * AddThisDefaultWidgetFunctionalTest.
 *
 * @group vsite-preset
 * @group functional
 */
class AddThisDefaultWidgetFunctionalTest extends VsiteExistingSiteTestBase {

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
      'field_preset' => 'minimal',
    ]);
    $this->vsiteContextManager->activateVsite($this->group);
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
  public function testRecentEventsBlock() {
    /** @var \Drupal\vsite_preset\Entity\GroupPreset $preset */
    $preset = GroupPreset::load('minimal');
    $uriArr = $this->vsitePresetHelper->getCreateFilePaths($preset, $this->group);
    // Retrieve file creation csv source path.
    foreach ($uriArr as $uri) {
      $this->vsitePresetHelper->createDefaultContent($this->group, $uri);
    }

    // Test negative.
    $this->visitViaVsite('faq', $this->group);
    $this->assertSession()->elementNotExists('css', '.addthis_toolbox');

    // Test positive.
    $this->visitViaVsite('publications', $this->group);
    $this->assertSession()->elementExists('css', '.addthis_toolbox');
    $this->assertSession()->elementExists('css', '.region-sidebar-second .block--type-addthis');

    $this->visitViaVsite('software', $this->group);
    $this->assertSession()->elementExists('css', '.addthis_toolbox');
    $this->assertSession()->elementExists('css', '.region-sidebar-second .block--type-addthis');

    $this->visitViaVsite('blog', $this->group);
    $this->assertSession()->elementExists('css', '.addthis_toolbox');
    $this->assertSession()->elementExists('css', '.region-sidebar-second .block--type-addthis');

    $this->visitViaVsite('classes', $this->group);
    $this->assertSession()->elementExists('css', '.addthis_toolbox');
    $this->assertSession()->elementExists('css', '.region-sidebar-second .block--type-addthis');

    $this->visitViaVsite('news', $this->group);
    $this->assertSession()->elementExists('css', '.addthis_toolbox');
    $this->assertSession()->elementExists('css', '.region-sidebar-second .block--type-addthis');

    $this->visitViaVsite('presentations', $this->group);
    $this->assertSession()->elementExists('css', '.addthis_toolbox');
    $this->assertSession()->elementExists('css', '.region-sidebar-second .block--type-addthis');
  }

}
