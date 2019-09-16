<?php

namespace Drupal\os_pager\Plugin\views\pager;

use Drupal\views\Plugin\views\pager\Full;

/**
 * The plugin to handle full pager.
 *
 * @ingroup views_pager_plugins
 *
 * @ViewsPager(
 *   id = "os_pager",
 *   title = @Translation("OS pager"),
 *   short_title = @Translation("Custom pager"),
 *   help = @Translation("Paged output format like: < 2 of 11 >"),
 *   theme = "views_mini_pager",
 *   register_theme = FALSE
 * )
 */
class OsPager extends Full {
  // Working same as Full pager,
  // except uses views_mini_pager theme for rendering.
  //
  // We need count query when rendering output. (ex. 2 of 11)
}
