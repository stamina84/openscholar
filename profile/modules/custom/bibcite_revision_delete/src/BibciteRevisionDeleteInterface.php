<?php

namespace Drupal\bibcite_revision_delete;

/**
 * Interface BibciteRevisionDeleteInterface.
 *
 * @package Drupal\bibcite_revision_delete
 */
interface BibciteRevisionDeleteInterface {

  /**
   * Return the list of candidate bibcites for bibcite revision delete.
   *
   * @return array
   *   Array of ids.
   */
  public function getCandidatesBibcites(): array;

  /**
   * Return the list of candidate revisions to be deleted.
   *
   * @return array
   *   Array of revision_ids.
   */
  public function getCandidatesRevisions(): array;

  /**
   * Return a number of candidate revisions to be deleted.
   *
   * @param string $number
   *   The number of revisions to return.
   *
   * @return array
   *   Array of vids.
   */
  public function getCandidatesRevisionsByNumber($number): array;

}
