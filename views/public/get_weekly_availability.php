<?php
require_once '../../config/Database.php';
require_once '../../models/Availability.php';

header('Content-Type: application/json');

if (!isset($_GET['user_id'])) {
    echo json_encode([]);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    $availability = new Availability($db);
    $userAvailability = $availability->getUserAvailability($_GET['user_id']);
    
    // Extrair os dias da semana únicos
    $availableDays = array_unique(array_map(function($period) {
        return (int)$period['day_of_week'];
    }, $userAvailability));
    
    echo json_encode(array_values($availableDays));
} catch (Exception $e) {
    error_log("Erro: " . $e->getMessage());
    echo json_encode([]);
}
?>