<?php

namespace Drupal\events_management\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for the events calendar view.
 */
class EventsCalendarController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new EventsCalendarController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Renders the calendar view page.
   *
   * @return array
   *   Render array for the calendar page.
   */
  public function calendarView() {
    return [
      '#theme' => 'events_calendar',
      '#attached' => [
        'library' => ['events_management/calendar'],
        'drupalSettings' => [
          'eventsManagement' => [
            'calendarJsonUrl' => $this->getEventsJsonUrl(),
          ],
        ],
      ],
      '#cache' => [
        'tags' => ['node_list:event'],
        'contexts' => ['url.query_args'],
      ],
    ];
  }

  /**
   * Get the events JSON URL.
   *
   * @return string
   *   The URL to the events JSON endpoint.
   */
  protected function getEventsJsonUrl() {
    return \Drupal::urlGenerator()->generateFromRoute('events_management.calendar_events_json');
  }

  /**
   * Returns events as JSON for the calendar.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response containing events data.
   */
  public function getEventsJson(Request $request) {
    // Get start and end dates from request if available.
    $start = $request->query->get('start');
    $end = $request->query->get('end');
    
    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'event')
      ->condition('status', 1)
      ->sort('field_event_start_date', 'ASC')
      ->accessCheck(TRUE);
    
    // Filter by date range if provided.
    if ($start && $end) {
      $query->condition('field_event_start_date', $end, '<=');
      $query->condition('field_event_end_date', $start, '>=');
    }
    
    $nids = $query->execute();
    $events = [];
    
    if (!empty($nids)) {
      $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);
      
      foreach ($nodes as $node) {
        // Skip events without dates.
        if ($node->field_event_start_date->isEmpty()) {
          continue;
        }
        
        // Get the start date.
        $start_date = $node->field_event_start_date->value;
        
        // Get the end date, use start date if end date isn't set.
        $end_date = !$node->field_event_end_date->isEmpty() 
          ? $node->field_event_end_date->value 
          : $start_date;
        
        // Get category if available.
        $category = '';
        $backgroundColor = '#4e9af1'; // Default color.
        
        if (!$node->field_event_category->isEmpty()) {
          $category_term = $node->field_event_category->entity;
          if ($category_term) {
            $category = $category_term->label();
            
            // Assign different colors based on category name.
            switch (strtolower($category)) {
              case 'conference':
                $backgroundColor = '#4e9af1'; // Blue
                break;
              case 'seminar':
                $backgroundColor = '#ff6b6b'; // Red
                break;
              case 'workshop':
                $backgroundColor = '#20c997'; // Green
                break;
              case 'meeting':
                $backgroundColor = '#ffa94d'; // Orange
                break;
              case 'webinar':
                $backgroundColor = '#cc5de8'; // Purple
                break;
              default:
                $backgroundColor = '#4e9af1'; // Default blue
            }
          }
        }
        
        // Get location if available.
        $location = '';
        if (!$node->field_event_city->isEmpty() && !$node->field_event_country->isEmpty()) {
          $city = $node->field_event_city->entity ? $node->field_event_city->entity->label() : '';
          $country = $node->field_event_country->entity ? $node->field_event_country->entity->label() : '';
          
          if ($city && $country) {
            $location = $city . ', ' . $country;
          }
        }
        
        // Build the event data.
        $events[] = [
          'id' => $node->id(),
          'title' => $node->getTitle(),
          'start' => $start_date,
          'end' => $end_date,
          'url' => $node->toUrl()->toString(),
          'backgroundColor' => $backgroundColor,
          'borderColor' => $backgroundColor,
          'category' => $category,
          'location' => $location,
          'allDay' => false,
        ];
      }
    }
    
    return new JsonResponse($events);
  }

} 