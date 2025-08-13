# The Truth Perspective - Technical Architecture

## System Overview

The Truth Perspective is an AI-powered news analysis platform built on Drupal 11, designed to analyze news articles for bias, motivation, and credibility. The system integrates multiple data sources and AI services to provide comprehensive analytics.

## Core Infrastructure

- **Platform**: Drupal 11 on Ubuntu 22.04 LTS
- **PHP**: 8.3.6 (CLI)
- **Database**: MySQL with optimized indexing for large datasets
- **AI Integration**: AWS Bedrock Claude 3.5 Sonnet
- **Content Extraction**: Diffbot API for article parsing
- **Production URL**: https://thetruthperspective.org

## Module Architecture

### Primary Modules

#### 1. **news_extractor** (Core Content Processing)
- **Purpose**: Content scraping, AI analysis, and data processing pipeline
- **Key Features**:
  - RSS feed import and processing
  - Diffbot API integration for content extraction
  - Multi-stage news source population system
  - AWS Bedrock Claude integration for AI analysis
  - Comprehensive Drush command suite for maintenance

#### 2. **newsmotivationmetrics** (Public Analytics Dashboard)
- **Purpose**: Public-facing analytics and metrics visualization with multiple chart types
- **Key Features**:
  - Real-time analytics dashboard with Chart.js visualizations
  - News motivation timeline charts for trend analysis
  - News source quality timeline charts for bias/credibility tracking
  - **Data Isolation Architecture**: Unique settings keys prevent chart data contamination
  - Mobile-responsive design with professional presentation
  - Public accessibility without authentication

#### Recent Critical Fix (August 13, 2025): Chart Data Isolation
- **Problem Resolved**: Multiple chart types on same page were experiencing data contamination
- **Root Cause**: Shared `drupalSettings['newsmotivationmetrics']` namespace causing chart data overwriting
- **Solution Implemented**: 
  - `drupalSettings.newsmotivationmetrics` → News motivation timeline data
  - `drupalSettings.newsmotivationmetrics_sources` → News source quality data
- **Files Modified**: NewsSourceTimelineChartService.php, news-source-timeline-chart.js
- **Result**: Clean chart displays without undefined labels or mixed datasets

#### 3. **ai_conversation** (AI Chat Interface)
- **Purpose**: Interactive AI conversation system
- **Key Features**:
  - Real-time chat interface
  - Claude API integration
  - Conversation history management

## Field Architecture

### Article Content Type Fields

#### Core Content Fields
- **field_json_scraped_article_data** (Long text)
  - **Purpose**: Raw Diffbot API responses in JSON format
  - **Structure**: Complete article extraction data including metadata
  - **Usage**: Primary source for news source extraction and content analysis

- **field_news_source** (Text - plain)
  - **Purpose**: Standardized news source name
  - **Population**: Multi-stage extraction (JSON → URL → Feed)
  - **Examples**: "CNN", "Fox News", "Reuters", "BBC News"

- **field_original_url** (Link)
  - **Purpose**: Original article URL
  - **Structure**: Drupal link field with .uri property
  - **Usage**: Fallback source extraction and content verification

#### AI Analysis Fields
- **field_ai_raw_response** (Long text)
  - **Purpose**: Complete raw AI responses from Claude
  - **Format**: JSON structure with full analysis data

- **field_ai_summary** (Long text)
  - **Purpose**: AI-generated article summaries
  - **Processing**: Extracted from Claude responses

- **field_motivation_analysis** (Long text)
  - **Purpose**: Detailed motivation analysis from AI
  - **Content**: Political, economic, social motivation breakdown

- **field_motivation_data** (Long text)
  - **Purpose**: Structured motivation data for analytics
  - **Format**: JSON data for dashboard consumption

- **field_bias_analysis** (Long text)
  - **Purpose**: Comprehensive bias analysis
  - **Content**: Political bias detection and scoring

- **field_bias_rating** (Text - plain)
  - **Purpose**: Simplified bias rating
  - **Values**: Standardized bias classifications

#### Scoring and Metrics Fields
- **field_article_sentiment_score** (Number - decimal)
  - **Purpose**: Numerical sentiment analysis score
  - **Range**: Typically -1.0 to 1.0

- **field_credibility_score** (Number - decimal)
  - **Purpose**: Article credibility assessment
  - **Usage**: Quality and reliability metrics

