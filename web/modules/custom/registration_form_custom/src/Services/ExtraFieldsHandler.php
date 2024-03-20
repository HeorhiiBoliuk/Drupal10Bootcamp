<?php

namespace Drupal\registration_form_custom\Services;

use Drupal\Core\Database\Connection;

/**
 * Class methods adds extra fields in custom DB.
 */
class ExtraFieldsHandler {

  /**
   * Initialize a service property.
   */
  public function __construct(protected Connection $database) {
  }

  /**
   * This function is saving extra fields that i`m added to default register
   * form.
   */
  public function saveExtraFieldsForUser($userId, $country, $intersted): void {
    $this->database->delete('extra_field_register')
      ->condition('uid', $userId)
      ->execute();

    $this->database->insert('extra_field_register')
      ->fields([
        'uid' => $userId,
        'country' => $country,
        'interested' => $intersted,
      ])
      ->execute();
  }

}
