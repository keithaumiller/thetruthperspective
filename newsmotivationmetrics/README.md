# News Motivation Metrics Module

A comprehensive analytics dashboard for The Truth Perspective news analysis system, providing real-time insights into AI-powered content evaluation, narrative tracking, and media source analysis.

## Overview

The News Motivation Metrics module creates a public-facing analytics dashboard that showcases the depth and scope of news analysis performed by The Truth Perspective platform. It provides transparency into the AI-driven content evaluation process and offers insights into media narrative patterns.

## Features

### 📊 Public Analytics Dashboard (`/metrics`)
- **Real-time content analysis metrics** showing processing coverage and success rates
- **Activity tracking** with recent article processing and daily averages  
- **Analysis quality indicators** measuring AI response depth and classification density
- **Dominant narrative themes** with interactive tag exploration
- **Media source distribution** showing content source diversity
- **Methodology transparency** explaining the analysis process

### 🏷️ Interactive Theme Analysis (`/metrics/tag/{tid}`)
- **Detailed theme breakdowns** for individual narrative topics
- **Article timelines** showing theme prevalence over time
- **Cross-source analysis** revealing which outlets cover specific themes
- **Direct links** to analyzed articles for deeper investigation

### 🔧 Admin Dashboard (`/admin/reports/news-motivation-metrics`)
- **Enhanced administrative view** with additional operational metrics
- **Full data access** for system administrators
- **Performance monitoring** and system health indicators

## Technical Architecture

### Data Sources
- **Article Content**: Extracted via Diffbot API from 15+ media sources
- **AI Analysis**: Powered by Claude language models for motivation and entity analysis
- **Taxonomy Classification**: Automated tag generation based on content themes
- **Publication Metadata**: Including source attribution and publication timing

### Database Queries
The module uses optimized database queries to aggregate metrics from:
- `node_field_data` - Core article information
- `node__field_*` - Custom field data (AI responses, tags, metadata)
- `taxonomy_term_field_data` - Tag and classification information

### Performance Considerations
- **Efficient aggregation queries** with proper indexing
- **Caching-friendly architecture** for public dashboard performance
- **Batch processing** for large datasets
- **Responsive design** for mobile accessibility

## Installation

1. **Enable the module**:
   ```bash
   drush en newsmotivationmetrics -y
   drush cr
   ```

2. **Set permissions** (for admin dashboard):
   ```bash
   drush user:role:add administrator "access news motivation metrics"
   ```

3. **Access dashboards**:
   - Public: `/metrics`
   - Admin: `/admin/reports/news-motivation-metrics`

## Configuration

### Public Access
The public dashboard (`/metrics`) is accessible to all visitors without authentication, providing transparency into the analysis process.

### Admin Access
The admin dashboard requires the "access news motivation metrics" permission and provides additional operational insights.

### Customization
Module behavior can be customized by modifying:
- **MetricsController.php** - Dashboard layout and content
- **newsmotivationmetrics.module** - Data aggregation functions
- **CSS styling** - Visual presentation and branding

## API Functions

### Core Metrics Functions

#### `newsmotivationmetrics_get_article_metrics()`
Returns comprehensive article processing statistics:
```php
[
  'total_articles' => 1250,
  'articles_with_ai' => 1200,
  'articles_with_json' => 1240,
  'articles_with_tags' => 1180,
  'articles_with_motivation' => 1150,
  'articles_with_images' => 980,
  'total_tags' => 450,
  'articles_last_7_days' => 85,
  'articles_last_30_days' => 340
]
```

#### `newsmotivationmetrics_get_tag_metrics()`
Returns tag usage analytics with article counts:
```php
[
  [
    'tid' => 123,
    'name' => 'Political Strategy',
    'article_count' => 45
  ],
  // ... more tags
]
```

#### `newsmotivationmetrics_get_news_source_metrics()`
Returns media source distribution data:
```php
[
  [
    'source' => 'CNN',
    'article_count' => 156
  ],
  // ... more sources
]
```

#### `newsmotivationmetrics_get_motivation_insights()`
Returns content analysis quality metrics:
```php
[
  'avg_motivation_length' => 1250,
  'avg_ai_response_length' => 2840,
  'avg_tags_per_article' => 4.2
]
```

## Data Privacy & Transparency

### What We Track
- **Article processing statistics** (counts, success rates, timing)
- **Content classification data** (tags, themes, entities)
- **Source attribution** (media outlet names, publication dates)
- **Analysis quality metrics** (response lengths, classification density)

### What We Don't Track
- **Individual user behavior** or analytics
- **Personal identification** data
- **Private content** or sensitive information
- **User interactions** with the dashboard

### Public Data Policy
All data displayed on the public dashboard represents:
- **Aggregated statistics** without individual article identification
- **Public media content** already published by news organizations
- **Analytical insights** derived through automated processing
- **Transparent methodology** with clear source attribution

## Development & Extension

### Adding New Metrics
To add custom metrics, extend the core functions in `newsmotivationmetrics.module`:

```php
function newsmotivationmetrics_get_custom_metric() {
  $database = Database::getConnection();
  
  $query = $database->select('your_table', 't');
  // Build your query
  
  return $results;
}
```

### Customizing the Dashboard
Modify `MetricsController::dashboard()` to:
- **Add new sections** with custom data
- **Modify visual presentation** with CSS updates
- **Include additional charts** or visualizations
- **Enhance interactivity** with JavaScript components

### Database Optimization
For large datasets, consider:
- **Adding database indexes** on frequently queried fields
- **Implementing caching** for expensive queries
- **Using views** for complex aggregations
- **Batch processing** for real-time updates

## Troubleshooting

### Common Issues

**Dashboard not accessible**: Check routing cache
```bash
drush cr
```

**Missing data**: Verify news_extractor module is processing articles
```bash
drush pml | grep news_extractor
```

**Performance issues**: Check database indexes and query optimization
```bash
drush sqlq "SHOW PROCESSLIST;"
```

### Debug Mode
Enable detailed logging by adding to settings.php:
```php
$config['system.logging']['error_level'] = 'verbose';
```

## Dependencies

- **Drupal Core**: 9.x or 10.x
- **Node module**: For article content management
- **Taxonomy module**: For tag classification
- **Field module**: For custom field handling
- **News Extractor module**: For content processing (custom)

## License & Attribution

This module is part of The Truth Perspective platform and is designed to provide transparency into AI-powered news analysis. It respects media source attribution and promotes understanding of automated content evaluation processes.

## Support & Contribution

For issues, feature requests, or contributions:
1. **Review existing functionality** in the codebase
2. **Test changes** in a development environment
3. **Document modifications** for future maintenance
4. **Consider performance impact** of new features

---

**The Truth Perspective** - Bringing transparency to news analysis through AI-powered insights and public accountability.