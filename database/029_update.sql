--gisapp upgrade script v30

INSERT INTO settings (version, date) VALUES (30, now());

DROP VIEW project_groups_view;
CREATE OR REPLACE VIEW public.project_groups_view AS
 SELECT g.id,
    g.name,
    g.display_name,
    g.ordr,
    g.parent_id,
    ( SELECT project_groups.name
           FROM project_groups
          WHERE project_groups.id = g.parent_id) AS parent,
    g.type,
    g.client_id,
    c.display_name AS client,
    c.name AS client_name,
    p.project_crs,
        CASE
            WHEN p.count IS NULL THEN 0::bigint
            ELSE p.count
        END AS projects,
        CASE
            WHEN icount(g.base_layers_ids) IS NULL THEN 0
            ELSE icount(g.base_layers_ids)
        END AS base_layers,
        CASE
            WHEN icount(g.extra_layers_ids) IS NULL THEN 0
            ELSE icount(g.extra_layers_ids)
        END AS extra_layers,
        CASE
            WHEN ur.count IS NULL THEN 0::bigint
            ELSE ur.count
        END AS users,
    g.contact_id,
        CASE
            WHEN g.contact_id IS NULL THEN g.contact
            ELSE (u.first_name || ' '::text) || u.last_name
        END AS contact,
        CASE
            WHEN g.contact_id IS NULL THEN g.contact_email
            ELSE u.user_email
        END AS contact_email,
        CASE
            WHEN g.contact_id IS NULL THEN g.contact_phone
            ELSE u.phone
        END AS contact_phone,
    g.custom1,
    g.custom2
   FROM project_groups g
     JOIN clients c ON g.client_id = c.id
     LEFT JOIN ( SELECT count(p_1.id) AS count,
            string_agg(DISTINCT p_1.crs, ','::text) AS project_crs,
            p_1.project_group_id
           FROM projects p_1
          GROUP BY p_1.project_group_id) p ON p.project_group_id = g.id
     LEFT JOIN ( SELECT users_roles.project_group_id,
            count(*) AS count
           FROM users_roles
          GROUP BY users_roles.project_group_id) ur ON ur.project_group_id = g.id
     LEFT JOIN users u ON g.contact_id = u.user_id;
