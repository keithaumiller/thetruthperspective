<?php

/**
 * Debug endpoint for investigating news source taxonomy terms.
 * 
 * Access: https://thetruthperspective.org/debug_sources.php
 */

// Simple security check
if (!isset($_GET['debug']) || $_GET['debug'] !== 'sources') {
    http_response_code(404);
    exit('Not found');
}

// Set content type
header('Content-Type: text/plain; charset=utf-8');

echo "=== NEWS SOURCES TAXONOMY DEBUG ===\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // Database connection (using Drupal's database settings)
    $database_file = '/var/www/html/drupal/sites/default/settings.php';
    if (!file_exists($database_file)) {
        throw new Exception("Drupal settings file not found");
    }
    
    // Parse database settings
    $settings_content = file_get_contents($database_file);
    if (preg_match("/\\\$databases\['default'\]\['default'\]\s*=\s*\[(.*?)\];/s", $settings_content, $matches)) {
        // Extract database configuration
        $config_string = $matches[1];
        preg_match("/'database'\s*=>\s*'([^']+)'/", $config_string, $db_matches);
        preg_match("/'username'\s*=>\s*'([^']+)'/", $config_string, $user_matches);
        preg_match("/'password'\s*=>\s*'([^']+)'/", $config_string, $pass_matches);
        preg_match("/'host'\s*=>\s*'([^']+)'/", $config_string, $host_matches);
        
        $database = $db_matches[1] ?? 'drupal_db';
        $username = $user_matches[1] ?? 'drupal_user';
        $password = $pass_matches[1] ?? '';
        $host = $host_matches[1] ?? 'localhost';
        
        $pdo = new PDO("mysql:host={$host};dbname={$database};charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        echo "✅ Database connection successful\n\n";
        
    } else {
        throw new Exception("Could not parse database configuration");
    }
    
    // Query 1: Get all news sources from field_news_source
    echo "1. NEWS SOURCES FROM FIELD_NEWS_SOURCE:\n";
    echo str_repeat("-", 50) . "\n";
    
    $sql = "
        SELECT nfs.field_news_source_value as source_name, 
               COUNT(DISTINCT n.nid) as article_count
        FROM node__field_news_source nfs
        JOIN node_field_data n ON nfs.entity_id = n.nid AND n.type = 'article' AND n.status = 1
        WHERE nfs.field_news_source_value IS NOT NULL 
          AND nfs.field_news_source_value != ''
          AND nfs.field_news_source_value != 'Source Unavailable'
        GROUP BY nfs.field_news_source_value
        HAVING COUNT(DISTINCT n.nid) > 0
        ORDER BY article_count DESC, source_name ASC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $sources = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($sources as $source) {
        printf("%-30s %3d articles\n", substr($source['source_name'], 0, 29), $source['article_count']);
    }
    
    echo "\nTotal distinct sources: " . count($sources) . "\n\n";
    
    // Query 2: Check taxonomy terms for each source
    echo "2. TAXONOMY TERM STATUS FOR EACH SOURCE:\n";
    echo str_repeat("-", 50) . "\n";
    
    foreach ($sources as $source) {
        $source_name = $source['source_name'];
        
        // Check if taxonomy term exists
        $term_sql = "
            SELECT tid, name, status
            FROM taxonomy_term_field_data 
            WHERE vid = 'tags' 
              AND name = :source_name
        ";
        
        $term_stmt = $pdo->prepare($term_sql);
        $term_stmt->execute(['source_name' => $source_name]);
        $term = $term_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($term) {
            $status = $term['status'] ? 'Published' : 'Unpublished';
            printf("✅ %-25s → TID: %d (%s)\n", substr($source_name, 0, 24), $term['tid'], $status);
        } else {
            printf("❌ %-25s → No taxonomy term found\n", substr($source_name, 0, 24));
        }
    }
    
    echo "\n";
    
    // Query 3: Check for similar names (case sensitivity, extra spaces, etc.)
    echo "3. CHECKING FOR TAXONOMY TERMS WITH SIMILAR NAMES:\n";
    echo str_repeat("-", 50) . "\n";
    
    $problematic_sources = ['FOXNews.com', 'TheOnion'];
    
    foreach ($problematic_sources as $source_name) {
        echo "Checking variations for: {$source_name}\n";
        
        // Check for case variations, spaces, etc.
        $variations_sql = "
            SELECT tid, name, status
            FROM taxonomy_term_field_data 
            WHERE vid = 'tags' 
              AND (
                LOWER(name) LIKE LOWER(:source_pattern)
                OR name LIKE :source_pattern_wildcard
                OR REPLACE(LOWER(name), ' ', '') LIKE REPLACE(LOWER(:source_name), ' ', '')
              )
        ";
        
        $variations_stmt = $pdo->prepare($variations_sql);
        $variations_stmt->execute([
            'source_pattern' => "%{$source_name}%",
            'source_pattern_wildcard' => "%{$source_name}%",
            'source_name' => $source_name
        ]);
        $variations = $variations_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($variations) {
            foreach ($variations as $variation) {
                $status = $variation['status'] ? 'Published' : 'Unpublished';
                echo "  Found: \"{$variation['name']}\" (TID: {$variation['tid']}, {$status})\n";
            }
        } else {
            echo "  No similar terms found\n";
        }
        echo "\n";
    }
    
    // Query 4: Show recent articles for problematic sources
    echo "4. RECENT ARTICLES FOR PROBLEMATIC SOURCES:\n";
    echo str_repeat("-", 50) . "\n";
    
    foreach ($problematic_sources as $source_name) {
        echo "Articles from {$source_name}:\n";
        
        $articles_sql = "
            SELECT n.nid, n.title, n.created, nfs.field_news_source_value
            FROM node_field_data n
            JOIN node__field_news_source nfs ON n.nid = nfs.entity_id
            WHERE n.type = 'article' 
              AND n.status = 1
              AND nfs.field_news_source_value = :source_name
            ORDER BY n.created DESC
            LIMIT 3
        ";
        
        $articles_stmt = $pdo->prepare($articles_sql);
        $articles_stmt->execute(['source_name' => $source_name]);
        $articles = $articles_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($articles) {
            foreach ($articles as $article) {
                $date = date('Y-m-d', $article['created']);
                echo "  [{$article['nid']}] {$date} - " . substr($article['title'], 0, 60) . "...\n";
            }
        } else {
            echo "  No articles found\n";
        }
        echo "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== DEBUG COMPLETE ===\n";

?>
