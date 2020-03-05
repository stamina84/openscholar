<?php

namespace Drupal\Tests\cp_import\ExistingSite;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\cp_import\AppImport\os_news_import\AppImport;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate_tools\MigrateExecutable;
use Drupal\Tests\openscholar\ExistingSite\OsExistingSiteTestBase;

/**
 * Class CpImportNewsTest.
 *
 * @group kernel
 * @group cp-1
 */
class CpImportNewsTest extends OsExistingSiteTestBase {

  /**
   * CpImport helper service.
   *
   * @var \Drupal\cp_import\Helper\CpImportHelper
   */
  protected $cpImportHelper;

  /**
   * Migration manager.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManager
   */
  protected $migrationManager;

  /**
   * Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Cp Import access checker.
   *
   * @var \Drupal\cp_import\Access\CpImportAccessCheck
   */
  protected $cpImportAccessChecker;

  /**
   * File system service.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  protected $fileSystem;

  /**
   * Group member.
   *
   * @var \Drupal\user\Entity\User|false
   */
  protected $groupMember;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->vsiteContextManager->activateVsite($this->group);
    $this->cpImportHelper = $this->container->get('cp_import.helper');
    $this->migrationManager = $this->container->get('plugin.manager.migration');
    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->fileSystem = $this->container->get('file_system');

    // Setup role.
    $group_role = $this->createRoleForGroup($this->group);
    $group_role->grantPermissions([
      'create group_node:news entity',
    ])->save();

    // Setup user.
    $this->groupMember = $this->createUser();
    $this->group->addMember($this->groupMember, [
      'group_roles' => [
        $group_role->id(),
      ],
    ]);

    $this->drupalLogin($this->groupMember);
  }

  /**
   * Tests CpImport News AppImport factory.
   */
  public function testCpImportNewsAppImportFactory() {
    /** @var \Drupal\cp_import\AppImportFactory $appImportFactory */
    $appImportFactory = $this->container->get('app_import_factory');
    $instance = $appImportFactory->create('os_news_import');
    $this->assertInstanceOf(AppImport::class, $instance);
  }

  /**
   * Tests CpImport News header validations.
   */
  public function testCpImportNewsHeaderValidation() {
    /** @var \Drupal\cp_import\AppImportFactory $appImportFactory */
    $appImportFactory = $this->container->get('app_import_factory');
    $instance = $appImportFactory->create('os_news_import');

    // Test header errors.
    $data[0] = [
      'Title' => 'Test1',
      'Body' => 'Body1',
      'Redirect' => '',
      'Image' => '',
      'Files' => 'https://www.harvard.edu/sites/default/files/content/Review_Committee_Report_20181113.pdf',
      'Created date' => '01/15/2015',
      'Path' => 'news-1',
    ];
    $message = $instance->validateHeaders($data);
    $this->assertEmpty($message['@Title']);
    $this->assertInstanceOf(TranslatableMarkup::class, $message['@Date']);

    // Test No header errors.
    $data[0] = [
      'Title' => 'Test1',
      'Date' => '2015-01-01',
      'Body' => 'Body1',
      'Redirect' => '',
      'Image' => '',
      'Files' => 'https://www.harvard.edu/sites/default/files/content/Review_Committee_Report_20181113.pdf',
      'Created date' => '01/15/2015',
      'Path' => 'news-1',
    ];
    $message = $instance->validateHeaders($data);
    $this->assertEmpty($message);
  }

  /**
   * Tests CpImport News row validations.
   */
  public function testCpImportNewsRowValidation() {
    /** @var \Drupal\cp_import\AppImportFactory $appImportFactory */
    $appImportFactory = $this->container->get('app_import_factory');
    $instance = $appImportFactory->create('os_news_import');

    // Test Title errors.
    $data[0] = [
      'Title' => '',
      'Date' => '2015-01-01',
      'Body' => 'Body1',
      'Redirect' => '',
      'Image' => '',
      'Files' => 'https://www.harvard.edu/sites/default/files/content/Review_Committee_Report_20181113.pdf',
      'Created date' => '01/15/2015',
      'Path' => 'news-1',
    ];
    $message = $instance->validateRows($data);
    $this->assertEmpty($message['@news_date']);
    $this->assertInstanceOf(TranslatableMarkup::class, $message['@title']);

    // Test Date field errors.
    $data[0] = [
      'Title' => 'News Title',
      'Date' => '',
      'Body' => 'Body1',
      'Redirect' => '',
      'Image' => '',
      'Files' => 'https://www.harvard.edu/sites/default/files/content/Review_Committee_Report_20181113.pdf',
      'Created date' => '01/15/2015',
      'Path' => 'news-1',
    ];
    $message = $instance->validateRows($data);
    $this->assertEmpty($message['@title']);
    $this->assertInstanceOf(TranslatableMarkup::class, $message['@news_date']);

    // Test no errors in row.
    $data[0] = [
      'Title' => 'News Title',
      'Date' => '2015-01-01',
      'Body' => 'Body1',
      'Redirect' => '',
      'Image' => '',
      'Files' => 'https://www.harvard.edu/sites/default/files/content/Review_Committee_Report_20181113.pdf',
      'Created date' => '01/15/2015',
      'Path' => 'news-1',
    ];
    $message = $instance->validateRows($data);
    $this->assertEmpty($message);
  }

  /**
   * Tests Migration/import for os_news_import.
   *
   * @throws \Drupal\migrate\MigrateException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function testCpImportMigrationNews() {
    $filename = drupal_get_path('module', 'cp_import_csv_test') . '/artifacts/news.csv';
    $this->cpImportHelper->csvToArray($filename, 'utf-8');
    // Replace existing source file.
    $path = 'public://importcsv';
    $this->fileSystem->delete($path . '/os_news.csv');
    $this->fileSystem->prepareDirectory($path, FileSystemInterface::CREATE_DIRECTORY);
    file_save_data(file_get_contents($filename), $path . '/os_news.csv', FileSystemInterface::EXISTS_REPLACE);

    $storage = $this->entityTypeManager->getStorage('node');
    // Test Negative case.
    $node1 = $storage->loadByProperties(['title' => 'Os Title1']);
    $this->assertCount(0, $node1);
    $node2 = $storage->loadByProperties(['title' => 'Os Title2']);
    $this->assertCount(0, $node2);

    /** @var \Drupal\migrate\Plugin\Migration $migration */
    $migration = $this->migrationManager->createInstance('os_news_import');
    $executable = new MigrateExecutable($migration, new MigrateMessage());
    $executable->import();

    // Test positive case.
    $node1 = $storage->loadByProperties(['title' => 'Os Title1']);
    $this->assertCount(1, $node1);
    $node2 = $storage->loadByProperties(['title' => 'Os Title2']);
    $this->assertCount(1, $node2);

    // Import similar content again.
    $filename2 = drupal_get_path('module', 'cp_import_csv_test') . '/artifacts/news_small.csv';
    $this->cpImportHelper->csvToArray($filename2, 'utf-8');
    $this->fileSystem->delete($path . '/os_news.csv');
    $this->fileSystem->prepareDirectory($path, FileSystemInterface::CREATE_DIRECTORY);
    file_save_data(file_get_contents($filename2), $path . '/os_news.csv', FileSystemInterface::EXISTS_REPLACE);
    $migration = $this->migrationManager->createInstance('os_news_import');
    $executable = new MigrateExecutable($migration, new MigrateMessage());
    $executable->import();
    // If count is now 2 it means same content is imported again.
    $node1 = $storage->loadByProperties(['title' => 'Os Title1']);
    $this->assertCount(2, $node1);

    $executable->rollback();
  }

}
