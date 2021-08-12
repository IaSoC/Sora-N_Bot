<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit4f1e25e480fa336b45dd3ce2f2837528
{
    public static $prefixLengthsPsr4 = array (
        'W' => 
        array (
            'Workerman\\' => 10,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Workerman\\' => 
        array (
            0 => __DIR__ . '/..' . '/workerman/workerman',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit4f1e25e480fa336b45dd3ce2f2837528::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit4f1e25e480fa336b45dd3ce2f2837528::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit4f1e25e480fa336b45dd3ce2f2837528::$classMap;

        }, null, ClassLoader::class);
    }
}
