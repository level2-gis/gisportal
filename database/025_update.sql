--gisapp upgrade script v25

INSERT INTO settings (version, date) VALUES (26, now());

ALTER TABLE users_roles DROP COLUMN IF EXISTS mask_filter;
ALTER TABLE users_roles DROP COLUMN IF EXISTS mask_geom;

CREATE TABLE masks
(
    id serial PRIMARY KEY,
    display_name text,
    filter text,
    geom_wkt text,
    client_id integer
);
ALTER TABLE masks ADD FOREIGN KEY (client_id) REFERENCES clients(id);
ALTER TABLE users_roles ADD COLUMN mask_id integer;
ALTER TABLE users_roles ADD FOREIGN KEY (mask_id) REFERENCES masks(id);


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
			--first check if user is (client) administrator
			select roles.name
			from users,users_roles,roles
			where users.user_id=users_roles.user_id AND users_roles.role_id=roles.id AND roles.name='admin' AND
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
