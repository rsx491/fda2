<?php

/**
 * @file
 * Contains \Drupal\Tests\deploy\Unit\DeployUnitTest.
 */

namespace Drupal\Tests\deploy\Unit;

use Drupal\KernelTests\KernelTestBase;
use Drupal\multiversion\Entity\Workspace;
use Drupal\relaxed\Entity\Endpoint;

/**
 * @group deploy
 */
class DeployUnitTest extends KernelTestBase {

  /**
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'serialization',
    'key_value',
    'file',
    'multiversion',
    'rest',
    'relaxed',
    'deploy'
  ];

  /**
   * @var \Drupal\deploy\Deploy
   */
  protected $deploy;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('workspace');
    $this->installConfig(['multiversion', 'relaxed']);
    $workspace = Workspace::create(['machine_name' => 'default', 'label' => 'default']);
    $workspace->save();
    $this->deploy = \Drupal::service('deploy.deploy');
  }

  /**
   * Test creating a source.
   */
  public function testCreateSource() {
    $endpoint = Endpoint::create([
      'id' => 'workspace_default',
      'label' => 'Workspace Default',
      'plugin' => 'workspace:default',
      'configuration' => ['username' => 'replicator', 'password' => base64_encode('replicator')]
    ]);

    $source = $this->deploy->createSource($endpoint->getPlugin());
    $this->assertTrue($source instanceof \Doctrine\CouchDB\CouchDBClient, 'Source is CouchDB Client.');
  }

  /**
   * Test creating a target.
   */
  public function testCreateTarget() {
    $endpoint = Endpoint::create([
      'id' => 'workspace_default',
      'label' => 'Workspace Default',
      'plugin' => 'workspace:default',
      'configuration' => ['username' => 'replicator', 'password' => base64_encode('replicator')]
    ]);

    $source = $this->deploy->createTarget($endpoint->getPlugin());
    $this->assertTrue($source instanceof \Doctrine\CouchDB\CouchDBClient, 'Source is CouchDB Client.');
  }

}
