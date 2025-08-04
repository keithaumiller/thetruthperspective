# News Extractor Module

**Version**: 1.3.0  
**Status**: Production Ready  
**Drupal Version**: 9.x / 10.x / 11.x  
**PHP Version**: 7.4+ / 8.1+  
**Last Updated**: August 2025

A comprehensive Drupal module that automatically extracts full article content using the Diffbot API and generates AI-powered motivation analysis with entity-motivation pairing for political and news content.

## 🚀 Production Features

### ✅ **Automated Content Extraction**
- **Diffbot API Integration**: Extracts full article text, title, and metadata from news URLs
- **Smart Content Detection**: Filters out ads, podcasts, videos, and non-article content
- **Bulk Processing**: Processes articles missing body content via Drush commands
- **Error Handling**: Comprehensive logging and graceful failure recovery

### ✅ **AI-Powered Analysis** 
- **AWS Bedrock Integration**: Uses Claude 3.5 Sonnet for sophisticated content analysis
- **Entity Recognition**: Identifies people, organizations, and institutions mentioned
- **Motivation Mapping**: Assigns psychological motivations from curated list
- **Key Metric Identification**: Determines relevant US performance metrics
- **Structured JSON Output**: Machine-readable data extraction

### ✅ **Multi-Format Data Storage**
- **Raw AI Response**: Complete unprocessed Claude output for debugging
- **Structured JSON**: Parsed data for programmatic access
- **Formatted HTML**: Human-readable analysis for display
- **Taxonomy Integration**: Auto-generated tags for site navigation

## 📊 Field Architecture

### **Drupal Node Fields Used:**

| Field | Machine Name | Type | Content | Purpose |
|-------|--------------|------|---------|---------|
| **AI Raw Response** | `field_ai_raw_response` | Text (long) | **Raw JSON from Claude** | **Debugging & audit trail** |
| **AI Summary** | `field_ai_summary` | Text (long) | Raw JSON (backup) | Backward compatibility |
| **Motivation Data** | `field_motivation_data` | Text (long) | Parsed JSON structure | Machine processing |
| **Motivation Analysis** | `field_motivation_analysis` | Text (formatted) | HTML display format | User interface |
| **Tags** | `field_tags` | Entity reference | Taxonomy terms | Site navigation |
| **Original URL** | `field_original_url` | Link | Source article URL | Content attribution |
| **Body** | `body` | Text (formatted) | Extracted article text | Main content |

## 🎯 AI Analysis Format

### **Input to Claude:**
```
As a social scientist, analyze this article in the context of key performance metrics for the United States.

Instructions:
1. Identify each entity (person, organization, institution) mentioned
2. For each entity, select their top 2-3 motivations from the allowed list
3. Choose the most relevant US performance metric this article impacts
4. Provide analysis of how this affects that metric

Return response as valid JSON:
{
  "entities": [
    {
      "name": "Entity Name",
      "motivations": ["Motivation1", "Motivation2", "Motivation3"]
    }
  ],
  "key_metric": "Specific Metric Name",
  "analysis": "As a social scientist, I analyze that [detailed analysis]."
}
```

### **Output Processing:**
1. **Raw JSON stored** in `field_ai_raw_response` for debugging
2. **Structured data extracted** and stored in `field_motivation_data`
3. **HTML formatted** for display in `field_motivation_analysis`
4. **Taxonomy tags created** from entities, motivations, and metrics
5. **Site navigation enabled** through automatic tag generation

## 🔧 Technical Implementation

### **Core Functions:**

#### **Content Extraction**
```php
_news_extractor_extract_content($entity, $url)
```
- **Diffbot API**: Extracts full article content
- **AI Processing**: Generates motivation analysis
- **Multi-field Storage**: Saves data in appropriate formats
- **Tag Generation**: Creates taxonomy terms for navigation

#### **AI Integration**
```php
_news_extractor_generate_ai_summary($article_text, $article_title)
```
- **AWS Bedrock**: Claude 3.5 Sonnet integration
- **JSON Prompt**: Structured response format
- **Raw Response**: Returns unprocessed AI output
- **Error Handling**: Graceful failure with logging

#### **Data Processing**
```php
_news_extractor_extract_structured_data($ai_response)
_news_extractor_extract_tags_from_summary($structured_data)
news_extractor_format_json_analysis($structured_data)
```
- **JSON Parsing**: Extracts structured data from AI response
- **Tag Extraction**: Creates browsable taxonomy terms
- **HTML Formatting**: User-friendly display formatting

### **Utility Functions**

#### **Content Validation**
```php
_news_extractor_is_article_url($url)
```
- **Domain Filtering**: Blocks ads, financial content, promotions
- **Pattern Detection**: Identifies actual news articles
- **Content Type**: Filters out podcasts, videos, galleries

#### **Bulk Operations**
```php
news_extractor_update_articles_missing_body_from_diffbot()
```
- **Batch Processing**: Updates articles without body content
- **Drush Integration**: Command-line execution
- **Progress Tracking**: Comprehensive logging

## 🛠️ Installation & Configuration

### **Dependencies**
```bash
# Required services
- Diffbot API account and token
- AWS Bedrock access with Claude models
- Drupal 9.x/10.x/11.x
- PHP 7.4+ or 8.1+
```

### **Field Setup**
1. **Article Content Type** must have these fields:
   - `field_ai_raw_response` (Text long) - **NEW FIELD REQUIRED**
   - `field_ai_summary` (Text long)
   - `field_motivation_data` (Text long)
   - `field_motivation_analysis` (Text formatted)
   - `field_original_url` (Link)
   - `field_tags` (Entity reference to Tags taxonomy)

