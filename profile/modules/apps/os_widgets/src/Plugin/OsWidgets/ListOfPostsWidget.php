<?php

namespace Drupal\os_widgets\Plugin\OsWidgets;

use Drupal\bibcite_entity\Entity\Reference;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\Entity\Node;
use Drupal\os_widgets\OsWidgetsBase;
use Drupal\os_widgets\OsWidgetsInterface;
use Drupal\vsite\Plugin\VsiteContextManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * Vsite context manager.
   *
   * @var \Drupal\vsite\Plugin\VsiteContextManagerInterface
   */
  private $vsiteContextManager;

  /**
   * {@inheritdoc}
   */
  public function __construct($configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, Connection $connection, VsiteContextManagerInterface $vsite_context_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $connection);
    $this->vsiteContextManager = $vsite_context_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('database'),
      $container->get('vsite.context_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildBlock(&$build, $block_content) {
    $contentType = $block_content->field_content_type->value;
    $displayStyle = $block_content->field_display_style->value;
    $sortedBy = $block_content->field_sorted_by->value;
    $numItems = $block_content->field_number_of_items_to_display->value;
    $showEvents = $block_content->field_show->value;
    $publicationValues = $block_content->get('field_publication_types')->getValue();
    foreach ($publicationValues as $type) {
      $publicationTypes[] = $type['value'];
    }
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
    $vsite = $this->vsiteContextManager->getActiveVsite();
    $pubTypes = $this->entityTypeManager->getStorage('bibcite_reference_type')->loadMultiple();
    $pubTypes = array_keys($pubTypes);
    $nodeTypes = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
    $nodeTypes = array_keys($nodeTypes);
    // Get nodes and publications for current vsite.
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

    // Extract entity ids from nodes and publications.
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

    // Filter nodes based on vsite nids and taxonomy terms.
    $nodesList = $nodesList ?? '';
    /** @var \Drupal\Core\Database\Query\SelectInterface $nodeQuery */
    $nodeQuery = $this->connection->select('node_field_data', 'nfd');
    $nodeQuery->fields('nfd', ['nid', 'created', 'title', 'type'])
      ->condition('nid', $nodesList, 'IN');
    if ($tids) {
      $nodeQuery->join('node__field_taxonomy_terms', 'nftm', 'nfd.nid = nftm.entity_id');
      $nodeQuery->condition('field_taxonomy_terms_target_id', $tids, 'IN');
    }

    // If events node is selected then check if only upcoming or past events
    // need to be shown.
    if ($contentType === 'events' && $showEvents !== 'all_events') {
      $eventQuery = clone $nodeQuery;
      $currentTime = new DrupalDateTime('now');
      $eventQuery->join('node__field_recurring_date', 'nfrd', 'nfd.nid = nfrd.entity_id');
      $eventQuery->addField('nfrd', 'field_recurring_date_value');
      $eventResults = $eventQuery->execute()->fetchAll();
      foreach ($eventResults as $eventNode) {
        $dateTime = new DrupalDateTime($eventNode->field_recurring_date_value);
        switch ($showEvents) {
          case 'upcoming_events':
            if ($currentTime >= $dateTime) {
              continue;
            }
            $to_keep[] = $eventNode->nid;
            break;

          case 'past_events':
            if ($currentTime >= $dateTime) {
              $to_keep[] = $eventNode->nid;
            }
        }
      }
      $nodeQuery->condition('nid', $to_keep, 'IN');
    }

    // Filter publications based on vsite nids and taxonomy terms.
    $pubList = $pubList ?? '';
    /** @var \Drupal\Core\Database\Query\SelectInterface $pubQuery */
    $pubQuery = $this->connection->select('bibcite_reference', 'pub');
    $pubQuery->fields('pub', ['id', 'created', 'title', 'type'])
      ->condition('id', $pubList, 'IN');
    if ($tids) {
      $pubQuery->join('bibcite_reference__field_taxonomy_terms', 'pubftm', 'pub.id = pubftm.entity_id');
      $pubQuery->condition('field_taxonomy_terms_target_id', $tids, 'IN');
    }

    // Check if only certain publication types are to be displayed.
    if ($contentType === 'publication') {
      $pubQuery->condition('type', $publicationTypes, 'IN');
    }

    // Union of two queries so that we can sort them as one. Join will not work
    // in our case.
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
      $pubQuery->addExpression('RAND()', 'random_field');
      $query->orderRandom();
    }

    $results = $query->execute()->fetchAll();

    // Prepare render array for the template based on type and display styles.
    $renderItems = [];
    foreach ($results as $item) {
      if (in_array($item->type, $nodeTypes)) {
        if ($displayStyle === 'title') {
          $renderItems[] = Node::load($item->nid)->toLink()->toRenderable();
        }
        else {
          $renderItems[] = $node_view_builder->view(Node::load($item->nid), $displayStyle);
        }
      }
      elseif (in_array($item->type, $pubTypes)) {
        if ($displayStyle === 'title') {
          $renderItems[] = Reference::load($item->nid)->toLink()->toRenderable();
        }
        else {
          $displayStyle = $displayStyle === 'default' ? 'citation' : $displayStyle;
          $renderItems[] = $publication_view_builder->view(Reference::load($item->nid), $displayStyle);
        }
      }
    }

    // Pager for the widget.
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
