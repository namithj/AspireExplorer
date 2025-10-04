# AspireExplorer

A WordPress plugin that provides a comprehensive repository browser for exploring and managing WordPress plugins and themes from the AspirePress ecosystem.

## Features

- 🔍 **Plugin Search & Browse** - Search and explore WordPress plugins with detailed information
- 🎨 **Theme Discovery** - Browse and preview WordPress themes
- 🛒 **Cart Functionality** - Add plugins/themes to cart with persistent storage via cookies
- � **WordPress Playground Integration** - Generate blueprint URLs for instant WordPress demos
- �💫 **Interactive UI** - Modern lightbox galleries, floating cart button, and smooth animations
- 📱 **Responsive Design** - Mobile-friendly interface with SCSS-powered styling
- ♿ **Accessibility** - ARIA-compliant components with keyboard navigation support
- 🔌 **REST API** - REST API endpoints for external integrations

## Installation

1. Download or clone this repository
2. Place the `aspireexplorer` folder in your `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Create pages with slugs `plugins` and `themes` for the archive pages

## Configuration

### Required Constants

Add these constants to your `wp-config.php` file:

```php
// Root path for URL structure (optional, defaults to empty)
define( 'AE_ROOT', 'packages/' );
```

### Required Pages

Create WordPress pages with these exact slugs:
- **plugins** - For the plugins archive and individual plugin pages
- **themes** - For the themes archive and individual theme pages

## URL Structure

With the default configuration, your URLs will be:

**Plugins:**
- Archive: `yoursite.com/packages/plugins/`
- Individual: `yoursite.com/packages/plugins/plugin-name/`

**Themes:**
- Archive: `yoursite.com/packages/themes/`
- Individual: `yoursite.com/packages/themes/theme-name/`

**REST API:**
- Playground Blueprint: `yoursite.com/wp-json/aspireexplorer/v1/playground/blueprint`

## REST API Endpoints

### WordPress Playground Blueprint Generator

**Endpoint:** `GET /wp-json/aspireexplorer/v1/playground/blueprint`

Generate WordPress Playground blueprints for instant theme/plugin demos.

#### Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `theme` | string | No | Theme download URL (.zip file, HTTPS only) |
| `plugin` | string | No | Plugin download URL (.zip file, HTTPS only) |
| `landing_page` | string | No | Landing page path (default: `/`) |
| `activate` | boolean | No | Auto-activate asset (default: `true`) |
| `import_starter_content` | boolean | No | Import theme starter content (default: `true`) |

#### Usage Examples

**Theme Blueprint:**
```bash
GET /wp-json/aspireexplorer/v1/playground/blueprint?theme=https://example.com/theme.zip
```

**Plugin Blueprint:**
```bash
GET /wp-json/aspireexplorer/v1/playground/blueprint?plugin=https://example.com/plugin.zip
```

**Combined Theme + Plugin:**
```bash
GET /wp-json/aspireexplorer/v1/playground/blueprint?theme=https://example.com/theme.zip&plugin=https://example.com/plugin.zip&landing_page=/demo
```

#### Response Format

Returns a JSON blueprint compatible with WordPress Playground:

```json
{
  "$schema": "https://playground.wordpress.net/blueprint-schema.json",
  "landingPage": "/",
  "features": {
    "networking": true
  },
  "steps": [
    {
      "step": "installTheme",
      "themeData": {
        "resource": "url",
        "url": "https://example.com/theme.zip"
      },
      "options": {
        "activate": true,
        "importStarterContent": true
      }
    }
  ]
}
```

#### Security & Validation

- ✅ **HTTPS Only**: Only secure URLs accepted
- ✅ **File Type Validation**: Only `.zip` files allowed
- ✅ **Domain Filtering**: Blocks localhost and internal IPs
- ✅ **Input Sanitization**: All parameters properly sanitized
- ✅ **CORS Headers**: Cross-origin requests enabled

## Development

### Code Structure

```
aspireexplorer/
├── includes/
│   ├── controller/          # MVC Controllers
│   │   ├── class-main.php
│   │   ├── class-plugins.php
│   │   ├── class-themes.php
│   │   └── class-playground.php  # WordPress Playground API
│   ├── model/              # Data Models
│   │   ├── class-assetinfo.php
│   │   ├── class-plugininfo.php
│   │   └── class-themeinfo.php
│   └── views/              # Template Files
│       ├── plugins/
│       ├── themes/
├── assets/
│   ├── js/
│   │   └── aspire-explorer.js    # Main JavaScript (ES6 classes)
│   ├── scss/
│   │   ├── aspire-explorer.scss  # Main SCSS file
│   │   ├── _cart.scss           # Cart styling
│   │   ├── _lightbox.scss       # Lightbox component
│   │   └── _search.scss         # Search components
│   └── css/
│       └── aspire-explorer.css   # Compiled CSS
└── composer.json           # PHP dependencies and scripts
```

### JavaScript Classes

The frontend uses modern ES6 classes:

- **AeCart** - Cart functionality with cookie persistence
- **AeLightbox** - Image lightbox with keyboard navigation
- **AeDetails** - Collapsible details/summary components
- **FallingText** - Animation effects

### Development Setup

1. **Install PHP dependencies:**
   ```bash
   composer install
   ```

2. **Code Quality Tools:**
   ```bash
   # Format code
   composer run format
   
   # Lint code
   composer run lint
   
   # Fix linting issues
   composer run lint:fix
   ```

3. **SCSS Compilation:**
   ```bash
   # Install Node dependencies
   npm install
   
   # Compile SCSS
   npm run sass
   
   # Watch for changes
   npm run sass:watch
   ```

### Coding Standards

This project follows WordPress Coding Standards with:
- **PHP_CodeSniffer** with WordPress rules
- **PHPCS** for code formatting
- **SCSS** for modular styling
- **ES6** JavaScript with jQuery integration

## Cart System

The cart system allows users to collect plugins/themes for later reference:

- **Persistent Storage** - Uses cookies to maintain selections across sessions
- **Toggle Behavior** - Add/remove items with single button click
- **Floating UI** - Minimalist floating cart button with popup
- **Accessibility** - Full keyboard navigation and screen reader support

## Troubleshooting

### Rewrite Rules Not Working

1. Go to **Settings > Permalinks** and click "Save Changes"
2. Ensure your pages have the correct slugs (`plugins`, `themes`)
3. Check that `AE_ROOT` constant matches your URL structure

### Cart Not Persisting

- Ensure cookies are enabled in browser
- Check for JavaScript errors in browser console
- Verify jQuery is loaded properly

### Styling Issues

- Check if CSS file is properly enqueued
- Compile SCSS if using development version
- Clear any caching plugins

### REST API Issues

**Playground Blueprint Endpoint Not Working:**
- Verify REST API is enabled (`/wp-json/` accessible)
- Check WordPress version (requires 4.7+)
- Ensure plugin is activated and REST routes are registered

**Blueprint Validation Errors:**
- URLs must be HTTPS only
- Only `.zip` files are accepted
- At least one theme or plugin URL must be provided
- Landing page must start with `/`

**CORS Issues:**
- REST endpoint includes CORS headers automatically
- For custom domains, verify server CORS configuration

## Contributing

1. Fork the repository
2. Create a feature branch
3. Follow WordPress coding standards
4. Run linting and formatting tools
5. Submit a pull request

## License

This project is licensed under the GPL v2 or later - see the WordPress plugin header for details.

## Support

For support and bug reports, please use the GitHub issue tracker or contact the AspirePress team.
