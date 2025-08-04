# News Extractor Module

**Version**: 1.4.0  
**Status**: Production Ready with Reprocessing Capability  
**Drupal Version**: 9.x / 10.x / 11.x  
**PHP Version**: 7.4+ / 8.1+  
**Last Updated**: August 2025

A comprehensive Drupal module that automatically extracts full article content using the Diffbot API and generates AI-powered motivation analysis with entity-motivation pairing for political and news content. Now includes advanced media assessment capabilities and cost-effective reprocessing from stored AI responses.

## 🚀 Production Features

### ✅ **Automated Content Extraction**
- **Diffbot API Integration**: Extracts full article text, title, and metadata from news URLs
- **Smart Content Detection**: Filters out ads, podcasts, videos, and non-article content
- **Bulk Processing**: Processes articles missing body content via Drush commands
- **Error Handling**: Comprehensive logging and graceful failure recovery

### ✅ **AI-Powered Comprehensive Analysis** 
- **AWS Bedrock Integration**: Uses Claude 3.5 Sonnet for sophisticated content analysis
- **Entity Recognition**: Identifies people, organizations, and institutions mentioned
- **Motivation Mapping**: Assigns psychological motivations from curated list
- **Key Metric Identification**: Determines relevant US performance metrics
- **Media Assessment**: Credibility scoring, bias rating, sentiment analysis
- **Structured JSON Output**: Machine-readable data extraction

### ✅ **Advanced Media Literacy Assessment**
- **Credibility Score**: 0-100 scale assessing information reliability
- **Political Bias Rating**: 0-100 scale from Extreme Left to Extreme Right
- **Bias Analysis**: Detailed explanation of bias assessment reasoning
- **Sentiment Score**: 0-100 emotional tone analysis
- **Comprehensive Evaluation**: Multi-dimensional content assessment

### ✅ **Multi-Format Data Storage & Reprocessing**
- **Raw AI Response**: Complete unprocessed Claude output for debugging and reprocessing
- **Structured JSON**: Parsed data for programmatic access
- **Individual Assessment Fields**: Dedicated storage for scores and analysis
- **Formatted HTML**: Human-readable analysis for display
- **Taxonomy Integration**: Auto-generated tags for site navigation
- **Cost-Effective Reprocessing**: Update fields from stored data without new AI calls

## 📊 Field Architecture

### **Drupal Node Fields Used:**

| Field | Machine Name | Type | Content | Purpose |
|-------|--------------|------|---------|---------|
| **AI Raw Response** | `field_ai_raw_response` | Text (long) | **Raw JSON from Claude** | **Debugging, audit, reprocessing** |
| **AI Summary** | `field_ai_summary` | Text (long) | Raw JSON (backup) | Backward compatibility |
| **Motivation Data** | `field_motivation_data` | Text (long) | Parsed JSON structure | Machine processing |
| **Motivation Analysis** | `field_motivation_analysis` | Text (formatted) | HTML display format | User interface |
| **Credibility Score** | `field_credibility_score` | Text (plain) | "0" to "100" | Information reliability |
| **Bias Rating** | `field_bias_rating` | Text (plain) | "0" to "100" | Political lean assessment |
| **Bias Analysis** | `field_bias_analysis` | Text (plain, LENGTH MUST BE TEXT NOT VARCHAR) | Explanation text | Bias reasoning |
| **Sentiment Score** | `field_article_sentiment_score` | Text (plain) | "0" to "100" | Emotional tone |
| **Tags** | `field_tags` | Entity reference | Taxonomy terms | Site navigation |
| **Original URL** | `field_original_url` | Link | Source article URL | Content attribution |
| **Body** | `body` | Text (formatted) | Extracted article text | Main content |

## 🎯 Enhanced AI Analysis Format

