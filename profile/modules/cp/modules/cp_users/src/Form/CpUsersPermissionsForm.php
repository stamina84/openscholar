<?php

namespace Drupal\cp_users\Form;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\cp_users\CpRolesHelperInterface;
use Drupal\group\Access\GroupPermissionHandlerInterface;
use Drupal\group\Form\GroupPermissionsForm;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\vsite\Plugin\AppManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\os_app_access\AppAccessLevels;

/**
 * Base for CpUsers permissions form.
 */
abstract class CpUsersPermissionsForm extends GroupPermissionsForm {

  /**
   * The Permission grouping array specifying title and permission group.
   */
  const PERMISSION_GROUPS = [
    'os_app_access' => [
      'title' => 'Apps',
    ],
    'group' => [
      'title' => 'Site',
    ],
    'group_entity' => [
      'title' => 'Site Content',
    ],
    'groupmenu' => [
      'title' => 'Menus',
    ],
    'gnode' => [
      'title' => 'Site Content',
      'provider' => 'group_entity',
    ],
    'os_pages' => [
      'title' => 'Site Content',
      'provider' => 'group_entity',
    ],
    'os_publications' => [
      'title' => 'Site Content',
      'provider' => 'group_entity',
    ],
    'os_redirect' => [
      'title' => 'Openscholar',
      'provider' => 'os',
    ],
    'os_media' => [
      'title' => 'Openscholar',
      'provider' => 'os',
    ],
    'os' => [
      'title' => 'Openscholar',
    ],
    'os_search_solr' => [
      'title' => 'Search',
    ],
    'vsite_domain' => [
      'title' => 'Domain',
    ],
    'os_widgets' => [
      'title' => 'Widgets',
    ],
  ];

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * CpRoles helper service.
   *
   * @var \Drupal\cp_users\CpRolesHelperInterface
   */
  protected $cpRolesHelper;

  /**
   * Vsite app manager.
   *
   * @var \Drupal\vsite\Plugin\AppManager
   */
  protected $vsiteAppManager;

  /**
   * Creates a new CpUsersPermissionsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory service.
   * @param \Drupal\group\Access\GroupPermissionHandlerInterface $permission_handler
   *   The group permission handler.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\cp_users\CpRolesHelperInterface $cp_roles_helper
   *   CpRoles editable service.
   * @param \Drupal\vsite\Plugin\AppManager $vsiteAppManager
   *   Vsite app manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, GroupPermissionHandlerInterface $permission_handler, ModuleHandlerInterface $module_handler, CpRolesHelperInterface $cp_roles_helper, AppManager $vsiteAppManager) {
    parent::__construct($permission_handler, $module_handler);
    $this->configFactory = $config_factory;
    $this->cpRolesHelper = $cp_roles_helper;
    $this->vsiteAppManager = $vsiteAppManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('group.permissions'),
      $container->get('module_handler'),
      $container->get('cp_users.cp_roles_helper'),
      $container->get('vsite.app.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $role_info = [];

    /** @var \Drupal\Core\Config\ImmutableConfig $levels */
    $levels = $this->configFactory->get('os_app_access.access');

    // Get the sections of the permissions related to disbled apps.
    $disabled_nodes = [];
    $disabled_entities = [];
    $disabled_nodes_and_entities = [];
    $appDefinitions = $this->vsiteAppManager->getDefinitions();
    foreach ($appDefinitions as $appDefinition) {
      /** @var int $access_level */
      $access_level = (int) $levels->get($appDefinition['id']);
      if ($access_level === AppAccessLevels::DISABLED) {
        if ($appDefinition['entityType'] == 'node') {
          $disabled_nodes[] = $appDefinition['bundle'];
        }
        else {
          $disabled_entities[] = $appDefinition['entityType'];;
        }
      }
    }
    $disabled_nodes_and_entities = array_merge($disabled_entities, ...$disabled_nodes);
    // Sort the group roles using the static sort() method.
    // See \Drupal\Core\Config\Entity\ConfigEntityBase::sort().
    $group_roles = $this->getGroupRoles();
    uasort($group_roles, '\Drupal\group\Entity\GroupRole::sort');

    foreach ($group_roles as $role_name => $group_role) {
      $role_info[$role_name] = [
        'label' => $group_role->label(),
        'permissions' => $group_role->getPermissions(),
        'is_anonymous' => $group_role->isAnonymous(),
        'is_outsider' => $group_role->isOutsider(),
        'is_member' => $group_role->isMember(),
      ];
    }

