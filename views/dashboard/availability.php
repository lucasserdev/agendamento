<?php
require_once '../../config/Database.php';
require_once '../../models/Availability.php';
include 'includes/header.php';

$database = new Database();
$db = $database->getConnection();
$availability = new Availability($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $data = [
                    'user_id' => $_SESSION['user_id'],
                    'day_of_week' => $_POST['day_of_week'],
                    'start_time' => $_POST['start_time'],
                    'end_time' => $_POST['end_time']
                ];
                
                if ($availability->create($data)) {
                    $_SESSION['success'] = "Horário adicionado com sucesso!";
                } else {
                    $_SESSION['error'] = "Erro ao adicionar horário.";
                }
                break;

            case 'delete':
                if (isset($_POST['availability_id'])) {
                    if ($availability->delete($_POST['availability_id'], $_SESSION['user_id'])) {
                        $_SESSION['success'] = "Horário removido com sucesso!";
                    } else {
                        $_SESSION['error'] = "Erro ao remover horário.";
                    }
                }
                break;
        }
        header("Location: availability.php");
        exit;
    }
}

$weekDays = [
    0 => "Domingo",
    1 => "Segunda-feira",
    2 => "Terça-feira",
    3 => "Quarta-feira",
    4 => "Quinta-feira",
    5 => "Sexta-feira",
    6 => "Sábado"
];

$userAvailability = $availability->getUserAvailability($_SESSION['user_id']);
?>

<div class="dashboard-header">
    <h2>Gerenciar Disponibilidade</h2>
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

<div class="availability-container">
    <div class="add-availability">
        <h3>Adicionar Horário</h3>
        <form method="POST" class="availability-form">
            <input type="hidden" name="action" value="add">
            
            <div class="form-group">
                <label for="day_of_week">Dia da Semana</label>
                <select name="day_of_week" id="day_of_week" required>
                    <?php foreach ($weekDays as $key => $day): ?>
                        <option value="<?php echo $key; ?>"><?php echo $day; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="start_time">Horário Inicial</label>
                <input type="time" name="start_time" id="start_time" required>
            </div>

            <div class="form-group">
                <label for="end_time">Horário Final</label>
                <input type="time" name="end_time" id="end_time" required>
            </div>

            <button type="submit" class="btn btn-primary">Adicionar Horário</button>
        </form>
    </div>

    <div class="current-availability">
        <h3>Horários Configurados</h3>
        <?php foreach ($weekDays as $dayNum => $dayName): ?>
            <div class="day-schedule">
                <h4><?php echo $dayName; ?></h4>
                <?php
                $daySchedules = array_filter($userAvailability, function($schedule) use ($dayNum) {
                    return $schedule['day_of_week'] == $dayNum;
                });
                ?>
                
                <?php if (!empty($daySchedules)): ?>
                    <div class="time-slots">
                        <?php foreach ($daySchedules as $schedule): ?>
                            <div class="time-slot">
                                <span><?php 
                                    echo date('H:i', strtotime($schedule['start_time'])) . 
                                    ' - ' . 
                                    date('H:i', strtotime($schedule['end_time'])); 
                                ?></span>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="availability_id" value="<?php echo $schedule['id']; ?>">
                                    <button type="submit" class="btn btn-small btn-danger" 
                                            onclick="return confirm('Tem certeza que deseja remover este horário?')">
                                        Remover
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="no-data">Nenhum horário configurado</p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>