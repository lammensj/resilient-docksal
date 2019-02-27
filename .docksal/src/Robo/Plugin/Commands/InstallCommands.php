<?php

/**
 * @file
 * File: InstallCommands.php.
 */

declare(strict_types=1);

namespace Resilient\Robo\Plugin\Commands;

use Robo\Collection\CollectionBuilder;

/**
 * Class InstallCommands.
 */
class InstallCommands extends AbstractCommands
{

    /**
     * Installs the project.
     *
     * @command resilient:install
     *
     * @return \Robo\Collection\CollectionBuilder
     *   Returns a collection builder to run.
     *
     * @throws \DI\NotFoundException
     */
    public function install(): CollectionBuilder
    {
        $this->initialize();
        $type = $this->configFactory->get('project_type');
        $pluginConfig = [
          'collection_builder' => $this->collectionBuilder,
        ];

        $this->collectionBuilder->addCode(
          function () use ($type) {
              $this->say(
                sprintf('Installing project type \'%s\'...', $type)
              );
          }
        );
        /** @var \Resilient\Core\RoboPlugin\RoboPluginInstallerInterface $plugin */
        $plugin = $this->roboPluginFactory->createInstance($type, $pluginConfig);
        $this->collectionBuilder->addTaskList($plugin->install());
        $this->collectionBuilder->addCode(
          function () use ($type) {
              $this->say(
                sprintf('Installing project type \'%s\'... DONE', $type)
              );
          }
        );

        return $this->collectionBuilder;
    }
}
