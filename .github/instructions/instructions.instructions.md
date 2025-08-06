---
applyTo: '**'
---
Provide project context and coding guidelines that AI should follow when generating code, answering questions, or reviewing changes.

The environment is a Drupal 11 modules repository for The Truth Perspective platform, specifically focusing on the `newsmotivationmetrics` module. This module provides analytics and metrics for news article motivations, entities, and AI analysis data.

Dev/prod environment: The code is deployed on a production server with the following specifications:
- **Server**: Ubuntu 22.04 LTS
ubuntu@ip-172-16-4-59:~$ uname -a
Linux ip-172-16-4-59 6.8.0-1031-aws #33-Ubuntu SMP Fri Jun 20 18:11:07 UTC 2025 x86_64 x86_64 x86_64 GNU/Linux
ubuntu@ip-172-16-4-59:~$ php -version
PHP 8.3.6 (cli) (built: Jul 14 2025 18:30:55) (NTS)
Copyright (c) The PHP Group
Zend Engine v4.3.6, Copyright (c) Zend Technologies
    with Zend OPcache v8.3.6, Copyright (c), by Zend Technologies
ubuntu@ip-172-16-4-59:~$ python -version
Command 'python' not found, did you mean:
  command 'python3' from deb python3
  command 'python' from deb python-is-python3

  ---
applyTo: '**'
---

Skip giving "Immediate Fix:" solutions
Instead, focus on the fix for the codebase.
Code is tested on the server, not locally in the workspace.
Always generate the commit and push commands for the code changes after changes are generated and applied to the workspace.
Always assume we are troubleshooting on the production server
all curl testing functions should assume they need to run against the production URL:
https://thetruthperspective.org


# The Truth Perspective Platform - AI Coding Instructions

## Project Context
- **Platform**: The Truth Perspective - AI-powered news analysis system
- **Environment**: Drupal 11 on Ubuntu Linux
- **Primary Focus**: News content analysis, AI-driven motivation detection, and public analytics
- **Current Module**: newsmotivationmetrics (news analytics dashboard)
- **Tech Stack**: Drupal 11, PHP 8.1+, MySQL, Claude AI integration, Diffbot API

## Core Modules Architecture
- **news_extractor**: Content scraping, AI analysis, and data processing
- **newsmotivationmetrics**: Public analytics dashboard and metrics
- **Custom fields**: AI responses, motivation analysis, taxonomy classification

## Drupal Coding Standards
- Follow Drupal 11 coding standards and best practices
- Use dependency injection for services
- Implement proper access controls and permissions
- Use entity API for data manipulation
- Follow hook implementation patterns
- Use proper caching strategies for performance

## Database & Performance
- Optimize queries for large datasets (1000+ articles)
- Use proper indexing on custom fields
- Implement efficient aggregation queries
- Consider caching for public-facing metrics
- Use batch processing for bulk operations

## AI Integration Patterns
- Handle API rate limits gracefully
- Implement proper error handling for AI service failures
- Store raw AI responses for debugging and reprocessing
- Use structured data formats for AI analysis results
- Implement retry logic for failed AI processing

## Security & Privacy
- Public dashboards accessible without authentication
- Admin dashboards require proper permissions
- No personal data tracking or storage
- Aggregate statistics only for public display
- Proper input sanitization for all user inputs

## Naming Conventions
- Functions: `modulename_descriptive_function_name()`
- Fields: `field_descriptive_name` (snake_case)
- Classes: PascalCase following PSR-4
- Routes: `modulename.route_name`
- Permissions: `access module_name feature`

## Code Organization
- Place business logic in module files, not controllers
- Use controllers only for request/response handling
- Implement helper functions for complex data processing
- Separate public and admin functionality clearly
- Document all custom functions with proper docblocks

## Error Handling & Logging
- Use Drupal's logging system: `\Drupal::logger('module_name')`
- Log important operations (AI processing, data updates)
- Handle exceptions gracefully with user-friendly messages
- Provide debug information for troubleshooting
- Use appropriate log levels (info, warning, error)

