# The Truth Perspective - Drupal Modules

**Version**: 2.0.0  
**Last Updated**: January 2025  
**Drupal Version**: 9.x / 10.x / 11.x
**PHP Version**: 7.4+  

## Overview

This repository contains custom Drupal modules for The Truth Perspective website, providing AI-powered content processing, conversational interfaces, automated workflows, and advanced key metric analysis with taxonomy integration.

## Modules

### 1. 🗞️ News Extractor Module
**Version**: 1.3.0 | **Location**: [`/news_extractor/`](./news_extractor/README.md)

Automated news content extraction and AI-powered analysis system with enhanced taxonomy management.

**Key Features**:
- Extracts news content from URLs and RSS feeds
- AI content analysis using Claude 3.5 Sonnet
- Custom article template with motivation data fields
- AWS Bedrock integration for processing
- **NEW**: Enhanced taxonomy term extraction and validation
- **NEW**: Automatic cleanup of malformed taxonomy terms
- **NEW**: Improved metric parsing to prevent data concatenation

**Recent Updates**:
- Fixed taxonomy term creation to properly extract clean metric names
- Added `news_extractor_cleanup_malformed_taxonomy_terms()` function
- Improved regex parsing to separate metrics from credibility/bias scores
- Enhanced `_news_extractor_extract_tags_from_summary()` for better AI response parsing

**Usage**: Content managers can input news URLs for automatic extraction and AI analysis with clean taxonomy tagging.

---

### 2. 💬 AI Conversation Module  
**Version**: 1.0.0 | **Location**: [`/ai_conversation/`](./ai_conversation/README.md)

Interactive conversational AI interface for authenticated users.

**Key Features**:
- Persistent conversation history per user
- Real-time chat interface with typing indicators
- Multiple Claude model support (3.5 Sonnet, 3 Haiku, 3 Opus)
- Context-aware responses with conversation memory

**Usage**: Authenticated users can engage in AI conversations at `/ai-conversation`.

---

### 3. 🤖 Job Application Automation
**Version**: 1.0.0 | **Location**: [`/job_application_automation/`](./job_application_automation/README.md)

Automated job search and application processing system.

**Key Features**:
- Job posting discovery and extraction
- AI-powered application letter generation
- Automated form filling and submission
- Application tracking and status management

**Usage**: Automates job search workflows for configured user profiles.

---

### 4. 📊 Key Metric Management
**Version**: 2.0.0 | **Location**: [`/key_metric_management/`](./key_metric_management/README.md)

Advanced metric tracking and analysis system with full taxonomy integration.

**Key Features**:
- Extracts key metrics from article motivation data
- Interactive dashboard with statistics and trends at `/key-metrics`
- **NEW**: Individual metric detail pages at `/key-metrics/{metric_name}`
- **NEW**: Full taxonomy integration with automatic term linking
- **NEW**: Enhanced TaxonomyAnalyzer service for malformed term handling
- **NEW**: Professional metric detail templates with category classification
- Configurable block widget for site integration
- **NEW**: Back-navigation and breadcrumb support

**Federal Performance Metrics Tracked** (48 total):
- **Political Performance**: Presidential/Congressional Approval, Trust in Government, Ethics Violations, Polarization Index
- **Government Efficiency**: Effectiveness Score, Transparency Index, Shutdown Days, Federal Employee Satisfaction  
- **Legislative Performance**: Bills Passed, Bipartisan Legislation Rate, Committee Productivity, Filibuster Usage
- **Executive Performance**: Executive Order Frequency
- **Democratic Participation**: Voter Turnout/Registration, Electoral Competitiveness, Campaign Finance, Lobbying
- **Judicial Performance**: Federal Judicial Confirmation Rate
- **Economic Performance**: GDP Growth, Unemployment, Inflation, Federal Funds Rate, Labor Force Participation, Manufacturing Output, Productivity Growth, Consumer Confidence, Housing Prices
- **Fiscal Performance**: National Debt, Budget Deficit/Surplus, Debt-to-GDP Ratio, Tax Revenue Growth, Government Spending Growth
- **Trade & International**: Trade Balance, Current Account Balance, Dollar Index (DXY)
- **Social & Economic Welfare**: Median Household Income, Poverty Rate, Income Inequality (Gini Coefficient)

