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
use Drupal\user\Entity\User;
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
  ) {
    $user = User::create();
    $this->setEntity($user);
    $this->setModuleHandler($moduleHandler);

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
    $term_options = $this->getTaxonomyTerm();

    $form['password'] = [
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#description' => $this->t('Input your password'),
    ];
    $form['country'] = [
      '#type' => 'select',
      '#title' => $this->t('Country'),
      '#options' => $this->getCountryOptions(),
      '#default_value' => 'UA',
      '#description' => $this->t('Select your country'),
    ];
    $form['city'] = [
      '#type' => 'select',
      '#title' => $this->t('Choose your city'),
      '#options' => array_combine($cities, $cities),
      '#empty_option' => $this->t('-select-'),
      '#description' => $this->t('Select your city'),
    ];
    $form['interested'] = [
      '#type' => 'select',
      '#title' => $this->t('Interests'),
      '#options' => $term_options,
      '#description' => $this->t('Select your interests'),
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
   * Get a taxonomy terms.
   */
  private function getTaxonomyTerm(): array {
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
    $username = $form_state->getValue('name');
    $email = $form_state->getValue('mail');
    $password = $form_state->getValue('password');
    $this->entity->setUsername($username);
    $this->entity->setEmail($email);
    $this->entity->setPassword($password);
    $this->entity->activate();

    $form_state->setRedirect('<front>');
  }

}
