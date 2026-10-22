# Unstoppable Media Folders

[![WordPress Plugin Version](https://img.shields.io/wordpress/plugin/v/unstoppable-media-folders.svg)](https://wordpress.org/plugins/unstoppable-media-folders/)
[![WordPress Plugin Downloads](https://img.shields.io/wordpress/plugin/dt/unstoppable-media-folders.svg)](https://wordpress.org/plugins/unstoppable-media-folders/)
[![License: GPL v2+](https://img.shields.io/badge/License-GPLv2+-blue.svg)](https://www.gnu.org/licenses/gpl-2.0)

Organize your WordPress Media Library into virtual, nestable folders — **without moving a single file on disk**. Existing image URLs stay exactly where they are, so nothing in your posts or pages breaks.

> **End users:** install from the WordPress Plugin Directory — [wordpress.org/plugins/unstoppable-media-folders](https://wordpress.org/plugins/unstoppable-media-folders/)

## Features

- **Folder sidebar in the Media Library** — drag-and-drop items into folders
- **Nestable folders** — folders inside folders, as deep as you want
- **URLs never change** — folders are a taxonomy; the underlying file path is untouched
- **No disk reshuffling** — safe to install on a long-standing site with thousands of items
- **Fast filtering** — click a folder, see only its items; "All items" is still one click away

## Why "no file moves" matters

Media plugins that reorganize the filesystem break every post that references the old path. Unstoppable Media Folders stays on top of the stock library and uses WordPress taxonomy under the hood, so you get the organization without the migration risk.

## Development

### Release workflow

Releases are deployed to WordPress.org automatically by GitHub Actions:

1. Finish the feature work on `main`
2. Bump the version in `unstoppable-media-folders.php` (header + any constant), `readme.txt` (Stable tag), and add a Changelog entry
3. Commit and push `main`
4. On GitHub, create a new **Release** tagged `vX.Y.Z` — the deploy workflow fires and pushes to WordPress.org SVN

### CI

Every push and pull request runs the official WordPress Plugin Check against the codebase.

## License

GPL-2.0-or-later. See the `License` header in `unstoppable-media-folders.php`.

## Author

Developed and maintained by [Unstoppable Solutions](https://unstoppablesolutions.app).
