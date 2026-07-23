# Content - Tag Access

> Show or hide parts of a Joomla article based on the visitor's Access Level, and optionally the page they are on. One tag, two attributes, no framework.

<img width="2672" height="1521" alt="TagAccessPlugin-110" src="https://github.com/user-attachments/assets/d0e8707c-cfee-4e17-941d-bb77c8d3b062" />

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

Teaching artefact. This is the "plugin tag" pattern used by commercial extensions (Regular Labs Conditional Content, ECR, Akeeba's content plugin)