# News Extractor Module

**Enhanced news article processing with AI-powered analysis for The Truth Perspective platform**

## Overview

The News Extractor module is a core component of The Truth Perspective platform that automatically processes news articles through a sophisticated pipeline combining content extraction, AI analysis, and data enrichment. It transforms raw RSS feeds into fully analyzed articles with sentiment analysis, bias detection, entity recognition, and motivation classification.

## Architecture

The module follows a service-oriented architecture with clear separation of concerns:

### Service Architecture (Sensors → Processors → Levers)

- **ScrapingService** (Sensors): Diffbot API integration and content extraction
- **AIProcessingService** (Processors): Claude AI analysis and prompt building  
- **DataProcessingService** (Levers): Field updates, taxonomy management, and data formatting
- **NewsExtractionService** (Orchestrator): Coordinates all services in the processing pipeline

## Key Features

### 🔍 **Content Extraction**
- **Diffbot API Integration**: Extracts clean article content from any news URL
- **Metadata Extraction**: Automatically captures title, author, publication date, source
- **Content Validation**: Filters out invalid articles and blocked content
- **Source Detection**: Identifies news source from various feed formats

### 🤖 **AI-Powered Analysis**
- **Claude AI Integration**: Advanced natural language processing
- **Sentiment Analysis**: Measures article emotional tone (-1 to +1 scale)
- **Bias Detection**: Identifies political/ideological bias (0-10 scale)
- **Credibility Scoring**: Assesses article reliability (0-10 scale)
- **Entity Recognition**: Extracts people, organizations, locations, concepts
- **Motivation Analysis**: Identifies underlying motivations (political, economic, social)

### 📊 **Data Management**
- **Structured Field Storage**: Organized data in Drupal custom fields
- **Taxonomy Integration**: Auto-generates and assigns relevant tags
- **Batch Processing**: Handles large volumes of articles efficiently
- **Processing Status Tracking**: Monitors completion status of each processing stage

### ⚙️ **Processing Pipeline**
1. **Feed Import**: RSS/feed integration with validation
2. **Content Scraping**: Diffbot API extraction
3. **AI Analysis**: Claude AI processing with structured prompts
4. **Data Processing**: Field updates and taxonomy assignment
5. **Quality Assurance**: Validation and error handling

## Installation & Configuration

### Prerequisites
- Drupal 11
- Feeds module
- Custom article content type with required fields
- API keys for Diffbot and Claude AI

### Required Fields
The module expects the following fields on the `article` content type:

**Content Fields:**
- `field_original_url` (Link): Source article URL
- `field_news_source` (Text): News source/publication name
- `field_json_scraped_article_data` (Long text): Raw Diffbot response
- `field_ai_raw_response` (Long text): Raw Claude AI response

**Analysis Fields:**
- `field_article_sentiment_score` (Number): Sentiment analysis (-1 to +1)
- `field_bias_rating` (Number): Bias score (0-10)
- `field_credibility_score` (Number): Credibility score (0-10)

**Entity Fields:**
- `field_ai_people` (Text, multiple): Extracted person entities
- `field_ai_organizations` (Text, multiple): Extracted organization entities
- `field_ai_locations` (Text, multiple): Extracted location entities
- `field_ai_concepts` (Text, multiple): Extracted concept entities

**Motivation Fields:**
- `field_ai_motivations` (Text, multiple): Identified motivations
- `field_ai_political_motivations` (Text, multiple): Political motivations
- `field_ai_economic_motivations` (Text, multiple): Economic motivations
- `field_ai_social_motivations` (Text, multiple): Social motivations

### Configuration

1. **Install the module**: `drush en news_extractor`

2. **Configure API settings**: Navigate to `/admin/config/news-extractor/settings`
   - Set Diffbot API key
   - Set Claude AI API key
   - Configure processing options

3. **Set up Feeds**: Configure RSS/feed imports to target the article content type

## Usage

### Automatic Processing
Articles are automatically processed when:
- New articles are imported via Feeds
- Articles are created with `field_original_url`

### Manual Processing
Use Drush commands for manual operations and maintenance:

#### Comprehensive Statistics and Analysis
```bash
# Get detailed field statistics and debug information
drush ne:stats                    # Shows field existence, data availability
drush news-extractor:source-stats # Full alias

# Get processing summary with recommendations
drush ne:summary                  # Overview with next steps
drush news-extractor:summary      # Full alias
```

#### News Source Population (Primary Tasks)
```bash
# Process from JSON data (most reliable method)
drush ne:pop-sources              # Default batch size (100)
drush ne:pop-sources 50           # Custom batch size
drush ne:pop-sources --all        # Process all articles in one run
drush news-extractor:populate-sources  # Full alias

# Process from URLs (fallback method)
drush ne:pop-url                  # Default batch size (50)
drush ne:pop-url 25               # Custom batch size
drush news-extractor:populate-sources-url  # Full alias
```

#### Smart Cleanup (Remove Unsuitable Content)
```bash
# Preview what would be deleted (recommended first step)
drush ne:cleanup --dry-run        # Shows articles that would be deleted
drush ne:cleanup --dry-run --limit=200  # Check more articles

# Actually delete unsuitable articles
drush ne:cleanup                  # Delete up to 100 articles
drush ne:cleanup --limit=500      # Delete more articles

# Full alias
drush news-extractor:cleanup      # Same functionality
```

**Automatically removes articles that are:**
- 🎥 **Video content** (URLs containing `/video/`)
- 📘 **Facebook links** (`facebook.com` domains)
- 📄 **PDF files** (`.pdf` extensions)
- 📱 **Social media** (Twitter, Instagram, LinkedIn, TikTok)
- 🎬 **Video platforms** (YouTube, Vimeo, Dailymotion)
- 🔗 **Articles with no URL** (missing `field_original_url`)

