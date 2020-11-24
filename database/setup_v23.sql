--
-- PostgreSQL database dump
--

-- Dumped from database version 9.5.14
-- Dumped by pg_dump version 9.5.14

-- Started on 2019-07-05 10:12:14 CEST

SET statement_timeout = 0;
SET lock_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET client_min_messages = warning;
SET row_security = off;

--
-- TOC entry 1 (class 3079 OID 12395)
-- Name: plpgsql; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS plpgsql WITH SCHEMA pg_catalog;


--
-- TOC entry 2472 (class 0 OID 0)
-- Dependencies: 1
-- Name: EXTENSION plpgsql; Type: COMMENT; Schema: -; Owner: -
--

COMMENT ON EXTENSION plpgsql IS 'PL/pgSQL procedural language';


--
-- TOC entry 2 (class 3079 OID 207509)
-- Name: intarray; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS intarray WITH SCHEMA public;


--
-- TOC entry 2473 (class 0 OID 0)
-- Dependencies: 2
-- Name: EXTENSION intarray; Type: COMMENT; Schema: -; Owner: -
--

COMMENT ON EXTENSION intarray IS 'functions, operators, and index support for 1-D arrays of integers';


--
-- TOC entry 277 (class 1255 OID 207923)
-- Name: check_user_project(text, text); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.check_user_project(uname text, project text) RETURNS TABLE(check_user_project text, role text)
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


--
-- TOC entry 2474 (class 0 OID 0)
-- Dependencies: 277
-- Name: FUNCTION check_user_project(uname text, project text); Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON FUNCTION public.check_user_project(uname text, project text) IS 'IN uname, project --> validates project, user and user permissions and role on project';


--
-- TOC entry 274 (class 1255 OID 207904)
-- Name: count_groups_for_layer(integer); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.count_groups_for_layer(id integer) RETURNS integer
    LANGUAGE sql STABLE COST 10
    AS $_$
	select count(*)::integer from project_groups where (idx(base_layers_ids,$1) > 0 OR idx(extra_layers_ids,$1) > 0);
$_$;


--
-- TOC entry 275 (class 1255 OID 207921)
-- Name: get_child_groups(integer); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.get_child_groups(id integer) RETURNS integer[]
    LANGUAGE plpgsql
    AS $_$
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
$_$;


--
-- TOC entry 276 (class 1255 OID 207922)
-- Name: get_child_menus(integer); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.get_child_menus(id integer) RETURNS integer[]
    LANGUAGE plpgsql
    AS $_$
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
$_$;


--
-- TOC entry 278 (class 1255 OID 207924)
-- Name: get_project_data(text); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.get_project_data(project text)
  RETURNS TABLE(client_id integer, client_name text, client_display_name text, client_url text, theme_name text, overview_layer json, base_layers json, extra_layers json, tables_onstart text[], is_public boolean, project_id integer, project_name text, project_display_name text, crs text, description text, restrict_to_start_extent boolean, geolocation boolean, feedback boolean, measurements boolean, print boolean, zoom_back_forward boolean, identify_mode boolean, permalink boolean, feedback_email text, project_path text, plugins text[], custom1 text, custom2 text)
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
                 plugins,
                 g.custom1,
                 g.custom2

     FROM projects,clients,themes,project_groups g WHERE clients.theme_id=themes.id AND projects.client_id = clients.id AND projects.project_group_id = g.id AND projects.name=$1;
end;

$BODY$;

COMMENT ON FUNCTION public.get_project_data(text)
IS 'IN project --> client, theme, baselayers, overview layer, extra layers and tables_onstart for project_name.';


SET default_tablespace = '';

SET default_with_oids = false;

--
-- TOC entry 182 (class 1259 OID 207622)
-- Name: clients; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.clients (
    id integer NOT NULL,
    name text NOT NULL,
    display_name text,
    theme_id integer DEFAULT 1 NOT NULL,
    url text,
    description text,
    ordr integer DEFAULT 0 NOT NULL
);


--
-- TOC entry 183 (class 1259 OID 207630)
-- Name: clients_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.clients_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 2476 (class 0 OID 0)
-- Dependencies: 183
-- Name: clients_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.clients_id_seq OWNED BY public.clients.id;


--
-- TOC entry 199 (class 1259 OID 207769)
-- Name: project_groups; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.project_groups (
    id integer NOT NULL,
    name text NOT NULL,
    display_name text,
    parent_id integer,
    type integer NOT NULL,
    client_id integer NOT NULL,
    ordr integer DEFAULT 0 NOT NULL,
    base_layers_ids integer[],
    extra_layers_ids integer[],
    contact text,
    contact_id integer,
    contact_phone text,
    contact_email text,
    custom1 text,
    custom2 text
);


