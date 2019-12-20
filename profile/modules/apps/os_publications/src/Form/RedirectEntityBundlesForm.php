<?php

namespace Drupal\os_publications\Form;

use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a default form.
 */
class RedirectEntityBundlesForm extends FormBase {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * Constructs a new EntityController.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'redirect_entity_bundles_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $entity_type_id = 'bibcite_reference';

    $form['bundle_url'] = [
      '#type' => 'select',
      '#options' => [],
      '#ajax' => [
        'callback' => [$this, 'ajaxBundleRedirect'],
        'event' => 'change',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Loading form ...'),
        ],
      ],
      '#empty_option' => $this->t('- Select -'),
    ];
    // Filter out the bundles the user doesn't have access to.
    $access_control_handler = $this->entityTypeManager->getAccessControlHandler($entity_type_id);
    $bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id);
    foreach ($bundles as $bundle_name => $bundle_info) {
      $access = $access_control_handler->createAccess($bundle_name, NULL, [], TRUE);
      if (!$access->isAllowed()) {
        continue;
      }
      $link_route_name = 'entity.' . $entity_type_id . '.add_form';
      $url = Url::fromRoute($link_route_name, ['bibcite_reference_type' => $bundle_name])->toString();
      $form['bundle_url']['#options'][$url] = $bundle_info['label'];
    }

    // Create the submit button.
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#attributes' => [
        'class' => [
          'js-hide',
        ],
      ],
    ];

    return $form;
  }

  /**
   * Ajax callback, handle redirect immediately.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return mixed
   *   Ajax response with redirect.
   */
  public function ajaxBundleRedirect(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new RedirectCommand($form_state->getValue('bundle_url')));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $url = $form_state->getValue('bundle_url');
    $form_state->setRedirectUrl(Url::fromUserInput($url));
  }

}
