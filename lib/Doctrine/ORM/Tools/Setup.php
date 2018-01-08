<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\ClassLoader;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use function class_exists;
use function sys_get_temp_dir;
use function md5;
use function extension_loaded;
use Doctrine\Common\Cache\ApcuCache;
use Doctrine\Common\Cache\MemcachedCache;
use Doctrine\Common\Cache\RedisCache;

/**
 * Convenience class for setting up Doctrine from different installations and configurations.
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
class Setup
{
    /**
     * Use this method to register all autoloads for a downloaded Doctrine library.
     * Pick the directory the library was uncompressed into.
     *
     * @param string $directory
     *
     * @return void
     */
    public static function registerAutoloadDirectory($directory)
    {
        if (!class_exists('Doctrine\Common\ClassLoader', false)) {
            require_once $directory . "/Doctrine/Common/ClassLoader.php";
        }

        $loader = new ClassLoader("Doctrine", $directory);
        $loader->register();

        $loader = new ClassLoader('Symfony\Component', $directory . "/Doctrine");
        $loader->register();
    }

    /**
     * Creates a configuration with an annotation metadata driver.
     *
     * @param array   $paths
     * @param boolean $isDevMode
     * @param string  $proxyDir
     * @param Cache   $cache
     *
     * @return Configuration
     */
    public static function createAnnotationMetadataConfiguration(array $paths, $isDevMode = false, $proxyDir = null, Cache $cache = null)
    {
        $config = self::createConfiguration($isDevMode, $proxyDir, $cache);
        $config->setMetadataDriverImpl($config->newDefaultAnnotationDriver($paths));

        return $config;
    }

    /**
     * Creates a configuration with a xml metadata driver.
     *
     * @param array   $paths
     * @param boolean $isDevMode
     * @param string  $proxyDir
     * @param Cache   $cache
     *
     * @return Configuration
     */
    public static function createXMLMetadataConfiguration(array $paths, $isDevMode = false, $proxyDir = null, Cache $cache = null)
    {
        $config = self::createConfiguration($isDevMode, $proxyDir, $cache);
        $config->setMetadataDriverImpl(new XmlDriver($paths));

        return $config;
    }

    /**
     * Creates a configuration without a metadata driver.
     *
     * @param bool   $isDevMode
     * @param string $proxyDir
     * @param Cache  $cache
     *
     * @return Configuration
     */
    public static function createConfiguration($isDevMode = false, $proxyDir = null, Cache $cache = null)
    {
        $proxyDir = $proxyDir ?: sys_get_temp_dir();

        $cache = self::createCacheConfiguration($isDevMode, $proxyDir, $cache);

        $config = new Configuration();
        $config->setMetadataCacheImpl($cache);
        $config->setQueryCacheImpl($cache);
        $config->setResultCacheImpl($cache);
        $config->setProxyDir($proxyDir);
        $config->setProxyNamespace('DoctrineProxies');
        $config->setAutoGenerateProxyClasses($isDevMode);

        return $config;
    }

    private static function createCacheConfiguration(bool $isDevMode, string $proxyDir, ?Cache $cache) :  Cache
    {
        $cache = self::createCacheInstance($isDevMode, $cache);

        if ( ! $cache instanceof CacheProvider) {
            return $cache;
        }

        $namespace = $cache->getNamespace();

        if ($namespace !== '') {
            $namespace .= ':';
        }

        $cache->setNamespace($namespace . 'dc2_' . md5($proxyDir) . '_'); // to avoid collisions

        return $cache;
    }

    private static function createCacheInstance(bool $isDevMode, ?Cache $cache) : Cache
    {
        if ($cache !== null) {
            return $cache;
        }

        if ($isDevMode === true) {
            return new ArrayCache();
        }

        if (extension_loaded('apcu')) {
            return new ApcuCache();
        }


        if (extension_loaded('memcached')) {
            $memcache = new \Memcached();
            $memcache->addServer('127.0.0.1', 11211);

            $cache = new MemcachedCache();
            $cache->setMemcache($memcache);

            return $cache;
        }

        if (extension_loaded('redis')) {
            $redis = new \Redis();
            $redis->connect('127.0.0.1');

            $cache = new RedisCache();
            $cache->setRedis($redis);

            return $cache;
        }

        return new ArrayCache();
    }
}
