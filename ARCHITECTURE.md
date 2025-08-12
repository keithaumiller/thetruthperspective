# The Truth Perspective - Technical Architecture Documentation

## Article Content Type Field Structure

### Core Content Fields
| Field Label | Machine Name | Type | Purpose |
|-------------|--------------|------|---------|
| **Body** | `body` | Text (formatted, long, with summary) | Main article content extracted via Diffbot |
| **Original URL** | `field_original_url` | Link | Source URL for content extraction |
| **News Source** | `field_news_source` | Text (plain) | Standardized news publication name |
| **Original Author** | `field_original_author` | Text (plain) | Article author from source |
| **Publication Date** | `field_publication_date` | Date | When article was originally published |

### AI Analysis Fields  
| Field Label | Machine Name | Type | Purpose |
|-------------|--------------|------|---------|
| **AI Summary** | `field_ai_summary` | Text (plain, long) | Legacy AI response field |
| **ai_raw_response** | `field_ai_raw_response` | Text (plain, long) | Raw Claude API response |
| **Motivation Analysis** | `field_motivation_analysis` | Text (formatted, long, with summary) | Structured motivation analysis |
| **motivation_data** | `field_motivation_data` | Text (plain, long) | JSON structured motivation data |

### Media Assessment Fields
| Field Label | Machine Name | Type | Purpose |
|-------------|--------------|------|---------|
| **Credibility Score** | `field_credibility_score` | Text (plain) | 0-100 credibility rating |
| **Bias Rating** | `field_bias_rating` | Text (plain) | 0-100 bias rating (50=center) |
| **Bias Analysis** | `field_bias_analysis` | Text (plain) | Explanation of bias assessment |
| **Article Sentiment Score** | `field_article_sentiment_score` | List (text) | Sentiment analysis score |
| **Fact Check Status** | `field_fact_check_status` | Text (plain) | Fact-checking status |

### Technical Data Fields
| Field Label | Machine Name | Type | Purpose |
|-------------|--------------|------|---------|
| **json scraped article data** | `field_json_scraped_article_data` | Text (plain, long) | Complete Diffbot API response |
| **Article Hash** | `field_article_hash` | Text (plain, long) | Content deduplication hash |
| **external_image_url** | `field_external_image_url` | Link | External article image URL |

### Content Management Fields
| Field Label | Machine Name | Type | Purpose |
|-------------|--------------|------|---------|
| **Image** | `field_image` | Image | Local article image |
| **Tags** | `field_tags` | Entity reference (Taxonomy: Tags) | Auto-generated content tags |
| **Comments** | `comment` | Comments | User comments system |
| **Feeds item** | `feeds_item` | Feed Reference | RSS feed source tracking |

## Data Flow Architecture

### Stage 1: Content Import
```
RSS Feed → feeds_item → field_original_url
```

### Stage 2: Content Extraction  
```
field_original_url → Diffbot API → field_json_scraped_article_data
                                → body (extracted content)
                                → field_original_author
                                → field_publication_date
```

### Stage 3: News Source Population
```
Priority 1: field_json_scraped_article_data → objects[].siteName → field_news_source
Priority 2: field_original_url → domain mapping → field_news_source  
Priority 3: feeds_item metadata → field_news_source
```

### Stage 4: AI Analysis
```
body + title → Claude 3.5 Sonnet → field_ai_raw_response
                                 → field_motivation_analysis (formatted)
                                 → field_motivation_data (JSON)
                                 → field_credibility_score
                                 → field_bias_rating  
                                 → field_bias_analysis
                                 → field_article_sentiment_score
```

### Stage 5: Content Tagging
```
field_motivation_data → entities/motivations extraction → field_tags (taxonomy terms)
```

## News Source Extraction Logic

### Multi-Stage Fallback System:

#### Stage 1: JSON Data Extraction (Primary)
- **Source**: `field_json_scraped_article_data`
- **Method**: Parse JSON → extract `objects[].siteName`
- **Reliability**: Highest (direct from Diffbot)
- **Processing**: Clean and standardize site name

