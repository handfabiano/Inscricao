<?php
require_once '../config/config.php';
requireAdminLogin();

$pdo = getDBConnection();
$id = $_GET['id'] ?? null;

if (!$id) {
    redirect('competicoes.php', 'ID inválido', 'error');
}

try {
    // Verificar se há inscrições
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM inscricoes_competicoes WHERE competicao_id = ?");
    $stmt->execute([$id]);
    $countInscricoes = $stmt->fetchColumn();
    
    if ($countInscricoes > 0) {
        redirect('competicoes.php', 'Não é possível excluir: existem ' . $countInscricoes . ' inscrição(ões) nesta competição', 'error');
    }
    
    // Buscar banner para excluir
    $stmt = $pdo->prepare("SELECT banner_path FROM competicoes WHERE id = ?");
    $stmt->execute([$id]);
    $comp = $stmt->fetch();
    
    if ($comp && !empty($comp['banner_path'])) {
        $bannerFile = BANNER_PATH . $comp['banner_path'];
        if (file_exists($bannerFile)) {
            unlink($bannerFile);
        }
    }
    
    // Excluir competição
    $stmt = $pdo->prepare("DELETE FROM competicoes WHERE id = ?");
    $stmt->execute([$id]);
    
    redirect('competicoes.php', 'Competição excluída com sucesso!', 'success');
    
} catch (Exception $e) {
    redirect('competicoes.php', 'Erro ao excluir: ' . $e->getMessage(), 'error');
}
?>
