<?php

declare(strict_types=1);

/*
 * This file is part of contao-release-stages-bundle.
 *
 * (c) BROCKHAUS AG 2022 <info@brockhaus-ag.de>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/brockhaus-ag/contao-release-stages-bundle
 */

namespace BrockhausAg\ContaoReleaseStagesBundle\Logic;

use Exception;

class IOLogic {
    const SETTINGS_PATH = "config/brockhaus-ag/contao-release-stages-bundle";
    const CONFIG_FILE = "config.json";

    private $configuration;
    private string $projectDir;

    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;
    }

    private function getConfigPath()
    {
        $settingsPath = $this->projectDir .DIRECTORY_SEPARATOR . self::SETTINGS_PATH;
        if (!is_readable($settingsPath)) {
            throw new Exception('Settings path could not be found or is not readable: ' . $settingsPath);
        }

        $configPath = $settingsPath . DIRECTORY_SEPARATOR . self::CONFIG_FILE;
        if (!is_readable($configPath)) {
            throw new Exception('Config file could not be found or is not readable: ' . $configPath);
        }

        return realpath($configPath);
    }

    private function checkIfFileExists(string $file)
    {
        if (!file_exists($file)) {
            $errorMessage = "File: \"". $file. "\" could not be found. Please create it!";
            echo $errorMessage;
            exit();
        }
    }

    private function loadJsonFileAndDecode(string $file) : ?array
    {
        $this->checkIfFileExists($file);
        $fileContent = file_get_contents($file);
        return json_decode($fileContent, true);
    }

    private function loadConfiguration() : array
    {
        $this->configuration = $this->loadJsonFileAndDecode($this->getConfigPath());
        return $this->configuration;
    }

    /**
     * @throws Exception
     */
    private function getConfiguration(string $key = null)
    {
        if (is_null($this->configuration)) {
            $this->loadConfiguration();
        }

        if (is_null($key)) {
            return $this->configuration;
        }

        if (!array_key_exists($key, $this->configuration)) {
            throw new Exception('Konfigurationswert existiert nicht: ' . $key);
        }

        return $this->configuration[$key];
    }

    private function loadContaoPath() : string
    {
        return $this->getConfiguration("contaoPath");
    }

    private function getLocalContaoPath(string $folder) : string
    {
        return $this->getConfiguration("contaoPath") . DIRECTORY_SEPARATOR . $folder;
    }

    public function getPathToContaoFiles() : string
    {
        return $this->getLocalContaoPath("files");
    }

    public function loadDatabaseConfiguration() : array
    {
        return $this->getConfiguration("database");
    }

    public function loadTestStageDatabaseName() : string
    {
        return $this->loadDatabaseConfiguration()["testStageDatabaseName"];
    }

    public function loadDatabaseIgnoredTablesConfiguration() : array
    {
        $ignoredTables = $this->loadDatabaseConfiguration()["ignoredTables"];
        array_push($ignoredTables, "tl_user", "tl_cron_job", "tl_release_stages");
        return $ignoredTables;
    }

    public function loadDNSRecords() : array
    {
        return $this->getConfiguration("dnsRecords");
    }

    public function checkWhereToCopy() : string
    {
        return $this->getConfiguration("copyTo");
    }

    public function loadFileServerConfiguration() : array
    {
        return $this->getConfiguration("fileServer");
    }

    public function loadLocalFileServerConfiguration() : array
    {
        return $this->getConfiguration("local");
    }

    /**
     * @throws Exception
     */
    public function getLocalFileServerConfiguration(string $key = null)
    {
        $config = $this->getConfiguration("local");
        if (is_null($key)) {
            return $config;
        }

        if (!array_key_exists($key, $config)) {
            throw new Exception('Konfigurationswert existiert nicht: ' . $key);
        }

        return $config[$key];
    }

    private function getLocalFileServerContaoPath(string $folder) : string
    {
        return $this->getLocalFileServerConfiguration("contaoProdPath") . DIRECTORY_SEPARATOR . $folder;
    }

    public function getLocalFileServerPathToContaoFiles() : string
    {
        return $this->getLocalFileServerContaoPath("files");
    }

    public function loadFileFormats() : array
    {
        return $this->getConfiguration("fileFormats");
    }
}
