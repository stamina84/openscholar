<?php

namespace Drupal\os_widgets\Plugin\DisplayVariant;

use Drupal\block_place\Plugin\DisplayVariant\PlaceBlockPageVariant as OriginalVariant;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\os_widgets\Entity\LayoutContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * PageVariant to handle our custom layout management.
 */
class PlaceBlockPageVariant extends OriginalVariant {

  /**
   * Section Storage Manager.
   *
   * Might not be needed.
   *
   * @var \Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface
   */
  protected $sectionStorageManager;

  /**
   * Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->sectionStorageManager = $container->get('plugin.manager.layout_builder.section_storage');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * Build out the page.
   *
   * Context should be in path.
   */
  public function build() {
    $build = parent::build();
    $applicable = LayoutContext::getApplicable();

    $contexts = [];
    foreach ($applicable as $app) {
      $contexts[$app->id()] = $app->label();
    }

    foreach (Element::children($build) as $region) {
      $build[$region]['#attributes']['class'][] = 'block-place-region';
      $build[$region]['#attributes']['data-region'] = $region;
      unset($build[$region]['block_place_operations']);
      $build[$region]['placeholder'] = [
        '#type' => 'markup',
        '#markup' => '<div class="block-placeholder"></div>',
      ];
      foreach (Element::children($build[$region]) as $block) {
        if (isset($build[$region][$block]['#block'])) {
          /** @var \Drupal\block\BlockInterface $block_obj */
          $block_obj = $build[$region][$block]['#block'];
          $build[$region][$block] = [
            '#type' => 'inline_template',
            '#template' => '<div class="block" data-block-id="{{ id }}" tabindex="0"><h3 class="block-title">{{ title }}</h3>{{ content }}</div>',
            '#context' => [
              'id' => $block_obj->id(),
              'title' => $block_obj->label(),
              'content' => $build[$region][$block],
            ],
          ];
        }
        elseif (isset($build[$region][$block]['#lazy_builder'])) {
          $callable = $build[$region][$block]['#lazy_builder'][0];
          $args = $build[$region][$block]['#lazy_builder'][1];
          if (is_string($callable) && strpos($callable, '::') === FALSE) {
            /** @var \Drupal\Core\Controller\ControllerResolverInterface $controllerResolver */
            $controllerResolver = \Drupal::service('controller_resolver');
            $callable = $controllerResolver->getControllerFromDefinition($callable);
          }
          $new_elements = call_user_func_array($callable, $args);
          /** @var \Drupal\block\BlockInterface $block_obj */
          $block_obj = $new_elements['#block'];
          $build[$region][$block] = [
            '#type' => 'inline_template',
            '#template' => '<div class="block" data-block-id="{{ id }}" tabindex="0"><h3 class="block-title">{{ title }}</h3>{{ content }}</div>',
            '#context' => [
              'id' => $block_obj->id(),
              'title' => $block_obj->label(),
              'content' => $new_elements,
            ],
          ];
        }
      }
    }

    $context = \Drupal::request()->query->get('context');

    $build['footer_bottom']['context_selector'] = [
      '#type' => 'markup',
      '#prefix' => '<div id="block-place-context-selector-wrapper">',
      '#suffix' => '</div>',
      'selector' => [
        '#type' => 'select',
        '#default_value' => $context,
        '#options' => $contexts,
        '#title' => $this->t('Select Content Type'),
        '#label_attributes' => ['for' => ['block-place-context-selector']],
        '#attributes' => [
          'id' => 'block-place-context-selector',
        ],
      ],
      '#attached' => [
        'library' => [
          'os_widgets/layout',
        ],
        'drupalSettings' => [
          'layoutContexts' => $contexts,
        ],
      ],
    ];
    $build['footer_bottom']['widget_selector'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'block-place-widget-selector-wrapper',
      ],
      'markup' => $this->buildWidgetLibrary(),
      '#attached' => [
        'library' => [
          'os_media/mediaBrowserField',
        ],
      ],
    ];

    $build['footer_bottom']['actions'] = [
      '#type' => 'markup',
      '#prefix' => '<div id="block-place-actions-wrapper">',
      '#suffix' => '</div>',
      'save' => [
        '#type' => 'button',
        '#value' => $this->t('Save'),
      ],
      'reset' => [
        '#type' => 'button',
        '#value' => $this->t('Reset'),
      ],
    ];

