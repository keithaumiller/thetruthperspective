# News Motivation Metrics Module - Drupal 11 Architecture

## Overview

This module provides comprehensive public analytics dashboard for The Truth Perspective platform with a service-oriented architecture featuring proper dependency injection, multiple chart types, and data isolation for concurrent visualizations.

## Current Architecture (August 2025)

### Multi-Chart Service Architecture

#### Core Services
- **MetricsDataService**: Database queries for all metrics calculation and aggregation
- **ChartDataService**: Generic chart data processing and preparation for Chart.js
- **NewsMotivationTimelineChartService**: Specialized service for news motivation timeline charts
- **NewsSourceTimelineChartService**: Dedicated service for news source quality/timeline charts
- **DashboardBuilderService**: Constructs comprehensive dashboard render arrays

#### Chart-Specific Services
Each chart type has its own dedicated service for data isolation and specialized processing:

1. **NewsMotivationTimelineChartService**
   - **Settings Key**: `drupalSettings.newsmotivationmetrics`
   - **Purpose**: News motivation trend analysis over time
   - **Data Source**: Taxonomy term frequency and motivation analysis
   - **JavaScript**: `news-motivation-timeline-chart.js`

2. **NewsSourceTimelineChartService** 
   - **Settings Key**: `drupalSettings.newsmotivationmetrics_sources`
   - **Purpose**: News source quality metrics (bias, credibility, sentiment)
   - **Data Source**: Source-specific analytics from processed articles
   - **JavaScript**: `news-source-timeline-chart.js`

### Data Isolation Implementation (August 13, 2025)

#### Problem Solved
Multiple chart types on the same page were experiencing data contamination when sharing the same `drupalSettings` namespace, resulting in undefined chart labels and mixed datasets.

#### Solution Architecture
- **Unique Settings Keys**: Each chart service uses isolated `drupalSettings` keys
- **Service Separation**: Clear boundaries between chart types and their data
- **JavaScript Isolation**: Dedicated behaviors for each chart type without cross-contamination

#### Technical Implementation
```php
// NewsMotivationTimelineChartService
$build['#attached']['drupalSettings']['newsmotivationmetrics'] = $chartData;

// NewsSourceTimelineChartService  
$build['#attached']['drupalSettings']['newsmotivationmetrics_sources'] = $sourceData;
```

### Controllers

- **MetricsController**: Main dashboard endpoints using dependency injection
- **ChartDebugController**: Debug tools and testing interface for chart development
- **ChartTestController**: Production testing interface for chart validation

### Block Plugin Architecture

#### Chart Block Plugins
- **NewsMotivationTimelineBlock**: Block plugin for motivation timeline charts
- **NewsMotivationTimelineChartBlock**: Advanced motivation chart block with controls
- **NewsSourceTimelineBlock**: Block plugin for source quality timeline charts

#### Benefits of Block-Based Architecture
- **Flexible Placement**: Blocks can be placed in any region
- **Individual Configuration**: Each block has its own settings
- **Content Integration**: Seamless integration with existing page layouts
- **Cache Management**: Block-level caching for performance optimization

## Service Layer Details

### MetricsDataService
```php
interface MetricsDataServiceInterface {
  public function getArticleMetrics(): array;
  public function getTaxonomyMetrics(): array;
  public function getTimelineData(array $options = []): array;
  public function getNewsSourceTimelineData(array $options = []): array;
}
```

### Chart Service Interfaces
```php
interface NewsMotivationTimelineChartServiceInterface {
  public function buildTimelineChart(array $options = []): array;
  public function prepareChartData(array $rawData): array;
}

interface NewsSourceTimelineChartServiceInterface {
  public function buildSourceChart(array $options = []): array;
  public function getSourceQualityData(): array;
}
```

### JavaScript Architecture

#### Drupal Behaviors (Chart-Specific)
```javascript
// News motivation timeline charts
Drupal.behaviors.newsMotivationTimelineChart = {
  attach: function(context, settings) {
    const data = settings.newsmotivationmetrics;
    // Process motivation timeline data
  }
};

// News source timeline charts
Drupal.behaviors.newsSourceTimelineChart = {
  attach: function(context, settings) {
    const data = settings.newsmotivationmetrics_sources;
    // Process source quality data
  }
};
```

#### Library Organization
```yaml
# newsmotivationmetrics.libraries.yml
news-motivation-timeline:
  js:
    js/news-motivation-timeline-chart.js: {}
    js/news-motivation-timeline-orientation.js: {}
  dependencies:
    - core/drupal
    - core/jquery

news-source-timeline:
  js:
    js/news-source-timeline-chart.js: {}
  dependencies:
    - core/drupal
    - core/jquery
    - newsmotivationmetrics/charts
```

## Route Structure

### Public Routes
- **Front Page Blocks**: Analytics displayed via block placement in hero region
- **Individual Chart Pages**: Dedicated pages for specific chart types
- **API Endpoints**: Internal AJAX endpoints for chart data updates

### Administrative Routes
- **Admin Dashboard**: `/admin/reports/news-motivation-metrics`
- **Configuration**: Block-level configuration via standard Drupal block admin
- **Debug Interface**: `/newsmotivationmetrics/debug/chart`

### Legacy Route Migration
- **Deprecated**: `/metrics` route disabled in favor of block-based approach
- **Redirect Logic**: Legacy URLs redirect to front page with blocks
- **Backward Compatibility**: Existing bookmarks and links still function

