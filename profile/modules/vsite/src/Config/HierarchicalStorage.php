<?php
/**
 * Allows Drupal to keep a list of multiple storages to search from.
 */

namespace Drupal\vsite\Config;


use Drupal\Core\Config\StorageInterface;

class HierarchicalStorage implements HierarchicalStorageInterface {

  const GLOBAL_STORAGE = INF;

  /** @var array  */
  protected $storages = [];

  public function __construct(StorageInterface $storage) {
    $this->storages[] = [
      'storage' => $storage,
      'weight' => self::GLOBAL_STORAGE
    ];
  }

  public function addStorage(StorageInterface $s, $weight) {
    $this->storages[] = [
      'storage' => $s,
      'weight' => $weight
    ];

    usort($this->storages, function ($a, $b) {
      if ($a['weight'] == $b['weight']) {
        return 0;
      }
      return ($a['weight'] < $b['weight']) ? -1 : 1;
    });
  }

  protected function iterate(callable $func) {
    foreach ($this->storages as $s) {
      /** @var StorageInterface $store */
      $store = $s['storage'];
      $output = $func($store);
      if (!is_null($output)) {
        return $output;
      }
    }
    return false;
  }

  /**
   * @inheritDoc
   */
  public function exists ($name) {
    return $this->iterate(function (StorageInterface $store) use ($name) {
      return $store->exists($name);
    });
  }

  /**
   * @inheritDoc
   */
  public function read ($name) {
    $output = $this->iterate(function (StorageInterface $store) use ($name) {
      $output = $store->read($name);
      if (!is_null($output)) {
        return $output;
      }
    });
    return $output;
  }

  /**
   * @inheritDoc
   */
  public function readMultiple (array $names) {
    $output = [];
    foreach ($this->storages as $s) {
      /** @var StorageInterface $store */
      $store = $s['storage'];
      $output += $store->readMultiple ($names);
    }

    return $output;
  }

  /**
   * @inheritDoc
   *
   * We always write to the bottom-most storage
   */
  public function write ($name, array $data) {
    /** @var StorageInterface $store */
    $store = $this->storages[0]['storage'];
    $store->write($name, $data);
  }

  /**
   * @inheritDoc
   */
  public function delete ($name) {
    /** @var StorageInterface $store */
    $store = $this->storages[0]['storage'];
    $store->delete($name);
  }

  /**
   * @inheritDoc
   */
  public function rename ($name, $new_name) {
    /** @var StorageInterface $store */
    $store = $this->storages[0]['storage'];
    $store->rename($name, $new_name);
  }

  /**
   * @inheritDoc
   */
  public function encode ($data) {
    /** @var StorageInterface $store */
    $store = end($this->storages)['storage'];
    return $store->encode($data);
  }

  /**
   * @inheritDoc
   */
  public function decode ($raw) {
    /** @var StorageInterface $store */
    $store = end($this->storages)['storage'];
    return $store->decode($raw);
  }

  /**
   * @inheritDoc
   */
  public function listAll ($prefix = '') {
    $output = [];
    foreach ($this->storages as $s) {
      /** @var StorageInterface $store */
      $store = $s['storage'];
      $output += $store->listAll($prefix);
    }

    return $output;
  }

  /**
   * @inheritDoc
   */
  public function deleteAll ($prefix = '') {
    /** @var StorageInterface $store */
    $store = $this->storages[0]['storage'];
    $store->deleteAll ($prefix);
  }

  /**
   * @inheritDoc
   */
  public function createCollection ($collection) {
    /** @var StorageInterface $store */
    $store = end($this->storages)['storage'];
    return $store->createCollection ($collection);
  }

  /**
   * @inheritDoc
   */
  public function getAllCollectionNames () {
    /** @var StorageInterface $store */
    $store = end($this->storages)['storage'];
    return $store->getAllCollectionNames ();
  }

  /**
   * @inheritDoc
   */
  public function getCollectionName () {
    /** @var StorageInterface $store */
    $store = $this->storages[0]['storage'];
    return $store->getCollectionName ();
  }
}