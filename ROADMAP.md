# Unstoppable Media Folders — Roadmap

This roadmap tracks planned work for Unstoppable Media Folders. It is a
planning document: items here are **proposed/planned**, not promises, and
nothing is implemented until it is reviewed, built, tested, and released
through the GitHub → WordPress.org workflow.

Each item lists: **Benefit · Priority · Complexity · Milestone · Status · Source**.

- **Priority:** High / Medium / Low
- **Complexity:** Small / Medium / Large
- **Status:** Proposed / Planned / In Progress / Completed
- **Source:** Previously Discussed / Code Review / New Suggestion

> Items marked **[Requirement]** come from the GitHub-migration project plan.
> Everything else is a **[Proposed idea]** for review before any work starts.

---

## Immediate Priorities

### 1. GitHub migration completion — [Requirement]
Make `Unstoppable-Solutions/unstoppable-media-folders` the source of truth.
- **Benefit:** One home for code, history, CI, and releases.
- **Priority:** High · **Complexity:** Small · **Milestone:** 1.0.6 maintenance
- **Status:** Completed — the repo already holds the full 1.0.6 code, both workflows, `.distignore`, and accurate README/CONTRIBUTING.
- **Source:** Previously Discussed

### 2. WordPress 7.1 compatibility verification — [Requirement]
Confirm the plugin runs cleanly on the current WordPress branch (7.1.x).
- **Benefit:** Avoids "untested / outdated" warnings; keeps user trust.
- **Priority:** High · **Complexity:** Small · **Milestone:** 1.0.6 maintenance
- **Status:** Completed (code) / Proposed (manual test) — `Tested up to: 7.1` is already set and is the correct WordPress.org format (a branch covers all point releases, incl. 7.1.3). Code review found **no deprecated functions** and no 7.1 issues. A manual smoke test on 7.1.3 is recommended before the next tagged release to formally verify.
- **Source:** Previously Discussed

### 3. Correct the stale `CHANGELOG.md`
Repo `CHANGELOG.md` still reads "template scaffold / 0.1.0" and omits 1.0.0–1.0.6.
- **Benefit:** Accurate public history that matches `readme.txt`.
- **Priority:** High · **Complexity:** Small · **Milestone:** 1.0.6 maintenance
- **Status:** Proposed (corrected file drafted; in review)
- **Source:** Code Review

### 4. Add the SVN deploy secrets — [Requirement]
`deploy-to-wporg.yml` needs `SVN_USERNAME` / `SVN_PASSWORD` repo secrets.
- **Benefit:** Enables one-click GitHub-Release → WordPress.org publishing.
- **Priority:** High · **Complexity:** Small · **Milestone:** 1.0.7 release readiness
- **Status:** Proposed — add in repo Settings → Secrets (use a WordPress.org SVN-specific password, not the login password).
- **Source:** Previously Discussed

### 5. Dry-run the deploy workflow before a real release — [Requirement]
Validate via `workflow_dispatch` (or a throwaway tag) before trusting production.
- **Benefit:** Proves the pipeline without risking a bad public release.
- **Priority:** High · **Complexity:** Small · **Milestone:** 1.0.7 release readiness
- **Status:** Proposed
- **Source:** Previously Discussed

### 6. Add `SECURITY.md`
Both `CONTRIBUTING.md` and `plugin-check.yml` reference it, but the file is absent.
- **Benefit:** Clear private vulnerability-reporting path; standard hygiene.
- **Priority:** Medium · **Complexity:** Small · **Milestone:** 1.0.6 maintenance
- **Status:** Proposed (drafted; in review)
- **Source:** Code Review

---

## Short-Term Improvements

### 7. Hide the built-in "Media Folders" taxonomy box/column
The taxonomy registers with `show_admin_column` and `show_in_quick_edit`, duplicating the sidebar and causing confusion. Make the sidebar the single source of truth.
- **Benefit:** One clear place to manage folders; removes ambiguity.
- **Priority:** Medium · **Complexity:** Small · **Milestone:** 1.1.0
- **Status:** Proposed · **Source:** Previously Discussed

