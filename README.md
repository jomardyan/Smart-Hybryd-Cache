# Smart Hybrid Cache

Persistent WordPress object caching with **Redis** or **Memcached**, safe fallbacks, admin controls, diagnostics, and WP-CLI support.

## Overview

Smart Hybrid Cache is a WordPress plugin that adds persistent object caching using Redis or Memcached through their PHP extensions. It is designed to work safely in real-world environments by falling back gracefully when services or extensions are unavailable.

This repository contains the plugin source, build workflow, and packaging setup for generating an installable WordPress plugin ZIP.

## Features

- Redis, Memcached, Auto, and Disabled engine modes
- Safe fallback to the default WordPress object cache behavior
- WordPress admin settings for configuring and testing cache connections
- Persistent object cache drop-in installation support
- WP-CLI commands for common cache operations
- Site Health integration and diagnostics export
- Monitoring, logging, and cache status visibility
- Multisite-aware cache key prefixing
- Configurable global and non-persistent cache groups

## Requirements

- WordPress 6.0 or later
- PHP 8.0 or later
- Redis server with the `ext-redis` PHP extension, or
- Memcached server with the `ext-memcached` PHP extension

## Important Notes

- This plugin does **not** install Redis or Memcached for you.
- Your cache server must already be installed and running.
- Persistent object caching in WordPress requires an `object-cache.php` drop-in.
- This plugin focuses on **object caching**, not full page caching.

## Repository Structure

```text
.
├── .github/
│   └── workflows/
├── build/
├── smart-hybrid-cache/
│   ├── assets/
│   ├── dropins/
│   ├── includes/
│   ├── readme.txt
│   ├── smart-hybrid-cache.php
│   └── uninstall.php
├── tools/
├── Makefile
├── README.md
└── LICENSE
```

## Installation

### Install from GitHub Actions artifact

Open the workflow run on GitHub and download the `smart-hybrid-cache` artifact.

GitHub delivers it as `smart-hybrid-cache.zip`. Upload that file as-is in WordPress under **Plugins > Add New > Upload Plugin**.

**Do not extract it first**, and do **not** upload a ZIP nested inside another ZIP.

The artifact ZIP contains the plugin files directly at its root, so WordPress installs it correctly as the `smart-hybrid-cache` plugin.

### Install from a local build

Build the plugin locally:

```sh
make build
```

Then upload:

```text
build/smart-hybrid-cache.zip
```

in WordPress under **Plugins > Add New > Upload Plugin**.

### Manual installation

1. Copy the `smart-hybrid-cache` directory into `/wp-content/plugins/`
2. Activate the plugin in WordPress
3. Go to **Settings > Smart Hybrid Cache**
4. Choose **Auto**, **Redis**, or **Memcached**
5. Save settings and test the connection
6. Install the object cache drop-in to enable persistent object caching

## Development

### Available Make targets

```sh
make lint
make build
make clean
make tree
```

### What they do

- `make lint` — runs PHP linting on all plugin PHP files
- `make build` — cleans, lints, and creates `build/smart-hybrid-cache.zip`
- `make clean` — removes the build directory
- `make tree` — prints the plugin file tree

## GitHub Actions

This repository includes a GitHub Actions workflow that:

- checks out the repository
- sets up PHP 8.2
- optionally bumps plugin version metadata for release packaging
- builds a standalone plugin ZIP
- uploads the installable plugin artifact
- attaches the ZIP to GitHub releases

## WP-CLI Support

The plugin includes WP-CLI support for cache management and diagnostics.

Examples of supported operations include:

- status
- test
- flush
- enable
- disable
- install-dropin
- remove-dropin
- diagnostics

## Safety and Compatibility

Smart Hybrid Cache is designed to be conservative and safe:

- falls back when Redis or Memcached is unavailable
- avoids overwriting another plugin’s `object-cache.php` unless explicitly confirmed
- integrates with WordPress Site Health
- supports multisite-aware cache behavior

## License

Licensed under **GPL-3.0-or-later**.

See [LICENSE](LICENSE).

## Contributing

Contributions, fixes, and improvements are welcome. If you plan to make significant changes, consider opening an issue or pull request first to discuss the proposed update.

## Plugin Metadata

- **Plugin Name:** Smart Hybrid Cache
- **Version:** 1.1.0
- **Requires WordPress:** 6.0+
- **Requires PHP:** 8.0+
- **License:** GPL-3.0-or-later
