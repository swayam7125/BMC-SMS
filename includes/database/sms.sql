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
-- Name: oauth_registration_type; Type: TYPE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TYPE auth.oauth_registration_type AS ENUM (
    'dynamic',
    'manual'
);


ALTER TYPE auth.oauth_registration_type OWNER TO supabase_auth_admin;

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
    'Absent',
    'Half Day'
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
-- Name: self_transport_mode; Type: TYPE; Schema: public; Owner: postgres
--

CREATE TYPE public.self_transport_mode AS ENUM (
    'Public Transport',
    'Walking',
    'Parents',
    'Bike',
    'Car'
);


ALTER TYPE public.self_transport_mode OWNER TO postgres;

--
-- Name: teacher_attendance_status; Type: TYPE; Schema: public; Owner: postgres
--

CREATE TYPE public.teacher_attendance_status AS ENUM (
    'Present',
    'Absent',
    'Leave',
    'Half Day'
);


ALTER TYPE public.teacher_attendance_status OWNER TO postgres;

--
-- Name: transport_mode_enum; Type: TYPE; Schema: public; Owner: postgres
--

CREATE TYPE public.transport_mode_enum AS ENUM (
    'School Transport',
    'Self',
    'Self Transport'
);


ALTER TYPE public.transport_mode_enum OWNER TO postgres;

--
-- Name: user_role; Type: TYPE; Schema: public; Owner: postgres
--

CREATE TYPE public.user_role AS ENUM (
    'student',
    'teacher',
    'principal',
    'superadmin',
    'librarian',
    'payroll'
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
-- Name: buckettype; Type: TYPE; Schema: storage; Owner: supabase_storage_admin
--

CREATE TYPE storage.buckettype AS ENUM (
    'STANDARD',
    'ANALYTICS'
);


ALTER TYPE storage.buckettype OWNER TO supabase_storage_admin;

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
-- Name: add_prefixes(text, text); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.add_prefixes(_bucket_id text, _name text) RETURNS void
    LANGUAGE plpgsql SECURITY DEFINER
    AS $$
DECLARE
    prefixes text[];
BEGIN
    prefixes := "storage"."get_prefixes"("_name");

    IF array_length(prefixes, 1) > 0 THEN
        INSERT INTO storage.prefixes (name, bucket_id)
        SELECT UNNEST(prefixes) as name, "_bucket_id" ON CONFLICT DO NOTHING;
    END IF;
END;
$$;


ALTER FUNCTION storage.add_prefixes(_bucket_id text, _name text) OWNER TO supabase_storage_admin;

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
-- Name: delete_prefix(text, text); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.delete_prefix(_bucket_id text, _name text) RETURNS boolean
    LANGUAGE plpgsql SECURITY DEFINER
    AS $$
BEGIN
    -- Check if we can delete the prefix
    IF EXISTS(
        SELECT FROM "storage"."prefixes"
        WHERE "prefixes"."bucket_id" = "_bucket_id"
          AND level = "storage"."get_level"("_name") + 1
          AND "prefixes"."name" COLLATE "C" LIKE "_name" || '/%'
        LIMIT 1
    )
    OR EXISTS(
        SELECT FROM "storage"."objects"
        WHERE "objects"."bucket_id" = "_bucket_id"
          AND "storage"."get_level"("objects"."name") = "storage"."get_level"("_name") + 1
          AND "objects"."name" COLLATE "C" LIKE "_name" || '/%'
        LIMIT 1
    ) THEN
    -- There are sub-objects, skip deletion
    RETURN false;
    ELSE
        DELETE FROM "storage"."prefixes"
        WHERE "prefixes"."bucket_id" = "_bucket_id"
          AND level = "storage"."get_level"("_name")
          AND "prefixes"."name" = "_name";
        RETURN true;
    END IF;
END;
$$;


ALTER FUNCTION storage.delete_prefix(_bucket_id text, _name text) OWNER TO supabase_storage_admin;

--
-- Name: delete_prefix_hierarchy_trigger(); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.delete_prefix_hierarchy_trigger() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE
    prefix text;
BEGIN
    prefix := "storage"."get_prefix"(OLD."name");

    IF coalesce(prefix, '') != '' THEN
        PERFORM "storage"."delete_prefix"(OLD."bucket_id", prefix);
    END IF;

    RETURN OLD;
END;
$$;


ALTER FUNCTION storage.delete_prefix_hierarchy_trigger() OWNER TO supabase_storage_admin;

--
-- Name: enforce_bucket_name_length(); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.enforce_bucket_name_length() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
begin
    if length(new.name) > 100 then
        raise exception 'bucket name "%" is too long (% characters). Max is 100.', new.name, length(new.name);
    end if;
    return new;
end;
$$;


ALTER FUNCTION storage.enforce_bucket_name_length() OWNER TO supabase_storage_admin;

--
-- Name: extension(text); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.extension(name text) RETURNS text
    LANGUAGE plpgsql IMMUTABLE
    AS $$
DECLARE
    _parts text[];
    _filename text;
BEGIN
    SELECT string_to_array(name, '/') INTO _parts;
    SELECT _parts[array_length(_parts,1)] INTO _filename;
    RETURN reverse(split_part(reverse(_filename), '.', 1));
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
    LANGUAGE plpgsql IMMUTABLE
    AS $$
DECLARE
    _parts text[];
BEGIN
    -- Split on "/" to get path segments
    SELECT string_to_array(name, '/') INTO _parts;
    -- Return everything except the last segment
    RETURN _parts[1 : array_length(_parts,1) - 1];
END
$$;


ALTER FUNCTION storage.foldername(name text) OWNER TO supabase_storage_admin;

--
-- Name: get_level(text); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.get_level(name text) RETURNS integer
    LANGUAGE sql IMMUTABLE STRICT
    AS $$
SELECT array_length(string_to_array("name", '/'), 1);
$$;


ALTER FUNCTION storage.get_level(name text) OWNER TO supabase_storage_admin;

--
-- Name: get_prefix(text); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.get_prefix(name text) RETURNS text
    LANGUAGE sql IMMUTABLE STRICT
    AS $_$
SELECT
    CASE WHEN strpos("name", '/') > 0 THEN
             regexp_replace("name", '[\/]{1}[^\/]+\/?$', '')
         ELSE
             ''
        END;
$_$;


ALTER FUNCTION storage.get_prefix(name text) OWNER TO supabase_storage_admin;

--
-- Name: get_prefixes(text); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.get_prefixes(name text) RETURNS text[]
    LANGUAGE plpgsql IMMUTABLE STRICT
    AS $$
DECLARE
    parts text[];
    prefixes text[];
    prefix text;
BEGIN
    -- Split the name into parts by '/'
    parts := string_to_array("name", '/');
    prefixes := '{}';

    -- Construct the prefixes, stopping one level below the last part
    FOR i IN 1..array_length(parts, 1) - 1 LOOP
            prefix := array_to_string(parts[1:i], '/');
            prefixes := array_append(prefixes, prefix);
    END LOOP;

    RETURN prefixes;
END;
$$;


ALTER FUNCTION storage.get_prefixes(name text) OWNER TO supabase_storage_admin;

--
-- Name: get_size_by_bucket(); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.get_size_by_bucket() RETURNS TABLE(size bigint, bucket_id text)
    LANGUAGE plpgsql STABLE
    AS $$
BEGIN
    return query
        select sum((metadata->>'size')::bigint) as size, obj.bucket_id
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
-- Name: objects_insert_prefix_trigger(); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.objects_insert_prefix_trigger() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    PERFORM "storage"."add_prefixes"(NEW."bucket_id", NEW."name");
    NEW.level := "storage"."get_level"(NEW."name");

    RETURN NEW;
END;
$$;


ALTER FUNCTION storage.objects_insert_prefix_trigger() OWNER TO supabase_storage_admin;

--
-- Name: objects_update_prefix_trigger(); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.objects_update_prefix_trigger() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE
    old_prefixes TEXT[];
BEGIN
    -- Ensure this is an update operation and the name has changed
    IF TG_OP = 'UPDATE' AND (NEW."name" <> OLD."name" OR NEW."bucket_id" <> OLD."bucket_id") THEN
        -- Retrieve old prefixes
        old_prefixes := "storage"."get_prefixes"(OLD."name");

        -- Remove old prefixes that are only used by this object
        WITH all_prefixes as (
            SELECT unnest(old_prefixes) as prefix
        ),
        can_delete_prefixes as (
             SELECT prefix
             FROM all_prefixes
             WHERE NOT EXISTS (
                 SELECT 1 FROM "storage"."objects"
                 WHERE "bucket_id" = OLD."bucket_id"
                   AND "name" <> OLD."name"
                   AND "name" LIKE (prefix || '%')
             )
         )
        DELETE FROM "storage"."prefixes" WHERE name IN (SELECT prefix FROM can_delete_prefixes);

        -- Add new prefixes
        PERFORM "storage"."add_prefixes"(NEW."bucket_id", NEW."name");
    END IF;
    -- Set the new level
    NEW."level" := "storage"."get_level"(NEW."name");

    RETURN NEW;
END;
$$;


ALTER FUNCTION storage.objects_update_prefix_trigger() OWNER TO supabase_storage_admin;

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
-- Name: prefixes_insert_trigger(); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.prefixes_insert_trigger() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    PERFORM "storage"."add_prefixes"(NEW."bucket_id", NEW."name");
    RETURN NEW;
END;
$$;


ALTER FUNCTION storage.prefixes_insert_trigger() OWNER TO supabase_storage_admin;

--
-- Name: search(text, text, integer, integer, integer, text, text, text); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.search(prefix text, bucketname text, limits integer DEFAULT 100, levels integer DEFAULT 1, offsets integer DEFAULT 0, search text DEFAULT ''::text, sortcolumn text DEFAULT 'name'::text, sortorder text DEFAULT 'asc'::text) RETURNS TABLE(name text, id uuid, updated_at timestamp with time zone, created_at timestamp with time zone, last_accessed_at timestamp with time zone, metadata jsonb)
    LANGUAGE plpgsql
    AS $$
declare
    can_bypass_rls BOOLEAN;
begin
    SELECT rolbypassrls
    INTO can_bypass_rls
    FROM pg_roles
    WHERE rolname = coalesce(nullif(current_setting('role', true), 'none'), current_user);

    IF can_bypass_rls THEN
        RETURN QUERY SELECT * FROM storage.search_v1_optimised(prefix, bucketname, limits, levels, offsets, search, sortcolumn, sortorder);
    ELSE
        RETURN QUERY SELECT * FROM storage.search_legacy_v1(prefix, bucketname, limits, levels, offsets, search, sortcolumn, sortorder);
    END IF;
end;
$$;


ALTER FUNCTION storage.search(prefix text, bucketname text, limits integer, levels integer, offsets integer, search text, sortcolumn text, sortorder text) OWNER TO supabase_storage_admin;

--
-- Name: search_legacy_v1(text, text, integer, integer, integer, text, text, text); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.search_legacy_v1(prefix text, bucketname text, limits integer DEFAULT 100, levels integer DEFAULT 1, offsets integer DEFAULT 0, search text DEFAULT ''::text, sortcolumn text DEFAULT 'name'::text, sortorder text DEFAULT 'asc'::text) RETURNS TABLE(name text, id uuid, updated_at timestamp with time zone, created_at timestamp with time zone, last_accessed_at timestamp with time zone, metadata jsonb)
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


ALTER FUNCTION storage.search_legacy_v1(prefix text, bucketname text, limits integer, levels integer, offsets integer, search text, sortcolumn text, sortorder text) OWNER TO supabase_storage_admin;

--
-- Name: search_v1_optimised(text, text, integer, integer, integer, text, text, text); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.search_v1_optimised(prefix text, bucketname text, limits integer DEFAULT 100, levels integer DEFAULT 1, offsets integer DEFAULT 0, search text DEFAULT ''::text, sortcolumn text DEFAULT 'name'::text, sortorder text DEFAULT 'asc'::text) RETURNS TABLE(name text, id uuid, updated_at timestamp with time zone, created_at timestamp with time zone, last_accessed_at timestamp with time zone, metadata jsonb)
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
           select (string_to_array(name, ''/''))[level] as name
           from storage.prefixes
             where lower(prefixes.name) like lower($2 || $3) || ''%''
               and bucket_id = $4
               and level = $1
           order by name ' || v_sort_order || '
     )
     (select name,
            null as id,
            null as updated_at,
            null as created_at,
            null as last_accessed_at,
            null as metadata from folders)
     union all
     (select path_tokens[level] as "name",
            id,
            updated_at,
            created_at,
            last_accessed_at,
            metadata
     from storage.objects
     where lower(objects.name) like lower($2 || $3) || ''%''
       and bucket_id = $4
       and level = $1
     order by ' || v_order_by || ')
     limit $5
     offset $6' using levels, prefix, search, bucketname, limits, offsets;
end;
$_$;


ALTER FUNCTION storage.search_v1_optimised(prefix text, bucketname text, limits integer, levels integer, offsets integer, search text, sortcolumn text, sortorder text) OWNER TO supabase_storage_admin;

--
-- Name: search_v2(text, text, integer, integer, text); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.search_v2(prefix text, bucket_name text, limits integer DEFAULT 100, levels integer DEFAULT 1, start_after text DEFAULT ''::text) RETURNS TABLE(key text, name text, id uuid, updated_at timestamp with time zone, created_at timestamp with time zone, metadata jsonb)
    LANGUAGE plpgsql STABLE
    AS $_$
BEGIN
    RETURN query EXECUTE
        $sql$
        SELECT * FROM (
            (
                SELECT
                    split_part(name, '/', $4) AS key,
                    name || '/' AS name,
                    NULL::uuid AS id,
                    NULL::timestamptz AS updated_at,
                    NULL::timestamptz AS created_at,
                    NULL::jsonb AS metadata
                FROM storage.prefixes
                WHERE name COLLATE "C" LIKE $1 || '%'
                AND bucket_id = $2
                AND level = $4
                AND name COLLATE "C" > $5
                ORDER BY prefixes.name COLLATE "C" LIMIT $3
            )
            UNION ALL
            (SELECT split_part(name, '/', $4) AS key,
                name,
                id,
                updated_at,
                created_at,
                metadata
            FROM storage.objects
            WHERE name COLLATE "C" LIKE $1 || '%'
                AND bucket_id = $2
                AND level = $4
                AND name COLLATE "C" > $5
            ORDER BY name COLLATE "C" LIMIT $3)
        ) obj
        ORDER BY name COLLATE "C" LIMIT $3;
        $sql$
        USING prefix, bucket_name, limits, levels, start_after;
END;
$_$;


ALTER FUNCTION storage.search_v2(prefix text, bucket_name text, limits integer, levels integer, start_after text) OWNER TO supabase_storage_admin;

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
-- Name: oauth_clients; Type: TABLE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TABLE auth.oauth_clients (
    id uuid NOT NULL,
    client_id text NOT NULL,
    client_secret_hash text NOT NULL,
    registration_type auth.oauth_registration_type NOT NULL,
    redirect_uris text NOT NULL,
    grant_types text NOT NULL,
    client_name text,
    client_uri text,
    logo_uri text,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    deleted_at timestamp with time zone,
    CONSTRAINT oauth_clients_client_name_length CHECK ((char_length(client_name) <= 1024)),
    CONSTRAINT oauth_clients_client_uri_length CHECK ((char_length(client_uri) <= 2048)),
    CONSTRAINT oauth_clients_logo_uri_length CHECK ((char_length(logo_uri) <= 2048))
);


ALTER TABLE auth.oauth_clients OWNER TO supabase_auth_admin;

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
    disabled boolean,
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
    student_id integer NOT NULL,
    teacher_id integer NOT NULL,
    school_id integer NOT NULL,
    standard character varying(10) NOT NULL,
    subject character varying(100) NOT NULL,
    period_number integer NOT NULL,
    attendance_date date NOT NULL,
    status public.attendance_status NOT NULL,
    id bigint NOT NULL
);


ALTER TABLE public.attendance OWNER TO postgres;

--
-- Name: attendance_id_new_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.attendance ALTER COLUMN id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.attendance_id_new_seq
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
    created_at timestamp with time zone DEFAULT now() NOT NULL
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
    fine_status public.fine_status DEFAULT 'Unpaid'::public.fine_status NOT NULL,
    CONSTRAINT chk_fine_amount CHECK ((fine_amount >= (0)::numeric)),
    CONSTRAINT chk_return_date CHECK (((return_date IS NULL) OR (return_date >= checkout_date)))
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
    original_book_id bigint NOT NULL,
    title character varying(255) NOT NULL,
    author character varying(255) NOT NULL,
    isbn character varying(25) DEFAULT NULL::character varying,
    quantity_total integer,
    school_id bigint NOT NULL,
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
    deleted_at timestamp with time zone DEFAULT now() NOT NULL,
    batch public.batch_enum
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
    gender public.gender_enum_mfo,
    blood_group public.blood_group_enum,
    address text,
    father_name character varying(100) DEFAULT NULL::character varying,
    father_phone character varying(15) DEFAULT NULL::character varying,
    mother_name character varying(100) DEFAULT NULL::character varying,
    mother_phone character varying(15) DEFAULT NULL::character varying,
    school_id integer,
    reason_for_leaving text,
    deleted_by_role character varying(50) DEFAULT NULL::character varying,
    deleted_at timestamp with time zone DEFAULT now() NOT NULL,
    transport_mode public.transport_mode_enum
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
    gender public.gender_enum_mfo,
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
-- Name: drivers; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.drivers (
    id integer NOT NULL,
    school_id bigint NOT NULL,
    driver_name character varying(100) NOT NULL,
    phone_number character varying(15) NOT NULL,
    license_number character varying(50) NOT NULL,
    created_at timestamp with time zone DEFAULT now()
);


ALTER TABLE public.drivers OWNER TO postgres;

--
-- Name: drivers_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.drivers_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.drivers_id_seq OWNER TO postgres;

--
-- Name: drivers_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.drivers_id_seq OWNED BY public.drivers.id;


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
-- Name: holidays; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.holidays (
    id integer NOT NULL,
    school_id bigint NOT NULL,
    holiday_date date NOT NULL,
    description character varying(255) NOT NULL,
    created_at timestamp with time zone DEFAULT now()
);


ALTER TABLE public.holidays OWNER TO postgres;

--
-- Name: holidays_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.holidays ALTER COLUMN id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.holidays_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: incentives; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.incentives (
    id integer NOT NULL,
    school_id bigint NOT NULL,
    incentive_name character varying(255) NOT NULL,
    percentage numeric(5,2) NOT NULL,
    created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
    type character varying(20) DEFAULT 'Addition'::character varying NOT NULL,
    CONSTRAINT chk_incentive_type CHECK (((type)::text = ANY ((ARRAY['Addition'::character varying, 'Subtraction'::character varying])::text[])))
);


ALTER TABLE public.incentives OWNER TO postgres;

--
-- Name: incentives_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.incentives_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.incentives_id_seq OWNER TO postgres;

--
-- Name: incentives_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.incentives_id_seq OWNED BY public.incentives.id;


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
    salary numeric(10,2) DEFAULT NULL::numeric,
    batch public.batch_enum,
    date_of_joining date,
    transport_mode public.transport_mode_enum,
    self_transport_mode public.self_transport_mode,
    vehicle_number character varying(50),
    license_number character varying(50),
    stop_id integer
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
-- Name: librarian_leave_applications_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.librarian_leave_applications_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.librarian_leave_applications_id_seq OWNER TO postgres;

--
-- Name: librarian_leave_applications; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.librarian_leave_applications (
    id bigint DEFAULT nextval('public.librarian_leave_applications_id_seq'::regclass) NOT NULL,
    librarian_id bigint NOT NULL,
    from_date date NOT NULL,
    to_date date NOT NULL,
    reason text NOT NULL,
    leave_type character varying(20) DEFAULT 'Full Day'::character varying NOT NULL,
    status character varying(20) DEFAULT 'Pending'::character varying NOT NULL,
    applied_on timestamp with time zone DEFAULT now() NOT NULL,
    rejection_reason text
);


ALTER TABLE public.librarian_leave_applications OWNER TO postgres;

--
-- Name: librarian_payroll; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.librarian_payroll (
    id integer NOT NULL,
    librarian_id bigint NOT NULL,
    payroll_user_id bigint NOT NULL,
    school_id bigint NOT NULL,
    salary_month integer NOT NULL,
    salary_year integer NOT NULL,
    base_salary numeric(10,2) NOT NULL,
    total_working_days integer NOT NULL,
    present_days numeric(4,1) NOT NULL,
    absent_days integer NOT NULL,
    deduction_amount numeric(10,2) NOT NULL,
    total_incentives numeric(10,2) DEFAULT 0.00 NOT NULL,
    net_salary_paid numeric(10,2) NOT NULL,
    payment_date timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
    status character varying(20) DEFAULT 'Paid'::character varying
);


ALTER TABLE public.librarian_payroll OWNER TO postgres;

--
-- Name: librarian_payroll_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.librarian_payroll_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.librarian_payroll_id_seq OWNER TO postgres;

--
-- Name: librarian_payroll_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.librarian_payroll_id_seq OWNED BY public.librarian_payroll.id;


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
    is_read boolean DEFAULT false NOT NULL,
    file_path character varying(255) DEFAULT NULL::character varying,
    file_type character varying(50) DEFAULT NULL::character varying,
    sender_role character varying(50),
    receiver_role character varying(50),
    original_filename character varying(255)
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
-- Name: payroll; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.payroll (
    id bigint NOT NULL,
    school_id bigint NOT NULL,
    payroll_name character varying(100),
    payroll_image character varying(255),
    salary numeric(10,2)
);


ALTER TABLE public.payroll OWNER TO postgres;

--
-- Name: payroll_attendance; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.payroll_attendance (
    id bigint NOT NULL,
    payroll_id bigint NOT NULL,
    school_id bigint NOT NULL,
    attendance_date date NOT NULL,
    status public.attendance_status NOT NULL,
    login_latitude numeric(10,8),
    login_longitude numeric(11,8),
    login_time time without time zone,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    marked_by_user_id bigint
);


ALTER TABLE public.payroll_attendance OWNER TO postgres;

--
-- Name: payroll_attendance_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.payroll_attendance ALTER COLUMN id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.payroll_attendance_id_seq
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
    batch public.batch_enum,
    date_of_joining date,
    transport_mode public.transport_mode_enum,
    self_transport_mode public.self_transport_mode,
    vehicle_number character varying(50),
    license_number character varying(50),
    stop_id integer
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
-- Name: principal_payroll; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.principal_payroll (
    id integer NOT NULL,
    principal_id bigint NOT NULL,
    payroll_user_id bigint NOT NULL,
    school_id bigint NOT NULL,
    salary_month integer NOT NULL,
    salary_year integer NOT NULL,
    base_salary numeric(10,2) NOT NULL,
    total_working_days integer NOT NULL,
    present_days numeric(4,1) NOT NULL,
    absent_days integer NOT NULL,
    deduction_amount numeric(10,2) NOT NULL,
    total_incentives numeric(10,2) DEFAULT 0.00 NOT NULL,
    net_salary_paid numeric(10,2) NOT NULL,
    payment_date timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
    status character varying(20) DEFAULT 'Paid'::character varying
);


ALTER TABLE public.principal_payroll OWNER TO postgres;

--
-- Name: principal_payroll_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.principal_payroll_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.principal_payroll_id_seq OWNER TO postgres;

--
-- Name: principal_payroll_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.principal_payroll_id_seq OWNED BY public.principal_payroll.id;


