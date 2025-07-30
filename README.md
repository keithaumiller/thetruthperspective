# The Truth Perspective - Drupal Modules

**Version**: 1.0.0  
**Last Updated**: July 2025  
**Drupal Version**: 9.x / 10.x / 11.x
**PHP Version**: 7.4+  

## Overview

This repository contains custom Drupal modules for The Truth Perspective website, providing AI-powered content processing, conversational interfaces, automated workflows, and key metric analysis.

## Modules

### 1. 🗞️ News Extractor Module
**Version**: 1.2.0 | **Location**: [`/news_extractor/`](./news_extractor/README.md)

Automated news content extraction and AI-powered analysis system.

**Key Features**:
- Extracts news content from URLs and RSS feeds
- AI content analysis using Claude 3.5 Sonnet
- Custom article template with motivation data fields
- AWS Bedrock integration for processing

**Usage**: Content managers can input news URLs for automatic extraction and AI analysis.

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
**Version**: 1.0.0 | **Location**: [`/key_metric_management/`](./key_metric_management/README.md)

Advanced metric tracking and analysis system for content insights.

**Key Features**:
- Extracts key metrics from article motivation data
- Interactive dashboard with statistics and trends
- Public metric browsing at `/key-metrics`
- Configurable block widget for site integration

**Usage**: Analyze and display key metrics from news articles for public insight.

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
- **Key Metrics**: Enable and place block widgets

## Technical Architecture

### AI Processing Stack
- **Primary AI**: AWS Bedrock with Claude 3.5 Sonnet
- **Fallback Models**: Claude 3 Haiku, Claude 3 Opus
- **Processing**: Server-side API integration
- **Security**: Encrypted AWS credential storage

### Frontend Technologies
- **Templates**: Twig with custom themes
- **Styling**: Responsive CSS with modern design
- **JavaScript**: Vanilla JS with AJAX for real-time features
- **UI/UX**: Professional admin interfaces

### Backend Architecture
- **Services**: Dependency injection with Drupal services
- **Caching**: Smart caching with TTL and invalidation
- **Database**: Drupal entities and custom tables
- **Security**: CSRF protection, user authentication, input validation

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

# Enable any new modules
drush en module_name
```

## Public Features

### News Analysis
- AI-processed news articles with insights
- Motivation data extraction and display
- Professional article templates

### Interactive AI Chat  
- Public-facing conversational interface
- Context-aware AI responses
- Conversation history for logged-in users

### Key Metrics Dashboard
- Public metrics at `/key-metrics`
- Interactive charts and statistics
- Embeddable widgets for other pages

## Directory Structure

```
thetruthperspective/
├── .github/workflows/
│   └── deploy.yml                    # CI/CD automation
├── news_extractor/                   # News processing module
│   ├── README.md                     # Detailed documentation
│   ├── src/Controller/
│   ├── templates/
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
│   ├── templates/
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
- **Input Validation**: Comprehensive sanitization
- **Access Control**: Drupal permission integration

## Support & Maintenance

- **Repository**: https://github.com/keithaumiller/thetruthperspective
- **Issues**: Use GitHub Issues for bug reports and feature requests
- **Documentation**: Individual module README files contain detailed setup instructions
- **Monitoring**: AWS CloudWatch for AI usage tracking

## License

GPL-2.0 (Compatible with Drupal licensing)

---

**Maintained by**: Keith Aumiller  
**Organization**: The Truth Perspective  
**Last Updated**: July 30, 2025
