<?php
require_once '../../config/Database.php';
require_once '../../models/User.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    $user = new User($db);
    
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    $errors = [];
    
    // Validações
    if (empty($name)) {
        $errors[] = "Nome é obrigatório";
    }
    
    if (empty($email)) {
        $errors[] = "Email é obrigatório";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email inválido";
    }
    
    if (empty($password)) {
        $errors[] = "Senha é obrigatória";
    } elseif (strlen($password) < 6) {
        $errors[] = "Senha deve ter no mínimo 6 caracteres";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Senhas não conferem";
    }
    
    if (empty($errors)) {
        if ($user->create($name, $email, $password)) {
            $_SESSION['success'] = "Cadastro realizado com sucesso!";
            header("Location: login.php");
            exit;
        } else {
            $errors[] = "Erro ao cadastrar usuário";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Sistema de Agendamento</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="auth-form">
            <h2>Criar Conta</h2>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo $error; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="name">Nome</label>
                    <input type="text" id="name" name="name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="password">Senha</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirmar Senha</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>

                <button type="submit" class="btn btn-primary">Cadastrar</button>
            </form>

            <p class="text-center">
                Já tem uma conta? <a href="login.php">Faça login</a>
            </p>
        </div>
    </div>
</body>
</html>