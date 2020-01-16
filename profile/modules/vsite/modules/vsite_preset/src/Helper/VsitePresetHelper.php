<?php

namespace Drupal\vsite_preset\Helper;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\cp_menu\Services\MenuHelper;
use Drupal\group\Entity\GroupInterface;
use Drupal\os_app_access\AppAccessLevels;
use Drupal\os_widgets\Entity\LayoutContext;
use Drupal\vsite\Plugin\AppManager;
use Drupal\vsite\Plugin\VsiteContextManager;
use League\Csv\Reader;

/**
 * Class VsitePresetHelper.
 *
 * @package Drupal\vsite_preset\Helper
 */
class VsitePresetHelper implements VsitePresetHelperInterface {

  use StringTranslationTrait;

  /**
   * VsiteContextManager service.
   *
   * @var \Drupal\vsite\Plugin\VsiteContextManager
   */
  protected $vsiteContextManager;

  /**
   * AppManager service.
   *
   * @var \Drupal\vsite\Plugin\AppManager
   */
  protected $appManager;

  /**
   * EntityTypeManager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Menu Helper service.
   *
   * @var \Drupal\cp_menu\Services\MenuHelper
   */
  protected $menuHelper;

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * VsitePresetHelper constructor.
   *
   * @param \Drupal\vsite\Plugin\VsiteContextManager $vsiteContextManager
   *   VsiteContextManager instance.
   * @param \Drupal\vsite\Plugin\AppManager $appManager
   *   AppManager instance.
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   EntityTypeManager instance.
   * @param \Drupal\cp_menu\Services\MenuHelper $menuHelper
   *   MenuHelper instance.
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   *   ConfigFactory instance.
   */
  public function __construct(VsiteContextManager $vsiteContextManager, AppManager $appManager, EntityTypeManager $entityTypeManager, MenuHelper $menuHelper, ConfigFactory $configFactory) {
    $this->vsiteContextManager = $vsiteContextManager;
    $this->appManager = $appManager;
    $this->entityTypeManager = $entityTypeManager;
    $this->menuHelper = $menuHelper;
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public function enableApps(GroupInterface $group, $appsToEnable): void {
    $this->vsiteContextManager->activateVsite($group);
    $app_definitions = $this->appManager->getDefinitions();
    $access = $this->configFactory->getEditable('os_app_access.access');
    foreach ($app_definitions as $name => $app) {
      if (in_array($name, $appsToEnable)) {
        $access->set($name, AppAccessLevels::PUBLIC);
      }
      else {
        $access->set($name, AppAccessLevels::DISABLED);
      }
    }
    $access->save();
    // Enable menu links for the enabled apps.
    $this->enableAppMenuLinks($group, $app_definitions, $appsToEnable);
  }

  /**
   * {@inheritdoc}
   */
  public function createDefaultContent(GroupInterface $group, $uri): void {
    $parsedUri = explode('/', $uri);
    $count = count($parsedUri);
    $entityType = $parsedUri[$count - 3];
    $bundle = $parsedUri[$count - 2];

    $header = NULL;
    $data = [];

    $csv = Reader::createFromPath($uri, 'r');
    foreach ($csv as $row) {
      if (!$header) {
        $header = $row;
      }
      else {
        $data[] = array_combine($header, $row);
      }
    }

    switch ($entityType) {
      case 'node':
        $storage = $this->entityTypeManager->getStorage($entityType);
        foreach ($data as $row) {
          $node = $storage->create([
            'type' => $bundle,
            'title' => $row['Title'],
          ]);
          $node->save();
          $group->addContent($node, "group_node:$bundle");
          if ($row['Link'] == 'TRUE') {
            $this->createContentMenuLink($row['Title'], $group->id(), $node);
          }
        }
        break;

      case 'block_content':
        $storage = $this->entityTypeManager->getStorage($entityType);
        foreach ($data as $row) {
          $block = $storage->create([
            'type' => $bundle,
            'info' => $row['Info'],
            'field_widget_title' => $row['Title'],
            'body' => $row['Body'],
          ]);
          $block->save();
          $group->addContent($block, "group_entity:$entityType");

          /** @var \Drupal\os_widgets\Entity\LayoutContext $context */
          $context = LayoutContext::load('all_pages');
          $data = $context->getBlockPlacements();
          $block_uuid = $block->uuid();
          $data[] = [
            'id' => "$entityType|$block_uuid",
            'region' => 'sidebar_second',
            'weight' => 0,
          ];
          $context->setBlockPlacements($data);
          $context->save();
        }
    }
  }

  /**
   * Create and enable menu links for enabled apps.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The newly created vsite in context.
   * @param array $app_definitions
   *   App definitions.
   * @param array $appsToEnable
   *   Apps which are enabled.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function enableAppMenuLinks(GroupInterface $group, array $app_definitions, array $appsToEnable) {
    $this->menuHelper->resetVsiteMenus($group);

    $menu_id = $this->menuHelper::DEFAULT_VSITE_MENU_MAPPING['main'] . $group->id();
    $storage = $this->entityTypeManager->getStorage('menu_link_content');
    sort($appsToEnable);
    foreach ($appsToEnable as $weight => $id) {
      if (!isset($app_definitions[$id]['contextualRoute'])) {
        continue;
      }
      $route_name = $app_definitions[$id]['contextualRoute'];
      $storage->create([
        'title' => $app_definitions[$id]['title'],
        'link' => ['uri' => "route:$route_name"],
        'menu_name' => $menu_id,
        'weight' => $weight + 1,
        'expanded' => TRUE,
      ])->save();
    }
  }

  /**
   * Create menu links for the content during vsite creation.
   *
   * @param string $title
   *   The menu link title.
   * @param int $gid
   *   The group/vsite id.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which link is to be created.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createContentMenuLink($title, $gid, EntityInterface $entity) {
    $entity_id = $entity->id();
    $uri = $entity->getEntityTypeId() === 'bibcite_reference' ? "entity:bibcite_reference/$entity_id" : "entity:node/$entity_id";
    $menu_id = $this->menuHelper::DEFAULT_VSITE_MENU_MAPPING['main'] . $gid;
    $storage = $this->entityTypeManager->getStorage('menu_link_content');
    $storage->create([
      'title' => $this->t('@title', ['@title' => $title]),
      'link' => ['uri' => $uri],
      'menu_name' => $menu_id,
      'weight' => 0,
      'expanded' => TRUE,
    ])->save();
  }

}
