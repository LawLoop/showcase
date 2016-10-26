<?php 
class Database{ 
 
    // specify your own database credentials 

	private $host = 'proloop.ckyj1meiloyg.us-east-1.rds.amazonaws.com';
	private $port = 5432;
	private $db_name = 'mayhem';

	private $username = 'tomz';
	private $password = 'zuberduber';

    public $conn; 
 
    // get the database connection 
    public function getConnection()
    { 
    	$this->conn = null;
        $dsn = "pgsql:host={$this->host};port={$this->port};dbname={$this->db_name}";
 
        try{
            $this->conn = new PDO($dsn, $this->username, $this->password);
        }catch(PDOException $exception){
            echo "Connection error: " . $exception->getMessage();
        }
         
        return $this->conn;
    }
}
