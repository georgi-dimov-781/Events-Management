/**
 * @file
 * JavaScript for the events map.
 */
(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.eventsMap = {
    attach: function (context, settings) {
      const mapElement = once('events-map', '#events-map', context)[0];
      if (!mapElement) return;

      // Initialize the map
      const map = L.map(mapElement).setView([20, 0], 2);

      // Add tile layer (OpenStreetMap)
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19
      }).addTo(map);

      // Initialize markers object
      const markers = {};
      const markersList = [];
      const popupTemplate = document.getElementById('event-popup-template');
      const eventsListContainer = document.getElementById('map-events-list');

      // Create custom marker icon
      const defaultIcon = L.divIcon({
        className: 'custom-map-marker',
        html: '<div class="marker-pin"></div>',
        iconSize: [30, 42],
        iconAnchor: [15, 42]
      });

      const activeIcon = L.divIcon({
        className: 'custom-map-marker active',
        html: '<div class="marker-pin active"></div>',
        iconSize: [30, 42],
        iconAnchor: [15, 42]
      });

      // Fetch events data
      $.getJSON(drupalSettings.eventsManagement.mapJsonUrl)
        .done(function (data) {
          if (data.length === 0) {
            // Show no events message
            eventsListContainer.innerHTML = '<div class="no-events-message">No upcoming events found with location information.</div>';
            return;
          }

          // Process events data
          const bounds = L.latLngBounds();
          const eventsList = document.createDocumentFragment();

          data.forEach(function (event) {
            // Create marker
            const markerLatLng = L.latLng(event.lat, event.lng);
            bounds.extend(markerLatLng);

            const marker = L.marker(markerLatLng, {
              icon: defaultIcon,
              title: event.title,
              eventId: event.id
            });

            // Create popup content
            const popupContent = popupTemplate.content.cloneNode(true);
            popupContent.querySelector('.popup-title').textContent = event.title;
            popupContent.querySelector('.popup-date').textContent = event.start_date;
            popupContent.querySelector('.popup-location').textContent = event.city + (event.country ? ', ' + event.country : '');
            popupContent.querySelector('.popup-category').textContent = event.category || 'Uncategorized';
            popupContent.querySelector('.popup-link').href = event.url;

            // Add popup to marker
            const popup = L.popup({
              className: 'event-map-popup',
              closeButton: true,
              closeOnEscapeKey: true,
              closeOnClick: false,
              autoClose: false
            }).setContent(popupContent);

            marker.bindPopup(popup);

            // Create event item for sidebar
            const eventItem = document.createElement('div');
            eventItem.className = 'map-event-item';
            eventItem.dataset.eventId = event.id;

            const eventTitle = document.createElement('div');
            eventTitle.className = 'map-event-title';
            eventTitle.textContent = event.title;
            eventItem.appendChild(eventTitle);

            const eventDate = document.createElement('div');
            eventDate.className = 'map-event-date';
            eventDate.textContent = event.start_date;
            eventItem.appendChild(eventDate);

            const eventLocation = document.createElement('div');
            eventLocation.className = 'map-event-location';
            eventLocation.textContent = event.city + (event.country ? ', ' + event.country : '');
            eventItem.appendChild(eventLocation);

            // Add event handlers
            eventItem.addEventListener('click', function() {
              setActiveMarker(event.id);
              
              // Center map on marker
              map.setView(markerLatLng, 10);
              
              // Open popup
              marker.openPopup();
            });

            marker.on('click', function() {
              setActiveMarker(event.id);
            });

            marker.on('popupopen', function() {
              setActiveMarker(event.id);
            });

            marker.on('popupclose', function() {
              resetMarkers();
            });

            // Store marker and add to map
            markers[event.id] = {
              marker: marker,
              element: eventItem
            };
            markersList.push(marker);
            marker.addTo(map);
            eventsList.appendChild(eventItem);
          });

          // Add events to sidebar
          eventsListContainer.appendChild(eventsList);

          // Set map bounds to include all markers
          if (markersList.length > 0) {
            map.fitBounds(bounds, {
              padding: [50, 50],
              maxZoom: 10
            });
          }
        })
        .fail(function (jqXHR, textStatus, errorThrown) {
          console.error('Failed to load events data:', textStatus, errorThrown);
          eventsListContainer.innerHTML = '<div class="error-message">Failed to load events data. Please try again later.</div>';
        });

      // Function to set active marker
      function setActiveMarker(eventId) {
        // Reset all markers first
        resetMarkers();

        // Set active class
        if (markers[eventId]) {
          markers[eventId].marker.setIcon(activeIcon);
          markers[eventId].element.classList.add('active');
          
          // Scroll the sidebar to show the active event
          const sidebarContainer = document.querySelector('.map-sidebar');
          const activeElement = markers[eventId].element;
          
          if (sidebarContainer && activeElement) {
            sidebarContainer.scrollTop = activeElement.offsetTop - sidebarContainer.offsetTop - 10;
          }
        }
      }

      // Function to reset all markers
      function resetMarkers() {
        Object.values(markers).forEach(function(item) {
          item.marker.setIcon(defaultIcon);
          item.element.classList.remove('active');
        });
      }

      // Handle map resize
      map.on('resize', function() {
        if (markersList.length > 0) {
          const bounds = L.latLngBounds();
          markersList.forEach(function(marker) {
            bounds.extend(marker.getLatLng());
          });
          map.fitBounds(bounds, {
            padding: [50, 50],
            maxZoom: 10
          });
        }
      });
    }
  };

})(jQuery, Drupal, drupalSettings); 