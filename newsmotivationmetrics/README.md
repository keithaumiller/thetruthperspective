# News Motivation Metrics Module

## Overview
The News Motivation Metrics module provides a comprehensive public analytics dashboard for The Truth Perspective platform. It displays aggregated data from AI-powered news analysis, showing trends in media motivations, entity recognition, and narrative patterns over time.

## Features

### Public Analytics Dashboard (Block-Based)
- **Block-based architecture** placed in hero region on front page
- **Individual configurable blocks** for each metrics section
- **Full-width responsive design** using Olivero theme's hero region
- **Interactive timeline charts** showing taxonomy term frequency over time
- **Multi-select controls** for filtering and comparing different motivations/entities
- **Mobile-optimized interface** with professional presentation
- **Real-time data visualization** powered by Chart.js
- **Transparent methodology explanations** for public credibility

### Dashboard Components (Blocks)
- **Content Analysis Overview**: Article processing and AI analysis statistics
- **Temporal Processing Analytics**: Time-based processing metrics and trends
- **Sentiment Distribution Analysis**: Positive/negative/neutral sentiment breakdown
- **Entity Recognition Metrics**: People, organizations, and locations tracking
- **News Motivation Timeline Chart**: Interactive visualization of news motivation trends over time
- **Recent Activity**: 7-day and 30-day processing activity metrics
- **Analysis Quality**: AI response depth and classification quality metrics
- **About Section**: Mission, methodology, and transparency information

### Data Visualization
- News motivation timeline charts showing motivation trend analysis over time
- Comparative analysis between different news motivation categories  
- Entity recognition patterns (people, organizations, locations) in news coverage
- Article volume and processing statistics
- Temporal narrative analysis for news motivation tracking

### Professional Public Interface
- SEO-optimized structure for discoverability
- Fast loading performance (<2 seconds target)
- Accessible design following WCAG guidelines
- Print-friendly chart export capabilities
- Professional gradient styling suitable for media/academic use

## Recent Updates (August 13, 2025)

### Data Isolation Fix for Multiple Chart Types
Fixed critical issue with undefined labels in chart displays when multiple chart types were present on the same page:

#### Root Cause Resolution
- **Problem**: Multiple chart services (NewsMotivationTimelineChart and NewsSourceTimelineChart) were using the same `drupalSettings['newsmotivationmetrics']` key, causing data contamination
- **Solution**: Implemented unique settings keys for data isolation:
  - `newsmotivationmetrics` → News motivation timeline data
  - `newsmotivationmetrics_sources` → News source timeline data

#### Technical Implementation
- **NewsSourceTimelineChartService.php**: Modified to use isolated settings key
- **news-source-timeline-chart.js**: Updated all references to use new settings namespace
- **Data Integrity**: Prevents motivation timeline data from polluting source chart data

#### Benefits
- **Clean Chart Displays**: Eliminates undefined labels and data contamination
- **Scalable Architecture**: Supports multiple chart types on same page without conflicts
- **Future-Proof Design**: Prepared for additional chart type implementations

### Timeline Component Renaming
All timeline chart components have been renamed to be specific about their news motivation tracking purpose:

#### Component Name Changes
- **JavaScript Files**:
  - `taxonomy-timeline-chart.js` → `news-motivation-timeline-chart.js`
  - `timeline-orientation.js` → `news-motivation-timeline-orientation.js`

- **PHP Service Classes**:
  - `TimelineChartService` → `NewsMotivationTimelineChartService`
  - `TimelineChartServiceInterface` → `NewsMotivationTimelineChartServiceInterface`

- **Block Plugin Classes**:
  - `TaxonomyTimelineBlock` → `NewsMotivationTimelineBlock` 
  - `TimelineChartBlock` → `NewsMotivationTimelineChartBlock`

- **Service Registry**:
  - `newsmotivationmetrics.timeline_chart_service` → `newsmotivationmetrics.news_motivation_timeline_chart_service`

#### Identifier Updates
- **Canvas IDs**: `taxonomy-timeline-chart` → `news-motivation-timeline-chart`
- **CSS Classes**: `taxonomy-timeline-section` → `news-motivation-timeline-section`
- **JavaScript Behaviors**: `taxonomyTimelineChart` → `newsMotivationTimelineChart`
- **Library Names**: New `news-motivation-timeline` library for dedicated functionality

#### Purpose and Benefits
This renaming provides clear semantic separation for when additional timeline chart types are introduced in the future. The components now explicitly identify themselves as designed for **news motivation tracking analysis**, making the codebase more maintainable and self-documenting.

## Architecture

### Service Layer (Updated)
- **NewsMotivationTimelineChartService**: Centralized service for building news motivation timeline chart components
- **ChartDataService**: Aggregates and processes timeline data for news motivation visualization
- **DashboardBuilderService**: Coordinates multiple chart components and dashboard sections
- **MetricsDataService**: Core data processing and aggregation for analytics
- **TimelineService**: Backend data processing specifically for news motivation timeline analysis

