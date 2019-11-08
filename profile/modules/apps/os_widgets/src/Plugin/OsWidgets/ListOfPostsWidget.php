<?php

namespace Drupal\os_widgets\Plugin\OsWidgets;

use Drupal\bibcite_entity\Entity\Reference;
use Drupal\node\Entity\Node;
use Drupal\os_widgets\OsWidgetsBase;
use Drupal\os_widgets\OsWidgetsInterface;

/**
 * Class FeaturedPostsWidget.
 *
 * @OsWidget(
 *   id = "list_of_posts_widget",
 *   title = @Translation("List Of Posts")
 * )
 */
class ListOfPostsWidget extends OsWidgetsBase implements OsWidgetsInterface {

  /**
   * {@inheritdoc}
   */
  public function buildBlock(&$build, $block_content) {
    $contentType = $block_content->field_content_type->value;
    $displayStyle = $block_content->field_display_style->value;
    $sortedBy = $block_content->field_sorted_by->value;
    $numItems = $block_content->field_number_of_items_to_display->value;
    $terms = $block_content->get('field_filter_by_vocabulary')->getValue();
    $tids = [];
    foreach ($terms as $tid) {
      $tids[] = $tid['target_id'];
    }
    $nodes = [];
    $publications = [];

    $node_view_builder = $this->entityTypeManager->getViewBuilder('node');
    $publication_view_builder = $this->entityTypeManager->getViewBuilder('bibcite_reference');
    /** @var \Drupal\group\Entity\GroupInterface $vsite */
    $vsite = \Drupal::service('vsite.context_manager')->getActiveVsite();
    $pubTypes = \Drupal::entityTypeManager()->getStorage('bibcite_reference_type')->loadMultiple();
    $pubTypes = array_keys($pubTypes);
    $nodeTypes = \Drupal::entityTypeManager()->getStorage('node_type')->loadMultiple();
    $nodeTypes = array_keys($nodeTypes);
    if ($contentType === 'all') {
      foreach ($nodeTypes as $type) {
        $nodes[] = $vsite->getContentEntities("group_node:$type");
      }
      $publications[] = $vsite->getContentEntities("group_entity:bibcite_reference");
    }
    elseif ($contentType === 'publication') {
      $publications[] = $vsite->getContentEntities("group_entity:bibcite_reference");
    }
    else {
      $nodes[] = $vsite->getContentEntities("group_node:$contentType");
    }
    $nodes = array_filter($nodes);
    foreach ($nodes as $nodeArr) {
      foreach ($nodeArr as $node) {
        $nodesList[] = $node->id();
      }
    }
    $publications = array_filter($publications);
    foreach ($publications as $pubArr) {
      foreach ($pubArr as $pub) {
        $pubList[] = $pub->id();
      }
    }

    /** @var \Drupal\Core\Database\Query\SelectInterface $nodeQuery */
    $nodeQuery = $this->connection->select('node_field_data', 'nfd');
    $nodeQuery->fields('nfd', ['nid', 'created', 'title', 'type'])
      ->condition('nid', $nodesList, 'IN');
    $nodeQuery->join('node__field_taxonomy_terms', 'nftm', 'nfd.nid = nftm.entity_id');
    $nodeQuery->condition('field_taxonomy_terms_target_id', $tids, 'IN');

    /** @var \Drupal\Core\Database\Query\SelectInterface $pubQuery */
    $pubQuery = $this->connection->select('bibcite_reference', 'pub');
    $pubQuery->fields('pub', ['id', 'created', 'title', 'type'])
      ->condition('id', $pubList, 'IN');

    $query = $nodeQuery->union($pubQuery, 'UNION ALL');

    if ($sortedBy === 'sort_newest') {
      $query->orderBy('created', 'DESC');
    }
    elseif ($sortedBy === 'sort_oldest') {
      $query->orderBy('created', 'ASC');
    }
    elseif ($sortedBy === 'sort_alpha') {
      $query->orderBy('title', 'ASC');
    }
    elseif ($sortedBy === 'sort_random') {
      $query->orderRandom();
    }

    $results = $query->execute()->fetchAll();

    foreach ($results as $item) {
      if (in_array($item->type, $nodeTypes)) {
        $renderItems[] = $node_view_builder->view(Node::load($item->nid), $displayStyle);
      }
      elseif (in_array($item->type, $pubTypes)) {
        $renderItems[] = $publication_view_builder->view(Reference::load($item->nid), 'citation');
      }
    }

    $total_count = count($renderItems);

    $page = pager_find_page();
    $offset = $numItems * $page;
    $renderItems = array_slice($renderItems, $offset, $numItems);
    // Now that we have the total number of results, initialize the pager.
    pager_default_initialize($total_count, $numItems);
    $build['rendered_posts']['#theme'] = 'os_widgets_list_of_posts';
    $build['rendered_posts']['#posts'] = $renderItems;
    $build['rendered_posts']['#pager'] = [
      '#type' => 'pager',
    ];
  }

}
