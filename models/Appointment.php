<?php
class Appointment {
    private $conn;
    private $table = "appointments";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($data) {
        try {
            $query = "INSERT INTO " . $this->table . "
                    (service_id, user_id, client_name, client_email, client_phone, 
                     appointment_date, start_time, end_time, status)
                    VALUES
                    (:service_id, :user_id, :client_name, :client_email, :client_phone,
                     :appointment_date, :start_time, :end_time, 'pending')";

            $stmt = $this->conn->prepare($query);

            // Limpar e validar dados
            $data['client_name'] = htmlspecialchars(strip_tags($data['client_name']));
            $data['client_email'] = htmlspecialchars(strip_tags($data['client_email']));
            $data['client_phone'] = htmlspecialchars(strip_tags($data['client_phone']));

            // Bind dos valores
            $stmt->bindParam(":service_id", $data['service_id'], PDO::PARAM_INT);
            $stmt->bindParam(":user_id", $data['user_id'], PDO::PARAM_INT);
            $stmt->bindParam(":client_name", $data['client_name'], PDO::PARAM_STR);
            $stmt->bindParam(":client_email", $data['client_email'], PDO::PARAM_STR);
            $stmt->bindParam(":client_phone", $data['client_phone'], PDO::PARAM_STR);
            $stmt->bindParam(":appointment_date", $data['appointment_date']);
            $stmt->bindParam(":start_time", $data['start_time']);
            $stmt->bindParam(":end_time", $data['end_time']);

            return $stmt->execute();

        } catch (PDOException $e) {
            error_log("Erro ao criar agendamento: " . $e->getMessage());
            return false;
        }
    }

    public function update($id, $data) {
        try {
            $query = "UPDATE " . $this->table . "
                    SET status = :status,
                        appointment_date = :appointment_date,
                        start_time = :start_time,
                        end_time = :end_time
                    WHERE id = :id AND user_id = :user_id";

            $stmt = $this->conn->prepare($query);

            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
            $stmt->bindParam(":user_id", $data['user_id'], PDO::PARAM_INT);
            $stmt->bindParam(":status", $data['status']);
            $stmt->bindParam(":appointment_date", $data['appointment_date']);
            $stmt->bindParam(":start_time", $data['start_time']);
            $stmt->bindParam(":end_time", $data['end_time']);

            return $stmt->execute();

        } catch (PDOException $e) {
            error_log("Erro ao atualizar agendamento: " . $e->getMessage());
            return false;
        }
    }

