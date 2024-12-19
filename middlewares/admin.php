<?php
function checkAdmin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: /agendamento/views/auth/login.php");
        exit;
    }

    $database = new Database();
    $db = $database->getConnection();
    $admin = new Admin($db);

    if (!$admin->isAdminOrMod($_SESSION['user_id'])) {
        header("Location: /agendamento/views/dashboard/index.php");
        exit;
    }
}
?>