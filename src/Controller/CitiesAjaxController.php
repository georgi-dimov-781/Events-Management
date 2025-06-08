<?php

namespace Drupal\events_management\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Controller for AJAX operations related to cities.
 */
class CitiesAjaxController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new CitiesAjaxController object.
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
   * Get cities by country via AJAX.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with cities data.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   *   Thrown when country_id is missing from the request.
   */
  public function getCitiesByCountry(Request $request) {
    $country_id = $request->query->get('country_id');
    if (empty($country_id)) {
      throw new BadRequestHttpException('Country ID is required.');
    }
    
    $cities = [];
    
    try {
      // Load the country term
      $country = $this->entityTypeManager->getStorage('taxonomy_term')->load($country_id);
      if (!$country || $country->bundle() !== 'event_countries') {
        return new JsonResponse([
          'error' => $this->t('Invalid country selected.'),
          'cities' => [],
        ]);
      }
      
      // Query for cities that reference the selected country
      $city_ids = $this->entityTypeManager->getStorage('taxonomy_term')->getQuery()
        ->condition('vid', 'event_cities')
        ->condition('field_country_reference.target_id', $country_id)
        ->accessCheck(FALSE)
        ->execute();
        
      if (!empty($city_ids)) {
        $city_terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadMultiple($city_ids);
        foreach ($city_terms as $term) {
          $cities[$term->id()] = $term->label();
        }
      }
      
      return new JsonResponse(['cities' => $cities]);
    }
    catch (\Exception $e) {
      // Log the error
      $this->getLogger('events_management')->error('Error fetching cities: @error', [
        '@error' => $e->getMessage(),
      ]);
      
      return new JsonResponse([
        'error' => $this->t('An error occurred while fetching cities.'),
        'cities' => [],
      ]);
    }
  }

} 