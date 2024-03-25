<?php

namespace Drupal\batch_api_drush\Commands;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 */
final class BatchApiDrushCommands extends DrushCommands {
  use DependencySerializationTrait;
  use StringTranslationTrait;

  /**
   * Constructs a BatchApiDrushCommands object.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly MessengerInterface $messenger,
    protected EntityRepositoryInterface $entityRepository,
  ) {
    parent::__construct();
  }

  /**
   * Triggers the batch process to update node body formats.
   *
   * @command batch_basic_html:triggerPostUpdate
   * @aliases batch_format_custom
   */
  public function triggerPostUpdate() {
    $nodes = $this->getNodes();
    $format = 'basic_html';

    $batch = [
      'title' => $this->t('Change text format on basic_html'),
      'operations' => [
        [[$this, 'processItems'], [$nodes, $format]],
      ],
      'finished' => [$this, 'finished'],
      'progress_message' => $this->t('Progress is: @current of @total'),
    ];

    if (!empty($batch)) {
      batch_set($batch);
    }
    drush_backend_batch_process();
  }

  /**
   * Batch operation callback function to process items.
   */
  public function processItems($items, $format, array &$context): array {
    $limit = 50;

    if (empty($context['sandbox']['progress'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['max'] = count($items);
    }

    if (empty($context['sandbox']['items'])) {
      $context['sandbox']['items'] = $items;
    }

    $counter = 0;
    if (!empty($context['sandbox']['items'])) {
      if ($context['sandbox']['progress'] != 0) {
        array_splice($context['sandbox']['items'], 0, $limit);
      }

      $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($context['sandbox']['items']);

      foreach ($nodes as $node) {
        if ($counter != $limit) {
          $this->processItem($node, $format);

          $counter++;
          $context['sandbox']['progress']++;

          $context['message'] = $this->t('Now processing node @progress of @max', [
            '@progress' => $context['sandbox']['progress'],
            '@max' => $context['sandbox']['max'],
          ]);

          $context['results']['processed'] = $context['sandbox']['progress'];
        }
      }
    }
    if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }
    return $context;
  }

  /**
   * Batch operation callback function to process item.
   */
  public function processItem($node, $new_text_format): void {
    $node->get('body')->format = $new_text_format;
    $node->save();
  }

  /**
   * Callback function to indicate batch processing is finished.
   */
  public function finished($success, $results, $operations): void {
    $message = $this->t('Number of nodes affected by batch: @processed', ['@processed' => $results['processed']]);
    $this->messenger->addStatus($message);
  }

  /**
   * Get all nodes with type 'article'.
   */
  public function getNodes(): int|array {
    return $this->entityTypeManager->getStorage('node')
      ->getQuery()
      ->condition('status', NodeInterface::PUBLISHED)
      ->condition('type', 'article')
      ->accessCheck(FALSE)
      ->execute();
  }

}