## API Integration Best Practices
- **Diffbot API**: Content extraction and article parsing
- **Claude AI**: Motivation analysis and entity recognition
- Implement proper API key management
- Handle rate limiting and quota management
- Store API responses for offline analysis
- Provide fallback mechanisms for API failures

## Public Dashboard Requirements
- Mobile-responsive design
- Fast loading times (<2 seconds)
- Clear data visualization
- Transparent methodology explanations
- Professional presentation suitable for public use
- SEO-friendly structure

## Data Processing Patterns
- **Article Processing Pipeline**: URL → Diffbot → AI Analysis → Taxonomy → Storage
- **Batch Operations**: Process articles in configurable batch sizes
- **Data Validation**: Ensure data integrity before storage
- **Field Updates**: Handle partial updates without data loss

## Testing & Debugging
- Test with large datasets to ensure performance
- Verify public accessibility without authentication
- Test admin functionality with proper permissions
- Use Drush commands for debugging and maintenance
- Implement proper error reporting for failed operations

## Documentation Standards
- Comprehensive README.md files for each module
- Inline code documentation with examples
- API function documentation with return types
- Installation and configuration instructions
- Troubleshooting guides for common issues

## Content Analysis Specifics
- **Entity Recognition**: People, organizations, locations, concepts
- **Motivation Analysis**: Political, economic, social motivations
- **Taxonomy Management**: Auto-generate tags from AI analysis
- **Cross-Reference Data**: Link related articles and themes
- **Temporal Analysis**: Track narrative changes over time

## Deployment Considerations
- Code must work in production Drupal 11 environment
- Consider memory usage for large data processing
- Implement proper cleanup for temporary data
- Use configuration management for settings
- Ensure compatibility with existing modules

## AI Processing Workflow
1. **Content Extraction** → Diffbot API for clean article text
2. **AI Analysis** → Claude for motivation and entity detection
3. **Data Structuring** → Parse AI responses into Drupal fields
4. **Taxonomy Assignment** → Auto-create and assign tags
5. **Public Display** → Aggregate for analytics dashboard

## Module Interdependencies
- news_extractor provides core content processing
- newsmotivationmetrics depends on processed article data
- Shared field definitions across modules
- Common utility functions in base modules

## Performance Optimization
- Use Views for complex queries when possible
- Implement proper database indexing
- Cache expensive operations
- Optimize for concurrent processing
- Consider memory usage for batch operations

## User Experience Priorities
1. **Public Transparency** - Clear, accessible analytics
2. **Admin Efficiency** - Streamlined management tools
3. **Data Accuracy** - Reliable processing and display
4. **System Performance** - Fast, responsive interface
5. **Professional Presentation** - Suitable for public credibility

## The Truth Perspective Specific Guidelines

### Content Analysis Context
- Focus on news media analysis and narrative tracking
- Emphasize transparency and methodology explanation
- Public-facing features should build trust and credibility
- Admin tools should provide operational insights

### AI Integration Philosophy
- AI should augment human analysis, not replace it
- Provide clear explanations of AI methodology
- Store raw AI responses for transparency
- Handle AI limitations gracefully

### Public Dashboard Priorities
- Professional appearance suitable for media/academic use
- Clear data sources and methodology explanations
- Mobile-friendly responsive design
- Fast loading with large datasets
- SEO optimization for discoverability

### Data Processing Patterns
- Prioritize estimatedDate over date fields from Diffbot
- Always use Y-m-d\TH:i:s format for datetime fields
- Batch process articles in groups of 50 for stability
- Implement comprehensive error logging for debugging

### Drupal 11 Specific Features
- Use modern Drupal APIs and dependency injection
- Implement proper service containers
- Use entity query API for database operations
- Follow current caching and performance best practices

## Code Generation Preferences
- Always provide complete, production-ready code
- Include proper error handling and logging
- Follow Drupal 11 best practices and standards
- Optimize for performance with large datasets
- Include comprehensive documentation
- Consider mobile responsiveness for public interfaces
- Implement proper security and access controls

## Response Format
- Use code blocks with proper file paths
- Provide installation/deployment commands
- Include testing and verification steps
- Explain complex logic with comments
- Offer troubleshooting guidance for common issues