    public function delete($id, $user_id) {
        try {
            $query = "DELETE FROM " . $this->table . " WHERE id = :id AND user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
            $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);

            return $stmt->execute();

        } catch (PDOException $e) {
            error_log("Erro ao deletar agendamento: " . $e->getMessage());
            return false;
        }
    }

    public function getUserAppointments($user_id) {
        try {
            $query = "SELECT a.*, s.name as service_name, s.duration
                    FROM " . $this->table . " a
                    JOIN services s ON a.service_id = s.id
                    WHERE a.user_id = :user_id
                    ORDER BY a.appointment_date, a.start_time";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Erro ao buscar agendamentos: " . $e->getMessage());
            return [];
        }
    }

    public function getAppointment($id) {
        try {
            $query = "SELECT a.*, s.name as service_name, s.duration
                    FROM " . $this->table . " a
                    JOIN services s ON a.service_id = s.id
                    WHERE a.id = :id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Erro ao buscar agendamento: " . $e->getMessage());
            return false;
        }
    }

    public function checkAvailability($user_id, $date, $start_time, $end_time) {
        try {
            // 1. Verificar se é um dia/horário configurado como disponível
            $dayOfWeek = date('w', strtotime($date)); // 0 (domingo) a 6 (sábado)
            
            $availQuery = "SELECT COUNT(*) as count FROM availability 
                          WHERE user_id = :user_id 
                          AND day_of_week = :day_of_week
                          AND :start_time >= start_time 
                          AND :end_time <= end_time";
            
            $availStmt = $this->conn->prepare($availQuery);
            $availStmt->bindParam(":user_id", $user_id);
            $availStmt->bindParam(":day_of_week", $dayOfWeek);
            $availStmt->bindParam(":start_time", $start_time);
            $availStmt->bindParam(":end_time", $end_time);
            $availStmt->execute();
            
            $availResult = $availStmt->fetch(PDO::FETCH_ASSOC);
            
            // Se não encontrou disponibilidade configurada, retorna false
            if ($availResult['count'] == 0) {
                return false;
            }

            // 2. Verificar se não há conflito com outros agendamentos
            $query = "SELECT COUNT(*) as count
                    FROM " . $this->table . " a
                    WHERE a.user_id = :user_id
                    AND a.appointment_date = :date
                    AND a.status != 'cancelled'
                    AND (
                        (a.start_time <= :start_time AND a.end_time > :start_time)
                        OR (a.start_time < :end_time AND a.end_time >= :end_time)
                        OR (:start_time <= a.start_time AND :end_time >= a.end_time)
                    )";

            $stmt = $this->conn->prepare($query);
            
            $stmt->bindParam(":user_id", $user_id);
            $stmt->bindParam(":date", $date);
            $stmt->bindParam(":start_time", $start_time);
            $stmt->bindParam(":end_time", $end_time);
            
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            // Retorna true se não houver conflitos
            return $result['count'] == 0;

        } catch (PDOException $e) {
            error_log("Erro ao verificar disponibilidade: " . $e->getMessage());
            return false;
        }
    }

    public function getAvailableTimeSlots($user_id, $date, $serviceDuration) {
        try {
            $dayOfWeek = date('w', strtotime($date));
            $availableSlots = [];

            // 1. Buscar horários configurados para o dia
            $availQuery = "SELECT start_time, end_time 
                          FROM availability 
                          WHERE user_id = :user_id 
                          AND day_of_week = :day_of_week
                          ORDER BY start_time";
            
            $availStmt = $this->conn->prepare($availQuery);
            $availStmt->bindParam(":user_id", $user_id);
            $availStmt->bindParam(":day_of_week", $dayOfWeek);
            $availStmt->execute();
            
            $availablePeriods = $availStmt->fetchAll(PDO::FETCH_ASSOC);

            // 2. Buscar agendamentos existentes para o dia
            $apptQuery = "SELECT start_time, end_time 
                         FROM " . $this->table . "
                         WHERE user_id = :user_id 
                         AND appointment_date = :date
                         AND status != 'cancelled'
                         ORDER BY start_time";
            
            $apptStmt = $this->conn->prepare($apptQuery);
            $apptStmt->bindParam(":user_id", $user_id);
            $apptStmt->bindParam(":date", $date);
            $apptStmt->execute();
            
            $bookedSlots = $apptStmt->fetchAll(PDO::FETCH_ASSOC);

            // 3. Gerar slots disponíveis
            foreach ($availablePeriods as $period) {
                $currentTime = strtotime($period['start_time']);
                $endTime = strtotime($period['end_time']);
                
                while ($currentTime + ($serviceDuration * 60) <= $endTime) {
                    $slotStart = date('H:i', $currentTime);
                    $slotEnd = date('H:i', $currentTime + ($serviceDuration * 60));
                    
                    // Verificar se o slot não conflita com agendamentos existentes
                    $isAvailable = true;
                    foreach ($bookedSlots as $booked) {
                        if (
                            (strtotime($slotStart) >= strtotime($booked['start_time']) && 
                             strtotime($slotStart) < strtotime($booked['end_time'])) ||
                            (strtotime($slotEnd) > strtotime($booked['start_time']) && 
                             strtotime($slotEnd) <= strtotime($booked['end_time']))
                        ) {
                            $isAvailable = false;
                            break;
                        }
                    }
                    
                    if ($isAvailable) {
                        $availableSlots[] = $slotStart;
                    }
                    
                    $currentTime += 30 * 60; // Incremento de 30 minutos
                }
            }

            return $availableSlots;

        } catch (PDOException $e) {
            error_log("Erro ao buscar horários disponíveis: " . $e->getMessage());
            return [];
        }
    }

    public function updateStatus($id, $status, $user_id) {
        try {
            $query = "UPDATE " . $this->table . "
                    SET status = :status
                    WHERE id = :id AND user_id = :user_id";

            $stmt = $this->conn->prepare($query);
            
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
            $stmt->bindParam(":status", $status);
            $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);

            return $stmt->execute();

        } catch (PDOException $e) {
            error_log("Erro ao atualizar status: " . $e->getMessage());
            return false;
        }
    }

    public function getUpcomingAppointments($user_id) {
        try {
            $query = "SELECT a.*, s.name as service_name, s.duration
                    FROM " . $this->table . " a
                    JOIN services s ON a.service_id = s.id
                    WHERE a.user_id = :user_id
                    AND a.appointment_date >= CURRENT_DATE
                    AND a.status != 'cancelled'
                    ORDER BY a.appointment_date, a.start_time
                    LIMIT 5";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Erro ao buscar próximos agendamentos: " . $e->getMessage());
            return [];
        }
    }
}
?>