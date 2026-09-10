-- MIGRATION: BT SCHEDULER v1.1 - JANELAS DE LIBERAÇÃO
-- Permite configurar quando a agenda de um dia específico fica visível para o público.

-- Blindagem para MariaDB (Ignora erro se a coluna já existir)
ALTER TABLE agenda_regras ADD COLUMN IF NOT EXISTS liberacao_dia_semana INTEGER NULL;
ALTER TABLE agenda_regras ADD COLUMN IF NOT EXISTS liberacao_hora_inicio TEXT DEFAULT '00:00';
ALTER TABLE agenda_regras ADD COLUMN IF NOT EXISTS liberacao_hora_fim TEXT DEFAULT '23:59';
