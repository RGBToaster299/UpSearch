# UpSearch - PHP Search Platform

A clean, modern search platform built with vanilla PHP.


> [!CAUTION]
> Since i left GitHub, i moved UpSearch to our Gitea instance.
> https://basicsites.ddns.net/git/RGBToaster/UpSearch <br>
> [Read this article](https://www.businessinsider.com/github-ceo-developers-embrace-ai-or-get-out-2025-8)<br/>


## Live-Version

A live version is available on [our Website](https://basicsites.ddns.net/search/)

## Features

- **Homepage** with centered search bar and clean UI
- **Search functionality** with full-text search across titles, descriptions, URLs, and categories
- **Website submission** with spam protection and Discord webhook integration
- **Report system** for flagging inappropriate content
- **Browse page** to view all indexed websites with filtering and sorting
- **CLI status checker** to verify site availability and remove broken links

## Installation

1. Copy all files to your PHP-enabled web server (Apache with PHP 8+ recommended)
2. Ensure the web server has write permissions for the `savedSites/` and `reports/` directories
3. Optional: Configure Discord webhook URLs in the respective PHP files

## Configuration

### Discord Webhooks (Optional)

To enable Discord notifications for submissions and reports, add your webhook URLs to:

- `submit.php` - Line 8: `'discord_webhook' => 'YOUR_WEBHOOK_URL_HERE'`
- `report.php` - Line 8: `'discord_webhook' => 'YOUR_WEBHOOK_URL_HERE'`

### Spam Protection

The platform includes several spam protection measures:
- Honeypot fields in forms
- IP-based cooldown (5 minutes between submissions)
- Input validation and sanitization
- URL duplicate checking

## File Structure

```
/
├── index.php          # Homepage with search
├── search.php         # Search results page
├── submit.php         # Website submission form
├── report.php         # Report website form
├── list.php          # Browse all websites
├── check.php         # CLI status checker
├── savedSites/       # Directory for website JSON files
├── reports/          # Directory for report JSON files
└── README.md         # This file
```

## Usage

### Adding Websites
Visit `/submit.php` to add new websites to the search index. Required fields:
- Website URL
- Title (max 100 characters)
- Description (max 500 characters)
- Category selection
- Optional screenshot URL

### Searching
Use the search bar on the homepage or search page. The search algorithm looks for matches in:
- Website titles (highest priority)
- Descriptions
- URLs
- Categories

### Browsing
Visit `/list.php` to browse all indexed websites with options to:
- Filter by category
- Sort by newest, oldest, or alphabetical
- View website cards with all details

### Reporting
Use `/report.php` to report inappropriate websites. Reports are stored locally and optionally sent to Discord.

### Maintenance
Run the status checker from command line:
\`\`\`bash
php check.php
\`\`\`

This will:
- Check HTTP status of all indexed websites
- Remove sites returning 4xx or 5xx errors
- Remove sites that fail to connect
- Provide detailed output of the checking process

## Technical Details

- **Pure PHP** - No frameworks or external dependencies
- **Responsive design** - Works on desktop and mobile
- **Standards compliant** - Valid HTML5 with proper DOCTYPE
- **Security focused** - Input sanitization, spam protection, XSS prevention
- **File-based storage** - No database required, uses JSON files
- **Modern CSS** - Gradient backgrounds, smooth transitions, clean typography

## TODO

[ ] Add proper Dark-Mode

[ ] Correct centering-problems  

## Browser Support

- Chrome/Chromium 60+
- Firefox 55+
- Safari 12+
- Edge 79+
