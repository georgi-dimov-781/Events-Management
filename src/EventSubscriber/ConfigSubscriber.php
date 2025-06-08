<?php

namespace Drupal\events_management\EventSubscriber;

use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber for configuration changes.
 */
class ConfigSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      ConfigEvents::SAVE => 'onConfigSave',
    ];
  }

  /**
   * React to configuration save events.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The configuration event.
   */
  public function onConfigSave(ConfigCrudEvent $event) {
    $config = $event->getConfig();
    
    // Only log changes to our module's configuration.
    if ($config->getName() === 'events_management.settings') {
      // Get the current configuration data.
      $new_data = $config->getRawData();
      
      // In Drupal 10/11, we need to handle this differently since 
      // there's no direct way to get the original config in ConfigCrudEvent.
      // We'll use a global static to store the values before changes.
      // For new installations, we'll just log the initial values.
      $old_data = &drupal_static('events_management_config_values', []);
      
      // Log each changed setting.
      foreach ($new_data as $key => $value) {
        // If we have the old value, log the change.
        if (isset($old_data[$key]) && $old_data[$key] !== $value) {
          events_management_log_config_change($key, $old_data[$key], $value);
        }
        elseif (!isset($old_data[$key])) {
          // For new values, log NULL as the old value.
          events_management_log_config_change($key, NULL, $value);
        }
        
        // Update the static cache with current values for next time.
        $old_data[$key] = $value;
      }
    }
  }

} 