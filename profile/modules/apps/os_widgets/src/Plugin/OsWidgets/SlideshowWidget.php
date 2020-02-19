<?php

namespace Drupal\os_widgets\Plugin\OsWidgets;

use Drupal\Component\Utility\Html;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\group\Access\GroupAccessResult;
use Drupal\os_widgets\OsWidgetsBase;
use Drupal\os_widgets\OsWidgetsInterface;
use Drupal\vsite\Plugin\VsiteContextManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SlideshowWidget.
 *
 * @OsWidget(
 *   id = "slideshow_widget",
 *   title = @Translation("Slideshow")
 * )
 */
class SlideshowWidget extends OsWidgetsBase implements OsWidgetsInterface {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Vsite context manager.
   *
   * @var \Drupal\vsite\Plugin\VsiteContextManagerInterface
   */
  protected $vsiteContextManager;

  /**
   * {@inheritdoc}
   */
  public function __construct($configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, Connection $connection, AccountInterface $current_user, VsiteContextManagerInterface $vsite_context_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $connection);
    $this->currentUser = $current_user;
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
      $container->get('current_user'),
      $container->get('vsite.context_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildBlock(&$build, $block_content) {
    $slideshow_layout = $block_content->get('field_slideshow_layout')->getValue();
    if ($slideshow_layout[0]['value'] == '3_1_overlay') {
      $build['field_slideshow']['#build']['settings']['view_mode'] = 'slideshow_wide';
      if (!empty($build['field_slideshow']['#build']['items'])) {
        foreach ($build['field_slideshow']['#build']['items'] as &$item) {
          $item['#view_mode'] = 'slideshow_wide';
        }
      }
    }
    $build['add_slideshow_button'] = [
      '#type' => 'link',
      '#prefix' => '<p>',
      '#suffix' => '</p>',
      '#title' => $this->t('Add slide'),
      '#url' => Url::fromRoute('os_widgets.add_slideshow', [
        'block_content' => $block_content->id(),
      ]),
      '#attributes' => [
        'class' => [
          'use-ajax',
          'btn',
          'btn-success',
        ],
        'data-dialog-type' => 'modal',
      ],
      '#access' => FALSE,
    ];
    if ($group = $this->vsiteContextManager->getActiveVsite()) {
      $build['add_slideshow_button']['#access'] = GroupAccessResult::allowedIfHasGroupPermission($group, $this->currentUser, 'manage vsite content');
    }

    $build['#attributes']['class'][] = Html::cleanCssIdentifier('slideshow-layout-' . $slideshow_layout[0]['value']);
  }

}
