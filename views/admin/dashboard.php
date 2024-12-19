<?php
require_once '../../config/Database.php';
require_once '../../models/Admin.php';
require_once '../../middlewares/admin.php';
include 'includes/header.php';

// Chamar a função de verificação
checkAdmin();

$database = new Database();
$db = $database->getConnection();
$admin = new Admin($db);

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['user_id'])) {
        $userId = $_POST['user_id'];
        
        switch ($_POST['action']) {
            case 'change_plan':
                if (isset($_POST['plan_id'])) {
                    $expirationDate = !empty($_POST['expiration_date']) ? 
                                    $_POST['expiration_date'] : null;
                    
                    if ($admin->updateUserPlan($userId, $_POST['plan_id'], $expirationDate)) {
                        $_SESSION['success'] = "Plano atualizado com sucesso!";
                    } else {
                        $_SESSION['error'] = "Erro ao atualizar plano.";
                    }
                }
                break;
            case 'block':
                $admin->updateUserStatus($userId, 'blocked');
                $_SESSION['success'] = "Usuário bloqueado com sucesso!";
                break;
            case 'activate':
                $admin->updateUserStatus($userId, 'active');
                $_SESSION['success'] = "Usuário ativado com sucesso!";
                break;
            case 'deactivate':
                $admin->updateUserStatus($userId, 'inactive');
                $_SESSION['success'] = "Usuário desativado com sucesso!";
                break;
        }
        header("Location: dashboard.php");
        exit;
    }
}

// Buscar usuários
$users = $admin->getAllUsers();
?>

<div class="admin-container">
    <h1>Dashboard Administrativo</h1>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php 
            echo $_SESSION['success'];
            unset($_SESSION['success']);
            ?>
        </div>
    <?php endif; ?>

    <div class="users-section">
    <h2>Gerenciar Usuários</h2>
    
    <table class="users-table">
        <thead>
            <tr>
                <th>Nome</th>
                <th>Email</th>
                <th>Status</th>
                <th>Plano Atual</th>
                <th>Expira em</th>
                <th>Data de Cadastro</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td>
                        <span class="status-badge <?php echo $user['status']; ?>">
                            <?php echo ucfirst($user['status']); ?>
                        </span>
                    </td>
                    <td>
                        <span class="plan-badge">
                            <?php 
                            if ($user['plan_name']) {
                                echo htmlspecialchars($user['plan_name']); 
                            } else {
                                echo 'Sem plano';
                            }
                            ?>
                        </span>
                    </td>
                    <td>
                        <?php 
                        if ($user['plan_expires_at']) {
                            $daysLeft = (new DateTime($user['plan_expires_at']))->diff(new DateTime())->days;
                            echo $daysLeft . ' dias (' . date('d/m/Y', strtotime($user['plan_expires_at'])) . ')';
                        } else {
                            echo '-';
                        }
                        ?>
                    </td>
                    <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                    <td class="actions">
                        <button onclick="openPlanModal(<?php echo $user['id']; ?>)" class="btn btn-primary">
                            Alterar Plano
                        </button>
                        <?php if ($user['status'] === 'active'): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <button type="submit" name="action" value="block" class="btn btn-danger">
                                    Bloquear
                                </button>
                            </form>
                        <?php else: ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <button type="submit" name="action" value="activate" class="btn btn-success">
                                    Ativar
                                </button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal para alteração de plano -->
<div id="planModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h3>Alterar Plano do Usuário</h3>
        <form method="POST" action="">
            <input type="hidden" name="action" value="change_plan">
            <input type="hidden" name="user_id" id="planUserId">
            
            <div class="form-group">
                <label>Escolha o Plano:</label>
                <select name="plan_id" required class="form-control">
                    <option value="1">Bronze - R$ 29,90 (3 serviços, 50 agendamentos/mês)</option>
                    <option value="2">Prata - R$ 49,90 (6 serviços, 50 agendamentos/mês)</option>
                    <option value="3">Ouro - R$ 69,90 (Ilimitado)</option>
                    <option value="4">Teste - Gratuito (7 dias de teste com todas as funcionalidades)</option>
                </select>
            </div>

            <div class="form-group">
                <label>Data de Expiração:</label>
                <input type="date" name="expiration_date" class="form-control" 
                       min="<?php echo date('Y-m-d'); ?>" 
                       value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>">
                <small class="form-text text-muted">Deixe em branco para usar o padrão (7 dias para teste, 30 para outros planos)</small>
            </div>

            <button type="submit" class="btn btn-primary">Salvar Alteração</button>
        </form>
    </div>
</div>

<script>
const planModal = document.getElementById("planModal");
const planSpan = planModal.getElementsByClassName("close")[0];

function openPlanModal(userId) {
    document.getElementById("planUserId").value = userId;
    planModal.style.display = "block";
}

planSpan.onclick = function() {
    planModal.style.display = "none";
}

window.onclick = function(event) {
    if (event.target == planModal) {
        planModal.style.display = "none";
    }
}
</script>

<?php include 'includes/footer.php'; ?>