<?php

namespace Drupal\weather_block\Services;

use Drupal\Core\Database\Connection;
use Drupal\Core\Http\ClientFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\Exception\ClientException;

/**
 * Save cities for users.
 */
class GetSetCityUser {

  /**
   * Initialize a database property.
   */
  public function __construct(protected Connection $database, protected LoggerChannelFactoryInterface $logger, protected ClientFactory $httpClient) {
  }

  /**
   * Saves selected cities for a user in the database.
   *
   * @throws \Exception
   */
  public function saveCitiesForUser($userId, $cities): void {
    $this->database->delete('weather_block_user_cities')
      ->condition('user_id', $userId)
      ->execute();

    foreach ($cities as $city) {
      $this->database->insert('weather_block_user_cities')
        ->fields([
          'user_id' => $userId,
          'city' => $city,
        ])
        ->execute();
    }
  }

  /**
   * Retrieves saved cities for a user from the database.
   */
  public function getSavedCitiesForUser($userId): array {
    $query = $this->database->select('weather_block_user_cities', 'w');
    $query->fields('w', ['city']);
    $query->condition('w.user_id', $userId);
    $result = $query->execute()->fetchAll();
    $cities = [];
    foreach ($result as $row) {
      $cities[] = $row->city;
    }
    return $cities;
  }

  /**
   * Saves weather data for a city in the database.
   */
  public function saveWeatherDataForCity($city, array $weatherData): void {
    $existingRecord = $this->database->select('weather_block_user_cities', 'w')
      ->fields('w')
      ->condition('city', $city)
      ->execute()
      ->fetchAssoc();

    if ($existingRecord) {
      $this->database->update('weather_block_user_cities')
        ->fields([
          'weather_data' => serialize($weatherData),
          'cache_expire' => time() + 3600,
        ])
        ->condition('city', $city)
        ->execute();
    }
    else {
      $this->database->insert('weather_block_user_cities')
        ->fields([
          'user_id' => \Drupal::currentUser()->id(),
          'city' => $city,
          'weather_data' => serialize($weatherData),
          'cache_expire' => time() + 3600,
        ])
        ->execute();
    }
  }

  /**
   * Private function for getting array with temperature.
   */
  public function getDataFromApi(string $cities, string $api_key): array {
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
   * Return a city array.
   */
  public function getCitiesArray(): array {
    return [
      'Вінниця', 'Дніпро', 'Донецьк', 'Житомир', 'Запоріжжя', 'Івано-Франківськ',
      'Київ', 'Кропивницький', 'Луганськ', 'Луцьк', 'Львів', 'Миколаїв', 'Одеса',
      'Полтава', 'Рівне', 'Суми', 'Тернопіль', 'Ужгород', 'Харків', 'Херсон',
      'Хмельницький', 'Черкаси', 'Чернівці', 'Чернігів', 'Сімферополь',
    ];
  }

}
