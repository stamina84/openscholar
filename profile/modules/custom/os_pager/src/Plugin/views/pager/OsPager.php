<?php

namespace Drupal\os_pager\Plugin\views\pager;

use Drupal\views\Plugin\views\pager\Full;

/**
 * The plugin to handle os pager.
 *
 * @ingroup views_pager_plugins
 *
 * @ViewsPager(
 *   id = "os_pager",
 *   title = @Translation("OS pager"),
 *   short_title = @Translation("OS pager"),
 *   help = @Translation("Paged output format like: < 2 of 11 >"),
 *   theme = "os_views_pager",
 *   register_theme = FALSE
 * )
 */
class OsPager extends Full {
  // Working same as Full pager,
  // except uses os_views_pager theme for rendering.
  //
  // We need count query when rendering output. (ex. 2 of 11)
}
