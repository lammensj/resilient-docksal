<?php

/**
 * @file
 * File: DownloadCommands.php.
 */

declare(strict_types=1);

namespace Resilient\Robo\Plugin\Commands;

use Resilient\Core\RoboPlugin\RoboPluginFactoryInterface;
use Robo\Collection\CollectionBuilder;

/**
 * Class DownloadCommands.
 */
class DownloadCommands extends AbstractCommands
{

    /**
     * Downloads the project.
     *
     * @command resilient:download
     *
     * @return \Robo\Collection\CollectionBuilder
     *   Returns a collection builder to run.
     *
     * @throws \DI\NotFoundException
     */
    public function download(): CollectionBuilder
    {
        $this->initialize();
        $type = $this->configFactory->get('project_type');
        $pluginConfig = [
          'collection_builder' => $this->collectionBuilder,
        ];

        $this->collectionBuilder->addCode(
          function () {
              $this->say('Downloading project files...');
          }
        );

        // Quality tools.
        $this->collectionBuilder->addCode(
          function () {
              $this->say('Downloading quality tools...');
          }
        );
        /** @var \Resilient\Core\RoboPlugin\RoboPluginDownloaderInterface $grumphp */
        $grumphp = $this->roboPluginFactory->createInstance(RoboPluginFactoryInterface::GRUMPHP, $pluginConfig);
        $this->collectionBuilder->addTaskList($grumphp->download());
        $this->collectionBuilder->addCode(
          function () {
              $this->say('Downloading quality tools... DONE');
          }
        );

        // Additional PHP packages.
        $this->collectionBuilder->addCode(
          function () {
              $this->say('Downloading additional PHP packages...');
          }
        );
        /** @var \Resilient\Core\RoboPlugin\RoboPluginDownloaderInterface $phpPackages */
        $phpPackages = $this->roboPluginFactory->createInstance(RoboPluginFactoryInterface::PHP_PACKAGES, $pluginConfig);
        $this->collectionBuilder->addTaskList($phpPackages->download());
        $this->collectionBuilder->addCode(
          function () {
              $this->say('Downloading additional PHP packages... DONE');
          }
        );

        // Core project files.
        $this->collectionBuilder->addCode(
          function () use ($type) {
              $this->say(
                sprintf(
                  'Downloading core project files for type \'%s\'...',
                  $type
                )
              );
          }
        );
        /** @var \Resilient\Core\RoboPlugin\RoboPluginDownloaderInterface $plugin */
        $plugin = $this->roboPluginFactory->createInstance($type, $pluginConfig);
        $this->collectionBuilder->addTaskList($plugin->download());
        $this->collectionBuilder->addCode(
          function () use ($type) {
              $this->say(
                sprintf(
                  'Downloading core project files for type \'%s\'... DONE',
                  $type
                )
              );
          }
        );

        $this->collectionBuilder->addCode(
          function () {
              $this->say('Downloading project files... DONE');
          }
        );

        return $this->collectionBuilder;
    }
}