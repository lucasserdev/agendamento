<?php
session_start();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
</head>
<body>
    <nav class="admin-nav">
        <div class="nav-brand">
            Painel Administrativo
        </div>
        <ul class="nav-menu">
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="users.php">Usuários</a></li>
            <li><a href="../dashboard/index.php">Voltar ao Site</a></li>
            <li><a href="../../logout.php">Sair</a></li>
        </ul>
    </nav>