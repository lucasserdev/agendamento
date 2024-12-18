<?php 
require_once '../../config/Database.php';
require_once '../../models/Appointment.php';
require_once '../../models/Service.php';
include 'includes/header.php';

$database = new Database();
$db = $database->getConnection();

$appointment = new Appointment($db);
$service = new Service($db);

$upcoming_appointments = $appointment->getUserAppointments($_SESSION['user_id']);
$services = $service->getUserServices($_SESSION['user_id']);

$total_services = count($services);
$total_appointments = count($upcoming_appointments);

// Gerar link público para compartilhar
$public_link = "http://" . $_SERVER['HTTP_HOST'] . "/agendamento/views/public/services.php?user=" . $_SESSION['user_id'];
?>

<div class="dashboard-header">
    <h2>Visão Geral</h2>
</div>

<div style="padding: 20px; background: #fff; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
    <h3 style="margin-bottom: 15px; color: #2c3e50;">Seu Link de Agendamento</h3>
    <p style="margin-bottom: 15px; color: #666;">Compartilhe este link com seus clientes para que eles vejam todos os seus serviços:</p>
    <div style="display: flex; gap: 10px;">
        <input type="text" id="shareLink" value="<?php echo $public_link; ?>" 
               style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 4px;" 
               readonly onclick="this.select();">
        <button onclick="copyLink()" 
                style="padding: 10px 20px; background: #4a90e2; color: white; border: none; border-radius: 4px; cursor: pointer;">
            Copiar Link
        </button>
    </div>
</div>

<div class="dashboard-stats">
    <div class="stat-card">
        <h3>Serviços Ativos</h3>
        <p class="stat-number"><?php echo $total_services; ?></p>
    </div>
    <div class="stat-card">
        <h3>Agendamentos</h3>
        <p class="stat-number"><?php echo $total_appointments; ?></p>
    </div>
</div>

<div class="dashboard-section">
    <h3>Próximos Agendamentos</h3>
    <?php if (!empty($upcoming_appointments)): ?>
        <div class="appointments-list">
            <?php foreach(array_slice($upcoming_appointments, 0, 5) as $appointment): ?>
                <div class="appointment-card">
                    <div class="service-info">
                        <h4><?php echo htmlspecialchars($appointment['service_name']); ?></h4>
                    </div>
                    <div class="client-info">
                        <p>Cliente: <?php echo htmlspecialchars($appointment['client_name']); ?></p>
                        <p>Data: <?php echo date('d/m/Y', strtotime($appointment['appointment_date'])); ?></p>
                        <p>Horário: <?php echo date('H:i', strtotime($appointment['start_time'])); ?></p>
                    </div>
                    <div class="appointment-status">
                        <span class="status-badge <?php echo $appointment['status']; ?>">
                            <?php 
                            $statusLabels = [
                                'pending' => 'Pendente',
                                'confirmed' => 'Confirmado',
                                'in_progress' => 'Em Andamento',
                                'completed' => 'Finalizado',
                                'no_show' => 'Não Compareceu',
                                'cancelled' => 'Cancelado'
                            ];
                            echo $statusLabels[$appointment['status']] ?? ucfirst($appointment['status']); 
                            ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="no-data">Nenhum agendamento próximo.</p>
    <?php endif; ?>
</div>

<script>
function copyLink() {
    var copyText = document.getElementById("shareLink");
    copyText.select();
    
    try {
        navigator.clipboard.writeText(copyText.value).then(function() {
            alert('Link copiado para a área de transferência!');
        });
    } catch (err) {
        // Fallback para o método antigo se o clipboard API não funcionar
        document.execCommand('copy');
        alert('Link copiado para a área de transferência!');
    }
}
</script>

<?php include 'includes/footer.php'; ?>