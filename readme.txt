=== Verdix Reviews ===
Contributors: jeangalea
Tags: review, rating, schema, structured data, stars
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 2.0.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Review boxes with structured data (JSON-LD) for WordPress. Star ratings, pros/cons, and schema markup that Google understands.

== Description ==

Verdix Reviews adds a clean, responsive review box to your posts and pages with proper schema.org structured data. Built as a modern, lightweight replacement for legacy review plugins.

Features:

* Block editor "Review Box" block with live preview
* Classic editor meta box fallback
* Star ratings (0-5, half-star increments)
* Pros and cons lists
* Summary text and CTA button
* Clean JSON-LD structured data output
* 14 schema.org types (Product, Software Application, Book, Movie, TV Series, Video Game, Music Album, Recipe, Course, Event, Local Business, Restaurant, Creative Work, Thing)
* Per-type optional fields (e.g. Book author/ISBN, Recipe ingredients, Movie director)
* `[verdix]` shortcode support
* Configurable review box position (auto-insert before/after content, or manual)
* One-click migration from WP Review Pro
* REST API endpoint for headless use
* CSS custom properties for easy theming

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`, or install through the Plugins menu.
2. Activate the plugin.
3. Go to Verdix Reviews > Settings to configure defaults.
4. Add a Review Box block to any post or page, or use the `[verdix]` shortcode.

== Frequently Asked Questions ==

= How do I use the shortcode? =

Use `[verdix]` to display the review box for the current post, or `[verdix id="123"]` for a specific post.

= How do I migrate from WP Review Pro? =

Go to Verdix Reviews > Migration. The tool scans for posts with WP Review Pro data and lets you migrate them one by one or all at once. Ratings stored as percentage or points are converted to the 0-5 star scale on import.

= Does this work with the classic editor? =

Yes. If you use the classic editor, a meta box appears below the post editor with the same fields as the block.

= How do I customize the review box colors? =

Go to Verdix Reviews > Settings > Appearance to set the star/accent color, background color, and border color. Values are applied via CSS custom properties so they can be overridden in your theme CSS.

= How do I control where the review box appears? =

Set a global default position in Verdix Reviews > Settings (before content, after content, both, or manual). Override per post in the block sidebar or meta box. "Manual" means the review box only appears where you place a Review Box block or `[verdix]` shortcode.

= Where is the JSON-LD output? =

In `<head>` on singular post/page views that have review data. The schema is normalized to a 0-5 ratingValue and includes both `review` and `aggregateRating` for compatibility with Google rich results.

== Screenshots ==

1. The frontend review box: star rating, summary, and pros/cons rendered on a single post.

== Changelog ==

= 2.0.2 =
* Fixed JSON-LD: removed a duplicate bare Product node and a conflicting itemReviewed field that caused Google "Product snippets" and "Review snippets" validation errors.
* Review author now always has a name, falling back to the site name, to satisfy Google's required author field.

= 2.0.1 =
* Moved meta-box schema-type toggle script out of the template and into the enqueued meta-box.js.

= 2.0.0 =
* Renamed plugin to Verdix Reviews.
* Slimmed down to focus on a clean, fully-functional review box experience: star ratings, pros/cons, summary, CTA, and 14 schema.org types with per-type fields.
* Registries (rating types, styles, schema types) are now driven entirely by filters so add-ons can extend them cleanly.

= 1.2.0 =
* Block editor sidebar shows type-specific schema fields (Book author, Recipe ingredients, etc.) matching the classic meta box.
* Migration page rebuilt with clear "Data detected" / "Nothing to migrate" status per source.
* JSON-LD now emits aggregateRating alongside review for better Google rich result compatibility.
* Offers are only emitted when a price is set — no placeholder price=0 offers.
* Nested Review object now includes name and itemReviewed fields.

= 1.1.0 =
* Live appearance preview in settings.
* Schema types expanded from 3 to 14 with optional per-type fields.
* Migration page hides empty sources and shows a single empty state.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 2.0.0 =
Plugin renamed to Verdix Reviews. Existing star ratings and review data continue to work without changes.
