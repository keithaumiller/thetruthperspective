# The Truth Perspective - Drupal Modules 

**Version**: 1.0.0  
**Last Updated**: July 2025  
**Drupal Version**: 9.x / 10.x  
**PHP Version**: 7.4+  

## Overview

This repository contains custom Drupal modules for The Truth Perspective website, providing AI-powered content processing and conversational interfaces.

## Modules

### 1. News Extractor Module
**Version**: 1.2.0  
**Location**: `/news_extractor/`  
**Purpose**: Extracts and processes news content using AI analysis

**Features**:
- Automated news content extraction
- AI-powered content analysis
- AWS Bedrock integration
- Claude 3.5 Sonnet processing

### 2. AI Conversation Module  
**Version**: 1.0.0  
**Location**: `/ai_conversation/`  
**Purpose**: Provides conversational AI interface for authenticated users

**Features**:
- Persistent AI conversations
- Real-time chat interface
- Multiple Claude model support
- AWS Bedrock integration
- Context-aware responses

## Technical Stack

- **Backend**: Drupal 9.x/10.x, PHP 7.4+
- **AI Provider**: AWS Bedrock (Anthropic Claude)
- **Default Model**: `anthropic.claude-3-5-sonnet-20240620-v1:0`
- **Frontend**: JavaScript, CSS, Twig templates
- **Deployment**: GitHub Actions with SSH deployment

## Installation

1. **Clone the repository**:
   ```bash
   git clone https://github.com/keithaumiller/thetruthperspective.git
   ```

2. **Enable modules**:
   ```bash
   drush en news_extractor ai_conversation
   ```

3. **Configure AWS credentials** in each module's settings page

## Configuration

### News Extractor
- Configure at: `/admin/config/news-extractor`
- Requires: AWS Bedrock access, IAM permissions

### AI Conversation
- Configure at: `/admin/config/ai-conversation`
- Requires: AWS Bedrock access, IAM permissions

## AWS Requirements

Both modules require:
- AWS account with Bedrock access
- IAM user with `bedrock:InvokeModel` permissions
- Access to Claude models in AWS Bedrock
- Supported regions: us-east-1, us-west-2, eu-west-1

## Deployment

Automated deployment via GitHub Actions:
- **Trigger**: Push to `main` branch
- **Target**: Custom modules directory on server
- **Process**: Git pull + file deployment + cache clear

## Directory Structure

```
thetruthperspective/
├── .github/workflows/
│   └── deploy.yml              # Automated deployment
├── news_extractor/
│   ├── news_extractor.info.yml
│   ├── news_extractor.module
│   └── [other module files]
├── ai_conversation/
│   ├── ai_conversation.info.yml
│   ├── ai_conversation.module
│   ├── src/
│   │   ├── Controller/
│   │   ├── Service/
│   │   └── Form/
│   ├── templates/
│   ├── css/
│   └── js/
└── README.md                   # This file
```

## Version History

### v1.0.0 (July 2025)
- Initial release of AI Conversation module
- AWS Bedrock integration for both modules
- Automated deployment pipeline
- Updated News Extractor to use Bedrock

### News Extractor Changelog
- **v1.2.0**: AWS Bedrock integration
- **v1.1.0**: Enhanced content processing
- **v1.0.0**: Initial release

### AI Conversation Changelog
- **v1.0.0**: Initial release with AWS Bedrock support

## Security

- AWS credentials stored in Drupal configuration
- User-specific conversation access
- CSRF protection on all AJAX requests
- Server-side API key handling

## Maintenance

- **Monitoring**: Check AWS CloudWatch for Bedrock usage
- **Updates**: Monitor for new Claude model releases
- **Backups**: Regular Drupal database backups recommended
- **Logs**: Check `/admin/reports/dblog` for module errors

## Support

- **Repository**: https://github.com/keithaumiller/thetruthperspective
- **Issues**: Use GitHub Issues for bug reports
- **Documentation**: See individual module README files

## License

GPL-2.0 (Compatible with Drupal licensing)

---

**Maintained by**: Keith Aumiller  
**Organization**: The Truth Perspective  
**Contact**: [Contact information]
