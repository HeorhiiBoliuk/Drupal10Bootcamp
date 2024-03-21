<?php

namespace Drupal\registration_form_custom\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block for the popup registration form.
 *
 * @Block(
 *   id = "register_form_popup",
 *   admin_label = @Translation("Register form popup"),
 * )
 */
class RegisterFormPopup extends BlockBase implements ContainerFactoryPluginInterface {
  public function __construct(array $configuration,
  $plugin_id,
  $plugin_definition,
    protected LinkGeneratorInterface $link_generator,
  protected AccountProxyInterface $current_user,
  protected UrlGeneratorInterface $url_generator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('link_generator'),
      $container->get('current_user'),
      $container->get('url_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $url = Url::fromRoute('user.register');
    $url->setOptions([
      'attributes' => [
        'class' => ['use-ajax', 'register-popup-form'],
        'data-dialog-type' => 'modal',
      ],
    ]);
    $link = $this->link_generator->generate(t('Register'), $url);
    $build = [];

    if ($this->current_user->isAnonymous()) {
      $build['register_popup_block']['#markup'] = '<div class="Register-popup-link">' . $link . '</div>';
    }

    $build['#attached']['library'][] = 'core/drupal.dialog.ajax';

    return $build;
  }

}
