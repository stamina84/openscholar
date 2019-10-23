<?php

namespace Drupal\cp_users\Controller;

use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\cp_users\Access\ChangeOwnershipAccessCheck;
use Drupal\cp_users\CpUsersHelperInterface;
use Drupal\cp_users\Form\CpUsersAddExistingUserMemberForm;
use Drupal\cp_users\Form\CpUsersAddNewMemberForm;
use Drupal\group\GroupMembership;
use Drupal\group\GroupMembershipLoaderInterface;
use Drupal\user\UserInterface;
use Drupal\vsite\Plugin\VsiteContextManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Component\Serialization\Json;

/**
 * Controller for the cp_users page.
 *
 * Also invokes the modals.
 */
class CpUserMainController extends ControllerBase {

  /**
   * Vsite Context Manager.
   *
   * @var \Drupal\vsite\Plugin\VsiteContextManagerInterface
   */
  protected $vsiteContextManager;

  /**
   * Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * ChangeOwnershipAccessCheck service.
   *
   * @var \Drupal\cp_users\Access\ChangeOwnershipAccessCheck
   */
  protected $changeOwnershipAccessChecker;

  /**
   * CpUsers helper service.
   *
   * @var \Drupal\cp_users\CpUsersHelperInterface
   */
  protected $cpUsersHelper;

