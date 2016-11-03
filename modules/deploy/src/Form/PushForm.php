<?php

/**
 * @file
 * Contains \Drupal\deploy\Form\PushForm.
 */

namespace Drupal\deploy\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\deploy\DeployInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\relaxed\Entity\Endpoint;

/**
 * Class PushForm
 *
 * @package Drupal\deploy\Form
 */
class PushForm extends FormBase {

  /**
   * @var \Drupal\deploy\Deploy
   */
  protected $deploy;

  /**
   * @var RendererInterface
   */
  protected $renderer;

  /**
   * @var array
   */
  protected $endpoints;

  /**
   * @param DeployInterface $deploy
   * @param RendererInterface $renderer
   */
  function __construct(DeployInterface $deploy, RendererInterface $renderer) {
    $this->deploy = $deploy;
    $this->renderer = $renderer;
    $this->endpoints = Endpoint::loadMultiple();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('deploy.deploy'),
      $container->get('renderer')
    );
  }

  /**
   * @return string
   */
  public function getFormId() {
    // Unique ID of the form.
    return 'deploy_form';
  }

  /**
   * @param array $form
   * @param FormStateInterface $form_state
   * @return array
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $replicationtask_exists = class_exists('Relaxed\Replicator\ReplicationTask');
    $replication_exists = class_exists('Relaxed\Replicator\Replication');
    $couchdbclient_exists = class_exists('Doctrine\CouchDB\CouchDBClient');
    // Check if all dependencies are available.
    if (!$replicationtask_exists || !$replication_exists || !$couchdbclient_exists) {
      drupal_set_message(
        $this->t('One or more dependencies required by <a href=":deploy">Deploy</a> module are missing. Check the <a href=":status">status report</a> for more information.',
        [
          ':status' => $this->url('system.status'),
          ':deploy' => 'https://drupal.org/project/deploy',
        ]),
        'error'
      );
      return [];
    }

    if (empty($this->endpoints)) {
      drupal_set_message('Please setup an endpoint before deploying.', 'warning');
      return $this->redirect('entity.endpoint.collection');
    }
    $endpoints = [];
    foreach ($this->endpoints as $endpoint_entity) {
      $endpoints[$endpoint_entity->id()] = $endpoint_entity->label();
    }

    $form['message'] = [
      '#markup' => '<div id="deploy-messages"></div>'
    ];

    $form['source'] = [
      '#type' => 'select',
      '#title' => t('Source'),
      '#options' => $endpoints
    ];

    $form['target'] = [
      '#type' => 'select',
      '#title' => t('Target'),
      '#options' => $endpoints
    ];

    $form['push'] = [
      '#type' => 'submit',
      '#value' => t('Push'),
      '#button_type' => 'primary',
      '#ajax' => [
        'callback' => [$this, 'submitFormAjax'],
        'event' => 'mousedown',
        'prevent' => 'click',
        'progress' => [
          'type' => 'throbber',
          'message' => 'Pushing deployment',
        ],
      ],
    ];
    $form['cancel'] = [
      '#type' => 'button',
      '#value' => t('Cancel'),
      '#attributes' => [
        'class' => ['dialog-cancel'],
      ],
    ];
    return $form;
  }

  /**
   * @param array $form
   * @param FormStateInterface $form_state
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * @param array $form
   * @param FormStateInterface $form_state
   * @return AjaxResponse
   */
  public function submitFormAjax(array &$form, FormStateInterface $form_state) {
    $result = $this->doDeployment($form_state);
    $response = new AjaxResponse();
    if (!isset($result['error'])) {
      $response->addCommand(new CloseModalDialogCommand());
      drupal_set_message('Successful deployment.');
    }
    else {
      drupal_set_message($result['error'], 'error');
    }
    $status_messages = ['#type' => 'status_messages'];
    $response->addCommand(new HtmlCommand('#deploy-messages', $this->renderer->renderRoot($status_messages)));
    return $response;
  }

  /**
   * @param array $form
   * @param FormStateInterface $form_state
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $result = $this->doDeployment($form_state);
    if (!isset($result['error'])) {
      drupal_set_message('Successful deployment.');
    }
    else {
      drupal_set_message($result['error'], 'error');
    }
  }

  /**
   * @param FormStateInterface $form_state
   * @return array
   */
  protected function doDeployment(FormStateInterface $form_state) {
    // Get the source and target Endpoint entities based on the id.
    $source = $this->endpoints[$form_state->getValue('source')];
    $target = $this->endpoints[$form_state->getValue('target')];

    // Send the source and target entities to the Deploy service.
    $source = $this->deploy->createSource($source->getPlugin());
    $target = $this->deploy->createTarget($target->getPlugin());

    // Run a push deployment.
    return $this->deploy->push($source, $target);
  }

}