**Recent Updates**:
- Added comprehensive metric categorization system
- Implemented intelligent taxonomy term lookup with fallback matching
- Created professional metric detail pages with clean typography
- Enhanced TaxonomyAnalyzer to handle malformed terms from post-processor
- Added taxonomy URL generation and linking to related articles
- Improved caching and performance optimization

**Usage**: Analyze and display key federal performance metrics from news articles with direct links to tagged content.

## Quick Start

### Installation
```bash
# Clone repository
git clone https://github.com/keithaumiller/thetruthperspective.git

# Navigate to Drupal modules directory
cd /path/to/drupal/modules/custom/

# Copy modules
cp -r thetruthperspective/* .

# Enable modules
drush en news_extractor ai_conversation job_application_automation key_metric_management

# Clear cache
drush cr
```

### Configuration Requirements

#### AWS Bedrock (Required for AI modules)
- AWS account with Bedrock access
- IAM user with `bedrock:InvokeModel` permissions  
- Access to Anthropic Claude models
- Supported regions: us-east-1, us-west-2, eu-west-1

#### Module-Specific Setup
- **News Extractor**: Configure at `/admin/config/news-extractor`
- **AI Conversation**: Configure at `/admin/config/ai-conversation`  
- **Job Application**: Configure automation settings
- **Key Metrics**: Available at `/key-metrics` (no additional config needed)

#### Taxonomy Setup
- Ensure 'tags' vocabulary exists for metric tagging
- Run cleanup if upgrading: `drush php:eval "news_extractor_cleanup_malformed_taxonomy_terms();"`

## Technical Architecture

### AI Processing Stack
- **Primary AI**: AWS Bedrock with Claude 3.5 Sonnet
- **Fallback Models**: Claude 3 Haiku, Claude 3 Opus
- **Processing**: Server-side API integration with enhanced parsing
- **Security**: Encrypted AWS credential storage
- **NEW**: Improved prompt engineering for clean metric extraction

### Frontend Technologies
- **Templates**: Twig with custom themes and professional styling
- **Styling**: Responsive CSS with modern design patterns
- **JavaScript**: Vanilla JS with AJAX for real-time features
- **UI/UX**: Professional admin interfaces with enhanced navigation

### Backend Architecture
- **Services**: Dependency injection with Drupal services
- **Caching**: Smart caching with TTL and invalidation strategies
- **Database**: Drupal entities with optimized queries
- **Security**: CSRF protection, user authentication, comprehensive input validation
- **NEW**: Enhanced taxonomy management and term validation

### Data Processing Improvements
- **Metric Extraction**: Advanced regex parsing to separate metrics from scores
- **Taxonomy Management**: Intelligent term creation and cleanup
- **URL Generation**: Proper taxonomy term linking with fallback handling
- **Performance**: Optimized queries and caching strategies

## Deployment

### Automated CI/CD
- **Platform**: GitHub Actions
- **Trigger**: Push to `main` branch
- **Process**: Git deployment → file sync → permission setting → cache clear
- **Configuration**: [`.github/workflows/deploy.yml`](./.github/workflows/deploy.yml)

### Manual Deployment
```bash
# Pull latest changes
git pull origin main

# Clear Drupal cache
drush cr

# Run taxonomy cleanup if needed
drush php:eval "news_extractor_cleanup_malformed_taxonomy_terms();"

# Rebuild key metric cache
drush php:eval "\Drupal::service('key_metric_management.taxonomy_analyzer')->clearCache();"
```

## Public Features

### News Analysis
- AI-processed news articles with insights
- Clean motivation data extraction and display
- Professional article templates with proper taxonomy tagging

### Interactive AI Chat  
- Public-facing conversational interface
- Context-aware AI responses
- Conversation history for logged-in users

### Key Metrics Dashboard
- **Main Dashboard**: Public metrics at `/key-metrics`
- **Individual Metrics**: Detailed pages at `/key-metrics/{metric_name}`
- **Taxonomy Integration**: Direct links to related articles via taxonomy terms
- **Categories**: Organized by performance areas (Political, Economic, Fiscal, etc.)
- Interactive charts and statistics
- Embeddable widgets for other pages

