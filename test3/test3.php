<?php

namespace test3;

class Test3 {

    protected $dir;

    function __construct() {
        $this->dir = __DIR__ . '/datafiles/';
    }

    public function execute() {
        $directoryIterator = new \DirectoryIterator($this->dir);
        foreach ($directoryIterator as $item) {
            if (!$item->isFile()) {
                continue;
            }
            $fileName = $item->getFilename();
            if (preg_match('/\w\.ixt/i', $fileName)) {
                echo $fileName . PHP_EOL;
            }
        }
    }

}
$test = new \test3\Test3();
$test->execute();

?>