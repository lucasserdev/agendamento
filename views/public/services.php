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
</head>
<body>
    <div class="container">
        <div class="services-header">
            <h1>Serviços de <?php echo htmlspecialchars($userData['name']); ?></h1>
            <p class="subtitle">Escolha o serviço desejado para realizar seu agendamento</p>
        </div>

        <div class="services-grid">
            <?php if (!empty($services)): ?>
                <?php foreach ($services as $srv): ?>
                    <div class="service-card">
                        <div class="service-info">
                            <h2><?php echo htmlspecialchars($srv['name']); ?></h2>
                            <p class="description"><?php echo htmlspecialchars($srv['description']); ?></p>
                            <div class="service-details">
                                <span class="duration">
                                    <i class="icon-clock"></i>
                                    <?php echo $srv['duration']; ?> minutos
                                </span>
                                <span class="price">
                                    <i class="icon-tag"></i>
                                    R$ <?php echo number_format($srv['price'], 2, ',', '.'); ?>
                                </span>
                            </div>
                        </div>
                        <a href="booking.php?service=<?php echo $srv['id']; ?>" class="btn btn-primary">
                            Agendar
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-services">Nenhum serviço disponível no momento.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>