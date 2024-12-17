<?php
class Service {
    private $conn;
    private $table = "services";
    private $lastError;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($data) {
        try {
            $query = "INSERT INTO " . $this->table . "
                    (user_id, name, description, duration, price)
                    VALUES
                    (:user_id, :name, :description, :duration, :price)";
            
            $stmt = $this->conn->prepare($query);
            
            // Limpar e validar dados
            $data['name'] = htmlspecialchars(strip_tags($data['name']));
            $data['description'] = htmlspecialchars(strip_tags($data['description']));
            
            // Debug
            error_log('SQL Query: ' . $query);
            error_log('Dados para insert: ' . print_r($data, true));
            
            // Bind dos valores
            $stmt->bindParam(":user_id", $data['user_id'], PDO::PARAM_INT);
            $stmt->bindParam(":name", $data['name'], PDO::PARAM_STR);
            $stmt->bindParam(":description", $data['description'], PDO::PARAM_STR);
            $stmt->bindParam(":duration", $data['duration'], PDO::PARAM_INT);
            $stmt->bindParam(":price", $data['price'], PDO::PARAM_STR);
            
            $result = $stmt->execute();
            if (!$result) {
                $this->lastError = $stmt->errorInfo();
                error_log('Erro na execução do SQL: ' . print_r($this->lastError, true));
            }
            return $result;
            
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            error_log('PDO Exception: ' . $e->getMessage());
            return false;
        }
    }

    public function getLastError() {
        return $this->lastError;
    }

    public function update($data) {
        $query = "UPDATE " . $this->table . "
                SET name = :name,
                    description = :description,
                    duration = :duration,
                    price = :price
                WHERE id = :id AND user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        
        // Limpar e validar dados
        $data['name'] = htmlspecialchars(strip_tags($data['name']));
        $data['description'] = htmlspecialchars(strip_tags($data['description']));
        
        // Bind dos valores
        $stmt->bindParam(":name", $data['name']);
        $stmt->bindParam(":description", $data['description']);
        $stmt->bindParam(":duration", $data['duration']);
        $stmt->bindParam(":price", $data['price']);
        $stmt->bindParam(":id", $data['id']);
        $stmt->bindParam(":user_id", $data['user_id']);
        
        return $stmt->execute();
    }

    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        return $stmt->execute();
    }

    public function getUserServices($user_id) {
        $query = "SELECT * FROM " . $this->table . " WHERE user_id = :user_id ORDER BY name";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getService($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>