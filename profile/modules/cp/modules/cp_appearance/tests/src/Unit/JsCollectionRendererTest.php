<?php

namespace Drupal\Tests\cp_appearance\Unit;

use Drupal\Core\Asset\CssCollectionRenderer as CoreCssCollectionRenderer;
use Drupal\Core\State\State;
use Drupal\cp_appearance\JsCollectionRenderer;
use Drupal\group\Entity\Group;
use Drupal\Tests\UnitTestCase;
use Drupal\vsite\Plugin\VsiteContextManager;

/**
 * Tests CpAppearance JsCollectionRenderer.
 *
 * @group unit
 * @coversDefaultClass \Drupal\cp_appearance\JsCollectionRenderer
 */
class JsCollectionRendererTest extends UnitTestCase {

  /**
   * Mocked core's js collection renderer.
   *
   * @var \Drupal\Core\Asset\JsCollectionRenderer|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $coreJsCollectionRenderer;

  /**
   * Mocked Drupal state.
   *
   * @var \Drupal\Core\State\StateInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $state;

  /**
   * Mocked vsite context manager.
   *
   * @var \Drupal\vsite\Plugin\VsiteContextManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $vsiteContextManager;

  /**
   * Mocked vsite.
   *
   * @var \Drupal\group\Entity\GroupInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $vsite;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->coreJsCollectionRenderer = $this->createMock(CoreCssCollectionRenderer::class);
    $this->state = $this->createMock(State::class);
    $this->vsiteContextManager = $this->createMock(VsiteContextManager::class);
    $this->vsite = $this->createMock(Group::class);
  }

  /**
   * Assets and Rendered provider.
   *
   * @return array
   *   Test data.
   */
  public function assetRenderProvider(): array {
    return [
      [
        'drupalSettings' => [
          [
            'type' => 'setting',
            'data' => [
              'path' => [
                'baseUrl' => '/',
              ],
              'otherBands' => 'lb',
            ],
          ],
          [
            'type' => 'file',
            'minified' => TRUE,
            'data' => 'public://js/js_dump_1.js',
            'preprocess' => TRUE,
          ],
          [
            'type' => 'file',
            'minified' => TRUE,
            'data' => 'public://js/js_dump_2.js',
            'preprocess' => TRUE,
          ],
        ],
        [
          [
            '#type' => 'html_tag',
            '#tag' => 'script',
            '#value' => '{"path": {"baseUrl": "/"}, "otherBands": "lb"}',
            '#attributes' => [
              'type' => 'application/json',
              'data-drupal-selector' => 'drupal-settings-json',
            ],
          ],
          [
            '#type' => 'html_tag',
            '#tag' => 'script',
            '#attributes' => [
              'src' => '/sites/default/files/js/js_dump_1.js',
            ],
          ],
          [
            '#type' => 'html_tag',
            '#tag' => 'script',
            '#attributes' => [
              'src' => '/sites/default/files/js/js_dump_2.js',
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * @covers ::render
   *
   * @dataProvider assetRenderProvider
   */
  public function testOutsideVsite($assets, $elements): void {
    $this->coreJsCollectionRenderer->method('render')->with($assets)->willReturn($elements);
    $this->vsiteContextManager->method('getActiveVsite')->willReturn(NULL);

    $cp_appearance_js_collection_renderer = new JsCollectionRenderer($this->coreJsCollectionRenderer, $this->state, $this->vsiteContextManager);

    $rendered_elements = $cp_appearance_js_collection_renderer->render($assets);

    $this->assertArrayEquals($elements, $rendered_elements);
  }

  /**
   * @covers ::render
   *
   * @dataProvider assetRenderProvider
   */
  public function testInsideVsiteNoCustomTheme($assets, $elements): void {
    $this->coreJsCollectionRenderer->method('render')->with($assets)->willReturn($elements);
    $this->vsiteContextManager->method('getActiveVsite')->willReturn($this->vsite);

    $cp_appearance_js_collection_renderer = new JsCollectionRenderer($this->coreJsCollectionRenderer, $this->state, $this->vsiteContextManager);

    $rendered_elements = $cp_appearance_js_collection_renderer->render($assets);

    $this->assertArrayEquals($elements, $rendered_elements);
  }

  /**
   * @covers ::render
   *
   * @dataProvider assetRenderProvider
   */
  public function testOutsideVsiteWithCustomTheme($assets, $elements): void {
    $assets[] = [
      'type' => 'file',
      'minified' => FALSE,
      'data' => 'themes/custom_themes/os_ct_theme_id/script.js',
      'preprocess' => FALSE,
    ];

    $elements[] = [
      '#type' => 'html_tag',
      '#tag' => 'script',
      '#attributes' => [
        'src' => '/themes/custom_themes/os_ct_theme_id/script.js?cache_buster_orig',
      ],
    ];

    $is_cache_updated = FALSE;

    $this->coreJsCollectionRenderer->method('render')->with($assets)->willReturn($elements);
    $this->vsiteContextManager->method('getActiveVsite')->willReturn($this->vsite);
    $this->vsite->method('id')->willReturn(47);
    $this->state->method('get')->with('vsite.css_js_query_string.47')->willReturn('cache_buster_new');

    $cp_appearance_js_collection_renderer = new JsCollectionRenderer($this->coreJsCollectionRenderer, $this->state, $this->vsiteContextManager);

    $rendered_elements = $cp_appearance_js_collection_renderer->render($assets);

    foreach ($rendered_elements as $element) {
      $is_cache_updated = (isset($element['#attributes']['src']) && (strpos($element['#attributes']['src'], 'cache_buster_new') !== FALSE));
    }

    $this->assertTrue($is_cache_updated);
  }

}