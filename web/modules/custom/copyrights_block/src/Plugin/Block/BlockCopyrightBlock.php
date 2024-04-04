<?php

namespace Drupal\copyrights_block\Plugin\Block;

use Drupal\config_pages\ConfigPagesLoaderServiceInterface;
use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block copyright block.
 */
#[Block(
id: "copyright_block",
admin_label: new TranslatableMarkup("Copyright Block"),
)]
final class BlockCopyrightBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Constructor for BlockCopyrightBlock.
   */
  public function __construct(array $configuration,
    $plugin_id,
    $plugin_definition,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ConfigPagesLoaderServiceInterface $configPages,
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
      $container->get('entity_type.manager'),
      $container->get('config_pages.loader'),
    );
  }

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
    $field_copyright = $this->configPages->getFieldView('global_configurations', 'field_copyright');

    $build = [
      '#theme' => 'copyrights_block_template',
      '#field_copyright' => $field_copyright,
      '#cache' => ['tags' => ['config_pages:1']],
    ];
    return $build;
  }

}
