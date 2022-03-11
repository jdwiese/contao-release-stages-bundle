<?php

namespace BrockhausAg\ContaoReleaseStagesBundle\EventListener\DataContainer;

use BrockhausAg\ContaoReleaseStagesBundle\Logic\Database\CopyToDatabaseLogic;
use BrockhausAg\ContaoReleaseStagesBundle\Logic\Database\DatabaseLogic;
use BrockhausAg\ContaoReleaseStagesBundle\Logic\FileServer\CopyToFileServerLogic;
use Contao\Backend;

class tl_release_stages extends Backend
{
    private DatabaseLogic $_databaseLogic;
    private CopyToDatabaseLogic $_copyToDatabaseLogic;
    private CopyToFileServerLogic $_copyToFileServerLogic;

    public function __construct(
        DatabaseLogic $databaseLogic,
        CopyToDatabaseLogic $copyToDatabaseLogic,
        CopyToFileServerLogic $fileServerLogic
    )
    {
        parent::__construct();
        //$this->_databaseLogic = new DatabaseLogic();
        $this->_databaseLogic = $databaseLogic;
        //$this->_copyToDatabaseLogic = new CopyToDatabaseLogic();
        $this->_copyToDatabaseLogic = $copyToDatabaseLogic;
        //$this->_copyToFileServerLogic = new CopyToFileServerLogic();
        $this->_copyToFileServerLogic = $fileServerLogic;
    }

    public function changeVersionNumber() : void
    {
        $release_stages = $this->_databaseLogic->getLastRows(2, array("id", "version", "kindOfRelease"),
            "tl_release_stages");
        $actualId = $release_stages->id;
        $kindOfRelease = $release_stages->kindOfRelease;

        $counter = $this->_databaseLogic->countRows($release_stages);
        $oldVersion = $release_stages->version;

        $newVersion = $this->createVersion($counter, $oldVersion, $kindOfRelease);

        $this->_databaseLogic->updateVersion($actualId, $newVersion);
    }

    private function createVersion(int $counter, string $oldVersion, string $kindOfRelease) : string
    {
        if ($counter > 0) {
            $version = explode(".", $oldVersion);
            if (strcmp($kindOfRelease, "release") == 0) {
                return $this->createRelease($version);
            }
            return $this->createMajorRelease($version);
        }
        return "1.0";
    }

    private function createRelease(array $version) : string
    {
        return $version[0]. ".". intval($version[1]+1);
    }

    private function createMajorRelease(array $version) : string
    {
        return intval($version[0]+1). ".0";
    }

    public function copy() : void
    {
        $this->_copyToDatabaseLogic->copyToDatabase();
        $this->_copyToFileServerLogic->copyToFileServer();
    }
}
