# Content - Tag Access

> Show or hide parts of a Joomla article based on the visitor's Access Level, and optionally the page they are on. One tag, two attributes, no framework.

<img width="1200" height="683" alt="SAME-Article-Different-Window-Dressing-WEB" src="https://github.com/user-attachments/assets/3e082f94-ea80-4528-b4a5-a184e61f4ed6" />

## Why I'm building this

I built a 3-tier demo (Public, Silver, Gold) the way Joomla makes you build it by default: one menu item per tier, each with its own Access level, each generating its own URL. That's not a workaround I chose, it's a structural fact of the CMS. Every menu item is its own URL, there is no way to view a page without one pointing at it, and Joomla will not let two menu items share the same URL (a "Menu Item Alias" gets you close, reusing another item's settings, but it still generates its own separate URL). If you want three audiences to see three different things using only Joomla's native Access field, you need three menu items, full stop.

That's a fine, supported way to do it, Astroid's own Multi-Layouts feature is built for exactly this case: different layout per menu item, different URL per tier. But it's also more structure than a lot of "different content per tier" problems actually need. If the underlying page is the same and only the content should change, multiplying menu items and URLs to get there is solving a routing problem you didn't need to have.

Commercial plugins (Regular Labs Conditional Content, ECR, Akeeba's own content plugin) take the other route: gate the content itself, inside one article, behind one URL, and decide what to render per visitor at page-load time. I wanted to understand exactly how that trick works, well enough to explain it on camera, so I rebuilt a minimal version of it myself instead of reverse-engineering someone else's plugin. That's what this is: a small, readable plugin that lets one URL carry three tiers of content, built first as a teaching artefact for the [Joomla · Astroid Framework · Tutorials & Training](https://www.youtube.com/@Astroid-Joomla-Seamlessly) channel, and only secondarily as something that might grow into more (JED listing, extra attributes, an options screen, see the roadmap below).

Whether this stays a standalone teaching example or ties more tightly to the video series is still an open question, not yet decided.

## What it does

Wrap any part of an article in a tag:

```
{accesslevel id="7"}Only holders of access level 7 can read this.{/accesslevel}
```

Add a page condition (optional):

```
{accesslevel id="7" menuitem="133"}Only holders of level 7, and only on menu item 133.{/accesslevel}
```

The plugin checks the visitor at render time. WHO = the `id` attribute (a Joomla Access Level ID). WHERE = the `menuitem` attribute (a menu item ID, optional). Both must pass, or the content is removed before the page ships.

Output a configured value instead of gating content:

```
{version id="my-plugin"}
{downloadlink id="my-plugin"}
{downloadlink id="my-plugin" accesslevel="7"}
```

`{version}` outputs a version string; `{downloadlink}` outputs a link. Both read from the plugin's `downloads` option (one entry per line: `id|version|url|label`). Add `accesslevel="Y"` to either tag to gate it by access level too - one tag doing WHO + WHAT. These are self-closing (no wrapped content), so a failed check just renders nothing.

## Why it exists

Teaching artefact. This is the "plugin tag" pattern used by commercial extensions (Regular Labs Conditional Content, ECR, Akeeba's content plugin) rebuilt at readable scale on nothing but core Joomla, so you can see exactly how the trick works under the hood. Built alongside tutorials on the [Joomla · Astroid Framework · Tutorials & Training](https://www.youtube.com/@Astroid-Joomla-Seamlessly) channel.

Work in progress - exact purpose (standalone teaching example vs. tied to specific videos) still to be decided.

## Requirements

- Joomla 6.1.1 (modern namespaced plugin, services/provider.php pattern)
- PHP 8.3.30
- No dependencies

## Installation

1. Download the latest release zip.
2. Joomla backend: System, Install, Extensions, drag the zip in.
3. Enable it: System, Plugins, search "Tag Access", set Status to Enabled. **New plugins install disabled, this step is the number one support question.**

## Usage

**Finding the two IDs:**

- Access level ID: Users, Access Levels, the ID column. (Careful: this is NOT the user group ID, they are separate numbering sequences.)
- Menu item ID: Menus, open the menu, the ID column.

**Fallback text (optional):** in the plugin's options you can set text shown to visitors who fail the access check on an otherwise matching page ("Subscribe to read this"). Wrong-page misses show nothing at all, no teasing.

## Read this before using it on a real site

- **Fails OPEN.** If this plugin is ever disabled, the raw tags render as plain text, INCLUDING the content you meant to hide. Gating by module Access fails closed (worst case: nothing shows); tag gating fails exposed. Do not gate genuinely sensitive content with tags alone.
- **No superset exclusion, content.** "Show to Silver but NOT Gold" is not possible with access levels alone when Gold qualifies for the Silver level, on this plugin's roadmap (see below). One layer up, menu-item visibility has the identical limitation and no content plugin, commercial or otherwise, reaches it: which links show in navigation is decided by Joomla core checking each menu item's own Access field directly, confirmed live 2026-07-23.
- **No nested tags.** One tag pair per block, nesting is not handled.
- **Caching:** Joomla's page cache is guests-only in both directions and com_content views are never cached for logged-in users (verified against core source, Joomla 5.4/6.1), so the standard cache setups do not leak gated content. Keep the Page Cache plugin's "Use Browser Caching" option off on tier-gated sites.

## How it works (the whole trick)

The plugin subscribes to `onContentPrepare`, which fires every time Joomla prepares article text for display. It scans the text with one regular expression, asks the application two questions (does this visitor's `getAuthorisedViewLevels()` include the required level, and does the active menu item match, if one was named), and replaces each tag block with its content, the fallback, or nothing. That is the entire mechanism, about 80 lines with comments: [src/Extension/TagAccess.php](https://github.com/paurray/plg_content_tagaccess/blob/main/src/Extension/TagAccess.php).

## Current state and roadmap

**Where it stands (v1.1.0, 2026-07-20):** both halves of the plugin-tag pattern are now built and live-verified on Joomla 6.1.1 + Astroid 3.4.2. Visibility gating (`{accesslevel}`) does WHO (access level) and WHERE (menu item) in one tag, with optional global fallback text. Value injection (`{version}`, `{downloadlink}`) outputs a configured value, with an optional `accesslevel` attribute so one tag can do WHO + WHAT - confirmed live on the staging site (ART-dark-mode-TEST), guest vs. Silver member, same page.

**Next, in rough order:**

1. **Per-tag fallback** - `fallback="..."` as a tag attribute, overriding the global plugin option per block.
2. **Per-group exclusion** ("Silver but NOT Gold") - decided 2026-07-23, moved off the out-of-scope list below. Checks the visitor's authorised levels and rejects an excluded one, instead of only checking for an included one. Scope, confirmed 2026-07-23: article tag content only. Does not and cannot touch menu-item visibility, that's a separate Joomla-core limitation one layer up, see "No superset exclusion" above.
3. **Options screen** - proper settings UI (candidate for a future paid/extended version).
4. **JED listing** - targeted for this year. GitHub releases reach developers; the Joomla Extension Directory reaches the site owners this plugin is actually for, the real distribution channel every named reference (Regular Labs, Akeeba) already uses.

**Deliberately out of scope, use Regular Labs Conditional Content instead:** nested tags, non-access conditions (device, date, geolocation).

**Considered and set aside for now, 2026-07-23: URL-routing.** A system plugin that auto-redirects a visitor to their tier's correct menu item when Joomla's own generated links drop them back to a default page. This only matters if a site uses separate menu items per tier (the Astroid Multi-Layouts pattern) rather than one URL with tag- or module-gated content (this plugin's own pattern, and the one this series recommends by default). Different problem domain than content gating, a meaningfully bigger build than the two items above it, and arguably solving a self-created problem, most "different content per tier" needs are better served by not creating multiple URLs in the first place. Parked here, not dropped, in case a real use case shows up that actually needs separate pages.

**Nice to have, not blocking (2026-07-23):** a second pair of eyes on the plugin code itself, Sean Carney or Sonny (Astroid lead dev) both already in the loop via the Members Only email thread. Worth naming plainly: this is generic Joomla plugin code, nothing Astroid-specific, so either reviewer would be doing it as a favour rather than as the domain expert. All verification to date is self-tested, one person, one session, honestly documented but not independently confirmed.

## Version history

See [CHANGELOG.md](CHANGELOG.md). Short version: 1.0.0 installed cleanly and silently did nothing (missing service provider, a lesson in itself), 1.0.4 added the WHERE dimension, 1.1.0 added the WHAT dimension (value injection). Installable zips are attached to each [GitHub Release](https://github.com/paurray/plg_content_tagaccess/releases/tag/v1.1.0).

## License

GNU General Public License version 2 or later. Free to use, modify, and learn from, that is the point.

## Credits

Paul Staub
