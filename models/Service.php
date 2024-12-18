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
            // Verificar se usuário pode adicionar mais serviços
            $user = new User($this->conn);
            $user->setId($data['user_id']);
            
            if (!$user->canAddService()) {
                $this->lastError = "Limite de serviços do plano atingido";
                return false;
            }
    
            $query = "INSERT INTO " . $this->table . "
                    (user_id, name, description, duration, price, concurrent_capacity)
                    VALUES
                    (:user_id, :name, :description, :duration, :price, :concurrent_capacity)";
            
            $stmt = $this->conn->prepare($query);
    
            // Limpar e validar dados
            $data['name'] = htmlspecialchars(strip_tags($data['name']));
            $data['description'] = htmlspecialchars(strip_tags($data['description']));
            
            // Bind dos valores
            $stmt->bindParam(":user_id", $data['user_id']);
            $stmt->bindParam(":name", $data['name']);
            $stmt->bindParam(":description", $data['description']);
            $stmt->bindParam(":duration", $data['duration']);
            $stmt->bindParam(":price", $data['price']);
            $stmt->bindParam(":concurrent_capacity", $data['concurrent_capacity']);
            
            return $stmt->execute();
        } catch(PDOException $e) {
            $this->lastError = $e->getMessage();
            error_log("Erro ao criar serviço: " . $e->getMessage());
            return false;
        }
    }

    public function getLastError() {
        return $this->lastError;
    }

    public function update($data) {
        try {
            $query = "UPDATE " . $this->table . "
                    SET name = :name,
                        description = :description,
                        duration = :duration,
                        price = :price,
                        concurrent_capacity = :concurrent_capacity
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
            $stmt->bindParam(":concurrent_capacity", $data['concurrent_capacity']);
            $stmt->bindParam(":id", $data['id']);
            $stmt->bindParam(":user_id", $data['user_id']);
            
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Erro ao atualizar serviço: " . $e->getMessage());
            return false;
        }
    }

    public function delete($id) {
        try {
            $query = "UPDATE " . $this->table . " 
                     SET status = 'inactive' 
                     WHERE id = :id AND user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $id);
            $stmt->bindParam(":user_id", $_SESSION['user_id']); // Adicionar segurança extra
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Erro ao deletar serviço: " . $e->getMessage());
            return false;
        }
    }

    public function getUserServices($user_id) {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE user_id = :user_id 
                  AND (status = 'active' OR status IS NULL) 
                  ORDER BY name";
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