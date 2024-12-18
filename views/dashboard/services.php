<?php
require_once '../../config/Database.php';
require_once '../../models/Service.php';
include 'includes/header.php';

$database = new Database();
$db = $database->getConnection();
$service = new Service($db);

// Debug para verificar se o POST está chegando
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log('POST recebido: ' . print_r($_POST, true));
}

// Processar criação/atualização
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_service'])) {
    try {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $duration = (int)$_POST['duration'];
        $price = (float)str_replace(',', '.', $_POST['price']);
        $concurrent_capacity = (int)$_POST['concurrent_capacity'];
        
        $serviceData = [
            'user_id' => $_SESSION['user_id'],
            'name' => $name,
            'description' => $description,
            'duration' => $duration,
            'price' => $price,
            'concurrent_capacity' => $concurrent_capacity
        ];

        // Debug dos dados
        error_log('Dados do serviço: ' . print_r($serviceData, true));

        if (isset($_POST['service_id']) && !empty($_POST['service_id'])) {
            // Atualização
            $serviceData['id'] = $_POST['service_id'];
            if ($service->update($serviceData)) {
                $_SESSION['success'] = "Serviço atualizado com sucesso!";
            } else {
                $_SESSION['error'] = "Erro ao atualizar serviço.";
                error_log('Erro ao atualizar serviço: ' . print_r($service->getLastError(), true));
            }
        } else {
            // Criação
            if ($service->create($serviceData)) {
                $_SESSION['success'] = "Serviço criado com sucesso!";
            } else {
                $_SESSION['error'] = "Erro ao criar serviço.";
                error_log('Erro ao criar serviço: ' . print_r($service->getLastError(), true));
            }
        }
        
    } catch (Exception $e) {
        $_SESSION['error'] = "Erro ao processar serviço: " . $e->getMessage();
        error_log('Exceção ao processar serviço: ' . $e->getMessage());
    }
    
    header("Location: services.php");
    exit;
}

// Processar exclusão
if (isset($_POST['delete_service'])) {
    $id = $_POST['service_id'];
    if ($service->delete($id)) {
        $_SESSION['success'] = "Serviço excluído com sucesso!";
    } else {
        $_SESSION['error'] = "Erro ao excluir serviço.";
    }
    header("Location: services.php");
    exit;
}

// Buscar todos os serviços do usuário
$services = $service->getUserServices($_SESSION['user_id']);
?>

<div class="dashboard-header">
    <h2>Gerenciar Serviços</h2>
    <button class="btn btn-primary" onclick="openServiceModal()">Novo Serviço</button>
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

<div class="services-grid">
    <?php if (!empty($services)): ?>
        <?php foreach ($services as $srv): ?>
            <div class="service-card">
                <div class="service-header">
                    <h3><?php echo htmlspecialchars($srv['name']); ?></h3>
                    <div class="service-actions">
                        <button class="btn btn-small btn-edit" 
                                onclick="editService(<?php echo htmlspecialchars(json_encode($srv)); ?>)">
                            Editar
                        </button>
                        <form method="POST" style="display: inline;" 
                              onsubmit="return confirm('Tem certeza que deseja excluir este serviço?');">
                            <input type="hidden" name="service_id" value="<?php echo $srv['id']; ?>">
                            <button type="submit" name="delete_service" class="btn btn-small btn-danger">
                                Excluir
                            </button>
                        </form>
                    </div>
                </div>
                <p class="service-description"><?php echo htmlspecialchars($srv['description']); ?></p>
                <div class="service-details">
                    <span>Duração: <?php echo $srv['duration']; ?> minutos</span>
                    <span>Preço: R$ <?php echo number_format($srv['price'], 2, ',', '.'); ?></span>
                </div>
                <div class="service-link">
                    <small>Link para seus clientes acessarem todos os seus serviços:</small>
                    <input type="text" readonly 
                        value="<?php echo "http://" . $_SERVER['HTTP_HOST'] . "/agendamento/views/public/services.php?user=" . $_SESSION['user_id']; ?>"
                        onclick="this.select();" class="booking-link">
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="no-data">Nenhum serviço cadastrado.</p>
    <?php endif; ?>
</div>

<!-- Modal de Serviço -->
<div id="serviceModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h3 id="modalTitle">Novo Serviço</h3>
        <form method="POST" action="">
            <input type="hidden" name="service_id" id="serviceId">
            <div class="form-group">
                <label for="name">Nome do Serviço</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="description">Descrição</label>
                <textarea id="description" name="description" rows="3" required></textarea>
            </div>
            <div class="form-group">
                <label for="duration">Duração (minutos)</label>
                <input type="number" id="duration" name="duration" min="15" step="15" required>
            </div>
            <div class="form-group">
                <label for="price">Preço (R$)</label>
                <input type="text" id="price" name="price" required 
                       pattern="^\d*[0-9](|,\d{0,2}|.\d{0,2}|\d*[0-9])(|,\d{0,2}|.\d{0,2})$">
            </div>
            <div class="form-group">
                <label for="concurrent_capacity">Atendimentos Simultâneos</label>
                <input type="number" id="concurrent_capacity" name="concurrent_capacity" 
                    min="1" value="1" required>
                <small>Quantos clientes podem ser atendidos ao mesmo tempo neste serviço</small>
            </div>
            <button type="submit" name="save_service" class="btn btn-primary">Salvar</button>
        </form>
    </div>
</div>

<script>
const modal = document.getElementById("serviceModal");
const span = document.getElementsByClassName("close")[0];
const form = modal.querySelector("form");

function openServiceModal() {
    document.getElementById("modalTitle").textContent = "Novo Serviço";
    form.reset();
    form.service_id.value = "";
    modal.style.display = "block";
}

function editService(service) {
    document.getElementById("modalTitle").textContent = "Editar Serviço";
    document.getElementById("serviceId").value = service.id;
    document.getElementById("name").value = service.name;
    document.getElementById("description").value = service.description;
    document.getElementById("duration").value = service.duration;
    document.getElementById("price").value = service.price.toString().replace('.', ',');
    modal.style.display = "block";
}

span.onclick = function() {
    modal.style.display = "none";
}

window.onclick = function(event) {
    if (event.target == modal) {
        modal.style.display = "none";
    }
}

// Formatar input de preço
document.getElementById('price').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    value = (parseFloat(value) / 100).toFixed(2);
    value = value.replace('.', ',');
    e.target.value = value;
});
</script>

<?php include 'includes/footer.php'; ?>