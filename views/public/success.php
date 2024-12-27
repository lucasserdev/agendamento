<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendamento Realizado</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/booking.css">
</head>
<body>
    <div class="booking-container">
        <div class="success-content" style="text-align: center; padding: 40px 20px;">
            <div class="success-icon" style="color: #2ecc71; font-size: 48px; margin-bottom: 20px;">✓</div>
            <h2>Agendamento Realizado com Sucesso!</h2>
            <p>Obrigado por agendar conosco.</p>
            <p>Entraremos em contato em breve para confirmar seu horário.</p>
            
            <?php if (isset($_GET['details'])): ?>
                <?php $details = json_decode(base64_decode($_GET['details']), true); ?>
                <div class="appointment-details" style="margin: 30px 0; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                    <h3>Detalhes do Agendamento</h3>
                    <p><strong>Serviço:</strong> <?php echo htmlspecialchars($details['service']); ?></p>
                    <p><strong>Data:</strong> <?php echo htmlspecialchars($details['date']); ?></p>
                    <p><strong>Horário:</strong> <?php echo htmlspecialchars($details['time']); ?></p>
                    <p><strong>Cliente:</strong> <?php echo htmlspecialchars($details['client_name']); ?></p>
                </div>
            <?php endif; ?>

            <a href="services.php?user=<?php echo $_GET['user']; ?>" class="btn btn-primary" style="margin-top: 20px;">
                Voltar para Serviços
            </a>
        </div>
    </div>
</body>
</html>