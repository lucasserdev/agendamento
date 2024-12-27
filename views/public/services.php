<?php
require_once '../../config/Database.php';
require_once '../../models/Service.php';
require_once '../../models/User.php';

$database = new Database();
$db = $database->getConnection();

// Verificar se um prestador foi especificado
if (!isset($_GET['user'])) {
    die("Prestador não especificado");
}

$user = new User($db);
$userData = $user->getById($_GET['user']);

if (!$userData) {
    die("Prestador não encontrado");
}

$service = new Service($db);
$services = $service->getUserServices($_GET['user']);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Serviços - <?php echo htmlspecialchars($userData['name']); ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/services-public.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>
    <div class="page-wrapper">
        <div class="container">
            <div class="services-header">
                <div class="header-content">
                    <h1>Serviços de <?php echo htmlspecialchars($userData['name']); ?></h1>
                    <p class="subtitle">Escolha o serviço desejado para realizar seu agendamento</p>
                </div>
            </div>

            <div class="services-grid">
                <?php if (!empty($services)): ?>
                    <?php foreach ($services as $srv): ?>
                        <div class="service-card">
                            <div class="service-info">
                                <div class="service-header">
                                    <h2><?php echo htmlspecialchars($srv['name']); ?></h2>
                                    <div class="price-tag">
                                        <span class="currency">R$</span>
                                        <span class="value"><?php echo number_format($srv['price'], 2, ',', '.'); ?></span>
                                    </div>
                                </div>
                                <p class="description"><?php echo htmlspecialchars($srv['description']); ?></p>
                                <div class="service-details">
                                    <span class="duration">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <polyline points="12 6 12 12 16 14"></polyline>
                                        </svg>
                                        <?php echo $srv['duration']; ?> minutos
                                    </span>
                                </div>
                            </div>
                            <a href="booking.php?service=<?php echo $srv['id']; ?>" class="btn-booking">
                                Agendar Agora
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                    <polyline points="12 5 19 12 12 19"></polyline>
                                </svg>
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-services">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                        <p>Nenhum serviço disponível no momento.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>