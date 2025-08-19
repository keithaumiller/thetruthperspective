# The Truth Perspective - AI-Powered News Analysis Platform

**Version**: 1.3.0  
**Last Updated**: August 19, 2025  
**Drupal Version**: 11.x  
**PHP Version**: 8.3.6+  
**Status**: Production Ready

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

## 📈 Production Performance

### Current Statistics
- **Articles Processed**: 135+ articles in production database
- **Processing Rate**: 50+ articles per hour via automated cron
- **Field Coverage**: 20+ specialized analysis fields per article
- **Platform Integration**: 4 social media platforms ready
- **API Integration**: Stable Diffbot and AWS Bedrock connectivity

### System Architecture
- **Backend**: Drupal 11 with modern service architecture
- **AI Processing**: AWS Bedrock Claude 3.5 Sonnet
- **Content Extraction**: Diffbot API for clean article parsing
- **Frontend**: Responsive design with Chart.js visualizations
- **Infrastructure**: Ubuntu 22.04 LTS with MySQL and PHP 8.3

## 📁 Technical Documentation

- **[News Extractor README](./news_extractor/README.md)** - Content extraction and AI analysis
- **[News Motivation Metrics README](./newsmotivationmetrics/README.md)** - Analytics dashboard
- **[Social Media Automation README](./social_media_automation/README.md)** - Multi-platform automation
- **[AI Conversation README](./ai_conversation/README.md)** - Conversational AI interface
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

**Maintained by**: Keith Aumiller | **Organization**: The Truth Perspective | **Last Updated**: August 19, 2025
