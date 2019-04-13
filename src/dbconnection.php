<?php

class DBConnection {

    private $pdo = null;
    private $host;
    private $user;
    private $pass;
    private $dbname;


    function __construct($host, $user, $pass, $dbname) {
        $this->$host = $host;
        $this->$user = $user;
        $this->pass = $pass;
        $this->dbname = $dbname;
    }

    function connect() {
        if ($pdo == null) {

            try {
                $pdo = new PDO('mysql:host=' . $this->$host . ';dbname=' . $this->$dbname,
                    $this->$user, $this->$pass);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            } catch (PDOException $e){
                print "Connection Failed: " . $e->getMessage() . "<br/>";
                die();
            }
        }
        return $pdo;
    }

    function executeQuery($query) {
        if ($pdo != null) {
            // try{
            //     $stmt = $pdo->prepare($query);
                
            // }
            // catch (Exception ex) {

            // }


        }
    }

} 