# The Truth Perspective - Drupal Modules

**Version**: 1.2.0  
**Last Updated**: August 12, 2025  
**Drupal Version**: 11.x  
**PHP Version**: 8.3.6+  
**Status**: Production Ready

## Overview

This repository contains custom Drupal modules for The Truth Perspective website, providing AI-powered content processing, news analysis, conversational interfaces, and comprehensive analytics. All modules are production-tested and actively deployed on the live platform.

## 🚀 Live Production Modules

### 1. 🗞️ News Extractor Module ✅ **FULLY OPERATIONAL**
**Version**: 1.2.0 | **Location**: [`/news_extractor/`](./news_extractor/README.md)

Comprehensive news content extraction, AI analysis, and automated source population system.

**✅ Production Features**:
- ✅ **Multi-Stage Source Population**: JSON data → URL mapping → Feed metadata
- ✅ **Comprehensive Field Architecture**: 20+ specialized fields for complete article analysis
- ✅ **AI Integration**: AWS Bedrock Claude 3.5 Sonnet for content analysis
- ✅ **Automated Processing**: Cron-based background processing (50 articles/hour)
- ✅ **Drush Commands**: Complete command suite for bulk operations and statistics
- ✅ **Database Optimization**: Proper NULL handling and efficient queries

**Core Capabilities**:
- **Content Extraction**: Diffbot API integration for clean article parsing
- **News Source Detection**: Multi-stage extraction from JSON, URLs, and feeds
- **AI Analysis**: Motivation detection, bias analysis, sentiment scoring
- **Field Management**: 135+ articles with comprehensive metadata
- **Automation**: Real-time processing via Drupal hooks and cron

**Technical Architecture**:
- Service injection with dependency management
- Batch processing for performance optimization
- Comprehensive error handling and logging
- Multi-source fallback mechanisms

---

### 2. 📊 News Motivation Metrics ✅ **DEPLOYED**
**Version**: 1.1.0 | **Location**: [`/newsmotivationmetrics/`](./newsmotivationmetrics/README.md)

Public analytics dashboard for news analysis metrics and visualizations.

**✅ Production Features**:
- Public analytics dashboard with Chart.js visualizations
- Real-time metrics from processed article data
- Mobile-responsive design for public accessibility
- Professional data presentation for media and academic use

---

### 3. 💬 AI Conversation Module ✅ **DEPLOYED**
**Version**: 1.0.0 | **Location**: [`/ai_conversation/`](./ai_conversation/README.md)

Interactive conversational AI interface with persistent history and real-time responses.