### **Input to Claude:**
```
As a social scientist, analyze this article comprehensively for both content analysis and media assessment.

Instructions:
1. Identify each entity (person, organization, institution) mentioned
2. For each entity, select their top 2-3 motivations from the allowed list
3. Choose the most relevant US performance metric this article impacts
4. Provide analysis of how this affects that metric
5. Assess the article's credibility, bias, and sentiment

CREDIBILITY SCORING (0-100):
- 0-20: Intentional deceit, false information, propaganda
- 21-40: Highly questionable sources, unverified claims
- 41-60: Mixed reliability, some factual issues
- 61-80: Generally reliable with minor issues
- 81-100: Highly credible, well-sourced, factual

BIAS RATING (0-100):
- 0-20: Extreme Left
- 21-40: Lean Left
- 41-60: Center
- 61-80: Lean Right
- 81-100: Extreme Right

SENTIMENT SCORING (0-100):
- 0-20: Very negative, doom, crisis
- 21-40: Negative, critical, pessimistic
- 41-60: Neutral, balanced reporting
- 61-80: Positive, optimistic, hopeful
- 81-100: Very positive, celebratory, triumphant

Return response as valid JSON:
{
  "entities": [
    {
      "name": "Entity Name",
      "motivations": ["Motivation1", "Motivation2", "Motivation3"]
    }
  ],
  "key_metric": "Specific Metric Name",
  "analysis": "As a social scientist, I analyze that [detailed analysis].",
  "credibility_score": 75,
  "bias_rating": 45,
  "bias_analysis": "Two-line explanation of bias assessment reasoning.",
  "sentiment_score": 35
}
```

### **Output Processing:**
1. **Raw JSON stored** in `field_ai_raw_response` for debugging and reprocessing
2. **Individual scores stored** in dedicated assessment fields
3. **Structured data extracted** and stored in `field_motivation_data`
4. **HTML formatted** for display in `field_motivation_analysis`
5. **Taxonomy tags created** from entities, motivations, and metrics
6. **Site navigation enabled** through automatic tag generation

## 🔧 Technical Implementation

### **Core Functions:**

#### **Content Extraction & Processing**
```php
_news_extractor_extract_content($entity, $url)
```
- **Diffbot API**: Extracts full article content
- **AI Processing**: Generates comprehensive motivation and media analysis
- **Multi-field Storage**: Saves data in appropriate formats
- **Assessment Storage**: Individual fields for credibility, bias, sentiment
- **Tag Generation**: Creates taxonomy terms for navigation

#### **AI Integration**
```php
_news_extractor_generate_ai_summary($article_text, $article_title)
```
- **AWS Bedrock**: Claude 3.5 Sonnet integration
- **Enhanced JSON Prompt**: Comprehensive assessment format
- **Raw Response**: Returns unprocessed AI output
- **Error Handling**: Graceful failure with logging

#### **Data Processing**
```php
_news_extractor_extract_structured_data($ai_response)
_news_extractor_extract_tags_from_summary($structured_data)
news_extractor_format_json_analysis($structured_data)
```
- **Enhanced JSON Parsing**: Extracts assessment scores and analysis
- **Tag Extraction**: Creates browsable taxonomy terms
- **HTML Formatting**: User-friendly display with assessment data

#### **🆕 Reprocessing Capabilities**
```php
news_extractor_reprocess_node_from_raw_response($nid)
news_extractor_bulk_reprocess_from_raw_responses($limit)
```
- **Cost-Effective Updates**: Reprocess from stored AI responses
- **No API Calls**: Update fields without calling Claude again
- **Bulk Operations**: Process hundreds of articles efficiently
- **Error Recovery**: Fix parsing issues using stored raw data

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
   - `field_ai_raw_response` (Text long) - **REQUIRED FOR REPROCESSING**
   - `field_ai_summary` (Text long)
   - `field_motivation_data` (Text long)
   - `field_motivation_analysis` (Text formatted)
   - `field_credibility_score` (Text plain) - **NEW ASSESSMENT FIELD**
   - `field_bias_rating` (Text plain) - **NEW ASSESSMENT FIELD**
   - `field_bias_analysis` (Text plain, LENGTH MUST BE TEXT NOT VARCHAR) - **NEW ASSESSMENT FIELD**
   - `field_article_sentiment_score` (Text plain) - **NEW ASSESSMENT FIELD**
   - `field_original_url` (Link)
   - `field_tags` (Entity reference to Tags taxonomy)

