<?php

namespace Drupal\cp_users\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\vsite\Plugin\VsiteContextManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Url;

/**
 * Confirmation form for support vsite.
 */
class CpUserSupportConfirmForm extends ConfirmFormBase {

  /**
   * Vsite Context Manager.
   *
   * @var \Drupal\vsite\Plugin\VsiteContextManagerInterface
   */
  protected $vsiteContextManager;

  /**
   * Current User.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * User Account object.
   *
   * @var Drupal\user\UserInterface
   */
  protected $account;

  /**
   * EntityTypeManager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('vsite.context_manager'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Constructor.
   *
   * @param \Drupal\vsite\Plugin\VsiteContextManagerInterface $vsiteContextManager
   *   Vsite Context Manager Interface.
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   EntityTypeManager service.
   */
  public function __construct(VsiteContextManagerInterface $vsiteContextManager, EntityTypeManager $entityTypeManager) {
    $this->vsiteContextManager = $vsiteContextManager;
    $this->currentUser = $this->currentUser();
    $this->entityTypeManager = $entityTypeManager;
    $this->account = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $group = $this->vsiteContextManager->getActiveVsite();
    $confirm = $form_state->getValue('confirm');
    if ($confirm && $group) {
      $member = $group->getMember($this->account);
      if ($member) {
        $group->removeMember($this->account);
        $msg = $this->t('Successfully unsubscribed to @group', ['@group' => $group->label()]);
      }
      else {
        $group->addMember($this->account, [
          'group_roles' => [
            'personal-support_user',
          ],
        ]);
        $msg = $this->t('Successfully subscribed to @group', ['@group' => $group->label()]);
      }
    }

    $this->messenger()->addMessage($msg);
    // Redirect to vsite landing page.
    $form_state->setRedirectUrl(new Url('<front>'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() : string {
    return "confirm_support_form";
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('<front>');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this
      ->t('Click confirm to subscribe/unsubscribe this website');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    // Get current vsite group.
    $group = $this->vsiteContextManager->getActiveVsite();
    $group_name = $group->get('label')->value;
    $member = $group->getMember($this->currentUser);
    if ($member) {
      $question = $this->t('Are you sure you want to unsubscribe this website @group', ['@group' => $group_name]);
    }
    else {
      $question = $this->t('Are you sure you want to subscribe this website @group', ['@group' => $group_name]);
    }

    return $question;
  }

}
