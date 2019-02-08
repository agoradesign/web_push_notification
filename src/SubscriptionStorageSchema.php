<?php

/**
 * Defines the subscription entity schema handler.
 */
class SubscriptionStorageSchema extends \Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getSharedTableFieldSchema(\Drupal\Core\Field\FieldStorageDefinitionInterface $storage_definition, $table_name, array $column_mapping) {
    $schema = parent::getSharedTableFieldSchema($storage_definition, $table_name, $column_mapping);

    return $schema;
  }

}