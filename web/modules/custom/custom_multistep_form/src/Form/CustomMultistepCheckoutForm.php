<?php

namespace Drupal\custom_multistep_form\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Creates a custom multistep checkout form.
 */
class CustomMultistepCheckoutForm extends FormBase {

  /**
   * Steps names.
   */
  protected $stepNames = [
    1 => 'Products',
    2 => 'Delivery',
    3 => 'Payment',
  ];

  /**
   * Construct for form.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFact,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
    );
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'custom_multistep_checkout_form';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->configFact->getEditable('custom_multistep_form.settings');
    $finished = $form_state->getStorage()['finished'] ?? 0;
    $enabled_steps = 0;
    for ($i = 1; $i <= 3; $i++) {
      $step_config = $config->get($this->stepNames[$i]);
      if ($step_config['enabled']) {
        $enabled_steps++;
      }
    }
    /*
     * The last finished step is always enabled.
     * So i`m always adding the 1 to step collection if the steps is existing.
     */
    if ($enabled_steps > 0) {
      $enabled_steps++;
    }
    else {
      return [];
    }

    $page_num = $form_state->get('page_num') ?? 1;
    $progress_percentage = ($page_num / $enabled_steps) * 100;

    $form['links']['progress_bar'] = [
      '#theme' => 'progress_bar',
      '#percent' => $progress_percentage,
      '#message' => $this->t('@completed/@total in progress', ['@completed' => $page_num, '@total' => $enabled_steps]),
    ];

    for ($i = 1; $i <= $finished; $i++) {
      $form['links']['step_' . $i] = [
        '#type' => 'submit',
        '#value' => $this->t('Step @step', ['@step' => $i]),
        '#limit_validation_errors' => [],
        '#submit' => ['::switchStep'],
        '#ajax' => [
          'callback' => '::ajaxCallback',
          'wrapper' => 'custom-multistep-form-' . $page_num,
        ],
      ];
    }

    $step_order = [];
    for ($i = 1; $i <= 3; $i++) {
      $step_config = $config->get($this->stepNames[$i]);
      if ($step_config['enabled']) {
        $step_order[$i] = $step_config['order'];
      }
    }
    $page_num = $form_state->get('page_num') ?? 1;
    $form['#tree'] = TRUE;

    $form_position = array_search($page_num, $step_order);
    $form_state->set('page_num', $page_num);

