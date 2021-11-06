<?php

declare(strict_types=1);

namespace Evoweb\Extender;

/*
 * This file is part of the "extender" Extension for TYPO3 CMS.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use Evoweb\Extender\Utility\ClassLoader;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Cache\Backend\FileBackend;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Package\AbstractServiceProvider;

class ServiceProvider extends AbstractServiceProvider
{
    protected static function getPackagePath(): string
    {
        return __DIR__ . '/../';
    }

    public function getFactories(): array
    {
        return [];
    }

    public function getExtensions(): array
    {
        return [
            EventDispatcherInterface::class => [ static::class, 'registerExtenderAutoloader' ],
        ] + parent::getExtensions();
    }

    public static function registerExtenderAutoloader(
        ContainerInterface $container,
        EventDispatcherInterface $eventDispatcher
    ): EventDispatcherInterface {
        if ($container->has(ClassLoader::class)) {
            // Register extender cache
            // needs to stay above autoloader registration to always have settings before using the cache
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['extender'] = [
                'frontend' => PhpFrontend::class,
                'backend' => FileBackend::class,
                'groups' => [
                    'all',
                    'system',
                ],
                'options' => [
                    'defaultLifetime' => 0,
                ],
            ];

            spl_autoload_register([$container->get(ClassLoader::class), 'loadClass'], true, true);
        }
        return $eventDispatcher;
    }
}