  /**
   * Group membership loader.
   *
   * @var \Drupal\group\GroupMembershipLoaderInterface
   */
  protected $groupMembershipLoader;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('vsite.context_manager'),
      $container->get('entity_type.manager'),
      $container->get('cp_users.change_ownership_access_check'),
      $container->get('cp_users.cp_users_helper'),
      $container->get('group.membership_loader')
    );
  }

  /**
   * CpUserMainController constructor.
   *
   * @param \Drupal\vsite\Plugin\VsiteContextManagerInterface $vsiteContextManager
   *   Vsite Context Manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity Type Manager.
   * @param \Drupal\cp_users\Access\ChangeOwnershipAccessCheck $change_ownership_access_check
   *   ChangeOwnershipAccessCheck service.
   * @param \Drupal\cp_users\CpUsersHelperInterface $cp_users_helper
   *   CpUsers helper service.
   * @param \Drupal\group\GroupMembershipLoaderInterface $group_membership_loader
   *   Group membership loader.
   */
  public function __construct(VsiteContextManagerInterface $vsiteContextManager, EntityTypeManagerInterface $entityTypeManager, ChangeOwnershipAccessCheck $change_ownership_access_check, CpUsersHelperInterface $cp_users_helper, GroupMembershipLoaderInterface $group_membership_loader) {
    $this->vsiteContextManager = $vsiteContextManager;
    $this->entityTypeManager = $entityTypeManager;
    $this->changeOwnershipAccessChecker = $change_ownership_access_check;
    $this->cpUsersHelper = $cp_users_helper;
    $this->groupMembershipLoader = $group_membership_loader;
  }

  /**
   * Entry point for cp/users.
   */
  public function main() {
    $group = $this->vsiteContextManager->getActiveVsite();
    if (!$group) {
      throw new AccessDeniedHttpException();
    }
    /** @var \Drupal\Core\Session\AccountInterface $current_user */
    $current_user = $this->currentUser();
    $can_change_ownership = ($this->changeOwnershipAccessChecker->access($current_user) instanceof AccessResultAllowed);

    $users = $group->getContentEntities('group_membership');

    $build = [];

    $userRows = [];
    /* @var \Drupal\user\UserInterface $u */
    foreach ($users as $u) {
      $is_vsite_owner = $this->cpUsersHelper->isVsiteOwner($group, $u);
      $roles = $group->getMember($u)->getRoles();
      $role_link = '';

      if ($can_change_ownership && $is_vsite_owner) {
        $role_link = Link::createFromRoute('Change Owner', 'cp.users.owner', ['user' => $u->id()], ['attributes' => ['class' => ['use-ajax']]])->toString();
      }
      elseif (!$is_vsite_owner && $group->hasPermission('manage cp roles', $this->currentUser())) {
        $role_link = Link::createFromRoute($this->t('Change Role'), 'cp_users.role.change', ['user' => $u->id()], [
          'attributes' => [
            'class' => ['use-ajax'],
            'data-dialog-type' => 'modal',
            'data-dialog-options' => Json::encode(['width' => 800]),
          ],
        ])->toString();
      }

      $remove_link = Link::createFromRoute($this->t('Remove'), 'cp.users.remove', ['user' => $u->id()], ['attributes' => ['class' => ['use-ajax']]])->toString();
      $row = [
        'data-user-id' => $u->id(),
        'data' => [
          $u->label(),
          $u->label(),
          $group->getOwnerId() == $u->id() ? $this->t('Site Owner') : current($roles)->label(),
          $role_link,
          $this->t('Active'),
          ($group->getOwnerId() == $u->id()) ? '' : $remove_link,
        ],
      ];
      $userRows[] = $row;
    }

    $build['cp_user'] = [
      '#cache' => [
        'max-age' => 0,
      ],
      '#type' => 'container',
      '#attributes' => [
        'id' => 'cp-user',
        'class' => ['cp-manage-users-wrapper'],
      ],
      'cp_user_actions' => [
        '#type' => 'container',
        'add-member' => [
          '#type' => 'link',
          '#title' => $this->t('Add a member'),
          '#url' => Url::fromRoute('cp.users.add'),
          '#attributes' => [
            'class' => [
              'os-green-button',
              'cp-user-float-right',
              'use-ajax',
              'button',
              'button--primary',
              'button-action',
              'action-links',
            ],
            'data-dialog-type' => 'modal',
          ],
          '#attached' => [
            'library' => [
              'core/drupal.dialog.ajax',
            ],
          ],
        ],
        'add-new-member' => [
          '#type' => 'link',
          '#title' => $this->t('Add a new member'),
          '#url' => Url::fromRoute('cp.users.add_new'),
          '#attributes' => [
            'class' => [
              'os-green-button',
              'cp-user-float-right',
              'use-ajax',
              'button',
              'button--primary',
              'button-action',
              'action-links',
              'visually-hidden',
            ],
            'data-dialog-type' => 'modal',
            'id' => 'add-new-member-option-opener',
          ],
          '#attached' => [
            'library' => [
              'core/drupal.dialog.ajax',
            ],
          ],
        ],
      ],
      'cp_user_table' => [
        '#type' => 'table',
        '#header' => [
          $this->t('Name'),
          $this->t('Username'),
          $this->t('Role'),
          $this->t('Change Role'),
          $this->t('Status'),
          $this->t('Remove'),
        ],
        '#rows' => $userRows,
        '#empty' => $this->t('There are no users in your site. This is very not right, please contact the support team immediately.'),
        '#attributes' => [
          'class' => ['cp-manager-user-content'],
        ],
      ],
    ];

    return $build;
  }

  /**
   * Opens a modal with the Add Member form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The response returned to the client.
   */
  public function addUserForm() {
    $dialogOptions = [
      'dialogClass' => 'add-user-dialog',
      'width' => 800,
      'modal' => TRUE,
      'position' => [
        'my' => 'center top',
        'at' => 'center top',
      ],
    ];

    $response = new AjaxResponse();

    $modal_form = $this->formBuilder()->getForm(CpUsersAddExistingUserMemberForm::class);

    $response->addCommand(new OpenModalDialogCommand($this->t('Add an existing member'), $modal_form, $dialogOptions));

    return $response;
  }

  /**
   * Opens the modal for adding a new member.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The response containing the updates.
   */
  public function addNewUserForm(): AjaxResponse {
    $response = new AjaxResponse();

    $modal_form = $this->formBuilder()->getForm(CpUsersAddNewMemberForm::class);

    $response->addCommand(new OpenModalDialogCommand($this->t('Add new member'), $modal_form, [
      'dialogClass' => 'add-user-dialog',
      'width' => 800,
      'modal' => TRUE,
      'position' => [
        'my' => 'center top',
        'at' => 'center top',
      ],
    ]));

    return $response;
  }

  /**
   * Open a modal with the Remove User.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user being removed from the site.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The response to open the modal
   */
  public function removeUserForm(UserInterface $user) {
    $group = $this->vsiteContextManager->getActiveVsite();
    if (!$group) {
      throw new AccessDeniedHttpException();
    }

    $response = new AjaxResponse();

    $modal_form = $this->formBuilder()->getForm('Drupal\cp_users\Form\CpUsersRemoveForm', $user);

    $response->addCommand(new OpenModalDialogCommand($this->removeUserFormTitle($user), $modal_form, ['width' => '800']));

    return $response;
  }

  /**
   * Customize the title to have the target user's name.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user being removed from the site.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The title of the modal.
   */
  public function removeUserFormTitle(UserInterface $user) {
    return $this->t('Remove Member @name', ['@name' => $user->label()]);
  }

  /**
   * Modal for changing the owner of a Vsite.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The response to open the modal.
   */
  public function changeOwnershipForm() {
    $group = $this->vsiteContextManager->getActiveVsite();
    if (!$group) {
      throw new AccessDeniedHttpException();
    }

    $response = new AjaxResponse();

    $modal_form = $this->formBuilder()->getForm('Drupal\cp_users\Form\CpUsersOwnershipForm');

    $response->addCommand(new OpenModalDialogCommand('Change Site Ownership', $modal_form, ['width' => '800']));

    return $response;
  }

  /**
   * Autocomplete handler for adding existing user as vsite member.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Response containing the matched users.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function existingUserAutocomplete(Request $request): JsonResponse {
    $results = [];
    $query = $request->query->get('q');
    /** @var \Drupal\group\Entity\GroupInterface|null $active_vsite */
    $active_vsite = $this->vsiteContextManager->getActiveVsite();

    if ($query && $active_vsite) {
      /** @var \Drupal\user\UserStorageInterface $user_storage */
      $user_storage = $this->entityTypeManager()->getStorage('user');
      /** @var \Drupal\Core\Entity\Query\QueryInterface $user_query */
      $user_query = $user_storage->getQuery();

      // Get existing members of the active vsite.
      /** @var \Drupal\group\GroupMembership[] $memberships */
      $memberships = $this->groupMembershipLoader->loadByGroup($active_vsite);
      /** @var int[] $member_ids */
      $member_ids = array_map(static function (GroupMembership $membership) {
        return $membership->getUser()->id();
      }, $memberships);

      // Find users who are no members of existing vsite.
      /** @var array $query_result */
      $query_result = $user_query->condition('name', "%$query%", 'LIKE')
        ->condition('status', 1)
        ->condition('uid', $member_ids, 'NOT IN')
        ->execute();

      /** @var \Drupal\user\UserInterface[] $non_members */
      $non_members = $user_storage->loadMultiple(array_keys($query_result));

      foreach ($non_members as $account) {
        $results[] = [
          'value' => "{$account->label()} ({$account->id()})",
          'label' => $account->label(),
        ];
      }
    }

    return new JsonResponse($results);
  }

}
