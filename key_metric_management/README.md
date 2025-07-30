# Key Metric Management Module

A Drupal 11 module for analyzing and managing key performance metrics identified in news articles through AI analysis.

## Overview

This module extracts and analyzes key performance metrics from news articles that have been processed by the News Extractor module. It provides administrative interfaces to view metric statistics, trends, and relationships between metrics and taxonomy terms using a modern service-based architecture.

## Features

- **Metric Dashboard**: View all identified key metrics with article counts and statistics
- **Individual Metric Pages**: Detailed analysis pages for each metric
- **Taxonomy Integration**: Links key metrics to their corresponding taxonomy terms
- **Performance Optimized**: Service-based architecture with comprehensive caching
- **Professional Admin Interface**: Clean, responsive administrative pages with CSS styling
- **Real-time Statistics**: Live counts and trend analysis with visual displays

## Requirements

- **Drupal 11.x** (Updated for latest version)
- **PHP 8.1+** (Drupal 11 requirement)
- **News Extractor module** (dependency)
- Articles with `field_motivation_data` JSON field containing metrics

## Installation

1. Download and place the module in `/modules/custom/key_metric_management/`
2. Enable the module: `drush en key_metric_management -y`
3. Clear cache: `drush cr`
4. Navigate to `/admin/config/content/key-metrics` to access the dashboard

## Architecture

### Service-Based Design
The module uses Drupal's service container with dependency injection:

- **MetricAnalyzer**: Core metric analysis and data extraction
- **TaxonomyAnalyzer**: Taxonomy integration and relationship mapping  
- **CacheManager**: Centralized cache management and invalidation
- **KeyMetricController**: Route handling with injected services

### Clean Code Flow
```
HTTP Request → Route → Controller (DI) → Service → Cache/Database → Template → CSS
```

## Usage

### Admin Dashboard
Access the main dashboard at `/admin/config/content/key-metrics` to view:
- Professional statistics grid with visual cards
- Total unique metrics count
- Articles with metrics count  
- Most common metric identification
- Complete sortable table of all metrics with action buttons

### Individual Metric Analysis
Each metric has its own page at `/admin/config/content/key-metrics/{metric-name}` showing:
- Large visual count display of articles referencing the metric
- Clean, focused interface with navigation
- Professional styling and responsive design

### Taxonomy Integration
Visit `/admin/config/content/key-metrics-taxonomy` to see:
- All taxonomy terms that correspond to key metrics
- Ranked table by frequency of occurrence
- Links to both taxonomy pages and metric analysis pages
- Cross-reference between taxonomy system and metric analysis

## API - Service Usage

### Using Services (Recommended)
```php
// Get the metric analyzer service
$metricAnalyzer = \Drupal::service('key_metric_management.metric_analyzer');

// Get all metrics with counts
$metrics = $metricAnalyzer->getAllMetrics();

// Get count for specific metric
$count = $metricAnalyzer->getMetricCount('Public Trust in Government');

// Get articles for specific metric (with pagination)
$articles = $metricAnalyzer->getArticlesByMetric('Economic Growth', 50, 0);

// Get summary statistics
$stats = $metricAnalyzer->getMetricStats();

// Taxonomy integration
$taxonomyAnalyzer = \Drupal::service('key_metric_management.taxonomy_analyzer');
$terms = $taxonomyAnalyzer->getMetricTaxonomyTerms();

// Cache management
$cacheManager = \Drupal::service('key_metric_management.cache_manager');
$cacheManager->invalidateMetricCaches();
```

### Dependency Injection in Custom Code
```php
use Drupal\key_metric_management\Service\MetricAnalyzer;

class YourCustomClass {
  
  protected MetricAnalyzer $metricAnalyzer;
  
  public function __construct(MetricAnalyzer $metric_analyzer) {
    $this->metricAnalyzer = $metric_analyzer;
  }
  
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('key_metric_management.metric_analyzer')
    );
  }
}
```

## Performance Features

- **Service Architecture**: Dependency injection eliminates redundant object creation
- **Query Optimization**: Uses targeted database queries instead of loading all nodes
- **Batch Processing**: Processes large datasets in configurable chunks (default: 100)
- **Multi-Level Caching**: Service-level caching with automatic invalidation
- **Memory Management**: Proper object lifecycle management prevents memory leaks
- **Database Indexing**: Leverages database LIKE queries for JSON field searches

## Configuration

### Cache Settings
```php
// Modify cache times in service classes
const CACHE_TTL = 3600; // 1 hour default
const BATCH_SIZE = 100; // Processing batch size
```

### Vocabulary Integration
```php
// Default vocabulary (can be customized)
$terms = $taxonomyAnalyzer->getMetricTaxonomyTerms('news_extractor');
```

## Data Structure

The module expects articles to have a `field_motivation_data` field containing JSON like:

