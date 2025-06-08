# Events Management

## Overview

Events Management is a comprehensive Drupal 10/11 module designed to help you create and manage events on your website. With a modern, responsive design and powerful functionality, this module provides everything you need to showcase events to your users.


## Features

- **Complete Event Content Type**: Pre-configured with all essential fields:
  - Title
  - Image
  - Description (rich text)
  - Start/End dates and times
  - Category (taxonomy)
  - Country (taxonomy)
  - City (taxonomy with dynamic loading based on country)

- **Dynamic City Selection**: Cities are filtered based on the selected country using AJAX

- **Validation**: Ensures event end dates don't occur before start dates

- **Administrative Settings**:
  - Configure whether to show past events
  - Set number of events displayed per page
  - Configuration changes are logged for auditing

- **Display Pages**:
  - Events listing page (`/events`)
  - Individual event detail pages (`/events/{node}`)
  - Latest events block for sidebars or other regions
  - Calendar view of events (`/events/calendar`)
  - Location map of events (`/events/map`)

- **UI/UX**:
  - Responsive, mobile-friendly design
  - Clean interface with styling
  - Visual indicators for event categories
  - Hover effects and smooth transitions
  - Interactive calendar with event popups
  - Dynamic map with location markers and event information

## Installation

1. Download and place the module in your `/web/modules/custom` directory
2. Enable the module using Drush:
   ```
   drush en events_management -y
   ```
   drush cex -y
   ```
3. Clear cache:
   ```
   drush cr
   ```

## Configuration

1. Navigate to `/admin/config/events-management/settings`
2. Configure the following options:
   - **Show Past Events**: Toggle to show/hide events that have already occurred
   - **Events Per Page**: Set the number of events to display per page on the listing

## Usage

### Creating Events

1. Go to `/node/add/event`
2. Fill in the event details:
   - Add a title, image, and description
   - Set start and end dates/times
   - Select a category
   - Choose a country, which will then load available cities
   - Select a city

### Viewing Events

- Browse all events at `/events`
- View individual event details at `/events/{node}`
- Past events can be shown or hidden based on your configuration
- View events on an interactive calendar at `/events/calendar`
- Explore events on a map interface at `/events/map`

### Block Display

To add the Latest Events block to your site:
1. Go to Structure > Block layout
2. Click "Place block" in your desired region
3. Look for "Latest Events" in the list of available blocks
4. Configure the block settings and save

## Customization

### Theming

The module comes with the following template files that can be overridden in your theme:
- `events-listing.html.twig`: For the events listing page
- `event-detail.html.twig`: For individual event pages
- `latest-events-block.html.twig`: For the latest events block
- `node--event.html.twig`: Node template specifically for events
- `events-calendar.html.twig`: For the calendar view
- `events-map.html.twig`: For the map view

### CSS Styling

The module includes a comprehensive CSS file that can be extended or overridden:
- Base styles in `css/events.css`
- Calendar styles in `css/events-calendar.css`
- Map styles in `css/events-map.css`

## Uninstallation

When uninstalling the module, all content and configuration related to events will be properly removed:

```
drush pmu events_management -y
```

## Technical Details

### Database Tables

- `events_management_config_log`: Stores a history of configuration changes

### Custom Entities

- No custom entities are used; the module leverages Drupal's core Node, Taxonomy, and Field systems

### Dependencies

- Core modules: node, taxonomy, datetime, image, text

## Credits

Developed with ❤️ for Drupal 10/11