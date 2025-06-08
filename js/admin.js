/**
 * @file
 * JavaScript behaviors for the Events Management module admin interface.
 */

(function ($, Drupal, once) {
  'use strict';

  /**
   * Behaviors for the event form.
   */
  Drupal.behaviors.eventsManagementAdmin = {
    attach: function (context, settings) {
      // Make sure our code runs when the DOM is fully loaded
      $(document).ready(function() {
        // Target both node form and entity form contexts
        const countrySelector = 'select[name="field_event_country[0][target_id]"], #edit-field-event-country-0-target-id';
        const citySelector = 'select[name="field_event_city[0][target_id]"], #edit-field-event-city-0-target-id';
        
        // Initialize city field state
        const $cityField = $(citySelector, context);
        const $countryField = $(countrySelector, context);
        
        // Initial state - disable city field if no country is selected
        if ($cityField.length && $countryField.length) {
          const countryValue = $countryField.val();
          
          if (!countryValue || countryValue === '_none') {
            // Disable city field initially
            $cityField.prop('disabled', true);
            
            // Add placeholder text
            if ($cityField.find('option[value=""]').length === 0 && 
                $cityField.find('option[value="_none"]').length === 0) {
              $cityField.empty().append('<option value="_none">' + Drupal.t('Select a country first') + '</option>');
            }
          }
        }
        
        // Handle country selection change
        $countryField.once('country-change').on('change', function() {
          const countryId = $(this).val();
          const $citySelect = $(citySelector);
          
          // Reset city select field
          $citySelect.empty();
          
          // If country is unselected or _none, disable city
          if (!countryId || countryId === '_none') {
            $citySelect.prop('disabled', true);
            $citySelect.append('<option value="_none">' + Drupal.t('Select a country first') + '</option>');
            return;
          }
          
          // Show loading indicator
          $citySelect.prop('disabled', true);
          $citySelect.append('<option value="_none">' + Drupal.t('Loading cities...') + '</option>');
          
          // Make AJAX call to get cities for selected country
          $.ajax({
            url: Drupal.url('events-management/ajax/cities'),
            type: 'GET',
            data: {
              country_id: countryId
            },
            dataType: 'json',
            success: function(response) {
              // Clear loading message
              $citySelect.empty();
              
              // Add default empty option
              $citySelect.append('<option value="_none">' + Drupal.t('- Select City -') + '</option>');
              
              if (response.error) {
                // Show error message
                Drupal.message('error', response.error);
                $citySelect.prop('disabled', true);
              } else if (response.cities && Object.keys(response.cities).length > 0) {
                // Add cities to select field
                $.each(response.cities, function(id, name) {
                  $citySelect.append('<option value="' + id + '">' + name + '</option>');
                });
                
                // Enable the city field now that we have options
                $citySelect.prop('disabled', false);
              } else {
                // No cities found for this country
                $citySelect.append('<option value="_none" disabled>' + Drupal.t('No cities available for this country') + '</option>');
                $citySelect.prop('disabled', true);
              }
            },
            error: function(xhr, status, error) {
              console.error('Error fetching cities: ' + error);
              $citySelect.empty();
              $citySelect.append('<option value="_none">' + Drupal.t('Error loading cities') + '</option>');
              $citySelect.prop('disabled', true);
              Drupal.message('error', Drupal.t('Error loading cities. Please try again.'));
            }
          });
        });
      });
    }
  };
  
  /**
   * Helper function to display messages.
   *
   * @param {string} type
   *   The message type (status, warning, error).
   * @param {string} message
   *   The message to display.
   */
  Drupal.message = function(type, message) {
    var messageContainer = $('.messages-list');
    
    // Create container if it doesn't exist
    if (messageContainer.length === 0) {
      messageContainer = $('<div class="messages-list"></div>');
      $('.form-item-field-event-city-0-target-id, #edit-field-event-city').before(messageContainer);
    }
    
    // Create message element
    var messageElement = $('<div class="messages messages--' + type + '" role="contentinfo" aria-label="' + type + ' message"></div>');
    messageElement.html('<div class="messages__content">' + message + '</div>');
    
    // Add to container
    messageContainer.append(messageElement);
    
    // Auto-remove after 5 seconds
    setTimeout(function() {
      messageElement.fadeOut(500, function() {
        $(this).remove();
      });
    }, 5000);
  };

})(jQuery, Drupal, once); 