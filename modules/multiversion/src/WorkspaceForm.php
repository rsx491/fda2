<?php

/**
 * @file
 * Contains \Drupal\multiversion\WorkspaceForm.
 */

namespace Drupal\multiversion;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the workspace edit forms.
 */
class WorkspaceForm extends ContentEntityForm {

  /**
   * The workspace content entity.
   *
   * @var \Drupal\multiversion\Entity\WorkspaceInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $workspace = $this->entity;
    $form = parent::form($form, $form_state, $workspace);

    if ($this->operation == 'edit') {
      $form['#title'] = $this->t('Edit workspace %label', array('%label' => $workspace->label()));
    }
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $workspace->label(),
      '#description' => $this->t("Label for the Endpoint."),
      '#required' => TRUE,
    );

    $form['machine_name'] = array(
      '#type' => 'machine_name',
      '#title' => $this->t('Workspace ID'),
      '#maxlength' => 255,
      '#default_value' => $workspace->get('machine_name')->value,
      '#machine_name' => array(
        'exists' => '\Drupal\multiversion\Entity\Workspace::load',
      ),
      '#element_validate' => array(
        array(get_class($this), 'validateMachineName'),
      ),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $workspace = $this->entity;
    $insert = $workspace->isNew();
    $workspace->save();
    $info = ['%info' => $workspace->label()];
    $context = array('@type' => $workspace->bundle(), $info);
    $logger = $this->logger('multiversion');

    if ($insert) {
      $logger->notice('@type: added %info.', $context);
      drupal_set_message($this->t('Workspace %info has been created.', $info));
    }
    else {
      $logger->notice('@type: updated %info.', $context);
      drupal_set_message($this->t('Workspace %info has been updated.', $info));
    }

    if ($workspace->id()) {
      $form_state->setValue('id', $workspace->id());
      $form_state->set('id', $workspace->id());
      $form_state->setRedirectUrl($workspace->urlInfo('collection'));
    }
    else {
      drupal_set_message($this->t('The workspace could not be saved.'), 'error');
      $form_state->setRebuild();
    }
  }

  /**
   * Form element validation handler for machine_name elements.
   *
   * Note that #maxlength is validated by _form_validate() already.
   *
   * This checks that the submitted value:
   * - Does not contain the replacement character only.
   * - Does not contain disallowed characters.
   * - Is unique; i.e., does not already exist.
   * - Does not exceed the maximum length (via #maxlength).
   * - Cannot be changed after creation (via #disabled).
   */
  public static function validateMachineName(&$element, FormStateInterface $form_state, &$complete_form) {
    $workspace_id_form_title = $element['#title']->getUntranslatedString();
    // Verify that the workspace ID not only consists of replacement tokens.
    if (preg_match('@^' . $element['#machine_name']['replace'] . '+$@', $element['#value'])) {
      $form_state->setError($element, t('The @title must contain unique characters.', ['@title' => $workspace_id_form_title]));
    }

    // Verify that the workspace ID contains no disallowed characters.
    if (preg_match('@' . $element['#machine_name']['replace_pattern'] . '@', $element['#value'])) {
      if (!isset($element['#machine_name']['error'])) {
        // Since a hyphen is the most common alternative replacement character,
        // a corresponding validation error message is supported here.
        if ($element['#machine_name']['replace'] == '-') {
          $form_state->setError($element, t('The @title must contain only lowercase letters, numbers, and hyphens.', ['@title' => $workspace_id_form_title]));
        }
        // Otherwise, we assume the default (underscore).
        else {
          $form_state->setError($element, t('The @title must contain only lowercase letters, numbers, and underscores.', ['@title' => $workspace_id_form_title]));
        }
      }
      else {
        $form_state->setError($element, $element['#machine_name']['error']);
      }
    }

    // Verify that the workspace ID is unique.
    if ($element['#default_value'] !== $element['#value']) {
      $function = $element['#machine_name']['exists'];
      if (call_user_func($function, $element['#value'], $element, $form_state)) {
        $form_state->setError($element, t('The @title is already in use. It must be unique.', ['@title' => $workspace_id_form_title]));
      }
    }
  }

}
