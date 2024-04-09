<?php

namespace Drupal\custom_migration_users\Plugin\migrate\destination;

use Drupal\Core\Database\Connection;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Plugin\migrate\destination\DestinationBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Custom migrate destination plugin for users extra fields.
 *
 * @MigrateDestination(
 *   id = "my_custom_fields"
 * )
 */
class MyCustomFields extends DestinationBase implements ContainerFactoryPluginInterface {

  /**
   * Constructor for DestinationPlugin class.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    MigrationInterface $migration,
    protected Connection $database,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
    MigrationInterface $migration = NULL,
  ): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('database'),
    );
  }

  /**
   * {@inheritDoc}
   */
  public function getIds(): array {
    return ['uid' => ['type' => 'integer']];
  }

  /**
   * {@inheritDoc}
   */
  public function fields(): array {
    return [
      ['uid' => ['type' => 'integer']],
      ['city_name' => ['type' => 'string']],
      ['country' => ['type' => 'string']],
      ['interested' => ['type' => 'string']],
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function import(Row $row, array $old_destination_id_values = []): void {
    $uid = $row->getDestinationProperty('uid');
    $country = $row->getDestinationProperty('country');
    $city_name = $row->getDestinationProperty('city_name');
    $interested = $row->getDestinationProperty('interested');
    $this->database->upsert('extra_field_register')
      ->fields([
        'uid' => $uid,
        'country' => $country,
        'interested' => $interested,
        'city_name' => $city_name,
      ])
      ->key('uid')
      ->execute();
  }

}
