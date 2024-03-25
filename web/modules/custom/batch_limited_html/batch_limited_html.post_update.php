<?php

/**
 * @file
 */

use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

/**
 * Implements hook_post_update_NAME().
 */
function batch_limited_html_post_update_format(&$sandbox) {
  $nodes = get_nodes();
  $format = 'limited_html';

  $limit = 50;

  if (empty($sandbox['progress'])) {
    $sandbox['progress'] = 0;
    $sandbox['max'] = count($nodes);
  }
  if (empty($sandbox['items'])) {
    $sandbox['items'] = $nodes;
  }

  if (empty($sandbox['max'])) {
    $sandbox['#finished'] = 1;
  }

  $counter = 0;
  if (!empty($sandbox['items'])) {
    if ($sandbox['progress'] != 0) {
      array_splice($sandbox['items'], 0, $limit);
    }

    foreach ($sandbox['items'] as $item) {
      if ($counter != $limit) {
        process_item($item, $format);

        $counter++;
        $sandbox['progress']++;

        $sandbox['processed'] = $sandbox['progress'];
      }
    }
    if ($sandbox['progress'] != $sandbox['max']) {
      $sandbox['#finished'] = $sandbox['progress'] / $sandbox['max'];
    }
  }
}

/**
 * Batch operation callback function to process item.
 */
function process_item($node, $new_text_format) {
  $node->get('body')->format = $new_text_format;
  $node->save();
}

/**
 * Get all nodes.
 */
function get_nodes() {
  $query = \Drupal::entityQuery('node');
  $query->condition('status', NodeInterface::PUBLISHED);
  $query->condition('type', 'article');
  $query->accessCheck(FALSE);
  $nids = $query->execute();

  return Node::loadMultiple($nids);
}
