<?php

/**
 * @file
 * Contains \Drupal\deploy\Deploy.
 */

namespace Drupal\deploy;

use Doctrine\CouchDB\CouchDBClient;
use Drupal\multiversion\Workspace\WorkspaceManagerInterface;
use Drupal\relaxed\Entity\EndpointInterface;
use Psr\Http\Message\UriInterface;
use Relaxed\Replicator\ReplicationTask;
use Relaxed\Replicator\Replication;

/**
 * Class Deploy
 *
 * @package Drupal\deploy
 */
class Deploy implements DeployInterface {

  /**
   * @var \Drupal\multiversion\Workspace\WorkspaceManagerInterface
   */
  protected $workspaceManager;

  /**
   * @var array
   */
  protected $docIds = [];

  /**
   * @param \Drupal\multiversion\Workspace\WorkspaceManagerInterface $workspace_manager
   */
  public function __construct(WorkspaceManagerInterface $workspace_manager) {
    $this->workspaceManager = $workspace_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function createSource(UriInterface $source) {
    // Create the source client.
    $source_client = CouchDBClient::create([
      'url' => (string) $source,
      'timeout' => 10
    ]);

    return $source_client;
  }

  /**
   * {@inheritdoc}
   */
  public function createTarget(UriInterface $target) {
    // Create the source client.
    $target = CouchDBClient::create([
      'url' => (string) $target,
      'timeout' => 10
    ]);

    return $target;
  }

  /**
   * @param array $docIds
   */
  protected function setDocIds(array $docIds) {
    $this->docIds = $docIds;
  }

  /**
   * {@inheritdoc}
   */
  public function push(CouchDBClient $source, CouchDBClient $target) {

    try {
      // Create the replication task.
      $task = new ReplicationTask();
      // Create the replication.
      $replication = new Replication($source, $target, $task);
      // Generate and set a replication ID.
      $replication->task->setRepId($replication->generateReplicationId());
      // Filter by document IDs if set.
      if (!empty($this->docIds)) {
        $replication->task->setDocIds($this->docIds);
      }
      // Start the replication.
      $replicationResult = $replication->start();
    }
    catch (\Exception $e) {
      \Drupal::logger('Deploy')->info($e->getMessage() . ': ' . $e->getTraceAsString());
      return ['error' => $e->getMessage()];
    }
    // Return the response.
    return $replicationResult;
  }

}
