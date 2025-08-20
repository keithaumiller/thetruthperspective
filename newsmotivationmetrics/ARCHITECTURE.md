# News Motivation Metrics Module - Drupal 11 Architecture

## Overview

This module has been restructured to comply with Drupal 11 custom module development standards, featuring a service-oriented architecture with proper dependency injection.

## Architecture

### Services

- **MetricsDataService**: Handles all database queries for metrics calculation
- **TimelineService**: Manages time-based data retrieval for charts
- **ChartDataService**: Prepares data structures optimized for Chart.js
- **DashboardBuilderService**: Constructs dashboard render arrays
- **NewsMotivationTimelineChartService**: News motivation specific timeline charts ✅ **IMPLEMENTED**
- **BiasTimelineChartService**: Individual bias trend analysis charts ⚠️ **NEEDS IMPLEMENTATION**
- **CredibilityTimelineChartService**: Individual credibility trend analysis charts ⚠️ **NEEDS IMPLEMENTATION**
- **SentimentTimelineChartService**: Individual sentiment trend analysis charts ⚠️ **NEEDS IMPLEMENTATION**

### Controllers

- **MetricsController**: Main dashboard endpoints using dependency injection ✅ **IMPLEMENTED**
- **ChartDebugController**: Debug tools using services for chart development ✅ **IMPLEMENTED**
- **BiasTimelineChartController**: Individual bias chart routes ⚠️ **NEEDS IMPLEMENTATION**
- **CredibilityTimelineChartController**: Individual credibility chart routes ⚠️ **NEEDS IMPLEMENTATION**
- **SentimentTimelineChartController**: Individual sentiment chart routes ⚠️ **NEEDS IMPLEMENTATION**

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

// Get news motivation timeline chart data (IMPLEMENTED)
$newsMotivationService = \Drupal::service('newsmotivationmetrics.news_motivation_timeline_chart_service');
$newsMotivationChart = $newsMotivationService->buildTimelineChart();

// Get individual assessment chart data (NEED IMPLEMENTATION)
// These services need to be implemented following the NewsMotivationTimelineChartService pattern
/*
$biasService = \Drupal::service('newsmotivationmetrics.bias_timeline_chart_service');
$biasChart = $biasService->buildTimelineChart();

$credibilityService = \Drupal::service('newsmotivationmetrics.credibility_timeline_chart_service');
$credibilityChart = $credibilityService->buildTimelineChart();

$sentimentService = \Drupal::service('newsmotivationmetrics.sentiment_timeline_chart_service');
$sentimentChart = $sentimentService->buildTimelineChart();
*/

// Build dashboard
$dashboardBuilder = \Drupal::service('newsmotivationmetrics.dashboard_builder');
$build = $dashboardBuilder->buildPublicDashboard();
```

## Current Implementation Status

### ✅ Completed Components
- **NewsMotivationTimelineChartService**: Fully implemented and functional
- **MetricsController**: Main dashboard with dependency injection
- **ChartDebugController**: Debug tools for chart development
- **JavaScript Chart Behaviors**: All individual assessment chart behaviors implemented
- **Routes Configuration**: All individual assessment routes configured
- **Template System**: Twig templates ready for all chart types

### ⚠️ Missing Components (Causing "No Data Available" Errors)
The following service classes need implementation following the `NewsMotivationTimelineChartService` pattern:

1. **BiasTimelineChartService** 
   - Route: `/metrics/bias`
   - Error: "No bias timeline data available"
   - JavaScript: `bias-timeline-chart.js` (ready)

2. **CredibilityTimelineChartService**
   - Route: `/metrics/credibility` 
   - Error: "No credibility timeline data available"
   - JavaScript: `credibility-timeline-chart.js` (ready)

3. **SentimentTimelineChartService**
   - Route: `/metrics/sentiment`
   - Error: "No sentiment timeline data available" 
   - JavaScript: `sentiment-timeline-chart.js` (ready)

### 🚧 Next Steps for Implementation
1. **Create Service Classes**: Implement the three missing service classes
2. **Register Services**: Add service definitions to `newsmotivationmetrics.services.yml`
3. **Create Controllers**: Implement controller classes for each assessment type
4. **Test Integration**: Verify data flow from service to JavaScript
5. **Documentation Update**: Update this architecture document when complete

## Compatibility

- **Drupal Core**: ^9 || ^10 || ^11
- **Existing Code**: Fully backward compatible with deprecation warnings
- **Frontend**: No changes required to existing JavaScript/CSS

## Last Updated

Architecture reviewed and implementation status updated: January 17, 2025

**Current Focus**: Individual assessment chart service implementations needed to resolve "No timeline data available" JavaScript errors.

## Future Development

The service-based architecture provides a clean foundation for:
- Enhanced caching strategies
- API endpoint development
- Advanced analytics features
- Third-party integrations
- Additional individual assessment metric charts
