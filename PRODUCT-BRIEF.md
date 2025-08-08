# Product Brief: WordPress "RSS Content Planner & Rewriter" Plugin

## 1) Goal & Outcomes

**Goal:** Aggregate content from multiple RSS/Atom feeds, auto-classify into WordPress taxonomies, and power an editor workflow to plan, rewrite, and republish ethically attributed content through n8n workflow automation.

**Primary outcomes**

* Fewer tabs & manual copy-paste for editors
* Consistent taxonomy/tagging at scale
* Fast "fetch → n8n workflow → rewrite → schedule" pipeline
* Clear provenance & compliance
* Leverage existing n8n automation workflows
* Cost-efficient AI processing through user's existing integrations

## 2) Users & Roles

* **Admin:** Global settings, API keys, feed CRUD, bulk import, permissions.
* **Editor:** Curate items, manage categories/tags, set rules, approve/rewrite/schedule.
* **Author:** Rewrite drafts, add metadata, request approval.
* **SEO Manager (optional):** Final metadata & internal links.

Map to WP roles via custom capabilities (e.g., `rssplanner_manage_feeds`, `rssplanner_edit_items`).

## 3) Core Features

### A) Feed Management

* **Add Feed**: URL, title, description, source site, polling interval, language, default attribution line, license note.
* **Validation**: Fetch + validate RSS/Atom; store `etag`/`last-modified`; handle redirects; error logs.
* **Bulk Upload (CSV)**: Columns: `feed_name, feed_url, default_wp_category, default_tags, language, polling_interval, source_license`. Downloadable CSV template + importer with preview & row-level errors.
* **Scheduling**: WP-Cron based polling with staggered jobs, retries, backoff, per-feed interval.
* **De-duplication**: Hash by `guid`/URL + fuzzy similarity (title+content) to prevent repeats.

### B) Category & Taxonomy Mapping

* **Use native taxonomies**: WP Categories/Tags by default; support custom taxonomies via settings.
* **Feed → Subcategory**: Within a feed, define internal **Feed Categories** (e.g., using feed `<category>` or keyword rules).
* **Per-Feed-Category Settings**:

  * Target WP Category(ies)
  * Default Tags (static + dynamic from keywords/entities)
  * Post Status on import (Draft/Pending/Private)
  * Author (default or rotating)
  * Featured image logic
  * AI processing preset (see Rules Engine below)

### C) Rules Engine (“Permutation/Combination”)

Define **ordered rules** that evaluate on import. Conditions (AND/OR):

* Feed or Feed Category
* Title/body contains keywords/regex
* Domain/source
* Language detected
* Author in feed
* Publication time/day
* Content length, has image/video
* Topic/entity detected (AI)
* Duplicate score threshold

**Actions:**

* Assign WP Category/Tags (merge/replace)
* Set post status/author
* Add canonical URL custom field
* Generate excerpt/summary (AI)
* Rewrite post body/title/meta (AI preset)
* Translate to target language
* Append attribution template
* Schedule window (randomized time within X hours)
* Add internal link suggestions (AI) as post meta
* Reject/Quarantine (if license/quality issues)

### D) Content Ingestion & Enrichment

* **Parsing**: Use SimplePie for RSS/Atom + Readability-like full-text extraction (optional “fetch source page” toggle).
* **Media**: Option to fetch first image; download to Media Library; set as featured; handle hotlink vs. local copy; image deduping by hash.
* **Metadata**: Source URL, source title, published date, author, categories, `canonical_url`, `source_license` stored as custom fields.
* **Attribution**: Configurable template with tokens (e.g., “Originally published at {source\_site} on {pub\_date}.”).

### E) Editorial Workflow

* **Inbox (Fetched Items)**: Kanban/List with columns: *Fetched → To Rewrite → In Review → Scheduled → Published* (custom post statuses).
* **Bulk Actions**: Apply rules, assign categories/tags, trigger AI summaries, schedule.
* **Rewrite Workspace**:

  * Side-by-side: Source content vs. Working draft
  * AI tools: *Summarize, Rewrite (tone sliders), Expand, Simplify, Headline ideas, Bullet → prose, Translation*
  * Plagiarism/similarity check vis-à-vis source (lightweight n-gram similarity)
  * Fact-check checklist & manual citation notes
* **SEO panel**: AI-suggested title/meta description, outline, schema hints, FAQs, internal link targets (based on site content index).

### F) n8n Workflow Integration (Primary)

