<?php
require_once '../../config/Database.php';
require_once '../../models/Appointment.php';
require_once '../../models/Service.php';
require_once '../../models/Availability.php';

header('Content-Type: application/json');

if (!isset($_GET['date']) || !isset($_GET['user_id']) || !isset($_GET['duration']) || !isset($_GET['service_id'])) {
    echo json_encode([]);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    // Definir fuso horário
    date_default_timezone_set('America/Sao_Paulo'); // Ou seu fuso horário correto

    // Verificar se é hoje e pegar horário atual
    $selectedDate = date('Y-m-d', strtotime($_GET['date']));
    $currentDate = date('Y-m-d');
    $currentHour = (int)date('H');
    $currentMinutes = (int)date('i');
    $currentTimeInMinutes = ($currentHour * 60) + $currentMinutes;

     // Debug
     error_log("Selected Date: " . $selectedDate);
     error_log("Current Date: " . $currentDate);
     error_log("Current Time in Minutes: " . $currentTimeInMinutes);

    // Verificar disponibilidade configurada para o dia
    $availability = new Availability($db);
    $dayOfWeek = date('w', strtotime($_GET['date']));
    $availablePeriods = $availability->getDayAvailability($_GET['user_id'], $dayOfWeek);

    // Se não houver disponibilidade configurada para este dia, retorna array vazio
    if (empty($availablePeriods)) {
        echo json_encode([]);
        exit;
    }

    // Verificar capacidade do serviço
    $query = "SELECT concurrent_capacity FROM services WHERE id = :service_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':service_id', $_GET['service_id']);
    $stmt->execute();
    $serviceData = $stmt->fetch(PDO::FETCH_ASSOC);
    $maxCapacity = $serviceData['concurrent_capacity'];

    // Buscar agendamentos existentes
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
    
    $unavailableSlots = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if ((int)$row['booking_count'] >= $maxCapacity) {
            $unavailableSlots[] = $row['start_time'];
        }
    }

    // Gerar slots baseados na disponibilidade configurada
    $availableSlots = [];
    $interval = 30 * 60; // 30 minutos

    foreach ($availablePeriods as $period) {
        $startTime = strtotime($period['start_time']);
        $endTime = strtotime($period['end_time']);
        
        while ($startTime < $endTime) {
            $currentSlot = date('H:i', $startTime);
            list($slotHour, $slotMinute) = explode(':', $currentSlot);
            $slotTimeInMinutes = ((int)$slotHour * 60) + (int)$slotMinute;

            // Debug
            error_log("Verificando horário: " . $currentSlot);
            error_log("Minutos do slot: " . $slotTimeInMinutes);
            error_log("Minutos atuais + 30: " . ($currentTimeInMinutes + 30));
            
            // Se for hoje, verificar se o horário já passou + margem de 30 min
            $showSlot = true;
            if ($selectedDate === $currentDate) {
                if ($slotTimeInMinutes <= ($currentTimeInMinutes + 30)) {
                    $showSlot = false;
                    error_log("Horário bloqueado: " . $currentSlot);
                } else {
                    error_log("Horário liberado: " . $currentSlot);
                }
            }
            
            if ($showSlot && !in_array($currentSlot, $unavailableSlots)) {
                // Verificar se há tempo suficiente até o fim do período
                $slotEndTime = $startTime + ($_GET['duration'] * 60);
                if ($slotEndTime <= $endTime) {
                    $availableSlots[] = $currentSlot;
                    error_log("Added available slot: " . $currentSlot);
                }
            }
            
            $startTime += $interval;
        }
    }

    echo json_encode($availableSlots);

} catch (Exception $e) {
    error_log("Erro: " . $e->getMessage());
    echo json_encode([]);
}
?>