<?php

namespace Drupal\weather_block\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
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
   * The Config Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;
  /**
   * The Logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected LoggerChannelFactoryInterface $logger;

  /**
   * Constructor for WeatherBlock.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $configFactory, LoggerChannelFactoryInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $configFactory;
    $this->logger = $logger;
  }

  /**
   * Container for WeatherBlock.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('logger.factory')
       );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $cities = $this->configuration['cities'] ?? $this->getDefaultCities();
    $api_key = $this->configuration['key'] ?? $this->getDefaultApiKey();
    $api_data = $this->getDataFromApi($cities, $api_key);

    if ($api_data === ['no key']) {
      return [];
    }

    $firstCity = reset($api_data);
    $weatherType = $firstCity['weather-type'];
    $formatted_data = $this->formatData($api_data);

    return [
      '#theme' => 'weather_block_template',
      '#content' => $formatted_data,
      '#weather_type' => $weatherType,
    ];
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
    $form['API - key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Your API key'),
      '#default_value' => $this->configuration['key'] ?? 'Your API Key',
      '#description' => $this->t('Enter your API key'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    $this->configuration['cities'] = array_map('trim', explode(',', $form_state->getValue('cities')));
    $this->configuration['key'] = trim($form_state->getValue('API - key'));
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state): void {
    $api_key = $form_state->getValue('API - key');
    if (empty($api_key)) {
      $form_state->setErrorByName('API - key', $this->t('Api Key can not be empty'));
      $form_state->setValidationEnforced(TRUE);
    }
    else {
      $this->configuration['key'] = $api_key;
    }
  }

  /**
   * Private function for getting array with temperature.
   */
  private function getDataFromApi(array $cities, string $api_key): array {
    $weather_data = [];

    foreach ($cities as $city) {
      try {
        $url = 'https://api.openweathermap.org/data/2.5/weather?q=' . urlencode($city) . '&appid=' . urlencode($api_key);
        $response = \Drupal::httpClient()->get($url);
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

  /**
   * Get default cities.
   */
  private function getDefaultCities(): array {
    return $this->configFactory->get('weather_block.settings')->get('cities');
  }

  /**
   * Get default api key.
   */
  private function getDefaultApiKey(): array {
    return $this->configFactory->get('api_key.settings')->get('key');
  }

}
