<?php

namespace Drupal\newsmotivationmetrics\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a donation support block for The Truth Perspective.
 *
 * @Block(
 *   id = "donation_support_block",
 *   admin_label = @Translation("Donation Support"),
 *   category = @Translation("The Truth Perspective")
 * )
 */
class DonationBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'donation_message' => 'Support independent journalism and transparent news analysis.',
      'venmo_username' => '@Keith-Aumiller',
      'show_venmo_link' => TRUE,
      'custom_message' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    $form['donation_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Donation Message'),
      '#description' => $this->t('The main message encouraging donations.'),
      '#default_value' => $config['donation_message'],
      '#rows' => 3,
    ];

    $form['venmo_username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Venmo Username'),
      '#description' => $this->t('Your Venmo username (e.g., @Keith-Aumiller).'),
      '#default_value' => $config['venmo_username'],
      '#size' => 30,
    ];

    $form['show_venmo_link'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Venmo Link'),
      '#description' => $this->t('Display a clickable Venmo link.'),
      '#default_value' => $config['show_venmo_link'],
    ];

    $form['custom_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Additional Message'),
      '#description' => $this->t('Optional additional message or thank you note.'),
      '#default_value' => $config['custom_message'],
      '#rows' => 2,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    $this->configuration['donation_message'] = $values['donation_message'];
    $this->configuration['venmo_username'] = $values['venmo_username'];
    $this->configuration['show_venmo_link'] = $values['show_venmo_link'];
    $this->configuration['custom_message'] = $values['custom_message'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    
    $build = [
      '#theme' => 'donation_support_block',
      '#donation_message' => $config['donation_message'],
      '#venmo_username' => $config['venmo_username'],
      '#show_venmo_link' => $config['show_venmo_link'],
      '#custom_message' => $config['custom_message'],
      '#attached' => [
        'library' => [
          'newsmotivationmetrics/donation-support',
        ],
      ],
      '#cache' => [
        'max-age' => 3600, // Cache for 1 hour
        'contexts' => ['url.path'],
      ],
    ];

    return $build;
  }

}
