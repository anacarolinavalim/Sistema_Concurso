-- Sistema de Inscrição Educacional – Carmo de Minas
-- Importe este arquivo no phpMyAdmin (banco oficinahotrods_equipeX).
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS candidato (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  cpf CHAR(11) NOT NULL,
  nome VARCHAR(150) NOT NULL,
  data_nascimento DATE NOT NULL,
  telefone VARCHAR(15) NOT NULL,
  email VARCHAR(150) NOT NULL,
  endereco VARCHAR(255) NOT NULL,
  rg VARCHAR(20) NOT NULL,
  titulo_eleitor VARCHAR(13) NOT NULL,
  senha_hash VARCHAR(255) NOT NULL,
  data_criacao DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_candidato_cpf (cpf),
  UNIQUE KEY uk_candidato_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS administrador (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nome VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL,
  senha_hash VARCHAR(255) NOT NULL,
  data_criacao DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_admin_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cargo (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  titulo_cargo VARCHAR(120) NOT NULL,
  escolaridade_minima VARCHAR(20) NOT NULL,
  titulacoes_permitidas VARCHAR(120) NOT NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS inscricao (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  candidato_id INT UNSIGNED NOT NULL,
  cargo_id INT UNSIGNED NOT NULL,
  tempo_servico_dias INT UNSIGNED NOT NULL DEFAULT 0,
  nivel_titulacao VARCHAR(20) NOT NULL,
  data_inscricao DATETIME NOT NULL,
  ultima_alteracao DATETIME NOT NULL,
  status ENUM('Ativa','Cancelada','Desclassificada') NOT NULL DEFAULT 'Ativa',
  versao_atual INT UNSIGNED NOT NULL DEFAULT 1,
  codigo_validacao VARCHAR(14) NOT NULL,
  PRIMARY KEY (id),
  KEY idx_insc_candidato (candidato_id, status),
  KEY idx_insc_cargo (cargo_id, status),
  CONSTRAINT fk_insc_candidato FOREIGN KEY (candidato_id) REFERENCES candidato (id),
  CONSTRAINT fk_insc_cargo FOREIGN KEY (cargo_id) REFERENCES cargo (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS inscricao_versao (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  inscricao_id INT UNSIGNED NOT NULL,
  versao INT UNSIGNED NOT NULL,
  codigo_validacao VARCHAR(14) NOT NULL,
  dados_json MEDIUMTEXT NOT NULL,
  data_registro DATETIME NOT NULL,
  ip VARCHAR(45) NOT NULL,
  usuario_tipo VARCHAR(20) NOT NULL,
  usuario_id INT UNSIGNED NULL,
  usuario_nome VARCHAR(150) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_versao_codigo (codigo_validacao),
  UNIQUE KEY uk_versao_insc (inscricao_id, versao),
  CONSTRAINT fk_versao_insc FOREIGN KEY (inscricao_id) REFERENCES inscricao (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS documento_inscricao (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  inscricao_id INT UNSIGNED NOT NULL,
  tipo_documento VARCHAR(30) NOT NULL,
  nome_original VARCHAR(255) NOT NULL,
  caminho_arquivo VARCHAR(255) NOT NULL,
  data_upload DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_doc_tipo (inscricao_id, tipo_documento),
  CONSTRAINT fk_doc_insc FOREIGN KEY (inscricao_id) REFERENCES inscricao (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS recurso (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  inscricao_id INT UNSIGNED NOT NULL,
  numero_protocolo VARCHAR(30) NOT NULL,
  data_protocolo DATETIME NOT NULL,
  descricao TEXT NOT NULL,
  parecer_deferimento ENUM('Pendente','Deferido','Indeferido') NOT NULL DEFAULT 'Pendente',
  parecer_justificativa TEXT NULL,
  data_parecer DATETIME NULL,
  registrado_por INT UNSIGNED NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_recurso_insc (inscricao_id),
  UNIQUE KEY uk_recurso_protocolo (numero_protocolo),
  CONSTRAINT fk_recurso_insc FOREIGN KEY (inscricao_id) REFERENCES inscricao (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS leilao_vaga (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  cargo_id INT UNSIGNED NOT NULL,
  escolaridade VARCHAR(20) NOT NULL,
  horario VARCHAR(80) NOT NULL,
  vagas_ofertadas INT UNSIGNED NOT NULL,
  status ENUM('Agendado','Em andamento','Encerrado') NOT NULL DEFAULT 'Agendado',
  data_leilao DATE NOT NULL,
  ano_letivo SMALLINT UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  CONSTRAINT fk_leilao_cargo FOREIGN KEY (cargo_id) REFERENCES cargo (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contrato_leilao (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  leilao_id INT UNSIGNED NOT NULL,
  candidato_id INT UNSIGNED NOT NULL,
  inscricao_id INT UNSIGNED NOT NULL,
  data_contratacao DATETIME NOT NULL,
  status ENUM('Ativo','Desistido','Encerrado') NOT NULL DEFAULT 'Ativo',
  tipo_encerramento ENUM('Declarada','Abandono') NULL,
  data_desistencia DATE NULL,
  observacao VARCHAR(255) NULL,
  PRIMARY KEY (id),
  KEY idx_contrato_cand (candidato_id, status),
  KEY idx_contrato_leilao (leilao_id, status),
  CONSTRAINT fk_contrato_leilao FOREIGN KEY (leilao_id) REFERENCES leilao_vaga (id),
  CONSTRAINT fk_contrato_cand FOREIGN KEY (candidato_id) REFERENCES candidato (id),
  CONSTRAINT fk_contrato_insc FOREIGN KEY (inscricao_id) REFERENCES inscricao (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS punicao_candidato (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  candidato_id INT UNSIGNED NOT NULL,
  cargo_id INT UNSIGNED NOT NULL,
  contrato_id INT UNSIGNED NULL,
  data_inicio DATE NOT NULL,
  data_fim DATE NOT NULL,
  motivo VARCHAR(255) NOT NULL,
  PRIMARY KEY (id),
  KEY idx_punicao (candidato_id, cargo_id, data_fim),
  CONSTRAINT fk_punicao_cand FOREIGN KEY (candidato_id) REFERENCES candidato (id),
  CONSTRAINT fk_punicao_cargo FOREIGN KEY (cargo_id) REFERENCES cargo (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS conferencia_leilao (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  leilao_id INT UNSIGNED NOT NULL,
  inscricao_id INT UNSIGNED NOT NULL,
  itens_conferidos VARCHAR(255) NOT NULL DEFAULT '',
  status ENUM('Conferido','Desclassificado') NOT NULL,
  observacao VARCHAR(255) NULL,
  avaliador_id INT UNSIGNED NULL,
  data_conferencia DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_conf (leilao_id, inscricao_id),
  CONSTRAINT fk_conf_leilao FOREIGN KEY (leilao_id) REFERENCES leilao_vaga (id),
  CONSTRAINT fk_conf_insc FOREIGN KEY (inscricao_id) REFERENCES inscricao (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS classificacao (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  tipo ENUM('Preliminar','Definitiva') NOT NULL,
  data_geracao DATETIME NOT NULL,
  gerado_por INT UNSIGNED NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS classificacao_item (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  classificacao_id INT UNSIGNED NOT NULL,
  cargo_id INT UNSIGNED NOT NULL,
  posicao INT UNSIGNED NOT NULL,
  inscricao_id INT UNSIGNED NOT NULL,
  candidato_id INT UNSIGNED NOT NULL,
  nome VARCHAR(150) NOT NULL,
  cpf CHAR(11) NOT NULL,
  data_nascimento DATE NOT NULL,
  tempo_servico_dias INT UNSIGNED NOT NULL,
  nivel_titulacao VARCHAR(20) NOT NULL,
  habilitado TINYINT(1) NOT NULL,
  PRIMARY KEY (id),
  KEY idx_item_class (classificacao_id, cargo_id, posicao),
  CONSTRAINT fk_item_class FOREIGN KEY (classificacao_id) REFERENCES classificacao (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS log_auditoria (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  data_hora DATETIME NOT NULL,
  usuario_tipo VARCHAR(20) NOT NULL,
  usuario_id INT UNSIGNED NULL,
  usuario_nome VARCHAR(150) NOT NULL,
  acao VARCHAR(60) NOT NULL,
  entidade VARCHAR(40) NOT NULL,
  entidade_id INT UNSIGNED NULL,
  detalhes TEXT NULL,
  ip VARCHAR(45) NOT NULL,
  PRIMARY KEY (id),
  KEY idx_log_data (data_hora),
  KEY idx_log_acao (acao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS configuracao (
  chave VARCHAR(50) NOT NULL,
  valor VARCHAR(255) NOT NULL,
  PRIMARY KEY (chave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- Cargos (Anexo I). Escolaridade mínima e titulações são editáveis em Admin > Cargos.
INSERT IGNORE INTO cargo (id, titulo_cargo, escolaridade_minima, titulacoes_permitidas, ativo) VALUES
(1, 'Servente Escolar', 'fundamental', 'fundamental,medio', 1),
(2, 'Educador Infantil', 'medio', 'medio,graduacao,pos,mestrado', 1),
(3, 'Inspetor de Alunos', 'medio', 'medio,graduacao,pos,mestrado', 1),
(4, 'Auxiliar de Secretaria', 'medio', 'medio,secretariado,graduacao,pos,mestrado', 1),
(5, 'Professor de Educação Infantil', 'medio', 'medio,graduacao,pos,mestrado', 1),
(6, 'Professor dos Anos Iniciais do Ensino Fundamental', 'medio', 'medio,graduacao,pos,mestrado', 1),
(7, 'Professor de Educação Física', 'graduacao', 'graduacao,pos,mestrado', 1),
(8, 'Especialista da Educação (Orientador Educacional)', 'graduacao', 'graduacao,pos,mestrado', 1),
(9, 'Especialista da Educação (Supervisor Pedagógico)', 'graduacao', 'graduacao,pos,mestrado', 1),
(10, 'Psicopedagogo', 'graduacao', 'graduacao,pos,mestrado', 1);

-- Datas do edital (editáveis em Admin > Configurações)
INSERT IGNORE INTO configuracao (chave, valor) VALUES
('inscricao_inicio', '2027-01-08 00:00:00'),
('inscricao_fim', '2027-01-10 23:59:59'),
('preliminar_data', '2027-01-14'),
('recurso_data_inicio', '2027-01-16'),
('recurso_data_fim', '2027-01-17'),
('recurso_hora_inicio', '12:00'),
('recurso_hora_fim', '16:30'),
('definitiva_data', '2027-01-20'),
('data_simulada', '');
