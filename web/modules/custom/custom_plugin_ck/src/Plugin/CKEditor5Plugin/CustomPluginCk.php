<?php

namespace Drupal\custom_plugin_ck\Plugin\CKEditor5Plugin;

use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableInterface;
use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableTrait;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefault;
use Drupal\ckeditor5\Plugin\CKEditor5PluginElementsSubsetInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\EditorInterface;

/**
 * Class CustomPluginCk.
 */
class CustomPluginCk extends CKEditor5PluginDefault implements CKEditor5PluginConfigurableInterface, CKEditor5PluginElementsSubsetInterface {

  use CKEditor5PluginConfigurableTrait;

  const T_CONTEXT = ['context' => 'CIHI Demo Link'];

  /**
   * {@inheritdoc}
   */
  public function getElementsSubset(): array {
    return ['<p>', '<a>', '<span>'];
  }

  const ALWAYS_ENABLED_COLOR = [
    '#000',
  ];

  /**
   * {@inheritdoc}
   */
  public function getChoosedColors() {
    return array_merge(
      self::ALWAYS_ENABLED_COLOR,
      $this->configuration['enabled_types'] ?? []
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDynamicPluginConfig(array $static_plugin_config, EditorInterface $editor): array {
    $enabled_types = $this->getChoosedColors();
    return [
      'colors_config' => $enabled_types,
    ];
  }

  const DEFAULT_CONFIGURATION = [
    'enabled_types' => [
      'color' => '',
      'background_color' => '',
    ],
  ];

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return static::DEFAULT_CONFIGURATION;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['enabled_types'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Enabled Types'),
    ];

    $form['enabled_types']['color'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Color'),
      '#description' => $this->t('Choose your default color.'),
      '#default_value' => $this->configuration['enabled_types']['color'] ?? '',
      '#rows' => 1,
    ];

    $form['enabled_types']['background_color'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Background color'),
      '#description' => $this->t('Choose your default background color.'),
      '#default_value' => $this->configuration['enabled_types']['background_color'] ?? '',
      '#rows' => 1,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Validation logic if needed.
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['enabled_types']['color'] = $form_state->getValue(['enabled_types', 'color']);
    $this->configuration['enabled_types']['background_color'] = $form_state->getValue(['enabled_types', 'background_color']);
  }

}

