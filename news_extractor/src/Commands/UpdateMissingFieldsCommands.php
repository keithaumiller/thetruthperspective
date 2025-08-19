<?php

namespace Drupal\news_extractor\Commands;

use Drush\Commands\DrushCommands;
use Drupal\node\Entity\Node;

/**
 * Commands for updating missing assessment fields from raw AI responses.
 */
class UpdateMissingFieldsCommands extends DrushCommands {

  /**
   * Reprocess articles missing authoritarianism scores from raw AI responses.
   *
   * @param array $options
   *   Command options.
   *
   * @command news-extractor:update-authoritarianism
   * @aliases ne:auth
   * @option limit Number of articles to process (default: 50)
   * @option all Process all articles missing authoritarianism scores
   * @usage news-extractor:update-authoritarianism
   *   Update 50 articles missing authoritarianism scores
   * @usage news-extractor:update-authoritarianism --limit=100
   *   Update 100 articles missing authoritarianism scores
   * @usage news-extractor:update-authoritarianism --all
   *   Update ALL articles missing authoritarianism scores
   */
  public function updateAuthoritarianismScores(array $options = ['limit' => 50, 'all' => FALSE]) {
    $limit = $options['all'] ? NULL : $options['limit'];
    
    $this->output()->writeln("🔄 Finding articles missing authoritarianism scores...");
    
    // Query for articles that have raw AI responses but missing authoritarianism scores
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'article')
      ->condition('field_ai_raw_response', '', '<>')
      ->accessCheck(FALSE)
      ->sort('created', 'DESC');
    
    // Add condition for missing authoritarianism score
    $query->group()
      ->condition('field_authoritarianism_score', NULL, 'IS NULL')
      ->condition('field_authoritarianism_score', '', '=')
      ->groupOperator('OR');
    
    if ($limit) {
      $query->range(0, $limit);
    }
    
    $nids = $query->execute();
    
    if (empty($nids)) {
      $this->output()->writeln("✅ No articles found missing authoritarianism scores.");
      return;
    }
    
    $total_count = count($nids);
    $this->output()->writeln("Found {$total_count} articles missing authoritarianism scores...");
    
    /** @var \Drupal\news_extractor\Service\DataProcessingService $data_processing_service */
    $data_processing_service = \Drupal::service('news_extractor.data_processing');
    
    $processed = 0;
    $updated = 0;
    $failed = 0;
    
    foreach ($nids as $nid) {
      $node = Node::load($nid);
      if (!$node) {
        continue;
      }
      
      $processed++;
      $this->output()->writeln("[{$processed}/{$total_count}] Processing: " . $node->getTitle() . " (ID: {$nid})");
      
      try {
        // Use the existing reprocessFromRawResponse method
        $result = $data_processing_service->reprocessFromRawResponse($node);
        
        if ($result) {
          // Check if authoritarianism score was actually added
          $node = Node::load($nid); // Reload to get updated data
          $auth_score = $node->hasField('field_authoritarianism_score') ? 
            $node->get('field_authoritarianism_score')->value : NULL;
          
          if (!empty($auth_score)) {
            $updated++;
            $this->output()->writeln("  ✅ Updated (Authoritarianism: {$auth_score}/100)");
          } else {
            $this->output()->writeln("  ⚠️  Reprocessed but no authoritarianism score found in raw AI response");
          }
        } else {
          $failed++;
          $this->output()->writeln("  ❌ Failed to reprocess");
        }
      } catch (\Exception $e) {
        $failed++;
        $this->output()->writeln("  ❌ Error: " . $e->getMessage());
        \Drupal::logger('news_extractor')->error('Failed to update authoritarianism for node @nid: @error', [
          '@nid' => $nid,
          '@error' => $e->getMessage(),
        ]);
      }
      
      // Small delay to prevent overwhelming the system
      if ($processed % 10 === 0) {
        sleep(1);
      }
    }
    
