<?php

namespace Drupal\vsite\Config;

use Drupal\Core\Config\StorageInterface;

/**
 * Allows multiple StorageInterfaces to be stacked.
 */
class HierarchicalStorage implements HierarchicalStorageInterface {

  const GLOBAL_STORAGE = INF;

  /**
   * The list of StorageInterfaces.
   *
   * Each value is in the format
   *  [
   *    StorageInterface storage,
   *    int weight
   *  ]
   *
   * @var array
   */
  protected $storages = [];

  /**
   * The level that writes should occur at.
   *
   * @var int
   */
  protected $overrideLevel;

  /**
   * Constructor.
   */
  public function __construct(StorageInterface $storage) {
    $this->storages[] = [
      'storage' => $storage,
      'weight' => self::GLOBAL_STORAGE,
    ];
  }

  /**
   * Add a storage to the stack.
   *
   * {@inheritdoc}
   */
  public function addStorage(StorageInterface $s, $weight) {
    $this->storages[] = [
      'storage' => $s,
      'weight' => $weight,
    ];

    usort($this->storages, function ($a, $b) {
      if ($a['weight'] == $b['weight']) {
        return 0;
      }
      return ($a['weight'] < $b['weight']) ? -1 : 1;
    });
  }

  /**
   * {@inheritdoc}
   */
  public function exists($name) {
    $output = FALSE;
    foreach ($this->storages as $s) {
      /** @var \Drupal\Core\Config\StorageInterface $store */
      $store = $s['storage'];
      $output |= $store->exists($name);
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function read($name) {
    foreach ($this->storages as $s) {
      /** @var \Drupal\Core\Config\StorageInterface $store */
      $store = $s['storage'];
      if ($store->exists($name)) {
        return $store->read($name);
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function readMultiple(array $names) {
    $output = [];
    foreach ($this->storages as $s) {
      /** @var \Drupal\Core\Config\StorageInterface $store */
      $store = $s['storage'];
      $output += $store->readMultiple($names);
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function write($name, array $data) {
    if (!is_null($this->overrideLevel)) {
      foreach ($this->storages as $st) {
        if ($st['weight'] == $this->overrideLevel) {
          /** @var \Drupal\Core\Config\StorageInterface $store */
          $store = $st['storage'];
          break;
        }
      }
    }
    else {
      $store = $this->storages[0]['storage'];
    }
    if ($store) {
      $store->write($name, $data);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function delete($name) {
    /** @var \Drupal\Core\Config\StorageInterface $store */
    $store = $this->storages[0]['storage'];
    $store->delete($name);
  }

  /**
   * {@inheritdoc}
   */
  public function rename($name, $new_name) {
    /** @var \Drupal\Core\Config\StorageInterface $store */
    $store = $this->storages[0]['storage'];
    $store->rename($name, $new_name);
  }

  /**
   * {@inheritdoc}
   */
  public function encode($data) {
    /** @var \Drupal\Core\Config\StorageInterface $store */
    $store = end($this->storages)['storage'];
    return $store->encode($data);
  }

  /**
   * {@inheritdoc}
   */
  public function decode($raw) {
    /** @var \Drupal\Core\Config\StorageInterface $store */
    $store = end($this->storages)['storage'];
    return $store->decode($raw);
  }

  /**
   * {@inheritdoc}
   */
  public function listAll($prefix = '') {
    $output = [];
    foreach ($this->storages as $s) {
      /** @var \Drupal\Core\Config\StorageInterface $store */
      $store = $s['storage'];
      $output = array_merge($output, $store->listAll($prefix));
    }

    return array_unique($output);
  }

  /**
   * {@inheritdoc}
   */
  public function listAllFromLevel($prefix = '', $level = self::GLOBAL_STORAGE) {
    foreach ($this->storages as $s) {
      if ($s['weight'] == $level) {
        /** @var \Drupal\Core\Config\StorageInterface $store */
        $store = $s['storage'];
        return $store->listAll($prefix);
      }
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll($prefix = '') {
    /** @var \Drupal\Core\Config\StorageInterface $store */
    $store = $this->storages[0]['storage'];
    $store->deleteAll($prefix);
  }

  /**
   * {@inheritdoc}
   */
  public function createCollection($collection) {
    /** @var \Drupal\Core\Config\StorageInterface $store */
    $store = end($this->storages)['storage'];
    return $store->createCollection($collection);
  }

  /**
   * {@inheritdoc}
   */
  public function getAllCollectionNames() {
    /** @var \Drupal\Core\Config\StorageInterface $store */
    $store = end($this->storages)['storage'];
    return $store->getAllCollectionNames();
  }

  /**
   * {@inheritdoc}
   */
  public function getCollectionName() {
    /** @var \Drupal\Core\Config\StorageInterface $store */
    $store = $this->storages[0]['storage'];
    return $store->getCollectionName();
  }

  /**
   * {@inheritdoc}
   */
  public function saveTolevel($name, $value, $level) {
    /** @var \Drupal\Core\Config\StorageInterface $st */
    foreach ($this->storages as $st) {
      if ($st['weight'] == $level) {
        $st['storage']->write($name, $value);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function readFromlevel($name, $level) {
    /** @var \Drupal\Core\Config\StorageInterface $st */
    foreach ($this->storages as $st) {
      if ($st['weight'] == $level && $st['storage']->exists($name)) {
        return $st['storage']->read($name);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function overrideWriteLevel($level) {
    $this->overrideLevel = $level;
  }

  /**
   * {@inheritdoc}
   */
  public function clearWriteOverride() {
    $this->overrideLevel = NULL;
  }

}
