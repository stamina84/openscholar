<?php

namespace Drupal\os_media;

/**
 * Helper for Media entity for media browser related operations.
 */
interface MediaEntityHelperInterface {

  /**
   * Handles field mappings for different bundles.
   *
   * @param string $bundle
   *   The bundle to return the field for.
   *
   * @return string
   *   The mapped field.
   */
  public function getField(string $bundle) : string;

}
