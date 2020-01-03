<?php

namespace Drupal\bibcite_revision_delete;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Database\Connection;

/**
 * Class BibciteRevisionDelete.
 *
 * @package Drupal\bibcite_reference_revision_delete
 */
class BibciteRevisionDelete implements BibciteRevisionDeleteInterface {

  use StringTranslationTrait;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The configuration file name.
   *
   * @var string
   */
  protected $configurationFileName;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(ConfigFactoryInterface $config_factory, Connection $connection) {
    $this->configurationFileName = 'bibcite_revision_delete.settings';
    $this->configFactory = $config_factory;
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public function getCandidatesRevisions(): array {
    $minimum_revisions_to_keep = $this->configFactory->get($this->configurationFileName)->get('bibcite_revision_minimum_revisions_to_keep');

    // Getting the candidate bibcites.
    $candidate_bibcites = $this->getCandidatesBibcites();

    $candidate_revisions = [];

    foreach ($candidate_bibcites as $candidate_bibcite) {
      $sub_query = $this->connection->select('bibcite_reference', 'br');
      $sub_query->join('bibcite_reference_revision', 'r', 'r.id = br.id');
      $sub_query->fields('r', ['revision_id', 'revision_created']);
      $sub_query->condition('br.id', $candidate_bibcite);
      $sub_query->where('br.revision_id <> r.revision_id');
      $sub_query->groupBy('br.id');
      $sub_query->groupBy('r.revision_id');
      $sub_query->groupBy('r.revision_created');
      $sub_query->orderBy('revision_created', 'DESC');
      // We need to reduce in 1 because we don't want to count the default vid.
      // We excluded the default revision in the where call.
      $sub_query->range($minimum_revisions_to_keep - 1, PHP_INT_MAX);

      $query = $this->connection->select($sub_query, 't');
      $query->fields('t', ['revision_id']);

      $candidate_revisions = array_merge($candidate_revisions, $query->execute()->fetchCol());
    }
    return $candidate_revisions;
  }

  /**
   * {@inheritdoc}
   */
  public function getCandidatesBibcites(): array {
    $minimum_revisions_to_keep = $this->configFactory->get($this->configurationFileName)->get('bibcite_revision_minimum_revisions_to_keep');

    $query = $this->connection->select('bibcite_reference', 'br');
    $query->join('bibcite_reference_revision', 'r', 'r.id = br.id');
    $query->fields('br', ['id']);
    $query->addExpression('COUNT(*)', 'total');
    $query->groupBy('br.id');
    $query->having('COUNT(*) > ' . $minimum_revisions_to_keep);

    // Allow other modules to alter candidates query.
    $query->addTag('bibcite_reference_revision_delete_candidates');

    return $query->execute()->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function getCandidatesRevisionsByNumber($number): array {
    $revisions = $this->getCandidatesRevisions();
    // Getting the number of revision we will delete.
    if ($number < count($revisions)) {
      $revisions = array_slice($revisions, 0, $number, TRUE);
    }
    return $revisions;
  }

}
