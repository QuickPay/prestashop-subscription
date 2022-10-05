<?php

// autoload_real.php @generated by Composer

class ComposerAutoloaderInite1abd0e45ecff6461addb2da12d0a1c9
{
    private static $loader;

    public static function loadClassLoader($class)
    {
        if ('Composer\Autoload\ClassLoader' === $class) {
            require __DIR__ . '/ClassLoader.php';
        }
    }

    /**
     * @return \Composer\Autoload\ClassLoader
     */
    public static function getLoader()
    {
        if (null !== self::$loader) {
            return self::$loader;
        }

        require __DIR__ . '/platform_check.php';

        spl_autoload_register(array('ComposerAutoloaderInite1abd0e45ecff6461addb2da12d0a1c9', 'loadClassLoader'), true, false);
        self::$loader = $loader = new \Composer\Autoload\ClassLoader(\dirname(__DIR__));
        spl_autoload_unregister(array('ComposerAutoloaderInite1abd0e45ecff6461addb2da12d0a1c9', 'loadClassLoader'));

        require __DIR__ . '/autoload_static.php';
        call_user_func(\Composer\Autoload\ComposerStaticInite1abd0e45ecff6461addb2da12d0a1c9::getInitializer($loader));

        $loader->register(false);

        return $loader;
    }
}
