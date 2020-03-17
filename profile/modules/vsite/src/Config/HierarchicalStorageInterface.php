<?php

namespace Drupal\vsite\Config;

use Drupal\Core\Config\StorageInterface;

/**
 * Interface class for the HierarchicalStorage functionality.
 *
 * This allows ConfigStorage objects to be stacked from most important to least,
 *   and allows a ConfigStorage to inherit config objects
 *   it hasn't defined itself.
 */
interface HierarchicalStorageInterface extends StorageInterface {

  /**
   * Adds a storage to stack with the given weight.
   *
   * @param \Drupal\Core\Config\StorageInterface $storage
   *   The storage being added to the stack.
   * @param int $weight
   *   The weight of the storage. Higher weights are read from first.
   */
  public function addStorage(StorageInterface $storage, $weight);

  /**
   * List all results from a certain level.
   *
   * @param string $prefix
   *   Config name prefix to search for.
   * @param int $level
   *   The level of storage we want to pull from.
   *
   * @return string[]
   *   All matching config names from the given storage level.
   */
  public function listAllFromLevel($prefix = '', $level = HierarchicalStorage::GLOBAL_STORAGE);

  /**
   * Save a value to a specific level.
   *
   * @param string $name
   *   Name of config item.
   * @param mixed $value
   *   Value of config item.
   * @param int $level
   *   The level being being saved to.
   */
  public function saveTolevel($name, $value, $level);

  /**
   * Override the level that writes should occur at.
   *
   * @param int $level
   *   Level to write to.
   */
  public function overrideWriteLevel($level);

  /**
   * Clear any write level overrides.
   */
  public function clearWriteOverride();

  /**
   * Reads a value from a specific level.
   *
   * @param string $name
   *   Name of config item.
   * @param int $level
   *   The level being being saved to.
   *
   * @return array|bool
   *   The configuration data stored for the configuration object name.
   *   If no configuration data exists for the given name, FALSE is returned.
   */
  public function readFromlevel($name, $level);

}