--
-- TOC entry 186 (class 1259 OID 207640)
-- Name: projects; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.projects (
    id integer NOT NULL,
    name text NOT NULL,
    display_name text,
    crs text,
    description text,
    overview_layer_id integer,
    client_id integer NOT NULL,
    tables_onstart text[],
    public boolean DEFAULT false NOT NULL,
    restrict_to_start_extent boolean DEFAULT false NOT NULL,
    geolocation boolean DEFAULT true NOT NULL,
    feedback boolean DEFAULT true NOT NULL,
    measurements boolean DEFAULT true NOT NULL,
    print boolean DEFAULT true NOT NULL,
    zoom_back_forward boolean DEFAULT true NOT NULL,
    identify_mode boolean DEFAULT false NOT NULL,
    permalink boolean DEFAULT true NOT NULL,
    feedback_email text,
    project_path text,
    ordr integer DEFAULT 0 NOT NULL,
    plugin_ids integer[],
    project_group_id integer NOT NULL,
    version text
);


--
-- TOC entry 214 (class 1259 OID 207894)
-- Name: clients_view; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW public.clients_view AS
 SELECT clients.id,
    clients.name,
    clients.display_name,
    clients.url,
    clients.description,
    clients.ordr,
        CASE
            WHEN (sum.count IS NULL) THEN 0
            ELSE (sum.count)::integer
        END AS count,
    (( SELECT count(*) AS count
           FROM public.project_groups
          WHERE ((project_groups.client_id = clients.id) AND (project_groups.type = 0))))::integer AS count_groups,
    sum.project_group_ids
   FROM (public.clients
     LEFT JOIN ( SELECT sum(g.count) AS count,
            public.sort(array_agg(g.project_group_id)) AS project_group_ids,
            project_groups.client_id
           FROM ( SELECT count(p.id) AS count,
                    p.project_group_id
                   FROM public.projects p
                  GROUP BY p.project_group_id) g,
            public.project_groups
          WHERE (g.project_group_id = project_groups.id)
          GROUP BY project_groups.client_id) sum ON ((clients.id = sum.client_id)));


--
-- TOC entry 184 (class 1259 OID 207632)
-- Name: layers; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.layers (
    id integer NOT NULL,
    name text NOT NULL,
    display_name text,
    type text NOT NULL,
    definition text NOT NULL,
    client_id integer
);


--
-- TOC entry 185 (class 1259 OID 207638)
-- Name: layers_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.layers_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 2477 (class 0 OID 0)
-- Dependencies: 185
-- Name: layers_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.layers_id_seq OWNED BY public.layers.id;


--
-- TOC entry 216 (class 1259 OID 207905)
-- Name: layers_view; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW public.layers_view AS
 SELECT layers.id,
    layers.name,
    layers.display_name,
    layers.type,
        CASE
            WHEN (public.count_groups_for_layer(layers.id) IS NULL) THEN 0
            ELSE public.count_groups_for_layer(layers.id)
        END AS groups,
    clients.display_name AS client,
    clients.name AS client_name,
    clients.id AS client_id
   FROM (public.layers
     LEFT JOIN public.clients ON ((layers.client_id = clients.id)));


--
-- TOC entry 207 (class 1259 OID 207849)
-- Name: login_attempts; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.login_attempts (
    id integer NOT NULL,
    ip_address text,
    login text NOT NULL,
    "time" integer
);


--
-- TOC entry 206 (class 1259 OID 207847)
-- Name: login_attempts_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.login_attempts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 2478 (class 0 OID 0)
-- Dependencies: 206
-- Name: login_attempts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.login_attempts_id_seq OWNED BY public.login_attempts.id;


--
-- TOC entry 193 (class 1259 OID 207679)
-- Name: plugins; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.plugins (
    id integer NOT NULL,
    name text NOT NULL,
    description text,
    active boolean NOT NULL DEFAULT true
);


--
-- TOC entry 192 (class 1259 OID 207677)
-- Name: plugins_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.plugins_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 2479 (class 0 OID 0)
-- Dependencies: 192
-- Name: plugins_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.plugins_id_seq OWNED BY public.plugins.id;


--
-- TOC entry 209 (class 1259 OID 207860)
-- Name: portal; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.portal (
    id integer NOT NULL,
    login_msg text
);


