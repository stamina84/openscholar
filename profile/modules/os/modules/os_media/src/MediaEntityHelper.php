<?php

namespace Drupal\os_media;

/**
 * Class MediaEntityHelper.
 *
 * @package Drupal\os_media
 */
final class MediaEntityHelper implements MediaEntityHelperInterface {

  /**
   * File fields to be used.
   */
  const FILE_FIELDS = [
    'filename',
  ];

  /**
   * Field mappings for media bundles.
   */
  const FIELD_MAPPINGS = [
    'image' => 'field_media_image',
    'document' => 'field_media_file',
    'video' => 'field_media_video_file',
  ];

  /**
   * Allowed media types for Media browser.
   */
  const ALLOWED_TYPES = [
    'Image' => 'image',
    'Document' => 'document',
    'Video' => 'video',
    'HTML' => 'html',
    'Executable' => 'executable',
    'Audio' => 'audio',
    'Icon' => 'icon',
  ];

  /**
   * {@inheritdoc}
   */
  public function getField(string $bundle) : string {
    return self::FIELD_MAPPINGS[$bundle];
  }

}
