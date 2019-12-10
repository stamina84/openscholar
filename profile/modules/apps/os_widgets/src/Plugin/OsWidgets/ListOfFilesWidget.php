<?php

namespace Drupal\os_widgets\Plugin\OsWidgets;

use Drupal\Component\Utility\Html;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Url;
use Drupal\group\Plugin\GroupContentEnablerManager;
use Drupal\media\Entity\Media;
use Drupal\os_app_access\AppLoader;
use Drupal\os_media\MediaEntityHelperInterface;
use Drupal\os_widgets\Helper\ListWidgetsHelper;
use Drupal\os_widgets\OsWidgetsBase;
use Drupal\os_widgets\OsWidgetsInterface;
use Drupal\vsite\Plugin\VsiteContextManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ListOfFilesWidget.
 *
 * @OsWidget(
 *   id = "list_of_files_widget",
 *   title = @Translation("List Of Files")
 * )
 */
class ListOfFilesWidget extends OsWidgetsBase implements OsWidgetsInterface {

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
  protected $listWidgetsHelper;

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
   * Media Helper service.
   *
   * @var \Drupal\os_media\MediaEntityHelperInterface
   */
  protected $mediaHelper;

  /**
   * {@inheritdoc}
   */
  public function __construct($configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, Connection $connection, VsiteContextManagerInterface $vsite_context_manager, ListWidgetsHelper $lop_helper, GroupContentEnablerManager $plugin_manager, AppLoader $app_loader, AccountProxy $account, MediaEntityHelperInterface $media_helper) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $connection);
    $this->vsiteContextManager = $vsite_context_manager;
    $this->listWidgetsHelper = $lop_helper;
    $this->contentEnablerPluginManager = $plugin_manager;
    $this->appLoader = $app_loader;
    $this->currentUser = $account;
    $this->mediaHelper = $media_helper;
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
      $container->get('current_user'),
      $container->get('os_media.media_helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildBlock(&$build, $block_content) {

    $fieldData['filesType'] = $block_content->field_file_type->value;
    $displayStyle = $block_content->field_display_style_lof->value;
    $fieldData['sortedBy'] = $block_content->field_sorted_by_lof->value;
    $pager['numItems'] = $block_content->field_number_of_items_to_display->value;
    $layout = $block_content->field_layout->value;
    $columns = $block_content->field_columns->value;
    $moreLinkStatus = $block_content->field_show_more_link->value;
    $moreLink = $moreLinkStatus ? $block_content->get('field_url_for_the_more_link')->view(['label' => 'hidden']) : '';
    $showPager = $block_content->field_show_pager->value;

    $vsite = $this->vsiteContextManager->getActiveVsite();
    if (!$vsite) {
      return [];
    }
    // Get media for current vsite.
    $media = $vsite->getContentEntities('group_entity:media');

    $mids = [];
    foreach ($media as $item) {
      if ($fieldData['filesType'] === 'all') {
        $mids[] = $item->id();
      }
      elseif ($fieldData['filesType'] === $item->bundle()) {
        $mids[] = $item->id();
      }
    }
    if (empty($mids)) {
      return [];
    }

    $results = $this->listWidgetsHelper->getLofResults($mids, $fieldData['sortedBy']);

    // Prepare render array for the template based on type and display styles.
    $media_view_builder = $this->entityTypeManager->getViewBuilder('media');
    $renderItems = [];

    $common = ['title', 'link', 'link_icon'];

    foreach ($results as $item) {
      /** @var \Drupal\media\Entity\Media $media */
      $media = Media::load($item->mid);
      $bundle = $media->bundle();

      // Handle title,link with icon and link without icon display styles.
      if (in_array($displayStyle, $common)) {
        $renderItems[$item->mid]['item'] = $media->toLink()->toRenderable();
        if ($displayStyle === 'link_icon') {
          $renderItems[$item->mid]['icon_type'] = $this->listWidgetsHelper->getMediaIcon($media, $this->mediaHelper::FIELD_MAPPINGS);
        }
      }
      else {
        // Handle grid mode for Images and Embeds.
        if (($bundle === 'image' || $bundle === 'oembed')) {
          $style = $displayStyle;
          if ($layout === 'grid') {
            $style = $displayStyle === 'teaser' ? 'grid_teaser' : $displayStyle;
            $style = $style === 'thumbnail' ? 'grid_thumbnail' : $style;
          }
          $renderItems[$item->mid]['item'] = $media_view_builder->view(Media::load($item->mid), $style);
        }
        else {
          $renderItems[$item->mid]['item'] = $media_view_builder->view(Media::load($item->mid), $displayStyle);
        }
        if ($displayStyle === 'full' || $displayStyle === 'teaser') {
          if ($this->mediaHelper::FIELD_MAPPINGS[$bundle] === 'field_media_file' || $this->mediaHelper::FIELD_MAPPINGS[$bundle] === 'field_media_image') {
            $renderItems[$item->mid]['download'] = Link::fromTextAndUrl(t('Download'), Url::fromRoute('os_media_entity.download', ['media' => $item->mid], ['attributes' => ['class' => 'download-link']]));
          }
        }
      }
    }

    $blockData['block_attribute_id'] = Html::getUniqueId('list-of-files');
    $blockData['moreLinkId'] = Html::getUniqueId('node-readmore');
    $blockData['block_id'] = $block_content->id();

    $pager['total_count'] = count($renderItems);
    $pager['page'] = pager_find_page();
    $pager['offset'] = $pager['numItems'] * $pager['page'];
    $renderItems = array_slice($renderItems, $pager['offset'], $pager['numItems']);

    // Final build array that will be returned.
    $build['render_content'] = [
      '#theme' => 'os_widgets_list_of_files',
      '#files' => $renderItems,
      '#more_link' => $moreLink,
      '#attributes' => [
        'id' => $blockData['block_attribute_id'],
        'more_link_id' => $blockData['moreLinkId'],
        'class' => "$displayStyle-lof",
      ],
    ];

    if ($fieldData['filesType'] === 'image' || $fieldData['filesType'] === 'oembed') {
      if ($layout === 'grid' && $displayStyle !== 'title') {
        $build['render_content']['#grid'] = TRUE;
        $build['render_content']['#attributes']['class'] = ['lof-grid', "lof-grid-$columns"];
      }
      if ($layout === 'grid' && $displayStyle == 'thumbnail') {
        $build['render_content']['#grid'] = TRUE;
        $build['render_content']['#attributes']['class'] = [
          'thumbnail-lof',
          'lof-grid',
          "lof-grid-$columns",
        ];
      }
    }

    if ($displayStyle === 'link_icon') {
      $icon_path = drupal_get_path('module', 'os_widgets') . '/images/lof/icons';
      $build['render_content']['#icon_path'] = $icon_path;
      $build['render_content']['#link_with_icon'] = TRUE;
    }
    elseif ($displayStyle === 'title') {
      $build['render_content']['#only_title'] = TRUE;
    }

    if ($showPager && $renderItems) {
      $this->listWidgetsHelper->addWidgetMiniPager($build, $pager, $blockData);
    }
  }

}
