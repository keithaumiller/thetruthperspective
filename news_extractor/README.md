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
- **Credibility & Bias Analysis**: Provides credibility scores (0-100) and bias ratings (0-100) with explanations
- **Sentiment Analysis**: Analyzes article sentiment with scores from 0-100
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
$config['news_extractor.settings']['aws_region'] = 'us-west-2';
```

### Required Fields

The module expects these fields on your Article content type:

- `field_original_url` (Link field) - Source URL
- `field_motivation_analysis` (Text, formatted, long) - Human-readable analysis
- `field_motivation_data` (Text, plain, long) - JSON structured data
- `field_ai_summary` (Text, plain, long) - Raw AI response
- `field_credibility_score` (Text, plain) - Credibility score (0-100)
- `field_bias_rating` (Text, plain) - Bias rating (0-100)
- `field_bias_analysis` (Text, plain) - Bias explanation
- `field_article_sentiment_score` (List, text) - Sentiment score (0-100)
- `field_original_author` (Text, plain) - Article author
- `field_publication_date` (Date) - Publication date
- `field_news_source` (Text, plain) - News source name
- `field_article_hash` (Text, plain, long) - Content hash for deduplication
- `field_external_image_url` (Link) - External image URL
- `field_tags` (Entity reference to Taxonomy terms) - Auto-generated tags
- `body` (Text, formatted, long) - Full article content

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

Credibility Score: 85

Bias Rating: 25

Bias Analysis:
- The article presents factual information with minimal editorial commentary
- Sources are clearly attributed and claims are substantiated with evidence

Article Sentiment Score: 45

As a social scientist, I analyze that this article will impact...
[Analysis paragraph continues]
```

### Analysis Components

- **Credibility Score**: 0-100 scale (100 = perfect trust, 0 = no credibility)
- **Bias Rating**: 0-100 scale (0=Extreme Left, 25=Lean Left, 50=Center, 75=Lean Right, 100=Extreme Right)
- **Bias Analysis**: Two-line explanation of the bias rating
- **Article Sentiment Score**: 0-100 scale (100 = most positive, 0 = most negative)

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
Formatted HTML with proper spacing and links to taxonomy terms for web display.

### Machine-Readable (field_motivation_data)
JSON structure for programmatic access:

```json
{
  "entities": [
    {
      "name": "Entity Name",
      "motivations": ["Motivation1", "Motivation2"]
    }
  ],
  "motivations": ["Motivation1", "Motivation2"],
  "metrics": ["Public Trust in Government"],
  "credibility_score": 85,
  "bias_rating": 25,
  "bias_analysis": [
    "First line explaining bias rating",
    "Second line explaining bias rating"
  ],
  "sentiment_score": 45
}
```

### Individual Fields
- **field_ai_summary**: Raw AI response text
- **field_credibility_score**: Numeric credibility score as string (0-100)
- **field_bias_rating**: Numeric bias rating as string (0-100)
- **field_bias_analysis**: Newline-separated bias explanation
- **field_article_sentiment_score**: Numeric sentiment score as string (0-100)
- **field_original_author**: Author extracted from Diffbot
- **field_publication_date**: Date from Diffbot data
- **field_news_source**: Site name from Diffbot
- **field_article_hash**: MD5 hash of title + content

### Taxonomy Tags (field_tags)
Automatically created taxonomy terms for:
- Entity names
- Motivation keywords
- Performance metrics

## Key Functions

- `_news_extractor_generate_ai_summary()` – Handles AWS Bedrock integration for AI analysis
- `_news_extractor_build_ai_prompt()` – Constructs the prompt for the AI with new scoring requirements
- `_news_extractor_extract_structured_data()` – Extracts all analysis components including scores
- `_news_extractor_extract_tags_from_summary()` – Extracts tags from the AI summary
- `news_extractor_linkify_summary_tags_enhanced()` – Auto-links taxonomy terms in the analysis
- `news_extractor_format_motivation_analysis()` – Formats the analysis for display
- `_news_extractor_update_article()` – Updates article with Diffbot metadata and generates content hash

## Dependencies

- Drupal 9+
- Feeds module
- Diffbot API access
- AWS Bedrock access (Claude 3.5 Sonnet)
- Taxonomy module
