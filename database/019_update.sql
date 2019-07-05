--gisapp upgrade script for gisportal v2

INSERT INTO settings (version, date) VALUES (20, now());

--fix google layers so they are editable with portal
UPDATE LAYERS SET definition = '{
    "type": "satellite",
    "numZoomLevels": 20,
    "isBaseLayer": true,
    "useTiltImages": false
}'
WHERE name='google_sat';

UPDATE LAYERS SET definition = '{
    "type": "roadmap",
    "numZoomLevels": 22,
    "isBaseLayer": true,
    "useTiltImages": false
}'
WHERE name='google_map';

--CREATE NEW TABLES
CREATE TABLE project_groups (
	id serial PRIMARY KEY,
	name text UNIQUE NOT NULL,
	display_name text,	
	parent_id integer,
	type integer NOT NULL,
	client_id integer NOT NULL,
	ordr integer NOT NULL DEFAULT 0,
	base_layers_ids integer[],
	extra_layers_ids integer[],
	contact text,
	contact_id integer,
	contact_phone text,
	contact_email text,
	custom1 text,
	custom2 text
);
ALTER TABLE ONLY project_groups ADD CONSTRAINT project_groups_client_id_fkey FOREIGN KEY (client_id) REFERENCES clients(id);
ALTER TABLE ONLY project_groups ADD CONSTRAINT project_groups_parent_id_fkey FOREIGN KEY (parent_id) REFERENCES project_groups(id);

CREATE TABLE tasks (
	id serial PRIMARY KEY,
	name text UNIQUE NOT NULL,
	admin boolean NOT NULL,
	power boolean NOT NULL
);
INSERT INTO tasks (name, admin, power) VALUES ('clients_table_view',TRUE, TRUE);
INSERT INTO tasks (name, admin, power) VALUES ('clients_edit',TRUE, TRUE);
INSERT INTO tasks (name, admin, power) VALUES ('clients_send_email',TRUE, TRUE);
INSERT INTO tasks (name, admin, power) VALUES ('project_groups_table_view',TRUE, TRUE);
INSERT INTO tasks (name, admin, power) VALUES ('project_groups_edit',TRUE, TRUE);
INSERT INTO tasks (name, admin, power) VALUES ('project_groups_edit_properties',TRUE, FALSE);
INSERT INTO tasks (name, admin, power) VALUES ('project_groups_edit_contacts',TRUE, TRUE);
INSERT INTO tasks (name, admin, power) VALUES ('project_groups_edit_layers',TRUE, FALSE);
INSERT INTO tasks (name, admin, power) VALUES ('project_groups_edit_access',TRUE, TRUE);
INSERT INTO tasks (name, admin, power) VALUES ('project_groups_send_email',TRUE, TRUE);
INSERT INTO tasks (name, admin, power) VALUES ('projects_table_view',TRUE, TRUE);
INSERT INTO tasks (name, admin, power) VALUES ('projects_edit',TRUE, FALSE);
INSERT INTO tasks (name, admin, power) VALUES ('projects_edit_plugins',TRUE, FALSE);
INSERT INTO tasks (name, admin, power) VALUES ('users_table_view',TRUE, TRUE);
INSERT INTO tasks (name, admin, power) VALUES ('users_edit',TRUE, TRUE);
INSERT INTO tasks (name, admin, power) VALUES ('users_delete',TRUE, TRUE);

CREATE TABLE roles (
	id serial PRIMARY KEY,
	name text UNIQUE,
	display_name text
);

CREATE TABLE users_roles (
	id serial PRIMARY KEY,
	user_id integer NOT NULL,
	role_id integer NOT NULL,
	client_id integer,
	project_group_id integer,
	CONSTRAINT "uc_users_roles" UNIQUE (user_id, project_group_id)
);
ALTER TABLE ONLY users_roles ADD CONSTRAINT users_roles_user_id_fkey FOREIGN KEY (user_id) REFERENCES users(user_id);
ALTER TABLE ONLY users_roles ADD CONSTRAINT users_roles_role_id_fkey FOREIGN KEY (role_id) REFERENCES roles(id);
ALTER TABLE ONLY users_roles ADD CONSTRAINT users_roles_client_id_fkey FOREIGN KEY (client_id) REFERENCES clients(id);
ALTER TABLE ONLY users_roles ADD CONSTRAINT users_roles_project_group_id_fkey FOREIGN KEY (project_group_id) REFERENCES project_groups(id);

