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
    $auth_or_group = \Drupal::entityQuery('node')->orConditionGroup()
      ->condition('field_authoritarianism_score', NULL, 'IS NULL')
      ->condition('field_authoritarianism_score', '', '=');
    
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'article')
      ->condition('field_ai_raw_response', '', '<>')
      ->condition($auth_or_group)
      ->accessCheck(FALSE)
      ->sort('created', 'DESC');
    
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

  /**
   * Automated cron cleanup for missing assessment fields.
   *
   * @param array $options
   *   Command options.
   *
   * @command news-extractor:cron-cleanup-assessments
   * @aliases ne:cron-assess
   * @option limit Number of articles to process per cron run (default: 20)
   * @option max-age Maximum age in days for articles to reprocess (default: 30)
   * @usage news-extractor:cron-cleanup-assessments
   *   Process 20 articles missing assessment fields
   * @usage news-extractor:cron-cleanup-assessments --limit=50
   *   Process 50 articles missing assessment fields
   * @usage news-extractor:cron-cleanup-assessments --max-age=7
   *   Only process articles from last 7 days
   */
  public function cronCleanupAssessments(array $options = ['limit' => 20, 'max-age' => 30]) {
    $limit = $options['limit'];
    $max_age = $options['max-age'];
    
    $this->output()->writeln("🔄 [CRON] Automated cleanup: Finding articles missing assessment fields...");
    
    // Calculate cutoff date for max-age
    $cutoff_timestamp = time() - ($max_age * 24 * 60 * 60);
    
    // Query for articles missing any assessment field
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'article')
      ->condition('status', 1) // Only published articles
      ->condition('created', $cutoff_timestamp, '>')
      ->accessCheck(FALSE)
      ->range(0, $limit)
      ->sort('created', 'DESC');
    
    // Create OR condition group for missing any assessment field
    $missing_fields_group = \Drupal::entityQuery('node')->orConditionGroup();
    
    // Add conditions for each assessment field being missing
    $auth_missing = \Drupal::entityQuery('node')->orConditionGroup()
      ->condition('field_authoritarianism_score', NULL, 'IS NULL')
      ->condition('field_authoritarianism_score', '', '=');
    
    $cred_missing = \Drupal::entityQuery('node')->orConditionGroup()
      ->condition('field_credibility_score', NULL, 'IS NULL')
      ->condition('field_credibility_score', '', '=');
    
    $bias_missing = \Drupal::entityQuery('node')->orConditionGroup()
      ->condition('field_bias_rating', NULL, 'IS NULL')
      ->condition('field_bias_rating', '', '=');
    
    $sentiment_missing = \Drupal::entityQuery('node')->orConditionGroup()
      ->condition('field_article_sentiment_score', NULL, 'IS NULL')
      ->condition('field_article_sentiment_score', '', '=');
    
    $missing_fields_group
      ->condition($auth_missing)
      ->condition($cred_missing)
      ->condition($bias_missing)
      ->condition($sentiment_missing);
    
    $query->condition($missing_fields_group);
    
    $nids = $query->execute();
    
    if (empty($nids)) {
      $this->output()->writeln("✅ [CRON] No articles found missing assessment fields (last {$max_age} days).");
      return;
    }
    
    $total_count = count($nids);
    $this->output()->writeln("📊 [CRON] Found {$total_count} articles missing assessment fields...");
    
    /** @var \Drupal\news_extractor\Service\NewsExtractionService $extraction_service */
    $extraction_service = \Drupal::service('news_extractor.extraction');
    
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
      $this->output()->writeln("[{$processed}/{$total_count}] [CRON] Processing: " . $node->getTitle() . " (ID: {$nid})");
      
      // Check which fields are missing
      $missing_fields = [];
      if ($node->hasField('field_authoritarianism_score') && $node->get('field_authoritarianism_score')->isEmpty()) {
        $missing_fields[] = 'authoritarianism';
      }
      if ($node->hasField('field_credibility_score') && $node->get('field_credibility_score')->isEmpty()) {
        $missing_fields[] = 'credibility';
      }
      if ($node->hasField('field_bias_rating') && $node->get('field_bias_rating')->isEmpty()) {
        $missing_fields[] = 'bias';
      }
      if ($node->hasField('field_article_sentiment_score') && $node->get('field_article_sentiment_score')->isEmpty()) {
        $missing_fields[] = 'sentiment';
      }
      
      if (empty($missing_fields)) {
        $skipped++;
        $this->output()->writeln("  ⏭️  [CRON] All fields present, skipping");
        continue;
      }
      
      $this->output()->writeln("  🔧 [CRON] Missing: " . implode(', ', $missing_fields));
      
      try {
        // Full reprocessing with AI call to get all assessment fields
        if (!$node->hasField('field_original_url') || $node->get('field_original_url')->isEmpty()) {
          throw new \Exception("No URL available for reprocessing");
        }
        
        $url = $node->get('field_original_url')->uri;
        $result = $extraction_service->processArticle($node, $url);
        
        if ($result) {
          $updated++;
          $this->output()->writeln("  ✅ [CRON] Successfully reprocessed with new AI analysis");
        } else {
          $failed++;
          $this->output()->writeln("  ❌ [CRON] Failed to reprocess");
        }
      } catch (\Exception $e) {
        $failed++;
        $this->output()->writeln("  ❌ [CRON] Error: " . $e->getMessage());
        \Drupal::logger('news_extractor')->error('[CRON] Failed to reprocess node @nid: @error', [
          '@nid' => $nid,
          '@error' => $e->getMessage(),
        ]);
      }
      
      // Rate limiting for cron jobs
      sleep(3);
    }
    
    $this->output()->writeln("");
    $this->output()->writeln("🎉 [CRON] Cleanup complete!");
    $this->output()->writeln("📊 [CRON] Summary:");
    $this->output()->writeln("   Total processed: {$processed}");
    $this->output()->writeln("   Successfully updated: {$updated}");
    $this->output()->writeln("   Skipped (complete): {$skipped}");
    $this->output()->writeln("   Failed: {$failed}");
    
    // Log summary for monitoring
    \Drupal::logger('news_extractor')->info('[CRON] Assessment cleanup completed: @updated updated, @failed failed, @skipped skipped from @total articles', [
      '@updated' => $updated,
      '@failed' => $failed,
      '@skipped' => $skipped,
      '@total' => $processed,
    ]);
  }

  /**
   * Quick assessment field status check for monitoring.
   *
   * @command news-extractor:assessment-status
   * @aliases ne:assess-status
   * @usage news-extractor:assessment-status
   *   Check status of assessment fields across all articles
   */
  public function assessmentStatus() {
    $this->output()->writeln("📊 Assessment Field Status Report");
    $this->output()->writeln("================================");
    
    // Total articles
    $total_articles = \Drupal::entityQuery('node')
      ->condition('type', 'article')
      ->condition('status', 1)
      ->accessCheck(FALSE)
      ->count()
      ->execute();
    
    $this->output()->writeln("📄 Total published articles: {$total_articles}");
    $this->output()->writeln("");
    
    // Check each assessment field
    $fields = [
      'field_authoritarianism_score' => 'Authoritarianism Score',
      'field_credibility_score' => 'Credibility Score', 
      'field_bias_rating' => 'Bias Rating',
      'field_article_sentiment_score' => 'Sentiment Score',
    ];
    
    foreach ($fields as $field_name => $field_label) {
      // Articles with this field populated
      $with_field = \Drupal::entityQuery('node')
        ->condition('type', 'article')
        ->condition('status', 1)
        ->condition($field_name, '', '<>')
        ->exists($field_name)
        ->accessCheck(FALSE)
        ->count()
        ->execute();
      
      // Articles missing this field
      $missing_field_group = \Drupal::entityQuery('node')->orConditionGroup()
        ->condition($field_name, NULL, 'IS NULL')
        ->condition($field_name, '', '=');
      
      $missing_field = \Drupal::entityQuery('node')
        ->condition('type', 'article')
        ->condition('status', 1)
        ->condition($missing_field_group)
        ->accessCheck(FALSE)
        ->count()
        ->execute();
      
      $percentage = $total_articles > 0 ? round(($with_field / $total_articles) * 100, 1) : 0;
      
      $this->output()->writeln("🔍 {$field_label}:");
      $this->output()->writeln("   ✅ Present: {$with_field} ({$percentage}%)");
      $this->output()->writeln("   ❌ Missing: {$missing_field}");
      $this->output()->writeln("");
    }
    
    // Articles missing ANY assessment field
    $missing_any_group = \Drupal::entityQuery('node')->orConditionGroup();
    foreach (array_keys($fields) as $field_name) {
      $field_missing = \Drupal::entityQuery('node')->orConditionGroup()
        ->condition($field_name, NULL, 'IS NULL')
        ->condition($field_name, '', '=');
      $missing_any_group->condition($field_missing);
    }
    
    $missing_any = \Drupal::entityQuery('node')
      ->condition('type', 'article')
      ->condition('status', 1)
      ->condition($missing_any_group)
      ->accessCheck(FALSE)
      ->count()
      ->execute();
    
    $complete = $total_articles - $missing_any;
    $complete_percentage = $total_articles > 0 ? round(($complete / $total_articles) * 100, 1) : 0;
    
    $this->output()->writeln("📈 Overall Assessment Completion:");
    $this->output()->writeln("   ✅ Complete: {$complete} ({$complete_percentage}%)");
    $this->output()->writeln("   🔧 Need Processing: {$missing_any}");
  }

}
