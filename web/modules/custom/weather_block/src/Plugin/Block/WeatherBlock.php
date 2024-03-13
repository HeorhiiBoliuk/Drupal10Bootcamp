<?php

namespace Drupal\weather_block\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Http\ClientFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
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
  public function __construct(array $configuration, $plugin_id, $plugin_definition, protected ConfigFactoryInterface $configFactory, protected LoggerChannelFactoryInterface $logger, protected ClientFactory $httpClient) {
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
      $container->get('http_client_factory'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $cities = $this->configuration['cities'];
    $api_key = $this->configuration['key'];

    $cache_id = 'weather_block_data_' . md5(serialize($cities));
    if (!$cache = \Drupal::cache()->get($cache_id)) {
      $api_data = $this->getDataFromApi($cities, $api_key);

      if ($api_data === ['no key']) {
        return [];
      }

      \Drupal::cache()->set($cache_id, $api_data, time() + 3600);
    }
    else {
      $api_data = $cache->data;
    }

    $firstCity = reset($api_data);
    $weatherType = $firstCity['weather-type'];
    $formatted_data = $this->formatData($api_data);

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
    return Cache::mergeTags(parent::getCacheTags(), $tags);
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $cities = [
      'Вінниця', 'Дніпро', 'Донецьк', 'Житомир', 'Запоріжжя', 'Івано-Франківськ',
      'Київ', 'Кропивницький', 'Луганськ', 'Луцьк', 'Львів', 'Миколаїв', 'Одеса',
      'Полтава', 'Рівне', 'Суми', 'Тернопіль', 'Ужгород', 'Харків', 'Херсон',
      'Хмельницький', 'Черкаси', 'Чернівці', 'Чернігів', 'Сімферополь',
    ];

    $form['cities'] = [
      '#type' => 'select',
      '#title' => $this->t('Choose a city'),
      '#options' => array_combine($cities, $cities),
      '#default_value' => $this->configuration['cities'] ?? reset($cities),
      '#description' => $this->t('Select the city.'),
    ];
    $form['settings']['key'] = [
      '#required' => TRUE,
      '#type' => 'textfield',
      '#title' => $this->t('Your API key'),
      '#default_value' => $this->configuration['key'] ?? NULL,
      '#description' => $this->t('Enter your API key'),
    ];

    if (!empty($this->configuration['settings']['key'])) {
      $form['settings']['key']['#default_value'] = $this->configuration['key'];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    $this->configuration['cities'] = array_map('trim', explode(',', $form_state->getValue('cities')));
    $this->configuration['key'] = trim($form_state->getValue(['settings', 'key']));
  }

  /**
   * Private function for getting array with temperature.
   */
  private function getDataFromApi(array $cities, string $api_key): array {
    $weather_data = [];
    foreach ($cities as $city) {
      try {
        $url = 'https://api.openweathermap.org/data/2.5/weather?q=' . urlencode($city) . '&appid=' . urlencode($api_key);
        $httpClient = $this->httpClient->fromOptions();
        $response = $httpClient->get($url);
        $data = json_decode($response->getBody(), TRUE);

      }
      catch (ClientException $e) {
        $this->logger->get('weather_block')->error('Failed to get weather data for city @city: @message', [
          '@city' => $city,
          '@message' => $e->getMessage(),
        ]);
        continue;
      }

      if (isset($data['weather'][0]['main'])) {
        $weather_type = $data['weather'][0]['main'];
        $weather_data[$city]['weather-type'] = $weather_type;
      }

      if (isset($data['main']['temp'])) {
        $temperature = round($data['main']['temp'] - 273);
        $weather_data[$city]['temperature'] = $temperature;
      }
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
