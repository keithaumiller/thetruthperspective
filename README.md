# The Truth Perspective - AI-Powered News Analysis Platform

**Version**: 1.4.0  
**Last Updated**: August 21, 2025  
**Drupal Version**: 11.x  
**PHP Version**: 8.3.6+  
**Status**: Production Ready with Enhanced Logging Framework

## Overview

The Truth Perspective is an AI-powered platform for analyzing news articles using advanced natural language processing. The system automatically processes news content to detect bias, analyze sentiment, identify motivations, and provide transparent insights into media narratives.

**Live Platform**: [https://thetruthperspective.org](https://thetruthperspective.org)  
**Repository**: [https://github.com/keithaumiller/thetruthperspective](https://github.com/keithaumiller/thetruthperspective)

## 🚀 Core Production Modules

### 📊 News Motivation Metrics ✅ **DEPLOYED**
**Location**: [`/newsmotivationmetrics/`](./newsmotivationmetrics/README.md)

Public analytics dashboard providing transparent insights into news analysis data.

**Features**:
- **Individual Assessment Timeline Charts**: Separate charts for bias, credibility, and sentiment trends following consistent design patterns
- **Enhanced Chart Visualization**: Dedicated timeline charts with isolated data namespaces to prevent data contamination
- Real-time analytics dashboard with Chart.js visualizations
- Mobile-responsive design for public accessibility
- Professional data presentation suitable for media and academic use
- Comprehensive metrics from processed article data

### 🗞️ News Extractor ✅ **FULLY OPERATIONAL**
**Location**: [`/news_extractor/`](./news_extractor/README.md)

Comprehensive news content extraction, AI analysis, and automated processing system.

**Features**:
- **Content Extraction**: Diffbot API integration for clean article parsing
- **AI Analysis**: AWS Bedrock Claude 3.5 Sonnet for motivation detection, bias analysis, sentiment scoring, and authoritarianism assessment
- **Automated Processing**: Multi-stage pipeline with enhanced cron-based background processing
- **Enhanced Cron Maintenance**: Comprehensive statistics tracking for assessment field coverage (authoritarianism, credibility, bias, sentiment)
- **Smart Assessment Field Management**: Automatic detection and reprocessing of articles missing assessment scores with detailed before/after logging
- **Source Detection**: Multi-stage extraction from JSON data, URLs, and feed metadata
- **Stuck Feed Recovery**: Automatic detection and resolution of blocked feed imports

### 📱 Social Media Automation ✅ **FULLY OPERATIONAL**
**Location**: [`/social_media_automation/`](./social_media_automation/README.md)

Multi-platform social media automation system with AI-powered content generation.

**Features**:
- **Platform Support**: Mastodon (✅ Working), LinkedIn, Facebook, Twitter integration ready
- **AI Content Generation**: Automated post creation from analyzed articles
- **Daily Automation**: Scheduled posting with content type rotation
- **Manual Override**: Force posting capability for immediate content
- **Admin Interface**: Complete configuration at `/admin/config/services/social-media-automation/settings`

### 💬 AI Conversation ✅ **DEPLOYED**
**Location**: [`/ai_conversation/`](./ai_conversation/README.md)

Interactive conversational AI interface for engaging with content insights.

**Features**:
- Real-time chat at [/ai-conversation](https://thetruthperspective.org/ai-conversation)
- AWS Bedrock integration with Claude 3.5 Sonnet
- Persistent conversation history for authenticated users
- Professional UI with typing indicators and AJAX communication

## 📊 Centralized Logging Framework ✅ **NEW**

**Version**: 1.0.0  
**Location**: Platform-wide logging management system  
**Documentation**: [`/newsmotivationmetrics/LOGGING_SYSTEM.md`](./newsmotivationmetrics/LOGGING_SYSTEM.md)

### Overview
Comprehensive logging management system that addresses excessive log noise in production environments by providing centralized control over logging verbosity across all platform modules.

### 🎯 **Problem Solved**
- **Before**: 700-1400 log entries per hour with excessive debug/info messages
- **After**: 5-20 log entries per hour (ERROR only) - **95% reduction**
- **Performance**: Significant improvement through reduced I/O operations
- **Monitoring**: Cleaner logs make error diagnosis much more effective

### 🔧 **Quick Commands**
```bash
# Check current logging configuration
drush ttplog-status

# Production setting (ERROR only) - RECOMMENDED
drush ttplog-error

# Debugging setting (INFO level) 
drush ttplog-info

# Development setting (ALL including DEBUG)
drush ttplog-debug

# Set specific module logging level
drush ttplog-set social_media_automation 1
```

### 📋 **Logging Levels**
| Level | Name | Includes | Use Case |
|-------|------|----------|----------|
| **1** | **ERROR ONLY** | Emergency, Alert, Critical, Error | **Production** (Recommended) |
| **2** | **WARNING & ERROR** | + Warning | Production with warnings |
| **3** | **INFO & ABOVE** | + Notice, Info | Debugging/troubleshooting |
| **4** | **ALL LOGS** | + Debug | Development only |

### 🌐 **Web Interface**
**Admin URL**: `/admin/config/development/thetruthperspective/logging`
- Configure global and module-specific logging levels
- Real-time configuration changes without code deployment
- Production-ready defaults with safety warnings

### 🏗️ **Technical Implementation**
- **System Integration**: `hook_logger_log_alter()` for platform-wide filtering
- **Configuration Storage**: `thetruthperspective.logging` config entity
- **Module Coverage**: All platform modules (news_extractor, social_media_automation, etc.)
- **Performance**: Logs filtered at system level before database writes

### 📈 **Benefits**
1. **Cleaner Production Logs**: Only critical errors visible by default
2. **Better Performance**: ~95% reduction in log I/O operations
3. **Easier Monitoring**: Errors stand out clearly for alerting systems
4. **Flexible Debugging**: Enable verbose logging only when troubleshooting
5. **Consistent Platform**: Unified logging standards across all modules

### 🚀 **Production Deployment**
```bash
# Deploy logging system
git pull origin main
drush updb

# Set production logging (ERROR only)
drush ttplog-error

# Verify configuration
drush ttplog-status

# Optional: Clear old verbose logs
drush watchdog:delete all
```

### 🔄 **Module Integration**
The logging system provides a `ConfigurableLoggingTrait` for easy integration:
```php
use Drupal\[module]\Traits\ConfigurableLoggingTrait;

class MyService {
  use ConfigurableLoggingTrait;
  
  public function processData() {
    $this->logError('Critical error occurred', ['@id' => $id]);     // Always logged
    $this->logWarning('Non-critical issue', ['@type' => $type]);    // If warnings enabled
    $this->logInfo('Processing complete', ['@count' => $count]);    // If info enabled  
    $this->logDebug('Debug details', ['@data' => $debug_data]);     // If debug enabled
  }
}
```

## 🎯 Key Features

### 🔍 **Content Analysis**
- **AI-Powered Processing**: Claude 3.5 Sonnet for advanced natural language understanding
- **Bias Detection**: Systematic identification of political and ideological bias
- **Sentiment Analysis**: Emotional tone measurement and tracking
- **Authoritarianism Assessment**: Political authoritarianism tendency scoring (0-10 scale)
- **Entity Recognition**: Extraction of people, organizations, locations, and concepts
- **Motivation Analysis**: Detection of underlying political, economic, and social motivations

### 📊 **Public Transparency**
- **Open Analytics**: Public dashboard accessible without authentication
- **Methodology Transparency**: Clear explanations of analysis processes
- **Professional Presentation**: Suitable for media professionals and academic research
- **Mobile Responsive**: Optimized for all device types

### 🤖 **Automation & Processing**
- **Real-time Processing**: Automatic analysis of new articles via RSS feeds
- **Batch Operations**: Efficient processing of large article datasets
- **Error Recovery**: Automatic retry logic and graceful failure handling
- **Feed Management**: Stuck feed detection and automatic recovery

## 🔧 Quick Start

### Installation
```bash
# Clone repository
git clone https://github.com/keithaumiller/thetruthperspective.git

# Navigate to Drupal modules directory
cd /path/to/drupal/modules/custom/

# Copy modules
cp -r thetruthperspective/* .

# Enable core modules
drush en news_extractor newsmotivationmetrics ai_conversation social_media_automation
```

### Configuration

#### Required: AWS Bedrock Setup
```bash
# AWS services needed:
- AWS Bedrock with Anthropic Claude model access
- IAM permissions for bedrock:InvokeModel
- Supported regions: us-east-1, us-west-2, eu-west-1
```

#### Optional: Social Media Integration
```bash
# Platform API credentials:
- Mastodon: Instance URL + Access Token (free)
- LinkedIn: Client ID + Client Secret + OAuth (free tier)
- Facebook: App ID + App Secret + Page Token (requires review)
- Twitter: Consumer Key + Secret + OAuth ($100/month required)
```

## 🛠️ Essential Commands

### News Processing
```bash
# Check system status and statistics
drush ne:stats
drush ne:summary

# Process articles from JSON data (primary method)
drush ne:pop-sources

# Process articles from URLs (fallback method)
drush ne:pop-url

# Test extraction for specific URL
drush ne:test https://example.com/article

# Run enhanced cron maintenance with assessment field statistics
sudo -u www-data drush cron

# Watch Drupal logs for detailed processing statistics
drush watchdog:tail --filter=news_extractor
```

### Social Media Automation
```bash
# Check platform status
drush social-media:status

# Test platform connections
drush social-media:test-connections

# Force a post immediately
drush social-media:force-post

# Test content generation
drush social-media:test-content
```

### Logging Management
```bash
# Check current logging configuration and levels
drush ttplog-status

# Production setting - ERROR only (recommended for live environment)
drush ttplog-error

# Debugging setting - INFO level (for troubleshooting)
drush ttplog-info

# Development setting - ALL logs including DEBUG (verbose, development only)
drush ttplog-debug

# Set specific module logging level (1=ERROR, 2=WARNING, 3=INFO, 4=DEBUG)
drush ttplog-set social_media_automation 1
drush ttplog-set news_extractor 3

# Monitor error logs only (production monitoring)
drush watchdog:show --severity=3 --count=50

# Monitor specific module logs
drush watchdog:show --type=news_extractor --count=20

# Clear all logs (useful after changing logging levels)
drush watchdog:delete all
```

## 📈 Production Performance

### Current Statistics
- **Articles Processed**: 135+ articles in production database
- **Processing Rate**: 50+ articles per hour via automated cron
- **Field Coverage**: 20+ specialized analysis fields per article
- **Platform Integration**: 4 social media platforms ready
- **API Integration**: Stable Diffbot and AWS Bedrock connectivity
- **Logging Efficiency**: 95% reduction in log entries (ERROR-only mode)

### System Architecture
- **Backend**: Drupal 11 with modern service architecture
- **AI Processing**: AWS Bedrock Claude 3.5 Sonnet
- **Content Extraction**: Diffbot API for clean article parsing
- **Frontend**: Responsive design with Chart.js visualizations
- **Infrastructure**: Ubuntu 22.04 LTS with MySQL and PHP 8.3
- **Logging**: Centralized logging framework with production-optimized filtering

## 📁 Technical Documentation

- **[News Extractor README](./news_extractor/README.md)** - Content extraction and AI analysis
- **[News Motivation Metrics README](./newsmotivationmetrics/README.md)** - Analytics dashboard
- **[Social Media Automation README](./social_media_automation/README.md)** - Multi-platform automation
- **[AI Conversation README](./ai_conversation/README.md)** - Conversational AI interface
- **[Logging System Documentation](./newsmotivationmetrics/LOGGING_SYSTEM.md)** - Centralized logging framework
- **[ARCHITECTURE.md](./ARCHITECTURE.md)** - Complete technical architecture

## 🔒 Security & Performance

### Security Measures
- Input sanitization and XSS prevention
- SQL injection protection via entity queries
- Proper access controls and permissions
- Secure API credential management

### Performance Features
- Batch processing for memory efficiency
- Database optimization with proper indexing
- Multi-layer caching with TTL management
- Respectful API rate limiting

## 📞 Support

- **Live Platform**: [https://thetruthperspective.org](https://thetruthperspective.org)
- **Repository**: [https://github.com/keithaumiller/thetruthperspective](https://github.com/keithaumiller/thetruthperspective)
- **Issues**: GitHub Issues for bug reports and feature requests
- **Documentation**: Comprehensive module-specific READMEs

## License

GPL-2.0+ (Drupal compatible)

---

**Maintained by**: Keith Aumiller | **Organization**: The Truth Perspective | **Last Updated**: August 21, 2025
