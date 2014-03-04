<?php

namespace init;

use Exception;
use PDO;

final class Init {

    private $dsn = "mysql:dbname=test;host=127.0.0.1";
    private $dbUser = 'root';
    private $dbPass = '123';
    private $db;

    public function __construct() {
        $this->db = new PDO($this->dsn, $this->dbUser, $this->dbPass);
        if (!$this->db) {
            throw new Exception("Error connecting to DB" . PHP_EOL);
            return;
        }
        $this->create();
        $this->fill();
    }

    public function get() {
        $params = array('normal', 'success');
        $sql = "SELECT * FROM test WHERE result = ? OR result = ?";
        $prepare = $this->db->prepare($sql);
        $prepare->execute($params);
        $result = $prepare->fetchAll();
        var_dump($result);
    }

    private function create() {
        $sql = "CREATE TABLE IF NOT EXISTS test
            (
                id int NOT NULL AUTO_INCREMENT PRIMARY KEY,
                script_name varchar(25),
                start_time int,
                end_time int,
                result varchar(10)
            )";
        try {
            $this->db->beginTransaction();
            $this->db->exec($sql);
            $this->db->commit();
            echo 'The table successfully created'.PHP_EOL;
        } catch (Exception $exc) {
            $this->db->rollBack();
            throw new Exception("Error when creating table in DB" . PHP_EOL);
            echo $exc->getTraceAsString();
        }
    }

    private function fill() {
        $resultFieldData = array(
            'normal',
            'illegal',
            'filed',
            'success',
        );
        $params = array();
        $sql = '';
        for ($i = 0; $i < 10; $i++) {
            $randIndex = rand(0, count($resultFieldData) - 1);
            $params = array(
                'id' => 'NULL',
                'script_name' => 'script' . $i,
                'start_time' => time(),
                'end_time' => time() + 1,
                'result' => $resultFieldData[$randIndex],
            );
            $vals = implode(",", array_values($params));
            $vals = preg_replace('/(.*?)(,)/i', "$1','", $vals);
            $sql .= "INSERT INTO test(" .
                    implode(",", array_keys($params)) .
                    ") VALUES('" . $vals . "');";
        }
        $this->db->beginTransaction();
        if ($this->db->exec($sql)) {
            $this->db->commit();
            echo 'All record successfully inserted' . PHP_EOL;
        } else {
            $this->db->rollBack();
            throw new Exception("Error when inserting records in DB" . PHP_EOL);
        }
    }

}

$test = new \init\Init();
$test->get();
?>
