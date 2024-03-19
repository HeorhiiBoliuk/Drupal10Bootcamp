<?php

namespace Drupal\weather_block\Services;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Database\Connection;
use Drupal\Core\Http\ClientFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\Exception\ClientException;

/**
 * Fetch a data from api by city name.
 */
class FetchApiData {

  /**
   * Constructor for FetchApiData.
   */
  public function __construct(protected Connection $database, protected LoggerChannelFactoryInterface $logger, protected ClientFactory $httpClient, protected ConfigFactory $configFactory) {
  }

  /**
   * Public function for getting array with temperature and weather type.
   */
  public function getDataFromApi(string $city): array {
    $weather_data = [];
    $api_key = $this->configFactory->get('api_key_for_weather_data.settings')->get('key');

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
    }
    if (isset($data['weather'][0]['main'])) {
      $weather_type = $data['weather'][0]['main'];
      $weather_data[$city]['weather-type'] = $weather_type;
    }
    if (isset($data['main']['temp'])) {
      $temperature = round($data['main']['temp'] - 273);
      $weather_data[$city]['temperature'] = $temperature;
    }
    return $weather_data;
  }

}
