<?php

namespace Drupal\Tests\cp_appearance\Unit;

use Drupal\Core\Asset\CssCollectionRenderer as CoreCssCollectionRenderer;
use Drupal\Core\State\State;
use Drupal\cp_appearance\CssCollectionRenderer;
use Drupal\group\Entity\Group;
use Drupal\Tests\UnitTestCase;
use Drupal\vsite\Plugin\VsiteContextManager;

/**
 * Tests CpAppearance CssCollectionRenderer.
 *
 * @group unit
 * @coversDefaultClass \Drupal\cp_appearance\CssCollectionRenderer
 */
class CssCollectionRendererTest extends UnitTestCase {

  /**
   * Mocked core's css collection renderer.
   *
   * @var \Drupal\Core\Asset\CssCollectionRenderer|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $coreCssCollectionRenderer;

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
  public function setUp() {
    parent::setUp();

    $this->coreCssCollectionRenderer = $this->createMock(CoreCssCollectionRenderer::class);
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
        [
          [
            'type' => 'file',
            'data' => 'public://css/css_dump_1.css',
            'preprocessed' => TRUE,
          ],
          [
            'type' => 'file',
            'data' => 'public://css/css_dump_2.css',
            'preprocessed' => TRUE,
          ],
        ],
        [
          [
            '#type' => 'html_tag',
            '#tag' => 'link',
            '#attributes' => [
              'rel' => 'stylesheet',
              'media' => 'all',
              'href' => '/sites/default/files/css/css_dump_1.css',
            ],
          ],
          [
            '#type' => 'html_tag',
            '#tag' => 'link',
            '#attributes' => [
              'rel' => 'stylesheet',
              'media' => 'all',
              'href' => '/sites/default/files/css/css_dump_2.css',
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
    $this->coreCssCollectionRenderer->method('render')->with($assets)->willReturn($elements);
    $this->vsiteContextManager->method('getActiveVsite')->willReturn(NULL);

    $cp_appearance_css_collection_renderer = new CssCollectionRenderer($this->coreCssCollectionRenderer, $this->state, $this->vsiteContextManager);

    $rendered_elements = $cp_appearance_css_collection_renderer->render($assets);

    $this->assertArrayEquals($elements, $rendered_elements);
  }

  /**
   * @covers ::render
   *
   * @dataProvider assetRenderProvider
   */
  public function testInsideVsiteNoCustomTheme($assets, $elements): void {
    $this->coreCssCollectionRenderer->method('render')->with($assets)->willReturn($elements);
    $this->vsiteContextManager->method('getActiveVsite')->willReturn($this->vsite);

    $cp_appearance_css_collection_renderer = new CssCollectionRenderer($this->coreCssCollectionRenderer, $this->state, $this->vsiteContextManager);

    $rendered_elements = $cp_appearance_css_collection_renderer->render($assets);

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
      'data' => 'themes/custom_themes/os_ct_theme_id/style.css',
      'preprocessed' => FALSE,
    ];

    $elements[] = [
      '#type' => 'html_tag',
      '#tag' => 'link',
      '#attributes' => [
        'rel' => 'stylesheet',
        'media' => 'all',
        'href' => '/themes/custom_themes/os_ct_theme_id/style.css?cache_buster_orig',
      ],
    ];

    $is_cache_updated = FALSE;

    $this->coreCssCollectionRenderer->method('render')->with($assets)->willReturn($elements);
    $this->vsiteContextManager->method('getActiveVsite')->willReturn($this->vsite);
    $this->vsite->method('id')->willReturn(47);
    $this->state->method('get')->with('vsite.css_js_query_string.47')->willReturn('cache_buster_new');

    $cp_appearance_css_collection_renderer = new CssCollectionRenderer($this->coreCssCollectionRenderer, $this->state, $this->vsiteContextManager);

    $rendered_elements = $cp_appearance_css_collection_renderer->render($assets);

    foreach ($rendered_elements as $element) {
      $is_cache_updated = (strpos($element['#attributes']['href'], 'cache_buster_new') !== FALSE);
    }

    $this->assertTrue($is_cache_updated);
  }

}
