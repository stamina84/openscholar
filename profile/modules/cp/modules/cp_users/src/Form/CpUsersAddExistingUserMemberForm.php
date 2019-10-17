<?php

namespace Drupal\cp_users\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\cp_users\CpRolesHelperInterface;
use Drupal\cp_users\CpUsersHelper;
use Drupal\vsite\Plugin\VsiteContextManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for adding existing user as vsite member.
 */
class CpUsersAddExistingUserMemberForm extends CpUsersAddMemberFormBase {

  /**
   * Form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Creates a new CpUsersAddExistingUserMemberForm object.
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
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   Form builder service.
   */
  public function __construct(VsiteContextManagerInterface $vsite_context_manager, CpRolesHelperInterface $cp_roles_helper, EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user, MailManagerInterface $mail_manager, FormBuilderInterface $form_builder) {
    parent::__construct($vsite_context_manager, $cp_roles_helper, $entity_type_manager, $current_user, $mail_manager);
    $this->formBuilder = $form_builder;
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
      $container->get('plugin.manager.mail'),
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cp_users_add_existing_user_member';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $form['user'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'user',
      '#selection_settings' => [
        'include_anonymous' => FALSE,
      ],
      '#title' => $this->t('Member'),
      '#weight' => 1,
    ];

    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Save'),
        '#attributes' => [
          'class' => [
            'use-ajax',
          ],
        ],
        '#ajax' => [
          'callback' => [$this, 'submitForm'],
          'event' => 'click',
        ],
        '#name' => 'submit',
      ],
      'cancel' => [
        '#type' => 'button',
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
      ],
    ];

    $form['new_member_option'] = [
      '#type' => 'fieldgroup',
      '#weight' => 100,
      'message' => [
        '#type' => 'inline_template',
        '#template' => "<span>{% trans %} Can't find their account above? {% endtrans %}</span>",
      ],
      'option' => [
        '#type' => 'button',
        '#value' => $this->t('Create a new member'),
        '#ajax' => [
          'callback' => [$this, 'showAddNewMemberForm'],
        ],
        '#attributes' => [
          'class' => [
            'use-ajax',
          ],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $response = parent::submitForm($form, $form_state);

    if (!$form_state->getErrors()) {
      /** @var array $form_state_values */
      $form_state_values = $form_state->getValues();
      /** @var \Drupal\user\UserInterface $account */
      $account = $this->entityTypeManager->getStorage('user')->load($form_state_values['user']);
      $email_key = CpUsersHelper::CP_USERS_ADD_TO_GROUP;
      $role = $form_state_values['role'];

      $values = [
        'group_roles' => [
          $role,
        ],
      ];
      $this->activeVsite->addMember($account, $values);

      $params = [
        'user' => $account,
        'role' => $role,
        'creator' => $this->currentUser,
        'group' => $this->activeVsite,
      ];
      $this->mailManager->mail('cp_users', $email_key, $account->getEmail(), LanguageInterface::LANGCODE_DEFAULT, $params);

      $response->addCommand(new CloseModalDialogCommand());
      $response->addCommand(new RedirectCommand(Url::fromRoute('cp.users')->toString()));
    }

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $parent_validation_response = parent::validateForm($form, $form_state);
    /** @var array $triggering_element */
    $triggering_element = $form_state->getTriggeringElement();

    if ($triggering_element['#name'] === 'submit' && !$form_state->getValue('user')) {
      $form_state->setError($form['user'], $this->t('Please select a member.'));
      return FALSE;
    }

    return $parent_validation_response;
  }

  /**
   * Shows the options for adding new member.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The response containing the updates.
   */
  public function showAddNewMemberForm(): AjaxResponse {
    $response = new AjaxResponse();

    $response->addCommand(new CloseModalDialogCommand());
    $response->addCommand(new InvokeCommand('#add-new-member-option-opener', 'click'));

    return $response;
  }

}
