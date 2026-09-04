-- MIGRATION: BT SCHEDULER v1.2 - PAUSAS E OPERADORES
-- Adiciona suporte a pausas programadas e vínculo de operador em bloqueios.

-- 1. Adiciona colunas de pausa em agenda_regras
ALTER TABLE agenda_regras ADD COLUMN IF NOT EXISTS pausa_inicio TEXT NULL;
ALTER TABLE agenda_regras ADD COLUMN IF NOT EXISTS pausa_fim TEXT NULL;

-- 2. Adiciona operador_id em agenda_bloqueios
ALTER TABLE agenda_bloqueios ADD COLUMN IF NOT EXISTS operador_id INT NOT NULL DEFAULT 0;
