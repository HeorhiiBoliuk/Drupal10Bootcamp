<?php

namespace Drupal\registration_form_custom\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;

/**
 *  Creating a block for popup register form.
 */
#[Block(
  id: "register_form_popup",
  admin_label: new TranslatableMarkup("Register form popup"),
)]
class RegisterFormPopup extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $url = Url::fromRoute('user.register');
    $link_options = [
      'attributes' => [
        'class' => [
          'use-ajax',
          'register-popup-form',
        ],
        'data-dialog-type' => 'modal',
      ],
    ];
    $url->setOptions($link_options);
    $link = Link::fromTextAndUrl(t('Register'), $url)->toString();
    $build = [];
    if (\Drupal::currentUser()->isAnonymous()) {
      $build['register_popup_block']['#markup'] = '<div class="Register-popup-link">' . $link . '</div>';
    }
    $build['register_popup_block']['#attached']['library'][] = 'core/drupal.dialog.ajax';

    return $build;
  }

}
