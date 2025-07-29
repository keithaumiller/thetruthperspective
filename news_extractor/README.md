# News Extractor Module

A Drupal module that automatically extracts full article content using the Diffbot API and generates AI-powered motivation analysis with entity-motivation pairing for political and news content.

## Features

### Content Extraction
- **Diffbot Integration**: Automatically extracts full article text, title, and metadata from news URLs
- **Smart Filtering**: Filters out non-article content (podcasts, videos, ads, galleries)
- **Automatic Processing**: Processes articles during feed imports or manually via Drush commands

### AI-Powered Analysis
- **Motivation Analysis**: Uses AWS Bedrock Claude 3.5 Sonnet to analyze articles from a social scientist perspective
- **Entity-Motivation Pairing**: Identifies key entities (people, organizations) and their motivations in the context of each article
- **Performance Metrics**: Selects relevant US performance metrics and analyzes potential impact
- **Structured Data**: Stores both human-readable and machine-readable (JSON) versions of the analysis

### Taxonomy & Tagging
- **Automatic Tagging**: Creates taxonomy terms for entities, motivations, and metrics
- **Smart Linking**: Automatically links entities and motivations to their taxonomy pages
- **Browseable Content**: Users can browse articles by entity or motivation

## Installation

1. Place the module in your `modules/custom/news_extractor/` directory
2. Enable the module: `drush en news_extractor`
3. Configure API tokens (see Configuration section)

## Configuration

### Required API Tokens

Set these in your Drupal configuration:

```php
// Diffbot API Token
$config['news_extractor.settings']['diffbot_token'] = 'your_diffbot_token';

// AWS Credentials for Bedrock
$config['news_extractor.settings']['aws_access_key'] = 'your_aws_access_key';
$config['news_extractor.settings']['aws_secret_key'] = 'your_aws_secret_key';
$config['news_extractor.settings']['aws_region'] = 'us-east-1';
```

### Required Fields

The module expects these fields on your Article content type:

- `field_original_url` (Link field) - Source URL
- `field_motivation_analysis` (Text, Basic HTML) - Human-readable analysis
- `field_motivation_data` (Text, plain) - JSON structured data
- `field_tags` (Entity reference to Taxonomy terms) - Auto-generated tags
- `body` (Text, Basic HTML) - Full article content

## Usage

### Automatic Processing

The module automatically processes articles when:
- New articles are imported via Feeds module
- Articles are created with a `field_original_url` but no body content

### Manual Processing

Process articles missing body content:
```bash
drush php-eval "require_once './modules/custom/news_extractor/news_extractor.scraper.php'; news_extractor_update_articles_missing_body_from_diffbot();"
```

Update formatting for existing motivation analysis:
```bash
drush php-eval "require_once './modules/custom/news_extractor/news_extractor.scraper.php'; news_extractor_update_motivation_analysis_formatting();"
```

## AI Analysis Format

The AI generates analysis in this structured format:

```
Entities mentioned:
- Donald Trump: Ambition, Power, Recognition
- Joe Biden: Legacy, Unity, Justice
- Department of Justice: Professional pride, Duty

Key metric: Public Trust in Government

As a social scientist, I speculate that this article will impact...
[Analysis paragraph continues]
```

## Motivation Categories

The system uses these core motivations:
- Ambition, Competitive spirit, Righteousness, Moral outrage
- Loyalty, Pride, Determination, Fear, Greed, Power, Control
- Revenge, Justice, Self-preservation, Recognition, Legacy
- Influence, Security, Freedom, Unity, Professional pride
- Duty, Curiosity, Enthusiasm, Wariness, Anxiety
- Self-respect, Obligation, Indignation

## Data Structure

### Human-Readable (field_motivation_analysis)
Formatted with proper spacing and links to taxonomy terms for web display.

### Machine-Readable (field_motivation_data)
JSON structure for programmatic access:

```json
{
  "entities": [
    {
      "name": "Donald Trump",
      "motivations": ["Ambition", "Power", "Recognition"]
    }
  ],
  "motivations": ["Ambition", "Power", "Recognition"],
  "metrics": ["Public Trust in Government"]
}
```

### Taxonomy Tags (field_tags)
Automatically created taxonomy terms for:
- Entity names
- Motivation keywords  
- Performance metrics

## Debugging

Debug newest articles:
```bash
drush php-eval "require_once './modules/custom/news_extractor/news_extractor.scraper.php'; news_extractor_debug_newest_motivation_analysis();"
```

Test formatting updates:
```bash
drush php-eval "require_once './modules/custom/news_extractor/news_extractor.scraper.php'; news_extractor_test_update();"
```

## File Structure

```
news_extractor/
├── news_extractor.info.yml       # Module definition
├── news_extractor.module          # Core functions, AI integration
├── news_extractor.scraper.php     # Content extraction, formatting
└── README.md                      # This file
```

## Key Functions

### news_extractor.module
- `_news_extractor_generate_ai_summary()` - AWS Bedrock integration
- `_news_extractor_build_ai_prompt()` - AI prompt construction
- `_news_extractor_extract_tags_from_summary()` - Tag extraction
- `news_extractor_linkify_summary_tags()` - Auto-linking

### news_extractor.scraper.php  
- `_news_extractor_extract_content()` - Main content processing
- `news_extractor_format_motivation_analysis()` - Text formatting
- `_news_extractor_extract_structured_data()` - JSON data extraction
- `news_extractor_update_motivation_analysis_formatting()` - Bulk updates

## Requirements

- Drupal 9+
- Feeds module (for automatic article imports)
- Diffbot API account
- AWS account with Bedrock access
- Taxonomy module (core)

## License

This module is provided as-is for educational and research purposes.
