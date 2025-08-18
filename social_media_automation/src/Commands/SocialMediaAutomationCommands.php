<?php

namespace Drupal\social_media_automation\Commands;

use Drush\Commands\DrushCommands;

/**
 * Drush commands for testing social media automation.
 */
class SocialMediaAutomationCommands extends DrushCommands {

  /**
   * Show platform status and statistics.
   *
   * @command social-media:status
   * @usage social-media:status
   *   Show automation status and platform statistics
   */
  public function status() {
    $this->output()->writeln('<info>Social Media Automation module is loaded and commands are working!</info>');
    $this->output()->writeln('This is a basic test command to verify Drush integration.');
  }

}
