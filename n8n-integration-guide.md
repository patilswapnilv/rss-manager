# RSS Content Planner - n8n Integration Guide

## Overview

The RSS Content Planner plugin has been **simplified and optimized** for seamless n8n integration. Instead of complex rules engines, all content processing logic happens in n8n workflows, making the system more flexible and user-friendly.

## Key Benefits of This Approach

✅ **Simplified Architecture**: WordPress handles RSS feeds, n8n handles all processing logic  
✅ **Visual Workflow Builder**: Create complex content rules using n8n's drag-and-drop interface  
✅ **No Code Required**: Business users can modify workflows without touching PHP  
✅ **Scalable**: n8n can handle complex AI integrations, API calls, and conditional logic  
✅ **Maintainable**: Less custom code means fewer bugs and easier updates  

## How It Works

```
RSS Feeds → WordPress Plugin → n8n Workflows → Processed Content → WordPress
```

1. **WordPress Plugin** fetches RSS feeds and stores them as draft posts
2. **n8n Workflows** receive webhooks about new content
3. **AI Processing** happens in n8n (OpenAI, content filters, SEO optimization)
4. **Updated Content** gets sent back to WordPress via REST API

## API Endpoints

### Authentication
All API requests require the API key in header: `X-RCP-API-Key: your-api-key`

Get your API key from: **WordPress Admin → RSS Content Planner → Settings → AI & Automation**

### Available Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/wp-json/rcp/v1/trigger-processing` | POST | Get pending content for processing |
| `/wp-json/rcp/v1/content/{id}/processed` | POST | Update processed content |
| `/wp-json/rcp/v1/n8n-webhook` | POST | Generic webhook handler |

### Example: Get Pending Content

```bash
curl -X POST "https://yoursite.com/wp-json/rcp/v1/trigger-processing" \
  -H "X-RCP-API-Key: your-api-key" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "pending",
    "feed_id": 1
  }'
```

**Response:**
```json
{
  "success": true,
  "count": 3,
  "items": [
    {
      "id": 123,
      "title": "Sample Article Title",
      "content": "Article content here...",
      "meta": {
        "source_url": "https://example.com/article",
        "source_name": "Example Blog",
        "published_date": "2024-01-15"
      }
    }
  ]
}
```

### Example: Update Processed Content

```bash
curl -X POST "https://yoursite.com/wp-json/rcp/v1/content/123/processed" \
  -H "X-RCP-API-Key: your-api-key" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Rewritten Article Title",
    "content": "AI-processed content here...",
    "excerpt": "Brief summary...",
    "status": "draft",
    "meta": {
      "ai_confidence": "95%",
      "processing_time": "2.3s"
    }
  }'
```

## n8n Workflow Templates

We've created ready-to-use workflow templates for common use cases:

### 1. **Content Rewriter** (`content_rewriter_basic`)
- Fetches pending content from WordPress
- Rewrites using OpenAI GPT
- Updates WordPress with rewritten content
- Perfect for: Blog content automation

### 2. **SEO Optimizer** (`seo_optimizer`)
- Optimizes titles and meta descriptions
- Adds proper heading structure
- Generates relevant tags
- Perfect for: Search engine optimization

### 3. **Content Filter** (`content_filter_and_categorize`)
- Filters content by keywords
- Auto-categorizes posts
- Rejects irrelevant content
- Perfect for: Content curation

### 4. **Multilingual Processor** (`multilingual_processor`)
- Translates content into multiple languages
- Creates separate posts for each language
- Perfect for: International websites

## Quick Setup Guide

### Step 1: WordPress Setup
1. Install and activate the RSS Content Planner plugin
2. Complete the onboarding wizard
3. Add your RSS feeds
4. Copy your API key from Settings → AI & Automation

### Step 2: n8n Setup
1. Download a workflow template from the plugin settings
2. Import the JSON file into your n8n instance
3. Configure the WordPress URL and API key
4. Add your OpenAI API key (for AI processing)
5. Activate the workflow

### Step 3: Test the Integration
1. Manually trigger content fetching in WordPress
2. Check n8n execution logs
3. Verify processed content appears in WordPress

## Advanced Workflow Examples

### Custom Content Filter
```
Webhook → Get Content → Check Keywords → AI Classification → Update/Reject
```

### Multi-step Processing
```
Webhook → Get Content → AI Rewrite → SEO Optimize → Social Media Prep → Publish
```

### Quality Control
```
Webhook → Get Content → AI Analysis → Human Review (Slack) → Approve/Reject
```

## Best Practices

### Security
- Keep your API key secure
- Use HTTPS for all requests
- Regenerate API keys if compromised

### Performance
- Process content in batches (max 10 items per request)
- Use n8n's queue system for high-volume workflows
- Set appropriate timeouts

### Error Handling
- Always check API response status
- Implement retry logic for failed requests
- Log errors for debugging

## Troubleshooting

### Common Issues

**API Key Invalid**
- Verify the key is correct
- Check the header format: `X-RCP-API-Key`
- Regenerate if needed

**No Pending Content**
- Check if feeds are actively fetching
- Verify feed URLs are valid
- Look at WordPress cron status

**Processing Stuck**
- Check n8n execution logs
- Verify OpenAI API limits
- Test API endpoints manually

### Debug Mode
Enable WordPress debug logging to see detailed API request logs:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Migration from Complex Rules Engine

If you were using the complex rules engine before, here's how to migrate:

1. **Export existing rules** (if any) for reference
2. **Recreate logic in n8n** using visual workflows
3. **Test workflows** with sample content
4. **Gradually replace** old rules with n8n workflows
5. **Monitor and optimize** performance

## Support & Resources

- **WordPress Plugin**: Manages feeds and provides REST API
- **n8n Documentation**: https://docs.n8n.io/
- **OpenAI API**: https://platform.openai.com/docs/
- **Workflow Templates**: Available in plugin settings

## What's Next?

The simplified architecture opens up exciting possibilities:

- **Custom AI Models**: Integrate any AI service via n8n
- **Advanced Workflows**: Multi-step processing with human approval
- **External Integrations**: Connect to CRM, social media, analytics
- **Real-time Processing**: Instant content updates via webhooks

This simplified approach makes the RSS Content Planner more powerful, flexible, and easier to maintain while providing unlimited customization through n8n's visual workflow builder.
