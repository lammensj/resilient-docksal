<?php

/**
 * @file
 * File: Wordpress.php.
 */

declare(strict_types=1);

namespace Resilient\Core\Plugins\RoboPlugin;

use Resilient\Core\RoboPlugin\RoboPluginDownloaderInterface;
use Resilient\Core\RoboPlugin\RoboPluginInstallerInterface;

/**
 * Class WordpressRoboPlugin.
 */
class WordpressRoboPlugin extends AbstractRoboPlugin implements RoboPluginDownloaderInterface, RoboPluginInstallerInterface
{

    protected const WP_PROJECT = 'wordplate/wordplate';
    protected const WP_CLI = 'wp-cli/wp-cli-bundle';
    protected const WP_CLI_DOTENV = 'aaemnnosttv/wp-cli-dotenv-command';
    protected const WP_TIMBER_ST = 'upstatement/timber-starter-theme:dev-master';

    /**
     * {@inheritdoc}
     */
    public function download(): array
    {
        $wpTasks = [];

        $coreFolderPath = sprintf(
          '%s/%s',
          $this->configFactory->get('frmwrk_path'),
          $this->configFactory->get('app_root')
        );

        if (!file_exists(
          sprintf('%s/wp-load.php', $coreFolderPath)
        )) {
            if (file_exists(sprintf('%s/composer.json', $this->configFactory->get('frmwrk_path')))) {
                $wpTasks[] = $this->taskComposerInstall()
                  ->workingDir($this->configFactory->get('frmwrk_path'));
            } else {
                $wpTasks[] = $this->taskComposerCreateProject()
                  ->workingDir($this->configFactory->get('project_root'))
                  ->source(self::WP_PROJECT)
                  ->target($this->configFactory->get('frmwrk_root'));

                $wpTasks[] = $this->taskComposerRequire()
                  ->workingDir($this->configFactory->get('frmwrk_path'))
                  ->dependency(self::WP_CLI)
                  ->dependency(self::WP_TIMBER_ST);
            }

            // Install DotEnv package for WP CLI.
            $wpCli = sprintf('%s/vendor/bin/wp', $this->configFactory->get('frmwrk_path'));
            $wpTasks[] = $this->taskExec(
              sprintf(
                '%s package install %s',
                $wpCli,
                self::WP_CLI_DOTENV
              )
            );

            // Update environment variables.
            $env = [
              'DB_NAME' => $this->configFactory->get('database.name'),
              'DB_USER' => $this->configFactory->get('database.user'),
              'DB_PASSWORD' => $this->configFactory->get('database.password'),
              'DB_HOST' => $this->configFactory->get('database.host'),
              'WP_ENV' => 'local',
            ];
            $dotEnvFile = sprintf('%s/.env', $this->configFactory->get('frmwrk_path'));
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
     * {@inheritdoc}
     */
    public function install(): array
    {
        $tasks = [];

        $wpCli = sprintf('%s/vendor/bin/wp', $this->configFactory->get('frmwrk_path'));
        $path = sprintf(
          '%s/%s',
          $this->configFactory->get('frmwrk_path'),
          $this->configFactory->get('app_root'),
        );

        // Wipe the db.
        $tasks[] = $this->taskExec(sprintf('%s db reset --yes', $wpCli))
          ->option('yes')
          ->option('path', $path, '=');

        // Install Wordpress.
        $tasks[] = $this->taskExec(sprintf('%s core install', $wpCli))
          ->option('url', $this->configFactory->get('VIRTUAL_HOST'), '=')
          ->option('title', $this->configFactory->get('site.name'), '=')
          ->option('admin_user', $this->configFactory->get('site.account.name'), '=')
          ->option('admin_email', $this->configFactory->get('site.account.email'), '=')
          ->option('admin_password', $this->configFactory->get('site.account.password'), '=')
          ->option('skip-email')
          ->option('path', $path, '=');

        return $tasks;
    }
}
