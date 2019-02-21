<?php

/**
 * @file
 * File: DownloadCommands.php.
 */

declare(strict_types=1);

namespace Resilient\Robo\Plugin\Commands;

/**
 * Class DownloadCommands.
 */
class DownloadCommands extends AbstractCommands
{

    const DRUPAL_PROJECT = 'lammensj/drupal-project:^2.0';
    const WP_PROJECT = 'wordplate/wordplate';
    const WP_CLI = 'wp-cli/wp-cli';
    const WP_CLI_PACKAGE = 'wp-cli/package-command';
    const WP_CLI_DOTENV = 'aaemnnosttv/wp-cli-dotenv-command';
    const WP_TIMBER_ST = 'upstatement/timber-starter-theme:dev-master';

    /**
     * Downloads the project.
     *
     * @command resilient:download
     *
     * @return \Robo\Collection\CollectionBuilder
     *   Returns a collection builder to run.
     *
     * @throws \Robo\Exception\TaskException
     */
    public function download()
    {
        $this->initialize();
        $this->collection->addCode(
          function () {
              $this->say('Downloading project files...');
          }
        );

        // Quality tools.
        $this->collection->addCode(
          function () {
              $this->say('Downloading quality tools...');
          }
        );
        $this->collection->addTaskList($this->loadGrumphpTasks());
        $this->collection->addCode(
          function () {
              $this->say('Downloading quality tools... DONE');
          }
        );

        // Additional PHP packages.
        $this->collection->addCode(
          function () {
              $this->say('Downloading additional PHP packages...');
          }
        );
        $this->collection->addTaskList($this->loadExtraPackages());
        $this->collection->addCode(
          function () {
              $this->say('Downloading additional PHP packages... DONE');
          }
        );

        // Core project files.
        $this->collection->addCode(
          function () {
              $this->say(
                sprintf(
                  'Downloading core project files for type \'%s\'...',
                  $this->type
                )
              );
          }
        );
        switch ($this->type) {
            case 'wp':
                $this->collection->addTaskList($this->loadWordpressTasks());
                break;
            case 'drupal8':
            default:
                $this->collection->addTaskList($this->loadDrupal8Tasks());
                break;
        }
        $this->collection->addCode(
          function () {
              $this->say(
                sprintf(
                  'Downloading core project files for type \'%s\'... DONE',
                  $this->type
                )
              );
          }
        );

        $this->collection->addCode(
          function () {
              $this->say('Downloading project files... DONE');
          }
        );

        return $this->collection;
    }

    /**
     * Load tasks for GrumPHP.
     *
     * @return array
     *   Returns an array of tasks for GrumPHP.
     *
     * @throws \Robo\Exception\TaskException
     */
    private function loadGrumphpTasks()
    {
        $grumphpTasks = [];

        $grumphpDir = sprintf(
          '%s/assets/%s/grumphp',
          $this->setupPath,
          $this->type
        );
        if (file_exists($grumphpDir)) {
            $grumphpTasks[] = $this->taskFilesystemStack()
              ->mirror(
                $grumphpDir,
                $this->projectRoot,
                null,
                ['override' => true]
              );
            $grumphpTasks[] = $this->taskReplaceInFile(
              sprintf('%s/grumphp.yml', $this->projectRoot)
            )
              ->from('[jira_code]')
              ->to($this->getConfigValue('jira_code'));
        }

        return $grumphpTasks;
    }

    /**
     * Load tasks for additional PHP packages.
     *
     * @return array
     *   Returns an array of tasks for additional PHP packages.
     */
    private function loadExtraPackages()
    {
        $pckgTasks = [];

        $sourceList = sprintf(
          '%s/assets/%s/composer.extra.json',
          $this->setupPath,
          $this->type
        );
        $destinList = sprintf('%s/composer.extra.json', $this->projectRoot);

        if (
          !file_exists($destinList)
          && file_exists($sourceList)
        ) {
            $pckgTasks[] = $this->taskFilesystemStack()
              ->copy($sourceList, $destinList)
              ->remove(sprintf('%s/composer.lock', $this->projectRoot));
            $pckgTasks[] = $this->taskComposerInstall()
              ->workingDir($this->projectRoot);
        }

        return $pckgTasks;
    }

