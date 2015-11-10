<?php
/**
 * Created by JetBrains PhpStorm.
 * User: mprice
 * Date: 14-07-25
 * Time: 14:31
 * To change this template use File | Settings | File Templates.
 */

namespace Cogeco\Database;

use Cogeco\Database\Exception\ConnectionException;
use Cogeco\Database\Exception\RuntimeException;

/**
 * The Pdo class handle very basic database functionality
 * Class Pdo
 * @package Cogeco\Database
 */
class Pdo extends \PDO
{
    /**
     * @param string $dsn The connection string
     * @param string $user The username
     * @param string $passwd The password
     * @param array $options An array of PDO options
     * @throws ConnectionException
     */
    public function __construct($dsn, $user = "", $passwd = "", array $options = array())
    {
        // Set some default options. These should ideally come from an external config rather than hard coded
        // in the constructor
        $defaultOptions = array(
            \PDO::ATTR_PERSISTENT => false,
            \PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        );

        if (strpos($dsn, "mysql") === 0) {
            $defaultOptions[\PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES utf8";
        }

        $options = $options + $defaultOptions;

        try {
            parent::__construct($dsn, $user, $passwd, $options);
        } catch (\PDOException $e) {
            throw new ConnectionException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * @param string $sql
     * @param array $bind
     * @return array|bool|int
     * @throws Exception\RuntimeException
     */
    public function run($sql, $bind = array())
    {
        try {
            $stmt = $this->prepare($sql);
            if (($result = $stmt->execute($bind)) !== false) {
                if (preg_match("/^(select|describe|pragma)/i", $sql)) {
                    return $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
                elseif (preg_match("/^(delete|insert|update)/i", $sql)) {
                    return $stmt->rowCount();
                }
            }
        } catch (\PDOException $e) {
            throw new RuntimeException($e->getMessage(), $e->getCode());
        }
        return $result;
    }
}
