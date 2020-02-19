<?php

declare(strict_types = 1);

namespace Drupal\cp_appearance;

use Drupal\Core\Asset\AssetCollectionRendererInterface;
use Drupal\Core\State\StateInterface;
use Drupal\cp_appearance\Entity\CustomTheme;
use Drupal\vsite\Plugin\VsiteContextManager;
use Drupal\vsite\Plugin\VsiteContextManagerInterface;

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
   * Drupal state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Vsite context manager.
   *
   * @var \Drupal\vsite\Plugin\VsiteContextManagerInterface
   */
  protected $vsiteContextManager;

  /**
   * Creates a new JsCollectionRenderer object.
   *
   * @param \Drupal\Core\Asset\AssetCollectionRendererInterface $js_collection_renderer
   *   Core's js collection renderer.
   * @param \Drupal\Core\State\StateInterface $state
   *   Drupal state.
   * @param \Drupal\vsite\Plugin\VsiteContextManagerInterface $vsite_context_manager
   *   Vsite context manager.
   */
  public function __construct(AssetCollectionRendererInterface $js_collection_renderer, StateInterface $state, VsiteContextManagerInterface $vsite_context_manager) {
    $this->coreJsCollectionRenderer = $js_collection_renderer;
    $this->state = $state;
    $this->vsiteContextManager = $vsite_context_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function render(array $assets): array {
    $elements = $this->coreJsCollectionRenderer->render($assets);
    /** @var \Drupal\group\Entity\GroupInterface|null $active_vsite */
    $active_vsite = $this->vsiteContextManager->getActiveVsite();

    if (!$active_vsite) {
      return $elements;
    }

    // Only alter the query string for any custom theme script.
    array_walk($elements, function (&$item) use ($active_vsite) {
      if (isset($item['#attributes']['src']) &&
        strpos($item['#attributes']['src'], CustomTheme::CUSTOM_THEME_ID_PREFIX) !== FALSE) {
        list($path) = explode('?', $item['#attributes']['src']);

        $new_query_string = $this->state->get(VsiteContextManager::VSITE_CSS_JS_QUERY_STRING_STATE_KEY_PREFIX . $active_vsite->id(), 0);

        $item['#attributes']['src'] = "$path?$new_query_string";
      }
    });

    return $elements;
  }

}