CREATE TABLE login_attempts (
	id serial PRIMARY KEY,
	ip_address text,
	login text NOT NULL,
	time int
);

CREATE TABLE portal (
	id serial PRIMARY KEY,
	login_msg text
);

--NEW COLUMNS
ALTER TABLE users ADD COLUMN "ip_address" text;
ALTER TABLE users ADD COLUMN "activation_selector" text;
ALTER TABLE users ADD COLUMN "activation_code" text;
ALTER TABLE users ADD COLUMN "forgotten_password_selector" text;
ALTER TABLE users ADD COLUMN "forgotten_password_code" text;
ALTER TABLE users ADD COLUMN "forgotten_password_time" int;
ALTER TABLE users ADD COLUMN "remember_selector" text;
ALTER TABLE users ADD COLUMN "remember_code" text;
ALTER TABLE users ADD COLUMN "active" int;
ALTER TABLE users ADD COLUMN "first_name" text;
ALTER TABLE users ADD COLUMN "last_name" text;
ALTER TABLE users ADD COLUMN "phone" text;

ALTER TABLE projects ADD COLUMN project_group_id integer;
ALTER TABLE layers ADD COLUMN client_id integer;

ALTER TABLE ONLY layers ADD CONSTRAINT layers_client_id_fkey FOREIGN KEY (client_id) REFERENCES clients(id);

--updatable view for users table to match ion-auth
CREATE OR REPLACE VIEW users_auth AS
SELECT 
	user_id AS id,
	ip_address,
	user_name AS username,
	first_name || ' ' || last_name AS user_display_name,
	user_password_hash AS password,
	user_email AS email,
	activation_selector,
	activation_code,
	forgotten_password_selector,
	forgotten_password_code,
	forgotten_password_time,
	remember_selector,
	remember_code,
	registered,
	cast(extract(epoch from last_login) as integer) AS last_login,
	active,
	first_name,
	last_name,
	organization AS company,
	phone,
	lang,
	count_login
FROM users;

--DROP VIEW public.users_view;
CREATE OR REPLACE VIEW public.users_view AS 
 SELECT users.user_id,
    users.first_name,
    users.last_name,
    first_name || ' ' || last_name AS display_name,
    users.user_name,
    users.user_email,
    users.organization,
    users.registered,
    users.count_login,
    users.last_login,
    users.lang,
    users.active,
    users.phone,
        CASE
            WHEN adm.name = 'admin'::text THEN true
            ELSE false
        END AS admin,
    adm.filter,
    adm.scope,
    adm.id AS role_id,
    adm.name AS role_name,
    adm.display_name AS role_display_name,
        CASE
            WHEN groups.count IS NULL THEN 0::bigint
            ELSE groups.count
        END AS groups
   FROM users
     LEFT JOIN ( SELECT users_roles.user_id, client_id AS filter, (SELECT display_name FROM clients WHERE id=client_id) AS scope,
            roles.id, roles.name, roles.display_name
           FROM users_roles,
            roles
          WHERE users_roles.role_id = roles.id AND roles.id IN(1,2) AND users_roles.project_group_id IS NULL) adm ON users.user_id = adm.user_id
     LEFT JOIN ( SELECT users_roles.user_id,
            count(*) AS count
           FROM users_roles
          WHERE users_roles.role_id > 10
          GROUP BY users_roles.user_id) groups ON users.user_id = groups.user_id;

-- View: public.users_view_for_clients

-- DROP VIEW public.users_view_for_clients;

