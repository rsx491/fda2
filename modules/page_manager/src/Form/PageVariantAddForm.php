<?php

/**
 * @file
 * Contains Drupal\page_manager\Form\PageVariantAddForm.
 */

namespace Drupal\page_manager\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for adding a variant.
 */
class PageVariantAddForm extends PageVariantFormBase {

  /**
   * {@inheritdoc}
   */
  protected function submitText() {
    return $this->t('Add variant');
  }

  /**
   * {@inheritdoc}
   */
  protected function getVariantPlugin() {
    $variant_plugin = parent::getVariantPlugin();
    // Before showing the add form, we need to set the Panels storage
    // information so that it knows to give the user the IPE as an option.
    $this->setPanelsStorage($variant_plugin);
    return $variant_plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);
    $form_state->setRedirectUrl($this->getEntity()->toUrl('edit-form'));
  }

}