#### Metadata Fields
- **field_publication_date** (Date)
  - **Purpose**: Article publication timestamp
  - **Format**: Y-m-d\TH:i:s (ISO 8601)
  - **Priority**: Uses estimatedDate from Diffbot when available

- **field_article_hash** (Text - plain)
  - **Purpose**: Unique content identifier
  - **Usage**: Duplicate detection and content tracking

- **field_tags** (Entity reference - taxonomy)
  - **Purpose**: Categorization and tagging
  - **Population**: AI-generated and manual assignment

- **field_original_author** (Text - plain)
  - **Purpose**: Original article author
  - **Source**: Extracted from Diffbot data

#### Extended Analysis Fields
- **field_external_image_url** (Link)
  - **Purpose**: External image references
  - **Usage**: Media asset management

- **field_image** (Image)
  - **Purpose**: Local image storage
  - **Processing**: Downloaded and stored locally

- **field_fact_check_status** (Text - plain)
  - **Purpose**: Fact-checking status tracking
  - **Values**: Standardized fact-check classifications

## Data Processing Pipeline

### Stage 1: Content Import (Feed Processing)
```
RSS/JSON Feed → Feeds Module → Entity Creation → Initial Fields
```
- **Process**: `diffbot_integration` feeds import article list from Diffbot API
- **Data Available**: `title`, `field_original_url` (link from feed)
- **Hook**: `news_extractor_feeds_process_alter()` (NOT called for diffbot_integration)
- **Initial State**: Minimal entity with URL only

### Stage 2: Entity Creation
```
Minimal Entity → hook_entity_insert() → Queue Full Processing
```
- **Location**: `news_extractor_entity_insert()`
- **Action**: Trigger complete article processing pipeline
- **Note**: News source extraction deferred to metadata stage

### Stage 3: Content Scraping (Diffbot Individual Article API)
```
Article URL → Diffbot Article API → Complete Article Data → JSON Storage
```
- **External Dependency**: Diffbot Article Analysis API
- **API Endpoint**: `https://api.diffbot.com/v3/article`
- **Rate Limits**: 13-second delay between calls
- **Data Retrieved**: `siteName`, `text`, `author`, `date`, `images`
- **Storage**: Complete response in `field_json_scraped_article_data`

### Stage 4: Metadata Update (PRIMARY NEWS SOURCE SETTING)
```
Diffbot Article Data → Extract siteName → Clean & Standardize → field_news_source
```
- **Location**: `ScrapingService::updateMetadataFields()`
- **Primary Source**: `siteName` from Diffbot JSON (e.g., "CNN Politics")
- **Cleaning**: Map "CNN Politics" → "CNN", "Reuters.com" → "Reuters"
- **Fallback**: URL domain extraction if siteName unavailable
- **Update Logic**: Always update if empty, "Source Unavailable", or different

#### News Source Extraction Priority:
1. **Diffbot siteName** (Priority 1): `"CNN Politics"` → `"CNN"`
2. **URL Domain Mapping** (Priority 2): `cnn.com` → `"CNN"`
3. **Unknown Domain Processing** (Priority 3): `unknown-news.com` → `"Unknown News"`

### Stage 5: AI Analysis (Claude)
```
Article Text → AWS Bedrock Claude → Structured Analysis → Multiple Fields
```
- **External Dependency**: AWS Bedrock Claude 3.5 Sonnet
- **Rate Limits**: Managed by AWS
- **Analysis Types**: Bias detection, motivation analysis, sentiment scoring
- **Storage**: Raw responses in `field_ai_raw_response`, parsed data in specific fields

### Stage 6: Analytics Aggregation
```
Individual Article Data → Batch Processing → Aggregated Statistics → Dashboard
```
- **Frequency**: Real-time for new articles, batch for historical analysis
- **Output**: Public analytics dashboard with comprehensive metrics

## External Dependencies

### Required External Services

#### 1. Diffbot API
- **Purpose**: Content extraction and article analysis
- **Endpoints Used**:
  - List API: `https://api.diffbot.com/v3/analyze?url=https://cnn.com/politics`
  - Article API: `https://api.diffbot.com/v3/article?url=<article_url>`
- **Authentication**: API token in URL parameter
- **Rate Limits**: Self-imposed 13-second delays
- **Failure Handling**: Store "Scraped data unavailable." placeholder

