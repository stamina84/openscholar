<?php

namespace Drupal\cp_appearance;

use Drupal\Core\Asset\AssetCollectionRendererInterface;
use Drupal\cp_appearance\Entity\CustomTheme;

/**
 * Makes sure only custom theme styles are refreshed in browser.
 */
class CssCollectionRenderer implements AssetCollectionRendererInterface {

  /**
   * Core's css collection renderer.
   *
   * @var \Drupal\Core\Asset\AssetCollectionRendererInterface
   */
  protected $coreCssCollectionRenderer;

  /**
   * Creates a new CssCollectionRenderer object.
   *
   * @param \Drupal\Core\Asset\AssetCollectionRendererInterface $css_collection_renderer
   *   Core's css collection renderer.
   */
  public function __construct(AssetCollectionRendererInterface $css_collection_renderer) {
    $this->coreCssCollectionRenderer = $css_collection_renderer;
  }

  /**
   * {@inheritdoc}
   */
  public function render(array $assets): array {
    $elements = $this->coreCssCollectionRenderer->render($assets);

    // Only alter the query string for any custom theme style.
    array_walk($elements, static function (&$item, $index) use ($assets) {
      if (strpos($item['#attributes']['href'], CustomTheme::CUSTOM_THEME_ID_PREFIX) !== FALSE) {
        $query_string_separator = (strpos($assets[$index]['data'], '?') !== FALSE) ? '&' : '?';
        list($path) = explode($query_string_separator, $item['#attributes']['href']);
        // TODO: Use the custom state here.
        $new_query_string = base_convert(REQUEST_TIME + 1, 10, 36);
        $item['#attributes']['href'] = "$path$query_string_separator$new_query_string";
      }
    });

    return $elements;
  }

}
