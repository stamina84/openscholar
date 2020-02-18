<?php

namespace Drupal\cp_appearance;

use Drupal\Core\Asset\AssetCollectionRendererInterface;
use Drupal\cp_appearance\Entity\CustomTheme;

/**
 * Makes sure only custom theme scripts are refreshed in browser.
 */
class JsCollectionRenderer implements AssetCollectionRendererInterface {

  /**
   * Core's js collection renderer.
   *
   * @var \Drupal\Core\Asset\AssetCollectionRendererInterface
   */
  protected $coreJsCollectionRenderer;

  /**
   * Creates a new JsCollectionRenderer object.
   *
   * @param \Drupal\Core\Asset\AssetCollectionRendererInterface $js_collection_renderer
   *   Core's js collection renderer.
   */
  public function __construct(AssetCollectionRendererInterface $js_collection_renderer) {
    $this->coreJsCollectionRenderer = $js_collection_renderer;
  }

  /**
   * {@inheritdoc}
   */
  public function render(array $assets): array {
    $elements = $this->coreJsCollectionRenderer->render($assets);

    // Only alter the query string for any custom theme script.
    array_walk($elements, static function (&$item) {
      if (isset($item['#attributes']['src']) &&
        strpos($item['#attributes']['src'], CustomTheme::CUSTOM_THEME_ID_PREFIX) !== FALSE) {
        list($path) = explode('?', $item['#attributes']['src']);
        // TODO: Use the custom state here.
        $new_query_string = base_convert(REQUEST_TIME + 1, 10, 36);
        $item['#attributes']['src'] = "$path?$new_query_string";
      }
    });

    return $elements;
  }

}