#### 2. AWS Bedrock (Claude AI)
- **Purpose**: Article analysis and bias detection
- **Model**: Claude 3.5 Sonnet
- **Authentication**: AWS credentials
- **Rate Limits**: Managed by AWS service
- **Failure Handling**: Skip AI analysis, log errors

#### 3. Feed Sources
- **CNN Politics Feed**: Via Diffbot integration
- **Processing**: Automated import via Drupal Feeds module
- **Frequency**: Configurable (currently 6-hour intervals)

### Service Architecture Dependencies

#### Internal Drupal Services
- `news_extractor.scraping`: Content extraction (Sensors)
- `news_extractor.ai_processing`: AI analysis (Processors) 
- `news_extractor.data_processing`: Field updates (Levers)
- `news_extractor.extraction`: Orchestration (Coordinator)

#### External API Integration Points
- **Diffbot Integration**: Handle API failures gracefully
- **AWS Integration**: Manage authentication and rate limits
- **Feed Processing**: Support multiple feed types and sources

## News Source Population System### Query Logic and Database Handling

#### NULL vs Empty String Handling
Drupal stores empty fields as `NULL` in the database, not empty strings. All queries use proper NULL handling:

```php
// Correct query pattern for finding empty news sources
->group()
  ->condition('field_news_source', NULL, 'IS NULL')
  ->condition('field_news_source', '', '=')
->groupOperator('OR')
```

#### Processing Functions

**Primary Function: JSON Data Extraction**
```php
_news_extractor_populate_news_source_from_json_data($batch_size = 50)
```
- **Query**: Articles with JSON data but NULL/empty news source
- **Processing**: Extract siteName from Diffbot JSON, clean and standardize
- **Batch Size**: 50 articles per run for performance

**Fallback Function: URL Domain Extraction**
```php
_news_extractor_fix_missing_news_sources($batch_size = 25)
```
- **Query**: Articles with URLs but NULL/empty news source
- **Processing**: Extract domain, map to known sources, generate names
- **Batch Size**: 25 articles per run (lighter processing)

### Domain Mapping System

#### Known Domain Mappings
```php
$domain_map = [
  'cnn.com' => 'CNN',
  'foxnews.com' => 'Fox News',
  'reuters.com' => 'Reuters',
  'ap.org' => 'Associated Press',
  'npr.org' => 'NPR',
  'bbc.com' => 'BBC News',
  // ... 15+ major news sources
];
```

#### Unknown Domain Processing
- **Pattern**: Extract primary domain component
- **Transform**: Convert hyphens/underscores to spaces, title case
- **Example**: `unknown-news-site.com` → `Unknown News Site`

### Source Name Standardization

#### Cleaning Patterns
```php
$patterns_to_remove = [
  '/\s*-\s*RSS.*$/i',        // Remove RSS suffixes
  '/\s*Breaking News.*$/i',   // Remove breaking news
  '/\s*\|.*$/i',             // Remove pipe separators
  '/\s*::.*$/i',             // Remove double colons
];
```

#### Standardization Rules
```php
$standardizations = [
  '/^CNN Politics$/i' => 'CNN',
  '/^FOX News.*$/i' => 'Fox News',
  '/^The New York Times.*$/i' => 'New York Times',
  // ... comprehensive standardization rules
];
```

## Complete Drush Command Reference

### News Extractor Module Commands

#### Statistics and Analysis Commands
```bash
# Comprehensive field statistics with debug information
drush ne:stats                    # Shows field existence, data availability, processing opportunities
drush news-extractor:source-stats # Full alias for statistics

# Processing summary with recommendations
drush ne:summary                  # Overview with next steps and current status
drush news-extractor:summary      # Full alias for summary
```

**Output**: Field existence verification, data availability counts, processing opportunities, and actionable recommendations.

#### News Source Population Commands (Primary Operations)
```bash
# Process articles from JSON data (most reliable method)
drush ne:pop-sources              # Default batch size (100 articles)
drush ne:pop-sources 50           # Custom batch size
drush ne:pop-sources --all        # Process all articles in one run
drush news-extractor:populate-sources        # Full alias

# Process articles from URLs (fallback method)
drush ne:pop-url                  # Default batch size (50 articles)
drush ne:pop-url 25               # Custom batch size
drush news-extractor:populate-sources-url    # Full alias
```

