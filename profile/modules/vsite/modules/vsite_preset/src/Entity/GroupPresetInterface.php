<?php

namespace Drupal\vsite_preset\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Interface GroupPresetInterface.
 *
 * @package Drupal\vsite\Entity
 */
interface GroupPresetInterface extends ConfigEntityInterface {

  /**
   * Returns the storage object that contains the config for this preset.
   *
   * @return \Drupal\Core\Config\StorageInterface
   *   Config storage for this preset.
   */
  public function getPresetStorage() : StorageInterface;

  /**
   * Returns the file uri that should be imported when a Group is created.
   *
   * @return array
   *   File uris keyed by group types.
   */
  public function getCreationFilePaths() : array;

  /**
   * Returns apps which are to be enabled.
   */
  public function getEnabledApps(): array;

  /**
   * Returns apps that are to be made private.
   */
  public function getPrivateApps(): array;

}
