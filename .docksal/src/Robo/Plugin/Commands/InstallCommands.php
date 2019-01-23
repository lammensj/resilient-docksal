<?php

/**
 * @file
 * File: InstallCommands.php.
 */

declare(strict_types=1);

namespace Resilient\Robo\Plugin\Commands;

use Symfony\Component\Finder\Finder;

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
     */
    public function install()
    {
        $this->initialize();
        $this->collection->addCode(
          function () {
              $this->say(
                sprintf('Installing project type \'%s\'...', $this->type)
              );
          }
        );
        switch ($this->type) {
            case 'drupal8':
            default:
                $this->collection->addTaskList($this->loadDrupal8Tasks());
                break;
        }
        $this->collection->addCode(
          function () {
              $this->say(
                sprintf('Installing project type \'%s\'... DONE', $this->type)
              );
          }
        );

        return $this->collection;
    }

    /**
     * Load tasks for installing Drupal 8.
     *
     * @return array
     *   Returns an array of tasks for Drupal 8.
     */
    private function loadDrupal8Tasks()
    {
        $drupalTasks = [];

        // Include the necessary external tasks.
        $drushStackClass = '\Boedah\Robo\Task\Drush\DrushStack';

        $drupalTasks[] = $this->task($drushStackClass)
          ->drupalRootDirectory($this->frmwrkPath)
          ->drush('sql-drop');

        $dbFiles = Finder::create()
          ->files()
          ->name('drupal8.sql*')
          ->in($this->projectRoot);
        if ($dbFiles->hasResults()) {
            $drupalTasks[] = $this->task($drushStackClass)
              ->drupalRootDirectory($this->frmwrkPath)
              ->drush(
                sprintf('sqlq --file=%s', reset($dbFiles)->getPathname())
              );
        } elseif (file_exists(
          sprintf('%s/config/sync/core.extension.yml', $this->frmwrkPath)
        )) {
            $drupalTasks[] = $this->task($drushStackClass)
              ->drupalRootDirectory($this->frmwrkPath)
              ->drush(
                sprintf(
                  'si config_installer config_installer_sync_configure_form.sync_directory=%s/config/sync',
                  $this->frmwrkPath
                )
              );
        }
        else {
            $drupalTasks[] = $this->task($drushStackClass)
              ->drupalRootDirectory($this->frmwrkPath)
              ->siteInstall('resilient')
              ->drush('entup');
        }

        return $drupalTasks;
    }
}
