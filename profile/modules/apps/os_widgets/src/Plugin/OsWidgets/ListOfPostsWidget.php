<?php

namespace Drupal\os_widgets\Plugin\OsWidgets;

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

    $node_view_builder = $this->entityTypeManager->getViewBuilder('node');
    // publication_view_builder =
    // $this->entityTypeManager->getViewBuilder('reference');.
    /** @var \Drupal\group\Entity\GroupInterface $vsite */
    $vsite = \Drupal::service('vsite.context_manager')->getActiveVsite();
    $types = \Drupal::entityTypeManager()->getStorage('node_type')->loadMultiple();
    $types = array_keys($types);
    foreach ($types as $type) {
      $content[] = $vsite->getContentEntities("group_node:$type");
      // $content[] = $vsite->getContent("group_node:$type");.
    }
    $content = array_filter($content);
    foreach ($content as $nodes) {
      foreach ($nodes as $node) {
        $list[] = $node->id();
      }
    }

    /** @var \Drupal\Core\Database\Query\SelectInterface $query */
    $query = $this->connection->select('node_field_data', 'nfd')
      ->fields('nfd', ['nid'])
      ->condition('nid', $list, 'IN')
      ->orderBy('created', 'DESC');
    $result = $query->execute()->fetchAllAssoc('nid');
    $result = array_keys($result);

    foreach ($result as $id) {
      $renderItems[] = $node_view_builder->view(Node::load($id), $displayStyle);
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
    // kint($build);
    // $build
    // $this->connection->select('')
  }

}
