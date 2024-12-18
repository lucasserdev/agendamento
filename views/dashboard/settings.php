<?php
require_once '../../config/Database.php';
require_once '../../models/User.php';
include 'includes/header.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'save':
                $whatsapp = preg_replace("/[^0-9]/", "", $_POST['whatsapp']);
                if ($user->updateWhatsapp($_SESSION['user_id'], $whatsapp)) {
                    $_SESSION['success'] = "WhatsApp atualizado com sucesso!";
                } else {
                    $_SESSION['error'] = "Erro ao atualizar WhatsApp.";
                }
                break;

            case 'delete':
                if ($user->updateWhatsapp($_SESSION['user_id'], null)) {
                    $_SESSION['success'] = "WhatsApp removido com sucesso!";
                } else {
                    $_SESSION['error'] = "Erro ao remover WhatsApp.";
                }
                break;
        }
        header("Location: settings.php");
        exit;
    }
}

$userData = $user->getUserData($_SESSION['user_id']);
?>

<div class="dashboard-header">
    <h2>Configurações</h2>
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

<div class="settings-card">
    <h3>Configurações de Contato</h3>
    <form method="POST" id="whatsappForm">
        <input type="hidden" name="action" value="save">
        <div class="form-group">
            <label for="whatsapp">Seu WhatsApp (com DDD)</label>
            <input type="text" id="whatsapp" name="whatsapp" 
                   value="<?php echo $userData['whatsapp'] ?? ''; ?>" 
                   placeholder="(99) 99999-9999" required>
        </div>
        <button type="submit" class="btn btn-primary">Salvar</button>
        
        <?php if (!empty($userData['whatsapp'])): ?>
            <button type="button" onclick="deleteWhatsapp()" class="btn btn-danger">Excluir</button>
        <?php endif; ?>
    </form>

    <?php if (!empty($userData['whatsapp'])): ?>
        <div class="current-whatsapp">
            <h4>Número Atual:</h4>
            <p class="whatsapp-number">
                <?php 
                    $number = $userData['whatsapp'];
                    // Formatar o número como (XX) XXXXX-XXXX
                    $formatted = preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $number);
                    echo $formatted;
                ?>
            </p>
        </div>
    <?php endif; ?>
</div>

<script>
// Máscara para o input de WhatsApp
document.getElementById('whatsapp').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    let formatted = '';
    
    if (value.length <= 11) {
        if (value.length > 2) {
            formatted += '(' + value.substring(0,2) + ') ';
            if (value.length > 7) {
                formatted += value.substring(2,7) + '-' + value.substring(7);
            } else {
                formatted += value.substring(2);
            }
        } else {
            formatted = value;
        }
        
        e.target.value = formatted;
    }
});

// Função para excluir o WhatsApp
function deleteWhatsapp() {
    if (confirm('Tem certeza que deseja remover seu número de WhatsApp?')) {
        const form = document.getElementById('whatsappForm');
        const actionInput = form.querySelector('input[name="action"]');
        actionInput.value = 'delete';
        form.submit();
    }
}
</script>