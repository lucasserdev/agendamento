<?php
require_once '../../config/Database.php';
require_once '../../models/Appointment.php';
require_once '../../models/User.php';
include 'includes/header.php';

$database = new Database();
$db = $database->getConnection();
$appointment = new Appointment($db);

// Processar ações de atualização de status
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['appointment_id'])) {
        $id = $_POST['appointment_id'];
        $status = '';
        
        switch($_POST['action']) {
            case 'confirm':
                $status = 'confirmed';
                break;
            case 'in_progress':
                $status = 'in_progress';
                break;
            case 'completed':
                $status = 'completed';
                break;
            case 'no_show':
                $status = 'no_show';
                break;
            case 'cancel':
                $status = 'cancelled';
                break;
            case 'delete':
                if ($appointment->delete($id, $_SESSION['user_id'])) {
                    $_SESSION['success'] = "Agendamento excluído com sucesso!";
                } else {
                    $_SESSION['error'] = "Erro ao excluir agendamento.";
                }
                header("Location: appointments.php");
                exit;
        }
        
        if ($status && $appointment->updateStatus($id, $status, $_SESSION['user_id'])) {
            $_SESSION['success'] = "Status atualizado com sucesso!";
        } else {
            $_SESSION['error'] = "Erro ao atualizar status.";
        }
        header("Location: appointments.php");
        exit;
    }
}

// Buscar todos os agendamentos do usuário
$appointments = $appointment->getUserAppointments($_SESSION['user_id']);
?>

<div class="dashboard-header">
    <h2>Gerenciar Agendamentos</h2>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success">
        <?php 
        echo $_SESSION['success'];
        unset($_SESSION['success']);
        ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger">
        <?php 
        echo $_SESSION['error'];
        unset($_SESSION['error']);
        ?>
    </div>
<?php endif; ?>

<div class="dashboard-filters">
    <div class="filter-group">
        <label for="status-filter">Status:</label>
        <select id="status-filter">
            <option value="all">Todos</option>
            <option value="pending">Pendentes</option>
            <option value="confirmed">Confirmados</option>
            <option value="in_progress">Em Andamento</option>
            <option value="completed">Finalizados</option>
            <option value="no_show">Não Compareceu</option>
            <option value="cancelled">Cancelados</option>
        </select>
    </div>
    <div class="filter-group">
        <label for="date-filter">Data:</label>
        <select id="date-filter">
            <option value="all">Todas</option>
            <option value="today">Hoje</option>
            <option value="tomorrow">Amanhã</option>
            <option value="week">Esta semana</option>
            <option value="month">Este mês</option>
        </select>
    </div>
</div>

