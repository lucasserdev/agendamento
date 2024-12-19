<?php
class Admin {
    private $conn;
    private $table = "users";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Buscar todos os usuários normais
    public function getAllUsers() {
        try {
            $query = "SELECT u.*, p.name as plan_name 
                     FROM " . $this->table . " u 
                     LEFT JOIN plans p ON u.plan_id = p.id 
                     WHERE u.role = 'user' 
                     ORDER BY u.created_at DESC";
    
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Erro ao buscar usuários: " . $e->getMessage());
            return false;
        }
    }

    // Atualizar status do usuário
    public function updateUserStatus($userId, $status) {
        $query = "UPDATE " . $this->table . "
                 SET status = :status 
                 WHERE id = :id AND role = 'user'";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":status", $status);
        $stmt->bindParam(":id", $userId);
        return $stmt->execute();
    }

    // Verificar se é admin ou moderador
    public function isAdminOrMod($userId) {
        $query = "SELECT role FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $userId);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result && ($result['role'] === 'admin' || $result['role'] === 'mod');
    }

    public function updateUserPlan($userId, $planId, $expirationDate = null) {
        try {
            $query = "UPDATE users 
                     SET plan_id = :plan_id,
                         plan_expires_at = CASE 
                             WHEN :expiration_date IS NOT NULL THEN :expiration_date
                             WHEN :plan_id = 4 THEN DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                             ELSE DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                         END
                     WHERE id = :user_id";
            
            error_log("Atualizando plano - User ID: " . $userId . ", Plan ID: " . $planId);
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":plan_id", $planId);
            $stmt->bindParam(":user_id", $userId);
            $stmt->bindParam(":expiration_date", $expirationDate);
            
            $result = $stmt->execute();
            
            if (!$result) {
                error_log("Erro ao atualizar plano: " . print_r($stmt->errorInfo(), true));
            }
            
            return $result;
        } catch(PDOException $e) {
            error_log("Erro na query de atualização do plano: " . $e->getMessage());
            return false;
        }
    }

    public function updateUserPlanExpiration($userId, $expirationDate) {
        try {
            $query = "UPDATE " . $this->table . " 
                     SET plan_expires_at = :expiration_date 
                     WHERE id = :user_id AND role = 'user'";
    
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":expiration_date", $expirationDate);
            $stmt->bindParam(":user_id", $userId);
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Erro ao atualizar data de expiração: " . $e->getMessage());
            return false;
        }
    }
}
?>