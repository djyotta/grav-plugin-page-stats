<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInita2c090e39093f29eb47e896c40be01d1
{
    public static $prefixLengthsPsr4 = array (
        'G' => 
        array (
            'Grav\\Plugin\\PageStats\\' => 22,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Grav\\Plugin\\PageStats\\' => 
        array (
            0 => __DIR__ . '/../..' . '/classes',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
        'Grav\\Plugin\\PageStatsPlugin' => __DIR__ . '/../..' . '/page-stats.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInita2c090e39093f29eb47e896c40be01d1::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInita2c090e39093f29eb47e896c40be01d1::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInita2c090e39093f29eb47e896c40be01d1::$classMap;

        }, null, ClassLoader::class);
    }
}