### 8. Multi-select drag-and-drop
Drag a whole selection of media into a folder at once (today it's one item at a time).
- **Benefit:** Much faster bulk organizing in the grid.
- **Priority:** Medium · **Complexity:** Medium · **Milestone:** 1.1.0
- **Status:** Proposed · **Source:** Previously Discussed

### 9. Live-refresh the list-view "Folders" column after a move
The server-rendered column currently lags until a page reload.
- **Benefit:** Immediate, accurate feedback after AJAX moves.
- **Priority:** Medium · **Complexity:** Small · **Milestone:** 1.1.0
- **Status:** Proposed · **Source:** Previously Discussed

### 10. Folder search / filter box
Quickly find a folder in a large tree.
- **Benefit:** Scales to sites with many folders.
- **Priority:** Medium · **Complexity:** Small · **Milestone:** 1.1.0
- **Status:** Proposed · **Source:** Previously Discussed

### 11. Cache folder counts
`get_tree` runs `wp_update_term_count_now` plus count queries on every load; cache and invalidate on change instead.
- **Benefit:** Faster sidebar on large libraries; less DB work.
- **Priority:** Medium · **Complexity:** Small · **Milestone:** 1.1.0
- **Status:** Proposed · **Source:** Code Review

### 12. Scope the MutationObserver
It currently observes the whole `#wpbody-content` subtree; narrow it to the media containers.
- **Benefit:** Slightly lower overhead on busy admin screens.
- **Priority:** Low · **Complexity:** Small · **Milestone:** 1.1.0
- **Status:** Proposed · **Source:** Code Review

---

## Mid-Term Feature Development

### 13. Migration importer (FileBird / HappyFiles / Real Media Library)
Import existing folder structures from popular media-folder plugins.
- **Benefit:** Removes the biggest switching barrier; strong adoption driver (competitors ship this).
- **Priority:** High · **Complexity:** Large · **Milestone:** 1.3.0
- **Status:** Proposed · **Source:** New Suggestion (competitive gap)

### 14. Folder colors & custom icons
Per-folder color/icon stored as term meta.
- **Benefit:** Faster visual scanning; a commonly expected feature.
- **Priority:** Medium · **Complexity:** Medium · **Milestone:** 1.2.0
- **Status:** Proposed · **Source:** Previously Discussed

### 15. Custom folder ordering
Drag to reorder folders (stored order) instead of alphabetical only.
- **Benefit:** Users arrange folders to match their workflow.
- **Priority:** Medium · **Complexity:** Medium · **Milestone:** 1.2.0
- **Status:** Proposed · **Source:** Previously Discussed

### 16. Undo for folder moves
A short-lived "Undo" after a move / bulk move.
- **Benefit:** Confidence and safety when organizing quickly.
- **Priority:** Medium · **Complexity:** Medium · **Milestone:** 1.3.0
- **Status:** Proposed · **Source:** New Suggestion

### 17. REST API endpoints for folders
First-class REST routes for folders and assignments.
- **Benefit:** Headless/automation use; cleaner internal architecture.
- **Priority:** Medium · **Complexity:** Medium · **Milestone:** 1.3.0
- **Status:** Proposed · **Source:** New Suggestion

---

## Long-Term Development

### 18. Folder gallery block / shortcode
Output a folder's contents as a gallery (block + shortcode), page-builder friendly.
- **Benefit:** Turns organization into front-end value (pairs with WooCommerce/Elementor).
- **Priority:** Medium · **Complexity:** Large · **Milestone:** 2.0.0
- **Status:** Proposed · **Source:** Previously Discussed

### 19. Role-based folder permissions
Restrict which roles/users can see or edit which folders.
- **Benefit:** Agencies and multi-author sites; a paid-tier-worthy capability.
- **Priority:** Medium · **Complexity:** Large · **Milestone:** 2.0.0
- **Status:** Proposed · **Source:** Previously Discussed

### 20. Smart / dynamic folders
Auto-group by file type, date, dimensions, or uploader (virtual views).
- **Benefit:** Organization with zero manual effort.
- **Priority:** Low · **Complexity:** Large · **Milestone:** 2.0.0
- **Status:** Proposed · **Source:** Previously Discussed

### 21. Bulk operations panel + folder ZIP download
Progress-tracked bulk move/tag/delete and "download folder as ZIP".
- **Benefit:** Power-user efficiency; a differentiator.
- **Priority:** Low · **Complexity:** Large · **Milestone:** 2.1.0
- **Status:** Proposed · **Source:** Previously Discussed

### 22. Usage insights
Surface unused media and likely duplicates.
- **Benefit:** Helps users reclaim space and tidy libraries.
- **Priority:** Low · **Complexity:** Large · **Milestone:** 2.1.0
- **Status:** Proposed · **Source:** Previously Discussed

### 23. Pro edition (separate plugin)
A separately distributed Pro plugin (license + update checker) layering premium features on hooks left in the free plugin.
- **Benefit:** Sustainable funding without compromising the free .org plugin.
- **Priority:** Low · **Complexity:** Large · **Milestone:** 2.x
- **Status:** Proposed · **Source:** Previously Discussed
- **Note:** License/activation/update code must live **only** in the Pro plugin — never in the free WordPress.org build (trialware / external-update rules).

---

## Guardrails for every release

- The free WordPress.org plugin stays free of trialware, locked features, external update mechanisms, tracking, and bundled libraries.
- Preserve backward compatibility and user data (folder terms) across updates.
- Apply sanitization, validation, escaping, capability checks, and nonce verification to all new code.
- Keep versions in sync across the main plugin header, `UMFD_VERSION`, `readme.txt` Stable tag, `CHANGELOG.md`, and the `.pot`; then create the GitHub Release tag so the SVN deploy runs.
