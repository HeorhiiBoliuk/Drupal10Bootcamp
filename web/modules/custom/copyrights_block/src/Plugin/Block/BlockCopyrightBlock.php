<?php

namespace Drupal\copyrights_block\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a block copyright block.
 */
#[Block(
id: "copyright_block",
admin_label: new TranslatableMarkup("Copyright Block"),
)]
final class BlockCopyrightBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array {
    $form['url'] = [
      '#markup' => 'Copyrights text can be edited <a href="http://my-site.ddev.site/admin/structure/config_pages">here</a>',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $config = \Drupal::entityTypeManager()->getStorage('config_pages')->load('global_configurations');
    $token_text = $config->get('field_copyright')->value;
    $copyright_text = \Drupal::token()->replace($token_text);
    $build['content'] = [
      '#markup' => $copyright_text,
      '#cache' => ['tags' => ['config_pages:1']],
    ];
    return $build;
  }

}
