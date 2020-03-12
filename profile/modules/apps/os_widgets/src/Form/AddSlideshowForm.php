<?php

namespace Drupal\os_widgets\Form;

use Drupal\block_content\BlockContentInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
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

  /**
   * Block content.
   *
   * @var \Drupal\block_content\BlockContentInterface
   */
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
    if (!empty($form["field_slide_image"]["widget"][0]["#after_build"])) {
      $form["field_slide_image"]["widget"][0]["#after_build"][] = '_os_widgets_media_fields_after_build';
    }
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
      $this->blockContent->get('field_slideshow')->appendItem($paragraph);
      $this->blockContent->save();

      $instances = $this->blockContent->getInstances();
      $block = reset($instances);
      $block_markup = $this->entityTypeManager->getViewBuilder('block')->view($block);
      $response->addCommand(new ReplaceCommand('section[class*="block-' . Html::getId($block->id()) . '"]', $block_markup));
      $response->addCommand(new CloseModalDialogCommand());
    }

    return $response;
  }

}