> **💡 Tip**: Run `drush ne:cleanup --dry-run` first to see what would be deleted before running actual cleanup.

#### Standard Processing
```bash
# Process recent articles
drush news-extractor:process

# Bulk process with options
drush news-extractor:bulk-process --limit=50 --type=full

# Process only scraping (no AI analysis)
drush news-extractor:bulk-process --type=scrape_only

# Process only AI analysis (articles already scraped)
drush news-extractor:bulk-process --type=analyze_only

# Reprocess articles (re-run AI analysis) - now with smart cleanup
drush news-extractor:bulk-process --type=reprocess

# Check processing status
drush news-extractor:status
```

> **🧹 Smart Processing**: All bulk processing commands now automatically delete unsuitable articles (videos, PDFs, social media, etc.) instead of trying to process them, keeping your database clean and focused on actual news content.

### Processing Types

**Full Processing** (`full`): Complete pipeline from URL to analyzed article
**Scrape Only** (`scrape_only`): Diffbot extraction only, no AI analysis
**Analyze Only** (`analyze_only`): AI analysis on already scraped articles
**Reprocess** (`reprocess`): Re-run AI analysis on articles with existing data

## API Integration

### Diffbot API
- **Purpose**: Clean content extraction from news URLs
- **Benefits**: Removes ads, navigation, and extracts pure article content
- **Data**: Title, author, text, publication date, site metadata

### Claude AI API
- **Purpose**: Advanced content analysis and entity extraction
- **Capabilities**: Sentiment, bias, credibility, entities, motivations
- **Output**: Structured JSON responses for systematic data storage

## Content Filtering & Smart Cleanup

### Automatically Deleted Content
The module now automatically **deletes** (rather than processes) articles that are:

**🎥 Video Content**
- URLs containing `/video/` paths
- Fox News video links, YouTube videos, etc.

**📱 Social Media Content**
- Facebook links (`facebook.com`)
- Twitter/X links (`twitter.com`) 
- Instagram links (`instagram.com`)
- LinkedIn articles (`linkedin.com`)
- TikTok links (`tiktok.com`)

**🎬 Video Platforms**
- YouTube videos (`youtube.com`)
- Vimeo content (`vimeo.com`)
- Dailymotion videos (`dailymotion.com`)

**📄 Document Files**
- PDF files (`.pdf` extensions)
- Direct document downloads

**🔗 Invalid Articles**
- Articles with no URL (`field_original_url` missing/empty)
- Malformed or unreachable URLs

### Traditional Content Filtering
The module also filters out content from:
- `comparecards.com`
- `fool.com` 
- `lendingtree.com`
- Articles with very short titles (< 10 characters)
- Articles with missing or empty titles

### URL Validation
- Validates article URLs before processing
- Automatically deletes invalid or unsuitable content
- Logs deleted content for review

## Data Flow

```
RSS Feed → Feed Import → URL Validation → Content Filtering
    ↓
Diffbot API → Content Extraction → Metadata Capture
    ↓
Claude AI → Sentiment Analysis → Bias Detection → Entity Extraction
    ↓
Data Processing → Field Updates → Taxonomy Assignment → Storage
```

## Error Handling

- **Graceful Degradation**: Failed processing doesn't break the import pipeline
- **Comprehensive Logging**: All operations logged for debugging
- **Retry Logic**: Automatic retry for temporary API failures
- **Status Tracking**: Processing status stored for each article

## Performance Considerations

- **Batch Processing**: Configurable batch sizes for bulk operations
- **API Rate Limiting**: Respects API rate limits and quotas
- **Efficient Queries**: Optimized database queries for large datasets
- **Memory Management**: Handles large article datasets efficiently

## Monitoring & Debugging

### Log Messages
Monitor processing via Drupal logs:
- Successful extractions and analyses
- Failed operations with error details
- Skipped content with reasons
- Processing statistics

### Status Checking
```bash
# Get detailed status of recent articles
drush news-extractor:status --limit=20

# Check processing statistics
drush news-extractor:stats
```

## Integration with The Truth Perspective

This module provides the foundation data for:
- **News Motivation Metrics**: Public analytics dashboard
- **Credibility Tracking**: Source reliability analysis  
- **Bias Monitoring**: Political bias detection and trending
- **Entity Networks**: Relationship mapping between people/organizations
- **Narrative Analysis**: Story development and motivation tracking

## Development

### Service Extensions
New analysis capabilities can be added by:
1. Extending existing services or creating new ones
2. Adding new field mappings in `DataProcessingService`
3. Updating AI prompts in `AIProcessingService`
4. Registering services in `news_extractor.services.yml`

### Custom Processing
Developers can hook into the processing pipeline:
```php
// Custom processing after AI analysis
function mymodule_news_extractor_post_ai_analysis($node, $ai_response) {
  // Custom logic here
}
```

## Troubleshooting

### Common Issues

**Articles not processing:**
- Check API keys in configuration
- Verify field machine names match expectations
- Check logs for specific error messages

**Missing data:**
- Ensure all required fields exist on article content type
- Verify API responses in raw data fields
- Check field mapping in `DataProcessingService`

**Performance issues:**
- Reduce batch processing limits
- Check API rate limits and quotas
- Monitor memory usage during bulk operations

### Support

- Check Drupal logs: `/admin/reports/dblog`
- Review processing status: `drush news-extractor:status`
- Enable debug mode for detailed logging

## License

This module is part of The Truth Perspective platform and follows the project's licensing terms.

---

*For technical support or questions about this module, please refer to the project documentation or contact the development team.*
