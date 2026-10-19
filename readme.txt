=== Bullet Gallery ===
Contributors: afazalif
Tags: gallery, photo gallery, image gallery, carousel, lightbox
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Build a gallery page out of named sections. Each section is a swipeable row of evenly sized photos with its own colours, ordering and lightbox.

== Description ==

Bullet Gallery is for the kind of gallery page where photos belong to groups: Rooms, Food, Activities, Events. Each group becomes a section, and each section shows as one horizontal, swipeable row of photos that are all cropped to the same shape, so no row ever looks ragged.

Adding photos takes one click. Open a section, press Add photos, select as many as you like from the Media Library, drag the thumbnails into the order you want, and update. No page builder, no per-photo posts, no repeater fields to fill in one at a time.

**What you get**

* Sections you create, rename, reorder by dragging, and delete
* Multi-select photo picking straight from the Media Library
* Drag and drop photo ordering inside each section
* Every photo cropped to one shape you choose, so rows stay even
* A per-section background colour, heading colour and text colour
* Arrows and a scroll progress bar on desktop, native swipe on touch
* Optional photo count beside each section name
* Optional full screen lightbox with keyboard and arrow navigation
* One shortcode for the whole page, or one section at a time

**Built for search engines**

* Real `img` tags with `srcset`, `sizes`, width and height, so layout never shifts while photos load
* Alt text comes from the Media Library, nothing to retype
* The first two photos load eagerly for a good LCP, the rest lazily
* Every photo is in the HTML source from the start, so crawlers see the ones scrolled off screen
* `ImageGallery` and `ImageObject` JSON-LD output automatically
* Each section gets an id, so you can link to `yoursite.com/gallery/#food`

**No dependencies**

Bullet Gallery uses only WordPress core APIs. No page builder, no framework, no external service, no fonts or scripts loaded from anywhere else. Assets load only on pages where the shortcode is used.

== Installation ==

1. Upload the `bullet-gallery` folder to `/wp-content/plugins/`, or install the zip from Plugins → Add New → Upload Plugin.
2. Activate the plugin.
3. Go to **Bullet Gallery → Add Section**, name it, add photos, publish.
4. Repeat for each section, then drag them into order under **Bullet Gallery → Reorder**.
5. Put `[bullet_gallery]` on the page where the gallery should appear.

== Frequently Asked Questions ==

= How do I show only some sections? =

Use `[bullet_gallery ids="12,15"]` with the section IDs, or `[bullet_gallery slug="food,events"]` with the section slugs. The order you list them in is the order they appear.

= Can I turn the lightbox or the photo count off for one page? =

Yes: `[bullet_gallery lightbox="no" count="no"]`. Leave an attribute out to use the global setting.

= My photos are different shapes. Will the rows look uneven? =

No. Every photo is cropped to the shape you pick under Settings, so each row stays even no matter what gets uploaded.

= Does it work with a page builder? =

Yes. Drop the shortcode into any shortcode or text widget, including Elementor, Beaver Builder, Divi and the block editor.

= Where does the alt text come from? =

From the Media Library. Set the alt text when you upload, and the gallery uses it. Photos with no alt text are flagged with a small red marker in the admin so they are easy to spot.

== Screenshots ==

1. A gallery page with several sections, each a swipeable row.
2. Editing a section: multi-select photo picking and drag to reorder.
3. Dragging sections into order.
4. The settings screen.
5. The full screen lightbox.

== Changelog ==

= 1.0.1 =
* Added click-and-drag scrolling with a mouse on desktop.
* Fixed the scroll progress bar running past the end of its track.
* Fixed the arrow buttons being squashed by theme button styles.
* Tightened spacing, photo height and the next-photo peek on mobile.
* Softened the missing alt text marker in the admin from red to amber.

= 1.0.0 =
* First release.