2. **Create required fields**:
```bash
# Via Drush
drush field:create node.article.field_ai_raw_response text_long --field-label="AI Raw Response"

# Via Admin UI
/admin/structure/types/manage/article/fields
```

3. **⚠️ CRITICAL: Field Length Requirements**:
```sql
# Ensure bias_analysis field can handle long text
ALTER TABLE node__field_bias_analysis MODIFY field_bias_analysis_value TEXT;
ALTER TABLE node_revision__field_bias_analysis MODIFY field_bias_analysis_value TEXT;
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
- **Assessment Integration**: All media literacy scores generated automatically

### **Manual Processing**
```bash
# Process articles missing body content
drush news-extractor:update-missing-body

# 🆕 Reprocess from stored AI responses (no API costs)
drush news-extractor:reprocess-from-raw --limit=100

# Reprocess specific article
drush news-extractor:reprocess-from-raw --nid=2530

# Debug motivation analysis
drush news-extractor:debug-analysis

# Test formatting updates
drush news-extractor:test-update
```

### **Content Workflow**
1. **Create Article Node** with `field_original_url`
2. **Diffbot Extraction** pulls full article content
3. **AI Analysis** generates comprehensive assessment data
4. **Multi-format Storage** preserves raw and processed data
5. **Assessment Storage** saves credibility, bias, sentiment scores
6. **Tag Generation** enables site navigation
7. **Display Formatting** provides user-friendly output

## 📈 Enhanced Data Flow Architecture

### **Processing Pipeline:**
```
Article URL → Diffbot API → Full Content → Claude AI → Raw JSON
     ↓
Raw JSON → field_ai_raw_response (debugging/reprocessing)
     ↓
Parse JSON → Structured Data (entities, motivations, assessments)
     ↓
Individual Fields:
├── field_credibility_score (reliability assessment)
├── field_bias_rating (political lean score)
├── field_bias_analysis (bias reasoning)
├── field_article_sentiment_score (emotional tone)
├── field_motivation_data (machine processing)
└── field_tags (site navigation)
     ↓
Format HTML → field_motivation_analysis (user display)
```

### **🆕 Reprocessing Pipeline:**
```
field_ai_raw_response → Parse Stored JSON → Update All Fields
                                              ↓
                                    No AI API Calls Required
                                    Cost-Effective Updates
                                    Error Recovery Capability
```

### **Storage Strategy:**
- **Raw Preservation**: Complete AI responses for debugging and reprocessing
- **Assessment Scores**: Individual fields for filtering and analysis
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
news_extractor_reprocess_node_from_raw_response($nid)
news_extractor_bulk_reprocess_from_raw_responses($limit)
```

### **Raw Data Access:**
- **field_ai_raw_response**: Complete Claude responses for reprocessing
- **Individual Assessment Fields**: Direct access to scores and analysis
- **Drupal Logs**: Detailed processing information
- **Performance Metrics**: Character counts and processing times

## 🛡️ Error Handling

### **Graceful Degradation:**
- **API Failures**: Logged with retry capability
- **JSON Parsing**: Fallback to raw text storage
- **Missing Fields**: Safe field existence checking
- **Content Validation**: Comprehensive URL filtering
- **Field Length**: Automatic handling of long assessment text

### **Data Recovery:**
- **Raw AI responses** preserved for reprocessing
- **Multiple storage formats** provide backup options
- **Audit trails** enable troubleshooting
- **Manual reprocessing** commands available
- **🆕 Cost-effective recovery** from stored data without new AI calls

## 📊 Performance Metrics

