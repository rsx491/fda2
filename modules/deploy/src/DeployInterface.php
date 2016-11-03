<?php

/**
 * @file
 * Contains \Drupal\deploy\DeployInterface.
 */

namespace Drupal\deploy;

use Doctrine\CouchDB\CouchDBClient;
use Psr\Http\Message\UriInterface;

/**
 * Interface DeployInterface
 * @package Drupal\deploy
 */
interface DeployInterface {

  /**
   * @param \Psr\Http\Message\UriInterface $source
   * @return \Doctrine\CouchDB\CouchDBClient
   */
  public function createSource(UriInterface $source);

  /**
   * @param \Psr\Http\Message\UriInterface $target
   * @return \Doctrine\CouchDB\CouchDBClient
   */
  public function createTarget(UriInterface $target);

  /**
   * @param \Doctrine\CouchDB\CouchDBClient $source
   * @param \Doctrine\CouchDB\CouchDBClient target
   * @return array
   */
  public function push(CouchDBClient $source, CouchDBClient $target);

}
