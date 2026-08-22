-- AtualizaÃ§Ã£o para suporte a Logo de PromoÃ§Ãµes Mobile
INSERT IGNORE INTO configuracoes (chave, valor, tipo, descricao, editavel) VALUES
('promo_logo', '../uploads/logo.png', 'STRING', 'Logo que aparece no topo da area de promocoes no celular', 1);