### Component Structure
- **Block Plugins**: `NewsMotivationTimelineBlock`, `NewsMotivationTimelineChartBlock` 
- **Service Interfaces**: `NewsMotivationTimelineChartServiceInterface` for dependency injection
- **JavaScript Libraries**: `news-motivation-timeline` library with dedicated chart behaviors
- **Template System**: News motivation specific templates for proper rendering

### Route Structure
- **Legacy Route**: `/metrics` - DISABLED (redirects to front page)
- **Block-Based Dashboard**: Displayed on front page (`<front>`) via hero region blocks
- **Admin Dashboard**: `/admin/reports/news-motivation-metrics` - Administrative interface
- **API Endpoints**: Internal data fetching for chart updates
- **Tag Details**: `/metrics/tag/{tid}` - Individual taxonomy term analysis

### Data Sources
- Processed articles from `news_extractor` module
- AI analysis results stored in custom fields
- Taxonomy term assignments and frequency data
- Entity recognition data from Claude AI integration

### Performance Optimizations
- Efficient database queries with proper indexing
- Caching layer for aggregated statistics
- Batch processing for large dataset operations
- Optimized for concurrent user access

## Installation

### Prerequisites
- Drupal 11.x
- PHP 8.1+
- `news_extractor` module (provides source data)
- Chart.js library integration

### Installation Steps
1. Place module in `/modules/custom/newsmotivationmetrics`
2. Enable the module: `drush en newsmotivationmetrics`
3. Clear caches: `drush cr`
4. **Blocks are automatically placed** in hero region on front page during installation
5. Configure individual block settings via Block Layout if needed (`/admin/structure/block`)

### Required Dependencies
- Core Drupal modules: Views, Field, Entity API
- Custom modules: news_extractor (for data source)
- JavaScript libraries: Chart.js (loaded via CDN)

## Configuration

### Permissions
- **Access public metrics dashboard**: Anonymous users (default)
- **Administer metrics settings**: Admin users only
- **Export analytics data**: Configurable per role

### Content Types Integration
- Works with any content type having motivation/entity analysis fields
- Automatically detects relevant taxonomy vocabularies
- Processes custom fields: `field_ai_motivation_analysis`, `field_ai_entities`

### Taxonomy Integration
- **Motivations vocabulary**: Political, economic, social, cultural motivations
- **Entities vocabulary**: Auto-generated from AI entity recognition
- **Topics vocabulary**: Article subject categorization
- Dynamic term creation from AI analysis results

## Usage

### Public Dashboard Access
Navigate to the **front page** to view the public analytics dashboard displayed in the hero region. No authentication required. The previous `/metrics` URL has been disabled in favor of the block-based approach.

### Block Management
- **Individual Configuration**: Each metrics section is now a separate block
- **Block Layout Admin**: `/admin/structure/block` - Configure individual blocks
- **Visibility Settings**: Blocks are configured to show only on front page
- **Ordering**: Blocks maintain proper weight-based ordering
- **Admin Interface**: Individual settings for each dashboard component

### Chart Interactions
- **Multi-select controls**: Choose multiple news motivation terms to compare trends
- **Time filtering**: Select date ranges for focused news motivation analysis
- **Export options**: Print-friendly views for news motivation trend reports
- **Responsive design**: Optimized for mobile and desktop viewing of motivation charts

### Data Interpretation
- **News Motivation Frequency trends**: Shows how often specific news motivations appear over time
- **Comparative analysis**: Identify correlations between different news motivation types
- **Entity tracking**: Monitor coverage patterns of specific people/organizations in news
- **Narrative evolution**: Track how news motivation themes develop over time periods

## Technical Implementation

### Hero Region Integration
The module uses Drupal's `hook_preprocess_page()` to move content from the standard content region to the hero region, achieving full-width display matching the site's social media links layout.

### CSS Architecture
- **Hero region styling**: Full-width responsive layout
- **Chart containers**: Professional presentation with subtle shadows
- **Control interfaces**: Intuitive multi-select and filtering options
- **Mobile optimization**: Responsive breakpoints for all device sizes
- **Print styles**: Clean output for report generation

### Database Queries
- Optimized aggregation queries for large datasets (1000+ articles)
- Proper indexing on date and taxonomy reference fields
- Efficient JOIN operations across content and taxonomy tables
- Caching layer for frequently accessed aggregated data

### JavaScript Integration
- **News Motivation Timeline Charts**: Chart.js integration for dynamic news motivation data visualization
- **AJAX-powered updates**: Chart updates without page reloads for news motivation data
- **Responsive chart resizing**: Mobile-optimized news motivation timeline displays
- **Loading states and error handling**: Robust API calls for news motivation data
- **Dedicated Libraries**: `news-motivation-timeline` library with specific behaviors:
  - `newsMotivationTimelineChart`: Main chart initialization and interaction
  - `newsMotivationTimelineOrientation`: Mobile orientation handling for charts

