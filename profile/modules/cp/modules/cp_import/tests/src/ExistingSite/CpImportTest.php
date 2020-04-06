<?php

namespace Drupal\Tests\cp_import\ExistingSite;

use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultForbidden;
use Drupal\Core\Access\AccessResultNeutral;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\cp_import\AppImport\os_faq_import\AppImport;
use Drupal\cp_import\AppImport\os_blog_import\AppImport as BlogAppImport;
use Drupal\cp_import\AppImport\os_software_import\AppImport as SoftwareAppImport;
use Drupal\media\Entity\Media;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate_tools\MigrateExecutable;
use Drupal\os_app_access\AppAccessLevels;
use Drupal\Tests\openscholar\ExistingSite\OsExistingSiteTestBase;
use Drupal\Tests\openscholar\Traits\ExistingSiteTestTrait;

/**
 * Class CpImportTest.
 *
 * @group kernel
 * @group cp-1
 */
class CpImportTest extends OsExistingSiteTestBase {

  use ExistingSiteTestTrait;

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
    $this->groupMember = $this->createUser();
    $this->drupalLogin($this->groupMember);
  }

  /**
   * Tests Cp import helper media creation.
   */
  public function testCpImportHelperMediaCreation() {
    // Test Media creation with File.
    $url = 'https://www.harvard.edu/sites/default/files/content/Review_Committee_Report_20181113.pdf';
    $media1 = $this->cpImportHelper->getMedia($url, 'faq', 'field_attached_media');
    $this->assertInstanceOf(Media::class, $media1);
    $this->assertEquals('Review_Committee_Report_20181113.pdf', $media1->getName());
    $this->markEntityForCleanup($media1);

    // Test Negative case for Media creation of Oembed type.
    $url = 'https://www.youtube.com/watch?v=WadTyp3FcgU&t';
    $media2 = $this->cpImportHelper->getMedia($url, 'faq', 'field_attached_media');
    $this->assertNull($media2);
  }

  /**
   * Tests Add to vsite.
   */
  public function testCpImportHelperAddToVsite() {
    $node = $this->createNode([
      'title' => 'Test',
      'type' => 'faq',
    ]);
    // Test No vsite.
    $vsite = $this->vsiteContextManager->getActiveVsite();
    $content = $vsite->getContentByEntityId('group_node:faq', $node->id());
    $this->assertCount(0, $content);

    // Call helper method and check again. Test vsite.
    $this->cpImportHelper->addContentToVsite($node->id(), 'group_node:faq', 'node');
    $content = $vsite->getContentByEntityId('group_node:faq', $node->id());
    $this->assertCount(1, $content);
  }

  /**
   * Tests Csv to Array conversion.
   */
  public function testCpImportHelperCsvToArray() {
    $filename = drupal_get_path('module', 'cp_import_csv_test') . '/artifacts/faq.csv';
    $data = $this->cpImportHelper->csvToArray($filename, 'utf-8');
    $this->assertCount(10, $data);
    $this->assertEquals('Some Question 5', $data[4]['Title']);
    $this->assertEquals('01/20/2014', $data[9]['Created date']);
  }

  /**
   * Tests if aliases get updated when needed.
   */
  public function testCpImportHandlePath() {
    $node = $this->createNode([
      'title' => 'Test Faq',
      'type' => 'faq',
      'path' => 'test-faq',
    ]);
    $this->group->addContent($node, 'group_node:faq');
    // Update alias.
    $nid = $node->id();
    $this->cpImportHelper->handleContentPath($node->getEntityTypeId(), $nid);
    /** @var \Drupal\Core\Path\AliasStorage $pathStorage */
    $pathStorage = $this->container->get('path.alias_storage');
    $new_alias = $pathStorage->lookupPathAlias("/node/$nid", $node->language()->getId());
    // Assert alias gets updated.
    $this->assertNotEquals('test-faq', $new_alias);
  }

  /**
   * Tests CpImport AppImport factory.
   */
  public function testCpImportAppImportFactory() {
    /** @var \Drupal\cp_import\AppImportFactory $appImportFactory */
    $appImportFactory = $this->container->get('app_import_factory');
    $instance = $appImportFactory->create('os_faq_import');
    $this->assertInstanceOf(AppImport::class, $instance);
  }

  /**
   * Tests CpImport header validations.
   */
  public function testCpImportFaqHeaderValidation() {
    /** @var \Drupal\cp_import\AppImportFactory $appImportFactory */
    $appImportFactory = $this->container->get('app_import_factory');
    $instance = $appImportFactory->create('os_faq_import');

    // Test header errors.
    $data[0] = [
      'Title' => 'Test1',
      'Body' => 'Body1',
      'Files' => '',
      'Created date' => '01/01/2015',
    ];
    $message = $instance->validateHeaders($data);
    $this->assertEmpty($message['@Title']);
    $this->assertInstanceOf(TranslatableMarkup::class, $message['@Path']);

    // Test No header errors.
    $data[0] = [
      'Title' => 'Test1',
      'Body' => 'Body1',
      'Files' => 'https://www.harvard.edu/sites/default/files/content/Review_Committee_Report_20181113.pdf',
      'Created date' => '01/01/2015',
      'Path' => 'test1',
    ];
    $message = $instance->validateHeaders($data);
    $this->assertEmpty($message);
  }

  /**
   * Tests CpImport row validations.
   */
  public function testCpImportFaqRowValidation() {
    /** @var \Drupal\cp_import\AppImportFactory $appImportFactory */
    $appImportFactory = $this->container->get('app_import_factory');
    $instance = $appImportFactory->create('os_faq_import');

    // Test errors.
    $data[0] = [
      'Title' => '',
      'Body' => 'Body1',
      'Files' => '',
      'Created date' => '01/01/2015',
      'Path' => 'test1',
    ];
    $message = $instance->validateRows($data);
    $this->assertEmpty($message['@date']);
    $this->assertInstanceOf(TranslatableMarkup::class, $message['@title']);

    // Test body field errors.
    $data[0] = [
      'Title' => 'Title1',
      'Body' => '',
      'Files' => '',
      'Created date' => '01/01/2015',
      'Path' => 'test1',
    ];
    $message = $instance->validateRows($data);
    $this->assertEmpty($message['@title']);
    $this->assertInstanceOf(TranslatableMarkup::class, $message['@body']);

    // Test no errors in row.
    $data[0] = [
      'Title' => 'Test1',
      'Body' => 'Body1',
      'Files' => '',
      'Created date' => '01/01/2015',
      'Path' => 'test1',
    ];
    $message = $instance->validateRows($data);
    $this->assertEmpty($message);
  }

  /**
   * Checks access for Faq and publication import.
   */
  public function testCpImportAccessChecker() {
    $this->cpImportAccessChecker = $this->container->get('cp_import_access.check');
    $levels = $this->configFactory->getEditable('os_app_access.access');

    // Setup role.
    $group_role = $this->createRoleForGroup($this->group);
    $group_role->grantPermissions([
      'create group_node:faq entity',
      'create group_entity:bibcite_reference entity',
    ])->save();

    // Setup user.
    $member = $this->createUser();
    $this->group->addMember($member, [
      'group_roles' => [
        $group_role->id(),
      ],
    ]);

    // Perform tests.
    $this->drupalLogin($member);

    $levels->set('faq', AppAccessLevels::PUBLIC)->save();
    $result = $this->cpImportAccessChecker->access($this->groupMember, 'faq');
    $this->assertInstanceOf(AccessResultAllowed::class, $result);

    $levels->set('publications', AppAccessLevels::PUBLIC)->save();
    $result = $this->cpImportAccessChecker->access($this->groupMember, 'publications');
    $this->assertInstanceOf(AccessResultAllowed::class, $result);

    // App access level is Disabled and user has create access.
    $levels->set('faq', AppAccessLevels::DISABLED)->save();
    $result = $this->cpImportAccessChecker->access($this->groupMember, 'faq');
    $this->assertInstanceOf(AccessResultForbidden::class, $result);

    $levels->set('publications', AppAccessLevels::DISABLED)->save();
    $result = $this->cpImportAccessChecker->access($this->groupMember, 'publications');
    $this->assertInstanceOf(AccessResultForbidden::class, $result);

    // Update role.
    $group_role->revokePermissions([
      'create group_node:faq entity',
      'create group_entity:bibcite_reference entity',
    ])->save();

    // Setup user.
    $member = $this->createUser();
    $this->group->addMember($member, [
      'group_roles' => [
        $group_role->id(),
      ],
    ]);

    // Perform tests.
    $this->drupalLogin($member);

    // App access level is Public and user does not have create access.
    $levels->set('faq', AppAccessLevels::PUBLIC)->save();
    $result = $this->cpImportAccessChecker->access($this->groupMember, 'faq');
    $this->assertInstanceOf(AccessResultNeutral::class, $result);

    $levels->set('publications', AppAccessLevels::PUBLIC)->save();
    $result = $this->cpImportAccessChecker->access($this->groupMember, 'publications');
    $this->assertInstanceOf(AccessResultNeutral::class, $result);

    // App access level is Disabled and user does not have create access.
    $levels->set('faq', AppAccessLevels::DISABLED)->save();
    $result = $this->cpImportAccessChecker->access($this->groupMember, 'faq');
    $this->assertInstanceOf(AccessResultForbidden::class, $result);

    $levels->set('faq', AppAccessLevels::DISABLED)->save();
    $result = $this->cpImportAccessChecker->access($this->groupMember, 'faq');
    $this->assertInstanceOf(AccessResultForbidden::class, $result);
  }

  /**
   * Tests Migration/import for os_faq_import.
   *
   * @throws \Drupal\migrate\MigrateException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function testCpImportMigrationFaq() {
    $filename = drupal_get_path('module', 'cp_import_csv_test') . '/artifacts/faq.csv';
    // Replace existing source file.
    $path = 'public://importcsv';
    $this->fileSystem->delete($path . '/os_faq.csv');
    $this->fileSystem->prepareDirectory($path, FileSystemInterface::CREATE_DIRECTORY);
    file_save_data(file_get_contents($filename), $path . '/os_faq.csv', FileSystemInterface::EXISTS_REPLACE);

    $storage = $this->entityTypeManager->getStorage('node');
    // Test Negative case.
    $node1 = $storage->loadByProperties(['title' => 'Some Question 10']);
    $this->assertCount(0, $node1);
    $node2 = $storage->loadByProperties(['title' => 'Some Question 2']);
    $this->assertCount(0, $node2);

    /** @var \Drupal\migrate\Plugin\Migration $migration */
    $migration = $this->migrationManager->createInstance('os_faq_import');
    $executable = new MigrateExecutable($migration, new MigrateMessage());
    $executable->import();

    // Test positive case.
    $node1 = $storage->loadByProperties(['title' => 'Some Question 10']);
    $this->assertCount(1, $node1);
    $node2 = $storage->loadByProperties(['title' => 'Some Question 2']);
    $this->assertCount(1, $node2);

    // Test date is converted from Y-n-j to Y-m-d if node is created
    // successfully it means conversion works.
    $node3 = $storage->loadByProperties(['title' => 'Some Question 6']);
    $this->assertCount(1, $node3);

    // Test if no date was given time is not set to 0.
    $node5 = $node3 = $storage->loadByProperties(['title' => 'Some Question 5']);
    $node5 = array_values($node5)[0];
    $this->assertNotEquals(0, $node5->getCreatedTime());

    // Tests event calls helper to add content to vsite.
    $vsite = $this->vsiteContextManager->getActiveVsite();
    reset($node3);
    $id = key($node3);
    $content = $vsite->getContentByEntityId('group_node:faq', $id);
    $this->assertCount(1, $content);

    // Import same content again to check if adding timestamp works.
    $filename2 = drupal_get_path('module', 'cp_import_csv_test') . '/artifacts/faq_small.csv';
    $this->cpImportHelper->csvToArray($filename2, 'utf-8');
    $this->fileSystem->delete($path . '/os_faq.csv');
    $this->fileSystem->prepareDirectory($path, FileSystemInterface::CREATE_DIRECTORY);
    file_save_data(file_get_contents($filename2), $path . '/os_faq.csv', FileSystemInterface::EXISTS_REPLACE);
    $migration = $this->migrationManager->createInstance('os_faq_import');
    $executable = new MigrateExecutable($migration, new MigrateMessage());
    $executable->import();
    // If count is now 2 it means same content is imported again.
    $node2 = $storage->loadByProperties(['title' => 'Some Question 2']);
    $this->assertCount(2, $node2);

    // Delete all the test data created.
    $executable->rollback();
  }

  /**
   * Tests CpImport Blog AppImport factory.
   */
  public function testCpImportBlogAppImportFactory() {
    /** @var \Drupal\cp_import\AppImportFactory $appImportFactory */
    $appImportFactory = $this->container->get('app_import_factory');
    $instance = $appImportFactory->create('os_blog_import');
    $this->assertInstanceOf(BlogAppImport::class, $instance);
  }

  /**
   * Tests CpImport Blog header validations.
   */
  public function testCpImportBlogHeaderValidation() {
    /** @var \Drupal\cp_import\AppImportFactory $appImportFactory */
    $appImportFactory = $this->container->get('app_import_factory');
    $instance = $appImportFactory->create('os_blog_import');

    // Test header errors.
    $data[0] = [
      'Title' => 'Blog1',
      'Body' => 'Blog1 Test Body',
      'Files' => '',
      'Created date' => '01/01/2015',
    ];
    $message = $instance->validateHeaders($data);
    $this->assertEmpty($message['@Title']);
    $this->assertInstanceOf(TranslatableMarkup::class, $message['@Path']);

    // Test No header errors.
    $data[0] = [
      'Title' => 'Blog2',
      'Body' => 'Body2 Test Body',
      'Files' => 'https://www.harvard.edu/sites/default/files/content/Review_Committee_Report_20181113.pdf',
      'Created date' => '01/01/2015',
      'Path' => 'test1',
    ];
    $message = $instance->validateHeaders($data);
    $this->assertEmpty($message);
  }

  /**
   * Tests CpImport Blog row validations.
   */
  public function testCpImportBlogRowValidation() {
    /** @var \Drupal\cp_import\AppImportFactory $appImportFactory */
    $appImportFactory = $this->container->get('app_import_factory');
    $instance = $appImportFactory->create('os_blog_import');

    // Test Single Row error.
    $data[0] = [
      'Title' => '',
      'Body' => 'Body1',
      'Files' => '',
      'Created date' => '01/01/2015',
      'Path' => 'test1',
    ];
    $message = $instance->validateRows($data);
    $this->assertEmpty($message['@date']);
    $this->assertInstanceOf(TranslatableMarkup::class, $message['@title']);
    $this->assertContains('Row 1: The Title is required.', $message['@title']->__toString());
    $this->assertInstanceOf(TranslatableMarkup::class, $message['@summary']);
    $this->assertContains('The Import file has 1 error(s).', $message['@summary']->__toString());

    // Test Multiple error messages.
    $data[0] = [
      'Title' => '',
      'Body' => 'Body1',
      'Files' => '',
      'Created date' => '01/01/2015',
      'Path' => 'test1',
    ];
    $data[1] = [
      'Title' => '',
      'Body' => 'Body1',
      'Files' => '',
      'Created date' => '33/01/2015',
      'Path' => 'test1',
    ];
    $message = $instance->validateRows($data);
    $this->assertInstanceOf(TranslatableMarkup::class, $message['@title']);
    $this->assertContains('<a data-toggle="tooltip" title="Rows: 1,2">2 Rows</a>: The Title is required.', $message['@title']->__toString());
    $this->assertInstanceOf(TranslatableMarkup::class, $message['@date']);
    $this->assertContains('Row 2: Created date format is invalid.', $message['@date']->__toString());
    $this->assertInstanceOf(TranslatableMarkup::class, $message['@summary']);
    $this->assertContains('The Import file has 3 error(s).', $message['@summary']->__toString());

    // Test no errors in row.
    $data = [];
    $data[0] = [
      'Title' => 'Blog1',
      'Body' => 'Body1',
      'Files' => '',
      'Created date' => '01/01/2015',
      'Path' => 'test1',
    ];
    $message = $instance->validateRows($data);
    $this->assertEmpty($message);
  }

  /**
   * Tests Migration/import for os_blog_import.
   *
   * @throws \Drupal\migrate\MigrateException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function testCpImportMigrationBlog() {
    $filename = drupal_get_path('module', 'cp_import_csv_test') . '/artifacts/blog.csv';
    // Replace existing source file.
    $path = 'public://importcsv';
    $this->cpImportHelper->csvToArray($filename, 'utf-8');
    $this->fileSystem->delete($path . '/os_blog.csv');
    $this->fileSystem->prepareDirectory($path, FileSystemInterface::CREATE_DIRECTORY);
    file_save_data(file_get_contents($filename), $path . '/os_blog.csv', FileSystemInterface::EXISTS_REPLACE);

    $storage = $this->entityTypeManager->getStorage('node');
    // Test Negative case.
    $node1 = $storage->loadByProperties(['title' => 'Test Blog 1']);
    $this->assertCount(0, $node1);
    $node2 = $storage->loadByProperties(['title' => 'Test Blog 8']);
    $this->assertCount(0, $node2);

    /** @var \Drupal\migrate\Plugin\Migration $migration */
    $migration = $this->migrationManager->createInstance('os_blog_import');
    $executable = new MigrateExecutable($migration, new MigrateMessage());
    $executable->import();

    // Test positive case.
    $node1 = $storage->loadByProperties(['title' => 'Test Blog 1']);
    $this->assertCount(1, $node1);
    $node2 = $storage->loadByProperties(['title' => 'Test Blog 3']);
    $this->assertCount(1, $node2);

    // Import same content again to check if adding timestamp works.
    $filename2 = drupal_get_path('module', 'cp_import_csv_test') . '/artifacts/blog_small.csv';
    $this->cpImportHelper->csvToArray($filename2, 'utf-8');
    $this->fileSystem->delete($path . '/os_blog.csv');
    $this->fileSystem->prepareDirectory($path, FileSystemInterface::CREATE_DIRECTORY);
    file_save_data(file_get_contents($filename2), $path . '/os_blog.csv', FileSystemInterface::EXISTS_REPLACE);
    $migration = $this->migrationManager->createInstance('os_blog_import');
    $executable = new MigrateExecutable($migration, new MigrateMessage());
    $executable->import();
    // If count is now 2 it means same content is imported again.
    $node1 = $storage->loadByProperties(['title' => 'Test Blog 1']);
    $this->assertCount(2, $node1);

    // Delete all the test data created.
    $executable->rollback();
  }

  /**
   * Tests CpImport Software AppImport factory.
   */
  public function testCpImportSoftwareAppImportFactory() {
    /** @var \Drupal\cp_import\AppImportFactory $appImportFactory */
    $appImportFactory = $this->container->get('app_import_factory');
    $instance = $appImportFactory->create('os_software_import');
    $this->assertInstanceOf(SoftwareAppImport::class, $instance);
  }

  /**
   * Tests CpImport Software header validations.
   */
  public function testCpImportSoftwareHeaderValidation() {
    /** @var \Drupal\cp_import\AppImportFactory $appImportFactory */
    $appImportFactory = $this->container->get('app_import_factory');
    $instance = $appImportFactory->create('os_software_import');

    // Test header errors.
    $data[0] = [
      'Title' => 'Software1',
      'Body' => 'Software1 Test Body',
      'Files' => '',
      'Created date' => '01/01/2015',
    ];
    $message = $instance->validateHeaders($data);
    $this->assertEmpty($message['@Title']);
    $this->assertInstanceOf(TranslatableMarkup::class, $message['@Path']);

    // Test No header errors.
    $data[0] = [
      'Title' => 'Software1',
      'Body' => 'Body2 Test Body',
      'Files' => 'https://www.harvard.edu/sites/default/files/content/Review_Committee_Report_20181113.pdf',
      'Created date' => '01/01/2015',
      'Path' => 'test1',
    ];
    $message = $instance->validateHeaders($data);
    $this->assertEmpty($message);
  }

  /**
   * Tests CpImport Profiles header validations.
   */
  public function testCpImportProfilesHeaderValidation() {
    /** @var \Drupal\cp_import\AppImportFactory $appImportFactory */
    $appImportFactory = $this->container->get('app_import_factory');
    $instance = $appImportFactory->create('os_profiles_import');

    // Test header errors.
    $data[0] = [
      'First Name' => '',
      'Last Name' => '',
      'Photo' => 'https://i.picsum.photos/id/57/536/354.jpg',
      'Email' => 'test@test.com',
      'Created date' => '01/01/2015',
    ];
    $message = $instance->validateHeaders($data);
    $this->assertNotEmpty($message['@First name']);
    $this->assertInstanceOf(TranslatableMarkup::class, $message['@Path']);

    // Test No header errors.
    $data[0] = [
      'First name' => 'First name',
      'Last name' => 'Last name',
      'Photo' => 'https://i.picsum.photos/id/57/536/354.jpg',
      'Created date' => '01/01/2015',
      'Email' => 'test@test.com',
      'Path' => 'test1',
    ];
    $message = $instance->validateHeaders($data);
    $this->assertEmpty($message);
  }

  /**
   * Tests CpImport Profiles row validations.
   */
  public function testCpImportProfilesRowValidation() {
    /** @var \Drupal\cp_import\AppImportFactory $appImportFactory */
    $appImportFactory = $this->container->get('app_import_factory');
    $instance = $appImportFactory->create('os_profiles_import');

    // Test errors.
    $data[0] = [
      'First name' => '',
      'Last name' => '',
      'Photo' => 'https://i.picsum.photos/id/57/536/354.jpg',
      'Created date' => '01/01/2015',
      'Path' => 'test1',
      'Email' => 'test@test.com',
      'Websites url 1' => 'ht://www.homersimpson1.com',
      'Websites url 2' => 'http://www.homersimpson2.com',
      'Websites url 3' => 'http://www.homersimpson3.com',
    ];
    $message = $instance->validateRows($data);
    $this->assertEmpty($message['@date']);
    $this->assertNotEmpty($message['@website1']);
    $this->assertInstanceOf(TranslatableMarkup::class, $message['@firstNameRows']);

    // Test no errors in row.
    $data[0] = [
      'First name' => 'First name',
      'Last name' => 'Last name',
      'Photo' => '',
      'Created date' => '01/01/2015',
      'Path' => 'test1',
      'Email' => 'test@test.com',
      'Websites url 1' => 'http://www.homersimpson1.com',
      'Websites url 2' => 'http://www.homersimpson2.com',
      'Websites url 3' => 'http://www.homersimpson3.com',
    ];
    $message = $instance->validateRows($data);
    $this->assertEmpty($message);
  }

  /**
   * Tests CpImport Software row validations.
   */
  public function testCpImportSoftwareRowValidation() {
    /** @var \Drupal\cp_import\AppImportFactory $appImportFactory */
    $appImportFactory = $this->container->get('app_import_factory');
    $instance = $appImportFactory->create('os_software_import');

    // Test errors.
    $data[0] = [
      'Title' => '',
      'Body' => 'Body1',
      'Files' => '',
      'Created date' => '01/01/2015',
      'Path' => 'test1',
    ];
    $message = $instance->validateRows($data);
    $this->assertEmpty($message['@date']);
    $this->assertInstanceOf(TranslatableMarkup::class, $message['@title']);

    // Test no errors in row.
    $data[0] = [
      'Title' => 'Software1',
      'Body' => 'Body1',
      'Files' => '',
      'Created date' => '01/01/2015',
      'Path' => 'test1',
    ];
    $message = $instance->validateRows($data);
    $this->assertEmpty($message);
  }

  /**
   * Tests Migration/import for os_software_import.
   *
   * @throws \Drupal\migrate\MigrateException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function testCpImportMigrationSoftware() {
    $filename = drupal_get_path('module', 'cp_import_csv_test') . '/artifacts/software.csv';
    $this->cpImportHelper->csvToArray($filename, 'utf-8');
    // Replace existing source file.
    $path = 'public://importcsv';
    $this->fileSystem->delete($path . '/os_software.csv');
    $this->fileSystem->prepareDirectory($path, FileSystemInterface::CREATE_DIRECTORY);
    file_save_data(file_get_contents($filename), $path . '/os_software.csv', FileSystemInterface::EXISTS_REPLACE);

    $storage = $this->entityTypeManager->getStorage('node');
    // Test Negative case.
    $node1 = $storage->loadByProperties(['title' => 'Test Software 1']);
    $this->assertCount(0, $node1);
    $node2 = $storage->loadByProperties(['title' => 'Test Software 8']);
    $this->assertCount(0, $node2);

    /** @var \Drupal\migrate\Plugin\Migration $migration */
    $migration = $this->migrationManager->createInstance('os_software_import');
    $executable = new MigrateExecutable($migration, new MigrateMessage());
    $executable->import();
    // Test positive case.
    $node1 = $storage->loadByProperties(['title' => 'Test Software 1']);
    $this->assertCount(1, $node1);
    $node2 = $storage->loadByProperties(['title' => 'Test Software 3']);
    $this->assertCount(1, $node2);
    // Test date is converted from Y-n-j to Y-m-d if node is created
    // successfully it means conversion works.
    $node3 = $storage->loadByProperties(['title' => 'Test Software 6']);
    $node3 = array_values($node3)[0];
    $created = $node3->getCreatedTime();
    $created_date = date('Y-m-d', $created);
    $this->assertEquals('2014-01-01', $created_date);
    // Import same content again to check if adding timestamp works.
    $filename2 = drupal_get_path('module', 'cp_import_csv_test') . '/artifacts/software_small.csv';
    $this->cpImportHelper->csvToArray($filename2, 'utf-8');
    $this->fileSystem->delete($path . '/os_software.csv');
    $this->fileSystem->prepareDirectory($path, FileSystemInterface::CREATE_DIRECTORY);
    file_save_data(file_get_contents($filename2), $path . '/os_software.csv', FileSystemInterface::EXISTS_REPLACE);
    $migration = $this->migrationManager->createInstance('os_software_import');
    $executable = new MigrateExecutable($migration, new MigrateMessage());
    $executable->import();
    // If count is now 2 it means same content is imported again.
    $node1 = $storage->loadByProperties(['title' => 'Test Software 1']);
    $this->assertCount(2, $node1);

    // Delete all the test data created.
    $executable->rollback();
  }

  /**
   * Tests Migration/import for os_profiles_import.
   *
   * @throws \Drupal\migrate\MigrateException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function testCpImportMigrationProfiles() {
    $filename = drupal_get_path('module', 'cp_import_csv_test') . '/artifacts/profiles.csv';
    $this->cpImportHelper->csvToArray($filename, 'utf-8');
    // Replace existing source file.
    $path = 'public://importcsv';
    $this->fileSystem->delete($path . '/os_profiles.csv');
    $this->fileSystem->prepareDirectory($path, FileSystemInterface::CREATE_DIRECTORY);
    file_save_data(file_get_contents($filename), $path . '/os_profiles.csv', FileSystemInterface::EXISTS_REPLACE);

    $storage = $this->entityTypeManager->getStorage('node');
    // Test Negative case.
    $node1 = $storage->loadByProperties(['field_first_name' => 'Homer 1']);
    $this->assertCount(0, $node1);
    $node2 = $storage->loadByProperties(['field_first_name' => 'Homer 2']);
    $this->assertCount(0, $node2);

    /** @var \Drupal\migrate\Plugin\Migration $migration */
    $migration = $this->migrationManager->createInstance('os_profiles_import');
    $executable = new MigrateExecutable($migration, new MigrateMessage());
    $executable->import();
    // Test positive case.
    $node1 = $storage->loadByProperties(['field_first_name' => 'Homer 1']);
    $this->assertCount(1, $node1);
    $node2 = $storage->loadByProperties(['field_first_name' => 'Homer 2']);
    $this->assertCount(1, $node2);
    // Test date is converted from Y-n-j to Y-m-d if node is created.
    // successfully it means conversion works.
    $node3 = $storage->loadByProperties(['field_first_name' => 'Homer 6']);
    $node3 = array_values($node3)[0];
    $created = $node3->getCreatedTime();
    $created_date = date('Y-m-d', $created);
    $this->assertEquals('2020-01-01', $created_date);
    // Import same content again to check if adding timestamp works.
    $filename2 = drupal_get_path('module', 'cp_import_csv_test') . '/artifacts/profiles_small.csv';
    $this->cpImportHelper->csvToArray($filename2, 'utf-8');
    $this->fileSystem->delete($path . '/os_profiles.csv');
    $this->fileSystem->prepareDirectory($path, FileSystemInterface::CREATE_DIRECTORY);
    file_save_data(file_get_contents($filename2), $path . '/os_profiles.csv', FileSystemInterface::EXISTS_REPLACE);
    $migration = $this->migrationManager->createInstance('os_profiles_import');
    $executable = new MigrateExecutable($migration, new MigrateMessage());
    $executable->import();
    // If count is now 2 it means same content is imported again.
    $node1 = $storage->loadByProperties(['field_first_name' => 'Homer 1']);
    $this->assertCount(2, $node1);

    // Delete all the test data created.
    $executable->rollback();
  }

}
