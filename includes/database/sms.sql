--
-- PostgreSQL database dump
--

-- Dumped from database version 17.4
-- Dumped by pg_dump version 17.5

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: auth; Type: SCHEMA; Schema: -; Owner: supabase_admin
--

CREATE SCHEMA auth;


ALTER SCHEMA auth OWNER TO supabase_admin;

--
-- Name: extensions; Type: SCHEMA; Schema: -; Owner: postgres
--

CREATE SCHEMA extensions;


ALTER SCHEMA extensions OWNER TO postgres;

--
-- Name: graphql; Type: SCHEMA; Schema: -; Owner: supabase_admin
--

CREATE SCHEMA graphql;


ALTER SCHEMA graphql OWNER TO supabase_admin;

--
-- Name: graphql_public; Type: SCHEMA; Schema: -; Owner: supabase_admin
--

CREATE SCHEMA graphql_public;


ALTER SCHEMA graphql_public OWNER TO supabase_admin;

--
-- Name: pgbouncer; Type: SCHEMA; Schema: -; Owner: pgbouncer
--

CREATE SCHEMA pgbouncer;


ALTER SCHEMA pgbouncer OWNER TO pgbouncer;

--
-- Name: realtime; Type: SCHEMA; Schema: -; Owner: supabase_admin
--

CREATE SCHEMA realtime;


ALTER SCHEMA realtime OWNER TO supabase_admin;

--
-- Name: storage; Type: SCHEMA; Schema: -; Owner: supabase_admin
--

CREATE SCHEMA storage;


ALTER SCHEMA storage OWNER TO supabase_admin;

--
-- Name: vault; Type: SCHEMA; Schema: -; Owner: supabase_admin
--

CREATE SCHEMA vault;


ALTER SCHEMA vault OWNER TO supabase_admin;

--
-- Name: pg_graphql; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS pg_graphql WITH SCHEMA graphql;


--
-- Name: EXTENSION pg_graphql; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION pg_graphql IS 'pg_graphql: GraphQL support';


--
-- Name: pg_stat_statements; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS pg_stat_statements WITH SCHEMA extensions;


--
-- Name: EXTENSION pg_stat_statements; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION pg_stat_statements IS 'track planning and execution statistics of all SQL statements executed';


--
-- Name: pgcrypto; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS pgcrypto WITH SCHEMA extensions;


--
-- Name: EXTENSION pgcrypto; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION pgcrypto IS 'cryptographic functions';


--
-- Name: supabase_vault; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS supabase_vault WITH SCHEMA vault;


--
-- Name: EXTENSION supabase_vault; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION supabase_vault IS 'Supabase Vault Extension';


--
-- Name: uuid-ossp; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS "uuid-ossp" WITH SCHEMA extensions;


--
-- Name: EXTENSION "uuid-ossp"; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION "uuid-ossp" IS 'generate universally unique identifiers (UUIDs)';


--
-- Name: aal_level; Type: TYPE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TYPE auth.aal_level AS ENUM (
    'aal1',
    'aal2',
    'aal3'
);


ALTER TYPE auth.aal_level OWNER TO supabase_auth_admin;

--
-- Name: code_challenge_method; Type: TYPE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TYPE auth.code_challenge_method AS ENUM (
    's256',
    'plain'
);


ALTER TYPE auth.code_challenge_method OWNER TO supabase_auth_admin;

--
-- Name: factor_status; Type: TYPE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TYPE auth.factor_status AS ENUM (
    'unverified',
    'verified'
);


ALTER TYPE auth.factor_status OWNER TO supabase_auth_admin;

--
-- Name: factor_type; Type: TYPE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TYPE auth.factor_type AS ENUM (
    'totp',
    'webauthn',
    'phone'
);


ALTER TYPE auth.factor_type OWNER TO supabase_auth_admin;

--
-- Name: one_time_token_type; Type: TYPE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TYPE auth.one_time_token_type AS ENUM (
    'confirmation_token',
    'reauthentication_token',
    'recovery_token',
    'email_change_token_new',
    'email_change_token_current',
    'phone_change_token'
);


ALTER TYPE auth.one_time_token_type OWNER TO supabase_auth_admin;

--
-- Name: account_status; Type: TYPE; Schema: public; Owner: postgres
--

CREATE TYPE public.account_status AS ENUM (
    'active',
    'suspended'
);


ALTER TYPE public.account_status OWNER TO postgres;

--
-- Name: attendance_status; Type: TYPE; Schema: public; Owner: postgres
--

CREATE TYPE public.attendance_status AS ENUM (
    'Present',
    'Absent'
);


ALTER TYPE public.attendance_status OWNER TO postgres;

--
-- Name: batch_enum; Type: TYPE; Schema: public; Owner: postgres
--

CREATE TYPE public.batch_enum AS ENUM (
    'Morning',
    'Evening'
);


ALTER TYPE public.batch_enum OWNER TO postgres;

--
-- Name: blood_group_enum; Type: TYPE; Schema: public; Owner: postgres
--

CREATE TYPE public.blood_group_enum AS ENUM (
    'A+',
    'A-',
    'B+',
    'B-',
    'AB+',
    'AB-',
    'O+',
    'O-'
);


ALTER TYPE public.blood_group_enum OWNER TO postgres;

--
-- Name: book_requester_role; Type: TYPE; Schema: public; Owner: postgres
--

CREATE TYPE public.book_requester_role AS ENUM (
    'teacher',
    'principal',
    'student'
);


ALTER TYPE public.book_requester_role OWNER TO postgres;

--
-- Name: borrow_request_status; Type: TYPE; Schema: public; Owner: postgres
--

CREATE TYPE public.borrow_request_status AS ENUM (
    'Pending',
    'Approved',
    'Rejected',
    'Collected'
);


ALTER TYPE public.borrow_request_status OWNER TO postgres;

--
-- Name: borrow_requester_role; Type: TYPE; Schema: public; Owner: postgres
--

CREATE TYPE public.borrow_requester_role AS ENUM (
    'student',
    'teacher'
);


ALTER TYPE public.borrow_requester_role OWNER TO postgres;

--
-- Name: borrower_role; Type: TYPE; Schema: public; Owner: postgres
--

CREATE TYPE public.borrower_role AS ENUM (
    'student',
    'teacher',
    'principal'
);


ALTER TYPE public.borrower_role OWNER TO postgres;

--
-- Name: day_of_week; Type: TYPE; Schema: public; Owner: postgres
--

CREATE TYPE public.day_of_week AS ENUM (
    'Monday',
    'Tuesday',
    'Wednesday',
    'Thursday',
    'Friday',
    'Saturday',
    'Sunday'
);


ALTER TYPE public.day_of_week OWNER TO postgres;

--
-- Name: fine_status; Type: TYPE; Schema: public; Owner: postgres
--

CREATE TYPE public.fine_status AS ENUM (
    'Paid',
    'Unpaid'
);


ALTER TYPE public.fine_status OWNER TO postgres;

--
-- Name: gender_enum_mfo; Type: TYPE; Schema: public; Owner: postgres
--

CREATE TYPE public.gender_enum_mfo AS ENUM (
    'Male',
    'Female',
    'Others'
);


ALTER TYPE public.gender_enum_mfo OWNER TO postgres;

--
-- Name: gender_enum_mfo_lower; Type: TYPE; Schema: public; Owner: postgres
--

CREATE TYPE public.gender_enum_mfo_lower AS ENUM (
    'male',
    'female',
    'other'
);


ALTER TYPE public.gender_enum_mfo_lower OWNER TO postgres;

--
-- Name: leave_request_status; Type: TYPE; Schema: public; Owner: postgres
--

CREATE TYPE public.leave_request_status AS ENUM (
    'Pending',
    'Approved',
    'Rejected'
);


ALTER TYPE public.leave_request_status OWNER TO postgres;

--
-- Name: recipient_type; Type: TYPE; Schema: public; Owner: postgres
--

CREATE TYPE public.recipient_type AS ENUM (
    'teacher',
    'standard'
);


ALTER TYPE public.recipient_type OWNER TO postgres;

--
-- Name: school_type; Type: TYPE; Schema: public; Owner: postgres
--

CREATE TYPE public.school_type AS ENUM (
    'Government',
    'Private'
);


ALTER TYPE public.school_type OWNER TO postgres;

--
-- Name: teacher_attendance_status; Type: TYPE; Schema: public; Owner: postgres
--

CREATE TYPE public.teacher_attendance_status AS ENUM (
    'Present',
    'Absent',
    'Leave'
);


ALTER TYPE public.teacher_attendance_status OWNER TO postgres;

--
-- Name: user_role; Type: TYPE; Schema: public; Owner: postgres
--

CREATE TYPE public.user_role AS ENUM (
    'student',
    'teacher',
    'principal',
    'superadmin',
    'librarian'
);


ALTER TYPE public.user_role OWNER TO postgres;

--
-- Name: action; Type: TYPE; Schema: realtime; Owner: supabase_admin
--

CREATE TYPE realtime.action AS ENUM (
    'INSERT',
    'UPDATE',
    'DELETE',
    'TRUNCATE',
    'ERROR'
);


ALTER TYPE realtime.action OWNER TO supabase_admin;

--
-- Name: equality_op; Type: TYPE; Schema: realtime; Owner: supabase_admin
--

CREATE TYPE realtime.equality_op AS ENUM (
    'eq',
    'neq',
    'lt',
    'lte',
    'gt',
    'gte',
    'in'
);


ALTER TYPE realtime.equality_op OWNER TO supabase_admin;

--
-- Name: user_defined_filter; Type: TYPE; Schema: realtime; Owner: supabase_admin
--

CREATE TYPE realtime.user_defined_filter AS (
	column_name text,
	op realtime.equality_op,
	value text
);


ALTER TYPE realtime.user_defined_filter OWNER TO supabase_admin;

--
-- Name: wal_column; Type: TYPE; Schema: realtime; Owner: supabase_admin
--

CREATE TYPE realtime.wal_column AS (
	name text,
	type_name text,
	type_oid oid,
	value jsonb,
	is_pkey boolean,
	is_selectable boolean
);


ALTER TYPE realtime.wal_column OWNER TO supabase_admin;

--
-- Name: wal_rls; Type: TYPE; Schema: realtime; Owner: supabase_admin
--

CREATE TYPE realtime.wal_rls AS (
	wal jsonb,
	is_rls_enabled boolean,
	subscription_ids uuid[],
	errors text[]
);


ALTER TYPE realtime.wal_rls OWNER TO supabase_admin;

--
-- Name: email(); Type: FUNCTION; Schema: auth; Owner: supabase_auth_admin
--

CREATE FUNCTION auth.email() RETURNS text
    LANGUAGE sql STABLE
    AS $$
  select 
  coalesce(
    nullif(current_setting('request.jwt.claim.email', true), ''),
    (nullif(current_setting('request.jwt.claims', true), '')::jsonb ->> 'email')
  )::text
$$;


ALTER FUNCTION auth.email() OWNER TO supabase_auth_admin;

--
-- Name: FUNCTION email(); Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON FUNCTION auth.email() IS 'Deprecated. Use auth.jwt() -> ''email'' instead.';


--
-- Name: jwt(); Type: FUNCTION; Schema: auth; Owner: supabase_auth_admin
--

CREATE FUNCTION auth.jwt() RETURNS jsonb
    LANGUAGE sql STABLE
    AS $$
  select 
    coalesce(
        nullif(current_setting('request.jwt.claim', true), ''),
        nullif(current_setting('request.jwt.claims', true), '')
    )::jsonb
$$;


ALTER FUNCTION auth.jwt() OWNER TO supabase_auth_admin;

--
-- Name: role(); Type: FUNCTION; Schema: auth; Owner: supabase_auth_admin
--

CREATE FUNCTION auth.role() RETURNS text
    LANGUAGE sql STABLE
    AS $$
  select 
  coalesce(
    nullif(current_setting('request.jwt.claim.role', true), ''),
    (nullif(current_setting('request.jwt.claims', true), '')::jsonb ->> 'role')
  )::text
$$;


ALTER FUNCTION auth.role() OWNER TO supabase_auth_admin;

--
-- Name: FUNCTION role(); Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON FUNCTION auth.role() IS 'Deprecated. Use auth.jwt() -> ''role'' instead.';


--
-- Name: uid(); Type: FUNCTION; Schema: auth; Owner: supabase_auth_admin
--

CREATE FUNCTION auth.uid() RETURNS uuid
    LANGUAGE sql STABLE
    AS $$
  select 
  coalesce(
    nullif(current_setting('request.jwt.claim.sub', true), ''),
    (nullif(current_setting('request.jwt.claims', true), '')::jsonb ->> 'sub')
  )::uuid
$$;


ALTER FUNCTION auth.uid() OWNER TO supabase_auth_admin;

--
-- Name: FUNCTION uid(); Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON FUNCTION auth.uid() IS 'Deprecated. Use auth.jwt() -> ''sub'' instead.';


--
-- Name: grant_pg_cron_access(); Type: FUNCTION; Schema: extensions; Owner: supabase_admin
--

CREATE FUNCTION extensions.grant_pg_cron_access() RETURNS event_trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
  IF EXISTS (
    SELECT
    FROM pg_event_trigger_ddl_commands() AS ev
    JOIN pg_extension AS ext
    ON ev.objid = ext.oid
    WHERE ext.extname = 'pg_cron'
  )
  THEN
    grant usage on schema cron to postgres with grant option;

    alter default privileges in schema cron grant all on tables to postgres with grant option;
    alter default privileges in schema cron grant all on functions to postgres with grant option;
    alter default privileges in schema cron grant all on sequences to postgres with grant option;

    alter default privileges for user supabase_admin in schema cron grant all
        on sequences to postgres with grant option;
    alter default privileges for user supabase_admin in schema cron grant all
        on tables to postgres with grant option;
    alter default privileges for user supabase_admin in schema cron grant all
        on functions to postgres with grant option;

    grant all privileges on all tables in schema cron to postgres with grant option;
    revoke all on table cron.job from postgres;
    grant select on table cron.job to postgres with grant option;
  END IF;
END;
$$;


ALTER FUNCTION extensions.grant_pg_cron_access() OWNER TO supabase_admin;

--
-- Name: FUNCTION grant_pg_cron_access(); Type: COMMENT; Schema: extensions; Owner: supabase_admin
--

COMMENT ON FUNCTION extensions.grant_pg_cron_access() IS 'Grants access to pg_cron';


--
-- Name: grant_pg_graphql_access(); Type: FUNCTION; Schema: extensions; Owner: supabase_admin
--

CREATE FUNCTION extensions.grant_pg_graphql_access() RETURNS event_trigger
    LANGUAGE plpgsql
    AS $_$
DECLARE
    func_is_graphql_resolve bool;
BEGIN
    func_is_graphql_resolve = (
        SELECT n.proname = 'resolve'
        FROM pg_event_trigger_ddl_commands() AS ev
        LEFT JOIN pg_catalog.pg_proc AS n
        ON ev.objid = n.oid
    );

    IF func_is_graphql_resolve
    THEN
        -- Update public wrapper to pass all arguments through to the pg_graphql resolve func
        DROP FUNCTION IF EXISTS graphql_public.graphql;
        create or replace function graphql_public.graphql(
            "operationName" text default null,
            query text default null,
            variables jsonb default null,
            extensions jsonb default null
        )
            returns jsonb
            language sql
        as $$
            select graphql.resolve(
                query := query,
                variables := coalesce(variables, '{}'),
                "operationName" := "operationName",
                extensions := extensions
            );
        $$;

        -- This hook executes when `graphql.resolve` is created. That is not necessarily the last
        -- function in the extension so we need to grant permissions on existing entities AND
        -- update default permissions to any others that are created after `graphql.resolve`
        grant usage on schema graphql to postgres, anon, authenticated, service_role;
        grant select on all tables in schema graphql to postgres, anon, authenticated, service_role;
        grant execute on all functions in schema graphql to postgres, anon, authenticated, service_role;
        grant all on all sequences in schema graphql to postgres, anon, authenticated, service_role;
        alter default privileges in schema graphql grant all on tables to postgres, anon, authenticated, service_role;
        alter default privileges in schema graphql grant all on functions to postgres, anon, authenticated, service_role;
        alter default privileges in schema graphql grant all on sequences to postgres, anon, authenticated, service_role;

        -- Allow postgres role to allow granting usage on graphql and graphql_public schemas to custom roles
        grant usage on schema graphql_public to postgres with grant option;
        grant usage on schema graphql to postgres with grant option;
    END IF;

END;
$_$;


ALTER FUNCTION extensions.grant_pg_graphql_access() OWNER TO supabase_admin;

--
-- Name: FUNCTION grant_pg_graphql_access(); Type: COMMENT; Schema: extensions; Owner: supabase_admin
--

COMMENT ON FUNCTION extensions.grant_pg_graphql_access() IS 'Grants access to pg_graphql';


--
-- Name: grant_pg_net_access(); Type: FUNCTION; Schema: extensions; Owner: supabase_admin
--

CREATE FUNCTION extensions.grant_pg_net_access() RETURNS event_trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
  IF EXISTS (
    SELECT 1
    FROM pg_event_trigger_ddl_commands() AS ev
    JOIN pg_extension AS ext
    ON ev.objid = ext.oid
    WHERE ext.extname = 'pg_net'
  )
  THEN
    IF NOT EXISTS (
      SELECT 1
      FROM pg_roles
      WHERE rolname = 'supabase_functions_admin'
    )
    THEN
      CREATE USER supabase_functions_admin NOINHERIT CREATEROLE LOGIN NOREPLICATION;
    END IF;

    GRANT USAGE ON SCHEMA net TO supabase_functions_admin, postgres, anon, authenticated, service_role;

    IF EXISTS (
      SELECT FROM pg_extension
      WHERE extname = 'pg_net'
      -- all versions in use on existing projects as of 2025-02-20
      -- version 0.12.0 onwards don't need these applied
      AND extversion IN ('0.2', '0.6', '0.7', '0.7.1', '0.8', '0.10.0', '0.11.0')
    ) THEN
      ALTER function net.http_get(url text, params jsonb, headers jsonb, timeout_milliseconds integer) SECURITY DEFINER;
      ALTER function net.http_post(url text, body jsonb, params jsonb, headers jsonb, timeout_milliseconds integer) SECURITY DEFINER;

      ALTER function net.http_get(url text, params jsonb, headers jsonb, timeout_milliseconds integer) SET search_path = net;
      ALTER function net.http_post(url text, body jsonb, params jsonb, headers jsonb, timeout_milliseconds integer) SET search_path = net;

      REVOKE ALL ON FUNCTION net.http_get(url text, params jsonb, headers jsonb, timeout_milliseconds integer) FROM PUBLIC;
      REVOKE ALL ON FUNCTION net.http_post(url text, body jsonb, params jsonb, headers jsonb, timeout_milliseconds integer) FROM PUBLIC;

      GRANT EXECUTE ON FUNCTION net.http_get(url text, params jsonb, headers jsonb, timeout_milliseconds integer) TO supabase_functions_admin, postgres, anon, authenticated, service_role;
      GRANT EXECUTE ON FUNCTION net.http_post(url text, body jsonb, params jsonb, headers jsonb, timeout_milliseconds integer) TO supabase_functions_admin, postgres, anon, authenticated, service_role;
    END IF;
  END IF;
END;
$$;


ALTER FUNCTION extensions.grant_pg_net_access() OWNER TO supabase_admin;

--
-- Name: FUNCTION grant_pg_net_access(); Type: COMMENT; Schema: extensions; Owner: supabase_admin
--

COMMENT ON FUNCTION extensions.grant_pg_net_access() IS 'Grants access to pg_net';


--
-- Name: pgrst_ddl_watch(); Type: FUNCTION; Schema: extensions; Owner: supabase_admin
--

CREATE FUNCTION extensions.pgrst_ddl_watch() RETURNS event_trigger
    LANGUAGE plpgsql
    AS $$
DECLARE
  cmd record;
BEGIN
  FOR cmd IN SELECT * FROM pg_event_trigger_ddl_commands()
  LOOP
    IF cmd.command_tag IN (
      'CREATE SCHEMA', 'ALTER SCHEMA'
    , 'CREATE TABLE', 'CREATE TABLE AS', 'SELECT INTO', 'ALTER TABLE'
    , 'CREATE FOREIGN TABLE', 'ALTER FOREIGN TABLE'
    , 'CREATE VIEW', 'ALTER VIEW'
    , 'CREATE MATERIALIZED VIEW', 'ALTER MATERIALIZED VIEW'
    , 'CREATE FUNCTION', 'ALTER FUNCTION'
    , 'CREATE TRIGGER'
    , 'CREATE TYPE', 'ALTER TYPE'
    , 'CREATE RULE'
    , 'COMMENT'
    )
    -- don't notify in case of CREATE TEMP table or other objects created on pg_temp
    AND cmd.schema_name is distinct from 'pg_temp'
    THEN
      NOTIFY pgrst, 'reload schema';
    END IF;
  END LOOP;
END; $$;


ALTER FUNCTION extensions.pgrst_ddl_watch() OWNER TO supabase_admin;

--
-- Name: pgrst_drop_watch(); Type: FUNCTION; Schema: extensions; Owner: supabase_admin
--

CREATE FUNCTION extensions.pgrst_drop_watch() RETURNS event_trigger
    LANGUAGE plpgsql
    AS $$
DECLARE
  obj record;
BEGIN
  FOR obj IN SELECT * FROM pg_event_trigger_dropped_objects()
  LOOP
    IF obj.object_type IN (
      'schema'
    , 'table'
    , 'foreign table'
    , 'view'
    , 'materialized view'
    , 'function'
    , 'trigger'
    , 'type'
    , 'rule'
    )
    AND obj.is_temporary IS false -- no pg_temp objects
    THEN
      NOTIFY pgrst, 'reload schema';
    END IF;
  END LOOP;
END; $$;


ALTER FUNCTION extensions.pgrst_drop_watch() OWNER TO supabase_admin;

--
-- Name: set_graphql_placeholder(); Type: FUNCTION; Schema: extensions; Owner: supabase_admin
--

CREATE FUNCTION extensions.set_graphql_placeholder() RETURNS event_trigger
    LANGUAGE plpgsql
    AS $_$
    DECLARE
    graphql_is_dropped bool;
    BEGIN
    graphql_is_dropped = (
        SELECT ev.schema_name = 'graphql_public'
        FROM pg_event_trigger_dropped_objects() AS ev
        WHERE ev.schema_name = 'graphql_public'
    );

    IF graphql_is_dropped
    THEN
        create or replace function graphql_public.graphql(
            "operationName" text default null,
            query text default null,
            variables jsonb default null,
            extensions jsonb default null
        )
            returns jsonb
            language plpgsql
        as $$
            DECLARE
                server_version float;
            BEGIN
                server_version = (SELECT (SPLIT_PART((select version()), ' ', 2))::float);

                IF server_version >= 14 THEN
                    RETURN jsonb_build_object(
                        'errors', jsonb_build_array(
                            jsonb_build_object(
                                'message', 'pg_graphql extension is not enabled.'
                            )
                        )
                    );
                ELSE
                    RETURN jsonb_build_object(
                        'errors', jsonb_build_array(
                            jsonb_build_object(
                                'message', 'pg_graphql is only available on projects running Postgres 14 onwards.'
                            )
                        )
                    );
                END IF;
            END;
        $$;
    END IF;

    END;
$_$;


ALTER FUNCTION extensions.set_graphql_placeholder() OWNER TO supabase_admin;

--
-- Name: FUNCTION set_graphql_placeholder(); Type: COMMENT; Schema: extensions; Owner: supabase_admin
--

COMMENT ON FUNCTION extensions.set_graphql_placeholder() IS 'Reintroduces placeholder function for graphql_public.graphql';


--
-- Name: get_auth(text); Type: FUNCTION; Schema: pgbouncer; Owner: supabase_admin
--

CREATE FUNCTION pgbouncer.get_auth(p_usename text) RETURNS TABLE(username text, password text)
    LANGUAGE plpgsql SECURITY DEFINER
    AS $_$
begin
    raise debug 'PgBouncer auth request: %', p_usename;

    return query
    select 
        rolname::text, 
        case when rolvaliduntil < now() 
            then null 
            else rolpassword::text 
        end 
    from pg_authid 
    where rolname=$1 and rolcanlogin;
end;
$_$;


ALTER FUNCTION pgbouncer.get_auth(p_usename text) OWNER TO supabase_admin;

--
-- Name: apply_rls(jsonb, integer); Type: FUNCTION; Schema: realtime; Owner: supabase_admin
--

CREATE FUNCTION realtime.apply_rls(wal jsonb, max_record_bytes integer DEFAULT (1024 * 1024)) RETURNS SETOF realtime.wal_rls
    LANGUAGE plpgsql
    AS $$
declare
-- Regclass of the table e.g. public.notes
entity_ regclass = (quote_ident(wal ->> 'schema') || '.' || quote_ident(wal ->> 'table'))::regclass;

-- I, U, D, T: insert, update ...
action realtime.action = (
    case wal ->> 'action'
        when 'I' then 'INSERT'
        when 'U' then 'UPDATE'
        when 'D' then 'DELETE'
        else 'ERROR'
    end
);

-- Is row level security enabled for the table
is_rls_enabled bool = relrowsecurity from pg_class where oid = entity_;

subscriptions realtime.subscription[] = array_agg(subs)
    from
        realtime.subscription subs
    where
        subs.entity = entity_;

-- Subscription vars
roles regrole[] = array_agg(distinct us.claims_role::text)
    from
        unnest(subscriptions) us;

working_role regrole;
claimed_role regrole;
claims jsonb;

subscription_id uuid;
subscription_has_access bool;
visible_to_subscription_ids uuid[] = '{}';

-- structured info for wal's columns
columns realtime.wal_column[];
-- previous identity values for update/delete
old_columns realtime.wal_column[];

error_record_exceeds_max_size boolean = octet_length(wal::text) > max_record_bytes;

-- Primary jsonb output for record
output jsonb;

begin
perform set_config('role', null, true);

columns =
    array_agg(
        (
            x->>'name',
            x->>'type',
            x->>'typeoid',
            realtime.cast(
                (x->'value') #>> '{}',
                coalesce(
                    (x->>'typeoid')::regtype, -- null when wal2json version <= 2.4
                    (x->>'type')::regtype
                )
            ),
            (pks ->> 'name') is not null,
            true
        )::realtime.wal_column
    )
    from
        jsonb_array_elements(wal -> 'columns') x
        left join jsonb_array_elements(wal -> 'pk') pks
            on (x ->> 'name') = (pks ->> 'name');

old_columns =
    array_agg(
        (
            x->>'name',
            x->>'type',
            x->>'typeoid',
            realtime.cast(
                (x->'value') #>> '{}',
                coalesce(
                    (x->>'typeoid')::regtype, -- null when wal2json version <= 2.4
                    (x->>'type')::regtype
                )
            ),
            (pks ->> 'name') is not null,
            true
        )::realtime.wal_column
    )
    from
        jsonb_array_elements(wal -> 'identity') x
        left join jsonb_array_elements(wal -> 'pk') pks
            on (x ->> 'name') = (pks ->> 'name');

for working_role in select * from unnest(roles) loop

    -- Update `is_selectable` for columns and old_columns
    columns =
        array_agg(
            (
                c.name,
                c.type_name,
                c.type_oid,
                c.value,
                c.is_pkey,
                pg_catalog.has_column_privilege(working_role, entity_, c.name, 'SELECT')
            )::realtime.wal_column
        )
        from
            unnest(columns) c;

    old_columns =
            array_agg(
                (
                    c.name,
                    c.type_name,
                    c.type_oid,
                    c.value,
                    c.is_pkey,
                    pg_catalog.has_column_privilege(working_role, entity_, c.name, 'SELECT')
                )::realtime.wal_column
            )
            from
                unnest(old_columns) c;

    if action <> 'DELETE' and count(1) = 0 from unnest(columns) c where c.is_pkey then
        return next (
            jsonb_build_object(
                'schema', wal ->> 'schema',
                'table', wal ->> 'table',
                'type', action
            ),
            is_rls_enabled,
            -- subscriptions is already filtered by entity
            (select array_agg(s.subscription_id) from unnest(subscriptions) as s where claims_role = working_role),
            array['Error 400: Bad Request, no primary key']
        )::realtime.wal_rls;

    -- The claims role does not have SELECT permission to the primary key of entity
    elsif action <> 'DELETE' and sum(c.is_selectable::int) <> count(1) from unnest(columns) c where c.is_pkey then
        return next (
            jsonb_build_object(
                'schema', wal ->> 'schema',
                'table', wal ->> 'table',
                'type', action
            ),
            is_rls_enabled,
            (select array_agg(s.subscription_id) from unnest(subscriptions) as s where claims_role = working_role),
            array['Error 401: Unauthorized']
        )::realtime.wal_rls;

    else
        output = jsonb_build_object(
            'schema', wal ->> 'schema',
            'table', wal ->> 'table',
            'type', action,
            'commit_timestamp', to_char(
                ((wal ->> 'timestamp')::timestamptz at time zone 'utc'),
                'YYYY-MM-DD"T"HH24:MI:SS.MS"Z"'
            ),
            'columns', (
                select
                    jsonb_agg(
                        jsonb_build_object(
                            'name', pa.attname,
                            'type', pt.typname
                        )
                        order by pa.attnum asc
                    )
                from
                    pg_attribute pa
                    join pg_type pt
                        on pa.atttypid = pt.oid
                where
                    attrelid = entity_
                    and attnum > 0
                    and pg_catalog.has_column_privilege(working_role, entity_, pa.attname, 'SELECT')
            )
        )
        -- Add "record" key for insert and update
        || case
            when action in ('INSERT', 'UPDATE') then
                jsonb_build_object(
                    'record',
                    (
                        select
                            jsonb_object_agg(
                                -- if unchanged toast, get column name and value from old record
                                coalesce((c).name, (oc).name),
                                case
                                    when (c).name is null then (oc).value
                                    else (c).value
                                end
                            )
                        from
                            unnest(columns) c
                            full outer join unnest(old_columns) oc
                                on (c).name = (oc).name
                        where
                            coalesce((c).is_selectable, (oc).is_selectable)
                            and ( not error_record_exceeds_max_size or (octet_length((c).value::text) <= 64))
                    )
                )
            else '{}'::jsonb
        end
        -- Add "old_record" key for update and delete
        || case
            when action = 'UPDATE' then
                jsonb_build_object(
                        'old_record',
                        (
                            select jsonb_object_agg((c).name, (c).value)
                            from unnest(old_columns) c
                            where
                                (c).is_selectable
                                and ( not error_record_exceeds_max_size or (octet_length((c).value::text) <= 64))
                        )
                    )
            when action = 'DELETE' then
                jsonb_build_object(
                    'old_record',
                    (
                        select jsonb_object_agg((c).name, (c).value)
                        from unnest(old_columns) c
                        where
                            (c).is_selectable
                            and ( not error_record_exceeds_max_size or (octet_length((c).value::text) <= 64))
                            and ( not is_rls_enabled or (c).is_pkey ) -- if RLS enabled, we can't secure deletes so filter to pkey
                    )
                )
            else '{}'::jsonb
        end;

        -- Create the prepared statement
        if is_rls_enabled and action <> 'DELETE' then
            if (select 1 from pg_prepared_statements where name = 'walrus_rls_stmt' limit 1) > 0 then
                deallocate walrus_rls_stmt;
            end if;
            execute realtime.build_prepared_statement_sql('walrus_rls_stmt', entity_, columns);
        end if;

        visible_to_subscription_ids = '{}';

        for subscription_id, claims in (
                select
                    subs.subscription_id,
                    subs.claims
                from
                    unnest(subscriptions) subs
                where
                    subs.entity = entity_
                    and subs.claims_role = working_role
                    and (
                        realtime.is_visible_through_filters(columns, subs.filters)
                        or (
                          action = 'DELETE'
                          and realtime.is_visible_through_filters(old_columns, subs.filters)
                        )
                    )
        ) loop

            if not is_rls_enabled or action = 'DELETE' then
                visible_to_subscription_ids = visible_to_subscription_ids || subscription_id;
            else
                -- Check if RLS allows the role to see the record
                perform
                    -- Trim leading and trailing quotes from working_role because set_config
                    -- doesn't recognize the role as valid if they are included
                    set_config('role', trim(both '"' from working_role::text), true),
                    set_config('request.jwt.claims', claims::text, true);

                execute 'execute walrus_rls_stmt' into subscription_has_access;

                if subscription_has_access then
                    visible_to_subscription_ids = visible_to_subscription_ids || subscription_id;
                end if;
            end if;
        end loop;

        perform set_config('role', null, true);

        return next (
            output,
            is_rls_enabled,
            visible_to_subscription_ids,
            case
                when error_record_exceeds_max_size then array['Error 413: Payload Too Large']
                else '{}'
            end
        )::realtime.wal_rls;

    end if;
end loop;

perform set_config('role', null, true);
end;
$$;


ALTER FUNCTION realtime.apply_rls(wal jsonb, max_record_bytes integer) OWNER TO supabase_admin;

--
-- Name: broadcast_changes(text, text, text, text, text, record, record, text); Type: FUNCTION; Schema: realtime; Owner: supabase_admin
--

CREATE FUNCTION realtime.broadcast_changes(topic_name text, event_name text, operation text, table_name text, table_schema text, new record, old record, level text DEFAULT 'ROW'::text) RETURNS void
    LANGUAGE plpgsql
    AS $$
DECLARE
    -- Declare a variable to hold the JSONB representation of the row
    row_data jsonb := '{}'::jsonb;
BEGIN
    IF level = 'STATEMENT' THEN
        RAISE EXCEPTION 'function can only be triggered for each row, not for each statement';
    END IF;
    -- Check the operation type and handle accordingly
    IF operation = 'INSERT' OR operation = 'UPDATE' OR operation = 'DELETE' THEN
        row_data := jsonb_build_object('old_record', OLD, 'record', NEW, 'operation', operation, 'table', table_name, 'schema', table_schema);
        PERFORM realtime.send (row_data, event_name, topic_name);
    ELSE
        RAISE EXCEPTION 'Unexpected operation type: %', operation;
    END IF;
EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Failed to process the row: %', SQLERRM;
END;

$$;


ALTER FUNCTION realtime.broadcast_changes(topic_name text, event_name text, operation text, table_name text, table_schema text, new record, old record, level text) OWNER TO supabase_admin;

--
-- Name: build_prepared_statement_sql(text, regclass, realtime.wal_column[]); Type: FUNCTION; Schema: realtime; Owner: supabase_admin
--

CREATE FUNCTION realtime.build_prepared_statement_sql(prepared_statement_name text, entity regclass, columns realtime.wal_column[]) RETURNS text
    LANGUAGE sql
    AS $$
      /*
      Builds a sql string that, if executed, creates a prepared statement to
      tests retrive a row from *entity* by its primary key columns.
      Example
          select realtime.build_prepared_statement_sql('public.notes', '{"id"}'::text[], '{"bigint"}'::text[])
      */
          select
      'prepare ' || prepared_statement_name || ' as
          select
              exists(
                  select
                      1
                  from
                      ' || entity || '
                  where
                      ' || string_agg(quote_ident(pkc.name) || '=' || quote_nullable(pkc.value #>> '{}') , ' and ') || '
              )'
          from
              unnest(columns) pkc
          where
              pkc.is_pkey
          group by
              entity
      $$;


ALTER FUNCTION realtime.build_prepared_statement_sql(prepared_statement_name text, entity regclass, columns realtime.wal_column[]) OWNER TO supabase_admin;

--
-- Name: cast(text, regtype); Type: FUNCTION; Schema: realtime; Owner: supabase_admin
--

CREATE FUNCTION realtime."cast"(val text, type_ regtype) RETURNS jsonb
    LANGUAGE plpgsql IMMUTABLE
    AS $$
    declare
      res jsonb;
    begin
      execute format('select to_jsonb(%L::'|| type_::text || ')', val)  into res;
      return res;
    end
    $$;


ALTER FUNCTION realtime."cast"(val text, type_ regtype) OWNER TO supabase_admin;

--
-- Name: check_equality_op(realtime.equality_op, regtype, text, text); Type: FUNCTION; Schema: realtime; Owner: supabase_admin
--

