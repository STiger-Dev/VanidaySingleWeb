<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit6744c72a6da1c3b599416a4ab19f6057
{
    public static $prefixLengthsPsr4 = array (
        'B' => 
        array (
            'Booknetic_PHPMailer\\PHPMailer\\' => 30,
            'BookneticAddon\\EmailWorkflow\\' => 29,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Booknetic_PHPMailer\\PHPMailer\\' => 
        array (
            0 => __DIR__ . '/..' . '/phpmailer/phpmailer/src',
        ),
        'BookneticAddon\\EmailWorkflow\\' => 
        array (
            0 => __DIR__ . '/../..' . '/App',
        ),
    );

    public static $classMap = array (
        'BookneticAddon\\EmailWorkflow\\Backend\\Ajax' => __DIR__ . '/../..' . '/App/Backend/Ajax.php',
        'BookneticAddon\\EmailWorkflow\\EmailWorkflowAddon' => __DIR__ . '/../..' . '/App/EmailWorkflowAddon.php',
        'BookneticAddon\\EmailWorkflow\\EmailWorkflowDriver' => __DIR__ . '/../..' . '/App/EmailWorkflowDriver.php',
        'Booknetic_PHPMailer\\PHPMailer\\Exception' => __DIR__ . '/..' . '/phpmailer/phpmailer/src/Exception.php',
        'Booknetic_PHPMailer\\PHPMailer\\OAuth' => __DIR__ . '/..' . '/phpmailer/phpmailer/src/OAuth.php',
        'Booknetic_PHPMailer\\PHPMailer\\PHPMailer' => __DIR__ . '/..' . '/phpmailer/phpmailer/src/PHPMailer.php',
        'Booknetic_PHPMailer\\PHPMailer\\POP3' => __DIR__ . '/..' . '/phpmailer/phpmailer/src/POP3.php',
        'Booknetic_PHPMailer\\PHPMailer\\SMTP' => __DIR__ . '/..' . '/phpmailer/phpmailer/src/SMTP.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit6744c72a6da1c3b599416a4ab19f6057::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit6744c72a6da1c3b599416a4ab19f6057::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit6744c72a6da1c3b599416a4ab19f6057::$classMap;

        }, null, ClassLoader::class);
    }
}
