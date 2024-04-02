<?php

namespace Drupal\custom_location\Plugin\search_api\data_type;

use Drupal\search_api\DataType\DataTypePluginBase;

/**
 * Provides a custom location data type.
 *
 * @SearchApiDataType(
 *   id = "custom_location",
 *   label = @Translation("Location Custom"),
 *   description = @Translation("Custom location field type."),
 *   fallback_type = "text",
 *   prefix = "rpt"
 * )
 */
class CustomLocation extends DataTypePluginBase {}
