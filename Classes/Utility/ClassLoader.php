<?php
namespace Evoweb\Extender\Utility;

/**
 * This file is developed by evoweb.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class ClassLoader
 *
 * @author Sebastian Fischer <typo3@evoweb.de>
 */
class ClassLoader implements \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * Class cache instance
     *
     * @var \TYPO3\CMS\Core\Cache\Frontend\PhpFrontend
     */
    protected $classCache;

    /**
     * Known classnames that cause problems and can not be extended
     *
     * @var array
     */
    protected $excludedClassNames = [
        'Symfony\Polyfill\Mbstring\Mbstring'
    ];

    /**
     * Register instance of this class as spl autoloader
     */
    public static function registerAutoloader()
    {
        spl_autoload_register([new self(), 'loadClass'], true, true);
    }

    /**
     * ClassLoader constructor.
     *
     * @param \TYPO3\CMS\Core\Cache\Frontend\PhpFrontend|null $classCache
     */
    public function __construct($classCache = null)
    {
        $this->classCache = $classCache;
    }

    /**
     * @return \TYPO3\CMS\Core\Cache\Frontend\FrontendInterface|\TYPO3\CMS\Core\Cache\Frontend\PhpFrontend|null
     */
    protected function getClassCache(): \TYPO3\CMS\Core\Cache\Frontend\FrontendInterface
    {
        if (is_null($this->classCache)) {
            /**
             * Cache manager
             *
             * @var \TYPO3\CMS\Core\Cache\CacheManager $cacheManager
             */
            $cacheManager = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\CacheManager::class);
            // Set configuration in case some cache settings are not loaded by now.
            $cacheManager->setCacheConfigurations($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']);
            /** @var \TYPO3\CMS\Core\Cache\Frontend\PhpFrontend $cache */
            $this->classCache = $cacheManager->getCache('extender');
        }
        return $this->classCache;
    }

    /**
     * Loads php files containing classes or interfaces part of the
     * classes directory of an extension.
     *
     * @param string $className Name of the class/interface to load
     *
     * @return bool
     */
    public function loadClass($className)
    {
        $className = ltrim($className, '\\');

        if ($this->isExcludedClassName($className)) {
            return false;
        }

        $extensionKey = $this->getExtensionKey($className);

        if (!$this->isValidClassName($className, $extensionKey)) {
            return false;
        }

        $cacheEntryIdentifier = GeneralUtility::underscoredToLowerCamelCase($extensionKey) . '_' .
            str_replace('\\', '_', $className);

        if ($this->getClassCache()) {
            if (!$this->getClassCache()->has($cacheEntryIdentifier)) {
                /**
                 * Class cache manager
                 *
                 * @var \Evoweb\Extender\Utility\ClassCacheManager $classCacheManager
                 */
                $classCacheManager = GeneralUtility::makeInstance(
                    \Evoweb\Extender\Utility\ClassCacheManager::class,
                    $this->getClassCache()
                );
                $classCacheManager->reBuild();
            }
            $this->getClassCache()->requireOnce($cacheEntryIdentifier);
            return true;
        }

        return false;
    }

    /**
     * @param string $className
     *
     * @return bool
     */
    protected function isExcludedClassName($className)
    {
        $result = false;

        if (in_array($className, $this->excludedClassNames)) {
            $result = true;
        }

        return $result;
    }

    /**
     * Get extension key from namespaced classname
     *
     * @param string $className Class name
     *
     * @return string
     */
    protected function getExtensionKey($className)
    {
        $extensionKey = null;

        if (strpos($className, '\\') !== false) {
            $namespaceParts = GeneralUtility::trimExplode(
                '\\',
                $className,
                0,
                (substr($className, 0, 9) === 'TYPO3\\CMS' ? 4 : 3)
            );
            array_pop($namespaceParts);
            $extensionKey = GeneralUtility::camelCaseToLowerCaseUnderscored(array_pop($namespaceParts));
        }

        return $extensionKey;
    }

    /**
     * Find out if a class name is valid
     *
     * @param string $className Class name
     * @param string $extensionKey Extension key
     *
     * @return bool
     */
    protected function isValidClassName($className, $extensionKey)
    {
        $oldClassnamePart = substr(strtolower($className), 0, 5);

        $extensionConfiguration = array();
        if (isset($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][$extensionKey])) {
            $extensionConfiguration = (array) $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][$extensionKey];
        }

        return (bool) preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9\\\\_\x7f-\xff]*$/', $className)
            && (
                strpos($oldClassnamePart, 'tx_') === false
                && strpos($oldClassnamePart, 'ux_') === false
                && strpos($oldClassnamePart, 'user_') === false
            )
            && (
                isset($extensionConfiguration['extender'])
                && is_array($extensionConfiguration['extender'])
                && isset($extensionConfiguration['extender'][$className])
            );
    }
}
