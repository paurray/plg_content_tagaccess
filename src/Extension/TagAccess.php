<?php
/**
 * @package     Plugin.Content.TagAccess
 * @subpackage  Teaching example
 *
 * Demonstrates the "plugin tag" pattern used by extensions like
 * OSD Content Restriction / ECR / Regular Labs Conditional Content —
 * but built on nothing but core Joomla.
 *
 * Syntax in an article:
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
 * Access level IDs: Users -> Access Levels, ID column.
 * Menu item IDs: Menus -> [menu], ID column.
 *
 * v1.0.4 (2026-07-17): optional menuitem="N" attribute added - the WHERE
 * dimension, a teaching-scale miniature of Regular Labs Conditional
 * Content's menu-item rule (see UAM-P06). WHO-only tags keep working
 * unchanged.
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
     * Scans for {accesslevel id="X" menuitem="Y"}...{/accesslevel} blocks
     * (menuitem optional) and keeps or strips the wrapped content.
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

        // Nothing to do if there's no tag in the text at all — cheap bail-out.
        if (stripos($article->text, '{accesslevel') === false) {
            return;
        }

        $app        = $this->getApplication();
        $userLevels = $app->getIdentity()->getAuthorisedViewLevels();
        $fallback   = (string) $this->params->get('fallback', '');

        // WHERE: the active menu item ID, frontend only (0 = no menu context,
        // e.g. backend preview or a URL with no matching menu item).
        $activeMenuItemId = 0;

        if ($app->isClient('site')) {
            $active           = $app->getMenu()->getActive();
            $activeMenuItemId = $active ? (int) $active->id : 0;
        }

        $pattern = '/\{accesslevel\s+id="(\d+)"(?:\s+menuitem="(\d+)")?\s*\}(.*?)\{\/accesslevel\}/is';

        $article->text = preg_replace_callback(
            $pattern,
            function (array $matches) use ($userLevels, $fallback, $activeMenuItemId): string {
                $requiredLevel    = (int) $matches[1];
                $requiredMenuItem = (int) $matches[2]; // 0 when the attribute is absent
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
            $article->text
        );
    }
}
