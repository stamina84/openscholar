<?php

namespace Drupal\cp_import\Helper;

use Drupal\media\Entity\Media;

/**
 * CpImportHelperInterface.
 */
interface CpImportHelperInterface {

  /**
   * Get the media to be attached to the node.
   *
   * @param string $media_val
   *   The media value entered in the csv.
   * @param string $contentType
   *   Content type.
   *
   * @return \Drupal\media\Entity\Media|null
   *   Media entity/Null when not able to fetch/download media.
   */
  public function getMedia(string $media_val, string $contentType) : ?Media;

  /**
   * Adds the newly imported content to Vsite.
   *
   * @param string $id
   *   Entity to be added to the vsite.
   * @param string $plugin_id
   *   Plugin id of the entity in context.
   */
  public function addContentToVsite(string $id, string $plugin_id): void;

  /**
   * Helper method to convert csv to array.
   *
   * @param string $filename
   *   File uri.
   * @param string $encoding
   *   Encoding of the file.
   *
   * @return array|bool
   *   Data as an array.
   */
  public function csvToArray($filename, $encoding);

}