**✅ Production Features**:
- Real-time chat at [/ai-conversation](https://thetruthperspective.org/ai-conversation)
- AWS Bedrock integration with Claude 3.5 Sonnet
- Persistent conversation history per authenticated user
- Professional UI with typing indicators and AJAX communication

---

### 4. 📱 Social Media Automation ✅ **DEPLOYED**
**Version**: 1.0.0 | **Location**: [`/social_media_automation/`](./social_media_automation/README.md)

Unified multi-platform social media automation system for automated content distribution.

**✅ Production Features**:
- ✅ **Multi-Platform Support**: Mastodon, LinkedIn, Facebook, Twitter (unified architecture)
- ✅ **Intelligent Content Generation**: AI-powered content creation with platform-specific formatting
- ✅ **Automated Scheduling**: Queue-based posting system with configurable timing
- ✅ **Free Platform Focus**: Mastodon (primary), LinkedIn (secondary) for cost-effective automation
- ✅ **Complete Drush Commands**: Full command suite for testing, status monitoring, and management
- ✅ **OAuth Integration**: Complete authentication workflows for all supported platforms

**Core Capabilities**:
- **Platform Management**: Unified interface supporting 4 major social media platforms
- **Content Adaptation**: Platform-specific character limits, formatting, and feature support
- **Rate Limiting**: Intelligent rate limit management for each platform's API constraints
- **Queue Processing**: Background processing with Drupal's queue system
- **Admin Interface**: Configuration management at `/admin/config/services/social-media-automation`

**Technical Architecture**:
- Service-based architecture with dependency injection
- PlatformInterface for consistent multi-platform implementation
- OAuth 1.0a (Twitter) and OAuth 2.0 (Mastodon, LinkedIn, Facebook) support
- Queue worker system for reliable background processing
- Comprehensive error handling and logging

**Available Platforms**:
- **🟢 Mastodon**: Free, decentralized platform (500 chars, media support)
- **🟡 LinkedIn**: Professional network (3000 chars, article sharing)
- **🔵 Facebook**: Social platform (63,206 chars, requires page review)
- **⚪ Twitter**: Microblogging platform (280 chars, requires $100/month API access)

---

### 5. 🤖 Job Application Automation ⚠️ **DEVELOPMENT**
**Version**: 1.0.0 | **Location**: [`/job_application_automation/`](./job_application_automation/README.md)

Automated job search and application processing system (development framework).

## 📈 Current Production Data

### **Article Processing Statistics**
- ✅ **Total Articles**: 135 articles in production database
- ✅ **JSON Data Available**: 132 articles with Diffbot extraction data
- ✅ **News Sources**: Multi-stage population system operational
- ✅ **Field Coverage**: 20+ specialized fields per article
- ✅ **Processing Rate**: 50 articles per hour via automated cron

### **System Performance**
- ✅ **Database Queries**: Optimized with proper NULL handling
- ✅ **Batch Processing**: Memory-efficient processing in configurable batches
- ✅ **Error Handling**: Comprehensive logging and graceful degradation
- ✅ **API Integration**: Stable Diffbot and AWS Bedrock connectivity

## 🔧 Comprehensive Drush Command Suite

### Social Media Automation Commands

#### Platform Management
```bash
drush social-media:platforms        # List all configured platforms with details
drush social-media:status           # System status and configuration overview
```
**Output**: Platform capabilities, character limits, authentication status, rate limits

#### Content Operations  
```bash
drush social-media:test-content     # Generate and display test content for all platforms
drush social-media:test-connections # Test API connectivity for configured platforms
drush social-media:post "message"   # Post content to all enabled platforms
```
**Options**:
- `--platform=mastodon`: Target specific platform
- `--schedule="+1 hour"`: Schedule for future posting

#### Configuration Management
```bash
drush social-media:enable mastodon  # Enable specific platform
drush social-media:disable twitter  # Disable platform
drush social-media:queue-status     # Check posting queue status
```

### News Extractor Commands

#### Statistics and Analysis
```bash
drush ne:stats                    # Comprehensive field statistics and debug info
drush ne:summary                  # Processing status summary with recommendations
drush news-extractor:source-stats # Full alias
```
**Output**: Field existence, data availability, processing opportunities

#### Bulk Processing
```bash
drush ne:pop-sources             # Process articles from JSON data (primary)
drush ne:pop-url                 # Process articles from URLs (fallback)
```
**Options**: 
- `--batch_size=N`: Custom batch size (default: 50/25)
- `--all`: Process all articles in one run

#### Testing and Debugging
```bash
drush ne:test https://example.com  # Test extraction for specific URL
```
**Output**: Detailed extraction results and source mapping

## Technical Architecture

### Field Architecture (Article Content Type)

#### Core Content Fields
- **field_json_scraped_article_data**: Raw Diffbot API responses (JSON)
- **field_news_source**: Standardized news source names
- **field_original_url**: Source URLs for content extraction
- **field_publication_date**: Article timestamps with ISO 8601 format

#### AI Analysis Fields
- **field_ai_raw_response**: Complete Claude API responses
- **field_motivation_analysis**: Political/economic motivation breakdown
- **field_bias_analysis**: Comprehensive bias detection
- **field_credibility_score**: Article quality assessments

#### Processing Metrics
- **field_article_sentiment_score**: Numerical sentiment analysis
- **field_bias_rating**: Standardized bias classifications
- **field_tags**: AI-generated content categorization

📋 **Complete Technical Documentation**: [ARCHITECTURE.md](./ARCHITECTURE.md)

### Data Processing Pipeline

#### Stage 1: Content Import
```
RSS Feed → Feeds Module → Article Creation → Initial Population
```

#### Stage 2: Content Enhancement
```
Article URL → Diffbot API → JSON Response → field_json_scraped_article_data
```

#### Stage 3: News Source Population (Multi-Stage)
```
Priority 1: JSON Data → siteName Extraction → Cleaned Source Name
Priority 2: URL Domain → Domain Mapping → Standardized Name  
Priority 3: Feed Metadata → RSS Source → Cleaned Name
```

#### Stage 4: AI Analysis
```
Clean Content → AWS Bedrock Claude → Multiple Analysis Fields
```

### Production Infrastructure

- **Platform**: Drupal 11 on Ubuntu 22.04 LTS
- **PHP**: 8.3.6 with Zend OPcache
- **Database**: MySQL with optimized indexing for large datasets
- **AI Integration**: AWS Bedrock Claude 3.5 Sonnet
- **Content Extraction**: Diffbot API for article parsing
- **Production URL**: https://thetruthperspective.org

## Quick Start

### Installation
```bash
# Clone repository
git clone https://github.com/keithaumiller/thetruthperspective.git

# Navigate to Drupal modules directory
cd /path/to/drupal/modules/custom/

# Copy modules
cp -r thetruthperspective/* .

# Enable production modules
drush en news_extractor newsmotivationmetrics ai_conversation social_media_automation
```

### Configuration

#### AWS Bedrock Setup (Required)
```bash
# Required AWS services
- AWS Bedrock with Anthropic Claude model access
- IAM permissions for bedrock:InvokeModel
- Supported regions: us-east-1, us-west-2, eu-west-1
```

#### Social Media Automation Setup (Optional)
```bash
# Platform API Keys (configure as needed)
- Mastodon: Instance URL + Access Token (free)
- LinkedIn: Client ID + Client Secret + OAuth (free tier)
- Facebook: App ID + App Secret + Page Token (requires review)
- Twitter: Consumer Key + Secret + OAuth (requires $100/month)
```

#### Production Commands
```bash
# Check system status
drush ne:stats

# Process articles from JSON data
drush ne:pop-sources

# Process articles from URLs (fallback)
drush ne:pop-url

# Test specific URL extraction
drush ne:test https://cnn.com/article

# Check social media automation status
drush social-media:status

# Test platform connections
drush social-media:test-connections

# Generate test content for all platforms
drush social-media:test-content
```

## Social Media Automation System

### Multi-Platform Architecture

#### Supported Platforms
```
🟢 Mastodon (Primary Free Platform)
- Character Limit: 500
- Features: Media uploads, polls, content warnings
- Authentication: Access token per instance
- Rate Limits: 300 requests per 5 minutes
- Cost: Free

🟡 LinkedIn (Secondary Free Platform)  
- Character Limit: 3,000
- Features: Article sharing, professional posts
- Authentication: OAuth 2.0 
- Rate Limits: 100 requests per day (free tier)
- Cost: Free tier available

🔵 Facebook (Page Posts)
- Character Limit: 63,206
- Features: Media, links, reactions
- Authentication: App + Page tokens
- Rate Limits: 200 requests per hour
- Cost: Free (requires app review)

⚪ Twitter (Optional Paid)
- Character Limit: 280 (threads for longer)
- Features: Media, polls, threads
- Authentication: OAuth 1.0a
- Rate Limits: 50 posts per day (paid tier)
- Cost: $100/month for API access
```

### Content Generation System

#### AI-Powered Content Creation
- **Source Integration**: Generate posts from News Extractor articles
- **Platform Adaptation**: Automatic formatting for each platform's constraints
- **Content Variations**: Multiple versions to avoid duplicate content
- **Hashtag Generation**: Platform-appropriate hashtag strategies
- **Media Integration**: Automatic image and link handling

#### Content Examples
```
Mastodon (500 chars):
🗞️ New analysis reveals concerning trends in media coverage...
#MediaAnalysis #TruthPerspective #NewsAnalysis
[Auto-generated link]

LinkedIn (3000 chars):
📊 In-Depth Analysis: Our latest investigation into media narrative patterns...
[Full professional summary with insights]
#MediaResearch #Journalism #DataAnalysis

Twitter (280 chars):
📈 Breaking: Latest media analysis shows...
[Concise summary with thread continuation]
#News #MediaBias
```

### Automated Posting Workflow

#### Queue-Based Processing
```
Content Generation → Platform Formatting → Queue Storage → Scheduled Posting
                                                      ↓
                                            Rate Limit Management
                                                      ↓
                                              Error Handling & Retry
```

#### Scheduling Options
- **Immediate Posting**: Direct API calls for urgent content
- **Scheduled Posts**: Queue-based posting with time delays
- **Batch Processing**: Multiple platforms simultaneously
- **Rate Limit Respect**: Automatic spacing to avoid API limits

### Platform Management Interface

#### Admin Configuration
**Location**: `/admin/config/services/social-media-automation`

**Features**:
- Platform enable/disable toggles
- API credential management
- Posting schedule configuration
- Rate limit monitoring
- Error log viewing
- Test content generation

#### Platform Status Dashboard
- Connection status indicators
- API quota usage tracking
- Recent posting history
- Error rate monitoring
- Performance metrics

## News Source Population System

### Processing Logic

#### Primary Method: JSON Data Extraction
- **Source**: Diffbot API responses in `field_json_scraped_article_data`
- **Target**: Extract `objects[].siteName` values
- **Processing**: Clean and standardize source names
- **Coverage**: 132 articles with available JSON data

#### Fallback Method: URL Domain Mapping
- **Source**: URLs in `field_original_url`
- **Mapping**: 15+ major news sources (CNN, Fox News, Reuters, etc.)
- **Unknown Domains**: Convert to readable names (e.g., `news-site.com` → `News Site`)

#### Source Name Standardization
- **Cleaning**: Remove RSS suffixes, breaking news tags, pipe separators
- **Standardization**: CNN Politics → CNN, FOX News → Fox News
- **Validation**: Ensure consistent naming across sources

### Database Query Optimization

#### NULL vs Empty String Handling
```php
// Proper Drupal query pattern for empty fields
->group()
  ->condition('field_news_source', NULL, 'IS NULL')
  ->condition('field_news_source', '', '=')
->groupOperator('OR')
```

#### Batch Processing
- **JSON Processing**: 50 articles per batch
- **URL Processing**: 25 articles per batch
- **Memory Management**: Prevent exhaustion with large datasets
- **Progress Tracking**: Real-time updates during processing

## Automated Processing

### Drupal Hook Implementation
- **feeds_process_alter**: Extract source during RSS import
- **node_insert**: Process new articles immediately
- **node_update**: Re-process when URLs change
- **cron**: Background processing (50 articles/hour)

### Performance Optimization
- **Indexing**: Optimized database indexes for frequent queries
- **Caching**: Strategic caching of expensive operations
- **API Rate Limiting**: Respectful API usage patterns
- **Error Recovery**: Graceful handling of failures

## 📁 Module Documentation

Each module contains detailed technical documentation:

- **[Social Media Automation README](./social_media_automation/README.md)** - Multi-platform automation system
- **[News Extractor README](./news_extractor/README.md)** - Content extraction and AI analysis
- **[News Motivation Metrics README](./newsmotivationmetrics/README.md)** - Analytics dashboard
- **[AI Conversation README](./ai_conversation/README.md)** - Conversational AI interface
- **[Job Application README](./job_application_automation/README.md)** - Automation framework
- **[ARCHITECTURE.md](./ARCHITECTURE.md)** - Complete technical architecture

## Version History

### v1.2.0 (August 18, 2025) - **CURRENT PRODUCTION**
- ✅ **Social Media Automation**: Complete multi-platform automation system deployed
- ✅ **Platform Support**: Mastodon, LinkedIn, Facebook, Twitter with unified architecture
- ✅ **Drush Commands**: Full command suite for platform management and testing
- ✅ **OAuth Integration**: Complete authentication workflows for all platforms
- ✅ **Content Generation**: AI-powered platform-specific content creation
- ✅ **Queue System**: Background processing with rate limit management
- ✅ **News Source Population**: Complete multi-stage extraction system
- ✅ **Database Optimization**: Proper NULL handling in all queries
- ✅ **Field Architecture**: 20+ specialized fields fully documented
- ✅ **Processing Pipeline**: Multi-stage automation with cron integration

### v1.1.1 (August 2025)
- ✅ Enhanced news source extraction from JSON scraped data
- ✅ Multi-stage fallback system implementation
- ✅ Comprehensive field documentation and architecture
- ✅ Workspace stability improvements

### v1.1.0 (August 2025)
- ✅ Key Metric Management production deployment
- ✅ AI Conversation system operational
- ✅ Public dashboard and analytics features

### v1.0.0 (July 2025)
- Initial production release with core module architecture
- AWS Bedrock integration and service injection
- Template system and responsive design

## Security and Performance

### Security Measures
- **Input Sanitization**: All user inputs properly validated
- **XSS Prevention**: Output properly escaped in templates
- **SQL Injection**: Entity queries prevent injection attacks
- **Access Control**: Proper permissions for admin functions

### Performance Features
- **Batch Processing**: Memory-efficient bulk operations
- **Database Optimization**: Indexed fields and efficient queries
- **Caching Strategy**: Multi-layer caching with TTL management
- **API Rate Limiting**: Respectful external API usage

## Support & Maintenance

- **Repository**: https://github.com/keithaumiller/thetruthperspective
- **Live Site**: https://thetruthperspective.org
- **Issues**: GitHub Issues for bug reports and feature requests
- **Documentation**: Individual module READMEs with detailed setup
- **Monitoring**: Comprehensive logging and error tracking

## License

GPL-2.0+ (Drupal compatible)

---

**✅ Production Status**: All core systems operational with 135+ articles processed  
**🔧 Processing**: 50 articles/hour automated with multi-stage source population  
**📊 Coverage**: 132 articles with JSON data, 20+ specialized fields per article  
**🚀 Commands**: Complete Drush suite for statistics, processing, and testing  
**📱 Social Media**: Multi-platform automation with 4 platform support ready

**Maintained by**: Keith Aumiller | **Organization**: The Truth Perspective | **Last Updated**: August 18, 2025

## 🚀 Live Production Modules

### 1. 📊 Key Metric Management ✅ **FULLY OPERATIONAL**
**Version**: 1.0.0 | **Location**: [`/key_metric_management/`](./key_metric_management/README.md)

Advanced metric tracking and analysis system extracting insights from article content.

**✅ Production Features**:
- ✅ **Live Dashboard**: [/key-metrics](https://thetruthperspective.org/key-metrics) - **18 metrics tracked**
- ✅ **Block Widget**: Deployed in hero region with **2,348 chars** rendered output
- ✅ **Real Data**: **20 articles** processed with motivation analysis
- ✅ **Top Metrics**: "Public Trust in Government" (3 articles), Government Institutions, Foreign Policy
- ✅ **Public API**: Metric browsing and statistics available

**Technical Stack**:
- Service injection architecture with MetricAnalyzer and TaxonomyAnalyzer
- Smart caching with TTL and automatic invalidation
- Professional template system with responsive CSS
- Block plugin system for site integration

---

### 2. 💬 AI Conversation Module ✅ **DEPLOYED**
**Version**: 1.0.0 | **Location**: [`/ai_conversation/`](./ai_conversation/README.md)

Interactive conversational AI interface with persistent history and real-time responses.

**✅ Production Features**:
- Real-time chat at [/ai-conversation](https://thetruthperspective.org/ai-conversation)
- AWS Bedrock integration with Claude 3.5 Sonnet
- Persistent conversation history per authenticated user
- Professional UI with typing indicators and AJAX communication
- Multiple model support (Sonnet, Haiku, Opus)

**Usage**: Public conversational AI interface for authenticated users.

---

### 3. 🗞️ News Extractor Module ✅ **STABLE**
**Version**: 1.2.1 | **Location**: [`/news_extractor/`](./news_extractor/README.md)

Automated news content extraction and AI-powered analysis system.

**✅ Current Status**: Stable production version with comprehensive field architecture.

**Core Features**:
- URL-based content extraction with AI analysis
- AWS Bedrock integration for content processing
- Multi-stage news source population from JSON scraped data and URL extraction
- Comprehensive field architecture with 20+ specialized fields
- Motivation data extraction and entity analysis
- Custom article templates with structured data
- Automated cron-based maintenance for missing news sources

**Field Architecture**:
- **Core Content**: body, field_original_url, field_news_source, field_original_author
- **AI Analysis**: field_motivation_analysis, field_ai_raw_response, field_motivation_data
- **Media Assessment**: field_credibility_score, field_bias_rating, field_bias_analysis
- **Technical Data**: field_json_scraped_article_data, field_article_hash, field_external_image_url
- **Content Management**: field_tags, field_image, field_publication_date

**Recent Updates**: 
- Enhanced news source extraction from field_json_scraped_article_data
- Multi-stage fallback system: JSON data → URL mapping → Feed metadata
- Comprehensive Drush commands for bulk processing
- Graceful field handling for different installation configurations

📋 **Technical Details**: See [ARCHITECTURE.md](./ARCHITECTURE.md) for complete field structure and data flow documentation.

---

### 4. 🤖 Job Application Automation ⚠️ **DEVELOPMENT**
**Version**: 1.0.0 | **Location**: [`/job_application_automation/`](./job_application_automation/README.md)

Automated job search and application processing system.

**Development Features**:
- Job posting discovery and extraction framework
- AI-powered application letter generation
- Form automation infrastructure
- Application tracking system architecture

**Status**: Framework complete, integration testing in progress.

## 📈 Production Statistics

### **Live Deployment Metrics**
- ✅ **Key Metrics Dashboard**: **18 unique metrics** from **20 articles**
- ✅ **Block System**: Rendering **2,348 characters** of structured HTML
- ✅ **Top Tracked Metric**: "Public Trust in Government" (3 articles)
- ✅ **Public Access**: `/key-metrics` dashboard fully operational
- ✅ **AI Chat**: Real-time conversational interface live

### **Technical Performance**
- ✅ **Deployment**: Automated CI/CD via GitHub Actions
- ✅ **Caching**: Smart invalidation with 1-hour TTL
- ✅ **Templates**: Professional Twig templates with responsive CSS
- ✅ **Services**: Full dependency injection architecture

## Quick Start

### Installation
```bash
# Clone repository
git clone https://github.com/keithaumiller/thetruthperspective.git

# Navigate to Drupal modules directory
cd /path/to/drupal/modules/custom/

# Copy modules
cp -r thetruthperspective/* .

# Enable production-ready modules
drush en key_metric_management ai_conversation
```

### Production Configuration

#### AWS Bedrock Setup (Required for AI modules)
```bash
# Required AWS services
- AWS Bedrock with Anthropic Claude model access
- IAM permissions for bedrock:InvokeModel
- Supported regions: us-east-1, us-west-2, eu-west-1
```

#### Module Configuration Paths
- **Social Media**: Configure platforms at `/admin/config/services/social-media-automation`
- **Key Metrics**: Auto-configured, place block at `/admin/structure/block`
- **AI Conversation**: Configure models at `/admin/config/ai-conversation`
- **News Extractor**: Setup at `/admin/config/news-extractor` (maintenance mode)

## 🎯 Public Features Currently Live

### Key Metrics Analytics
**Public URL**: [https://thetruthperspective.org/key-metrics](https://thetruthperspective.org/key-metrics)
- Interactive dashboard with real-time statistics
- 18 unique metrics tracked across government trust, policy, and institutions
- Public browsing of metric trends and article associations
- Professional data visualization

### AI Conversation Interface  
**Public URL**: [https://thetruthperspective.org/ai-conversation](https://thetruthperspective.org/ai-conversation)
- Context-aware AI responses using Claude 3.5 Sonnet
- Conversation history for registered users
- Real-time typing indicators and professional UI
- Multi-model support for different conversation types

### Integrated Block Widgets
- **Key Metric Stats Block**: Deployed in hero region
- **Real-time data**: Shows current metrics with "View All" links
- **Responsive design**: Mobile-friendly with modern styling

## Technical Architecture

### Production Stack
- **Backend**: Drupal 11.x with modern service architecture
- **AI Processing**: AWS Bedrock with Claude 3.5 Sonnet (primary)
- **Frontend**: Responsive CSS Grid + Vanilla JavaScript
- **Caching**: Multi-layer with smart invalidation
- **Database**: Optimized queries with proper indexing

### Security & Performance
- **CSRF Protection**: All AJAX endpoints secured
- **Input Validation**: Comprehensive sanitization
- **Service Injection**: Type-safe dependency management
- **Caching Strategy**: TTL-based with automatic invalidation
- **Error Handling**: Graceful degradation with logging

## Deployment Pipeline

### Automated CI/CD
**Platform**: GitHub Actions with production deployment
```yaml
Trigger: Push to main branch
Process: Git sync → File deployment → Cache clearing → Verification
Status: ✅ Operational with comprehensive testing
```

### Production Verification
- Template discovery verification
- Block plugin registration testing
- Service injection validation
- Cache clearing confirmation
- Live endpoint testing

## 📁 Module Documentation

Each module contains comprehensive technical documentation:

- **[Key Metric Management README](./key_metric_management/README.md)** - Analytics and dashboard system
- **[AI Conversation README](./ai_conversation/README.md)** - Conversational AI implementation  
- **[News Extractor README](./news_extractor/README.md)** - Content extraction and analysis
- **[Job Application README](./job_application_automation/README.md)** - Automation framework
- **[ARCHITECTURE.md](./ARCHITECTURE.md)** - Complete field structure, data flow, and technical architecture

## 🔧 Development Status

### ✅ Production Ready
- **Social Media Automation**: Multi-platform system with 4 platform support
- **Key Metric Management**: Fully operational with live data
- **AI Conversation**: Real-time chat system deployed
- **News Extractor**: Stable with enhanced news source data processing

### 🔧 Active Development
- **News Extractor**: Enhanced news source field population from JSON scraped data
- **Job Application**: Framework complete, integration testing

### 📊 Real Production Data
```
Key Metrics Dashboard: 18 metrics tracked
Articles Processed: 20 with motivation analysis
Block Rendering: 2,348 characters HTML output
Top Metric: "Public Trust in Government" (3 articles)
Cache Performance: 1-hour TTL with smart invalidation
```

## Version History

### v1.1.1 (August 12, 2025) - **CURRENT PRODUCTION**
- ✅ **News Extractor**: Stable version with enhanced news source extraction system
- ✅ **Multi-stage Data Flow**: JSON scraped data → URL extraction → Feed data processing
- ✅ **Improved Source Mapping**: Enhanced domain-to-source name standardization
- ✅ **Workspace Stability**: Reverted to commit 45f9cd4 for reliable operation
- ✅ **Key Metric Management**: Continued full production deployment with 18 metrics
- ✅ **AI Conversation**: Real-time chat system operational
- ✅ **Block System**: Hero region deployment with professional styling

### v1.1.0 (August 2025) - Previous Production
- ✅ **Key Metric Management**: Full production deployment with 18 metrics
- ✅ **AI Conversation**: Real-time chat system operational
- ✅ **Block System**: Hero region deployment with professional styling
- ✅ **Public Dashboard**: Live metrics at `/key-metrics`
- ✅ **Performance Optimization**: Enhanced caching and error handling
- ⚠️ **News Extractor**: Maintenance mode for class optimization

### v1.0.0 (July 2025)
- Initial production release with AWS Bedrock integration
- Core module architecture and service injection
- Template system and responsive design
- Automated deployment pipeline

## Support & Maintenance

- **Repository**: https://github.com/keithaumiller/thetruthperspective
- **Live Site**: https://thetruthperspective.org
- **Issues**: GitHub Issues for bug reports and feature requests
- **Documentation**: Individual module READMEs with detailed setup
- **Monitoring**: AWS CloudWatch for AI usage and performance

## License

GPL-2.0+ (Drupal compatible)

---

**✅ Production Status**: Key systems operational with live data  
**🔗 Live URLs**: [Key Metrics Dashboard](https://thetruthperspective.org/key-metrics) | [AI Chat](https://thetruthperspective.org/ai-conversation)  
**📊 Current Data**: 18 metrics, 20 articles, real-time analytics  
**🚀 Deployment**: Automated CI/CD with GitHub Actions  
**📱 Social Media**: Multi-platform automation system deployed and ready

**Maintained by**: Keith Aumiller | **Organization**: The Truth Perspective | **Last Updated**: August 18, 2025
