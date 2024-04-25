<?php

namespace Drupal\custom_multistep_form\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'FormConfigureBlock' block.
 */
#[Block(
  id: "form_configure_block",
  admin_label: new TranslatableMarkup("Configure Form Steps"),
)]
class FormConfigureBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Constructor for FormConfigureBlock.
   */
  public function __construct(array $configuration,
    $plugin_id,
    $plugin_definition,
    protected ConfigFactoryInterface $configFactory,
    protected FormBuilderInterface $formBuilder,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('form_builder'),
    );
  }

  /**
   * {@inheritDoc}
   */
  public function build(): array {
    return $this->formBuilder->getForm('\Drupal\custom_multistep_form\Form\CustomMultistepForm');
  }

  /**
   * Configuration for Form.
   */
  public function blockForm($form, FormStateInterface $form_state): array {
    $config = $this->configFactory->getEditable('custom_multistep_form.settings');

    $form['steps'] = [
      '#type' => 'details',
      '#title' => $this->t('Form Steps Configuration'),
      '#open' => TRUE,
      '#prefix' => '<div id="custom-configuration-form">',
      '#suffix' => '</div>',
    ];

    $form['steps']['step_order'] = [
      '#type' => 'table',
      '#header' => [$this->t('Step'), $this->t('Weight')],
      '#empty' => $this->t('No steps available.'),
    ];

    $active_steps_count = 0;
    for ($i = 1; $i <= 3; $i++) {
      if ($config->get("step_$i")) {
        $active_steps_count++;
      }
    }

    $options = [];
    for ($i = 1; $i <= $active_steps_count; $i++) {
      $options[$i] = $this->t('@step', ['@step' => $i]);
    }

    for ($i = 1; $i <= 3; $i++) {
      $step_weight = $config->get("step_order_$i");
      $step_enabled = $config->get("step_$i");

      $form['steps']['step_order'][$i]['step'] = [
        '#plain_text' => $this->t('Step @step', ['@step' => $i]),
      ];
      $form['steps']['step_order'][$i]['weight'] = [
        '#type' => 'select',
        '#options' => $options,
        '#default_value' => $step_weight ?? $i,
        '#attributes' => ['class' => ['step-weight']],
        '#access' => !($step_enabled == 0),
        '#ajax' => [
          'callback' => [$this, 'submitWeightAjax'],
          'wrapper' => 'custom-configuration-form',
        ],
      ];

      $form['steps']['step_order'][$i]['weight']['#validate'][] = [$this, 'validateFormBlock'];

      $form['steps']["step_$i"] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enable step @step', ['@step' => $i]),
        '#default_value' => $step_enabled ?? FALSE,
        '#access' => TRUE,
        '#ajax' => [
          'callback' => [$this, 'submitCheckboxAjax'],
          'wrapper' => 'custom-configuration-form',
        ],
      ];
    }

    $form['steps']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Configuration'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    for ($i = 1; $i <= 3; $i++) {
      $step_order = $form_state->getValue(['steps', 'step_order', $i, 'weight']);
      $enabled_steps = $form_state->getValue(['steps', "step_$i"]);
      $this->configFactory->getEditable('custom_multistep_form.settings')
        ->set("step_order_$i", $step_order)
        ->set("step_$i", $enabled_steps)
        ->save();
    }
  }

  /**
   * Custom AJAX callback method for weights.
   */
  public function submitWeightAjax(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild(TRUE);
    return $form["settings"]["steps"];
  }

  /**
   * Custom method to handle checkbox submission.
   */
  protected function handleCheckboxSubmission(array &$form, FormStateInterface $form_state): mixed {
    $config = $this->configFactory->getEditable('custom_multistep_form.settings');
    $complete_form = $form_state->getCompleteForm();

    $active_steps_count = 0;
    for ($i = 1; $i <= 3; $i++) {
      $form["settings"]['steps']['step_order'][$i]['weight']['#options'] = [];
      if ($config->get("step_$i")) {
        $active_steps_count++;
      }
    }

    $changed = FALSE;

    for ($i = 1; $i <= 3; $i++) {
      $step_status = $complete_form['settings']['steps']["step_$i"]['#value'];

      if ($step_status == 0) {
        if ($config->get("step_$i")) {
          $config
            ->set("step_$i", NULL)
            ->set("step_order_$i", NULL)
            ->save();
          $changed = TRUE;
          $form["settings"]["steps"]["step_order"][$i]["weight"]["#access"] = FALSE;
          $form_state->setValue(['settings', 'steps', 'step_order', $i, 'weight', '#access'], FALSE);
          $active_steps_count--;
        }
      }
      elseif ($step_status == 1) {
        if (!$config->get("step_$i")) {
          $config
            ->set("step_$i", 1)
            ->save();
          $changed = TRUE;
          $form["settings"]['steps']['step_order'][$i]['weight']['#access'] = TRUE;
          $form_state->setValue(['settings', 'steps', 'step_order', $i, 'weight', '#access'], TRUE);

          $active_steps_count++;

          for ($j = 1; $j <= $active_steps_count; $j++) {
            $form["settings"]['steps']['step_order'][$i]['weight']['#options'][$j] = $this->t('@step', ['@step' => $j]);
            $form_state->setValue(['settings', 'steps', 'step_order', $i, 'weight', 'options', $j], $this->t('@step', ['@step' => $j]));
          }
        }
      }
    }

    if ($changed) {
      for ($i = 1; $i <= 3; $i++) {
        $step_enabled = $config->get("step_$i");
        if ($step_enabled) {
          for ($j = 1; $j <= $active_steps_count; $j++) {
            $form["settings"]['steps']['step_order'][$i]['weight']['#options'][$j] = $this->t('@step', ['@step' => $j]);
            $form_state->setValue(['settings', 'steps', 'step_order', $i, 'weight', 'options', $j], $this->t('@step', ['@step' => $j]));
          }
        }
      }
    }
    return $form["settings"]["steps"];
  }

  /**
   * Custom AJAX callback method for checkboxes.
   */
  public function submitCheckboxAjax(array &$form, FormStateInterface $form_state): array {
    $updated_steps = $this->handleCheckboxSubmission($form, $form_state);
    $form_state->setRebuild(TRUE);

    return $updated_steps;
  }

  /**
   * Validation for steps weights.
   */
  public function validateFormBlock(array &$form, FormStateInterface $form_state): void {
    $selected_weights = [];
    $complete_form = $form_state->getCompleteForm();

    for ($i = 1; $i <= 3; $i++) {
      $weight = $complete_form["settings"]["steps"]["step_order"][$i]["weight"]["#value"];

      if (in_array($weight, $selected_weights)) {
        $error_message = $this->t('The weight @weight is already in use.', ['@weight' => $weight]);
        $this->messenger()->addError($error_message);
      }
      else {
        $selected_weights[] = $weight;
      }
    }
  }

}
