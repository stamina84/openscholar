<?php

namespace Drupal\vsite_preset\Entity\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\os_app_access\AppLoader;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class GroupPresetForm.
 *
 * @package Drupal\vsite_preset\Entity\Form
 */
class GroupPresetForm extends EntityForm {

  /**
   * The entity being operated on.
   *
   * @var \Drupal\vsite_preset\Entity\GroupPresetInterface
   */
  protected $entity;

  /**
   * App Loader.
   *
   * @var \Drupal\os_app_access\AppLoader
   */
  protected $appLoader;

  /**
   * Constructor for the form.
   *
   * @param \Drupal\os_app_access\AppLoader $appLoader
   *   AppLoader instance.
   */
  public function __construct(AppLoader $appLoader) {
    $this->appLoader = $appLoader;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('os_app_access.app_loader')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $this->entity->label(),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Id'),
      '#default_value' => $this->entity->id(),
      '#machine_name' => [
        'exists' => '\\Drupal\\vsite_preset\\Entity\\GroupPreset::load',
      ],
      // '#disabled' => true.
    ];

    $form['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#default_value' => $this->entity->get('description'),
    ];

    $applicableToOptions = [];
    /** @var \Drupal\group\Entity\GroupTypeInterface[] $group_types */
    $group_types = $this->entityTypeManager->getStorage('group_type')->loadMultiple();
    foreach ($group_types as $gt) {
      $applicableToOptions[$gt->id()] = $gt->label();
    }

    $app_list = [];
    $app_definition = $this->appLoader->getAppsForUser($this->currentUser());

    foreach ($app_definition as $app) {
      $app_list[$app['id']] = $app['title'];
    }
    $form['applicableTo'] = [
      '#type' => 'select',
      '#title' => $this->t('Applies To'),
      '#multiple' => TRUE,
      '#options' => $applicableToOptions,
      '#default_value' => $this->entity->get('applicableTo'),
      '#description' => $this->t('Select what group types can use this preset.'),
      '#required' => TRUE,
    ];

    $form['enabledApps'] = [
      '#type' => 'select',
      '#title' => $this->t('Enabled Apps'),
      '#multiple' => TRUE,
      '#options' => $app_list,
      '#default_value' => $this->entity->get('enabledApps'),
      '#description' => $this->t('Select apps to be enabled by default for this preset.'),
      '#required' => TRUE,
    ];

    $form['privateApps'] = [
      '#type' => 'select',
      '#title' => $this->t('Private Apps'),
      '#multiple' => TRUE,
      '#options' => $app_list,
      '#default_value' => $this->entity->get('privateApps'),
      '#description' => $this->t('Select apps to be set as private by default for this preset.'),
    ];

    $this->getRedirectDestination()->set(Url::fromRoute('entity.group_preset.collection')->toString());
    return $form;
  }

}