#### Stage 2: URL Domain Mapping (Fallback)
- **Source**: `field_original_url`  
- **Method**: Parse domain → map to standard names
- **Coverage**: 30+ major news outlets
- **Examples**: `cnn.com` → `CNN`, `foxnews.com` → `Fox News`

#### Stage 3: Feed Metadata (During Import)
- **Source**: `feeds_item` metadata
- **Method**: Extract from RSS source/description fields
- **Timing**: During feed processing via `hook_feeds_process_alter`

#### Stage 4: Cron Maintenance
- **Frequency**: Every cron run
- **Batch Size**: 25 articles from JSON + 25 from URL
- **Purpose**: Continuous background population

## AI Analysis Architecture

### Claude 3.5 Sonnet Integration
```php
Input: article title + extracted body text
Prompt: Social scientist perspective analysis
Output: JSON structure with:
  - entities: [{"name": "Entity", "motivations": ["Motivation1", "Motivation2"]}]
  - key_metric: "Specific US Performance Metric"  
  - analysis: "Detailed social scientist analysis"
  - credibility_score: 0-100
  - bias_rating: 0-100 (50=center)
  - bias_analysis: "Explanation of bias assessment"
  - sentiment_score: 0-100
```

### Field Population Logic
```php
field_ai_raw_response → store complete Claude response
field_motivation_analysis → formatted analysis for display  
field_motivation_data → JSON structure for programmatic use
field_credibility_score → extracted credibility rating
field_bias_rating → extracted bias score
field_bias_analysis → bias explanation
field_article_sentiment_score → sentiment score
```

## Processing Hooks

### Content Import Hooks
- `hook_feeds_process_alter()` - News source extraction during feed import
- `hook_node_insert()` - Trigger Diffbot extraction and AI analysis
- `hook_node_update()` - Re-process when URL changes

### Maintenance Hooks  
- `hook_cron()` - Background news source population and content extraction

## Drush Commands

### News Source Population
```bash
drush ne:stats                    # Field population statistics
drush ne:pop-sources             # Process JSON data (batch 100)  
drush ne:pop-sources 50          # Custom batch size
drush ne:pop-sources --all       # Process all articles
drush ne:pop-url                 # URL extraction fallback
drush ne:test https://cnn.com    # Test URL extraction
```

## Database Optimization

### Indexes Required
- `field_news_source` - For source-based queries
- `field_json_scraped_article_data` - For processing discovery
- `field_original_url` - For URL-based processing
- `field_publication_date` - For temporal analysis

### Query Patterns
- Batch processing: 25-100 articles per operation
- OR conditions for multi-field discovery
- Range limits for memory management
- Access checks disabled for administrative operations

## Error Handling

### JSON Processing
- Validation of JSON structure before parsing
- Graceful handling of malformed data
- Logging of parsing errors with article IDs

### Field Access
- `hasField()` checks before field operations
- Graceful degradation when fields missing
- Comprehensive error logging

### API Integration
- Retry logic for Diffbot/Claude failures
- Rate limiting compliance
- Fallback processing for API unavailability

## Caching Strategy

### Content Caching
- Diffbot responses stored in `field_json_scraped_article_data`
- AI responses cached in multiple fields for different use cases
- Deduplication via `field_article_hash`

### Processing Caching
- Batch discovery queries cached per cron run
- Domain mapping cached in memory during processing
- Taxonomy term creation cached to avoid duplicates

## Security Considerations

### Data Access
- Admin-only access to raw JSON and AI response fields
- Public access to formatted analysis and scores
- Input sanitization for all external data

### API Security
- Secure credential management for Diffbot/Claude
- Rate limiting to prevent abuse
- Error handling to prevent information disclosure

## Performance Optimization

### Batch Processing
- Configurable batch sizes for different server capabilities
- Memory management with sleep intervals
- Progress tracking for large operations

### Query Optimization
- Efficient field discovery queries
- Proper use of entity query conditions
- Range limiting for memory management

### Content Processing
- Asynchronous AI processing where possible
- Incremental updates vs full reprocessing
- Smart caching of expensive operations