--
-- TOC entry 208 (class 1259 OID 207858)
-- Name: portal_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.portal_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 2480 (class 0 OID 0)
-- Dependencies: 208
-- Name: portal_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.portal_id_seq OWNED BY public.portal.id;


--
-- TOC entry 198 (class 1259 OID 207767)
-- Name: project_groups_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.project_groups_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 2481 (class 0 OID 0)
-- Dependencies: 198
-- Name: project_groups_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.project_groups_id_seq OWNED BY public.project_groups.id;


--
-- TOC entry 191 (class 1259 OID 207669)
-- Name: users; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.users (
    user_id integer NOT NULL,
    user_name text,
    user_password_hash text,
    user_email text,
    last_login timestamp with time zone,
    registered timestamp with time zone,
    count_login integer DEFAULT 0,
    lang text,
    organization text,
    ip_address text,
    activation_selector text,
    activation_code text,
    forgotten_password_selector text,
    forgotten_password_code text,
    forgotten_password_time integer,
    remember_selector text,
    remember_code text,
    active integer,
    first_name text,
    last_name text,
    phone text,
    CONSTRAINT check_active CHECK ((active >= 0))
);


--
-- TOC entry 205 (class 1259 OID 207819)
-- Name: users_roles; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.users_roles (
    id integer NOT NULL,
    user_id integer NOT NULL,
    role_id integer NOT NULL,
    client_id integer,
    project_group_id integer
);


--
-- TOC entry 215 (class 1259 OID 207899)
-- Name: project_groups_view; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW public.project_groups_view AS
 SELECT g.id,
    g.name,
    g.display_name,
    g.parent_id,
    ( SELECT project_groups.name
           FROM public.project_groups
          WHERE (project_groups.id = g.parent_id)) AS parent,
    g.type,
    g.client_id,
    c.display_name AS client,
    c.name AS client_name,
    p.project_crs,
        CASE
            WHEN (p.count IS NULL) THEN (0)::bigint
            ELSE p.count
        END AS projects,
        CASE
            WHEN (public.icount(g.base_layers_ids) IS NULL) THEN 0
            ELSE public.icount(g.base_layers_ids)
        END AS base_layers,
        CASE
            WHEN (public.icount(g.extra_layers_ids) IS NULL) THEN 0
            ELSE public.icount(g.extra_layers_ids)
        END AS extra_layers,
        CASE
            WHEN (ur.count IS NULL) THEN (0)::bigint
            ELSE ur.count
        END AS users,
    g.contact_id,
        CASE
            WHEN (g.contact_id IS NULL) THEN g.contact
            ELSE ((u.first_name || ' '::text) || u.last_name)
        END AS contact,
        CASE
            WHEN (g.contact_id IS NULL) THEN g.contact_email
            ELSE u.user_email
        END AS contact_email,
        CASE
            WHEN (g.contact_id IS NULL) THEN g.contact_phone
            ELSE u.phone
        END AS contact_phone,
    g.custom1,
    g.custom2
   FROM ((((public.project_groups g
     JOIN public.clients c ON ((g.client_id = c.id)))
     LEFT JOIN ( SELECT count(p_1.id) AS count,
            string_agg(DISTINCT p_1.crs, ','::text) AS project_crs,
            p_1.project_group_id
           FROM public.projects p_1
          GROUP BY p_1.project_group_id) p ON ((p.project_group_id = g.id)))
     LEFT JOIN ( SELECT users_roles.project_group_id,
            count(*) AS count
           FROM public.users_roles
          GROUP BY users_roles.project_group_id) ur ON ((ur.project_group_id = g.id)))
     LEFT JOIN public.users u ON ((g.contact_id = u.user_id)));


--
-- TOC entry 187 (class 1259 OID 207656)
-- Name: projects_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.projects_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 2482 (class 0 OID 0)
-- Dependencies: 187
-- Name: projects_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.projects_id_seq OWNED BY public.projects.id;


--
-- TOC entry 213 (class 1259 OID 207889)
-- Name: projects_view; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW public.projects_view AS
 SELECT p.id,
    p.name,
    g.client_id,
    p.public,
        CASE
            WHEN (p.display_name IS NULL) THEN p.name
            ELSE p.display_name
        END AS display_name,
    p.crs,
    p.description,
    p.ordr,
    p.project_path,
    c.display_name AS client,
    c.name AS client_name,
        CASE
            WHEN (g.display_name IS NULL) THEN g.name
            ELSE (((g.display_name || ' ('::text) || g.name) || ')'::text)
        END AS "group",
    g.id AS group_id,
    g.name AS group_name,
    p.overview_layer_id,
    ( SELECT layers.display_name
           FROM public.layers
          WHERE (layers.id = p.overview_layer_id)) AS overview_layer
   FROM ((public.projects p
     JOIN public.project_groups g ON ((g.id = p.project_group_id)))
     JOIN public.clients c ON ((c.id = g.client_id)));