## API Structure

### Internal Endpoints
- **News motivation chart data API**: Provides aggregated timeline data for news motivation trends
- **Motivation term frequency API**: Returns usage statistics for news motivation taxonomy terms
- **Export API**: Generates news motivation data for external analysis tools

### Data Format
```json
{
  "timeline_data": {
    "labels": ["2024-01", "2024-02", "..."],
    "datasets": [
      {
        "label": "Political News Motivation",
        "data": [15, 23, 18, "..."],
        "borderColor": "#667eea"
      },
      {
        "label": "Economic News Motivation", 
        "data": [12, 19, 25, "..."],
        "borderColor": "#f093fb"
      }
    ]
  },
  "metadata": {
    "total_articles": 1250,
    "date_range": "2024-01-01 to 2024-12-31",
    "last_updated": "2024-12-15T10:30:00Z",
    "chart_type": "news_motivation_timeline"
  }
}
```

## Customization

### Styling Customization
- Override CSS classes in your theme
- Modify color schemes via CSS custom properties
- Adjust responsive breakpoints as needed
- Customize chart styling through Chart.js options

### Data Source Expansion
- Add support for additional content types
- Integrate custom taxonomy vocabularies
- Extend entity recognition categories
- Implement custom aggregation algorithms

### Chart Configuration
- **News motivation chart types**: Line, bar, area charts for motivation trend visualization
- **Time granularity**: Daily, weekly, monthly views for news motivation analysis
- **Custom data filtering**: Filter by news motivation categories, entities, or date ranges
- **Multiple chart support**: Prepare architecture for additional timeline visualizations beyond news motivation tracking

## Troubleshooting

### Common Issues

#### Dashboard Not Loading
- Verify `news_extractor` module is enabled and has processed articles
- Check that news motivation taxonomy vocabularies exist and have terms
- Ensure JavaScript is enabled in browser and `news-motivation-timeline` library loads
- Clear Drupal caches: `drush cr`
- Verify news motivation timeline chart service is properly registered

#### Performance Issues
- Verify database indexing on date and news motivation taxonomy fields
- Check for large dataset queries without proper limits in news motivation processing
- Monitor memory usage during news motivation chart rendering
- Consider enabling additional caching layers for motivation data aggregation

#### Styling Problems
- Clear browser cache and check for CSS conflicts with news motivation timeline styles
- Verify Chart.js library is loading correctly for news motivation charts
- Check responsive design across different devices for motivation timeline display
- Validate CSS syntax and media query breakpoints for news motivation timeline sections

### Debug Commands
```bash
# Check module status
drush pm:list | grep newsmotivationmetrics

# Clear all caches
drush cr

# Check for errors
drush watchdog:show --filter=newsmotivationmetrics

# Verify database structure
drush sql:query "DESCRIBE taxonomy_term_field_data"
```

## Development

### Code Organization
- **Module file**: Core hooks and preprocessing logic for news motivation analytics
- **Service Layer**: NewsMotivationTimelineChartService and related interfaces
- **Controller**: Route handling and news motivation data processing
- **CSS**: Comprehensive styling for news motivation timeline components
- **JavaScript**: News motivation chart initialization and interaction handling

### File Structure
```
newsmotivationmetrics/
├── src/
│   ├── Service/
│   │   ├── NewsMotivationTimelineChartService.php
│   │   └── Interface/
│   │       └── NewsMotivationTimelineChartServiceInterface.php
│   └── Plugin/Block/
│       ├── NewsMotivationTimelineBlock.php
│       └── NewsMotivationTimelineChartBlock.php
├── js/
│   ├── news-motivation-timeline-chart.js
│   ├── news-motivation-timeline-orientation.js
│   └── chart-behavior.js
├── css/
│   ├── chart-styles.css (with news-motivation-timeline-section classes)
│   └── fullwidth-override.css
├── templates/
│   ├── block--news-motivation-timeline-chart.html.twig
│   └── block--olivero-newsmotivationtimelinechart.html.twig
└── newsmotivationmetrics.libraries.yml (with news-motivation-timeline library)
```

### Testing
- Test with datasets of various sizes (100, 1000, 10000+ articles)
- Verify responsive design across devices
- Check accessibility compliance (WCAG 2.1)
- Performance testing with concurrent users
- Cross-browser compatibility validation

### Contributing
- Follow Drupal 11 coding standards
- Include comprehensive docblocks
- Add unit tests for data processing functions
- Update this README for any feature additions
- Test changes against production-sized datasets

## Security Considerations

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

# Clear module caches
drush cr

# Check for module errors
drush watchdog:show --filter=newsmotivationmetrics
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