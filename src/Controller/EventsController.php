<?php

namespace Drupal\events_management\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Pager\PagerManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for events management pages.
 */
class EventsController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The pager manager.
   *
   * @var \Drupal\Core\Pager\PagerManagerInterface
   */
  protected $pagerManager;

  /**
   * Constructs a new EventsController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Pager\PagerManagerInterface $pager_manager
   *   The pager manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory, PagerManagerInterface $pager_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
    $this->pagerManager = $pager_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
      $container->get('pager.manager')
    );
  }

  /**
   * Events listing page.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return array
   *   Render array for the events listing page.
   */
  public function eventsList(Request $request) {
    $config = $this->configFactory->get('events_management.settings');
    $events_per_page = $config->get('events_per_page') ?? 10;

    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'event')
      ->condition('status', 1)
      ->condition('field_event_start_date', date('Y-m-d\TH:i:s'), '>=')
      ->sort('field_event_start_date', 'ASC')
      ->accessCheck(TRUE);

    $query->pager($events_per_page);
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
      '#theme' => 'events_listing',
      '#events' => $events,
      '#attached' => [
        'library' => ['events_management/frontend']
      ],
      '#pager' => [
        '#type' => 'pager',
      ],
      '#cache' => [
        'tags' => ['node_list:event'],
        'contexts' => ['url.query_args'],
      ],
    ];
  }

  /**
   * Past events listing page.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return array
   *   Render array for the past events listing page.
   */
  public function pastEventsList(Request $request) {
    $config = $this->configFactory->get('events_management.settings');
    $events_per_page = $config->get('events_per_page') ?? 10;

    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'event')
      ->condition('status', 1)
      ->condition('field_event_end_date', date('Y-m-d\TH:i:s'), '<')
      ->sort('field_event_end_date', 'DESC') // Most recently ended events first
      ->accessCheck(TRUE);

    $query->pager($events_per_page);
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
      '#theme' => 'past_events_listing',
      '#events' => $events,
      '#attached' => [
        'library' => ['events_management/frontend']
      ],
      '#pager' => [
        '#type' => 'pager',
      ],
      '#cache' => [
        'tags' => ['node_list:event'],
        'contexts' => ['url.query_args'],
      ],
    ];
  }

  /**
   * Event detail page.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The node entity.
   *
   * @return array
   *   Render array for the event detail page.
   */
  public function eventDetail($node) {
    if (!$node || $node->bundle() !== 'event') {
      throw new NotFoundHttpException();
    }

    $view_builder = $this->entityTypeManager->getViewBuilder('node');
    $content = $view_builder->view($node, 'full');

    return [
      '#theme' => 'event_detail',
      '#event' => $node,
      '#content' => $content,
      '#attached' => [
        'library' => ['events_management/frontend']
      ],
      '#cache' => [
        'tags' => $node->getCacheTags(),
      ],
    ];
  }

  /**
   * Get title for event detail page.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The node entity.
   *
   * @return string
   *   The title of the event.
   */
  public function eventDetailTitle($node) {
    if (!$node || $node->bundle() !== 'event') {
      return $this->t('Event not found');
    }
    return $node->label();
  }

} 