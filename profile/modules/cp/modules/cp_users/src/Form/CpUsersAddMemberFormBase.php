<?php

namespace Drupal\cp_users\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\cp_users\CpRolesHelperInterface;
use Drupal\group\Entity\GroupRoleInterface;
use Drupal\vsite\Plugin\VsiteContextManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form base for adding vsite members.
 */
abstract class CpUsersAddMemberFormBase extends FormBase {

  /**
   * Vsite context manager.
   *
   * @var \Drupal\vsite\Plugin\VsiteContextManagerInterface
   */
  protected $vsiteContextManager;

  /**
   * Active vsite.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $activeVsite;

  /**
   * Cp roles helper.
   *
   * @var \Drupal\cp_users\CpRolesHelperInterface
   */
  protected $cpRolesHelper;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * Creates a new CpUsersAddMemberFormBase object.
   *
   * @param \Drupal\vsite\Plugin\VsiteContextManagerInterface $vsite_context_manager
   *   Vsite context manager.
   * @param \Drupal\cp_users\CpRolesHelperInterface $cp_roles_helper
   *   Cp roles helper.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   Mail manager.
   */
  public function __construct(VsiteContextManagerInterface $vsite_context_manager, CpRolesHelperInterface $cp_roles_helper, EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user, MailManagerInterface $mail_manager) {
    $this->vsiteContextManager = $vsite_context_manager;
    $this->cpRolesHelper = $cp_roles_helper;
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->mailManager = $mail_manager;
    $this->activeVsite = $vsite_context_manager->getActiveVsite();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('vsite.context_manager'),
      $container->get('cp_users.cp_roles_helper'),
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('plugin.manager.mail')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $options = [];
    /** @var \Drupal\group\Entity\GroupTypeInterface $group_type */
    $group_type = $this->activeVsite->getGroupType();
    $roles = $group_type->getRoles(TRUE);
    // Remove unwanted roles for vsites from the options.
    /** @var string[] $non_configurable_roles */
    $non_configurable_roles = $this->cpRolesHelper->getNonConfigurableGroupRoles($this->activeVsite);
    /** @var \Drupal\group\Entity\GroupRoleInterface[] $allowed_roles */
    $allowed_roles = array_filter($roles, static function (GroupRoleInterface $role) use ($non_configurable_roles) {
      return !\in_array($role->id(), $non_configurable_roles, TRUE) && !$role->isInternal();
    });
    foreach ($allowed_roles as $role) {
      $options[$role->id()] = cp_users_render_cp_role_label($role);
    }

    $form['#prefix'] = '<div id="cp-user-add-member-form">';
    $form['#suffix'] = '</div>';

    $form['status_messages'] = [
      '#type' => 'status_messages',
      '#weight' => -10,
    ];

    $form['role'] = [
      '#type' => 'radios',
      '#title' => $this->t('Role'),
      '#options' => $options,
      '#default_value' => $group_type->getMemberRoleId(),
      '#weight' => 99,
      '#required' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
      'cancel' => [
        '#type' => 'submit',
        '#value' => $this->t('Cancel'),
        '#attributes' => [
          'class' => [
            'use-ajax',
          ],
        ],
        '#ajax' => [
          'callback' => [$this, 'closeModal'],
          'event' => 'click',
        ],
        '#name' => 'cancel',
        '#weight' => 1,
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    if (!$form_state->getValue('role')) {
      $form_state->setError($form['role'], $this->t('Please select a role'));
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    if ($form_state->getErrors()) {
      $response->addCommand(new ReplaceCommand('#cp-user-add-member-form', $form));
    }

    return $response;
  }

  /**
   * Closes the modal.
   */
  public function closeModal(): AjaxResponse {
    $response = new AjaxResponse();
    $response->addCommand(new CloseModalDialogCommand());
    return $response;
  }

}