--
-- TOC entry 203 (class 1259 OID 207806)
-- Name: roles; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.roles (
    id integer NOT NULL,
    name text,
    display_name text
);


--
-- TOC entry 202 (class 1259 OID 207804)
-- Name: roles_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.roles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 2483 (class 0 OID 0)
-- Dependencies: 202
-- Name: roles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.roles_id_seq OWNED BY public.roles.id;


--
-- TOC entry 188 (class 1259 OID 207658)
-- Name: settings; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.settings (
    version integer NOT NULL,
    date date
);


--
-- TOC entry 201 (class 1259 OID 207793)
-- Name: tasks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tasks (
    id integer NOT NULL,
    name text NOT NULL,
    admin boolean NOT NULL,
    power boolean NOT NULL
);


--
-- TOC entry 200 (class 1259 OID 207791)
-- Name: tasks_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.tasks_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 2484 (class 0 OID 0)
-- Dependencies: 200
-- Name: tasks_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.tasks_id_seq OWNED BY public.tasks.id;


--
-- TOC entry 189 (class 1259 OID 207661)
-- Name: themes; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.themes (
    id integer NOT NULL,
    name text NOT NULL
);


--
-- TOC entry 190 (class 1259 OID 207667)
-- Name: themes_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.themes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 2485 (class 0 OID 0)
-- Dependencies: 190
-- Name: themes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.themes_id_seq OWNED BY public.themes.id;


--
-- TOC entry 210 (class 1259 OID 207874)
-- Name: users_auth; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW public.users_auth AS
 SELECT users.user_id AS id,
    users.ip_address,
    users.user_name AS username,
    ((users.first_name || ' '::text) || users.last_name) AS user_display_name,
    users.user_password_hash AS password,
    users.user_email AS email,
    users.activation_selector,
    users.activation_code,
    users.forgotten_password_selector,
    users.forgotten_password_code,
    users.forgotten_password_time,
    users.remember_selector,
    users.remember_code,
    users.registered,
    (date_part('epoch'::text, users.last_login))::integer AS last_login,
    users.active,
    users.first_name,
    users.last_name,
    users.organization AS company,
    users.phone,
    users.lang,
    users.count_login
   FROM public.users;


--
-- TOC entry 195 (class 1259 OID 207745)
-- Name: users_print_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.users_print_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 196 (class 1259 OID 207747)
-- Name: users_print; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.users_print (
    id integer DEFAULT nextval('public.users_print_id_seq'::regclass) NOT NULL,
    user_name text,
    title text,
    description text,
    print_time timestamp with time zone DEFAULT now(),
    project text
);


--
-- TOC entry 197 (class 1259 OID 207762)
-- Name: users_print_view; Type: VIEW; Schema: public; Owner: -
--

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


--
-- TOC entry 204 (class 1259 OID 207817)
-- Name: users_roles_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.users_roles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 2486 (class 0 OID 0)
-- Dependencies: 204
-- Name: users_roles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.users_roles_id_seq OWNED BY public.users_roles.id;


--
-- TOC entry 194 (class 1259 OID 207690)
-- Name: users_user_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.users_user_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 2487 (class 0 OID 0)
-- Dependencies: 194
-- Name: users_user_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.users_user_id_seq OWNED BY public.users.user_id;


--
-- TOC entry 211 (class 1259 OID 207879)
-- Name: users_view; Type: VIEW; Schema: public; Owner: -
--

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
        CASE
            WHEN (adm.name = 'admin'::text) THEN true
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


--
-- TOC entry 212 (class 1259 OID 207884)
-- Name: users_view_for_clients; Type: VIEW; Schema: public; Owner: -
--

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
        CASE
            WHEN (adm.admin IS NULL) THEN false
            ELSE adm.admin
        END AS admin,
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
                            WHEN (roles.name = 'admin'::text) THEN true
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


--
-- TOC entry 2220 (class 2604 OID 207692)
-- Name: id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.clients ALTER COLUMN id SET DEFAULT nextval('public.clients_id_seq'::regclass);


