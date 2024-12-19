<?php
// middlewares/plan_check.php

function checkPlanExpiration() {
    // Se estiver na página de plans.php ou na dashboard, permite acesso
    $currentPage = basename($_SERVER['PHP_SELF']);
    if ($currentPage == 'plans.php' || $currentPage == 'dashboard.php') {
        return true;
    }

    // Se não estiver logado, deixa o fluxo normal de autenticação
    if (!isset($_SESSION['user_id'])) {
        return true;
    }

    $database = new Database();
    $db = $database->getConnection();
    $user = new User($db);
    
    $daysLeft = $user->getDaysUntilExpiration($_SESSION['user_id']);
    
    // Se o plano estiver expirado
    if ($daysLeft !== null && $daysLeft <= 0) {
        $_SESSION['error'] = "Seu plano expirou! Por favor, escolha um novo plano para continuar usando o sistema.";
        header("Location: dashboard.php");
        exit;
    }
    
    return true;
}