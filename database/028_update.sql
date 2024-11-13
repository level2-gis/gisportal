--gisapp upgrade script v29

INSERT INTO settings (version, date) VALUES (29, now());

DROP VIEW projects_view;
CREATE OR REPLACE VIEW public.projects_view AS
 SELECT p.id,
    p.name,
    g.client_id,
    p.public,
        CASE
            WHEN p.display_name IS NULL THEN p.name
            ELSE p.display_name
        END AS display_name,
    p.crs,
    p.version,
    p.description,
    p.ordr,
    p.project_path,
    pl.plugins,
    c.display_name AS client,
    c.name AS client_name,
        CASE
            WHEN g.display_name IS NULL THEN g.name
            ELSE ((g.display_name || ' ('::text) || g.name) || ')'::text
        END AS "group",
    g.id AS group_id,
    g.name AS group_name,
    p.overview_layer_id,
    ( SELECT layers.display_name
           FROM layers
          WHERE layers.id = p.overview_layer_id) AS overview_layer
   FROM projects p
     JOIN project_groups g ON g.id = p.project_group_id
     JOIN clients c ON c.id = g.client_id
   LEFT JOIN (
	select project_id,string_agg(name,'<br>') as plugins from plugins pl,
	(select id as project_id,unnest(plugin_ids) from projects) x
	where pl.id=unnest
	group by project_id
   ) pl ON pl.project_id = p.id;
