<?php
/**
 * Script de Diagnóstico de Uploads
 * Verifica se as pastas e URLs de upload estão configuradas corretamente
 */

require_once '../config/config.php';
requireAdminLogin();

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico de Uploads</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4"><i class="fas fa-tools"></i> Diagnóstico de Sistema de Uploads</h1>

        <!-- Constantes Definidas -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-cog"></i> Constantes Configuradas</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <th>BASE_URL</th>
                        <td><code><?php echo BASE_URL; ?></code></td>
                    </tr>
                    <tr>
                        <th>UPLOAD_URL</th>
                        <td><code><?php echo UPLOAD_URL; ?></code></td>
                    </tr>
                    <tr>
                        <th>FOTO_URL</th>
                        <td><code><?php echo FOTO_URL; ?></code></td>
                    </tr>
                    <tr>
                        <th>BANNER_URL</th>
                        <td><code><?php echo BANNER_URL; ?></code></td>
                    </tr>
                    <tr>
                        <th>UPLOAD_PATH</th>
                        <td><code><?php echo UPLOAD_PATH; ?></code></td>
                    </tr>
                    <tr>
                        <th>FOTO_PATH</th>
                        <td><code><?php echo FOTO_PATH; ?></code></td>
                    </tr>
                    <tr>
                        <th>BANNER_PATH</th>
                        <td><code><?php echo BANNER_PATH; ?></code></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Verificação de Pastas -->
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-folder"></i> Verificação de Pastas</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Pasta</th>
                            <th>Existe?</th>
                            <th>Gravável?</th>
                            <th>Permissões</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $pastas = [
                            'UPLOAD_PATH' => UPLOAD_PATH,
                            'FOTO_PATH' => FOTO_PATH,
                            'BANNER_PATH' => BANNER_PATH,
                            'DOCUMENTO_PATH' => DOCUMENTO_PATH
                        ];

                        foreach ($pastas as $nome => $caminho) {
                            $existe = is_dir($caminho);
                            $gravavel = $existe && is_writable($caminho);
                            $permissoes = $existe ? substr(sprintf('%o', fileperms($caminho)), -4) : 'N/A';

                            $classeExiste = $existe ? 'success' : 'danger';
                            $classeGravavel = $gravavel ? 'success' : 'warning';
                            ?>
                            <tr>
                                <td><code><?php echo $nome; ?></code></td>
                                <td>
                                    <span class="badge bg-<?php echo $classeExiste; ?>">
                                        <?php echo $existe ? 'SIM' : 'NÃO'; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $classeGravavel; ?>">
                                        <?php echo $gravavel ? 'SIM' : 'NÃO'; ?>
                                    </span>
                                </td>
                                <td><code><?php echo $permissoes; ?></code></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Arquivos Existentes -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-images"></i> Arquivos Existentes</h5>
            </div>
            <div class="card-body">
                <h6>Fotos (<?php echo FOTO_PATH; ?>)</h6>
                <?php
                $fotos = glob(FOTO_PATH . '*');
                if (empty($fotos)) {
                    echo '<p class="text-muted">Nenhuma foto encontrada</p>';
                } else {
                    echo '<ul class="list-group list-group-flush mb-3">';
                    foreach (array_slice($fotos, 0, 10) as $foto) {
                        $nome = basename($foto);
                        $tamanho = filesize($foto);
                        $tamanhoKB = round($tamanho / 1024, 2);
                        echo "<li class='list-group-item d-flex justify-content-between align-items-center'>";
                        echo "<code>$nome</code>";
                        echo "<span class='badge bg-secondary'>$tamanhoKB KB</span>";
                        echo "</li>";
                    }
                    echo '</ul>';
                    if (count($fotos) > 10) {
                        echo '<p class="text-muted">...e mais ' . (count($fotos) - 10) . ' arquivo(s)</p>';
                    }
                }
                ?>

                <h6 class="mt-4">Banners (<?php echo BANNER_PATH; ?>)</h6>
                <?php
                $banners = glob(BANNER_PATH . '*');
                if (empty($banners)) {
                    echo '<p class="text-muted">Nenhum banner encontrado</p>';
                } else {
                    echo '<ul class="list-group list-group-flush">';
                    foreach (array_slice($banners, 0, 10) as $banner) {
                        $nome = basename($banner);
                        $tamanho = filesize($banner);
                        $tamanhoKB = round($tamanho / 1024, 2);
                        echo "<li class='list-group-item d-flex justify-content-between align-items-center'>";
                        echo "<code>$nome</code>";
                        echo "<span class='badge bg-secondary'>$tamanhoKB KB</span>";
                        echo "</li>";
                    }
                    echo '</ul>';
                    if (count($banners) > 10) {
                        echo '<p class="text-muted">...e mais ' . (count($banners) - 10) . ' arquivo(s)</p>';
                    }
                }
                ?>
            </div>
        </div>

        <!-- Teste de URL -->
        <div class="card mb-4">
            <div class="card-header bg-warning">
                <h5 class="mb-0"><i class="fas fa-link"></i> Teste de Acesso às URLs</h5>
            </div>
            <div class="card-body">
                <p>Exemplos de URLs que serão geradas:</p>
                <ul>
                    <li>Foto de exemplo: <code><?php echo FOTO_URL; ?>exemplo.jpg</code></li>
                    <li>Banner de exemplo: <code><?php echo BANNER_URL; ?>exemplo.jpg</code></li>
                </ul>

                <p class="text-muted mt-3">
                    <strong>Nota:</strong> As URLs acima são exemplos. Substitua "exemplo.jpg" pelo nome real de um arquivo para testar.
                </p>

                <?php
                // Tentar encontrar uma foto real para testar
                $fotosReais = glob(FOTO_PATH . '*.{jpg,jpeg,png}', GLOB_BRACE);
                if (!empty($fotosReais)) {
                    $fotoTeste = basename($fotosReais[0]);
                    $urlFotoTeste = FOTO_URL . $fotoTeste;
                    ?>
                    <div class="alert alert-info mt-3">
                        <h6>Teste com Foto Real:</h6>
                        <p>URL: <code><?php echo $urlFotoTeste; ?></code></p>
                        <p>Tentando carregar imagem:</p>
                        <img src="<?php echo $urlFotoTeste; ?>"
                             alt="Teste de foto"
                             style="max-width: 200px; border: 2px solid #ccc; padding: 5px;"
                             onerror="this.parentElement.innerHTML+='<p class=text-danger><strong>ERRO:</strong> Não foi possível carregar a imagem. Verifique as configurações do servidor.</p>'">
                    </div>
                <?php } ?>
            </div>
        </div>

        <!-- Recomendações -->
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="fas fa-lightbulb"></i> Recomendações</h5>
            </div>
            <div class="card-body">
                <ol>
                    <li>Certifique-se de que as pastas têm permissão <code>755</code> ou <code>775</code></li>
                    <li>Verifique se o arquivo <code>.htaccess</code> na pasta <code>public/</code> não está bloqueando o acesso</li>
                    <li>Se estiver na Hostinger, verifique o caminho correto do <code>public_html/</code></li>
                    <li>Teste fazer upload de um atleta ou competição com foto/banner</li>
                    <li>Use as ferramentas do navegador (F12) para verificar erros 404 nas imagens</li>
                </ol>
            </div>
        </div>

        <div class="text-center mb-5">
            <a href="index.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Voltar ao Dashboard
            </a>
            <a href="atletas.php" class="btn btn-secondary">
                <i class="fas fa-users"></i> Ver Atletas
            </a>
            <a href="competicoes.php" class="btn btn-secondary">
                <i class="fas fa-trophy"></i> Ver Competições
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
