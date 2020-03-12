<?php

namespace Drupal\vsite\Helper;

use Drupal\path_alias\PathAliasInterface;

/**
 * Contract for VsitePathAliasHelperInterface.
 */
interface VsitePathAliasHelperInterface {

  /**
   * Alter save.
   *
   * @param \Drupal\path_alias\PathAliasInterface $path_alias
   *   Processed path alias entity.
   */
  public function save(PathAliasInterface $path_alias): void;

}
