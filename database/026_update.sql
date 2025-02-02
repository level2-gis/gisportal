--gisapp upgrade script v27

INSERT INTO settings (version, date) VALUES (27, now());

ALTER TABLE users ADD COLUMN receive_system_emails boolean NOT NULL DEFAULT true;

CREATE OR REPLACE FUNCTION public.check_user_project(
    IN uname text,
    IN project text)
  RETURNS TABLE(check_user_project text, role text, mask_filter text, mask_wkt text) AS
$BODY$
declare projid integer;
declare groupid integer;
declare clientid integer;
declare is_public boolean;
declare role text;
declare mask integer;
declare mask_f text;
declare mask_w text;
begin
projid:=0;
role:=null;
select p.id,public,project_group_id,g.client_id from projects p,project_groups g where p.project_group_id = g.id and p.name=$2 into projid,is_public,groupid,clientid;

--RAISE NOTICE '%', projid;
if projid=0 OR projid IS NULL then
	RETURN QUERY SELECT 'TR.noProject'::text,role, null, null;
else
	if lower($1) = 'guest' then
			if is_public = true then RETURN QUERY SELECT 'OK'::text,'public'::text, null, null;
			else RETURN QUERY SELECT 'TR.noPublicAccess'::text,role, null, null;
			end if;
	else
			--first check if user is (client) administrator/power user
			select roles.name
			from users,users_roles,roles
			where users.user_id=users_roles.user_id AND users_roles.role_id=roles.id AND roles.id<9 AND
			user_name=$1 and  ((client_id is null and project_group_id is null) or (client_id=clientid and project_group_id is null)) INTO role;
			if role > '' then
				--RAISE NOTICE 'admin, proj:%, client:%', projid, clientid;
				RETURN QUERY SELECT 'OK'::text, role, null, null;
			else
				select roles.name, ur.mask_id
				from users,users_roles ur,roles
				where users.user_id=ur.user_id AND ur.role_id=roles.id AND
				user_name=$1 and project_group_id=groupid INTO role, mask;
				if role > '' then
				    IF mask IS NOT NULL THEN
				        SELECT filter, geom_wkt FROM MASKS where id = mask INTO mask_f, mask_w;
				    END IF;
					--RAISE NOTICE 'user, group:%, client:%', groupid, clientid;
					RETURN QUERY SELECT 'OK'::text, role, mask_f, mask_w;
				else
					if is_public = true then RETURN QUERY SELECT 'OK'::text,'public'::text, null, null;
					else RETURN QUERY SELECT 'TR.noPermission'::text, role, null, null;
					end if;
				end if;
			end if;
	end if;
end if;
end;
$BODY$
  LANGUAGE plpgsql VOLATILE
  COST 1
  ROWS 1000;

DROP VIEW public.users_view;
CREATE VIEW public.users_view AS
 SELECT users.user_id,
    users.first_name,
    users.last_name,
    ((users.first_name || ' '::text) || users.last_name) AS display_name,
    users.user_name,
    users.user_email,
    users.organization,
    users.registered,
    users.count_login,
    users.last_login,
    users.lang,
    users.active,
    users.phone,
    users.receive_system_emails,
        CASE
            WHEN adm.id < 9 THEN true
            ELSE false
        END AS admin,
    adm.filter,
    adm.scope,
    adm.id AS role_id,
    adm.name AS role_name,
    adm.display_name AS role_display_name,
        CASE
            WHEN (groups.count IS NULL) THEN (0)::bigint
            ELSE groups.count
        END AS groups
   FROM ((public.users
     LEFT JOIN ( SELECT users_roles.user_id,
            users_roles.client_id AS filter,
            ( SELECT clients.display_name
                   FROM public.clients
                  WHERE (clients.id = users_roles.client_id)) AS scope,
            roles.id,
            roles.name,
            roles.display_name
           FROM public.users_roles,
            public.roles
          WHERE ((users_roles.role_id = roles.id) AND (roles.id = ANY (ARRAY[1, 2])) AND (users_roles.project_group_id IS NULL))) adm ON ((users.user_id = adm.user_id)))
     LEFT JOIN ( SELECT users_roles.user_id,
            count(*) AS count
           FROM public.users_roles
          WHERE (users_roles.role_id > 10)
          GROUP BY users_roles.user_id) groups ON ((users.user_id = groups.user_id)));

DROP VIEW public.users_view_for_clients;
CREATE VIEW public.users_view_for_clients AS
 SELECT users.user_id,
    users.first_name,
    users.last_name,
    ((users.first_name || ' '::text) || users.last_name) AS display_name,
    users.user_name,
    users.user_email,
    users.organization,
    users.registered,
    users.count_login,
    users.last_login,
    users.lang,
    users.active,
    users.phone,
    users.receive_system_emails,
    adm.admin,
    adm.filter,
    ( SELECT clients.display_name
           FROM public.clients
          WHERE (clients.id = adm.filter)) AS scope,
    adm.role_id,
    adm.role_name,
    adm.role_display_name,
    adm.count AS groups
   FROM (public.users
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
                            WHEN roles.id < 9 THEN true
                            ELSE false
                        END AS admin,
                    roles.id AS role_id,
                    roles.name AS role_name,
                    roles.display_name AS role_display_name
                   FROM public.users_roles,
                    public.roles
                  WHERE ((users_roles.role_id = roles.id) AND (roles.id < 10) AND (users_roles.project_group_id IS NULL))
                UNION
                 SELECT ur.user_id,
                    (count(*))::integer AS count,
                    g.client_id AS filter,
                    false AS admin,
                    9 AS role_id,
                    'link'::text AS role_name,
                    NULL::text AS role_display_name
                   FROM public.users_roles ur,
                    public.roles,
                    public.project_groups g
                  WHERE ((ur.role_id = roles.id) AND (ur.role_id > 10) AND (ur.project_group_id = g.id))
                  GROUP BY ur.user_id, g.client_id) data
          GROUP BY data.user_id, data.filter, data.role_id, data.admin, data.role_name, data.role_display_name) adm ON ((users.user_id = adm.user_id)));
