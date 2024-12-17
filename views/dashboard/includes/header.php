<?php
session_start();
error_log('User ID: ' . $_SESSION['user_id']); // Debug
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema de Agendamento</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
</head>
<body>
    <div class="dashboard-container">
        <nav class="sidebar">
            <div class="sidebar-header">
                <h3>Painel de Controle</h3>
            </div>
            <div class="sidebar-user">
                <span>Bem-vindo, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
            </div>
            <ul class="sidebar-nav">
                <li><a href="index.php">Dashboard</a></li>
                <li><a href="services.php">Meus Serviços</a></li>
                <li><a href="appointments.php">Agendamentos</a></li>
                <li><a href="availability.php">Disponibilidade</a></li>
                <li><a href="../../logout.php">Sair</a></li>
            </ul>
        </nav>
        <main class="content">