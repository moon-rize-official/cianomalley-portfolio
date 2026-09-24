# Oxygen Templates — Build Runbook

How to build the site's templates in **Oxygen Builder 6** against `cian-portfolio-core`. Oxygen templates live in the WordPress database and are authored in the visual builder — this runbook is the checklist to recreate them, mapping each template to its condition and the plugin shortcodes / dynamic data it should contain.

Reference: [`docs/plan/08`](plan/08-builder-implementation-plans.md) (Oxygen plan) and [`docs/plan/05`](plan/05-world-and-navigation.md) (district specs).

## Prerequisites

- Oxygen Builder 6 active; `cian-portfolio-core` active; ACF Pro active (field groups auto-load from `acf-json/`).
- The plugin already enqueues `tokens.css` + base/components/content/video/accessibility CSS site-wide, so **do not re-define colors in Oxygen** — reference the CSS variables (e.g. `var(--violet-electric)`).
- On staging first; export template JSON to `design-exports/oxygen/` per milestone (see plan Part 08).

## Plugin shortcodes available (place these in templates)

| Shortcode | Renders | Use in |
|---|---|---|
| `[cian_guide_steps]` | the flexible-content Steps (sections, commands, code, callouts, downloads), numbered + anchored | Guide single |
| `[cian_guide_toc]` | table of contents from step titles | Guide single (sidebar) |
| `[cian_video_facade]` | consent-safe click-to-load player placeholder (no iframe until click) | Video single, Guide single (embedded), Review single |
| `[cian_chapters]` | chapter list with second-based deep-links | Video single |
| `[cian_command lang="bash"]…[/cian_command]` | copyable command block | anywhere |
| `[cian_review_scores]` | overall + `<meter>` sub-scores | Review single |
| `[cian_pros_cons]` | pros/cons columns | Review single |
| `[cian_spec_table]` | specification table | Review single |
| `[cian_card id="123"]` | one content card | manual placements |
| `[cian_related]` | related-content grid (from the relationships table, both directions) | every single |

All accept an `id` attribute (defaults to the current post). Transcript panel + REST: `GET /wp-json/cian/v1/world` and `/wp-json/cian/v1/videos/{id}/transcript?page=`.

## Global setup (once)

1. **Global classes** (Oxygen → Manage → Global Styles): create utility classes that only reference tokens — `.panel-glass`, `.chip`, `.btn`, `.btn-primary`, `.btn-ghost`, `.measure-reading`, `.grid-cards`. No literal colors.
2. **Header/footer**: build a slim accessible menu (skip link + `<nav aria-label="Site">`) and footer (legal, social from Portfolio Settings, sitemap link). This is the only always-visible navigation.
3. **Command-menu mount**: add an empty `<div id="cian-command-menu" hidden>` and a floating control with `data-cian-command-open`; the plugin JS wires it.

## Templates (Oxygen → Templates)

Build in this order. Each row: the Oxygen **template condition** and the **key contents**. Priority-sorted so the accessible core ships first (plan Phase 6).

