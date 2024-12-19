<?php
class User {
    private $conn;
    private $table = "users";
    private $id; // Adicionado para usar nos métodos de plano

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($name, $email, $password) {
        try {
            $this->conn->beginTransaction();
    
            // Calcular data de expiração (7 dias a partir de hoje)
            $expirationDate = date('Y-m-d', strtotime('+7 days'));
    
            $query = "INSERT INTO " . $this->table . " 
                    (name, email, password, role, plan_id, plan_expires_at) 
                    VALUES 
                    (:name, :email, :password, 'user', 4, :expires_at)"; // 4 é o ID do plano Teste
            
            $stmt = $this->conn->prepare($query);
            
            $password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt->bindParam(":name", $name);
            $stmt->bindParam(":email", $email);
            $stmt->bindParam(":password", $password);
            $stmt->bindParam(":expires_at", $expirationDate);
    
            $result = $stmt->execute();
            
            $this->conn->commit();
            return $result;
    
        } catch(PDOException $e) {
            $this->conn->rollBack();
            error_log("Erro ao criar usuário: " . $e->getMessage());
            return false;
        }
    }

    public function login($email, $password) {
        $query = "SELECT id, name, email, password, role FROM " . $this->table . " WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
    
        if($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if(password_verify($password, $row['password'])) {
                // Verificar expiração do plano teste
                $this->checkPlanExpiration($row['id']);
                return $row;
            }
        }
        return false;
    }

    public function isAdmin() {
        $query = "SELECT role FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result && $result['role'] === 'admin';
    }

    public function isModerator() {
        $query = "SELECT role FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result && $result['role'] === 'mod';
    }

    public function getById($id) {
        try {
            $query = "SELECT u.*, p.name as plan_name 
                     FROM users u 
                     LEFT JOIN plans p ON u.plan_id = p.id 
                     WHERE u.id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $id);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Erro ao buscar usuário: " . $e->getMessage());
            return false;
        }
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function getUserData($id) {
        try {
            $query = "SELECT u.*, p.name as plan_name, p.max_services, p.monthly_bookings 
                     FROM users u 
                     LEFT JOIN plans p ON u.plan_id = p.id 
                     WHERE u.id = :id";
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

    public function getPlanDetails() {
        try {
            $query = "SELECT p.* FROM plans p
                    INNER JOIN users u ON u.plan_id = p.id
                    WHERE u.id = :user_id";
                    
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $this->id);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Erro ao buscar detalhes do plano: " . $e->getMessage());
            return false;
        }
    }

    public function canAddService() {
        try {
            $planDetails = $this->getPlanDetails();
            if (!$planDetails) {
                return false;
            }
                
            // Se max_services for -1, significa serviços ilimitados
            if ($planDetails['max_services'] === -1) {
                return true;
            }
                
            // Conta quantos serviços o usuário já tem
            $query = "SELECT COUNT(*) as count FROM services WHERE user_id = :user_id AND status = 'active'";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $this->id);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
            // Verifica se ainda pode adicionar mais serviços
            return $result['count'] < $planDetails['max_services'];
        } catch(PDOException $e) {
            error_log("Erro ao verificar limite de serviços: " . $e->getMessage());
            return false;
        }
    }

    public function canMakeBooking() {
        try {
            $planDetails = $this->getPlanDetails();
            if (!$planDetails) return false;
            
            if ($planDetails['monthly_bookings'] === -1) return true;
            
            $month = date('Y-m');
            $query = "SELECT COUNT(*) as count FROM appointments 
                    WHERE user_id = :user_id 
                    AND DATE_FORMAT(created_at, '%Y-%m') = :month";
                    
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $this->id);
            $stmt->bindParam(":month", $month);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['count'] < $planDetails['monthly_bookings'];
        } catch(PDOException $e) {
            error_log("Erro ao verificar limite de agendamentos: " . $e->getMessage());
            return false;
        }
    }

    public function updateUserPlan($userId, $planId) {
        try {
            $query = "UPDATE " . $this->table . " 
                     SET plan_id = :plan_id, 
                         plan_expires_at = CASE 
                             WHEN :plan_id = 4 THEN DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                             ELSE DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                         END 
                     WHERE id = :user_id";
                     
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":plan_id", $planId);
            $stmt->bindParam(":user_id", $userId);
            
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Erro ao atualizar plano: " . $e->getMessage());
            return false;
        }
    }

    public function checkPlanExpiration($userId) {
        try {
            $query = "SELECT plan_expires_at, plan_id FROM " . $this->table . " 
                     WHERE id = :user_id AND plan_expires_at IS NOT NULL 
                     AND plan_expires_at < CURDATE()";
                     
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $userId);
            $stmt->execute();
            
            if ($stmt->fetch(PDO::FETCH_ASSOC)) {
                // Plano expirou, mudar para plano Bronze
                $this->updateUserPlan($userId, 1); // 1 = Bronze
                return true;
            }
            
            return false;
        } catch(PDOException $e) {
            error_log("Erro ao verificar expiração do plano: " . $e->getMessage());
            return false;
        }
    }

    public function getDaysUntilExpiration($userId) {
        try {
            $query = "SELECT DATEDIFF(plan_expires_at, CURDATE()) as days_left,
                            plan_id, plan_expires_at 
                     FROM " . $this->table . " 
                     WHERE id = :user_id";
                     
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $userId);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Se não tem data de expiração ou é plano gratuito, retorna null
            if (!$result || $result['plan_expires_at'] === null) {
                return null;
            }
            
            return (int)$result['days_left'];
            
        } catch(PDOException $e) {
            error_log("Erro ao verificar dias até expiração: " . $e->getMessage());
            return null;
        }
    }
}