* **Primary Mode**: n8n webhook-based processing with visual workflow automation
* **Workflow Templates**: Pre-built n8n workflows for common content processing tasks
* **Template Marketplace**: Community-contributed workflows with ratings and downloads
* **Webhook Management**: Secure token-based authentication, payload validation, execution tracking
* **Bidirectional Communication**: Send RSS items to n8n, receive processed content back
* **Execution Monitoring**: Real-time workflow status, error handling, and retry mechanisms

### G) Direct AI Integration (Fallback)

* **Providers**: OpenAI API out of the box + provider-agnostic interface (Anthropic, Google, local via OpenRouter/self-hosted) with per-preset model selection, temperature, max tokens.
* **Safety & Guardrails**: Rate limiting, cost estimates, content policy reminders, opt-out per feed/category.
* **Caching**: Store prompts+outputs (hashed) to avoid duplicate spend; manual revert/restore.
* **Migration Path**: Easy upgrade from direct API to n8n workflows

### H) Planning & Calendar

* **Editorial Calendar**: Month/Week views of drafts/scheduled posts (drag & drop reschedule).
* **Ideas Board**: Save interesting items as “Ideas” with tags, owners, due dates.
* **Notifications**: Email/Slack (webhook) on new high-priority items, failed fetches, approvals requested.

### I) Search & Discovery

* **Library**: Filter by feed, source, topic, entity, time, rule that fired, status.
* **Saved Views** & CSV export for reporting.

## 4) Data Model (simplified)

* **CPT `rss_item`** (private): stores fetched raw item (post\_content\_raw, html\_raw), relations to source post (WP post ID if created).
* **Taxonomies**: Use existing `category`, `post_tag`; optional custom `source_site` taxonomy.
* **Tables (custom)**:

  * `wp_rssplanner_feeds` (id, url, name, settings json, last\_fetch, etag, last\_modified)
  * `wp_rssplanner_rules` (id, feed\_id nullable, priority, conditions json, actions json, active)
  * `wp_rssplanner_webhooks` (id, feed\_id, workflow\_name, webhook\_url, auth\_token, active, created\_date, last\_used)
  * `wp_rssplanner_executions` (id, item\_id, webhook\_id, execution\_id, status, started\_at, completed\_at, error\_log)
  * `wp_rssplanner_templates` (id, name, description, category, n8n\_json, author, downloads, rating)
  * `wp_rssplanner_logs` (timestamp, level, context, message)
* **Post Meta**: `_rss_source_url`, `_rss_source_guid`, `_rss_canonical`, `_rss_source_license`, `_ai_provenance`, `_similarity_score`, `_internal_link_suggestions`.

## 5) UI/UX Outline

* **Processing Mode Selection**: Toggle between n8n workflows (primary) and direct API (fallback)
* **Feeds**: Table list (status badges, last fetched, new items count) → Feed detail tabs: *Settings | Categories | Rules | Workflows | Logs*.
* **n8n Integration Panel**: 
  * Workflow template library with search and filtering
  * Webhook URL management and testing
  * Execution monitoring with real-time status
  * Template import/export functionality
* **Rules Builder**: Visual condition blocks + action blocks; n8n workflow assignment; test mode with sample item.
* **Inbox & Rewrite**: Split-pane editor; "Apply workflow" dropdown; "Compare vs source" toggle; similarity meter; workflow execution status.
* **Calendar**: Drag & drop; filters; bulk reschedule; workflow processing indicators.
* **Settings**: n8n webhooks, API keys (fallback), default attribution, license policy, provider selection, cron health, roles/capabilities.

## 5.1) Sample n8n Workflow Templates

### Basic Content Rewriter
```json
{
  "name": "RSS Content Rewriter",
  "description": "Simple content rewriting with OpenAI",
  "nodes": [
    {"type": "Webhook", "name": "RSS Input", "settings": {"authentication": "headerAuth"}},
    {"type": "OpenAI", "operation": "text", "settings": {"model": "gpt-4", "prompt": "Rewrite this article in a professional tone while maintaining key facts"}},
    {"type": "HTTP Request", "name": "Return to WordPress", "method": "POST"}
  ]
}
```

### Advanced SEO Pipeline
```json
{
  "name": "SEO-Optimized Content",
  "description": "Multi-step SEO enhancement workflow",
  "nodes": [
    {"type": "Webhook", "name": "RSS Input"},
    {"type": "Google Trends", "operation": "keyword_research"},
    {"type": "OpenAI", "operation": "seo_rewrite", "settings": {"include_keywords": true}},
    {"type": "Screaming Frog", "operation": "link_analysis"},
    {"type": "Set", "name": "Format Response"},
    {"type": "HTTP Request", "name": "Return Enhanced Content"}
  ]
}
```