**Processing Logic**: 
- JSON method extracts from Diffbot `siteName` field (Priority 1)
- URL method uses domain mapping and standardization (Priority 2)

#### Data Cleanup and Maintenance Commands
```bash
# Fix articles with invalid JSON data using URL extraction fallback
drush ne:fix-json                 # Process articles with JSON but no extracted source
drush news-extractor:fix-invalid-json  # Full alias

# Standardize news source names (clean CNN variants, etc.)
drush ne:clean                    # Apply source name standardization
drush ne:clean --dry-run          # Show changes without applying them
drush ne:clean 50                 # Custom batch size for processing
drush news-extractor:clean-sources  # Full alias
```

**Standardization Examples**:
- `CNN Politics` → `CNN`
- `FOX News Breaking` → `Fox News`
- `Reuters.com` → `Reuters`

#### Testing and Debugging Commands
```bash
# Test news source extraction for specific URLs
drush ne:test https://cnn.com/article     # Test URL extraction logic
drush news-extractor:test-extraction URL # Full alias
```

**Output**: Detailed extraction results, URL parsing details, domain mapping, and final source assignment.

#### Recommended Command Workflow
```bash
# 1. Check current processing status
drush ne:summary

# 2. Process articles with JSON data first (highest success rate)
drush ne:pop-sources

# 3. Fix articles with invalid JSON using URL fallback
drush ne:fix-json

# 4. Process remaining articles using URL extraction
drush ne:pop-url

# 5. Standardize all source names
drush ne:clean

# 6. Verify final results
drush ne:stats
```

### News Motivation Metrics Module Commands

#### Cache Management Commands
```bash
# Update cached statistics and refresh dashboard data
drush newsmotivationmetrics:refresh-cache

# Generate performance report for monitoring
drush newsmotivationmetrics:performance-report

# Check for module-specific errors
drush watchdog:show --filter=newsmotivationmetrics

# Clear all module caches
drush cr
```

#### Debug and Testing Commands
```bash
# Test chart debug console (via curl)
curl https://thetruthperspective.org/newsmotivationmetrics/debug/chart

# Verify chart data processing
curl https://thetruthperspective.org/admin/reports/news-motivation-metrics
```

### AI Conversation Module Commands

#### System Testing and Configuration
```bash
# Test AI API connection and configuration
drush ai-conversation:test

# Check module configuration settings
drush config:get ai_conversation.settings

# View conversation-specific logs
drush watchdog:show --type=ai_conversation

# Verify database schema for conversation fields
drush sql:query "DESCRIBE node__field_conversation_summary"

# Update database schema if needed
drush updatedb
```

### General Maintenance Commands

#### System Health and Monitoring
```bash
# Check all enabled modules
drush pm:list | grep -E "(news_extractor|newsmotivationmetrics|ai_conversation)"

# Clear all Drupal caches
drush cr

# Check for any system errors
drush watchdog:show --count=50

# Update database schema for all modules
drush updatedb

# Rebuild cache and registry
drush cache:rebuild
```

#### Production Monitoring Commands
```bash
# Monitor processing status across all modules
drush ne:summary && drush watchdog:show --type=news_extractor --count=10

# Check for performance issues
drush newsmotivationmetrics:performance-report

# Verify AI conversation functionality
drush ai-conversation:test

# System health check
drush status
```

### Command Implementation

#### Debug Features
- **Field Existence Check**: Verify field availability on sample articles
- **Data Preview**: Show actual field content and structure
- **Processing Statistics**: Count articles at each processing stage
- **Error Logging**: Comprehensive logging for troubleshooting

#### Batch Processing Logic
- **Memory Management**: Process articles in configurable batches
- **Progress Tracking**: Real-time progress updates
- **Error Handling**: Graceful failure recovery
- **Performance Optimization**: Sleep delays between batches

## Automated Processing Hooks

### Drupal Hook Implementation

#### Feed Processing Hook
```php
function news_extractor_feeds_process_alter(&$process, $item, $entity_interface)
```
- **Trigger**: During RSS feed import
- **Action**: Extract source from feed metadata
- **Priority**: First stage source population

#### Node Creation Hook
```php
function news_extractor_node_insert($entity)
```
- **Trigger**: When new article created
- **Actions**: 
  1. Extract from JSON data if available
  2. Fallback to URL domain extraction
  3. Set initial news source

