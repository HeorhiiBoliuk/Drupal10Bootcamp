<?php

namespace Drupal\custom_migration_users\Plugin\migrate\destination;

use Drupal\migrate\Annotation\MigrateDestination;
use Drupal\migrate\Plugin\MigrateDestinationInterface;
use Drupal\migrate\Row;

/**
 * Custom migrate destination plugin for users extra fields.
 *
 * @MigrateDestination(
 *   id = "my_custom_fields"
 * )
 */
class MyCustomFields implements MigrateDestinationInterface {

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
    $uid = $row->getSourceProperty('uid');
    $country = $row->getSourceProperty('country');
    $city_name = $row->getSourceProperty('city_name');
    $interested = $row->getSourceProperty('interested');
    \Drupal::database()->upsert('extra_field_register')
      ->fields([
        'uid' => $uid,
        'country' => $country,
        'interested' => $interested,
        'city_name' => $city_name,
      ])
      ->key('uid')
      ->execute();
  }

  /**
   * {@inheritDoc}
   */
  public function rollback(array $destination_identifier): void {
    if ($this->supportsRollback() && isset($destination_identifier['uid'])) {
      $uid = $destination_identifier['uid'];
      \Drupal::database()->delete('extra_field_register')
        ->condition('uid', $uid)
        ->execute();
    }
  }

  /**
   * {@inheritDoc}
   */
  public function supportsRollback(): bool {
    return TRUE;
  }

  /**
   * {@inheritDoc}
   */
  public function rollbackAction(): int|string {
    return 'delete';
  }

  /**
   * {@inheritDoc}
   */
  public function getDestinationModule(): ?string {
    return 'custom_registration_form';
  }

  /**
   * {@inheritDoc}
   */
  public function getPluginId(): string {
    return 'my_custom_fields';
  }

  /**
   * {@inheritDoc}
   */
  public function getPluginDefinition(): array {
    return [
      'uid' => 'Users id',
      'country' => 'Countries',
      'city_name' => 'City name',
      'interested' => 'Interested in',
    ];
  }

}
