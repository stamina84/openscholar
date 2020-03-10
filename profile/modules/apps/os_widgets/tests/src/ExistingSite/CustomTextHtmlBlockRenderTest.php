<?php

namespace Drupal\Tests\os_widgets\ExistingSite;

use Drupal\vsite_preset\Entity\GroupPreset;

/**
 * Class CustomTextHtmlBlockRenderTest.
 *
 * @group kernel
 * @group widgets-2
 * @covers \Drupal\os_widgets\Plugin\OsWidgets\CustomTextHtmlWidget
 */
class CustomTextHtmlBlockRenderTest extends OsWidgetsExistingSiteTestBase {

  /**
   * The object we're testing.
   *
   * @var \Drupal\os_widgets\Plugin\OsWidgets\CustomTextHtmlWidget
   */
  protected $customTextHtmlWidget;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->customTextHtmlWidget = $this->osWidgets->createInstance('custom_text_html_widget');
    $this->vsitePresetHelper = $this->container->get('vsite_preset.preset_helper');
    $this->entityTypeManager = $this->container->get('entity_type.manager');
  }

  /**
   * Test build with suspicious/unsecure body.
   */
  public function testBuildSuspiciousBody() {

    $block_content = $this->createBlockContent([
      'type' => 'custom_text_html',
      'body' => [
        'Lorem<script type="application/javascript">var bad_code;</script>Ipsum',
      ],
    ]);
    $view_builder = $this->entityTypeManager
      ->getViewBuilder('block_content');
    $render = $view_builder->view($block_content);
    $renderer = $this->container->get('renderer');

    /** @var \Drupal\Core\Render\Markup $markup_array */
    $markup = $renderer->renderRoot($render);
    $this->assertContains('<p>Lorem</p>', $markup->__toString());
    $this->assertNotContains('<p><script type="application/javascript">var bad_code;</script></p>', $markup->__toString());
    $this->assertContains('<p>Ipsum</p>', $markup->__toString());
  }

  /**
   * Test special chars in css classes field.
   */
  public function testBuildClassSpecialChars() {

    $block_content = $this->createBlockContent([
      'type' => 'custom_text_html',
      'field_css_classes' => [
        'text-_\'"+!%/=$ß¤×÷;css second-class  third-with-extra-space 123456',
      ],
    ]);
    $build = [];
    $this->customTextHtmlWidget->buildBlock($build, $block_content);
    $this->assertSame('text---ß¤×÷css', $build['#extra_classes'][0]);
    $this->assertSame('second-class', $build['#extra_classes'][1]);
    $this->assertSame('', $build['#extra_classes'][2]);
    $this->assertSame('third-with-extra-space', $build['#extra_classes'][3]);
    $this->assertSame('_23456', $build['#extra_classes'][4]);
  }

  /**
   * Tests CreateWidget().
   *
   * @throws \Exception
   */
  public function testCreateWidget(): void {
    /** @var \Drupal\vsite_preset\Entity\GroupPreset $preset */
    $preset = GroupPreset::load('personal');
    $uriArr = $this->vsitePresetHelper->getCreateFilePaths($preset, $this->group);
    $blockStorage = $this->entityTypeManager->getStorage('group_content');
    $gid = $this->group->id();
    // Test negative, block content does not exist already.
    $blockArr = $blockStorage->loadByProperties([
      'gid' => $gid,
      'label' => 'Contact Widget',
    ]);
    $this->assertEmpty($blockArr);
    // Retrieve file creation csv source path.
    foreach ($uriArr as $uri) {
      $this->vsitePresetHelper->createDefaultContent($this->group, $uri);
    }
    // Test positive page content is created.
    $blockArr = $blockStorage->loadByProperties([
      'gid' => $gid,
      'label' => 'Contact Widget',
    ]);
    $this->assertNotEmpty($blockArr);
    $blockEntity = array_values($blockArr)[0];
    $blockEntityId = $blockEntity->entity_id->target_id;
    // Assert correct field values.
    $blockContentEntity = $this->entityTypeManager->getStorage('block_content')->load($blockEntityId);
    $widgeTitle = $blockContentEntity->get('field_widget_title')->value;
    $info = $blockContentEntity->get('info')->value;
    $this->assertEquals('Contact', $widgeTitle);
    $this->assertEquals('Contact Widget', $info);
  }

}
