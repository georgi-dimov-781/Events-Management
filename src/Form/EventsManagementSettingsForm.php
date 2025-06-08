<?php

namespace Drupal\events_management\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configuration form for Events Management module.
 */
class EventsManagementSettingsForm extends ConfigFormBase {

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'events_management.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'events_management_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

    $form['show_past_events'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show past events'),
      '#description' => $this->t('Check this option to display past events in the listing page.'),
      '#default_value' => $config->get('show_past_events') ?? TRUE,
    ];

    $form['events_per_page'] = [
      '#type' => 'number',
      '#title' => $this->t('Events per page'),
      '#description' => $this->t('Number of events to display per page in the listing.'),
      '#default_value' => $config->get('events_per_page') ?? 10,
      '#min' => 1,
      '#max' => 100,
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);
    
    // Get old values for logging.
    $old_show_past_events = $config->get('show_past_events');
    $old_events_per_page = $config->get('events_per_page');
    
    // Get new values.
    $new_show_past_events = $form_state->getValue('show_past_events');
    $new_events_per_page = $form_state->getValue('events_per_page');
    
    // Save configuration.
    $config->set('show_past_events', $new_show_past_events)
      ->set('events_per_page', $new_events_per_page)
      ->save();

    // Log configuration changes.
    if ($old_show_past_events !== $new_show_past_events) {
      events_management_log_config_change('show_past_events', $old_show_past_events, $new_show_past_events);
    }
    
    if ($old_events_per_page !== $new_events_per_page) {
      events_management_log_config_change('events_per_page', $old_events_per_page, $new_events_per_page);
    }

    parent::submitForm($form, $form_state);
  }

} 