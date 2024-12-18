<?php

class User {
    private $conn;
    private $table = "users";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($name, $email, $password) {
        $query = "INSERT INTO " . $this->table . " (name, email, password) VALUES (:name, :email, :password)";
        $stmt = $this->conn->prepare($query);
        
        $password = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt->bindParam(":name", $name);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":password", $password);

        return $stmt->execute();
    }

    public function login($email, $password) {
        $query = "SELECT id, name, email, password FROM " . $this->table . " WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();

        if($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if(password_verify($password, $row['password'])) {
                return $row;
            }
        }
        return false;
    }

    public function getById($id) {
        try {
            $query = "SELECT id, name, email FROM users WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
            $stmt->execute();
    
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Erro ao buscar usuário: " . $e->getMessage());
            return false;
        }
    }

    public function getUserData($id) {
        try {
            $query = "SELECT * FROM users WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $id);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Erro ao buscar dados do usuário: " . $e->getMessage());
            return false;
        }
    }

    public function updateWhatsapp($user_id, $whatsapp) {
        try {
            $query = "UPDATE users SET whatsapp = :whatsapp WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":whatsapp", $whatsapp);
            $stmt->bindParam(":id", $user_id);
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Erro ao atualizar whatsapp: " . $e->getMessage());
            return false;
        }
    }
}