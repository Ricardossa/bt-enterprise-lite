DROP TABLE IF EXISTS `tenants`;
CREATE TABLE `tenants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `status` enum('ATIVO','SUSPENSO','BLOQUEADO') DEFAULT 'ATIVO',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `tenants` (`id`, `uuid`, `slug`, `nome`, `status`, `created_at`, `updated_at`) VALUES ('30', '5579fd18-b48e-473e-a6e1-fb3cf1335700', 'lite', 'VM - BANCADA', 'ATIVO', '2026-08-22 09:49:19', '2026-08-22 09:49:19');

DROP TABLE IF EXISTS `servicos`;
CREATE TABLE `servicos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `prefixo` varchar(5) NOT NULL,
  `icone` varchar(10) DEFAULT '?',
  `cor` varchar(7) DEFAULT '#1565C0',
  `ordem` int(11) DEFAULT 0,
  `tempo_medio` int(11) DEFAULT 10,
  `preco` decimal(10,2) DEFAULT 0.00,
  `ativo` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_tenant_servico_slug` (`tenant_id`,`slug`),
  UNIQUE KEY `idx_tenant_servico_codigo` (`tenant_id`,`codigo`),
  CONSTRAINT `fk_servicos_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `servicos` (`id`, `tenant_id`, `codigo`, `nome`, `slug`, `prefixo`, `icone`, `cor`, `ordem`, `tempo_medio`, `preco`, `ativo`, `created_at`, `updated_at`) VALUES ('44', '30', '1', 'Corte Simples', 'Máquina e Tesoura', 'CRS', '✂️', '#1565c0', '1', '10', '30.00', '1', '2026-08-22 10:03:43', '2026-08-22 10:03:43');
INSERT INTO `servicos` (`id`, `tenant_id`, `codigo`, `nome`, `slug`, `prefixo`, `icone`, `cor`, `ordem`, `tempo_medio`, `preco`, `ativo`, `created_at`, `updated_at`) VALUES ('45', '30', '02', 'Vacinação', 'Vacinação', 'VAC', '💉', '#1565c0', '2', '10', '0.00', '0', '2026-08-22 10:03:43', '2026-08-22 10:03:43');
INSERT INTO `servicos` (`id`, `tenant_id`, `codigo`, `nome`, `slug`, `prefixo`, `icone`, `cor`, `ordem`, `tempo_medio`, `preco`, `ativo`, `created_at`, `updated_at`) VALUES ('46', '30', 'E', 'EXAMES', 'exames', 'EX', '🩺', '#c19c15', '1', '10', '0.00', '0', '2026-08-22 10:03:43', '2026-08-22 10:03:43');
INSERT INTO `servicos` (`id`, `tenant_id`, `codigo`, `nome`, `slug`, `prefixo`, `icone`, `cor`, `ordem`, `tempo_medio`, `preco`, `ativo`, `created_at`, `updated_at`) VALUES ('47', '30', '2', 'Degradê', 'Taper Fade', 'DEG', '👨🏼‍🦰', '#010509', '0', '10', '70.00', '1', '2026-08-22 10:03:43', '2026-08-22 10:03:43');
INSERT INTO `servicos` (`id`, `tenant_id`, `codigo`, `nome`, `slug`, `prefixo`, `icone`, `cor`, `ordem`, `tempo_medio`, `preco`, `ativo`, `created_at`, `updated_at`) VALUES ('48', '30', '1786887154869', 'Barba', 'barba', 'BAR', '🧔🏾‍♂️', '#1db4ff', '0', '30', '15.00', '1', '2026-08-22 10:03:43', '2026-08-22 10:03:43');
INSERT INTO `servicos` (`id`, `tenant_id`, `codigo`, `nome`, `slug`, `prefixo`, `icone`, `cor`, `ordem`, `tempo_medio`, `preco`, `ativo`, `created_at`, `updated_at`) VALUES ('49', '30', '1786890923218', 'Corte Simples + Barba', 'corte-simples-+-barba', 'COR', '✂️🧔🏾‍♂️', '#1db4ff', '0', '45', '35.00', '1', '2026-08-22 10:03:43', '2026-08-22 10:03:43');