    $this->output()->writeln("");
    $this->output()->writeln("🎉 Processing complete!");
    $this->output()->writeln("📊 Summary:");
    $this->output()->writeln("   Total processed: {$processed}");
    $this->output()->writeln("   Successfully updated: {$updated}");
    $this->output()->writeln("   Failed: {$failed}");
    $this->output()->writeln("   No authoritarianism data: " . ($processed - $updated - $failed));
  }

  /**
   * Update all missing assessment fields from raw AI responses.
   *
   * @param array $options
   *   Command options.
   *
   * @command news-extractor:update-missing-fields
   * @aliases ne:missing
   * @option limit Number of articles to process (default: 50)
   * @option all Process all articles with raw AI responses
   * @option field Specific field to update (authoritarianism, credibility, bias, sentiment)
   * @usage news-extractor:update-missing-fields
   *   Update all missing assessment fields for 50 articles
   * @usage news-extractor:update-missing-fields --field=authoritarianism
   *   Update only authoritarianism scores for 50 articles
   * @usage news-extractor:update-missing-fields --all
   *   Update all missing fields for ALL articles
   */
  public function updateMissingFields(array $options = ['limit' => 50, 'all' => FALSE, 'field' => NULL]) {
    $limit = $options['all'] ? NULL : $options['limit'];
    $specific_field = $options['field'];
    
    $this->output()->writeln("🔄 Finding articles with raw AI responses for field updates...");
    
    // Query for articles that have raw AI responses
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'article')
      ->condition('field_ai_raw_response', '', '<>')
      ->accessCheck(FALSE)
      ->sort('created', 'DESC');
    
    if ($limit) {
      $query->range(0, $limit);
    }
    
    $nids = $query->execute();
    
    if (empty($nids)) {
      $this->output()->writeln("❌ No articles found with raw AI responses.");
      return;
    }
    
    $total_count = count($nids);
    $field_desc = $specific_field ? $specific_field : 'all assessment';
    $this->output()->writeln("Found {$total_count} articles to check for missing {$field_desc} fields...");
    
    /** @var \Drupal\news_extractor\Service\DataProcessingService $data_processing_service */
    $data_processing_service = \Drupal::service('news_extractor.data_processing');
    
    $processed = 0;
    $updated = 0;
    $failed = 0;
    $skipped = 0;
    
    foreach ($nids as $nid) {
      $node = Node::load($nid);
      if (!$node) {
        continue;
      }
      
      $processed++;
      $this->output()->writeln("[{$processed}/{$total_count}] Processing: " . $node->getTitle() . " (ID: {$nid})");
      
      // Check if this article needs updates based on specific field or missing any assessment field
      $needs_update = FALSE;
      
      if ($specific_field) {
        switch ($specific_field) {
          case 'authoritarianism':
            $needs_update = $node->hasField('field_authoritarianism_score') && 
                           $node->get('field_authoritarianism_score')->isEmpty();
            break;
          case 'credibility':
            $needs_update = $node->hasField('field_credibility_score') && 
                           $node->get('field_credibility_score')->isEmpty();
            break;
          case 'bias':
            $needs_update = $node->hasField('field_bias_rating') && 
                           $node->get('field_bias_rating')->isEmpty();
            break;
          case 'sentiment':
            $needs_update = $node->hasField('field_article_sentiment_score') && 
                           $node->get('field_article_sentiment_score')->isEmpty();
            break;
        }
      } else {
        // Check if any assessment field is missing
        $auth_missing = $node->hasField('field_authoritarianism_score') && 
                       $node->get('field_authoritarianism_score')->isEmpty();
        $cred_missing = $node->hasField('field_credibility_score') && 
                       $node->get('field_credibility_score')->isEmpty();
        $bias_missing = $node->hasField('field_bias_rating') && 
                       $node->get('field_bias_rating')->isEmpty();
        $sentiment_missing = $node->hasField('field_article_sentiment_score') && 
                            $node->get('field_article_sentiment_score')->isEmpty();
        
        $needs_update = $auth_missing || $cred_missing || $bias_missing || $sentiment_missing;
      }
      
      if (!$needs_update) {
        $skipped++;
        $this->output()->writeln("  ⏭️  Already has all required fields, skipping");
        continue;
      }
      
      try {
        // Use the existing reprocessFromRawResponse method
        $result = $data_processing_service->reprocessFromRawResponse($node);
        
        if ($result) {
          $updated++;
          $this->output()->writeln("  ✅ Successfully reprocessed and updated");
        } else {
          $failed++;
          $this->output()->writeln("  ❌ Failed to reprocess");
        }
      } catch (\Exception $e) {
        $failed++;
        $this->output()->writeln("  ❌ Error: " . $e->getMessage());
        \Drupal::logger('news_extractor')->error('Failed to update fields for node @nid: @error', [
          '@nid' => $nid,
          '@error' => $e->getMessage(),
        ]);
      }
      
      // Small delay to prevent overwhelming the system
      if ($processed % 10 === 0) {
        sleep(1);
      }
    }
    
    $this->output()->writeln("");
    $this->output()->writeln("🎉 Processing complete!");
    $this->output()->writeln("📊 Summary:");
    $this->output()->writeln("   Total processed: {$processed}");
    $this->output()->writeln("   Successfully updated: {$updated}");
    $this->output()->writeln("   Skipped (already complete): {$skipped}");
    $this->output()->writeln("   Failed: {$failed}");
  }

}
