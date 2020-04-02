<?php

namespace Drupal\os_software\Plugin\App;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\vsite\Plugin\AppPluginBase;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\cp_import\AppImportFactory;
use Drupal\cp_import\Helper\CpImportHelper;
use Drupal\migrate\Plugin\MigrationPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Software app.
 *
 * @App(
 *   title = @Translation("Software"),
 *   canDisable = true,
 *   entityType = "node",
 *   bundle = {
 *     "software_project",
 *     "software_release"
 *   },
 *   viewsTabs = {
 *     "os_software_projects" = {
 *       "page_1",
 *     },
 *     "os_software_releases" = {
 *       "page_1",
 *     },
 *   },
 *   id = "software",
 *   contextualRoute = "view.os_software_projects.page_1",
 *   cpImportId = "os_software_import",
 *   cpImportFilePath = "public://importcsv/os_software.csv"
 * )
 */
class SoftwareApp extends AppPluginBase {
  use StringTranslationTrait;

  /**
   * Title for Software Project link.
   */
  const TITLE_PROJECT = 'Software Project';

  /**
   * Title for Software Release link.
   */
  const TITLE_RELEASE = 'Software Release';

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
    $softwareImport = $this->appImportFactory->create($definition['cpImportId']);

    // Validate Headers.
    if ($missing = $softwareImport->validateHeaders($data)) {
      $headerMessage = $this->t('The following Header/columns are missing:<ul> @Title @Body @Files @Created date @Path</ul></br> The structure of your CSV file probably needs to be updated. Please download the template again.', $missing);
      $this->messenger->addError($headerMessage);
      $formState->setError($form['import_file']);
      return;
    }

    // Validate rows.
    if ($message = $softwareImport->validateRows($data)) {
      $formState->setError($form['import_file']);
      $this->messenger->addError($this->t('@title @file @body @date', $message));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCreateLinks() {
    return [
      'software-project' => [
        'menu_name' => 'control-panel',
        'route_name' => 'node.add',
        'route_parameters' => ['node_type' => 'software_project'],
        'parent' => 'cp.content.add',
        'title' => $this->t('@title', ['@title' => self::TITLE_PROJECT]),
      ],
      'software-release' => [
        'menu_name' => 'control-panel',
        'route_name' => 'node.add',
        'route_parameters' => ['node_type' => 'software_release'],
        'parent' => 'cp.content.add',
        'title' => $this->t('@title', ['@title' => self::TITLE_RELEASE]),
      ],
      'software-project-import' => [
        'menu_name' => 'control-panel',
        'route_name' => 'cp.content.import',
        'route_parameters' => ['app_name' => $this->getPluginId()],
        'parent' => 'cp.content.import.collection',
        'title' => $this->getTitle(),
      ],
    ];
  }

}
