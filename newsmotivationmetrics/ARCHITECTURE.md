# News Motivation Metrics Module - Drupal 11 Architecture

## Overview

This module has been restructured to comply with Drupal 11 custom module development standards, featuring a service-oriented architecture with proper dependency injection.

## Architecture

### Services

- **MetricsDataService**: Handles all database queries for metrics calculation
- **TimelineService**: Manages time-based data retrieval for charts
- **ChartDataService**: Prepares data structures optimized for Chart.js
- **DashboardBuilderService**: Constructs dashboard render arrays

### Controllers

- **MetricsController**: Main dashboard endpoints using dependency injection
- **ChartDebugController**: Debug tools using services for chart development

### Key Improvements

1. **Dependency Injection**: All controllers use proper DI containers
2. **Service Interfaces**: Clear contracts for all service implementations
3. **Error Handling**: Comprehensive error handling with logging
4. **Backward Compatibility**: Legacy functions maintained with deprecation warnings
5. **Performance**: Optimized database queries and caching strategies

### Migration Path

Legacy module functions are deprecated but still functional:
- `newsmotivationmetrics_get_*()` functions trigger deprecation warnings
- Direct service usage recommended: `\Drupal::service('newsmotivationmetrics.metrics_data_service')`

### Service Usage Examples

```php
// Get metrics data
$metricsService = \Drupal::service('newsmotivationmetrics.metrics_data_service');
$articleMetrics = $metricsService->getArticleMetrics();

// Get chart data
$chartService = \Drupal::service('newsmotivationmetrics.chart_data_service');
$chartData = $chartService->getTimelineChartData(['limit' => 10]);

// Build dashboard
$dashboardBuilder = \Drupal::service('newsmotivationmetrics.dashboard_builder');
$build = $dashboardBuilder->buildPublicDashboard();
```

## Compatibility

- **Drupal Core**: ^9 || ^10 || ^11
- **Existing Code**: Fully backward compatible with deprecation warnings
- **Frontend**: No changes required to existing JavaScript/CSS

## Future Development

The service-based architecture provides a clean foundation for:
- Enhanced caching strategies
- API endpoint development
- Advanced analytics features
- Third-party integrations
