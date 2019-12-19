<?php

namespace Drupal\vsite_site_creation;

use Drupal\Core\Url;
use Drupal\user\ToolbarLinkBuilder as Original;
use Drupal\os\AngularModuleManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\vsite\Plugin\VsiteContextManager;
use Drupal\cp_users\Access\CpUsersSupportAccessCheck;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Decorator for the user Toolbar Builder.
 */
class ToolbarLinkBuilder extends Original {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * Angular module manager.
   *
   * @var \Drupal\os\AngularModuleManagerInterface
   */
  protected $angularModuleManager;

  /**
   * Access checker for Vsite Support.
   *
   * @var \Drupal\cp_users\Access\CpUsersSupportAccessCheck
   */
  protected $supportAccessCheck;

  /**
   * Vsite context manager.
   *
   * @var \Drupal\vsite\Plugin\VsiteContextManagerInterface
   */
  protected $vsiteContextManager;


  /**
   * Entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * ToolbarHandler constructor.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The current user.
   * @param \Drupal\os\AngularModuleManagerInterface $angular_module_manager
   *   Angular module manager.
   * @param \Drupal\cp_users\Access\CpUsersSupportAccessCheck $support_access_check
   *   Access checker for Vsite Support.
   * @param Drupal\vsite\Plugin\VsiteContextManager $vsite_context_manager
   *   Vsite context manager.
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   Entity Manager service.
   */
  public function __construct(AccountProxyInterface $account, AngularModuleManagerInterface $angular_module_manager, CpUsersSupportAccessCheck $support_access_check, VsiteContextManager $vsite_context_manager, EntityTypeManager $entity_type_manager) {
    $this->account = $account;
    $this->angularModuleManager = $angular_module_manager;
    $this->supportAccessCheck = $support_access_check;
    $this->vsiteContextManager = $vsite_context_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function renderToolbarLinks() {
    $build = parent::renderToolbarLinks();

    $build['#links']['site-create'] = [
      'title' => $this->t('Create site'),
      'url' => Url::fromRoute('<none>'),
      'attributes' => [
        'title' => $this->t('Create site'),
        'site-creation-form' => '',
      ],
    ];

    // Show support group link for support user.
    $can_support_vsite = ($this->supportAccessCheck->access($this->account)->isAllowed());
    if ($can_support_vsite) {
      // Since the access checker makes sure that there will be an active vsite,
      // therefore, we can be certain here that $vsite will not be null.
      $vsite = $this->vsiteContextManager->getActiveVsite();
      $user = $this->entityTypeManager->getStorage('user')->load($this->account->id());
      $member = $vsite->getMember($user);
      $roles = [];
      if ($member) {
        $roles = $member->getRoles();
      }

      $title = $this->t('Subscribe to this site');
      if ($roles && array_key_exists($vsite->getGroupType()->id() . '-support_user', $roles)) {
        $title = $this->t('Unsubscribe to this site');
      }

      $build['#links']['support-site'] = [
        'title' => $title,
        'url' => Url::fromRoute('cp_users.support.group_subscribe'),
        'attributes' => [
          'title' => $title,
        ],
      ];
    }

    $build['#attached']['library'][] = 'vsite_site_creation/site_creation';

    return $build;
  }

}
