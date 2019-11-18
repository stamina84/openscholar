<?php

namespace Drupal\os_widgets\Helper;

/**
 * ListOfPosts Widget HelperInterface.
 */
interface ListOfPostsWidgetHelperInterface {

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
  public function getResults(array $fieldData, array $nodesList = NULL, array $pubList = NULL, array $tids = NULL) : array;

}