DROP TABLE IF EXISTS `guiches`;
CREATE TABLE `guiches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `icone` varchar(10) DEFAULT '⚙️',
  `cor` varchar(7) DEFAULT '#1565C0',
  `ativo` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_tenant_guiche_codigo` (`tenant_id`,`codigo`),
  CONSTRAINT `fk_guiches_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `guiches` (`id`, `tenant_id`, `codigo`, `nome`, `icone`, `cor`, `ativo`, `created_at`, `updated_at`) VALUES ('38', '30', '01', 'Barber Ricardo', '⚙️', '#1565C0', '1', '2026-08-22 10:03:43', '2026-08-22 16:08:01');
INSERT INTO `guiches` (`id`, `tenant_id`, `codigo`, `nome`, `icone`, `cor`, `ativo`, `created_at`, `updated_at`) VALUES ('39', '30', '02', 'Barber João', '⚙️', '#1565C0', '1', '2026-08-22 10:03:43', '2026-08-22 16:07:44');
INSERT INTO `guiches` (`id`, `tenant_id`, `codigo`, `nome`, `icone`, `cor`, `ativo`, `created_at`, `updated_at`) VALUES ('40', '30', '03', 'Barber Piu', '⚙️', '#1565C0', '1', '2026-08-22 10:03:43', '2026-08-22 10:13:19');

DROP TABLE IF EXISTS `operadores`;
CREATE TABLE `operadores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `login` varchar(100) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `nivel` enum('ADMIN','GERENTE','OPERADOR') DEFAULT 'OPERADOR',
  `guiche_id` int(11) DEFAULT NULL,
  `servico_id` int(11) DEFAULT NULL,
  `prefixo` varchar(5) DEFAULT NULL,
  `foto_url` text DEFAULT NULL,
  `magic_token` varchar(100) DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` varchar(20) DEFAULT 'ONLINE',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_tenant_login` (`tenant_id`,`login`),
  CONSTRAINT `fk_operadores_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `operadores` (`id`, `tenant_id`, `nome`, `login`, `senha`, `nivel`, `guiche_id`, `servico_id`, `prefixo`, `foto_url`, `magic_token`, `ativo`, `created_at`, `updated_at`, `status`) VALUES ('30', '30', 'Administrador Master', 'admin', '$2y$12$0yVHl1Nc7DCrbatIdRaUJ.xwOgPYZDcWSz8mXiS.k2f/rEZ/JMdhC', 'ADMIN', NULL, NULL, NULL, NULL, NULL, '1', '2026-08-22 09:49:19', '2026-08-22 09:49:19', 'ONLINE');
INSERT INTO `operadores` (`id`, `tenant_id`, `nome`, `login`, `senha`, `nivel`, `guiche_id`, `servico_id`, `prefixo`, `foto_url`, `magic_token`, `ativo`, `created_at`, `updated_at`, `status`) VALUES ('31', '30', 'Ricardo Brandão', 'ricardo', '$2y$12$GFpEVZs12qxBJwqPnJ1l8eKYfssb2IZVaiwCUjgVp3TzUWix1M3US', 'OPERADOR', '38', '48', 'RB', 'tenants/30/operador_31.png', NULL, '1', '2026-08-22 10:03:43', '2026-08-22 11:08:16', 'ONLINE');
INSERT INTO `operadores` (`id`, `tenant_id`, `nome`, `login`, `senha`, `nivel`, `guiche_id`, `servico_id`, `prefixo`, `foto_url`, `magic_token`, `ativo`, `created_at`, `updated_at`, `status`) VALUES ('33', '30', 'JOÃO', 'joao', '$2y$12$I6xG/oV0PG6N.0X2kArd.OAVfbafdOA9DVmqp4k.nwfrqfsFbwZ82', 'OPERADOR', '39', '48', 'JO', 'tenants/30/operador_33.png', NULL, '1', '2026-08-22 11:08:54', '2026-08-22 11:09:09', 'ONLINE');
INSERT INTO `operadores` (`id`, `tenant_id`, `nome`, `login`, `senha`, `nivel`, `guiche_id`, `servico_id`, `prefixo`, `foto_url`, `magic_token`, `ativo`, `created_at`, `updated_at`, `status`) VALUES ('34', '30', 'PIU', 'piu', '$2y$12$6CRxMH2jKazkz0VxCwxqNOt9pFy9P5pZJ5LlhSY2mqCIjk0fxcXBS', 'OPERADOR', '40', '48', 'PI', 'tenants/30/operador_34.png', NULL, '1', '2026-08-22 11:09:35', '2026-08-22 11:09:48', 'ONLINE');

DROP TABLE IF EXISTS `senhas`;
CREATE TABLE `senhas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `uuid` varchar(64) NOT NULL,
  `cliente_uuid` varchar(64) DEFAULT NULL,
  `servico_id` int(11) DEFAULT NULL,
  `guiche_id` int(11) DEFAULT NULL,
  `operador_id` int(11) DEFAULT NULL,
  `device_id` varchar(100) DEFAULT NULL,
  `codigo` varchar(20) NOT NULL,
  `numero` int(11) NOT NULL,
  `prefixo` varchar(5) NOT NULL,
  `nome_cliente` varchar(255) DEFAULT NULL,
  `whatsapp` varchar(20) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'AGUARDANDO',
  `tipo_atendimento` varchar(20) DEFAULT 'NORMAL',
  `data_agendamento` datetime DEFAULT NULL,
  `cancel_token` varchar(32) DEFAULT NULL,
  `pagamento_status` varchar(20) DEFAULT 'PENDENTE',
  `pagamento_id` varchar(100) DEFAULT NULL,
  `valor_pago` decimal(10,2) DEFAULT 0.00,
  `valor_total` decimal(10,2) DEFAULT 0.00,
  `servicos_desc` text DEFAULT NULL,
  `atendente` varchar(255) DEFAULT NULL,
  `atendente_nome` varchar(255) DEFAULT NULL,
  `emitida_em` datetime DEFAULT current_timestamp(),
  `chamada_em` datetime DEFAULT NULL,
  `finalizada_em` datetime DEFAULT NULL,
  `sincronizado` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `cliente_id` int(11) DEFAULT 0,
  `pix_qr_code` text DEFAULT NULL,
  `pix_qr_base64` longtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  KEY `idx_tenant_status` (`tenant_id`,`status`),
  KEY `idx_tenant_created` (`tenant_id`,`created_at`),
  KEY `idx_senhas_status` (`status`),
  KEY `idx_senhas_uuid` (`uuid`),
  CONSTRAINT `fk_senhas_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=87 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `senhas` (`id`, `tenant_id`, `uuid`, `cliente_uuid`, `servico_id`, `guiche_id`, `operador_id`, `device_id`, `codigo`, `numero`, `prefixo`, `nome_cliente`, `whatsapp`, `status`, `tipo_atendimento`, `data_agendamento`, `cancel_token`, `pagamento_status`, `pagamento_id`, `valor_pago`, `valor_total`, `servicos_desc`, `atendente`, `atendente_nome`, `emitida_em`, `chamada_em`, `finalizada_em`, `sincronizado`, `created_at`, `updated_at`, `cliente_id`, `pix_qr_code`, `pix_qr_base64`) VALUES ('84', '30', 'cfaabfbbb68207b0ae0e22afe87d6479', 'CLI-7ZL3R48BW', '47', '38', '31', NULL, 'AGD001', '0', 'G', 'MARCIO RICARDO BRANDãO DE JESUS', '71991077018', 'FINALIZADA', 'NORMAL', '2026-08-22 12:00:00', '0F50B31B', 'PAGO', 'STATIC_1787407948', '0.00', '70.00', 'Degradê', 'Ricardo Brandão', NULL, '2026-08-22 12:00:00', '2026-08-22 11:17:16', '2026-08-22 11:24:12', '0', '2026-08-22 11:12:28', '2026-08-22 11:24:12', '0', '00020101021126360014br.gov.bcb.pix0114+5571991077018520400005303986540570.005802BR5917BRANDAO TECH LITE6008BRASILIA62070503***630482A2', NULL);
INSERT INTO `senhas` (`id`, `tenant_id`, `uuid`, `cliente_uuid`, `servico_id`, `guiche_id`, `operador_id`, `device_id`, `codigo`, `numero`, `prefixo`, `nome_cliente`, `whatsapp`, `status`, `tipo_atendimento`, `data_agendamento`, `cancel_token`, `pagamento_status`, `pagamento_id`, `valor_pago`, `valor_total`, `servicos_desc`, `atendente`, `atendente_nome`, `emitida_em`, `chamada_em`, `finalizada_em`, `sincronizado`, `created_at`, `updated_at`, `cliente_id`, `pix_qr_code`, `pix_qr_base64`) VALUES ('85', '30', '23f3dc9d6cfe941c21f579a15bd2771e', '24a6e14332b3376ae12fa64a06a63eda', '47', '39', '33', 'dev-1787079976163', 'JO001', '1', 'DEG', '', NULL, 'FINALIZADA', 'NORMAL', NULL, NULL, 'PAGO', 'STATIC_1787414723', '0.00', '70.00', 'Degradê', 'JOÃO', NULL, '2026-08-22 13:05:23', '2026-08-22 13:06:47', '2026-08-22 13:08:10', '0', '2026-08-22 13:05:23', '2026-08-22 13:10:50', '0', '00020101021126360014br.gov.bcb.pix0114+5571991077018520400005303986540570.005802BR5917BRANDAO TECH LITE6008BRASILIA62070503***630482A2', '');
INSERT INTO `senhas` (`id`, `tenant_id`, `uuid`, `cliente_uuid`, `servico_id`, `guiche_id`, `operador_id`, `device_id`, `codigo`, `numero`, `prefixo`, `nome_cliente`, `whatsapp`, `status`, `tipo_atendimento`, `data_agendamento`, `cancel_token`, `pagamento_status`, `pagamento_id`, `valor_pago`, `valor_total`, `servicos_desc`, `atendente`, `atendente_nome`, `emitida_em`, `chamada_em`, `finalizada_em`, `sincronizado`, `created_at`, `updated_at`, `cliente_id`, `pix_qr_code`, `pix_qr_base64`) VALUES ('86', '30', '0172951e6932aa2cabcf0163d3b568e5', 'CLI-JDKJB3E8F', '49', '39', '33', NULL, 'AGD003', '0', 'G', 'RICARDO', '7191077018', 'FINALIZADA', 'NORMAL', '2026-08-22 14:00:00', '83D3F958', 'PENDENTE', 'STATIC_1787415142', '0.00', '35.00', 'Corte Simples + Barba', 'JOÃO', NULL, '2026-08-22 14:00:00', '2026-08-22 13:18:03', '2026-08-22 13:39:32', '0', '2026-08-22 13:12:22', '2026-08-22 13:39:32', '0', '00020101021126360014br.gov.bcb.pix0114+5571991077018520400005303986540535.005802BR5917BRANDAO TECH LITE6008BRASILIA62070503***6304DDD4', NULL);

DROP TABLE IF EXISTS `configuracoes`;
CREATE TABLE `configuracoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `chave` varchar(100) NOT NULL,
  `valor` text DEFAULT NULL,
  `tipo` varchar(20) DEFAULT 'STRING',
  `descricao` text DEFAULT NULL,
  `editavel` tinyint(1) DEFAULT 1,
  `label_cliente` varchar(50) DEFAULT 'Paciente',
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_tenant_chave` (`tenant_id`,`chave`),
  CONSTRAINT `fk_config_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=169751 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `configuracoes` (`id`, `tenant_id`, `chave`, `valor`, `tipo`, `descricao`, `editavel`, `label_cliente`, `updated_at`) VALUES ('140620', '30', 'master_url', 'http://api.brandaotech.com.br:8080/api/v1/sync.php', 'STRING', NULL, '1', 'Paciente', '2026-08-22 09:49:19');
