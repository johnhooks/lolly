# Lolly Log WordPress Plugin

A Ecs/Elk compatible logging plugin for WordPress.

## Development

### Admin Interface

The admin interface is built using React and WordPress components. To work on the admin interface:

1. Install dependencies:

```bash
npm install
```

2. Start the development server:

```bash
npm run start
```

3. Build for production:

```bash
npm run build
```

### PHP Development

See the CLAUDE.md file for PHP development instructions.

## Admin Settings

The plugin provides an admin interface to configure logging and redaction settings. Navigate to "Lolly Log" in the WordPress admin menu to access these settings.

### Available Settings

- Enable/disable redaction
- Configure path redaction patterns
- Configure host redaction patterns

## Building Assets

The plugin uses @wordpress/scripts to build assets. This provides a standardized build process for WordPress plugins.

- `npm run build`: Build the assets for production
- `npm run start`: Start the development server with hot reloading
- `npm run lint:js`: Lint JavaScript files
- `npm run lint:style`: Lint CSS/SCSS files
- `npm run format:js`: Format JavaScript files
