<?php

namespace Drupal\cp_appearance;

use Drupal\Core\Asset\AssetCollectionRendererInterface;

/**
 * Makes sure custom theme styles are only refreshed in browser.
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
    // TODO: Alter the custom theme query string.
    return $this->coreCssCollectionRenderer->render($assets);
  }

}
