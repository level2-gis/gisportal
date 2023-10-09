--gisapp upgrade script v27

INSERT INTO settings (version, date) VALUES (28, now());

ALTER TABLE clients ADD COLUMN max_users integer;