INSERT INTO `configuracoes` (`id`, `tenant_id`, `chave`, `valor`, `tipo`, `descricao`, `editavel`, `label_cliente`, `updated_at`) VALUES ('140621', '30', 'uuid', '5579fd18-b48e-473e-a6e1-fb3cf1335700', 'STRING', NULL, '1', 'Paciente', '2026-08-22 09:49:19');
INSERT INTO `configuracoes` (`id`, `tenant_id`, `chave`, `valor`, `tipo`, `descricao`, `editavel`, `label_cliente`, `updated_at`) VALUES ('140622', '30', 'token', '1C3E5CE9B707A09D1A80A33D87C1BF30A5F7D05F68D10E628365A99A52AEF125', 'STRING', NULL, '1', 'Paciente', '2026-08-22 09:49:19');
INSERT INTO `configuracoes` (`id`, `tenant_id`, `chave`, `valor`, `tipo`, `descricao`, `editavel`, `label_cliente`, `updated_at`) VALUES ('145843', '30', 'app_name', 'BT Queue Enterprise', 'STRING', 'Nome da aplicação local', '1', 'Paciente', '2026-08-22 10:03:43');
INSERT INTO `configuracoes` (`id`, `tenant_id`, `chave`, `valor`, `tipo`, `descricao`, `editavel`, `label_cliente`, `updated_at`) VALUES ('145844', '30', 'offline_limit_days', '7', 'INT', 'Dias permitidos de operação sem sincronização', '1', 'Paciente', '2026-08-22 10:03:43');
INSERT INTO `configuracoes` (`id`, `tenant_id`, `chave`, `valor`, `tipo`, `descricao`, `editavel`, `label_cliente`, `updated_at`) VALUES ('145845', '30', 'printer_name', 'BT_TICKET', 'STRING', 'Nome da impressora compartilhada no Windows', '1', 'Paciente', '2026-08-22 10:03:43');
INSERT INTO `configuracoes` (`id`, `tenant_id`, `chave`, `valor`, `tipo`, `descricao`, `editavel`, `label_cliente`, `updated_at`) VALUES ('145846', '30', 'integration_token', 'CEF3504AC887599D03C07639D5C90D63', 'STRING', 'Token de Segurança para Chamadas via API Externa', '1', 'Paciente', '2026-08-22 10:03:43');
INSERT INTO `configuracoes` (`id`, `tenant_id`, `chave`, `valor`, `tipo`, `descricao`, `editavel`, `label_cliente`, `updated_at`) VALUES ('145847', '30', 'qr_security_salt', 'bf3f736fa4c0024aca6a87317277fd93', 'STRING', 'Chave secreta para validação de QR Code dinâmico', '1', 'Paciente', '2026-08-22 10:03:43');
INSERT INTO `configuracoes` (`id`, `tenant_id`, `chave`, `valor`, `tipo`, `descricao`, `editavel`, `label_cliente`, `updated_at`) VALUES ('145851', '30', 'feature_max_devices', '1', 'BOOLEAN', NULL, '1', 'Paciente', '2026-08-22 10:03:43');
INSERT INTO `configuracoes` (`id`, `tenant_id`, `chave`, `valor`, `tipo`, `descricao`, `editavel`, `label_cliente`, `updated_at`) VALUES ('145852', '30', 'feature_voice_enabled', '1', 'BOOLEAN', NULL, '1', 'Paciente', '2026-08-22 10:03:43');
INSERT INTO `configuracoes` (`id`, `tenant_id`, `chave`, `valor`, `tipo`, `descricao`, `editavel`, `label_cliente`, `updated_at`) VALUES ('145853', '30', 'feature_custom_branding', '1', 'BOOLEAN', NULL, '1', 'Paciente', '2026-08-22 10:03:43');
INSERT INTO `configuracoes` (`id`, `tenant_id`, `chave`, `valor`, `tipo`, `descricao`, `editavel`, `label_cliente`, `updated_at`) VALUES ('145854', '30', 'feature_reports', '1', 'BOOLEAN', NULL, '1', 'Paciente', '2026-08-22 10:03:43');
INSERT INTO `configuracoes` (`id`, `tenant_id`, `chave`, `valor`, `tipo`, `descricao`, `editavel`, `label_cliente`, `updated_at`) VALUES ('145855', '30', 'feature_multi_ticket', '1', 'BOOLEAN', NULL, '1', 'Paciente', '2026-08-22 10:03:43');
INSERT INTO `configuracoes` (`id`, `tenant_id`, `chave`, `valor`, `tipo`, `descricao`, `editavel`, `label_cliente`, `updated_at`) VALUES ('145856', '30', 'feature_hybrid_scheduling', '1', 'BOOLEAN', NULL, '1', 'Paciente', '2026-08-22 10:03:43');
INSERT INTO `configuracoes` (`id`, `tenant_id`, `chave`, `valor`, `tipo`, `descricao`, `editavel`, `label_cliente`, `updated_at`) VALUES ('145857', '30', 'modo', 'cloud', 'STRING', NULL, '1', 'Paciente', '2026-08-22 10:03:43');
INSERT INTO `configuracoes` (`id`, `tenant_id`, `chave`, `valor`, `tipo`, `descricao`, `editavel`, `label_cliente`, `updated_at`) VALUES ('145858', '30', 'url_local', 'http://192.168.100.245', 'STRING', NULL, '1', 'Paciente', '2026-08-22 10:03:43');
INSERT INTO `configuracoes` (`id`, `tenant_id`, `chave`, `valor`, `tipo`, `descricao`, `editavel`, `label_cliente`, `updated_at`) VALUES ('145859', '30', 'url_publica', 'http://lite.brandaotech.com.br:8120', 'STRING', NULL, '1', 'Paciente', '2026-08-22 10:03:43');
INSERT INTO `configuracoes` (`id`, `tenant_id`, `chave`, `valor`, `tipo`, `descricao`, `editavel`, `label_cliente`, `updated_at`) VALUES ('145860', '30', 'whatsapp', '', 'STRING', NULL, '1', 'Paciente', '2026-08-22 10:03:43');
INSERT INTO `configuracoes` (`id`, `tenant_id`, `chave`, `valor`, `tipo`, `descricao`, `editavel`, `label_cliente`, `updated_at`) VALUES ('145861', '30', 'instagram', '', 'STRING', NULL, '1', 'Paciente', '2026-08-22 10:03:43');
INSERT INTO `configuracoes` (`id`, `tenant_id`, `chave`, `valor`, `tipo`, `descricao`, `editavel`, `label_cliente`, `updated_at`) VALUES ('145862', '30', 'slogan', '', 'STRING', NULL, '1', 'Paciente', '2026-08-22 10:03:43');
INSERT INTO `configuracoes` (`id`, `tenant_id`, `chave`, `valor`, `tipo`, `descricao`, `editavel`, `label_cliente`, `updated_at`) VALUES ('145863', '30', 'local_print_ip', '192.168.100.126', 'STRING', NULL, '1', 'Paciente', '2026-08-22 10:03:43');
INSERT INTO `configuracoes` (`id`, `tenant_id`, `chave`, `valor`, `tipo`, `descricao`, `editavel`, `label_cliente`, `updated_at`) VALUES ('145865', '30', 'opening_time', '00:00', 'STRING', NULL, '1', 'Paciente', '2026-08-22 10:03:43');
INSERT INTO `configuracoes` (`id`, `tenant_id`, `chave`, `valor`, `tipo`, `descricao`, `editavel`, `label_cliente`, `updated_at`) VALUES ('145866', '30', 'closing_time', '23:59', 'STRING', NULL, '1', 'Paciente', '2026-08-22 10:03:43');
INSERT INTO `configuracoes` (`id`, `tenant_id`, `chave`, `valor`, `tipo`, `descricao`, `editavel`, `label_cliente`, `updated_at`) VALUES ('145868', '30', 'promo_campanha', 'uploads/logo_campanha.png', 'STRING', NULL, '1', 'Paciente', '2026-08-22 10:03:43');
INSERT INTO `configuracoes` (`id`, `tenant_id`, `chave`, `valor`, `tipo`, `descricao`, `editavel`, `label_cliente`, `updated_at`) VALUES ('145869', '30', 'empresa', '', 'STRING', NULL, '1', 'Paciente', '2026-08-22 10:03:43');
INSERT INTO `configuracoes` (`id`, `tenant_id`, `chave`, `valor`, `tipo`, `descricao`, `editavel`, `label_cliente`, `updated_at`) VALUES ('145870', '30', 'label_cliente', 'Cliente', 'STRING', NULL, '1', 'Paciente', '2026-08-22 10:03:43');
INSERT INTO `configuracoes` (`id`, `tenant_id`, `chave`, `valor`, `tipo`, `descricao`, `editavel`, `label_cliente`, `updated_at`) VALUES ('145877', '30', 'mercadopago_token', '', 'STRING', NULL, '1', 'Paciente', '2026-08-22 10:03:43');
INSERT INTO `configuracoes` (`id`, `tenant_id`, `chave`, `valor`, `tipo`, `descricao`, `editavel`, `label_cliente`, `updated_at`) VALUES ('145878', '30', 'pix_chave_estatica', '71991077018', 'STRING', NULL, '1', 'Paciente', '2026-08-22 10:03:43');
INSERT INTO `configuracoes` (`id`, `tenant_id`, `chave`, `valor`, `tipo`, `descricao`, `editavel`, `label_cliente`, `updated_at`) VALUES ('145879', '30', 'mercadopago_test_mode', '0', 'STRING', NULL, '1', 'Paciente', '2026-08-22 10:03:43');
INSERT INTO `configuracoes` (`id`, `tenant_id`, `chave`, `valor`, `tipo`, `descricao`, `editavel`, `label_cliente`, `updated_at`) VALUES ('150052', '30', 'promo_logo', 'uploads/tenants/30/logo_mobile.png', 'STRING', NULL, '1', 'Paciente', '2026-08-22 11:07:02');
INSERT INTO `configuracoes` (`id`, `tenant_id`, `chave`, `valor`, `tipo`, `descricao`, `editavel`, `label_cliente`, `updated_at`) VALUES ('150866', '30', 'agenda_horizonte', '30', 'NUMBER', NULL, '1', 'Paciente', '2026-08-22 11:10:50');
INSERT INTO `configuracoes` (`id`, `tenant_id`, `chave`, `valor`, `tipo`, `descricao`, `editavel`, `label_cliente`, `updated_at`) VALUES ('169742', '30', 'whatsapp_enabled', '0', 'BOOLEAN', 'Habilita notificações automáticas via WhatsApp', '1', 'Paciente', '2026-08-22 16:14:58');
INSERT INTO `configuracoes` (`id`, `tenant_id`, `chave`, `valor`, `tipo`, `descricao`, `editavel`, `label_cliente`, `updated_at`) VALUES ('169743', '30', 'whatsapp_api_url', '', 'STRING', 'URL da Instância da API (Ex: Evolution API)', '1', 'Paciente', '2026-08-22 16:14:58');
INSERT INTO `configuracoes` (`id`, `tenant_id`, `chave`, `valor`, `tipo`, `descricao`, `editavel`, `label_cliente`, `updated_at`) VALUES ('169744', '30', 'whatsapp_api_token', '', 'STRING', 'Token de autenticação da API', '1', 'Paciente', '2026-08-22 16:14:58');
INSERT INTO `configuracoes` (`id`, `tenant_id`, `chave`, `valor`, `tipo`, `descricao`, `editavel`, `label_cliente`, `updated_at`) VALUES ('169745', '30', 'ai_url', 'http://192.168.100.250:11434/api/generate', 'STRING', 'URL do motor de Inteligência Artificial (Ollama)', '1', 'Paciente', '2026-08-22 16:14:58');
INSERT INTO `configuracoes` (`id`, `tenant_id`, `chave`, `valor`, `tipo`, `descricao`, `editavel`, `label_cliente`, `updated_at`) VALUES ('169746', '30', 'radar_enabled', '0', 'BOOLEAN', 'Habilita o radar de faltas automáticas', '1', 'Paciente', '2026-08-22 16:14:58');
INSERT INTO `configuracoes` (`id`, `tenant_id`, `chave`, `valor`, `tipo`, `descricao`, `editavel`, `label_cliente`, `updated_at`) VALUES ('169747', '30', 'radar_tolerance', '15', 'NUMBER', 'Minutos de tolerância para falta automática', '1', 'Paciente', '2026-08-22 16:14:58');
INSERT INTO `configuracoes` (`id`, `tenant_id`, `chave`, `valor`, `tipo`, `descricao`, `editavel`, `label_cliente`, `updated_at`) VALUES ('169748', '30', 'priority_mode', 'STRICT', 'STRING', 'Modo de chamada: STRICT (Sempre Prioridade) ou BALANCED (Intercalado)', '1', 'Paciente', '2026-08-22 16:14:58');
INSERT INTO `configuracoes` (`id`, `tenant_id`, `chave`, `valor`, `tipo`, `descricao`, `editavel`, `label_cliente`, `updated_at`) VALUES ('169749', '30', 'priority_ratio', '3', 'NUMBER', 'Quantidade de prioridades antes de um normal (no modo BALANCED)', '1', 'Paciente', '2026-08-22 16:14:58');
INSERT INTO `configuracoes` (`id`, `tenant_id`, `chave`, `valor`, `tipo`, `descricao`, `editavel`, `label_cliente`, `updated_at`) VALUES ('169750', '30', 'feature_priority_selection', '1', 'BOOLEAN', 'Habilita a tela de escolha entre Normal e Prioritário no Totem', '1', 'Paciente', '2026-08-22 16:14:58');