#### Node Update Hook
```php
function news_extractor_node_update($entity)
```
- **Trigger**: When article updated
- **Actions**:
  1. Re-extract if URL changed
  2. Update source if JSON data added
  3. Maintain source consistency

#### Cron Processing Hook
```php
function news_extractor_cron()
```
- **Frequency**: Hourly execution
- **Processing**: 
  - 25 articles from JSON data
  - 25 articles from URL fallback
  - Continuous background population

## Performance Considerations

### Database Optimization
- **Indexing**: Optimized indexes on frequently queried fields
- **Batch Processing**: Prevent memory exhaustion with large datasets
- **Query Optimization**: Efficient entity queries with proper conditions

### API Rate Limiting
- **Diffbot**: Managed through existing integration
- **AWS Bedrock**: Built-in rate limiting and retry logic
- **Batch Delays**: Sleep intervals between processing batches

### Caching Strategy
- **Field Data**: Cache expensive field queries
- **Statistics**: Cache aggregated statistics for dashboard
- **API Responses**: Store complete responses for offline analysis

## Error Handling and Logging

### Logging Implementation
```php
\Drupal::logger('news_extractor')->info('Processing completed', [
  '@count' => $updated_count,
  '@batch' => $batch_size,
]);
```

### Log Categories
- **info**: Successful operations and processing milestones
- **warning**: Non-critical issues and fallback usage
- **error**: Processing failures and system errors

### Error Recovery
- **Graceful Degradation**: Continue processing on individual failures
- **Retry Logic**: Automatic retry for transient failures
- **Fallback Methods**: Multiple extraction methods ensure coverage

## Security and Access Control

### Public Dashboard
- **Access**: No authentication required
- **Data**: Aggregated statistics only
- **Privacy**: No personal data tracking

### Admin Functions
- **Permissions**: `access news_extractor admin` required
- **Drush Commands**: Server access required
- **Field Management**: Admin role required

### Data Protection
- **Input Sanitization**: All user inputs properly sanitized
- **XSS Prevention**: Output properly escaped
- **SQL Injection**: Entity queries prevent injection

## Deployment and Maintenance

### Production Environment
- **Server**: Ubuntu 22.04 LTS on AWS
- **PHP**: 8.3.6 with Zend OPcache
- **Database**: MySQL with InnoDB storage engine
- **Caching**: Redis for session and cache storage

### Maintenance Procedures
1. **Regular Monitoring**: Check `drush ne:stats` for processing health
2. **Batch Processing**: Run manual processing during low-traffic periods
3. **Log Review**: Monitor Drupal logs for processing errors
4. **Performance Tuning**: Adjust batch sizes based on server performance

### Troubleshooting
1. **Field Verification**: Use debug output to verify field structure
2. **Query Testing**: Test individual queries for data availability
3. **Processing Validation**: Use test commands for specific URLs
4. **Error Analysis**: Review logs for systematic issues

## Integration Points

### External APIs
- **Diffbot**: Content extraction and article parsing
- **AWS Bedrock**: AI analysis and content processing
- **RSS Feeds**: Content discovery and import

### Internal Systems
- **Drupal Core**: Entity system, user management, permissions
- **Feeds Module**: RSS processing and content import
- **Views**: Data display and filtering
- **Taxonomy**: Content categorization and tagging

## Future Enhancements

### Planned Features
- **Enhanced AI Analysis**: Additional analysis dimensions
- **Real-time Processing**: Webhook-based immediate processing
- **Advanced Analytics**: More sophisticated statistical analysis
- **API Endpoints**: RESTful API for external integrations

### Scalability Considerations
- **Microservices**: Potential extraction to dedicated services
- **Queue Processing**: Background job processing for heavy operations
- **CDN Integration**: Static asset delivery optimization
- **Database Sharding**: Horizontal scaling for large datasets

## Development Guidelines

### Code Standards
- **Drupal Standards**: Follow Drupal 11 coding standards
- **Documentation**: Comprehensive inline documentation
- **Error Handling**: Proper exception handling and logging
- **Testing**: Unit tests for critical functions

### Best Practices
- **Batch Processing**: Always use batch processing for bulk operations
- **Field Validation**: Verify field existence before access
- **NULL Handling**: Proper NULL vs empty string handling in queries
- **Performance**: Monitor and optimize query performance