### Multi-Language Content Pipeline
```json
{
  "name": "Translation & Localization",
  "description": "Translate and adapt content for different markets",
  "nodes": [
    {"type": "Webhook", "name": "RSS Input"},
    {"type": "Google Translate", "operation": "translate", "settings": {"target_language": "es"}},
    {"type": "OpenAI", "operation": "cultural_adaptation"},
    {"type": "IF", "name": "Quality Check"},
    {"type": "HTTP Request", "name": "Return Translated Content"}
  ]
}
```

## 6) Compliance, Ethics, Legal

* Respect `robots.txt` & feed TOS; provide **per-feed license field** and “block republish if license ≠ allowed”.
* Always store and surface **canonical/source link** and **attribution**; default to noindex until editor approval (configurable).
* Avoid “spinning”; emphasize **transformative rewriting** & fact-checking. Include a “quotas & excerpts only” mode if required.
* GDPR/PII: do not store unnecessary personal data; allow purge of cached AI prompts/outputs.
* Cache & rate limits to avoid hammering source sites.

## 7) Performance & Architecture

* **Dual-Mode Processing**: n8n webhooks (primary) + direct API (fallback)
* **Webhook Management**: Secure token authentication, payload validation, execution tracking
* **Workflow Templates**: JSON-based n8n workflow import/export system
* Batch imports; memory-safe streaming parsing.
* WP Transients/object cache for feed state and workflow execution status.
* Async queues (e.g., `wp_remote_post` loopback or Action Scheduler) for heavy jobs (full-text fetch, workflow calls, media).
* **WP-CLI** commands: `rssplanner import`, `rssplanner run-rules`, `rssplanner backfill`, `rssplanner sync-workflows`.
* **REST API**: `/wp-json/rssplanner/v1/webhooks/`, `/workflows/`, `/templates/`, `/executions/`
* Multisite-aware (network settings + per-site overrides).

## 8) Extensibility

* **Hooks/Filters**: Before/after fetch, before/after rule applied, n8n workflow filters, content sanitizer, attribution template, webhook payload filters.
* **REST API** (namespaced): feeds, items, rules, actions, webhooks, workflows, templates, executions → allows external dashboards and n8n integration.
* **n8n Integration**: Custom nodes, workflow templates, community marketplace.
* **Gutenberg blocks/shortcodes**: Surface curated source lists on the site (optional).

## 9) Non-Functional

* PHP 8.1+, WP 6.5+, MySQL 5.7+/MariaDB 10.3+.
* Internationalization (i18n), RTL styles.
* Accessibility (WCAG 2.1 AA).
* Test coverage for parsers/rules; integration tests for cron & AI calls.

## 10) Success Metrics (examples)

* Time from fetch to scheduled ↓ 50%
* % items auto-tagged correctly ≥ 90%
* Duplicate/near-duplicate publishes ≤ 1%
* Editor satisfaction (survey) ≥ 8/10

## 11) Suggestions & Nice-to-Haves

* **Topic clustering**: Auto-group similar items into story hubs.
* **Entity extraction**: Build a knowledge graph (people, places, orgs) and tag posts.
* **Translation pipelines**: Source in Language A → publish in Language B with glossary.
* **Auto-image generation**: If no image, generate a header image via AI with safe presets (opt-in).
* **License-aware actions**: Different rules for “CC BY” vs “All rights reserved”.
* **Quality gates**: Block publish if < X words or similarity > Y% to source.
* **Fact prompts**: Auto-generate fact questions for editor review (“Verify the CEO’s name”).
* **Source scoring**: Prioritize feeds with historically high engagement.
* **Spam/low-quality filter**: Heuristics + AI to quarantine clickbait.
* **Link monitoring**: Alert on broken source links; auto-update canonical if changed.

## 12) Phased Roadmap

**v1.0 (MVP - 3-4 months)**: 
* Core feed management and RSS parsing
* Basic n8n webhook integration
* Simple taxonomy mapping
* Editorial inbox with basic workflow
* Direct API fallback (OpenAI)
* Attribution system
* Basic workflow templates

**v1.1 (Enhanced - 2-3 months)**:
* Advanced webhook management
* Template marketplace
* Rules engine integration with n8n
* Editorial calendar
* Media handling and de-duplication
* Execution monitoring dashboard

**v1.2 (Advanced - 3-4 months)**:
* Full-text extraction
* SEO integration
* Multisite support
* Advanced workflow templates
* Community template sharing
* WP-CLI commands

**v2.0 (Enterprise - 4-5 months)**:
* Advanced analytics and reporting
* Translation workflows
* License-aware automation
* Internal links recommender
* Enterprise workflow features
* Performance optimization

---

