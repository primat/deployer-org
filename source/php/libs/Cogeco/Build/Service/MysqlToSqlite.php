<?php
/**
 *
 */
namespace Cogeco\Build\Service;

/**
 * Class MysqlToSqlite
 * @package Cogeco\Build\Service
 */
class MysqlToSqlite
{
    private $inHandle;
    private $outHandle;
    private $inTrigger = false;
    private $inComment = false;
    private $tableName = '';
    private $indexes = array();

    /**
     * Create a Sqlite dump file from a MySQL dump file
     * @param $sourceFile
     * @param $destinationFile
     * @throws \Exception
     */
    public function convert($sourceFile, $destinationFile)
    {
        // Initialize the file resources
        $this->inHandle = fopen($sourceFile, "r");
        if (!$this->inHandle) {
            throw new \Exception("Unable to open file $sourceFile for reading");
        }
        $this->outHandle = fopen($destinationFile, "w");
        if (!$this->outHandle) {
            throw new \Exception("Unable to open file $destinationFile for writing");
        }

        // Start initial output
        fwrite($this->outHandle, "PRAGMA synchronous = OFF;\n");
        fwrite($this->outHandle, "PRAGMA journal_mode = MEMORY;\n");
        fwrite($this->outHandle, "BEGIN TRANSACTION;\n");

        // Loop through the MySQL and convert each line, one at a time
        while (($buffer = fgets($this->inHandle)) !== false) {
            $line = $this->parseLine($buffer);
            fwrite($this->outHandle, $line);
        }
        if (!feof($this->inHandle)) {
            throw new \Exception("Unexpected fgets() fail");
        }

        foreach($this->indexes as $name => $indexVars) {
            $lineTpl = 'CREATE INDEX "%s" ON "%s" (%s);' . "\n";
            fwrite($this->outHandle, sprintf($lineTpl, $name, $indexVars['tableName'], $indexVars['keys']));
        }

        fwrite($this->outHandle, "END TRANSACTION;\n");

        fclose($this->inHandle);
        fclose($this->outHandle);
    }

    /**
     * Input a MySQL line and get back a Sqlite line
     * @param string $line The lone of Mysql to convert
     * @return mixed|string
     */
    protected function parseLine($line)
    {
        $result = $line;
        if (($this->inComment && strpos($result, '*/;') === false) ||
            strpos($result, 'SET ') === 0  ||
            strpos($result, 'LOCK ') === 0 ||
            strpos($result, 'UNLOCK ') === 0 ||
            strpos($result, '-- ') === 0
        ) {
            $result = '';
        }
        else if ($this->inTrigger) {
            // Proceed normally
        }
        else if (strpos($result, ')') === 0 && ! empty($this->tableName)) {
            $result = ");\n";
            $this->tableName = '';
        }
        else if (strpos($result, 'INSERT') === 0) {
            $result = str_replace("\\\047", "\047\047", $result);
            $result = str_replace("\\\n", "\n", $result);
            $result = str_replace("\\\r", "\r", $result);
            $result = str_replace("\\\"", "\"", $result);
            $result = str_replace("\\\\", "\\", $result);
            $result = str_replace("\\\032", "\032", $result);
        }
        else if (preg_match('/CREATE VIEW/', $result)) {
            $result = preg_replace('/^\/\*\!50001 /', '', $result);
            $result = str_replace('*/', '', $result);
        }
        else if (preg_match('/^\/\*.*CREATE.*TRIGGER/', $result)) {
            $result = preg_replace('/^.*TRIGGER/', 'CREATE TRIGGER', $result);
            $this->inTrigger = true;
        }
        else if (strpos($result, 'END */') === 0) {
            $result = str_replace('*/', '', $result);
            $this->inTrigger = false;
        }
        else if (preg_match('/^\/\*.*PARTITION.*/', $result)) {
            $result .= ");\n";
        }
        else if (strpos($result, '/*') === 0) {
            if (strpos($result, '*/') === false) {
                $this->inComment = true;
            }
            $result = '';
        }
        else if (strpos($result, '*/;') !== false) {
            $this->inComment = false;
            $result = '';
        }
        else if (strpos($result, 'CREATE TABLE') !== false) {
            $pattern = '/\"[^\"]+/';
            preg_match($pattern, $result, $matches);
            if (isset($matches[0])) {
                $this->tableName = substr($matches[0], 1);
            }
        }
        else if (preg_match('/PRIMARY KEY/', $result)) {
            $result = preg_replace("/\,\s*$/", "\n", $result);
        }
        else if (preg_match('/^  KEY/', $result)) {
            $result = preg_replace('/\([0-9]+\)/', "", $result);
            $name = '';
            $keys = '';
            $pattern = '/\"[^\"]+/';
            preg_match($pattern, $result, $matches);
            if (isset($matches[0])) {
                $name = substr($matches[0], 1);
            }
            $pattern = '/\([^\)]+/';
            preg_match($pattern, $result, $matches);
            if (isset($matches[0])) {
                $keys = substr($matches[0], 1);
            }
            $indexKey = $this->tableName . '_' .  $name;
            $this->indexes[$indexKey] = array("tableName" => $this->tableName, "keys" => $keys);
            $result = '';
        }
        else if (preg_match('/^  /', $result)) {
            $result = preg_replace('/AUTO_INCREMENT|auto_increment/', '', $result);
            $result = preg_replace("/ (COMMENT|comment) '.*'/", '', $result);
            $result = preg_replace('/ (CHARACTER SET|character set) [^ ,]+/', '', $result);
            $result = preg_replace('/ DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP|default current_timestamp on update current_timestamp/', '', $result);
            $result = preg_replace('/ (COLLATE|collate) [^ ,]+/', '', $result);
            $result = preg_replace('/ (ENUM|enum)[^)]+\)/', ' text', $result);
            $result = preg_replace('/ (SET|set)\([^)]+\)/', ' text', $result);
            $result = preg_replace('/ (UNSIGNED|unsigned)/', '', $result);
        }
        return $result;
    }
}