    switch ($form_position) {
      case 1:
        return $this->formProductSelection($form, $form_state);

      case 2:
        return $this->formDelivery($form, $form_state);

      case 3:
        return $this->formPayment($form, $form_state);

      default:
        return $this->formFinish($form, $form_state);
    }
  }

  /**
   * Form step: Product selection.
   */
  public function formProductSelection(array $form, FormStateInterface $form_state): array {
    // Used in submit form.
    $form_state->set('page_name', 'product');
    $default_values = $form_state->getStorage();

    $form['description'] = [
      '#type' => 'item',
      '#title' => $this->t('Select product'),
    ];

    $form['#id'] = 'custom-multistep-form-' . $form_state->get('page_num') ?? 1;

    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Product'),
      '#description' => $this->t('Select product type.'),
      '#options' => [
        '1' => $this->t('Food'),
        '2' => $this->t('Drink'),
        '3' => $this->t('Other'),
      ],
      '#default_value' => $default_values["page_product_values"]["type"] ?? NULL,
      '#required' => TRUE,
    ];

    $this->actionsForm($form, $form_state);

    return $form;
  }

  /**
   * Form step: Delivery options.
   */
  public function formDelivery(array $form, FormStateInterface $form_state): array {
    // Used in submit form.
    $form_state->set('page_name', 'delivery');
    $default_values = $form_state->getStorage();

    $form['description'] = [
      '#type' => 'item',
      '#title' => $this->t('Select delivery options'),
    ];

    $form['#id'] = 'custom-multistep-form-' . $form_state->get('page_num') ?? 1;

    $form['country'] = [
      '#type' => 'select',
      '#title' => $this->t('Country'),
      '#options' => [$this->t('Ukraine'), $this->t('Turkey'), $this->t('Poland')],
      '#description' => $this->t('Select your country'),
      '#default_value' => $default_values["page_delivery_values"]["country"] ?? NULL,
      '#required' => TRUE,
    ];
    $form['city'] = [
      '#type' => 'select',
      '#title' => $this->t('City'),
      '#options' => [$this->t('Kherson'), $this->t('Lutsk'), $this->t('Kiev')],
      '#default_value' => $default_values["page_delivery_values"]["city"] ?? NULL,
      '#description' => $this->t('Select your city'),
      '#required' => TRUE,
    ];

    $this->actionsForm($form, $form_state);

    return $form;
  }

  /**
   * Form step: Payment.
   */
  public function formPayment(array $form, FormStateInterface $form_state): array {
    // Used in submit form.
    $form_state->set('page_name', 'payment');

    $form['#id'] = 'custom-multistep-form-' . $form_state->get('page_num') ?? 1;

    $form['description'] = [
      '#type' => 'item',
      '#title' => $this->t('Select payment options'),
    ];

    $form['card']['number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Card number'),
      '#required' => TRUE,
      '#default_value' => $default_values["page_payment_values"]["number"] ?? NULL,
      '#size' => 16,
      '#maxlength' => 16,
      '#minlength' => 15,
      '#description' => $this->t('Enter the card number'),
    ];
    $form['expiry_date'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Expiry date'),
      '#weight' => 3,
      '#required' => TRUE,
    ];

    $months = [];
    foreach (range(1, 12) as $month) {
      $month = str_pad($month, 2, '0', STR_PAD_LEFT);
      $months[$month] = $month;
    }
    $year = (int) date('Y');
    $form['expiry_date']['month'] = [
      '#type' => 'select',
      '#title' => $this->t('Month'),
      '#options' => $months,
      '#attributes' => ['class' => ['expiry-date']],
    ];
    $form['expiry_date']['year'] = [
      '#type' => 'select',
      '#title' => $this->t('Year'),
      '#default_value' => $year + 1,
      '#options' => array_combine(range($year, $year + 8), range($year, $year + 8)),
    ];

    $form['secure_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secure code'),
      '#weight' => 2,
      '#size' => 3,
      '#maxlength' => 3,
      '#minlength' => 3,
      '#required' => TRUE,
    ];

    $this->actionsForm($form, $form_state);

    return $form;
  }

  /**
   * Form step: Finish.
   */
  public function formFinish(array $form, FormStateInterface $form_state): array {
    // Used in submit form.
    $form_state->set('page_name', 'payment');

    $values = $form_state->getStorage();

    $summary = $this->t(
      'Your product: @product, delivery option: @delivery_option, @delivery_city, payment method: @payment_method, @card_month, @card_year, @secure_code',
      [
        '@product' => $values["page_product_values"]["type"] ?? '',
        '@secure_code' => $values["page_payment_values"]["secure_code"] ?? '',
        '@card_month' => $values["page_payment_values"]["expiry_date"]["month"] ?? '',
        '@card_year' => $values["page_payment_values"]["expiry_date"]["year"] ?? '',
        '@delivery_option' => $values["page_delivery_values"]["country"] ?? '',
        '@delivery_city' => $values["page_delivery_values"]["city"] ?? '',
        '@payment_method' => $values["page_payment_values"]['card']["number"] ?? '',
      ]);

    $form['summary'] = [
      '#type' => 'item',
      '#title' => $this->t('Summary'),
      '#markup' => $summary,
    ];

    $form['#id'] = 'custom-multistep-form-' . $form_state->get('page_num') ?? 1;

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#submit' => ['::resetForm'],
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * Custom submission handler for "Next" button.
   */
  public function submitForm(array &$form, FormStateInterface $form_state): array {
    $page_num = $form_state->get('page_num') ?? 1;
    $page_name = $form_state->get('page_name') ?? '';
    if ($page_num > $form_state->get('finished') ?? 0) {
      $form_state->set('finished', $page_num);
    }
    $page_num++;
    $form_state->set('page_num', $page_num);
    $values = $form_state->getValues();
    $form_state->set('page_' . $page_name . '_values', $values);
    $form_state->setRebuild(TRUE);
    return $form;
  }

  /**
   * Custom submission handler for "Back" button.
   */
  public function pageBack(array $form, FormStateInterface $form_state): array {
    $page_num = $form_state->get('page_num') ?? 1;
    $form_state->set('page_num', $page_num - 1);
    $form_state->setRebuild(TRUE);
    return $form;
  }

  /**
   * Redirect if user on the last step.
   */
  public function resetForm(array $form, FormStateInterface $form_state): void {
    $form_state->setRedirect('<front>');
  }

  /**
   * Custom AJAX callback method.
   */
  public function ajaxCallback(array &$form, FormStateInterface $form_state): array {
    return $form;
  }

  /**
   * Custom submission handler for switching steps.
   */
  public function switchStep(array &$form, FormStateInterface $form_state): void {
    $triggered = $form_state->getTriggeringElement();
    $step_number = $triggered['#value']->getArguments()['@step'];
    $form_state->set('page_num', $step_number);
    $form_state->setRebuild(TRUE);
  }

  /**
   * Return the form with actions for forms.
   */
  public function actionsForm(array &$form, FormStateInterface $form_state): array {
    $page_num = $form_state->get('page_num') ?? 1;

    $form['actions'] = [
      '#type' => 'actions',
    ];

    if ($page_num != 1) {
      $form['actions']['back'] = [
        '#type' => 'submit',
        '#value' => $this->t('Back'),
        '#button_type' => 'primary',
        '#limit_validation_errors' => [],
        '#submit' => ['::pageBack'],
        '#ajax' => [
          'callback' => '::ajaxCallback',
          'wrapper' => 'custom-multistep-form-' . $page_num,
        ],
      ];
    }

    $form['actions']['next'] = [
      '#type' => 'submit',
      '#value' => $this->t('Next'),
      '#button_type' => 'primary',
      '#submit' => ['::submitForm'],
      '#ajax' => [
        'callback' => '::ajaxCallback',
        'wrapper' => 'custom-multistep-form-' . $page_num,
      ],
    ];

    return $form;
  }

}
