<?php

/**
 * @file
 * File: AbstractCommands.php.
 */

declare(strict_types=1);

namespace Resilient\Robo\Plugin\Commands;

use Robo\Exception\TaskException;
use Robo\Robo;
use Robo\Tasks;

/**
 * Class AbstractCommands.
 */
abstract class AbstractCommands extends Tasks
{

    /**
     * The project root.
     *
     * @var string
     */
    protected $projectRoot;

    /**
     * The absolute path to the setup folder.
     *
     * @var string
     */
    protected $setupPath;

    /**
     * The absolute path to the framework root.
     *
     * @var string
     */
    protected $frmwrkPath;

    /**
     * The project type.
     *
     * @var string
     */
    protected $type;

    /**
     * The collection builder.
     *
     * @var \Robo\Collection\CollectionBuilder
     */
    protected $collection;

    /**
     * Load all the configuration file.
     */
    protected function initialize()
    {
        $this->collection = $this->collectionBuilder();

        $this->projectRoot = explode(' ', getenv('PROJECT_ROOT'))[0];
        $this->loadRoboConfiguration();
        $this->validateConfig();

        $this->type = $this->getConfigValue('project_type');

        $this->setupPath = realpath(
          dirname($this->getConfigValue('ROBO_CONFIG'))
        );

        $this->frmwrkPath = implode(
          '/',
          [$this->projectRoot, $this->getConfigValue('frmwrk_root')]
        );
        $this->frmwrkPath = array_reduce(
          explode('/', $this->frmwrkPath),
          function ($a, $b) {
              if ($a === 0) {
                  $a = '/';
              }
              if ($b === '' || $b === '.') {
                  return $a;
              }
              if ($b === '..') {
                  return dirname($a);
              }

              return preg_replace('/\/+/', '/', sprintf('%s/%s', $a, $b));
          },
          0
        );
    }

    /**
     * Get a configuration value.
     *
     * @param string $key
     *   The key of the config.
     *
     * @return mixed
     *   Returns the configuration value.
     *
     * @throws \Robo\Exception\TaskException
     */
    protected function getConfigValue($key)
    {
        $value = getenv($key);

        if (!$value) {
            $key = sprintf('%s.%s', $this->getConfigPrefix(), $key);
            if (!Robo::config()->has($key)) {
                throw new TaskException(
                  $this,
                  sprintf('Robo config with key \'%s\' not found.', $key)
                );
            }
            $value = Robo::config()->get($key);
        }

        return $value;
    }

    /**
     * Get the configuration prefix.
     *
     * @return string
     *   Returns the configuration prefix.
     */
    protected function getConfigPrefix()
    {
        return 'command.resilient';
    }

    /**
     * Load the robo.yml variables.
     */
    private function loadRoboConfiguration()
    {
        if (getenv('RESILIENT_ROBO_LOADED') === false) {
            $this->say('Loading Robo variables...');
            Robo::loadConfiguration(
              [
                getenv('ROBO_CONFIG'),
              ]
            );
            putenv('RESILIENT_ROBO_LOADED=1');

            $this->say('Loading Robo variables... DONE');
        }
    }

    /**
     * Validate the provides configuration.
     *
     * @throws \Robo\Exception\TaskException
     */
    private function validateConfig()
    {
        $errorMessages = [];

        if ($this->getConfigValue('VIRTUAL_HOST') === 'customize-me.docksal') {
            $errorMessages[] = '- The VIRTUAL_HOST parameter in the docksal-local.env file has not been modified.';
        }

        $projectTypes = ['drupal8', 'wp', 'sf'];
        $projectType = $this->getConfigValue('project_type');
        if (!$projectType || !in_array($projectType, $projectTypes, true)) {
            $errorMessages[] = sprintf(
              '- The project_type parameter in the robo.yml file is not set or doesn\'t have a supported site type (found %s).',
              $projectType
            );
        }

        $jiraCode = $this->getConfigValue('jira_code');
        if ($jiraCode === 'CUSTOMIZEME') {
            $errorMessages[] = '- The jira_code parameter in the robo.yml file has not been modified.';
        }

        if (!empty($errorMessages)) {
            throw new TaskException($this, implode("\n", $errorMessages));
        }
        $this->io()->success('Configuration passed validation!');
    }
}
