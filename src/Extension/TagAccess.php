<?php
/**
 * @package     Plugin.Content.TagAccess
 * @subpackage  Teaching example
 *
 * Demonstrates both halves of the "plugin tag" pattern used by extensions
 * like OSD Content Restriction / ECR / Regular Labs Conditional Content /
 * Akeeba's content plugin — but built on nothing but core Joomla.
 *
 * VISIBILITY (WHO / WHERE) - hides or shows wrapped content:
 *
 *   {accesslevel id="7"}
 *   Renders only for visitors whose access levels include ID 7. (WHO)
 *   {/accesslevel}
 *
 *   {accesslevel id="7" menuitem="102"}
 *   Renders only for those visitors AND only on the page whose active
 *   menu item ID is 102. (WHO + WHERE)
 *   {/accesslevel}
 *
 * VALUE INJECTION (WHAT, added v1.1.0) - outputs a configured value,
 * self-closing, no wrapped content:
 *
 *   {version id="my-plugin"}
 *   Outputs the version string configured for download entry "my-plugin".
 *
 *   {downloadlink id="my-plugin"}
 *   Outputs a link to the URL configured for "my-plugin", using its
 *   configured label as the link text (falls back to the URL itself).
 *
 *   {downloadlink id="my-plugin" accesslevel="7"}
 *   Same, but only for visitors holding access level 7 - one tag doing
 *   WHO + WHAT. A failed check renders nothing (no fallback text here;
 *   these tags output a value or a link, not prose).
 *
 *   Download entries are configured in the plugin's own options, one
 *   per line: id|version|url|label (label optional).
 *
 * Access level IDs: Users -> Access Levels, ID column.
 * Menu item IDs: Menus -> [menu], ID column.
 *
 * v1.0.4 (2026-07-17): optional menuitem="N" attribute added - the WHERE
 * dimension, a teaching-scale miniature of Regular Labs Conditional
 * Content's menu-item rule (see UAM-P06). WHO-only tags keep working
 * unchanged.
 *
 * v1.1.0 (2026-07-20): {version} and {downloadlink} added - the WHAT
 * dimension (Akeeba-style value injection), the other half of the
 * plugin-tag pattern. Same onContentPrepare mechanism, this time
 * outputting a configured value instead of deciding visibility. Optional
 * accesslevel="Y" reuses the existing WHO check so one tag can do
 * WHO + WHAT together.
 */

namespace Joomla\Plugin\Content\TagAccess\Extension;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;

\defined('_JEXEC') or die;

final class TagAccess extends CMSPlugin implements SubscriberInterface
{
    /**
     * Map Joomla core events to methods on this class.
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onContentPrepare' => 'onContentPrepare',
        ];
    }

    /**
     * Fires whenever Joomla prepares article/content text for display.
     * Scans for {accesslevel}, {version}, and {downloadlink} tags and
     * replaces each with the content, value, or nothing the checks allow.
     *
     * @param   \Joomla\Event\Event  $event  onContentPrepare event
     */
    public function onContentPrepare($event): void
    {
        $article = $event->getArgument('subject');

        // Skip anything without body text (e.g. some list/teaser contexts).
        if (empty($article->text)) {
            return;
        }

        // Nothing to do if none of our tags are present - cheap bail-out.
        $hasAccessTag = stripos($article->text, '{accesslevel') !== false;
        $hasValueTag  = stripos($article->text, '{version') !== false
            || stripos($article->text, '{downloadlink') !== false;

        if (!$hasAccessTag && !$hasValueTag) {
            return;
        }

        $app        = $this->getApplication();
        $userLevels = $app->getIdentity()->getAuthorisedViewLevels();

        if ($hasAccessTag) {
            $fallback = (string) $this->params->get('fallback', '');

            // WHERE: the active menu item ID, frontend only (0 = no menu
            // context, e.g. backend preview or a URL with no matching
            // menu item).
            $activeMenuItemId = 0;

            if ($app->isClient('site')) {
                $active           = $app->getMenu()->getActive();
                $activeMenuItemId = $active ? (int) $active->id : 0;
            }

            $article->text = $this->replaceAccessLevelTags($article->text, $userLevels, $fallback, $activeMenuItemId);
        }

        if ($hasValueTag) {
            $article->text = $this->replaceValueTags($article->text, $userLevels);
        }
    }