--
-- TOC entry 2221 (class 2604 OID 207693)
-- Name: id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.layers ALTER COLUMN id SET DEFAULT nextval('public.layers_id_seq'::regclass);


--
-- TOC entry 2245 (class 2604 OID 207852)
-- Name: id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.login_attempts ALTER COLUMN id SET DEFAULT nextval('public.login_attempts_id_seq'::regclass);


--
-- TOC entry 2237 (class 2604 OID 207682)
-- Name: id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.plugins ALTER COLUMN id SET DEFAULT nextval('public.plugins_id_seq'::regclass);


--
-- TOC entry 2246 (class 2604 OID 207863)
-- Name: id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.portal ALTER COLUMN id SET DEFAULT nextval('public.portal_id_seq'::regclass);


--
-- TOC entry 2240 (class 2604 OID 207772)
-- Name: id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_groups ALTER COLUMN id SET DEFAULT nextval('public.project_groups_id_seq'::regclass);


--
-- TOC entry 2232 (class 2604 OID 207694)
-- Name: id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.projects ALTER COLUMN id SET DEFAULT nextval('public.projects_id_seq'::regclass);


--
-- TOC entry 2243 (class 2604 OID 207809)
-- Name: id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles ALTER COLUMN id SET DEFAULT nextval('public.roles_id_seq'::regclass);


--
-- TOC entry 2242 (class 2604 OID 207796)
-- Name: id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tasks ALTER COLUMN id SET DEFAULT nextval('public.tasks_id_seq'::regclass);


--
-- TOC entry 2233 (class 2604 OID 207695)
-- Name: id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.themes ALTER COLUMN id SET DEFAULT nextval('public.themes_id_seq'::regclass);


--
-- TOC entry 2235 (class 2604 OID 207696)
-- Name: user_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users ALTER COLUMN user_id SET DEFAULT nextval('public.users_user_id_seq'::regclass);


--
-- TOC entry 2244 (class 2604 OID 207822)
-- Name: id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users_roles ALTER COLUMN id SET DEFAULT nextval('public.users_roles_id_seq'::regclass);


--
-- TOC entry 2437 (class 0 OID 207622)
-- Dependencies: 182
-- Data for Name: clients; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.clients VALUES (1, 'demo', 'DEMO', 1, 'http://www.level2.si', NULL, 0);


--
-- TOC entry 2488 (class 0 OID 0)
-- Dependencies: 183
-- Name: clients_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.clients_id_seq', 1, false);


--
-- TOC entry 2439 (class 0 OID 207632)
-- Dependencies: 184
-- Data for Name: layers; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.layers VALUES (4, 'osm_mapnik', 'OpenStreetMap', 'OSM', '{"numZoomLevels": 20, "serverResolutions": [156543.03390625, 78271.516953125, 39135.7584765625, 19567.87923828125, 9783.939619140625, 4891.9698095703125, 2445.9849047851562, 1222.9924523925781, 611.4962261962891, 305.74811309814453, 152.87405654907226, 76.43702827453613, 38.218514137268066, 19.109257068634033, 9.554628534317017, 4.777314267158508, 2.388657133579254, 1.194328566789627, 0.5971642833948135, 0.29858214169740677]}', NULL);
INSERT INTO public.layers VALUES (2, 'google_sat', 'Google Satellite', 'Google', '{
    "type": "satellite",
    "numZoomLevels": 20,
    "isBaseLayer": true,
    "useTiltImages": false
}', NULL);
INSERT INTO public.layers VALUES (1, 'google_map', 'Google Streets', 'Google', '{
    "type": "roadmap",
    "numZoomLevels": 22,
    "isBaseLayer": true,
    "useTiltImages": false
}', NULL);


--
-- TOC entry 2489 (class 0 OID 0)
-- Dependencies: 185
-- Name: layers_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.layers_id_seq', 5, true);


--
-- TOC entry 2461 (class 0 OID 207849)
-- Dependencies: 207
-- Data for Name: login_attempts; Type: TABLE DATA; Schema: public; Owner: -
--



--
-- TOC entry 2490 (class 0 OID 0)
-- Dependencies: 206
-- Name: login_attempts_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.login_attempts_id_seq', 1, false);


--
-- TOC entry 2448 (class 0 OID 207679)
-- Dependencies: 193
-- Data for Name: plugins; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.plugins VALUES (1, 'streetview', NULL);
INSERT INTO public.plugins VALUES (2, 'simpleaction', NULL);


