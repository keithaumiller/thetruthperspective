# News Motivation Metrics Module

## Overview
The News Motivation Metrics module provides a comprehensive public analytics dashboard for The Truth Perspective platform. It displays aggregated data from AI-powered news analysis, showing trends in media motivations, entity recognition, and narrative patterns over time.

## Features

### Public Analytics Dashboard
- **Full-width responsive design** using Olivero theme's hero region
- **Interactive timeline charts** showing taxonomy term frequency over time
- **Multi-select controls** for filtering and comparing different motivations/entities
- **Mobile-optimized interface** with professional presentation
- **Real-time data visualization** powered by Chart.js
- **Transparent methodology explanations** for public credibility

### Data Visualization
- Timeline charts showing term frequency trends
- Comparative analysis between different motivation categories
- Entity recognition patterns (people, organizations, locations)
- Article volume and processing statistics
- Temporal narrative analysis

### Professional Public Interface
- SEO-optimized structure for discoverability
- Fast loading performance (<2 seconds target)
- Accessible design following WCAG guidelines
- Print-friendly chart export capabilities
- Professional gradient styling suitable for media/academic use

## Architecture

### Route Structure
- **Public Dashboard**: `/metrics` - Main analytics interface (no authentication required)
- **API Endpoints**: Internal data fetching for chart updates
- **Hero Region Integration**: Full-width layout matching social media links

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
4. Configure permissions if needed for admin features

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
Navigate to `/metrics` to view the public analytics dashboard. No authentication required.

### Chart Interactions
- **Multi-select controls**: Choose multiple terms to compare trends
- **Time filtering**: Select date ranges for focused analysis
- **Export options**: Print-friendly views for reports
- **Responsive design**: Optimized for mobile and desktop viewing

### Data Interpretation
- **Frequency trends**: Shows how often specific motivations appear over time
- **Comparative analysis**: Identify correlations between different motivation types
- **Entity tracking**: Monitor coverage patterns of specific people/organizations
- **Narrative evolution**: Track how story themes develop over time

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
- Chart.js integration for dynamic data visualization
- AJAX-powered chart updates without page reloads
- Responsive chart resizing for mobile devices
- Loading states and error handling for API calls

## API Structure

### Internal Endpoints
- **Chart data API**: Provides aggregated timeline data
- **Term frequency API**: Returns usage statistics for taxonomy terms
- **Export API**: Generates data for external analysis tools

### Data Format
```json
{
  "timeline_data": {
    "labels": ["2024-01", "2024-02", "..."],
    "datasets": [
      {
        "label": "Political Motivation",
        "data": [15, 23, 18, "..."],
        "borderColor": "#667eea"
      }
    ]
  },
  "metadata": {
    "total_articles": 1250,
    "date_range": "2024-01-01 to 2024-12-31",
    "last_updated": "2024-12-15T10:30:00Z"
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
- Modify chart types (line, bar, area charts)
- Adjust time granularity (daily, weekly, monthly)
- Add custom data filtering options
- Implement additional visualization types

## Troubleshooting

### Common Issues

#### Dashboard Not Loading
- Verify `news_extractor` module is enabled and has processed articles
- Check that taxonomy vocabularies exist and have terms
- Ensure JavaScript is enabled in browser
- Clear Drupal caches: `drush cr`

#### Performance Issues
- Verify database indexing on date and taxonomy fields
- Check for large dataset queries without proper limits
- Monitor memory usage during chart rendering
- Consider enabling additional caching layers

#### Styling Problems
- Clear browser cache and check for CSS conflicts
- Verify Chart.js library is loading correctly
- Check responsive design across different devices
- Validate CSS syntax and media query breakpoints

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
- **Module file**: Core hooks and preprocessing logic
- **Controller**: Route handling and data processing
- **CSS**: Comprehensive styling for all components
- **JavaScript**: Chart initialization and interaction handling

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