    /**
     * Handles {accesslevel id="X" menuitem="Y"}...{/accesslevel} - the
     * visibility half of the pattern (WHO / WHERE).
     */
    private function replaceAccessLevelTags(string $text, array $userLevels, string $fallback, int $activeMenuItemId): string
    {
        $pattern = '/\{accesslevel\s+id="(\d+)"(?:\s+menuitem="(\d+)")?\s*\}(.*?)\{\/accesslevel\}/is';

        return preg_replace_callback(
            $pattern,
            function (array $matches) use ($userLevels, $fallback, $activeMenuItemId): string {
                $requiredLevel    = (int) $matches[1];
                $requiredMenuItem = (int) ($matches[2] ?? 0); // 0 when the attribute is absent
                $wrappedHtml      = $matches[3];

                // WHO: visitor must hold the access level.
                $whoPasses = \in_array($requiredLevel, $userLevels, true);

                // WHERE: if a menuitem is named, the active menu item must match.
                $wherePasses = ($requiredMenuItem === 0) || ($requiredMenuItem === $activeMenuItemId);

                if ($whoPasses && $wherePasses) {
                    return $wrappedHtml;
                }

                // Fallback text only for a WHO failure on an otherwise
                // matching page - a wrong-page miss shows nothing at all
                // (no point teasing content that does not belong here).
                if (!$whoPasses && $wherePasses) {
                    return $fallback;
                }

                return '';
            },
            $text
        );
    }

    /**
     * Handles {version id="X"} and {downloadlink id="X"}, each with an
     * optional accesslevel="Y" - the value-injection half of the pattern
     * (WHAT), Akeeba-style. Self-closing tags: no wrapped content, so a
     * failed accesslevel check simply renders nothing (fallback is
     * content-shaped prose; these tags output a value or a link instead).
     */
    private function replaceValueTags(string $text, array $userLevels): string
    {
        $downloads = $this->getDownloads();

        $pattern = '/\{(version|downloadlink)\s+id="([\w-]+)"(?:\s+accesslevel="(\d+)")?\s*\}/i';

        return preg_replace_callback(
            $pattern,
            function (array $matches) use ($downloads, $userLevels): string {
                $tag         = strtolower($matches[1]);
                $id          = $matches[2];
                $rawLevel    = $matches[3] ?? '';
                $requiresWho = $rawLevel !== '';

                // WHO, only checked if accesslevel was actually named on this tag.
                if ($requiresWho && !\in_array((int) $rawLevel, $userLevels, true)) {
                    return '';
                }

                if (!isset($downloads[$id])) {
                    return '';
                }

                $entry = $downloads[$id];

                if ($tag === 'version') {
                    return htmlspecialchars($entry['version'], ENT_QUOTES, 'UTF-8');
                }

                // downloadlink
                $label = $entry['label'] !== '' ? $entry['label'] : $entry['url'];

                return '<a href="' . htmlspecialchars($entry['url'], ENT_QUOTES, 'UTF-8') . '">'
                    . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</a>';
            },
            $text
        );
    }

    /**
     * Parses the "downloads" plugin param into an id-keyed array.
     * One entry per line: id|version|url|label (label optional).
     * Blank lines and malformed lines (fewer than 3 fields) are skipped.
     *
     * @return array<string, array{version: string, url: string, label: string}>
     */
    private function getDownloads(): array
    {
        $raw   = (string) $this->params->get('downloads', '');
        $lines = preg_split('/\r\n|\r|\n/', $raw);
        $out   = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $parts = array_map('trim', explode('|', $line));

            if (\count($parts) < 3) {
                continue; // malformed - needs at least id|version|url
            }

            [$id, $version, $url] = $parts;
            $label                = $parts[3] ?? '';

            if ($id === '') {
                continue;
            }

            $out[$id] = [
                'version' => $version,
                'url'     => $url,
                'label'   => $label,
            ];
        }

        return $out;
    }
}
