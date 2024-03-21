<?php

namespace Drupal\registration_form_custom\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Locale\CountryManager;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\registration_form_custom\Services\ExtraFieldsHandler;
use Drupal\user\RegisterForm;
use Drupal\weather_block\Services\FetchApiData;
use Drupal\weather_block\Services\UserWeatherHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a user register form.
 */
class NewUserRegisterForm extends RegisterForm {

  /**
   * {@inheritDoc}
   */
  public function __construct(EntityRepositoryInterface $entity_manager,
    EntityRepositoryInterface $entity_repository,
    LanguageManagerInterface $language_manager,
    EntityTypeBundleInfoInterface $entityTypeBundleInfo = NULL,
    TimeInterface $time = NULL,
    ModuleHandlerInterface $moduleHandler,
    public UserWeatherHandler $cityService,
    private CountryManager $countryManager,
    private EntityTypeManager $typeManager,
    protected AccountProxyInterface $accountProxy,
    protected FetchApiData $apiData,
    protected ConfigFactoryInterface $configFact,
    protected Connection $database,
    protected ExtraFieldsHandler $extraFieldsHandler,
  ) {

    parent::__construct($entity_repository, $language_manager, $entityTypeBundleInfo, $time);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity.repository'),
      $container->get('language_manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('module_handler'),
      $container->get('weather_block.get_set_city_user'),
      $container->get('country_manager'),
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('weather_block.fetch_api_data'),
      $container->get('config.factory'),
      $container->get('database'),
      $container->get('register_form_custom.extra_fields_handler'),
    );
  }

  /**
   * Return form ID.
   */
  public function getFormId() {
    return 'custom_registration_form';
  }

  /**
   * {@inheritDoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {

    $form = parent::form($form, $form_state);
    $cities = $this->cityService->getCitiesArray();
    $term_options = $this->getTaxonomyTerms();

    $form['password'] = [
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#description' => $this->t('Input your password'),
      '#required' => TRUE,
    ];
    $form['country'] = [
      '#type' => 'select',
      '#title' => $this->t('Country'),
      '#options' => $this->getCountryOptions(),
      '#default_value' => 'UA',
      '#description' => $this->t('Select your country'),
      '#required' => TRUE,
    ];
    $form['city'] = [
      '#type' => 'select',
      '#title' => $this->t('Choose your city'),
      '#options' => array_combine($cities, $cities),
      '#empty_option' => $this->t('-select-'),
      '#description' => $this->t('Select your city'),
      '#required' => TRUE,
    ];
    $form['interested'] = [
      '#type' => 'select',
      '#title' => $this->t('Interests'),
      '#options' => $term_options,
      '#description' => $this->t('Select your interest'),
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * Getting a list of Countries.
   */
  private function getCountryOptions(): array {
    $country_options = [];
    $countries = $this->countryManager->getList();
    foreach ($countries as $code => $name) {
      $country_options[$code] = $name;
    }
    return $country_options;
  }

  /**
   * Get a terms of taxonomy category.
   */
  private function getTaxonomyTerms(): array {
    $terms = $this->typeManager->getStorage('taxonomy_term')->loadTree('news');
    $term_options = [];
    foreach ($terms as $term) {
      $term_options[$term->tid] = $term->name;
    }
    return $term_options;
  }

  /**
   * {@inheritDoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    parent::submitForm($form, $form_state);
    $country_value = $form_state->getValue('country');
    $interest_value = $form_state->getValue('interested');
    $city_value = $form_state->getValue('city');
    $this->entity->activate();

    $form_state->setRedirect('<front>');
  }

  /**
   *
   */
  public function save(array $form, FormStateInterface $form_state): void {
    parent::save($form, $form_state);
    $country_value = $form_state->getValue('country');
    $interest_value = $form_state->getValue('interested');
    $city_value = $form_state->getValue('city');
    $userid = $this->entity->id();
    $this->cityService->saveCityForUser($userid, $city_value);

    $this->extraFieldsHandler->saveExtraFieldsForUser($userid, $country_value, $interest_value);
  }

}
