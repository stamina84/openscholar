<?php

namespace Drupal\os_profiles\Plugin\App;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\cp_import\AppImportFactory;
use Drupal\cp_import\Helper\CpImportHelper;
use Drupal\migrate\Plugin\MigrationPluginManager;
use Drupal\vsite\Plugin\AppPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Profiles app.
 *
 * @App(
 *   title = @Translation("Person"),
 *   canDisable = true,
 *   entityType = "node",
 *   bundle = {
 *    "person"
 *   },
 *   viewsTabs = {
 *     "people" = {
 *       "page_1",
 *     },
 *   },
 *   id = "profiles",
 *   contextualRoute = "view.people.page_1",
 *   cpImportId = "os_profiles_import",
 *   cpImportFilePath = "public://importcsv/os_profiles.csv"
 * )
 */
class ProfilesApp extends AppPluginBase {

  /**
   * App Import factory service.
   *
   * @var \Drupal\cp_import\AppImportFactory
   */
  protected $appImportFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationPluginManager $migrationPluginManager, CpImportHelper $cpImportHelper, Messenger $messenger, EntityTypeManager $entityTypeManager, AppImportFactory $appImportFactory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migrationPluginManager, $cpImportHelper, $messenger, $entityTypeManager);
    $this->appImportFactory = $appImportFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.migration'),
      $container->get('cp_import.helper'),
      $container->get('messenger'),
      $container->get('entity_type.manager'),
      $container->get('app_import_factory')
    );
  }

  /**
   * Validates import file and shows errors row wise.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The Form State.
   */
  public function validateImportSource(array $form, FormStateInterface $formState) {
    $data = parent::validateImportSource($form, $formState);
    if (!$data) {
      return;
    }

    $definition = $this->getPluginDefinition();
    $faqImport = $this->appImportFactory->create($definition['cpImportId']);

    // Validate Headers.
    if ($missing = $faqImport->validateHeaders($data)) {
      $headerMessage = $this->t('The following Header/columns are missing:<ul> @First name @Last name @Photo @Created date @Path</ul></br> The structure of your CSV file probably needs to be updated. Please download the template again.', $missing);
      $this->messenger->addError($headerMessage);
      $formState->setError($form['import_file']);
      return;
    }

    // Validate rows.
    if ($message = $faqImport->validateRows($data)) {
      $formState->setError($form['import_file']);
      $this->messenger->addError($this->t('@summary @firstNameRows @lastNameRows @photo @email @date @website1Rows @website2Rows @website3Rows', $message));
    }
  }

}
