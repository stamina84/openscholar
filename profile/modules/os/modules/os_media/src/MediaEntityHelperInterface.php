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

  /**
   * Returns the type of embed.
   *
   * @param string $embed
   *   Embedded code.
   *
   * @return string|null
   *   Type of media embed.
   */
  public function getEmbedType($embed) : ?string;

  /**
   * Get height and width for the content.
   *
   * @param string $html
   *   Actual embed html.
   * @param array $max
   *   Max height/width settings.
   *
   * @return array
   *   Optimal height/width settings.
   */
  public function getDimensions($html, array $max) : array;

  /**
   * Fetches Embedly resource.
   *
   * @param string $url
   *   Resource url to fetch.
   *
   * @return mixed
   *   Data representation of embedly resource
   */
  public function fetchEmbedlyResource($url);

  /**
   * Returns Iframe data.
   *
   * @param string $value
   *   Field value.
   * @param array $max
   *   Max dimensions.
   * @param array $resource
   *   Resource data.
   * @param string $domain
   *   Domain to set.
   *
   * @return array
   *   Iframe data.
   */
  public function iFrameData($value, array $max, array $resource, $domain) : array;

  /**
   * Gets the thumbnail image URI for Media embeds.
   *
   * @return string
   *   URI of the thumbnail image or NULL if there is no specific icon.
   */
  public function getThumbnail() : ?string;

}
