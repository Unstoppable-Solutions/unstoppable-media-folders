=== Unstoppable Media Folders ===
Contributors: paramedic192
Tags: media library, folders, media organization, attachments, taxonomy
Requires at least: 5.8
Tested up to: 7.1
Stable tag: 1.0.6
Requires PHP: 7.4
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Organize the WordPress Media Library into virtual, nestable folders without moving any files. Existing image URLs never change.

== Description ==

Unstoppable Media Folders adds a folder sidebar to your WordPress Media Library so you can keep images, PDFs, and other attachments tidy.

Folders are **virtual**: assigning a file to a folder only records a relationship in the database using a native WordPress taxonomy. Nothing is moved on the server, so every existing media URL keeps working.

**Features**

* Hierarchical "Media Folders" taxonomy attached to attachments.
* Folder sidebar on the Media Library (list and grid modes).
* Create, rename, delete, and nest folders (requires the `upload_files` capability).
* All Media, Uncategorized, and your own folders shown as a tree.
* Filter the library by clicking a folder.
* Assign one or more folders per item from the attachment details panel.
* Bulk "Move to: folder" actions in list view.
* Drag-and-drop media onto folders.
* Native "Folders" dropdown filter inside the media modal (block editor compatible).
* Deleting a folder never deletes media — items fall back to Uncategorized.

== Installation ==

1. Upload the `unstoppable-media-folders` folder to `/wp-content/plugins/`.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Open **Media** and use the **Folders** sidebar.

== Frequently Asked Questions ==

= Does this move my files? =
No. Folders are stored as taxonomy terms in the database. Files stay exactly where they are and URLs do not change.

= Will it interfere with WooCommerce product images? =
No. It only adds an optional folder relationship and never alters the attachment, its URL, or its usage.

= Who can manage folders? =
Any user with the `upload_files` capability (Authors and above by default).

== Screenshots ==

1. The folder sidebar in the Media Library, showing All Media, Uncategorized, and nested folders with item counts.
2. Creating and nesting folders.
3. Assigning a media item to folders from the attachment details panel.
4. Moving media into a folder with drag-and-drop.
5. The "Move to folder" bulk action in list view.

== External Services ==

This plugin does not connect to, or send any data to, external services. It makes no remote requests, includes no tracking or telemetry, and has no license or activation system. All functionality runs entirely on your own site using the native WordPress taxonomy system.

== Changelog ==

= 1.0.6 =
* Fixed: folder names could still render under the folder icon with certain themes; the sidebar layout is now forced so the icon and name never overlap.

= 1.0.5 =
* Fixed: folder names could appear cut off behind the folder icon under some themes; hardened the sidebar layout so the icon and name never overlap.

= 1.0.4 =
* Fixed: the confirm button on the inline folder form now shows a crisp check icon (switched from the icon font to inline SVG for reliable rendering).

= 1.0.3 =
* Fixed: dragging an item onto a folder now moves it into that single folder instead of leaving a copy in its previous folder.
* Fixed: alignment of the confirm/cancel buttons on the inline folder form.
* Accessibility: the folder tree is now a full ARIA tree with keyboard navigation (arrow keys, Home/End, Enter/Space to open, F2 to rename, Delete to remove).
* Performance: replaced a deprecated browser event and a polling timer with a MutationObserver.
* Added a friendly empty state and a translation template (.pot).

= 1.0.1 =
* Code quality: resolved Plugin Check warnings. Documented that all AJAX handlers verify their nonce via a shared helper, and prefixed file-scope variables in uninstall.php.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.6 =
Stronger fix for folder names overlapping their icons.

= 1.0.5 =
Fixes folder names overlapping their icons in the sidebar.

= 1.0.4 =
Minor UI fix for the add-folder button icon.

= 1.0.3 =
Keyboard accessibility, performance and translation improvements, plus drag-move and UI fixes.

= 1.0.1 =
Code-quality pass for Plugin Check compliance. Recommended.

= 1.0.0 =
Initial release.
