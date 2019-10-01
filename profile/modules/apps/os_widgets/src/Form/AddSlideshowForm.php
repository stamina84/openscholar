<?php

namespace Drupal\os_widgets\Form;

use Drupal\block_content\BlockContentInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\vsite\Plugin\VsiteContextManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for creating Block content slideshow paragraph.
 */
class AddSlideshowForm extends ContentEntityForm {

  /**
   * Vsite Context Manager.
   *
   * @var \Drupal\vsite\Plugin\VsiteContextManagerInterface
   */
  protected $vsiteContextManager;
  protected $blockContent;
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'add_slideshow_form';
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info, TimeInterface $time, ModuleHandlerInterface $moduleHandler, EntityTypeManagerInterface $entity_type_manager, VsiteContextManagerInterface $vsite_context_manager) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
    $this->entityTypeManager = $entity_type_manager;
    $this->setEntity($this->entityTypeManager->getStorage('paragraph')->create([
      'type' => 'slideshow',
    ]));
    $this->setModuleHandler($moduleHandler);
    $this->vsiteContextManager = $vsite_context_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('module_handler'),
      $container->get('entity_type.manager'),
      $container->get('vsite.context_manager')
    );
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\block_content\BlockContentInterface $block_content
   *   Block content entity.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, BlockContentInterface $block_content = NULL) {
    $this->blockContent = $block_content;
    $form = parent::buildForm($form, $form_state);
    $form['actions']['submit']['#ajax'] = [
      'callback' => '::ajaxSubmit',
      'event' => 'click',
    ];

    return $form;
  }

  /**
   * Ajax form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response.
   */
  public function ajaxSubmit(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $response = new AjaxResponse();
    if ($form_state->hasAnyErrors()) {
      $response->addCommand(new ReplaceCommand('#drupal-modal--body .add-slideshow-form', $form));
    }
    else {
      $paragraph = $this->getEntity();
      $this->blockContent->field_slideshow->appendItem($paragraph);
      $this->blockContent->save();

      $instances = $this->blockContent->getInstances();
      $block = reset($instances);
      $block_markup = $this->entityTypeManager->getViewBuilder('block')->view($block);
      $response->addCommand(new ReplaceCommand('Section[data-quickedit-entity-id="block_content/' . $this->blockContent->id() . '"]', $block_markup));
      $response->addCommand(new CloseModalDialogCommand());
    }

    return $response;
  }

  /**
   * Checks access for access form.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   * @param \Drupal\block_content\BlockContentInterface $block_content
   *   Given block content.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function access(AccountInterface $account, BlockContentInterface $block_content) {
    if ($block_content->bundle() != 'slideshow') {
      return AccessResult::forbidden([$this->t('Given block content is not a slideshow.')]);
    }
    $group_contents = $this->entityTypeManager->getStorage('group_content')->loadByEntity($block_content);
    $group_content = array_shift($group_contents);
    $block_content_group = $group_content->getGroup();
    $group = $this->vsiteContextManager->getActiveVsite();
    if ($group && $group->id() != $block_content_group->id()) {
      return AccessResult::forbidden();
    }
    return AccessResult::allowed();
  }

}
