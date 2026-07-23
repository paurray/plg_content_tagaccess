Content - Tag Access

Show or hide parts of a Joomla article based on the visitor's Access Level, and optionally the page they are on. One tag, two attributes, no framework.

<img width="2672" height="1521" alt="TagAccessPlugin-110" src="https://github.com/user-attachments/assets/d0e8707c-cfee-4e17-941d-bb77c8d3b062" />

What it does

Wrap any part of an article in a tag:

{accesslevel id="7"}Only holders of access level 7 can read this.{/accesslevel}

Add a page condition (optional):

{accesslevel id="7" menuitem="133"}Only holders of level 7, and only on menu item 133.{/accesslevel}

The plugin checks the visitor at render time. WHO = the id attribute (a Joomla Access Level ID). WHERE = the menuitem attribute (a menu item ID, optional). Both must pass, or the content is removed before the page ships.

Output a configured value instead of gating content:

{version id="my-plugin"}
{downloadlink id="my-plugin"}
{downloadlink id="my-plugin" accesslevel="7"}

{version} outputs a version string; {downloadlink} outputs a link. Both read from the plugin's downloads option (one entry per line: id|version|url|label). Add accesslevel="Y" to either tag to gate it by access level too - one tag doing WHO + WHAT. These are self-closing (no wrapped content), so a failed check just renders nothing.

Why it exists

Teaching artefact. This is the "plugin tag" pattern used by commercial extensions (Regular Labs Conditional Content, ECR, Akeeba's content plugin) rebuilt at readable scale on nothing but core Joomla, so you can see exactly how the trick works under the hood. Built alongside tutorials on the Joomla · Astroid Framework · Tutorials & Training channel.

Work in progress - exact purpose (standalone teaching example vs. tied to specific videos) still to be decided.

Requirements
Joomla 6.1.1 (modern namespaced plugin, services/provider.php pattern)
PHP 8.3.30
No dependencies
Installation
Download the latest release zip.
Joomla backend: System, Install, Extensions, drag the zip in.
Enable it: System, Plugins, search "Tag Access", set Status to Enabled. New plugins install disabled, this step is the number one support question.
Usage

Finding the two IDs:

Access level ID: Users, Access Levels, the ID column. (Careful: this is NOT the user group ID, they are separate numbering sequences.)
Menu item ID: Menus, open the menu, the ID column.

Fallback text (optional): in the plugin's options you can set text shown to visitors who fail the access check on an otherwise matching page ("Subscribe to read this"). Wrong-page misses show nothing at all, no teasing.

Read this before using it on a real site
Fails OPEN. If this plugin is ever disabled, the raw tags render as plain text, INCLUDING the content you meant to hide. Gating by module Access fails closed (worst case: nothing shows); tag gating fails exposed. Do not gate genuinely sensitive content with tags alone.
No superset exclusion. "Show to Silver but NOT Gold" is not possible with access levels when Gold qualifies for the Silver level. That job needs rule-based tools (Regular Labs Conditional Content) or group architecture that keeps the tiers apart.
No nested tags. One tag pair per block, nesting is not handled.
Caching: Joomla's page cache is guests-only in both directions and com_content views are never cached for logged-in users (verified against core source, Joomla 5.4/6.1), so the standard cache setups do not leak gated content. Keep the Page Cache plugin's "Use Browser Caching" option off on tier-gated sites.
How it works (the whole trick)

The plugin subscribes to onContentPrepare, which fires every time Joomla prepares article text for display. It scans the text with one regular expression, asks the application two questions (does this visitor's getAuthorisedViewLevels() include the required level, and does the active menu item match, if one was named), and replaces each tag block with its content, the fallback, or nothing. That is the entire mechanism, about 80 lines with comments: src/Extension/TagAccess.php.

Current state and roadmap

Where it stands (v1.1.0, 2026-07-20): both halves of the plugin-tag pattern are now built and live-verified on Joomla 6.1.1 + Astroid 3.4.2. Visibility gating ({accesslevel}) does WHO (access level) and WHERE (menu item) in one tag, with optional global fallback text. Value injection ({version}, {downloadlink}) outputs a configured value, with an optional accesslevel attribute so one tag can do WHO + WHAT - confirmed live on the staging site (ART-dark-mode-TEST), guest vs. Silver member, same page.

Next, in rough order:

Per-tag fallback - fallback="..." as a tag attribute, overriding the global plugin option per block.
Options screen - proper settings UI (candidate for the extended/paid version per the Members Only seed).

Deliberately out of scope, use Regular Labs Conditional Content instead: per-group exclusion ("Silver but NOT Gold"), nested tags, non-access conditions (device, date, geolocation).

Nice to have, not blocking (2026-07-23): a second pair of eyes on the plugin code itself, Sean Carney or Sonny (Astroid lead dev) both already in the loop via the Members Only email thread. Worth naming plainly: this is generic Joomla plugin code, nothing Astroid-specific, so either reviewer would be doing it as a favour rather than as the domain expert. All verification to date is self-tested, one person, one session, honestly documented but not independently confirmed.

Version history

See CHANGELOG.md. Short version: 1.0.0 installed cleanly and silently did nothing (missing service provider, a lesson in itself), 1.0.4 added the WHERE dimension, 1.1.0 added the WHAT dimension (value injection). Installable zips are attached to each GitHub Release.

License

GNU General Public License version 2 or later. Free to use, modify, and learn from, that is the point.

Credits

[Paul Staub - Joomla · Astroid Framework · Tutorials & Training](https://www.youtube.com/@Astroid-Joomla-Seamlessly)