CREATE FUNCTION realtime.check_equality_op(op realtime.equality_op, type_ regtype, val_1 text, val_2 text) RETURNS boolean
    LANGUAGE plpgsql IMMUTABLE
    AS $$
      /*
      Casts *val_1* and *val_2* as type *type_* and check the *op* condition for truthiness
      */
      declare
          op_symbol text = (
              case
                  when op = 'eq' then '='
                  when op = 'neq' then '!='
                  when op = 'lt' then '<'
                  when op = 'lte' then '<='
                  when op = 'gt' then '>'
                  when op = 'gte' then '>='
                  when op = 'in' then '= any'
                  else 'UNKNOWN OP'
              end
          );
          res boolean;
      begin
          execute format(
              'select %L::'|| type_::text || ' ' || op_symbol
              || ' ( %L::'
              || (
                  case
                      when op = 'in' then type_::text || '[]'
                      else type_::text end
              )
              || ')', val_1, val_2) into res;
          return res;
      end;
      $$;


ALTER FUNCTION realtime.check_equality_op(op realtime.equality_op, type_ regtype, val_1 text, val_2 text) OWNER TO supabase_admin;

--
-- Name: is_visible_through_filters(realtime.wal_column[], realtime.user_defined_filter[]); Type: FUNCTION; Schema: realtime; Owner: supabase_admin
--

CREATE FUNCTION realtime.is_visible_through_filters(columns realtime.wal_column[], filters realtime.user_defined_filter[]) RETURNS boolean
    LANGUAGE sql IMMUTABLE
    AS $_$
    /*
    Should the record be visible (true) or filtered out (false) after *filters* are applied
    */
        select
            -- Default to allowed when no filters present
            $2 is null -- no filters. this should not happen because subscriptions has a default
            or array_length($2, 1) is null -- array length of an empty array is null
            or bool_and(
                coalesce(
                    realtime.check_equality_op(
                        op:=f.op,
                        type_:=coalesce(
                            col.type_oid::regtype, -- null when wal2json version <= 2.4
                            col.type_name::regtype
                        ),
                        -- cast jsonb to text
                        val_1:=col.value #>> '{}',
                        val_2:=f.value
                    ),
                    false -- if null, filter does not match
                )
            )
        from
            unnest(filters) f
            join unnest(columns) col
                on f.column_name = col.name;
    $_$;


ALTER FUNCTION realtime.is_visible_through_filters(columns realtime.wal_column[], filters realtime.user_defined_filter[]) OWNER TO supabase_admin;

--
-- Name: list_changes(name, name, integer, integer); Type: FUNCTION; Schema: realtime; Owner: supabase_admin
--

CREATE FUNCTION realtime.list_changes(publication name, slot_name name, max_changes integer, max_record_bytes integer) RETURNS SETOF realtime.wal_rls
    LANGUAGE sql
    SET log_min_messages TO 'fatal'
    AS $$
      with pub as (
        select
          concat_ws(
            ',',
            case when bool_or(pubinsert) then 'insert' else null end,
            case when bool_or(pubupdate) then 'update' else null end,
            case when bool_or(pubdelete) then 'delete' else null end
          ) as w2j_actions,
          coalesce(
            string_agg(
              realtime.quote_wal2json(format('%I.%I', schemaname, tablename)::regclass),
              ','
            ) filter (where ppt.tablename is not null and ppt.tablename not like '% %'),
            ''
          ) w2j_add_tables
        from
          pg_publication pp
          left join pg_publication_tables ppt
            on pp.pubname = ppt.pubname
        where
          pp.pubname = publication
        group by
          pp.pubname
        limit 1
      ),
      w2j as (
        select
          x.*, pub.w2j_add_tables
        from
          pub,
          pg_logical_slot_get_changes(
            slot_name, null, max_changes,
            'include-pk', 'true',
            'include-transaction', 'false',
            'include-timestamp', 'true',
            'include-type-oids', 'true',
            'format-version', '2',
            'actions', pub.w2j_actions,
            'add-tables', pub.w2j_add_tables
          ) x
      )
      select
        xyz.wal,
        xyz.is_rls_enabled,
        xyz.subscription_ids,
        xyz.errors
      from
        w2j,
        realtime.apply_rls(
          wal := w2j.data::jsonb,
          max_record_bytes := max_record_bytes
        ) xyz(wal, is_rls_enabled, subscription_ids, errors)
      where
        w2j.w2j_add_tables <> ''
        and xyz.subscription_ids[1] is not null
    $$;


ALTER FUNCTION realtime.list_changes(publication name, slot_name name, max_changes integer, max_record_bytes integer) OWNER TO supabase_admin;

--
-- Name: quote_wal2json(regclass); Type: FUNCTION; Schema: realtime; Owner: supabase_admin
--

CREATE FUNCTION realtime.quote_wal2json(entity regclass) RETURNS text
    LANGUAGE sql IMMUTABLE STRICT
    AS $$
      select
        (
          select string_agg('' || ch,'')
          from unnest(string_to_array(nsp.nspname::text, null)) with ordinality x(ch, idx)
          where
            not (x.idx = 1 and x.ch = '"')
            and not (
              x.idx = array_length(string_to_array(nsp.nspname::text, null), 1)
              and x.ch = '"'
            )
        )
        || '.'
        || (
          select string_agg('' || ch,'')
          from unnest(string_to_array(pc.relname::text, null)) with ordinality x(ch, idx)
          where
            not (x.idx = 1 and x.ch = '"')
            and not (
              x.idx = array_length(string_to_array(nsp.nspname::text, null), 1)
              and x.ch = '"'
            )
          )
      from
        pg_class pc
        join pg_namespace nsp
          on pc.relnamespace = nsp.oid
      where
        pc.oid = entity
    $$;


ALTER FUNCTION realtime.quote_wal2json(entity regclass) OWNER TO supabase_admin;

--
-- Name: send(jsonb, text, text, boolean); Type: FUNCTION; Schema: realtime; Owner: supabase_admin
--

CREATE FUNCTION realtime.send(payload jsonb, event text, topic text, private boolean DEFAULT true) RETURNS void
    LANGUAGE plpgsql
    AS $$
BEGIN
  BEGIN
    -- Set the topic configuration
    EXECUTE format('SET LOCAL realtime.topic TO %L', topic);

    -- Attempt to insert the message
    INSERT INTO realtime.messages (payload, event, topic, private, extension)
    VALUES (payload, event, topic, private, 'broadcast');
  EXCEPTION
    WHEN OTHERS THEN
      -- Capture and notify the error
      RAISE WARNING 'ErrorSendingBroadcastMessage: %', SQLERRM;
  END;
END;
$$;


ALTER FUNCTION realtime.send(payload jsonb, event text, topic text, private boolean) OWNER TO supabase_admin;

--
-- Name: subscription_check_filters(); Type: FUNCTION; Schema: realtime; Owner: supabase_admin
--

CREATE FUNCTION realtime.subscription_check_filters() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
    /*
    Validates that the user defined filters for a subscription:
    - refer to valid columns that the claimed role may access
    - values are coercable to the correct column type
    */
    declare
        col_names text[] = coalesce(
                array_agg(c.column_name order by c.ordinal_position),
                '{}'::text[]
            )
            from
                information_schema.columns c
            where
                format('%I.%I', c.table_schema, c.table_name)::regclass = new.entity
                and pg_catalog.has_column_privilege(
                    (new.claims ->> 'role'),
                    format('%I.%I', c.table_schema, c.table_name)::regclass,
                    c.column_name,
                    'SELECT'
                );
        filter realtime.user_defined_filter;
        col_type regtype;

        in_val jsonb;
    begin
        for filter in select * from unnest(new.filters) loop
            -- Filtered column is valid
            if not filter.column_name = any(col_names) then
                raise exception 'invalid column for filter %', filter.column_name;
            end if;

            -- Type is sanitized and safe for string interpolation
            col_type = (
                select atttypid::regtype
                from pg_catalog.pg_attribute
                where attrelid = new.entity
                      and attname = filter.column_name
            );
            if col_type is null then
                raise exception 'failed to lookup type for column %', filter.column_name;
            end if;

            -- Set maximum number of entries for in filter
            if filter.op = 'in'::realtime.equality_op then
                in_val = realtime.cast(filter.value, (col_type::text || '[]')::regtype);
                if coalesce(jsonb_array_length(in_val), 0) > 100 then
                    raise exception 'too many values for `in` filter. Maximum 100';
                end if;
            else
                -- raises an exception if value is not coercable to type
                perform realtime.cast(filter.value, col_type);
            end if;

        end loop;

        -- Apply consistent order to filters so the unique constraint on
        -- (subscription_id, entity, filters) can't be tricked by a different filter order
        new.filters = coalesce(
            array_agg(f order by f.column_name, f.op, f.value),
            '{}'
        ) from unnest(new.filters) f;

        return new;
    end;
    $$;


ALTER FUNCTION realtime.subscription_check_filters() OWNER TO supabase_admin;

--
-- Name: to_regrole(text); Type: FUNCTION; Schema: realtime; Owner: supabase_admin
--

CREATE FUNCTION realtime.to_regrole(role_name text) RETURNS regrole
    LANGUAGE sql IMMUTABLE
    AS $$ select role_name::regrole $$;


ALTER FUNCTION realtime.to_regrole(role_name text) OWNER TO supabase_admin;

--
-- Name: topic(); Type: FUNCTION; Schema: realtime; Owner: supabase_realtime_admin
--

CREATE FUNCTION realtime.topic() RETURNS text
    LANGUAGE sql STABLE
    AS $$
select nullif(current_setting('realtime.topic', true), '')::text;
$$;


ALTER FUNCTION realtime.topic() OWNER TO supabase_realtime_admin;

--
-- Name: can_insert_object(text, text, uuid, jsonb); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.can_insert_object(bucketid text, name text, owner uuid, metadata jsonb) RETURNS void
    LANGUAGE plpgsql
    AS $$
BEGIN
  INSERT INTO "storage"."objects" ("bucket_id", "name", "owner", "metadata") VALUES (bucketid, name, owner, metadata);
  -- hack to rollback the successful insert
  RAISE sqlstate 'PT200' using
  message = 'ROLLBACK',
  detail = 'rollback successful insert';
END
$$;


ALTER FUNCTION storage.can_insert_object(bucketid text, name text, owner uuid, metadata jsonb) OWNER TO supabase_storage_admin;

--
-- Name: extension(text); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.extension(name text) RETURNS text
    LANGUAGE plpgsql
    AS $$
DECLARE
_parts text[];
_filename text;
BEGIN
	select string_to_array(name, '/') into _parts;
	select _parts[array_length(_parts,1)] into _filename;
	-- @todo return the last part instead of 2
	return reverse(split_part(reverse(_filename), '.', 1));
END
$$;


ALTER FUNCTION storage.extension(name text) OWNER TO supabase_storage_admin;

--
-- Name: filename(text); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.filename(name text) RETURNS text
    LANGUAGE plpgsql
    AS $$
DECLARE
_parts text[];
BEGIN
	select string_to_array(name, '/') into _parts;
	return _parts[array_length(_parts,1)];
END
$$;


ALTER FUNCTION storage.filename(name text) OWNER TO supabase_storage_admin;

--
-- Name: foldername(text); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.foldername(name text) RETURNS text[]
    LANGUAGE plpgsql
    AS $$
DECLARE
_parts text[];
BEGIN
	select string_to_array(name, '/') into _parts;
	return _parts[1:array_length(_parts,1)-1];
END
$$;


ALTER FUNCTION storage.foldername(name text) OWNER TO supabase_storage_admin;

--
-- Name: get_size_by_bucket(); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.get_size_by_bucket() RETURNS TABLE(size bigint, bucket_id text)
    LANGUAGE plpgsql
    AS $$
BEGIN
    return query
        select sum((metadata->>'size')::int) as size, obj.bucket_id
        from "storage".objects as obj
        group by obj.bucket_id;
END
$$;


ALTER FUNCTION storage.get_size_by_bucket() OWNER TO supabase_storage_admin;

--
-- Name: list_multipart_uploads_with_delimiter(text, text, text, integer, text, text); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.list_multipart_uploads_with_delimiter(bucket_id text, prefix_param text, delimiter_param text, max_keys integer DEFAULT 100, next_key_token text DEFAULT ''::text, next_upload_token text DEFAULT ''::text) RETURNS TABLE(key text, id text, created_at timestamp with time zone)
    LANGUAGE plpgsql
    AS $_$
