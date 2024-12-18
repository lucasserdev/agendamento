<?php
require_once '../../config/Database.php';
require_once '../../models/Appointment.php';
require_once '../../models/Service.php';

error_log("Parâmetros recebidos:");
error_log("service_id: " . $_GET['service_id']);
error_log("user_id: " . $_GET['user_id']);
error_log("date: " . $_GET['date']);
error_log("duration: " . $_GET['duration']);

header('Content-Type: application/json');

if (!isset($_GET['date']) || !isset($_GET['user_id']) || !isset($_GET['duration']) || !isset($_GET['service_id'])) {
    echo json_encode([]);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    // Primeiro, verifica a capacidade do serviço
    $query = "SELECT concurrent_capacity FROM services WHERE id = :service_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':service_id', $_GET['service_id']);
    $stmt->execute();
    $serviceData = $stmt->fetch(PDO::FETCH_ASSOC);
    $maxCapacity = $serviceData['concurrent_capacity'];

    // Depois, busca os horários com suas contagens
    $query = "SELECT TIME_FORMAT(start_time, '%H:%i') as start_time, 
                     COUNT(*) as booking_count 
              FROM appointments 
              WHERE service_id = :service_id 
              AND appointment_date = :date 
              AND status != 'cancelled'
              GROUP BY start_time";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':service_id', $_GET['service_id']);
    $stmt->bindParam(':date', $_GET['date']);
    $stmt->execute();
    
    // Array para guardar horários que já estão lotados
    $unavailableSlots = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if ((int)$row['booking_count'] >= $maxCapacity) {
            $unavailableSlots[] = $row['start_time'];
        }
    }

    // Debug
    error_log("Data selecionada: " . $_GET['date']);
    error_log("Capacidade máxima: " . $maxCapacity);
    error_log("Horários indisponíveis: " . print_r($unavailableSlots, true));

    // Gerar todos os horários possíveis
    $availableSlots = [];
    $startTime = strtotime('08:00');
    $endTime = strtotime('18:00');
    $interval = 30 * 60; // 30 minutos

    while ($startTime <= $endTime) {
        $currentSlot = date('H:i', $startTime);
        if (!in_array($currentSlot, $unavailableSlots)) {
            $availableSlots[] = $currentSlot;
        }
        $startTime += $interval;
    }

    error_log("Horários disponíveis: " . print_r($availableSlots, true));
    echo json_encode($availableSlots);

} catch (Exception $e) {
    error_log("Erro: " . $e->getMessage());
    echo json_encode([]);
}
?>