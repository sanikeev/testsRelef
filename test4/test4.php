<?php

namespace test4;

use Exception;
use PDO;

class Test4 {

    private $url = "http://www.bills.ru";
    private $dsn = "mysql:dbname=test;host=localhost";
    private $dbUser = 'root';
    private $dbPass = '123';
    private $db;

    public function __construct() {
        $this->db = new PDO($this->dsn, $this->dbUser, $this->dbPass);
        if (empty($this->db)) {
            throw new Exception("Error connecting to DB" . PHP_EOL);
            return;
        }
        $this->create();
        $clientData = $this->httpClient($this->url);
        $parsedData = $this->parseData($clientData);
        $this->fill($parsedData);
        $this->get();
    }

    public function get() {
        $sql = "SELECT * FROM bills_ru_events ORDER BY id";
        $result = $this->db->query($sql)->fetchAll();
        var_dump($result);
    }

    public function create() {
        $sql = "CREATE TABLE IF NOT EXISTS bills_ru_events
            (
                id int NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `date` date,
                title varchar(255),
                url varchar(255),
                hash varchar(32)
            )";
        try {
            $this->db->beginTransaction();
            $this->db->exec($sql);
            $this->db->commit();
            echo 'The table successfully created' . PHP_EOL;
        } catch (Exception $exc) {
            echo $exc->getTraceAsString();
            $this->db->rollBack();
            throw new Exception("Error when creating table in DB" . PHP_EOL);
        }
    }

    public function fill($data) {
        $checkSql = "SELECT id, hash FROM bills_ru_events ORDER BY id";
        $res = $this->db->query($checkSql)->fetchAll();
        $checkData = array();
        if (!empty($res)) {
            foreach ($res as $row) {
                $checkData[$row['hash']] = $row['id'];
            }
        }
        $params = array();
        $sql = '';
        foreach ($data as $params) {
            $params['hash'] = md5(implode(",",$params));
            if (!empty($checkData) && isset($checkData[$params['hash']])) {
                continue;
            }
            $params['hash'] = "'".$params['hash']."'"; // грязный хак
            $params['id'] = "NULL";
            $keys = implode(",", array_keys($params));
            $keys = preg_replace('/(.*?)(,)/i', "$1`,`", $keys);
            $vals = implode(",", array_values($params));
            $sql .= "INSERT INTO bills_ru_events(`" . $keys . "`) VALUES(" . $vals . ");";
        }
        $this->db->beginTransaction();
        if (empty($sql)) {
            return;
        }
        if ($this->db->exec($sql)) {
            $this->db->commit();
            echo 'All record successfully inserted' . PHP_EOL;
        } else {
            $this->db->rollBack();
            print_r($this->db->errorInfo());
            throw new Exception("Error when inserting records in DB" . PHP_EOL);
        }
    }

    public function parseData($data) {
        $data = preg_replace("/\s{2,}|\n+/i", "", $data);
        $table = array();
        preg_match('/<table[^>]*><tr[^>]*><td[^>]*class="news"[^>]*>.*?<\/table>?/i', $data, $table);
        $rows = array();
        preg_match_all('/<tr[^>]*>(.*?)<\/tr>/i', $table[0], $rows);
        $result = array();
        foreach ($rows[1] as $row) {
            preg_match('/<td[^>]*>[^<]*(\d+\.\d+\.\d+)[^<]*<\/td><td[^>]*><a[^>]*href="(.*?)"[^>]*>(.*?)<\/a><\/td>/', $row, $arr);
            $result[] = array(
                'date' => "'" . date("Y-m-d H:i:s", strtotime($arr[1])) . "'",
                'title' => "'" . iconv("cp1251", "utf-8", trim($arr[3])) . "'",
                'url' => "'" . 'http://bills.ru' . $arr[2] . "'",
            );
        }
        return $result;
    }

    public function httpClient($url) {
        $headers = array(
            'User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64; rv:27.0) Gecko/20100101 Firefox/27.0 FirePHP/0.7.4',
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: ru-RU,ru;q=0.8,en-US;q=0.5,en;q=0.3',
        );
        $options = array(
            CURLOPT_AUTOREFERER => 1,
            CURLOPT_HEADER => 1,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_COOKIESESSION => 1,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_FOLLOWLOCATION => 1,
        );
        $ch = curl_init($url);
        curl_setopt_array($ch, $options);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

}

$test = new \test4\Test4();
?>