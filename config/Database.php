<?php
class Database {
    private $host = "localhost";
    private $db_name = "agendamento";
    private $username = "root";
    private $password = "";
    private $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            error_log('Conexão com banco de dados estabelecida com sucesso');
        } catch(PDOException $e) {
            error_log('Erro de conexão: ' . $e->getMessage());
            echo "Connection Error: " . $e->getMessage();
        }
        return $this->conn;
    }
}
?>