<?php

namespace Drupal\registration_form_custom\Services;

use Drupal\Core\Database\Connection;
use Drupal\weather_block\Services\UserWeatherHandler;

/**
 * Class method adds extra fields in custom DB.
 */
class ExtraFieldsHandler {

  /**
   * Initialize a service property.
   */
  public function __construct(protected Connection $database, public UserWeatherHandler $cityService) {
  }

  /**
   * This function is saving in DB extra fields form.
   */
  public function saveExtraFieldsForUser($userId, $country, $interested, $city): void {
    $this->database->upsert('extra_field_register')
      ->fields([
        'uid' => $userId,
        'country' => $country,
        'interested' => $interested,
        'city_name' => $city,
      ])
      ->key('uid')
      ->execute();
  }

}
