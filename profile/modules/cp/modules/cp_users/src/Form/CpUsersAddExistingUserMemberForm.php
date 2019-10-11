<?php

namespace Drupal\cp_users\Form;

use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Url;
use Drupal\cp_users\CpUsersHelper;

/**
 * Form for adding existing user as vsite member.
 */
class CpUsersAddExistingUserMemberForm extends CpUsersAddMemberFormBase {

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
      '#required' => TRUE,
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

}
