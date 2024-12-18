<?php
require_once '../../config/Database.php';
require_once '../../models/Appointment.php';
require_once '../../models/Service.php';

header('Content-Type: application/json');

if (!isset($_GET['date']) || !isset($_GET['user_id']) || !isset($_GET['duration']) || !isset($_GET['service_id'])) {
    echo json_encode(['error' => 'Parâmetros inválidos']);
    exit;
}

$database = new Database();
$db = $database->getConnection();
$appointment = new Appointment($db);
$service = new Service($db);

// Buscar a capacidade do serviço
$serviceData = $service->getService($_GET['service_id']);
$capacity = $serviceData['concurrent_capacity'];

$date = $_GET['date'];

// Verificar horários que já atingiram a capacidade máxima
$query = "SELECT a.start_time 
          FROM appointments a 
          WHERE a.service_id = :service_id 
          AND a.appointment_date = :date 
          AND a.status NOT IN ('cancelled')
          GROUP BY a.start_time
          HAVING COUNT(*) >= :capacity";

$stmt = $db->prepare($query);
$stmt->bindParam(':service_id', $_GET['service_id']);
$stmt->bindParam(':date', $date);
$stmt->bindParam(':capacity', $capacity);
$stmt->execute();

// Horários que já atingiram o limite
$fullSlots = array_map(function($row) {
    return $row['start_time'];
}, $stmt->fetchAll(PDO::FETCH_ASSOC));

// Gerar horários disponíveis
$availableSlots = [];
$startTime = strtotime('08:00');
$endTime = strtotime('18:00');
$duration = intval($_GET['duration']);

while ($startTime <= $endTime - ($duration * 60)) {
    $timeSlot = date('H:i', $startTime);
    
    // Só adiciona o horário se não estiver cheio
    if (!in_array($timeSlot, $fullSlots)) {
        $availableSlots[] = $timeSlot;
    }
    
    $startTime += 30 * 60; // Incrementa 30 minutos
}

echo json_encode($availableSlots);
?>