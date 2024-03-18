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
use Drupal\weather_block\Services\FetchApiData;
use Drupal\weather_block\Services\UserWeatherHandler;
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
    protected UserWeatherHandler $cityService,
    protected CacheBackendInterface $cacheBackend,
    protected AccountProxyInterface $accountProxy,
    protected FetchApiData $apiData
  ) {
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
      $container->get('weather_block.get_set_city_user'),
      $container->get('cache.default'),
      $container->get('current_user'),
      $container->get('weather_block.fetch_api_data'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $build = [];
    $user_id = $this->accountProxy->id();
    $cityArray = $this->cityService->getSavedCityForUser($user_id);
    if (empty($cityArray)) {
      $defaultCity = $this->cityService->getDefaultCity();
      $city = $defaultCity[0]->default_city;
    }
    else {
      $city = $cityArray[0]->city_name;
    }

    $cache_id = 'weather_block_city_' . md5($city);

    if ($cache = $this->cacheBackend->get($cache_id)) {
      return $cache->data;
    }

    $api_data = $this->apiData->getDataFromApi($city);

    if (!empty($api_data) && is_array($api_data)) {
      $weather_data = $api_data;
      $firstCity = reset($weather_data);
      $weatherType = $firstCity['weather-type'] ?? '';

      $formatted_data = $this->formatData($weather_data);

      $build = [
        '#theme' => 'weather_block_template',
        '#content' => $formatted_data,
        '#weather_type' => $weatherType,
        '#attached' => [
          'library' => [
            'weather_block/weather_block-css',
          ],
        ],
      ];
      $tags = $this->getCacheTags();
      $this->cacheBackend->set($cache_id, $build, 60 * 60, $tags);
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags(): array {
    $tags = [];
    $tags[] = 'api_key.settings';

    return Cache::mergeTags(parent::getCacheTags(), $tags);
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $cities = $this->cityService->getCitiesArray();

    $user_id = $this->accountProxy->id();
    $savedCity = $this->cityService->getSavedCityForUser($user_id);
    $default_city = $this->cityService->getDefaultCity($user_id);

    $form['city_selection'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('City Selection'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];

    $form['city_selection']['city'] = [
      '#type' => 'select',
      '#title' => $this->t('Choose a city'),
      '#options' => array_combine($cities, $cities),
      '#default_value' => $savedCity ?? $default_city,
      '#description' => $this->t('Select the city.'),
    ];

    $form['city_selection']['settings']['key'] = [
      '#required' => TRUE,
      '#type' => 'textfield',
      '#title' => $this->t('Your API key'),
      '#default_value' => $this->configFactory->get('api_key.settings')->get('key'),
      '#description' => $this->t('Enter your API key'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    $userId = $this->accountProxy->id();
    $city = $form_state->getValue([
      'city_selection',
      'city',
    ]);
    $this->configFactory->getEditable('api_key.settings')
      ->set('key', $form_state->getValue(['city_selection', 'settings', 'key']))
      ->save();
    $this->cityService->saveDefaultCity($city, $userId);
    $api_data = $this->apiData->getDataFromApi($city);
    $this->cityService->saveWeatherDataForCity($city, $api_data);
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
