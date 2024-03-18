<?php

namespace Drupal\weather_block\Services;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Database\Connection;
use Drupal\Core\Http\ClientFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Save cities for users.
 */
class UserWeatherHandler {

  /**
   * Initialize a database property.
   */
  public function __construct(protected Connection $database, protected LoggerChannelFactoryInterface $logger, protected ClientFactory $httpClient, protected ConfigFactory $configFactory) {
  }

  /**
   * Saves selected cities for a user in the database.
   *
   * @throws \Exception
   */
  public function saveCityForUser($userId, $city): void {
    $this->database->delete('weather_block_city_users')
      ->condition('user_id', $userId)
      ->execute();

    $this->database->insert('weather_block_city_users')
      ->fields([
        'user_id' => $userId,
        'city_name' => $city,
      ])
      ->execute();
  }

  /**
   * Set a default value of city.
   */
  public function saveDefaultCity($city) {
    $this->configFactory->getEditable('default_value_of_city.settings')
      ->set('default_city', $city)->save();
  }

  /**
   * Retrieves saved default city name.
   */
  public function getDefaultCity(): string {
    return $this->configFactory->get('default_value_of_city.settings')->get('default_city');
  }

  /**
   * Retrieves saved cities for a user from the database.
   */
  public function getSavedCityForUser($userId): string {
    $query = $this->database->select('weather_block_city_users', 'cu');
    $query->fields('cu', ['city_name']);
    $query->condition('cu.user_id', $userId);
    $cityArray = $query->execute()->fetchAll();
    return $cityArray[0]->city_name;
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
      'Vinnytsia' => t('Vinnytsia'),
      'Dnipro' => t('Dnipro'),
      'Donetsk' => t('Donetsk'),
      'Zhytomyr' => t('Zhytomyr'),
      'Zaporizhzhia' => t('Zaporizhzhia'),
      'Ivano-Frankivsk' => t('Ivano-Frankivsk'),
      'Kyiv' => t('Kyiv'),
      'Kropyvnytskyi' => t('Kropyvnytskyi'),
      'Luhansk' => t('Luhansk'),
      'Lutsk' => t('Lutsk'),
      'Lviv' => t('Lviv'),
      'Mykolaiv' => t('Mykolaiv'),
      'Odesa' => t('Odesa'),
      'Poltava' => t('Poltava'),
      'Rivne' => t('Rivne'),
      'Sumy' => t('Sumy'),
      'Ternopil' => t('Ternopil'),
      'Uzhhorod' => t('Uzhhorod'),
      'Kharkiv' => t('Kharkiv'),
      'Kherson' => t('Kherson'),
      'Khmelnytskyi' => t('Khmelnytskyi'),
      'Cherkasy' => t('Cherkasy'),
      'Chernivtsi' => t('Chernivtsi'),
      'Chernihiv' => t('Chernihiv'),
      'Simferopol' => t('Simferopol'),
    ];
  }

}
