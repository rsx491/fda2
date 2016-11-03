<?php

namespace Drupal\multiversion\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\multiversion\Workspace\WorkspaceManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @Block(
 *   id = "multiversion_workspace_block",
 *   admin_label = @Translation("Workspace switcher"),
 *   category = @Translation("Multiversion"),
 * )
 */
class WorkspaceBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\multiversion\Workspace\WorkspaceManagerInterface
   */
  protected $workspaceManager;

  /**
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Drupal\multiversion\Workspace\WorkspaceManagerInterface $workspace_manager
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, WorkspaceManagerInterface $workspace_manager, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->workspaceManager = $workspace_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('workspace.manager'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = array();
    $route_name = \Drupal::service('path.matcher')->isFrontPage() ? '<front>' : '<current>';
    $links = $this->workspaceManager->getWorkspaceSwitchLinks(Url::fromRoute($route_name));

    if (isset($links)) {
      $build = array(
        '#theme' => 'links__workspace_block',
        '#links' => $links,
        '#attributes' => array(
          'class' => array(
            'workspace-switcher',
          ),
        ),
        '#set_active_class' => TRUE,
        // @todo: The caching need tests.
        '#cache' => [
          'contexts' => $this->entityTypeManager->getDefinition('workspace')->getListCacheContexts(),
          'tags' => $this->entityTypeManager->getDefinition('workspace')->getListCacheTags(),
        ],
      );
    }
    return $build;
  }

}
