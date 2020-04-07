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
class VsiteHWPProjectPresetTest extends OsExistingSiteJavascriptTestBase {

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
      'type' => 'project',
      'field_preset' => 'hwp_project',
    ]);
  }

  /**
   * Tests department preset vsite.
   */
  public function test() {
    /** @var \Drupal\vsite_preset\Entity\GroupPreset $preset */
    $preset = GroupPreset::load('hwp_project');
    $paths = $preset->getCreationFilePaths();
    $uriArr = array_keys($paths['project']);

    // Retrieve file creation csv source path.
    foreach ($uriArr as $uri) {
      $this->vsitePresetHelper->createDefaultContent($this->group, $uri);
    }

    $web_assert = $this->assertSession();
    $this->visitViaVsite('', $this->group);
    $web_assert->statusCodeEquals(200);

    $web_assert->linkExists('Home');
    $web_assert->linkExists('Overview');
    $web_assert->linkExists('Activities');
    $web_assert->linkExists('People');
    $web_assert->linkExists('Resources');
    $web_assert->linkExists('News & Events');
    $web_assert->linkExists('About');
  }

}
