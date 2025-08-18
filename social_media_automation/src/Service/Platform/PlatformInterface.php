<?php

namespace Drupal\social_media_automation\Service\Platform;

/**
 * Interface for social media platform clients.
 * 
 * All platform integrations must implement this interface to ensure
 * consistent functionality across different social media services.
 */
interface PlatformInterface {

  /**
   * Get the platform name.
   *
   * @return string
   *   The human-readable platform name.
   */
  public function getName(): string;

  /**
   * Get the platform machine name.
   *
   * @return string
   *   The machine-readable platform identifier.
   */
  public function getMachineName(): string;

  /**
   * Check if the platform is properly configured.
   *
   * @return bool
   *   TRUE if all required credentials are present.
   */
  public function isConfigured(): bool;

  /**
   * Test the platform API connection.
   *
   * @return bool
   *   TRUE if connection is successful.
   */
  public function testConnection(): bool;

  /**
   * Get the character limit for posts on this platform.
   *
   * @return int
   *   Maximum characters allowed per post.
   */
  public function getCharacterLimit(): int;

  /**
   * Post content to the platform.
   *
   * @param string $content
   *   The content to post.
   * @param array $options
   *   Optional parameters like media, reply settings, etc.
   *
   * @return array|false
   *   Response data from platform API or FALSE on failure.
   */
  public function postContent(string $content, array $options = []): array|false;

  /**
   * Format content for this specific platform.
   *
   * @param string $content
   *   The base content to format.
   * @param array $context
   *   Additional context for formatting decisions.
   *
   * @return string
   *   Platform-optimized content.
   */
  public function formatContent(string $content, array $context = []): string;

  /**
   * Get the OAuth authorization URL for this platform.
   *
   * @return string
   *   URL for user authorization.
   */
  public function getAuthenticationUrl(): string;

  /**
   * Get the list of required credentials for this platform.
   *
   * @return array
   *   Array of required credential field names.
   */
  public function getRequiredCredentials(): array;

  /**
   * Validate platform-specific credentials.
   *
   * @param array $credentials
   *   Array of credential values to validate.
   *
   * @return array
   *   Array of validation errors, empty if all valid.
   */
  public function validateCredentials(array $credentials): array;

  /**
   * Get platform-specific posting features.
   *
   * @return array
   *   Array of supported features (hashtags, mentions, media, etc.).
   */
  public function getSupportedFeatures(): array;

  /**
   * Get platform-specific rate limits.
   *
   * @return array
   *   Array with rate limit information.
   */
  public function getRateLimits(): array;

}
