<?php

namespace Drupal\file_delete_ui\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;

/**
 * Provides a file entity Delete Form.
 */
class FileEntityDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritDoc}
   */
  public function getCancelUrl() {
    try {
      return parent::getCancelUrl();
    }
    catch (\Exception $e) {
      return 'file/' . $this->getEntity()->id();
    }
  }

}
