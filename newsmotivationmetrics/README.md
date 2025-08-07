# News Motivation Metrics Module

A comprehensive Drupal 11 module for The Truth Perspective platform that provides public analytics dashboards and advanced Chart.js debugging tools for news article motivation analysis.

## Overview

The News Motivation Metrics module serves as the public-facing analytics dashboard for The Truth Perspective, displaying aggregated statistics and insights from AI-powered news analysis. It includes professional Chart.js debugging tools for development and testing of data visualizations.

## Features

### Public Analytics Dashboard
- **Article Statistics**: Total articles analyzed, processing timeline
- **Motivation Analysis**: Distribution of political, economic, and social motivations
- **Entity Recognition**: People, organizations, locations, and concepts
- **Temporal Analysis**: Trends over time with interactive charts
- **Geographic Insights**: Location-based article distribution
- **Mobile-Responsive Design**: Professional presentation suitable for public use

### Chart.js Debug Console
- **Environment Detection**: Comprehensive Chart.js v4.4.0 analysis
- **Interactive Testing**: Multiple chart type generation and testing
- **Real-Time Debugging**: Live console output with timestamps
- **Performance Monitoring**: Chart rendering and data processing analysis
- **Template Integration**: Seamless Drupal theme integration

### Technical Capabilities
- **Large Dataset Optimization**: Efficient handling of 1000+ articles
- **Caching Strategy**: Performance optimization for public dashboards
- **API Integration**: Real-time data from news_extractor module
- **Professional UI**: The Truth Perspective branded interface
- **Mobile Compatibility**: Responsive design across all devices

## Installation

### Prerequisites
- Drupal 11.x
- PHP 8.3+
- news_extractor module (provides core data)
- Modern web browser with JavaScript support

### Module Installation
```bash
# Enable the module
drush en newsmotivationmetrics

# Clear caches
drush cr

# Import configuration
drush cim
```

## Configuration

### Dashboard Settings
Navigate to `/admin/config/newsmotivationmetrics` to configure:
- **Display Options**: Choose which metrics to show publicly
- **Cache Settings**: Configure caching duration for performance
- **Chart Preferences**: Default chart types and styling options
- **Data Sources**: Select which article data to include

### Permissions
- **Access news motivation metrics**: Public access to analytics dashboard
- **Administer news motivation metrics**: Administrative configuration access
- **Access chart debug console**: Development and debugging access

## Usage

### Public Analytics Dashboard
Visit `/newsmotivationmetrics` to view:
- Overview statistics and key metrics
- Interactive charts showing motivation trends
- Entity analysis with geographic distribution
- Timeline analysis of article processing

### Chart Debug Console
Access `/newsmotivationmetrics/debug/chart` for:
- Chart.js environment analysis and testing
- Interactive chart generation with sample data
- Real-time debugging output and performance monitoring
- Template integration testing

### Administrative Interface
Manage settings at `/admin/config/newsmotivationmetrics`:
- Configure dashboard display options
- Set caching and performance parameters
- Review processing statistics and system health

## Chart.js Integration

### Supported Chart Types
- **Bar Charts**: Article counts, motivation distribution
- **Line Charts**: Temporal trends and timeline analysis  
- **Doughnut Charts**: Percentage breakdowns and category distribution
- **Timeline Charts**: Date-based article processing with time scales

### Debug Console Features
- **Environment Detection**: Chart.js version, controllers, scales analysis
- **Interactive Testing**: Generate test charts with various data types
- **Real-Time Logging**: Comprehensive debug output with timestamps
- **Canvas Management**: Chart creation, destruction, and cleanup
- **Template Integration**: Drupal behavior attachment and theming

### Performance Optimization
- **Efficient Rendering**: Optimized for large datasets (1000+ data points)
- **Memory Management**: Proper chart cleanup and resource management
- **Responsive Design**: Charts adapt to various screen sizes
- **Caching Strategy**: Chart data cached for improved performance

## API Endpoints

### Public Data Access
- `GET /newsmotivationmetrics/api/stats` - Overall statistics
- `GET /newsmotivationmetrics/api/motivations` - Motivation analysis data
- `GET /newsmotivationmetrics/api/entities` - Entity recognition results
- `GET /newsmotivationmetrics/api/timeline` - Temporal analysis data

### Administrative Endpoints
- `GET /admin/newsmotivationmetrics/health` - System health check
- `POST /admin/newsmotivationmetrics/refresh` - Force cache refresh
- `GET /admin/newsmotivationmetrics/debug` - Debug information

## Database Schema

### Chart Debug Tables
- **chart_debug_sessions**: Debug session tracking
- **chart_performance_logs**: Chart rendering performance data

### Analytics Cache Tables  
- **motivation_metrics_cache**: Cached aggregated statistics
- **entity_metrics_cache**: Cached entity analysis results

## Templates