CREATE OR REPLACE VIEW public.users_view_for_clients AS 
 SELECT users.user_id,
    users.first_name,
    users.last_name,
    first_name || ' ' || last_name AS display_name,
    users.user_name,
    users.user_email,
    users.organization,
    users.registered,
    users.count_login,
    users.last_login,
    users.lang,
    users.active,
    users.phone,
        CASE
            WHEN adm.admin IS NULL THEN false
            ELSE adm.admin
        END AS admin,
    adm.filter,
    ( SELECT clients.display_name
           FROM clients
          WHERE clients.id = adm.filter) AS scope,
    adm.role_id,
    adm.role_name,
    adm.role_display_name,      
    adm.count AS groups
   FROM users
     LEFT JOIN ( SELECT data.user_id,
            sum(data.count) AS count,
            data.filter,
            data.role_id,
	    data.admin,
            data.role_name,
            data.role_display_name
           FROM ( SELECT users_roles.user_id,
                    0 AS count,
                    users_roles.client_id AS filter,
                        CASE
                            WHEN roles.name = 'admin'::text THEN true
                            ELSE false
                        END AS admin,
			roles.id AS role_id,
                        roles.name AS role_name,
                        roles.display_name AS role_display_name
                   FROM users_roles,
                    roles
                  WHERE users_roles.role_id = roles.id AND roles.id < 10 AND users_roles.project_group_id IS NULL
                UNION
                 SELECT ur.user_id,
                    count(*)::integer AS count,
                    g.client_id AS filter,
                    false AS admin,
		    9 AS role_id,
                    'link'::text AS role_name,
                    null::text AS role_display_name
                   FROM users_roles ur,
                    roles,
                    project_groups g
                  WHERE ur.role_id = roles.id AND ur.role_id > 10 AND ur.project_group_id = g.id
                  GROUP BY ur.user_id, g.client_id) data
          GROUP BY data.user_id, data.filter, data.role_id, data.admin, data.role_name, data.role_display_name) adm ON users.user_id = adm.user_id;

-- View: public.users_print_view

-- DROP VIEW public.users_print_view;

CREATE OR REPLACE VIEW public.users_print_view AS 
 SELECT users_print.user_name,
    users.user_email,
    (users.first_name || ' '::text) || users.last_name AS display_name,
    users_print.title,
    users_print.description,
    users_print.print_time,
    users.user_id
   FROM users,
    users_print
  WHERE users_print.user_name = users.user_name;

-- View: public.projects_view

DROP VIEW IF EXISTS public.projects_view;
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
    p.description,
    p.ordr,    
    p.project_path,
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
   JOIN clients c ON c.id = g.client_id;

-- View: public.clients_view

DROP VIEW IF EXISTS public.clients_view;
CREATE OR REPLACE VIEW public.clients_view AS 
 SELECT clients.id,
    clients.name,
    clients.display_name,
    clients.url,
    clients.description,
    clients.ordr,
        CASE
            WHEN sum.count IS NULL THEN 0
            ELSE sum.count::integer
        END AS count,
    (( SELECT count(*) AS count
           FROM project_groups
          WHERE project_groups.client_id = clients.id AND project_groups.type = 0))::integer AS count_groups,
    sum.project_group_ids
   FROM clients
     LEFT JOIN ( SELECT sum(g.count) AS count,
            sort(array_agg(g.project_group_id)) AS project_group_ids,
            project_groups.client_id
           FROM ( SELECT count(p.id) AS count,
                    p.project_group_id
                   FROM projects p
                  GROUP BY p.project_group_id) g,
            project_groups
          WHERE g.project_group_id = project_groups.id
          GROUP BY project_groups.client_id) sum ON clients.id = sum.client_id;

-- View: public.project_groups_view

-- DROP VIEW public.project_groups_view;

CREATE OR REPLACE VIEW public.project_groups_view AS 
 SELECT g.id,
    g.name,
    g.display_name,
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
	CASE WHEN g.contact_id IS NULL THEN g.contact ELSE u.first_name || ' ' || u.last_name END AS contact,	
	CASE WHEN g.contact_id IS NULL THEN g.contact_email ELSE u.user_email END AS contact_email,	
	CASE WHEN g.contact_id IS NULL THEN g.contact_phone ELSE u.phone END AS contact_phone,
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