--
-- TOC entry 2491 (class 0 OID 0)
-- Dependencies: 192
-- Name: plugins_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.plugins_id_seq', 2, true);


--
-- TOC entry 2463 (class 0 OID 207860)
-- Dependencies: 209
-- Data for Name: portal; Type: TABLE DATA; Schema: public; Owner: -
--



--
-- TOC entry 2492 (class 0 OID 0)
-- Dependencies: 208
-- Name: portal_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.portal_id_seq', 1, false);


--
-- TOC entry 2453 (class 0 OID 207769)
-- Dependencies: 199
-- Data for Name: project_groups; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.project_groups VALUES (1, 'helloworld', NULL, NULL, 0, 1, 0, '{4}', '{}', NULL, NULL, NULL, NULL, NULL, NULL);


--
-- TOC entry 2493 (class 0 OID 0)
-- Dependencies: 198
-- Name: project_groups_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.project_groups_id_seq', 1, true);


--
-- TOC entry 2441 (class 0 OID 207640)
-- Dependencies: 186
-- Data for Name: projects; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.projects VALUES (1, 'helloworld', NULL, NULL, NULL, 4, 1, NULL, true, false, true, true, true, true, true, false, true, NULL, NULL, 0, NULL, 1);


--
-- TOC entry 2494 (class 0 OID 0)
-- Dependencies: 187
-- Name: projects_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.projects_id_seq', 1, false);


--
-- TOC entry 2457 (class 0 OID 207806)
-- Dependencies: 203
-- Data for Name: roles; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.roles VALUES (1, 'admin', 'Administrator');
INSERT INTO public.roles VALUES (2, 'power', 'Power user');
INSERT INTO public.roles VALUES (9, 'link', NULL);
INSERT INTO public.roles VALUES (20, 'user', 'Project user (viewer)');
INSERT INTO public.roles VALUES (21, 'editor', 'Project editor');


--
-- TOC entry 2495 (class 0 OID 0)
-- Dependencies: 202
-- Name: roles_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.roles_id_seq', 1, false);


--
-- TOC entry 2443 (class 0 OID 207658)
-- Dependencies: 188
-- Data for Name: settings; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.settings VALUES (23, '2020-11-24');


--
-- TOC entry 2455 (class 0 OID 207793)
-- Dependencies: 201
-- Data for Name: tasks; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.tasks VALUES (1, 'clients_table_view', true, true);
INSERT INTO public.tasks VALUES (2, 'clients_edit', true, true);
INSERT INTO public.tasks VALUES (3, 'clients_send_email', true, true);
INSERT INTO public.tasks VALUES (4, 'project_groups_table_view', true, true);
INSERT INTO public.tasks VALUES (5, 'project_groups_edit', true, true);
INSERT INTO public.tasks VALUES (6, 'project_groups_edit_properties', true, false);
INSERT INTO public.tasks VALUES (7, 'project_groups_edit_contacts', true, true);
INSERT INTO public.tasks VALUES (8, 'project_groups_edit_layers', true, false);
INSERT INTO public.tasks VALUES (9, 'project_groups_edit_access', true, true);
INSERT INTO public.tasks VALUES (10, 'project_groups_send_email', true, true);
INSERT INTO public.tasks VALUES (11, 'projects_table_view', true, true);
INSERT INTO public.tasks VALUES (12, 'projects_edit', true, false);
INSERT INTO public.tasks VALUES (13, 'projects_edit_plugins', true, false);
INSERT INTO public.tasks VALUES (14, 'users_table_view', true, true);
INSERT INTO public.tasks VALUES (15, 'users_edit', true, true);
INSERT INTO public.tasks VALUES (16, 'users_delete', true, true);


--
-- TOC entry 2496 (class 0 OID 0)
-- Dependencies: 200
-- Name: tasks_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.tasks_id_seq', 16, true);


--
-- TOC entry 2444 (class 0 OID 207661)
-- Dependencies: 189
-- Data for Name: themes; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.themes VALUES (1, 'xtheme-blue.css');


--
-- TOC entry 2497 (class 0 OID 0)
-- Dependencies: 190
-- Name: themes_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.themes_id_seq', 1, false);


--
-- TOC entry 2446 (class 0 OID 207669)
-- Dependencies: 191
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.users VALUES (1, 'admin', '$2y$10$.LVNQqxHzKLW9P/Pjw7LTepsLgvT1UEbJWZOaXFVDgjvrPq.a66QW', 'admin@level2.si', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'Admin', '', NULL);