| # | Template | Condition | Key contents |
|---|---|---|---|
| 1 | `tpl-base` | Catch-all (lowest priority) | header, footer, command-menu mount; inner content area |
| 2 | Guide single | Singular → Guide | H1, `[cian_guide_toc]` (sticky sidebar), meta (difficulty/time/**last-verified**/OS via dynamic data), `[cian_guide_steps]`, embedded `[cian_video_facade]` when a related video exists, action bar (Watch on YouTube / Download / Report outdated / Print), `[cian_related]` |
| 3 | Guide archive | Archive → Guide (+ its taxonomies) | filter bar (difficulty/category/OS/technology via GET params), Oxygen **Repeater** looping guides → place `[cian_card]` per item (or build a native card) |
| 4 | Video single | Singular → Video | H1, `[cian_video_facade]`, publication date/duration (dynamic data), description, chapter panel `[cian_chapters]`, transcript panel (`<div class="cian-transcript" data-video-id data-endpoint>` + toggle button), commands, downloads, "Read the written guide" link to related guide, `[cian_related]` |
| 5 | Video archive | Archive → Video | featured slot, filter bar, Repeater → cards with **static thumbnails only** (never a live player) |
| 6 | Review single | Singular → Review | product hero (image/manufacturer/price/date/disclosure via dynamic data), `[cian_review_scores]`, `[cian_pros_cons]`, `[cian_spec_table]`, detail sections (dynamic data), video review `[cian_video_facade]`, final verdict, `[cian_related]` |
| 7 | Review archive | Archive → Review | filter bar (category/recommended/score), Repeater → cards |
| 8 | Project single | Singular → Project | hero (role/year/status + GitHub/Live/Docs links via dynamic data), case-study sections (Problem → Future improvements, dynamic data — skip empty), screenshot gallery, video gallery `[cian_video_facade]`, `[cian_related]` |
| 9 | Project archive | Archive → Project | filter bar (category/status/technology/year), Repeater → cards, featured first |
| 9a | Client Work single / archive | Singular / Archive → Client Work | render case-study details through `cian_core_client_work_data( $post_id )`; archive filters use the shared `technology`, `project_category`, and `project_status` taxonomies |
| 10 | Article single / archive | Singular / Archive → Article | reading-optimized single; chronological cards archive |
| 11 | Series single | Singular → Tutorial Series | cover/summary/difficulty/outcomes, ordered lessons Repeater (each row: video + guide links, `[cian_card]`), "Continue at lesson N" (localStorage) |
| 12 | Home / Arrival | Front page | identity hero (H1 + positioning + tech strip, all real HTML), world mount `<div id="district-root">`, then the fallback sections beneath (featured projects, latest guide + video, entry links, contact CTA) |
| 13 | Pages | Page | About (+ timeline shortcode when built), Contact (Fluent Forms + consent), Lab, Map, Search results, legal pages |

## Rules

- **Dynamic data** for simple fields (title, single meta); **plugin shortcodes** for anything complex (steps, scores, related). Never put business logic in an Oxygen code block.
- Archives use Oxygen's native Repeater/query loop; the filter bar submits GET params so results are server-rendered and no-JS-safe.
- Reading templates (Guide/Article single) must **not** enqueue the world — the plugin already gates this; just don't add the `#district-root` mount there.
- Breakpoints: 480 / 768 / 1024 / 1366 / 1920; cap content at 1200px (reading at 70ch). Gate hover styles behind `@media (hover:hover)`.
- Adopt Breakdance Elements for Oxygen elements (accordion/tabs/slider) only after an a11y check; record results in `docs/decisions/`.

## Client Work data contract

The plugin owns the `client_work` post type (public archive `/work/`) and attaches it to the shared `technology`, `project_category`, and `project_status` taxonomies. A frontend can call `cian_core_client_work_data( $post_id )` to get the stable `client`, `status`, `services`, `year`, and `live_url` string keys. Escape values for their output context.

Canonical REST meta keys are `client_work_client`, `client_work_status`, `client_work_services`, `client_work_year`, and `client_work_live_url`. The adapter falls back to the Digital District theme's legacy `dd_client`, `dd_status`, `dd_services`, `dd_year`, and `dd_live_url` values so existing entries remain readable during migration. Oxygen remains the planned default template builder; this data contract also supports an optional theme frontend.

## Manual GitHub project import

The plugin offers `wp cian project import-github <repository>` for a deliberate, one-repository import. Set `CIAN_GITHUB_IMPORT_OWNER` and `CIAN_GITHUB_IMPORT_ALLOWLIST` in `wp-config.php` before using it, for example `define( 'CIAN_GITHUB_IMPORT_OWNER', 'cian-omalley' );` and `define( 'CIAN_GITHUB_IMPORT_ALLOWLIST', array( 'repo-name' ) );`. The allowlist is empty by default. The importer requests only that repository's public metadata endpoint, refuses private or mismatched responses, skips existing imports, and creates new items as drafts. It does not list an account's repositories, fetch README or source contents, run on theme/plugin activation, or schedule a sync. Review the draft and publish it manually when ready.

## Verifying a template

1. Create a real post of that type with representative fields.
2. Load the URL; confirm the shortcodes render (steps numbered, facade shows no iframe until click, related cards appear).
3. Keyboard-walk it; run axe; check reduced-motion.
4. Export the template JSON to `design-exports/oxygen/` and commit with a changelog line.
