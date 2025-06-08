<?php

namespace Drupal\events_management\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for the events map view.
 */
class EventsMapController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new EventsMapController object.
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
   * Renders the map view page.
   *
   * @return array
   *   Render array for the map page.
   */
  public function mapView() {
    return [
      '#theme' => 'events_map',
      '#attached' => [
        'library' => ['events_management/map'],
        'drupalSettings' => [
          'eventsManagement' => [
            'mapJsonUrl' => $this->getEventsJsonUrl(),
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
    return \Drupal::urlGenerator()->generateFromRoute('events_management.map_events_json');
  }

  /**
   * Returns events as JSON for the map.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response containing events data.
   */
  public function getEventsJson(Request $request) {
    // For demonstration purposes, we'll use sample coordinates 
    // In a real implementation, you would store geocoded coordinates for each city
    // or use a geocoding service to get coordinates
    $cityCoordinates = [
      'New York' => ['lat' => 40.7128, 'lng' => -74.0060],
      'London' => ['lat' => 51.5074, 'lng' => -0.1278],
      'Berlin' => ['lat' => 52.5200, 'lng' => 13.4050],
      'Paris' => ['lat' => 48.8566, 'lng' => 2.3522],
      'Sofia' => ['lat' => 42.6977, 'lng' => 23.3219],
      'Toronto' => ['lat' => 43.6532, 'lng' => -79.3832],
      'Sydney' => ['lat' => -33.8688, 'lng' => 151.2093],
      'Los Angeles' => ['lat' => 34.0522, 'lng' => -118.2437],
      'Chicago' => ['lat' => 41.8781, 'lng' => -87.6298],
      'San Francisco' => ['lat' => 37.7749, 'lng' => -122.4194],
      'Boston' => ['lat' => 42.3601, 'lng' => -71.0589],
      'Seattle' => ['lat' => 47.6062, 'lng' => -122.3321],
      'Manchester' => ['lat' => 53.4808, 'lng' => -2.2426],
      'Birmingham' => ['lat' => 52.4862, 'lng' => -1.8904],
      'Liverpool' => ['lat' => 53.4084, 'lng' => -2.9916],
      'Edinburgh' => ['lat' => 55.9533, 'lng' => -3.1883],
      'Glasgow' => ['lat' => 55.8642, 'lng' => -4.2518],
      'Munich' => ['lat' => 48.1351, 'lng' => 11.5820],
      'Hamburg' => ['lat' => 53.5511, 'lng' => 9.9937],
      'Frankfurt' => ['lat' => 50.1109, 'lng' => 8.6821],
      'Cologne' => ['lat' => 50.9375, 'lng' => 6.9603],
      'Dresden' => ['lat' => 51.0504, 'lng' => 13.7373],
      'Lyon' => ['lat' => 45.7640, 'lng' => 4.8357],
      'Marseille' => ['lat' => 43.2965, 'lng' => 5.3698],
      'Nice' => ['lat' => 43.7102, 'lng' => 7.2620],
      'Bordeaux' => ['lat' => 44.8378, 'lng' => -0.5792],
      'Toulouse' => ['lat' => 43.6047, 'lng' => 1.4442],
      'Plovdiv' => ['lat' => 42.1354, 'lng' => 24.7453],
      'Varna' => ['lat' => 43.2141, 'lng' => 27.9147],
      'Burgas' => ['lat' => 42.5048, 'lng' => 27.4626],
      'Ruse' => ['lat' => 43.8564, 'lng' => 25.9705],
      'Stara Zagora' => ['lat' => 42.4280, 'lng' => 25.6349],
      'Vancouver' => ['lat' => 49.2827, 'lng' => -123.1207],
      'Montreal' => ['lat' => 45.5017, 'lng' => -73.5673],
      'Calgary' => ['lat' => 51.0447, 'lng' => -114.0719],
      'Ottawa' => ['lat' => 45.4215, 'lng' => -75.6972],
      'Edmonton' => ['lat' => 53.5461, 'lng' => -113.4938],
      'Melbourne' => ['lat' => -37.8136, 'lng' => 144.9631],
      'Brisbane' => ['lat' => -27.4698, 'lng' => 153.0251],
      'Perth' => ['lat' => -31.9505, 'lng' => 115.8605],
      'Adelaide' => ['lat' => -34.9285, 'lng' => 138.6007],
      'Canberra' => ['lat' => -35.2809, 'lng' => 149.1300],
    ];
    
    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'event')
      ->condition('status', 1)
      ->accessCheck(TRUE);
    
    // Filter for upcoming events only
    $query->condition('field_event_start_date', date('Y-m-d\TH:i:s'), '>=');
    $query->sort('field_event_start_date', 'ASC');
    
    $nids = $query->execute();
    $events = [];
    
    if (!empty($nids)) {
      $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);
      
      foreach ($nodes as $node) {
        // Skip events without dates or locations.
        if ($node->field_event_start_date->isEmpty() || 
            $node->field_event_city->isEmpty() ||
            !$node->field_event_city->entity) {
          continue;
        }
        
        $city_name = $node->field_event_city->entity->label();
        
        // Skip if we don't have coordinates for this city.
        if (!isset($cityCoordinates[$city_name])) {
          continue;
        }
        
        // Get the start date.
        $start_date = $node->field_event_start_date->value;
        $formatted_date = date('F j, Y', strtotime($start_date));
        
        // Get category if available.
        $category = '';
        
        if (!$node->field_event_category->isEmpty() && $node->field_event_category->entity) {
          $category = $node->field_event_category->entity->label();
        }
        
        // Get country if available.
        $country = '';
        if (!$node->field_event_country->isEmpty() && $node->field_event_country->entity) {
          $country = $node->field_event_country->entity->label();
        }
        
        // Build the event data.
        $events[] = [
          'id' => $node->id(),
          'title' => $node->getTitle(),
          'start_date' => $formatted_date,
          'url' => $node->toUrl()->toString(),
          'category' => $category,
          'city' => $city_name,
          'country' => $country,
          'lat' => $cityCoordinates[$city_name]['lat'],
          'lng' => $cityCoordinates[$city_name]['lng'],
        ];
      }
    }
    
    return new JsonResponse($events);
  }

} 