<?php

namespace Drupal\Tests\openscholar\Traits;

/**
 * Provides a trait for cleanup class.
 */
trait OsCleanupClassTestTrait {

  /**
   * Clean up given class properties.
   */
  public function cleanUpProperties($class) {
    $ref = new \ReflectionClass($class);
    $properties = $ref->getProperties();
    foreach ($properties as $property) {
      $property_name = $property->getName();
      if (!empty($this->{$property_name})) {
        $this->{$property_name} = NULL;
      }
    }
  }

}