2. **Create the new field**:
```bash
# Via Drush
drush field:create node.article.field_ai_raw_response text_long --field-label="AI Raw Response"

# Via Admin UI
/admin/structure/types/manage/article/fields
```

### **Configuration**
1. **Diffbot API Token**: Set in module configuration
2. **AWS Credentials**: Configure Bedrock access
3. **Motivation List**: Customize allowed motivations if needed
4. **Processing Rules**: Configure content filtering patterns

## 🚀 Usage

### **Automatic Processing**
- **Node Creation**: Articles with URLs automatically processed
- **Node Updates**: Re-processes when URL changes
- **Cron Integration**: Background processing of pending articles

### **Manual Processing**
```bash
# Process articles missing body content
drush news-extractor:update-missing-body

# Debug motivation analysis
drush news-extractor:debug-analysis

# Test formatting updates
drush news-extractor:test-update
```

### **Content Workflow**
1. **Create Article Node** with `field_original_url`
2. **Diffbot Extraction** pulls full article content
3. **AI Analysis** generates motivation data
4. **Multi-format Storage** preserves raw and processed data
5. **Tag Generation** enables site navigation
6. **Display Formatting** provides user-friendly output

## 📈 Data Flow Architecture

### **Processing Pipeline:**
```
Article URL → Diffbot API → Full Content → Claude AI → Raw JSON
     ↓
Raw JSON → field_ai_raw_response (debugging)
     ↓
Parse JSON → field_motivation_data (machine processing)
     ↓
Extract Tags → field_tags (site navigation)
     ↓
Format HTML → field_motivation_analysis (user display)
```

### **Storage Strategy:**
- **Raw Preservation**: Complete AI responses for debugging
- **Structured Access**: JSON data for programmatic use
- **Human Display**: Formatted HTML for user interface
- **Navigation Integration**: Taxonomy terms for browsing

## 🔍 Debugging & Monitoring

### **Logging Levels:**
- **Info**: Successful processing and statistics
- **Warning**: Content extraction issues
- **Error**: API failures and processing errors

### **Debug Functions:**
```php
news_extractor_debug_newest_motivation_analysis()
news_extractor_debug_motivation_analysis_formatting()
news_extractor_test_update()
```

### **Raw Data Access:**
- **field_ai_raw_response**: Complete Claude responses
- **Drupal Logs**: Detailed processing information
- **Performance Metrics**: Character counts and processing times

## 🛡️ Error Handling

### **Graceful Degradation:**
- **API Failures**: Logged with retry capability
- **JSON Parsing**: Fallback to raw text storage
- **Missing Fields**: Safe field existence checking
- **Content Validation**: Comprehensive URL filtering

### **Data Recovery:**
- **Raw AI responses** preserved for reprocessing
- **Multiple storage formats** provide backup options
- **Audit trails** enable troubleshooting
- **Manual reprocessing** commands available

## 📊 Performance Metrics

### **Processing Statistics:**
- **Content Extraction**: ~30 second timeout per article
- **AI Analysis**: Variable based on article length
- **Data Storage**: Multi-field preservation
- **Tag Generation**: Automatic taxonomy integration

### **Storage Efficiency:**
- **Raw JSON**: Complete debugging capability
- **Structured Data**: Optimized for queries
- **Formatted HTML**: Ready for display
- **Taxonomy Terms**: Efficient navigation

## 🔧 Development & Maintenance

### **File Structure:**
```
news_extractor/
├── news_extractor.module          # Core Drupal hooks and AI functions
├── news_extractor.scraper.php     # Content extraction and formatting
├── src/Form/                      # Configuration forms
├── templates/                     # Display templates
└── README.md                      # This documentation
```

### **Key Integration Points:**
- **Drupal Hooks**: Node create/update triggers
- **AWS Bedrock**: Claude 3.5 Sonnet API
- **Diffbot API**: Content extraction service
- **Taxonomy System**: Automatic tag generation

### **Future Enhancements:**
- **Multiple AI Models**: Support for different analysis types
- **Batch Processing UI**: Admin interface for bulk operations
- **Custom Motivation Lists**: Per-content-type configurations
- **Analytics Dashboard**: Processing statistics and insights

## Version History

### **v1.3.0 (August 2025) - CURRENT**
- ✅ **Raw AI Response Storage**: New `field_ai_raw_response` field
- ✅ **Enhanced JSON Processing**: Structured data extraction
- ✅ **Improved Error Handling**: Comprehensive logging and fallbacks
- ✅ **Multi-format Storage**: Raw, structured, and formatted data
- ✅ **Production Stability**: Robust field validation and processing

### **v1.2.0 (July 2025)**
- AI integration with AWS Bedrock and Claude
- Structured JSON response processing
- Automatic taxonomy tag generation
- Enhanced content filtering

### **v1.1.0 (June 2025)**
- Diffbot API integration
- Basic content extraction
- Article validation and filtering

### **v1.0.0 (Initial Release)**
- Core module structure
- Basic content processing framework

## Support & Documentation

- **Module Status**: Production ready with comprehensive error handling
- **AI Integration**: AWS Bedrock with Claude 3.5 Sonnet
- **Data Preservation**: Complete audit trail from raw to processed
- **Field Requirements**: New `field_ai_raw_response` field must be created
- **Debug Capability**: Raw AI responses available for troubleshooting

---

**✅ Production Ready**: Comprehensive content extraction and AI analysis  
**🔧 Field Setup Required**: Create `field_ai_raw_response` for full functionality  
**📊 Multi-format Storage**: Raw, structured, and formatted data preservation  
**🛡️ Error Recovery**: Complete debugging and reprocessing capability  

**Maintained by**: Keith Aumiller | **Last Updated**: August 4, 2025
