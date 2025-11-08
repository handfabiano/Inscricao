-- =====================================================
-- MIGRATION: Analytics & Business Intelligence System
-- Versão: 1.0
-- Data: 2025-11-08
-- Descrição: Sistema de análises preditivas e BI
-- =====================================================

-- =====================================================
-- PREVISÕES E MACHINE LEARNING
-- =====================================================

-- Modelos de previsão
CREATE TABLE IF NOT EXISTS prediction_models (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    tipo ENUM('match_outcome', 'player_performance', 'team_rating', 'goal_prediction') NOT NULL,
    algoritmo VARCHAR(50) COMMENT 'logistic_regression, random_forest, neural_network',

    -- Configuração do modelo
    features JSON COMMENT 'Features utilizadas no modelo',
    hiperparametros JSON COMMENT 'Configurações do algoritmo',

    -- Performance do modelo
    acuracia DECIMAL(5,2) COMMENT 'Acurácia em %',
    precisao DECIMAL(5,2) COMMENT 'Precision',
    recall_rate DECIMAL(5,2) COMMENT 'Recall',
    f1_score DECIMAL(5,2) COMMENT 'F1 Score',

    -- Status
    versao VARCHAR(20) DEFAULT '1.0',
    ativo BOOLEAN DEFAULT TRUE,
    treinado_em TIMESTAMP NULL COMMENT 'Última vez que foi treinado',
    total_predicoes INT DEFAULT 0,
    predicoes_corretas INT DEFAULT 0,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_tipo (tipo),
    INDEX idx_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Modelos de Machine Learning para previsões';

-- Previsões de partidas
CREATE TABLE IF NOT EXISTS match_predictions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    match_id INT NOT NULL,
    model_id INT NOT NULL,

    -- Previsões
    vitoria_casa_prob DECIMAL(5,2) COMMENT 'Probabilidade de vitória da casa (%)',
    empate_prob DECIMAL(5,2) COMMENT 'Probabilidade de empate (%)',
    vitoria_visitante_prob DECIMAL(5,2) COMMENT 'Probabilidade de vitória visitante (%)',

    -- Previsão de gols
    gols_esperados_casa DECIMAL(3,2),
    gols_esperados_visitante DECIMAL(3,2),

    -- Análise de confiança
    confianca DECIMAL(5,2) COMMENT 'Confiança da previsão (0-100)',

    -- Fatores considerados
    fatores_analise JSON COMMENT 'ELO, forma recente, confronto direto, etc',

    -- Resultado real (após partida)
    resultado_real VARCHAR(20) COMMENT 'casa, empate, visitante',
    previsao_correta BOOLEAN,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
    FOREIGN KEY (model_id) REFERENCES prediction_models(id),

    INDEX idx_match (match_id),
    INDEX idx_confianca (confianca DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Previsões de resultados de partidas';

-- =====================================================
-- IDENTIFICAÇÃO DE TALENTOS
-- =====================================================

-- Scouts de talentos
CREATE TABLE IF NOT EXISTS talent_scouts (
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- Critérios de avaliação
    criterios JSON COMMENT 'Pesos para cada métrica',

    -- Configuração
    min_jogos INT DEFAULT 5 COMMENT 'Mínimo de jogos para avaliar',
    max_idade INT DEFAULT 23 COMMENT 'Idade máxima para considerar talento',

    -- Limiares
    score_minimo DECIMAL(5,2) DEFAULT 70.00 COMMENT 'Score mínimo para ser talento',

    ultima_execucao TIMESTAMP NULL,
    total_talentos_identificados INT DEFAULT 0,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Configuração de identificação de talentos';

-- Talentos identificados
CREATE TABLE IF NOT EXISTS identified_talents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    atleta_id INT NOT NULL,
    scout_id INT NOT NULL,
    modalidade_id INT NOT NULL,

    -- Score do talento
    talent_score DECIMAL(5,2) NOT NULL COMMENT '0-100',

    -- Categorização
    potencial ENUM('promissor', 'destaque', 'elite') NOT NULL,
    posicao_sugerida VARCHAR(50),

    -- Métricas que contribuíram
    metricas_detalhadas JSON COMMENT 'Breakdown do score',

    -- Análise qualitativa
    pontos_fortes TEXT,
    pontos_desenvolver TEXT,
    comparacao_similar VARCHAR(200) COMMENT 'Jogador profissional similar',

    -- Tracking
    visualizado BOOLEAN DEFAULT FALSE,
    favoritado BOOLEAN DEFAULT FALSE,
    notas_observacao TEXT,

    -- Status
    status ENUM('ativo', 'contratado', 'arquivado') DEFAULT 'ativo',

    identificado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (atleta_id) REFERENCES atletas(id) ON DELETE CASCADE,
    FOREIGN KEY (scout_id) REFERENCES talent_scouts(id),
    FOREIGN KEY (modalidade_id) REFERENCES modalidades(id),

    UNIQUE KEY unique_talent (atleta_id, scout_id),
    INDEX idx_score (talent_score DESC),
    INDEX idx_potencial (potencial),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Talentos identificados pelo sistema';

-- =====================================================
-- ANÁLISE DE PADRÕES E PERFORMANCE
-- =====================================================

-- Padrões de jogo de equipes
CREATE TABLE IF NOT EXISTS team_patterns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipe_id INT NOT NULL,
    modalidade_id INT NOT NULL,

    -- Estilo de jogo
    estilo_predominante ENUM('ofensivo', 'defensivo', 'balanceado', 'posse', 'contra-ataque') NOT NULL,

    -- Métricas calculadas
    posse_media DECIMAL(5,2) COMMENT 'Posse de bola média (%)',
    passes_por_jogo DECIMAL(6,2),
    finalizacoes_por_jogo DECIMAL(5,2),
    precisao_passes DECIMAL(5,2),

    -- Padrões táticos
    formacao_preferida VARCHAR(20) COMMENT '4-4-2, 4-3-3, etc',
    altura_linha_defesa DECIMAL(5,2) COMMENT 'Posição média da defesa',
    largura_jogo DECIMAL(5,2) COMMENT 'Utilização da largura do campo',

    -- Performance em contextos
    aproveitamento_casa DECIMAL(5,2),
    aproveitamento_fora DECIMAL(5,2),
    performance_jogos_decisivos DECIMAL(5,2),

    -- Análise temporal
    melhor_periodo ENUM('primeiro_tempo', 'segundo_tempo', 'ambos'),
    gols_primeiro_tempo DECIMAL(3,2),
    gols_segundo_tempo DECIMAL(3,2),

    -- Dados para análise
    periodo_analise_inicio DATE,
    periodo_analise_fim DATE,
    jogos_analisados INT,

    ultima_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (equipe_id) REFERENCES equipes(id) ON DELETE CASCADE,
    FOREIGN KEY (modalidade_id) REFERENCES modalidades(id),

    UNIQUE KEY unique_team_pattern (equipe_id, modalidade_id),
    INDEX idx_estilo (estilo_predominante)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Padrões táticos e de performance das equipes';

-- Tendências e insights
CREATE TABLE IF NOT EXISTS performance_insights (
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- Escopo do insight
    tipo ENUM('team', 'player', 'competition', 'general') NOT NULL,
    entidade_id INT COMMENT 'ID da equipe/jogador/competição',

    -- Insight
    categoria ENUM('performance', 'tendencia', 'anomalia', 'oportunidade', 'risco') NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    descricao TEXT NOT NULL,

    -- Dados de suporte
    metricas_relacionadas JSON,
    confianca DECIMAL(5,2) COMMENT 'Confiança do insight (0-100)',

    -- Severidade/Importância
    prioridade ENUM('baixa', 'media', 'alta', 'critica') DEFAULT 'media',

    -- Ações sugeridas
    acoes_sugeridas JSON COMMENT 'Lista de ações recomendadas',

    -- Tracking
    visualizado BOOLEAN DEFAULT FALSE,
    acao_tomada BOOLEAN DEFAULT FALSE,
    feedback_usuario ENUM('util', 'neutro', 'nao_util') NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL COMMENT 'Insights podem expirar',

    INDEX idx_tipo (tipo, entidade_id),
    INDEX idx_categoria (categoria),
    INDEX idx_prioridade (prioridade),
    INDEX idx_created (created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Insights automáticos gerados pelo sistema';

-- =====================================================
-- RELATÓRIOS E DASHBOARDS
-- =====================================================

-- Templates de relatórios
CREATE TABLE IF NOT EXISTS report_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,

    -- Tipo de relatório
    tipo ENUM('performance_team', 'performance_athlete', 'competition_summary',
              'financial', 'scouting', 'comparative', 'custom') NOT NULL,

    -- Estrutura do relatório
    secoes JSON COMMENT 'Seções e widgets do relatório',
    metricas JSON COMMENT 'Métricas a serem incluídas',
    filtros_padrao JSON,

    -- Visualizações
    graficos JSON COMMENT 'Configuração de gráficos',
    tabelas JSON COMMENT 'Configuração de tabelas',

    -- Agendamento
    permite_agendamento BOOLEAN DEFAULT TRUE,
    formato_exportacao JSON COMMENT 'PDF, Excel, CSV',

    -- Permissões
    visibilidade ENUM('publico', 'privado', 'organizacao') DEFAULT 'privado',
    criado_por INT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_tipo (tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Templates de relatórios personalizáveis';

-- Relatórios gerados
CREATE TABLE IF NOT EXISTS generated_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_id INT NOT NULL,

    -- Parâmetros de geração
    titulo VARCHAR(200) NOT NULL,
    parametros JSON COMMENT 'Filtros aplicados',
    periodo_inicio DATE,
    periodo_fim DATE,

    -- Dados do relatório
    dados JSON COMMENT 'Dados processados do relatório',
    graficos_base64 JSON COMMENT 'Gráficos em base64 para cache',

    -- Arquivos gerados
    pdf_path VARCHAR(255),
    excel_path VARCHAR(255),

    -- Metadata
    gerado_por INT,
    organizacao_id INT,
    tempo_geracao INT COMMENT 'Tempo em segundos',

    -- Status
    status ENUM('processando', 'concluido', 'erro') DEFAULT 'processando',
    erro_mensagem TEXT,

    -- Compartilhamento
    compartilhado BOOLEAN DEFAULT FALSE,
    link_compartilhamento VARCHAR(100) UNIQUE,
    expira_em TIMESTAMP NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (template_id) REFERENCES report_templates(id),

    INDEX idx_template (template_id),
    INDEX idx_created (created_at DESC),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Relatórios gerados pelo sistema';

-- =====================================================
-- MÉTRICAS AVANÇADAS
-- =====================================================

-- KPIs customizados
CREATE TABLE IF NOT EXISTS custom_kpis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organizacao_id INT NOT NULL,

    nome VARCHAR(100) NOT NULL,
    descricao TEXT,

    -- Definição do KPI
    formula TEXT NOT NULL COMMENT 'Fórmula SQL ou expressão para cálculo',
    tipo_resultado ENUM('numero', 'percentual', 'tempo', 'moeda') DEFAULT 'numero',

    -- Metas
    meta_valor DECIMAL(15,2),
    meta_tipo ENUM('minimo', 'maximo', 'exato') DEFAULT 'minimo',

    -- Apresentação
    formato_exibicao VARCHAR(50) COMMENT 'Formato de exibição do valor',
    cor_indicador VARCHAR(20),
    icone VARCHAR(50),

    -- Atualização
    frequencia_atualizacao ENUM('tempo_real', 'horaria', 'diaria', 'semanal', 'mensal') DEFAULT 'diaria',
    ultimo_valor DECIMAL(15,2),
    ultima_atualizacao TIMESTAMP NULL,

    -- Status
    ativo BOOLEAN DEFAULT TRUE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_organizacao (organizacao_id),
    INDEX idx_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='KPIs personalizados por organização';

-- Histórico de KPIs
CREATE TABLE IF NOT EXISTS kpi_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kpi_id INT NOT NULL,

    valor DECIMAL(15,2) NOT NULL,
    meta_atingida BOOLEAN,
    variacao_anterior DECIMAL(10,2) COMMENT 'Variação % em relação ao período anterior',

    periodo_referencia DATE NOT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (kpi_id) REFERENCES custom_kpis(id) ON DELETE CASCADE,

    INDEX idx_kpi_periodo (kpi_id, periodo_referencia DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Histórico de valores dos KPIs';

-- =====================================================
-- VIEWS ÚTEIS
-- =====================================================

-- Top talentos identificados
CREATE OR REPLACE VIEW top_talents AS
SELECT
    it.id,
    it.atleta_id,
    a.nome_completo,
    a.data_nascimento,
    TIMESTAMPDIFF(YEAR, a.data_nascimento, CURDATE()) as idade,
    a.foto_path,
    e.nome as equipe_atual,
    it.talent_score,
    it.potencial,
    it.posicao_sugerida,
    it.pontos_fortes,
    it.identificado_em,
    m.nome as modalidade
FROM identified_talents it
INNER JOIN atletas a ON it.atleta_id = a.id
LEFT JOIN equipes e ON a.equipe_atual_id = e.id
INNER JOIN modalidades m ON it.modalidade_id = m.id
WHERE it.status = 'ativo'
ORDER BY it.talent_score DESC;

-- Insights não visualizados
CREATE OR REPLACE VIEW pending_insights AS
SELECT
    pi.*,
    CASE
        WHEN pi.tipo = 'team' THEN e.nome
        WHEN pi.tipo = 'player' THEN a.nome_completo
        ELSE 'Geral'
    END as entidade_nome
FROM performance_insights pi
LEFT JOIN equipes e ON pi.tipo = 'team' AND pi.entidade_id = e.id
LEFT JOIN atletas a ON pi.tipo = 'player' AND pi.entidade_id = a.id
WHERE pi.visualizado = FALSE
  AND (pi.expires_at IS NULL OR pi.expires_at > NOW())
ORDER BY
    FIELD(pi.prioridade, 'critica', 'alta', 'media', 'baixa'),
    pi.created_at DESC;

-- Performance de modelos de previsão
CREATE OR REPLACE VIEW model_performance AS
SELECT
    pm.id,
    pm.nome,
    pm.tipo,
    pm.algoritmo,
    pm.acuracia,
    pm.total_predicoes,
    pm.predicoes_corretas,
    ROUND((pm.predicoes_corretas / NULLIF(pm.total_predicoes, 0)) * 100, 2) as taxa_acerto,
    pm.ativo,
    pm.treinado_em,
    COUNT(mp.id) as predicoes_pendentes
FROM prediction_models pm
LEFT JOIN match_predictions mp ON pm.id = mp.model_id AND mp.resultado_real IS NULL
GROUP BY pm.id
ORDER BY pm.acuracia DESC;

-- =====================================================
-- TRIGGERS
-- =====================================================

-- Atualizar performance do modelo após resultado de partida
DELIMITER //
CREATE TRIGGER after_match_prediction_result
AFTER UPDATE ON match_predictions
FOR EACH ROW
BEGIN
    IF NEW.resultado_real IS NOT NULL AND OLD.resultado_real IS NULL THEN
        UPDATE prediction_models
        SET
            total_predicoes = total_predicoes + 1,
            predicoes_corretas = predicoes_corretas + IF(NEW.previsao_correta = TRUE, 1, 0),
            acuracia = ROUND(((predicoes_corretas + IF(NEW.previsao_correta = TRUE, 1, 0)) / (total_predicoes + 1)) * 100, 2)
        WHERE id = NEW.model_id;
    END IF;
END//
DELIMITER ;

-- =====================================================
-- DADOS INICIAIS
-- =====================================================

-- Modelo de previsão padrão
INSERT INTO prediction_models (nome, tipo, algoritmo, features, versao, ativo) VALUES
('Modelo Base - Resultado de Partida', 'match_outcome', 'logistic_regression',
 JSON_OBJECT(
     'elo_rating', 0.35,
     'forma_recente', 0.25,
     'confronto_direto', 0.15,
     'mando_campo', 0.15,
     'descanso_dias', 0.10
 ), '1.0', TRUE);

-- Scout padrão de talentos
INSERT INTO talent_scouts (criterios, min_jogos, max_idade, score_minimo) VALUES
(JSON_OBJECT(
    'nota_media', 15,
    'gols_por_jogo', 20,
    'assistencias_por_jogo', 15,
    'consistencia', 15,
    'evolucao', 20,
    'disciplina', 10,
    'versatilidade', 5
), 5, 23, 70.00);

-- Templates de relatórios padrão
INSERT INTO report_templates (nome, descricao, tipo, secoes, formato_exportacao, visibilidade) VALUES
('Relatório de Performance de Equipe', 'Análise completa de performance de uma equipe em período específico',
 'performance_team',
 JSON_ARRAY('resumo_executivo', 'estatisticas_gerais', 'analise_tacatica', 'jogadores_destaque', 'evolucao_temporal'),
 JSON_ARRAY('pdf', 'excel'),
 'organizacao'),

('Relatório de Competição', 'Sumário completo de uma competição',
 'competition_summary',
 JSON_ARRAY('informacoes_gerais', 'classificacao', 'artilharia', 'estatisticas', 'destaques'),
 JSON_ARRAY('pdf', 'excel'),
 'publico'),

('Relatório de Scouting', 'Análise de talentos identificados',
 'scouting',
 JSON_ARRAY('top_talentos', 'analise_detalhada', 'comparativos', 'recomendacoes'),
 JSON_ARRAY('pdf'),
 'privado');

-- =====================================================
-- ÍNDICES ADICIONAIS PARA PERFORMANCE
-- =====================================================

-- Índices para queries de analytics
CREATE INDEX idx_talent_score_status ON identified_talents(talent_score DESC, status);
CREATE INDEX idx_insights_priority_viewed ON performance_insights(prioridade, visualizado, created_at DESC);
CREATE INDEX idx_reports_org_created ON generated_reports(organizacao_id, created_at DESC);

-- =====================================================
-- IMPORTANTE:
-- - Execute as funções PHP em analytics_helper.php
-- - Configure agendamentos para análises periódicas
-- - Ajuste os pesos dos modelos conforme necessário
-- =====================================================
