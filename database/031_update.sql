--gisapp upgrade script v32

INSERT INTO settings (version, date) VALUES (32, now());

ALTER TABLE clients ADD COLUMN default_project_group integer;
ALTER TABLE clients ADD COLUMN default_trial_days integer;

ALTER TABLE users_roles ADD COLUMN validto date;