BEGIN
    RETURN QUERY EXECUTE
        'SELECT DISTINCT ON(key COLLATE "C") * from (
            SELECT
                CASE
                    WHEN position($2 IN substring(key from length($1) + 1)) > 0 THEN
                        substring(key from 1 for length($1) + position($2 IN substring(key from length($1) + 1)))
                    ELSE
                        key
                END AS key, id, created_at
            FROM
                storage.s3_multipart_uploads
            WHERE
                bucket_id = $5 AND
                key ILIKE $1 || ''%'' AND
                CASE
                    WHEN $4 != '''' AND $6 = '''' THEN
                        CASE
                            WHEN position($2 IN substring(key from length($1) + 1)) > 0 THEN
                                substring(key from 1 for length($1) + position($2 IN substring(key from length($1) + 1))) COLLATE "C" > $4
                            ELSE
                                key COLLATE "C" > $4
                            END
                    ELSE
                        true
                END AND
                CASE
                    WHEN $6 != '''' THEN
                        id COLLATE "C" > $6
                    ELSE
                        true
                    END
            ORDER BY
                key COLLATE "C" ASC, created_at ASC) as e order by key COLLATE "C" LIMIT $3'
        USING prefix_param, delimiter_param, max_keys, next_key_token, bucket_id, next_upload_token;
END;
$_$;


ALTER FUNCTION storage.list_multipart_uploads_with_delimiter(bucket_id text, prefix_param text, delimiter_param text, max_keys integer, next_key_token text, next_upload_token text) OWNER TO supabase_storage_admin;

--
-- Name: list_objects_with_delimiter(text, text, text, integer, text, text); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.list_objects_with_delimiter(bucket_id text, prefix_param text, delimiter_param text, max_keys integer DEFAULT 100, start_after text DEFAULT ''::text, next_token text DEFAULT ''::text) RETURNS TABLE(name text, id uuid, metadata jsonb, updated_at timestamp with time zone)
    LANGUAGE plpgsql
    AS $_$
BEGIN
    RETURN QUERY EXECUTE
        'SELECT DISTINCT ON(name COLLATE "C") * from (
            SELECT
                CASE
                    WHEN position($2 IN substring(name from length($1) + 1)) > 0 THEN
                        substring(name from 1 for length($1) + position($2 IN substring(name from length($1) + 1)))
                    ELSE
                        name
                END AS name, id, metadata, updated_at
            FROM
                storage.objects
            WHERE
                bucket_id = $5 AND
                name ILIKE $1 || ''%'' AND
                CASE
                    WHEN $6 != '''' THEN
                    name COLLATE "C" > $6
                ELSE true END
                AND CASE
                    WHEN $4 != '''' THEN
                        CASE
                            WHEN position($2 IN substring(name from length($1) + 1)) > 0 THEN
                                substring(name from 1 for length($1) + position($2 IN substring(name from length($1) + 1))) COLLATE "C" > $4
                            ELSE
                                name COLLATE "C" > $4
                            END
                    ELSE
                        true
                END
            ORDER BY
                name COLLATE "C" ASC) as e order by name COLLATE "C" LIMIT $3'
        USING prefix_param, delimiter_param, max_keys, next_token, bucket_id, start_after;
END;
$_$;


ALTER FUNCTION storage.list_objects_with_delimiter(bucket_id text, prefix_param text, delimiter_param text, max_keys integer, start_after text, next_token text) OWNER TO supabase_storage_admin;

--
-- Name: operation(); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.operation() RETURNS text
    LANGUAGE plpgsql STABLE
    AS $$
BEGIN
    RETURN current_setting('storage.operation', true);
END;
$$;


ALTER FUNCTION storage.operation() OWNER TO supabase_storage_admin;

--
-- Name: search(text, text, integer, integer, integer, text, text, text); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.search(prefix text, bucketname text, limits integer DEFAULT 100, levels integer DEFAULT 1, offsets integer DEFAULT 0, search text DEFAULT ''::text, sortcolumn text DEFAULT 'name'::text, sortorder text DEFAULT 'asc'::text) RETURNS TABLE(name text, id uuid, updated_at timestamp with time zone, created_at timestamp with time zone, last_accessed_at timestamp with time zone, metadata jsonb)
    LANGUAGE plpgsql STABLE
    AS $_$
declare
  v_order_by text;
  v_sort_order text;
begin
  case
    when sortcolumn = 'name' then
      v_order_by = 'name';
    when sortcolumn = 'updated_at' then
      v_order_by = 'updated_at';
    when sortcolumn = 'created_at' then
      v_order_by = 'created_at';
    when sortcolumn = 'last_accessed_at' then
      v_order_by = 'last_accessed_at';
    else
      v_order_by = 'name';
  end case;

  case
    when sortorder = 'asc' then
      v_sort_order = 'asc';
    when sortorder = 'desc' then
      v_sort_order = 'desc';
    else
      v_sort_order = 'asc';
  end case;

  v_order_by = v_order_by || ' ' || v_sort_order;

  return query execute
    'with folders as (
       select path_tokens[$1] as folder
       from storage.objects
         where objects.name ilike $2 || $3 || ''%''
           and bucket_id = $4
           and array_length(objects.path_tokens, 1) <> $1
       group by folder
       order by folder ' || v_sort_order || '
     )
     (select folder as "name",
            null as id,
            null as updated_at,
            null as created_at,
            null as last_accessed_at,
            null as metadata from folders)
     union all
     (select path_tokens[$1] as "name",
            id,
            updated_at,
            created_at,
            last_accessed_at,
            metadata
     from storage.objects
     where objects.name ilike $2 || $3 || ''%''
       and bucket_id = $4
       and array_length(objects.path_tokens, 1) = $1
     order by ' || v_order_by || ')
     limit $5
     offset $6' using levels, prefix, search, bucketname, limits, offsets;
end;
$_$;


ALTER FUNCTION storage.search(prefix text, bucketname text, limits integer, levels integer, offsets integer, search text, sortcolumn text, sortorder text) OWNER TO supabase_storage_admin;

--
-- Name: update_updated_at_column(); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.update_updated_at_column() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    NEW.updated_at = now();
    RETURN NEW; 
END;
$$;


ALTER FUNCTION storage.update_updated_at_column() OWNER TO supabase_storage_admin;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: audit_log_entries; Type: TABLE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TABLE auth.audit_log_entries (
    instance_id uuid,
    id uuid NOT NULL,
    payload json,
    created_at timestamp with time zone,
    ip_address character varying(64) DEFAULT ''::character varying NOT NULL
);


ALTER TABLE auth.audit_log_entries OWNER TO supabase_auth_admin;

--
-- Name: TABLE audit_log_entries; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON TABLE auth.audit_log_entries IS 'Auth: Audit trail for user actions.';


--
-- Name: flow_state; Type: TABLE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TABLE auth.flow_state (
    id uuid NOT NULL,
    user_id uuid,
    auth_code text NOT NULL,
    code_challenge_method auth.code_challenge_method NOT NULL,
    code_challenge text NOT NULL,
    provider_type text NOT NULL,
    provider_access_token text,
    provider_refresh_token text,
    created_at timestamp with time zone,
    updated_at timestamp with time zone,
    authentication_method text NOT NULL,
    auth_code_issued_at timestamp with time zone
);


ALTER TABLE auth.flow_state OWNER TO supabase_auth_admin;

--
-- Name: TABLE flow_state; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON TABLE auth.flow_state IS 'stores metadata for pkce logins';


--
-- Name: identities; Type: TABLE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TABLE auth.identities (
    provider_id text NOT NULL,
    user_id uuid NOT NULL,
    identity_data jsonb NOT NULL,
    provider text NOT NULL,
    last_sign_in_at timestamp with time zone,
    created_at timestamp with time zone,
    updated_at timestamp with time zone,
    email text GENERATED ALWAYS AS (lower((identity_data ->> 'email'::text))) STORED,
    id uuid DEFAULT gen_random_uuid() NOT NULL
);


ALTER TABLE auth.identities OWNER TO supabase_auth_admin;

--
-- Name: TABLE identities; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON TABLE auth.identities IS 'Auth: Stores identities associated to a user.';


--
-- Name: COLUMN identities.email; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON COLUMN auth.identities.email IS 'Auth: Email is a generated column that references the optional email property in the identity_data';


--
-- Name: instances; Type: TABLE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TABLE auth.instances (
    id uuid NOT NULL,
    uuid uuid,
    raw_base_config text,
    created_at timestamp with time zone,
    updated_at timestamp with time zone
);


ALTER TABLE auth.instances OWNER TO supabase_auth_admin;

--
-- Name: TABLE instances; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON TABLE auth.instances IS 'Auth: Manages users across multiple sites.';


--
-- Name: mfa_amr_claims; Type: TABLE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TABLE auth.mfa_amr_claims (
    session_id uuid NOT NULL,
    created_at timestamp with time zone NOT NULL,
    updated_at timestamp with time zone NOT NULL,
    authentication_method text NOT NULL,
    id uuid NOT NULL
);


ALTER TABLE auth.mfa_amr_claims OWNER TO supabase_auth_admin;

--
-- Name: TABLE mfa_amr_claims; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON TABLE auth.mfa_amr_claims IS 'auth: stores authenticator method reference claims for multi factor authentication';


--
-- Name: mfa_challenges; Type: TABLE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TABLE auth.mfa_challenges (
    id uuid NOT NULL,
    factor_id uuid NOT NULL,
    created_at timestamp with time zone NOT NULL,
    verified_at timestamp with time zone,
    ip_address inet NOT NULL,
    otp_code text,
    web_authn_session_data jsonb
);


ALTER TABLE auth.mfa_challenges OWNER TO supabase_auth_admin;

--
-- Name: TABLE mfa_challenges; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON TABLE auth.mfa_challenges IS 'auth: stores metadata about challenge requests made';


--
-- Name: mfa_factors; Type: TABLE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TABLE auth.mfa_factors (
    id uuid NOT NULL,
    user_id uuid NOT NULL,
    friendly_name text,
    factor_type auth.factor_type NOT NULL,
    status auth.factor_status NOT NULL,
    created_at timestamp with time zone NOT NULL,
    updated_at timestamp with time zone NOT NULL,
    secret text,
    phone text,
    last_challenged_at timestamp with time zone,
    web_authn_credential jsonb,
    web_authn_aaguid uuid
);


ALTER TABLE auth.mfa_factors OWNER TO supabase_auth_admin;

--
-- Name: TABLE mfa_factors; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON TABLE auth.mfa_factors IS 'auth: stores metadata about factors';


--
-- Name: one_time_tokens; Type: TABLE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TABLE auth.one_time_tokens (
    id uuid NOT NULL,
    user_id uuid NOT NULL,
    token_type auth.one_time_token_type NOT NULL,
    token_hash text NOT NULL,
    relates_to text NOT NULL,
    created_at timestamp without time zone DEFAULT now() NOT NULL,
    updated_at timestamp without time zone DEFAULT now() NOT NULL,
    CONSTRAINT one_time_tokens_token_hash_check CHECK ((char_length(token_hash) > 0))
);


ALTER TABLE auth.one_time_tokens OWNER TO supabase_auth_admin;

--
-- Name: refresh_tokens; Type: TABLE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TABLE auth.refresh_tokens (
    instance_id uuid,
    id bigint NOT NULL,
    token character varying(255),
    user_id character varying(255),
    revoked boolean,
    created_at timestamp with time zone,
    updated_at timestamp with time zone,
    parent character varying(255),
    session_id uuid
);


ALTER TABLE auth.refresh_tokens OWNER TO supabase_auth_admin;

--
-- Name: TABLE refresh_tokens; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON TABLE auth.refresh_tokens IS 'Auth: Store of tokens used to refresh JWT tokens once they expire.';


--
-- Name: refresh_tokens_id_seq; Type: SEQUENCE; Schema: auth; Owner: supabase_auth_admin
--

CREATE SEQUENCE auth.refresh_tokens_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE auth.refresh_tokens_id_seq OWNER TO supabase_auth_admin;

--
-- Name: refresh_tokens_id_seq; Type: SEQUENCE OWNED BY; Schema: auth; Owner: supabase_auth_admin
--

ALTER SEQUENCE auth.refresh_tokens_id_seq OWNED BY auth.refresh_tokens.id;


--
-- Name: saml_providers; Type: TABLE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TABLE auth.saml_providers (
    id uuid NOT NULL,
    sso_provider_id uuid NOT NULL,
    entity_id text NOT NULL,
    metadata_xml text NOT NULL,
    metadata_url text,
    attribute_mapping jsonb,
    created_at timestamp with time zone,
    updated_at timestamp with time zone,
    name_id_format text,
    CONSTRAINT "entity_id not empty" CHECK ((char_length(entity_id) > 0)),
    CONSTRAINT "metadata_url not empty" CHECK (((metadata_url = NULL::text) OR (char_length(metadata_url) > 0))),
    CONSTRAINT "metadata_xml not empty" CHECK ((char_length(metadata_xml) > 0))
);


ALTER TABLE auth.saml_providers OWNER TO supabase_auth_admin;

--
-- Name: TABLE saml_providers; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON TABLE auth.saml_providers IS 'Auth: Manages SAML Identity Provider connections.';


--
-- Name: saml_relay_states; Type: TABLE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TABLE auth.saml_relay_states (
    id uuid NOT NULL,
    sso_provider_id uuid NOT NULL,
    request_id text NOT NULL,
    for_email text,
    redirect_to text,
    created_at timestamp with time zone,
    updated_at timestamp with time zone,
    flow_state_id uuid,
    CONSTRAINT "request_id not empty" CHECK ((char_length(request_id) > 0))
);


ALTER TABLE auth.saml_relay_states OWNER TO supabase_auth_admin;

--
-- Name: TABLE saml_relay_states; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON TABLE auth.saml_relay_states IS 'Auth: Contains SAML Relay State information for each Service Provider initiated login.';


--
-- Name: schema_migrations; Type: TABLE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TABLE auth.schema_migrations (
    version character varying(255) NOT NULL
);


ALTER TABLE auth.schema_migrations OWNER TO supabase_auth_admin;

--
-- Name: TABLE schema_migrations; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON TABLE auth.schema_migrations IS 'Auth: Manages updates to the auth system.';


--
-- Name: sessions; Type: TABLE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TABLE auth.sessions (
    id uuid NOT NULL,
    user_id uuid NOT NULL,
    created_at timestamp with time zone,
    updated_at timestamp with time zone,
    factor_id uuid,
    aal auth.aal_level,
    not_after timestamp with time zone,
    refreshed_at timestamp without time zone,
    user_agent text,
    ip inet,
    tag text
);


ALTER TABLE auth.sessions OWNER TO supabase_auth_admin;

--
-- Name: TABLE sessions; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON TABLE auth.sessions IS 'Auth: Stores session data associated to a user.';


--
-- Name: COLUMN sessions.not_after; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON COLUMN auth.sessions.not_after IS 'Auth: Not after is a nullable column that contains a timestamp after which the session should be regarded as expired.';


--
-- Name: sso_domains; Type: TABLE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TABLE auth.sso_domains (
    id uuid NOT NULL,
    sso_provider_id uuid NOT NULL,
    domain text NOT NULL,
    created_at timestamp with time zone,
    updated_at timestamp with time zone,
    CONSTRAINT "domain not empty" CHECK ((char_length(domain) > 0))
);


ALTER TABLE auth.sso_domains OWNER TO supabase_auth_admin;

--
-- Name: TABLE sso_domains; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON TABLE auth.sso_domains IS 'Auth: Manages SSO email address domain mapping to an SSO Identity Provider.';


--
-- Name: sso_providers; Type: TABLE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TABLE auth.sso_providers (
    id uuid NOT NULL,
    resource_id text,
    created_at timestamp with time zone,
    updated_at timestamp with time zone,
    CONSTRAINT "resource_id not empty" CHECK (((resource_id = NULL::text) OR (char_length(resource_id) > 0)))
);


ALTER TABLE auth.sso_providers OWNER TO supabase_auth_admin;

--
-- Name: TABLE sso_providers; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON TABLE auth.sso_providers IS 'Auth: Manages SSO identity provider information; see saml_providers for SAML.';


--
-- Name: COLUMN sso_providers.resource_id; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON COLUMN auth.sso_providers.resource_id IS 'Auth: Uniquely identifies a SSO provider according to a user-chosen resource ID (case insensitive), useful in infrastructure as code.';


--
-- Name: users; Type: TABLE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TABLE auth.users (
    instance_id uuid,
    id uuid NOT NULL,
    aud character varying(255),
    role character varying(255),
    email character varying(255),
    encrypted_password character varying(255),
    email_confirmed_at timestamp with time zone,
    invited_at timestamp with time zone,
    confirmation_token character varying(255),
    confirmation_sent_at timestamp with time zone,
    recovery_token character varying(255),
    recovery_sent_at timestamp with time zone,
    email_change_token_new character varying(255),
    email_change character varying(255),
    email_change_sent_at timestamp with time zone,
    last_sign_in_at timestamp with time zone,
    raw_app_meta_data jsonb,
    raw_user_meta_data jsonb,
    is_super_admin boolean,
    created_at timestamp with time zone,
    updated_at timestamp with time zone,
    phone text DEFAULT NULL::character varying,
    phone_confirmed_at timestamp with time zone,
    phone_change text DEFAULT ''::character varying,
    phone_change_token character varying(255) DEFAULT ''::character varying,
    phone_change_sent_at timestamp with time zone,
    confirmed_at timestamp with time zone GENERATED ALWAYS AS (LEAST(email_confirmed_at, phone_confirmed_at)) STORED,
    email_change_token_current character varying(255) DEFAULT ''::character varying,
    email_change_confirm_status smallint DEFAULT 0,
    banned_until timestamp with time zone,
    reauthentication_token character varying(255) DEFAULT ''::character varying,
    reauthentication_sent_at timestamp with time zone,
    is_sso_user boolean DEFAULT false NOT NULL,
    deleted_at timestamp with time zone,
    is_anonymous boolean DEFAULT false NOT NULL,
    CONSTRAINT users_email_change_confirm_status_check CHECK (((email_change_confirm_status >= 0) AND (email_change_confirm_status <= 2)))
);


ALTER TABLE auth.users OWNER TO supabase_auth_admin;

--
-- Name: TABLE users; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON TABLE auth.users IS 'Auth: Stores user login data within a secure schema.';


--
-- Name: COLUMN users.is_sso_user; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON COLUMN auth.users.is_sso_user IS 'Auth: Set this column to true when the account comes from SSO. These accounts can have duplicate emails.';


--
-- Name: assignment_submissions; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.assignment_submissions (
    id bigint NOT NULL,
    assignment_id bigint NOT NULL,
    student_id bigint NOT NULL,
    file_path character varying(255) NOT NULL,
    original_filename character varying(255) DEFAULT NULL::character varying,
    status character varying(50) DEFAULT 'Submitted'::character varying NOT NULL,
    rejection_reason text,
    submitted_at timestamp with time zone DEFAULT now() NOT NULL,
    evaluated_at timestamp with time zone,
    rejection_count integer DEFAULT 0 NOT NULL
);


ALTER TABLE public.assignment_submissions OWNER TO postgres;

--
-- Name: assignment_submissions_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.assignment_submissions ALTER COLUMN id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.assignment_submissions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: assignments; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.assignments (
    id bigint NOT NULL,
    teacher_id bigint NOT NULL,
    school_id integer NOT NULL,
    standard character varying(50) NOT NULL,
    subject character varying(100) NOT NULL,
    title character varying(255) NOT NULL,
    description text NOT NULL,
    file_path character varying(255) DEFAULT NULL::character varying,
    original_filename character varying(255) DEFAULT NULL::character varying,
    due_date date,
    created_at timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.assignments OWNER TO postgres;

--
-- Name: assignments_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.assignments ALTER COLUMN id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.assignments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: attendance; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.attendance (
    id bigint NOT NULL,
    student_id integer NOT NULL,
    teacher_id integer NOT NULL,
    school_id integer NOT NULL,
    standard character varying(10) NOT NULL,
    subject character varying(100) NOT NULL,
    period_number integer NOT NULL,
    attendance_date date NOT NULL,
    status public.attendance_status NOT NULL
);


ALTER TABLE public.attendance OWNER TO postgres;

--
-- Name: attendance_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.attendance ALTER COLUMN id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.attendance_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: book_requests; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.book_requests (
    request_id bigint NOT NULL,
    requester_id bigint NOT NULL,
    requester_role public.book_requester_role NOT NULL,
    school_id bigint NOT NULL,
    book_title character varying(255) NOT NULL,
    author character varying(255) DEFAULT NULL::character varying,
    reason text,
    status public.borrow_request_status DEFAULT 'Pending'::public.borrow_request_status NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.book_requests OWNER TO postgres;

--
-- Name: book_requests_request_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.book_requests ALTER COLUMN request_id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.book_requests_request_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: books; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.books (
    book_id bigint NOT NULL,
    school_id bigint NOT NULL,
    title character varying(255) NOT NULL,
    author character varying(255) NOT NULL,
    isbn character varying(20) DEFAULT NULL::character varying,
    publisher character varying(255) DEFAULT NULL::character varying,
    quantity_total integer DEFAULT 0 NOT NULL,
    quantity_available integer DEFAULT 0 NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    file_path character varying(255) DEFAULT NULL::character varying,
    is_digital boolean DEFAULT false NOT NULL
);


ALTER TABLE public.books OWNER TO postgres;

--
-- Name: books_book_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.books ALTER COLUMN book_id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.books_book_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: borrow_requests; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.borrow_requests (
    request_id bigint NOT NULL,
    book_id bigint NOT NULL,
    school_id bigint NOT NULL,
    borrower_id bigint NOT NULL,
    borrower_role public.borrow_requester_role NOT NULL,
    requested_due_date date,
    request_date timestamp with time zone DEFAULT now() NOT NULL,
    status public.borrow_request_status DEFAULT 'Pending'::public.borrow_request_status NOT NULL,
    librarian_id bigint,
    action_date timestamp with time zone,
    rejection_reason text,
    due_date date
);


ALTER TABLE public.borrow_requests OWNER TO postgres;

--
-- Name: borrow_requests_request_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.borrow_requests ALTER COLUMN request_id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.borrow_requests_request_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: borrowing_records; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.borrowing_records (
    record_id bigint NOT NULL,
    book_id bigint NOT NULL,
    borrower_id bigint NOT NULL,
    borrower_role public.borrower_role NOT NULL,
    checkout_date date NOT NULL,
    due_date date NOT NULL,
    return_date date,
    is_returned boolean DEFAULT false NOT NULL,
    fine_amount numeric(10,2) DEFAULT 0.00 NOT NULL,
    fine_status public.fine_status DEFAULT 'Unpaid'::public.fine_status NOT NULL
);


ALTER TABLE public.borrowing_records OWNER TO postgres;

--
-- Name: borrowing_records_record_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.borrowing_records ALTER COLUMN record_id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.borrowing_records_record_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: deleted_books; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.deleted_books (
    archived_book_id bigint NOT NULL,
    original_book_id integer NOT NULL,
    title character varying(255) NOT NULL,
    author character varying(255) NOT NULL,
    isbn character varying(25) DEFAULT NULL::character varying,
    quantity_total integer,
    school_id integer NOT NULL,
    is_digital boolean,
    deleted_at timestamp with time zone DEFAULT now() NOT NULL,
    deleted_by_role character varying(50) DEFAULT NULL::character varying
);


ALTER TABLE public.deleted_books OWNER TO postgres;

--
-- Name: deleted_books_archived_book_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.deleted_books ALTER COLUMN archived_book_id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.deleted_books_archived_book_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: deleted_librarians; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.deleted_librarians (
    id bigint NOT NULL,
    librarian_name character varying(100) DEFAULT NULL::character varying,
    email character varying(100) DEFAULT NULL::character varying,
    phone character varying(15) DEFAULT NULL::character varying,
    dob date,
    gender public.gender_enum_mfo,
    blood_group public.blood_group_enum,
    address text,
    qualification character varying(100) DEFAULT NULL::character varying,
    salary numeric(10,2) DEFAULT NULL::numeric,
    school_id integer,
    deleted_by_role character varying(50) DEFAULT NULL::character varying,
    deleted_at timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.deleted_librarians OWNER TO postgres;

--
-- Name: deleted_librarians_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.deleted_librarians ALTER COLUMN id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.deleted_librarians_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: deleted_principals; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.deleted_principals (
    id bigint NOT NULL,
    principal_name character varying(100) DEFAULT NULL::character varying,
    email character varying(100) DEFAULT NULL::character varying,
    phone character varying(15) DEFAULT NULL::character varying,
    dob date,
    gender public.gender_enum_mfo_lower,
    blood_group public.blood_group_enum,
    address text,
    qualification character varying(100) DEFAULT NULL::character varying,
    salary numeric(10,2) DEFAULT NULL::numeric,
    batch public.batch_enum,
    school_id integer,
    deleted_by_role character varying(50) DEFAULT NULL::character varying,
    deleted_at timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.deleted_principals OWNER TO postgres;

--
-- Name: deleted_principals_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.deleted_principals ALTER COLUMN id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.deleted_principals_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: deleted_schools; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.deleted_schools (
    id bigint NOT NULL,
    school_logo character varying(255) DEFAULT NULL::character varying,
    school_name character varying(100) DEFAULT NULL::character varying,
    email character varying(50) DEFAULT NULL::character varying,
    phone character varying(10) DEFAULT NULL::character varying,
    school_opening date,
    school_type public.school_type,
    education_board text[],
    school_medium text[],
    school_category text[],
    address text,
    deleted_by_role character varying(50) DEFAULT NULL::character varying,
    deleted_at timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.deleted_schools OWNER TO postgres;

--
-- Name: deleted_schools_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.deleted_schools ALTER COLUMN id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.deleted_schools_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: deleted_students; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.deleted_students (
    id bigint NOT NULL,
    student_name character varying(100) DEFAULT NULL::character varying,
    email character varying(100) DEFAULT NULL::character varying,
    rollno character varying(20) DEFAULT NULL::character varying,
    std character varying(10) DEFAULT NULL::character varying,
    academic_year character varying(10) DEFAULT NULL::character varying,
    dob date,
    gender public.gender_enum_mfo_lower,
    blood_group public.blood_group_enum,
    address text,
    father_name character varying(100) DEFAULT NULL::character varying,
    father_phone character varying(15) DEFAULT NULL::character varying,
    mother_name character varying(100) DEFAULT NULL::character varying,
    mother_phone character varying(15) DEFAULT NULL::character varying,
    school_id integer,
    reason_for_leaving text,
    deleted_by_role character varying(50) DEFAULT NULL::character varying,
    deleted_at timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.deleted_students OWNER TO postgres;

--
-- Name: deleted_students_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.deleted_students ALTER COLUMN id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.deleted_students_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: deleted_teachers; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.deleted_teachers (
    id bigint NOT NULL,
    teacher_name character varying(100) DEFAULT NULL::character varying,
    email character varying(100) DEFAULT NULL::character varying,
    phone character varying(15) DEFAULT NULL::character varying,
    gender public.gender_enum_mfo_lower,
    dob date,
    blood_group public.blood_group_enum,
    address text,
    school_id integer,
    qualification character varying(100) DEFAULT NULL::character varying,
    subject character varying(100) DEFAULT NULL::character varying,
    language_known character varying(100) DEFAULT NULL::character varying,
    salary numeric(10,2) DEFAULT NULL::numeric,
    std text[],
    experience character varying(50) DEFAULT NULL::character varying,
    batch public.batch_enum,
    class_teacher boolean,
    class_teacher_std character varying(10) DEFAULT NULL::character varying,
    deleted_by_role character varying(50) DEFAULT NULL::character varying,
    deleted_at timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.deleted_teachers OWNER TO postgres;

--
-- Name: deleted_teachers_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.deleted_teachers ALTER COLUMN id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.deleted_teachers_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: exam_timetables; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.exam_timetables (
    id bigint NOT NULL,
    principal_id bigint NOT NULL,
    school_id bigint NOT NULL,
    title character varying(255) NOT NULL,
    description text,
    file_path character varying(255) NOT NULL,
    original_filename character varying(255) NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.exam_timetables OWNER TO postgres;

--
-- Name: exam_timetables_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.exam_timetables ALTER COLUMN id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.exam_timetables_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: leave_applications; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.leave_applications (
    id bigint NOT NULL,
    teacher_id bigint NOT NULL,
    from_date date NOT NULL,
    to_date date NOT NULL,
    reason text NOT NULL,
    leave_type character varying(20) DEFAULT 'Full Day'::character varying NOT NULL,
    status character varying(20) DEFAULT 'Pending'::character varying NOT NULL,
    applied_on timestamp with time zone DEFAULT now() NOT NULL,
    rejection_reason text
);


ALTER TABLE public.leave_applications OWNER TO postgres;

--
-- Name: leave_applications_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.leave_applications ALTER COLUMN id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.leave_applications_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: librarian; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.librarian (
    id bigint NOT NULL,
    librarian_image character varying(255) DEFAULT NULL::character varying,
    librarian_name character varying(50) NOT NULL,
    school_id bigint NOT NULL,
    email character varying(50) NOT NULL,
    password character varying(255) NOT NULL,
    phone character varying(10) NOT NULL,
    dob date NOT NULL,
    gender public.gender_enum_mfo NOT NULL,
    blood_group public.blood_group_enum NOT NULL,
    address text,
    qualification character varying(100) DEFAULT NULL::character varying,
    salary numeric(10,2) DEFAULT NULL::numeric
);


ALTER TABLE public.librarian OWNER TO postgres;

--
-- Name: librarian_attendance; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.librarian_attendance (
    attendance_id bigint NOT NULL,
    librarian_id bigint NOT NULL,
    school_id bigint NOT NULL,
    attendance_date date NOT NULL,
    status public.teacher_attendance_status NOT NULL,
    remark character varying(255) DEFAULT NULL::character varying,
    marked_by_user_id bigint,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.librarian_attendance OWNER TO postgres;

--
-- Name: librarian_attendance_attendance_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.librarian_attendance ALTER COLUMN attendance_id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.librarian_attendance_attendance_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: librarian_timings; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.librarian_timings (
    timing_id bigint NOT NULL,
    librarian_id bigint NOT NULL,
    day_of_week public.day_of_week NOT NULL,
    opens_at time without time zone,
    closes_at time without time zone,
    is_closed boolean DEFAULT false NOT NULL
);


ALTER TABLE public.librarian_timings OWNER TO postgres;

--
-- Name: librarian_timings_timing_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.librarian_timings ALTER COLUMN timing_id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.librarian_timings_timing_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: messages; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.messages (
    id bigint NOT NULL,
    sender_id bigint NOT NULL,
    receiver_id bigint NOT NULL,
    message_text text NOT NULL,
    "timestamp" timestamp with time zone DEFAULT now() NOT NULL,
    is_read boolean DEFAULT false NOT NULL
);


ALTER TABLE public.messages OWNER TO postgres;

--
-- Name: messages_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.messages ALTER COLUMN id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.messages_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: notes; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.notes (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    school_id integer,
    target_standard character varying(50) DEFAULT NULL::character varying,
    title character varying(255) NOT NULL,
    content text NOT NULL,
    file_path character varying(255) DEFAULT NULL::character varying,
    original_filename character varying(255) DEFAULT NULL::character varying,
    created_at timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.notes OWNER TO postgres;

--
-- Name: notes_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.notes ALTER COLUMN id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.notes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: notice; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.notice (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    title character varying(255) NOT NULL,
    content text NOT NULL,
    file_path character varying(255) DEFAULT NULL::character varying,
    original_filename character varying(255) DEFAULT NULL::character varying,
    created_at timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.notice OWNER TO postgres;

--
-- Name: notice_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.notice ALTER COLUMN id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.notice_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: notifications; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.notifications (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    message character varying(255) NOT NULL,
    link character varying(255) DEFAULT NULL::character varying,
    is_read boolean DEFAULT false NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    type character varying(50) DEFAULT NULL::character varying
);


ALTER TABLE public.notifications OWNER TO postgres;

--
-- Name: notifications_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.notifications ALTER COLUMN id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.notifications_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: password_resets; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.password_resets (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    email character varying(255) NOT NULL,
    otp_hash character varying(255) NOT NULL,
    expires_at timestamp with time zone NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.password_resets OWNER TO postgres;

--
-- Name: password_resets_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.password_resets ALTER COLUMN id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.password_resets_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: principal; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.principal (
    id bigint NOT NULL,
    principal_image character varying(255) DEFAULT NULL::character varying,
    school_id bigint,
    principal_name character varying(50) DEFAULT NULL::character varying,
    email character varying(50) NOT NULL,
    password character varying(255) NOT NULL,
    phone character varying(10) DEFAULT NULL::character varying,
    dob date,
    gender public.gender_enum_mfo NOT NULL,
    blood_group public.blood_group_enum NOT NULL,
    address text,
    qualification character varying(50) DEFAULT NULL::character varying,
    salary numeric(10,2) DEFAULT NULL::numeric,
    batch public.batch_enum
);


ALTER TABLE public.principal OWNER TO postgres;

--
-- Name: principal_attendance; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.principal_attendance (
    id bigint NOT NULL,
    principal_id bigint NOT NULL,
    school_id bigint NOT NULL,
    attendance_date date NOT NULL,
    status public.attendance_status NOT NULL,
    login_latitude numeric(10,8) DEFAULT NULL::numeric,
    login_longitude numeric(11,8) DEFAULT NULL::numeric,
    login_time time without time zone NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.principal_attendance OWNER TO postgres;

--
-- Name: principal_attendance_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.principal_attendance ALTER COLUMN id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.principal_attendance_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: principal_timings; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.principal_timings (
    timing_id bigint NOT NULL,
    principal_id bigint NOT NULL,
    day_of_week public.day_of_week NOT NULL,
    opens_at time without time zone,
    closes_at time without time zone,
    is_closed boolean DEFAULT false NOT NULL
);


ALTER TABLE public.principal_timings OWNER TO postgres;

--
-- Name: principal_timings_timing_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.principal_timings ALTER COLUMN timing_id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.principal_timings_timing_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: principal_to_bmc_notices; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.principal_to_bmc_notices (
    id bigint NOT NULL,
    principal_id bigint NOT NULL,
    school_id bigint NOT NULL,
    title character varying(255) NOT NULL,
    content text NOT NULL,
    file_path character varying(255) DEFAULT NULL::character varying,
    original_filename character varying(255) DEFAULT NULL::character varying,
    created_at timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.principal_to_bmc_notices OWNER TO postgres;

--
-- Name: principal_to_bmc_notices_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.principal_to_bmc_notices ALTER COLUMN id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.principal_to_bmc_notices_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: principal_to_librarian_notices; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.principal_to_librarian_notices (
    id bigint NOT NULL,
    principal_id bigint NOT NULL,
    school_id bigint NOT NULL,
    title character varying(255) NOT NULL,
    content text NOT NULL,
    file_path character varying(255) DEFAULT NULL::character varying,
    original_filename character varying(255) DEFAULT NULL::character varying,
    created_at timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.principal_to_librarian_notices OWNER TO postgres;

--
-- Name: principal_to_librarian_notices_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.principal_to_librarian_notices ALTER COLUMN id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.principal_to_librarian_notices_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: school; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.school (
    id bigint NOT NULL,
    school_logo character varying(255) DEFAULT NULL::character varying,
    school_name character varying(100) DEFAULT NULL::character varying,
    email character varying(50) DEFAULT NULL::character varying,
    phone character varying(10) DEFAULT NULL::character varying,
    school_opening date,
    school_type public.school_type,
    education_board text[],
    school_medium text[],
    school_category text[],
    address text,
    latitude numeric(10,8) DEFAULT NULL::numeric,
    longitude numeric(11,8) DEFAULT NULL::numeric,
    passing_percentage numeric(5,2) DEFAULT 33.00 NOT NULL
);


ALTER TABLE public.school OWNER TO postgres;

--
-- Name: school_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.school ALTER COLUMN id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.school_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: school_notice_recipients; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.school_notice_recipients (
    id bigint NOT NULL,
    notice_id bigint NOT NULL,
    recipient_type public.recipient_type NOT NULL,
    recipient_identifier character varying(50) NOT NULL
);


ALTER TABLE public.school_notice_recipients OWNER TO postgres;

--
-- Name: school_notice_recipients_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.school_notice_recipients ALTER COLUMN id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.school_notice_recipients_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: school_notices_content; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.school_notices_content (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    school_id bigint NOT NULL,
    title character varying(255) NOT NULL,
    content text NOT NULL,
    file_path character varying(255) DEFAULT NULL::character varying,
    original_filename character varying(255) DEFAULT NULL::character varying,
    created_at timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.school_notices_content OWNER TO postgres;

--
-- Name: school_notices_content_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.school_notices_content ALTER COLUMN id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.school_notices_content_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: school_timetable; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.school_timetable (
    id bigint NOT NULL,
    school_id bigint NOT NULL,
    standard character varying(10) NOT NULL,
    day_of_week public.day_of_week NOT NULL,
    period_number integer NOT NULL,
    subject_name character varying(100) NOT NULL,
    teacher_id bigint NOT NULL,
    start_time time without time zone NOT NULL,
    end_time time without time zone NOT NULL
);


ALTER TABLE public.school_timetable OWNER TO postgres;

--
-- Name: school_timetable_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.school_timetable ALTER COLUMN id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.school_timetable_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: standard_subjects; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.standard_subjects (
    std_subject_id bigint NOT NULL,
    standard character varying(10) NOT NULL,
    subject_id bigint NOT NULL
);


ALTER TABLE public.standard_subjects OWNER TO postgres;

--
-- Name: standard_subjects_std_subject_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.standard_subjects ALTER COLUMN std_subject_id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.standard_subjects_std_subject_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: student; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.student (
    id bigint NOT NULL,
    student_image character varying(255) DEFAULT NULL::character varying,
    student_name character varying(50) DEFAULT NULL::character varying,
    rollno character varying(10) DEFAULT NULL::character varying,
    std character varying(4) DEFAULT NULL::character varying,
    email character varying(50) NOT NULL,
    password character varying(255) NOT NULL,
    academic_year character varying(9) DEFAULT NULL::character varying,
    school_id bigint,
    dob date,
    gender public.gender_enum_mfo_lower,
    blood_group public.blood_group_enum,
    address text,
    father_name character varying(50) DEFAULT NULL::character varying,
    father_phone character varying(10) DEFAULT NULL::character varying,
    mother_name character varying(50) DEFAULT NULL::character varying,
    mother_phone character varying(10) DEFAULT NULL::character varying
);


ALTER TABLE public.student OWNER TO postgres;

--
-- Name: student_marks; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.student_marks (
    mark_id bigint NOT NULL,
    student_id bigint NOT NULL,
    school_id bigint NOT NULL,
    academic_year character varying(10) NOT NULL,
    std character varying(10) NOT NULL,
    division character varying(5) NOT NULL,
    exam_type character varying(100) NOT NULL,
    subject_name character varying(100) NOT NULL,
    marks_obtained numeric(5,2) NOT NULL,
    total_marks numeric(5,2) DEFAULT 100.00 NOT NULL,
    entry_date timestamp with time zone DEFAULT now() NOT NULL,
    entered_by_user_id bigint
);


ALTER TABLE public.student_marks OWNER TO postgres;

--
-- Name: student_marks_mark_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.student_marks ALTER COLUMN mark_id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.student_marks_mark_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: subjects; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.subjects (
    subject_id bigint NOT NULL,
    subject_name character varying(100) NOT NULL
);


ALTER TABLE public.subjects OWNER TO postgres;

--
-- Name: subjects_subject_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.subjects ALTER COLUMN subject_id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.subjects_subject_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: teacher; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.teacher (
    id bigint NOT NULL,
    teacher_image character varying(255) DEFAULT NULL::character varying,
    teacher_name character varying(50) NOT NULL,
    phone character varying(10) NOT NULL,
    school_id bigint NOT NULL,
    dob date NOT NULL,
    gender public.gender_enum_mfo NOT NULL,
    blood_group public.blood_group_enum NOT NULL,
    address text,
    email character varying(50) NOT NULL,
    password character varying(255) NOT NULL,
    qualification character varying(100) DEFAULT NULL::character varying,
    subject character varying(100) DEFAULT NULL::character varying,
    language_known character varying(100) DEFAULT NULL::character varying,
    salary integer,
    std text[],
    experience character varying(10) DEFAULT NULL::character varying,
    batch public.batch_enum,
    class_teacher boolean DEFAULT false,
    class_teacher_std character varying(50) DEFAULT NULL::character varying
);


ALTER TABLE public.teacher OWNER TO postgres;

--
-- Name: teacher_attendance; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.teacher_attendance (
    attendance_id bigint NOT NULL,
    teacher_id bigint NOT NULL,
    school_id bigint NOT NULL,
    attendance_date date NOT NULL,
    status public.teacher_attendance_status NOT NULL,
    remark character varying(255) DEFAULT NULL::character varying,
    marked_by_user_id bigint,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.teacher_attendance OWNER TO postgres;

--
-- Name: teacher_attendance_attendance_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.teacher_attendance ALTER COLUMN attendance_id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.teacher_attendance_attendance_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: teacher_timings; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.teacher_timings (
    timing_id bigint NOT NULL,
    teacher_id bigint NOT NULL,
    day_of_week public.day_of_week NOT NULL,
    opens_at time without time zone,
    closes_at time without time zone,
    is_closed boolean DEFAULT false NOT NULL
);


ALTER TABLE public.teacher_timings OWNER TO postgres;

--
-- Name: teacher_timings_timing_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.teacher_timings ALTER COLUMN timing_id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.teacher_timings_timing_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: timetables; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.timetables (
    id bigint NOT NULL,
    school_id bigint NOT NULL,
    standard character varying(50) NOT NULL,
    class_teacher_id bigint NOT NULL,
    timetable_file character varying(255) NOT NULL,
    original_filename character varying(255) NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.timetables OWNER TO postgres;

--
-- Name: timetables_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.timetables ALTER COLUMN id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.timetables_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: users; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    role public.user_role NOT NULL,
    email character varying(50) NOT NULL,
    password character varying(255) NOT NULL,
    account_status public.account_status DEFAULT 'active'::public.account_status NOT NULL,
    otp_hash character varying(255),
    otp_expires_at timestamp without time zone
);


ALTER TABLE public.users OWNER TO postgres;

--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.users ALTER COLUMN id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: messages; Type: TABLE; Schema: realtime; Owner: supabase_realtime_admin
--

CREATE TABLE realtime.messages (
    topic text NOT NULL,
    extension text NOT NULL,
    payload jsonb,
    event text,
    private boolean DEFAULT false,
    updated_at timestamp without time zone DEFAULT now() NOT NULL,
    inserted_at timestamp without time zone DEFAULT now() NOT NULL,
    id uuid DEFAULT gen_random_uuid() NOT NULL
)
PARTITION BY RANGE (inserted_at);


ALTER TABLE realtime.messages OWNER TO supabase_realtime_admin;

--
-- Name: schema_migrations; Type: TABLE; Schema: realtime; Owner: supabase_admin
--

CREATE TABLE realtime.schema_migrations (
    version bigint NOT NULL,
    inserted_at timestamp(0) without time zone
);


ALTER TABLE realtime.schema_migrations OWNER TO supabase_admin;

--
-- Name: subscription; Type: TABLE; Schema: realtime; Owner: supabase_admin
--

CREATE TABLE realtime.subscription (
    id bigint NOT NULL,
    subscription_id uuid NOT NULL,
    entity regclass NOT NULL,
    filters realtime.user_defined_filter[] DEFAULT '{}'::realtime.user_defined_filter[] NOT NULL,
    claims jsonb NOT NULL,
    claims_role regrole GENERATED ALWAYS AS (realtime.to_regrole((claims ->> 'role'::text))) STORED NOT NULL,
    created_at timestamp without time zone DEFAULT timezone('utc'::text, now()) NOT NULL
);


ALTER TABLE realtime.subscription OWNER TO supabase_admin;

--
-- Name: subscription_id_seq; Type: SEQUENCE; Schema: realtime; Owner: supabase_admin
--

ALTER TABLE realtime.subscription ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME realtime.subscription_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: buckets; Type: TABLE; Schema: storage; Owner: supabase_storage_admin
--

CREATE TABLE storage.buckets (
    id text NOT NULL,
    name text NOT NULL,
    owner uuid,
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now(),
    public boolean DEFAULT false,
    avif_autodetection boolean DEFAULT false,
    file_size_limit bigint,
    allowed_mime_types text[],
    owner_id text
);


ALTER TABLE storage.buckets OWNER TO supabase_storage_admin;

--
-- Name: COLUMN buckets.owner; Type: COMMENT; Schema: storage; Owner: supabase_storage_admin
--

COMMENT ON COLUMN storage.buckets.owner IS 'Field is deprecated, use owner_id instead';


--
-- Name: migrations; Type: TABLE; Schema: storage; Owner: supabase_storage_admin
--

CREATE TABLE storage.migrations (
    id integer NOT NULL,
    name character varying(100) NOT NULL,
    hash character varying(40) NOT NULL,
    executed_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE storage.migrations OWNER TO supabase_storage_admin;

--
-- Name: objects; Type: TABLE; Schema: storage; Owner: supabase_storage_admin
--

CREATE TABLE storage.objects (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    bucket_id text,
    name text,
    owner uuid,
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now(),
    last_accessed_at timestamp with time zone DEFAULT now(),
    metadata jsonb,
    path_tokens text[] GENERATED ALWAYS AS (string_to_array(name, '/'::text)) STORED,
    version text,
    owner_id text,
    user_metadata jsonb
);


ALTER TABLE storage.objects OWNER TO supabase_storage_admin;

--
-- Name: COLUMN objects.owner; Type: COMMENT; Schema: storage; Owner: supabase_storage_admin
--

COMMENT ON COLUMN storage.objects.owner IS 'Field is deprecated, use owner_id instead';


--
-- Name: s3_multipart_uploads; Type: TABLE; Schema: storage; Owner: supabase_storage_admin
--

CREATE TABLE storage.s3_multipart_uploads (
    id text NOT NULL,
    in_progress_size bigint DEFAULT 0 NOT NULL,
    upload_signature text NOT NULL,
    bucket_id text NOT NULL,
    key text NOT NULL COLLATE pg_catalog."C",
    version text NOT NULL,
    owner_id text,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    user_metadata jsonb
);


ALTER TABLE storage.s3_multipart_uploads OWNER TO supabase_storage_admin;

--
-- Name: s3_multipart_uploads_parts; Type: TABLE; Schema: storage; Owner: supabase_storage_admin
--

CREATE TABLE storage.s3_multipart_uploads_parts (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    upload_id text NOT NULL,
    size bigint DEFAULT 0 NOT NULL,
    part_number integer NOT NULL,
    bucket_id text NOT NULL,
    key text NOT NULL COLLATE pg_catalog."C",
    etag text NOT NULL,
    owner_id text,
    version text NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE storage.s3_multipart_uploads_parts OWNER TO supabase_storage_admin;

--
-- Name: refresh_tokens id; Type: DEFAULT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.refresh_tokens ALTER COLUMN id SET DEFAULT nextval('auth.refresh_tokens_id_seq'::regclass);


--
-- Data for Name: audit_log_entries; Type: TABLE DATA; Schema: auth; Owner: supabase_auth_admin
--

COPY auth.audit_log_entries (instance_id, id, payload, created_at, ip_address) FROM stdin;
\.


--
-- Data for Name: flow_state; Type: TABLE DATA; Schema: auth; Owner: supabase_auth_admin
--

COPY auth.flow_state (id, user_id, auth_code, code_challenge_method, code_challenge, provider_type, provider_access_token, provider_refresh_token, created_at, updated_at, authentication_method, auth_code_issued_at) FROM stdin;
\.


--
-- Data for Name: identities; Type: TABLE DATA; Schema: auth; Owner: supabase_auth_admin
--

COPY auth.identities (provider_id, user_id, identity_data, provider, last_sign_in_at, created_at, updated_at, id) FROM stdin;
\.


--
-- Data for Name: instances; Type: TABLE DATA; Schema: auth; Owner: supabase_auth_admin
--

COPY auth.instances (id, uuid, raw_base_config, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: mfa_amr_claims; Type: TABLE DATA; Schema: auth; Owner: supabase_auth_admin
--

COPY auth.mfa_amr_claims (session_id, created_at, updated_at, authentication_method, id) FROM stdin;
\.


--
-- Data for Name: mfa_challenges; Type: TABLE DATA; Schema: auth; Owner: supabase_auth_admin
--

COPY auth.mfa_challenges (id, factor_id, created_at, verified_at, ip_address, otp_code, web_authn_session_data) FROM stdin;
\.


--
-- Data for Name: mfa_factors; Type: TABLE DATA; Schema: auth; Owner: supabase_auth_admin
--

COPY auth.mfa_factors (id, user_id, friendly_name, factor_type, status, created_at, updated_at, secret, phone, last_challenged_at, web_authn_credential, web_authn_aaguid) FROM stdin;
\.


--
-- Data for Name: one_time_tokens; Type: TABLE DATA; Schema: auth; Owner: supabase_auth_admin
--

COPY auth.one_time_tokens (id, user_id, token_type, token_hash, relates_to, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: refresh_tokens; Type: TABLE DATA; Schema: auth; Owner: supabase_auth_admin
--

COPY auth.refresh_tokens (instance_id, id, token, user_id, revoked, created_at, updated_at, parent, session_id) FROM stdin;
\.


--
-- Data for Name: saml_providers; Type: TABLE DATA; Schema: auth; Owner: supabase_auth_admin
--

COPY auth.saml_providers (id, sso_provider_id, entity_id, metadata_xml, metadata_url, attribute_mapping, created_at, updated_at, name_id_format) FROM stdin;
\.


--
-- Data for Name: saml_relay_states; Type: TABLE DATA; Schema: auth; Owner: supabase_auth_admin
--

COPY auth.saml_relay_states (id, sso_provider_id, request_id, for_email, redirect_to, created_at, updated_at, flow_state_id) FROM stdin;
\.


--
-- Data for Name: schema_migrations; Type: TABLE DATA; Schema: auth; Owner: supabase_auth_admin
--

COPY auth.schema_migrations (version) FROM stdin;
20171026211738
20171026211808
20171026211834
20180103212743
20180108183307
20180119214651
20180125194653
00
20210710035447
20210722035447
20210730183235
20210909172000
20210927181326
20211122151130
20211124214934
20211202183645
20220114185221
20220114185340
20220224000811
20220323170000
20220429102000
20220531120530
20220614074223
20220811173540
20221003041349
20221003041400
20221011041400
20221020193600
20221021073300
20221021082433
20221027105023
20221114143122
20221114143410
20221125140132
20221208132122
20221215195500
20221215195800
20221215195900
20230116124310
20230116124412
20230131181311
20230322519590
20230402418590
20230411005111
20230508135423
20230523124323
20230818113222
20230914180801
20231027141322
20231114161723
20231117164230
20240115144230
20240214120130
20240306115329
20240314092811
20240427152123
20240612123726
20240729123726
20240802193726
20240806073726
20241009103726
\.


--
-- Data for Name: sessions; Type: TABLE DATA; Schema: auth; Owner: supabase_auth_admin
--

COPY auth.sessions (id, user_id, created_at, updated_at, factor_id, aal, not_after, refreshed_at, user_agent, ip, tag) FROM stdin;
\.


--
-- Data for Name: sso_domains; Type: TABLE DATA; Schema: auth; Owner: supabase_auth_admin
--

COPY auth.sso_domains (id, sso_provider_id, domain, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: sso_providers; Type: TABLE DATA; Schema: auth; Owner: supabase_auth_admin
--

COPY auth.sso_providers (id, resource_id, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: users; Type: TABLE DATA; Schema: auth; Owner: supabase_auth_admin
--

COPY auth.users (instance_id, id, aud, role, email, encrypted_password, email_confirmed_at, invited_at, confirmation_token, confirmation_sent_at, recovery_token, recovery_sent_at, email_change_token_new, email_change, email_change_sent_at, last_sign_in_at, raw_app_meta_data, raw_user_meta_data, is_super_admin, created_at, updated_at, phone, phone_confirmed_at, phone_change, phone_change_token, phone_change_sent_at, email_change_token_current, email_change_confirm_status, banned_until, reauthentication_token, reauthentication_sent_at, is_sso_user, deleted_at, is_anonymous) FROM stdin;
\.


--
-- Data for Name: assignment_submissions; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.assignment_submissions (id, assignment_id, student_id, file_path, original_filename, status, rejection_reason, submitted_at, evaluated_at, rejection_count) FROM stdin;
\.


--
-- Data for Name: assignments; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.assignments (id, teacher_id, school_id, standard, subject, title, description, file_path, original_filename, due_date, created_at) FROM stdin;
3	6	4	11	maths	maths	chbjdcj	/BMC-SMS/pages/assignments/uploads/assign_688223fef08ce9.86748149_INTERNSHIP REGISTRATION FORM JAY (4).pdf	INTERNSHIP REGISTRATION FORM JAY (4).pdf	2025-08-17	2025-07-24 12:15:58+00
4	6	4	8	maths	vfvf	dfvfdv	/BMC-SMS/pages/assignments/uploads/assign_688364726c8816.85613585_INTERNSHIP REGISTRATION FORM JAY (4) (1) (1) (1).pdf	INTERNSHIP REGISTRATION FORM JAY (4) (1) (1) (1).pdf	2025-02-02	2025-07-25 11:03:14+00
5	6	4	11	maths	cjbdcn	cm d cm	/BMC-SMS/pages/assignments/uploads/assign_6883672a4665c9.17531706_INTERNSHIP REGISTRATION FORM JAY (4) (1) (1) (1).pdf	INTERNSHIP REGISTRATION FORM JAY (4) (1) (1) (1).pdf	2025-08-17	2025-07-25 11:14:50+00
6	6	4	11	maths	test	testing	/BMC-SMS/pages/assignments/uploads/assign_6888bc6286f051.53392889_💻 Case Study.pdf	💻 Case Study.pdf	2025-08-01	2025-07-29 12:19:46+00
7	6	4	11	maths	hyy	fgbdb	\N	\N	2025-08-17	2025-07-29 12:43:57+00
8	6	4	11	maths	fsdgfasf	dasfaffffffffff	\N	\N	2025-08-05	2025-07-31 09:28:40+00
9	6	4	11	maths	sggtwgt	fwswefwfwew	\N	\N	2025-08-09	2025-07-31 09:29:44+00
10	6	4	11	maths	fenil	fenillllll	\N	\N	2025-08-01	2025-07-31 09:37:21+00
11	6	4	11	maths	sff	dadadwefwfe	\N	\N	2025-08-02	2025-07-31 09:48:34+00
12	6	4	11	maths	ddasdsfd	asfasf	\N	\N	2025-02-03	2025-07-31 10:05:12+00
13	6	4	11	maths	bjb	csv	\N	\N	2025-12-12	2025-07-31 10:09:34+00
14	6	4	11	maths	ssff	asdsaf	\N	\N	2026-01-30	2025-07-31 10:13:51+00
15	6	4	11	maths	qwfdadfdd	dasfs	\N	\N	2025-08-01	2025-07-31 10:15:45+00
16	6	4	11	maths	asfffascsa	csaf	\N	\N	2025-08-01	2025-07-31 12:11:36+00
\.


--
-- Data for Name: attendance; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.attendance (id, student_id, teacher_id, school_id, standard, subject, period_number, attendance_date, status) FROM stdin;
1	3	6	4	11	0	2	2025-07-28	Absent
\.


--
-- Data for Name: book_requests; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.book_requests (request_id, requester_id, requester_role, school_id, book_title, author, reason, status, created_at) FROM stdin;
\.


--
-- Data for Name: books; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.books (book_id, school_id, title, author, isbn, publisher, quantity_total, quantity_available, created_at, file_path, is_digital) FROM stdin;
1	4	Clean Code: A Handbook of Agile Software Craftsmanship	Robert C. Martin	9780132350884	Prentice Hall	10	9	2025-08-04 15:41:08+00	\N	f
\.


--
-- Data for Name: borrow_requests; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.borrow_requests (request_id, book_id, school_id, borrower_id, borrower_role, requested_due_date, request_date, status, librarian_id, action_date, rejection_reason, due_date) FROM stdin;
3	1	4	15	student	2025-08-15	2025-08-04 16:37:04+00	Rejected	23	2025-08-04 22:08:34+00	My choice	\N
4	1	4	6	teacher	2025-08-18	2025-08-04 16:37:39+00	Approved	23	2025-08-04 22:08:15+00	\N	2025-08-18
5	1	4	6	teacher	2025-08-04	2025-08-04 16:41:20+00	Approved	23	2025-08-04 22:11:42+00	\N	2025-08-18
\.


--
-- Data for Name: borrowing_records; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.borrowing_records (record_id, book_id, borrower_id, borrower_role, checkout_date, due_date, return_date, is_returned, fine_amount, fine_status) FROM stdin;
2	1	6	teacher	2025-08-04	2025-08-18	2025-08-04	t	0.00	Unpaid
3	1	6	teacher	2025-08-04	2025-08-18	2025-08-04	t	0.00	Unpaid
\.


--
-- Data for Name: deleted_books; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.deleted_books (archived_book_id, original_book_id, title, author, isbn, quantity_total, school_id, is_digital, deleted_at, deleted_by_role) FROM stdin;
1	1	Harry	Devam	7965412BD	2	4	f	2025-08-05 03:31:40+00	librarian
\.


--
-- Data for Name: deleted_librarians; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.deleted_librarians (id, librarian_name, email, phone, dob, gender, blood_group, address, qualification, salary, school_id, deleted_by_role, deleted_at) FROM stdin;
21	Devang Odedra	devang@gmail.com	9567845678	1980-09-01	Male	AB+	Surat	B.A	30000.00	4	principal	2025-08-04 09:06:44+00
22	Devang Odedra	devang@gmail.com	9654378291	1980-09-01	Male	AB+	Surat	B.A	30000.00	4	principal	2025-08-04 09:56:08+00
\.


--
-- Data for Name: deleted_principals; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.deleted_principals (id, principal_name, email, phone, dob, gender, blood_group, address, qualification, salary, batch, school_id, deleted_by_role, deleted_at) FROM stdin;
1	HARSH	harsh@gmail.com	5674231689	2005-02-06	male	B-	Adajan	B.C.A	500000.00	\N	3	principal	2025-07-22 11:51:18+00
11	Harish	harish120@gmail.com	5674567458	2005-10-22	male	O+	adajan	d	560000.00	Morning	1	superadmin	2025-08-07 10:48:08.569224+00
14	Raj	raj@gmail.com	8532415697	2000-03-28	male	AB-	Adajan	MD	800000.00	Morning	3	superadmin	2025-08-07 11:22:44.378451+00
17	Manav	manav@gmail.com	5641237893	1997-05-08	male	B-	Varacha	12th	1000000.00	Evening	3	superadmin	2025-08-07 11:54:44.986561+00
\.


--
-- Data for Name: deleted_schools; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.deleted_schools (id, school_logo, school_name, email, phone, school_opening, school_type, education_board, school_medium, school_category, address, deleted_by_role, deleted_at) FROM stdin;
6	\N	LP SAVANI CANAL ROAD	lpsavani@gmail.com	5478931254	1999-03-11	Private	{State}	{Hindi}	{}	Adajan	superadmin	2025-07-24 10:06:38+00
2	\N	MANIDHAR	mani@gmail.com	7452639812	2025-08-07	Private	{State}	{Hindi}	{Primary,"Upper Primary"}	Adajan	superadmin	2025-08-07 11:01:59.328133+00
3	\N	Countryside	countryside@gmail.com	8574964152	2018-06-06	Government	{CBSE}	{English}	{Pre-Primary,Primary,"Upper Primary",Secondary,"Higher Secondary"}	Bhesan	superadmin	2025-08-07 11:54:59.314645+00
\.


--
-- Data for Name: deleted_students; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.deleted_students (id, student_name, email, rollno, std, academic_year, dob, gender, blood_group, address, father_name, father_phone, mother_name, mother_phone, school_id, reason_for_leaving, deleted_by_role, deleted_at) FROM stdin;
1	Rahul Patel	rahul@gmail.com	1	5th	2024-2025	2005-02-02	male	AB+	surat	harsh	6565548720	hemina	6523012304	3	\N	principal	2025-07-22 11:51:18+00
3	devam parekh	devamparekh1200@gmail.com	9	11	2024-2025	2025-07-11	male	B+	canal road	mukesh	9874522589	sunita	753685124	4	\N	student	2025-08-01 10:04:14+00
13	vansh	vansh@gmail.com	15	12	2024-2025	2011-03-11	female	B+	surat	girishbhai	5565615555	Sita Patel	5454454455	4	\N	teacher	2025-07-24 15:42:54+00
16	mihir	mihir@gmail.com	15	11	2024-2025	2005-08-17	male	B-	nutan	janak	5746895214	harshita	6352417898	4	\N	principal	2025-07-30 08:06:28+00
18	devam parekh	devamparekh1200@gmail.com	69	10	2024-2025	2005-03-11	male	AB-	LP savani	mukesh	852369741	vanita	9874563210	4	he is not a good student	student	2025-08-01 06:50:40+00
19	devam parekh	devamparekh1200@gmail.com	69	10	2024-2025	2005-03-11	female	A+	LP savani	mukesh	8523697415	vanita	1234567872	4	bye bye bye	student	2025-08-01 06:53:19+00
20	devam parekh	devamparekh1200@gmail.com	69	10	2024-2025	2005-03-11	male	O+	LP savani	mukesh	8523697415	vanita	9632587415	4	bye bye bye	student	2025-08-01 06:57:45+00
33	assdfdgf	as@gmail.com	29	12th	2025-2026	2021-12-02	male	AB+	sdd	dgf	963	we	9852	4	\N	principal	2025-08-07 12:11:36.908775+00
32	asdfs	akash@gmail.com	29	11th	2024-2025	22020-02-01	male	A-	v	c	55	d	88	4	\N	principal	2025-08-07 12:11:42.070897+00
27	Meet	meet111@gmail.com	12	12	2024-2025	2003-06-06	male	A-	surat	Sanket Patel	457896241	Sita Patel	457868547	4	\N	principal	2025-08-07 12:11:48.320871+00
34	ewrt	a@gmail.com	29	11th	2025-2026	2020-02-12	male	AB+	x	sdfd	8	x	85	4	\N	principal	2025-08-07 12:13:11.351813+00
28	Palak Bhala	palak@gmail.com	28	11th	2025-2026	2022-02-12	female	A-	adsfd	dsd	9856324477	sasf	9999999998	4	\N	principal	2025-08-07 12:13:27.073788+00
21	Meet Patel	meet@gmail.com	27	11th	2025-2026	2020-02-02	male	A+	ffdf	asd	8745632011	df	5896320147	4	\N	principal	2025-08-07 12:13:31.004808+00
30	Akash Patani	akash1@gmail.com	28	12th	2025-2026	2020-02-01	male	B-	cs	sasd	5963	sasd	58452	4	\N	principal	2025-08-07 12:13:35.126175+00
\.


--
-- Data for Name: deleted_teachers; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.deleted_teachers (id, teacher_name, email, phone, gender, dob, blood_group, address, school_id, qualification, subject, language_known, salary, std, experience, batch, class_teacher, class_teacher_std, deleted_by_role, deleted_at) FROM stdin;
1	JAY	jay@gmail.com	5674298791	male	2005-11-03	AB-	0	3	BA	Account	Hindi	500000.00	{Nursery,Junior,1}	5	Evening	f	\N	principal	2025-07-22 11:51:18+00
12	ram	ram@gmail.com	5545875655	male	2005-03-11	AB+	surat	4	MA	English	English	100000.00	{5,6}	5	Morning	f	\N	principal	2025-07-24 09:34:16+00
14	Hemant	hemant@gmail.com	5674231495	male	2000-03-11	AB+	Surat	4	MA	account	English	150000.00	{11,12}	5	Morning	t	12	principal	2025-07-25 08:19:17+00
17	Yug gandhi	yug@gmail.com	5874693214	male	2005-03-11	B-	surat	4	MA	maths	English	250000.00	{7,9}	5	Morning	t	7	principal	2025-07-30 08:10:40+00
\.


--
-- Data for Name: exam_timetables; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.exam_timetables (id, principal_id, school_id, title, description, file_path, original_filename, created_at) FROM stdin;
1	10	4	Term 1 Exam Timetable	time table for term 1	/BMC-SMS/uploads/timetables/examtt_688b5a0eec5341.29191278_INTERNSHIP REGISTRATION FORM Sujal.pdf	INTERNSHIP REGISTRATION FORM Sujal.pdf	2025-07-31 11:57:02+00
2	10	4	Term 2 Exam Timetable	dasf	/BMC-SMS/uploads/timetables/examtt_688b5f8df01541.57695077_INTERNSHIP REGISTRATION FORM JAY (1).pdf	INTERNSHIP REGISTRATION FORM JAY (1).pdf	2025-07-31 12:20:30+00
3	10	4	Final Exam Timetable	final	/BMC-SMS/uploads/timetables/examtt_688cf86a003ef1.98578217_UNIT 1 AWT.pdf	UNIT 1 AWT.pdf	2025-08-01 17:24:58+00
\.


--
-- Data for Name: leave_applications; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.leave_applications (id, teacher_id, from_date, to_date, reason, leave_type, status, applied_on, rejection_reason) FROM stdin;
1	6	2025-07-30	2025-08-10	My friend's marriage	Full Day	Approved	2025-07-23 17:40:03+00	\N
2	6	2025-07-31	2025-08-20	swayam marriage	Full Day	Approved	2025-07-23 17:55:10+00	\N
3	6	2025-07-26	2025-07-30	Marriage	Full Day	Rejected	2025-07-25 07:45:29+00	Because you don't deserve
4	6	2025-07-25	2025-07-25	fgdgvbdfvc	Full Day	Approved	2025-07-25 11:04:58+00	\N
5	6	2025-08-01	2025-08-17	I'm Sick	Full Day	Rejected	2025-07-28 08:31:40+00	You're telling lie
6	6	2025-07-28	2025-08-01	dcnjdkjcdckdcdk	Full Day	Rejected	2025-07-28 09:17:10+00	njcdmcdcd cdcdcdc dm.cdc dcd mcd c mdc md c dc d mcmdm c,d c d c,d mcdm ,cm,dc m,dmcdm,cv m,dmdm,clkm dlv,dfkjnhvnjkmvhfjkm,l.poio-
7	6	2025-07-29	2025-07-29	want to go for shopping	First Half	Rejected	2025-07-29 09:44:55+00	do shoping after school hours
8	6	2025-07-29	2025-07-29	i am sick	Second Half	Approved	2025-07-29 09:48:16+00	\N
9	6	2025-07-29	2025-07-29	personal reason\\r\\n	First Half	Approved	2025-07-29 11:03:55+00	\N
10	6	2025-07-29	2025-07-29	personal reason\\r\\n	First Half	Approved	2025-07-29 11:08:03+00	\N
11	6	2025-07-29	2025-07-29	medical emegency	Second Half	Rejected	2025-07-29 11:08:42+00	cant
12	6	2025-07-09	2025-07-09	i want leave	Second Half	Rejected	2025-07-29 11:31:47+00	no you can't
13	6	2025-07-29	2025-07-29	leave	First Half	Approved	2025-07-29 12:35:04+00	\N
14	6	2025-08-01	2025-08-03	oooooooo	Full Day	Approved	2025-07-31 11:09:39+00	\N
15	6	2025-08-01	2025-08-05	bghugg	Full Day	Approved	2025-07-31 11:20:57+00	\N
16	6	2025-08-01	2025-08-05	bghugg	Full Day	Rejected	2025-07-31 11:22:24+00	jnjknjnj
17	6	2025-08-08	2025-08-09	bhugv nk nn n	Full Day	Approved	2025-07-31 11:23:31+00	\N
18	6	2025-08-08	2025-08-09	bhugv nk nn n	Full Day	Approved	2025-07-31 11:24:31+00	\N
19	6	2025-08-01	2025-08-05	csdff	Full Day	Approved	2025-07-31 12:11:58+00	\N
21	6	2025-08-08	2025-08-18	Again testing after removing the db error 	Full Day	Approved	2025-08-07 13:18:49.047998+00	\N
20	6	2025-08-08	2025-08-13	Testing the Principal Panel "Teacher Leave"\r\nConverted the two different pages into one.	Full Day	Rejected	2025-08-07 13:18:20.543103+00	Ok, testing so rejecting your leave
\.


--
-- Data for Name: librarian; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.librarian (id, librarian_image, librarian_name, school_id, email, password, phone, dob, gender, blood_group, address, qualification, salary) FROM stdin;
23	/BMC-SMS/pages/librarian/uploads/librarian_68908540cde142.65959337.jpg	Devang Odedra	4	devang@gmail.com	$2y$10$gwi.CWfIjYwlqScnYMzLbeTB1KEryLZ8tb1TTwv7r68rXwj/K1Z3u	9654378291	1980-09-01	Male	B+	Canal Road, Palanpur Patiya, Surat	B.A	30000.00
\.


--
-- Data for Name: librarian_attendance; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.librarian_attendance (attendance_id, librarian_id, school_id, attendance_date, status, remark, marked_by_user_id, updated_at) FROM stdin;
1	23	4	2025-08-06	Present	\N	10	2025-08-06 11:07:08+00
2	23	4	2025-08-28	Present	\N	10	2025-08-07 09:48:15.363183+00
3	23	4	2025-08-07	Present	\N	10	2025-08-07 09:51:45.114329+00
\.


--
-- Data for Name: librarian_timings; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.librarian_timings (timing_id, librarian_id, day_of_week, opens_at, closes_at, is_closed) FROM stdin;
\.


--
-- Data for Name: messages; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.messages (id, sender_id, receiver_id, message_text, "timestamp", is_read) FROM stdin;
2	6	15	Hello, Harsh!	2025-08-05 09:09:09+00	t
3	6	15	bol su kaam che?	2025-08-05 09:43:22+00	t
5	6	15	hrg	2025-08-05 09:47:09+00	t
6	6	15	jkslgd	2025-08-05 09:47:15+00	t
10	6	15	bolo	2025-08-05 11:14:33+00	t
12	6	15	yes, you can	2025-08-05 12:00:02+00	t
13	6	15	say it	2025-08-05 12:07:41+00	t
15	6	15	please say it harsh beta!	2025-08-05 12:13:44+00	t
17	6	15	please say it na..	2025-08-05 12:18:34+00	t
19	6	15	please	2025-08-05 12:25:06+00	t
22	6	15	please	2025-08-05 12:27:28+00	t
24	6	15	yesssssssssssssssss	2025-08-05 12:30:12+00	t
1	15	6	hello sir	2025-08-05 09:07:09+00	t
4	15	6	i will not come tomm. to school	2025-08-05 09:43:57+00	t
7	15	6	jfkdlf;g	2025-08-05 09:48:49+00	t
8	15	6	hyy	2025-08-05 11:12:58+00	t
9	15	6	hiiiiiiiiiiiiii	2025-08-05 11:13:53+00	t
11	15	6	I want to ask you something	2025-08-05 11:59:27+00	t
14	15	6	no I want	2025-08-05 12:08:19+00	t
16	15	6	no meet sir!	2025-08-05 12:14:21+00	t
18	15	6	na	2025-08-05 12:19:13+00	t
20	15	6	na\\\\	2025-08-05 12:26:34+00	t
38	6	15	hy	2025-08-07 12:39:56.388169+00	t
41	15	6	hii	2025-08-07 13:12:08.530294+00	t
45	15	6	hyyy swa	2025-08-07 13:14:02.700462+00	t
34	6	15	hello fenil.	2025-08-07 10:34:49.206189+00	t
47	15	6	harsh	2025-08-07 13:14:29.228122+00	t
32	6	15	hii	2025-08-07 10:33:45.877321+00	t
36	6	15	hiii fenil im sendig you message	2025-08-07 10:35:15.461737+00	t
21	15	6	na	2025-08-05 12:26:41+00	t
23	15	6	yes	2025-08-05 12:29:47+00	t
25	15	6	hyy	2025-08-07 09:43:40.145145+00	t
26	15	6	hyy	2025-08-07 09:43:40.41017+00	t
27	15	6	hy	2025-08-07 09:43:43.582081+00	t
28	15	6	hy	2025-08-07 09:43:46.712929+00	t
29	15	6	how are you meet parekh sir	2025-08-07 09:44:40.34261+00	t
30	15	6	say na	2025-08-07 09:46:02.099217+00	t
31	15	6	...	2025-08-07 09:48:02.971876+00	t
33	15	6	hyy	2025-08-07 10:34:34.564744+00	t
35	15	6	hello swayam	2025-08-07 10:35:00.99698+00	t
37	15	6	yeeee	2025-08-07 10:35:24.664226+00	t
48	15	12	hii	2025-08-07 13:14:57.480104+00	f
42	15	6	hii swayu	2025-08-07 13:12:44.409111+00	t
46	15	6	harsh	2025-08-07 13:14:29.020084+00	t
\.


--
-- Data for Name: notes; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.notes (id, user_id, school_id, target_standard, title, content, file_path, original_filename, created_at) FROM stdin;
3	6	4	11	Fee	BLAW BLAW	/BMC-SMS/pages/teacher/uploads/note_6882136a28ca99.45092353_INTERNSHIP REGISTRATION FORM JAY.pdf	INTERNSHIP REGISTRATION FORM JAY.pdf	2025-07-24 11:05:14+00
4	6	4	11	Hello test notification	this is test notification for educational purposes only	/BMC-SMS/pages/teacher/uploads/note_688756ecdc5275.27306642_research sign paper.pdf	research sign paper.pdf	2025-07-28 10:54:36+00
5	6	4	11	safafdevammmmmmmmmmmm	devammmmmmmmmmmmmm	\N	\N	2025-07-31 09:02:53+00
6	6	4	11	njisijfj	happpp	/BMC-SMS/pages/teacher/uploads/note_688b317e718586.38465640_view_attendence.txt	view_attendence.txt	2025-07-31 09:03:58+00
7	6	4	11	csfxasc	cddddddddddddddddddddddddddddddddddd	\N	\N	2025-07-31 12:12:31+00
1	6	4	11	swayam	shah	\N	\N	2025-08-07 12:41:48.444133+00
\.


--
-- Data for Name: notice; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.notice (id, user_id, title, content, file_path, original_filename, created_at) FROM stdin;
1	8	Internship	Complete Internship	/BMC-SMS/pages/bmc/uploads/notice_68834a91915150.91659686_INTERNSHIP REGISTRATION FORM JAY (4) (1).pdf	INTERNSHIP REGISTRATION FORM JAY (4) (1).pdf	2025-07-25 09:12:49+00
2	8	Day 6 Test	waoaz	/BMC-SMS/pages/bmc/uploads/notice_688362673aef16.92057394_INTERNSHIP REGISTRATION FORM JAY (4) (1) (1) (1).pdf	INTERNSHIP REGISTRATION FORM JAY (4) (1) (1) (1).pdf	2025-07-25 10:54:31+00
3	8	Devam 	parekh	/BMC-SMS/pages/bmc/uploads/notice_6887332e030931.00260915_UI-UX_Fenil_74.pdf	UI-UX_Fenil_74.pdf	2025-07-28 08:22:06+00
4	8	Harsh	Shah	/BMC-SMS/pages/bmc/uploads/notice_6887347bbbd760.91586774_UI-UX_Fenil_74.pdf	UI-UX_Fenil_74.pdf	2025-07-28 08:27:39+00
5	8	aafafasdf	zcasfasfasf	\N	\N	2025-07-31 09:53:31+00
6	8	efewf	cdwdwffffffffffffffffff	\N	\N	2025-07-31 11:01:54+00
7	8	wefwsee	seeeeeeeeee	\N	\N	2025-07-31 11:08:15+00
8	8	fweff	wffw	\N	\N	2025-07-31 12:21:46+00
9	8	URGENT	blaw blaw	/BMC-SMS/pages/bmc/uploads/notice_688e2241b1dbe4.49890246_UNIT 2 APPLET.pdf	UNIT 2 APPLET.pdf	2025-08-02 14:35:45+00
10	8	test	test test	\N	\N	2025-08-02 14:50:23+00
11	8	Testing	Notice to SuperAdmin	\N	\N	2025-08-07 12:28:28.850399+00
12	8	Testing again	Notification in Principal panel not coming	\N	\N	2025-08-07 12:43:00.058239+00
13	8	Again n Again	dsdfb	\N	\N	2025-08-07 12:55:20.786447+00
\.


--
-- Data for Name: notifications; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.notifications (id, user_id, message, link, is_read, created_at, type) FROM stdin;
1	10	New notice from BMC: Devam 	/pages/principal/view_notice.php	t	2025-07-28 08:22:06+00	new_notice
2	10	New notice from BMC: Harsh	/pages/principal/view_notice.php	t	2025-07-28 08:27:39+00	new_notice
3	10	New leave request from meet parekh	/pages/principal/principal_leave_requests.php	t	2025-07-28 08:31:40+00	leave_request
4	10	New leave request from meet parekh	/pages/principal/principal_leave_requests.php	t	2025-07-28 09:17:10+00	leave_request
5	6	Your leave application has been Rejected.	/pages/teacher/teacher_leave_history.php	t	2025-07-28 09:18:01+00	leave_status
7	10	New leave request from meet parekh	/pages/principal/principal_leave_requests.php	t	2025-07-29 09:44:56+00	leave_request
8	6	Your leave application has been Rejected.	/pages/teacher/teacher_leave_history.php	t	2025-07-29 09:45:50+00	leave_status
9	10	New leave request from meet parekh	/pages/principal/principal_leave_requests.php	t	2025-07-29 09:48:16+00	leave_request
10	6	Your leave application has been Approved.	/pages/teacher/teacher_leave_history.php	t	2025-07-29 09:48:41+00	leave_status
11	10	New leave request from meet parekh	/pages/principal/principal_leave_requests.php	t	2025-07-29 11:03:55+00	leave_request
12	10	New leave request from meet parekh	/pages/principal/principal_leave_requests.php	t	2025-07-29 11:08:03+00	leave_request
13	10	New leave request from meet parekh	/pages/principal/principal_leave_requests.php	t	2025-07-29 11:08:42+00	leave_request
14	6	Your leave application has been Approved.	/pages/teacher/teacher_leave_history.php	t	2025-07-29 11:09:26+00	leave_status
15	6	Your leave application has been Rejected.	/pages/teacher/teacher_leave_history.php	t	2025-07-29 11:09:32+00	leave_status
16	6	Your leave application has been Approved.	/pages/teacher/teacher_leave_history.php	t	2025-07-29 11:09:33+00	leave_status
17	6	New notice from Principal: Email testing...	/pages/teacher/view_notice.php	t	2025-07-29 11:25:01+00	school_notice
19	15	New notice from Principal: Email testing...	/pages/student/view_notice.php	t	2025-07-29 11:25:16+00	school_notice
21	15	New notice from Principal: testing...	/pages/student/view_notice.php	t	2025-07-29 11:28:34+00	school_notice
22	6	New notice from Principal: sending to both teacher and students...	/pages/teacher/view_notice.php	t	2025-07-29 11:29:33+00	school_notice
24	15	New notice from Principal: sending to both teacher and students...	/pages/student/view_notice.php	t	2025-07-29 11:29:44+00	school_notice
25	10	New leave request from meet parekh	/pages/principal/principal_leave_requests.php	t	2025-07-29 11:31:47+00	leave_request
26	6	Your leave application has been Rejected.	/pages/teacher/teacher_leave_management.php	t	2025-07-29 11:32:47+00	leave_status
28	10	New leave request from meet parekh	/pages/principal/principal_leave_requests.php	t	2025-07-29 12:35:04+00	leave_request
30	6	Your leave application has been Approved.	/pages/teacher/teacher_leave_management.php	t	2025-07-30 08:11:43+00	leave_status
37	6	devam parekh has submitted an assignment.	/BMC-SMS/pages/assignments/view_submissions.php?id=11	t	2025-07-31 09:48:55+00	assignment_submission
38	6	devam parekh has submitted an assignment.	/BMC-SMS/pages/assignments/view_submissions.php?id=11	t	2025-07-31 09:49:34+00	assignment_submission
39	10	New notice from BMC: aafafasdf	/pages/principal/view_notice.php	t	2025-07-31 09:53:31+00	new_notice
40	6	New notice from Principal: ffdefw...	/pages/teacher/view_notice.php	t	2025-07-31 09:54:19+00	school_notice
42	15	New notice from Principal: ffdefw...	/pages/student/view_notice.php	t	2025-07-31 09:54:28+00	school_notice
43	6	New notice from Principal: fweff...	/pages/teacher/view_notice.php	t	2025-07-31 10:01:37+00	school_notice
45	15	New notice from Principal: fweff...	/pages/student/view_notice.php	t	2025-07-31 10:01:48+00	school_notice
47	6	devam parekh has submitted an assignment.	/BMC-SMS/pages/assignments/view_submissions.php?id=12	t	2025-07-31 10:05:55+00	assignment_submission
49	6	devam parekh has submitted an assignment.	/BMC-SMS/pages/assignments/view_submissions.php?id=13	t	2025-07-31 10:09:55+00	assignment_submission
51	6	devam parekh has submitted an assignment.	/BMC-SMS/pages/assignments/view_submissions.php?id=14	t	2025-07-31 10:14:08+00	assignment_submission
53	6	devam parekh has submitted an assignment.	/BMC-SMS/pages/assignments/view_submissions.php?id=15	t	2025-07-31 10:16:02+00	assignment_submission
54	10	New notice from BMC: efewf	/pages/principal/view_notice.php	t	2025-07-31 11:01:54+00	new_notice
55	10	New notice from BMC: wefwsee	/pages/principal/view_notice.php	t	2025-07-31 11:08:15+00	new_notice
56	10	New leave request from meet parekh	/pages/principal/principal_leave_requests.php	t	2025-07-31 11:09:39+00	leave_request
57	6	Your leave application has been Approved.	/pages/teacher/teacher_leave_management.php	t	2025-07-31 11:12:56+00	leave_status
58	10	New leave request from meet parekh	/pages/principal/principal_leave_requests.php	t	2025-07-31 11:20:57+00	leave_request
59	6	Your leave application has been Approved.	/pages/teacher/teacher_leave_management.php	t	2025-07-31 11:21:59+00	leave_status
60	10	New leave request from meet parekh	/pages/principal/principal_leave_requests.php	t	2025-07-31 11:22:24+00	leave_request
61	10	New leave request from meet parekh	/pages/principal/principal_leave_requests.php	t	2025-07-31 11:23:31+00	leave_request
62	6	Your leave application has been Rejected.	/pages/teacher/teacher_leave_management.php	t	2025-07-31 11:24:03+00	leave_status
63	6	Your leave application has been Approved.	/pages/teacher/teacher_leave_management.php	t	2025-07-31 11:24:17+00	leave_status
64	10	New leave request from meet parekh	/pages/principal/principal_leave_requests.php	t	2025-07-31 11:24:31+00	leave_request
65	6	Your leave application has been Approved.	/pages/teacher/teacher_leave_management.php	t	2025-07-31 11:25:23+00	leave_status
66	8	New Notice from Fenil Pastagia	/pages/bmc/view_principal_notices.php	t	2025-07-31 11:39:41+00	principal_notice
67	6	New Exam Timetable: Term 1 Exam Timetable	/pages/teacher/view_exam_timetable.php	t	2025-07-31 11:57:03+00	exam_timetable
69	15	New Exam Timetable: Term 1 Exam Timetable	/pages/student/view_exam_timetable.php	t	2025-07-31 11:57:03+00	exam_timetable
71	10	New leave request from meet parekh	/pages/principal/principal_leave_requests.php	t	2025-07-31 12:11:58+00	leave_request
73	6	devam parekh has submitted an assignment.	/BMC-SMS/pages/assignments/view_submissions.php?id=16	t	2025-07-31 12:12:58+00	assignment_submission
74	6	Your leave application has been Approved.	/pages/teacher/teacher_leave_management.php	t	2025-07-31 12:15:49+00	leave_status
75	6	New notice from Principal: csdff...	/pages/teacher/view_notice.php	t	2025-07-31 12:16:25+00	school_notice
77	15	New notice from Principal: csdff...	/pages/student/view_notice.php	t	2025-07-31 12:16:35+00	school_notice
78	8	New Notice from Fenil Pastagia	/pages/bmc/view_principal_notices.php	t	2025-07-31 12:16:52+00	principal_notice
79	6	New Exam Timetable: Term 2 Exam Timetable	/pages/teacher/view_exam_timetable.php	t	2025-07-31 12:20:30+00	exam_timetable
81	15	New Exam Timetable: Term 2 Exam Timetable	/pages/student/view_exam_timetable.php	t	2025-07-31 12:20:30+00	exam_timetable
82	10	New notice from BMC: fweff	/pages/principal/view_notice.php	t	2025-07-31 12:21:46+00	new_notice
83	8	New Notice from Fenil Pastagia	/pages/bmc/view_principal_notices.php	t	2025-08-01 16:33:57+00	principal_notice
84	6	New Exam Timetable: Final Exam Timetable	/pages/teacher/view_exam_timetable.php	t	2025-08-01 17:24:58+00	exam_timetable
85	15	New Exam Timetable: Final Exam Timetable	/pages/student/view_exam_timetable.php	t	2025-08-01 17:24:58+00	exam_timetable
86	10	New notice from BMC: URGENT	/pages/principal/view_notice.php	t	2025-08-02 14:35:45+00	new_notice
87	10	New notice from BMC: test	/pages/principal/view_notice.php	t	2025-08-02 14:50:23+00	new_notice
88	8	New Notice from Fenil Pastagia	/BMC-SMS/pages/bmc/view_principal_notices.php	t	2025-08-07 12:20:01.14332+00	principal_notice
89	8	New Notice from Fenil Pastagia	/BMC-SMS/pages/bmc/view_principal_notices.php	t	2025-08-07 12:22:47.248376+00	principal_notice
91	13	New notice from BMC: Testing	/pages/principal/view_notice.php	f	2025-08-07 12:28:28.850399+00	new_notice
93	12	New notice from Principal: to everyone ...	/pages/teacher/view_notice.php	f	2025-08-07 12:28:51.843058+00	school_notice
90	10	New notice from BMC: Testing	/pages/principal/view_notice.php	t	2025-08-07 12:28:28.850399+00	new_notice
96	12	New notice from Principal: to everyone ...	/pages/teacher/view_notice.php	f	2025-08-07 12:31:12.924034+00	school_notice
99	12	New notice from Principal: to everyone ...	/pages/teacher/view_notice.php	f	2025-08-07 12:31:34.624458+00	school_notice
102	12	New notice from Principal: to everyone ...	/pages/teacher/view_notice.php	f	2025-08-07 12:35:21.374491+00	school_notice
92	6	New notice from Principal: to everyone ...	/pages/teacher/view_notice.php	t	2025-08-07 12:28:47.933164+00	school_notice
95	6	New notice from Principal: to everyone ...	/pages/teacher/view_notice.php	t	2025-08-07 12:31:09.01844+00	school_notice
98	6	New notice from Principal: to everyone ...	/pages/teacher/view_notice.php	t	2025-08-07 12:31:30.953481+00	school_notice
109	8	New Notice from Fenil Pastagia	/BMC-SMS/pages/bmc/view_principal_notices.php	t	2025-08-07 12:54:06.823115+00	principal_notice
101	6	New notice from Principal: to everyone ...	/pages/teacher/view_notice.php	t	2025-08-07 12:35:17.436517+00	school_notice
104	8	New Notice from Fenil Pastagia	/BMC-SMS/pages/bmc/view_principal_notices.php	t	2025-08-07 12:40:42.258664+00	principal_notice
107	13	New notice from BMC: Testing again	/pages/principal/view_notice.php	f	2025-08-07 12:43:00.058239+00	new_notice
106	10	New notice from BMC: Testing again	/pages/principal/view_notice.php	t	2025-08-07 12:43:00.058239+00	new_notice
108	8	New Notice from Fenil Pastagia	/BMC-SMS/pages/bmc/view_principal_notices.php	t	2025-08-07 12:48:41.753048+00	principal_notice
111	13	New notice from BMC: Again n Again	/pages/principal/view_notice.php	f	2025-08-07 12:55:20.786447+00	new_notice
110	10	New notice from BMC: Again n Again	/pages/principal/view_notice.php	t	2025-08-07 12:55:20.786447+00	new_notice
103	15	New notice from Principal: to everyone ...	/pages/student/view_notice.php	t	2025-08-07 12:35:25.107543+00	school_notice
94	15	New notice from Principal: to everyone ...	/pages/student/view_notice.php	t	2025-08-07 12:28:55.576463+00	school_notice
97	15	New notice from Principal: to everyone ...	/pages/student/view_notice.php	t	2025-08-07 12:31:16.678452+00	school_notice
100	15	New notice from Principal: to everyone ...	/pages/student/view_notice.php	t	2025-08-07 12:31:38.652517+00	school_notice
105	15	New notes posted: swayam...	/BMC-SMS/pages/student/view_notes.php	t	2025-08-07 12:41:48.444133+00	new_notes
112	10	New leave request from meet parekh	/BMC-SMS/pages/principal/principal_leave_requests.php	t	2025-08-07 13:18:20.543103+00	leave_request
113	10	New leave request from meet parekh	/BMC-SMS/pages/principal/principal_leave_requests.php	t	2025-08-07 13:18:49.047998+00	leave_request
114	6	Your leave application has been Approved.	/BMC-SMS/pages/teacher/teacher_leave_management.php	f	2025-08-07 13:20:12.984471+00	leave_status
115	6	Your leave application has been Rejected.	/BMC-SMS/pages/teacher/teacher_leave_management.php	f	2025-08-07 13:20:34.537929+00	leave_status
\.


--
-- Data for Name: password_resets; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.password_resets (id, user_id, email, otp_hash, expires_at, created_at) FROM stdin;
1	10	17fenill@gmail.com	$2y$10$7vfvXCUgJyghSXWhu63cluThJruljgfVCt2JWyqnHkV.Pgm8a3J0S	2025-08-01 18:07:16+00	2025-08-01 12:22:16+00
\.


--
-- Data for Name: principal; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.principal (id, principal_image, school_id, principal_name, email, password, phone, dob, gender, blood_group, address, qualification, salary, batch) FROM stdin;
10	pages/principal/uploads/principal_688e1e9a2a4f50.30741006.jpg	4	Fenil Pastagia	17fenill@gmail.com	$2y$10$WSgb/L7db17vIrhYjlXHguX6.gSNxFJq513rpU0JvlIZETe7eLTty	9924976503	1990-08-17	Female	B+	canal road	M.A. M.Ed	90000.00	Morning
13	\N	1	Dhaval	dhaval@gmail.com	$2y$10$/PhOzkuBDiabEZAW5eIZKuEr9Gcr0NTvpE7mGegA1Z6oNalzKXQcW	2563417897	1995-08-06	Male	A+	Varacha	12	600000.00	Morning
\.


--
-- Data for Name: principal_attendance; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.principal_attendance (id, principal_id, school_id, attendance_date, status, login_latitude, login_longitude, login_time, updated_at) FROM stdin;
1	10	4	2025-07-30	Absent	21.21014980	72.77075840	23:47:25	2025-07-30 18:17:25+00
12	10	4	2025-07-31	Absent	\N	\N	17:44:31	2025-07-31 12:14:31+00
20	10	4	2025-08-01	Absent	21.18451200	72.83671040	22:54:28	2025-08-01 17:24:28+00
31	10	4	2025-08-02	Absent	21.19761920	72.83998720	21:57:48	2025-08-02 16:27:48+00
38	10	4	2025-08-04	Absent	21.21010290	72.77055590	21:26:25	2025-08-04 15:56:25+00
43	10	4	2025-08-05	Absent	21.18556770	72.77576010	18:13:59	2025-08-05 12:43:59+00
46	10	4	2025-08-06	Absent	21.18535340	72.77703840	13:24:57	2025-08-06 07:54:57+00
2	10	4	2025-08-07	Absent	21.20090950	72.77102720	12:54:24.916438	2025-08-07 11:51:37.880963+00
\.


--
-- Data for Name: principal_timings; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.principal_timings (timing_id, principal_id, day_of_week, opens_at, closes_at, is_closed) FROM stdin;
1	10	Monday	06:00:00	20:00:00	f
2	10	Tuesday	10:00:00	20:00:00	f
3	10	Wednesday	10:00:00	20:00:00	f
4	10	Thursday	10:00:00	20:00:00	f
5	10	Friday	10:00:00	20:00:00	f
6	10	Saturday	10:00:00	20:00:00	f
7	10	Sunday	10:00:00	20:00:00	f
15	13	Monday	10:00:00	20:00:00	f
16	13	Tuesday	10:00:00	20:00:00	f
17	13	Wednesday	10:00:00	20:00:00	f
18	13	Thursday	10:00:00	20:00:00	f
19	13	Friday	10:00:00	20:00:00	f
20	13	Saturday	10:00:00	20:00:00	f
21	13	Sunday	\N	\N	t
\.


--
-- Data for Name: principal_to_bmc_notices; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.principal_to_bmc_notices (id, principal_id, school_id, title, content, file_path, original_filename, created_at) FROM stdin;
1	10	4	mioweojfkj	jwndjkngfbn	\N	\N	2025-07-31 11:39:41+00
2	10	4	njhshdjgsjd	 sdhjbfdhbfdbwfbwfbw gwvshjvwehjfhsjbfhjsbhewb	\N	\N	2025-07-31 12:16:52+00
3	10	4	Asking about meating	meatine/.....	/BMC-SMS/pages/principal/uploads/bmc_notices/p2b_notice_688cec7575ea72.23702023_UNIT 1 AWT.pdf	UNIT 1 AWT.pdf	2025-08-01 16:33:57+00
4	10	4	Hiii	This Is for testing	pages/principal/uploads/bmc_notices/p2b_notice_689499f1b64746.70284184_generate_lc.php	generate_lc.php	2025-08-07 12:20:01.14332+00
5	10	4	Testing	vcdfg	\N	\N	2025-08-07 12:22:47.248376+00
6	10	4	hii	again testing	\N	\N	2025-08-07 12:40:42.258664+00
7	10	4	hiii	once again	\N	\N	2025-08-07 12:48:41.753048+00
8	10	4	Testing testing	notifications are coming, checked and passed.	\N	\N	2025-08-07 12:54:06.823115+00
\.


--
-- Data for Name: principal_to_librarian_notices; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.principal_to_librarian_notices (id, principal_id, school_id, title, content, file_path, original_filename, created_at) FROM stdin;
\.


--
-- Data for Name: school; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.school (id, school_logo, school_name, email, phone, school_opening, school_type, education_board, school_medium, school_category, address, latitude, longitude, passing_percentage) FROM stdin;
1	/BMC-SMS/uploads/school_logos/school_1_1754562122.jpg	LP SAVANI CANAL ROAD	lpsavani@gmail.com	8974589654	2025-08-06	Government	{State}	{Hindi}	{Pre-Primary,Primary,"Upper Primary"}	Surat	\N	\N	33.00
4	/BMC-SMS/uploads/school_logos/school_4_1754557425.jpg	sanskar bharti vidyalay	sbv@gmail.com	8526548525	2025-07-06	Private	{CBSE}	{Hindi}	{Primary,"Upper Primary"}	adajan	21.21060270	72.76795460	20.00
\.


--
-- Data for Name: school_notice_recipients; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.school_notice_recipients (id, notice_id, recipient_type, recipient_identifier) FROM stdin;
1	2	teacher	6
2	2	standard	11
3	3	teacher	6
4	4	teacher	6
5	4	standard	10
6	4	standard	11
7	5	standard	10
8	5	standard	11
9	6	teacher	6
10	6	standard	10
11	6	standard	11
12	7	teacher	6
13	7	standard	10
14	7	standard	11
15	8	teacher	6
16	8	standard	10
17	8	standard	11
18	9	teacher	6
19	9	standard	10
20	9	standard	11
21	10	teacher	12
22	11	teacher	12
23	11	teacher	6
24	12	teacher	12
25	12	teacher	6
26	12	standard	11
27	13	teacher	12
28	13	teacher	6
29	13	standard	11
30	14	teacher	12
31	14	teacher	6
32	14	standard	11
33	15	teacher	12
34	15	teacher	6
35	15	standard	11
\.


--
-- Data for Name: school_notices_content; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.school_notices_content (id, user_id, school_id, title, content, file_path, original_filename, created_at) FROM stdin;
2	10	4	Internship	Do Work	/BMC-SMS/pages/principal/uploads/notice_688352064e9079.52076292_INTERNSHIP REGISTRATION FORM JAY (4) (1) (1).pdf	INTERNSHIP REGISTRATION FORM JAY (4) (1) (1).pdf	2025-07-25 09:44:38+00
3	10	4	Complete work	HII	/BMC-SMS/pages/principal/uploads/notice_6883539e857812.52522225_INTERNSHIP REGISTRATION FORM JAY (5).pdf	INTERNSHIP REGISTRATION FORM JAY (5).pdf	2025-07-25 09:51:26+00
4	10	4	Email testing	this notice is being sent to test email feature	/BMC-SMS/pages/principal/uploads/notice_6888af8d830663.02075899_💻 Case Study.pdf	💻 Case Study.pdf	2025-07-29 11:25:01+00
5	10	4	testing	hello	/BMC-SMS/pages/principal/uploads/notice_6888b05c2e9f11.53130874_💻 Case Study.pdf	💻 Case Study.pdf	2025-07-29 11:28:28+00
6	10	4	sending to both teacher and students	testing	\N	\N	2025-07-29 11:29:33+00
7	10	4	ffdefw	dewwwwwwww	\N	\N	2025-07-31 09:54:19+00
8	10	4	fweff	casfafsf	\N	\N	2025-07-31 10:01:37+00
9	10	4	csdff	readdddd	\N	\N	2025-07-31 12:16:25+00
10	10	4	hiiiiii	heloooooo how are you	\N	\N	2025-08-07 12:04:51.975649+00
11	10	4	all teacher	alll teacherrrrr	\N	\N	2025-08-07 12:08:20.89825+00
12	10	4	to everyone 	this is to everyone	\N	\N	2025-08-07 12:28:47.673402+00
13	10	4	to everyone 	to everyone	\N	\N	2025-08-07 12:31:08.729534+00
14	10	4	to everyone 	to everyone	\N	\N	2025-08-07 12:31:30.694563+00
15	10	4	to everyone 	to everyone\r\n	\N	\N	2025-08-07 12:35:17.171786+00
\.


--
-- Data for Name: school_timetable; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.school_timetable (id, school_id, standard, day_of_week, period_number, subject_name, teacher_id, start_time, end_time) FROM stdin;
1	4	11	Monday	1	Computer Science	6	08:00:00	09:00:00
2	4	11	Monday	2	0	6	09:00:00	10:00:00
3	4	11	Tuesday	1	English	6	18:00:00	19:00:00
4	4	11	Tuesday	2	0	6	09:00:00	10:00:00
5	4	11	Wednesday	1	Mathematics	6	08:00:00	09:00:00
6	4	11	Wednesday	2	0	6	09:00:00	10:00:00
7	4	11	Thursday	1	Physical Education	6	08:09:00	09:00:00
8	4	11	Thursday	2	0	6	09:00:00	10:00:00
9	4	11	Friday	1	Sanskrit	6	08:00:00	09:00:00
10	4	11	Friday	2	0	6	09:00:00	10:00:00
11	4	11	Saturday	1	Science	6	08:00:00	09:00:00
12	4	11	Saturday	2	0	6	09:00:00	10:00:00
62	4	11	Monday	3	Science	6	18:46:00	00:00:00
64	4	11	Tuesday	3	Sanskrit	6	00:00:00	00:00:00
66	4	11	Wednesday	3	Social Studies	6	00:00:00	00:00:00
68	4	11	Thursday	3	Physical Education	6	00:00:00	00:00:00
70	4	11	Friday	3	English	6	00:00:00	00:00:00
72	4	11	Saturday	3	Mathematics	6	00:00:00	00:00:00
\.


--
-- Data for Name: standard_subjects; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.standard_subjects (std_subject_id, standard, subject_id) FROM stdin;
100	1	1
103	1	2
105	1	3
99	1	9
102	1	11
101	1	12
104	1	16
73	10	1
74	10	2
77	10	3
75	10	7
71	10	8
72	10	10
76	10	13
79	11	1
80	11	2
83	11	3
84	11	4
78	11	10
82	11	13
81	11	16
93	12	1
95	12	2
98	12	4
92	12	10
94	12	12
97	12	13
96	12	16
8	2	1
11	2	2
10	2	11
9	2	12
12	2	16
7	2	17
14	3	1
17	3	2
19	3	3
16	3	11
15	3	12
18	3	16
13	3	17
21	4	1
24	4	2
26	4	3
23	4	11
22	4	12
25	4	16
20	4	17
28	5	1
31	5	2
33	5	3
34	5	4
30	5	11
29	5	12
32	5	16
27	5	17
40	6	3
41	6	4
37	6	5
36	6	11
35	6	12
38	6	15
39	6	16
43	7	1
46	7	2
48	7	4
45	7	5
42	7	10
44	7	12
47	7	13
49	8	1
51	8	2
55	8	4
50	8	12
54	8	13
52	8	15
53	8	16
58	9	1
63	9	4
59	9	6
61	9	7
56	9	8
57	9	10
62	9	13
60	9	16
\.


--
-- Data for Name: student; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.student (id, student_image, student_name, rollno, std, email, password, academic_year, school_id, dob, gender, blood_group, address, father_name, father_phone, mother_name, mother_phone) FROM stdin;
15	pages/student/uploads/student_68930bc8321217.11101259.jpg	harsh shah	26	11	shh.260105@gmail.com	$2y$10$nj4MFVjg.rCq6AmmAOX3jewd9VDTeNZCvWoeE138bfbUQaFAZmtY2	2025-2026	4	2005-01-26	male	AB+	navyug	hemant shah	8520321456	sunita shah	6547852365
\.


--
-- Data for Name: student_marks; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.student_marks (mark_id, student_id, school_id, academic_year, std, division, exam_type, subject_name, marks_obtained, total_marks, entry_date, entered_by_user_id) FROM stdin;
\.


--
-- Data for Name: subjects; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.subjects (subject_id, subject_name) FROM stdin;
14	Art
9	Biology
8	Chemistry
10	Computer Science
17	Drawing
1	English
6	Geography
12	Gujarati
11	Hindi
5	History
2	Mathematics
15	Music
16	Physical Education
7	Physics
13	Sanskrit
3	Science
4	Social Studies
\.


--
-- Data for Name: teacher; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.teacher (id, teacher_image, teacher_name, phone, school_id, dob, gender, blood_group, address, email, password, qualification, subject, language_known, salary, std, experience, batch, class_teacher, class_teacher_std) FROM stdin;
12	/BMC-SMS/pages/teacher/uploads/teacher_12_689493c22c092.jpg	Jay Shah	9874522589	4	2025-08-13	Male	AB+	canal road	jay@gmail.com	$2y$10$TUf4M/5ENm2A6oun27EuAuAz8Wlr8e8Ub8xwCR3w9i09nTBhFEWMO	M.A	maths	english,gujarati	10000	{9,10,11}	10	Morning	f	\N
6	../../pages/teacher/uploads/teacher_6894ac43c7d350.05352562.jpg	Meet Patel	9900990099	4	2005-09-04	Male	B-	Motavarachha	otherswayam@gmail.com	$2y$10$sdz4DZ5oaMJNrUA9mld44uiBNIIkAQCPjs2XrrnUcl.Bp6wlzYz1a	B.C.A	Maths	english	100000	{10}	10	Evening	t	11
\.


--
-- Data for Name: teacher_attendance; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.teacher_attendance (attendance_id, teacher_id, school_id, attendance_date, status, remark, marked_by_user_id, updated_at) FROM stdin;
1	6	4	2025-07-28	Leave	\N	10	2025-07-28 08:53:46+00
2	15	4	2025-07-28	Absent	\N	10	2025-07-28 08:53:30+00
3	16	4	2025-07-28	Present	\N	10	2025-07-28 09:09:23+00
4	17	4	2025-07-28	Absent	\N	10	2025-07-28 09:15:12+00
6	19	4	2025-07-28	Absent	\N	10	2025-07-28 09:22:16+00
7	20	4	2025-07-28	Present	\N	10	2025-07-28 09:26:16+00
8	15	4	2025-07-27	Absent	\N	10	2025-07-28 09:35:08+00
9	16	4	2025-07-27	Present	\N	10	2025-07-28 09:35:08+00
10	19	4	2025-07-27	Present	\N	10	2025-07-28 09:35:08+00
11	6	4	2025-07-27	Present	\N	10	2025-07-28 09:35:08+00
12	17	4	2025-07-27	Present	\N	10	2025-07-28 09:35:08+00
13	20	4	2025-07-27	Present	\N	10	2025-07-28 09:35:08+00
15	6	4	2025-08-30	Leave	\N	10	2025-08-07 09:19:11.792668+00
16	6	4	2025-08-08	Present	\N	10	2025-08-07 09:26:16.317456+00
14	6	4	2025-08-06	Present	\N	10	2025-08-07 09:41:03.347087+00
17	12	4	2025-08-07	Present	\N	10	2025-08-07 12:55:27.54209+00
5	6	4	2025-08-07	Absent	\N	10	2025-08-07 09:27:20.189809+00
\.


--
-- Data for Name: teacher_timings; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.teacher_timings (timing_id, teacher_id, day_of_week, opens_at, closes_at, is_closed) FROM stdin;
8	17	Monday	10:00:00	18:00:00	f
9	17	Tuesday	10:00:00	18:00:00	f
10	17	Wednesday	10:00:00	18:00:00	f
11	17	Thursday	10:00:00	18:00:00	f
12	17	Friday	10:00:00	18:00:00	f
13	17	Saturday	\N	\N	t
14	17	Sunday	\N	\N	t
15	12	Monday	10:00:00	18:00:00	f
16	12	Tuesday	10:00:00	18:00:00	f
17	12	Wednesday	10:00:00	18:00:00	f
18	12	Thursday	10:00:00	18:00:00	f
19	12	Friday	10:00:00	18:00:00	f
20	12	Saturday	10:00:00	18:00:00	f
21	12	Sunday	10:00:00	18:00:00	f
1	6	Monday	10:00:00	18:00:00	f
2	6	Tuesday	10:00:00	18:00:00	f
3	6	Wednesday	10:00:00	18:00:00	f
4	6	Thursday	10:00:00	18:00:00	f
5	6	Friday	10:00:00	18:00:00	f
6	6	Saturday	\N	\N	t
7	6	Sunday	\N	\N	t
\.


--
-- Data for Name: timetables; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.timetables (id, school_id, standard, class_teacher_id, timetable_file, original_filename, created_at) FROM stdin;
1	4	11	6	/BMC-SMS/pages/teacher/uploads/timetables/tt_6882190a814100.28997107_INTERNSHIP REGISTRATION FORM JAY.pdf	INTERNSHIP REGISTRATION FORM JAY.pdf	2025-07-24 11:29:14+00
\.


--
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.users (id, role, email, password, account_status, otp_hash, otp_expires_at) FROM stdin;
8	superadmin	shahswayam7125@gmail.com	$2y$10$T74F9Gb05l.StKcZg2sy/ub6PHeH.l3tT3Lv1JwOZzioXJCdEN0zO	active	\N	\N
10	principal	17fenill@gmail.com	$2y$10$WSgb/L7db17vIrhYjlXHguX6.gSNxFJq513rpU0JvlIZETe7eLTty	active	\N	\N
15	student	shh.260105@gmail.com	$2y$10$nj4MFVjg.rCq6AmmAOX3jewd9VDTeNZCvWoeE138bfbUQaFAZmtY2	active	\N	\N
23	librarian	devang@gmail.com	$2y$10$gwi.CWfIjYwlqScnYMzLbeTB1KEryLZ8tb1TTwv7r68rXwj/K1Z3u	active	\N	\N
12	teacher	jay@gmail.com	$2y$10$TUf4M/5ENm2A6oun27EuAuAz8Wlr8e8Ub8xwCR3w9i09nTBhFEWMO	active	\N	\N
13	principal	dhaval@gmail.com	$2y$10$/PhOzkuBDiabEZAW5eIZKuEr9Gcr0NTvpE7mGegA1Z6oNalzKXQcW	active	\N	\N
6	teacher	otherswayam@gmail.com	$2y$10$Dy/QvvkcnkheaPFyapfKR.9hzc/ZA5twsbVVqc6Gm.jR4nglV6Mv6	active	\N	\N
\.


--
-- Data for Name: schema_migrations; Type: TABLE DATA; Schema: realtime; Owner: supabase_admin
--

COPY realtime.schema_migrations (version, inserted_at) FROM stdin;
20211116024918	2025-08-06 08:12:55
20211116045059	2025-08-06 08:12:56
20211116050929	2025-08-06 08:12:56
20211116051442	2025-08-06 08:12:57
20211116212300	2025-08-06 08:12:58
20211116213355	2025-08-06 08:12:59
20211116213934	2025-08-06 08:12:59
20211116214523	2025-08-06 08:13:00
20211122062447	2025-08-06 08:13:01
20211124070109	2025-08-06 08:13:02
20211202204204	2025-08-06 08:13:03
20211202204605	2025-08-06 08:13:04
20211210212804	2025-08-06 08:13:06
20211228014915	2025-08-06 08:13:07
20220107221237	2025-08-06 08:13:08
20220228202821	2025-08-06 08:13:08
20220312004840	2025-08-06 08:13:09
20220603231003	2025-08-06 08:13:10
20220603232444	2025-08-06 08:13:11
20220615214548	2025-08-06 08:13:11
20220712093339	2025-08-06 08:13:12
20220908172859	2025-08-06 08:13:13
20220916233421	2025-08-06 08:13:13
20230119133233	2025-08-06 08:13:14
20230128025114	2025-08-06 08:13:15
20230128025212	2025-08-06 08:13:16
20230227211149	2025-08-06 08:13:16
20230228184745	2025-08-06 08:13:17
20230308225145	2025-08-06 08:13:17
20230328144023	2025-08-06 08:13:18
20231018144023	2025-08-06 08:13:19
20231204144023	2025-08-06 08:13:20
20231204144024	2025-08-06 08:13:20
20231204144025	2025-08-06 08:13:22
20240108234812	2025-08-06 08:13:23
20240109165339	2025-08-06 08:13:24
20240227174441	2025-08-06 08:13:25
20240311171622	2025-08-06 08:13:26
20240321100241	2025-08-06 08:13:28
20240401105812	2025-08-06 08:13:29
20240418121054	2025-08-06 08:13:30
20240523004032	2025-08-06 08:13:32
20240618124746	2025-08-06 08:13:33
20240801235015	2025-08-06 08:13:34
20240805133720	2025-08-06 08:13:34
20240827160934	2025-08-06 08:13:35
20240919163303	2025-08-06 08:13:36
20240919163305	2025-08-06 08:13:37
20241019105805	2025-08-06 08:13:37
20241030150047	2025-08-06 08:13:40
20241108114728	2025-08-06 08:13:40
20241121104152	2025-08-06 08:13:41
20241130184212	2025-08-06 08:13:42
20241220035512	2025-08-06 08:13:42
20241220123912	2025-08-06 08:13:43
20241224161212	2025-08-06 08:13:44
20250107150512	2025-08-06 08:13:44
20250110162412	2025-08-06 08:13:45
20250123174212	2025-08-06 08:13:46
20250128220012	2025-08-06 08:13:47
20250506224012	2025-08-06 08:13:47
20250523164012	2025-08-06 08:13:48
20250714121412	2025-08-06 08:13:49
\.


--
-- Data for Name: subscription; Type: TABLE DATA; Schema: realtime; Owner: supabase_admin
--

COPY realtime.subscription (id, subscription_id, entity, filters, claims, created_at) FROM stdin;
\.


--
-- Data for Name: buckets; Type: TABLE DATA; Schema: storage; Owner: supabase_storage_admin
--

COPY storage.buckets (id, name, owner, created_at, updated_at, public, avif_autodetection, file_size_limit, allowed_mime_types, owner_id) FROM stdin;
\.


--
-- Data for Name: migrations; Type: TABLE DATA; Schema: storage; Owner: supabase_storage_admin
--

COPY storage.migrations (id, name, hash, executed_at) FROM stdin;
0	create-migrations-table	e18db593bcde2aca2a408c4d1100f6abba2195df	2025-08-06 08:12:53.765823
1	initialmigration	6ab16121fbaa08bbd11b712d05f358f9b555d777	2025-08-06 08:12:53.806007
2	storage-schema	5c7968fd083fcea04050c1b7f6253c9771b99011	2025-08-06 08:12:53.809919
3	pathtoken-column	2cb1b0004b817b29d5b0a971af16bafeede4b70d	2025-08-06 08:12:53.934106
4	add-migrations-rls	427c5b63fe1c5937495d9c635c263ee7a5905058	2025-08-06 08:12:54.233305
5	add-size-functions	79e081a1455b63666c1294a440f8ad4b1e6a7f84	2025-08-06 08:12:54.238194
6	change-column-name-in-get-size	f93f62afdf6613ee5e7e815b30d02dc990201044	2025-08-06 08:12:54.246807
7	add-rls-to-buckets	e7e7f86adbc51049f341dfe8d30256c1abca17aa	2025-08-06 08:12:54.251728
8	add-public-to-buckets	fd670db39ed65f9d08b01db09d6202503ca2bab3	2025-08-06 08:12:54.255809
9	fix-search-function	3a0af29f42e35a4d101c259ed955b67e1bee6825	2025-08-06 08:12:54.26042
10	search-files-search-function	68dc14822daad0ffac3746a502234f486182ef6e	2025-08-06 08:12:54.26519
11	add-trigger-to-auto-update-updated_at-column	7425bdb14366d1739fa8a18c83100636d74dcaa2	2025-08-06 08:12:54.272349
12	add-automatic-avif-detection-flag	8e92e1266eb29518b6a4c5313ab8f29dd0d08df9	2025-08-06 08:12:54.290688
13	add-bucket-custom-limits	cce962054138135cd9a8c4bcd531598684b25e7d	2025-08-06 08:12:54.296024
14	use-bytes-for-max-size	941c41b346f9802b411f06f30e972ad4744dad27	2025-08-06 08:12:54.303661
15	add-can-insert-object-function	934146bc38ead475f4ef4b555c524ee5d66799e5	2025-08-06 08:12:54.424681
16	add-version	76debf38d3fd07dcfc747ca49096457d95b1221b	2025-08-06 08:12:54.510901
17	drop-owner-foreign-key	f1cbb288f1b7a4c1eb8c38504b80ae2a0153d101	2025-08-06 08:12:54.515342
18	add_owner_id_column_deprecate_owner	e7a511b379110b08e2f214be852c35414749fe66	2025-08-06 08:12:54.535468
19	alter-default-value-objects-id	02e5e22a78626187e00d173dc45f58fa66a4f043	2025-08-06 08:12:54.556104
20	list-objects-with-delimiter	cd694ae708e51ba82bf012bba00caf4f3b6393b7	2025-08-06 08:12:54.562111
21	s3-multipart-uploads	8c804d4a566c40cd1e4cc5b3725a664a9303657f	2025-08-06 08:12:54.576215
22	s3-multipart-uploads-big-ints	9737dc258d2397953c9953d9b86920b8be0cdb73	2025-08-06 08:12:54.604544
23	optimize-search-function	9d7e604cddc4b56a5422dc68c9313f4a1b6f132c	2025-08-06 08:12:54.626099
24	operation-function	8312e37c2bf9e76bbe841aa5fda889206d2bf8aa	2025-08-06 08:12:54.63028
25	custom-metadata	d974c6057c3db1c1f847afa0e291e6165693b990	2025-08-06 08:12:54.634482
\.


--
-- Data for Name: objects; Type: TABLE DATA; Schema: storage; Owner: supabase_storage_admin
--

COPY storage.objects (id, bucket_id, name, owner, created_at, updated_at, last_accessed_at, metadata, version, owner_id, user_metadata) FROM stdin;
\.


--
-- Data for Name: s3_multipart_uploads; Type: TABLE DATA; Schema: storage; Owner: supabase_storage_admin
--

COPY storage.s3_multipart_uploads (id, in_progress_size, upload_signature, bucket_id, key, version, owner_id, created_at, user_metadata) FROM stdin;
\.


--
-- Data for Name: s3_multipart_uploads_parts; Type: TABLE DATA; Schema: storage; Owner: supabase_storage_admin
--

COPY storage.s3_multipart_uploads_parts (id, upload_id, size, part_number, bucket_id, key, etag, owner_id, version, created_at) FROM stdin;
\.


--
-- Data for Name: secrets; Type: TABLE DATA; Schema: vault; Owner: supabase_admin
--

COPY vault.secrets (id, name, description, secret, key_id, nonce, created_at, updated_at) FROM stdin;
\.


--
-- Name: refresh_tokens_id_seq; Type: SEQUENCE SET; Schema: auth; Owner: supabase_auth_admin
--

SELECT pg_catalog.setval('auth.refresh_tokens_id_seq', 1, false);


--
-- Name: assignment_submissions_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.assignment_submissions_id_seq', 1, false);


--
-- Name: assignments_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.assignments_id_seq', 1, false);


--
-- Name: attendance_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.attendance_id_seq', 1, false);


--
-- Name: book_requests_request_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.book_requests_request_id_seq', 1, false);


--
-- Name: books_book_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.books_book_id_seq', 1, false);


--
-- Name: borrow_requests_request_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.borrow_requests_request_id_seq', 1, false);


--
-- Name: borrowing_records_record_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.borrowing_records_record_id_seq', 1, false);


--
-- Name: deleted_books_archived_book_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.deleted_books_archived_book_id_seq', 1, false);


--
-- Name: deleted_librarians_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.deleted_librarians_id_seq', 1, false);


--
-- Name: deleted_principals_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.deleted_principals_id_seq', 1, false);


--
-- Name: deleted_schools_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.deleted_schools_id_seq', 1, false);


--
-- Name: deleted_students_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.deleted_students_id_seq', 1, false);


--
-- Name: deleted_teachers_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.deleted_teachers_id_seq', 1, false);


--
-- Name: exam_timetables_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.exam_timetables_id_seq', 1, false);


--
-- Name: leave_applications_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.leave_applications_id_seq', 21, true);


--
-- Name: librarian_attendance_attendance_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.librarian_attendance_attendance_id_seq', 3, true);


--
-- Name: librarian_timings_timing_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.librarian_timings_timing_id_seq', 1, false);


--
-- Name: messages_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.messages_id_seq', 48, true);


--
-- Name: notes_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.notes_id_seq', 1, true);


--
-- Name: notice_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.notice_id_seq', 13, true);


--
-- Name: notifications_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.notifications_id_seq', 115, true);


--
-- Name: password_resets_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.password_resets_id_seq', 1, false);


--
-- Name: principal_attendance_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.principal_attendance_id_seq', 7, true);


--
-- Name: principal_timings_timing_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.principal_timings_timing_id_seq', 35, true);


--
-- Name: principal_to_bmc_notices_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.principal_to_bmc_notices_id_seq', 8, true);


--
-- Name: principal_to_librarian_notices_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.principal_to_librarian_notices_id_seq', 1, false);


--
-- Name: school_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.school_id_seq', 3, true);


--
-- Name: school_notice_recipients_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.school_notice_recipients_id_seq', 35, true);


--
-- Name: school_notices_content_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.school_notices_content_id_seq', 15, true);


--
-- Name: school_timetable_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.school_timetable_id_seq', 1, false);


--
-- Name: standard_subjects_std_subject_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.standard_subjects_std_subject_id_seq', 1, false);


--
-- Name: student_marks_mark_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.student_marks_mark_id_seq', 1, false);


--
-- Name: subjects_subject_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.subjects_subject_id_seq', 1, false);


--
-- Name: teacher_attendance_attendance_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.teacher_attendance_attendance_id_seq', 18, true);


--
-- Name: teacher_timings_timing_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.teacher_timings_timing_id_seq', 56, true);


--
-- Name: timetables_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.timetables_id_seq', 1, false);


--
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.users_id_seq', 34, true);


--
-- Name: subscription_id_seq; Type: SEQUENCE SET; Schema: realtime; Owner: supabase_admin
--

SELECT pg_catalog.setval('realtime.subscription_id_seq', 1, false);


--
-- Name: mfa_amr_claims amr_id_pk; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.mfa_amr_claims
    ADD CONSTRAINT amr_id_pk PRIMARY KEY (id);


--
-- Name: audit_log_entries audit_log_entries_pkey; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.audit_log_entries
    ADD CONSTRAINT audit_log_entries_pkey PRIMARY KEY (id);


--
-- Name: flow_state flow_state_pkey; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.flow_state
    ADD CONSTRAINT flow_state_pkey PRIMARY KEY (id);


--
-- Name: identities identities_pkey; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.identities
    ADD CONSTRAINT identities_pkey PRIMARY KEY (id);


--
-- Name: identities identities_provider_id_provider_unique; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.identities
    ADD CONSTRAINT identities_provider_id_provider_unique UNIQUE (provider_id, provider);


--
-- Name: instances instances_pkey; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.instances
    ADD CONSTRAINT instances_pkey PRIMARY KEY (id);


--
-- Name: mfa_amr_claims mfa_amr_claims_session_id_authentication_method_pkey; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.mfa_amr_claims
    ADD CONSTRAINT mfa_amr_claims_session_id_authentication_method_pkey UNIQUE (session_id, authentication_method);


--
-- Name: mfa_challenges mfa_challenges_pkey; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.mfa_challenges
    ADD CONSTRAINT mfa_challenges_pkey PRIMARY KEY (id);


--
-- Name: mfa_factors mfa_factors_last_challenged_at_key; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.mfa_factors
    ADD CONSTRAINT mfa_factors_last_challenged_at_key UNIQUE (last_challenged_at);


--
-- Name: mfa_factors mfa_factors_pkey; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.mfa_factors
    ADD CONSTRAINT mfa_factors_pkey PRIMARY KEY (id);


--
-- Name: one_time_tokens one_time_tokens_pkey; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.one_time_tokens
    ADD CONSTRAINT one_time_tokens_pkey PRIMARY KEY (id);


--
-- Name: refresh_tokens refresh_tokens_pkey; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.refresh_tokens
    ADD CONSTRAINT refresh_tokens_pkey PRIMARY KEY (id);


--
-- Name: refresh_tokens refresh_tokens_token_unique; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.refresh_tokens
    ADD CONSTRAINT refresh_tokens_token_unique UNIQUE (token);


--
-- Name: saml_providers saml_providers_entity_id_key; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.saml_providers
    ADD CONSTRAINT saml_providers_entity_id_key UNIQUE (entity_id);


--
-- Name: saml_providers saml_providers_pkey; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.saml_providers
    ADD CONSTRAINT saml_providers_pkey PRIMARY KEY (id);


--
-- Name: saml_relay_states saml_relay_states_pkey; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.saml_relay_states
    ADD CONSTRAINT saml_relay_states_pkey PRIMARY KEY (id);


--
-- Name: schema_migrations schema_migrations_pkey; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.schema_migrations
    ADD CONSTRAINT schema_migrations_pkey PRIMARY KEY (version);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: sso_domains sso_domains_pkey; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.sso_domains
    ADD CONSTRAINT sso_domains_pkey PRIMARY KEY (id);


--
-- Name: sso_providers sso_providers_pkey; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.sso_providers
    ADD CONSTRAINT sso_providers_pkey PRIMARY KEY (id);


--
-- Name: users users_phone_key; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.users
    ADD CONSTRAINT users_phone_key UNIQUE (phone);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: assignment_submissions assignment_submissions_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.assignment_submissions
    ADD CONSTRAINT assignment_submissions_pkey PRIMARY KEY (id);


--
-- Name: assignments assignments_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.assignments
    ADD CONSTRAINT assignments_pkey PRIMARY KEY (id);


--
-- Name: attendance attendance_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.attendance
    ADD CONSTRAINT attendance_pkey PRIMARY KEY (id);


--
-- Name: attendance attendance_student_id_attendance_date_period_number_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.attendance
    ADD CONSTRAINT attendance_student_id_attendance_date_period_number_key UNIQUE (student_id, attendance_date, period_number);


--
-- Name: book_requests book_requests_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.book_requests
    ADD CONSTRAINT book_requests_pkey PRIMARY KEY (request_id);


--
-- Name: books books_isbn_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.books
    ADD CONSTRAINT books_isbn_key UNIQUE (isbn);


--
-- Name: books books_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.books
    ADD CONSTRAINT books_pkey PRIMARY KEY (book_id);


--
-- Name: borrow_requests borrow_requests_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.borrow_requests
    ADD CONSTRAINT borrow_requests_pkey PRIMARY KEY (request_id);


--
-- Name: borrowing_records borrowing_records_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.borrowing_records
    ADD CONSTRAINT borrowing_records_pkey PRIMARY KEY (record_id);


--
-- Name: deleted_books deleted_books_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.deleted_books
    ADD CONSTRAINT deleted_books_pkey PRIMARY KEY (archived_book_id);


--
-- Name: deleted_librarians deleted_librarians_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.deleted_librarians
    ADD CONSTRAINT deleted_librarians_pkey PRIMARY KEY (id);


--
-- Name: deleted_principals deleted_principals_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.deleted_principals
    ADD CONSTRAINT deleted_principals_pkey PRIMARY KEY (id);


--
-- Name: deleted_schools deleted_schools_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.deleted_schools
    ADD CONSTRAINT deleted_schools_pkey PRIMARY KEY (id);


--
-- Name: deleted_students deleted_students_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.deleted_students
    ADD CONSTRAINT deleted_students_pkey PRIMARY KEY (id);


--
-- Name: deleted_teachers deleted_teachers_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.deleted_teachers
    ADD CONSTRAINT deleted_teachers_pkey PRIMARY KEY (id);


--
-- Name: exam_timetables exam_timetables_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.exam_timetables
    ADD CONSTRAINT exam_timetables_pkey PRIMARY KEY (id);


--
-- Name: leave_applications leave_applications_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.leave_applications
    ADD CONSTRAINT leave_applications_pkey PRIMARY KEY (id);


--
-- Name: librarian_attendance librarian_attendance_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.librarian_attendance
    ADD CONSTRAINT librarian_attendance_pkey PRIMARY KEY (attendance_id);


--
-- Name: librarian librarian_email_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.librarian
    ADD CONSTRAINT librarian_email_key UNIQUE (email);


--
-- Name: librarian librarian_phone_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.librarian
    ADD CONSTRAINT librarian_phone_key UNIQUE (phone);


--
-- Name: librarian librarian_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.librarian
    ADD CONSTRAINT librarian_pkey PRIMARY KEY (id);


--
-- Name: librarian_timings librarian_timings_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.librarian_timings
    ADD CONSTRAINT librarian_timings_pkey PRIMARY KEY (timing_id);


--
-- Name: messages messages_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.messages
    ADD CONSTRAINT messages_pkey PRIMARY KEY (id);


--
-- Name: notes notes_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.notes
    ADD CONSTRAINT notes_pkey PRIMARY KEY (id);


--
-- Name: notice notice_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.notice
    ADD CONSTRAINT notice_pkey PRIMARY KEY (id);


--
-- Name: notifications notifications_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.notifications
    ADD CONSTRAINT notifications_pkey PRIMARY KEY (id);


--
-- Name: password_resets password_resets_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.password_resets
    ADD CONSTRAINT password_resets_pkey PRIMARY KEY (id);


--
-- Name: principal_attendance principal_attendance_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.principal_attendance
    ADD CONSTRAINT principal_attendance_pkey PRIMARY KEY (id);


--
-- Name: principal_attendance principal_attendance_principal_id_attendance_date_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.principal_attendance
    ADD CONSTRAINT principal_attendance_principal_id_attendance_date_key UNIQUE (principal_id, attendance_date);


--
-- Name: principal principal_email_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.principal
    ADD CONSTRAINT principal_email_key UNIQUE (email);


--
-- Name: principal principal_phone_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.principal
    ADD CONSTRAINT principal_phone_key UNIQUE (phone);


--
-- Name: principal principal_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.principal
    ADD CONSTRAINT principal_pkey PRIMARY KEY (id);


--
-- Name: principal_timings principal_timings_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.principal_timings
    ADD CONSTRAINT principal_timings_pkey PRIMARY KEY (timing_id);


--
-- Name: principal_timings principal_timings_principal_id_day_of_week_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.principal_timings
    ADD CONSTRAINT principal_timings_principal_id_day_of_week_key UNIQUE (principal_id, day_of_week);


--
-- Name: principal_to_bmc_notices principal_to_bmc_notices_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.principal_to_bmc_notices
    ADD CONSTRAINT principal_to_bmc_notices_pkey PRIMARY KEY (id);


--
-- Name: principal_to_librarian_notices principal_to_librarian_notices_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.principal_to_librarian_notices
    ADD CONSTRAINT principal_to_librarian_notices_pkey PRIMARY KEY (id);


--
-- Name: school school_email_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.school
    ADD CONSTRAINT school_email_key UNIQUE (email);


--
-- Name: school_notice_recipients school_notice_recipients_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.school_notice_recipients
    ADD CONSTRAINT school_notice_recipients_pkey PRIMARY KEY (id);


--
-- Name: school_notices_content school_notices_content_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.school_notices_content
    ADD CONSTRAINT school_notices_content_pkey PRIMARY KEY (id);


--
-- Name: school school_phone_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.school
    ADD CONSTRAINT school_phone_key UNIQUE (phone);


--
-- Name: school school_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.school
    ADD CONSTRAINT school_pkey PRIMARY KEY (id);


--
-- Name: school_timetable school_timetable_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.school_timetable
    ADD CONSTRAINT school_timetable_pkey PRIMARY KEY (id);


--
-- Name: school_timetable school_timetable_school_id_standard_day_of_week_period_numb_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.school_timetable
    ADD CONSTRAINT school_timetable_school_id_standard_day_of_week_period_numb_key UNIQUE (school_id, standard, day_of_week, period_number);


--
-- Name: standard_subjects standard_subjects_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.standard_subjects
    ADD CONSTRAINT standard_subjects_pkey PRIMARY KEY (std_subject_id);


--
-- Name: standard_subjects standard_subjects_standard_subject_id_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.standard_subjects
    ADD CONSTRAINT standard_subjects_standard_subject_id_key UNIQUE (standard, subject_id);


--
-- Name: student_marks student_marks_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.student_marks
    ADD CONSTRAINT student_marks_pkey PRIMARY KEY (mark_id);


--
-- Name: student_marks student_marks_student_id_academic_year_exam_type_subject_na_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.student_marks
    ADD CONSTRAINT student_marks_student_id_academic_year_exam_type_subject_na_key UNIQUE (student_id, academic_year, exam_type, subject_name);


--
-- Name: student_marks student_marks_unique_entry; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.student_marks
    ADD CONSTRAINT student_marks_unique_entry UNIQUE (student_id, academic_year, exam_type, subject_name);


--
-- Name: student student_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.student
    ADD CONSTRAINT student_pkey PRIMARY KEY (id);


--
-- Name: subjects subjects_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.subjects
    ADD CONSTRAINT subjects_pkey PRIMARY KEY (subject_id);


--
-- Name: subjects subjects_subject_name_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.subjects
    ADD CONSTRAINT subjects_subject_name_key UNIQUE (subject_name);


--
-- Name: teacher_attendance teacher_attendance_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.teacher_attendance
    ADD CONSTRAINT teacher_attendance_pkey PRIMARY KEY (attendance_id);


--
-- Name: teacher_attendance teacher_attendance_teacher_id_attendance_date_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.teacher_attendance
    ADD CONSTRAINT teacher_attendance_teacher_id_attendance_date_key UNIQUE (teacher_id, attendance_date);


--
-- Name: teacher_timings teacher_day_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.teacher_timings
    ADD CONSTRAINT teacher_day_unique UNIQUE (teacher_id, day_of_week);


--
-- Name: teacher teacher_email_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.teacher
    ADD CONSTRAINT teacher_email_key UNIQUE (email);


--
-- Name: teacher teacher_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.teacher
    ADD CONSTRAINT teacher_pkey PRIMARY KEY (id);


--
-- Name: teacher_timings teacher_timings_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.teacher_timings
    ADD CONSTRAINT teacher_timings_pkey PRIMARY KEY (timing_id);


--
-- Name: timetables timetables_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.timetables
    ADD CONSTRAINT timetables_pkey PRIMARY KEY (id);


--
-- Name: librarian_timings unique_librarian_day; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.librarian_timings
    ADD CONSTRAINT unique_librarian_day UNIQUE (librarian_id, day_of_week);


--
-- Name: librarian_attendance uq_librarian_attendance_date; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.librarian_attendance
    ADD CONSTRAINT uq_librarian_attendance_date UNIQUE (librarian_id, attendance_date);


--
-- Name: users users_email_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_key UNIQUE (email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: messages messages_pkey; Type: CONSTRAINT; Schema: realtime; Owner: supabase_realtime_admin
--

ALTER TABLE ONLY realtime.messages
    ADD CONSTRAINT messages_pkey PRIMARY KEY (id, inserted_at);


--
-- Name: subscription pk_subscription; Type: CONSTRAINT; Schema: realtime; Owner: supabase_admin
--

ALTER TABLE ONLY realtime.subscription
    ADD CONSTRAINT pk_subscription PRIMARY KEY (id);


--
-- Name: schema_migrations schema_migrations_pkey; Type: CONSTRAINT; Schema: realtime; Owner: supabase_admin
--

ALTER TABLE ONLY realtime.schema_migrations
    ADD CONSTRAINT schema_migrations_pkey PRIMARY KEY (version);


--
-- Name: buckets buckets_pkey; Type: CONSTRAINT; Schema: storage; Owner: supabase_storage_admin
--

ALTER TABLE ONLY storage.buckets
    ADD CONSTRAINT buckets_pkey PRIMARY KEY (id);


--
-- Name: migrations migrations_name_key; Type: CONSTRAINT; Schema: storage; Owner: supabase_storage_admin
--

ALTER TABLE ONLY storage.migrations
    ADD CONSTRAINT migrations_name_key UNIQUE (name);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: storage; Owner: supabase_storage_admin
--

ALTER TABLE ONLY storage.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: objects objects_pkey; Type: CONSTRAINT; Schema: storage; Owner: supabase_storage_admin
--

ALTER TABLE ONLY storage.objects
    ADD CONSTRAINT objects_pkey PRIMARY KEY (id);


--
-- Name: s3_multipart_uploads_parts s3_multipart_uploads_parts_pkey; Type: CONSTRAINT; Schema: storage; Owner: supabase_storage_admin
--

ALTER TABLE ONLY storage.s3_multipart_uploads_parts
    ADD CONSTRAINT s3_multipart_uploads_parts_pkey PRIMARY KEY (id);


--
-- Name: s3_multipart_uploads s3_multipart_uploads_pkey; Type: CONSTRAINT; Schema: storage; Owner: supabase_storage_admin
--

ALTER TABLE ONLY storage.s3_multipart_uploads
    ADD CONSTRAINT s3_multipart_uploads_pkey PRIMARY KEY (id);


--
-- Name: audit_logs_instance_id_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX audit_logs_instance_id_idx ON auth.audit_log_entries USING btree (instance_id);


--
-- Name: confirmation_token_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE UNIQUE INDEX confirmation_token_idx ON auth.users USING btree (confirmation_token) WHERE ((confirmation_token)::text !~ '^[0-9 ]*$'::text);


--
-- Name: email_change_token_current_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE UNIQUE INDEX email_change_token_current_idx ON auth.users USING btree (email_change_token_current) WHERE ((email_change_token_current)::text !~ '^[0-9 ]*$'::text);


--
-- Name: email_change_token_new_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE UNIQUE INDEX email_change_token_new_idx ON auth.users USING btree (email_change_token_new) WHERE ((email_change_token_new)::text !~ '^[0-9 ]*$'::text);


--
-- Name: factor_id_created_at_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX factor_id_created_at_idx ON auth.mfa_factors USING btree (user_id, created_at);


--
-- Name: flow_state_created_at_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX flow_state_created_at_idx ON auth.flow_state USING btree (created_at DESC);


--
-- Name: identities_email_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX identities_email_idx ON auth.identities USING btree (email text_pattern_ops);


--
-- Name: INDEX identities_email_idx; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON INDEX auth.identities_email_idx IS 'Auth: Ensures indexed queries on the email column';


--
-- Name: identities_user_id_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX identities_user_id_idx ON auth.identities USING btree (user_id);


--
-- Name: idx_auth_code; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX idx_auth_code ON auth.flow_state USING btree (auth_code);


--
-- Name: idx_user_id_auth_method; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX idx_user_id_auth_method ON auth.flow_state USING btree (user_id, authentication_method);


--
-- Name: mfa_challenge_created_at_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX mfa_challenge_created_at_idx ON auth.mfa_challenges USING btree (created_at DESC);


--
-- Name: mfa_factors_user_friendly_name_unique; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE UNIQUE INDEX mfa_factors_user_friendly_name_unique ON auth.mfa_factors USING btree (friendly_name, user_id) WHERE (TRIM(BOTH FROM friendly_name) <> ''::text);


--
-- Name: mfa_factors_user_id_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX mfa_factors_user_id_idx ON auth.mfa_factors USING btree (user_id);


--
-- Name: one_time_tokens_relates_to_hash_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX one_time_tokens_relates_to_hash_idx ON auth.one_time_tokens USING hash (relates_to);


--
-- Name: one_time_tokens_token_hash_hash_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX one_time_tokens_token_hash_hash_idx ON auth.one_time_tokens USING hash (token_hash);


--
-- Name: one_time_tokens_user_id_token_type_key; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE UNIQUE INDEX one_time_tokens_user_id_token_type_key ON auth.one_time_tokens USING btree (user_id, token_type);


--
-- Name: reauthentication_token_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE UNIQUE INDEX reauthentication_token_idx ON auth.users USING btree (reauthentication_token) WHERE ((reauthentication_token)::text !~ '^[0-9 ]*$'::text);


--
-- Name: recovery_token_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE UNIQUE INDEX recovery_token_idx ON auth.users USING btree (recovery_token) WHERE ((recovery_token)::text !~ '^[0-9 ]*$'::text);


--
-- Name: refresh_tokens_instance_id_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX refresh_tokens_instance_id_idx ON auth.refresh_tokens USING btree (instance_id);


--
-- Name: refresh_tokens_instance_id_user_id_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX refresh_tokens_instance_id_user_id_idx ON auth.refresh_tokens USING btree (instance_id, user_id);


--
-- Name: refresh_tokens_parent_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX refresh_tokens_parent_idx ON auth.refresh_tokens USING btree (parent);


--
-- Name: refresh_tokens_session_id_revoked_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX refresh_tokens_session_id_revoked_idx ON auth.refresh_tokens USING btree (session_id, revoked);


--
-- Name: refresh_tokens_updated_at_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX refresh_tokens_updated_at_idx ON auth.refresh_tokens USING btree (updated_at DESC);


--
-- Name: saml_providers_sso_provider_id_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX saml_providers_sso_provider_id_idx ON auth.saml_providers USING btree (sso_provider_id);


--
-- Name: saml_relay_states_created_at_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX saml_relay_states_created_at_idx ON auth.saml_relay_states USING btree (created_at DESC);


--
-- Name: saml_relay_states_for_email_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX saml_relay_states_for_email_idx ON auth.saml_relay_states USING btree (for_email);


--
-- Name: saml_relay_states_sso_provider_id_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX saml_relay_states_sso_provider_id_idx ON auth.saml_relay_states USING btree (sso_provider_id);


--
-- Name: sessions_not_after_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX sessions_not_after_idx ON auth.sessions USING btree (not_after DESC);


--
-- Name: sessions_user_id_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX sessions_user_id_idx ON auth.sessions USING btree (user_id);


--
-- Name: sso_domains_domain_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE UNIQUE INDEX sso_domains_domain_idx ON auth.sso_domains USING btree (lower(domain));


--
-- Name: sso_domains_sso_provider_id_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX sso_domains_sso_provider_id_idx ON auth.sso_domains USING btree (sso_provider_id);


--
-- Name: sso_providers_resource_id_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE UNIQUE INDEX sso_providers_resource_id_idx ON auth.sso_providers USING btree (lower(resource_id));


--
-- Name: unique_phone_factor_per_user; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE UNIQUE INDEX unique_phone_factor_per_user ON auth.mfa_factors USING btree (user_id, phone);


--
-- Name: user_id_created_at_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX user_id_created_at_idx ON auth.sessions USING btree (user_id, created_at);


--
-- Name: users_email_partial_key; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE UNIQUE INDEX users_email_partial_key ON auth.users USING btree (email) WHERE (is_sso_user = false);


--
-- Name: INDEX users_email_partial_key; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON INDEX auth.users_email_partial_key IS 'Auth: A partial unique index that applies only when is_sso_user is false';


--
-- Name: users_instance_id_email_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX users_instance_id_email_idx ON auth.users USING btree (instance_id, lower((email)::text));


--
-- Name: users_instance_id_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX users_instance_id_idx ON auth.users USING btree (instance_id);


--
-- Name: users_is_anonymous_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX users_is_anonymous_idx ON auth.users USING btree (is_anonymous);


--
-- Name: assignment_submissions_assignment_id_idx; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX assignment_submissions_assignment_id_idx ON public.assignment_submissions USING btree (assignment_id);


--
-- Name: assignment_submissions_student_id_idx; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX assignment_submissions_student_id_idx ON public.assignment_submissions USING btree (student_id);


--
-- Name: assignments_teacher_id_idx; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX assignments_teacher_id_idx ON public.assignments USING btree (teacher_id);


--
-- Name: book_requests_requester_id_idx; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX book_requests_requester_id_idx ON public.book_requests USING btree (requester_id);


--
-- Name: book_requests_school_id_idx; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX book_requests_school_id_idx ON public.book_requests USING btree (school_id);


--
-- Name: books_school_id_idx; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX books_school_id_idx ON public.books USING btree (school_id);


--
-- Name: borrow_requests_book_id_idx; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX borrow_requests_book_id_idx ON public.borrow_requests USING btree (book_id);


--
-- Name: borrow_requests_borrower_id_idx; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX borrow_requests_borrower_id_idx ON public.borrow_requests USING btree (borrower_id);


--
-- Name: borrow_requests_librarian_id_idx; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX borrow_requests_librarian_id_idx ON public.borrow_requests USING btree (librarian_id);


--
-- Name: borrow_requests_school_id_idx; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX borrow_requests_school_id_idx ON public.borrow_requests USING btree (school_id);


--
-- Name: borrowing_records_book_id_idx; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX borrowing_records_book_id_idx ON public.borrowing_records USING btree (book_id);


--
-- Name: borrowing_records_borrower_id_idx; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX borrowing_records_borrower_id_idx ON public.borrowing_records USING btree (borrower_id);


--
-- Name: exam_timetables_principal_id_idx; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX exam_timetables_principal_id_idx ON public.exam_timetables USING btree (principal_id);


--
-- Name: exam_timetables_school_id_idx; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX exam_timetables_school_id_idx ON public.exam_timetables USING btree (school_id);


--
-- Name: idx_librarian_timings_librarian_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_librarian_timings_librarian_id ON public.librarian_timings USING btree (librarian_id);


--
-- Name: idx_ptln_principal_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_ptln_principal_id ON public.principal_to_librarian_notices USING btree (principal_id);


--
-- Name: idx_ptln_school_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_ptln_school_id ON public.principal_to_librarian_notices USING btree (school_id);


--
-- Name: idx_users_email; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_users_email ON public.users USING btree (email);


--
-- Name: leave_applications_teacher_id_idx; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX leave_applications_teacher_id_idx ON public.leave_applications USING btree (teacher_id);


--
-- Name: librarian_attendance_marked_by_user_id_idx; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX librarian_attendance_marked_by_user_id_idx ON public.librarian_attendance USING btree (marked_by_user_id);


--
-- Name: librarian_attendance_school_id_idx; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX librarian_attendance_school_id_idx ON public.librarian_attendance USING btree (school_id);


--
-- Name: librarian_school_id_idx; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX librarian_school_id_idx ON public.librarian USING btree (school_id);


--
-- Name: messages_receiver_id_idx; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX messages_receiver_id_idx ON public.messages USING btree (receiver_id);


--
-- Name: messages_sender_id_idx; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX messages_sender_id_idx ON public.messages USING btree (sender_id);


--
-- Name: notes_user_id_idx; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX notes_user_id_idx ON public.notes USING btree (user_id);


--
-- Name: notice_user_id_idx; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX notice_user_id_idx ON public.notice USING btree (user_id);


--
-- Name: notifications_user_id_idx; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX notifications_user_id_idx ON public.notifications USING btree (user_id);


--
-- Name: password_resets_user_id_idx; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX password_resets_user_id_idx ON public.password_resets USING btree (user_id);


--
-- Name: principal_attendance_school_id_idx; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX principal_attendance_school_id_idx ON public.principal_attendance USING btree (school_id);


--
-- Name: principal_school_id_idx; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX principal_school_id_idx ON public.principal USING btree (school_id);


--
-- Name: principal_to_bmc_notices_principal_id_idx; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX principal_to_bmc_notices_principal_id_idx ON public.principal_to_bmc_notices USING btree (principal_id);


--
-- Name: principal_to_bmc_notices_school_id_idx; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX principal_to_bmc_notices_school_id_idx ON public.principal_to_bmc_notices USING btree (school_id);


--
-- Name: school_notice_recipients_notice_id_idx; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX school_notice_recipients_notice_id_idx ON public.school_notice_recipients USING btree (notice_id);


--
-- Name: school_notices_content_school_id_idx; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX school_notices_content_school_id_idx ON public.school_notices_content USING btree (school_id);


--
-- Name: school_notices_content_user_id_idx; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX school_notices_content_user_id_idx ON public.school_notices_content USING btree (user_id);


--
-- Name: standard_subjects_subject_id_idx; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX standard_subjects_subject_id_idx ON public.standard_subjects USING btree (subject_id);


--
-- Name: student_marks_entered_by_user_id_idx; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX student_marks_entered_by_user_id_idx ON public.student_marks USING btree (entered_by_user_id);


--
-- Name: student_marks_school_id_idx; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX student_marks_school_id_idx ON public.student_marks USING btree (school_id);


--
-- Name: student_school_id_idx; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX student_school_id_idx ON public.student USING btree (school_id);


--
-- Name: teacher_attendance_marked_by_user_id_idx; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX teacher_attendance_marked_by_user_id_idx ON public.teacher_attendance USING btree (marked_by_user_id);


--
-- Name: teacher_attendance_school_id_idx; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX teacher_attendance_school_id_idx ON public.teacher_attendance USING btree (school_id);


--
-- Name: timetables_class_teacher_id_idx; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX timetables_class_teacher_id_idx ON public.timetables USING btree (class_teacher_id);


--
-- Name: timetables_school_id_idx; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX timetables_school_id_idx ON public.timetables USING btree (school_id);


--
-- Name: ix_realtime_subscription_entity; Type: INDEX; Schema: realtime; Owner: supabase_admin
--

CREATE INDEX ix_realtime_subscription_entity ON realtime.subscription USING btree (entity);


--
-- Name: subscription_subscription_id_entity_filters_key; Type: INDEX; Schema: realtime; Owner: supabase_admin
--

CREATE UNIQUE INDEX subscription_subscription_id_entity_filters_key ON realtime.subscription USING btree (subscription_id, entity, filters);


--
-- Name: bname; Type: INDEX; Schema: storage; Owner: supabase_storage_admin
--

CREATE UNIQUE INDEX bname ON storage.buckets USING btree (name);


--
-- Name: bucketid_objname; Type: INDEX; Schema: storage; Owner: supabase_storage_admin
--

CREATE UNIQUE INDEX bucketid_objname ON storage.objects USING btree (bucket_id, name);


--
-- Name: idx_multipart_uploads_list; Type: INDEX; Schema: storage; Owner: supabase_storage_admin
--

CREATE INDEX idx_multipart_uploads_list ON storage.s3_multipart_uploads USING btree (bucket_id, key, created_at);


--
-- Name: idx_objects_bucket_id_name; Type: INDEX; Schema: storage; Owner: supabase_storage_admin
--

CREATE INDEX idx_objects_bucket_id_name ON storage.objects USING btree (bucket_id, name COLLATE "C");


--
-- Name: name_prefix_search; Type: INDEX; Schema: storage; Owner: supabase_storage_admin
--

CREATE INDEX name_prefix_search ON storage.objects USING btree (name text_pattern_ops);


--
-- Name: subscription tr_check_filters; Type: TRIGGER; Schema: realtime; Owner: supabase_admin
--

CREATE TRIGGER tr_check_filters BEFORE INSERT OR UPDATE ON realtime.subscription FOR EACH ROW EXECUTE FUNCTION realtime.subscription_check_filters();


--
-- Name: objects update_objects_updated_at; Type: TRIGGER; Schema: storage; Owner: supabase_storage_admin
--

CREATE TRIGGER update_objects_updated_at BEFORE UPDATE ON storage.objects FOR EACH ROW EXECUTE FUNCTION storage.update_updated_at_column();


--
-- Name: identities identities_user_id_fkey; Type: FK CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.identities
    ADD CONSTRAINT identities_user_id_fkey FOREIGN KEY (user_id) REFERENCES auth.users(id) ON DELETE CASCADE;


--
-- Name: mfa_amr_claims mfa_amr_claims_session_id_fkey; Type: FK CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.mfa_amr_claims
    ADD CONSTRAINT mfa_amr_claims_session_id_fkey FOREIGN KEY (session_id) REFERENCES auth.sessions(id) ON DELETE CASCADE;


--
-- Name: mfa_challenges mfa_challenges_auth_factor_id_fkey; Type: FK CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.mfa_challenges
    ADD CONSTRAINT mfa_challenges_auth_factor_id_fkey FOREIGN KEY (factor_id) REFERENCES auth.mfa_factors(id) ON DELETE CASCADE;


--
-- Name: mfa_factors mfa_factors_user_id_fkey; Type: FK CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.mfa_factors
    ADD CONSTRAINT mfa_factors_user_id_fkey FOREIGN KEY (user_id) REFERENCES auth.users(id) ON DELETE CASCADE;


--
-- Name: one_time_tokens one_time_tokens_user_id_fkey; Type: FK CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.one_time_tokens
    ADD CONSTRAINT one_time_tokens_user_id_fkey FOREIGN KEY (user_id) REFERENCES auth.users(id) ON DELETE CASCADE;


--
-- Name: refresh_tokens refresh_tokens_session_id_fkey; Type: FK CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.refresh_tokens
    ADD CONSTRAINT refresh_tokens_session_id_fkey FOREIGN KEY (session_id) REFERENCES auth.sessions(id) ON DELETE CASCADE;


--
-- Name: saml_providers saml_providers_sso_provider_id_fkey; Type: FK CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.saml_providers
    ADD CONSTRAINT saml_providers_sso_provider_id_fkey FOREIGN KEY (sso_provider_id) REFERENCES auth.sso_providers(id) ON DELETE CASCADE;


--
-- Name: saml_relay_states saml_relay_states_flow_state_id_fkey; Type: FK CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.saml_relay_states
    ADD CONSTRAINT saml_relay_states_flow_state_id_fkey FOREIGN KEY (flow_state_id) REFERENCES auth.flow_state(id) ON DELETE CASCADE;


--
-- Name: saml_relay_states saml_relay_states_sso_provider_id_fkey; Type: FK CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.saml_relay_states
    ADD CONSTRAINT saml_relay_states_sso_provider_id_fkey FOREIGN KEY (sso_provider_id) REFERENCES auth.sso_providers(id) ON DELETE CASCADE;


--
-- Name: sessions sessions_user_id_fkey; Type: FK CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.sessions
    ADD CONSTRAINT sessions_user_id_fkey FOREIGN KEY (user_id) REFERENCES auth.users(id) ON DELETE CASCADE;


--
-- Name: sso_domains sso_domains_sso_provider_id_fkey; Type: FK CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.sso_domains
    ADD CONSTRAINT sso_domains_sso_provider_id_fkey FOREIGN KEY (sso_provider_id) REFERENCES auth.sso_providers(id) ON DELETE CASCADE;


--
-- Name: assignments assignments_ibfk_1; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.assignments
    ADD CONSTRAINT assignments_ibfk_1 FOREIGN KEY (teacher_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: book_requests book_requests_ibfk_1; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.book_requests
    ADD CONSTRAINT book_requests_ibfk_1 FOREIGN KEY (requester_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: book_requests book_requests_ibfk_2; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.book_requests
    ADD CONSTRAINT book_requests_ibfk_2 FOREIGN KEY (school_id) REFERENCES public.school(id) ON DELETE CASCADE;


--
-- Name: books books_ibfk_1; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.books
    ADD CONSTRAINT books_ibfk_1 FOREIGN KEY (school_id) REFERENCES public.school(id) ON DELETE CASCADE;


--
-- Name: borrow_requests borrow_requests_ibfk_1; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.borrow_requests
    ADD CONSTRAINT borrow_requests_ibfk_1 FOREIGN KEY (book_id) REFERENCES public.books(book_id) ON DELETE CASCADE;


--
-- Name: borrow_requests borrow_requests_ibfk_2; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.borrow_requests
    ADD CONSTRAINT borrow_requests_ibfk_2 FOREIGN KEY (school_id) REFERENCES public.school(id) ON DELETE CASCADE;


--
-- Name: borrow_requests borrow_requests_ibfk_3; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.borrow_requests
    ADD CONSTRAINT borrow_requests_ibfk_3 FOREIGN KEY (borrower_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: borrow_requests borrow_requests_ibfk_4; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.borrow_requests
    ADD CONSTRAINT borrow_requests_ibfk_4 FOREIGN KEY (librarian_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: borrowing_records borrowing_records_ibfk_1; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.borrowing_records
    ADD CONSTRAINT borrowing_records_ibfk_1 FOREIGN KEY (book_id) REFERENCES public.books(book_id) ON DELETE CASCADE;


--
-- Name: borrowing_records borrowing_records_ibfk_2; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.borrowing_records
    ADD CONSTRAINT borrowing_records_ibfk_2 FOREIGN KEY (borrower_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: exam_timetables fk_ett_principal; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.exam_timetables
    ADD CONSTRAINT fk_ett_principal FOREIGN KEY (principal_id) REFERENCES public.principal(id) ON DELETE CASCADE;


--
-- Name: exam_timetables fk_ett_school; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.exam_timetables
    ADD CONSTRAINT fk_ett_school FOREIGN KEY (school_id) REFERENCES public.school(id) ON DELETE CASCADE;


--
-- Name: leave_applications fk_leave_teacher_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.leave_applications
    ADD CONSTRAINT fk_leave_teacher_id FOREIGN KEY (teacher_id) REFERENCES public.teacher(id) ON DELETE CASCADE;


--
-- Name: librarian fk_librarian_school_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.librarian
    ADD CONSTRAINT fk_librarian_school_id FOREIGN KEY (school_id) REFERENCES public.school(id) ON DELETE CASCADE;


--
-- Name: librarian_timings fk_librarian_timing; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.librarian_timings
    ADD CONSTRAINT fk_librarian_timing FOREIGN KEY (librarian_id) REFERENCES public.librarian(id) ON DELETE CASCADE;


--
-- Name: librarian fk_librarian_user_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.librarian
    ADD CONSTRAINT fk_librarian_user_id FOREIGN KEY (id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: school_notice_recipients fk_notice_recipients_notice_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.school_notice_recipients
    ADD CONSTRAINT fk_notice_recipients_notice_id FOREIGN KEY (notice_id) REFERENCES public.school_notices_content(id) ON DELETE CASCADE;


--
-- Name: principal_to_bmc_notices fk_pbn_principal; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.principal_to_bmc_notices
    ADD CONSTRAINT fk_pbn_principal FOREIGN KEY (principal_id) REFERENCES public.principal(id) ON DELETE CASCADE;


--
-- Name: principal_to_bmc_notices fk_pbn_school; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.principal_to_bmc_notices
    ADD CONSTRAINT fk_pbn_school FOREIGN KEY (school_id) REFERENCES public.school(id) ON DELETE CASCADE;


--
-- Name: principal fk_principal_user_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.principal
    ADD CONSTRAINT fk_principal_user_id FOREIGN KEY (id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: principal_to_librarian_notices fk_ptln_principal; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.principal_to_librarian_notices
    ADD CONSTRAINT fk_ptln_principal FOREIGN KEY (principal_id) REFERENCES public.principal(id) ON DELETE CASCADE;


--
-- Name: principal_to_librarian_notices fk_ptln_school; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.principal_to_librarian_notices
    ADD CONSTRAINT fk_ptln_school FOREIGN KEY (school_id) REFERENCES public.school(id) ON DELETE CASCADE;


--
-- Name: student fk_student_user_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.student
    ADD CONSTRAINT fk_student_user_id FOREIGN KEY (id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: teacher fk_teacher_user_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.teacher
    ADD CONSTRAINT fk_teacher_user_id FOREIGN KEY (id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: principal_timings fk_timing_principal_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.principal_timings
    ADD CONSTRAINT fk_timing_principal_id FOREIGN KEY (principal_id) REFERENCES public.principal(id) ON DELETE CASCADE;


--
-- Name: messages messages_ibfk_1; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.messages
    ADD CONSTRAINT messages_ibfk_1 FOREIGN KEY (sender_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: messages messages_ibfk_2; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.messages
    ADD CONSTRAINT messages_ibfk_2 FOREIGN KEY (receiver_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: notes notes_ibfk_1; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.notes
    ADD CONSTRAINT notes_ibfk_1 FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: notice notice_ibfk_1; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.notice
    ADD CONSTRAINT notice_ibfk_1 FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: notifications notifications_ibfk_1; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.notifications
    ADD CONSTRAINT notifications_ibfk_1 FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: password_resets password_resets_ibfk_1; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.password_resets
    ADD CONSTRAINT password_resets_ibfk_1 FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: principal_attendance principal_attendance_ibfk_1; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.principal_attendance
    ADD CONSTRAINT principal_attendance_ibfk_1 FOREIGN KEY (principal_id) REFERENCES public.principal(id) ON DELETE CASCADE;


--
-- Name: principal_attendance principal_attendance_ibfk_2; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.principal_attendance
    ADD CONSTRAINT principal_attendance_ibfk_2 FOREIGN KEY (school_id) REFERENCES public.school(id) ON DELETE CASCADE;


--
-- Name: principal principal_ibfk_1; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.principal
    ADD CONSTRAINT principal_ibfk_1 FOREIGN KEY (school_id) REFERENCES public.school(id);


--
-- Name: school_notices_content school_notices_content_ibfk_1; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.school_notices_content
    ADD CONSTRAINT school_notices_content_ibfk_1 FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: school_notices_content school_notices_content_ibfk_2; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.school_notices_content
    ADD CONSTRAINT school_notices_content_ibfk_2 FOREIGN KEY (school_id) REFERENCES public.school(id) ON DELETE CASCADE;


--
-- Name: standard_subjects standard_subjects_ibfk_1; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.standard_subjects
    ADD CONSTRAINT standard_subjects_ibfk_1 FOREIGN KEY (subject_id) REFERENCES public.subjects(subject_id) ON DELETE CASCADE;


--
-- Name: student student_ibfk_1; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.student
    ADD CONSTRAINT student_ibfk_1 FOREIGN KEY (school_id) REFERENCES public.school(id) ON DELETE CASCADE;


--
-- Name: student_marks student_marks_ibfk_1; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.student_marks
    ADD CONSTRAINT student_marks_ibfk_1 FOREIGN KEY (student_id) REFERENCES public.student(id) ON DELETE CASCADE;


--
-- Name: student_marks student_marks_ibfk_2; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.student_marks
    ADD CONSTRAINT student_marks_ibfk_2 FOREIGN KEY (school_id) REFERENCES public.school(id) ON DELETE CASCADE;


--
-- Name: student_marks student_marks_ibfk_3; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.student_marks
    ADD CONSTRAINT student_marks_ibfk_3 FOREIGN KEY (entered_by_user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: assignment_submissions submissions_ibfk_1; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.assignment_submissions
    ADD CONSTRAINT submissions_ibfk_1 FOREIGN KEY (assignment_id) REFERENCES public.assignments(id) ON DELETE CASCADE;


--
-- Name: assignment_submissions submissions_ibfk_2; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.assignment_submissions
    ADD CONSTRAINT submissions_ibfk_2 FOREIGN KEY (student_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: teacher_attendance teacher_attendance_ibfk_1; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.teacher_attendance
    ADD CONSTRAINT teacher_attendance_ibfk_1 FOREIGN KEY (school_id) REFERENCES public.school(id) ON DELETE CASCADE;


--
-- Name: teacher_attendance teacher_attendance_ibfk_2; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.teacher_attendance
    ADD CONSTRAINT teacher_attendance_ibfk_2 FOREIGN KEY (marked_by_user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: teacher teacher_ibfk_1; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.teacher
    ADD CONSTRAINT teacher_ibfk_1 FOREIGN KEY (school_id) REFERENCES public.school(id) ON DELETE CASCADE;


--
-- Name: timetables timetables_ibfk_1; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.timetables
    ADD CONSTRAINT timetables_ibfk_1 FOREIGN KEY (class_teacher_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: timetables timetables_ibfk_2; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.timetables
    ADD CONSTRAINT timetables_ibfk_2 FOREIGN KEY (school_id) REFERENCES public.school(id) ON DELETE CASCADE;


--
-- Name: objects objects_bucketId_fkey; Type: FK CONSTRAINT; Schema: storage; Owner: supabase_storage_admin
--

ALTER TABLE ONLY storage.objects
    ADD CONSTRAINT "objects_bucketId_fkey" FOREIGN KEY (bucket_id) REFERENCES storage.buckets(id);


--
-- Name: s3_multipart_uploads s3_multipart_uploads_bucket_id_fkey; Type: FK CONSTRAINT; Schema: storage; Owner: supabase_storage_admin
--

ALTER TABLE ONLY storage.s3_multipart_uploads
    ADD CONSTRAINT s3_multipart_uploads_bucket_id_fkey FOREIGN KEY (bucket_id) REFERENCES storage.buckets(id);


--
-- Name: s3_multipart_uploads_parts s3_multipart_uploads_parts_bucket_id_fkey; Type: FK CONSTRAINT; Schema: storage; Owner: supabase_storage_admin
--

ALTER TABLE ONLY storage.s3_multipart_uploads_parts
    ADD CONSTRAINT s3_multipart_uploads_parts_bucket_id_fkey FOREIGN KEY (bucket_id) REFERENCES storage.buckets(id);


--
-- Name: s3_multipart_uploads_parts s3_multipart_uploads_parts_upload_id_fkey; Type: FK CONSTRAINT; Schema: storage; Owner: supabase_storage_admin
--

ALTER TABLE ONLY storage.s3_multipart_uploads_parts
    ADD CONSTRAINT s3_multipart_uploads_parts_upload_id_fkey FOREIGN KEY (upload_id) REFERENCES storage.s3_multipart_uploads(id) ON DELETE CASCADE;


--
-- Name: audit_log_entries; Type: ROW SECURITY; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE auth.audit_log_entries ENABLE ROW LEVEL SECURITY;

--
-- Name: flow_state; Type: ROW SECURITY; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE auth.flow_state ENABLE ROW LEVEL SECURITY;

--
-- Name: identities; Type: ROW SECURITY; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE auth.identities ENABLE ROW LEVEL SECURITY;

--
-- Name: instances; Type: ROW SECURITY; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE auth.instances ENABLE ROW LEVEL SECURITY;

--
-- Name: mfa_amr_claims; Type: ROW SECURITY; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE auth.mfa_amr_claims ENABLE ROW LEVEL SECURITY;

--
-- Name: mfa_challenges; Type: ROW SECURITY; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE auth.mfa_challenges ENABLE ROW LEVEL SECURITY;

--
-- Name: mfa_factors; Type: ROW SECURITY; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE auth.mfa_factors ENABLE ROW LEVEL SECURITY;

--
-- Name: one_time_tokens; Type: ROW SECURITY; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE auth.one_time_tokens ENABLE ROW LEVEL SECURITY;

--
-- Name: refresh_tokens; Type: ROW SECURITY; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE auth.refresh_tokens ENABLE ROW LEVEL SECURITY;

--
-- Name: saml_providers; Type: ROW SECURITY; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE auth.saml_providers ENABLE ROW LEVEL SECURITY;

--
-- Name: saml_relay_states; Type: ROW SECURITY; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE auth.saml_relay_states ENABLE ROW LEVEL SECURITY;

--
-- Name: schema_migrations; Type: ROW SECURITY; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE auth.schema_migrations ENABLE ROW LEVEL SECURITY;

--
-- Name: sessions; Type: ROW SECURITY; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE auth.sessions ENABLE ROW LEVEL SECURITY;

--
-- Name: sso_domains; Type: ROW SECURITY; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE auth.sso_domains ENABLE ROW LEVEL SECURITY;

--
-- Name: sso_providers; Type: ROW SECURITY; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE auth.sso_providers ENABLE ROW LEVEL SECURITY;

--
-- Name: users; Type: ROW SECURITY; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE auth.users ENABLE ROW LEVEL SECURITY;

--
-- Name: principal_to_librarian_notices; Type: ROW SECURITY; Schema: public; Owner: postgres
--

ALTER TABLE public.principal_to_librarian_notices ENABLE ROW LEVEL SECURITY;

--
-- Name: messages; Type: ROW SECURITY; Schema: realtime; Owner: supabase_realtime_admin
--

ALTER TABLE realtime.messages ENABLE ROW LEVEL SECURITY;

--
-- Name: buckets; Type: ROW SECURITY; Schema: storage; Owner: supabase_storage_admin
--

ALTER TABLE storage.buckets ENABLE ROW LEVEL SECURITY;

--
-- Name: migrations; Type: ROW SECURITY; Schema: storage; Owner: supabase_storage_admin
--

ALTER TABLE storage.migrations ENABLE ROW LEVEL SECURITY;

--
-- Name: objects; Type: ROW SECURITY; Schema: storage; Owner: supabase_storage_admin
--

ALTER TABLE storage.objects ENABLE ROW LEVEL SECURITY;

--
-- Name: s3_multipart_uploads; Type: ROW SECURITY; Schema: storage; Owner: supabase_storage_admin
--

ALTER TABLE storage.s3_multipart_uploads ENABLE ROW LEVEL SECURITY;

--
-- Name: s3_multipart_uploads_parts; Type: ROW SECURITY; Schema: storage; Owner: supabase_storage_admin
--

ALTER TABLE storage.s3_multipart_uploads_parts ENABLE ROW LEVEL SECURITY;

--
-- Name: supabase_realtime; Type: PUBLICATION; Schema: -; Owner: postgres
--

CREATE PUBLICATION supabase_realtime WITH (publish = 'insert, update, delete, truncate');


ALTER PUBLICATION supabase_realtime OWNER TO postgres;

--
-- Name: SCHEMA auth; Type: ACL; Schema: -; Owner: supabase_admin
--

GRANT USAGE ON SCHEMA auth TO anon;
GRANT USAGE ON SCHEMA auth TO authenticated;
GRANT USAGE ON SCHEMA auth TO service_role;
GRANT ALL ON SCHEMA auth TO supabase_auth_admin;
GRANT ALL ON SCHEMA auth TO dashboard_user;
GRANT USAGE ON SCHEMA auth TO postgres;


--
-- Name: SCHEMA extensions; Type: ACL; Schema: -; Owner: postgres
--

GRANT USAGE ON SCHEMA extensions TO anon;
GRANT USAGE ON SCHEMA extensions TO authenticated;
GRANT USAGE ON SCHEMA extensions TO service_role;
GRANT ALL ON SCHEMA extensions TO dashboard_user;


--
-- Name: SCHEMA public; Type: ACL; Schema: -; Owner: pg_database_owner
--

GRANT USAGE ON SCHEMA public TO postgres;
GRANT USAGE ON SCHEMA public TO anon;
GRANT USAGE ON SCHEMA public TO authenticated;
GRANT USAGE ON SCHEMA public TO service_role;


--
-- Name: SCHEMA realtime; Type: ACL; Schema: -; Owner: supabase_admin
--

GRANT USAGE ON SCHEMA realtime TO postgres;
GRANT USAGE ON SCHEMA realtime TO anon;
GRANT USAGE ON SCHEMA realtime TO authenticated;
GRANT USAGE ON SCHEMA realtime TO service_role;
GRANT ALL ON SCHEMA realtime TO supabase_realtime_admin;


--
-- Name: SCHEMA storage; Type: ACL; Schema: -; Owner: supabase_admin
--

GRANT USAGE ON SCHEMA storage TO postgres WITH GRANT OPTION;
GRANT USAGE ON SCHEMA storage TO anon;
GRANT USAGE ON SCHEMA storage TO authenticated;
GRANT USAGE ON SCHEMA storage TO service_role;
GRANT ALL ON SCHEMA storage TO supabase_storage_admin;
GRANT ALL ON SCHEMA storage TO dashboard_user;


--
-- Name: SCHEMA vault; Type: ACL; Schema: -; Owner: supabase_admin
--

GRANT USAGE ON SCHEMA vault TO postgres WITH GRANT OPTION;
GRANT USAGE ON SCHEMA vault TO service_role;


--
-- Name: FUNCTION email(); Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT ALL ON FUNCTION auth.email() TO dashboard_user;


--
-- Name: FUNCTION jwt(); Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT ALL ON FUNCTION auth.jwt() TO postgres;
GRANT ALL ON FUNCTION auth.jwt() TO dashboard_user;


--
-- Name: FUNCTION role(); Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT ALL ON FUNCTION auth.role() TO dashboard_user;


--
-- Name: FUNCTION uid(); Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT ALL ON FUNCTION auth.uid() TO dashboard_user;


--
-- Name: FUNCTION armor(bytea); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.armor(bytea) FROM postgres;
GRANT ALL ON FUNCTION extensions.armor(bytea) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.armor(bytea) TO dashboard_user;


--
-- Name: FUNCTION armor(bytea, text[], text[]); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.armor(bytea, text[], text[]) FROM postgres;
GRANT ALL ON FUNCTION extensions.armor(bytea, text[], text[]) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.armor(bytea, text[], text[]) TO dashboard_user;


--
-- Name: FUNCTION crypt(text, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.crypt(text, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.crypt(text, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.crypt(text, text) TO dashboard_user;


--
-- Name: FUNCTION dearmor(text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.dearmor(text) FROM postgres;
GRANT ALL ON FUNCTION extensions.dearmor(text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.dearmor(text) TO dashboard_user;


--
-- Name: FUNCTION decrypt(bytea, bytea, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.decrypt(bytea, bytea, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.decrypt(bytea, bytea, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.decrypt(bytea, bytea, text) TO dashboard_user;


--
-- Name: FUNCTION decrypt_iv(bytea, bytea, bytea, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.decrypt_iv(bytea, bytea, bytea, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.decrypt_iv(bytea, bytea, bytea, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.decrypt_iv(bytea, bytea, bytea, text) TO dashboard_user;


--
-- Name: FUNCTION digest(bytea, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.digest(bytea, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.digest(bytea, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.digest(bytea, text) TO dashboard_user;


--
-- Name: FUNCTION digest(text, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.digest(text, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.digest(text, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.digest(text, text) TO dashboard_user;


--
-- Name: FUNCTION encrypt(bytea, bytea, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.encrypt(bytea, bytea, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.encrypt(bytea, bytea, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.encrypt(bytea, bytea, text) TO dashboard_user;


--
-- Name: FUNCTION encrypt_iv(bytea, bytea, bytea, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.encrypt_iv(bytea, bytea, bytea, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.encrypt_iv(bytea, bytea, bytea, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.encrypt_iv(bytea, bytea, bytea, text) TO dashboard_user;


--
-- Name: FUNCTION gen_random_bytes(integer); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.gen_random_bytes(integer) FROM postgres;
GRANT ALL ON FUNCTION extensions.gen_random_bytes(integer) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.gen_random_bytes(integer) TO dashboard_user;


--
-- Name: FUNCTION gen_random_uuid(); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.gen_random_uuid() FROM postgres;
GRANT ALL ON FUNCTION extensions.gen_random_uuid() TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.gen_random_uuid() TO dashboard_user;


--
-- Name: FUNCTION gen_salt(text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.gen_salt(text) FROM postgres;
GRANT ALL ON FUNCTION extensions.gen_salt(text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.gen_salt(text) TO dashboard_user;


--
-- Name: FUNCTION gen_salt(text, integer); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.gen_salt(text, integer) FROM postgres;
GRANT ALL ON FUNCTION extensions.gen_salt(text, integer) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.gen_salt(text, integer) TO dashboard_user;


--
-- Name: FUNCTION grant_pg_cron_access(); Type: ACL; Schema: extensions; Owner: supabase_admin
--

REVOKE ALL ON FUNCTION extensions.grant_pg_cron_access() FROM supabase_admin;
GRANT ALL ON FUNCTION extensions.grant_pg_cron_access() TO supabase_admin WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.grant_pg_cron_access() TO dashboard_user;


--
-- Name: FUNCTION grant_pg_graphql_access(); Type: ACL; Schema: extensions; Owner: supabase_admin
--

GRANT ALL ON FUNCTION extensions.grant_pg_graphql_access() TO postgres WITH GRANT OPTION;


--
-- Name: FUNCTION grant_pg_net_access(); Type: ACL; Schema: extensions; Owner: supabase_admin
--

REVOKE ALL ON FUNCTION extensions.grant_pg_net_access() FROM supabase_admin;
GRANT ALL ON FUNCTION extensions.grant_pg_net_access() TO supabase_admin WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.grant_pg_net_access() TO dashboard_user;


--
-- Name: FUNCTION hmac(bytea, bytea, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.hmac(bytea, bytea, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.hmac(bytea, bytea, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.hmac(bytea, bytea, text) TO dashboard_user;


--
-- Name: FUNCTION hmac(text, text, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.hmac(text, text, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.hmac(text, text, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.hmac(text, text, text) TO dashboard_user;


--
-- Name: FUNCTION pg_stat_statements(showtext boolean, OUT userid oid, OUT dbid oid, OUT toplevel boolean, OUT queryid bigint, OUT query text, OUT plans bigint, OUT total_plan_time double precision, OUT min_plan_time double precision, OUT max_plan_time double precision, OUT mean_plan_time double precision, OUT stddev_plan_time double precision, OUT calls bigint, OUT total_exec_time double precision, OUT min_exec_time double precision, OUT max_exec_time double precision, OUT mean_exec_time double precision, OUT stddev_exec_time double precision, OUT rows bigint, OUT shared_blks_hit bigint, OUT shared_blks_read bigint, OUT shared_blks_dirtied bigint, OUT shared_blks_written bigint, OUT local_blks_hit bigint, OUT local_blks_read bigint, OUT local_blks_dirtied bigint, OUT local_blks_written bigint, OUT temp_blks_read bigint, OUT temp_blks_written bigint, OUT shared_blk_read_time double precision, OUT shared_blk_write_time double precision, OUT local_blk_read_time double precision, OUT local_blk_write_time double precision, OUT temp_blk_read_time double precision, OUT temp_blk_write_time double precision, OUT wal_records bigint, OUT wal_fpi bigint, OUT wal_bytes numeric, OUT jit_functions bigint, OUT jit_generation_time double precision, OUT jit_inlining_count bigint, OUT jit_inlining_time double precision, OUT jit_optimization_count bigint, OUT jit_optimization_time double precision, OUT jit_emission_count bigint, OUT jit_emission_time double precision, OUT jit_deform_count bigint, OUT jit_deform_time double precision, OUT stats_since timestamp with time zone, OUT minmax_stats_since timestamp with time zone); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pg_stat_statements(showtext boolean, OUT userid oid, OUT dbid oid, OUT toplevel boolean, OUT queryid bigint, OUT query text, OUT plans bigint, OUT total_plan_time double precision, OUT min_plan_time double precision, OUT max_plan_time double precision, OUT mean_plan_time double precision, OUT stddev_plan_time double precision, OUT calls bigint, OUT total_exec_time double precision, OUT min_exec_time double precision, OUT max_exec_time double precision, OUT mean_exec_time double precision, OUT stddev_exec_time double precision, OUT rows bigint, OUT shared_blks_hit bigint, OUT shared_blks_read bigint, OUT shared_blks_dirtied bigint, OUT shared_blks_written bigint, OUT local_blks_hit bigint, OUT local_blks_read bigint, OUT local_blks_dirtied bigint, OUT local_blks_written bigint, OUT temp_blks_read bigint, OUT temp_blks_written bigint, OUT shared_blk_read_time double precision, OUT shared_blk_write_time double precision, OUT local_blk_read_time double precision, OUT local_blk_write_time double precision, OUT temp_blk_read_time double precision, OUT temp_blk_write_time double precision, OUT wal_records bigint, OUT wal_fpi bigint, OUT wal_bytes numeric, OUT jit_functions bigint, OUT jit_generation_time double precision, OUT jit_inlining_count bigint, OUT jit_inlining_time double precision, OUT jit_optimization_count bigint, OUT jit_optimization_time double precision, OUT jit_emission_count bigint, OUT jit_emission_time double precision, OUT jit_deform_count bigint, OUT jit_deform_time double precision, OUT stats_since timestamp with time zone, OUT minmax_stats_since timestamp with time zone) FROM postgres;
GRANT ALL ON FUNCTION extensions.pg_stat_statements(showtext boolean, OUT userid oid, OUT dbid oid, OUT toplevel boolean, OUT queryid bigint, OUT query text, OUT plans bigint, OUT total_plan_time double precision, OUT min_plan_time double precision, OUT max_plan_time double precision, OUT mean_plan_time double precision, OUT stddev_plan_time double precision, OUT calls bigint, OUT total_exec_time double precision, OUT min_exec_time double precision, OUT max_exec_time double precision, OUT mean_exec_time double precision, OUT stddev_exec_time double precision, OUT rows bigint, OUT shared_blks_hit bigint, OUT shared_blks_read bigint, OUT shared_blks_dirtied bigint, OUT shared_blks_written bigint, OUT local_blks_hit bigint, OUT local_blks_read bigint, OUT local_blks_dirtied bigint, OUT local_blks_written bigint, OUT temp_blks_read bigint, OUT temp_blks_written bigint, OUT shared_blk_read_time double precision, OUT shared_blk_write_time double precision, OUT local_blk_read_time double precision, OUT local_blk_write_time double precision, OUT temp_blk_read_time double precision, OUT temp_blk_write_time double precision, OUT wal_records bigint, OUT wal_fpi bigint, OUT wal_bytes numeric, OUT jit_functions bigint, OUT jit_generation_time double precision, OUT jit_inlining_count bigint, OUT jit_inlining_time double precision, OUT jit_optimization_count bigint, OUT jit_optimization_time double precision, OUT jit_emission_count bigint, OUT jit_emission_time double precision, OUT jit_deform_count bigint, OUT jit_deform_time double precision, OUT stats_since timestamp with time zone, OUT minmax_stats_since timestamp with time zone) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pg_stat_statements(showtext boolean, OUT userid oid, OUT dbid oid, OUT toplevel boolean, OUT queryid bigint, OUT query text, OUT plans bigint, OUT total_plan_time double precision, OUT min_plan_time double precision, OUT max_plan_time double precision, OUT mean_plan_time double precision, OUT stddev_plan_time double precision, OUT calls bigint, OUT total_exec_time double precision, OUT min_exec_time double precision, OUT max_exec_time double precision, OUT mean_exec_time double precision, OUT stddev_exec_time double precision, OUT rows bigint, OUT shared_blks_hit bigint, OUT shared_blks_read bigint, OUT shared_blks_dirtied bigint, OUT shared_blks_written bigint, OUT local_blks_hit bigint, OUT local_blks_read bigint, OUT local_blks_dirtied bigint, OUT local_blks_written bigint, OUT temp_blks_read bigint, OUT temp_blks_written bigint, OUT shared_blk_read_time double precision, OUT shared_blk_write_time double precision, OUT local_blk_read_time double precision, OUT local_blk_write_time double precision, OUT temp_blk_read_time double precision, OUT temp_blk_write_time double precision, OUT wal_records bigint, OUT wal_fpi bigint, OUT wal_bytes numeric, OUT jit_functions bigint, OUT jit_generation_time double precision, OUT jit_inlining_count bigint, OUT jit_inlining_time double precision, OUT jit_optimization_count bigint, OUT jit_optimization_time double precision, OUT jit_emission_count bigint, OUT jit_emission_time double precision, OUT jit_deform_count bigint, OUT jit_deform_time double precision, OUT stats_since timestamp with time zone, OUT minmax_stats_since timestamp with time zone) TO dashboard_user;


--
-- Name: FUNCTION pg_stat_statements_info(OUT dealloc bigint, OUT stats_reset timestamp with time zone); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pg_stat_statements_info(OUT dealloc bigint, OUT stats_reset timestamp with time zone) FROM postgres;
GRANT ALL ON FUNCTION extensions.pg_stat_statements_info(OUT dealloc bigint, OUT stats_reset timestamp with time zone) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pg_stat_statements_info(OUT dealloc bigint, OUT stats_reset timestamp with time zone) TO dashboard_user;


--
-- Name: FUNCTION pg_stat_statements_reset(userid oid, dbid oid, queryid bigint, minmax_only boolean); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pg_stat_statements_reset(userid oid, dbid oid, queryid bigint, minmax_only boolean) FROM postgres;
GRANT ALL ON FUNCTION extensions.pg_stat_statements_reset(userid oid, dbid oid, queryid bigint, minmax_only boolean) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pg_stat_statements_reset(userid oid, dbid oid, queryid bigint, minmax_only boolean) TO dashboard_user;


--
-- Name: FUNCTION pgp_armor_headers(text, OUT key text, OUT value text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pgp_armor_headers(text, OUT key text, OUT value text) FROM postgres;
GRANT ALL ON FUNCTION extensions.pgp_armor_headers(text, OUT key text, OUT value text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pgp_armor_headers(text, OUT key text, OUT value text) TO dashboard_user;


--
-- Name: FUNCTION pgp_key_id(bytea); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pgp_key_id(bytea) FROM postgres;
GRANT ALL ON FUNCTION extensions.pgp_key_id(bytea) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pgp_key_id(bytea) TO dashboard_user;


--
-- Name: FUNCTION pgp_pub_decrypt(bytea, bytea); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pgp_pub_decrypt(bytea, bytea) FROM postgres;
GRANT ALL ON FUNCTION extensions.pgp_pub_decrypt(bytea, bytea) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pgp_pub_decrypt(bytea, bytea) TO dashboard_user;


--
-- Name: FUNCTION pgp_pub_decrypt(bytea, bytea, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pgp_pub_decrypt(bytea, bytea, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.pgp_pub_decrypt(bytea, bytea, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pgp_pub_decrypt(bytea, bytea, text) TO dashboard_user;


--
-- Name: FUNCTION pgp_pub_decrypt(bytea, bytea, text, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pgp_pub_decrypt(bytea, bytea, text, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.pgp_pub_decrypt(bytea, bytea, text, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pgp_pub_decrypt(bytea, bytea, text, text) TO dashboard_user;


--
-- Name: FUNCTION pgp_pub_decrypt_bytea(bytea, bytea); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pgp_pub_decrypt_bytea(bytea, bytea) FROM postgres;
GRANT ALL ON FUNCTION extensions.pgp_pub_decrypt_bytea(bytea, bytea) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pgp_pub_decrypt_bytea(bytea, bytea) TO dashboard_user;


--
-- Name: FUNCTION pgp_pub_decrypt_bytea(bytea, bytea, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pgp_pub_decrypt_bytea(bytea, bytea, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.pgp_pub_decrypt_bytea(bytea, bytea, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pgp_pub_decrypt_bytea(bytea, bytea, text) TO dashboard_user;


--
-- Name: FUNCTION pgp_pub_decrypt_bytea(bytea, bytea, text, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pgp_pub_decrypt_bytea(bytea, bytea, text, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.pgp_pub_decrypt_bytea(bytea, bytea, text, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pgp_pub_decrypt_bytea(bytea, bytea, text, text) TO dashboard_user;


--
-- Name: FUNCTION pgp_pub_encrypt(text, bytea); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pgp_pub_encrypt(text, bytea) FROM postgres;
GRANT ALL ON FUNCTION extensions.pgp_pub_encrypt(text, bytea) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pgp_pub_encrypt(text, bytea) TO dashboard_user;


--
-- Name: FUNCTION pgp_pub_encrypt(text, bytea, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pgp_pub_encrypt(text, bytea, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.pgp_pub_encrypt(text, bytea, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pgp_pub_encrypt(text, bytea, text) TO dashboard_user;


--
-- Name: FUNCTION pgp_pub_encrypt_bytea(bytea, bytea); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pgp_pub_encrypt_bytea(bytea, bytea) FROM postgres;
GRANT ALL ON FUNCTION extensions.pgp_pub_encrypt_bytea(bytea, bytea) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pgp_pub_encrypt_bytea(bytea, bytea) TO dashboard_user;


--
-- Name: FUNCTION pgp_pub_encrypt_bytea(bytea, bytea, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pgp_pub_encrypt_bytea(bytea, bytea, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.pgp_pub_encrypt_bytea(bytea, bytea, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pgp_pub_encrypt_bytea(bytea, bytea, text) TO dashboard_user;


--
-- Name: FUNCTION pgp_sym_decrypt(bytea, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pgp_sym_decrypt(bytea, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.pgp_sym_decrypt(bytea, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pgp_sym_decrypt(bytea, text) TO dashboard_user;


--
-- Name: FUNCTION pgp_sym_decrypt(bytea, text, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pgp_sym_decrypt(bytea, text, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.pgp_sym_decrypt(bytea, text, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pgp_sym_decrypt(bytea, text, text) TO dashboard_user;


--
-- Name: FUNCTION pgp_sym_decrypt_bytea(bytea, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pgp_sym_decrypt_bytea(bytea, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.pgp_sym_decrypt_bytea(bytea, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pgp_sym_decrypt_bytea(bytea, text) TO dashboard_user;


--
-- Name: FUNCTION pgp_sym_decrypt_bytea(bytea, text, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pgp_sym_decrypt_bytea(bytea, text, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.pgp_sym_decrypt_bytea(bytea, text, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pgp_sym_decrypt_bytea(bytea, text, text) TO dashboard_user;


--
-- Name: FUNCTION pgp_sym_encrypt(text, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pgp_sym_encrypt(text, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.pgp_sym_encrypt(text, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pgp_sym_encrypt(text, text) TO dashboard_user;


--
-- Name: FUNCTION pgp_sym_encrypt(text, text, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pgp_sym_encrypt(text, text, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.pgp_sym_encrypt(text, text, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pgp_sym_encrypt(text, text, text) TO dashboard_user;


--
-- Name: FUNCTION pgp_sym_encrypt_bytea(bytea, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pgp_sym_encrypt_bytea(bytea, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.pgp_sym_encrypt_bytea(bytea, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pgp_sym_encrypt_bytea(bytea, text) TO dashboard_user;


--
-- Name: FUNCTION pgp_sym_encrypt_bytea(bytea, text, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pgp_sym_encrypt_bytea(bytea, text, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.pgp_sym_encrypt_bytea(bytea, text, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pgp_sym_encrypt_bytea(bytea, text, text) TO dashboard_user;


--
-- Name: FUNCTION pgrst_ddl_watch(); Type: ACL; Schema: extensions; Owner: supabase_admin
--

GRANT ALL ON FUNCTION extensions.pgrst_ddl_watch() TO postgres WITH GRANT OPTION;


--
-- Name: FUNCTION pgrst_drop_watch(); Type: ACL; Schema: extensions; Owner: supabase_admin
--

GRANT ALL ON FUNCTION extensions.pgrst_drop_watch() TO postgres WITH GRANT OPTION;


--
-- Name: FUNCTION set_graphql_placeholder(); Type: ACL; Schema: extensions; Owner: supabase_admin
--

GRANT ALL ON FUNCTION extensions.set_graphql_placeholder() TO postgres WITH GRANT OPTION;


--
-- Name: FUNCTION uuid_generate_v1(); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.uuid_generate_v1() FROM postgres;
GRANT ALL ON FUNCTION extensions.uuid_generate_v1() TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.uuid_generate_v1() TO dashboard_user;


--
-- Name: FUNCTION uuid_generate_v1mc(); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.uuid_generate_v1mc() FROM postgres;
GRANT ALL ON FUNCTION extensions.uuid_generate_v1mc() TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.uuid_generate_v1mc() TO dashboard_user;


--
-- Name: FUNCTION uuid_generate_v3(namespace uuid, name text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.uuid_generate_v3(namespace uuid, name text) FROM postgres;
GRANT ALL ON FUNCTION extensions.uuid_generate_v3(namespace uuid, name text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.uuid_generate_v3(namespace uuid, name text) TO dashboard_user;


--
-- Name: FUNCTION uuid_generate_v4(); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.uuid_generate_v4() FROM postgres;
GRANT ALL ON FUNCTION extensions.uuid_generate_v4() TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.uuid_generate_v4() TO dashboard_user;


--
-- Name: FUNCTION uuid_generate_v5(namespace uuid, name text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.uuid_generate_v5(namespace uuid, name text) FROM postgres;
GRANT ALL ON FUNCTION extensions.uuid_generate_v5(namespace uuid, name text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.uuid_generate_v5(namespace uuid, name text) TO dashboard_user;


--
-- Name: FUNCTION uuid_nil(); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.uuid_nil() FROM postgres;
GRANT ALL ON FUNCTION extensions.uuid_nil() TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.uuid_nil() TO dashboard_user;


--
-- Name: FUNCTION uuid_ns_dns(); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.uuid_ns_dns() FROM postgres;
GRANT ALL ON FUNCTION extensions.uuid_ns_dns() TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.uuid_ns_dns() TO dashboard_user;


--
-- Name: FUNCTION uuid_ns_oid(); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.uuid_ns_oid() FROM postgres;
GRANT ALL ON FUNCTION extensions.uuid_ns_oid() TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.uuid_ns_oid() TO dashboard_user;


--
-- Name: FUNCTION uuid_ns_url(); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.uuid_ns_url() FROM postgres;
GRANT ALL ON FUNCTION extensions.uuid_ns_url() TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.uuid_ns_url() TO dashboard_user;


--
-- Name: FUNCTION uuid_ns_x500(); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.uuid_ns_x500() FROM postgres;
GRANT ALL ON FUNCTION extensions.uuid_ns_x500() TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.uuid_ns_x500() TO dashboard_user;


--
-- Name: FUNCTION graphql("operationName" text, query text, variables jsonb, extensions jsonb); Type: ACL; Schema: graphql_public; Owner: supabase_admin
--

GRANT ALL ON FUNCTION graphql_public.graphql("operationName" text, query text, variables jsonb, extensions jsonb) TO postgres;
GRANT ALL ON FUNCTION graphql_public.graphql("operationName" text, query text, variables jsonb, extensions jsonb) TO anon;
GRANT ALL ON FUNCTION graphql_public.graphql("operationName" text, query text, variables jsonb, extensions jsonb) TO authenticated;
GRANT ALL ON FUNCTION graphql_public.graphql("operationName" text, query text, variables jsonb, extensions jsonb) TO service_role;


--
-- Name: FUNCTION get_auth(p_usename text); Type: ACL; Schema: pgbouncer; Owner: supabase_admin
--

REVOKE ALL ON FUNCTION pgbouncer.get_auth(p_usename text) FROM PUBLIC;
GRANT ALL ON FUNCTION pgbouncer.get_auth(p_usename text) TO pgbouncer;
GRANT ALL ON FUNCTION pgbouncer.get_auth(p_usename text) TO postgres;


--
-- Name: FUNCTION apply_rls(wal jsonb, max_record_bytes integer); Type: ACL; Schema: realtime; Owner: supabase_admin
--

GRANT ALL ON FUNCTION realtime.apply_rls(wal jsonb, max_record_bytes integer) TO postgres;
GRANT ALL ON FUNCTION realtime.apply_rls(wal jsonb, max_record_bytes integer) TO dashboard_user;
GRANT ALL ON FUNCTION realtime.apply_rls(wal jsonb, max_record_bytes integer) TO anon;
GRANT ALL ON FUNCTION realtime.apply_rls(wal jsonb, max_record_bytes integer) TO authenticated;
GRANT ALL ON FUNCTION realtime.apply_rls(wal jsonb, max_record_bytes integer) TO service_role;
GRANT ALL ON FUNCTION realtime.apply_rls(wal jsonb, max_record_bytes integer) TO supabase_realtime_admin;


--
-- Name: FUNCTION broadcast_changes(topic_name text, event_name text, operation text, table_name text, table_schema text, new record, old record, level text); Type: ACL; Schema: realtime; Owner: supabase_admin
--

GRANT ALL ON FUNCTION realtime.broadcast_changes(topic_name text, event_name text, operation text, table_name text, table_schema text, new record, old record, level text) TO postgres;
GRANT ALL ON FUNCTION realtime.broadcast_changes(topic_name text, event_name text, operation text, table_name text, table_schema text, new record, old record, level text) TO dashboard_user;


--
-- Name: FUNCTION build_prepared_statement_sql(prepared_statement_name text, entity regclass, columns realtime.wal_column[]); Type: ACL; Schema: realtime; Owner: supabase_admin
--

GRANT ALL ON FUNCTION realtime.build_prepared_statement_sql(prepared_statement_name text, entity regclass, columns realtime.wal_column[]) TO postgres;
GRANT ALL ON FUNCTION realtime.build_prepared_statement_sql(prepared_statement_name text, entity regclass, columns realtime.wal_column[]) TO dashboard_user;
GRANT ALL ON FUNCTION realtime.build_prepared_statement_sql(prepared_statement_name text, entity regclass, columns realtime.wal_column[]) TO anon;
GRANT ALL ON FUNCTION realtime.build_prepared_statement_sql(prepared_statement_name text, entity regclass, columns realtime.wal_column[]) TO authenticated;
GRANT ALL ON FUNCTION realtime.build_prepared_statement_sql(prepared_statement_name text, entity regclass, columns realtime.wal_column[]) TO service_role;
GRANT ALL ON FUNCTION realtime.build_prepared_statement_sql(prepared_statement_name text, entity regclass, columns realtime.wal_column[]) TO supabase_realtime_admin;


--
-- Name: FUNCTION "cast"(val text, type_ regtype); Type: ACL; Schema: realtime; Owner: supabase_admin
--

GRANT ALL ON FUNCTION realtime."cast"(val text, type_ regtype) TO postgres;
GRANT ALL ON FUNCTION realtime."cast"(val text, type_ regtype) TO dashboard_user;
GRANT ALL ON FUNCTION realtime."cast"(val text, type_ regtype) TO anon;
GRANT ALL ON FUNCTION realtime."cast"(val text, type_ regtype) TO authenticated;
GRANT ALL ON FUNCTION realtime."cast"(val text, type_ regtype) TO service_role;
GRANT ALL ON FUNCTION realtime."cast"(val text, type_ regtype) TO supabase_realtime_admin;


--
-- Name: FUNCTION check_equality_op(op realtime.equality_op, type_ regtype, val_1 text, val_2 text); Type: ACL; Schema: realtime; Owner: supabase_admin
--

GRANT ALL ON FUNCTION realtime.check_equality_op(op realtime.equality_op, type_ regtype, val_1 text, val_2 text) TO postgres;
GRANT ALL ON FUNCTION realtime.check_equality_op(op realtime.equality_op, type_ regtype, val_1 text, val_2 text) TO dashboard_user;
GRANT ALL ON FUNCTION realtime.check_equality_op(op realtime.equality_op, type_ regtype, val_1 text, val_2 text) TO anon;
GRANT ALL ON FUNCTION realtime.check_equality_op(op realtime.equality_op, type_ regtype, val_1 text, val_2 text) TO authenticated;
GRANT ALL ON FUNCTION realtime.check_equality_op(op realtime.equality_op, type_ regtype, val_1 text, val_2 text) TO service_role;
GRANT ALL ON FUNCTION realtime.check_equality_op(op realtime.equality_op, type_ regtype, val_1 text, val_2 text) TO supabase_realtime_admin;


--
-- Name: FUNCTION is_visible_through_filters(columns realtime.wal_column[], filters realtime.user_defined_filter[]); Type: ACL; Schema: realtime; Owner: supabase_admin
--

GRANT ALL ON FUNCTION realtime.is_visible_through_filters(columns realtime.wal_column[], filters realtime.user_defined_filter[]) TO postgres;
GRANT ALL ON FUNCTION realtime.is_visible_through_filters(columns realtime.wal_column[], filters realtime.user_defined_filter[]) TO dashboard_user;
GRANT ALL ON FUNCTION realtime.is_visible_through_filters(columns realtime.wal_column[], filters realtime.user_defined_filter[]) TO anon;
GRANT ALL ON FUNCTION realtime.is_visible_through_filters(columns realtime.wal_column[], filters realtime.user_defined_filter[]) TO authenticated;
GRANT ALL ON FUNCTION realtime.is_visible_through_filters(columns realtime.wal_column[], filters realtime.user_defined_filter[]) TO service_role;
GRANT ALL ON FUNCTION realtime.is_visible_through_filters(columns realtime.wal_column[], filters realtime.user_defined_filter[]) TO supabase_realtime_admin;


--
-- Name: FUNCTION list_changes(publication name, slot_name name, max_changes integer, max_record_bytes integer); Type: ACL; Schema: realtime; Owner: supabase_admin
--

GRANT ALL ON FUNCTION realtime.list_changes(publication name, slot_name name, max_changes integer, max_record_bytes integer) TO postgres;
GRANT ALL ON FUNCTION realtime.list_changes(publication name, slot_name name, max_changes integer, max_record_bytes integer) TO dashboard_user;
GRANT ALL ON FUNCTION realtime.list_changes(publication name, slot_name name, max_changes integer, max_record_bytes integer) TO anon;
GRANT ALL ON FUNCTION realtime.list_changes(publication name, slot_name name, max_changes integer, max_record_bytes integer) TO authenticated;
GRANT ALL ON FUNCTION realtime.list_changes(publication name, slot_name name, max_changes integer, max_record_bytes integer) TO service_role;
GRANT ALL ON FUNCTION realtime.list_changes(publication name, slot_name name, max_changes integer, max_record_bytes integer) TO supabase_realtime_admin;


--
-- Name: FUNCTION quote_wal2json(entity regclass); Type: ACL; Schema: realtime; Owner: supabase_admin
--

GRANT ALL ON FUNCTION realtime.quote_wal2json(entity regclass) TO postgres;
GRANT ALL ON FUNCTION realtime.quote_wal2json(entity regclass) TO dashboard_user;
GRANT ALL ON FUNCTION realtime.quote_wal2json(entity regclass) TO anon;
GRANT ALL ON FUNCTION realtime.quote_wal2json(entity regclass) TO authenticated;
GRANT ALL ON FUNCTION realtime.quote_wal2json(entity regclass) TO service_role;
GRANT ALL ON FUNCTION realtime.quote_wal2json(entity regclass) TO supabase_realtime_admin;


--
-- Name: FUNCTION send(payload jsonb, event text, topic text, private boolean); Type: ACL; Schema: realtime; Owner: supabase_admin
--

GRANT ALL ON FUNCTION realtime.send(payload jsonb, event text, topic text, private boolean) TO postgres;
GRANT ALL ON FUNCTION realtime.send(payload jsonb, event text, topic text, private boolean) TO dashboard_user;


--
-- Name: FUNCTION subscription_check_filters(); Type: ACL; Schema: realtime; Owner: supabase_admin
--

GRANT ALL ON FUNCTION realtime.subscription_check_filters() TO postgres;
GRANT ALL ON FUNCTION realtime.subscription_check_filters() TO dashboard_user;
GRANT ALL ON FUNCTION realtime.subscription_check_filters() TO anon;
GRANT ALL ON FUNCTION realtime.subscription_check_filters() TO authenticated;
GRANT ALL ON FUNCTION realtime.subscription_check_filters() TO service_role;
GRANT ALL ON FUNCTION realtime.subscription_check_filters() TO supabase_realtime_admin;


--
-- Name: FUNCTION to_regrole(role_name text); Type: ACL; Schema: realtime; Owner: supabase_admin
--

GRANT ALL ON FUNCTION realtime.to_regrole(role_name text) TO postgres;
GRANT ALL ON FUNCTION realtime.to_regrole(role_name text) TO dashboard_user;
GRANT ALL ON FUNCTION realtime.to_regrole(role_name text) TO anon;
GRANT ALL ON FUNCTION realtime.to_regrole(role_name text) TO authenticated;
GRANT ALL ON FUNCTION realtime.to_regrole(role_name text) TO service_role;
GRANT ALL ON FUNCTION realtime.to_regrole(role_name text) TO supabase_realtime_admin;


--
-- Name: FUNCTION topic(); Type: ACL; Schema: realtime; Owner: supabase_realtime_admin
--

GRANT ALL ON FUNCTION realtime.topic() TO postgres;
GRANT ALL ON FUNCTION realtime.topic() TO dashboard_user;


--
-- Name: FUNCTION _crypto_aead_det_decrypt(message bytea, additional bytea, key_id bigint, context bytea, nonce bytea); Type: ACL; Schema: vault; Owner: supabase_admin
--

GRANT ALL ON FUNCTION vault._crypto_aead_det_decrypt(message bytea, additional bytea, key_id bigint, context bytea, nonce bytea) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION vault._crypto_aead_det_decrypt(message bytea, additional bytea, key_id bigint, context bytea, nonce bytea) TO service_role;


--
-- Name: FUNCTION create_secret(new_secret text, new_name text, new_description text, new_key_id uuid); Type: ACL; Schema: vault; Owner: supabase_admin
--

GRANT ALL ON FUNCTION vault.create_secret(new_secret text, new_name text, new_description text, new_key_id uuid) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION vault.create_secret(new_secret text, new_name text, new_description text, new_key_id uuid) TO service_role;


--
-- Name: FUNCTION update_secret(secret_id uuid, new_secret text, new_name text, new_description text, new_key_id uuid); Type: ACL; Schema: vault; Owner: supabase_admin
--

GRANT ALL ON FUNCTION vault.update_secret(secret_id uuid, new_secret text, new_name text, new_description text, new_key_id uuid) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION vault.update_secret(secret_id uuid, new_secret text, new_name text, new_description text, new_key_id uuid) TO service_role;


--
-- Name: TABLE audit_log_entries; Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT ALL ON TABLE auth.audit_log_entries TO dashboard_user;
GRANT INSERT,REFERENCES,DELETE,TRIGGER,TRUNCATE,MAINTAIN,UPDATE ON TABLE auth.audit_log_entries TO postgres;
GRANT SELECT ON TABLE auth.audit_log_entries TO postgres WITH GRANT OPTION;


--
-- Name: TABLE flow_state; Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT INSERT,REFERENCES,DELETE,TRIGGER,TRUNCATE,MAINTAIN,UPDATE ON TABLE auth.flow_state TO postgres;
GRANT SELECT ON TABLE auth.flow_state TO postgres WITH GRANT OPTION;
GRANT ALL ON TABLE auth.flow_state TO dashboard_user;


--
-- Name: TABLE identities; Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT INSERT,REFERENCES,DELETE,TRIGGER,TRUNCATE,MAINTAIN,UPDATE ON TABLE auth.identities TO postgres;
GRANT SELECT ON TABLE auth.identities TO postgres WITH GRANT OPTION;
GRANT ALL ON TABLE auth.identities TO dashboard_user;


--
-- Name: TABLE instances; Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT ALL ON TABLE auth.instances TO dashboard_user;
GRANT INSERT,REFERENCES,DELETE,TRIGGER,TRUNCATE,MAINTAIN,UPDATE ON TABLE auth.instances TO postgres;
GRANT SELECT ON TABLE auth.instances TO postgres WITH GRANT OPTION;


--
-- Name: TABLE mfa_amr_claims; Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT INSERT,REFERENCES,DELETE,TRIGGER,TRUNCATE,MAINTAIN,UPDATE ON TABLE auth.mfa_amr_claims TO postgres;
GRANT SELECT ON TABLE auth.mfa_amr_claims TO postgres WITH GRANT OPTION;
GRANT ALL ON TABLE auth.mfa_amr_claims TO dashboard_user;


--
-- Name: TABLE mfa_challenges; Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT INSERT,REFERENCES,DELETE,TRIGGER,TRUNCATE,MAINTAIN,UPDATE ON TABLE auth.mfa_challenges TO postgres;
GRANT SELECT ON TABLE auth.mfa_challenges TO postgres WITH GRANT OPTION;
GRANT ALL ON TABLE auth.mfa_challenges TO dashboard_user;


--
-- Name: TABLE mfa_factors; Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT INSERT,REFERENCES,DELETE,TRIGGER,TRUNCATE,MAINTAIN,UPDATE ON TABLE auth.mfa_factors TO postgres;
GRANT SELECT ON TABLE auth.mfa_factors TO postgres WITH GRANT OPTION;
GRANT ALL ON TABLE auth.mfa_factors TO dashboard_user;


--
-- Name: TABLE one_time_tokens; Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT INSERT,REFERENCES,DELETE,TRIGGER,TRUNCATE,MAINTAIN,UPDATE ON TABLE auth.one_time_tokens TO postgres;
GRANT SELECT ON TABLE auth.one_time_tokens TO postgres WITH GRANT OPTION;
GRANT ALL ON TABLE auth.one_time_tokens TO dashboard_user;


--
-- Name: TABLE refresh_tokens; Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT ALL ON TABLE auth.refresh_tokens TO dashboard_user;
GRANT INSERT,REFERENCES,DELETE,TRIGGER,TRUNCATE,MAINTAIN,UPDATE ON TABLE auth.refresh_tokens TO postgres;
GRANT SELECT ON TABLE auth.refresh_tokens TO postgres WITH GRANT OPTION;


--
-- Name: SEQUENCE refresh_tokens_id_seq; Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT ALL ON SEQUENCE auth.refresh_tokens_id_seq TO dashboard_user;
GRANT ALL ON SEQUENCE auth.refresh_tokens_id_seq TO postgres;


--
-- Name: TABLE saml_providers; Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT INSERT,REFERENCES,DELETE,TRIGGER,TRUNCATE,MAINTAIN,UPDATE ON TABLE auth.saml_providers TO postgres;
GRANT SELECT ON TABLE auth.saml_providers TO postgres WITH GRANT OPTION;
GRANT ALL ON TABLE auth.saml_providers TO dashboard_user;


--
-- Name: TABLE saml_relay_states; Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT INSERT,REFERENCES,DELETE,TRIGGER,TRUNCATE,MAINTAIN,UPDATE ON TABLE auth.saml_relay_states TO postgres;
GRANT SELECT ON TABLE auth.saml_relay_states TO postgres WITH GRANT OPTION;
GRANT ALL ON TABLE auth.saml_relay_states TO dashboard_user;


--
-- Name: TABLE schema_migrations; Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT SELECT ON TABLE auth.schema_migrations TO postgres WITH GRANT OPTION;


--
-- Name: TABLE sessions; Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT INSERT,REFERENCES,DELETE,TRIGGER,TRUNCATE,MAINTAIN,UPDATE ON TABLE auth.sessions TO postgres;
GRANT SELECT ON TABLE auth.sessions TO postgres WITH GRANT OPTION;
GRANT ALL ON TABLE auth.sessions TO dashboard_user;


--
-- Name: TABLE sso_domains; Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT INSERT,REFERENCES,DELETE,TRIGGER,TRUNCATE,MAINTAIN,UPDATE ON TABLE auth.sso_domains TO postgres;
GRANT SELECT ON TABLE auth.sso_domains TO postgres WITH GRANT OPTION;
GRANT ALL ON TABLE auth.sso_domains TO dashboard_user;


--
-- Name: TABLE sso_providers; Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT INSERT,REFERENCES,DELETE,TRIGGER,TRUNCATE,MAINTAIN,UPDATE ON TABLE auth.sso_providers TO postgres;
GRANT SELECT ON TABLE auth.sso_providers TO postgres WITH GRANT OPTION;
GRANT ALL ON TABLE auth.sso_providers TO dashboard_user;


--
-- Name: TABLE users; Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT ALL ON TABLE auth.users TO dashboard_user;
GRANT INSERT,REFERENCES,DELETE,TRIGGER,TRUNCATE,MAINTAIN,UPDATE ON TABLE auth.users TO postgres;
GRANT SELECT ON TABLE auth.users TO postgres WITH GRANT OPTION;


--
-- Name: TABLE pg_stat_statements; Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON TABLE extensions.pg_stat_statements FROM postgres;
GRANT ALL ON TABLE extensions.pg_stat_statements TO postgres WITH GRANT OPTION;
GRANT ALL ON TABLE extensions.pg_stat_statements TO dashboard_user;


--
-- Name: TABLE pg_stat_statements_info; Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON TABLE extensions.pg_stat_statements_info FROM postgres;
GRANT ALL ON TABLE extensions.pg_stat_statements_info TO postgres WITH GRANT OPTION;
GRANT ALL ON TABLE extensions.pg_stat_statements_info TO dashboard_user;


--
-- Name: TABLE assignment_submissions; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.assignment_submissions TO anon;
GRANT ALL ON TABLE public.assignment_submissions TO authenticated;
GRANT ALL ON TABLE public.assignment_submissions TO service_role;


--
-- Name: SEQUENCE assignment_submissions_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.assignment_submissions_id_seq TO anon;
GRANT ALL ON SEQUENCE public.assignment_submissions_id_seq TO authenticated;
GRANT ALL ON SEQUENCE public.assignment_submissions_id_seq TO service_role;


--
-- Name: TABLE assignments; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.assignments TO anon;
GRANT ALL ON TABLE public.assignments TO authenticated;
GRANT ALL ON TABLE public.assignments TO service_role;


--
-- Name: SEQUENCE assignments_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.assignments_id_seq TO anon;
GRANT ALL ON SEQUENCE public.assignments_id_seq TO authenticated;
GRANT ALL ON SEQUENCE public.assignments_id_seq TO service_role;


--
-- Name: TABLE attendance; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.attendance TO anon;
GRANT ALL ON TABLE public.attendance TO authenticated;
GRANT ALL ON TABLE public.attendance TO service_role;


--
-- Name: SEQUENCE attendance_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.attendance_id_seq TO anon;
GRANT ALL ON SEQUENCE public.attendance_id_seq TO authenticated;
GRANT ALL ON SEQUENCE public.attendance_id_seq TO service_role;


--
-- Name: TABLE book_requests; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.book_requests TO anon;
GRANT ALL ON TABLE public.book_requests TO authenticated;
GRANT ALL ON TABLE public.book_requests TO service_role;


--
-- Name: SEQUENCE book_requests_request_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.book_requests_request_id_seq TO anon;
GRANT ALL ON SEQUENCE public.book_requests_request_id_seq TO authenticated;
GRANT ALL ON SEQUENCE public.book_requests_request_id_seq TO service_role;


--
-- Name: TABLE books; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.books TO anon;
GRANT ALL ON TABLE public.books TO authenticated;
GRANT ALL ON TABLE public.books TO service_role;


--
-- Name: SEQUENCE books_book_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.books_book_id_seq TO anon;
GRANT ALL ON SEQUENCE public.books_book_id_seq TO authenticated;
GRANT ALL ON SEQUENCE public.books_book_id_seq TO service_role;


--
-- Name: TABLE borrow_requests; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.borrow_requests TO anon;
GRANT ALL ON TABLE public.borrow_requests TO authenticated;
GRANT ALL ON TABLE public.borrow_requests TO service_role;


--
-- Name: SEQUENCE borrow_requests_request_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.borrow_requests_request_id_seq TO anon;
GRANT ALL ON SEQUENCE public.borrow_requests_request_id_seq TO authenticated;
GRANT ALL ON SEQUENCE public.borrow_requests_request_id_seq TO service_role;


--
-- Name: TABLE borrowing_records; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.borrowing_records TO anon;
GRANT ALL ON TABLE public.borrowing_records TO authenticated;
GRANT ALL ON TABLE public.borrowing_records TO service_role;


--
-- Name: SEQUENCE borrowing_records_record_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.borrowing_records_record_id_seq TO anon;
GRANT ALL ON SEQUENCE public.borrowing_records_record_id_seq TO authenticated;
GRANT ALL ON SEQUENCE public.borrowing_records_record_id_seq TO service_role;


--
-- Name: TABLE deleted_books; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.deleted_books TO anon;
GRANT ALL ON TABLE public.deleted_books TO authenticated;
GRANT ALL ON TABLE public.deleted_books TO service_role;


--
-- Name: SEQUENCE deleted_books_archived_book_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.deleted_books_archived_book_id_seq TO anon;
GRANT ALL ON SEQUENCE public.deleted_books_archived_book_id_seq TO authenticated;
GRANT ALL ON SEQUENCE public.deleted_books_archived_book_id_seq TO service_role;


--
-- Name: TABLE deleted_librarians; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.deleted_librarians TO anon;
GRANT ALL ON TABLE public.deleted_librarians TO authenticated;
GRANT ALL ON TABLE public.deleted_librarians TO service_role;


--
-- Name: SEQUENCE deleted_librarians_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.deleted_librarians_id_seq TO anon;
GRANT ALL ON SEQUENCE public.deleted_librarians_id_seq TO authenticated;
GRANT ALL ON SEQUENCE public.deleted_librarians_id_seq TO service_role;


--
-- Name: TABLE deleted_principals; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.deleted_principals TO anon;
GRANT ALL ON TABLE public.deleted_principals TO authenticated;
GRANT ALL ON TABLE public.deleted_principals TO service_role;


--
-- Name: SEQUENCE deleted_principals_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.deleted_principals_id_seq TO anon;
GRANT ALL ON SEQUENCE public.deleted_principals_id_seq TO authenticated;
GRANT ALL ON SEQUENCE public.deleted_principals_id_seq TO service_role;


--
-- Name: TABLE deleted_schools; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.deleted_schools TO anon;
GRANT ALL ON TABLE public.deleted_schools TO authenticated;
GRANT ALL ON TABLE public.deleted_schools TO service_role;


--
-- Name: SEQUENCE deleted_schools_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.deleted_schools_id_seq TO anon;
GRANT ALL ON SEQUENCE public.deleted_schools_id_seq TO authenticated;
GRANT ALL ON SEQUENCE public.deleted_schools_id_seq TO service_role;


--
-- Name: TABLE deleted_students; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.deleted_students TO anon;
GRANT ALL ON TABLE public.deleted_students TO authenticated;
GRANT ALL ON TABLE public.deleted_students TO service_role;


--
-- Name: SEQUENCE deleted_students_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.deleted_students_id_seq TO anon;
GRANT ALL ON SEQUENCE public.deleted_students_id_seq TO authenticated;
GRANT ALL ON SEQUENCE public.deleted_students_id_seq TO service_role;


--
-- Name: TABLE deleted_teachers; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.deleted_teachers TO anon;
GRANT ALL ON TABLE public.deleted_teachers TO authenticated;
GRANT ALL ON TABLE public.deleted_teachers TO service_role;


--
-- Name: SEQUENCE deleted_teachers_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.deleted_teachers_id_seq TO anon;
GRANT ALL ON SEQUENCE public.deleted_teachers_id_seq TO authenticated;
GRANT ALL ON SEQUENCE public.deleted_teachers_id_seq TO service_role;


--
-- Name: TABLE exam_timetables; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.exam_timetables TO anon;
GRANT ALL ON TABLE public.exam_timetables TO authenticated;
GRANT ALL ON TABLE public.exam_timetables TO service_role;


--
-- Name: SEQUENCE exam_timetables_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.exam_timetables_id_seq TO anon;
GRANT ALL ON SEQUENCE public.exam_timetables_id_seq TO authenticated;
GRANT ALL ON SEQUENCE public.exam_timetables_id_seq TO service_role;


--
-- Name: TABLE leave_applications; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.leave_applications TO anon;
GRANT ALL ON TABLE public.leave_applications TO authenticated;
GRANT ALL ON TABLE public.leave_applications TO service_role;


--
-- Name: SEQUENCE leave_applications_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.leave_applications_id_seq TO anon;
GRANT ALL ON SEQUENCE public.leave_applications_id_seq TO authenticated;
GRANT ALL ON SEQUENCE public.leave_applications_id_seq TO service_role;


--
-- Name: TABLE librarian; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.librarian TO anon;
GRANT ALL ON TABLE public.librarian TO authenticated;
GRANT ALL ON TABLE public.librarian TO service_role;


--
-- Name: TABLE librarian_attendance; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.librarian_attendance TO anon;
GRANT ALL ON TABLE public.librarian_attendance TO authenticated;
GRANT ALL ON TABLE public.librarian_attendance TO service_role;


--
-- Name: SEQUENCE librarian_attendance_attendance_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.librarian_attendance_attendance_id_seq TO anon;
GRANT ALL ON SEQUENCE public.librarian_attendance_attendance_id_seq TO authenticated;
GRANT ALL ON SEQUENCE public.librarian_attendance_attendance_id_seq TO service_role;


--
-- Name: TABLE librarian_timings; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.librarian_timings TO anon;
GRANT ALL ON TABLE public.librarian_timings TO authenticated;
GRANT ALL ON TABLE public.librarian_timings TO service_role;


--
-- Name: SEQUENCE librarian_timings_timing_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.librarian_timings_timing_id_seq TO anon;
GRANT ALL ON SEQUENCE public.librarian_timings_timing_id_seq TO authenticated;
GRANT ALL ON SEQUENCE public.librarian_timings_timing_id_seq TO service_role;


--
-- Name: TABLE messages; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.messages TO anon;
GRANT ALL ON TABLE public.messages TO authenticated;
GRANT ALL ON TABLE public.messages TO service_role;


--
-- Name: SEQUENCE messages_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.messages_id_seq TO anon;
GRANT ALL ON SEQUENCE public.messages_id_seq TO authenticated;
GRANT ALL ON SEQUENCE public.messages_id_seq TO service_role;


--
-- Name: TABLE notes; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.notes TO anon;
GRANT ALL ON TABLE public.notes TO authenticated;
GRANT ALL ON TABLE public.notes TO service_role;


--
-- Name: SEQUENCE notes_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.notes_id_seq TO anon;
GRANT ALL ON SEQUENCE public.notes_id_seq TO authenticated;
GRANT ALL ON SEQUENCE public.notes_id_seq TO service_role;


--
-- Name: TABLE notice; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.notice TO anon;
GRANT ALL ON TABLE public.notice TO authenticated;
GRANT ALL ON TABLE public.notice TO service_role;


--
-- Name: SEQUENCE notice_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.notice_id_seq TO anon;
GRANT ALL ON SEQUENCE public.notice_id_seq TO authenticated;
GRANT ALL ON SEQUENCE public.notice_id_seq TO service_role;


--
-- Name: TABLE notifications; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.notifications TO anon;
GRANT ALL ON TABLE public.notifications TO authenticated;
GRANT ALL ON TABLE public.notifications TO service_role;


--
-- Name: SEQUENCE notifications_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.notifications_id_seq TO anon;
GRANT ALL ON SEQUENCE public.notifications_id_seq TO authenticated;
GRANT ALL ON SEQUENCE public.notifications_id_seq TO service_role;


--
-- Name: TABLE password_resets; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.password_resets TO anon;
GRANT ALL ON TABLE public.password_resets TO authenticated;
GRANT ALL ON TABLE public.password_resets TO service_role;


--
-- Name: SEQUENCE password_resets_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.password_resets_id_seq TO anon;
GRANT ALL ON SEQUENCE public.password_resets_id_seq TO authenticated;
GRANT ALL ON SEQUENCE public.password_resets_id_seq TO service_role;


--
-- Name: TABLE principal; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.principal TO anon;
GRANT ALL ON TABLE public.principal TO authenticated;
GRANT ALL ON TABLE public.principal TO service_role;


--
-- Name: TABLE principal_attendance; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.principal_attendance TO anon;
GRANT ALL ON TABLE public.principal_attendance TO authenticated;
GRANT ALL ON TABLE public.principal_attendance TO service_role;


--
-- Name: SEQUENCE principal_attendance_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.principal_attendance_id_seq TO anon;
GRANT ALL ON SEQUENCE public.principal_attendance_id_seq TO authenticated;
GRANT ALL ON SEQUENCE public.principal_attendance_id_seq TO service_role;


--
-- Name: TABLE principal_timings; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.principal_timings TO anon;
GRANT ALL ON TABLE public.principal_timings TO authenticated;
GRANT ALL ON TABLE public.principal_timings TO service_role;


--
-- Name: SEQUENCE principal_timings_timing_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.principal_timings_timing_id_seq TO anon;
GRANT ALL ON SEQUENCE public.principal_timings_timing_id_seq TO authenticated;
GRANT ALL ON SEQUENCE public.principal_timings_timing_id_seq TO service_role;


--
-- Name: TABLE principal_to_bmc_notices; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.principal_to_bmc_notices TO anon;
GRANT ALL ON TABLE public.principal_to_bmc_notices TO authenticated;
GRANT ALL ON TABLE public.principal_to_bmc_notices TO service_role;


--
-- Name: SEQUENCE principal_to_bmc_notices_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.principal_to_bmc_notices_id_seq TO anon;
GRANT ALL ON SEQUENCE public.principal_to_bmc_notices_id_seq TO authenticated;
GRANT ALL ON SEQUENCE public.principal_to_bmc_notices_id_seq TO service_role;


--
-- Name: TABLE principal_to_librarian_notices; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.principal_to_librarian_notices TO anon;
GRANT ALL ON TABLE public.principal_to_librarian_notices TO authenticated;
GRANT ALL ON TABLE public.principal_to_librarian_notices TO service_role;


--
-- Name: SEQUENCE principal_to_librarian_notices_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.principal_to_librarian_notices_id_seq TO anon;
GRANT ALL ON SEQUENCE public.principal_to_librarian_notices_id_seq TO authenticated;
GRANT ALL ON SEQUENCE public.principal_to_librarian_notices_id_seq TO service_role;


--
-- Name: TABLE school; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.school TO anon;
GRANT ALL ON TABLE public.school TO authenticated;
GRANT ALL ON TABLE public.school TO service_role;


--
-- Name: SEQUENCE school_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.school_id_seq TO anon;
GRANT ALL ON SEQUENCE public.school_id_seq TO authenticated;
GRANT ALL ON SEQUENCE public.school_id_seq TO service_role;


--
-- Name: TABLE school_notice_recipients; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.school_notice_recipients TO anon;
GRANT ALL ON TABLE public.school_notice_recipients TO authenticated;
GRANT ALL ON TABLE public.school_notice_recipients TO service_role;


--
-- Name: SEQUENCE school_notice_recipients_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.school_notice_recipients_id_seq TO anon;
GRANT ALL ON SEQUENCE public.school_notice_recipients_id_seq TO authenticated;
GRANT ALL ON SEQUENCE public.school_notice_recipients_id_seq TO service_role;


--
-- Name: TABLE school_notices_content; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.school_notices_content TO anon;
GRANT ALL ON TABLE public.school_notices_content TO authenticated;
GRANT ALL ON TABLE public.school_notices_content TO service_role;


--
-- Name: SEQUENCE school_notices_content_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.school_notices_content_id_seq TO anon;
GRANT ALL ON SEQUENCE public.school_notices_content_id_seq TO authenticated;
GRANT ALL ON SEQUENCE public.school_notices_content_id_seq TO service_role;


--
-- Name: TABLE school_timetable; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.school_timetable TO anon;
GRANT ALL ON TABLE public.school_timetable TO authenticated;
GRANT ALL ON TABLE public.school_timetable TO service_role;


--
-- Name: SEQUENCE school_timetable_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.school_timetable_id_seq TO anon;
GRANT ALL ON SEQUENCE public.school_timetable_id_seq TO authenticated;
GRANT ALL ON SEQUENCE public.school_timetable_id_seq TO service_role;


--
-- Name: TABLE standard_subjects; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.standard_subjects TO anon;
GRANT ALL ON TABLE public.standard_subjects TO authenticated;
GRANT ALL ON TABLE public.standard_subjects TO service_role;


--
-- Name: SEQUENCE standard_subjects_std_subject_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.standard_subjects_std_subject_id_seq TO anon;
GRANT ALL ON SEQUENCE public.standard_subjects_std_subject_id_seq TO authenticated;
GRANT ALL ON SEQUENCE public.standard_subjects_std_subject_id_seq TO service_role;


--
-- Name: TABLE student; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.student TO anon;
GRANT ALL ON TABLE public.student TO authenticated;
GRANT ALL ON TABLE public.student TO service_role;


--
-- Name: TABLE student_marks; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.student_marks TO anon;
GRANT ALL ON TABLE public.student_marks TO authenticated;
GRANT ALL ON TABLE public.student_marks TO service_role;


--
-- Name: SEQUENCE student_marks_mark_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.student_marks_mark_id_seq TO anon;
GRANT ALL ON SEQUENCE public.student_marks_mark_id_seq TO authenticated;
GRANT ALL ON SEQUENCE public.student_marks_mark_id_seq TO service_role;


--
-- Name: TABLE subjects; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.subjects TO anon;
GRANT ALL ON TABLE public.subjects TO authenticated;
GRANT ALL ON TABLE public.subjects TO service_role;


--
-- Name: SEQUENCE subjects_subject_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.subjects_subject_id_seq TO anon;
GRANT ALL ON SEQUENCE public.subjects_subject_id_seq TO authenticated;
GRANT ALL ON SEQUENCE public.subjects_subject_id_seq TO service_role;


--
-- Name: TABLE teacher; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.teacher TO anon;
GRANT ALL ON TABLE public.teacher TO authenticated;
GRANT ALL ON TABLE public.teacher TO service_role;


--
-- Name: TABLE teacher_attendance; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.teacher_attendance TO anon;
GRANT ALL ON TABLE public.teacher_attendance TO authenticated;
GRANT ALL ON TABLE public.teacher_attendance TO service_role;


--
-- Name: SEQUENCE teacher_attendance_attendance_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.teacher_attendance_attendance_id_seq TO anon;
GRANT ALL ON SEQUENCE public.teacher_attendance_attendance_id_seq TO authenticated;
GRANT ALL ON SEQUENCE public.teacher_attendance_attendance_id_seq TO service_role;


--
-- Name: TABLE teacher_timings; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.teacher_timings TO anon;
GRANT ALL ON TABLE public.teacher_timings TO authenticated;
GRANT ALL ON TABLE public.teacher_timings TO service_role;


--
-- Name: SEQUENCE teacher_timings_timing_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.teacher_timings_timing_id_seq TO anon;
GRANT ALL ON SEQUENCE public.teacher_timings_timing_id_seq TO authenticated;
GRANT ALL ON SEQUENCE public.teacher_timings_timing_id_seq TO service_role;


--
-- Name: TABLE timetables; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.timetables TO anon;
GRANT ALL ON TABLE public.timetables TO authenticated;
GRANT ALL ON TABLE public.timetables TO service_role;


--
-- Name: SEQUENCE timetables_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.timetables_id_seq TO anon;
GRANT ALL ON SEQUENCE public.timetables_id_seq TO authenticated;
GRANT ALL ON SEQUENCE public.timetables_id_seq TO service_role;


--
-- Name: TABLE users; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.users TO anon;
GRANT ALL ON TABLE public.users TO authenticated;
GRANT ALL ON TABLE public.users TO service_role;


--
-- Name: SEQUENCE users_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.users_id_seq TO anon;
GRANT ALL ON SEQUENCE public.users_id_seq TO authenticated;
GRANT ALL ON SEQUENCE public.users_id_seq TO service_role;


--
-- Name: TABLE messages; Type: ACL; Schema: realtime; Owner: supabase_realtime_admin
--

GRANT ALL ON TABLE realtime.messages TO postgres;
GRANT ALL ON TABLE realtime.messages TO dashboard_user;
GRANT SELECT,INSERT,UPDATE ON TABLE realtime.messages TO anon;
GRANT SELECT,INSERT,UPDATE ON TABLE realtime.messages TO authenticated;
GRANT SELECT,INSERT,UPDATE ON TABLE realtime.messages TO service_role;


--
-- Name: TABLE schema_migrations; Type: ACL; Schema: realtime; Owner: supabase_admin
--

GRANT ALL ON TABLE realtime.schema_migrations TO postgres;
GRANT ALL ON TABLE realtime.schema_migrations TO dashboard_user;
GRANT SELECT ON TABLE realtime.schema_migrations TO anon;
GRANT SELECT ON TABLE realtime.schema_migrations TO authenticated;
GRANT SELECT ON TABLE realtime.schema_migrations TO service_role;
GRANT ALL ON TABLE realtime.schema_migrations TO supabase_realtime_admin;


--
-- Name: TABLE subscription; Type: ACL; Schema: realtime; Owner: supabase_admin
--

GRANT ALL ON TABLE realtime.subscription TO postgres;
GRANT ALL ON TABLE realtime.subscription TO dashboard_user;
GRANT SELECT ON TABLE realtime.subscription TO anon;
GRANT SELECT ON TABLE realtime.subscription TO authenticated;
GRANT SELECT ON TABLE realtime.subscription TO service_role;
GRANT ALL ON TABLE realtime.subscription TO supabase_realtime_admin;


--
-- Name: SEQUENCE subscription_id_seq; Type: ACL; Schema: realtime; Owner: supabase_admin
--

GRANT ALL ON SEQUENCE realtime.subscription_id_seq TO postgres;
GRANT ALL ON SEQUENCE realtime.subscription_id_seq TO dashboard_user;
GRANT USAGE ON SEQUENCE realtime.subscription_id_seq TO anon;
GRANT USAGE ON SEQUENCE realtime.subscription_id_seq TO authenticated;
GRANT USAGE ON SEQUENCE realtime.subscription_id_seq TO service_role;
GRANT ALL ON SEQUENCE realtime.subscription_id_seq TO supabase_realtime_admin;


--
-- Name: TABLE buckets; Type: ACL; Schema: storage; Owner: supabase_storage_admin
--

GRANT ALL ON TABLE storage.buckets TO anon;
GRANT ALL ON TABLE storage.buckets TO authenticated;
GRANT ALL ON TABLE storage.buckets TO service_role;
GRANT ALL ON TABLE storage.buckets TO postgres WITH GRANT OPTION;


--
-- Name: TABLE objects; Type: ACL; Schema: storage; Owner: supabase_storage_admin
--

GRANT ALL ON TABLE storage.objects TO anon;
GRANT ALL ON TABLE storage.objects TO authenticated;
GRANT ALL ON TABLE storage.objects TO service_role;
GRANT ALL ON TABLE storage.objects TO postgres WITH GRANT OPTION;


--
-- Name: TABLE s3_multipart_uploads; Type: ACL; Schema: storage; Owner: supabase_storage_admin
--

GRANT ALL ON TABLE storage.s3_multipart_uploads TO service_role;
GRANT SELECT ON TABLE storage.s3_multipart_uploads TO authenticated;
GRANT SELECT ON TABLE storage.s3_multipart_uploads TO anon;


--
-- Name: TABLE s3_multipart_uploads_parts; Type: ACL; Schema: storage; Owner: supabase_storage_admin
--

GRANT ALL ON TABLE storage.s3_multipart_uploads_parts TO service_role;
GRANT SELECT ON TABLE storage.s3_multipart_uploads_parts TO authenticated;
GRANT SELECT ON TABLE storage.s3_multipart_uploads_parts TO anon;


--
-- Name: TABLE secrets; Type: ACL; Schema: vault; Owner: supabase_admin
--

GRANT SELECT,REFERENCES,DELETE,TRUNCATE ON TABLE vault.secrets TO postgres WITH GRANT OPTION;
GRANT SELECT,DELETE ON TABLE vault.secrets TO service_role;


--
-- Name: TABLE decrypted_secrets; Type: ACL; Schema: vault; Owner: supabase_admin
--

GRANT SELECT,REFERENCES,DELETE,TRUNCATE ON TABLE vault.decrypted_secrets TO postgres WITH GRANT OPTION;
GRANT SELECT,DELETE ON TABLE vault.decrypted_secrets TO service_role;


--
-- Name: DEFAULT PRIVILEGES FOR SEQUENCES; Type: DEFAULT ACL; Schema: auth; Owner: supabase_auth_admin
--

ALTER DEFAULT PRIVILEGES FOR ROLE supabase_auth_admin IN SCHEMA auth GRANT ALL ON SEQUENCES TO postgres;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_auth_admin IN SCHEMA auth GRANT ALL ON SEQUENCES TO dashboard_user;


--
-- Name: DEFAULT PRIVILEGES FOR FUNCTIONS; Type: DEFAULT ACL; Schema: auth; Owner: supabase_auth_admin
--

ALTER DEFAULT PRIVILEGES FOR ROLE supabase_auth_admin IN SCHEMA auth GRANT ALL ON FUNCTIONS TO postgres;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_auth_admin IN SCHEMA auth GRANT ALL ON FUNCTIONS TO dashboard_user;


--
-- Name: DEFAULT PRIVILEGES FOR TABLES; Type: DEFAULT ACL; Schema: auth; Owner: supabase_auth_admin
--

ALTER DEFAULT PRIVILEGES FOR ROLE supabase_auth_admin IN SCHEMA auth GRANT ALL ON TABLES TO postgres;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_auth_admin IN SCHEMA auth GRANT ALL ON TABLES TO dashboard_user;


--
-- Name: DEFAULT PRIVILEGES FOR SEQUENCES; Type: DEFAULT ACL; Schema: extensions; Owner: supabase_admin
--

ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA extensions GRANT ALL ON SEQUENCES TO postgres WITH GRANT OPTION;


--
-- Name: DEFAULT PRIVILEGES FOR FUNCTIONS; Type: DEFAULT ACL; Schema: extensions; Owner: supabase_admin
--

ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA extensions GRANT ALL ON FUNCTIONS TO postgres WITH GRANT OPTION;


--
-- Name: DEFAULT PRIVILEGES FOR TABLES; Type: DEFAULT ACL; Schema: extensions; Owner: supabase_admin
--

ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA extensions GRANT ALL ON TABLES TO postgres WITH GRANT OPTION;


--
-- Name: DEFAULT PRIVILEGES FOR SEQUENCES; Type: DEFAULT ACL; Schema: graphql; Owner: supabase_admin
--

ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql GRANT ALL ON SEQUENCES TO postgres;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql GRANT ALL ON SEQUENCES TO anon;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql GRANT ALL ON SEQUENCES TO authenticated;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql GRANT ALL ON SEQUENCES TO service_role;


--
-- Name: DEFAULT PRIVILEGES FOR FUNCTIONS; Type: DEFAULT ACL; Schema: graphql; Owner: supabase_admin
--

ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql GRANT ALL ON FUNCTIONS TO postgres;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql GRANT ALL ON FUNCTIONS TO anon;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql GRANT ALL ON FUNCTIONS TO authenticated;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql GRANT ALL ON FUNCTIONS TO service_role;


--
-- Name: DEFAULT PRIVILEGES FOR TABLES; Type: DEFAULT ACL; Schema: graphql; Owner: supabase_admin
--

ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql GRANT ALL ON TABLES TO postgres;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql GRANT ALL ON TABLES TO anon;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql GRANT ALL ON TABLES TO authenticated;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql GRANT ALL ON TABLES TO service_role;


--
-- Name: DEFAULT PRIVILEGES FOR SEQUENCES; Type: DEFAULT ACL; Schema: graphql_public; Owner: supabase_admin
--

ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql_public GRANT ALL ON SEQUENCES TO postgres;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql_public GRANT ALL ON SEQUENCES TO anon;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql_public GRANT ALL ON SEQUENCES TO authenticated;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql_public GRANT ALL ON SEQUENCES TO service_role;


--
-- Name: DEFAULT PRIVILEGES FOR FUNCTIONS; Type: DEFAULT ACL; Schema: graphql_public; Owner: supabase_admin
--

ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql_public GRANT ALL ON FUNCTIONS TO postgres;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql_public GRANT ALL ON FUNCTIONS TO anon;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql_public GRANT ALL ON FUNCTIONS TO authenticated;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql_public GRANT ALL ON FUNCTIONS TO service_role;


--
-- Name: DEFAULT PRIVILEGES FOR TABLES; Type: DEFAULT ACL; Schema: graphql_public; Owner: supabase_admin
--

ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql_public GRANT ALL ON TABLES TO postgres;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql_public GRANT ALL ON TABLES TO anon;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql_public GRANT ALL ON TABLES TO authenticated;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql_public GRANT ALL ON TABLES TO service_role;


--
-- Name: DEFAULT PRIVILEGES FOR SEQUENCES; Type: DEFAULT ACL; Schema: public; Owner: postgres
--

ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA public GRANT ALL ON SEQUENCES TO postgres;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA public GRANT ALL ON SEQUENCES TO anon;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA public GRANT ALL ON SEQUENCES TO authenticated;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA public GRANT ALL ON SEQUENCES TO service_role;


--
-- Name: DEFAULT PRIVILEGES FOR SEQUENCES; Type: DEFAULT ACL; Schema: public; Owner: supabase_admin
--

ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA public GRANT ALL ON SEQUENCES TO postgres;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA public GRANT ALL ON SEQUENCES TO anon;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA public GRANT ALL ON SEQUENCES TO authenticated;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA public GRANT ALL ON SEQUENCES TO service_role;


--
-- Name: DEFAULT PRIVILEGES FOR FUNCTIONS; Type: DEFAULT ACL; Schema: public; Owner: postgres
--

ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA public GRANT ALL ON FUNCTIONS TO postgres;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA public GRANT ALL ON FUNCTIONS TO anon;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA public GRANT ALL ON FUNCTIONS TO authenticated;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA public GRANT ALL ON FUNCTIONS TO service_role;


--
-- Name: DEFAULT PRIVILEGES FOR FUNCTIONS; Type: DEFAULT ACL; Schema: public; Owner: supabase_admin
--

ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA public GRANT ALL ON FUNCTIONS TO postgres;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA public GRANT ALL ON FUNCTIONS TO anon;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA public GRANT ALL ON FUNCTIONS TO authenticated;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA public GRANT ALL ON FUNCTIONS TO service_role;


--
-- Name: DEFAULT PRIVILEGES FOR TABLES; Type: DEFAULT ACL; Schema: public; Owner: postgres
--

ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA public GRANT ALL ON TABLES TO postgres;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA public GRANT ALL ON TABLES TO anon;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA public GRANT ALL ON TABLES TO authenticated;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA public GRANT ALL ON TABLES TO service_role;


--
-- Name: DEFAULT PRIVILEGES FOR TABLES; Type: DEFAULT ACL; Schema: public; Owner: supabase_admin
--

ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA public GRANT ALL ON TABLES TO postgres;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA public GRANT ALL ON TABLES TO anon;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA public GRANT ALL ON TABLES TO authenticated;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA public GRANT ALL ON TABLES TO service_role;


--
-- Name: DEFAULT PRIVILEGES FOR SEQUENCES; Type: DEFAULT ACL; Schema: realtime; Owner: supabase_admin
--

ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA realtime GRANT ALL ON SEQUENCES TO postgres;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA realtime GRANT ALL ON SEQUENCES TO dashboard_user;


--
-- Name: DEFAULT PRIVILEGES FOR FUNCTIONS; Type: DEFAULT ACL; Schema: realtime; Owner: supabase_admin
--

ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA realtime GRANT ALL ON FUNCTIONS TO postgres;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA realtime GRANT ALL ON FUNCTIONS TO dashboard_user;


--
-- Name: DEFAULT PRIVILEGES FOR TABLES; Type: DEFAULT ACL; Schema: realtime; Owner: supabase_admin
--

ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA realtime GRANT ALL ON TABLES TO postgres;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA realtime GRANT ALL ON TABLES TO dashboard_user;


--
-- Name: DEFAULT PRIVILEGES FOR SEQUENCES; Type: DEFAULT ACL; Schema: storage; Owner: postgres
--

ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA storage GRANT ALL ON SEQUENCES TO postgres;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA storage GRANT ALL ON SEQUENCES TO anon;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA storage GRANT ALL ON SEQUENCES TO authenticated;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA storage GRANT ALL ON SEQUENCES TO service_role;


--
-- Name: DEFAULT PRIVILEGES FOR FUNCTIONS; Type: DEFAULT ACL; Schema: storage; Owner: postgres
--

ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA storage GRANT ALL ON FUNCTIONS TO postgres;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA storage GRANT ALL ON FUNCTIONS TO anon;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA storage GRANT ALL ON FUNCTIONS TO authenticated;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA storage GRANT ALL ON FUNCTIONS TO service_role;


--
-- Name: DEFAULT PRIVILEGES FOR TABLES; Type: DEFAULT ACL; Schema: storage; Owner: postgres
--

ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA storage GRANT ALL ON TABLES TO postgres;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA storage GRANT ALL ON TABLES TO anon;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA storage GRANT ALL ON TABLES TO authenticated;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA storage GRANT ALL ON TABLES TO service_role;


--
-- Name: issue_graphql_placeholder; Type: EVENT TRIGGER; Schema: -; Owner: supabase_admin
--

CREATE EVENT TRIGGER issue_graphql_placeholder ON sql_drop
         WHEN TAG IN ('DROP EXTENSION')
   EXECUTE FUNCTION extensions.set_graphql_placeholder();


ALTER EVENT TRIGGER issue_graphql_placeholder OWNER TO supabase_admin;

--
-- Name: issue_pg_cron_access; Type: EVENT TRIGGER; Schema: -; Owner: supabase_admin
--

CREATE EVENT TRIGGER issue_pg_cron_access ON ddl_command_end
         WHEN TAG IN ('CREATE EXTENSION')
   EXECUTE FUNCTION extensions.grant_pg_cron_access();


ALTER EVENT TRIGGER issue_pg_cron_access OWNER TO supabase_admin;

--
-- Name: issue_pg_graphql_access; Type: EVENT TRIGGER; Schema: -; Owner: supabase_admin
--

CREATE EVENT TRIGGER issue_pg_graphql_access ON ddl_command_end
         WHEN TAG IN ('CREATE FUNCTION')
   EXECUTE FUNCTION extensions.grant_pg_graphql_access();


ALTER EVENT TRIGGER issue_pg_graphql_access OWNER TO supabase_admin;

--
-- Name: issue_pg_net_access; Type: EVENT TRIGGER; Schema: -; Owner: supabase_admin
--

CREATE EVENT TRIGGER issue_pg_net_access ON ddl_command_end
         WHEN TAG IN ('CREATE EXTENSION')
   EXECUTE FUNCTION extensions.grant_pg_net_access();


ALTER EVENT TRIGGER issue_pg_net_access OWNER TO supabase_admin;

--
-- Name: pgrst_ddl_watch; Type: EVENT TRIGGER; Schema: -; Owner: supabase_admin
--

CREATE EVENT TRIGGER pgrst_ddl_watch ON ddl_command_end
   EXECUTE FUNCTION extensions.pgrst_ddl_watch();


ALTER EVENT TRIGGER pgrst_ddl_watch OWNER TO supabase_admin;

--
-- Name: pgrst_drop_watch; Type: EVENT TRIGGER; Schema: -; Owner: supabase_admin
--

CREATE EVENT TRIGGER pgrst_drop_watch ON sql_drop
   EXECUTE FUNCTION extensions.pgrst_drop_watch();


ALTER EVENT TRIGGER pgrst_drop_watch OWNER TO supabase_admin;

--
-- PostgreSQL database dump complete
--

