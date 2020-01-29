<?php

namespace Drupal\Tests\os_search\Traits;

use Drupal\group\Entity\Group;

/**
 * Search Test helpers.
 */
trait SearchTestTrait {

  /**
   * Count Earth docs.
   *
   * @var int
   */
  protected $earthDocsCount = 0;

  /**
   * Count Venus docs.
   *
   * @var int
   */
  protected $venusDocsCount = 0;

  /**
   * Count Jupiter docs.
   *
   * @var int
   */
  protected $jupiterDocsCount = 0;

  /**
   * Pause the process for short periods between calling.
   *
   * @param int $seconds
   *   Pause for seconds.
   */
  protected function waitForSeconds($seconds = 10) {
    \sleep($seconds);
  }

  /**
   * Helper function to generate grouped content.
   *
   * @param \Drupal\group\Entity\Group $group
   *   Group Entity.
   * @param int $each_type_count
   *   Number of entities to be created for each type.
   * @param string $differentiator
   *   To isolate content from others in way (helps asserting correct texts).
   */
  protected function generateContent(Group $group, $each_type_count = 5, $differentiator = 'searchtest') {
    $group_id = $group->id();

    // Reference title always appeards in ucwords format.
    $ref_differentiator = ucwords($differentiator);

    for ($i = 0; $i < $each_type_count; $i++) {
      $j = $i + 1;
      $news = $this->createNode([
        'type' => 'news',
        'title' => "Earth news {$differentiator} Group{$group_id} {$j}",
      ]);

      $group->addContent($news, 'group_node:news');
      $this->markEntityForCleanup($news);
      if ($news) {
        $this->earthDocsCount++;
      }

      $blog = $this->createNode([
        'type' => 'blog',
        'title' => "Venus blog {$differentiator} Group{$group_id} {$j}",
      ]);

      $group->addContent($blog, 'group_node:blog');
      $this->markEntityForCleanup($blog);
      if ($blog) {
        $this->venusDocsCount++;
      }

      $reference = $this->createReference([
        'html_title' => "Jupiter Reference {$ref_differentiator} Group{$group_id} {$j}",
      ]);

      $group->addContent($reference, 'group_entity:bibcite_reference');

      if ($reference) {
        $this->jupiterDocsCount++;
      }
    }
  }

  /**
   * Checks if elastic server started giving results.
   *
   * @param string $differentiator
   *   To isolate content from others in way (helps asserting correct texts).
   */
  protected function getIndexQueryStatus($differentiator = 'searchtest') {
    $query = $this->index->query();
    $query->keys($differentiator);
    $count = $query->execute()->getResultCount();

    return (int) $count;
  }

}
