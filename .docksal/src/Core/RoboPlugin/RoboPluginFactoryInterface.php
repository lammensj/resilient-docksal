<?php

/**
 * @file
 * File: RoboPluginFactoryInterface.php.
 */

declare(strict_types=1);

namespace Resilient\Core\RoboPlugin;

use Resilient\Core\Plugins\RoboPlugin\DrupalRoboPlugin;
use Resilient\Core\Plugins\RoboPlugin\GrumphpRoboPlugin;
use Resilient\Core\Plugins\RoboPlugin\PhpPackagesRoboPlugin;
use Resilient\Core\Plugins\RoboPlugin\WordpressRoboPlugin;

/**
 * Interface RoboPluginFactoryInterface.
 */
interface RoboPluginFactoryInterface
{

    public const GRUMPHP = 'grumphp';
    public const PHP_PACKAGES = 'php_packages';
    public const DRUPAL8 = 'drupal8';
    public const WORDPRESS = 'wp';

    public const MAPPING = [
      self::GRUMPHP => GrumphpRoboPlugin::class,
      self::PHP_PACKAGES => PhpPackagesRoboPlugin::class,
      self::DRUPAL8 => DrupalRoboPlugin::class,
      self::WORDPRESS => WordpressRoboPlugin::class,
    ];

    /**
     * Creates an instance of the desired Robo plugin.
     *
     * @param string $name
     *   The name of the desired Robo plugin.
     * @param array $configuration
     *   An array of configuration relevant to the plugin instance.
     *
     * @return \Resilient\Core\RoboPlugin\RoboPluginInterface
     */
    public function createInstance($name, array $configuration = []): RoboPluginInterface;
}
