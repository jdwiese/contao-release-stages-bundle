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

DEFINE("SETTINGS_PATH", "/html/contao/settings/brockhaus-ag/contao-release-stages-bundle/");
DEFINE("CONFIG_FILE", "config.json");

class IOLogic {
    public function loadPathToContaoFiles() : string
    {
        return $this->loadContaoPath(). "files";
    }

    public function loadDatabaseConfiguration() : array
    {
        return $this->loadConfiguration()["database"];
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
        return $this->loadConfiguration()["dnsRecords"];
    }

    public function checkWhereToCopy() : string
    {
        return $this->loadConfiguration()["copyTo"];
    }

    public function loadFileServerConfiguration() : array
    {
        return $this->loadConfiguration()["fileServer"];
    }

    public function loadLocalFileServerConfiguration() : array
    {
        return $this->loadConfiguration()["local"];
    }

    public function loadFileFormats() : array
    {
        return $this->loadConfiguration()["fileFormats"];
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
        return $this->loadJsonFileAndDecode(SETTINGS_PATH. CONFIG_FILE);
    }

    private function loadContaoPath() : string
    {
        return $this->loadConfiguration()["contaoPath"];
    }
}
