<?php

namespace Drupal\os_software;

use Drupal\node\NodeInterface;

/**
 * Helper functions interface.
 */
interface OsSoftwareHelperInterface {

  /**
   * Prepare release node title.
   *
   * @param \Drupal\node\NodeInterface $release_node
   *   Release node object.
   *
   * @return string
   *   New release title.
   */
  public function prepareReleaseTitle(NodeInterface $release_node) : string;

  /**
   * Pre populate the node form array by query parameters.
   *
   * @param array $form
   *   Node Form array.
   */
  public function prePopulateSoftwareProjectField(array &$form) : void;

  /**
   * Unset other recommended releases when current one is marked recommended.
   *
   * @param int $projectId
   *   Software project id for which releases are set.
   */
  public function unsetRecommendedReleases($projectId): void;

}
