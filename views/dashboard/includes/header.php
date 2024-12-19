<?php
session_start();
require_once '../../config/Database.php';
require_once '../../models/Admin.php';
require_once '../../models/User.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

// Instanciar o Admin e User para verificar permissões e plano
$database = new Database();
$db = $database->getConnection();
$admin = new Admin($db);
$user = new User($db);

// Verificar expiração do plano
$currentPage = basename($_SERVER['PHP_SELF']);
$allowedPages = ['index.php', 'plans.php'];

if (!in_array($currentPage, $allowedPages)) {
    $daysLeft = $user->getDaysUntilExpiration($_SESSION['user_id']);
    if ($daysLeft !== null && $daysLeft <= 0) {
        $_SESSION['error'] = "Seu plano expirou! Por favor, escolha um novo plano para continuar usando o sistema.";
        header("Location: index.php");
        exit;
    }
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
    <style>
    .alert {
        padding: 15px;
        margin-bottom: 20px;
        border: 1px solid transparent;
        border-radius: 4px;
    }
    
    .alert-danger {
        color: #721c24;
        background-color: #f8d7da;
        border-color: #f5c6cb;
    }

    .alert-warning {
        color: #856404;
        background-color: #fff3cd;
        border-color: #ffeeba;
    }

    .plan-expired-banner {
        background-color: #f8d7da;
        border-bottom: 1px solid #f5c6cb;
        padding: 10px;
        text-align: center;
        color: #721c24;
        position: sticky;
        top: 0;
        z-index: 1000;
    }

    .plan-expired-banner a {
        color: #721c24;
        font-weight: bold;
        text-decoration: underline;
    }
    </style>
</head>
<body>
    <?php
    $daysLeft = $user->getDaysUntilExpiration($_SESSION['user_id']);
    if ($daysLeft !== null && $daysLeft <= 0):
    ?>
    <div class="plan-expired-banner">
        ⚠️ Seu plano expirou! 
        <a href="plans.php">Clique aqui para escolher um novo plano</a>
    </div>
    <?php endif; ?>

    <div class="dashboard-container">
        <nav class="sidebar">
            <div class="sidebar-header">
                <h3>Painel de Controle</h3>
            </div>
            <div class="sidebar-user">
                <span>Bem-vindo, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
            </div>
            <ul class="sidebar-nav">
            <?php if ($admin->isAdminOrMod($_SESSION['user_id'])): ?>
                <li><a href="/agendamento/views/admin/dashboard.php">Painel Admin</a></li>
            <?php endif; ?>
                <li><a href="index.php">Dashboard</a></li>
                <?php if ($daysLeft === null || $daysLeft > 0): ?>
                    <li><a href="services.php">Meus Serviços</a></li>
                    <li><a href="appointments.php">Agendamentos</a></li>
                    <li><a href="availability.php">Disponibilidade</a></li>
                    <li><a href="settings.php">Configurações</a></li>
                <?php endif; ?>
                <li><a href="../../logout.php">Sair</a></li>
            </ul>
        </nav>
        <main class="content">
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>