CREATE OR REPLACE FUNCTION public.count_groups_for_layer(id integer)
  RETURNS integer AS
$BODY$
	select count(*)::integer from project_groups where (idx(base_layers_ids,$1) > 0 OR idx(extra_layers_ids,$1) > 0);
$BODY$
  LANGUAGE sql STABLE
  COST 10;


DROP VIEW IF EXISTS public.layers_view;

CREATE OR REPLACE VIEW public.layers_view AS 
 SELECT layers.id,
    layers.name,
    layers.display_name,
    layers.type,
        CASE
            WHEN count_groups_for_layer(layers.id) IS NULL THEN 0
            ELSE count_groups_for_layer(layers.id)
        END AS groups,
    clients.display_name AS client,
    clients.name AS client_name,
    clients.id AS client_id    
   FROM layers
   LEFT JOIN clients ON layers.client_id = clients.id;

     
--ROLES

INSERT INTO roles (id, name, display_name) VALUES (1,'admin','Administrator');
INSERT INTO roles (id, name, display_name) VALUES (2,'power','Power user');
INSERT INTO roles (id, name, display_name) VALUES (9,'link',null);
INSERT INTO roles (id, name, display_name) VALUES (20,'user','Project user (viewer)');
INSERT INTO roles (id, name, display_name) VALUES (21,'editor','Project editor');

--fill administrators based on admin field on users
INSERT INTO users_roles (user_id, role_id)
select user_id,1 from users where admin=true;

--make existing users active
UPDATE users SET active=1;

--split display_name into first and last. Not 100%, may need manual corrections!!!!
UPDATE users SET first_name = trim(left(display_name, length(display_name) - strpos(reverse(display_name),' ')));
UPDATE users SET last_name = trim(right(display_name,strpos(reverse(display_name),' ')));
--SELECT * FROM users;

--select projects to create groups
--first update null values to {}
UPDATE projects SET base_layers_ids='{}' WHERE base_layers_ids is null;
UPDATE projects SET base_layers_ids='{}' WHERE base_layers_ids = '{NULL}';
UPDATE projects SET extra_layers_ids='{}' WHERE extra_layers_ids is null;
UPDATE projects SET extra_layers_ids='{}' WHERE extra_layers_ids = '{NULL}';

-- ************************************************
-- ** OPTION TO CREATE ONE GROUP FOR ONE PROJECT **
-- ************************************************
	INSERT INTO project_groups(id, name, type, client_id, base_layers_ids, extra_layers_ids)
	SELECT id, name, 0::integer, client_id, base_layers_ids, extra_layers_ids
	FROM projects
	ORDER BY id;


	-- update projects with project_group_id (same as id because of query above)
	UPDATE projects SET project_group_id = id;

-- *******************************************
-- ** OPTION TO GROUP PROJECTS BY LAYER DEF **
-- *******************************************
	-- -- projects group by, use row_number as new project_group id
	-- INSERT INTO project_groups(id, name, type, client_id, base_layers_ids, extra_layers_ids)
	-- SELECT  num, (client || '_group_' || to_char(num,'fm000'))::text, 0::integer, client_id, base_layers_ids, extra_layers_ids
	-- FROM (
	-- 	SELECT ROW_NUMBER() OVER(ORDER BY client_id) AS num, g.*, c.name AS client
	-- 	FROM (
	-- 		SELECT client_id, array_agg(id), base_layers_ids, extra_layers_ids,count(*) 
	-- 		FROM projects 
	-- 		GROUP BY client_id, base_layers_ids, extra_layers_ids 
	-- 		ORDER BY client_id
	-- 	) g, clients c
	-- 	WHERE g.client_id = c.id
	-- ) p;
	-- 
	-- -- update projects with project_group_id use same query to get row_number
	-- UPDATE projects SET project_group_id = p2.group
	-- FROM
	-- (
	-- 	SELECT  num AS group, unnest(projects) AS project
	-- 	FROM (
	-- 		SELECT ROW_NUMBER() OVER(ORDER BY client_id) AS num, g.*, c.name AS client
	-- 		FROM (
	-- 			SELECT client_id, array_agg(id) AS projects, base_layers_ids, extra_layers_ids,count(*) 
	-- 			FROM projects 
	-- 			GROUP BY client_id, base_layers_ids, extra_layers_ids 
	-- 			ORDER BY client_id
	-- 		) g, clients c
	-- 		WHERE g.client_id = c.id
	-- 	) p 
	-- ) p2
	-- WHERE id=p2.project;

