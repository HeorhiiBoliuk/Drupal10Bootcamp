<?php

namespace Drupal\weather_block\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Http\ClientFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\weather_block\Services\GetSetCityUser;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'WeatherBlock' block.
 */
#[Block(
  id: "weather_block",
  admin_label: new TranslatableMarkup("Weather block"),
)]
class WeatherBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Constructor for WeatherBlock.
   */
  public function __construct(array $configuration,
    $plugin_id,
    $plugin_definition,
    protected ConfigFactoryInterface $configFactory,
    protected LoggerChannelFactoryInterface $logger,
    protected Connection $database,
    protected ClientFactory $httpClient,
    protected GetSetCityUser $cityService,
    protected CacheBackendInterface $cacheBackend,
    protected AccountProxyInterface $accountProxy) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * Creates a new instance of the WeatherBlock block plugin.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('logger.factory'),
      $container->get('database'),
      $container->get('http_client_factory'),
      $container->get('weather_block.save_cities_for_user'),
      $container->get('cache.default'),
      $container->get('current_user'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $user_id = $this->accountProxy->id();
    $cities = $this->cityService->getSavedCitiesForUser($user_id);
    $api_key = $this->configFactory->get('block.block.my_awesome_theme_weatherdata')->get('settings.key');
    $weather_data = [];

    foreach ($cities as $city) {
      $cache_id = 'weather_block_data_' . md5($city);

      if (!$cache = $this->cacheBackend->get($cache_id)) {
        $api_data = $this->cityService->getDataFromApi($city, $api_key);

        if ($api_data === ['no key']) {
          return [];
        }
        $tags = $this->getCacheTags();

        $this->cacheBackend->set($cache_id, $api_data, time() + 60 * 60, $tags);
      }
      else {
        $api_data = $cache->data;
      }

      if (!empty($api_data) && is_array($api_data)) {
        $weather_data = $api_data;
      }
    }

    if (!empty($weather_data)) {
      $firstCity = reset($weather_data);
      $weatherType = $firstCity['weather-type'] ?? '';
    }
    else {
      $weatherType = '';
    }

    $formatted_data = $this->formatData($weather_data);

    return [
      '#theme' => 'weather_block_template',
      '#content' => $formatted_data,
      '#weather_type' => $weatherType,
      '#attached' => [
        'library' => [
          'weather_block/weather_block-css',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags(): array {
    $tags = [];
    $tags[] = 'config:block.block.my_awesome_theme_weatherblock';
    $tags[] = 'user:' . $this->accountProxy->id();

    return Cache::mergeTags(parent::getCacheTags(), $tags);
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $cities = $this->cityService->getCitiesArray();

    $user_id = $this->accountProxy->id();
    $savedCities = $this->cityService->getSavedCitiesForUser($user_id);

    $form['city_selection'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('City Selection'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];

    $form['city_selection']['cities'] = [
      '#type' => 'select',
      '#title' => $this->t('Choose a city'),
      '#options' => array_combine($cities, $cities),
      '#default_value' => $savedCities,
      '#description' => $this->t('Select the city.'),
    ];

    $form['city_selection']['settings']['key'] = [
      '#required' => TRUE,
      '#type' => 'textfield',
      '#title' => $this->t('Your API key'),
      '#default_value' => $this->configFactory->get('block.block.my_awesome_theme_weatherdata')->get('settings.key') ?? NULL,
      '#description' => $this->t('Enter your API key'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    $user_id = $this->accountProxy->id();
    $cities = array_map('trim', explode(',', $form_state->getValue([
      'city_selection',
      'cities',
    ])));
    $this->cityService->saveCitiesForUser($user_id, $cities);
    $this->configFactory->getEditable('block.block.my_awesome_theme_weatherdata')
      ->set('settings.key', trim($form_state->getValue([
        'city_selection',
        'settings',
        'key',
      ])))
      ->set('users.' . $user_id . '.cities', $cities)
      ->save();

    $api_key = $this->configFactory->get('block.block.my_awesome_theme_weatherdata')->get('settings.key');
    foreach ($cities as $city) {
      $cache_id = 'weather_block_data_' . md5($city);
      $api_data = $this->cityService->getDataFromApi($city, $api_key);
      $this->cityService->saveWeatherDataForCity($city, $api_data);
    }
  }

  /**
   * Private function for getting array with temperature.
   */
  private function getDataFromApi(string $cities, string $api_key): array {
    $weather_data = [];

    try {
      $url = 'https://api.openweathermap.org/data/2.5/weather?q=' . urlencode($cities) . '&appid=' . urlencode($api_key);
      $httpClient = $this->httpClient->fromOptions();
      $response = $httpClient->get($url);
      $data = json_decode($response->getBody(), TRUE);
    }
    catch (ClientException $e) {
      $this->logger->get('weather_block')->error('Failed to get weather data for city @city: @message', [
        '@city' => $cities,
        '@message' => $e->getMessage(),
      ]);
    }
    if (isset($data['weather'][0]['main'])) {
      $weather_type = $data['weather'][0]['main'];
      $weather_data[$cities]['weather-type'] = $weather_type;
    }
    if (isset($data['main']['temp'])) {
      $temperature = round($data['main']['temp'] - 273);
      $weather_data[$cities]['temperature'] = $temperature;
    }
    return $weather_data;
  }

  /**
   * Format weather data into an array of list items.
   */
  private function formatData($weather_data): array {
    $formatted_data = [];
    foreach ($weather_data as $city => $info) {
      if (isset($info['temperature'])) {
        $formatted_data[] = $this->t('@city: @temperature°C', [
          '@city' => $city,
          '@temperature' => $info['temperature'],
        ]);
      }
    }
    return $formatted_data;
  }

}
