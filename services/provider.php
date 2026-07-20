<?php

/**
 * @package     Plugin.Content.TagAccess
 * @subpackage  Teaching example
 *
 * Service provider - the bootstrap file every modern namespaced Joomla
 * plugin needs. Without this file Joomla never instantiates the plugin
 * class: it installs and enables cleanly but silently does nothing.
 *
 * v1.0.1 (2026-07-17): file added after exactly that was caught pre-install.
 * v1.0.2 (2026-07-17): aligned with the core Joomla 6.1 pattern (config-only
 * constructor + lazy instantiation), verified against
 * plugins/content/joomla/services/provider.php and CMSPlugin::__construct
 * on the 6.1.1 tag. The previous dispatcher-first style still worked via a
 * deprecated BC branch but is removed in Joomla 7.
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Plugin\Content\TagAccess\Extension\TagAccess;

return new class () implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->set(
            PluginInterface::class,
            $container->lazy(TagAccess::class, function (Container $container) {
                $plugin = new TagAccess(
                    (array) PluginHelper::getPlugin('content', 'tagaccess')
                );
                $plugin->setApplication(Factory::getApplication());

                return $plugin;
            })
        );
    }
};
