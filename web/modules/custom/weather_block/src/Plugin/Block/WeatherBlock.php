<?php

namespace Drupal\weather_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'WeatherBlock' block.
 *
 * @Block(
 *   id = "weather_block",
 *   admin_label = @Translation("Weather block"),
 * )
 */
class WeatherBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $api_data = $this->getDataFromAPI();
    $formatted_data = $this->formatData($api_data);
    $weather_type = $this->getDataFromAPI();

    return [
      '#theme' => 'weather_block_template',
      '#content' => $formatted_data,
      '#weather_type' => $weather_type,
    ];
  }

  /**
   * Private function for getting array with temperature.
   */
  private function getDataFromAPI(): array {
    $cities = ['Луцьк'];
    $api_key = '8795cf1702a915bf4f6e1c1ca54fed35';
    $weather_data = [];

    foreach ($cities as $city) {
      $url = 'https://api.openweathermap.org/data/2.5/weather?q=' . $city . '&appid=' . $api_key;
      $json = file_get_contents($url);
      $data = json_decode($json, TRUE);
      $weather_type = $data['weather'][0]['main'];
      $weather_data['weather-type'] = $weather_type;

      if (isset($data['main']['temp'])) {
        $temperature = round($data['main']['temp'] - 273);
        $weather_data[$city] = $temperature;
      }
    }

    return $weather_data;
  }

  /**
   * Format weather data into an array of list items.
   */
  private function formatData($weather_data): array {
    $formatted_data = [];

    foreach ($weather_data as $city => $temperature) {
      if ($city !== 'weather-type') {
        $formatted_data[] = $this->t('@city: @temperature°C', [
          '@city' => $city,
          '@temperature' => $temperature,
        ]);
      }
    }
    return $formatted_data;
  }

}
