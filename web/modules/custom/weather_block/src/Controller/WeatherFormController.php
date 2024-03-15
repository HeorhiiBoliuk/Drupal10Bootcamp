<?php

namespace Drupal\weather_block\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for displaying the weather form.
 */
class WeatherFormController extends ControllerBase {

  /**
   * Displays the weather form.
   */
  public function showForm() {
    return \Drupal::formBuilder()->getForm('Drupal\weather_block\Form\InputWeather\InputWeather');
  }

}
