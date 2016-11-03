<?php

/**
 * @file
 * Contains \Drupal\deploy\Tests\DeployTest.
 */

namespace Drupal\deploy\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\relaxed\Entity\Endpoint;

/**
 * @group deploy
 */
class DeployTest extends WebTestBase {

  /**
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'file',
    'multiversion',
    'rest',
    'relaxed',
    'deploy',
    'deploy_test'
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
    $this->deploy = \Drupal::service('deploy.deploy');
  }

  /**
   * Test deploying from Drupal to Drupal.
   */
  public function testDeployDrupal() {
    $source_endpoint = Endpoint::create([
      'id' => 'workspace_default',
      'label' => 'Workspace Default',
      'plugin' => 'workspace:default',
      'configuration' => ['username' => 'replicator', 'password' => base64_encode('replicator')]
    ]);
    $source_endpoint->save();
    $target_endpoint = Endpoint::create([
      'id' => 'workspace_test',
      'label' => 'Workspace Test',
      'plugin' => 'workspace:test',
      'configuration' => ['username' => 'replicator', 'password' => base64_encode('replicator')]
    ]);
    $target_endpoint->save();
    $source = $this->deploy->createSource($source_endpoint->getPlugin());
    $target = $this->deploy->createTarget($target_endpoint->getPlugin());

    $result = $this->deploy->push($source, $target);
    $this->assertTrue(!isset($result['error']), 'Successful deployment.');
  }

}
