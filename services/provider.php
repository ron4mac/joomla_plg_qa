<?php
/**
* @package		plg_captcha_qa
* @copyright	Copyright (C) 2022-2026 RJCreations. All rights reserved.
* @license		GNU General Public License version 3 or later; see LICENSE.txt
* @since		2.0.0
*/
\defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use RJCreations\Plugin\Captcha\Qa\Extension\Qa;

return new class () implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->set(
            PluginInterface::class,
            function (Container $container): \RJCreations\Plugin\Captcha\Qa\Extension\Qa {
                $plugin = new Qa(
                    $container->get(DispatcherInterface::class),
                    (array) PluginHelper::getPlugin('captcha', 'qa')
                );
                $plugin->setApplication(Factory::getApplication());
                return $plugin;
            }
        );
    }
};
