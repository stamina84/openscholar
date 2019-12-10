<?php

namespace Drupal\cp_users\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Url;
use Drupal\cp_users\CpUsersHelper;
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
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('vsite.context_manager'),
      $container->get('cp_users.cp_roles_helper'),
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('plugin.manager.mail'),
      $container->get('cp_users.change_ownership_access_check'),
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
      '#type' => 'textfield',
      '#autocomplete_route_name' => 'cp_users.existing_user_autocomplete',
      '#title' => $this->t('Member'),
      '#weight' => 1,
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#attributes' => [
        'class' => [
          'use-ajax',
        ],
      ],
      '#ajax' => [
        'callback' => [$this, 'addMember'],
        'event' => 'click',
      ],
      '#name' => 'submit',
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
   * Adds a vsite member.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The response containing the updates.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function addMember(array &$form, FormStateInterface $form_state): AjaxResponse {
    $response = $this->submitForm($form, $form_state);

    if (!$form_state->getErrors()) {
      /** @var array $form_state_values */
      $form_state_values = $form_state->getValues();
      /** @var int $uid */
      $uid = EntityAutocomplete::extractEntityIdFromAutocompleteInput($form_state_values['user']);
      /** @var \Drupal\user\UserInterface $account */
      $account = $this->entityTypeManager->getStorage('user')->load($uid);
      $role = $form_state_values['role'];

      $this->activeVsite->addMember($account, [
        'group_roles' => [
          $role,
        ],
      ]);

      // Check if site owner option is set.
      if ($form_state_values['site_owner']) {
        $this->activeVsite->setOwnerId($account->get('uid')->value);
        $this->activeVsite->save();
      }

      $this->mailManager->mail('cp_users', CpUsersHelper::CP_USERS_ADD_TO_GROUP, $account->getEmail(), LanguageInterface::LANGCODE_DEFAULT, [
        'user' => $account,
        'role' => $role,
        'creator' => $this->currentUser,
        'group' => $this->activeVsite,
      ]);

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