### Public Templates
- `templates/dashboard.html.twig` - Main analytics dashboard
- `templates/chart-display.html.twig` - Individual chart rendering
- `templates/motivation-breakdown.html.twig` - Motivation analysis display

### Debug Templates
- `templates/chart-debug.html.twig` - Complete Chart.js debug console
- Professional interface with The Truth Perspective branding
- Real-time debug output and interactive testing controls
- Mobile-responsive design with comprehensive chart testing

## JavaScript Architecture

### Chart Management
- `js/chart-manager.js` - Core chart creation and management
- `js/chart-debug.js` - Debug console functionality with v1.3.4 features
- `js/data-processor.js` - Data formatting for chart consumption

### Debug Console Features
- **Environment Detection**: Comprehensive Chart.js analysis
- **Interactive Controls**: Test chart generation and management
- **Real-Time Logging**: Debug output with professional formatting
- **Template Integration**: Drupal behaviors and theme compatibility

## Performance Considerations

### Caching Strategy
- **Page-level caching**: Public dashboards cached for 1 hour
- **Data-level caching**: API responses cached for 30 minutes
- **Chart data caching**: Pre-processed chart data cached for performance

### Database Optimization
- **Indexed queries**: Efficient data retrieval for large datasets
- **Aggregation tables**: Pre-calculated statistics for faster display
- **Query optimization**: Minimal database calls for dashboard rendering

### Frontend Performance
- **Lazy loading**: Charts load progressively as needed
- **Responsive images**: Optimized for various screen sizes
- **CDN integration**: Chart.js v4.4.0 loaded from reliable CDN
- **Error handling**: Graceful degradation for offline scenarios

## Troubleshooting

### Common Issues

#### Charts Not Rendering
```bash
# Check Chart.js loading
curl -I https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js

# Verify debug console access
curl https://thetruthperspective.org/newsmotivationmetrics/debug/chart

# Clear caches
drush cr
```

#### Debug Console Issues
- **Environment Detection Failure**: Check JavaScript console for CDN loading errors
- **Template Integration Issues**: Verify Drupal behaviors are attaching properly
- **Chart Generation Problems**: Use debug console real-time logging for diagnosis

#### Performance Issues
```bash
# Check cache status
drush cache:get newsmotivationmetrics

# Monitor database performance
drush sql:query "SHOW PROCESSLIST;"

# Clear all caches
drush cr
```

### Debug Console Access
The Chart.js debug console provides comprehensive analysis:
- Real-time environment detection and status
- Interactive chart testing with multiple data types
- Professional debugging interface with detailed logging
- Template integration verification and testing

## Development

### Adding New Chart Types
1. Create chart configuration in `js/chart-manager.js`
2. Add test button to debug console template
3. Implement chart generation function
4. Update debug console with new chart testing

### Extending Analytics
1. Add new metric calculation in module file
2. Create corresponding template for display
3. Update caching strategy for new data
4. Add API endpoint for data access

### Debug Console Enhancement
1. Extend environment detection capabilities
2. Add new interactive testing features
3. Enhance real-time logging and monitoring
4. Improve template integration and theming

## Security

### Public Access
- No authentication required for public dashboards
- Aggregate data only, no individual article access
- Rate limiting on API endpoints
- Proper input sanitization for all parameters

### Administrative Access
- Proper permission checks for admin functions
- Secure configuration management
- Audit logging for administrative changes
- Protected debug console access

## Integration

### news_extractor Module
- Consumes processed article data and AI analysis
- Accesses motivation analysis and entity recognition
- Uses shared field definitions and taxonomy
- Depends on article processing pipeline completion

### Drupal Core Integration
- Follows Drupal 11 coding standards and best practices
- Uses modern dependency injection patterns
- Implements proper caching and performance optimization
- Compatible with standard Drupal theming and configuration

## Maintenance

### Regular Tasks
```bash
# Update cached statistics (daily)
drush newsmotivationmetrics:refresh-cache

# Performance monitoring (weekly)
drush newsmotivationmetrics:performance-report

# Debug console health check
curl https://thetruthperspective.org/newsmotivationmetrics/debug/chart
```

### Monitoring
- **Performance metrics**: Chart rendering times and database query performance
- **Error tracking**: JavaScript errors and Chart.js integration issues
- **Cache efficiency**: Hit rates and refresh frequency analysis
- **User engagement**: Public dashboard access patterns and usage

## Support

### Documentation
- Comprehensive inline code documentation
- API endpoint documentation with examples
- Chart.js integration guide with debug console usage
- Performance optimization recommendations

### Debugging Resources
- Chart.js Debug Console at `/newsmotivationmetrics/debug/chart`
- Real-time environment analysis and testing
- Interactive chart generation and validation
- Professional debugging interface for development

---

**The Truth Perspective Platform**  
AI-Powered News Analysis System  
Version: Drupal 11 Production Release