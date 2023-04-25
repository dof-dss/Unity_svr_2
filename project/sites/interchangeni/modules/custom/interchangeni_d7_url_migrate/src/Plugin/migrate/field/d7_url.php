<?php

namespace Drupal\interchangeni_d7_url_migrate\Plugin\migrate\field;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_drupal\Annotation\MigrateField;
use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;

/**
 * MigrateField Plugin for Drupal 7 URL field to Drupal 9 email field.
 *
 * @MigrateField(
 *   id = "d7_url",
 *   core = {7},
 *   type_map = {
 *     "url" = "link"
 *   },
 *   source_module = "url",
 *   destination_module = "link"
 * )
 */
class d7_url extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFieldWidgetMap() {
    return [
      'url_external' => 'link_default',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldFormatterMap() {
    return [
      'url_default' => 'link',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function processFieldValues(MigrationInterface $migration, $field_name, $data) {
    $process = [
      'plugin' => 'sub_process',
      'source' => $field_name,
      'process' => [
        'uri' => 'value',
      ],
    ];
    $migration->setProcessOfProperty($field_name, $process);
  }

}
