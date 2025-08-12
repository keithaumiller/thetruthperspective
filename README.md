# The Truth Perspective - Drupal Modules

**Version**: 1.1.1  
**Last Updated**: August 12, 2025  
**Drupal Version**: 9.x / 10.x / 11.x  
**PHP Version**: 7.4+ / 8.1+  
**Status**: Production Ready

## Overview

This repository contains custom Drupal modules for The Truth Perspective website, providing AI-powered content processing, conversational interfaces, automated workflows, and comprehensive key metric analysis. All modules are production-tested and actively deployed.

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

**✅ Current Status**: Stable production version with enhanced news source data flow architecture.

**Core Features**:
- URL-based content extraction with AI analysis
- AWS Bedrock integration for content processing
- Multi-stage news source population from feed data, JSON scraped data, and URL extraction
- Motivation data extraction and entity analysis
- Custom article templates with structured data
- Comprehensive field_news_source population system

**Recent Updates**: 
- Enhanced news source extraction with 4-stage fallback system
- JSON scraped data processing for Diffbot siteName field
- Improved domain mapping for standardized source names
- Cron-based maintenance for missing news sources

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

## 🔧 Development Status

### ✅ Production Ready
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

**Maintained by**: Keith Aumiller | **Organization**: The Truth Perspective | **Last Updated**: August 12, 2025
