<?php

namespace Drupal\events_management\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Latest Events' Block.
 *
 * @Block(
 *   id = "latest_events_block",
 *   admin_label = @Translation("Latest Events"),
 *   category = @Translation("Events Management"),
 * )
 */
class LatestEventsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new LatestEventsBlock instance.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'event')
      ->condition('status', 1)
      ->condition('field_event_start_date', date('Y-m-d\TH:i:s'), '>=')
      ->sort('field_event_start_date', 'ASC')
      ->range(0, 3)
      ->accessCheck(TRUE);

    $nids = $query->execute();

    $events = [];
    $view_builder = $this->entityTypeManager->getViewBuilder('node');
    
    if (!empty($nids)) {
      $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);
      foreach ($nodes as $node) {
        $events[] = [
          'entity' => $node,
          'content' => $view_builder->view($node, 'teaser'),
        ];
      }
    }

    return [
      '#theme' => 'latest_events_block',
      '#events' => $events,
      '#cache' => [
        'tags' => ['node_list:event'],
        'max-age' => 3600, // Cache for 1 hour
      ],
    ];
  }

} 