--
-- Name: principal_timings; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.principal_timings (
    timing_id bigint NOT NULL,
    principal_id bigint NOT NULL,
    day_of_week public.day_of_week NOT NULL,
    opens_at character varying(8),
    closes_at character varying(8),
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
-- Name: routes; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.routes (
    id integer NOT NULL,
    school_id bigint NOT NULL,
    route_name character varying(100) NOT NULL,
    vehicle_id integer NOT NULL,
    driver_id integer NOT NULL
);


ALTER TABLE public.routes OWNER TO postgres;

--
-- Name: routes_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.routes_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.routes_id_seq OWNER TO postgres;

--
-- Name: routes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.routes_id_seq OWNED BY public.routes.id;


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
    passing_percentage numeric(5,2) DEFAULT 33.00 NOT NULL,
    minimum_attendance_percentage numeric(5,2) DEFAULT 75.00 NOT NULL
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
-- Name: staff_incentives; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.staff_incentives (
    id integer NOT NULL,
    staff_id bigint NOT NULL,
    staff_role public.user_role NOT NULL,
    incentive_id integer NOT NULL,
    salary_month integer NOT NULL,
    salary_year integer NOT NULL,
    amount numeric(10,2) NOT NULL,
    assigned_by_user_id bigint NOT NULL,
    assigned_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_staff_role CHECK ((staff_role = ANY (ARRAY['teacher'::public.user_role, 'librarian'::public.user_role, 'principal'::public.user_role])))
);


ALTER TABLE public.staff_incentives OWNER TO postgres;

--
-- Name: staff_incentives_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.staff_incentives_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.staff_incentives_id_seq OWNER TO postgres;

--
-- Name: staff_incentives_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.staff_incentives_id_seq OWNED BY public.staff_incentives.id;


--
-- Name: standard_categories_mapping; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.standard_categories_mapping (
    category_name character varying(50) NOT NULL,
    standard_name character varying(50) NOT NULL
);


ALTER TABLE public.standard_categories_mapping OWNER TO postgres;

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
-- Name: stops; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.stops (
    id integer NOT NULL,
    route_id integer NOT NULL,
    stop_name character varying(100) NOT NULL,
    stop_fee numeric(10,2) DEFAULT 0.00
);


ALTER TABLE public.stops OWNER TO postgres;

--
-- Name: stops_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.stops_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.stops_id_seq OWNER TO postgres;

--
-- Name: stops_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.stops_id_seq OWNED BY public.stops.id;


--
-- Name: student; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.student (
    id bigint NOT NULL,
    student_image character varying(255) DEFAULT NULL::character varying,
    student_name character varying(50) DEFAULT NULL::character varying,
    rollno character varying(10) DEFAULT NULL::character varying,
    std character varying(50) DEFAULT NULL::character varying,
    email character varying(50) NOT NULL,
    password character varying(255) NOT NULL,
    academic_year character varying(9) DEFAULT NULL::character varying,
    school_id bigint,
    dob date,
    gender public.gender_enum_mfo,
    blood_group public.blood_group_enum,
    address text,
    father_name character varying(50) DEFAULT NULL::character varying,
    father_phone character varying(10) DEFAULT NULL::character varying,
    mother_name character varying(50) DEFAULT NULL::character varying,
    mother_phone character varying(10) DEFAULT NULL::character varying,
    stop_id integer,
    transport_mode public.transport_mode_enum DEFAULT 'Self'::public.transport_mode_enum NOT NULL,
    date_of_joining date,
    self_transport_mode public.self_transport_mode,
    vehicle_number character varying(50),
    license_number character varying(50)
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
    exam_type character varying(100) NOT NULL,
    subject_name character varying(100) NOT NULL,
    marks_obtained numeric(5,2) NOT NULL,
    total_marks numeric(5,2) DEFAULT 100.00 NOT NULL,
    entry_date timestamp with time zone DEFAULT now() NOT NULL,
    entered_by_user_id bigint,
    CONSTRAINT chk_marks_range CHECK (((marks_obtained >= (0)::numeric) AND (marks_obtained <= total_marks)))
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
    class_teacher_std character varying(50) DEFAULT NULL::character varying,
    date_of_joining date,
    transport_mode public.transport_mode_enum,
    self_transport_mode public.self_transport_mode,
    vehicle_number character varying(50),
    license_number character varying(50),
    stop_id integer
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
-- Name: teacher_payroll; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.teacher_payroll (
    id integer NOT NULL,
    teacher_id bigint NOT NULL,
    payroll_user_id bigint NOT NULL,
    school_id bigint NOT NULL,
    salary_month integer NOT NULL,
    salary_year integer NOT NULL,
    base_salary numeric(10,2) NOT NULL,
    total_working_days integer NOT NULL,
    present_days numeric(4,1) NOT NULL,
    absent_days integer NOT NULL,
    deduction_amount numeric(10,2) NOT NULL,
    total_incentives numeric(10,2) DEFAULT 0.00 NOT NULL,
    net_salary_paid numeric(10,2) NOT NULL,
    payment_date timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
    status character varying(20) DEFAULT 'Paid'::character varying
);


ALTER TABLE public.teacher_payroll OWNER TO postgres;

--
-- Name: teacher_payroll_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.teacher_payroll_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.teacher_payroll_id_seq OWNER TO postgres;

--
-- Name: teacher_payroll_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.teacher_payroll_id_seq OWNED BY public.teacher_payroll.id;


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
-- Name: vehicles; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.vehicles (
    id integer NOT NULL,
    school_id bigint NOT NULL,
    vehicle_number character varying(20) NOT NULL,
    model character varying(100),
    seating_capacity integer NOT NULL,
    insurance_expiry_date date,
    created_at timestamp with time zone DEFAULT now()
);


ALTER TABLE public.vehicles OWNER TO postgres;

--
-- Name: vehicles_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.vehicles_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.vehicles_id_seq OWNER TO postgres;

--
-- Name: vehicles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.vehicles_id_seq OWNED BY public.vehicles.id;


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
    owner_id text,
    type storage.buckettype DEFAULT 'STANDARD'::storage.buckettype NOT NULL
);


ALTER TABLE storage.buckets OWNER TO supabase_storage_admin;

--
-- Name: COLUMN buckets.owner; Type: COMMENT; Schema: storage; Owner: supabase_storage_admin
--

COMMENT ON COLUMN storage.buckets.owner IS 'Field is deprecated, use owner_id instead';


--
-- Name: buckets_analytics; Type: TABLE; Schema: storage; Owner: supabase_storage_admin
--

CREATE TABLE storage.buckets_analytics (
    id text NOT NULL,
    type storage.buckettype DEFAULT 'ANALYTICS'::storage.buckettype NOT NULL,
    format text DEFAULT 'ICEBERG'::text NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE storage.buckets_analytics OWNER TO supabase_storage_admin;

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
    user_metadata jsonb,
    level integer
);


ALTER TABLE storage.objects OWNER TO supabase_storage_admin;

--
-- Name: COLUMN objects.owner; Type: COMMENT; Schema: storage; Owner: supabase_storage_admin
--

COMMENT ON COLUMN storage.objects.owner IS 'Field is deprecated, use owner_id instead';


--
-- Name: prefixes; Type: TABLE; Schema: storage; Owner: supabase_storage_admin
--

CREATE TABLE storage.prefixes (
    bucket_id text NOT NULL,
    name text NOT NULL COLLATE pg_catalog."C",
    level integer GENERATED ALWAYS AS (storage.get_level(name)) STORED NOT NULL,
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now()
);


ALTER TABLE storage.prefixes OWNER TO supabase_storage_admin;

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
-- Name: drivers id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.drivers ALTER COLUMN id SET DEFAULT nextval('public.drivers_id_seq'::regclass);


--
-- Name: incentives id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.incentives ALTER COLUMN id SET DEFAULT nextval('public.incentives_id_seq'::regclass);


--
-- Name: librarian_payroll id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.librarian_payroll ALTER COLUMN id SET DEFAULT nextval('public.librarian_payroll_id_seq'::regclass);


--
-- Name: principal_payroll id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.principal_payroll ALTER COLUMN id SET DEFAULT nextval('public.principal_payroll_id_seq'::regclass);


--
-- Name: routes id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.routes ALTER COLUMN id SET DEFAULT nextval('public.routes_id_seq'::regclass);


--
-- Name: staff_incentives id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.staff_incentives ALTER COLUMN id SET DEFAULT nextval('public.staff_incentives_id_seq'::regclass);


--
-- Name: stops id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.stops ALTER COLUMN id SET DEFAULT nextval('public.stops_id_seq'::regclass);


--
-- Name: teacher_payroll id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.teacher_payroll ALTER COLUMN id SET DEFAULT nextval('public.teacher_payroll_id_seq'::regclass);


--
-- Name: vehicles id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.vehicles ALTER COLUMN id SET DEFAULT nextval('public.vehicles_id_seq'::regclass);


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
-- Data for Name: oauth_clients; Type: TABLE DATA; Schema: auth; Owner: supabase_auth_admin
--

COPY auth.oauth_clients (id, client_id, client_secret_hash, registration_type, redirect_uris, grant_types, client_name, client_uri, logo_uri, created_at, updated_at, deleted_at) FROM stdin;
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
20250717082212
20250731150234
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

COPY auth.sso_providers (id, resource_id, created_at, updated_at, disabled) FROM stdin;
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
18	24	150	/BMC-SMS/pages/assignments/submit/sub_68b48e1c9613a8.36311755_All_School_Library_Report.pdf	All_School_Library_Report.pdf	Accepted	\N	2025-08-31 18:02:03.573338+00	2025-08-31 18:02:34.142689+00	1
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
1	6	4	11	Maths	assignmentsss	do it	\N	\N	2025-08-29	2025-08-08 10:02:15.287176+00
2	6	4	11	Maths	assignmentsss 123	do this coreectly	\N	\N	2025-08-27	2025-08-08 10:07:22.108929+00
18	6	4	10	Maths	dg	fsdg	\N	\N	2025-08-22	2025-08-14 09:57:09.594333+00
19	6	4	11	Chemistry	etr	v	/BMC-SMS/pages/assignments/uploads/assign_689db501a986b2.45531819_Leaving_Certificate_Ashutosh Sharma.pdf	Leaving_Certificate_Ashutosh Sharma.pdf	2025-08-22	2025-08-14 10:05:52.27621+00
20	54	4	11	Biology	Biology Assignment	Submit before due date	\N	\N	2025-08-16	2025-08-14 10:13:51.331446+00
21	6	4	11	Maths	HIII	OP IN THE CHAT	\N	\N	2025-08-17	2025-08-14 11:33:28.317677+00
22	6	4	11	Maths	HII	WOW	\N	\N	2025-08-18	2025-08-14 11:42:48.473999+00
23	6	4	11	Maths	hellooo	byeeeeeeeeeeeeee	\N	\N	2025-09-05	2025-08-26 11:25:37.506675+00
24	6	4	11	Chemistry	assignment from swayam	swayam is sending assignment	\N	\N	2025-09-04	2025-08-26 11:27:54.452762+00
\.


--
-- Data for Name: attendance; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.attendance (student_id, teacher_id, school_id, standard, subject, period_number, attendance_date, status, id) FROM stdin;
91	51	4	12	Physical Education	1	2025-08-19	Present	1
93	51	4	12	Physical Education	1	2025-08-19	Present	2
59	6	4	11	Chemistry	2	2025-08-28	Present	3
15	6	4	11	Chemistry	2	2025-08-28	Absent	4
111	6	4	11	Chemistry	2	2025-08-28	Absent	5
112	6	4	11	Chemistry	2	2025-08-28	Present	6
113	6	4	11	Chemistry	2	2025-08-28	Present	7
142	6	4	11	Chemistry	2	2025-08-28	Present	8
\.


--
-- Data for Name: book_requests; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.book_requests (request_id, requester_id, requester_role, school_id, book_title, author, reason, status, created_at) FROM stdin;
3	6	teacher	4	swayam	swayam	read	Approved	2025-08-08 10:48:33.422181+00
2	6	teacher	4	maharbharat	Ved Vyas	I want to read	Approved	2025-08-08 10:40:43.407814+00
8	6	teacher	4	Meet me	devam	i want	Rejected	2025-08-08 11:52:07.960265+00
9	6	teacher	4	Testing by Meet	Meet Patel	This book helps us to solve the notification issues	Approved	2025-08-11 09:10:37.01456+00
10	6	teacher	4	Testing II by Meet	MSD	my wish	Rejected	2025-08-11 09:28:24.762984+00
11	54	teacher	4	War II	Napolean	Testing notification of new book in librarian panel	Rejected	2025-08-11 09:56:24.088218+00
12	54	teacher	4	Indian Economy	Vikram	test	Approved	2025-08-11 10:07:34.926626+00
13	54	teacher	4	ABCD	PQRS	abcd	Approved	2025-08-11 10:14:07.052465+00
14	6	teacher	4	Testing	abcd	ert	Approved	2025-08-11 12:38:09.56677+00
15	6	teacher	4	hello	wie0p	rpe	Approved	2025-08-12 09:13:54.746214+00
16	6	teacher	4	Coding Basics	Shivani Thakkar	Required to enhance learning of students	Approved	2025-08-14 11:36:29.013834+00
\.


--
-- Data for Name: books; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.books (book_id, school_id, title, author, isbn, publisher, quantity_total, quantity_available, created_at) FROM stdin;
2	4	The Ramayana	Valmiki (Translated by Arshia Sattar)	978-0143441828	Penguin Classics	15	15	2025-08-07 16:41:11.868196+00
5	4	Hitler : The Ruler	Meet Patil	978-0143441829	Ramson Books & Magazines	15	15	2025-08-14 11:11:25.033157+00
7	4	Love Birds	Harsh	978-0061120084	Swayam	10	10	2025-08-25 11:57:20.169915+00
\.


--
-- Data for Name: borrow_requests; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.borrow_requests (request_id, book_id, school_id, borrower_id, borrower_role, requested_due_date, request_date, status, librarian_id, action_date, rejection_reason, due_date) FROM stdin;
1	2	4	6	teacher	2025-08-17	2025-08-07 17:14:13.143276+00	Approved	\N	2025-08-07 17:25:31.757647+00	\N	2025-08-21
2	2	4	6	teacher	2025-08-17	2025-08-07 17:28:55.918004+00	Approved	\N	2025-08-07 17:30:10.456331+00	\N	2025-08-21
6	2	4	6	teacher	2025-08-17	2025-08-07 17:41:27.982507+00	Approved	\N	2025-08-07 17:42:03.323022+00	\N	2025-08-17
7	2	4	6	teacher	2025-08-17	2025-08-07 17:54:09.603121+00	Approved	\N	2025-08-07 17:55:20.546755+00	\N	2025-08-17
14	2	4	6	teacher	2025-08-09	2025-08-08 11:29:34.182816+00	Approved	36	2025-08-08 11:29:48.643384+00	\N	2025-08-09
16	2	4	6	teacher	2025-08-09	2025-08-08 11:48:22.396444+00	Approved	36	2025-08-08 11:48:33.815533+00	\N	2025-08-09
17	2	4	6	teacher	2025-08-09	2025-08-08 11:51:03.626265+00	Approved	36	2025-08-08 11:51:12.823561+00	\N	2025-08-09
18	2	4	6	teacher	2025-08-18	2025-08-11 09:11:02.803856+00	Approved	36	2025-08-11 09:12:06.088446+00	\N	2025-08-18
19	2	4	6	teacher	2025-08-14	2025-08-12 09:13:25.041776+00	Rejected	36	2025-08-12 09:15:51.106777+00	Not allowed	\N
20	2	4	6	teacher	2025-08-16	2025-08-14 11:37:33.381667+00	Approved	36	2025-08-14 11:38:27.811073+00	\N	2025-08-16
\.


--
-- Data for Name: borrowing_records; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.borrowing_records (record_id, book_id, borrower_id, borrower_role, checkout_date, due_date, return_date, is_returned, fine_amount, fine_status) FROM stdin;
1	2	6	teacher	2025-08-07	2025-08-21	2025-08-07	t	0.00	Unpaid
4	2	6	teacher	2025-08-07	2025-08-21	2025-08-07	t	0.00	Unpaid
5	2	6	teacher	2025-08-07	2025-08-21	2025-08-07	t	0.00	Unpaid
6	2	6	teacher	2025-08-07	2025-08-17	2025-08-07	t	0.00	Unpaid
7	2	6	teacher	2025-08-07	2025-08-21	2025-08-07	t	0.00	Unpaid
9	2	6	teacher	2025-08-07	2025-08-17	2025-08-07	t	0.00	Unpaid
10	2	6	teacher	2025-08-07	2025-08-21	2025-08-07	t	0.00	Unpaid
16	2	6	teacher	2025-08-08	2025-08-09	2025-08-08	t	0.00	Unpaid
18	2	6	teacher	2025-08-08	2025-08-09	2025-08-08	t	0.00	Unpaid
19	2	6	teacher	2025-08-08	2025-08-09	2025-08-08	t	0.00	Unpaid
20	2	6	teacher	2025-08-11	2025-08-18	2025-08-13	t	0.00	Unpaid
22	2	6	teacher	2025-08-14	2025-08-16	2025-08-21	t	0.00	Unpaid
21	5	6	teacher	2025-08-14	2025-08-28	2025-08-21	t	0.00	Unpaid
\.


--
-- Data for Name: deleted_books; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.deleted_books (archived_book_id, original_book_id, title, author, isbn, quantity_total, school_id, is_digital, deleted_at, deleted_by_role) FROM stdin;
1	1	Harry	Devam	7965412BD	2	4	f	2025-08-05 03:31:40+00	librarian
2	3	Wings of Fire: An Autobiography	A.P.J. Abdul Kalam	978-8173711466	15	4	f	2025-08-07 18:13:19.577394+00	librarian
3	6	Love or War	Shakespeare	978-0143441830	10	4	f	2025-08-14 11:14:06.209699+00	librarian
\.


--
-- Data for Name: deleted_librarians; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.deleted_librarians (id, librarian_name, email, phone, dob, gender, blood_group, address, qualification, salary, school_id, deleted_by_role, deleted_at, batch) FROM stdin;
21	Devang Odedra	devang@gmail.com	9567845678	1980-09-01	Male	AB+	Surat	B.A	30000.00	4	principal	2025-08-04 09:06:44+00	\N
22	Devang Odedra	devang@gmail.com	9654378291	1980-09-01	Male	AB+	Surat	B.A	30000.00	4	principal	2025-08-04 09:56:08+00	\N
23	Devang Odedra	devang@gmail.com	8956321456	1980-09-21	Male	AB-	Canal Road, Palanpur Patiya, Surat	B.A	32000.00	4	principal	2025-08-07 18:37:07.277285+00	\N
71	Nilay Thakkar	nilay@gmail.com	9863200144	1999-09-08	Male	B+	Motavarachha	PHD in Library	28000.00	4	principal	2025-08-13 10:19:32.91387+00	\N
104	Ami Unadkat	ami@gmail.com	9876543210	1990-05-09	Female	A-	Surat	M.A, B.Ed	30000.00	4	principal	2025-08-18 10:33:39.813199+00	\N
57	Rajesh Jain	rajesh@gmail.com	7410235896	1995-12-08	Male	O+	abcd	B.A	21000.00	4	principal	2025-08-21 10:58:01.977754+00	\N
130	Henil Patel	henil@gmail.com	9852142018	1998-02-02	Male	O-	fd	M.A. M.Ed	12800.00	4	principal	2025-08-28 10:26:49.774305+00	\N
\.


--
-- Data for Name: deleted_principals; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.deleted_principals (id, principal_name, email, phone, dob, gender, blood_group, address, qualification, salary, batch, school_id, deleted_by_role, deleted_at) FROM stdin;
1	HARSH	harsh@gmail.com	5674231689	2005-02-06	male	B-	Adajan	B.C.A	500000.00	\N	3	principal	2025-07-22 11:51:18+00
11	Harish	harish120@gmail.com	5674567458	2005-10-22	male	O+	adajan	d	560000.00	Morning	1	superadmin	2025-08-07 10:48:08.569224+00
14	Raj	raj@gmail.com	8532415697	2000-03-28	male	AB-	Adajan	MD	800000.00	Morning	3	superadmin	2025-08-07 11:22:44.378451+00
17	Manav	manav@gmail.com	5641237893	1997-05-08	male	B-	Varacha	12th	1000000.00	Evening	3	superadmin	2025-08-07 11:54:44.986561+00
38	Sanjay	sanjay@gmail.com	2316541236	1975-12-19	male	A+	Adajan Gam	PHD	1700000.00	Morning	6	superadmin	2025-08-08 03:41:46.876715+00
37	Jayesh	jayesh@gmail.com	6352417896	1997-05-06	male	B-	Adajan	MA	1200000.00	Morning	5	superadmin	2025-08-08 03:41:53.883961+00
43	Akash	akash@gmail.com	7874125639	2005-06-07	male	O+	Navsari	MA	600000.00	Morning	5	superadmin	2025-08-08 07:51:07.798923+00
66	Satish	satish@gmail.com	8596741235	1997-03-11	male	A+	Adajan	MA	850000.00	Morning	12	superadmin	2025-08-10 17:48:47.101344+00
69	Manan	manan@gmail.com	5674123985	1999-03-01	male	AB+	Varacha	MA	1000000.00	Evening	12	superadmin	2025-08-10 17:48:47.101344+00
58	Gotu	gotu@gmail.com	8527419632	1999-05-17	male	A-	surat	d	567504.00	Morning	5	superadmin	2025-08-10 18:09:20.831395+00
56	Dhruv	dhruv@gmail.com	8596321478	2005-02-28	male	A+	Surat	MA	650000.00	Morning	9	superadmin	2025-08-10 18:09:35.142576+00
41	Chinmay	chinmay@gmail.com	8596457291	1998-08-17	male	A+	Adajan	12	900000.00	Morning	7	superadmin	2025-08-11 07:50:42.589222+00
64	PQRS	pqrs@gmail.com	3208741230	2021-12-11	male	AB+	abcdef	B.C.A	12000.00	Morning	11	superadmin	2025-08-29 10:47:49.840582+00
133	Viken More	viken@gmail.com	8526548520	1996-02-08	male	AB+	kliuop	B.tech	51000.00	Evening	15	superadmin	2025-08-29 10:48:11.062256+00
148	Sachin Tendulkar	sachin@gmail.com	8723657401	1996-02-01	male	B+	dfc	M.tech	42350.00	Evening	1	superadmin	2025-08-29 10:48:37.157322+00
114	Hardik Kulkarni	hardik@gmail.com	8520123697	1996-02-06	male	O-	39, Sarkar Villa, Piplod, Surat	M.A. M.Ed	52300.00	Evening	4	superadmin	2025-08-29 10:48:51.149469+00
\.


--
-- Data for Name: deleted_schools; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.deleted_schools (id, school_logo, school_name, email, phone, school_opening, school_type, education_board, school_medium, school_category, address, deleted_by_role, deleted_at) FROM stdin;
6	\N	LP SAVANI CANAL ROAD	lpsavani@gmail.com	5478931254	1999-03-11	Private	{State}	{Hindi}	{}	Adajan	superadmin	2025-07-24 10:06:38+00
2	\N	MANIDHAR	mani@gmail.com	7452639812	2025-08-07	Private	{State}	{Hindi}	{Primary,"Upper Primary"}	Adajan	superadmin	2025-08-07 11:01:59.328133+00
3	\N	Countryside	countryside@gmail.com	8574964152	2018-06-06	Government	{CBSE}	{English}	{Pre-Primary,Primary,"Upper Primary",Secondary,"Higher Secondary"}	Bhesan	superadmin	2025-08-07 11:54:59.314645+00
10	\N	Pramukh Vidhyalaya	pramukh@gmail.com	9267895578	2025-08-17	Government	{CBSE,State}	{English,"Regional Language"}	{Secondary,"Higher Secondary"}	Surat	superadmin	2025-08-10 10:03:17.369038+00
12	uploads/school_logos/school_12_1754846396.png	MIT	mit@gmail.com	7874569825	2025-08-11	Private	{CBSE,State,IGCSE}	{English,Hindi,"Regional Language"}	{Pre-Primary,Primary,"Upper Primary",Secondary,"Higher Secondary"}	Surat	superadmin	2025-08-10 17:48:47.101344+00
9	uploads/school_logos/school_9_1754640816.jpg	KV	kv@gmail.com	8529637418	2025-06-06	Government	{CBSE,State,IGCSE}	{English,Hindi,"Regional Language"}	{Pre-Primary,Primary,"Upper Primary",Secondary,"Higher Secondary"}	kribhco	superadmin	2025-08-10 18:09:35.142576+00
7	/BMC-SMS/uploads/school_logos/school_7_1754637550.png	GD	gd@gmail.com	8596743214	2025-08-06	Private	{CBSE,State,IGCSE}	{English,Hindi,"Regional Language"}	{Pre-Primary,Primary,"Upper Primary",Secondary,"Higher Secondary"}	Surat, Vesu	superadmin	2025-08-11 07:50:42.589222+00
\.


--
-- Data for Name: deleted_students; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.deleted_students (id, student_name, email, rollno, std, academic_year, dob, gender, blood_group, address, father_name, father_phone, mother_name, mother_phone, school_id, reason_for_leaving, deleted_by_role, deleted_at, transport_mode) FROM stdin;
1	Rahul Patel	rahul@gmail.com	1	5th	2024-2025	2005-02-02	Male	AB+	surat	harsh	6565548720	hemina	6523012304	3	\N	principal	2025-07-22 11:51:18+00	\N
3	devam parekh	devamparekh1200@gmail.com	9	11	2024-2025	2025-07-11	Male	B+	canal road	mukesh	9874522589	sunita	753685124	4	\N	student	2025-08-01 10:04:14+00	\N
13	vansh	vansh@gmail.com	15	12	2024-2025	2011-03-11	Female	B+	surat	girishbhai	5565615555	Sita Patel	5454454455	4	\N	teacher	2025-07-24 15:42:54+00	\N
16	mihir	mihir@gmail.com	15	11	2024-2025	2005-08-17	Male	B-	nutan	janak	5746895214	harshita	6352417898	4	\N	principal	2025-07-30 08:06:28+00	\N
18	devam parekh	devamparekh1200@gmail.com	69	10	2024-2025	2005-03-11	Male	AB-	LP savani	mukesh	852369741	vanita	9874563210	4	he is not a good student	student	2025-08-01 06:50:40+00	\N
19	devam parekh	devamparekh1200@gmail.com	69	10	2024-2025	2005-03-11	Female	A+	LP savani	mukesh	8523697415	vanita	1234567872	4	bye bye bye	student	2025-08-01 06:53:19+00	\N
20	devam parekh	devamparekh1200@gmail.com	69	10	2024-2025	2005-03-11	Male	O+	LP savani	mukesh	8523697415	vanita	9632587415	4	bye bye bye	student	2025-08-01 06:57:45+00	\N
33	assdfdgf	as@gmail.com	29	12th	2025-2026	2021-12-02	Male	AB+	sdd	dgf	963	we	9852	4	\N	principal	2025-08-07 12:11:36.908775+00	\N
32	asdfs	akash@gmail.com	29	11th	2024-2025	22020-02-01	Male	A-	v	c	55	d	88	4	\N	principal	2025-08-07 12:11:42.070897+00	\N
27	Meet	meet111@gmail.com	12	12	2024-2025	2003-06-06	Male	A-	surat	Sanket Patel	457896241	Sita Patel	457868547	4	\N	principal	2025-08-07 12:11:48.320871+00	\N
34	ewrt	a@gmail.com	29	11th	2025-2026	2020-02-12	Male	AB+	x	sdfd	8	x	85	4	\N	principal	2025-08-07 12:13:11.351813+00	\N
28	Palak Bhala	palak@gmail.com	28	11th	2025-2026	2022-02-12	Female	A-	adsfd	dsd	9856324477	sasf	9999999998	4	\N	principal	2025-08-07 12:13:27.073788+00	\N
21	Meet Patel	meet@gmail.com	27	11th	2025-2026	2020-02-02	Male	A+	ffdf	asd	8745632011	df	5896320147	4	\N	principal	2025-08-07 12:13:31.004808+00	\N
30	Akash Patani	akash1@gmail.com	28	12th	2025-2026	2020-02-01	Male	B-	cs	sasd	5963	sasd	58452	4	\N	principal	2025-08-07 12:13:35.126175+00	\N
60	Purvi Desai	purvi@gmail.com	28	11th	2025-2026	2005-12-09	Female	A+	abcd	Suresh	9874563200	Heena	7410236589	4	Testing Generate LC Panel	student	2025-08-08 12:17:17+00	\N
81	Purav Jha	purav@gmail.com	29	11	2025-2026	2005-05-05	Male	A-	Parvat Patiya	Pollard	9852001478	Smriti Mandhana	9852001478	4	\N	principal	2025-08-13 11:09:09.267472+00	\N
61	Purvi Desai	purvi@gmail.com	28	11	2025-2026	2005-12-08	Female	O-	abcd	Hitesh	9874100052	Asha	7452036987	4	Misbehaving	student	2025-08-13 13:09:40+00	\N
82	Purvi Desai	purvi@gmail.com	28	11	2025-2026	2005-08-08	Female	AB-	Rampura, Rander	Krishna	8741002369	Shrusti	7410236698	4	\N	principal	2025-08-13 11:15:02.943712+00	\N
84	Akash	akash@gmail.com	4	11	2025-2026	2005-10-15	Male	B+	Nana Varachha	Hiren	8541236985	Pooja	9652301479	4	\N	principal	2025-08-13 12:06:28.971187+00	\N
83	Purvi Desai	purvi@gmail.com	3	11	2025-2026	2005-08-08	Female	O-	Limbayat	sfdfgh	9852001478	a	8523698742	4	No manners how to behave in school	student	2025-08-13 14:08:08+00	\N
85	Ashutosh Sharma	ashutosh@gmail.com	3	11	2025-2026	2005-12-12	Male	B-	fsdg	sf	8741236659	ew	9852336987	4	Changing School	principal	2025-08-13 14:19:05+00	\N
95	jane	jane@gmail.com	10	11	2024-2025	2002-02-02	Female	B-	new york	philip	9514785236	Madeline	9632587415	4	\N	principal	2025-08-18 09:18:17.555194+00	\N
94	joe	joe@gmail.com	10	12	2024-2025	2000-01-01	Male	O-	LP savani	mahesh	852369741	vani	8526548624	4	\N	principal	2025-08-18 09:18:24.554185+00	\N
96	jane	jane@gmail.com	10	11	2024-2025	2002-02-02	Female	O-	new york	philip	9514785236	Madeline	9513578965	4	\N	principal	2025-08-18 09:20:46.558217+00	\N
88	Krisiv	krishiv@gmail.com	1	Junior	2025-2026	2019-08-12	Male	O-	fs	sf	8523697410	dsf	9852147896	4	\N	principal	2025-08-18 09:21:05.389068+00	\N
89	Ankit Parekh	ankit@gmail.com	2	Junior	2025-2026	2011-06-19	Male	O-	s	Karan	9852366630	Prakruti	8741002369	4	\N	principal	2025-08-18 09:21:09.655949+00	\N
90	Khushi Dholakia	khushi@gmail.com	3	Junior	2025-2026	2011-04-23	Female	O-	dsfd	swre	8520014788	f	7410236589	4	\N	principal	2025-08-18 09:21:13.89119+00	\N
98	emma stone	emma@gmail.com	11	10	2024-2025	2003-03-03	Female	O+	new york	jeff	9632587415	krista	8523654752	4	\N	principal	2025-08-18 09:57:15.567451+00	\N
97	jane	jane@gmail.com	10	10	2024-2025	2002-02-02	Female	O-	new york	philip	9514785236	Madeline	7531598654	4	\N	principal	2025-08-18 10:08:24.019986+00	\N
99	peter parker	peter@gmail.com	15	10	2024-2025	2005-05-04	Male	B+	new york	ben	9632587415	may	9512589630	4	\N	principal	2025-08-18 10:08:32.488879+00	\N
100	peter parker	peter@gmail.com	15	10	2024-2025	2005-05-04	Male	O-	new york	ben	9632587415	may	8526547852	4	Changing School	principal	2025-08-21 13:50:41+00	\N
\.


--
-- Data for Name: deleted_teachers; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.deleted_teachers (id, teacher_name, email, phone, gender, dob, blood_group, address, school_id, qualification, subject, language_known, salary, std, experience, batch, class_teacher, class_teacher_std, deleted_by_role, deleted_at) FROM stdin;
1	JAY	jay@gmail.com	5674298791	Male	2005-11-03	AB-	0	3	BA	Account	Hindi	500000.00	{Nursery,Junior,1}	5	Evening	f	\N	principal	2025-07-22 11:51:18+00
12	ram	ram@gmail.com	5545875655	Male	2005-03-11	AB+	surat	4	MA	English	English	100000.00	{5,6}	5	Morning	f	\N	principal	2025-07-24 09:34:16+00
14	Hemant	hemant@gmail.com	5674231495	Male	2000-03-11	AB+	Surat	4	MA	account	English	150000.00	{11,12}	5	Morning	t	12	principal	2025-07-25 08:19:17+00
17	Yug gandhi	yug@gmail.com	5874693214	Male	2005-03-11	B-	surat	4	MA	maths	English	250000.00	{7,9}	5	Morning	t	7	principal	2025-07-30 08:10:40+00
55	Raj Purohit	raj@gmail.com	7896541023	Male	1999-12-05	O+	bfaaqw	4	B.A	Physics	English, Hindi, Gujarati	28500.00	{12}	4	Evening	f	\N	principal	2025-08-08 15:29:26.931328+00
103	Andrew Garfield	andrew@gmail.com	9156787656	Male	1980-08-20	O+	new york	4	M.A	Science	English	80000.00	{11,12}	20	Morning	t	10	principal	2025-08-18 10:28:06.061891+00
118	Andrew Garfield	andrew@gmail.com	9156787656	Male	1980-08-20	O+	new york	4	M.A	Science	English	8000.00	{11,12}	20	Evening	t	12	principal	2025-08-20 12:05:10.114155+00
123	Viraj Gelani	viraj@gmail.com	9852142011	Male	1997-01-01	O-	abcd	4	B.tech	Gujarati	English, Hindi, Gujarati	23000.00	{12}	5	Evening	t	12	principal	2025-08-29 09:50:01.18468+00
146	Prince Parmar	prince@gmail.com	7410235897	Male	1997-02-06	O-	pqrs	4	M.A. M.Ed	Economics	English, Hindi, Gujarati	33000.00	{12}	4	Morning	f	\N	principal	2025-08-29 10:43:38.894+00
141	Tina Sen	tina@gmail.com	8576548525	Female	1991-01-06	O-	acbd	4	M.A. M.Ed	Music	English, Hindi	12400.00	{8}	2	Morning	f	\N	principal	2025-08-29 10:43:52.437129+00
106	Yug Pandya	yug@gmail.com	7410025633	Male	1997-06-07	B+	V-101, Amrut Heights, Jakatnaka, Surat	4	B.A.	Business Studies	English, Hindi, Gujarati	23000.00	{12}	3	Morning	f	\N	principal	2025-08-29 10:44:09.427441+00
137	Avantika Sironi	avantika@gmail.com	9924976504	Female	1988-12-04	O+	pqrs	4	M.A. M.Ed	English	English, Gujarati	21000.00	{8}	4	Morning	f	\N	principal	2025-08-29 10:44:21.189216+00
124	Ranjit Chaudhari	ranjit@gmail.com	9852142012	Male	1999-02-02	O-	abcd	4	B.ed	Sanskrit	Hindi, Sanskrit	21500.00	{8}	4	Morning	f	\N	principal	2025-08-29 10:44:31.82957+00
119	Andrew Garfield	andrew@gmail.com	9156787656	Male	1980-08-20	AB-	new york	4	M.A	Science	English	8000.00	{10,11,12}	20	Morning	t	12	principal	2025-08-29 10:51:41.582632+00
\.


--
-- Data for Name: drivers; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.drivers (id, school_id, driver_name, phone_number, license_number, created_at) FROM stdin;
1	4	Rameshbhai Patel	9879012345	GJ0520180011223	2025-08-19 16:26:15.63852+00
2	4	Sanjay Kumar Shah	9925054321	GJ0520150098765	2025-08-19 16:33:07.895103+00
3	4	Mahesh Solanki	9727767890	GJ0520190045678	2025-08-19 16:33:46.779818+00
4	4	Shivam Dube	8520369874	GJ-1920219632014	2025-08-21 12:23:20.288682+00
\.


--
-- Data for Name: exam_timetables; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.exam_timetables (id, principal_id, school_id, title, description, file_path, original_filename, created_at) FROM stdin;
1	10	4	Term 1 Exam Timetable	time table for term 1	/BMC-SMS/uploads/timetables/examtt_688b5a0eec5341.29191278_INTERNSHIP REGISTRATION FORM Sujal.pdf	INTERNSHIP REGISTRATION FORM Sujal.pdf	2025-07-31 11:57:02+00
2	10	4	Term 2 Exam Timetable	dasf	/BMC-SMS/uploads/timetables/examtt_688b5f8df01541.57695077_INTERNSHIP REGISTRATION FORM JAY (1).pdf	INTERNSHIP REGISTRATION FORM JAY (1).pdf	2025-07-31 12:20:30+00
3	10	4	Final Exam Timetable	final	/BMC-SMS/uploads/timetables/examtt_688cf86a003ef1.98578217_UNIT 1 AWT.pdf	UNIT 1 AWT.pdf	2025-08-01 17:24:58+00
4	10	4	Term 1 Exam Timetable	Testing \r\n\r\nChecked by -\r\nMeet Patel	uploads/timetables/examtt_689622dbef4860.85724111_Leaving_Certificate_PurviDesai.pdf	Leaving_Certificate_Purvi Desai.pdf	2025-08-08 16:16:27.131438+00
5	10	4	Term 2 Exam Timetable	Exam starting from 01-09-2025\r\n\r\nBest of Luck	uploads/timetables/examtt_689c8750f3f553.63311884_Leaving_Certificate_AshutoshSharma.pdf	Leaving_Certificate_Ashutosh Sharma.pdf	2025-08-13 12:38:39.178629+00
6	10	4	Term 1 Exam Timetable	ds	uploads/timetables/examtt_689c8d42da5403.20596457_Leaving_Certificate_AshutoshSharma.pdf	Leaving_Certificate_Ashutosh Sharma.pdf	2025-08-13 13:04:01.127936+00
7	10	4	Final Exam Timetable	Exams are coming	uploads/timetables/examtt_689c8fe0d99042.63090597_Leaving_Certificate_AshutoshSharma.pdf	Leaving_Certificate_Ashutosh Sharma.pdf	2025-08-13 13:15:11.147442+00
8	10	4	Term 2 Exam Timetable	Checkingggg	uploads/timetables/examtt_689da5f77605d8.68631512_Leaving_Certificate_AshutoshSharma.pdf	Leaving_Certificate_Ashutosh Sharma.pdf	2025-08-14 09:01:41.931191+00
9	10	4	Final Exam Timetable	Meet Exam	uploads/timetables/examtt_689dab767af0b2.41853237_MUSTIMPROVEMENT.docx.pdf	MUST IMPROVEMENT.docx.pdf	2025-08-14 09:25:09.722633+00
10	10	4	Term 2 Exam Timetable	Term-2	uploads/timetables/examtt_689dab9f738337.51326470_MUSTIMPROVEMENT.docx.pdf	MUST IMPROVEMENT.docx.pdf	2025-08-14 09:25:50.650348+00
11	10	4	Term 1 Exam Timetable	Devam 	uploads/timetables/examtt_689dabdaa06f86.91396079_UNIT2APPLET.pdf	UNIT 2 APPLET.pdf	2025-08-14 09:26:49.835349+00
\.


--
-- Data for Name: holidays; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.holidays (id, school_id, holiday_date, description, created_at) FROM stdin;
3	4	2025-08-15	Independence Day	2025-08-14 09:55:41.302475+00
4	4	2025-08-16	Janmashtami	2025-08-14 09:56:13.813809+00
5	4	2025-08-27	Ganesh Chaturthi	2025-08-14 09:56:50.381009+00
8	4	2025-10-20	Diwali	2025-08-14 10:01:45.075059+00
10	4	2025-11-05	Guru Nanak Jayanti	2025-08-14 10:02:26.36083+00
11	4	2025-12-25	Christmas	2025-08-14 10:03:03.01183+00
12	4	2026-01-26	Republic Day	2025-08-14 10:04:51.6299+00
13	4	2026-03-04	Holi	2025-08-14 10:05:29.140655+00
14	4	2026-03-21	Ramzan	2025-08-14 10:06:09.671602+00
16	4	2026-03-26	Ram Navami	2025-08-14 10:06:42.942566+00
17	4	2026-03-31	Mahavir Jayanti	2025-08-14 10:07:04.605622+00
18	4	2026-04-03	Good Friday	2025-08-14 10:07:27.432363+00
19	4	2026-05-01	Buddh Purnima	2025-08-14 10:08:04.10821+00
20	4	2026-05-27	Bakri Eid	2025-08-14 10:08:25.472317+00
21	4	2026-06-26	Muharram	2025-08-14 10:09:30.533061+00
22	4	2026-08-15	Independence Day	2025-08-14 10:10:09.795247+00
23	4	2026-08-26	Milad un-Nabi/Eid-e-Milad	2025-08-14 10:10:49.930662+00
24	4	2025-09-05	Milad un-Nabi/Eid-e-Milad	2025-08-14 10:11:33.066012+00
26	4	2026-09-04	Janmashtami	2025-08-14 10:12:30.044408+00
27	4	2026-10-02	Mahatma Gandhi Jayanti	2025-08-14 10:14:06.016066+00
28	4	2026-10-20	Dussehra	2025-08-14 10:14:43.224104+00
29	4	2026-11-08	Diwali	2025-08-14 10:15:03.491893+00
30	4	2026-11-24	Guru Nanak Jayanti	2025-08-14 10:15:27.372317+00
31	4	2026-12-25	Christmas	2025-08-14 10:15:49.640605+00
32	4	2025-10-02	Mahatma Gandhi Jayanti/Dussehra	2025-08-14 10:17:24.582786+00
\.


--
-- Data for Name: incentives; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.incentives (id, school_id, incentive_name, percentage, created_at, type) FROM stdin;
6	4	providend fund	5.00	2025-08-25 11:33:04.094454+00	Subtraction
7	4	health insuarance	7.00	2025-08-25 11:33:16.655196+00	Addition
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
22	6	2025-09-13	2025-09-15	Testing the Approve and Reject \r\n\r\nChecked by - \r\nMeet Patel	Full Day	Approved	2025-08-08 11:05:01.38207+00	\N
39	6	2025-08-14	2025-08-28	we	Full Day	Approved	2025-08-13 13:07:12.713394+00	\N
23	6	2025-08-09	2025-08-10	Rakshabandhan	Full Day	Rejected	2025-08-08 12:01:26.125477+00	No, finish your pending work or else don't take salary of this month.
24	6	2025-08-09	2025-08-11	timepass	Full Day	Approved	2025-08-08 12:03:50.43374+00	\N
25	6	2025-08-13	2025-08-16	Update past and future date blur based on "From Date" selected it was not working previously.\r\n\r\nChecked by -\r\nMeet Patel	Full Day	Approved	2025-08-08 16:28:17.179174+00	\N
26	6	2025-08-09	2025-08-09	Testing "Approve" and "Reject" button	First Half	Approved	2025-08-08 16:36:15.670932+00	\N
28	54	2025-08-11	2025-08-11	Re-testing	Second Half	Approved	2025-08-08 16:38:43.05514+00	\N
29	54	2025-08-12	2025-08-14	abcd	Full Day	Rejected	2025-08-08 16:39:04.446811+00	Testing done of Leave Panel
27	54	2025-08-11	2025-08-11	Re-testing	Second Half	Approved	2025-08-08 16:38:42.93299+00	\N
30	6	2025-08-12	2025-08-17	I want rest	Full Day	Approved	2025-08-10 09:54:15.653659+00	\N
31	6	2025-08-12	2025-08-12	Dr. Appointment	Second Half	Approved	2025-08-10 15:29:03.827618+00	\N
32	6	2025-08-12	2025-08-24	Testing (path issue notification due to file name changed_leave app).\r\n	Full Day	Rejected	2025-08-10 18:19:48.306803+00	Still path issue
34	54	2025-08-13	2025-08-26	Trip	Full Day	Approved	2025-08-12 06:04:51.758534+00	\N
33	6	2025-08-13	2025-08-20	Testing, notification path wrong 	Full Day	Approved	2025-08-11 08:57:55.366333+00	\N
35	6	2025-08-14	2025-08-17	going ON date	Full Day	Approved	2025-08-12 07:58:05.980713+00	\N
36	6	2025-08-14	2025-08-28	leave	Full Day	Rejected	2025-08-12 08:06:59.902474+00	sorry to much work
37	6	2025-08-16	2025-08-20	addsfdg	Full Day	Rejected	2025-08-12 08:40:08.371206+00	no
38	6	2025-08-17	2025-08-27	weekly off	Full Day	Approved	2025-08-12 08:44:50.093328+00	\N
40	6	2025-08-18	2025-08-27	wre	Full Day	Approved	2025-08-14 08:54:29.627549+00	\N
41	6	2025-08-27	2025-08-28	e	Full Day	Approved	2025-08-14 08:57:14.360654+00	\N
42	6	2025-08-15	2025-08-21	ghg	Full Day	Approved	2025-08-14 09:32:44.540648+00	\N
43	6	2025-08-16	2025-08-18	HIII	Full Day	Approved	2025-08-14 09:35:12.904105+00	\N
44	6	2025-08-14	2025-08-14	wregh	First Half	Rejected	2025-08-14 09:38:35.323463+00	mari marji
45	6	2025-08-16	2025-08-18	Maari marji	Full Day	Approved	2025-08-14 10:07:15.58367+00	\N
46	6	2025-08-16	2025-08-18	hiii	Full Day	Approved	2025-08-14 10:15:11.369011+00	\N
47	6	2025-08-16	2025-08-17	hii	Full Day	Approved	2025-08-14 10:17:36.787857+00	\N
48	6	2025-08-19	2025-08-21	HII	Full Day	Rejected	2025-08-18 10:17:34.114742+00	NOO
\.


--
-- Data for Name: librarian; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.librarian (id, librarian_image, librarian_name, school_id, email, password, phone, dob, gender, blood_group, address, qualification, salary, batch, date_of_joining, transport_mode, self_transport_mode, vehicle_number, license_number, stop_id) FROM stdin;
53	/BMC-SMS/pages/librarian/uploads/librarian_6895b77a58c4d1.07997847.jpg	Rohit Singh	1	rohit@gmail.com	$2y$10$CX5ZFB02c4ncgdCCy9WplezqcHT0jAEaAAaI6uqr6fQHvj0FoSL8m	7412036985	2021-12-12	Male	O-	abcdefg	MBA	12000.00	Morning	\N	\N	\N	\N	\N	\N
107	\N	Prakash Jadav	1	prakash@gmail.com	$2y$10$pub4gPRk6EW7Il4jxjo2Ouc4F87zBPcLdf9p12/FYFppQYSX8yTai	9622001456	1998-08-12	Male	A-	101, Shivalik Row House, Surat	B.A	15000.00	Evening	\N	\N	\N	\N	\N	\N
108	/BMC-SMS/pages/librarian/uploads/librarian_68a47ee5dd0421.90308632.jpg	Santosh Pandit	6	santosh@gmai.com	$2y$10$JM1uBHaBUqobLR9miaICKO8tuD7q2LKAQysccyTcY7Tt0SH5sUAK.	8420369874	1997-04-03	Male	AB-	abcd	B.A.	12500.00	Morning	\N	\N	\N	\N	\N	\N
36	/BMC-SMS/pages/librarian/uploads/librarian_68b16e61daea87.50245944.jpeg	Devang Odedra	4	devang@gmail.com	$2y$10$zmDytTauKLi/cqAY89QgT.CyVW7b4lgK.rwFVPdJbZf4zIsHGyYdu	9187567898	1981-01-01	Male	A+	Canal Road	B.A	40000.00	Morning	\N	Self Transport	Bike	GJ-04-JE-8530	GJ-0420113658730	\N
147	/BMC-SMS/pages/librarian/uploads/librarian_68b16ee1e8ebe8.42276774.jpg	Dinesh Karthik	4	dinesh@gmail.com	$2y$10$e/ba0l3WZPzpCiKblkuMLOmVYTrB4k5WyrK/Zpe8WzyfoylNbCuIq	9852317789	1998-02-02	Male	O-	nkm	M.Lib.I.Sc.	12500.00	Evening	2025-08-29	School Transport	\N	\N	\N	4
\.


--
-- Data for Name: librarian_attendance; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.librarian_attendance (attendance_id, librarian_id, school_id, attendance_date, status, remark, marked_by_user_id, updated_at) FROM stdin;
2	23	4	2025-08-28	Present	\N	10	2025-08-07 09:48:15.363183+00
4	23	4	2025-08-14	Present	\N	10	2025-08-07 14:52:06.686115+00
5	23	4	2025-08-22	Present	\N	10	2025-08-07 14:52:29.217643+00
3	23	4	2025-08-07	Absent	\N	10	2025-08-07 09:51:45.114329+00
8	23	4	2025-08-05	Absent	\N	10	2025-08-07 15:39:56.305528+00
6	23	4	2025-08-01	Absent	\N	10	2025-08-07 15:37:26.087683+00
1	23	4	2025-08-06	Leave	\N	10	2025-08-06 11:07:08+00
11	36	4	2025-08-08	Present	\N	10	2025-08-08 09:31:55.876017+00
12	57	4	2025-08-08	Leave	\N	10	2025-08-08 09:31:55.876017+00
20	36	4	2025-08-01	Present	\N	10	2025-08-13 10:24:59.514273+00
21	57	4	2025-08-01	Absent	\N	10	2025-08-13 10:24:59.514273+00
22	36	4	2025-08-02	Present	\N	10	2025-08-13 10:46:59.650005+00
23	57	4	2025-08-02	Present	\N	10	2025-08-13 10:46:59.650005+00
24	36	4	2025-08-04	Present	\N	10	2025-08-13 10:47:07.802742+00
25	57	4	2025-08-04	Present	\N	10	2025-08-13 10:47:07.802742+00
26	36	4	2025-08-05	Absent	\N	10	2025-08-13 10:47:17.550184+00
27	57	4	2025-08-05	Present	\N	10	2025-08-13 10:47:17.550184+00
28	36	4	2025-08-06	Present	\N	10	2025-08-13 10:47:33.35301+00
29	57	4	2025-08-06	Absent	\N	10	2025-08-13 10:47:38.228509+00
30	36	4	2025-08-07	Absent	\N	10	2025-08-13 10:47:51.529448+00
31	57	4	2025-08-07	Absent	\N	10	2025-08-13 10:47:51.529448+00
32	36	4	2025-08-09	Present	\N	10	2025-08-13 10:48:00.747698+00
33	57	4	2025-08-09	Present	\N	10	2025-08-13 10:48:00.747698+00
36	36	4	2025-08-11	Present	\N	10	2025-08-13 10:48:22.458108+00
37	57	4	2025-08-11	Present	\N	10	2025-08-13 10:48:22.458108+00
16	36	4	2025-08-12	Present	\N	10	2025-08-13 09:59:59.12059+00
39	57	4	2025-08-12	Present	\N	10	2025-08-13 10:48:29.885212+00
15	57	4	2025-08-13	Leave	\N	10	2025-08-13 09:58:48.320333+00
14	36	4	2025-08-13	Present	\N	10	2025-08-13 09:58:48.320333+00
44	36	4	2025-08-14	Present	\N	10	2025-08-18 08:32:42.162389+00
45	57	4	2025-08-14	Present	\N	10	2025-08-18 08:32:42.162389+00
46	36	4	2025-08-18	Present	\N	10	2025-08-18 08:32:47.76961+00
47	57	4	2025-08-18	Present	\N	10	2025-08-18 08:32:47.76961+00
48	36	4	2025-08-19	Present	\N	10	2025-08-19 08:05:11.913458+00
49	57	4	2025-08-19	Absent	\N	10	2025-08-19 08:05:11.913458+00
52	36	4	2025-08-20	Present	\N	10	2025-08-20 09:51:36.481657+00
53	57	4	2025-08-20	Present	\N	10	2025-08-20 09:51:36.481657+00
54	36	4	2025-08-21	Present	\N	10	2025-08-21 12:24:50.607458+00
55	130	4	2025-08-21	Present	\N	10	2025-08-21 12:24:50.607458+00
56	36	4	2025-08-22	Present	\N	10	2025-08-25 07:50:51.236814+00
57	130	4	2025-08-22	Present	\N	10	2025-08-25 07:50:51.236814+00
58	36	4	2025-08-23	Present	\N	10	2025-08-25 07:50:59.056568+00
59	130	4	2025-08-23	Present	\N	10	2025-08-25 07:50:59.056568+00
60	36	4	2025-08-25	Present	\N	10	2025-08-25 07:51:06.389498+00
61	130	4	2025-08-25	Present	\N	10	2025-08-25 07:51:06.389498+00
62	36	4	2025-08-26	Present	\N	10	2025-08-26 16:17:35.243827+00
63	130	4	2025-08-26	Present	\N	10	2025-08-26 16:17:35.243827+00
\.


--
-- Data for Name: librarian_leave_applications; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.librarian_leave_applications (id, librarian_id, from_date, to_date, reason, leave_type, status, applied_on, rejection_reason) FROM stdin;
1	36	2025-08-15	2025-08-21	Testing librarian leave panel\r\nImplemented on 14-08-2025	Full Day	Approved	2025-08-14 08:39:06.80083+00	\N
2	36	2025-08-22	2025-08-28	v	Full Day	Approved	2025-08-21 13:03:55.056673+00	\N
\.


--
-- Data for Name: librarian_payroll; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.librarian_payroll (id, librarian_id, payroll_user_id, school_id, salary_month, salary_year, base_salary, total_working_days, present_days, absent_days, deduction_amount, total_incentives, net_salary_paid, payment_date, status) FROM stdin;
1	36	134	4	8	2025	40000.00	23	17.0	2	3478.26	2800.00	32365.22	2025-08-26 08:39:47.997126+00	Paid
\.


--
-- Data for Name: librarian_timings; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.librarian_timings (timing_id, librarian_id, day_of_week, opens_at, closes_at, is_closed) FROM stdin;
43	53	Monday	09:00:00	17:00:00	f
44	53	Tuesday	09:00:00	17:00:00	f
45	53	Wednesday	09:00:00	17:00:00	f
46	53	Thursday	09:00:00	17:00:00	f
47	53	Friday	09:00:00	17:00:00	f
48	53	Saturday	09:00:00	17:00:00	f
49	53	Sunday	\N	\N	t
141	107	Monday	09:00:00	17:00:00	f
142	107	Tuesday	09:00:00	17:00:00	f
143	107	Wednesday	09:00:00	17:00:00	f
144	107	Thursday	09:00:00	17:00:00	f
145	107	Friday	09:00:00	17:00:00	f
146	107	Saturday	09:00:00	17:00:00	f
147	107	Sunday	\N	\N	t
162	108	Monday	09:00:00	17:00:00	f
163	108	Tuesday	09:00:00	17:00:00	f
164	108	Wednesday	09:00:00	17:00:00	f
165	108	Thursday	09:00:00	17:00:00	f
166	108	Friday	09:00:00	17:00:00	f
167	108	Saturday	09:00:00	17:00:00	f
168	108	Sunday	\N	\N	t
190	147	Monday	09:00:00	17:00:00	f
191	147	Tuesday	09:00:00	17:00:00	f
192	147	Wednesday	09:00:00	17:00:00	f
193	147	Thursday	09:00:00	17:00:00	f
194	147	Friday	09:00:00	17:00:00	f
195	147	Saturday	09:00:00	17:00:00	f
196	147	Sunday	\N	\N	t
29	36	Monday	07:00:00	12:00:00	f
30	36	Tuesday	07:00:00	12:00:00	f
31	36	Wednesday	07:00:00	12:00:00	f
32	36	Thursday	07:00:00	12:00:00	f
33	36	Friday	07:00:00	12:00:00	f
34	36	Saturday	07:00:00	12:00:00	f
35	36	Sunday	\N	\N	t
\.


--
-- Data for Name: messages; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.messages (id, sender_id, receiver_id, message_text, "timestamp", is_read, file_path, file_type, sender_role, receiver_role, original_filename) FROM stdin;
159	6	149	hyy	2025-08-31 17:27:12.552859+00	f	\N	\N	\N	\N	\N
160	150	6	Brijesh samji gayo	2025-08-31 17:45:53.408874+00	t	\N	\N	\N	\N	\N
161	6	150	heeee, hamko shikha rahiya hai	2025-08-31 17:46:36.968078+00	t	\N	\N	\N	\N	\N
162	6	150	see this	2025-09-01 10:26:57.409506+00	t	\N	\N	\N	\N	\N
163	6	150	see	2025-09-01 11:02:19.939283+00	t	uploads/messages/msg_68b57d3cf041f_1756724540.pdf	application/pdf	teacher	student	\N
164	6	150	see	2025-09-01 11:02:33.36774+00	t	uploads/messages/msg_68b57d4a63921_1756724554.pdf	application/pdf	teacher	student	\N
165	6	150		2025-09-01 11:04:13.825861+00	t	uploads/messages/msg_68b57daed27b8_1756724654.pdf	application/pdf	teacher	student	\N
166	6	150		2025-09-01 11:22:30.266934+00	t	uploads/messages/msg_68b581f73b696_1756725751.pdf	application/pdf	teacher	student	Sanskar_Bharti_Vidyalay_Enrollment_report.pdf
167	150	6		2025-09-01 11:39:06.770725+00	t	uploads/messages/msg_68b585dba8642_1756726747.pdf	application/pdf	student	teacher	All_School_Library_Report.pdf
168	6	150	hyyy	2025-09-01 11:44:51.60304+00	t	uploads/messages/msg_68b587347a5a0_1756727092.pdf	application/pdf	teacher	student	All_School_Library_Report (1).pdf
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
2	6	4	11}	hi	testing	\N	\N	2025-08-08 09:27:12.19174+00
8	6	4	{10	Test	ab	\N	\N	2025-08-12 06:02:53.265172+00
9	6	4	11}	Test	abb	\N	\N	2025-08-12 06:03:16.69628+00
10	6	4	{10	gj	fop	\N	\N	2025-08-12 09:12:55.567868+00
11	6	4	{10	fv	xvc	\N	\N	2025-08-14 11:33:57.937922+00
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
14	8	Testing tt	asfdgfb	\N	\N	2025-08-07 16:07:31.662108+00
15	8	Hiii	How are You	\N	\N	2025-08-08 04:26:38.655728+00
16	8	hii	testing againnnnnnnn	\N	\N	2025-08-08 11:59:47.477884+00
17	8	hii	testinggggggggggggggggggggg	\N	\N	2025-08-08 12:04:39.464012+00
18	8	once more	add	\N	\N	2025-08-08 12:10:25.910866+00
19	8	dg	oe	\N	\N	2025-08-12 06:28:03.757251+00
20	8	fm	dl	\N	\N	2025-08-12 06:28:17.508115+00
21	8	sf	sd	\N	\N	2025-08-12 08:48:43.279865+00
22	8	ASAP	Sir i got your notice, path is correct now	/BMC-SMS/pages/bmc/uploads/notice_689dc361271b88.92258338_school_2.jpg	school_2.jpg	2025-08-14 11:07:11.865209+00
23	8	HII	How Are You	\N	\N	2025-08-18 07:31:15.795683+00
24	8	Notice	Hi, did you received notice ?	\N	\N	2025-08-21 09:09:37.518602+00
25	8	Quarterly General Meeting	Dear Principal,\r\nThere is a Quarterly General Meeting on -\r\nDate :- 4th Sept, 2025\r\nTime :- 5:00 p.m. onwards (followed by dinner)\r\nVenue ;- SMS Conference Hall, Near Ram Temple, Vesu, Surat\r\n\r\nLooking forward to assist you.\r\n\r\nRegards\r\nMeet Patel\r\n(Chairman, BMC-SMS)	\N	\N	2025-08-21 09:20:01.299446+00
26	8	hii	25-08-2025	\N	\N	2025-08-25 08:05:17.076391+00
27	8	hiii	once moreeeeeeeeeeeeeeeeeeeee	\N	\N	2025-08-25 08:11:53.750122+00
28	8	Annual General Meeting	Venue : K.P Commerce Conference Hall\r\nDate : 4th September, 2025	\N	\N	2025-08-25 10:45:55.836037+00
29	8	Re-scheduling AGM	Dear Principal date of AGM has been changed, please note it down \r\n\r\nRe-scheduled Date : 3rd September, 2025	\N	\N	2025-08-25 10:58:07.693808+00
\.


--
-- Data for Name: notifications; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.notifications (id, user_id, message, link, is_read, created_at, type) FROM stdin;
435	10	New leave request from Devang Odedra	pages/principal/librarian_leave_management.php	t	2025-08-14 08:39:06.80083+00	librarian_leave_request
455	6	Your leave application has been Approved.	pages/teacher/teacher_leave_management.php	t	2025-08-14 09:01:10.492705+00	leave_status
721	36	Your leave application has been Approved.	pages/librarian/my_leave_management.php	t	2025-08-31 17:17:06.968202+00	librarian_leave_status
476	6	New Exam Timetable: Final Exam Timetable	pages/teacher/view_exam_timetable.php	t	2025-08-14 09:25:09.722633+00	exam_timetable
461	6	New Exam Timetable: Term 2 Exam Timetable	pages/teacher/view_exam_timetable.php	t	2025-08-14 09:01:41.931191+00	exam_timetable
436	36	Your leave application has been Approved.	pages/librarian/my_leave_management.php	t	2025-08-14 08:39:31.727446+00	librarian_leave_status
440	70	New notice from Principal: Checking...	pages/teacher/view_notice.php	f	2025-08-14 08:51:46.93638+00	school_notice
441	49	New notice from Principal: Checking...	pages/teacher/view_notice.php	f	2025-08-14 08:51:49.402466+00	school_notice
443	12	New notice from Principal: Checking...	pages/teacher/view_notice.php	f	2025-08-14 08:51:55.720978+00	school_notice
442	6	New notice from Principal: Checking...	pages/teacher/view_notice.php	t	2025-08-14 08:51:52.539509+00	school_notice
452	10	New leave request from Meet Patel	pages/principal/teacher_leave_management.php	t	2025-08-14 08:54:29.627549+00	leave_request
453	6	Your leave application has been Approved.	pages/teacher/teacher_leave_management.php	t	2025-08-14 08:55:39.715223+00	leave_status
454	10	New leave request from Meet Patel	pages/principal/teacher_leave_management.php	t	2025-08-14 08:57:14.360654+00	leave_request
459	70	New Exam Timetable: Term 2 Exam Timetable	pages/teacher/view_exam_timetable.php	f	2025-08-14 09:01:41.931191+00	exam_timetable
460	49	New Exam Timetable: Term 2 Exam Timetable	pages/teacher/view_exam_timetable.php	f	2025-08-14 09:01:41.931191+00	exam_timetable
462	12	New Exam Timetable: Term 2 Exam Timetable	pages/teacher/view_exam_timetable.php	f	2025-08-14 09:01:41.931191+00	exam_timetable
474	70	New Exam Timetable: Final Exam Timetable	pages/teacher/view_exam_timetable.php	f	2025-08-14 09:25:09.722633+00	exam_timetable
475	49	New Exam Timetable: Final Exam Timetable	pages/teacher/view_exam_timetable.php	f	2025-08-14 09:25:09.722633+00	exam_timetable
477	12	New Exam Timetable: Final Exam Timetable	pages/teacher/view_exam_timetable.php	f	2025-08-14 09:25:09.722633+00	exam_timetable
439	54	New notice from Principal: Checking...	pages/teacher/view_notice.php	t	2025-08-14 08:51:44.463406+00	school_notice
458	54	New Exam Timetable: Term 2 Exam Timetable	pages/teacher/view_exam_timetable.php	t	2025-08-14 09:01:41.931191+00	exam_timetable
473	54	New Exam Timetable: Final Exam Timetable	pages/teacher/view_exam_timetable.php	t	2025-08-14 09:25:09.722633+00	exam_timetable
471	52	New Exam Timetable: Final Exam Timetable	pages/teacher/view_exam_timetable.php	t	2025-08-14 09:25:09.722633+00	exam_timetable
456	52	New Exam Timetable: Term 2 Exam Timetable	pages/teacher/view_exam_timetable.php	t	2025-08-14 09:01:41.931191+00	exam_timetable
437	52	New notice from Principal: Checking...	pages/teacher/view_notice.php	t	2025-08-14 08:51:38.497158+00	school_notice
438	51	New notice from Principal: Checking...	pages/teacher/view_notice.php	t	2025-08-14 08:51:41.319196+00	school_notice
472	51	New Exam Timetable: Final Exam Timetable	pages/teacher/view_exam_timetable.php	t	2025-08-14 09:25:09.722633+00	exam_timetable
457	51	New Exam Timetable: Term 2 Exam Timetable	pages/teacher/view_exam_timetable.php	t	2025-08-14 09:01:41.931191+00	exam_timetable
722	6	Your salary for August 2025 amounting to ₹80,260.87 has been processed.	pages/teacher/view_salary_history.php	t	2025-08-31 17:29:35.804271+00	salary
489	70	New Exam Timetable: Term 2 Exam Timetable	pages/teacher/view_exam_timetable.php	f	2025-08-14 09:25:50.650348+00	exam_timetable
490	49	New Exam Timetable: Term 2 Exam Timetable	pages/teacher/view_exam_timetable.php	f	2025-08-14 09:25:50.650348+00	exam_timetable
492	12	New Exam Timetable: Term 2 Exam Timetable	pages/teacher/view_exam_timetable.php	f	2025-08-14 09:25:50.650348+00	exam_timetable
520	6	Your leave application has been Approved.	pages/teacher/teacher_leave_management.php	t	2025-08-14 09:33:02.263553+00	leave_status
501	6	New notice from Principal: Hiii...	pages/teacher/view_notice.php	t	2025-08-14 09:25:54.991901+00	school_notice
521	10	New leave request from Meet Patel	pages/principal/teacher_leave_management.php	t	2025-08-14 09:35:12.904105+00	leave_request
491	6	New Exam Timetable: Term 2 Exam Timetable	pages/teacher/view_exam_timetable.php	t	2025-08-14 09:25:50.650348+00	exam_timetable
505	70	New Exam Timetable: Term 1 Exam Timetable	pages/teacher/view_exam_timetable.php	f	2025-08-14 09:26:49.835349+00	exam_timetable
506	49	New Exam Timetable: Term 1 Exam Timetable	pages/teacher/view_exam_timetable.php	f	2025-08-14 09:26:49.835349+00	exam_timetable
508	12	New Exam Timetable: Term 1 Exam Timetable	pages/teacher/view_exam_timetable.php	f	2025-08-14 09:26:49.835349+00	exam_timetable
522	6	Your leave application has been Approved.	pages/teacher/teacher_leave_management.php	t	2025-08-14 09:36:04.231845+00	leave_status
507	6	New Exam Timetable: Term 1 Exam Timetable	pages/teacher/view_exam_timetable.php	t	2025-08-14 09:26:49.835349+00	exam_timetable
519	10	New leave request from Meet Patel	pages/principal/teacher_leave_management.php	t	2025-08-14 09:32:44.540648+00	leave_request
523	10	New leave request from Meet Patel	pages/principal/teacher_leave_management.php	t	2025-08-14 09:38:35.323463+00	leave_request
525	6	New notice from Principal: HIIIIIIIIII...	pages/teacher/view_notice.php	t	2025-08-14 09:39:21.126868+00	school_notice
532	6	New notice from Principal: fenil...	pages/teacher/view_notice.php	t	2025-08-14 09:41:32.241499+00	school_notice
524	6	Your leave application has been Rejected.	pages/teacher/teacher_leave_management.php	t	2025-08-14 09:39:13.953817+00	leave_status
526	6	New notice from Principal: How are You...	pages/teacher/view_notice.php	t	2025-08-14 09:40:11.523008+00	school_notice
530	70	New notice from Principal: fenil...	pages/teacher/view_notice.php	f	2025-08-14 09:41:24.6046+00	school_notice
531	49	New notice from Principal: fenil...	pages/teacher/view_notice.php	f	2025-08-14 09:41:28.360917+00	school_notice
533	12	New notice from Principal: fenil...	pages/teacher/view_notice.php	f	2025-08-14 09:41:35.38161+00	school_notice
488	54	New Exam Timetable: Term 2 Exam Timetable	pages/teacher/view_exam_timetable.php	t	2025-08-14 09:25:50.650348+00	exam_timetable
504	54	New Exam Timetable: Term 1 Exam Timetable	pages/teacher/view_exam_timetable.php	t	2025-08-14 09:26:49.835349+00	exam_timetable
529	54	New notice from Principal: fenil...	pages/teacher/view_notice.php	t	2025-08-14 09:41:21.397639+00	school_notice
486	52	New Exam Timetable: Term 2 Exam Timetable	pages/teacher/view_exam_timetable.php	t	2025-08-14 09:25:50.650348+00	exam_timetable
502	52	New Exam Timetable: Term 1 Exam Timetable	pages/teacher/view_exam_timetable.php	t	2025-08-14 09:26:49.835349+00	exam_timetable
527	52	New notice from Principal: fenil...	pages/teacher/view_notice.php	t	2025-08-14 09:41:15.043516+00	school_notice
487	51	New Exam Timetable: Term 2 Exam Timetable	pages/teacher/view_exam_timetable.php	t	2025-08-14 09:25:50.650348+00	exam_timetable
503	51	New Exam Timetable: Term 1 Exam Timetable	pages/teacher/view_exam_timetable.php	t	2025-08-14 09:26:49.835349+00	exam_timetable
528	51	New notice from Principal: fenil...	pages/teacher/view_notice.php	t	2025-08-14 09:41:18.188366+00	school_notice
723	6	devam parekh has submitted an assignment.	pages/assignments/view_submissions.php?id=24	t	2025-08-31 17:58:47.528243+00	assignment_submission
558	54	Harsh Shah has submitted an assignment.	pages/assignments/view_submissions.php?id=20	t	2025-08-14 10:16:59.773629+00	assignment_submission
545	6	New notice from Principal: HIII...	pages/teacher/view_notice.php	t	2025-08-14 09:59:48.817463+00	school_notice
546	6	Rohini Seth has submitted an assignment.	pages/assignments/view_submissions.php?id=18	t	2025-08-14 10:00:27.435849+00	assignment_submission
549	6	New notice from Principal: hiiiii...	pages/teacher/view_notice.php	t	2025-08-14 10:06:23.336013+00	school_notice
550	6	Harsh Shah has submitted an assignment.	pages/assignments/view_submissions.php?id=19	t	2025-08-14 10:07:03.320111+00	assignment_submission
551	10	New leave request from Meet Patel	pages/principal/teacher_leave_management.php	t	2025-08-14 10:07:15.58367+00	leave_request
576	36	Meet Patel has requested to borrow a book.	pages/librarian/borrow_requests.php	t	2025-08-14 11:37:33.429495+00	borrow_request
552	6	Your leave application has been Approved.	pages/teacher/teacher_leave_management.php	t	2025-08-14 10:07:41.47693+00	leave_status
556	10	New leave request from Meet Patel	pages/principal/teacher_leave_management.php	t	2025-08-14 10:15:11.369011+00	leave_request
555	54	Harsh Shah has submitted an assignment.	pages/assignments/view_submissions.php?id=20	t	2025-08-14 10:14:59.458794+00	assignment_submission
557	6	Your leave application has been Approved.	pages/teacher/teacher_leave_management.php	t	2025-08-14 10:15:28.71019+00	leave_status
562	8	New Notice from Fenil Pastagia	pages/bmc/view_principal_notices.php	t	2025-08-14 11:05:20.196684+00	principal_notice
559	10	New leave request from Meet Patel	pages/principal/teacher_leave_management.php	t	2025-08-14 10:17:36.787857+00	leave_request
565	13	New notice from BMC: ASAP	pages/principal/view_notice.php	f	2025-08-14 11:07:11.865209+00	new_notice
560	6	Your leave application has been Approved.	pages/teacher/teacher_leave_management.php	t	2025-08-14 10:17:46.845042+00	leave_status
566	39	New notice from BMC: ASAP	pages/principal/view_notice.php	f	2025-08-14 11:07:11.865209+00	new_notice
567	40	New notice from BMC: ASAP	pages/principal/view_notice.php	f	2025-08-14 11:07:11.865209+00	new_notice
570	42	New notice from BMC: ASAP	pages/principal/view_notice.php	f	2025-08-14 11:07:11.865209+00	new_notice
563	36	New Notice from Principal Fenil Pastagia	pages/librarian/view_principal_notices.php	t	2025-08-14 11:05:48.185076+00	principal_to_librarian_notice
561	6	Harsh Shah has submitted an assignment.	pages/assignments/view_submissions.php?id=13	t	2025-08-14 10:59:07.40346+00	assignment_submission
573	6	Harsh Shah has submitted an assignment.	pages/assignments/view_submissions.php?id=21	t	2025-08-14 11:34:03.959213+00	assignment_submission
574	36	New book acquisition request from Meet Patel for "Coding Basics".	pages/librarian/book_requests.php	t	2025-08-14 11:36:29.091594+00	acquisition_request
581	6	Harsh Shah has submitted an assignment.	pages/assignments/view_submissions.php?id=22	t	2025-08-14 11:43:27.362197+00	assignment_submission
582	6	Harsh Shah has submitted an assignment.	pages/assignments/view_submissions.php?id=22	t	2025-08-14 11:43:30.178076+00	assignment_submission
577	6	Your request for 'The Ramayana' has been approved. Please collect it from the library.	/pages/teacher/my_library_record.php	t	2025-08-14 11:38:27.811073+00	borrow_status
578	6	Your book request for "Coding Basics" has been approved.	pages/user/my_book_requests.php	t	2025-08-14 11:38:39.753509+00	acquisition_status
569	10	New notice from BMC: ASAP	pages/principal/view_notice.php	t	2025-08-14 11:07:11.865209+00	new_notice
583	6	Harsh Shah has submitted an assignment.	pages/assignments/view_submissions.php?id=1	t	2025-08-14 12:05:32.444841+00	assignment_submission
584	13	New notice from BMC: HII	pages/principal/view_notice.php	f	2025-08-18 07:31:15.795683+00	new_notice
585	39	New notice from BMC: HII	pages/principal/view_notice.php	f	2025-08-18 07:31:15.795683+00	new_notice
586	40	New notice from BMC: HII	pages/principal/view_notice.php	f	2025-08-18 07:31:15.795683+00	new_notice
589	42	New notice from BMC: HII	pages/principal/view_notice.php	f	2025-08-18 07:31:15.795683+00	new_notice
588	10	New notice from BMC: HII	pages/principal/view_notice.php	t	2025-08-18 07:31:15.795683+00	new_notice
590	6	Harsh Shah has submitted an assignment.	pages/assignments/view_submissions.php?id=2	t	2025-08-18 07:32:23.032019+00	assignment_submission
618	13	New notice from BMC: Quarterly General Meeting	pages/principal/view_notice.php	f	2025-08-21 09:20:01.299446+00	new_notice
591	6	Harsh Shah has submitted an assignment.	pages/assignments/view_submissions.php?id=3	t	2025-08-18 09:40:58.573739+00	assignment_submission
592	6	Harsh Shah has submitted an assignment.	pages/assignments/view_submissions.php?id=9	t	2025-08-18 09:50:54.695452+00	assignment_submission
593	6	Harsh Shah has submitted an assignment.	pages/assignments/view_submissions.php?id=8	t	2025-08-18 09:54:55.389594+00	assignment_submission
594	49	Your salary for August 2025 amounting to ₹10,434.78 has been processed.	pages/teacher/view_salary_history.php	f	2025-08-18 10:11:23.181949+00	salary
596	10	New leave request from Meet Patel	pages/principal/teacher_leave_management.php	t	2025-08-18 10:17:34.114742+00	leave_request
597	6	Your leave application has been Rejected.	pages/teacher/teacher_leave_management.php	t	2025-08-18 10:18:04.646237+00	leave_status
595	6	Harsh Shah has submitted an assignment.	pages/assignments/view_submissions.php?id=11	t	2025-08-18 10:14:59.819383+00	assignment_submission
599	52	Your salary for August 2025 amounting to ₹14,130.43 has been processed.	pages/teacher/view_salary_history.php	t	2025-08-19 08:14:52.284094+00	salary
598	51	Your salary for August 2025 amounting to ₹8,478.26 has been processed.	pages/teacher/view_salary_history.php	t	2025-08-19 08:05:55.883157+00	salary
602	92	Your salary for August 2025 amounting to ₹15,217.39 has been processed.	pages/teacher/view_salary_history.php	f	2025-08-19 09:23:32.232467+00	salary
603	6	Your salary for August 2025 amounting to ₹56,521.74 has been processed.	pages/teacher/view_salary_history.php	t	2025-08-19 09:23:43.251695+00	salary
605	6	Your salary for August 2025 amounting to ₹56,521.74 has been processed.	pages/teacher/view_salary_history.php	t	2025-08-20 09:01:13.564516+00	salary
606	6	Your salary for July 2025 amounting to ₹3,703.70 has been processed.	pages/teacher/view_salary_history.php	t	2025-08-20 09:52:01.573184+00	salary
607	13	New notice from BMC: Notice	pages/principal/view_notice.php	f	2025-08-21 09:09:37.518602+00	new_notice
608	39	New notice from BMC: Notice	pages/principal/view_notice.php	f	2025-08-21 09:09:37.518602+00	new_notice
609	40	New notice from BMC: Notice	pages/principal/view_notice.php	f	2025-08-21 09:09:37.518602+00	new_notice
612	42	New notice from BMC: Notice	pages/principal/view_notice.php	f	2025-08-21 09:09:37.518602+00	new_notice
614	115	New notice from BMC: Notice	pages/principal/view_notice.php	f	2025-08-21 09:09:37.518602+00	new_notice
615	121	New notice from BMC: Notice	pages/principal/view_notice.php	f	2025-08-21 09:09:37.518602+00	new_notice
616	122	New notice from BMC: Notice	pages/principal/view_notice.php	f	2025-08-21 09:09:37.518602+00	new_notice
619	39	New notice from BMC: Quarterly General Meeting	pages/principal/view_notice.php	f	2025-08-21 09:20:01.299446+00	new_notice
613	10	New notice from BMC: Notice	pages/principal/view_notice.php	t	2025-08-21 09:09:37.518602+00	new_notice
617	8	New Notice from Fenil Pastagia	pages/bmc/view_principal_notices.php	t	2025-08-21 09:12:47.064478+00	principal_notice
620	40	New notice from BMC: Quarterly General Meeting	pages/principal/view_notice.php	f	2025-08-21 09:20:01.299446+00	new_notice
623	42	New notice from BMC: Quarterly General Meeting	pages/principal/view_notice.php	f	2025-08-21 09:20:01.299446+00	new_notice
624	115	New notice from BMC: Quarterly General Meeting	pages/principal/view_notice.php	f	2025-08-21 09:20:01.299446+00	new_notice
625	121	New notice from BMC: Quarterly General Meeting	pages/principal/view_notice.php	f	2025-08-21 09:20:01.299446+00	new_notice
626	122	New notice from BMC: Quarterly General Meeting	pages/principal/view_notice.php	f	2025-08-21 09:20:01.299446+00	new_notice
627	10	New notice from BMC: Quarterly General Meeting	pages/principal/view_notice.php	t	2025-08-21 09:20:01.299446+00	new_notice
628	12	New notice from Principal: wdfsc...	pages/teacher/view_notice.php	f	2025-08-21 12:25:15.984748+00	school_notice
630	52	New notice from Principal: wdfsc...	pages/teacher/view_notice.php	f	2025-08-21 12:25:25.627966+00	school_notice
632	105	New notice from Principal: wdfsc...	pages/teacher/view_notice.php	f	2025-08-21 12:25:37.845359+00	school_notice
633	70	New notice from Principal: wdfsc...	pages/teacher/view_notice.php	f	2025-08-21 12:25:42.934886+00	school_notice
635	49	New notice from Principal: wdfsc...	pages/teacher/view_notice.php	f	2025-08-21 12:25:53.47269+00	school_notice
637	92	New notice from Principal: wdfsc...	pages/teacher/view_notice.php	f	2025-08-21 12:26:03.10021+00	school_notice
639	51	New notice from Principal: wdfsc...	pages/teacher/view_notice.php	f	2025-08-21 12:26:09.343607+00	school_notice
631	54	New notice from Principal: wdfsc...	pages/teacher/view_notice.php	t	2025-08-21 12:25:31.643014+00	school_notice
604	36	Your salary for August 2025 amounting to ₹20,869.57 has been processed.	pages/librarian/view_salary_history.php	t	2025-08-19 17:47:24.738406+00	librarian_salary
636	6	New notice from Principal: wdfsc...	pages/teacher/view_notice.php	t	2025-08-21 12:25:58.435105+00	school_notice
600	12	Your salary for August 2025 amounting to ₹5,652.17 has been processed.	pages/teacher/view_salary_history.php	t	2025-08-19 08:29:27.155238+00	salary
724	6	devam parekh has submitted an assignment.	pages/assignments/view_submissions.php?id=24	t	2025-08-31 18:02:03.67286+00	assignment_submission
655	36	New Notice from Principal Fenil Pastagia	pages/librarian/view_principal_notices.php	t	2025-08-21 12:27:28.086334+00	principal_to_librarian_notice
658	13	New notice from BMC: hii	pages/principal/view_notice.php	f	2025-08-25 08:05:17.076391+00	new_notice
659	39	New notice from BMC: hii	pages/principal/view_notice.php	f	2025-08-25 08:05:17.076391+00	new_notice
660	40	New notice from BMC: hii	pages/principal/view_notice.php	f	2025-08-25 08:05:17.076391+00	new_notice
663	42	New notice from BMC: hii	pages/principal/view_notice.php	f	2025-08-25 08:05:17.076391+00	new_notice
665	115	New notice from BMC: hii	pages/principal/view_notice.php	f	2025-08-25 08:05:17.076391+00	new_notice
666	121	New notice from BMC: hii	pages/principal/view_notice.php	f	2025-08-25 08:05:17.076391+00	new_notice
667	122	New notice from BMC: hii	pages/principal/view_notice.php	f	2025-08-25 08:05:17.076391+00	new_notice
669	13	New notice from BMC: hiii	pages/principal/view_notice.php	f	2025-08-25 08:11:53.750122+00	new_notice
670	39	New notice from BMC: hiii	pages/principal/view_notice.php	f	2025-08-25 08:11:53.750122+00	new_notice
671	40	New notice from BMC: hiii	pages/principal/view_notice.php	f	2025-08-25 08:11:53.750122+00	new_notice
674	42	New notice from BMC: hiii	pages/principal/view_notice.php	f	2025-08-25 08:11:53.750122+00	new_notice
676	115	New notice from BMC: hiii	pages/principal/view_notice.php	f	2025-08-25 08:11:53.750122+00	new_notice
677	121	New notice from BMC: hiii	pages/principal/view_notice.php	f	2025-08-25 08:11:53.750122+00	new_notice
678	122	New notice from BMC: hiii	pages/principal/view_notice.php	f	2025-08-25 08:11:53.750122+00	new_notice
668	10	New notice from BMC: hii	pages/principal/view_notice.php	t	2025-08-25 08:05:17.076391+00	new_notice
679	10	New notice from BMC: hiii	pages/principal/view_notice.php	t	2025-08-25 08:11:53.750122+00	new_notice
680	51	Your salary for August 2025 amounting to ₹11,086.96 has been processed.	pages/teacher/view_salary_history.php	f	2025-08-25 08:32:15.469248+00	salary
681	13	New notice from BMC: Annual General Meeting	pages/principal/view_notice.php	f	2025-08-25 10:45:55.836037+00	new_notice
682	39	New notice from BMC: Annual General Meeting	pages/principal/view_notice.php	f	2025-08-25 10:45:55.836037+00	new_notice
683	40	New notice from BMC: Annual General Meeting	pages/principal/view_notice.php	f	2025-08-25 10:45:55.836037+00	new_notice
686	42	New notice from BMC: Annual General Meeting	pages/principal/view_notice.php	f	2025-08-25 10:45:55.836037+00	new_notice
688	115	New notice from BMC: Annual General Meeting	pages/principal/view_notice.php	f	2025-08-25 10:45:55.836037+00	new_notice
689	121	New notice from BMC: Annual General Meeting	pages/principal/view_notice.php	f	2025-08-25 10:45:55.836037+00	new_notice
690	122	New notice from BMC: Annual General Meeting	pages/principal/view_notice.php	f	2025-08-25 10:45:55.836037+00	new_notice
691	10	New notice from BMC: Annual General Meeting	pages/principal/view_notice.php	t	2025-08-25 10:45:55.836037+00	new_notice
692	13	New notice from BMC: Re-scheduling AGM	pages/principal/view_notice.php	f	2025-08-25 10:58:07.693808+00	new_notice
693	39	New notice from BMC: Re-scheduling AGM	pages/principal/view_notice.php	f	2025-08-25 10:58:07.693808+00	new_notice
694	40	New notice from BMC: Re-scheduling AGM	pages/principal/view_notice.php	f	2025-08-25 10:58:07.693808+00	new_notice
697	42	New notice from BMC: Re-scheduling AGM	pages/principal/view_notice.php	f	2025-08-25 10:58:07.693808+00	new_notice
699	115	New notice from BMC: Re-scheduling AGM	pages/principal/view_notice.php	f	2025-08-25 10:58:07.693808+00	new_notice
700	121	New notice from BMC: Re-scheduling AGM	pages/principal/view_notice.php	f	2025-08-25 10:58:07.693808+00	new_notice
701	122	New notice from BMC: Re-scheduling AGM	pages/principal/view_notice.php	f	2025-08-25 10:58:07.693808+00	new_notice
702	10	New notice from BMC: Re-scheduling AGM	pages/principal/view_notice.php	t	2025-08-25 10:58:07.693808+00	new_notice
703	12	Your salary for August 2025 amounting to ₹7,156.52 has been processed.	pages/teacher/view_salary_history.php	t	2025-08-26 08:17:00.084272+00	salary
705	36	Your salary for August 2025 amounting to ₹32,365.22 has been processed.	pages/librarian/view_salary_history.php	t	2025-08-26 08:39:47.997126+00	librarian_salary
719	6	Harsh Shah has submitted an assignment.	pages/assignments/view_submissions.php?id=24	t	2025-08-26 11:29:30.508949+00	assignment_submission
720	6	Harsh Shah has submitted an assignment.	pages/assignments/view_submissions.php?id=23	t	2025-08-26 11:31:04.608289+00	assignment_submission
\.


--
-- Data for Name: password_resets; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.password_resets (id, user_id, email, otp_hash, expires_at, created_at) FROM stdin;
1	10	17fenill@gmail.com	$2y$10$7vfvXCUgJyghSXWhu63cluThJruljgfVCt2JWyqnHkV.Pgm8a3J0S	2025-08-01 18:07:16+00	2025-08-01 12:22:16+00
\.


--
-- Data for Name: payroll; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.payroll (id, school_id, payroll_name, payroll_image, salary) FROM stdin;
134	4	prithvi	\N	\N
\.


--
-- Data for Name: payroll_attendance; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.payroll_attendance (id, payroll_id, school_id, attendance_date, status, login_latitude, login_longitude, login_time, updated_at, marked_by_user_id) FROM stdin;
1	134	4	2025-08-28	Present	\N	\N	\N	2025-08-28 11:00:37.464192+00	10
\.


--
-- Data for Name: principal; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.principal (id, principal_image, school_id, principal_name, email, password, phone, dob, gender, blood_group, address, qualification, salary, batch, date_of_joining, transport_mode, self_transport_mode, vehicle_number, license_number, stop_id) FROM stdin;
13	/BMC-SMS/pages/principal/uploads/principal_13_1756465402.jpg	1	Dhaval	dhaval@gmail.com	$2y$10$/PhOzkuBDiabEZAW5eIZKuEr9Gcr0NTvpE7mGegA1Z6oNalzKXQcW	2563417897	1995-08-06	Male	A+	Varacha	12	600000.00	Morning	\N	Self Transport	Walking	\N	\N	\N
39	/BMC-SMS/pages/principal/uploads/principal_39_1756465416.jpg	5	Sunny	sunny@gmail.com	$2y$10$B4ISjcDHMlF.dJwZ0X.LlOQT6ZKgHPQy.odp78wc8cEahPpkhMKKm	8796457852	2003-06-06	Male	B+	Surat	d	100000.00	Evening	\N	Self Transport	Walking	\N	\N	\N
42	/BMC-SMS/pages/principal/uploads/principal_42_1756465470.png	8	Akshat	akshat@gmail.com	$2y$10$YW7sWUGqVcbuiHzwxwjShOajCzOXNSbqML9zPAzrM8.RWml.41iXW	8574123698	2000-06-06	Male	AB-	Adajan	MA	560000.00	Morning	\N	Self Transport	Bike	GJ-08-VG-8403	GJ-05-20237896541	\N
40	/BMC-SMS/pages/principal/uploads/principal_40_1756465497.png	6	Viral	viral@gmail.com	$2y$10$jKjdp7PnB3Ys3M7TnrfQtO97DNw8WPvNtldu7M1rX/ckGXx.P/9QW	7405670345	2000-03-11	Male	O+	Jahangirpura	MA	800000.00	Morning	\N	Self Transport	Walking	\N	\N	\N
115	/BMC-SMS/pages/principal/uploads/principal_115_1756465541.jpeg	13	Rupesh Pastagia	rupesh@gmail.com	$2y$10$bE4j/mv0OVuqDix.gg1NLOTdhoDj2FYSBuDZ1hp6j.tubSV2vhwPm	9678906578	1988-12-28	Male	A+	Adajan, Surat	M.A, B.Ed	80000.00	Morning	\N	Self Transport	Public Transport	\N	\N	\N
121	/BMC-SMS/pages/principal/uploads/principal_121_1756465582.jpg	15	Tanvi Agarwal	tanvi@gmail.com	$2y$10$IUDB1lEgEfLXwNDaCMukEu7LCw5BgJoav0cwNDUIxuTBJY9SuyMou	8520147896	1997-01-02	Male	O+	21, Harikrushna Village Society, Surat	M.A. M.Ed	41000.00	Morning	\N	Self Transport	Walking	\N	\N	\N
122	/BMC-SMS/pages/principal/uploads/principal_122_1756465735.jpg	5	Jagruti Dhameliya	jagurti@gmail.com	$2y$10$RTm5fWQRtc3XHs.nkd53t.rvJCGgTzRdBq6aFqOh6c49ZGaRxvoxe	7410236589	2022-08-12	Female	O-	jkm	B.ed	21000.00	Morning	\N	Self Transport	Public Transport	\N	\N	\N
10	/BMC-SMS/pages/principal/uploads/principal_10_1756465389.jpg	4	Fenil Pastagia	17fenill@gmail.com	$2y$10$EP56edKNSOvDiPXCub4iZuAI5sEVX3XjU1tnqJu8a4f.VS58QO5de	9924976503	1990-08-17	Male	B+	canal road	M.A. M.Ed	90000.00	Morning	\N	Self Transport	Car	GJ-05-JE-2013	GJ-0520189732052	\N
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
200	10	4	2025-08-20	Absent	21.18556912	72.77700837	12:18:54.795465	2025-08-20 06:50:10.916801+00
54	10	4	2025-08-11	Absent	21.18607675	72.77755617	10:10:25.267356	2025-08-11 08:17:18.35632+00
201	115	13	2025-08-20	Absent	21.18541550	72.77866350	12:20:58.302854	2025-08-20 07:22:12.572275+00
182	13	1	2025-08-19	Absent	\N	\N	13:24:42.196869	2025-08-19 13:24:42.196869+00
109	10	4	2025-08-14	Absent	21.18778880	72.83671040	17:00:43.407176	2025-08-14 02:22:15.482863+00
183	40	6	2025-08-19	Absent	\N	\N	13:41:18.812112	2025-08-19 13:34:26.906254+00
2	10	4	2025-08-07	Absent	21.20995620	72.77066930	18:31:01.623545	2025-08-07 11:51:37.880963+00
63	10	4	2025-08-12	Absent	21.18553904	72.77813518	11:52:44.990294	2025-08-12 06:05:11.118698+00
336	10	4	2025-09-01	Absent	21.18613248	72.77633357	10:18:37.438459	2025-09-01 07:57:28.896058+00
22	13	1	2025-08-08	Absent	\N	\N	08:38:52.86685	2025-08-08 08:32:54.774228+00
155	10	4	2025-08-19	Absent	21.20139575	72.79829760	20:30:07.473768	2025-08-19 08:00:45.51607+00
17	10	4	2025-08-08	Absent	\N	\N	16:39:26.206211	2025-08-08 04:26:59.590595+00
40	10	4	2025-08-09	Absent	21.18541550	72.77866350	13:12:13.945994	2025-08-09 13:07:51.58185+00
238	10	4	2025-08-21	Absent	\N	\N	14:39:34.929006	2025-08-21 07:33:55.943217+00
128	10	4	2025-08-18	Absent	21.19106560	72.83671040	17:20:04.812795	2025-08-18 06:36:25.983619+00
99	13	1	2025-08-13	Absent	\N	\N	18:17:31.769506	2025-08-13 18:04:04.073787+00
42	10	4	2025-08-10	Absent	\N	\N	18:27:05.466767	2025-08-10 09:48:58.767457+00
262	13	1	2025-08-25	Absent	\N	\N	08:26:53.887285	2025-08-25 08:26:53.887285+00
289	10	4	2025-08-28	Absent	\N	\N	13:24:45.530753	2025-08-28 07:58:48.137937+00
107	42	8	2025-08-13	Absent	\N	\N	19:02:43.848609	2025-08-13 19:02:43.848609+00
76	10	4	2025-08-13	Absent	\N	\N	19:05:07.836238	2025-08-13 08:53:01.325826+00
256	10	4	2025-08-25	Absent	\N	\N	16:18:05.726122	2025-08-25 07:32:20.190986+00
313	10	4	2025-08-29	Absent	\N	\N	12:21:31.826575	2025-08-29 07:57:40.798962+00
331	10	4	2025-08-31	Absent	\N	\N	18:07:23.676104	2025-08-31 16:50:15.987694+00
274	10	4	2025-08-26	Absent	\N	\N	17:16:36.170339	2025-08-26 07:40:39.213264+00
\.


--
-- Data for Name: principal_payroll; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.principal_payroll (id, principal_id, payroll_user_id, school_id, salary_month, salary_year, base_salary, total_working_days, present_days, absent_days, deduction_amount, total_incentives, net_salary_paid, payment_date, status) FROM stdin;
\.


--
-- Data for Name: principal_timings; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.principal_timings (timing_id, principal_id, day_of_week, opens_at, closes_at, is_closed) FROM stdin;
113	42	Monday	10:00 AM	08:00 PM	f
114	42	Tuesday	10:00 AM	08:00 PM	f
115	42	Wednesday	10:00 AM	08:00 PM	f
116	42	Thursday	10:00 AM	08:00 PM	f
117	42	Friday	10:00 AM	08:00 PM	f
118	42	Saturday	10:00 AM	08:00 PM	f
119	42	Sunday	\N	\N	t
85	40	Monday	10:00 AM	08:00 PM	f
86	40	Tuesday	10:00 AM	08:00 PM	f
87	40	Wednesday	10:00 AM	08:00 PM	f
88	40	Thursday	10:00 AM	08:00 PM	f
89	40	Friday	10:00 AM	08:00 PM	f
90	40	Saturday	\N	\N	t
91	40	Sunday	\N	\N	t
190	115	Monday	07:00 AM	12:00 PM	f
191	115	Tuesday	07:00 AM	12:00 PM	f
192	115	Wednesday	07:00 AM	12:00 PM	f
193	115	Thursday	07:00 AM	12:00 PM	f
194	115	Friday	07:00 AM	12:00 PM	f
195	115	Saturday	07:00 AM	10:00 AM	f
196	115	Sunday	\N	\N	t
274	121	Monday	07:00 AM	06:00 AM	f
275	121	Tuesday	07:00 AM	06:00 AM	f
276	121	Wednesday	07:00 AM	06:00 AM	f
277	121	Thursday	07:00 AM	06:00 AM	f
278	121	Friday	07:00 AM	06:00 AM	f
279	121	Saturday	07:00 AM	06:00 AM	f
280	121	Sunday	\N	\N	t
288	122	Monday	10:00 AM	06:00 AM	f
289	122	Tuesday	10:00 AM	06:00 AM	f
290	122	Wednesday	10:00 AM	06:00 AM	f
291	122	Thursday	10:00 AM	06:00 AM	f
292	122	Friday	10:00 AM	06:00 AM	f
293	122	Saturday	10:00 AM	06:00 AM	f
294	122	Sunday	\N	\N	t
1	10	Monday	07:00 AM	12:00 PM	f
2	10	Tuesday	07:00 AM	12:00 PM	f
3	10	Wednesday	07:00 AM	12:00 PM	f
4	10	Thursday	07:00 AM	12:00 PM	f
5	10	Friday	07:00 AM	12:00 PM	f
6	10	Saturday	07:00 AM	10:00 AM	f
7	10	Sunday	\N	\N	t
15	13	Monday	10:00 AM	08:00 PM	f
16	13	Tuesday	10:00 AM	08:00 PM	f
17	13	Wednesday	10:00 AM	08:00 PM	f
18	13	Thursday	10:00 AM	08:00 PM	f
19	13	Friday	10:00 AM	08:00 PM	f
20	13	Saturday	10:00 AM	08:00 PM	f
21	13	Sunday	\N	\N	t
71	39	Monday	10:00 AM	08:00 AM	f
72	39	Tuesday	10:00 AM	08:00 AM	f
73	39	Wednesday	10:00 AM	08:00 AM	f
74	39	Thursday	10:00 AM	08:00 AM	f
75	39	Friday	10:00 AM	08:00 AM	f
76	39	Saturday	10:00 AM	08:00 AM	f
77	39	Sunday	\N	\N	t
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
9	10	4	Testing t	s	\N	\N	2025-08-07 16:06:10.879014+00
10	10	4	hii	i am fine	\N	\N	2025-08-08 04:28:05.994896+00
11	10	4	hii	checking	\N	\N	2025-08-08 04:41:55.941351+00
12	10	4	Testing	Notice to BMC\r\n\r\nChecked by -\r\nMeet Patel	\N	\N	2025-08-08 10:58:53.45766+00
13	10	4	hii	testing	\N	\N	2025-08-08 12:23:27.058402+00
14	10	4	hiii 	hiii	\N	\N	2025-08-10 17:46:08.675461+00
15	10	4	again	again	\N	\N	2025-08-10 17:46:33.941804+00
16	10	4	gd	kd	\N	\N	2025-08-12 06:27:21.277483+00
17	10	4	Notice toBMC	er	\N	\N	2025-08-12 08:45:45.801569+00
18	10	4	Notice to BMC	ff	\N	\N	2025-08-12 08:49:13.27422+00
19	10	4	Meeting	got it 	\N	\N	2025-08-13 12:22:56.93912+00
20	10	4	Notice to BMC	Check path in notification	\N	\N	2025-08-14 11:05:20.196684+00
21	10	4	Response	Yes sir, i have received your notice and notification is working correctly	\N	\N	2025-08-21 09:12:47.064478+00
\.


--
-- Data for Name: principal_to_librarian_notices; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.principal_to_librarian_notices (id, principal_id, school_id, title, content, file_path, original_filename, created_at) FROM stdin;
5	10	4	IMPORTANT	FENIL	\N	\N	2025-08-07 17:06:38.429894+00
6	10	4	Testing	Notice to Librarian\r\n\r\nChecked by -\r\nMeet Patel	/BMC-SMS/pages/principal/uploads/librarian_notices/p2l_notice_6895d89540d18_Leaving_Certificate_PurviDesai.pdf	Leaving_Certificate_Purvi Desai.pdf	2025-08-08 10:59:32.820583+00
7	10	4	hii	just testing	\N	\N	2025-08-08 11:53:15.563902+00
8	10	4	Notice to librarian	fg	\N	\N	2025-08-12 08:47:10.64029+00
9	10	4	Meeting	got it	\N	\N	2025-08-13 12:23:06.278797+00
10	10	4	Notice to Devang Sir	Check 	\N	\N	2025-08-14 11:05:48.185076+00
11	10	4	wgre	fdcv	\N	\N	2025-08-21 12:27:28.086334+00
\.


--
-- Data for Name: routes; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.routes (id, school_id, route_name, vehicle_id, driver_id) FROM stdin;
1	4	Adajan - Palanpur Route	1	1
2	4	Vesu - Citylight Route	4	2
\.


--
-- Data for Name: school; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.school (id, school_logo, school_name, email, phone, school_opening, school_type, education_board, school_medium, school_category, address, latitude, longitude, passing_percentage, minimum_attendance_percentage) FROM stdin;
13	uploads/school_logos/school_13_1755673545.jpg	DD Savani	dd@gmail.com	9087678990	2025-08-20	Private	{CBSE}	{English}	{Primary,"Upper Primary",Secondary,"Higher Secondary"}	Pal, Surat	\N	\N	33.00	75.00
11	\N	ABCD International School	abcd@gmail.com	abcd1234	2024-11-01	Private	{CBSE}	{English}	{Pre-Primary,Primary,"Upper Primary",Secondary,"Higher Secondary"}	abcder	\N	\N	33.00	75.00
1	/BMC-SMS/uploads/school_logos/school_1_1754848090.jpg	LP SAVANI CANAL ROAD	lpsavani@gmail.com	8974589654	2025-08-06	Government	{State}	{Hindi}	{Pre-Primary,Primary}	Surat	\N	\N	33.00	75.00
6	/BMC-SMS/uploads/school_logos/school_6_1754848118.jpg	Sevan Days	seven@gmail.com	7874145963	2003-05-06	Government	{CBSE,State}	{English}	{Pre-Primary,Primary,Secondary}	Canal Road	\N	\N	33.00	75.00
15	/BMC-SMS/uploads/school_logos/school_15_1755764522.jpg	CountrySide International School	countryside@gmail.com	9210368742	2025-08-22	Private	{CBSE,State}	{English,Hindi}	{Primary,"Upper Primary",Secondary,"Higher Secondary"}	CountrySide International School, Near Heaven Party Lawns, Dandi Road, Surat	\N	\N	33.00	75.00
5	/BMC-SMS/uploads/school_logos/school_5_1754623317.png	Riverdale	river@gmail.com	8574321698	2011-08-05	Private	{CBSE,State,IGCSE}	{English}	{Pre-Primary,Primary,Secondary}	Surat	\N	\N	33.00	75.00
4	/BMC-SMS/uploads/school_logos/school_4_1754848072.jpg	Sanskar Bharti Vidyalay	sbv@gmail.com	8526548525	2025-07-06	Private	{CBSE}	{English,Hindi}	{Pre-Primary,Primary,"Upper Primary",Secondary,"Higher Secondary"}	Crossroad, Beside D-Mart, Katargam, Surat-395001	21.21060270	72.76795460	33.45	70.00
16	/BMC-SMS/uploads/school_logos/school_16_1756663823.png	DailyFix	dailyfix@gmail.com	9675875456	2025-08-31	Government	{State}	{English}	{"Higher Secondary"}	Dindayal co. Socity	\N	\N	33.00	75.00
8	uploads/school_logos/school_8_1754638909.png	DPS	dps@gmail.com	8596321478	2025-02-11	Government	{State}	{"Regional Language"}	{Pre-Primary,Primary,"Upper Primary",Secondary,"Higher Secondary"}	Surat	\N	\N	33.00	75.00
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
36	16	teacher	12
37	16	teacher	6
38	16	standard	11
39	17	teacher	49
40	17	teacher	12
41	17	teacher	6
42	17	teacher	55
43	17	teacher	51
44	17	teacher	52
45	17	teacher	54
46	17	standard	11
47	17	standard	11th
48	18	teacher	49
49	18	teacher	12
50	18	teacher	6
51	18	teacher	55
52	18	teacher	51
53	18	teacher	52
54	18	teacher	54
55	18	standard	11
56	18	standard	11th
57	19	teacher	49
58	19	teacher	12
59	19	teacher	6
60	19	teacher	55
61	19	teacher	51
62	19	teacher	52
63	19	teacher	54
64	19	standard	11
65	19	standard	11th
66	20	teacher	49
67	20	teacher	12
68	20	teacher	6
69	20	teacher	55
70	20	teacher	51
71	20	teacher	52
72	20	teacher	54
73	20	standard	11
74	20	standard	11th
75	21	teacher	6
76	22	teacher	49
77	22	teacher	12
78	22	teacher	6
79	22	teacher	51
80	22	teacher	52
81	22	teacher	54
82	22	standard	10
83	22	standard	11
84	23	teacher	6
85	24	teacher	6
86	25	teacher	6
87	26	teacher	49
88	26	teacher	12
89	26	teacher	6
90	26	teacher	51
91	26	teacher	52
92	26	teacher	54
93	26	standard	10
94	26	standard	11
95	27	teacher	49
96	27	teacher	12
97	27	teacher	6
98	27	teacher	70
99	27	teacher	51
100	27	teacher	52
101	27	teacher	54
102	27	standard	10
103	27	standard	11
104	28	standard	11
105	29	teacher	49
106	29	teacher	12
107	29	teacher	6
108	29	teacher	70
109	29	teacher	51
110	29	teacher	52
111	29	teacher	54
112	29	standard	10
113	29	standard	11
114	29	standard	Junior
115	30	teacher	49
116	30	teacher	12
117	30	teacher	6
118	30	teacher	70
119	30	teacher	51
120	30	teacher	52
121	30	teacher	54
122	30	standard	10
123	30	standard	11
124	30	standard	Junior
125	31	teacher	6
126	32	standard	11
127	33	teacher	6
128	34	teacher	6
129	35	teacher	49
130	35	teacher	12
131	35	teacher	6
132	35	teacher	70
133	35	teacher	51
134	35	teacher	52
135	35	teacher	54
136	35	standard	10
137	35	standard	11
138	35	standard	Junior
139	36	teacher	6
140	37	teacher	6
141	38	teacher	119
142	38	teacher	49
143	38	teacher	12
144	38	teacher	92
145	38	teacher	6
146	38	teacher	70
147	38	teacher	124
148	38	teacher	51
149	38	teacher	105
150	38	teacher	52
151	38	teacher	54
152	38	teacher	123
153	38	teacher	106
154	38	standard	8
155	38	standard	10
156	38	standard	11
157	38	standard	12
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
16	10	4	Testing	abcdefg	\N	\N	2025-08-07 16:08:38.073397+00
17	10	4	hii	this is for testing	\N	\N	2025-08-08 10:33:58.409169+00
18	10	4	one more	one more	\N	\N	2025-08-08 10:35:07.757116+00
19	10	4	Testing 	Notice to teacher and student\r\n\r\nChecked by -\r\nMeet Patel	\N	\N	2025-08-08 10:55:09.603729+00
20	10	4	test	testing	\N	\N	2025-08-08 11:13:44.854025+00
21	10	4	hiiii	hiiii	\N	\N	2025-08-09 13:08:22.666233+00
22	10	4	to everyone 	notice	\N	\N	2025-08-09 13:12:37.533166+00
23	10	4	Testing Notification	Link to Notification	\N	\N	2025-08-11 08:48:18.69026+00
24	10	4	Testing	sf	\N	\N	2025-08-12 08:36:43.315523+00
25	10	4	Testing	sf	\N	\N	2025-08-12 08:36:47.824797+00
26	10	4	Notice to all teacher n student	df	\N	\N	2025-08-12 08:46:02.250984+00
27	10	4	Test	dsf	\N	\N	2025-08-13 12:21:28.030968+00
28	10	4	Testing 	sdf	\N	\N	2025-08-13 13:03:39.686804+00
29	10	4	Urgent	d	\N	\N	2025-08-13 13:08:14.300334+00
30	10	4	Checking	fg	\N	\N	2025-08-14 08:51:38.128927+00
31	10	4	Hiii	How are you	\N	\N	2025-08-14 09:25:54.822253+00
32	10	4	Meet	hyy	/BMC-SMS/pages/principal/uploads/notice_689dac63de0b02.75831324_MUST IMPROVEMENT.docx.pdf	MUST IMPROVEMENT.docx.pdf	2025-08-14 09:29:07.096811+00
33	10	4	HIIIIIIIIII	HIIIIIIIIIIIIIIIIIIIII	\N	\N	2025-08-14 09:39:20.942732+00
34	10	4	How are You	Fine ?	\N	\N	2025-08-14 09:40:11.382779+00
35	10	4	fenil	hyyy	\N	\N	2025-08-14 09:41:14.735293+00
36	10	4	HIII	OOOOOOOOOO	\N	\N	2025-08-14 09:59:48.65709+00
37	10	4	hiiiii	opppppppppp	\N	\N	2025-08-14 10:06:23.017049+00
38	10	4	wdfsc	ewrfd	\N	\N	2025-08-21 12:25:15.522969+00
\.


--
-- Data for Name: school_timetable; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.school_timetable (id, school_id, standard, day_of_week, period_number, subject_name, teacher_id, start_time, end_time) FROM stdin;
73	4	10	Monday	1	Maths	6	07:00:00	08:00:00
74	4	10	Monday	2	Computer Science	49	08:00:00	09:00:00
75	4	10	Tuesday	1	Chemistry	6	07:00:00	07:00:00
76	4	10	Tuesday	2	Computer Science	49	08:00:00	09:00:00
77	4	10	Wednesday	1	Maths	6	07:00:00	08:00:00
78	4	10	Wednesday	2	Computer Science	49	08:00:00	09:00:00
79	4	10	Thursday	1	Chemistry	6	07:00:00	08:00:00
80	4	10	Thursday	2	Computer Science	49	08:00:00	09:00:00
81	4	10	Friday	1	Maths	6	07:00:00	08:00:00
82	4	10	Friday	2	Computer Science	49	08:00:00	09:00:00
83	4	10	Saturday	1	Chemistry	6	07:00:00	08:00:00
84	4	10	Saturday	2	Computer Science	49	08:00:00	09:00:00
103	4	12	Monday	1	Social Studies	92	14:00:00	15:00:00
104	4	12	Tuesday	1	Physical Education	51	15:00:00	16:00:00
105	4	12	Wednesday	1	Social Studies	92	14:00:00	15:00:00
106	4	12	Thursday	1	Physical Education	51	14:00:00	15:00:00
107	4	12	Friday	1	Social Studies	92	14:00:00	15:00:00
108	4	12	Saturday	1	Physical Education	51	14:00:00	15:00:00
126	4	11	Monday	1	Maths	6	13:55:00	14:55:00
127	4	11	Tuesday	1	Chemistry	6	09:00:00	10:00:00
128	4	11	Wednesday	1	Physical Education	51	10:00:00	11:00:00
129	4	11	Thursday	2	Chemistry	6	13:57:00	14:57:00
130	4	11	Friday	1	Biology	54	12:00:00	13:00:00
131	4	11	Saturday	1	Maths	6	13:00:00	14:00:00
\.


--
-- Data for Name: staff_incentives; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.staff_incentives (id, staff_id, staff_role, incentive_id, salary_month, salary_year, amount, assigned_by_user_id, assigned_at) FROM stdin;
28	12	teacher	7	8	2025	700.00	134	2025-08-25 11:33:24.025901+00
30	52	teacher	7	8	2025	1750.00	134	2025-08-25 11:33:24.025901+00
31	54	teacher	7	8	2025	875.00	134	2025-08-25 11:33:24.025901+00
32	105	teacher	7	8	2025	3640.00	134	2025-08-25 11:33:24.025901+00
33	70	teacher	7	8	2025	3500.00	134	2025-08-25 11:33:24.025901+00
35	49	teacher	7	8	2025	1400.00	134	2025-08-25 11:33:24.025901+00
36	6	teacher	7	8	2025	7000.00	134	2025-08-25 11:33:24.025901+00
37	92	teacher	7	8	2025	1750.00	134	2025-08-25 11:33:24.025901+00
39	51	teacher	7	8	2025	1050.00	134	2025-08-25 11:33:24.025901+00
41	12	teacher	6	8	2025	-500.00	134	2025-08-25 11:33:38.276569+00
43	52	teacher	6	8	2025	-1250.00	134	2025-08-25 11:33:38.276569+00
44	54	teacher	6	8	2025	-625.00	134	2025-08-25 11:33:38.276569+00
45	105	teacher	6	8	2025	-2600.00	134	2025-08-25 11:33:38.276569+00
46	70	teacher	6	8	2025	-2500.00	134	2025-08-25 11:33:38.276569+00
48	49	teacher	6	8	2025	-1000.00	134	2025-08-25 11:33:38.276569+00
49	6	teacher	6	8	2025	-5000.00	134	2025-08-25 11:33:38.276569+00
50	92	teacher	6	8	2025	-1250.00	134	2025-08-25 11:33:38.276569+00
52	51	teacher	6	8	2025	-750.00	134	2025-08-25 11:33:38.276569+00
54	36	librarian	7	8	2025	2800.00	134	2025-08-25 16:20:24.260084+00
\.


--
-- Data for Name: standard_categories_mapping; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.standard_categories_mapping (category_name, standard_name) FROM stdin;
Pre-Primary	Nursery
Pre-Primary	Junior
Pre-Primary	Senior
Primary	1
Primary	2
Primary	3
Primary	4
Primary	5
Upper Primary	6
Upper Primary	7
Upper Primary	8
Secondary	9
Secondary	10
Higher Secondary	11
Higher Secondary	12
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
125	11	9
126	11	8
127	11	10
128	11	1
129	11	2
130	11	16
131	11	7
132	11	13
133	11	4
138	Nursery	17
139	Nursery	18
140	Nursery	20
141	Junior	17
142	Junior	20
143	Junior	18
144	Senior	14
145	Senior	19
146	Senior	18
154	12	21
155	12	10
156	12	1
157	12	12
158	12	2
159	12	16
160	12	4
161	12	22
\.


--
-- Data for Name: stops; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.stops (id, route_id, stop_name, stop_fee) FROM stdin;
1	1	Star Bazaar, Adajan	1200.00
2	1	TGB Circle, Pal	1300.00
3	1	L.P. Savani School, Palanpur Gam	1400.00
4	2	Science Center, City Light	1100.00
5	2	Rahul Raj Mall, Piplod	1250.00
6	2	VDOT, Vesu	1350.00
7	2	Big Bazaar, VIP Road	1450.00
\.


--
-- Data for Name: student; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.student (id, student_image, student_name, rollno, std, email, password, academic_year, school_id, dob, gender, blood_group, address, father_name, father_phone, mother_name, mother_phone, stop_id, transport_mode, date_of_joining, self_transport_mode, vehicle_number, license_number) FROM stdin;
149	/BMC-SMS/pages/student/uploads/student_68b173ebc7a3e6.68453386.jpeg	harsh shah	1	10	harsh@gmail.com	$2y$10$H.8lBW0bupzwRTyCyqfTNu53VOf8tPze9TIUMfXGIw7MuLMpWvTP2	2024-2025	4	2005-01-26	Male	AB+	shantibhavan	hemant	753685124	sunita	8575356545	\N	Self Transport	2025-08-29	Bike	GJ-05-JK-5297	GJ-0520173201830
152	/BMC-SMS/pages/student/uploads/student_68b1758f2f1a62.15594044.jpg	Rupali	4	8	rupali@gmail.com	$2y$10$syYFrJ7eZvKF62J8AVx6DOscekP9tS2qRlBJL0B1uIPc3.rbzD6fG	2024-2025	4	1998-08-08	Female	B-	vadodara	rupesh	7532145986	vanita	7412369852	\N	Self Transport	2025-08-29	Bike	GJ-06-HE-4133	GJ-05-20237396540
150	/BMC-SMS/pages/student/uploads/student_68b1747f3ec457.95083106.jpg	devam parekh	2	11	devamparekh1200@gmail.com	$2y$10$EAp3p41/hhr6/8KrZ5AtZu/e6AOOp5NBdW6yBxx.PkMD1OfMYDtvK	2024-2025	4	2005-03-11	Male	AB-	LP savani	mukesh	9874563210	falguni	7532147852	\N	Self Transport	2025-08-29	Bike	GJ5DD5887	GJ-05-20237896541
151	/BMC-SMS/pages/student/uploads/student_68b174f89872e0.32870785.jpg	Ravindra jadeja	3	11	ravindra@gmail.com	$2y$10$DTDfibDinWNWk4ssatRx4e5q12feiW4bb/M5T4BgevYGawJek2NBW	2024-2025	4	2000-12-08	Male	AB+	Jamnagar	haresh	7532147856	ramila	9512365478	1	School Transport	2025-08-29	\N	\N	\N
\.


--
-- Data for Name: student_marks; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.student_marks (mark_id, student_id, school_id, academic_year, std, exam_type, subject_name, marks_obtained, total_marks, entry_date, entered_by_user_id) FROM stdin;
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
18	Rhymes
19	Reading
20	General Awareness
21	Accounts
22	Economics
\.


--
-- Data for Name: teacher; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.teacher (id, teacher_image, teacher_name, phone, school_id, dob, gender, blood_group, address, email, password, qualification, subject, language_known, salary, std, experience, batch, class_teacher, class_teacher_std, date_of_joining, transport_mode, self_transport_mode, vehicle_number, license_number, stop_id) FROM stdin;
12	/BMC-SMS/pages/teacher/uploads/teacher_6899d4a35ee540.89855330.jpg	Jay Shah	9874522589	4	2025-08-13	Male	AB+	canal road	jay@gmail.com	$2y$10$TUf4M/5ENm2A6oun27EuAuAz8Wlr8e8Ub8xwCR3w9i09nTBhFEWMO	M.A	maths	english,gujarati	10000	{9,10,11}	10	Morning	f	\N	\N	School Transport	\N	\N	\N	5
92	/BMC-SMS/pages/teacher/uploads/teacher_92_68b17750b607c.jpg	Meera Rajput	8741203698	4	1997-01-05	Female	A+	J-501, Shivam Heights, Ankur Char Rasta, Udhana, Surat	meera@gmail.com	$2y$10$8rmmBKRvJBYB3g0Ct1iIIOiZ.o1pBFnqsjXqfbcnO0qbaJ.QIu7a6	MCA	Social Studies	English, Hindi, Gujarati	25000	{12}	5	Morning	f	\N	\N	Self Transport	Bike	GJ-21-CE-1152	GJ-2120108523637	\N
6	/BMC-SMS/pages/teacher/uploads/teacher_6_68b17688f110b.jpg	Meet Patel	9852142016	4	2005-09-04	Male	AB+	Motavarachha	meet@gmail.com	$2y$10$sdz4DZ5oaMJNrUA9mld44uiBNIIkAQCPjs2XrrnUcl.Bp6wlzYz1a	B.C.A	Maths, Chemistry	english	100000	{10,11}	10	Evening	t	11	\N	Self Transport	Car	GJ-05-RU-5556	GJ-1987119635203	\N
70	/BMC-SMS/pages/teacher/uploads/teacher_70_68b176f0b7bf8.jpg	Rahul	9523001459	4	1997-08-12	Male	AB+	sd	rahul@gmail.com	$2y$10$QjT3TSvtutmr1Jl2REhPBuJ4c6B34xrmOkAOe7GwI0ms3sB7Ov.iW	B.C.A	Hindi	Hindi	50000	{8}	8	Morning	f	\N	\N	Self Transport	Public Transport	\N	\N	\N
51	/BMC-SMS/pages/teacher/uploads/teacher_51_68b176c556070.jpg	Ravindra Jadeja	7456321062	4	1987-02-01	Male	A-	J-801, Shurveer Bunglows, Near HK Road, Udhana, Surat	jadeja@gmail.com	$2y$10$yzEERP8pUf.GaSHQrRtc6uLlzjr.ft1N6j/BS2KGqQzoykf289BGS	MCA	Physical Education	English	15000	{11,12}	3	Evening	f	\N	\N	Self Transport	Bike	GJ-08-VG-8403	GJ-0820126301236	\N
105	/BMC-SMS/pages/teacher/uploads/teacher_105_68b177731f863.jpg	Sapna Trivedi	6923001456	4	1997-02-06	Female	O+	G-02, Roman Row House, Near H.K. Marg, Piplod, Surat	sapna@gmail.com	$2y$10$wpEpcFBLJ7Msu0z60MmiQu6n2q.FJ9QY1rl1SPH67b5uaixJl0gNG	C.A.	Accounts	English, Hindi, Gujarati	52000	{12}	5	Morning	f	\N	\N	Self Transport	Walking	\N	\N	\N
52	/BMC-SMS/pages/teacher/uploads/teacher_52_68b177262328e.jpg	Tara Sutaria	8514789630	4	2021-07-12	Male	O-	ancdefgh	tara@gmail.com	$2y$10$sjbggi23JLfNjwuNGmvKY.EvgUgWS9.nh7pA3vv5QjJGP75YBBxDO	BBA	Sanskrit	Hindi	25000	{11}	5	Evening	f	\N	\N	School Transport	\N	\N	\N	7
54	/BMC-SMS/pages/teacher/uploads/teacher_54_68b177369c734.jpg	Tia Dholakia	9853200014	4	2005-08-08	Male	O+	Katargam	tia@gmail.com	$2y$10$qtWaXyrRnRYI72QYxah1QOa9VXCChAc0/aFadc2i1m2x.ZEq6Euha	BCA	Biology	English, Hindi, Gujarati	12500	{11}	5	Morning	f	\N	\N	Self Transport	Bike	GJ-01-RC-8630	GJ-0120086320146	\N
49	/BMC-SMS/pages/teacher/uploads/teacher_49_68b17697981ed.jpg	Ayushi Patil	8745632100	4	1980-12-12	Male	B+	abcd	ayushi@gmail.com	$2y$10$Vo893uZp26VV4sCkTWFXSePR9UxOVhLRZlnVtYthhIgdrm4kdIslO	B.C.A	Computer Science	English, Hindi, Gujarati	20000	{10,11}	2	Morning	f	\N	\N	Self Transport	Bike	GJ-21-CE-1152	GJ-2120108523001	\N
\.


--
-- Data for Name: teacher_attendance; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.teacher_attendance (attendance_id, teacher_id, school_id, attendance_date, status, remark, marked_by_user_id, updated_at) FROM stdin;
1	6	4	2025-07-28	Leave	\N	10	2025-07-28 08:53:46+00
11	6	4	2025-07-27	Present	\N	10	2025-07-28 09:35:08+00
15	6	4	2025-08-30	Leave	\N	10	2025-08-07 09:19:11.792668+00
33	49	4	2025-08-08	Absent	\N	10	2025-08-08 09:16:58.952298+00
34	12	4	2025-08-08	Present	\N	10	2025-08-08 09:16:58.952298+00
41	49	4	2025-08-09	Absent	\N	10	2025-08-09 13:13:20.840006+00
42	12	4	2025-08-09	Present	\N	10	2025-08-09 13:13:20.840006+00
55	49	4	2025-08-01	Present	\N	10	2025-08-13 10:27:01.783533+00
56	12	4	2025-08-01	Present	\N	10	2025-08-13 10:27:01.783533+00
134	92	4	2025-08-01	Present	\N	10	2025-08-18 08:29:22.014184+00
57	6	4	2025-08-01	Present	\N	10	2025-08-13 10:27:01.783533+00
58	70	4	2025-08-01	Present	\N	10	2025-08-13 10:27:01.783533+00
59	51	4	2025-08-01	Present	\N	10	2025-08-13 10:27:01.783533+00
62	49	4	2025-08-02	Present	\N	10	2025-08-13 10:44:04.085232+00
83	49	4	2025-08-06	Present	\N	10	2025-08-13 10:45:18.644532+00
111	49	4	2025-08-11	Present	\N	10	2025-08-13 10:46:05.653445+00
112	12	4	2025-08-11	Present	\N	10	2025-08-13 10:46:05.653445+00
113	6	4	2025-08-11	Present	\N	10	2025-08-13 10:46:05.653445+00
114	70	4	2025-08-11	Present	\N	10	2025-08-13 10:46:05.653445+00
115	51	4	2025-08-11	Present	\N	10	2025-08-13 10:46:05.653445+00
116	52	4	2025-08-11	Present	\N	10	2025-08-13 10:46:05.653445+00
120	6	4	2025-08-12	Present	\N	10	2025-08-13 10:46:16.617878+00
122	51	4	2025-08-12	Present	\N	10	2025-08-13 10:46:16.617878+00
123	52	4	2025-08-12	Present	\N	10	2025-08-13 10:46:16.617878+00
118	49	4	2025-08-12	Present	\N	10	2025-08-13 10:46:16.617878+00
119	12	4	2025-08-12	Present	\N	10	2025-08-13 10:46:16.617878+00
121	70	4	2025-08-12	Present	\N	10	2025-08-13 10:46:16.617878+00
60	52	4	2025-08-01	Present	\N	10	2025-08-13 10:27:01.783533+00
63	12	4	2025-08-02	Present	\N	10	2025-08-13 10:44:04.085232+00
142	92	4	2025-08-02	Present	\N	10	2025-08-18 08:29:36.385261+00
64	6	4	2025-08-02	Present	\N	10	2025-08-13 10:44:04.085232+00
65	70	4	2025-08-02	Present	\N	10	2025-08-13 10:44:04.085232+00
66	51	4	2025-08-02	Present	\N	10	2025-08-13 10:44:04.085232+00
67	52	4	2025-08-02	Present	\N	10	2025-08-13 10:44:04.085232+00
27	6	4	2025-08-04	Present	\N	10	2025-08-07 15:17:04.033176+00
73	51	4	2025-08-04	Present	\N	10	2025-08-13 10:44:39.901547+00
74	52	4	2025-08-04	Present	\N	10	2025-08-13 10:44:39.901547+00
69	49	4	2025-08-04	Present	\N	10	2025-08-13 10:44:39.901547+00
70	12	4	2025-08-04	Present	\N	10	2025-08-13 10:44:39.901547+00
158	92	4	2025-08-04	Present	\N	10	2025-08-18 08:30:11.734077+00
72	70	4	2025-08-04	Present	\N	10	2025-08-13 10:44:39.901547+00
148	49	4	2025-08-03	Present	\N	10	2025-08-18 08:29:54.819019+00
149	12	4	2025-08-03	Present	\N	10	2025-08-18 08:29:54.819019+00
150	92	4	2025-08-03	Present	\N	10	2025-08-18 08:29:54.819019+00
151	6	4	2025-08-03	Present	\N	10	2025-08-18 08:29:54.819019+00
152	70	4	2025-08-03	Present	\N	10	2025-08-18 08:29:54.819019+00
153	51	4	2025-08-03	Present	\N	10	2025-08-18 08:29:54.819019+00
154	52	4	2025-08-03	Present	\N	10	2025-08-18 08:29:54.819019+00
155	54	4	2025-08-03	Present	\N	10	2025-08-18 08:29:54.819019+00
76	49	4	2025-08-05	Present	\N	10	2025-08-13 10:44:54.645088+00
77	12	4	2025-08-05	Present	\N	10	2025-08-13 10:44:54.645088+00
166	92	4	2025-08-05	Present	\N	10	2025-08-18 08:30:24.911102+00
78	6	4	2025-08-05	Present	\N	10	2025-08-13 10:44:54.645088+00
79	70	4	2025-08-05	Present	\N	10	2025-08-13 10:44:54.645088+00
80	51	4	2025-08-05	Present	\N	10	2025-08-13 10:44:54.645088+00
81	52	4	2025-08-05	Present	\N	10	2025-08-13 10:44:54.645088+00
25	12	4	2025-08-06	Absent	\N	10	2025-08-07 15:08:47.070085+00
174	92	4	2025-08-06	Present	\N	10	2025-08-18 08:30:39.886205+00
14	6	4	2025-08-06	Absent	\N	10	2025-08-07 09:41:03.347087+00
86	70	4	2025-08-06	Present	\N	10	2025-08-13 10:45:18.644532+00
87	51	4	2025-08-06	Present	\N	10	2025-08-13 10:45:18.644532+00
88	52	4	2025-08-06	Present	\N	10	2025-08-13 10:45:18.644532+00
90	49	4	2025-08-07	Present	\N	10	2025-08-13 10:45:32.246089+00
17	12	4	2025-08-07	Present	\N	10	2025-08-07 12:55:27.54209+00
182	92	4	2025-08-07	Present	\N	10	2025-08-18 08:30:55.921656+00
5	6	4	2025-08-07	Present	\N	10	2025-08-07 09:27:20.189809+00
93	70	4	2025-08-07	Present	\N	10	2025-08-13 10:45:32.246089+00
94	51	4	2025-08-07	Present	\N	10	2025-08-13 10:45:32.246089+00
95	52	4	2025-08-07	Present	\N	10	2025-08-13 10:45:32.246089+00
190	92	4	2025-08-08	Present	\N	10	2025-08-18 08:31:05.109485+00
16	6	4	2025-08-08	Present	\N	10	2025-08-07 09:26:16.317456+00
100	70	4	2025-08-08	Present	\N	10	2025-08-13 10:45:42.571654+00
37	51	4	2025-08-08	Present	\N	10	2025-08-08 09:16:58.952298+00
38	52	4	2025-08-08	Absent	\N	10	2025-08-08 09:16:58.952298+00
43	6	4	2025-08-09	Absent	\N	10	2025-08-09 13:13:20.840006+00
107	70	4	2025-08-09	Present	\N	10	2025-08-13 10:45:52.397131+00
44	51	4	2025-08-09	Present	\N	10	2025-08-09 13:13:20.840006+00
45	52	4	2025-08-09	Present	\N	10	2025-08-09 13:13:20.840006+00
124	54	4	2025-08-12	Present	\N	10	2025-08-13 10:46:16.617878+00
47	49	4	2025-08-13	Present	\N	10	2025-08-13 10:10:26.300385+00
48	12	4	2025-08-13	Present	\N	10	2025-08-13 10:10:26.300385+00
49	6	4	2025-08-13	Present	\N	10	2025-08-13 10:10:26.300385+00
50	70	4	2025-08-13	Present	\N	10	2025-08-13 10:10:26.300385+00
51	51	4	2025-08-13	Leave	\N	10	2025-08-13 10:10:26.300385+00
52	52	4	2025-08-13	Absent	\N	10	2025-08-13 10:10:26.300385+00
53	54	4	2025-08-13	Absent	\N	10	2025-08-13 10:10:26.300385+00
19	12	4	2025-08-20	Absent	\N	10	2025-08-07 14:55:48.045386+00
20	6	4	2025-08-20	Present	\N	10	2025-08-07 14:55:48.045386+00
68	54	4	2025-08-02	Present	\N	10	2025-08-13 10:44:04.085232+00
75	54	4	2025-08-04	Present	\N	10	2025-08-13 10:44:39.901547+00
82	54	4	2025-08-05	Present	\N	10	2025-08-13 10:44:54.645088+00
96	54	4	2025-08-07	Present	\N	10	2025-08-13 10:45:32.246089+00
117	54	4	2025-08-11	Present	\N	10	2025-08-13 10:46:05.653445+00
258	105	4	2025-08-01	Present	\N	10	2025-08-19 17:57:30.161511+00
61	54	4	2025-08-01	Present	\N	10	2025-08-13 10:27:01.783533+00
268	105	4	2025-08-02	Present	\N	10	2025-08-19 17:59:50.866161+00
279	105	4	2025-08-04	Present	\N	10	2025-08-19 18:00:07.080538+00
288	105	4	2025-08-05	Present	\N	10	2025-08-19 18:00:19.645668+00
298	105	4	2025-08-06	Present	\N	10	2025-08-19 18:00:36.508774+00
89	54	4	2025-08-06	Present	\N	10	2025-08-13 10:45:18.644532+00
308	105	4	2025-08-07	Present	\N	10	2025-08-19 18:00:49.706507+00
318	105	4	2025-08-08	Present	\N	10	2025-08-19 18:01:05.526566+00
39	54	4	2025-08-08	Absent	\N	10	2025-08-08 09:16:58.952298+00
201	92	4	2025-08-09	Present	\N	10	2025-08-18 08:31:14.121843+00
328	105	4	2025-08-09	Present	\N	10	2025-08-19 18:01:15.225568+00
46	54	4	2025-08-09	Present	\N	10	2025-08-09 13:13:20.840006+00
206	92	4	2025-08-11	Present	\N	10	2025-08-18 08:31:24.599556+00
338	105	4	2025-08-11	Present	\N	10	2025-08-19 18:01:25.531094+00
214	92	4	2025-08-12	Present	\N	10	2025-08-18 08:31:40.864813+00
349	105	4	2025-08-12	Present	\N	10	2025-08-19 18:01:35.096441+00
222	92	4	2025-08-13	Present	\N	10	2025-08-18 08:32:05.056941+00
358	105	4	2025-08-13	Present	\N	10	2025-08-19 18:01:47.476165+00
228	49	4	2025-08-14	Present	\N	10	2025-08-18 08:32:16.370907+00
229	12	4	2025-08-14	Present	\N	10	2025-08-18 08:32:16.370907+00
230	92	4	2025-08-14	Present	\N	10	2025-08-18 08:32:16.370907+00
231	6	4	2025-08-14	Present	\N	10	2025-08-18 08:32:16.370907+00
232	70	4	2025-08-14	Present	\N	10	2025-08-18 08:32:16.370907+00
233	51	4	2025-08-14	Present	\N	10	2025-08-18 08:32:16.370907+00
368	105	4	2025-08-14	Present	\N	10	2025-08-19 18:01:58.735981+00
234	52	4	2025-08-14	Present	\N	10	2025-08-18 08:32:16.370907+00
235	54	4	2025-08-14	Present	\N	10	2025-08-18 08:32:16.370907+00
236	49	4	2025-08-18	Present	\N	10	2025-08-18 08:32:23.423785+00
237	12	4	2025-08-18	Present	\N	10	2025-08-18 08:32:23.423785+00
238	92	4	2025-08-18	Present	\N	10	2025-08-18 08:32:23.423785+00
239	6	4	2025-08-18	Present	\N	10	2025-08-18 08:32:23.423785+00
240	70	4	2025-08-18	Present	\N	10	2025-08-18 08:32:23.423785+00
241	51	4	2025-08-18	Present	\N	10	2025-08-18 08:32:23.423785+00
378	105	4	2025-08-18	Present	\N	10	2025-08-19 18:02:11.235902+00
242	52	4	2025-08-18	Present	\N	10	2025-08-18 08:32:23.423785+00
243	54	4	2025-08-18	Present	\N	10	2025-08-18 08:32:23.423785+00
244	49	4	2025-08-19	Present	\N	10	2025-08-19 08:04:48.5524+00
245	12	4	2025-08-19	Absent	\N	10	2025-08-19 08:04:48.5524+00
246	92	4	2025-08-19	Absent	\N	10	2025-08-19 08:04:48.5524+00
247	6	4	2025-08-19	Absent	\N	10	2025-08-19 08:04:48.5524+00
248	70	4	2025-08-19	Absent	\N	10	2025-08-19 08:04:48.5524+00
249	51	4	2025-08-19	Absent	\N	10	2025-08-19 08:04:48.5524+00
388	105	4	2025-08-19	Present	\N	10	2025-08-19 18:02:25.778938+00
250	52	4	2025-08-19	Present	\N	10	2025-08-19 08:04:48.5524+00
251	54	4	2025-08-19	Absent	\N	10	2025-08-19 08:04:48.5524+00
392	49	4	2025-08-20	Present	\N	10	2025-08-20 09:51:22.71613+00
394	92	4	2025-08-20	Present	\N	10	2025-08-20 09:51:22.71613+00
396	70	4	2025-08-20	Present	\N	10	2025-08-20 09:51:22.71613+00
397	51	4	2025-08-20	Present	\N	10	2025-08-20 09:51:22.71613+00
398	105	4	2025-08-20	Present	\N	10	2025-08-20 09:51:22.71613+00
399	52	4	2025-08-20	Present	\N	10	2025-08-20 09:51:22.71613+00
400	54	4	2025-08-20	Present	\N	10	2025-08-20 09:51:22.71613+00
403	49	4	2025-08-22	Present	\N	10	2025-08-25 07:50:20.710687+00
404	12	4	2025-08-22	Present	\N	10	2025-08-25 07:50:20.710687+00
405	92	4	2025-08-22	Present	\N	10	2025-08-25 07:50:20.710687+00
406	6	4	2025-08-22	Present	\N	10	2025-08-25 07:50:20.710687+00
407	70	4	2025-08-22	Present	\N	10	2025-08-25 07:50:20.710687+00
409	51	4	2025-08-22	Present	\N	10	2025-08-25 07:50:20.710687+00
410	105	4	2025-08-22	Present	\N	10	2025-08-25 07:50:20.710687+00
411	52	4	2025-08-22	Present	\N	10	2025-08-25 07:50:20.710687+00
412	54	4	2025-08-22	Present	\N	10	2025-08-25 07:50:20.710687+00
416	49	4	2025-08-23	Present	\N	10	2025-08-25 07:50:30.55195+00
417	12	4	2025-08-23	Present	\N	10	2025-08-25 07:50:30.55195+00
418	92	4	2025-08-23	Present	\N	10	2025-08-25 07:50:30.55195+00
419	6	4	2025-08-23	Present	\N	10	2025-08-25 07:50:30.55195+00
420	70	4	2025-08-23	Present	\N	10	2025-08-25 07:50:30.55195+00
422	51	4	2025-08-23	Present	\N	10	2025-08-25 07:50:30.55195+00
423	105	4	2025-08-23	Present	\N	10	2025-08-25 07:50:30.55195+00
424	52	4	2025-08-23	Present	\N	10	2025-08-25 07:50:30.55195+00
425	54	4	2025-08-23	Present	\N	10	2025-08-25 07:50:30.55195+00
429	49	4	2025-08-25	Present	\N	10	2025-08-25 07:50:39.497137+00
430	12	4	2025-08-25	Present	\N	10	2025-08-25 07:50:39.497137+00
431	92	4	2025-08-25	Present	\N	10	2025-08-25 07:50:39.497137+00
432	6	4	2025-08-25	Present	\N	10	2025-08-25 07:50:39.497137+00
433	70	4	2025-08-25	Present	\N	10	2025-08-25 07:50:39.497137+00
435	51	4	2025-08-25	Present	\N	10	2025-08-25 07:50:39.497137+00
436	105	4	2025-08-25	Present	\N	10	2025-08-25 07:50:39.497137+00
437	52	4	2025-08-25	Present	\N	10	2025-08-25 07:50:39.497137+00
438	54	4	2025-08-25	Present	\N	10	2025-08-25 07:50:39.497137+00
443	49	4	2025-08-26	Present	\N	10	2025-08-26 16:17:08.744982+00
444	12	4	2025-08-26	Present	\N	10	2025-08-26 16:17:08.744982+00
445	92	4	2025-08-26	Present	\N	10	2025-08-26 16:17:08.744982+00
446	6	4	2025-08-26	Present	\N	10	2025-08-26 16:17:08.744982+00
447	70	4	2025-08-26	Present	\N	10	2025-08-26 16:17:08.744982+00
449	51	4	2025-08-26	Present	\N	10	2025-08-26 16:17:08.744982+00
450	105	4	2025-08-26	Present	\N	10	2025-08-26 16:17:08.744982+00
451	52	4	2025-08-26	Present	\N	10	2025-08-26 16:17:08.744982+00
452	54	4	2025-08-26	Present	\N	10	2025-08-26 16:17:08.744982+00
456	49	4	2025-08-21	Present	\N	10	2025-08-31 16:53:44.124141+00
457	12	4	2025-08-21	Present	\N	10	2025-08-31 16:53:44.124141+00
458	92	4	2025-08-21	Present	\N	10	2025-08-31 16:53:44.124141+00
459	6	4	2025-08-21	Present	\N	10	2025-08-31 16:53:44.124141+00
460	70	4	2025-08-21	Present	\N	10	2025-08-31 16:53:44.124141+00
461	51	4	2025-08-21	Present	\N	10	2025-08-31 16:53:44.124141+00
462	105	4	2025-08-21	Present	\N	10	2025-08-31 16:53:44.124141+00
463	52	4	2025-08-21	Present	\N	10	2025-08-31 16:53:44.124141+00
464	54	4	2025-08-21	Present	\N	10	2025-08-31 16:53:44.124141+00
\.


--
-- Data for Name: teacher_payroll; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.teacher_payroll (id, teacher_id, payroll_user_id, school_id, salary_month, salary_year, base_salary, total_working_days, present_days, absent_days, deduction_amount, total_incentives, net_salary_paid, payment_date, status) FROM stdin;
1	12	134	4	8	2025	10000.00	23	16.0	3	1304.35	200.00	7156.52	2025-08-26 08:17:00.084272+00	Paid
3	6	134	4	8	2025	100000.00	23	18.0	3	13043.48	2000.00	80260.87	2025-08-31 17:29:35.804271+00	Paid
\.


--
-- Data for Name: teacher_timings; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.teacher_timings (timing_id, teacher_id, day_of_week, opens_at, closes_at, is_closed) FROM stdin;
64	49	Monday	10:00:00	18:00:00	f
65	49	Tuesday	10:00:00	18:00:00	f
66	49	Wednesday	10:00:00	18:00:00	f
67	49	Thursday	10:00:00	18:00:00	f
68	49	Friday	10:00:00	18:00:00	f
69	49	Saturday	10:00:00	18:00:00	f
15	12	Monday	10:00:00	06:00:00	f
1	6	Monday	10:00:00	18:00:00	f
2	6	Tuesday	10:00:00	18:00:00	f
3	6	Wednesday	10:00:00	18:00:00	f
4	6	Thursday	10:00:00	18:00:00	f
5	6	Friday	10:00:00	18:00:00	f
6	6	Saturday	10:00:00	18:00:00	f
70	49	Sunday	\N	\N	t
16	12	Tuesday	10:00:00	06:00:00	f
17	12	Wednesday	10:00:00	06:00:00	f
18	12	Thursday	10:00:00	06:00:00	f
19	12	Friday	10:00:00	06:00:00	f
20	12	Saturday	10:00:00	06:00:00	f
106	54	Monday	10:00:00	18:00:00	f
107	54	Tuesday	10:00:00	18:00:00	f
225	70	Monday	10:00:00	18:00:00	f
108	54	Wednesday	10:00:00	18:00:00	f
109	54	Thursday	10:00:00	18:00:00	f
110	54	Friday	10:00:00	18:00:00	f
111	54	Saturday	10:00:00	18:00:00	f
246	92	Monday	10:00:00	06:00:00	f
71	51	Monday	10:00:00	18:00:00	f
247	92	Tuesday	10:00:00	06:00:00	f
21	12	Sunday	\N	\N	t
72	51	Tuesday	10:00:00	18:00:00	f
73	51	Wednesday	10:00:00	18:00:00	f
74	51	Thursday	10:00:00	18:00:00	f
75	51	Friday	10:00:00	18:00:00	f
76	51	Saturday	10:00:00	18:00:00	f
77	51	Sunday	\N	\N	t
248	92	Wednesday	10:00:00	06:00:00	f
249	92	Thursday	10:00:00	06:00:00	f
250	92	Friday	10:00:00	06:00:00	f
251	92	Saturday	10:00:00	06:00:00	f
252	92	Sunday	\N	\N	t
295	105	Monday	10:00:00	18:00:00	f
226	70	Tuesday	10:00:00	18:00:00	f
227	70	Wednesday	10:00:00	18:00:00	f
228	70	Thursday	10:00:00	18:00:00	f
229	70	Friday	10:00:00	18:00:00	f
230	70	Saturday	10:00:00	18:00:00	f
231	70	Sunday	\N	\N	t
92	52	Monday	10:00:00	06:00:00	f
93	52	Tuesday	10:00:00	06:00:00	f
94	52	Wednesday	10:00:00	06:00:00	f
95	52	Thursday	10:00:00	06:00:00	f
96	52	Friday	10:00:00	06:00:00	f
97	52	Saturday	10:00:00	06:00:00	f
98	52	Sunday	\N	\N	t
296	105	Tuesday	10:00:00	18:00:00	f
297	105	Wednesday	10:00:00	18:00:00	f
298	105	Thursday	10:00:00	18:00:00	f
299	105	Friday	10:00:00	18:00:00	f
300	105	Saturday	10:00:00	18:00:00	f
301	105	Sunday	10:00:00	18:00:00	f
7	6	Sunday	\N	\N	t
112	54	Sunday	\N	\N	t
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
51	teacher	jadeja@gmail.com	$2y$10$yzEERP8pUf.GaSHQrRtc6uLlzjr.ft1N6j/BS2KGqQzoykf289BGS	active	\N	\N
147	librarian	dinesh@gmail.com	$2y$10$e/ba0l3WZPzpCiKblkuMLOmVYTrB4k5WyrK/Zpe8WzyfoylNbCuIq	active	\N	\N
150	student	devamparekh1200@gmail.com	$2y$10$EAp3p41/hhr6/8KrZ5AtZu/e6AOOp5NBdW6yBxx.PkMD1OfMYDtvK	active	\N	\N
151	student	ravindra@gmail.com	$2y$10$DTDfibDinWNWk4ssatRx4e5q12feiW4bb/M5T4BgevYGawJek2NBW	active	\N	\N
152	student	rupali@gmail.com	$2y$10$syYFrJ7eZvKF62J8AVx6DOscekP9tS2qRlBJL0B1uIPc3.rbzD6fG	active	\N	\N
105	teacher	sapna@gmail.com	$2y$10$wpEpcFBLJ7Msu0z60MmiQu6n2q.FJ9QY1rl1SPH67b5uaixJl0gNG	active	\N	\N
10	principal	17fenill@gmail.com	$2y$10$EP56edKNSOvDiPXCub4iZuAI5sEVX3XjU1tnqJu8a4f.VS58QO5de	active	$2y$10$KK4weLbHrHxvW39CjFoO4eFD3zn0llDzxmiP4HWMyQ2cp63teJJ5.	2025-09-01 11:22:31
107	librarian	prakash@gmail.com	$2y$10$pub4gPRk6EW7Il4jxjo2Ouc4F87zBPcLdf9p12/FYFppQYSX8yTai	active	\N	\N
108	librarian	santosh@gmai.com	$2y$10$JM1uBHaBUqobLR9miaICKO8tuD7q2LKAQysccyTcY7Tt0SH5sUAK.	active	\N	\N
13	principal	dhaval@gmail.com	$2y$10$/PhOzkuBDiabEZAW5eIZKuEr9Gcr0NTvpE7mGegA1Z6oNalzKXQcW	active	\N	\N
54	teacher	tia@gmail.com	$2y$10$qtWaXyrRnRYI72QYxah1QOa9VXCChAc0/aFadc2i1m2x.ZEq6Euha	active	\N	\N
39	principal	sunny@gmail.com	$2y$10$B4ISjcDHMlF.dJwZ0X.LlOQT6ZKgHPQy.odp78wc8cEahPpkhMKKm	active	\N	\N
92	teacher	meera@gmail.com	$2y$10$8rmmBKRvJBYB3g0Ct1iIIOiZ.o1pBFnqsjXqfbcnO0qbaJ.QIu7a6	active	\N	\N
40	principal	viral@gmail.com	$2y$10$jKjdp7PnB3Ys3M7TnrfQtO97DNw8WPvNtldu7M1rX/ckGXx.P/9QW	active	\N	\N
12	teacher	jay@gmail.com	$2y$10$TUf4M/5ENm2A6oun27EuAuAz8Wlr8e8Ub8xwCR3w9i09nTBhFEWMO	active	\N	\N
52	teacher	tara@gmail.com	$2y$10$sjbggi23JLfNjwuNGmvKY.EvgUgWS9.nh7pA3vv5QjJGP75YBBxDO	active	\N	\N
49	teacher	ayushi@gmail.com	$2y$10$Vo893uZp26VV4sCkTWFXSePR9UxOVhLRZlnVtYthhIgdrm4kdIslO	active	\N	\N
70	teacher	rahul@gmail.com	$2y$10$QjT3TSvtutmr1Jl2REhPBuJ4c6B34xrmOkAOe7GwI0ms3sB7Ov.iW	active	\N	\N
42	principal	akshat@gmail.com	$2y$10$YW7sWUGqVcbuiHzwxwjShOajCzOXNSbqML9zPAzrM8.RWml.41iXW	active	\N	\N
134	payroll	prithvi@gmail.com	$2y$10$FcKBnpIqM9ypJ6iwb.yZYOHyCwMcF//0KR50/J5AV9lN7fhYih44K	active	\N	\N
6	teacher	meet@gmail.com	$2y$10$Dy/QvvkcnkheaPFyapfKR.9hzc/ZA5twsbVVqc6Gm.jR4nglV6Mv6	active	\N	\N
53	librarian	rohit@gmail.com	$2y$10$CX5ZFB02c4ncgdCCy9WplezqcHT0jAEaAAaI6uqr6fQHvj0FoSL8m	active	\N	\N
115	principal	rupesh@gmail.com	$2y$10$bE4j/mv0OVuqDix.gg1NLOTdhoDj2FYSBuDZ1hp6j.tubSV2vhwPm	active	\N	\N
121	principal	tanvi@gmail.com	$2y$10$IUDB1lEgEfLXwNDaCMukEu7LCw5BgJoav0cwNDUIxuTBJY9SuyMou	active	\N	\N
122	principal	jagurti@gmail.com	$2y$10$RTm5fWQRtc3XHs.nkd53t.rvJCGgTzRdBq6aFqOh6c49ZGaRxvoxe	active	\N	\N
36	librarian	devang@gmail.com	$2y$10$zmDytTauKLi/cqAY89QgT.CyVW7b4lgK.rwFVPdJbZf4zIsHGyYdu	active	\N	\N
149	student	harsh@gmail.com	$2y$10$H.8lBW0bupzwRTyCyqfTNu53VOf8tPze9TIUMfXGIw7MuLMpWvTP2	active	\N	\N
\.


--
-- Data for Name: vehicles; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.vehicles (id, school_id, vehicle_number, model, seating_capacity, insurance_expiry_date, created_at) FROM stdin;
1	4	GJ05-EA-1021	Tata Winger Skool 15S	15	2026-03-15	2025-08-19 16:13:28.830014+00
5	4	GJ-05-RZ-9633	Force Traveller 3350	50	2026-04-22	2025-08-21 11:52:27.201204+00
3	4	GJ-05-VA-7854	Force Traveller 3350	20	2026-07-20	2025-08-19 16:24:49.798191+00
4	4	GJ-05-BU-3399	SML Isuzu S7 School Bus	40	2027-01-05	2025-08-19 16:25:43.134205+00
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

COPY storage.buckets (id, name, owner, created_at, updated_at, public, avif_autodetection, file_size_limit, allowed_mime_types, owner_id, type) FROM stdin;
\.


--
-- Data for Name: buckets_analytics; Type: TABLE DATA; Schema: storage; Owner: supabase_storage_admin
--

COPY storage.buckets_analytics (id, type, format, created_at, updated_at) FROM stdin;
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
26	objects-prefixes	ef3f7871121cdc47a65308e6702519e853422ae2	2025-08-28 19:06:05.485933
27	search-v2	33b8f2a7ae53105f028e13e9fcda9dc4f356b4a2	2025-08-28 19:06:06.076398
28	object-bucket-name-sorting	ba85ec41b62c6a30a3f136788227ee47f311c436	2025-08-28 19:06:06.190375
29	create-prefixes	a7b1a22c0dc3ab630e3055bfec7ce7d2045c5b7b	2025-08-28 19:06:06.281995
30	update-object-levels	6c6f6cc9430d570f26284a24cf7b210599032db7	2025-08-28 19:06:06.378303
31	objects-level-index	33f1fef7ec7fea08bb892222f4f0f5d79bab5eb8	2025-08-28 19:06:06.387725
32	backward-compatible-index-on-objects	2d51eeb437a96868b36fcdfb1ddefdf13bef1647	2025-08-28 19:06:06.39984
33	backward-compatible-index-on-prefixes	fe473390e1b8c407434c0e470655945b110507bf	2025-08-28 19:06:06.476479
34	optimize-search-function-v1	82b0e469a00e8ebce495e29bfa70a0797f7ebd2c	2025-08-28 19:06:06.481559
35	add-insert-trigger-prefixes	63bb9fd05deb3dc5e9fa66c83e82b152f0caf589	2025-08-28 19:06:06.494104
36	optimise-existing-functions	81cf92eb0c36612865a18016a38496c530443899	2025-08-28 19:06:06.586041
37	add-bucket-name-length-trigger	3944135b4e3e8b22d6d4cbb568fe3b0b51df15c1	2025-08-28 19:06:06.686739
38	iceberg-catalog-flag-on-buckets	19a8bd89d5dfa69af7f222a46c726b7c41e462c5	2025-08-28 19:06:06.776795
\.


--
-- Data for Name: objects; Type: TABLE DATA; Schema: storage; Owner: supabase_storage_admin
--

COPY storage.objects (id, bucket_id, name, owner, created_at, updated_at, last_accessed_at, metadata, version, owner_id, user_metadata, level) FROM stdin;
\.


--
-- Data for Name: prefixes; Type: TABLE DATA; Schema: storage; Owner: supabase_storage_admin
--

COPY storage.prefixes (bucket_id, name, created_at, updated_at) FROM stdin;
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

SELECT pg_catalog.setval('public.assignment_submissions_id_seq', 18, true);


--
-- Name: assignments_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.assignments_id_seq', 24, true);


--
-- Name: attendance_id_new_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.attendance_id_new_seq', 8, true);


--
-- Name: book_requests_request_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.book_requests_request_id_seq', 16, true);


--
-- Name: books_book_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.books_book_id_seq', 7, true);


--
-- Name: borrow_requests_request_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.borrow_requests_request_id_seq', 20, true);


--
-- Name: borrowing_records_record_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.borrowing_records_record_id_seq', 22, true);


--
-- Name: deleted_books_archived_book_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.deleted_books_archived_book_id_seq', 3, true);


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

SELECT pg_catalog.setval('public.deleted_teachers_id_seq', 1, true);


--
-- Name: drivers_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.drivers_id_seq', 4, true);


--
-- Name: exam_timetables_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.exam_timetables_id_seq', 11, true);


--
-- Name: holidays_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.holidays_id_seq', 33, true);


--
-- Name: incentives_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.incentives_id_seq', 7, true);


--
-- Name: leave_applications_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.leave_applications_id_seq', 48, true);


--
-- Name: librarian_attendance_attendance_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.librarian_attendance_attendance_id_seq', 63, true);


--
-- Name: librarian_leave_applications_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.librarian_leave_applications_id_seq', 2, true);


--
-- Name: librarian_payroll_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.librarian_payroll_id_seq', 2, true);


--
-- Name: librarian_timings_timing_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.librarian_timings_timing_id_seq', 203, true);


--
-- Name: messages_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.messages_id_seq', 168, true);


--
-- Name: notes_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.notes_id_seq', 11, true);


--
-- Name: notice_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.notice_id_seq', 29, true);


--
-- Name: notifications_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.notifications_id_seq', 724, true);


--
-- Name: password_resets_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.password_resets_id_seq', 1, false);


--
-- Name: payroll_attendance_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.payroll_attendance_id_seq', 1, true);


--
-- Name: principal_attendance_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.principal_attendance_id_seq', 345, true);


--
-- Name: principal_payroll_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.principal_payroll_id_seq', 1, false);


--
-- Name: principal_timings_timing_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.principal_timings_timing_id_seq', 434, true);


--
-- Name: principal_to_bmc_notices_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.principal_to_bmc_notices_id_seq', 21, true);


--
-- Name: principal_to_librarian_notices_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.principal_to_librarian_notices_id_seq', 11, true);


--
-- Name: routes_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.routes_id_seq', 2, true);


--
-- Name: school_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.school_id_seq', 16, true);


--
-- Name: school_notice_recipients_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.school_notice_recipients_id_seq', 157, true);


--
-- Name: school_notices_content_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.school_notices_content_id_seq', 38, true);


--
-- Name: school_timetable_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.school_timetable_id_seq', 131, true);


--
-- Name: staff_incentives_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.staff_incentives_id_seq', 55, true);


--
-- Name: standard_subjects_std_subject_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.standard_subjects_std_subject_id_seq', 161, true);


--
-- Name: stops_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.stops_id_seq', 7, true);


--
-- Name: student_marks_mark_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.student_marks_mark_id_seq', 79, true);


--
-- Name: subjects_subject_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.subjects_subject_id_seq', 22, true);


--
-- Name: teacher_attendance_attendance_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.teacher_attendance_attendance_id_seq', 464, true);


--
-- Name: teacher_payroll_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.teacher_payroll_id_seq', 3, true);


--
-- Name: teacher_timings_timing_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.teacher_timings_timing_id_seq', 567, true);


--
-- Name: timetables_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.timetables_id_seq', 1, false);


--
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.users_id_seq', 152, true);


--
-- Name: vehicles_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.vehicles_id_seq', 5, true);


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
-- Name: oauth_clients oauth_clients_client_id_key; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.oauth_clients
    ADD CONSTRAINT oauth_clients_client_id_key UNIQUE (client_id);


--
-- Name: oauth_clients oauth_clients_pkey; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.oauth_clients
    ADD CONSTRAINT oauth_clients_pkey PRIMARY KEY (id);


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
-- Name: drivers drivers_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.drivers
    ADD CONSTRAINT drivers_pkey PRIMARY KEY (id);


--
-- Name: exam_timetables exam_timetables_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.exam_timetables
    ADD CONSTRAINT exam_timetables_pkey PRIMARY KEY (id);


--
-- Name: holidays holidays_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.holidays
    ADD CONSTRAINT holidays_pkey PRIMARY KEY (id);


--
-- Name: incentives incentives_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.incentives
    ADD CONSTRAINT incentives_pkey PRIMARY KEY (id);


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
-- Name: librarian_leave_applications librarian_leave_applications_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.librarian_leave_applications
    ADD CONSTRAINT librarian_leave_applications_pkey PRIMARY KEY (id);


--
-- Name: librarian_payroll librarian_payroll_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.librarian_payroll
    ADD CONSTRAINT librarian_payroll_pkey PRIMARY KEY (id);


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
-- Name: payroll_attendance payroll_attendance_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payroll_attendance
    ADD CONSTRAINT payroll_attendance_pkey PRIMARY KEY (id);


--
-- Name: payroll payroll_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payroll
    ADD CONSTRAINT payroll_pkey PRIMARY KEY (id);


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
-- Name: principal_payroll principal_payroll_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.principal_payroll
    ADD CONSTRAINT principal_payroll_pkey PRIMARY KEY (id);


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
-- Name: routes routes_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.routes
    ADD CONSTRAINT routes_pkey PRIMARY KEY (id);


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
-- Name: staff_incentives staff_incentives_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.staff_incentives
    ADD CONSTRAINT staff_incentives_pkey PRIMARY KEY (id);


--
-- Name: standard_categories_mapping standard_categories_mapping_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.standard_categories_mapping
    ADD CONSTRAINT standard_categories_mapping_pkey PRIMARY KEY (category_name, standard_name);


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
-- Name: stops stops_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.stops
    ADD CONSTRAINT stops_pkey PRIMARY KEY (id);


--
-- Name: student student_email_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.student
    ADD CONSTRAINT student_email_key UNIQUE (email);


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
-- Name: teacher teacher_license_number_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.teacher
    ADD CONSTRAINT teacher_license_number_key UNIQUE (license_number);


--
-- Name: teacher_payroll teacher_payroll_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.teacher_payroll
    ADD CONSTRAINT teacher_payroll_pkey PRIMARY KEY (id);


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
-- Name: holidays unique_holiday; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.holidays
    ADD CONSTRAINT unique_holiday UNIQUE (school_id, holiday_date);


--
-- Name: incentives unique_incentive_name_per_school; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.incentives
    ADD CONSTRAINT unique_incentive_name_per_school UNIQUE (school_id, incentive_name);


--
-- Name: librarian_timings unique_librarian_day; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.librarian_timings
    ADD CONSTRAINT unique_librarian_day UNIQUE (librarian_id, day_of_week);


--
-- Name: librarian unique_librarian_license_number; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.librarian
    ADD CONSTRAINT unique_librarian_license_number UNIQUE (license_number);


--
-- Name: librarian unique_librarian_school_batch; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.librarian
    ADD CONSTRAINT unique_librarian_school_batch UNIQUE (school_id, batch);


--
-- Name: principal unique_principal_license_number; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.principal
    ADD CONSTRAINT unique_principal_license_number UNIQUE (license_number);


--
-- Name: student unique_student_license_number; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.student
    ADD CONSTRAINT unique_student_license_number UNIQUE (license_number);


--
-- Name: teacher unique_teacher_license_number; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.teacher
    ADD CONSTRAINT unique_teacher_license_number UNIQUE (license_number);


--
-- Name: teacher uq_class_teacher_std_batch; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.teacher
    ADD CONSTRAINT uq_class_teacher_std_batch UNIQUE (school_id, batch, class_teacher_std);


--
-- Name: school_timetable uq_class_time_conflict; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.school_timetable
    ADD CONSTRAINT uq_class_time_conflict UNIQUE (school_id, standard, day_of_week, start_time);


--
-- Name: librarian_attendance uq_librarian_attendance_date; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.librarian_attendance
    ADD CONSTRAINT uq_librarian_attendance_date UNIQUE (librarian_id, attendance_date);


--
-- Name: drivers uq_license_number; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.drivers
    ADD CONSTRAINT uq_license_number UNIQUE (school_id, license_number);


--
-- Name: payroll_attendance uq_payroll_attendance_date; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payroll_attendance
    ADD CONSTRAINT uq_payroll_attendance_date UNIQUE (payroll_id, attendance_date);


--
-- Name: student uq_student_rollno_std_year; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.student
    ADD CONSTRAINT uq_student_rollno_std_year UNIQUE (school_id, std, rollno, academic_year);


--
-- Name: teacher uq_teacher_phone; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.teacher
    ADD CONSTRAINT uq_teacher_phone UNIQUE (phone);


--
-- Name: school_timetable uq_teacher_time_conflict; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.school_timetable
    ADD CONSTRAINT uq_teacher_time_conflict UNIQUE (teacher_id, day_of_week, start_time);


--
-- Name: vehicles uq_vehicle_number; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.vehicles
    ADD CONSTRAINT uq_vehicle_number UNIQUE (school_id, vehicle_number);


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
-- Name: vehicles vehicles_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.vehicles
    ADD CONSTRAINT vehicles_pkey PRIMARY KEY (id);


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
-- Name: buckets_analytics buckets_analytics_pkey; Type: CONSTRAINT; Schema: storage; Owner: supabase_storage_admin
--

ALTER TABLE ONLY storage.buckets_analytics
    ADD CONSTRAINT buckets_analytics_pkey PRIMARY KEY (id);


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
-- Name: prefixes prefixes_pkey; Type: CONSTRAINT; Schema: storage; Owner: supabase_storage_admin
--

ALTER TABLE ONLY storage.prefixes
    ADD CONSTRAINT prefixes_pkey PRIMARY KEY (bucket_id, level, name);


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
-- Name: oauth_clients_client_id_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX oauth_clients_client_id_idx ON auth.oauth_clients USING btree (client_id);


--
-- Name: oauth_clients_deleted_at_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX oauth_clients_deleted_at_idx ON auth.oauth_clients USING btree (deleted_at);


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
-- Name: sso_providers_resource_id_pattern_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX sso_providers_resource_id_pattern_idx ON auth.sso_providers USING btree (resource_id text_pattern_ops);


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
-- Name: idx_name_bucket_level_unique; Type: INDEX; Schema: storage; Owner: supabase_storage_admin
--

CREATE UNIQUE INDEX idx_name_bucket_level_unique ON storage.objects USING btree (name COLLATE "C", bucket_id, level);


--
-- Name: idx_objects_bucket_id_name; Type: INDEX; Schema: storage; Owner: supabase_storage_admin
--

CREATE INDEX idx_objects_bucket_id_name ON storage.objects USING btree (bucket_id, name COLLATE "C");


--
-- Name: idx_objects_lower_name; Type: INDEX; Schema: storage; Owner: supabase_storage_admin
--

CREATE INDEX idx_objects_lower_name ON storage.objects USING btree ((path_tokens[level]), lower(name) text_pattern_ops, bucket_id, level);


--
-- Name: idx_prefixes_lower_name; Type: INDEX; Schema: storage; Owner: supabase_storage_admin
--

CREATE INDEX idx_prefixes_lower_name ON storage.prefixes USING btree (bucket_id, level, ((string_to_array(name, '/'::text))[level]), lower(name) text_pattern_ops);


--
-- Name: name_prefix_search; Type: INDEX; Schema: storage; Owner: supabase_storage_admin
--

CREATE INDEX name_prefix_search ON storage.objects USING btree (name text_pattern_ops);


--
-- Name: objects_bucket_id_level_idx; Type: INDEX; Schema: storage; Owner: supabase_storage_admin
--

CREATE UNIQUE INDEX objects_bucket_id_level_idx ON storage.objects USING btree (bucket_id, level, name COLLATE "C");


--
-- Name: subscription tr_check_filters; Type: TRIGGER; Schema: realtime; Owner: supabase_admin
--

CREATE TRIGGER tr_check_filters BEFORE INSERT OR UPDATE ON realtime.subscription FOR EACH ROW EXECUTE FUNCTION realtime.subscription_check_filters();


--
-- Name: buckets enforce_bucket_name_length_trigger; Type: TRIGGER; Schema: storage; Owner: supabase_storage_admin
--

CREATE TRIGGER enforce_bucket_name_length_trigger BEFORE INSERT OR UPDATE OF name ON storage.buckets FOR EACH ROW EXECUTE FUNCTION storage.enforce_bucket_name_length();


--
-- Name: objects objects_delete_delete_prefix; Type: TRIGGER; Schema: storage; Owner: supabase_storage_admin
--

CREATE TRIGGER objects_delete_delete_prefix AFTER DELETE ON storage.objects FOR EACH ROW EXECUTE FUNCTION storage.delete_prefix_hierarchy_trigger();


--
-- Name: objects objects_insert_create_prefix; Type: TRIGGER; Schema: storage; Owner: supabase_storage_admin
--

CREATE TRIGGER objects_insert_create_prefix BEFORE INSERT ON storage.objects FOR EACH ROW EXECUTE FUNCTION storage.objects_insert_prefix_trigger();


--
-- Name: objects objects_update_create_prefix; Type: TRIGGER; Schema: storage; Owner: supabase_storage_admin
--

CREATE TRIGGER objects_update_create_prefix BEFORE UPDATE ON storage.objects FOR EACH ROW WHEN (((new.name <> old.name) OR (new.bucket_id <> old.bucket_id))) EXECUTE FUNCTION storage.objects_update_prefix_trigger();


--
-- Name: prefixes prefixes_create_hierarchy; Type: TRIGGER; Schema: storage; Owner: supabase_storage_admin
--

CREATE TRIGGER prefixes_create_hierarchy BEFORE INSERT ON storage.prefixes FOR EACH ROW WHEN ((pg_trigger_depth() < 1)) EXECUTE FUNCTION storage.prefixes_insert_trigger();


--
-- Name: prefixes prefixes_delete_hierarchy; Type: TRIGGER; Schema: storage; Owner: supabase_storage_admin
--

CREATE TRIGGER prefixes_delete_hierarchy AFTER DELETE ON storage.prefixes FOR EACH ROW EXECUTE FUNCTION storage.delete_prefix_hierarchy_trigger();


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
-- Name: staff_incentives fk_assigned_by; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.staff_incentives
    ADD CONSTRAINT fk_assigned_by FOREIGN KEY (assigned_by_user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: routes fk_driver; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.routes
    ADD CONSTRAINT fk_driver FOREIGN KEY (driver_id) REFERENCES public.drivers(id) ON DELETE SET NULL;


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
-- Name: staff_incentives fk_incentive; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.staff_incentives
    ADD CONSTRAINT fk_incentive FOREIGN KEY (incentive_id) REFERENCES public.incentives(id) ON DELETE CASCADE;


--
-- Name: leave_applications fk_leave_teacher_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.leave_applications
    ADD CONSTRAINT fk_leave_teacher_id FOREIGN KEY (teacher_id) REFERENCES public.teacher(id) ON DELETE CASCADE;


--
-- Name: librarian_payroll fk_librarian; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.librarian_payroll
    ADD CONSTRAINT fk_librarian FOREIGN KEY (librarian_id) REFERENCES public.librarian(id) ON DELETE CASCADE;


--
-- Name: librarian_leave_applications fk_librarian_leave_librarian_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.librarian_leave_applications
    ADD CONSTRAINT fk_librarian_leave_librarian_id FOREIGN KEY (librarian_id) REFERENCES public.librarian(id) ON DELETE CASCADE;


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
-- Name: payroll fk_payroll_school; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payroll
    ADD CONSTRAINT fk_payroll_school FOREIGN KEY (school_id) REFERENCES public.school(id) ON DELETE CASCADE;


--
-- Name: payroll fk_payroll_user; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payroll
    ADD CONSTRAINT fk_payroll_user FOREIGN KEY (id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: teacher_payroll fk_payroll_user; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.teacher_payroll
    ADD CONSTRAINT fk_payroll_user FOREIGN KEY (payroll_user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: principal_payroll fk_payroll_user; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.principal_payroll
    ADD CONSTRAINT fk_payroll_user FOREIGN KEY (payroll_user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: librarian_payroll fk_payroll_user; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.librarian_payroll
    ADD CONSTRAINT fk_payroll_user FOREIGN KEY (payroll_user_id) REFERENCES public.users(id) ON DELETE SET NULL;


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
-- Name: principal_payroll fk_principal; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.principal_payroll
    ADD CONSTRAINT fk_principal FOREIGN KEY (principal_id) REFERENCES public.principal(id) ON DELETE CASCADE;


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
-- Name: stops fk_route; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.stops
    ADD CONSTRAINT fk_route FOREIGN KEY (route_id) REFERENCES public.routes(id) ON DELETE CASCADE;


--
-- Name: holidays fk_school; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.holidays
    ADD CONSTRAINT fk_school FOREIGN KEY (school_id) REFERENCES public.school(id) ON DELETE CASCADE;


--
-- Name: vehicles fk_school; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.vehicles
    ADD CONSTRAINT fk_school FOREIGN KEY (school_id) REFERENCES public.school(id) ON DELETE CASCADE;


--
-- Name: drivers fk_school; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.drivers
    ADD CONSTRAINT fk_school FOREIGN KEY (school_id) REFERENCES public.school(id) ON DELETE CASCADE;


--
-- Name: routes fk_school; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.routes
    ADD CONSTRAINT fk_school FOREIGN KEY (school_id) REFERENCES public.school(id) ON DELETE CASCADE;


--
-- Name: incentives fk_school; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.incentives
    ADD CONSTRAINT fk_school FOREIGN KEY (school_id) REFERENCES public.school(id) ON DELETE CASCADE;


--
-- Name: teacher_payroll fk_school; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.teacher_payroll
    ADD CONSTRAINT fk_school FOREIGN KEY (school_id) REFERENCES public.school(id) ON DELETE CASCADE;


--
-- Name: principal_payroll fk_school; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.principal_payroll
    ADD CONSTRAINT fk_school FOREIGN KEY (school_id) REFERENCES public.school(id) ON DELETE CASCADE;


--
-- Name: librarian_payroll fk_school; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.librarian_payroll
    ADD CONSTRAINT fk_school FOREIGN KEY (school_id) REFERENCES public.school(id) ON DELETE CASCADE;


--
-- Name: staff_incentives fk_staff; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.staff_incentives
    ADD CONSTRAINT fk_staff FOREIGN KEY (staff_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: student fk_student_stop; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.student
    ADD CONSTRAINT fk_student_stop FOREIGN KEY (stop_id) REFERENCES public.stops(id) ON DELETE SET NULL;


--
-- Name: student fk_student_user_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.student
    ADD CONSTRAINT fk_student_user_id FOREIGN KEY (id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: teacher_payroll fk_teacher; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.teacher_payroll
    ADD CONSTRAINT fk_teacher FOREIGN KEY (teacher_id) REFERENCES public.teacher(id) ON DELETE CASCADE;


--
-- Name: teacher_attendance fk_teacher_attendance_teacher_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.teacher_attendance
    ADD CONSTRAINT fk_teacher_attendance_teacher_id FOREIGN KEY (teacher_id) REFERENCES public.teacher(id) ON DELETE CASCADE;


--
-- Name: teacher_timings fk_teacher_timings_teacher_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.teacher_timings
    ADD CONSTRAINT fk_teacher_timings_teacher_id FOREIGN KEY (teacher_id) REFERENCES public.teacher(id) ON DELETE CASCADE;


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
-- Name: routes fk_vehicle; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.routes
    ADD CONSTRAINT fk_vehicle FOREIGN KEY (vehicle_id) REFERENCES public.vehicles(id) ON DELETE SET NULL;


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
-- Name: payroll_attendance payroll_attendance_payroll_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payroll_attendance
    ADD CONSTRAINT payroll_attendance_payroll_id_fkey FOREIGN KEY (payroll_id) REFERENCES public.payroll(id) ON DELETE CASCADE;


--
-- Name: payroll_attendance payroll_attendance_school_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payroll_attendance
    ADD CONSTRAINT payroll_attendance_school_id_fkey FOREIGN KEY (school_id) REFERENCES public.school(id) ON DELETE CASCADE;


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
-- Name: prefixes prefixes_bucketId_fkey; Type: FK CONSTRAINT; Schema: storage; Owner: supabase_storage_admin
--

ALTER TABLE ONLY storage.prefixes
    ADD CONSTRAINT "prefixes_bucketId_fkey" FOREIGN KEY (bucket_id) REFERENCES storage.buckets(id);


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
-- Name: buckets_analytics; Type: ROW SECURITY; Schema: storage; Owner: supabase_storage_admin
--

ALTER TABLE storage.buckets_analytics ENABLE ROW LEVEL SECURITY;

--
-- Name: migrations; Type: ROW SECURITY; Schema: storage; Owner: supabase_storage_admin
--

ALTER TABLE storage.migrations ENABLE ROW LEVEL SECURITY;

--
-- Name: objects; Type: ROW SECURITY; Schema: storage; Owner: supabase_storage_admin
--

ALTER TABLE storage.objects ENABLE ROW LEVEL SECURITY;

--
-- Name: prefixes; Type: ROW SECURITY; Schema: storage; Owner: supabase_storage_admin
--

ALTER TABLE storage.prefixes ENABLE ROW LEVEL SECURITY;

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
-- Name: TABLE oauth_clients; Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT ALL ON TABLE auth.oauth_clients TO postgres;
GRANT ALL ON TABLE auth.oauth_clients TO dashboard_user;


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
-- Name: SEQUENCE attendance_id_new_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.attendance_id_new_seq TO anon;
GRANT ALL ON SEQUENCE public.attendance_id_new_seq TO authenticated;
GRANT ALL ON SEQUENCE public.attendance_id_new_seq TO service_role;


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
-- Name: TABLE drivers; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.drivers TO anon;
GRANT ALL ON TABLE public.drivers TO authenticated;
GRANT ALL ON TABLE public.drivers TO service_role;


--
-- Name: SEQUENCE drivers_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.drivers_id_seq TO anon;
GRANT ALL ON SEQUENCE public.drivers_id_seq TO authenticated;
GRANT ALL ON SEQUENCE public.drivers_id_seq TO service_role;


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
-- Name: TABLE holidays; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.holidays TO anon;
GRANT ALL ON TABLE public.holidays TO authenticated;
GRANT ALL ON TABLE public.holidays TO service_role;


--
-- Name: SEQUENCE holidays_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.holidays_id_seq TO anon;
GRANT ALL ON SEQUENCE public.holidays_id_seq TO authenticated;
GRANT ALL ON SEQUENCE public.holidays_id_seq TO service_role;


--
-- Name: TABLE incentives; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.incentives TO anon;
GRANT ALL ON TABLE public.incentives TO authenticated;
GRANT ALL ON TABLE public.incentives TO service_role;


--
-- Name: SEQUENCE incentives_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.incentives_id_seq TO anon;
GRANT ALL ON SEQUENCE public.incentives_id_seq TO authenticated;
GRANT ALL ON SEQUENCE public.incentives_id_seq TO service_role;


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
-- Name: SEQUENCE librarian_leave_applications_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.librarian_leave_applications_id_seq TO anon;
GRANT ALL ON SEQUENCE public.librarian_leave_applications_id_seq TO authenticated;
GRANT ALL ON SEQUENCE public.librarian_leave_applications_id_seq TO service_role;


--
-- Name: TABLE librarian_leave_applications; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.librarian_leave_applications TO anon;
GRANT ALL ON TABLE public.librarian_leave_applications TO authenticated;
GRANT ALL ON TABLE public.librarian_leave_applications TO service_role;


--
-- Name: TABLE librarian_payroll; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.librarian_payroll TO anon;
GRANT ALL ON TABLE public.librarian_payroll TO authenticated;
GRANT ALL ON TABLE public.librarian_payroll TO service_role;


--
-- Name: SEQUENCE librarian_payroll_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.librarian_payroll_id_seq TO anon;
GRANT ALL ON SEQUENCE public.librarian_payroll_id_seq TO authenticated;
GRANT ALL ON SEQUENCE public.librarian_payroll_id_seq TO service_role;


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
-- Name: TABLE payroll; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.payroll TO anon;
GRANT ALL ON TABLE public.payroll TO authenticated;
GRANT ALL ON TABLE public.payroll TO service_role;


--
-- Name: TABLE payroll_attendance; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.payroll_attendance TO anon;
GRANT ALL ON TABLE public.payroll_attendance TO authenticated;
GRANT ALL ON TABLE public.payroll_attendance TO service_role;


--
-- Name: SEQUENCE payroll_attendance_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.payroll_attendance_id_seq TO anon;
GRANT ALL ON SEQUENCE public.payroll_attendance_id_seq TO authenticated;
GRANT ALL ON SEQUENCE public.payroll_attendance_id_seq TO service_role;


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
-- Name: TABLE principal_payroll; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.principal_payroll TO anon;
GRANT ALL ON TABLE public.principal_payroll TO authenticated;
GRANT ALL ON TABLE public.principal_payroll TO service_role;


--
-- Name: SEQUENCE principal_payroll_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.principal_payroll_id_seq TO anon;
GRANT ALL ON SEQUENCE public.principal_payroll_id_seq TO authenticated;
GRANT ALL ON SEQUENCE public.principal_payroll_id_seq TO service_role;


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
-- Name: TABLE routes; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.routes TO anon;
GRANT ALL ON TABLE public.routes TO authenticated;
GRANT ALL ON TABLE public.routes TO service_role;


--
-- Name: SEQUENCE routes_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.routes_id_seq TO anon;
GRANT ALL ON SEQUENCE public.routes_id_seq TO authenticated;
GRANT ALL ON SEQUENCE public.routes_id_seq TO service_role;


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
-- Name: TABLE staff_incentives; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.staff_incentives TO anon;
GRANT ALL ON TABLE public.staff_incentives TO authenticated;
GRANT ALL ON TABLE public.staff_incentives TO service_role;


--
-- Name: SEQUENCE staff_incentives_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.staff_incentives_id_seq TO anon;
GRANT ALL ON SEQUENCE public.staff_incentives_id_seq TO authenticated;
GRANT ALL ON SEQUENCE public.staff_incentives_id_seq TO service_role;


--
-- Name: TABLE standard_categories_mapping; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.standard_categories_mapping TO anon;
GRANT ALL ON TABLE public.standard_categories_mapping TO authenticated;
GRANT ALL ON TABLE public.standard_categories_mapping TO service_role;


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
-- Name: TABLE stops; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.stops TO anon;
GRANT ALL ON TABLE public.stops TO authenticated;
GRANT ALL ON TABLE public.stops TO service_role;


--
-- Name: SEQUENCE stops_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.stops_id_seq TO anon;
GRANT ALL ON SEQUENCE public.stops_id_seq TO authenticated;
GRANT ALL ON SEQUENCE public.stops_id_seq TO service_role;


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
-- Name: TABLE teacher_payroll; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.teacher_payroll TO anon;
GRANT ALL ON TABLE public.teacher_payroll TO authenticated;
GRANT ALL ON TABLE public.teacher_payroll TO service_role;


--
-- Name: SEQUENCE teacher_payroll_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.teacher_payroll_id_seq TO anon;
GRANT ALL ON SEQUENCE public.teacher_payroll_id_seq TO authenticated;
GRANT ALL ON SEQUENCE public.teacher_payroll_id_seq TO service_role;


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
-- Name: TABLE vehicles; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.vehicles TO anon;
GRANT ALL ON TABLE public.vehicles TO authenticated;
GRANT ALL ON TABLE public.vehicles TO service_role;


--
-- Name: SEQUENCE vehicles_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.vehicles_id_seq TO anon;
GRANT ALL ON SEQUENCE public.vehicles_id_seq TO authenticated;
GRANT ALL ON SEQUENCE public.vehicles_id_seq TO service_role;


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
-- Name: TABLE buckets_analytics; Type: ACL; Schema: storage; Owner: supabase_storage_admin
--

GRANT ALL ON TABLE storage.buckets_analytics TO service_role;
GRANT ALL ON TABLE storage.buckets_analytics TO authenticated;
GRANT ALL ON TABLE storage.buckets_analytics TO anon;


--
-- Name: TABLE objects; Type: ACL; Schema: storage; Owner: supabase_storage_admin
--

GRANT ALL ON TABLE storage.objects TO anon;
GRANT ALL ON TABLE storage.objects TO authenticated;
GRANT ALL ON TABLE storage.objects TO service_role;
GRANT ALL ON TABLE storage.objects TO postgres WITH GRANT OPTION;


--
-- Name: TABLE prefixes; Type: ACL; Schema: storage; Owner: supabase_storage_admin
--

GRANT ALL ON TABLE storage.prefixes TO service_role;
GRANT ALL ON TABLE storage.prefixes TO authenticated;
GRANT ALL ON TABLE storage.prefixes TO anon;


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