DROP TABLE IF EXISTS `atividades`;
CREATE TABLE `atividades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `tipo` varchar(20) DEFAULT 'INFO',
  `nivel` varchar(20) DEFAULT 'INFO',
  `categoria` varchar(50) DEFAULT 'SYSTEM',
  `mensagem` text NOT NULL,
  `usuario` varchar(100) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `data_criacao` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_atividades_tenant` (`tenant_id`),
  KEY `idx_atividades_data` (`data_criacao`),
  CONSTRAINT `fk_atividades_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `atividades` (`id`, `tenant_id`, `tipo`, `nivel`, `categoria`, `mensagem`, `usuario`, `metadata`, `data_criacao`) VALUES ('1', '30', 'INFO', 'INFO', 'FILA', 'Senha AGD001 chamada para Babrber Ricardo', 'Ricardo Brandão', NULL, '2026-08-22 11:17:16');
INSERT INTO `atividades` (`id`, `tenant_id`, `tipo`, `nivel`, `categoria`, `mensagem`, `usuario`, `metadata`, `data_criacao`) VALUES ('2', '30', 'SUCCESS', 'INFO', 'FILA', 'Nova senha emitida: JO001', 'Totem', NULL, '2026-08-22 13:05:23');
INSERT INTO `atividades` (`id`, `tenant_id`, `tipo`, `nivel`, `categoria`, `mensagem`, `usuario`, `metadata`, `data_criacao`) VALUES ('3', '30', 'INFO', 'INFO', 'FILA', 'Senha JO001 chamada para Babrber João', 'JOÃO', NULL, '2026-08-22 13:06:47');
INSERT INTO `atividades` (`id`, `tenant_id`, `tipo`, `nivel`, `categoria`, `mensagem`, `usuario`, `metadata`, `data_criacao`) VALUES ('4', '30', 'INFO', 'INFO', 'FILA', 'Senha AGD003 chamada para Babrber João', 'JOÃO', NULL, '2026-08-22 13:18:03');

DROP TABLE IF EXISTS `licencas`;
CREATE TABLE `licencas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `chave` varchar(100) NOT NULL,
  `token` varchar(100) DEFAULT NULL,
  `uuid` varchar(100) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'ATIVA',
  `validade` datetime DEFAULT NULL,
  `ultima_validacao` datetime DEFAULT NULL,
  `offline_dias` int(11) DEFAULT 15,
  `assinatura` text DEFAULT NULL,
  `hardware_id` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_tenant_licenca` (`tenant_id`,`chave`),
  CONSTRAINT `fk_licencas_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `licencas` (`id`, `tenant_id`, `cliente_id`, `chave`, `token`, `uuid`, `status`, `validade`, `ultima_validacao`, `offline_dias`, `assinatura`, `hardware_id`, `created_at`) VALUES ('17', '30', NULL, 'FORCE-KEY-2026', '1C3E5CE9B707A09D1A80A33D87C1BF30A5F7D05F68D10E628365A99A52AEF125', '5579fd18-b48e-473e-a6e1-fb3cf1335700', 'ATIVA', '2027-08-22 09:49:19', NULL, '15', '64672622b4fe39f919a24217da7fa2aac74a576af16c21b221d9913d0122a271', '174d0c49541eb26014b5f39f1286a04c9f63ba53b396de5b79d5a6522d81a234', '2026-08-22 09:49:19');

