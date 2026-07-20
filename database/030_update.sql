--gisapp upgrade script v31

INSERT INTO settings (version, date) VALUES (31, now());

CREATE OR REPLACE VIEW public.clients_view AS
WITH
	pg_summary AS (
		SELECT
			pg.client_id,
			sort(array_agg(DISTINCT g.project_group_id)) AS project_group_ids
		FROM (
				 SELECT p.project_group_id
				 FROM projects p
				 GROUP BY p.project_group_id
			 ) g
				 JOIN project_groups pg
					  ON pg.id = g.project_group_id
		GROUP BY pg.client_id
	),
	cm_summary AS (
		SELECT
			client_id,
			sort(array_agg(DISTINCT project_group_id)) AS project_group_ids
		FROM client_modules
		GROUP BY client_id
	)
SELECT
	c.id,
	c.name,
	c.display_name,
	c.url,
	c.description,
	c.ordr,

	COALESCE(cardinality(merged.project_group_ids), 0)::integer AS count,

	(
		SELECT COUNT(*)
		FROM project_groups pg
		WHERE pg.client_id = c.id
		  AND pg.type = 0
	)::integer AS count_groups,

	merged.project_group_ids

FROM clients c
		 LEFT JOIN pg_summary pg
				   ON pg.client_id = c.id
		 LEFT JOIN cm_summary cm
				   ON cm.client_id = c.id
		 LEFT JOIN LATERAL (
	SELECT
		sort(array_agg(DISTINCT x)) AS project_group_ids
	FROM unnest(
					 COALESCE(pg.project_group_ids, ARRAY[]::integer[])
					 ||
					 COALESCE(cm.project_group_ids, ARRAY[]::integer[])
			 ) AS t(x)
	) merged ON TRUE;
