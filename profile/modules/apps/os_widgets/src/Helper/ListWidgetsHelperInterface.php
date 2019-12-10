<?php

namespace Drupal\os_widgets\Helper;

use Drupal\media\Entity\Media;

/**
 * ListOfPosts Widget HelperInterface.
 */
interface ListWidgetsHelperInterface {

  /**
   * Get nodes and publication data for LOP widget rendering.
   *
   * @param array $fieldData
   *   Field Data.
   * @param array|null $nodesList
   *   List of nids to load.
   * @param array|null $pubList
   *   List of ids to load.
   * @param array|null $tids
   *   List of Term ids.
   *
   * @return array
   *   Structured data required for LOP rendering.
   */
  public function getLopResults(array $fieldData, array $nodesList = NULL, array $pubList = NULL, array $tids = NULL) : array;

  /**
   * Get media entity data for List of files widget.
   *
   * @param array $mids
   *   List of mids for the vsite.
   * @param string $sortedBy
   *   Sort by method.
   *
   * @return mixed
   *   Structured data required for LOF rendering.
   */
  public function getLofResults(array $mids, $sortedBy) : array;

  /**
   * Get the corresponding media icon.
   *
   * @param \Drupal\media\Entity\Media $media
   *   Media entity.
   * @param array $mapping
   *   Mapping of media fields.
   *
   * @return string
   *   Icon name.
   */
  public function getMediaIcon(Media $media, array $mapping) : string;

  /**
   * Adds a mini pager to the widget if needed.
   *
   * @param array $build
   *   The widget build array.
   * @param array $pager
   *   Pager details.
   * @param array $blockData
   *   Block data.
   */
  public function addWidgetMiniPager(array &$build, array $pager, array $blockData): void;

}