--insert into users_roles based on groups from user project_ids, default role 21=Editor
--select id,name,project_group_id from projects
INSERT INTO users_roles(user_id,role_id,project_group_id)
select distinct u.user_id,21::integer,p.project_group_id from (
select user_id,user_name, unnest(project_ids) AS project, project_ids from users) u, projects p
where u.project = p.id;

--test
--select project_group_id, count(*),array_agg(id) from projects group by project_group_id order by project_group_id

--NEW DB CONSTRAINTS
ALTER TABLE users ADD CONSTRAINT "uc_activation_selector" UNIQUE ("activation_selector");
ALTER TABLE users ADD CONSTRAINT "uc_forgotten_password_selector" UNIQUE ("forgotten_password_selector");
ALTER TABLE users ADD CONSTRAINT "uc_remember_selector" UNIQUE ("remember_selector");
ALTER TABLE users ADD CONSTRAINT "check_active" CHECK(active >= 0);

ALTER TABLE projects ALTER COLUMN project_group_id SET NOT NULL;
ALTER TABLE ONLY projects ADD CONSTRAINT projects_project_group_id_fkey FOREIGN KEY (project_group_id) REFERENCES project_groups(id);

--UPDATE SEQUENCES
select setval('project_groups_id_seq',(select max(id) from project_groups));
select setval('users_roles_id_seq',(select max(id) from users_roles));


--UPDATE OR NEW functions
CREATE OR REPLACE FUNCTION get_child_groups(id integer)
RETURNS integer[] AS
$$
declare ret integer[];
declare myrow record;
begin
FOR myrow IN SELECT p.id,p.type FROM project_groups p WHERE parent_id=$1 LOOP
	IF myrow.type = 0 THEN SELECT array_append(ret,myrow.id) INTO ret; 
	ELSIF myrow.type = 1 THEN SELECT array_cat(ret,get_child_groups(myrow.id)) INTO ret;
	END IF;
END LOOP;

return ret;
END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION get_child_menus(id integer)
RETURNS integer[] AS
$$
declare ret integer[];
declare myrow record;
begin
FOR myrow IN SELECT p.id,p.type FROM project_groups p WHERE parent_id=$1 LOOP
	IF myrow.type = 1 THEN 
		SELECT array_append(ret,myrow.id) INTO ret; 
		SELECT array_cat(ret,get_child_menus(myrow.id)) INTO ret;
	END IF;
END LOOP;

return ret;
END;
$$ LANGUAGE plpgsql;


DROP FUNCTION check_user_project(text,text);
CREATE OR REPLACE FUNCTION check_user_project(uname text, project text) RETURNS TABLE (check_user_project text, role text)
LANGUAGE plpgsql COST 1
AS $_$
declare projid integer;
declare groupid integer;
declare clientid integer;
declare is_public boolean;
declare role text;
begin
projid:=0;
role:=null;
select p.id,public,project_group_id,g.client_id from projects p,project_groups g where p.project_group_id = g.id and p.name=$2 into projid,is_public,groupid,clientid;

--RAISE NOTICE '%', projid;
if projid=0 OR projid IS NULL then
	RETURN QUERY SELECT 'TR.noProject'::text,role;
