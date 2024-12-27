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
    
    if ($appointment->checkAvailability(
        $serviceData['user_id'], 
        $_POST['date'], 
        $start_time, 
        $end_time,
        $serviceData['id']
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
            $details = base64_encode(json_encode([
                'service' => $serviceData['name'],
                'date' => date('d/m/Y', strtotime($_POST['date'])),
                'time' => $start_time,
                'client_name' => $_POST['client_name']
            ]));
            
            header("Location: success.php?details={$details}&user={$serviceData['user_id']}");
            exit;
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
    <div class="booking-container">
        <h2>Agendar <?php echo htmlspecialchars($serviceData['name']); ?></h2>

        <div class="service-info">
            <p><strong>Duração:</strong> <?php echo $serviceData['duration']; ?> minutos</p>
            <p><strong>Preço:</strong> R$ <?php echo number_format($serviceData['price'], 2, ',', '.'); ?></p>
            <?php if ($serviceData['description']): ?>
                <p><strong>Descrição:</strong> <?php echo htmlspecialchars($serviceData['description']); ?></p>
            <?php endif; ?>
        </div>

        <!-- <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?> -->

        <div class="schedule-section">
            <h3>Selecione o dia do atendimento</h3>

            <div class="calendar-navigation">
                <button class="btn-nav prev-week" disabled>&lt; Semana anterior</button>
                <span class="week-indicator">Semana atual</span>
                <button class="btn-nav next-week">Próxima semana &gt;</button>
            </div>
            
            <div class="legend">
                <div class="legend-item">
                    <div class="legend-color" style="background: #e3f2fd;"></div>
                    <span>Disponível</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background: #f5f5f5;"></div>
                    <span>Indisponível</span>
                </div>
            </div>

            <div class="days-grid">
                <div class="day-button" data-day="0">
                    <div class="day-name">Dom</div>
                    <div class="day-number"></div>
                </div>
                <div class="day-button" data-day="1">
                    <div class="day-name">Seg</div>
                    <div class="day-number"></div>
                </div>
                <div class="day-button" data-day="2">
                    <div class="day-name">Ter</div>
                    <div class="day-number"></div>
                </div>
                <div class="day-button" data-day="3">
                    <div class="day-name">Qua</div>
                    <div class="day-number"></div>
                </div>
                <div class="day-button" data-day="4">
                    <div class="day-name">Qui</div>
                    <div class="day-number"></div>
                </div>
                <div class="day-button" data-day="5">
                    <div class="day-name">Sex</div>
                    <div class="day-number"></div>
                </div>
                <div class="day-button" data-day="6">
                    <div class="day-name">Sáb</div>
                    <div class="day-number"></div>
                </div>
            </div>

            <div class="time-slots" style="display: none;"></div>
        </div>

        <form method="POST" id="bookingForm" class="form-section">
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

            <input type="hidden" id="selected_date" name="date">
            <input type="hidden" id="selected_time" name="time">

            <button type="submit" class="btn btn-primary">Confirmar Agendamento</button>
        </form>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const userId = <?php echo $serviceData['user_id']; ?>;
        const serviceId = <?php echo $serviceData['id']; ?>;
        const duration = <?php echo $serviceData['duration']; ?>;
        let currentWeekOffset = 0;

        function formatMonthName(month) {
            const months = [
                'Janeiro', 'Fevereiro', 'Março', 'Abril',
                'Maio', 'Junho', 'Julho', 'Agosto',
                'Setembro', 'Outubro', 'Novembro', 'Dezembro'
            ];
            return months[month];
        }

        function getWeekDates(weekOffset = 0) {
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            // Pegar o primeiro dia da semana (Domingo)
            let startOfWeek = new Date(today);
            startOfWeek.setDate(today.getDate() - today.getDay()); // Volta para o domingo
            
            // Adicionar o offset de semanas
            startOfWeek.setDate(startOfWeek.getDate() + (weekOffset * 7));
            
            let dates = [];
            for (let i = 0; i < 7; i++) {
                let date = new Date(startOfWeek);
                date.setDate(startOfWeek.getDate() + i);
                dates.push(date);
            }
            
            return dates;
        }

        function updateDayButtons(availableDays) {
            const dates = getWeekDates(currentWeekOffset);
            const dayButtons = document.querySelectorAll('.day-button');
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            dayButtons.forEach((button, index) => {
                const date = dates[index];
                const dayNumber = date.getDay();
                const day = date.getDate();
                const month = formatMonthName(date.getMonth());
                
                button.querySelector('.day-number').textContent = `${day} ${month}`;
                button.dataset.date = date.toISOString().split('T')[0];
                
                button.classList.remove('available', 'unavailable', 'past-date', 'selected');
                
                if (date < today) {
                    button.classList.add('unavailable', 'past-date');
                } else if (availableDays.includes(dayNumber)) {
                    button.classList.add('available');
                } else {
                    button.classList.add('unavailable');
                }
            });

            document.querySelector('.prev-week').disabled = currentWeekOffset <= 0;
            document.querySelector('.week-indicator').textContent = 
                currentWeekOffset === 0 ? 'Semana atual' : 
                `Semana de ${dates[0].getDate()} de ${formatMonthName(dates[0].getMonth())}`;
        }

        function loadWeekAvailability() {
            fetch(`get_weekly_availability.php?user_id=${userId}`)
                .then(response => response.json())
                .then(availableDays => {
                    updateDayButtons(availableDays);
                });
        }

        function loadAvailableTimeSlots(date) {
            const timeSlotsContainer = document.querySelector('.time-slots');
            const alertContainer = document.querySelector('.alert-danger');
            if (alertContainer) alertContainer.remove();
            
            timeSlotsContainer.style.display = 'grid';
            
            fetch(`get_available_times.php?date=${date}&user_id=${userId}&service_id=${serviceId}&duration=${duration}`)
                .then(response => response.json())
                .then(slots => {
                    timeSlotsContainer.innerHTML = '';
                    
                    // Filtrar horários se for hoje
                    let availableSlots = slots;
                    const now = new Date();
                    const selectedDate = new Date(date);
                    
                    if (selectedDate.toDateString() === now.toDateString()) {
                        const currentTime = now.getHours() * 60 + now.getMinutes(); // Converter para minutos
                        
                        availableSlots = slots.filter(time => {
                            const [hours, minutes] = time.split(':').map(Number);
                            const slotTime = hours * 60 + minutes; // Converter para minutos
                            
                            // Adicionar 30 minutos de margem ao horário atual
                            return slotTime > (currentTime + 30);
                        });
                    }
                    
                    if (availableSlots.length === 0) {
                        const alertDiv = document.createElement('div');
                        alertDiv.className = 'alert alert-danger';
                        alertDiv.textContent = 'Nenhum horário disponível nesta data.';
                        timeSlotsContainer.parentNode.insertBefore(alertDiv, timeSlotsContainer);
                        timeSlotsContainer.style.display = 'none';
                        return;
                    }
                    
                    availableSlots.forEach(time => {
                        const slot = document.createElement('div');
                        slot.className = 'time-slot';
                        slot.textContent = time;
                        slot.addEventListener('click', function() {
                            document.querySelectorAll('.time-slot.selected').forEach(s => {
                                s.classList.remove('selected');
                            });
                            this.classList.add('selected');
                            document.getElementById('selected_time').value = time;
                        });
                        timeSlotsContainer.appendChild(slot);
                    });
                });
        }

        // Inicializar navegação entre semanas
        document.querySelector('.prev-week').addEventListener('click', () => {
            if (currentWeekOffset > 0) {
                currentWeekOffset--;
                loadWeekAvailability();
            }
        });

        document.querySelector('.next-week').addEventListener('click', () => {
            currentWeekOffset++;
            loadWeekAvailability();
        });

        // Adicionar eventos de clique nos botões de dia
        document.querySelectorAll('.day-button').forEach(button => {
            button.addEventListener('click', function() {
                if (this.classList.contains('unavailable')) return;
                
                document.querySelectorAll('.day-button.selected').forEach(btn => {
                    btn.classList.remove('selected');
                });
                
                this.classList.add('selected');
                document.getElementById('selected_date').value = this.dataset.date;
                loadAvailableTimeSlots(this.dataset.date);
            });
        });

        // Formatação do telefone
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

        // Validação do formulário
        document.getElementById('bookingForm').addEventListener('submit', function(e) {
            const date = document.getElementById('selected_date').value;
            const time = document.getElementById('selected_time').value;

            if (!date || !time) {
                e.preventDefault();
                alert('Por favor, selecione uma data e horário para o agendamento.');
            }
        });

        // Inicializar a primeira semana
        loadWeekAvailability();
    });
    </script>
</body>
</html>