## Data Processing Pipeline

### News Motivation Analysis
```
Article Content → AI Analysis → Motivation Classification → Timeline Aggregation → Chart Display
```

### News Source Quality Analysis  
```
Article Source → Quality Metrics → Bias/Credibility Scoring → Source Timeline → Chart Display
```

### Aggregation Logic
- **Time-based Grouping**: Data aggregated by configurable time periods
- **Source-based Grouping**: News source specific metrics calculation
- **Trend Analysis**: Historical data comparison and pattern recognition

## Performance Optimizations

### Database Layer
- **Optimized Queries**: Efficient aggregation queries for large datasets
- **Proper Indexing**: Database indexes on frequently queried fields
- **Query Caching**: Drupal cache integration for expensive operations

### Frontend Performance
- **Chart.js Optimization**: Efficient dataset management for large data
- **Lazy Loading**: Charts load only when visible
- **Responsive Design**: Mobile-optimized chart rendering

### Cache Strategy
- **Block-level Caching**: Individual block cache management
- **Data Caching**: Service-level caching for aggregated data
- **CDN Integration**: Static asset delivery optimization

## Security Implementation

### Public Dashboard Security
- **No Authentication Required**: Anonymous access to aggregated data
- **Data Privacy**: No personal information exposed
- **Input Sanitization**: All parameters properly sanitized
- **XSS Prevention**: Output properly escaped in templates

### Administrative Security
- **Permission-based Access**: Admin functions require proper permissions
- **CSRF Protection**: Form submissions protected
- **Audit Logging**: Administrative actions logged

## Deployment Architecture

### Production Environment
- **Server**: Ubuntu 22.04 LTS
- **PHP**: 8.3.6 with Zend OPcache
- **Database**: MySQL with optimized indexing
- **Caching**: Redis for session and cache storage

### CI/CD Integration
- **GitHub Actions**: Automated deployment pipeline
- **Cache Clearing**: Automatic cache clearing on deployment
- **Database Updates**: Schema updates via `drush updatedb`
- **Asset Processing**: JavaScript/CSS minification and optimization

## Error Handling & Monitoring

### Comprehensive Logging
```php
\Drupal::logger('newsmotivationmetrics')->info('Chart data processed', [
  '@chart_type' => $chartType,
  '@data_points' => count($dataPoints),
  '@processing_time' => $processingTime,
]);
```

### Error Recovery
- **Graceful Degradation**: Charts fail gracefully with error messages
- **Fallback Data**: Default datasets when processing fails
- **User Feedback**: Clear error messages in chart interfaces

### Debug Tools
- **Chart Debug Console**: Live debugging interface at `/newsmotivationmetrics/debug/chart`
- **Real-time Testing**: Interactive chart generation and validation
- **Environment Analysis**: Server and configuration verification

## Future Enhancements

### Planned Features
- **Advanced Analytics**: Machine learning integration for trend prediction
- **Real-time Processing**: WebSocket integration for live data updates
- **Export Capabilities**: PDF/CSV export for chart data
- **API Endpoints**: RESTful API for external integrations

### Scalability Considerations
- **Microservices Architecture**: Potential extraction to dedicated services
- **Queue Processing**: Background processing for heavy analytics operations
- **CDN Integration**: Static asset delivery optimization
- **Database Sharding**: Horizontal scaling for large datasets

## Development Guidelines

### Adding New Chart Types
1. **Create Dedicated Service**: Implement chart-specific service with unique interface
2. **Unique Settings Key**: Use isolated `drupalSettings` namespace
3. **Dedicated JavaScript**: Create specific behavior and library
4. **Block Plugin**: Implement block plugin for flexible placement
5. **Template System**: Create specialized templates for chart rendering

### Code Standards
- **Drupal 11 Standards**: Follow current Drupal coding standards
- **Service Interfaces**: All services must implement proper interfaces
- **Dependency Injection**: Use container-based dependency injection
- **Comprehensive Testing**: Unit and integration tests for all services

### Performance Guidelines
- **Database Efficiency**: Optimize all database queries
- **Memory Management**: Monitor memory usage during processing
- **Cache Integration**: Implement appropriate caching strategies
- **Front-end Optimization**: Minimize JavaScript/CSS for performance

## Compatibility Matrix

| Component | Drupal 9 | Drupal 10 | Drupal 11 |
|-----------|----------|-----------|-----------|
| Core Services | ✅ | ✅ | ✅ |
| Block Plugins | ✅ | ✅ | ✅ |
| Chart.js Integration | ✅ | ✅ | ✅ |
| Data Isolation | ✅ | ✅ | ✅ |

## Migration Notes

### From Legacy Architecture
- **Service Migration**: Legacy functions deprecated with clear upgrade path
- **Data Compatibility**: Existing data fully compatible with new architecture
- **Frontend Migration**: No changes required to existing JavaScript/CSS
- **Configuration Migration**: Block configuration migrates automatically

### Upgrade Procedures
1. **Update Module**: Deploy updated module code
2. **Run Updates**: Execute `drush updatedb` for schema updates
3. **Clear Caches**: Run `drush cr` to refresh all caches
4. **Verify Blocks**: Check block placement and configuration
5. **Test Charts**: Validate all chart types display correctly

---

**Last Updated**: August 13, 2025  
**Version**: 1.1.0  
**Drupal Compatibility**: 9.x, 10.x, 11.x  
**Status**: Production Ready with Data Isolation
