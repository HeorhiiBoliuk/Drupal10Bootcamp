<?php

namespace Drupal\custom_map_plugin\Plugin\views\area;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\area\AreaPluginBase;

/**
 * Provides settings for a custom map view area.
 *
 * @ingroup views_area_handlers
 *
 * @ViewsArea("custom_map_settings")
 */
class StoreLocations extends AreaPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['color'] = ['default' => '#FF0000'];
    $options['size'] = ['default' => '2'];
    $options['zoom'] = ['default' => '13'];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Map Color'),
      '#default_value' => $this->options['color'],
    ];

    $form['size'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Marker Size'),
      '#description' => $this->t('Enter the marker size (in pixels)'),
      '#default_value' => $this->options['size'],
    ];

    $form['zoom'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Map Zoom'),
      '#description' => $this->t('Enter the map zoom level'),
      '#default_value' => $this->options['zoom'],
    ];
  }

  /**
   * Return stores location.
   */
  public function getStoresLocation(): array {
    $locations = [];
    $location_entities = $this->view->result;
    foreach ($location_entities as $location_entity) {
      $location_values = $location_entity->_entity->get('field_location')->getValue();
      if (!empty($location_values)) {
        $latitude = $location_values[0]['lat'];
        $longitude = $location_values[0]['lon'];
        $locations[] = [
          'latitude' => $latitude,
          'longitude' => $longitude,
        ];
      }
    }
    return $locations;
  }

  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE) {
    $locations = $this->getStoresLocation();
    $current_display_id = $this->view->id();
    if (!$empty || !empty($this->options['empty'])) {
      $build['#markup'] = "<div class='leaflet-map' data-display-id='$current_display_id'></div>";
      $build['#attached']['drupalSettings']['customMapView'][$current_display_id] = [
        'color' => $this->options['color'],
        'size' => $this->options['size'],
        'zoom' => $this->options['zoom'],
      ];
      $build['#attached']['drupalSettings']['locations_stores'][$current_display_id][] = [
        'locations' => $locations,
      ];
      return $build;
    }
    return NULL;
  }

}