    return $build;
  }

  /**
   * Builds the widget library section of the page.
   */
  private function buildWidgetLibrary() {

    /** @var \Drupal\block\BlockRepositoryInterface $blockRepository */
    $blockRepository = \Drupal::service('os_widgets.block.repository');
    $allBlocks = $blockRepository->getVisibleBlocksPerRegion();

    /** @var \Drupal\block\Entity\Block[] $widgets_not_yet_placed */
    $widgets_not_yet_placed = $allBlocks[0] ?? [];

    /** @var \Drupal\block_content\Entity\BlockContentType[] $block_types */
    $block_types = $this->entityTypeManager->getStorage('block_content_type')->loadMultiple();
    $factory_links = [];

    foreach ($block_types as $bt) {
      if ($bt->id() != 'basic') {
        $factory_links[$bt->id()] = [
          'title' => $bt->label(),
          'url' => Url::fromRoute('os_widgets.create_widget', ['block_content_type' => $bt->id()]),
          'attributes' => [
            'title' => $bt->label(),
            'class' => ['use-ajax'],
            'data-dialog-type' => 'modal',
            'data-dialog-options' => Json::encode([
              'width' => 1000,
              'autoOpen' => TRUE,
              'dialogClass' => 'widget-popup',
            ]),
          ],
        ];
      }
    }
    $output = [
      'factory' => [
        '#type' => 'button',
        '#title' => $this->t('Create New Widget'),
        '#attributes' => [
          'id' => 'create-new-widget-btn',
        ],
      ],
      'filter' => [
        '#type' => 'textfield',
        '#title' => $this->t('Filter Widgets by Title'),
        '#maxlength' => 60,
        '#size' => 60,
        '#label_attributes' => ['for' => ['filter-widgets']],
        '#attributes' => [
          'id' => [
            'filter-widgets',
          ],
        ],
      ],
      'filter_by_type' => [
        '#type' => 'select',
        '#title' => $this->t('Filter Widgets by Type'),
        '#label_attributes' => ['for' => ['filter-widgets-by-type']],
        '#options' => [
          'all' => $this->t('All'),
        ],
        '#attributes' => [
          'id' => [
            'filter-widgets-by-type',
          ],
        ],
      ],
      'existing-blocks' => [
        '#prefix' => '<div id="block-list">',
        '#suffix' => '</div>',
      ],
      'factories' => [
        '#type' => 'container',
        '#attributes' => [
          'id' => 'factory-wrapper',
        ],
        'title' => [
          '#markup' => '<h3>' . $this->t('Select Widget Type') . '</h3>',
        ],
        'close' => [
          '#markup' => '<div class="close" tabindex="0">X</div>',
        ],
        'links' => [
          '#theme' => 'links',
          '#links' => $factory_links,
        ],
      ],
    ];

    $block_storage = $this->entityTypeManager->getStorage('block_content');

    foreach ($widgets_not_yet_placed as $b) {
      $plugin_id = $b->getpluginId();
      $uuid = str_replace('block_content:', '', $plugin_id);
      $block_content_list = $block_storage->loadByProperties(['uuid' => $uuid]);
      $block_type = 'basic';

      if ($block_content_list) {
        $block_content = reset($block_content_list);
        $block_type = $block_content->bundle();
        $output['filter_by_type']['#options'][$block_content->bundle()] = $block_content->type->entity->label();
      }

      $block_build = [
        '#type' => 'inline_template',
        '#template' => '<div class="block block-active" data-block-type="{{ type }}" data-block-id="{{ id }}" tabindex="0"><h3 class="block-title">{{ title }}</h3>{{ content }}</div>',
        '#context' => [
          'id' => $b->id(),
          'type' => $block_type,
          'title' => '',
          'content' => '',
        ],
      ];

      $block_build['#context']['title'] = $b->label();
      if ($block_content->type->entity->id() == 'facet' || $block_content->type->entity->id() == 'search_sort') {
        $block_label = explode('|', $b->label());

        $block_build['#context']['title'] = isset($block_label[1]) ? trim($block_label[1]) : $b->label();
      }
      $block_build['#context']['content'] = $this->entityTypeManager->getViewBuilder('block')->view($b);
      $output['existing-blocks'][$b->id()] = $block_build;
    }

    return $output;
  }

}
