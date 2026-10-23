# Changelog

All notable user-facing changes to Unstoppable Media Folders are documented
here. The same history is mirrored in `readme.txt`, which renders on the
plugin's WordPress.org directory page. This file follows the spirit of
[Keep a Changelog](https://keepachangelog.com) and the project uses
[Semantic Versioning](https://semver.org).

## [Unreleased]

Nothing yet. Proposed and planned work is tracked in [ROADMAP.md](ROADMAP.md).

## [1.0.6]

### Fixed
- Folder names could still render under the folder icon with certain themes; the sidebar layout is now forced so the icon and name never overlap.

## [1.0.5]

### Fixed
- Folder names could appear cut off behind the folder icon under some themes; hardened the sidebar layout.

## [1.0.4]

### Fixed
- The confirm button on the inline folder form now shows a crisp check icon (switched from the icon font to inline SVG for reliable rendering).

## [1.0.3]

### Added
- Full ARIA tree with keyboard navigation (arrow keys, Home/End, Enter/Space to open, F2 to rename, Delete to remove).
- Friendly empty state and a translation template (`.pot`).

### Changed
- Replaced a deprecated browser event and a polling timer with a `MutationObserver`.

### Fixed
- Dragging an item onto a folder now moves it into that single folder instead of leaving a copy in its previous folder.
- Alignment of the confirm / cancel buttons on the inline folder form.

## [1.0.1]

### Fixed
- Resolved Plugin Check warnings: documented that all AJAX handlers verify their nonce via a shared helper, and prefixed file-scope variables in `uninstall.php`.

## [1.0.0]

### Added
- Initial release. Virtual, nestable Media Library folders built on a hierarchical attachment taxonomy: folder sidebar, drag-and-drop, bulk "Move to folder" actions, click-to-filter, per-item assignment, and a "Folders" filter in the block-editor media modal. Files never move on disk, so existing media URLs never change.
