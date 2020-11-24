--gisapp upgrade script v23

INSERT INTO settings (version, date) VALUES (23, now());

ALTER TABLE projects ADD COLUMN version text;
ALTER TABLE plugins ADD COLUMN active boolean NOT NULL DEFAULT true;
ALTER TABLE users_print ADD COLUMN project text;

DROP VIEW IF EXISTS users_print_view;
CREATE VIEW users_print_view AS
 SELECT up.user_name,
    u.user_email,
    (u.first_name || ' '::text) || u.last_name AS display_name,
    up.title,
    up.description,
    up.print_time,
    u.user_id,
    p.id as project_id,
    up.project,
    p.display_name as project_display_name,
    g.id as group_id,
    g.name as group,
    g.display_name as group_display_name,
    c.id as client_id,
    c.name as client,
    c.display_name as client_display_name
   FROM users u,
    users_print up,
    projects p,
    project_groups g,
    clients c
  WHERE up.user_name = u.user_name AND
  up.project = p.name AND
  p.project_group_id = g.id AND
  g.client_id = c.id;

--setup_v22.sql
