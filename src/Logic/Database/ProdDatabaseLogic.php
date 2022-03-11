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
use Exception;
use mysqli;
use PDO;
use PDOException;

class ProdDatabaseLogic
{
    private IOLogic $_ioLogic;
    private mysqli $_conn;
    private PDO $_pdo_conn;
    public string $prodDatabase;

    public function __construct(IOLogic $IOLogic)
    {
        $this->_ioLogic = $IOLogic;
        $config = $this->getDatabaseConfiguration();
        $this->prodDatabase = $config["name"];
        $this->_conn = $this->createConnectionToProdDatabase($config["server"], $config["username"],
            $config["password"], $config["name"], $config["port"]);
        $this->_pdo_conn = $this->createPdoConnectionToProdDatabase($config["server"], $config["username"],
            $config["password"], $config["name"], $config["port"]);
    }

    private function getDatabaseConfiguration() : array
    {
        $config = $this->_ioLogic->loadDatabaseConfiguration();

        return array(
            "server" => $config["server"],
            "name" => $config["name"],
            "port" => $config["port"],
            "username" => $config["username"],
            "password" => $config["password"]
        );
    }

    private function createConnectionToProdDatabase(string $server, string $user, string $password, string $database,
                                                    int $port)
    {
        $conn = new mysqli($server, $user, $password, $database, $port);
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        return $conn;
    }

    private function createPdoConnectionToProdDatabase(
        string $server, string $user, string $password, string $database, int $port
    )
    {
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s', $server, $port, $database);
        try {
            $dbh = new PDO($dsn, $user, $password);
        } catch (PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
        return $dbh;
    }

    public function getTableSchemes(string $tableName) : array
    {
        $sql = "DESCRIBE ". $this->prodDatabase. ".". $tableName;
        $req = $this->_conn->query($sql);
        $tableSchemes = array();
        if ($req->num_rows > 0) {
            while($tableScheme = $req->fetch_assoc()) {
                $tableSchemes[] = array(
                    "field" => $tableScheme["Field"],
                    "type" => $tableScheme["Type"],
                    "nullable" => $tableScheme["Null"]
                );
            }
        }
        return $tableSchemes;
    }

    public function runPreparedSqlCommandsOnProdDatabase(string $tableName, array $rows) : void
    {
        if (empty($rows)) {
            return;
        }

        $query = $this->createCommand($rows[0], $tableName);
        $sth = $this->_pdo_conn->prepare($query);
        foreach ($rows as $row) {
            $result = $sth->execute($row);
            if ($result === FALSE) {
                $errorInfo = $sth->errorInfo();
                throw new Exception(sprintf(
                    '%s:<pre>%s</pre><pre>%s</pre>',
                    $errorInfo[2], $query, print_r($row, true)
                ));
            }
        }
    }

    public function runSqlCommandsOnProdDatabase(array $commandsToBeExecuted) : void
    {
        if ($commandsToBeExecuted != null) {
            foreach ($commandsToBeExecuted as $command) {
                if ($this->_conn->query($command) === FALSE) {
                    throw new Exception($command . PHP_EOL . $this->_conn->error);
                    echo "<br/>Es ist ein Fehler aufgetreten :)</br>Fehler: ". $this->_conn->error;
                }
            }
        }
    }

    private function createCommand(array $row, string $tableName) : string
    {
        $columnNames = array_keys($row);

        $arrColumnNames = array_map(function(string $columnName){
            return '`' . $columnName . '`';
        }, $columnNames);
        $strColumnNames = implode(', ', $arrColumnNames);

        $arrPlaceholders = array_map(function(string $columnName){
            return ':' . $columnName;
        }, $columnNames);
        $strPlaceholders = implode(', ', $arrPlaceholders);

        $arrAssignments = array_map(function(string $columnName){
            return '`' . $columnName . '` = :' . $columnName;
        }, $columnNames);
        $strAssignments = implode(', ', $arrAssignments);

        return <<<SQL
INSERT INTO {$this->prodDatabase}.{$tableName} (
    {$strColumnNames}
) VALUES (
    {$strPlaceholders}
) ON DUPLICATE KEY UPDATE
    {$strAssignments}
;
SQL;
    }

    public function getLastIdFromTable(string $tableName) : int
    {
        $sql = sprintf("SELECT id FROM %s.%s ORDER BY id DESC LIMIT 1", $this->prodDatabase, $tableName);
        $req = $this->_conn->query($sql);

        if ($req->num_rows <= 0) {
            echo "<br/>Es ist ein Fehler aufgetreten :)</br>Fehler: ". $this->_conn->error;
            die;
        }
        $row = $req->fetch_assoc();
        return intval($row["id"]);
    }
}
