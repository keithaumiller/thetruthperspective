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
- **Purpose**: Public-facing analytics and metrics visualization
- **Key Features**:
  - Real-time analytics dashboard
  - Chart.js visualizations
  - Mobile-responsive design
  - Public accessibility without authentication

#### 3. **ai_conversation** (AI Chat Interface)
- **Purpose**: Interactive AI conversation system
- **Key Features**:
  - Real-time chat interface
  - Claude API integration
  - Conversation history management

### Stage 2: Content Extraction  
```
field_original_url → Diffbot API → field_json_scraped_article_data
                                → body (extracted content)
                                → field_original_author
                                → field_publication_date
```

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

### Stage 1: Content Import
```
RSS Feed → Feeds Module → Article Creation → Initial Field Population
```
- **Process**: RSS feeds processed via Drupal Feeds module
- **Hook**: `news_extractor_feeds_process_alter()`
- **Actions**: Extract source from feed metadata, set initial field values

### Stage 2: Content Enhancement (Diffbot)
```
Article URL → Diffbot API → JSON Response → field_json_scraped_article_data
```
- **Process**: Diffbot extracts clean article content and metadata
- **Storage**: Complete response stored in `field_json_scraped_article_data`
- **Format**: JSON with objects[], request[], and metadata
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
