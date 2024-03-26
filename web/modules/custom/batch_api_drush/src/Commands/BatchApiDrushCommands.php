<?php

namespace Drupal\batch_api_drush\Commands;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeInterface;
use Drush\Commands\DrushCommands;

/**
 * A custom Drush commandfile that call a BatchAPI.
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

    $batch = [
      'title' => $this->t('Change text format on basic_html'),
      'operations' => [
        [[$this, 'processItems'], []],
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
  public function processItems(array &$context): void {
    $limit = 50;
    $format = 'basic_html';
    $items = $this->entityTypeManager->getStorage('node')
      ->getQuery()
      ->condition('type', 'article')
      ->condition('body.format', $format, '<>')
      ->accessCheck(FALSE)
      ->range(0, $limit)
      ->execute();

    if (empty($items)) {
      $context['finished'] = 1;
      return;
    }

    $load_items = $this->entityTypeManager
      ->getStorage('node')
      ->loadMultiple($items);

    foreach ($load_items as $item) {
      $this->processItem($item, $format);
    }

    $context['finished'] = 0;
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

}