### Enhanced Navigation
- Breadcrumb navigation between dashboard and metric details
- Category-based metric organization
- Professional typography and responsive design

## Directory Structure

```
thetruthperspective/
├── .github/workflows/
│   └── deploy.yml                    # CI/CD automation
├── news_extractor/                   # News processing module
│   ├── README.md                     # Detailed documentation
│   ├── src/Controller/
│   ├── templates/
│   ├── news_extractor.module         # Enhanced tag extraction
│   └── [module files]
├── ai_conversation/                  # AI chat module  
│   ├── README.md                     # Detailed documentation
│   ├── src/Service/
│   ├── js/conversation.js
│   └── [module files]
├── job_application_automation/       # Job automation module
│   ├── README.md                     # Detailed documentation
│   └── [module files]
├── key_metric_management/            # Metrics analysis module
│   ├── README.md                     # Detailed documentation
│   ├── src/Plugin/Block/
│   ├── src/Controller/
│   │   └── KeyMetricController.php   # Enhanced with taxonomy URLs
│   ├── src/Service/
│   │   ├── MetricAnalyzer.php        # Core metric analysis
│   │   └── TaxonomyAnalyzer.php      # NEW: Advanced taxonomy handling
│   ├── templates/
│   │   ├── key-metric-stats.html.twig      # Dashboard template
│   │   └── key-metric-detail.html.twig     # NEW: Individual metric pages
│   └── [module files]
└── README.md                        # This overview file
```

## Module Documentation

Each module contains comprehensive documentation:

- **[News Extractor README](./news_extractor/README.md)** - Content extraction and AI analysis
- **[AI Conversation README](./ai_conversation/README.md)** - Interactive chat system  
- **[Job Application README](./job_application_automation/README.md)** - Automation workflows
- **[Key Metrics README](./key_metric_management/README.md)** - Metric tracking and analysis

## Version History

### v2.0.0 (January 2025)
- ✅ **Major Key Metric Management Update**:
  - Added individual metric detail pages with taxonomy integration
  - Implemented TaxonomyAnalyzer service for intelligent term matching
  - Enhanced metric categorization (48 federal performance metrics)
  - Professional UI with responsive design and navigation
- ✅ **News Extractor Improvements**:
  - Fixed taxonomy term extraction to prevent data concatenation
  - Added cleanup function for malformed terms
  - Enhanced AI response parsing with better regex patterns
- ✅ **Enhanced Documentation**: Updated with current functionality

### v1.0.0 (July 2025)
- ✅ News Extractor v1.2.0 with AWS Bedrock
- ✅ AI Conversation v1.0.0 initial release
- ✅ Job Application Automation v1.0.0
- ✅ Key Metric Management v1.0.0  
- ✅ Automated deployment pipeline
- ✅ Comprehensive documentation

## Security & Compliance

- **AWS Integration**: Secure credential management
- **User Privacy**: Conversation data isolation  
- **CSRF Protection**: All AJAX endpoints protected
- **Input Validation**: Comprehensive sanitization and taxonomy term validation
- **Access Control**: Drupal permission integration
- **Data Integrity**: Enhanced taxonomy management with cleanup procedures

## Troubleshooting

### Common Issues

**Malformed Taxonomy Terms**:
```bash
# Clean up existing malformed terms
drush php:eval "news_extractor_cleanup_malformed_taxonomy_terms();"
```

**Key Metric Page Not Loading**:
```bash
# Clear metric cache
drush php:eval "\Drupal::service('key_metric_management.taxonomy_analyzer')->clearCache();"
drush cr
```

**Missing Taxonomy Links**:
```bash
# Check if taxonomy terms exist
drush sql:query "SELECT name FROM taxonomy_term_field_data WHERE name LIKE '%Presidential%' LIMIT 5;"
```

## Support & Maintenance

- **Repository**: https://github.com/keithaumiller/thetruthperspective
- **Issues**: Use GitHub Issues for bug reports and feature requests
- **Documentation**: Individual module README files