### **Processing Statistics:**
- **Content Extraction**: ~30 second timeout per article
- **AI Analysis**: Variable based on article length
- **Assessment Generation**: Credibility, bias, sentiment scoring
- **Data Storage**: Multi-field preservation with assessment data
- **Tag Generation**: Automatic taxonomy integration

### **🆕 Reprocessing Efficiency:**
- **Raw JSON Reprocessing**: <1 second per article
- **Bulk Updates**: 100+ articles per minute
- **Zero API Costs**: Update logic without Claude calls
- **Error Recovery**: Fix thousands of articles instantly

### **Storage Efficiency:**
- **Raw JSON**: Complete debugging and reprocessing capability
- **Assessment Fields**: Individual score storage for queries
- **Structured Data**: Optimized for analysis and filtering
- **Formatted HTML**: Ready for display
- **Taxonomy Terms**: Efficient navigation

## 🔧 Development & Maintenance

### **File Structure:**
```
news_extractor/
├── news_extractor.module          # Core Drupal hooks and AI functions
├── news_extractor.scraper.php     # Content extraction, formatting, and reprocessing
├── src/Form/                      # Configuration forms
├── templates/                     # Display templates
└── README.md                      # This documentation
```

### **Key Integration Points:**
- **Drupal Hooks**: Node create/update triggers
- **AWS Bedrock**: Claude 3.5 Sonnet API with enhanced prompts
- **Diffbot API**: Content extraction service
- **Taxonomy System**: Automatic tag generation
- **Assessment Fields**: Individual media literacy scores

### **🆕 Reprocessing Architecture:**
- **Raw Data Utilization**: Complete use of stored AI responses
- **Cost Management**: Reprocess without API charges
- **Logic Updates**: Apply new extraction rules to existing data
- **Error Recovery**: Fix processing issues retroactively

### **Future Enhancements:**
- **Multiple AI Models**: Support for different analysis types
- **Batch Processing UI**: Admin interface for bulk operations
- **Custom Assessment Criteria**: Per-content-type scoring
- **Analytics Dashboard**: Processing statistics and insights
- **Advanced Reprocessing**: Selective field updates and rules

## Version History

### **v1.4.0 (August 2025) - CURRENT**
- ✅ **Enhanced Media Assessment**: Credibility, bias, and sentiment scoring
- ✅ **Reprocessing Capability**: Cost-effective updates from stored AI responses
- ✅ **Field Length Fixes**: Proper TEXT storage for long bias analysis
- ✅ **Individual Assessment Fields**: Dedicated storage for media literacy scores
- ✅ **Bulk Reprocessing**: Drush commands for efficient batch updates
- ✅ **Error Recovery**: Fix processing issues without new AI calls

### **v1.3.0 (August 2025)**
- ✅ **Raw AI Response Storage**: New `field_ai_raw_response` field
- ✅ **Enhanced JSON Processing**: Structured data extraction
- ✅ **Improved Error Handling**: Comprehensive logging and fallbacks
- ✅ **Multi-format Storage**: Raw, structured, and formatted data

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

- **Module Status**: Production ready with comprehensive media assessment
- **AI Integration**: AWS Bedrock with Claude 3.5 Sonnet and enhanced prompts
- **Data Preservation**: Complete audit trail from raw to processed
- **Assessment Capabilities**: Multi-dimensional media literacy scoring
- **Reprocessing**: Cost-effective updates and error recovery
- **Field Requirements**: Enhanced field setup with proper length configuration

---

**✅ Production Ready**: Comprehensive content extraction, AI analysis, and media assessment  
**🔧 Enhanced Setup Required**: Create assessment fields and ensure proper field lengths  
**📊 Multi-dimensional Analysis**: Entity-motivation mapping plus media literacy scoring  
**💰 Cost-Effective Reprocessing**: Update thousands of articles without AI API costs  
**🛡️ Complete Error Recovery**: Debug and fix issues using stored raw AI responses  

**Maintained by**: Keith Aumiller | **Last Updated**: August 4, 2025
