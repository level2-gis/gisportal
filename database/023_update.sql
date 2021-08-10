--gisapp upgrade script v24
--this is optional update for using dynamic mask filters
--adds postgis extension if not already in database

CREATE EXTENSION IF NOT EXISTS postgis;

INSERT INTO settings (version, date) VALUES (24, now());

ALTER TABLE users_roles ADD COLUMN mask_filter text;
ALTER TABLE users_roles ADD COLUMN mask_geom geometry;

DROP FUNCTION public.check_user_project(text, text);

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
			--first check if user is (client) administrator
			select roles.name
			from users,users_roles,roles
			where users.user_id=users_roles.user_id AND users_roles.role_id=roles.id AND roles.name='admin' AND
			user_name=$1 and  ((client_id is null and project_group_id is null) or (client_id=clientid and project_group_id is null)) INTO role;
			if role > '' then
				--RAISE NOTICE 'admin, proj:%, client:%', projid, clientid;
				RETURN QUERY SELECT 'OK'::text, role, null, null;
			else
				select roles.name, ur.mask_filter, st_astext(ur.mask_geom)
				from users,users_roles ur,roles
				where users.user_id=ur.user_id AND ur.role_id=roles.id AND
				user_name=$1 and project_group_id=groupid INTO role, mask_f, mask_w;
				if role > '' then
					--RAISE NOTICE 'user, group:%, client:%', groupid, clientid;
					RETURN QUERY SELECT 'OK'::text, role, mask_f, mask_w;
				else
					if is_public = true then RETURN QUERY SELECT 'OK'::text,'public'::text;
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
