<?php

class Database
{
    // Database connection
    private $host = 'localhost';
    private $dbname = 'shopmall';
    private $user = 'root';
    private $password = 'apmsetup';
    private $con;

    public function connect()
    {
        $this->con = new mysqli($this->host, $this->user, $this->password, $this->dbname);

        // 연결 오류 확인
        if ($this->con->connect_error) {
            die("Database connection failed: " . $this->con->connect_error);
        }

        // UTF-8 설정
        if (!$this->con->set_charset("utf8")) {
            die("Failed to set charset to utf8: " . $this->con->error);
        }

        return $this->con;
    }

    public function close()
    {
        if ($this->con) {
            $this->con->close();
        }
    }
}

?>