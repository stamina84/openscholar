<?php

namespace Drupal\Tests\vsite\Unit;

use Drupal\Core\Language\LanguageInterface;
use Drupal\path_alias\AliasRepositoryInterface;
use Drupal\purl\Modifier;
use Drupal\Tests\UnitTestCase;
use Drupal\vsite\Path\VsiteAliasRepository;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Tests for the VsitePathActivator class.
 *
 * @group vsite
 * @group unit
 * @coversDefaultClass \Drupal\vsite\Path\VsiteAliasRepository
 * @codeCoverageIgnore
 */
class VsiteAliasRepositoryTest extends UnitTestCase {

  /**
   * Dependency Injection container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerBuilder
   */
  protected $container;

  /**
   * The object to test.
   *
   * @var \Drupal\vsite\Path\VsiteAliasRepository
   */
  protected $vsiteAliasRepository;

  /**
   * Mock for EntityTypeManagerInterface.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityTypeManager;

  /**
   * The AliasRepository our tested class is wrapping.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject
   */
  protected $innerAliasRepository;

  /**
   * Set up all the needed mock classes for these tests.
   */
  public function setUp() {
    parent::setUp();

    $this->container = new ContainerBuilder();
    \Drupal::setContainer($this->container);

    $this->innerAliasRepository = $this->createMock(AliasRepositoryInterface::class);

    $mockProvider = $this->createMock('\Drupal\purl\Entity\Provider');

    $modifierIndex = $this->createMock('\Drupal\purl\Plugin\ModifierIndex');
    $method = $this->createMock('\Drupal\purl\Plugin\Purl\Method\MethodInterface');
    $modifierIndex->method('getProviderModifiers')
      ->willReturn([
        new Modifier('site01', 1, $method, $mockProvider),
      ]);

    $purlStorage = $this->createMock('\Drupal\Core\Entity\EntityStorageInterface');
    $purlStorage->method('load')
      ->willReturn($mockProvider);

    $this->entityTypeManager = $this->createMock('\Drupal\Core\Entity\EntityTypeManagerInterface');
    $this->entityTypeManager->method('getStorage')
      ->with('purl_provider')
      ->willReturn($purlStorage);

    $group = $this->createMock('\Drupal\group\Entity\GroupInterface');
    $group->method('id')
      ->willReturn(1);

    $vsiteContextManager = $this->createMock('\Drupal\vsite\Plugin\VsiteContextManager');
    $vsiteContextManager->method('getActiveVsite')
      ->willReturn($group);

    $this->vsiteAliasRepository = new VsiteAliasRepository(
      $this->innerAliasRepository, $modifierIndex, $this->entityTypeManager, $vsiteContextManager);
  }

  /**
   * Testing the lookupBySystemPath method.
   */
  public function testLookupBySource() {
    $this->innerAliasRepository->expects($this->once())
      ->method('lookupBySystemPath')
      ->with('/node/1', LanguageInterface::LANGCODE_SITE_DEFAULT)
      ->willReturn(['alias' => '/[vsite:1]/foo']);

    $this->assertEquals(['alias' => '/site01/foo'], $this->vsiteAliasRepository->lookupBySystemPath('/node/1', LanguageInterface::LANGCODE_SITE_DEFAULT));
  }

  /**
   * Testing the lookupByAlias method.
   */
  public function testLookupByAlias() {
    $this->innerAliasRepository->expects($this->once())
      ->method('lookupByAlias')
      ->with('/[vsite:1]/foo', LanguageInterface::LANGCODE_SITE_DEFAULT)
      ->willReturn('/node/1');

    $this->assertEquals('/node/1', $this->vsiteAliasRepository->lookupByAlias('/foo', LanguageInterface::LANGCODE_SITE_DEFAULT));
  }

  /**
   * Tests languageAliasExists.
   */
  public function testPathHasMatchingAlias() {
    $this->innerAliasRepository
      ->method('pathHasMatchingAlias')
      ->with()
      ->willReturn(TRUE);

    $this->assertTrue($this->vsiteAliasRepository->pathHasMatchingAlias('/'));
  }

}
