<?php

namespace Drupal\Tests\vsite_preset\ExistingSiteJavascript;

use Drupal\Tests\openscholar\ExistingSiteJavascript\OsExistingSiteJavascriptTestBase;
use Drupal\vsite_preset\Entity\GroupPreset;

/**
 * Tests vsite presets.
 *
 * @group vsite-preset
 * @group functional-javascript
 */
class VsitePresetTest extends OsExistingSiteJavascriptTestBase {

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
      'type' => 'department',
      'field_preset' => 'os_department',
    ]);
  }

  /**
   * Tests Minimal Department preset vsite.
   */
  public function testMinimalDepartmentPreset() {
    /** @var \Drupal\vsite_preset\Entity\GroupPreset $preset */
    $preset = GroupPreset::load('os_department');
    $paths = $preset->getCreationFilePaths();
    $uriArr = array_keys($paths['department']);

    // Retrieve file creation csv source path.
    foreach ($uriArr as $uri) {
      $this->vsitePresetHelper->createDefaultContent($this->group, $uri);
    }

    $web_assert = $this->assertSession();
    $this->visitViaVsite('', $this->group);
    $web_assert->statusCodeEquals(200);

    $web_assert->linkExists('Home');
  }

}