--
-- TOC entry 2451 (class 0 OID 207747)
-- Dependencies: 196
-- Data for Name: users_print; Type: TABLE DATA; Schema: public; Owner: -
--



--
-- TOC entry 2498 (class 0 OID 0)
-- Dependencies: 195
-- Name: users_print_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.users_print_id_seq', 1, false);


--
-- TOC entry 2459 (class 0 OID 207819)
-- Dependencies: 205
-- Data for Name: users_roles; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.users_roles VALUES (1, 1, 1, NULL, NULL);


--
-- TOC entry 2499 (class 0 OID 0)
-- Dependencies: 204
-- Name: users_roles_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.users_roles_id_seq', 1, true);


--
-- TOC entry 2500 (class 0 OID 0)
-- Dependencies: 194
-- Name: users_user_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.users_user_id_seq', 3, true);


--
-- TOC entry 2248 (class 2606 OID 207698)
-- Name: clients_name_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.clients
    ADD CONSTRAINT clients_name_key UNIQUE (name);


--
-- TOC entry 2250 (class 2606 OID 207700)
-- Name: clients_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.clients
    ADD CONSTRAINT clients_pkey PRIMARY KEY (id);


--
-- TOC entry 2252 (class 2606 OID 207702)
-- Name: layers_layer_name_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.layers
    ADD CONSTRAINT layers_layer_name_key UNIQUE (name);


--
-- TOC entry 2254 (class 2606 OID 207704)
-- Name: layers_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.layers
    ADD CONSTRAINT layers_pkey PRIMARY KEY (id);


--
-- TOC entry 2300 (class 2606 OID 207857)
-- Name: login_attempts_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.login_attempts
    ADD CONSTRAINT login_attempts_pkey PRIMARY KEY (id);


--
-- TOC entry 2278 (class 2606 OID 207689)
-- Name: plugins_name_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.plugins
    ADD CONSTRAINT plugins_name_key UNIQUE (name);


--
-- TOC entry 2280 (class 2606 OID 207687)
-- Name: plugins_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.plugins
    ADD CONSTRAINT plugins_pkey PRIMARY KEY (id);


--
-- TOC entry 2302 (class 2606 OID 207868)
-- Name: portal_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.portal
    ADD CONSTRAINT portal_pkey PRIMARY KEY (id);


--
-- TOC entry 2284 (class 2606 OID 207780)
-- Name: project_groups_name_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_groups
    ADD CONSTRAINT project_groups_name_key UNIQUE (name);


--
-- TOC entry 2286 (class 2606 OID 207778)
-- Name: project_groups_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_groups
    ADD CONSTRAINT project_groups_pkey PRIMARY KEY (id);


--
-- TOC entry 2256 (class 2606 OID 207706)
-- Name: projects_name_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.projects
    ADD CONSTRAINT projects_name_key UNIQUE (name);


--
-- TOC entry 2258 (class 2606 OID 207708)
-- Name: projects_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.projects
    ADD CONSTRAINT projects_pkey PRIMARY KEY (id);


--
-- TOC entry 2292 (class 2606 OID 207816)
-- Name: roles_name_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_name_key UNIQUE (name);


--
-- TOC entry 2294 (class 2606 OID 207814)
-- Name: roles_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_pkey PRIMARY KEY (id);


--
-- TOC entry 2260 (class 2606 OID 207710)
-- Name: settings_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.settings
    ADD CONSTRAINT settings_pkey PRIMARY KEY (version);


--
-- TOC entry 2288 (class 2606 OID 207803)
-- Name: tasks_name_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tasks
    ADD CONSTRAINT tasks_name_key UNIQUE (name);


--
-- TOC entry 2290 (class 2606 OID 207801)
-- Name: tasks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tasks
    ADD CONSTRAINT tasks_pkey PRIMARY KEY (id);


--
-- TOC entry 2262 (class 2606 OID 207712)
-- Name: themes_name_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.themes
    ADD CONSTRAINT themes_name_key UNIQUE (name);


--
-- TOC entry 2264 (class 2606 OID 207714)
-- Name: themes_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.themes
    ADD CONSTRAINT themes_pkey PRIMARY KEY (id);


--
-- TOC entry 2266 (class 2606 OID 207910)
-- Name: uc_activation_selector; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT uc_activation_selector UNIQUE (activation_selector);


--
-- TOC entry 2268 (class 2606 OID 207912)
-- Name: uc_forgotten_password_selector; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT uc_forgotten_password_selector UNIQUE (forgotten_password_selector);


