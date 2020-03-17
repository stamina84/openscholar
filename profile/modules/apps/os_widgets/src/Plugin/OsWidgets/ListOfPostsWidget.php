<?php

namespace Drupal\os_widgets\Plugin\OsWidgets;

use Drupal\bibcite_entity\Entity\Reference;
use Drupal\Component\Utility\Html;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\group\Plugin\GroupContentEnablerManager;
use Drupal\node\Entity\Node;
use Drupal\os_app_access\AppLoader;
use Drupal\os_widgets\Helper\ListWidgetsHelper;
use Drupal\os_widgets\OsWidgetsBase;
use Drupal\os_widgets\OsWidgetsInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\vsite\Plugin\VsiteContextManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\group\Entity\GroupInterface;

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
   * LoP helper service.
   *
   * @var \Drupal\os_widgets\Helper\ListWidgetsHelper
   */
  protected $lopHelper;

  /**
   * Plugin manager.
   *
   * @var \Drupal\group\Plugin\GroupContentEnablerManager
   */
  protected $contentEnablerPluginManager;

  /**
   * App Loader service.
   *
   * @var \Drupal\os_app_access\AppLoader
   */
  protected $appLoader;

  /**
   * Current User.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public function __construct($configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, Connection $connection, VsiteContextManagerInterface $vsite_context_manager, ListWidgetsHelper $lop_helper, GroupContentEnablerManager $plugin_manager, AppLoader $app_loader, AccountProxy $account) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $connection);
    $this->vsiteContextManager = $vsite_context_manager;
    $this->lopHelper = $lop_helper;
    $this->contentEnablerPluginManager = $plugin_manager;
    $this->appLoader = $app_loader;
    $this->currentUser = $account;
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
      $container->get('vsite.context_manager'),
      $container->get('os_widgets.list_widgets_helper'),
      $container->get('plugin.manager.group_content_enabler'),
      $container->get('os_app_access.app_loader'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildBlock(&$build, $block_content) {

    $fieldData['contentType'] = $block_content->field_content_type->value;
    $displayStyle = $block_content->field_display_style->value;
    $fieldData['sortedBy'] = $block_content->field_sorted_by->value;
    $pager['numItems'] = $block_content->field_number_of_items_to_display->value;
    $fieldData['showEvents'] = $block_content->field_show->value;
    $moreLinkStatus = $block_content->field_show_more_link->value;
    $moreLink = $moreLinkStatus ? $block_content->get('field_url_for_the_more_link')->view(['label' => 'hidden']) : '';
    $showPager = $block_content->field_show_pager->value;
    $fieldData['eventExpireAppear'] = $block_content->field_events_should_expire->value;

    $publicationValues = $block_content->get('field_publication_types')->getValue();
    foreach ($publicationValues as $type) {
      $fieldData['publicationTypes'][] = $type['value'];
    }
    $terms = $block_content->get('field_filter_by_vocabulary')->getValue();
    $tids = [];
    foreach ($terms as $tid) {
      if ($term = Term::load($tid['target_id'])) {
        $vid = $term->bundle();
        $tids[$vid][] = $tid['target_id'];
      }
    }

    $nodes = [];
    $publications = [];
    $nodesList = NULL;
    $pubList = NULL;

    $node_view_builder = $this->entityTypeManager->getViewBuilder('node');
    $publication_view_builder = $this->entityTypeManager->getViewBuilder('bibcite_reference');
    /** @var \Drupal\group\Entity\GroupInterface $vsite */
    $vsite = $this->vsiteContextManager->getActiveVsite();
    $pubTypes = $this->entityTypeManager->getStorage('bibcite_reference_type')->loadMultiple();
    $pubTypes = array_keys($pubTypes);

    $apps = $this->appLoader->getAppsForUser($this->currentUser);
    foreach ($apps as $app) {
      if ($app['entityType'] === 'media' || $app['entityType'] === 'bibcite_reference') {
        continue;
      }
      $nodeTypes[] = $app['bundle'][0];
    }

    // Get nodes and publications for current vsite.
    if ($fieldData['contentType'] === 'all') {
      foreach ($nodeTypes as $type) {
        // Safe to check for any test modules or newly created content types
        // which do not have a group node plugin.
        if (!$this->contentEnablerPluginManager->hasDefinition("group_node:$type")) {
          continue;
        }
        $nodes[] = $vsite->getContentEntities("group_node:$type");
      }
      $publications[] = $vsite->getContentEntities("group_entity:bibcite_reference");
    }
    elseif ($fieldData['contentType'] === 'publications') {
      $publications[] = $vsite->getContentEntities("group_entity:bibcite_reference");
    }
    else {
      $contentType = $fieldData['contentType'];
      if ($this->contentEnablerPluginManager->hasDefinition("group_node:$contentType")) {
        $nodes[] = $vsite->getContentEntities("group_node:$contentType");
      }
    }

    // Flatten the arrays and extract entity ids from nodes and publications.
    $nodes = array_filter($nodes);
    $nodes_flattened = $nodes ? array_merge(...$nodes) : [];
    foreach ($nodes_flattened as $node) {
      $nodesList[] = $node->id();
    }
    $publications = array_filter($publications);
    $pub_flattened = $publications ? array_merge(...$publications) : [];
    foreach ($pub_flattened as $pub) {
      $pubList[] = $pub->id();
    }

    // Get the results which we need to load finally.
    $results = $this->lopHelper->getLopResults($fieldData, $nodesList, $pubList, $tids);

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

    $blockData['block_attribute_id'] = Html::getUniqueId('list-of-posts');
    $blockData['moreLinkId'] = Html::getUniqueId('node-readmore');
    $blockData['block_id'] = $block_content->id();

    $pager['total_count'] = count($renderItems);
    $pager['page'] = pager_find_page();
    $pager['offset'] = $pager['numItems'] * $pager['page'];
    $renderItems = array_slice($renderItems, $pager['offset'], $pager['numItems']);

    // Final build array that will be returned.
    $build['render_content'] = [
      '#theme' => 'os_widgets_list_of_posts',
      '#posts' => $renderItems,
      '#more_link' => $moreLink,
      '#attributes' => [
        'id' => $blockData['block_attribute_id'],
        'more_link_id' => $blockData['moreLinkId'],
      ],
    ];

    if ($showPager && $renderItems) {
      $this->lopHelper->addWidgetMiniPager($build, $pager, $blockData);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createWidget(array $data, $bundle, GroupInterface $group): void {
    $storage = $this->entityTypeManager->getStorage('block_content');
    foreach ($data as $row) {
      $block = $storage->create([
        'type' => $bundle,
        'info' => $row['Info'],
        'field_widget_title' => $row['Title'],
        'field_display_style' => $row['Display style'],
        'field_sorted_by' => $row['Sort by'],
        'field_content_type' => $row['Content type'],
      ]);

      if ($row['Content type'] == 'events') {
        $block->set('field_show', $row['Show']);
        $block->set('field_events_should_expire', $row['Expire']);
      }

      if ($row['Content type'] == 'publications') {
        $block->set('field_publication_types', $row['Publication type']);
      }

      $block->save();
      $group->addContent($block, "group_entity:block_content");
      $block_uuid = $block->uuid();
      $this->saveWidgetLayout($row, $block_uuid);
    }
  }

}