DROP TABLE IF EXISTS `promocoes`;
CREATE TABLE `promocoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descricao` text DEFAULT NULL,
  `preco` varchar(50) DEFAULT NULL,
  `imagem` text DEFAULT NULL,
  `ordem` int(11) DEFAULT 0,
  `ativo` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_promocoes_tenant` (`tenant_id`),
  CONSTRAINT `fk_promocoes_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `promocoes` (`id`, `tenant_id`, `titulo`, `descricao`, `preco`, `imagem`, `ordem`, `ativo`, `created_at`, `updated_at`) VALUES ('6', '30', 'Cerveja Heineken Long Neck 330ml', '20% OFF', 'R$ 19,90', 'CCA23C49.jpg', '0', '1', '2026-08-22 11:07:33', '2026-08-22 11:07:33');
INSERT INTO `promocoes` (`id`, `tenant_id`, `titulo`, `descricao`, `preco`, `imagem`, `ordem`, `ativo`, `created_at`, `updated_at`) VALUES ('7', '30', 'Cerveja Budweiser, American Lager, Long Neck 330ml', '', 'R$ 18,90', 'D71AC7C2.jpg', '1', '1', '2026-08-22 11:07:52', '2026-08-22 11:07:52');
INSERT INTO `promocoes` (`id`, `tenant_id`, `titulo`, `descricao`, `preco`, `imagem`, `ordem`, `ativo`, `created_at`, `updated_at`) VALUES ('8', '30', 'Vitamina C 500mg 30 Comprimidos Neo Química', '20% OFF', 'R$ 14 , 99', '437319C3.webp', '0', '1', '2026-08-22 11:11:36', '2026-08-22 11:11:36');

