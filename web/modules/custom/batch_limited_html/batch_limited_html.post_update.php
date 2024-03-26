<?php

/**
 * @file
 */

use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

/**
 * Implements hook_post_update_NAME().
 */
function batch_limited_html_post_update_format009(&$sandbox) {
  $limit = 50;
  $format = 'limited_html';

  $query = \Drupal::entityQuery('node')
    ->accessCheck(FALSE)
    ->condition('body.format', $format, '<>')
    ->range(0, $limit);
  $nodes = $query->execute();

  if (empty($nodes)) {
    $sandbox['#finished'] = 1;
    return;
  }

  $load_items = \Drupal::entityTypeManager()
    ->getStorage('node')
    ->loadMultiple($nodes);

  foreach ($load_items as $item) {
    process_item($item, $format);
  }

  $sandbox['#finished'] = 0;
}

/**
 * Batch operation callback function to process item.
 */
function process_item($node, $new_text_format) {
  $node->get('body')->format = $new_text_format;
  $node->save();
}