```json
{
  "entities": [
    "Government Agency",
    "Public Institution"
  ],
  "motivations": [
    "Transparency",
    "Accountability"
  ],
  "metrics": [
    "Public Trust in Government",
    "Economic Growth",
    "National Security",
    "Healthcare Access"
  ]
}
```

## Theming and Styling

### CSS Classes Available
```css
.metric-stats-grid      /* Statistics display grid */
.stat-card             /* Individual statistic cards */
.stat-number           /* Large metric numbers */
.metric-count-display  /* Individual metric page display */
.count-number          /* Large count numbers */
.button--primary       /* Action buttons */
```

### Template Files
- `key-metric-stats.html.twig` - Statistics dashboard display
- `key-metric-detail.html.twig` - Individual metric page display

## Troubleshooting

### Common Issues

1. **No metrics showing**: 
   - Ensure articles have `field_motivation_data` field with proper JSON
   - Check if News Extractor module is enabled and processing articles

2. **Performance issues**: 
   - Review cache settings in service classes
   - Adjust `BATCH_SIZE` constant for memory optimization
   - Check database query performance

3. **Service injection errors**:
   - Clear cache: `drush cr`
   - Verify service definitions in `key_metric_management.services.yml`

4. **Missing CSS styling**:
   - Ensure library is attached: `key_metric_management/global-styling`
   - Check CSS file location: `css/key-metric-management.css`

### Debug Commands

```bash
# Test service availability
drush php-eval "
\$service = \Drupal::service('key_metric_management.metric_analyzer');
echo 'Service class: ' . get_class(\$service);
"

# Test metric extraction
drush php-eval "
\$analyzer = \Drupal::service('key_metric_management.metric_analyzer');
\$metrics = \$analyzer->getAllMetrics(false);
print_r(array_slice(\$metrics, 0, 5, true));
"

# Check specific metric count
drush php-eval "
\$analyzer = \Drupal::service('key_metric_management.metric_analyzer');
echo \$analyzer->getMetricCount('Public Trust in Government');
"

# Clear module cache
drush php-eval "
\$cache = \Drupal::service('key_metric_management.cache_manager');
\$cache->invalidateMetricCaches();
echo 'Cache cleared';
"

# Test routes
drush php-eval "
\$url = \Drupal\Core\Url::fromRoute('key_metric_management.dashboard');
echo 'Dashboard URL: ' . \$url->toString();
"
```

## Development

### Contributing Guidelines

1. **Follow Drupal 11 coding standards**
2. **Use dependency injection** for all service access
3. **Add comprehensive error handling** with logging
4. **Include cache invalidation** for data changes
5. **Test with large datasets** to ensure performance
6. **Document any new API methods** with proper docblocks
7. **Write unit tests** for service methods

### File Structure
```
key_metric_management/
├── key_metric_management.info.yml      # Module definition (Drupal 11)
├── key_metric_management.module         # Hooks and theme definitions
├── key_metric_management.services.yml  # Service container configuration
├── key_metric_management.routing.yml   # Route definitions
├── key_metric_management.libraries.yml # CSS library configuration
├── README.md                           # This documentation
├── src/
│   ├── Controller/
│   │   └── KeyMetricController.php     # Main controller with DI
│   └── Service/
│       ├── MetricAnalyzer.php          # Core analysis service
│       ├── TaxonomyAnalyzer.php        # Taxonomy integration
│       └── CacheManager.php            # Cache management
├── templates/
│   ├── key-metric-stats.html.twig     # Statistics template
│   └── key-metric-detail.html.twig    # Detail page template
└── css/
    └── key-metric-management.css       # Professional styling
```

## Routes

- **Dashboard**: `/admin/config/content/key-metrics`
- **Individual Metric**: `/admin/config/content/key-metrics/{metric}`
- **Taxonomy Integration**: `/admin/config/content/key-metrics-taxonomy`

## Public Access

### Public Routes
The module now provides public access to key metrics:
- **Dashboard**: `/key-metrics`
- **Individual Metrics**: `/key-metrics/{metric-name}`
- **Taxonomy Integration**: `/key-metrics/taxonomy`

### Block Widget
A configurable block is available that displays:
- Total unique metrics count
- Total articles with metrics
- Top 5 metrics with counts
- Link to full dashboard

**To add the block:**
1. Go to `/admin/structure/block`
2. Place "Key Metric Statistics" block in desired region
3. Configure display options as needed

### Admin Access (Protected)
Administrative routes remain protected:
- **Admin Dashboard**: `/admin/config/content/key-metrics`
- **Admin Individual Metrics**: `/admin/config/content/key-metrics/{metric}`
- **Admin Taxonomy**: `/admin/config/content/key-metrics-taxonomy`

## License

This module follows the same license as Drupal core (GPL-2.0+).

## Support

For issues and feature requests, please use the project's issue queue or contact the development team.

---

**Version**: 2.0  
**Drupal**: 11.x  
**PHP**: 8.1+  
**Architecture**: Service-based with Dependency Injection  
**Maintenance**: Active  
**Last Updated**: 2025