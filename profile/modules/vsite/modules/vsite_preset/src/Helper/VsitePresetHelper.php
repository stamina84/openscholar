<?php

namespace Drupal\vsite_preset\Helper;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\cp_menu\Services\MenuHelper;
use Drupal\group\Entity\GroupInterface;
use Drupal\os_app_access\AppAccessLevels;
use Drupal\vsite\Plugin\AppManager;
use Drupal\vsite\Plugin\VsiteContextManager;
use League\Csv\Reader;
use Drupal\os_widgets\OsWidgetsManager;
use Drupal\vsite_preset\Entity\GroupPreset;

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
   * Os widget factory.
   *
   * @var Drupal\os_widgets\OsWidgetsManager
   */
  protected $osWidgetsManager;

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
   * @param Drupal\os_widgets\OsWidgetsManager $osWidgetsManager
   *   OsWidgetsManager instance.
   */
  public function __construct(VsiteContextManager $vsiteContextManager, AppManager $appManager, EntityTypeManager $entityTypeManager, MenuHelper $menuHelper, ConfigFactory $configFactory, OsWidgetsManager $osWidgetsManager) {
    $this->vsiteContextManager = $vsiteContextManager;
    $this->appManager = $appManager;
    $this->entityTypeManager = $entityTypeManager;
    $this->menuHelper = $menuHelper;
    $this->configFactory = $configFactory;
    $this->osWidgetsManager = $osWidgetsManager;
  }

  /**
   * {@inheritdoc}
   */
  public function enableApps(GroupInterface $group, $appsToEnable, $appsToSetPrivate): void {
    $this->vsiteContextManager->activateVsite($group);
    $access = $this->configFactory->getEditable('os_app_access.access');
    $appDefinitions = $this->appManager->getDefinitions();
    foreach ($appDefinitions as $name => $app) {
      if (in_array($name, $appsToEnable)) {
        $access->set($name, AppAccessLevels::PUBLIC);
      }
      elseif (in_array($name, $appsToSetPrivate)) {
        $access->set($name, AppAccessLevels::PRIVATE);
      }
      else {
        $access->set($name, AppAccessLevels::DISABLED);
      }
    }
    $access->save();
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
        $this->createNode($data, $bundle, $group);
        break;

      case 'block_content':
        $plugin_id = $bundle . '_widget';
        $plugin = $this->osWidgetsManager->createInstance($plugin_id);
        $plugin->createWidget($data, $bundle, $group);
        break;

      case 'menu_link_content':
        $this->createMenuLinks($data, $group);
        break;
    }
  }

  /**
   * Returns file paths of contents to be created.
   *
   * @param \Drupal\vsite_preset\Entity\GroupPreset $preset
   *   Preset object. Group Preset Config.
   * @param \Drupal\group\Entity\GroupInterface $group
   *   Group for which data is created.
   *
   * @see vsite_preset_group_insert()
   *
   * @return array
   *   Returns path to all csv files.
   */
  public function getCreateFilePaths(GroupPreset $preset, GroupInterface $group) {
    $fileArr = $preset->getCreationFilePaths();
    $group_id = $group->getGroupType()->id();
    $default_content['default'] = file_scan_directory(drupal_get_path('module', 'vsite_preset') . "/default_content/", '/.csv/');
    // If for this group type no preset files exists then we need not proceed.
    if (isset($fileArr[$group_id])) {
      $fileArrAll = array_merge($default_content['default'], $fileArr[$group_id]);
    }
    else {
      $fileArrAll = $default_content;
    }
    return array_keys($fileArrAll);
  }

  /**
   * Creates default Node content for the group.
   *
   * @param array $data
   *   Csv rows as data array.
   * @param string $bundle
   *   Type of node to create.
   * @param \Drupal\group\Entity\GroupInterface $group
   *   Group for which data is created.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createNode(array $data, $bundle, GroupInterface $group) {
    $storage = $this->entityTypeManager->getStorage('node');
    foreach ($data as $row) {
      $node = $storage->create([
        'type' => $bundle,
        'title' => $row['Title'],
      ]);
      $node->save();
      $group->addContent($node, "group_node:$bundle");
      if ($row['Link'] == 'TRUE') {
        $this->createContentMenuLink($row['Title'], $group->id(), $node, $row['LinkWeight']);
      }
    }
  }

  /**
   * Creates Menu links as per csv data.
   *
   * @param array $data
   *   Data read from the csv.
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to create links for.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createMenuLinks(array $data, GroupInterface $group): void {
    $storage = $this->entityTypeManager->getStorage('menu_link_content');
    // Creates new vsite specific menus only once.
    $this->menuHelper->createMenu($group);
    $this->menuHelper->createMenu($group, FALSE);

    foreach ($data as $row) {
      $route_name = $row['Route'];
      $parent = $this->menuHelper::DEFAULT_VSITE_MENU_MAPPING[$row['Parent']] . $group->id();
      $storage->create([
        'title' => $row['Title'],
        'link' => ['uri' => "route:$route_name"],
        'menu_name' => $parent,
        'weight' => $row['Weight'],
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
   * @param int $link_weight
   *   Weight for ordering links in menu.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createContentMenuLink($title, $gid, EntityInterface $entity, $link_weight = 0) {
    $entity_id = $entity->id();
    $uri = $entity->getEntityTypeId() === 'bibcite_reference' ? "entity:bibcite_reference/$entity_id" : "entity:node/$entity_id";
    $menu_id = $this->menuHelper::DEFAULT_VSITE_MENU_MAPPING['main'] . $gid;
    $storage = $this->entityTypeManager->getStorage('menu_link_content');
    $storage->create([
      'title' => $title,
      'link' => ['uri' => $uri],
      'menu_name' => $menu_id,
      'weight' => $link_weight,
      'expanded' => TRUE,
    ])->save();
  }

}