    // This overrides the default permissions form, and improves the UX.
    // Instead, of building the form elements from scratch, it re-uses the form
    // elements from parent.
    $group_sections = [];
    foreach ($this->getPermissions() as $provider => $sections) {
      $provider_attributes = $this->getProviderKeyTitle($provider);
      $provider_key = $provider_attributes['provider_key'];

      if (!isset($form[$provider_key])) {
        $form[$provider_key] = [
          '#type' => 'details',
          '#title' => $this->t('@title', ['@title' => $provider_attributes['title']]),
          '#open' => FALSE,
        ];

        $form[$provider_key]['permissions'] = [
          '#type' => 'table',
          '#header' => [$this->t('Permission')],
          '#id' => 'permissions',
          '#attributes' => ['class' => ['permissions', 'js-permissions']],
        ];

        $form[$provider_key]['permissions']['#header'] = $form['permissions']['#header'];
      }

      if ($provider_key == "provider_group_entity") {
        $group_sections = array_merge_recursive($group_sections, $sections);
        unset($form[$provider_key]['permissions']);

        $form[$provider_key]['permissions'] = [
          '#type' => 'table',
          '#header' => [$this->t('Permission')],
          '#id' => 'permissions',
          '#attributes' => ['class' => ['permissions', 'js-permissions']],
        ];

        $form[$provider_key]['permissions']['#header'] = $form['permissions']['#header'];
        $sections = $group_sections;
      }

      foreach ($sections as $section => $permissions) {
        $get_provider = reset($permissions);
        $provider = $get_provider['provider'];
        // Create a clean section ID.
        $section_id = $provider . '-' . preg_replace('/[^a-z0-9_]+/', '_', strtolower($section));
        $disabled_section_id = NULL;
        // Remove the disabled sections.
        foreach ($disabled_nodes_and_entities as $disabled_item) {
          if (strpos($section_id, $disabled_item) !== FALSE) {
            $disabled_section_id = $section_id;
            unset($form[$provider_key]['permissions'][$section_id]);
            foreach ($permissions as $perm => $perm_item) {
              unset($form[$provider_key]['permissions'][$perm]);
            }
            break;
          }
        }

        if ($disabled_section_id == NULL && count($permissions)) {
          // Start each section with a full width row containing the section
          // name.
          if ($provider_key == "provider_group_entity") {
            $replace_words = ['group', 'node', '_', '(', ')'];
            $label = $form['permissions'][$section_id][0]['#markup'];
            $form['permissions'][$section_id][0]['#markup'] = ucwords(str_ireplace($replace_words, ' ', $label));
          }

          $form[$provider_key]['permissions'][$section_id] = $form['permissions'][$section_id];

          // Then list all of the permissions for that provider and section.
          foreach ($permissions as $perm => $perm_item) {
            $disabled_permission = NULL;
            foreach ($disabled_nodes_and_entities as $disabled_item) {
              if (strpos($perm, $disabled_item) !== FALSE) {
                $disabled_permission = $perm;
                unset($form[$provider_key]['permissions'][$perm]);
                unset($form['permissions'][$perm]);
                break;
              }
            }

            if ($disabled_permission == NULL) {
              // Create a row for the permission, starting with the description
              // cell.
              $form[$provider_key]['permissions'][$perm]['description'] = $form['permissions'][$perm]['description'];
              $form['permissions'][$perm]['description']['#context']['description'] = $perm_item['description'];
              $form['permissions'][$perm]['description']['#context']['warning'] = $perm_item['warning'];

              // Finally build a checkbox cell for every group role.
              foreach ($role_info as $role_name => $info) {
                $form[$provider_key]['permissions'][$perm][$role_name] = $form['permissions'][$perm][$role_name];
              }
            }
          }
        }
      }

      // Do not show relationship permissions in the UI.
      foreach ($this->cpRolesHelper->getRestrictedPermissions($this->getGroupType()) as $permission) {
        unset($form[$provider_key]['permissions'][$permission]);
      }
    }

    // The default permissions form element is no longer required.
    unset($form['permissions']);
    return $form;
  }

  /**
   * Creates the permissions wrapper key and label .
   *
   * It return permissions wrapper key and label based
   * on module name providing the permissions and in
   * which group permissions should appear.
   *
   * @param string $provider
   *   The module name providing the permissions.
   *
   * @return array
   *   An array having wrapper key and label.
   */
  protected function getProviderKeyTitle($provider) {
    $provider_key = "provider_$provider";
    $title = $this->moduleHandler->getName($provider);

    if (array_key_exists($provider, self::PERMISSION_GROUPS)) {
      $provider_key = array_key_exists('provider', self::PERMISSION_GROUPS[$provider]) ? self::PERMISSION_GROUPS[$provider]['provider'] : $provider;
      $provider_key = "provider_" . $provider_key;
      $title = self::PERMISSION_GROUPS[$provider]['title'];
    }
    return [
      'provider_key' => $provider_key,
      'title' => $title,
    ];
  }

}
