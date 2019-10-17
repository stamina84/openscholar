<?php

namespace Drupal\cp_users\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Url;
use Drupal\cp_users\CpUsersHelper;

/**
 * Form for adding new vsite member.
 */
class CpUsersAddNewMemberForm extends CpUsersAddMemberFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cp_users_add_new_member';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $form['new_user_wrapper'] = [
      '#type' => 'fieldgroup',
      '#access' => !$this->config('cp_users.settings')->get('disable_user_creation'),
      'first_name' => [
        '#type' => 'textfield',
        '#title' => $this->t('First Name'),
        '#maxlength' => 255,
        '#size' => 60,
      ],
      'last_name' => [
        '#type' => 'textfield',
        '#title' => $this->t('Last Name'),
        '#maxlength' => 255,
        '#size' => 60,
      ],
      'username' => [
        '#type' => 'textfield',
        '#title' => $this->t('Username'),
        '#maxlength' => 255,
        '#size' => 60,
        '#required' => TRUE,
      ],
      'email' => [
        '#type' => 'textfield',
        '#title' => $this->t('E-mail Address'),
        '#maxlength' => 255,
        '#size' => 60,
        '#required' => TRUE,
      ],
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
          'callback' => [$this, 'addNewMember'],
          'event' => 'click',
        ],
        '#name' => 'submit',
      ],
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
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $parent_validation_response = parent::validateForm($form, $form_state);
    $form_state_values = $form_state->getValues();

    $account = (bool) user_load_by_mail($form_state_values['email']);
    if ($account) {
      $form_state->setError($form['new_user_wrapper']['email'], $this->t('User with this email already exists. Please choose a different email.'));
      return FALSE;
    }

    $account = user_load_by_name($form_state_values['username']);
    if ($account) {
      $form_state->setError($form['new_user_wrapper']['username'], $this->t('User with this username already exists. Please choose a different username.'));
      return FALSE;
    }

    return $parent_validation_response;
  }

  /**
   * Adds a new vsite member.
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
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function addNewMember(array &$form, FormStateInterface $form_state): AjaxResponse {
    $response = $this->submitForm($form, $form_state);

    if (!$form_state->getErrors()) {
      /** @var array $form_state_values */
      $form_state_values = $form_state->getValues();
      /** @var \Drupal\user\UserStorageInterface $user_storage */
      $user_storage = $this->entityTypeManager->getStorage('user');
      $email_key = CpUsersHelper::CP_USERS_NEW_USER;
      $role = $form_state_values['role'];

      /** @var \Drupal\user\UserInterface $account */
      $account = $user_storage->create([
        'field_first_name' => $form_state_values['first_name'],
        'field_last_name' => $form_state_values['last_name'],
        'name' => $form_state_values['username'],
        'mail' => $form_state_values['email'],
        'status' => TRUE,
      ]);

      $account->save();

      $this->activeVsite->addMember($account, [
        'group_roles' => [
          $role,
        ],
      ]);

      $this->mailManager->mail('cp_users', $email_key, $account->getEmail(), LanguageInterface::LANGCODE_DEFAULT, [
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

}
