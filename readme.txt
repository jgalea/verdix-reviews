=== Verdix Reviews ===
Contributors: jeangalea
Tags: review, rating, schema, review box, rich snippets
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 2.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Review boxes with star ratings, pros and cons, and review schema that's checked in the editor so Google can show your stars.

== Description ==

Verdix adds a review box to your posts and pages: a star rating, pros and cons, a summary and buy buttons. It writes the JSON-LD review schema Google needs for review stars, and it checks that schema while you write.

= Schema you can check =

A Schema check panel in the editor sidebar lists missing required fields, review types Google won't show stars for, and other active plugins that print their own review markup. One click opens the post in Google's Rich Results Test.

With Yoast SEO, Rank Math or All in One SEO active, Verdix adds the review to that plugin's schema graph instead of printing a second script. Two sets of review markup on one page can cost you the stars.

Your own score is marked up as a Review. Verdix doesn't present an editor's score as an aggregate rating.

= Features =

* Review Box block with live preview, a classic editor meta box and a `[verdix]` shortcode
* Review List block and `[verdix_list]` shortcode for top rated and latest reviews
* Star ratings in half-star steps, pros and cons, summary and verdict heading
* 14 schema.org types (Product, Software Application, Book, Movie, TV Series, Video Game, Music Album, Recipe, Course, Event, Local Business, Restaurant, Creative Work, Thing) with optional per-type fields
* Several offers per product, each with merchant, price and button, output as Offer or AggregateOffer
* Affiliate links through Katina Link Manager when it's active, with `rel="sponsored nofollow"` added
* Disclosure line for affiliate, sponsored and free-product reviews, with a site-wide default
* "Last tested" date, shown in the box and output as the review's dateModified
* Import from WP Review Pro, Ultimate Blocks and Rank Math review schema, with an option to stop the old plugin printing its schema
* Abilities API abilities and a REST API for reading and writing reviews
* Configurable position: before content, after content, both or manual
* Colors through CSS custom properties

= Verdix Pro =

[Verdix Reviews Pro](https://github.com/jgalea/verdix-reviews-pro) is free on GitHub during early access. It adds ratings across several criteria, percentage and points scales, extra box styles, visitor ratings that feed a real aggregate rating, comparison tables, AI-drafted pros and cons, and migration from WP Product Review.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`, or install through the Plugins menu.
2. Activate the plugin.
3. Go to Verdix Reviews > Settings to configure defaults.
4. Add a Review Box block to any post or page, or use the `[verdix]` shortcode.

== Frequently Asked Questions ==

= How do I use the shortcode? =

Use `[verdix]` to display the review box for the current post, or `[verdix id="123"]` for a specific post.

= How do I show my top rated or latest reviews? =

Add the Review List block, or use `[verdix_list]`. Options: `type` ("top-rated" or "latest"), `count` (1 to 20), `schema_type`, `category` and `image` ("true" or "false"). For example `[verdix_list type="latest" count="3" image="false"]`.

= How do I migrate from WP Review Pro? =

Go to Verdix Reviews > Migration. The tool scans for posts with WP Review Pro, Ultimate Blocks or Rank Math review data and lets you migrate them one by one or all at once. Ratings stored as percentage or points are converted to the 0-5 star scale on import.

= Does this work with the classic editor? =

Yes. If you use the classic editor, a meta box appears below the post editor with the same fields as the block.

= How do I customize the review box colors? =

Go to Verdix Reviews > Settings > Appearance to set the star/accent color, background color, and border color. Values are applied via CSS custom properties so they can be overridden in your theme CSS.

= How do I control where the review box appears? =

Set a global default position in Verdix Reviews > Settings (before content, after content, both, or manual). Override per post in the block sidebar or meta box. "Manual" means the review box only appears where you place a Review Box block or `[verdix]` shortcode.

= Where is the JSON-LD output? =

In `<head>` on singular views with review data, or inside your SEO plugin's schema graph when one is active. Ratings are normalized to a 0-5 ratingValue.

= Why don't I see an aggregateRating in the schema? =

An aggregate rating has to come from your visitors, so Verdix only outputs one when visitor ratings exist. Visitor ratings come with Verdix Reviews Pro. Your own score is output as a Review.

= Does it work with Yoast SEO, Rank Math or All in One SEO? =

Yes. By default Verdix adds the review to the SEO plugin's schema graph. You can change this under Verdix Reviews > Settings > Schema Output.

== Screenshots ==

1. The frontend review box: star rating, summary, and pros/cons rendered on a single post.

== Changelog ==

= 2.1.0 =
* New: Schema check panel in the editor, with Rich Results Test link and warnings for review types Google won't show stars for.
* New: Review schema joins the Yoast SEO, Rank Math and All in One SEO graphs. New Schema Output setting.
* New: Several offers per product, output as Offer or AggregateOffer, with Katina Link Manager support.
* New: Disclosure line for incentivized reviews and a "last tested" date.
* New: Import from Ultimate Blocks and Rank Math review schema.
* New: Abilities API support. REST updates now merge partial fields.
* Changed: aggregateRating is no longer built from the author's own score.
* Fixed: block editor script dependencies and admin asset loading on the Settings and Migration pages.

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
* Offers are only emitted when a price is set, so there are no placeholder price=0 offers.
* Nested Review object now includes name and itemReviewed fields.

= 1.1.0 =
* Live appearance preview in settings.
* Schema types expanded from 3 to 14 with optional per-type fields.
* Migration page hides empty sources and shows a single empty state.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 2.1.0 =
Review schema no longer includes an aggregateRating built from your own score, which Google treats as invalid. Check one review with the new Schema check panel after updating.

= 2.0.0 =
Plugin renamed to Verdix Reviews. Existing star ratings and review data continue to work without changes.
