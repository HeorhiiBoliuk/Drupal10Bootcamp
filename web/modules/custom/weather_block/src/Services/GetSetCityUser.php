<?php

namespace Drupal\weather_block\Services;

use Drupal\Core\Database\Connection;
use Drupal\Core\Http\ClientFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

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
    $this->database->delete('weather_block_city_users')
      ->condition('user_id', $userId)
      ->execute();

    foreach ($cities as $city) {
      $this->database->insert('weather_block_city_users')
        ->fields([
          'user_id' => $userId,
          'city_name' => $city,
        ])
        ->execute();
    }
  }

  /**
   * Retrieves saved cities for a user from the database.
   */
  public function getSavedCityForUser($userId): ?array {
    $query = $this->database->select('weather_block_city_users', 'cu');
    $query->fields('cu', ['city_name']);
    $query->condition('cu.user_id', $userId);
    $result = $query->execute()->fetchAll();

    $cities = [];
    foreach ($result as $row) {
      $cities[] = $row->city_name;
    }

    return $cities;
  }

  /**
   * Saves weather data for a city in the database.
   */
  public function saveWeatherDataForCity($city, array $weatherData): void {
    $existingRecord = $this->database->select('weather_block_cities', 'w')
      ->fields('w')
      ->condition('name', $city)
      ->execute()
      ->fetchAssoc();

    if ($existingRecord) {
      $this->database->update('weather_block_cities')
        ->fields([
          'weather_data' => serialize($weatherData),
          'cache_expire' => time() + 3600,
        ])
        ->condition('name', $city);
    }
    else {
      $this->database->insert('weather_block_cities')
        ->fields([
          'name' => $city,
          'data' => serialize($weatherData),
        ])
        ->execute();
    }
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
