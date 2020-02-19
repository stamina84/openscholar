<?php

declare(strict_types = 1);

namespace Drupal\cp_appearance;

use Drupal\Core\Asset\AssetCollectionRendererInterface;
use Drupal\Core\State\StateInterface;
use Drupal\cp_appearance\Entity\CustomTheme;
use Drupal\vsite\Plugin\VsiteContextManager;
use Drupal\vsite\Plugin\VsiteContextManagerInterface;

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
   * Creates a new CssCollectionRenderer object.
   *
   * @param \Drupal\Core\Asset\AssetCollectionRendererInterface $css_collection_renderer
   *   Core's css collection renderer.
   * @param \Drupal\Core\State\StateInterface $state
   *   Drupal state.
   * @param \Drupal\vsite\Plugin\VsiteContextManagerInterface $vsite_context_manager
   *   Vsite context manager.
   */
  public function __construct(AssetCollectionRendererInterface $css_collection_renderer, StateInterface $state, VsiteContextManagerInterface $vsite_context_manager) {
    $this->coreCssCollectionRenderer = $css_collection_renderer;
    $this->state = $state;
    $this->vsiteContextManager = $vsite_context_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function render(array $assets): array {
    $elements = $this->coreCssCollectionRenderer->render($assets);
    /** @var \Drupal\group\Entity\GroupInterface|null $active_vsite */
    $active_vsite = $this->vsiteContextManager->getActiveVsite();

    if (!$active_vsite) {
      return $elements;
    }

    // Only alter the query string for any custom theme style.
    array_walk($elements, function (&$item, $index) use ($assets, $active_vsite) {
      if (strpos($item['#attributes']['href'], CustomTheme::CUSTOM_THEME_ID_PREFIX) !== FALSE) {
        $query_string_separator = (strpos($assets[$index]['data'], '?') !== FALSE) ? '&' : '?';
        list($path) = explode($query_string_separator, $item['#attributes']['href']);

        $new_query_string = $this->state->get(VsiteContextManager::VSITE_CSS_JS_QUERY_STRING_STATE_KEY_PREFIX . $active_vsite->id(), 0);

        $item['#attributes']['href'] = "$path$query_string_separator$new_query_string";
      }
    });

    return $elements;
  }

}