--
-- TOC entry 2270 (class 2606 OID 207914)
-- Name: uc_remember_selector; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT uc_remember_selector UNIQUE (remember_selector);


--
-- TOC entry 2296 (class 2606 OID 207826)
-- Name: uc_users_roles; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users_roles
    ADD CONSTRAINT uc_users_roles UNIQUE (user_id, project_group_id);


--
-- TOC entry 2272 (class 2606 OID 207716)
-- Name: users_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (user_id);


--
-- TOC entry 2282 (class 2606 OID 207756)
-- Name: users_print_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users_print
    ADD CONSTRAINT users_print_pkey PRIMARY KEY (id);


--
-- TOC entry 2298 (class 2606 OID 207824)
-- Name: users_roles_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users_roles
    ADD CONSTRAINT users_roles_pkey PRIMARY KEY (id);


--
-- TOC entry 2274 (class 2606 OID 207720)
-- Name: users_user_email_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_user_email_key UNIQUE (user_email);


--
-- TOC entry 2276 (class 2606 OID 207718)
-- Name: users_user_name_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_user_name_key UNIQUE (user_name);


--
-- TOC entry 2303 (class 2606 OID 207721)
-- Name: clients_theme_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.clients
    ADD CONSTRAINT clients_theme_id_fkey FOREIGN KEY (theme_id) REFERENCES public.themes(id);


--
-- TOC entry 2304 (class 2606 OID 207869)
-- Name: layers_client_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.layers
    ADD CONSTRAINT layers_client_id_fkey FOREIGN KEY (client_id) REFERENCES public.clients(id);


--
-- TOC entry 2308 (class 2606 OID 207757)
-- Name: print_user_name_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users_print
    ADD CONSTRAINT print_user_name_fkey FOREIGN KEY (user_name) REFERENCES public.users(user_name);


--
-- TOC entry 2309 (class 2606 OID 207781)
-- Name: project_groups_client_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_groups
    ADD CONSTRAINT project_groups_client_id_fkey FOREIGN KEY (client_id) REFERENCES public.clients(id);


--
-- TOC entry 2310 (class 2606 OID 207786)
-- Name: project_groups_parent_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_groups
    ADD CONSTRAINT project_groups_parent_id_fkey FOREIGN KEY (parent_id) REFERENCES public.project_groups(id);


--
-- TOC entry 2305 (class 2606 OID 207726)
-- Name: projects_client_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.projects
    ADD CONSTRAINT projects_client_id_fkey FOREIGN KEY (client_id) REFERENCES public.clients(id);


--
-- TOC entry 2306 (class 2606 OID 207731)
-- Name: projects_overview_layer_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.projects
    ADD CONSTRAINT projects_overview_layer_id_fkey FOREIGN KEY (overview_layer_id) REFERENCES public.layers(id);


--
-- TOC entry 2307 (class 2606 OID 207916)
-- Name: projects_project_group_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.projects
    ADD CONSTRAINT projects_project_group_id_fkey FOREIGN KEY (project_group_id) REFERENCES public.project_groups(id);


--
-- TOC entry 2313 (class 2606 OID 207837)
-- Name: users_roles_client_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users_roles
    ADD CONSTRAINT users_roles_client_id_fkey FOREIGN KEY (client_id) REFERENCES public.clients(id);


--
-- TOC entry 2314 (class 2606 OID 207842)
-- Name: users_roles_project_group_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users_roles
    ADD CONSTRAINT users_roles_project_group_id_fkey FOREIGN KEY (project_group_id) REFERENCES public.project_groups(id);


--
-- TOC entry 2312 (class 2606 OID 207832)
-- Name: users_roles_role_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users_roles
    ADD CONSTRAINT users_roles_role_id_fkey FOREIGN KEY (role_id) REFERENCES public.roles(id);


--
-- TOC entry 2311 (class 2606 OID 207827)
-- Name: users_roles_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users_roles
    ADD CONSTRAINT users_roles_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(user_id);


--
-- TOC entry 2471 (class 0 OID 0)
-- Dependencies: 7
-- Name: SCHEMA public; Type: ACL; Schema: -; Owner: -
--

REVOKE ALL ON SCHEMA public FROM PUBLIC;
REVOKE ALL ON SCHEMA public FROM postgres;
GRANT ALL ON SCHEMA public TO postgres;
GRANT ALL ON SCHEMA public TO PUBLIC;


-- Completed on 2019-07-05 10:12:15 CEST

--
-- PostgreSQL database dump complete
--

