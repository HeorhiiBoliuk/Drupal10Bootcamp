<?php

/**
 * @file
 */

/**
 * Implements hook_views_data().
 */
function custom_map_plugin_views_data() {
  $data = [];

  $data['views']['custom_map_settings'] = [
    'title' => t('Custom Map Settings'),
    'help' => t('Provides settings for a custom map view area.'),
    'area' => [
      'id' => 'custom_map_settings',
    ],
  ];

  return $data;
}