    /**
     * Load tasks for downloading Wordpress.
     *
     * @return array
     *   Returns an array of tasks for Wordpress.
     *
     * @throws \Robo\Exception\TaskException
     */
    private function loadWordpressTasks()
    {
        $wpTasks = [];

        $coreFolderPath = sprintf(
          '%s/%s',
          $this->frmwrkPath,
          $this->getConfigValue('app_root')
        );

        if (!file_exists(
          sprintf('%s/wp-load.php', $coreFolderPath)
        )) {
            if (file_exists(sprintf('%s/composer.json', $this->frmwrkPath))) {
                $wpTasks[] = $this->taskComposerInstall()
                  ->workingDir($this->frmwrkPath);
            } else {
                $wpTasks[] = $this->taskComposerCreateProject()
                  ->workingDir($this->projectRoot)
                  ->source(self::WP_PROJECT)
                  ->target($this->getConfigValue('frmwrk_root'));

                $wpTasks[] = $this->taskComposerRequire()
                  ->workingDir($this->frmwrkPath)
                  ->dependency(self::WP_CLI)
                  ->dependency(self::WP_CLI_PACKAGE)
                  ->dependency(self::WP_TIMBER_ST);
            }

            // Install DotEnv package for WP CLI.
            $wpCli = sprintf('%s/vendor/bin/wp', $this->frmwrkPath);
            $wpTasks[] = $this->taskExec(
              sprintf(
                '%s package install %s',
                $wpCli,
                self::WP_CLI_DOTENV
              )
            );

            // Update environment variables.
            $env = [
              'DB_NAME' => $this->getConfigValue('database.name'),
              'DB_USER' => $this->getConfigValue('database.user'),
              'DB_PASSWORD' => $this->getConfigValue('database.password'),
              'DB_HOST' => $this->getConfigValue('database.host'),
              'WP_ENV' => 'local',
            ];
            $dotEnvFile = sprintf('%s/.env', $this->frmwrkPath);
            $execStack = $this->taskExecStack();
            foreach ($env as $key => $value) {
                $execStack->exec(
                  sprintf(
                    '%s dotenv set %s %s --file=%s',
                    $wpCli,
                    $key,
                    $value,
                    $dotEnvFile
                  )
                );
            }
            $execStack->exec(
              sprintf('%s dotenv salts generate --file=%s', $wpCli, $dotEnvFile)
            );
            $wpTasks[] = $execStack;
        }

        return $wpTasks;
    }

    /**
     * Load tasks for downloading Drupal 8.
     *
     * @return array
     *   Returns an array of tasks for Drupal 8.
     *
     * @throws \Robo\Exception\TaskException
     */
    private function loadDrupal8Tasks()
    {
        $drupalTasks = [];

        $defaultFolderPath = sprintf(
          '%s/%s/sites/default',
          $this->frmwrkPath,
          $this->getConfigValue('app_root')
        );

        if (!file_exists(
          sprintf('%s/settings.local.php', $defaultFolderPath)
        )) {
            if (file_exists(sprintf('%s/composer.json', $this->frmwrkPath))) {
                $drupalTasks[] = $this->taskComposerInstall()
                  ->workingDir($this->frmwrkPath);
            } else {
                $drupalTasks[] = $this->taskComposerCreateProject()
                  ->workingDir($this->projectRoot)
                  ->source(self::DRUPAL_PROJECT)
                  ->target($this->getConfigValue('frmwrk_root'));
            }

            // Copy local settings files into Drupal directory.
            $source = sprintf(
              '%s/assets/%s/core',
              $this->setupPath,
              $this->type
            );
            $drupalTasks[] = $this->taskCopyDir(
              [$source => $defaultFolderPath]
            );

            // Insert database credentials.
            $localSettingsFilePath = sprintf('%s/settings.local.php', $defaultFolderPath);
            $drupalTasks[] = $this->taskReplaceInFile($localSettingsFilePath)
              ->from('INSERT_DB_HOST')
              ->to($this->getConfigValue('database.host'));
            $drupalTasks[] = $this->taskReplaceInFile($localSettingsFilePath)
              ->from('INSERT_DB_USER')
              ->to($this->getConfigValue('database.user'));
            $drupalTasks[] = $this->taskReplaceInFile($localSettingsFilePath)
              ->from('INSERT_DB_PASSWORD')
              ->to($this->getConfigValue('database.password'));
            $drupalTasks[] = $this->taskReplaceInFile($localSettingsFilePath)
              ->from('INSERT_DB_NAME')
              ->to($this->getConfigValue('database.name'));

            // Enable including local.settings.php.
            $settingsFilePath = sprintf('%s/settings.php', $defaultFolderPath);
            $drupalTasks[] = $this->taskReplaceInFile($settingsFilePath)
              ->from('# if (file_exists')
              ->to('if (file_exists');
            $drupalTasks[] = $this->taskReplaceInFile($settingsFilePath)
              ->from('#   include')
              ->to('  include');
            $drupalTasks[] = $this->taskReplaceInFile($settingsFilePath)
              ->from('# }')
              ->to('}');

            // Protect the local settings files.
            $chmod = sprintf('chmod 644 %s/*local*', $defaultFolderPath);
            $drupalTasks[] = $this->taskExec($chmod);
        }

        return $drupalTasks;
    }
}
