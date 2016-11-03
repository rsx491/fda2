<?php

/**
 * @file
 * Contains \Drupal\relaxed\Plugin\rest\resource\ChangesResource.
 */

namespace Drupal\relaxed\Plugin\rest\resource;

use Drupal\relaxed\Changes\Changes;
use Drupal\rest\ResourceResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @RestResource(
 *   id = "relaxed:changes",
 *   label = "Changes",
 *   serialization_class = {
 *     "canonical" = "Drupal\relaxed\Changes\Changes",
 *   },
 *   uri_paths = {
 *     "canonical" = "/{db}/_changes",
 *   },
 *   no_cache = TRUE
 * )
 */
class ChangesResource extends ResourceBase {

  public function get($workspace) {
    if (is_string($workspace)) {
      throw new NotFoundHttpException();
    }

    // @todo: {@link https://www.drupal.org/node/2599930 Use injected container instead.}
    $changes = Changes::createInstance(
      \Drupal::getContainer(),
      \Drupal::service('entity.index.sequence'),
      $workspace
    );

    $request = Request::createFromGlobals();
    if ($request->query->get('include_docs') == 'true') {
      $changes->includeDocs(TRUE);
    }

    return new ResourceResponse($changes, 200);
  }

}
