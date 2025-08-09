# RSS Content Planner & Rewriter

A WordPress plugin that aggregates content from RSS/Atom feeds and powers an editorial workflow with n8n automation to plan, rewrite, and republish ethically attributed content.

## Features

- **n8n-First Processing**: Primary integration with n8n workflow automation
- **Feed Management**: RSS/Atom feed validation, parsing, and bulk import
- **Smart Content Processing**: AI-powered content rewriting and enhancement
- **Editorial Workflow**: Kanban-style content inbox with approval workflows
- **Multisite Support**: Fully compatible with WordPress Multisite

## Requirements

- WordPress 6.5+
- PHP 8.1+
- MySQL 5.7+ or MariaDB 10.3+
- n8n instance (recommended) or direct AI API access

## Installation

1. Upload to `/wp-content/plugins/rss-manager/`
2. Activate the plugin
3. Configure n8n webhooks or API keys
4. Add RSS feeds and set up processing rules

## Quick Start

1. **Set Up n8n**: Go to RSS Planner > Workflows, create webhook integration
2. **Add Feeds**: Navigate to RSS Planner > Feeds, add RSS/Atom URLs
3. **Configure Rules**: Set up automatic content processing rules

## API Endpoints

- `GET /wp-json/rcp/v1/feeds` - List feeds
- `POST /wp-json/rcp/v1/feeds` - Create feed
- `GET /wp-json/rcp/v1/webhooks` - List webhooks
- `POST /wp-json/rcp/v1/webhook/callback` - Webhook callback

## License

GPL v2 or later

## Author

[patilswapnilv](https://swapnilpatil.in/)