else
	if lower($1) = 'guest' then
			if is_public = true then RETURN QUERY SELECT 'OK'::text,'public'::text;
			else RETURN QUERY SELECT 'TR.noPublicAccess'::text,role; 
			end if;
	else
			--first check if user is (client) administrator
			select roles.name 
			from users,users_roles,roles 
			where users.user_id=users_roles.user_id AND users_roles.role_id=roles.id AND roles.name='admin' AND
			user_name=$1 and  ((client_id is null and project_group_id is null) or (client_id=clientid and project_group_id is null)) INTO role;
			if role > '' then
				--RAISE NOTICE 'admin, proj:%, client:%', projid, clientid;
				RETURN QUERY SELECT 'OK'::text, role;
			else
				select roles.name 
				from users,users_roles,roles 
				where users.user_id=users_roles.user_id AND users_roles.role_id=roles.id AND 
				user_name=$1 and project_group_id=groupid INTO role;
				if role > '' then
					--RAISE NOTICE 'user, group:%, client:%', groupid, clientid;
					RETURN QUERY SELECT 'OK'::text, role;
				else
					if is_public = true then RETURN QUERY SELECT 'OK'::text,'public'::text;
					else RETURN QUERY SELECT 'TR.noPermission'::text, role;
					end if;
				end if;
			end if;
	end if;
end if;
end;
$_$;

COMMENT ON FUNCTION check_user_project(uname text, project text) IS 'IN uname, project --> validates project, user and user permissions and role on project';

DROP FUNCTION IF EXISTS public.get_project_data(text);
CREATE OR REPLACE FUNCTION public.get_project_data(project text)
  RETURNS TABLE(client_id integer, client_name text, client_display_name text, client_url text, theme_name text, overview_layer json, base_layers json, extra_layers json, tables_onstart text[], is_public boolean, project_id integer, project_name text, project_display_name text, crs text, description text, contact text, restrict_to_start_extent boolean, geolocation boolean, feedback boolean, measurements boolean, print boolean, zoom_back_forward boolean, identify_mode boolean, permalink boolean, feedback_email text, project_path text, plugins text[])
LANGUAGE 'plpgsql'

COST 1
VOLATILE
ROWS 1000
AS $BODY$

declare base json;
declare overview json;
declare extra json;
declare plugins text[];
begin
  base:=null;
  overview:=null;

  SELECT json_agg(json_build_object('type',layers.type,'definition',layers.definition,'name',layers.name,'title',layers.display_name))
  FROM
    (SELECT layers.* FROM projects,layers,project_groups where projects.project_group_id = project_groups.id AND layers.id = ANY(project_groups.base_layers_ids) AND projects.name=$1 ORDER BY idx(project_groups.base_layers_ids, layers.id)) AS layers INTO base;

  SELECT json_agg(json_build_object('type',layers.type,'definition',layers.definition,'name',layers.name,'title',layers.display_name))
  FROM
    (SELECT layers.* FROM projects,layers,project_groups where projects.project_group_id = project_groups.id AND layers.id = ANY(project_groups.extra_layers_ids) AND projects.name=$1 ORDER BY idx(project_groups.extra_layers_ids, layers.id)) AS layers INTO extra;

  SELECT array_agg(plugins.name) from projects,plugins WHERE plugins.id = ANY(projects.plugin_ids) AND projects.name=$1 INTO plugins;

  SELECT json_agg(json_build_object('type',layers.type,'definition',layers.definition,'name',layers.name,'title',layers.display_name))
  FROM projects,layers where layers.id = projects.overview_layer_id and projects.name=$1 INTO overview;

 RETURN QUERY SELECT
                 clients.id,
                 clients.name,
                 clients.display_name,
                 clients.url,
                 themes.name,
                 overview,
                 base,
                 extra,
                 projects.tables_onstart,
                 projects.public,
                 projects.id,
                 projects.name,
                 projects.display_name,
                 projects.crs,
                 projects.description,
                 projects.contact,
                 projects.restrict_to_start_extent,
                 projects.geolocation,
                 projects.feedback,
                 projects.measurements,
                 projects.print,
                 projects.zoom_back_forward,
                 projects.identify_mode,
                 projects.permalink,
                 projects.feedback_email,
                 projects.project_path,
                 plugins

     FROM projects,clients,themes WHERE clients.theme_id=themes.id AND projects.client_id = clients.id AND projects.name=$1;
end;

$BODY$;

COMMENT ON FUNCTION public.get_project_data(text)
IS 'IN project --> client, theme, baselayers, overview layer, extra layers and tables_onstart for project_name.';
