--gisapp upgrade script v32

INSERT INTO settings (version, date) VALUES (33, now());

ALTER TABLE clients ADD COLUMN default_role integer;

