# Job Application Automation Module

## 🎯 Overview

The Job Application Automation module streamlines the job application process by automatically generating tailored resumes using AI technology. When a job description is added to the site, it automatically creates a customized resume based on your original resume and the specific job requirements.

## ✨ Features

- **Automatic Resume Tailoring**: Generates tailored resumes when job descriptions are created
- **AI-Powered Optimization**: Uses AWS Bedrock Claude to optimize resumes for specific positions
- **Original Resume Integration**: Maintains a master "Original Resume" for all tailoring operations
- **Job-Specific Customization**: Tailors content based on job title, company, and job description
- **Seamless Integration**: Works automatically in the background without user intervention

## 🔧 Technical Details

### AI Integration
- **Service**: AWS Bedrock Runtime
- **Model**: `anthropic.claude-3-5-sonnet-20240620-v1:0`
- **Region**: `us-west-2`
- **Max Tokens**: 4000 for resume generation

### Core Architecture

#### Hook Implementation
- **`hook_entity_insert()`**: Triggers when Job Description nodes are created
- **Automatic Processing**: No manual intervention required
- **Error Handling**: Graceful fallback if AI service is unavailable

#### Service Class: `JobApplicationManager`
Main service responsible for AI integration and resume generation.

**Key Method**: `generateTailoredResume($resume_text, $job_title, $company, $job_description)`

### Content Type Requirements

#### Resume Content Type
- **Title**: Must be "Original Resume" (exact match)
- **Body**: Contains the master resume content
- **Type**: `resume`

#### Job Description Content Type
- **Title**: Job title
- **Company**: Company name (field: `field_company`)
- **Body**: Job description content
- **Tailored Resume**: AI-generated tailored resume (field: `field_tailored_resume`)
- **Type**: `job_description`

## 🚀 Installation

1. Enable the module in Drupal admin: `/admin/modules`
2. Create required content types (Resume and Job Description)
3. Configure AWS credentials for Bedrock access
4. Create the "Original Resume" node
5. Clear cache: `drush cr`

## 📋 Requirements

- Drupal 9, 10, or 11
- Node module
- AWS SDK for PHP
- AWS Bedrock access with Claude model permissions

## 🔑 Configuration

### AWS Setup
Ensure your server has AWS credentials configured with access to:
- AWS Bedrock Runtime
- Claude 3.5 Sonnet model permissions

### Content Type Setup

#### Resume Content Type
```yaml
Machine name: resume
Fields:
  - title (required)
  - body (long text, required)
```

#### Job Description Content Type
```yaml
Machine name: job_description
Fields:
  - title (required)
  - field_company (text, required)
  - body (long text, required)
  - field_tailored_resume (long text, optional)
```

### Original Resume Setup
1. Create a Resume node with title "Original Resume"
2. Add your master resume content to the body field
3. Save the node

## 📊 Usage

### Automatic Workflow
1. **Create Job Description**: Add a new Job Description node
2. **Fill Required Fields**: 
   - Title (job title)
   - Company name
   - Job description in body
3. **Save Node**: The module automatically triggers
4. **AI Processing**: Resume is tailored in the background
5. **Result**: Tailored resume appears in the "Tailored Resume" field

### Manual Process
If you need to regenerate a tailored resume:
1. Edit the Job Description node
2. Save it again to retrigger the process

## 🎨 AI Prompt Engineering

The module uses a sophisticated prompt that focuses on:

- **Keyword Optimization**: Incorporates job description keywords
- **Skill Highlighting**: Emphasizes relevant skills and experience
- **Achievement Focus**: Highlights accomplishments that align with the role
- **Format Preservation**: Maintains original resume structure
- **Contact Information**: Keeps personal details unchanged

## 🔍 Logging

The module provides comprehensive logging:

- **Successful Generation**: Logs when resumes are successfully created
- **Error Conditions**: Detailed error messages for troubleshooting
- **API Responses**: Unexpected response format logging
- **Performance Tracking**: Monitor processing times

Access logs: **Reports > Recent log messages > job_application_automation**

## 🛠️ Troubleshooting

### Common Issues

**Resume Not Generated**
- Verify "Original Resume" node exists with exact title
- Check AWS Bedrock permissions and connectivity
- Review error logs for specific issues
- Ensure required fields are populated

**Incomplete Tailored Resume**
- Check token limits (4000 max)
- Verify job description content is substantial
- Review AI service response in logs

**Permission Errors**
- Confirm AWS credentials are properly configured
- Verify Bedrock service permissions
- Check network connectivity to AWS

### Debug Steps
1. Check if "Original Resume" node exists: `/admin/content`
2. Review recent log messages: `/admin/reports/dblog`
3. Test AWS connectivity manually
4. Verify field machine names match code expectations

## 🔄 Customization

### Prompt Modification
Edit `buildResumePrompt()` method in `JobApplicationManager.php` to customize:
- AI instructions
- Resume focus areas
- Output format requirements
- Specific industry adaptations

### Field Mapping
Update field machine names in `job_application_automation.module` if using different field configurations.

### Model Configuration
Change AI model by updating the `modelId` in `JobApplicationManager.php`.

## 🚀 Future Enhancements

Potential improvements:
- Support for multiple original resumes
- Industry-specific resume templates
- Cover letter generation
- Integration with job board APIs
- Resume format conversion (PDF, Word, etc.)

## 📞 Support

For issues or questions:
1. Check module logs for specific error messages
2. Verify content type and field configurations
3. Test AWS Bedrock connectivity
4. Confirm "Original Resume" node setup
5. Review field machine name mappings
