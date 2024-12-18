<?php
require_once '../../config/Database.php';
require_once '../../models/Service.php';
require_once '../../models/Appointment.php';

$database = new Database();
$db = $database->getConnection();

// Verificar se um serviço foi especificado
if (!isset($_GET['service'])) {
    die("Serviço não especificado");
}

$service = new Service($db);
$serviceData = $service->getService($_GET['service']);

if (!$serviceData) {
    die("Serviço não encontrado");
}

$appointment = new Appointment($db);

// Processar o agendamento
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $start_time = $_POST['time'];
    $end_time = date('H:i', strtotime("+{$serviceData['duration']} minutes", strtotime($_POST['time'])));
    
    // Verificar disponibilidade antes de criar o agendamento
    if ($appointment->checkAvailability(
        $serviceData['user_id'], 
        $_POST['date'], 
        $start_time, 
        $end_time,
        $serviceData['id']  // Adicionado service_id para verificar capacidade
    )) {
        $appointmentData = [
            'service_id' => $serviceData['id'],
            'user_id' => $serviceData['user_id'],
            'client_name' => $_POST['client_name'],
            'client_email' => $_POST['client_email'],
            'client_phone' => $_POST['client_phone'],
            'appointment_date' => $_POST['date'],
            'start_time' => $start_time,
            'end_time' => $end_time
        ];

        if ($appointment->create($appointmentData)) {
            $success = "Agendamento realizado com sucesso!";
        } else {
            $error = "Erro ao realizar agendamento. Por favor, tente novamente.";
        }
    } else {
        $error = "Horário não disponível. Por favor, selecione outro horário.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendar - <?php echo htmlspecialchars($serviceData['name']); ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/booking.css">
</head>
<body>
    <div class="container">
        <div class="booking-form">
            <h2>Agendar <?php echo htmlspecialchars($serviceData['name']); ?></h2>
            
            <div class="service-info">
                <p><strong>Duração:</strong> <?php echo $serviceData['duration']; ?> minutos</p>
                <p><strong>Preço:</strong> R$ <?php echo number_format($serviceData['price'], 2, ',', '.'); ?></p>
                <?php if ($serviceData['description']): ?>
                    <p><strong>Descrição:</strong> <?php echo htmlspecialchars($serviceData['description']); ?></p>
                <?php endif; ?>
                <?php if ($serviceData['concurrent_capacity'] > 1): ?>
                    <p><strong>Capacidade:</strong> <?php echo $serviceData['concurrent_capacity']; ?> atendimentos simultâneos</p>
                <?php endif; ?>
            </div>

            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" id="bookingForm">
                <div class="form-group">
                    <label for="client_name">Nome Completo</label>
                    <input type="text" id="client_name" name="client_name" required>
                </div>

                <div class="form-group">
                    <label for="client_email">Email</label>
                    <input type="email" id="client_email" name="client_email" required>
                </div>

                <div class="form-group">
                    <label for="client_phone">Telefone</label>
                    <input type="tel" id="client_phone" name="client_phone" required>
                </div>

                <div class="form-group">
                    <label for="date">Data</label>
                    <input type="date" id="date" name="date" required min="<?php echo date('Y-m-d'); ?>">
                </div>

                <div class="form-group">
                    <label for="time">Horário</label>
                    <select id="time" name="time" required>
                        <option value="">Selecione um horário</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Confirmar Agendamento</button>
            </form>
        </div>
    </div>

    <script>
    document.getElementById('date').addEventListener('change', async function() {
        const date = this.value;
        const userId = <?php echo $serviceData['user_id']; ?>;
        const serviceId = <?php echo $serviceData['id']; ?>;
        const duration = <?php echo $serviceData['duration']; ?>;
        
        try {
            const response = await fetch(`get_available_times.php?date=${date}&user_id=${userId}&duration=${duration}&service_id=${serviceId}`);
            const availableSlots = await response.json();
            
            const timeSelect = document.getElementById('time');
            timeSelect.innerHTML = '<option value="">Selecione um horário</option>';
            
            if (availableSlots.length === 0) {
                timeSelect.innerHTML += '<option disabled>Nenhum horário disponível nesta data</option>';
            } else {
                availableSlots.forEach(slot => {
                    const option = document.createElement('option');
                    option.value = slot;
                    option.textContent = slot;
                    timeSelect.appendChild(option);
                });
            }
        } catch (error) {
            console.error('Erro ao buscar horários:', error);
        }
    });

    // Formatar telefone
    document.getElementById('client_phone').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 11) value = value.slice(0, 11);
        
        if (value.length > 2) {
            value = '(' + value.slice(0, 2) + ')' + value.slice(2);
        }
        if (value.length > 9) {
            value = value.slice(0, 9) + '-' + value.slice(9);
        }
        e.target.value = value;
    });
    </script>
</body>
</html>