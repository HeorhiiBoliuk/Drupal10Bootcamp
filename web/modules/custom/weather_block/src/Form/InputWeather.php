<?php

namespace Drupal\weather_block\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\weather_block\Services\FetchApiData;
use Drupal\weather_block\Services\UserWeatherHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for selecting a city for users.
 */
class InputWeather extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    protected UserWeatherHandler $cityService,
    protected ConfigFactoryInterface $configFact,
    protected AccountProxyInterface $accountProxy,
    protected FetchApiData $apiData,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('weather_block.get_set_city_user'),
      $container->get('config.factory'),
      $container->get('current_user'),
      $container->get('weather_block.fetch_api_data')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $cities = $this->cityService->getCitiesArray();

    $form['description'] = [
      '#type' => 'item',
      '#markup' => $this->t('Choose your city'),
    ];
    $form['city'] = [
      '#type' => 'select',
      '#title' => $this->t('Choose your city'),
      '#options' => array_combine($cities, $cities),
      '#empty_option' => $this->t('-select-'),
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * Return Form ID.
   */
  public function getFormId(): string {
    return 'form_input_weather';
  }

  /**
   * Saving in DB table a user choose of the city.
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $user_id = $this->accountProxy->id();
    $city_value = $form_state->getValue('city');

    if (is_null($city_value)) {
      return;
    }

    $cities = array_map('trim', explode(',', $city_value));
    $this->cityService->saveCityForUser($user_id, $cities);
    $config = $this->configFact->get('block.block.my_awesome_theme_weatherblock');
    $api_key = $config->get('settings.settings.key');

    foreach ($cities as $city) {
      $api_data = $this->apiData->getDataFromApi($city, $api_key);
      $this->cityService->saveWeatherDataForCity($city, $api_data);
    }
  }

}
