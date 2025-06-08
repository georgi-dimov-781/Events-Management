/**
 * @file
 * JavaScript for the events calendar.
 */
(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.eventsCalendar = {
    attach: function (context, settings) {
      const calendarEl = once('events-calendar', '#events-calendar', context)[0];
      if (!calendarEl) return;

      const tooltip = document.getElementById('event-calendar-tooltip');
      const tooltipTitle = tooltip.querySelector('.tooltip-title');
      const tooltipDate = tooltip.querySelector('.tooltip-date');
      const tooltipLocation = tooltip.querySelector('.tooltip-location');
      const tooltipCategory = tooltip.querySelector('.tooltip-category');
      const tooltipLink = tooltip.querySelector('.tooltip-link');
      const tooltipClose = tooltip.querySelector('.tooltip-close');

      // Initialize the calendar
      const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
          left: 'prev,next today',
          center: 'title',
          right: 'dayGridMonth,timeGridWeek,listMonth'
        },
        events: drupalSettings.eventsManagement.calendarJsonUrl,
        eventTimeFormat: {
          hour: '2-digit',
          minute: '2-digit',
          meridiem: 'short'
        },
        eventClick: function(info) {
          // Prevent redirecting to the event
          info.jsEvent.preventDefault();

          // Format dates for display
          const startDate = new Date(info.event.start);
          const endDate = info.event.end ? new Date(info.event.end) : null;
          
          let formattedDate;
          if (endDate) {
            const sameDay = startDate.toDateString() === endDate.toDateString();
            if (sameDay) {
              formattedDate = startDate.toLocaleDateString([], { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) + 
                ' (' + startDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) +
                ' - ' + endDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) + ')';
            } else {
              formattedDate = startDate.toLocaleDateString([], { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) + 
                ' - ' + endDate.toLocaleDateString([], { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            }
          } else {
            formattedDate = startDate.toLocaleDateString([], { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
          }

          // Update tooltip content
          tooltipTitle.textContent = info.event.title;
          tooltipDate.textContent = formattedDate;
          tooltipLocation.textContent = info.event.extendedProps.location || 'Location not specified';
          tooltipCategory.textContent = info.event.extendedProps.category || 'No category';
          tooltipLink.href = info.event.url;

          // Position and show the tooltip
          const rect = info.el.getBoundingClientRect();
          tooltip.style.left = rect.left + window.scrollX + 'px';
          tooltip.style.top = rect.bottom + window.scrollY + 10 + 'px';
          tooltip.style.display = 'block';

          // Close tooltip when clicking outside
          document.addEventListener('click', closeTooltipOnClickOutside);
        },
        eventMouseEnter: function(info) {
          // Add hover effect
          info.el.style.cursor = 'pointer';
        },
        windowResize: function(view) {
          // Hide tooltip on window resize
          tooltip.style.display = 'none';
        },
        datesSet: function(dateInfo) {
          // Hide tooltip when calendar view changes
          tooltip.style.display = 'none';
        }
      });

      // Render the calendar
      calendar.render();

      // Close tooltip when clicking the close button
      tooltipClose.addEventListener('click', function() {
        tooltip.style.display = 'none';
        document.removeEventListener('click', closeTooltipOnClickOutside);
      });

      // Function to close tooltip when clicking outside of it
      function closeTooltipOnClickOutside(e) {
        if (!tooltip.contains(e.target) && e.target !== tooltip) {
          tooltip.style.display = 'none';
          document.removeEventListener('click', closeTooltipOnClickOutside);
        }
      }

      // Responsive handling for small screens
      function handleResponsive() {
        if (window.innerWidth < 768) {
          calendar.changeView('listMonth');
        }
      }

      // Check responsive layout on load
      handleResponsive();

      // Handle window resize
      window.addEventListener('resize', handleResponsive);
    }
  };

})(jQuery, Drupal, drupalSettings); 