<div class="appointments-list">
    <?php if (!empty($appointments)): ?>
        <?php foreach ($appointments as $apt): ?>
            <div class="appointment-card" data-status="<?php echo $apt['status']; ?>" 
                 data-date="<?php echo $apt['appointment_date']; ?>">
                <div class="appointment-header">
                    <h3><?php echo htmlspecialchars($apt['service_name']); ?></h3>
                    <span class="status-badge <?php echo $apt['status']; ?>">
                        <?php 
                        $statusLabels = [
                            'pending' => 'Pendente',
                            'confirmed' => 'Confirmado',
                            'in_progress' => 'Em Andamento',
                            'completed' => 'Finalizado',
                            'no_show' => 'Não Compareceu',
                            'cancelled' => 'Cancelado'
                        ];
                        echo $statusLabels[$apt['status']] ?? ucfirst($apt['status']); 
                        ?>
                    </span>
                </div>
                <div class="appointment-details">
                    <div class="client-info">
                        <p><strong>Cliente:</strong> <?php echo htmlspecialchars($apt['client_name']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($apt['client_email']); ?></p>
                        <p><strong>Telefone:</strong> <?php echo htmlspecialchars($apt['client_phone']); ?></p>
                        
                        <?php if (!empty($apt['client_phone'])): ?>
                            <a href="<?php 
                                $message = "Olá " . $apt['client_name'] . "! ";
                                $message .= "Recebi seu agendamento para " . $apt['service_name'];
                                $message .= " no dia " . date('d/m/Y', strtotime($apt['appointment_date']));
                                $message .= " às " . date('H:i', strtotime($apt['start_time'])) . ".";
                                
                                $clientPhone = preg_replace("/[^0-9]/", "", $apt['client_phone']);
                                
                                echo "https://wa.me/55" . $clientPhone . "?text=" . urlencode($message);
                            ?>" 
                            target="_blank" 
                            class="btn btn-small btn-whatsapp">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="white" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>
                                </svg>
                                Enviar Mensagem
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="schedule-info">
                        <p><strong>Data:</strong> <?php echo date('d/m/Y', strtotime($apt['appointment_date'])); ?></p>
                        <p><strong>Horário:</strong> <?php echo date('H:i', strtotime($apt['start_time'])); ?></p>
                        <p><strong>Duração:</strong> <?php echo $apt['duration']; ?> minutos</p>
                    </div>
                </div>
                <div class="appointment-actions">
                    <?php if ($apt['status'] === 'pending'): ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="appointment_id" value="<?php echo $apt['id']; ?>">
                            <button type="submit" name="action" value="confirm" class="btn btn-small btn-success">
                                Confirmar
                            </button>
                        </form>
                    <?php endif; ?>
                    
                    <?php if ($apt['status'] === 'confirmed'): ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="appointment_id" value="<?php echo $apt['id']; ?>">
                            <button type="submit" name="action" value="in_progress" class="btn btn-small btn-info">
                                Iniciar Serviço
                            </button>
                        </form>
                    <?php endif; ?>
                    
                    <?php if ($apt['status'] === 'in_progress'): ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="appointment_id" value="<?php echo $apt['id']; ?>">
                            <button type="submit" name="action" value="completed" class="btn btn-small btn-success">
                                Finalizar Serviço
                            </button>
                        </form>
                    <?php endif; ?>
                    
                    <?php if ($apt['status'] === 'pending' || $apt['status'] === 'confirmed'): ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="appointment_id" value="<?php echo $apt['id']; ?>">
                            <button type="submit" name="action" value="no_show" class="btn btn-small btn-warning">
                                Não Compareceu
                            </button>
                        </form>
                    <?php endif; ?>
                    
                    <?php if ($apt['status'] !== 'cancelled' && $apt['status'] !== 'completed' && $apt['status'] !== 'no_show'): ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="appointment_id" value="<?php echo $apt['id']; ?>">
                            <button type="submit" name="action" value="cancel" class="btn btn-small btn-danger">
                                Cancelar
                            </button>
                        </form>
                    <?php endif; ?>

                    <?php if ($apt['status'] === 'cancelled' || $apt['status'] === 'completed' || $apt['status'] === 'no_show'): ?>
                        <form method="POST" style="display: inline;" 
                              onsubmit="return confirm('Tem certeza que deseja excluir este agendamento?');">
                            <input type="hidden" name="appointment_id" value="<?php echo $apt['id']; ?>">
                            <button type="submit" name="action" value="delete" class="btn btn-small btn-danger">
                                Excluir
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="no-data">Nenhum agendamento encontrado.</p>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const statusFilter = document.getElementById('status-filter');
    const dateFilter = document.getElementById('date-filter');
    const appointments = document.querySelectorAll('.appointment-card');

    function filterAppointments() {
        const statusValue = statusFilter.value;
        const dateValue = dateFilter.value;
        const today = new Date().toISOString().split('T')[0];

        appointments.forEach(card => {
            let showByStatus = statusValue === 'all' || card.dataset.status === statusValue;
            let showByDate = true;

            const appointmentDate = new Date(card.dataset.date);
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);

            switch(dateValue) {
                case 'today':
                    showByDate = card.dataset.date === today;
                    break;
                case 'tomorrow':
                    showByDate = card.dataset.date === tomorrow.toISOString().split('T')[0];
                    break;
                case 'week':
                    const nextWeek = new Date();
                    nextWeek.setDate(nextWeek.getDate() + 7);
                    showByDate = appointmentDate >= new Date() && appointmentDate <= nextWeek;
                    break;
                case 'month':
                    const nextMonth = new Date();
                    nextMonth.setMonth(nextMonth.getMonth() + 1);
                    showByDate = appointmentDate >= new Date() && appointmentDate <= nextMonth;
                    break;
            }

            card.style.display = showByStatus && showByDate ? 'block' : 'none';
        });
    }

    statusFilter.addEventListener('change', filterAppointments);
    dateFilter.addEventListener('change', filterAppointments);
});
</script>

<?php include 'includes/footer.php'; ?>