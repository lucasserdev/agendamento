<?php
class Availability {
    private $conn;
    private $table = "availability";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($data) {
        try {
            $query = "INSERT INTO " . $this->table . "
                    (user_id, day_of_week, start_time, end_time)
                    VALUES
                    (:user_id, :day_of_week, :start_time, :end_time)";

            $stmt = $this->conn->prepare($query);

            $stmt->bindParam(":user_id", $data['user_id'], PDO::PARAM_INT);
            $stmt->bindParam(":day_of_week", $data['day_of_week'], PDO::PARAM_INT);
            $stmt->bindParam(":start_time", $data['start_time']);
            $stmt->bindParam(":end_time", $data['end_time']);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erro ao criar disponibilidade: " . $e->getMessage());
            return false;
        }
    }

    public function update($id, $data) {
        try {
            $query = "UPDATE " . $this->table . "
                    SET start_time = :start_time,
                        end_time = :end_time
                    WHERE id = :id AND user_id = :user_id";

            $stmt = $this->conn->prepare($query);

            $stmt->bindParam(":start_time", $data['start_time']);
            $stmt->bindParam(":end_time", $data['end_time']);
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
            $stmt->bindParam(":user_id", $data['user_id'], PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erro ao atualizar disponibilidade: " . $e->getMessage());
            return false;
        }
    }

    public function delete($id, $user_id) {
        try {
            $query = "DELETE FROM " . $this->table . " 
                     WHERE id = :id AND user_id = :user_id";
                     
            $stmt = $this->conn->prepare($query);
            
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
            $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erro ao deletar disponibilidade: " . $e->getMessage());
            return false;
        }
    }

    public function getUserAvailability($user_id) {
        try {
            $query = "SELECT * FROM " . $this->table . "
                     WHERE user_id = :user_id
                     ORDER BY day_of_week, start_time";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erro ao buscar disponibilidade: " . $e->getMessage());
            return [];
        }
    }

    public function getDayAvailability($user_id, $day_of_week) {
        try {
            $query = "SELECT * FROM " . $this->table . "
                     WHERE user_id = :user_id AND day_of_week = :day_of_week
                     ORDER BY start_time";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
            $stmt->bindParam(":day_of_week", $day_of_week, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erro ao buscar disponibilidade do dia: " . $e->getMessage());
            return [];
        }
    }
}
?>