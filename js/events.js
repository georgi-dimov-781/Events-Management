/**
 * @file
 * JavaScript behaviors for the Events Management module frontend.
 */

(function ($, Drupal, once) {
  'use strict';

  /**
   * Behaviors for the events listing and detail pages.
   */
  Drupal.behaviors.eventsManagement = {
    attach: function (context, settings) {
      // Add hover effect to event items.
      once('event-hover', '.event-item', context).forEach(function (element) {
        $(element).hover(
          function () {
            $(this).addClass('event-hover');
          },
          function () {
            $(this).removeClass('event-hover');
          }
        );
      });
    }
  };

})(jQuery, Drupal, once); 