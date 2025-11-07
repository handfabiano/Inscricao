<?php
$equipeNome = $_GET['equipe'] ?? 'sua equipe';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro Realizado com Sucesso!</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .success-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            padding: 50px;
            text-align: center;
            max-width: 600px;
        }
        .check-icon {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: #198754;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            animation: scaleIn 0.5s ease-out;
        }
        @keyframes scaleIn {
            from {
                transform: scale(0);
            }
            to {
                transform: scale(1);
            }
        }
        .confetti {
            position: fixed;
            width: 10px;
            height: 10px;
            background: #f0f;
            position: absolute;
            animation: confetti-fall 3s linear infinite;
        }
        @keyframes confetti-fall {
            to {
                transform: translateY(100vh) rotate(360deg);
            }
        }
    </style>
</head>
<body>
    <div class="success-card">
        <div class="check-icon">
            <i class="fas fa-check fa-3x text-white"></i>
        </div>

        <h1 class="mb-3 text-success">Cadastro Realizado!</h1>
        <h4 class="mb-4">Bem-vindo(a) à <?php echo htmlspecialchars($equipeNome); ?>!</h4>

        <p class="lead mb-4">
            Seu cadastro foi concluído com sucesso e você já está vinculado(a) à equipe.
        </p>

        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            <strong>Próximos Passos:</strong>
            <ul class="text-start mt-2 mb-0">
                <li>O responsável pela equipe receberá uma notificação</li>
                <li>Seus dados estão salvos no sistema</li>
                <li>Você poderá participar das competições da equipe</li>
                <li>Mantenha contato com seu responsável para mais informações</li>
            </ul>
        </div>

        <div class="mt-4">
            <a href="index.php" class="btn btn-primary btn-lg">
                <i class="fas fa-home"></i> Ir para Página Inicial
            </a>
        </div>

        <p class="text-muted mt-4 mb-0">
            <small>
                <i class="fas fa-envelope"></i>
                Em caso de dúvidas, entre em contato com o responsável pela sua equipe
            </small>
        </p>
    </div>

    <script>
        // Confetes de celebração
        function createConfetti() {
            const colors = ['#ff0000', '#00ff00', '#0000ff', '#ffff00', '#ff00ff', '#00ffff'];
            for (let i = 0; i < 50; i++) {
                setTimeout(() => {
                    const confetti = document.createElement('div');
                    confetti.className = 'confetti';
                    confetti.style.left = Math.random() * 100 + '%';
                    confetti.style.background = colors[Math.floor(Math.random() * colors.length)];
                    confetti.style.animationDelay = Math.random() * 3 + 's';
                    document.body.appendChild(confetti);

                    setTimeout(() => confetti.remove(), 3000);
                }, i * 50);
            }
        }

        createConfetti();
    </script>
</body>
</html>
