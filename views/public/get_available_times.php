<?php
require_once '../../config/Database.php';
require_once '../../models/Appointment.php';

header('Content-Type: application/json');

if (!isset($_GET['date']) || !isset($_GET['user_id']) || !isset($_GET['duration'])) {
    echo json_encode(['error' => 'Parâmetros inválidos']);
    exit;
}

$database = new Database();
$db = $database->getConnection();
$appointment = new Appointment($db);

$availableSlots = $appointment->getAvailableTimeSlots(
    $_GET['user_id'],
    $_GET['date'],
    $_GET['duration']
);

echo json_encode($availableSlots);
?>