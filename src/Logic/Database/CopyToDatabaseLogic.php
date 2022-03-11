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

namespace BrockhausAg\ContaoReleaseStagesBundle\Logic\Database;

use BrockhausAg\ContaoReleaseStagesBundle\Logic\IOLogic;
use Contao\Backend;
use Exception;

class CopyToDatabaseLogic extends Backend
{
    private IOLogic $_ioLogic;
    private DatabaseLogic $_databaseLogic;
    private ProdDatabaseLogic $_prodDatabaseLogic;

    public function __construct(IOLogic $IOLogic, ProdDatabaseLogic $prodDatabaseLogic)
    {
        parent::__construct();
        $this->_ioLogic = $IOLogic;
        $this->_databaseLogic = new DatabaseLogic($this->_ioLogic);
        $this->_prodDatabaseLogic = $prodDatabaseLogic;
    }

    public function copyToDatabase() : void
    {
        $testStageDatabaseName = $this->_ioLogic->loadTestStageDatabaseName();
        $tables = $this->_databaseLogic->downloadFromDatabase($testStageDatabaseName);

        echo "to be inserted into/updated table: </br>";
        foreach ($tables as $table) {
            $tableName = $table[0];
            $tableContent = $table[1];
            printf("<strong>%s</strong></br>", $tableName);
            if (strcmp($tableName, "tl_page") == 0) {
                array_walk($tableContent, function(&$row, $index){
                    if (strcmp($row["type"], "root") == 0) {
                        $row["dns"] = $this->changeDNSEntryForProd($row["alias"]);
                    }
                });
            }

            try {
                $this->_prodDatabaseLogic->runPreparedSqlCommandsOnProdDatabase($tableName, $tableContent);
                echo "Tabelle {$tableName} erfolgreich kopiert :)<br/>";
            } catch (Exception $e) {
                echo "<br/>Es ist ein Fehler aufgetreten :)</br>Fehler: ". $e->getMessage();
                break;
            }
        }

        $lastId = $this->_prodDatabaseLogic->getLastIdFromTable("tl_log");
        $this->checkForDeleteFromInTlLogTable($lastId);
    }

    private function checkForDeleteFromInTlLogTable(int $lastId) : void
    {
        $arrConditions = [
            "id > ?",
            "text LIKE ?"
        ];
        $values = [$lastId, 'DELETE FROM %'];
        $res = $this->_databaseLogic
            ->getLastRowsWithWhereStatement(["text"], "tl_log", $arrConditions, $values)
            ->fetchAllAssoc();

        $deleteStatements = array();
        foreach ($res as $statement) {
            $deleteStatements[] = $statement["text"];
        }
        $this->_prodDatabaseLogic->runSqlCommandsOnProdDatabase($deleteStatements);
    }

    private function changeDNSEntryForProd(string $alias) : string
    {
        $dnsRecords = $this->_ioLogic->loadDNSRecords();
        foreach ($dnsRecords as $dnsRecord) {
            if (strcmp($dnsRecord["alias"], $alias) == 0) {
                return $dnsRecord["dns"];
            }
        }
        return "";
    }
}
