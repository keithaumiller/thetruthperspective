<?php

/**
 * Debug script to find news source taxonomy terms
 */

// Simple query to find taxonomy terms that might be news sources
echo "SELECT t.tid, t.name, COUNT(DISTINCT n.nid) as article_count 
FROM taxonomy_term_field_data t
LEFT JOIN node__field_tags nft ON t.tid = nft.field_tags_target_id 
LEFT JOIN node_field_data n ON nft.entity_id = n.nid AND n.type = 'article' AND n.status = 1
WHERE t.vid = 'tags' AND t.status = 1
AND (
  t.name LIKE '%CNN%' OR 
  t.name LIKE '%Fox%' OR 
  t.name LIKE '%Guardian%' OR 
  t.name LIKE '%Reuters%' OR 
  t.name LIKE '%BBC%' OR 
  t.name LIKE '%NBC%' OR 
  t.name LIKE '%ABC%' OR 
  t.name LIKE '%CBS%' OR 
  t.name LIKE '%MSNBC%' OR 
  t.name LIKE '%Bloomberg%' OR 
  t.name LIKE '%NPR%' OR 
  t.name LIKE '%Associated Press%' OR 
  t.name LIKE '%AP News%' OR 
  t.name LIKE '%New York Times%' OR 
  t.name LIKE '%Washington Post%' OR 
  t.name LIKE '%Wall Street Journal%' OR 
  t.name LIKE '%Politico%' OR 
  t.name LIKE '%USA Today%' OR
  t.name LIKE '%Onion%'
)
GROUP BY t.tid, t.name 
HAVING COUNT(DISTINCT n.nid) > 0
ORDER BY article_count DESC, t.name ASC;"

echo "\n\n--- Alternative: Find all terms with substantial article counts ---\n";

echo "SELECT t.tid, t.name, COUNT(DISTINCT n.nid) as article_count 
FROM taxonomy_term_field_data t
LEFT JOIN node__field_tags nft ON t.tid = nft.field_tags_target_id 
LEFT JOIN node_field_data n ON nft.entity_id = n.nid AND n.type = 'article' AND n.status = 1
WHERE t.vid = 'tags' AND t.status = 1
GROUP BY t.tid, t.name 
HAVING COUNT(DISTINCT n.nid) >= 5
ORDER BY article_count DESC 
LIMIT 30;"

?>
