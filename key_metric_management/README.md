# Key Metric Management Module

A Drupal module for analyzing and managing key performance metrics identified in news articles through AI analysis.

## Overview

This module extracts and analyzes key performance metrics from news articles that have been processed by the News Extractor module. It provides administrative interfaces to view metric statistics, trends, and relationships between metrics and taxonomy terms.

## Features

- **Metric Dashboard**: View all identified key metrics with article counts
- **Taxonomy Integration**: Links key metrics to their corresponding taxonomy terms
- **Performance Optimized**: Cached queries and batch processing for large datasets
- **Admin Interface**: Clean administrative pages for metric management
- **Real-time Statistics**: Live counts and trend analysis

## Requirements

- Drupal 9.x or 10.x
- PHP 7.4+
- News Extractor module (dependency)
- Articles with `field_motivation_data` JSON field

## Installation

1. Download and place the module in `/modules/custom/key_metric_management/`
2. Enable the module: `drush en key_metric_management`
3. Clear cache: `drush cr`
4. Navigate to `/admin/config/content/key-metrics` to access the dashboard

## Usage

### Admin Dashboard
Access the main dashboard at `/admin/config/content/key-metrics` to view:
- Total unique metrics count
- Articles with metrics count
- Most common metric
- Complete list of all metrics with article counts

### Taxonomy Integration
Visit `/admin/config/content/key-metrics-taxonomy` to see:
- All taxonomy terms that correspond to key metrics
- Links to both taxonomy pages and metric analysis pages
- Ranked by frequency of occurrence

### Individual Metric Pages
Each metric has its own page at `/admin/config/content/key-metrics/{metric-name}` showing:
- Large count display of articles referencing the metric
- Clean, simple interface focused on the data

## API Functions

### Core Functions

```php
// Get all metrics with counts
$metrics = key_metric_management_get_all_metrics();

// Get count for specific metric
$count = key_metric_management_get_metric_count('Public Trust in Government');

// Get articles for specific metric (with pagination)
$articles = key_metric_management_get_articles_by_metric('Economic Growth', 50, 0);

// Get taxonomy terms that are metrics
$terms = key_metric_management_get_metric_taxonomy_terms();

// Get summary statistics
$stats = key_metric_management_get_metric_stats();
```

### Cache Management

```php
// Clear all module caches
key_metric_management_clear_cache();
```

## Performance Features

- **Query Optimization**: Uses database queries instead of loading all nodes
- **Batch Processing**: Processes large datasets in manageable chunks
- **Smart Caching**: Caches expensive operations for 30-60 minutes
- **Memory Management**: Unloads nodes after processing to prevent memory issues
- **Database Indexing**: Leverages database LIKE queries for JSON field searches

## Configuration

The module works out-of-the-box but can be customized:

- **Vocabulary Name**: Default is 'news_extractor', can be changed in functions
- **Cache Duration**: Modify cache times in individual functions
- **Batch Size**: Adjust batch processing size for memory optimization
- **Pagination**: Control number of results returned

## Data Structure

The module expects articles to have a `field_motivation_data` field containing JSON like:

```json
{
  "entities": [...],
  "motivations": [...],
  "metrics": [
    "Public Trust in Government",
    "Economic Growth",
    "National Security"
  ]
}
```

## Troubleshooting

### Common Issues

1. **No metrics showing**: Ensure articles have `field_motivation_data` field with proper JSON
2. **Performance issues**: Check cache settings and consider adjusting batch sizes
3. **Missing taxonomy terms**: Verify the vocabulary name matches your setup

### Debug Commands

```bash
# Test metric extraction
drush php-eval "$metrics = key_metric_management_get_all_metrics(); print_r(array_slice($metrics, 0, 5));"

# Check specific metric count
drush php-eval "echo key_metric_management_get_metric_count('Public Trust in Government');"

# Clear module cache
drush php-eval "key_metric_management_clear_cache();"
```

## Contributing

When contributing to this module:

1. Follow Drupal coding standards
2. Add appropriate error handling
3. Include cache invalidation for data changes
4. Test with large datasets
5. Document any new API functions

## License

This module follows the same license as Drupal core.

## Support

For issues and feature requests, please use the project's issue queue or contact the development team.

---

**Version**: 1.0  
**Drupal**: 9.x, 10.x  
**Maintenance**: Active