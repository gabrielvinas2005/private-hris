--
-- PostgreSQL database dump
--

-- Dumped from database version 12.8
-- Dumped by pg_dump version 12.8

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: sp_employee_departments(integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.sp_employee_departments(dep_id integer) RETURNS TABLE(employee character varying, department character varying)
    LANGUAGE plpgsql
    AS $$

	begin

		return query

		select a.first_name,b.name from employees as a inner join departments as b on a.department_id = b.id

		where a.department_id = dep_id;

	end;

$$;


ALTER FUNCTION public.sp_employee_departments(dep_id integer) OWNER TO postgres;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: access; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.access (
    id bigint NOT NULL,
    user_id integer NOT NULL,
    menu_id integer NOT NULL,
    status boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.access OWNER TO postgres;

--
-- Name: access_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.access_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.access_id_seq OWNER TO postgres;

--
-- Name: access_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.access_id_seq OWNED BY public.access.id;


--
-- Name: adjectival_ratings; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.adjectival_ratings (
    id bigint NOT NULL,
    numerical_rating1 numeric(8,2) NOT NULL,
    numerical_rating2 numeric(8,2) NOT NULL,
    adjectival_rating character varying(255) NOT NULL,
    active boolean DEFAULT true,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.adjectival_ratings OWNER TO postgres;

--
-- Name: adjectival_ratings_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.adjectival_ratings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.adjectival_ratings_id_seq OWNER TO postgres;

--
-- Name: adjectival_ratings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.adjectival_ratings_id_seq OWNED BY public.adjectival_ratings.id;


--
-- Name: applicant_details; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.applicant_details (
    id bigint NOT NULL,
    applicant_id integer DEFAULT 0 NOT NULL,
    application_status_id integer NOT NULL,
    position_applied_id integer NOT NULL,
    is_plantilla boolean NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.applicant_details OWNER TO postgres;

--
-- Name: applicant_details_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.applicant_details_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.applicant_details_id_seq OWNER TO postgres;

--
-- Name: applicant_details_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.applicant_details_id_seq OWNED BY public.applicant_details.id;


--
-- Name: applicant_headers; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.applicant_headers (
    id bigint NOT NULL,
    applicant_no character varying(255) NOT NULL,
    photo text,
    first_name character varying(255),
    middle_name character varying(255),
    last_name character varying(255),
    address character varying(255),
    birth_date date,
    age integer DEFAULT 0,
    gender integer DEFAULT 0 NOT NULL,
    mobile_no character varying(255),
    email character varying(255) NOT NULL,
    employee_no character varying(255),
    resume character varying(255),
    application_status_id integer DEFAULT 0 NOT NULL,
    application_date date,
    user_id integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.applicant_headers OWNER TO postgres;

--
-- Name: applicant_headers_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.applicant_headers_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.applicant_headers_id_seq OWNER TO postgres;

--
-- Name: applicant_headers_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.applicant_headers_id_seq OWNED BY public.applicant_headers.id;


--
-- Name: application_status; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.application_status (
    id bigint NOT NULL,
    name character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.application_status OWNER TO postgres;

--
-- Name: application_status_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.application_status_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.application_status_id_seq OWNER TO postgres;

--
-- Name: application_status_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.application_status_id_seq OWNED BY public.application_status.id;


--
-- Name: audits; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.audits (
    id bigint NOT NULL,
    user_id integer,
    module character varying(255),
    menu character varying(255),
    activity character varying(255),
    description character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.audits OWNER TO postgres;

--
-- Name: audit_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.audit_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.audit_id_seq OWNER TO postgres;

--
-- Name: audit_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.audit_id_seq OWNED BY public.audits.id;


--
-- Name: biometric_logs; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.biometric_logs (
    id bigint NOT NULL,
    access_no character varying(255),
    date date,
    am_in time(0) without time zone,
    am_out time(0) without time zone,
    break_in time(0) without time zone,
    break_out time(0) without time zone,
    pm_in time(0) without time zone,
    pm_out time(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.biometric_logs OWNER TO postgres;

--
-- Name: biometric_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.biometric_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.biometric_logs_id_seq OWNER TO postgres;

--
-- Name: biometric_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.biometric_logs_id_seq OWNED BY public.biometric_logs.id;


--
-- Name: blood_types; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.blood_types (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    active boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.blood_types OWNER TO postgres;

--
-- Name: blood_types_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.blood_types_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.blood_types_id_seq OWNER TO postgres;

--
-- Name: blood_types_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.blood_types_id_seq OWNED BY public.blood_types.id;


--
-- Name: branches; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.branches (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.branches OWNER TO postgres;

--
-- Name: branches_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.branches_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.branches_id_seq OWNER TO postgres;

--
-- Name: branches_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.branches_id_seq OWNED BY public.branches.id;


--
-- Name: citizenships; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.citizenships (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    active boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.citizenships OWNER TO postgres;

--
-- Name: citizenships_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.citizenships_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.citizenships_id_seq OWNER TO postgres;

--
-- Name: citizenships_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.citizenships_id_seq OWNED BY public.citizenships.id;


--
-- Name: civil_status; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.civil_status (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    active boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.civil_status OWNER TO postgres;

--
-- Name: civil_status_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.civil_status_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.civil_status_id_seq OWNER TO postgres;

--
-- Name: civil_status_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.civil_status_id_seq OWNED BY public.civil_status.id;


--
-- Name: companies; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.companies (
    id bigint NOT NULL,
    logo text DEFAULT ''::character varying NOT NULL,
    name character varying(255) NOT NULL,
    address character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    telephone_no character varying(255) NOT NULL,
    mobile_no character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.companies OWNER TO postgres;

--
-- Name: companies_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.companies_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.companies_id_seq OWNER TO postgres;

--
-- Name: companies_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.companies_id_seq OWNED BY public.companies.id;


--
-- Name: deductions; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.deductions (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    active boolean DEFAULT true,
    is_sss boolean DEFAULT false,
    is_gsis boolean DEFAULT false,
    is_philhealth boolean DEFAULT false,
    is_pagibig boolean DEFAULT false,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.deductions OWNER TO postgres;

--
-- Name: deductions_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.deductions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.deductions_id_seq OWNER TO postgres;

--
-- Name: deductions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.deductions_id_seq OWNED BY public.deductions.id;


--
-- Name: departments; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.departments (
    id bigint NOT NULL,
    code character varying(255) DEFAULT '-'::character varying NOT NULL,
    name character varying(255) NOT NULL,
    functionality character varying(255),
    is_academic boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    active boolean DEFAULT false NOT NULL,
    employee_id integer DEFAULT 0
);


ALTER TABLE public.departments OWNER TO postgres;

--
-- Name: departments_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.departments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.departments_id_seq OWNER TO postgres;

--
-- Name: departments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.departments_id_seq OWNED BY public.departments.id;


--
-- Name: divisions; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.divisions (
    id bigint NOT NULL,
    code character varying(255) DEFAULT '-'::character varying NOT NULL,
    name character varying(255) NOT NULL,
    active boolean DEFAULT true,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.divisions OWNER TO postgres;

--
-- Name: divisions_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.divisions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.divisions_id_seq OWNER TO postgres;

--
-- Name: divisions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.divisions_id_seq OWNED BY public.divisions.id;


--
-- Name: eligibilities; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.eligibilities (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    active boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.eligibilities OWNER TO postgres;

--
-- Name: eligibilities_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.eligibilities_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.eligibilities_id_seq OWNER TO postgres;

--
-- Name: eligibilities_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.eligibilities_id_seq OWNED BY public.eligibilities.id;


--
-- Name: employee_children; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.employee_children (
    children_id bigint NOT NULL,
    employee_id integer DEFAULT 0 NOT NULL,
    child_name character varying(255),
    child_birthdate date,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.employee_children OWNER TO postgres;

--
-- Name: employee_children_children_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.employee_children_children_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.employee_children_children_id_seq OWNER TO postgres;

--
-- Name: employee_children_children_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.employee_children_children_id_seq OWNED BY public.employee_children.children_id;


--
-- Name: employee_children_temps; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.employee_children_temps (
    children_id bigint NOT NULL,
    request_id integer DEFAULT 0 NOT NULL,
    employee_id integer DEFAULT 0 NOT NULL,
    child_name character varying(255),
    child_birthdate date,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.employee_children_temps OWNER TO postgres;

--
-- Name: employee_children_temps_children_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.employee_children_temps_children_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.employee_children_temps_children_id_seq OWNER TO postgres;

--
-- Name: employee_children_temps_children_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.employee_children_temps_children_id_seq OWNED BY public.employee_children_temps.children_id;


--
-- Name: employee_dependents; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.employee_dependents (
    id bigint NOT NULL,
    employee_id integer NOT NULL,
    name character varying(255),
    relationship character varying(255),
    course character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.employee_dependents OWNER TO postgres;

--
-- Name: employee_dependents_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.employee_dependents_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.employee_dependents_id_seq OWNER TO postgres;

--
-- Name: employee_dependents_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.employee_dependents_id_seq OWNED BY public.employee_dependents.id;


--
-- Name: employee_educations; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.employee_educations (
    education_id bigint NOT NULL,
    employee_id integer DEFAULT 0 NOT NULL,
    academic_level_id integer DEFAULT 0 NOT NULL,
    school_name character varying(255),
    program character varying(255),
    "from" integer DEFAULT 0,
    "to" integer DEFAULT 0,
    graduated_year integer DEFAULT 0,
    units_earned numeric(8,2) DEFAULT '0'::numeric,
    honors character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.employee_educations OWNER TO postgres;

--
-- Name: employee_educations_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.employee_educations_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.employee_educations_id_seq OWNER TO postgres;

--
-- Name: employee_educations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.employee_educations_id_seq OWNED BY public.employee_educations.education_id;


--
-- Name: employee_educations_temps; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.employee_educations_temps (
    education_id bigint NOT NULL,
    request_id integer DEFAULT 0 NOT NULL,
    employee_id integer DEFAULT 0 NOT NULL,
    academic_level_id integer DEFAULT 0 NOT NULL,
    school_name character varying(255),
    program character varying(255),
    "from" integer DEFAULT 0,
    "to" integer DEFAULT 0,
    graduated_year integer DEFAULT 0,
    units_earned numeric(8,2) DEFAULT '0'::numeric,
    honors character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.employee_educations_temps OWNER TO postgres;

--
-- Name: employee_educations_temps_education_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.employee_educations_temps_education_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.employee_educations_temps_education_id_seq OWNER TO postgres;

--
-- Name: employee_educations_temps_education_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.employee_educations_temps_education_id_seq OWNED BY public.employee_educations_temps.education_id;


--
-- Name: employee_employment_records; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.employee_employment_records (
    employment_record_id bigint NOT NULL,
    employee_id integer DEFAULT 0 NOT NULL,
    work_start_date date,
    work_end_date date,
    work_company character varying(255),
    monthly_salary numeric(8,2) DEFAULT '0'::numeric,
    salary_grade_step character varying(255),
    status_of_appointment character varying(255),
    "position" character varying(255),
    government_service_id integer DEFAULT 0,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.employee_employment_records OWNER TO postgres;

--
-- Name: employee_employment_records_employment_record_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.employee_employment_records_employment_record_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.employee_employment_records_employment_record_id_seq OWNER TO postgres;

--
-- Name: employee_employment_records_employment_record_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.employee_employment_records_employment_record_id_seq OWNED BY public.employee_employment_records.employment_record_id;


--
-- Name: employee_employment_records_temps; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.employee_employment_records_temps (
    employment_record_id bigint NOT NULL,
    request_id integer DEFAULT 0 NOT NULL,
    employee_id integer DEFAULT 0 NOT NULL,
    work_start_date date,
    work_end_date date,
    work_company character varying(255),
    monthly_salary numeric(8,2) DEFAULT '0'::numeric,
    salary_grade_step character varying(255),
    status_of_appointment character varying(255),
    "position" character varying(255),
    government_service_id integer DEFAULT 0,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.employee_employment_records_temps OWNER TO postgres;

--
-- Name: employee_employment_records_temps_employment_record_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.employee_employment_records_temps_employment_record_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.employee_employment_records_temps_employment_record_id_seq OWNER TO postgres;

--
-- Name: employee_employment_records_temps_employment_record_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.employee_employment_records_temps_employment_record_id_seq OWNED BY public.employee_employment_records_temps.employment_record_id;


--
-- Name: employee_examinations; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.employee_examinations (
    examination_id bigint NOT NULL,
    employee_id integer DEFAULT 0 NOT NULL,
    exam_rating numeric(8,2) DEFAULT '0'::numeric NOT NULL,
    exam_date date,
    place_of_exam character varying(255),
    license_number character varying(255),
    date_released date,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    eligibility_id integer DEFAULT 0
);


ALTER TABLE public.employee_examinations OWNER TO postgres;

--
-- Name: employee_examinations_examination_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.employee_examinations_examination_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.employee_examinations_examination_id_seq OWNER TO postgres;

--
-- Name: employee_examinations_examination_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.employee_examinations_examination_id_seq OWNED BY public.employee_examinations.examination_id;


--
-- Name: employee_examinations_temps; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.employee_examinations_temps (
    examination_id bigint NOT NULL,
    request_id integer DEFAULT 0 NOT NULL,
    employee_id integer DEFAULT 0 NOT NULL,
    exam_rating numeric(8,2) DEFAULT '0'::numeric NOT NULL,
    exam_date date,
    place_of_exam character varying(255),
    license_number character varying(255),
    date_released date,
    eligibility_id integer DEFAULT 0,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.employee_examinations_temps OWNER TO postgres;

--
-- Name: employee_examinations_temps_examination_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.employee_examinations_temps_examination_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.employee_examinations_temps_examination_id_seq OWNER TO postgres;

--
-- Name: employee_examinations_temps_examination_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.employee_examinations_temps_examination_id_seq OWNED BY public.employee_examinations_temps.examination_id;


--
-- Name: employee_memberships; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.employee_memberships (
    membership_id bigint NOT NULL,
    employee_id integer DEFAULT 0 NOT NULL,
    membership character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.employee_memberships OWNER TO postgres;

--
-- Name: employee_memberships_membership_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.employee_memberships_membership_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.employee_memberships_membership_id_seq OWNER TO postgres;

--
-- Name: employee_memberships_membership_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.employee_memberships_membership_id_seq OWNED BY public.employee_memberships.membership_id;


--
-- Name: employee_memberships_temps; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.employee_memberships_temps (
    membership_id bigint NOT NULL,
    request_id integer DEFAULT 0 NOT NULL,
    employee_id integer DEFAULT 0 NOT NULL,
    membership character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.employee_memberships_temps OWNER TO postgres;

--
-- Name: employee_memberships_temps_membership_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.employee_memberships_temps_membership_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.employee_memberships_temps_membership_id_seq OWNER TO postgres;

--
-- Name: employee_memberships_temps_membership_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.employee_memberships_temps_membership_id_seq OWNED BY public.employee_memberships_temps.membership_id;


--
-- Name: employee_offboardings; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.employee_offboardings (
    id bigint NOT NULL,
    employee_id integer DEFAULT 0 NOT NULL,
    nature_id integer DEFAULT 0 NOT NULL,
    remarks character varying(255),
    date_effectivity date,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.employee_offboardings OWNER TO postgres;

--
-- Name: employee_offboardings_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.employee_offboardings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.employee_offboardings_id_seq OWNER TO postgres;

--
-- Name: employee_offboardings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.employee_offboardings_id_seq OWNED BY public.employee_offboardings.id;


--
-- Name: employee_organizations; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.employee_organizations (
    organization_id bigint NOT NULL,
    employee_id integer DEFAULT 0 NOT NULL,
    organization character varying(255),
    org_from date,
    org_to date,
    org_hours numeric(8,2) DEFAULT '0'::numeric,
    org_position character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.employee_organizations OWNER TO postgres;

--
-- Name: employee_organizations_organization_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.employee_organizations_organization_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.employee_organizations_organization_id_seq OWNER TO postgres;

--
-- Name: employee_organizations_organization_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.employee_organizations_organization_id_seq OWNED BY public.employee_organizations.organization_id;


--
-- Name: employee_organizations_temps; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.employee_organizations_temps (
    organization_id bigint NOT NULL,
    request_id integer DEFAULT 0 NOT NULL,
    employee_id integer DEFAULT 0 NOT NULL,
    organization character varying(255),
    org_from date,
    org_to date,
    org_hours numeric(8,2) DEFAULT '0'::numeric,
    org_position character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.employee_organizations_temps OWNER TO postgres;

--
-- Name: employee_organizations_temps_organization_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.employee_organizations_temps_organization_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.employee_organizations_temps_organization_id_seq OWNER TO postgres;

--
-- Name: employee_organizations_temps_organization_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.employee_organizations_temps_organization_id_seq OWNED BY public.employee_organizations_temps.organization_id;


--
-- Name: employee_promotions; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.employee_promotions (
    id bigint NOT NULL,
    employee_id integer DEFAULT 0 NOT NULL,
    position_id integer DEFAULT 0 NOT NULL,
    plantilla_id integer DEFAULT 0,
    is_plantilla boolean DEFAULT false,
    is_teaching boolean DEFAULT false,
    nature_of_appointment_id integer DEFAULT 0 NOT NULL,
    employment_type_id integer DEFAULT 0 NOT NULL,
    department_id integer DEFAULT 0 NOT NULL,
    branch_id integer DEFAULT 0 NOT NULL,
    payroll_interval_id integer DEFAULT 0 NOT NULL,
    old_salary numeric(8,2) DEFAULT '0'::numeric NOT NULL,
    old_tax_amount numeric(8,2) DEFAULT '0'::numeric NOT NULL,
    old_gsis_amount numeric(8,2) DEFAULT '0'::numeric NOT NULL,
    old_sss_amount numeric(8,2) DEFAULT '0'::numeric NOT NULL,
    old_pagibig_amount numeric(8,2) DEFAULT '0'::numeric NOT NULL,
    old_philhealth_amount numeric(8,2) DEFAULT '0'::numeric NOT NULL,
    new_salary numeric(8,2) DEFAULT '0'::numeric NOT NULL,
    new_tax_amount numeric(8,2) DEFAULT '0'::numeric NOT NULL,
    new_gsis_amount numeric(8,2) DEFAULT '0'::numeric NOT NULL,
    new_sss_amount numeric(8,2) DEFAULT '0'::numeric NOT NULL,
    new_pagibig_amount numeric(8,2) DEFAULT '0'::numeric NOT NULL,
    new_philhealth_amount numeric(8,2) DEFAULT '0'::numeric NOT NULL,
    date_position_appointed date,
    date_of_effectivity date,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.employee_promotions OWNER TO postgres;

--
-- Name: employee_promotions_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.employee_promotions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.employee_promotions_id_seq OWNER TO postgres;

--
-- Name: employee_promotions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.employee_promotions_id_seq OWNED BY public.employee_promotions.id;


--
-- Name: employee_recognations; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.employee_recognations (
    recognation_id bigint NOT NULL,
    employee_id integer DEFAULT 0 NOT NULL,
    recognation character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.employee_recognations OWNER TO postgres;

--
-- Name: employee_recognations_recognation_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.employee_recognations_recognation_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.employee_recognations_recognation_id_seq OWNER TO postgres;

--
-- Name: employee_recognations_recognation_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.employee_recognations_recognation_id_seq OWNED BY public.employee_recognations.recognation_id;


--
-- Name: employee_recognations_temps; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.employee_recognations_temps (
    recognation_id bigint NOT NULL,
    request_id integer DEFAULT 0 NOT NULL,
    employee_id integer DEFAULT 0 NOT NULL,
    recognation character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.employee_recognations_temps OWNER TO postgres;

--
-- Name: employee_recognations_temps_recognation_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.employee_recognations_temps_recognation_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.employee_recognations_temps_recognation_id_seq OWNER TO postgres;

--
-- Name: employee_recognations_temps_recognation_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.employee_recognations_temps_recognation_id_seq OWNED BY public.employee_recognations_temps.recognation_id;


--
-- Name: employee_references; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.employee_references (
    reference_id bigint NOT NULL,
    employee_id integer DEFAULT 1 NOT NULL,
    ref_name character varying(255),
    ref_address character varying(255),
    ref_occupation character varying(255),
    ref_contact_no character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.employee_references OWNER TO postgres;

--
-- Name: employee_references_reference_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.employee_references_reference_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.employee_references_reference_id_seq OWNER TO postgres;

--
-- Name: employee_references_reference_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.employee_references_reference_id_seq OWNED BY public.employee_references.reference_id;


--
-- Name: employee_references_temps; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.employee_references_temps (
    reference_id bigint NOT NULL,
    request_id integer DEFAULT 1 NOT NULL,
    employee_id integer DEFAULT 1 NOT NULL,
    ref_name character varying(255),
    ref_address character varying(255),
    ref_occupation character varying(255),
    ref_contact_no character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.employee_references_temps OWNER TO postgres;

--
-- Name: employee_references_temps_reference_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.employee_references_temps_reference_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.employee_references_temps_reference_id_seq OWNER TO postgres;

--
-- Name: employee_references_temps_reference_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.employee_references_temps_reference_id_seq OWNED BY public.employee_references_temps.reference_id;


--
-- Name: employee_requests; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.employee_requests (
    id bigint NOT NULL,
    employee_id integer NOT NULL,
    status_id integer NOT NULL,
    request_date date NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    remarks text
);


ALTER TABLE public.employee_requests OWNER TO postgres;

--
-- Name: employee_requests_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.employee_requests_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.employee_requests_id_seq OWNER TO postgres;

--
-- Name: employee_requests_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.employee_requests_id_seq OWNED BY public.employee_requests.id;


--
-- Name: employee_skills; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.employee_skills (
    skill_id bigint NOT NULL,
    employee_id integer DEFAULT 0 NOT NULL,
    skill character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.employee_skills OWNER TO postgres;

--
-- Name: employee_skills_skill_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.employee_skills_skill_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.employee_skills_skill_id_seq OWNER TO postgres;

--
-- Name: employee_skills_skill_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.employee_skills_skill_id_seq OWNED BY public.employee_skills.skill_id;


--
-- Name: employee_skills_temps; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.employee_skills_temps (
    skill_id bigint NOT NULL,
    request_id integer DEFAULT 0 NOT NULL,
    employee_id integer DEFAULT 0 NOT NULL,
    skill character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.employee_skills_temps OWNER TO postgres;

--
-- Name: employee_skills_temps_skill_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.employee_skills_temps_skill_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.employee_skills_temps_skill_id_seq OWNER TO postgres;

--
-- Name: employee_skills_temps_skill_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.employee_skills_temps_skill_id_seq OWNED BY public.employee_skills_temps.skill_id;


--
-- Name: employee_trainings; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.employee_trainings (
    training_id bigint NOT NULL,
    employee_id integer DEFAULT 0 NOT NULL,
    training character varying(255),
    training_from date,
    training_to date,
    hours numeric(8,2) DEFAULT '0'::numeric,
    sponsored_by character varying(255),
    learning_id integer DEFAULT 0,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.employee_trainings OWNER TO postgres;

--
-- Name: employee_trainings_temps; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.employee_trainings_temps (
    training_id bigint NOT NULL,
    request_id integer DEFAULT 0 NOT NULL,
    employee_id integer DEFAULT 0 NOT NULL,
    training character varying(255),
    training_from date,
    training_to date,
    hours numeric(8,2) DEFAULT '0'::numeric,
    sponsored_by character varying(255),
    learning_id integer DEFAULT 0,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.employee_trainings_temps OWNER TO postgres;

--
-- Name: employee_trainings_temps_training_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.employee_trainings_temps_training_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.employee_trainings_temps_training_id_seq OWNER TO postgres;

--
-- Name: employee_trainings_temps_training_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.employee_trainings_temps_training_id_seq OWNED BY public.employee_trainings_temps.training_id;


--
-- Name: employee_trainings_training_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.employee_trainings_training_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.employee_trainings_training_id_seq OWNER TO postgres;

--
-- Name: employee_trainings_training_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.employee_trainings_training_id_seq OWNED BY public.employee_trainings.training_id;


--
-- Name: employees; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.employees (
    id bigint NOT NULL,
    photo text DEFAULT ''::character varying NOT NULL,
    employee_no character varying(255) NOT NULL,
    access_no character varying(255),
    name_prefix_id integer DEFAULT 0 NOT NULL,
    first_name character varying(255) NOT NULL,
    middle_name character varying(255) NOT NULL,
    last_name character varying(255) NOT NULL,
    name_suffix_id integer DEFAULT 0 NOT NULL,
    birth_place character varying(255),
    birthdate date NOT NULL,
    age integer DEFAULT 0 NOT NULL,
    gender_id integer DEFAULT 0 NOT NULL,
    height numeric(8,2) DEFAULT '0'::numeric,
    weight numeric(8,2) DEFAULT '0'::numeric,
    email character varying(255) NOT NULL,
    mobile_no character varying(255),
    telephone_no character varying(255),
    citizenship_id integer DEFAULT 0 NOT NULL,
    civil_status_id integer DEFAULT 0 NOT NULL,
    religion_id integer DEFAULT 0 NOT NULL,
    is_dual_citizent boolean DEFAULT false,
    by_birth boolean DEFAULT false,
    by_naturalization boolean DEFAULT false,
    indicate_country character varying(255),
    ra_postal_id integer DEFAULT 0,
    ra_house_no character varying(255),
    ra_barangay character varying(255),
    ra_street character varying(255),
    ra_village character varying(255),
    pa_postal_id integer DEFAULT 0,
    pa_house_no character varying(255),
    pa_barangay character varying(255),
    pa_street character varying(255),
    pa_village character varying(255),
    father_name_prefix_id integer DEFAULT 0,
    father_first_name character varying(255),
    father_middle_name character varying(255),
    father_last_name character varying(255),
    father_name_suffix_id integer DEFAULT 0,
    mother_name_prefix_id integer DEFAULT 0,
    mother_first_name character varying(255),
    mother_middle_name character varying(255),
    mother_last_name character varying(255),
    mother_name_suffix_id integer DEFAULT 0,
    spouse_name_prefix_id integer DEFAULT 0,
    spouse_first_name character varying(255),
    spouse_middle_name character varying(255),
    spouse_last_name character varying(255),
    spouse_name_suffix_id integer DEFAULT 0,
    spouse_occupation character varying(255),
    spouse_employer character varying(255),
    spouse_business_address character varying(255),
    company_id integer DEFAULT 0 NOT NULL,
    branch_id integer DEFAULT 0 NOT NULL,
    department_id integer DEFAULT 0 NOT NULL,
    work_schedule_id integer DEFAULT 0,
    employment_type_id integer DEFAULT 0 NOT NULL,
    position_id integer DEFAULT 0 NOT NULL,
    plantilla_id integer DEFAULT 0,
    is_shifting boolean DEFAULT false,
    is_plantilla boolean DEFAULT false,
    is_employee boolean DEFAULT false,
    is_teaching boolean DEFAULT false,
    date_hired date,
    tin_no character varying(255),
    gsis_no character varying(255),
    sss_no character varying(255),
    pagibig_no character varying(255),
    philhealth_no character varying(255),
    salary numeric(8,2) DEFAULT '0'::numeric,
    tax_amount numeric(8,2) DEFAULT '0'::numeric,
    gsis_amount numeric(8,2) DEFAULT '0'::numeric,
    sss_amount numeric(8,2) DEFAULT '0'::numeric,
    pagibig_amount numeric(8,2) DEFAULT '0'::numeric,
    philhealth_amount numeric(8,2) DEFAULT '0'::numeric,
    payroll_interval_id integer DEFAULT 0,
    active boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    blood_type_id integer DEFAULT 0,
    ra_region character varying(255),
    ra_province character varying(255),
    ra_city character varying(255),
    pa_region character varying(255),
    pa_province character varying(255),
    pa_city character varying(255),
    salary_grade_id integer DEFAULT 0,
    salary_step_id integer DEFAULT 0,
    ra_region_name character varying(255),
    ra_province_name character varying(255),
    ra_city_name character varying(255),
    pa_region_name character varying(255),
    pa_province_name character varying(255),
    pa_city_name character varying(255),
    end_date date,
    date_applied date,
    application_status_id integer,
    position_applied_id integer,
    division_id integer DEFAULT 0,
    section_id integer DEFAULT 0,
    account_no character varying(255)
);


ALTER TABLE public.employees OWNER TO postgres;

--
-- Name: employees_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.employees_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.employees_id_seq OWNER TO postgres;

--
-- Name: employees_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.employees_id_seq OWNED BY public.employees.id;


--
-- Name: employees_temps; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.employees_temps (
    id bigint NOT NULL,
    request_id integer NOT NULL,
    employee_id integer NOT NULL,
    photo text DEFAULT ''::character varying NOT NULL,
    employee_no character varying(255) NOT NULL,
    access_no character varying(255) NOT NULL,
    name_prefix_id integer DEFAULT 0 NOT NULL,
    first_name character varying(255) NOT NULL,
    middle_name character varying(255) NOT NULL,
    last_name character varying(255) NOT NULL,
    name_suffix_id integer DEFAULT 0 NOT NULL,
    birth_place character varying(255),
    birthdate date NOT NULL,
    age integer DEFAULT 0 NOT NULL,
    gender_id integer DEFAULT 0 NOT NULL,
    height numeric(8,2) DEFAULT '0'::numeric,
    weight numeric(8,2) DEFAULT '0'::numeric,
    blood_type character varying(255),
    email character varying(255) NOT NULL,
    mobile_no character varying(255),
    telephone_no character varying(255),
    citizenship_id integer DEFAULT 0 NOT NULL,
    civil_status_id integer DEFAULT 0 NOT NULL,
    religion_id integer DEFAULT 0 NOT NULL,
    is_dual_citizent boolean DEFAULT false NOT NULL,
    by_birth boolean DEFAULT false NOT NULL,
    by_naturalization boolean DEFAULT false NOT NULL,
    indicate_country character varying(255),
    ra_postal_id integer DEFAULT 0 NOT NULL,
    ra_house_no character varying(255),
    ra_barangay character varying(255),
    ra_street character varying(255),
    ra_village character varying(255),
    pa_postal_id integer DEFAULT 0 NOT NULL,
    pa_house_no character varying(255),
    pa_barangay character varying(255),
    pa_street character varying(255),
    pa_village character varying(255),
    father_name_prefix_id integer DEFAULT 0 NOT NULL,
    father_first_name character varying(255),
    father_middle_name character varying(255),
    father_last_name character varying(255),
    father_name_suffix_id integer DEFAULT 0 NOT NULL,
    mother_name_prefix_id integer DEFAULT 0 NOT NULL,
    mother_first_name character varying(255),
    mother_middle_name character varying(255),
    mother_last_name character varying(255),
    mother_name_suffix_id integer DEFAULT 0 NOT NULL,
    spouse_name_prefix_id integer DEFAULT 0 NOT NULL,
    spouse_first_name character varying(255),
    spouse_middle_name character varying(255),
    spouse_last_name character varying(255),
    spouse_name_suffix_id integer DEFAULT 0 NOT NULL,
    spouse_occupation character varying(255),
    spouse_employer character varying(255),
    spouse_business_address character varying(255),
    company_id integer DEFAULT 0 NOT NULL,
    branch_id integer DEFAULT 0 NOT NULL,
    department_id integer DEFAULT 0 NOT NULL,
    work_schedule_id integer DEFAULT 0 NOT NULL,
    employment_type_id integer DEFAULT 0 NOT NULL,
    position_id integer DEFAULT 0,
    plantilla_id integer DEFAULT 0,
    is_shifting boolean DEFAULT false,
    is_plantilla boolean DEFAULT false,
    is_employee boolean DEFAULT false,
    is_teaching boolean DEFAULT false,
    date_hired date,
    tin_no character varying(255),
    gsis_no character varying(255),
    sss_no character varying(255),
    pagibig_no character varying(255),
    philhealth_no character varying(255),
    salary numeric(8,2) DEFAULT '0'::numeric,
    tax_amount numeric(8,2) DEFAULT '0'::numeric,
    gsis_amount numeric(8,2) DEFAULT '0'::numeric,
    sss_amount numeric(8,2) DEFAULT '0'::numeric,
    pagibig_amount numeric(8,2) DEFAULT '0'::numeric,
    philhealth_amount numeric(8,2) DEFAULT '0'::numeric,
    payroll_interval_id integer DEFAULT 0,
    active boolean DEFAULT false NOT NULL,
    blood_type_id integer DEFAULT 0,
    ra_region character varying(255),
    ra_province character varying(255),
    ra_city character varying(255),
    pa_region character varying(255),
    pa_province character varying(255),
    pa_city character varying(255),
    ra_region_name character varying(255),
    ra_province_name character varying(255),
    ra_city_name character varying(255),
    pa_region_name character varying(255),
    pa_province_name character varying(255),
    pa_city_name character varying(255),
    salary_grade_id integer DEFAULT 0,
    salary_step_id integer DEFAULT 0,
    end_date date,
    date_applied date,
    application_status_id integer,
    position_applied_id integer,
    division_id integer DEFAULT 0,
    section_id integer DEFAULT 0,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.employees_temps OWNER TO postgres;

--
-- Name: employees_temps_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.employees_temps_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.employees_temps_id_seq OWNER TO postgres;

--
-- Name: employees_temps_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.employees_temps_id_seq OWNED BY public.employees_temps.id;


--
-- Name: employment_types; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.employment_types (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    active boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    with_end_contract boolean DEFAULT false NOT NULL
);


ALTER TABLE public.employment_types OWNER TO postgres;

--
-- Name: employment_types_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.employment_types_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.employment_types_id_seq OWNER TO postgres;

--
-- Name: employment_types_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.employment_types_id_seq OWNED BY public.employment_types.id;


--
-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.failed_jobs (
    id bigint NOT NULL,
    connection text NOT NULL,
    queue text NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE public.failed_jobs OWNER TO postgres;

--
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.failed_jobs_id_seq OWNER TO postgres;

--
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;


--
-- Name: fix_schedules_details; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.fix_schedules_details (
    id bigint NOT NULL,
    fix_schedule_id integer DEFAULT 0 NOT NULL,
    day_id integer DEFAULT 0 NOT NULL,
    am_in time(0) without time zone,
    am_out time(0) without time zone,
    break_in time(0) without time zone,
    break_out time(0) without time zone,
    pm_in time(0) without time zone,
    pm_out time(0) without time zone,
    grace_period numeric(8,2) DEFAULT '0'::numeric,
    flexi_hours numeric(8,2) DEFAULT '0'::numeric,
    work_hours numeric(8,2) DEFAULT '0'::numeric,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.fix_schedules_details OWNER TO postgres;

--
-- Name: fix_schdules_details_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.fix_schdules_details_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.fix_schdules_details_id_seq OWNER TO postgres;

--
-- Name: fix_schdules_details_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.fix_schdules_details_id_seq OWNED BY public.fix_schedules_details.id;


--
-- Name: fix_schedules; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.fix_schedules (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    no_late boolean DEFAULT false,
    no_undertime boolean DEFAULT false,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.fix_schedules OWNER TO postgres;

--
-- Name: fix_schedules_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.fix_schedules_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.fix_schedules_id_seq OWNER TO postgres;

--
-- Name: fix_schedules_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.fix_schedules_id_seq OWNED BY public.fix_schedules.id;


--
-- Name: genders; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.genders (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    active boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.genders OWNER TO postgres;

--
-- Name: genders_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.genders_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.genders_id_seq OWNER TO postgres;

--
-- Name: genders_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.genders_id_seq OWNED BY public.genders.id;


--
-- Name: gsis; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.gsis (
    id bigint NOT NULL,
    year integer NOT NULL,
    multiplier numeric(8,2) DEFAULT '0'::numeric NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.gsis OWNER TO postgres;

--
-- Name: gsis_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.gsis_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.gsis_id_seq OWNER TO postgres;

--
-- Name: gsis_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.gsis_id_seq OWNED BY public.gsis.id;


--
-- Name: holiday_tagging_details; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.holiday_tagging_details (
    id bigint NOT NULL,
    holiday_tagging_id integer,
    holiday_id integer,
    created_at timestamp without time zone,
    updated_at timestamp without time zone
);


ALTER TABLE public.holiday_tagging_details OWNER TO postgres;

--
-- Name: holiday_tagging_details_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.holiday_tagging_details ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.holiday_tagging_details_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: holiday_tagging_headers; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.holiday_tagging_headers (
    id bigint NOT NULL,
    branch_id integer,
    holiday_type_id integer,
    created_at timestamp without time zone,
    updated_at timestamp without time zone,
    year integer
);


ALTER TABLE public.holiday_tagging_headers OWNER TO postgres;

--
-- Name: holiday_tagging_headers_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.holiday_tagging_headers ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.holiday_tagging_headers_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: holiday_types; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.holiday_types (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    rate numeric(8,2) DEFAULT '0'::numeric,
    active boolean DEFAULT false NOT NULL,
    created_at timestamp without time zone,
    updated_at timestamp without time zone,
    absent_with_pay boolean DEFAULT false
);


ALTER TABLE public.holiday_types OWNER TO postgres;

--
-- Name: holidays; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.holidays (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    holiday_type integer DEFAULT 0,
    branch integer DEFAULT 0,
    date date,
    active boolean DEFAULT false NOT NULL,
    created_at timestamp without time zone,
    updated_at timestamp without time zone
);


ALTER TABLE public.holidays OWNER TO postgres;

--
-- Name: incomes; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.incomes (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    is_taxable boolean DEFAULT false,
    active boolean DEFAULT true,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    is_time_related boolean DEFAULT false
);


ALTER TABLE public.incomes OWNER TO postgres;

--
-- Name: incomes_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.incomes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.incomes_id_seq OWNER TO postgres;

--
-- Name: incomes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.incomes_id_seq OWNED BY public.incomes.id;


--
-- Name: ipcr; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.ipcr (
    id bigint NOT NULL,
    employee_id integer,
    numerical_rating numeric(8,2) NOT NULL,
    adjectival_rating character varying(255) NOT NULL,
    date_from date,
    date_to date,
    year integer,
    progress character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.ipcr OWNER TO postgres;

--
-- Name: ipcr_details; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.ipcr_details (
    id bigint NOT NULL,
    ipcr_header_id integer NOT NULL,
    employee_id integer NOT NULL,
    division_id integer NOT NULL,
    rating numeric(8,2) NOT NULL,
    adjectival_rating character varying(255) NOT NULL,
    attachment character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    progress character varying(255)
);


ALTER TABLE public.ipcr_details OWNER TO postgres;

--
-- Name: ipcr_details_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.ipcr_details_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.ipcr_details_id_seq OWNER TO postgres;

--
-- Name: ipcr_details_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.ipcr_details_id_seq OWNED BY public.ipcr_details.id;


--
-- Name: ipcr_headers; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.ipcr_headers (
    id bigint NOT NULL,
    semester_id integer NOT NULL,
    department_id integer NOT NULL,
    division_id integer NOT NULL,
    is_posted boolean DEFAULT true,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    year integer NOT NULL,
    month_from integer,
    month_to integer
);


ALTER TABLE public.ipcr_headers OWNER TO postgres;

--
-- Name: ipcr_headers_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.ipcr_headers_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.ipcr_headers_id_seq OWNER TO postgres;

--
-- Name: ipcr_headers_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.ipcr_headers_id_seq OWNED BY public.ipcr_headers.id;


--
-- Name: ipcr_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.ipcr_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.ipcr_id_seq OWNER TO postgres;

--
-- Name: ipcr_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.ipcr_id_seq OWNED BY public.ipcr.id;


--
-- Name: learnings; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.learnings (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    active boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.learnings OWNER TO postgres;

--
-- Name: learnings_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.learnings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.learnings_id_seq OWNER TO postgres;

--
-- Name: learnings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.learnings_id_seq OWNED BY public.learnings.id;


--
-- Name: leave_credits; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.leave_credits (
    id bigint NOT NULL,
    employee_id integer NOT NULL,
    leave_type_id integer NOT NULL,
    credits numeric(8,3) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.leave_credits OWNER TO postgres;

--
-- Name: leave_credits_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.leave_credits_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.leave_credits_id_seq OWNER TO postgres;

--
-- Name: leave_credits_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.leave_credits_id_seq OWNED BY public.leave_credits.id;


--
-- Name: leave_details; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.leave_details (
    id bigint NOT NULL,
    leave_id integer DEFAULT 0,
    leave_date date,
    with_pay numeric(8,2) DEFAULT '0'::numeric,
    without_pay numeric(8,2) DEFAULT '0'::numeric,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.leave_details OWNER TO postgres;

--
-- Name: leave_details_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.leave_details_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.leave_details_id_seq OWNER TO postgres;

--
-- Name: leave_details_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.leave_details_id_seq OWNED BY public.leave_details.id;


--
-- Name: leave_headers; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.leave_headers (
    id bigint NOT NULL,
    employee_id integer DEFAULT 0,
    leave_type_id integer DEFAULT 0,
    day_type_id integer DEFAULT 0,
    date_from date,
    date_to date,
    reason character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    approved boolean DEFAULT false,
    disapproved boolean DEFAULT false,
    processed_by integer DEFAULT 0,
    processed_date date,
    remarks text
);


ALTER TABLE public.leave_headers OWNER TO postgres;

--
-- Name: leave_headers_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.leave_headers_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.leave_headers_id_seq OWNER TO postgres;

--
-- Name: leave_headers_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.leave_headers_id_seq OWNED BY public.leave_headers.id;


--
-- Name: leave_types; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.leave_types (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    active boolean DEFAULT false NOT NULL,
    created_at timestamp without time zone,
    updated_at timestamp without time zone,
    service_credit boolean DEFAULT false
);


ALTER TABLE public.leave_types OWNER TO postgres;

--
-- Name: loan_applications; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.loan_applications (
    id bigint NOT NULL,
    deduction_id integer DEFAULT 0,
    employee_id integer DEFAULT 0,
    loan_amount numeric(18,2) DEFAULT '0'::numeric,
    loan_amortization numeric(18,2) DEFAULT '0'::numeric,
    remarks character varying(255),
    effectivity_date date,
    end_date date,
    is_approve boolean DEFAULT false,
    is_disapprove boolean DEFAULT false,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    payment numeric(18,2) DEFAULT '0'::numeric,
    balance numeric(18,2) DEFAULT '0'::numeric
);


ALTER TABLE public.loan_applications OWNER TO postgres;

--
-- Name: loan_applications_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.loan_applications_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.loan_applications_id_seq OWNER TO postgres;

--
-- Name: loan_applications_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.loan_applications_id_seq OWNED BY public.loan_applications.id;


--
-- Name: menus; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.menus (
    id bigint NOT NULL,
    menu character varying(255),
    description character varying(255),
    active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    menu_key character(250),
    module_id integer,
    status boolean DEFAULT false
);


ALTER TABLE public.menus OWNER TO postgres;

--
-- Name: menus_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.menus_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.menus_id_seq OWNER TO postgres;

--
-- Name: menus_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.menus_id_seq OWNED BY public.menus.id;


--
-- Name: migrations; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


ALTER TABLE public.migrations OWNER TO postgres;

--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.migrations_id_seq OWNER TO postgres;

--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: months; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.months (
    id integer NOT NULL,
    name character varying NOT NULL
);


ALTER TABLE public.months OWNER TO postgres;

--
-- Name: name_prefixes; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.name_prefixes (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    active boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.name_prefixes OWNER TO postgres;

--
-- Name: name_prefixes_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.name_prefixes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.name_prefixes_id_seq OWNER TO postgres;

--
-- Name: name_prefixes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.name_prefixes_id_seq OWNED BY public.name_prefixes.id;


--
-- Name: name_suffixes; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.name_suffixes (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    active boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.name_suffixes OWNER TO postgres;

--
-- Name: name_suffixes_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.name_suffixes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.name_suffixes_id_seq OWNER TO postgres;

--
-- Name: name_suffixes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.name_suffixes_id_seq OWNED BY public.name_suffixes.id;


--
-- Name: non_plantillas; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.non_plantillas (
    id bigint NOT NULL,
    position_id integer DEFAULT 0 NOT NULL,
    salary_step_id integer DEFAULT 0 NOT NULL,
    salary_grade_id integer DEFAULT 0 NOT NULL,
    salary numeric(8,2) DEFAULT '0'::numeric NOT NULL,
    vacant integer DEFAULT 0 NOT NULL,
    department_id integer DEFAULT 0 NOT NULL,
    description character varying(255),
    qualification character varying(255),
    eligibility character varying(255),
    education character varying(255),
    experience character varying(255),
    training character varying(255),
    publication_from date,
    publication_to date,
    status boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    employee_type_id integer DEFAULT 0,
    number_of_months integer DEFAULT 0
);


ALTER TABLE public.non_plantillas OWNER TO postgres;

--
-- Name: non_plantillas_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.non_plantillas_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.non_plantillas_id_seq OWNER TO postgres;

--
-- Name: non_plantillas_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.non_plantillas_id_seq OWNED BY public.non_plantillas.id;


--
-- Name: notifications; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.notifications (
    id uuid NOT NULL,
    type character varying(255) NOT NULL,
    notifiable_type character varying(255) NOT NULL,
    notifiable_id bigint NOT NULL,
    data text NOT NULL,
    read_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.notifications OWNER TO postgres;

--
-- Name: offboarding_natures; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.offboarding_natures (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    active boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.offboarding_natures OWNER TO postgres;

--
-- Name: offboarding_natures_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.offboarding_natures_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.offboarding_natures_id_seq OWNER TO postgres;

--
-- Name: offboarding_natures_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.offboarding_natures_id_seq OWNED BY public.offboarding_natures.id;


--
-- Name: official_business_applications; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.official_business_applications (
    id bigint NOT NULL,
    employee_id integer DEFAULT 0 NOT NULL,
    date timestamp without time zone,
    date_time_from timestamp without time zone,
    date_time_to timestamp without time zone,
    client character varying(255),
    purpose character varying(255),
    approved boolean DEFAULT false,
    disapproved boolean DEFAULT false,
    created_at timestamp without time zone,
    updated_at timestamp without time zone,
    remarks text
);


ALTER TABLE public.official_business_applications OWNER TO postgres;

--
-- Name: official_business_applications_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.official_business_applications ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.official_business_applications_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: official_business_types; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.official_business_types (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    active boolean DEFAULT false NOT NULL,
    created_at timestamp without time zone,
    updated_at timestamp without time zone
);


ALTER TABLE public.official_business_types OWNER TO postgres;

--
-- Name: overtime_applications; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.overtime_applications (
    id bigint NOT NULL,
    employee_id integer DEFAULT 0 NOT NULL,
    overtime_type_id integer DEFAULT 0 NOT NULL,
    date timestamp without time zone,
    date_time_from timestamp without time zone,
    date_time_to timestamp without time zone,
    total_hours numeric(8,2) DEFAULT '0'::numeric,
    service_credits boolean DEFAULT false NOT NULL,
    payroll boolean DEFAULT false NOT NULL,
    remarks character varying(255),
    approved boolean DEFAULT false,
    disapproved boolean DEFAULT false,
    created_at timestamp without time zone,
    updated_at timestamp without time zone,
    ot_amount numeric(8,2) DEFAULT '0'::numeric,
    nd_amount numeric(8,2) DEFAULT '0'::numeric,
    disapprove_remarks text
);


ALTER TABLE public.overtime_applications OWNER TO postgres;

--
-- Name: overtime_applications_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.overtime_applications ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.overtime_applications_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: overtime_types; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.overtime_types (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    rate numeric(8,2) DEFAULT '0'::numeric,
    active boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    nd_from time(0) without time zone,
    nd_to time(0) without time zone,
    nd_rating numeric(8,2) DEFAULT '0'::numeric
);


ALTER TABLE public.overtime_types OWNER TO postgres;

--
-- Name: overtime_types_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.overtime_types_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.overtime_types_id_seq OWNER TO postgres;

--
-- Name: overtime_types_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.overtime_types_id_seq OWNED BY public.overtime_types.id;


--
-- Name: password_resets; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.password_resets (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);


ALTER TABLE public.password_resets OWNER TO postgres;

--
-- Name: payroll_cutoffs; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.payroll_cutoffs (
    id bigint NOT NULL,
    name character varying(255),
    payroll_interval_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.payroll_cutoffs OWNER TO postgres;

--
-- Name: payroll_cutoffs_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.payroll_cutoffs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.payroll_cutoffs_id_seq OWNER TO postgres;

--
-- Name: payroll_cutoffs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.payroll_cutoffs_id_seq OWNED BY public.payroll_cutoffs.id;


--
-- Name: payroll_deductions; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.payroll_deductions (
    id bigint NOT NULL,
    payroll_period_id integer NOT NULL,
    employment_id integer NOT NULL,
    deduction_id integer NOT NULL,
    employee_id integer NOT NULL,
    amount numeric(8,2) DEFAULT '0'::numeric,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.payroll_deductions OWNER TO postgres;

--
-- Name: payroll_deductions_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.payroll_deductions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.payroll_deductions_id_seq OWNER TO postgres;

--
-- Name: payroll_deductions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.payroll_deductions_id_seq OWNED BY public.payroll_deductions.id;


--
-- Name: payroll_incomes; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.payroll_incomes (
    id bigint NOT NULL,
    payroll_period_id integer NOT NULL,
    employment_id integer NOT NULL,
    income_id integer NOT NULL,
    employee_id integer NOT NULL,
    amount numeric(8,2) DEFAULT '0'::numeric,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.payroll_incomes OWNER TO postgres;

--
-- Name: payroll_incomes_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.payroll_incomes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.payroll_incomes_id_seq OWNER TO postgres;

--
-- Name: payroll_incomes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.payroll_incomes_id_seq OWNED BY public.payroll_incomes.id;


--
-- Name: payroll_intervals; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.payroll_intervals (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    day_interval integer DEFAULT 0,
    month_frequency integer DEFAULT 0,
    year_frequency integer DEFAULT 0
);


ALTER TABLE public.payroll_intervals OWNER TO postgres;

--
-- Name: payroll_intervals_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.payroll_intervals_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.payroll_intervals_id_seq OWNER TO postgres;

--
-- Name: payroll_intervals_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.payroll_intervals_id_seq OWNED BY public.payroll_intervals.id;


--
-- Name: payroll_item_schedule_details; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.payroll_item_schedule_details (
    id bigint NOT NULL,
    payroll_item_schedule_header_id integer NOT NULL,
    income_id integer NOT NULL,
    deduction_id integer NOT NULL,
    active boolean DEFAULT false,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.payroll_item_schedule_details OWNER TO postgres;

--
-- Name: payroll_item_schedule_details_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.payroll_item_schedule_details_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.payroll_item_schedule_details_id_seq OWNER TO postgres;

--
-- Name: payroll_item_schedule_details_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.payroll_item_schedule_details_id_seq OWNED BY public.payroll_item_schedule_details.id;


--
-- Name: payroll_item_schedule_headers; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.payroll_item_schedule_headers (
    id bigint NOT NULL,
    payroll_interval_type_id integer NOT NULL,
    payroll_period_type_id integer NOT NULL,
    employment_type_id integer NOT NULL,
    sss boolean DEFAULT false,
    pagibig boolean DEFAULT false,
    philhealth boolean DEFAULT false,
    gsis boolean DEFAULT false,
    tax boolean DEFAULT false,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.payroll_item_schedule_headers OWNER TO postgres;

--
-- Name: payroll_item_schedule_headers_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.payroll_item_schedule_headers_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.payroll_item_schedule_headers_id_seq OWNER TO postgres;

--
-- Name: payroll_item_schedule_headers_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.payroll_item_schedule_headers_id_seq OWNED BY public.payroll_item_schedule_headers.id;


--
-- Name: payroll_periods; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.payroll_periods (
    id bigint NOT NULL,
    payroll_interval_id integer DEFAULT 0 NOT NULL,
    payroll_cutoff_id integer DEFAULT 0 NOT NULL,
    attendance_start_date date,
    attendance_end_date date,
    payroll_start_date date,
    payroll_end_date date,
    release_date date,
    posted boolean DEFAULT false,
    active boolean DEFAULT false,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.payroll_periods OWNER TO postgres;

--
-- Name: payroll_periods_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.payroll_periods_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.payroll_periods_id_seq OWNER TO postgres;

--
-- Name: payroll_periods_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.payroll_periods_id_seq OWNED BY public.payroll_periods.id;


--
-- Name: payroll_summaries; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.payroll_summaries (
    id bigint NOT NULL,
    payroll_period_id integer DEFAULT 0,
    employee_id integer DEFAULT 0,
    salary numeric(8,2) DEFAULT '0'::numeric,
    gsis numeric(8,2) DEFAULT '0'::numeric,
    sss numeric(8,2) DEFAULT '0'::numeric,
    pagibig numeric(8,2) DEFAULT '0'::numeric,
    philhealth numeric(8,2) DEFAULT '0'::numeric,
    tax numeric(8,2) DEFAULT '0'::numeric,
    late_amount numeric(8,2) DEFAULT '0'::numeric,
    ut_amount numeric(8,2) DEFAULT '0'::numeric,
    absent_amount numeric(8,2) DEFAULT '0'::numeric,
    holiday_amount numeric(8,2) DEFAULT '0'::numeric,
    ot_amount numeric(8,2) DEFAULT '0'::numeric,
    nd_amount numeric(8,2) DEFAULT '0'::numeric,
    total_income numeric(8,2) DEFAULT '0'::numeric,
    total_deduction numeric(8,2) DEFAULT '0'::numeric,
    gross_amount numeric(8,2) DEFAULT '0'::numeric,
    net_pay numeric(8,2) DEFAULT '0'::numeric,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.payroll_summaries OWNER TO postgres;

--
-- Name: payroll_summaries_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.payroll_summaries_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.payroll_summaries_id_seq OWNER TO postgres;

--
-- Name: payroll_summaries_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.payroll_summaries_id_seq OWNED BY public.payroll_summaries.id;


--
-- Name: philhealths; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.philhealths (
    id bigint NOT NULL,
    year integer NOT NULL,
    multiplier numeric(8,4) DEFAULT '0'::numeric NOT NULL,
    income_floor numeric(8,2) DEFAULT '0'::numeric NOT NULL,
    income_ceiling numeric(8,2) DEFAULT '0'::numeric NOT NULL,
    fix_rate numeric(8,2) DEFAULT '0'::numeric NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.philhealths OWNER TO postgres;

--
-- Name: philhealths_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.philhealths_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.philhealths_id_seq OWNER TO postgres;

--
-- Name: philhealths_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.philhealths_id_seq OWNED BY public.philhealths.id;


--
-- Name: plantillas; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.plantillas (
    id bigint NOT NULL,
    code character varying(255) NOT NULL,
    position_id integer DEFAULT 0 NOT NULL,
    salary_step_id integer DEFAULT 0 NOT NULL,
    salary_grade_id integer DEFAULT 0 NOT NULL,
    employee_id integer DEFAULT 0 NOT NULL,
    active boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    department_id integer,
    eligibility character varying(255),
    experience character varying(3000),
    training character varying(3000),
    education character varying(3000),
    unit character varying(3000),
    publication_from date,
    publication_to date,
    status character varying(100)
);


ALTER TABLE public.plantillas OWNER TO postgres;

--
-- Name: plantillas_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.plantillas_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.plantillas_id_seq OWNER TO postgres;

--
-- Name: plantillas_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.plantillas_id_seq OWNED BY public.plantillas.id;


--
-- Name: positions; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.positions (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    active boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    description character varying(3000)
);


ALTER TABLE public.positions OWNER TO postgres;

--
-- Name: positions_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.positions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.positions_id_seq OWNER TO postgres;

--
-- Name: positions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.positions_id_seq OWNED BY public.positions.id;


--
-- Name: promotion_natures; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.promotion_natures (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    active boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.promotion_natures OWNER TO postgres;

--
-- Name: promotion_natures_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.promotion_natures_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.promotion_natures_id_seq OWNER TO postgres;

--
-- Name: promotion_natures_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.promotion_natures_id_seq OWNED BY public.promotion_natures.id;


--
-- Name: religions; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.religions (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    active boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.religions OWNER TO postgres;

--
-- Name: religions_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.religions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.religions_id_seq OWNER TO postgres;

--
-- Name: religions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.religions_id_seq OWNED BY public.religions.id;


--
-- Name: salary_adjustments; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.salary_adjustments (
    id bigint NOT NULL,
    employee_id integer NOT NULL,
    old_salary numeric(8,2),
    old_salary_grade_id integer,
    old_salary_step_id integer,
    new_salary numeric(8,2) NOT NULL,
    new_salary_grade_id integer NOT NULL,
    new_salary_step_id integer NOT NULL,
    salary_schedule_id integer NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.salary_adjustments OWNER TO postgres;

--
-- Name: salary_adjustments_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.salary_adjustments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.salary_adjustments_id_seq OWNER TO postgres;

--
-- Name: salary_adjustments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.salary_adjustments_id_seq OWNED BY public.salary_adjustments.id;


--
-- Name: salary_grades; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.salary_grades (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    active boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.salary_grades OWNER TO postgres;

--
-- Name: salary_grades_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.salary_grades_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.salary_grades_id_seq OWNER TO postgres;

--
-- Name: salary_grades_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.salary_grades_id_seq OWNED BY public.salary_grades.id;


--
-- Name: salary_schedules; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.salary_schedules (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    enabling_law character varying(255),
    effectivity date,
    active boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.salary_schedules OWNER TO postgres;

--
-- Name: salary_schedules_details; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.salary_schedules_details (
    id bigint NOT NULL,
    salary_schedule_id integer DEFAULT 0 NOT NULL,
    salary_grade_id integer DEFAULT 0 NOT NULL,
    salary_step_id integer DEFAULT 0 NOT NULL,
    amount numeric(8,2) DEFAULT '0'::numeric NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.salary_schedules_details OWNER TO postgres;

--
-- Name: salary_schedules_details_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.salary_schedules_details_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.salary_schedules_details_id_seq OWNER TO postgres;

--
-- Name: salary_schedules_details_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.salary_schedules_details_id_seq OWNED BY public.salary_schedules_details.id;


--
-- Name: salary_schedules_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.salary_schedules_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.salary_schedules_id_seq OWNER TO postgres;

--
-- Name: salary_schedules_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.salary_schedules_id_seq OWNED BY public.salary_schedules.id;


--
-- Name: salary_steps; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.salary_steps (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    active boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.salary_steps OWNER TO postgres;

--
-- Name: salary_steps_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.salary_steps_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.salary_steps_id_seq OWNER TO postgres;

--
-- Name: salary_steps_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.salary_steps_id_seq OWNED BY public.salary_steps.id;


--
-- Name: schedule_days; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.schedule_days (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.schedule_days OWNER TO postgres;

--
-- Name: schedule_days_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.schedule_days_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.schedule_days_id_seq OWNER TO postgres;

--
-- Name: schedule_days_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.schedule_days_id_seq OWNED BY public.schedule_days.id;


--
-- Name: sections; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.sections (
    id bigint NOT NULL,
    code character varying(255) DEFAULT '-'::character varying NOT NULL,
    name character varying(255) NOT NULL,
    active boolean DEFAULT true,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.sections OWNER TO postgres;

--
-- Name: sections_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.sections_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.sections_id_seq OWNER TO postgres;

--
-- Name: sections_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.sections_id_seq OWNED BY public.sections.id;


--
-- Name: semester_ratings; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.semester_ratings (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    active boolean DEFAULT true,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.semester_ratings OWNER TO postgres;

--
-- Name: semester_ratings_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.semester_ratings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.semester_ratings_id_seq OWNER TO postgres;

--
-- Name: semester_ratings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.semester_ratings_id_seq OWNED BY public.semester_ratings.id;


--
-- Name: service_records; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.service_records (
    service_record_id bigint NOT NULL,
    employee_id integer DEFAULT 0 NOT NULL,
    start_date date,
    end_date date,
    designation character varying(255),
    employment_type character varying(255),
    annual_salary numeric(8,2) DEFAULT '0'::numeric,
    place_of_assignment character varying(255),
    leave_without_pay numeric(8,2) DEFAULT '1'::numeric,
    separation_date date,
    cause character varying(255),
    branch character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.service_records OWNER TO postgres;

--
-- Name: service_records_service_record_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.service_records_service_record_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.service_records_service_record_id_seq OWNER TO postgres;

--
-- Name: service_records_service_record_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.service_records_service_record_id_seq OWNED BY public.service_records.service_record_id;


--
-- Name: service_records_temps; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.service_records_temps (
    service_record_id bigint NOT NULL,
    request_id integer DEFAULT 0 NOT NULL,
    employee_id integer DEFAULT 0 NOT NULL,
    start_date date,
    end_date date,
    designation character varying(255),
    employment_type character varying(255),
    annual_salary numeric(8,2) DEFAULT '0'::numeric,
    place_of_assignment character varying(255),
    leave_without_pay numeric(8,2) DEFAULT '1'::numeric,
    separation_date date,
    cause character varying(255),
    branch character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.service_records_temps OWNER TO postgres;

--
-- Name: service_records_temps_service_record_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.service_records_temps_service_record_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.service_records_temps_service_record_id_seq OWNER TO postgres;

--
-- Name: service_records_temps_service_record_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.service_records_temps_service_record_id_seq OWNED BY public.service_records_temps.service_record_id;


--
-- Name: shift_schedules_details; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.shift_schedules_details (
    id bigint NOT NULL,
    shift_schedule_id integer DEFAULT 0 NOT NULL,
    shift_date date,
    am_in time(0) without time zone,
    am_out time(0) without time zone,
    break_in time(0) without time zone,
    break_out time(0) without time zone,
    pm_in time(0) without time zone,
    pm_out time(0) without time zone,
    grace_period numeric(8,2) DEFAULT '0'::numeric,
    flexi_hours numeric(8,2) DEFAULT '0'::numeric,
    work_hours numeric(8,2) DEFAULT '0'::numeric,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.shift_schedules_details OWNER TO postgres;

--
-- Name: shift_schedules_details_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.shift_schedules_details_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.shift_schedules_details_id_seq OWNER TO postgres;

--
-- Name: shift_schedules_details_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.shift_schedules_details_id_seq OWNED BY public.shift_schedules_details.id;


--
-- Name: shift_schedules_headers; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.shift_schedules_headers (
    id bigint NOT NULL,
    name character varying(255),
    date_from date,
    date_to date,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.shift_schedules_headers OWNER TO postgres;

--
-- Name: shift_schedules_headers_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.shift_schedules_headers_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.shift_schedules_headers_id_seq OWNER TO postgres;

--
-- Name: shift_schedules_headers_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.shift_schedules_headers_id_seq OWNED BY public.shift_schedules_headers.id;


--
-- Name: sss; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.sss (
    id bigint NOT NULL,
    min_income numeric(8,2) DEFAULT '0'::numeric,
    max_income numeric(8,2) DEFAULT '0'::numeric,
    "ER" numeric(8,2) DEFAULT '0'::numeric,
    "EE" numeric(8,2) DEFAULT '0'::numeric,
    "MPF_ER" numeric(8,2) DEFAULT '0'::numeric,
    "MPF_EE" numeric(8,2) DEFAULT '0'::numeric,
    "WISP" numeric(8,2) DEFAULT '0'::numeric,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.sss OWNER TO postgres;

--
-- Name: sss_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.sss_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.sss_id_seq OWNER TO postgres;

--
-- Name: sss_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.sss_id_seq OWNED BY public.sss.id;


--
-- Name: step_increments; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.step_increments (
    id bigint NOT NULL,
    employee_id integer DEFAULT 0 NOT NULL,
    effectivity_date date,
    current_salary_grade_id integer DEFAULT 1,
    current_salary_step_id integer DEFAULT 1,
    current_salary numeric(8,2) DEFAULT '1'::numeric,
    new_salary_grade_id integer DEFAULT 1,
    new_salary_step_id integer DEFAULT 1,
    new_salary numeric(8,2) DEFAULT '1'::numeric,
    new_tax_amount numeric(8,2) DEFAULT '0'::numeric,
    new_gsis_amount numeric(8,2) DEFAULT '0'::numeric,
    new_sss_amount numeric(8,2) DEFAULT '0'::numeric,
    new_pagibig_amount numeric(8,2) DEFAULT '0'::numeric,
    new_philhealth_amount numeric(8,2) DEFAULT '0'::numeric,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.step_increments OWNER TO postgres;

--
-- Name: step_increments_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.step_increments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.step_increments_id_seq OWNER TO postgres;

--
-- Name: step_increments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.step_increments_id_seq OWNED BY public.step_increments.id;


--
-- Name: tax_tables; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.tax_tables (
    id bigint NOT NULL,
    percentage numeric(8,2) DEFAULT '0'::numeric NOT NULL,
    min_amount numeric(8,2) DEFAULT '0'::numeric NOT NULL,
    max_amount numeric(8,2) DEFAULT '0'::numeric NOT NULL,
    base_tax numeric(8,2) DEFAULT '0'::numeric NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.tax_tables OWNER TO postgres;

--
-- Name: tax_tables_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.tax_tables_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.tax_tables_id_seq OWNER TO postgres;

--
-- Name: tax_tables_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.tax_tables_id_seq OWNED BY public.tax_tables.id;


--
-- Name: time_data; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.time_data (
    id bigint NOT NULL,
    employee_id integer DEFAULT 0 NOT NULL,
    payroll_period_id integer DEFAULT 0 NOT NULL,
    date date,
    am_in time(0) without time zone,
    am_out time(0) without time zone,
    break_in time(0) without time zone,
    break_out time(0) without time zone,
    pm_in time(0) without time zone,
    pm_out time(0) without time zone,
    work_hours numeric(8,2) DEFAULT '0'::numeric,
    late numeric(8,2) DEFAULT '0'::numeric NOT NULL,
    undertime numeric(8,2) DEFAULT '0'::numeric NOT NULL,
    absent numeric(8,2) DEFAULT '0'::numeric NOT NULL,
    leave numeric(8,2) DEFAULT '0'::numeric NOT NULL,
    is_ob boolean DEFAULT false NOT NULL,
    ob_id integer DEFAULT 0 NOT NULL,
    is_holiday boolean DEFAULT false NOT NULL,
    holiday_id integer DEFAULT 0 NOT NULL,
    holiday_pay numeric(8,2) DEFAULT '0'::numeric NOT NULL,
    is_ot boolean DEFAULT false NOT NULL,
    ot_id integer DEFAULT 0 NOT NULL,
    ot_pay numeric(8,2) DEFAULT '0'::numeric NOT NULL,
    nd_pay numeric(8,2) DEFAULT '0'::numeric NOT NULL,
    remarks character varying(255) DEFAULT '0'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    is_shifting boolean DEFAULT false,
    work_schedule_id integer DEFAULT 0,
    ob_hours numeric(8,2) DEFAULT '0'::numeric,
    ot_hours numeric(8,2) DEFAULT '0'::numeric,
    holiday_type_id integer DEFAULT 0,
    overtime_type_id integer DEFAULT 0
);


ALTER TABLE public.time_data OWNER TO postgres;

--
-- Name: time_data_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.time_data_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.time_data_id_seq OWNER TO postgres;

--
-- Name: time_data_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.time_data_id_seq OWNED BY public.time_data.id;


--
-- Name: time_keeping_setups; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.time_keeping_setups (
    id bigint NOT NULL,
    employment_type_id integer NOT NULL,
    work_days numeric(8,2) DEFAULT '0'::numeric,
    work_hours numeric(8,2) DEFAULT '0'::numeric,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    with_holiday_pay boolean DEFAULT false
);


ALTER TABLE public.time_keeping_setups OWNER TO postgres;

--
-- Name: time_keeping_setups_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.time_keeping_setups_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.time_keeping_setups_id_seq OWNER TO postgres;

--
-- Name: time_keeping_setups_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.time_keeping_setups_id_seq OWNED BY public.time_keeping_setups.id;


--
-- Name: users; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    email_verified_at timestamp(0) without time zone,
    password character varying(255) NOT NULL,
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    active boolean,
    locked boolean,
    locked_date timestamp(0) without time zone,
    employee_no character varying(255),
    professor_no character varying(255),
    photo text,
    with_hrm_access boolean DEFAULT false NOT NULL,
    with_hrt_access boolean DEFAULT false NOT NULL,
    with_hrp_access boolean DEFAULT false NOT NULL,
    with_cpm_access boolean DEFAULT false NOT NULL,
    is_admin boolean DEFAULT false NOT NULL,
    has_change_password boolean DEFAULT false,
    is_applicant boolean DEFAULT false NOT NULL
);


ALTER TABLE public.users OWNER TO postgres;

--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.users_id_seq OWNER TO postgres;

--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: work_cancellations; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.work_cancellations (
    id bigint NOT NULL,
    date_from date,
    date_to date,
    with_pay boolean DEFAULT false,
    reason character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    time_from time(0) without time zone,
    time_to time(0) without time zone
);


ALTER TABLE public.work_cancellations OWNER TO postgres;

--
-- Name: work_cancellations_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.work_cancellations_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.work_cancellations_id_seq OWNER TO postgres;

--
-- Name: work_cancellations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.work_cancellations_id_seq OWNED BY public.work_cancellations.id;


--
-- Name: access id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.access ALTER COLUMN id SET DEFAULT nextval('public.access_id_seq'::regclass);


--
-- Name: adjectival_ratings id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.adjectival_ratings ALTER COLUMN id SET DEFAULT nextval('public.adjectival_ratings_id_seq'::regclass);


--
-- Name: applicant_details id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.applicant_details ALTER COLUMN id SET DEFAULT nextval('public.applicant_details_id_seq'::regclass);


--
-- Name: applicant_headers id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.applicant_headers ALTER COLUMN id SET DEFAULT nextval('public.applicant_headers_id_seq'::regclass);


--
-- Name: application_status id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.application_status ALTER COLUMN id SET DEFAULT nextval('public.application_status_id_seq'::regclass);


--
-- Name: audits id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.audits ALTER COLUMN id SET DEFAULT nextval('public.audit_id_seq'::regclass);


--
-- Name: biometric_logs id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.biometric_logs ALTER COLUMN id SET DEFAULT nextval('public.biometric_logs_id_seq'::regclass);


--
-- Name: blood_types id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.blood_types ALTER COLUMN id SET DEFAULT nextval('public.blood_types_id_seq'::regclass);


--
-- Name: branches id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.branches ALTER COLUMN id SET DEFAULT nextval('public.branches_id_seq'::regclass);


--
-- Name: citizenships id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.citizenships ALTER COLUMN id SET DEFAULT nextval('public.citizenships_id_seq'::regclass);


--
-- Name: civil_status id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.civil_status ALTER COLUMN id SET DEFAULT nextval('public.civil_status_id_seq'::regclass);


--
-- Name: companies id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.companies ALTER COLUMN id SET DEFAULT nextval('public.companies_id_seq'::regclass);


--
-- Name: deductions id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.deductions ALTER COLUMN id SET DEFAULT nextval('public.deductions_id_seq'::regclass);


--
-- Name: departments id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.departments ALTER COLUMN id SET DEFAULT nextval('public.departments_id_seq'::regclass);


--
-- Name: divisions id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.divisions ALTER COLUMN id SET DEFAULT nextval('public.divisions_id_seq'::regclass);


--
-- Name: eligibilities id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.eligibilities ALTER COLUMN id SET DEFAULT nextval('public.eligibilities_id_seq'::regclass);


--
-- Name: employee_children children_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.employee_children ALTER COLUMN children_id SET DEFAULT nextval('public.employee_children_children_id_seq'::regclass);


--
-- Name: employee_children_temps children_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.employee_children_temps ALTER COLUMN children_id SET DEFAULT nextval('public.employee_children_temps_children_id_seq'::regclass);


--
-- Name: employee_dependents id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.employee_dependents ALTER COLUMN id SET DEFAULT nextval('public.employee_dependents_id_seq'::regclass);


--
-- Name: employee_educations education_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.employee_educations ALTER COLUMN education_id SET DEFAULT nextval('public.employee_educations_id_seq'::regclass);


--
-- Name: employee_educations_temps education_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.employee_educations_temps ALTER COLUMN education_id SET DEFAULT nextval('public.employee_educations_temps_education_id_seq'::regclass);


--
-- Name: employee_employment_records employment_record_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.employee_employment_records ALTER COLUMN employment_record_id SET DEFAULT nextval('public.employee_employment_records_employment_record_id_seq'::regclass);


--
-- Name: employee_employment_records_temps employment_record_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.employee_employment_records_temps ALTER COLUMN employment_record_id SET DEFAULT nextval('public.employee_employment_records_temps_employment_record_id_seq'::regclass);


--
-- Name: employee_examinations examination_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.employee_examinations ALTER COLUMN examination_id SET DEFAULT nextval('public.employee_examinations_examination_id_seq'::regclass);


--
-- Name: employee_examinations_temps examination_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.employee_examinations_temps ALTER COLUMN examination_id SET DEFAULT nextval('public.employee_examinations_temps_examination_id_seq'::regclass);


--
-- Name: employee_memberships membership_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.employee_memberships ALTER COLUMN membership_id SET DEFAULT nextval('public.employee_memberships_membership_id_seq'::regclass);


--
-- Name: employee_memberships_temps membership_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.employee_memberships_temps ALTER COLUMN membership_id SET DEFAULT nextval('public.employee_memberships_temps_membership_id_seq'::regclass);


--
-- Name: employee_offboardings id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.employee_offboardings ALTER COLUMN id SET DEFAULT nextval('public.employee_offboardings_id_seq'::regclass);


--
-- Name: employee_organizations organization_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.employee_organizations ALTER COLUMN organization_id SET DEFAULT nextval('public.employee_organizations_organization_id_seq'::regclass);


--
-- Name: employee_organizations_temps organization_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.employee_organizations_temps ALTER COLUMN organization_id SET DEFAULT nextval('public.employee_organizations_temps_organization_id_seq'::regclass);


--
-- Name: employee_promotions id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.employee_promotions ALTER COLUMN id SET DEFAULT nextval('public.employee_promotions_id_seq'::regclass);


--
-- Name: employee_recognations recognation_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.employee_recognations ALTER COLUMN recognation_id SET DEFAULT nextval('public.employee_recognations_recognation_id_seq'::regclass);


--
-- Name: employee_recognations_temps recognation_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.employee_recognations_temps ALTER COLUMN recognation_id SET DEFAULT nextval('public.employee_recognations_temps_recognation_id_seq'::regclass);


--
-- Name: employee_references reference_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.employee_references ALTER COLUMN reference_id SET DEFAULT nextval('public.employee_references_reference_id_seq'::regclass);


--
-- Name: employee_references_temps reference_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.employee_references_temps ALTER COLUMN reference_id SET DEFAULT nextval('public.employee_references_temps_reference_id_seq'::regclass);


--
-- Name: employee_requests id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.employee_requests ALTER COLUMN id SET DEFAULT nextval('public.employee_requests_id_seq'::regclass);


--
-- Name: employee_skills skill_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.employee_skills ALTER COLUMN skill_id SET DEFAULT nextval('public.employee_skills_skill_id_seq'::regclass);


--
-- Name: employee_skills_temps skill_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.employee_skills_temps ALTER COLUMN skill_id SET DEFAULT nextval('public.employee_skills_temps_skill_id_seq'::regclass);


--
-- Name: employee_trainings training_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.employee_trainings ALTER COLUMN training_id SET DEFAULT nextval('public.employee_trainings_training_id_seq'::regclass);


--
-- Name: employee_trainings_temps training_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.employee_trainings_temps ALTER COLUMN training_id SET DEFAULT nextval('public.employee_trainings_temps_training_id_seq'::regclass);


--
-- Name: employees id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.employees ALTER COLUMN id SET DEFAULT nextval('public.employees_id_seq'::regclass);


--
-- Name: employees_temps id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.employees_temps ALTER COLUMN id SET DEFAULT nextval('public.employees_temps_id_seq'::regclass);


--
-- Name: employment_types id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.employment_types ALTER COLUMN id SET DEFAULT nextval('public.employment_types_id_seq'::regclass);


--
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- Name: fix_schedules id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.fix_schedules ALTER COLUMN id SET DEFAULT nextval('public.fix_schedules_id_seq'::regclass);


--
-- Name: fix_schedules_details id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.fix_schedules_details ALTER COLUMN id SET DEFAULT nextval('public.fix_schdules_details_id_seq'::regclass);


--
-- Name: genders id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.genders ALTER COLUMN id SET DEFAULT nextval('public.genders_id_seq'::regclass);


--
-- Name: gsis id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.gsis ALTER COLUMN id SET DEFAULT nextval('public.gsis_id_seq'::regclass);


--
-- Name: incomes id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.incomes ALTER COLUMN id SET DEFAULT nextval('public.incomes_id_seq'::regclass);


--
-- Name: ipcr id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ipcr ALTER COLUMN id SET DEFAULT nextval('public.ipcr_id_seq'::regclass);


--
-- Name: ipcr_details id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ipcr_details ALTER COLUMN id SET DEFAULT nextval('public.ipcr_details_id_seq'::regclass);


--
-- Name: ipcr_headers id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ipcr_headers ALTER COLUMN id SET DEFAULT nextval('public.ipcr_headers_id_seq'::regclass);


--
-- Name: learnings id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.learnings ALTER COLUMN id SET DEFAULT nextval('public.learnings_id_seq'::regclass);


--
-- Name: leave_credits id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.leave_credits ALTER COLUMN id SET DEFAULT nextval('public.leave_credits_id_seq'::regclass);


--
-- Name: leave_details id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.leave_details ALTER COLUMN id SET DEFAULT nextval('public.leave_details_id_seq'::regclass);


--
-- Name: leave_headers id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.leave_headers ALTER COLUMN id SET DEFAULT nextval('public.leave_headers_id_seq'::regclass);


--
-- Name: loan_applications id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.loan_applications ALTER COLUMN id SET DEFAULT nextval('public.loan_applications_id_seq'::regclass);


--
-- Name: menus id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.menus ALTER COLUMN id SET DEFAULT nextval('public.menus_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: name_prefixes id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.name_prefixes ALTER COLUMN id SET DEFAULT nextval('public.name_prefixes_id_seq'::regclass);


--
-- Name: name_suffixes id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.name_suffixes ALTER COLUMN id SET DEFAULT nextval('public.name_suffixes_id_seq'::regclass);


--
-- Name: non_plantillas id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.non_plantillas ALTER COLUMN id SET DEFAULT nextval('public.non_plantillas_id_seq'::regclass);


--
-- Name: offboarding_natures id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.offboarding_natures ALTER COLUMN id SET DEFAULT nextval('public.offboarding_natures_id_seq'::regclass);


--
-- Name: overtime_types id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.overtime_types ALTER COLUMN id SET DEFAULT nextval('public.overtime_types_id_seq'::regclass);


--
-- Name: payroll_cutoffs id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payroll_cutoffs ALTER COLUMN id SET DEFAULT nextval('public.payroll_cutoffs_id_seq'::regclass);


--
-- Name: payroll_deductions id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payroll_deductions ALTER COLUMN id SET DEFAULT nextval('public.payroll_deductions_id_seq'::regclass);


--
-- Name: payroll_incomes id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payroll_incomes ALTER COLUMN id SET DEFAULT nextval('public.payroll_incomes_id_seq'::regclass);


--
-- Name: payroll_intervals id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payroll_intervals ALTER COLUMN id SET DEFAULT nextval('public.payroll_intervals_id_seq'::regclass);


--
-- Name: payroll_item_schedule_details id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payroll_item_schedule_details ALTER COLUMN id SET DEFAULT nextval('public.payroll_item_schedule_details_id_seq'::regclass);


--
-- Name: payroll_item_schedule_headers id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payroll_item_schedule_headers ALTER COLUMN id SET DEFAULT nextval('public.payroll_item_schedule_headers_id_seq'::regclass);


--
-- Name: payroll_periods id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payroll_periods ALTER COLUMN id SET DEFAULT nextval('public.payroll_periods_id_seq'::regclass);


--
-- Name: payroll_summaries id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payroll_summaries ALTER COLUMN id SET DEFAULT nextval('public.payroll_summaries_id_seq'::regclass);


--
-- Name: philhealths id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.philhealths ALTER COLUMN id SET DEFAULT nextval('public.philhealths_id_seq'::regclass);


--
-- Name: plantillas id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.plantillas ALTER COLUMN id SET DEFAULT nextval('public.plantillas_id_seq'::regclass);


--
-- Name: positions id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.positions ALTER COLUMN id SET DEFAULT nextval('public.positions_id_seq'::regclass);


--
-- Name: promotion_natures id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.promotion_natures ALTER COLUMN id SET DEFAULT nextval('public.promotion_natures_id_seq'::regclass);


--
-- Name: religions id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.religions ALTER COLUMN id SET DEFAULT nextval('public.religions_id_seq'::regclass);


--
-- Name: salary_adjustments id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.salary_adjustments ALTER COLUMN id SET DEFAULT nextval('public.salary_adjustments_id_seq'::regclass);


--
-- Name: salary_grades id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.salary_grades ALTER COLUMN id SET DEFAULT nextval('public.salary_grades_id_seq'::regclass);


--
-- Name: salary_schedules id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.salary_schedules ALTER COLUMN id SET DEFAULT nextval('public.salary_schedules_id_seq'::regclass);


--
-- Name: salary_schedules_details id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.salary_schedules_details ALTER COLUMN id SET DEFAULT nextval('public.salary_schedules_details_id_seq'::regclass);


--
-- Name: salary_steps id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.salary_steps ALTER COLUMN id SET DEFAULT nextval('public.salary_steps_id_seq'::regclass);


--
-- Name: schedule_days id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.schedule_days ALTER COLUMN id SET DEFAULT nextval('public.schedule_days_id_seq'::regclass);


--
-- Name: sections id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sections ALTER COLUMN id SET DEFAULT nextval('public.sections_id_seq'::regclass);


--
-- Name: semester_ratings id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.semester_ratings ALTER COLUMN id SET DEFAULT nextval('public.semester_ratings_id_seq'::regclass);


--
-- Name: service_records service_record_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.service_records ALTER COLUMN service_record_id SET DEFAULT nextval('public.service_records_service_record_id_seq'::regclass);


--
-- Name: service_records_temps service_record_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.service_records_temps ALTER COLUMN service_record_id SET DEFAULT nextval('public.service_records_temps_service_record_id_seq'::regclass);


--
-- Name: shift_schedules_details id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.shift_schedules_details ALTER COLUMN id SET DEFAULT nextval('public.shift_schedules_details_id_seq'::regclass);


--
-- Name: shift_schedules_headers id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.shift_schedules_headers ALTER COLUMN id SET DEFAULT nextval('public.shift_schedules_headers_id_seq'::regclass);


--
-- Name: sss id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sss ALTER COLUMN id SET DEFAULT nextval('public.sss_id_seq'::regclass);


--
-- Name: step_increments id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.step_increments ALTER COLUMN id SET DEFAULT nextval('public.step_increments_id_seq'::regclass);


--
-- Name: tax_tables id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tax_tables ALTER COLUMN id SET DEFAULT nextval('public.tax_tables_id_seq'::regclass);


--
-- Name: time_data id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.time_data ALTER COLUMN id SET DEFAULT nextval('public.time_data_id_seq'::regclass);


--
-- Name: time_keeping_setups id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.time_keeping_setups ALTER COLUMN id SET DEFAULT nextval('public.time_keeping_setups_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Name: work_cancellations id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.work_cancellations ALTER COLUMN id SET DEFAULT nextval('public.work_cancellations_id_seq'::regclass);


--
-- Data for Name: access; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.access (id, user_id, menu_id, status, created_at, updated_at) FROM stdin;
1	1	62	t	\N	\N
2	1	1	t	\N	\N
3	1	66	t	\N	\N
4	1	64	t	\N	\N
5	1	17	t	\N	\N
6	1	2	t	\N	\N
7	1	65	t	\N	\N
8	1	57	t	\N	\N
9	1	3	t	\N	\N
10	1	6	t	\N	\N
11	1	4	t	\N	\N
12	1	46	t	\N	\N
13	1	51	t	\N	\N
14	1	55	t	\N	\N
15	1	54	t	\N	\N
16	1	48	t	\N	\N
17	1	5	t	\N	\N
18	1	56	t	\N	\N
19	1	53	t	\N	\N
20	1	52	t	\N	\N
21	1	68	t	\N	\N
22	1	8	t	\N	\N
23	1	7	t	\N	\N
24	1	9	t	\N	\N
25	1	35	t	\N	\N
26	1	34	t	\N	\N
27	1	20	t	\N	\N
28	1	19	t	\N	\N
29	1	33	t	\N	\N
30	1	15	t	\N	\N
31	1	12	t	\N	\N
32	1	60	t	\N	\N
33	1	25	t	\N	\N
34	1	18	t	\N	\N
35	1	21	t	\N	\N
36	1	30	t	\N	\N
37	1	45	t	\N	\N
38	1	42	t	\N	\N
39	1	40	t	\N	\N
40	1	41	t	\N	\N
41	1	14	t	\N	\N
42	1	49	t	\N	\N
43	1	26	t	\N	\N
44	1	43	t	\N	\N
45	1	23	t	\N	\N
46	1	24	t	\N	\N
47	1	67	t	\N	\N
48	1	38	t	\N	\N
49	1	44	t	\N	\N
50	1	39	t	\N	\N
51	1	47	t	\N	\N
52	1	36	t	\N	\N
53	1	29	t	\N	\N
54	1	13	t	\N	\N
55	1	11	t	\N	\N
56	1	37	t	\N	\N
57	1	58	t	\N	\N
58	1	22	t	\N	\N
59	1	32	t	\N	\N
60	1	27	t	\N	\N
61	1	31	t	\N	\N
62	1	61	t	\N	\N
63	1	59	t	\N	\N
64	1	63	t	\N	\N
65	1	28	t	\N	\N
66	1	50	t	\N	\N
67	1	10	t	\N	\N
68	1	16	t	\N	\N
\.


--
-- Data for Name: adjectival_ratings; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.adjectival_ratings (id, numerical_rating1, numerical_rating2, adjectival_rating, active, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: applicant_details; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.applicant_details (id, applicant_id, application_status_id, position_applied_id, is_plantilla, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: applicant_headers; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.applicant_headers (id, applicant_no, photo, first_name, middle_name, last_name, address, birth_date, age, gender, mobile_no, email, employee_no, resume, application_status_id, application_date, user_id, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: application_status; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.application_status (id, name, created_at, updated_at) FROM stdin;
1	New Applicant	\N	\N
2	Not Qualified	\N	\N
3	Not to Proceed	\N	\N
4	For Hiring	\N	\N
5	Proceed Next Step	\N	\N
\.


--
-- Data for Name: audits; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.audits (id, user_id, module, menu, activity, description, created_at, updated_at) FROM stdin;
1	1	Control Panel	Access Rights	Update	Update Administrator access rights informations.	2022-06-17 03:54:08	2022-06-17 03:54:08
2	1	Control Panel	Access Rights	Update	Update Administrator access rights informations.	2022-06-17 03:55:23	2022-06-17 03:55:23
\.


--
-- Data for Name: biometric_logs; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.biometric_logs (id, access_no, date, am_in, am_out, break_in, break_out, pm_in, pm_out, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: blood_types; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.blood_types (id, name, active, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: branches; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.branches (id, name, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: citizenships; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.citizenships (id, name, active, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: civil_status; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.civil_status (id, name, active, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: companies; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.companies (id, logo, name, address, email, telephone_no, mobile_no, created_at, updated_at) FROM stdin;
1	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	Mindanao State University - Marawi Campus	Marawi City, Northern Mindanao	msu.main@msu.edu	(123) 456-7890	-	\N	\N
\.


--
-- Data for Name: deductions; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.deductions (id, name, active, is_sss, is_gsis, is_philhealth, is_pagibig, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: departments; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.departments (id, code, name, functionality, is_academic, created_at, updated_at, active, employee_id) FROM stdin;
\.


--
-- Data for Name: divisions; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.divisions (id, code, name, active, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: eligibilities; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.eligibilities (id, name, active, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: employee_children; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.employee_children (children_id, employee_id, child_name, child_birthdate, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: employee_children_temps; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.employee_children_temps (children_id, request_id, employee_id, child_name, child_birthdate, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: employee_dependents; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.employee_dependents (id, employee_id, name, relationship, course, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: employee_educations; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.employee_educations (education_id, employee_id, academic_level_id, school_name, program, "from", "to", graduated_year, units_earned, honors, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: employee_educations_temps; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.employee_educations_temps (education_id, request_id, employee_id, academic_level_id, school_name, program, "from", "to", graduated_year, units_earned, honors, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: employee_employment_records; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.employee_employment_records (employment_record_id, employee_id, work_start_date, work_end_date, work_company, monthly_salary, salary_grade_step, status_of_appointment, "position", government_service_id, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: employee_employment_records_temps; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.employee_employment_records_temps (employment_record_id, request_id, employee_id, work_start_date, work_end_date, work_company, monthly_salary, salary_grade_step, status_of_appointment, "position", government_service_id, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: employee_examinations; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.employee_examinations (examination_id, employee_id, exam_rating, exam_date, place_of_exam, license_number, date_released, created_at, updated_at, eligibility_id) FROM stdin;
\.


--
-- Data for Name: employee_examinations_temps; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.employee_examinations_temps (examination_id, request_id, employee_id, exam_rating, exam_date, place_of_exam, license_number, date_released, eligibility_id, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: employee_memberships; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.employee_memberships (membership_id, employee_id, membership, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: employee_memberships_temps; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.employee_memberships_temps (membership_id, request_id, employee_id, membership, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: employee_offboardings; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.employee_offboardings (id, employee_id, nature_id, remarks, date_effectivity, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: employee_organizations; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.employee_organizations (organization_id, employee_id, organization, org_from, org_to, org_hours, org_position, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: employee_organizations_temps; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.employee_organizations_temps (organization_id, request_id, employee_id, organization, org_from, org_to, org_hours, org_position, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: employee_promotions; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.employee_promotions (id, employee_id, position_id, plantilla_id, is_plantilla, is_teaching, nature_of_appointment_id, employment_type_id, department_id, branch_id, payroll_interval_id, old_salary, old_tax_amount, old_gsis_amount, old_sss_amount, old_pagibig_amount, old_philhealth_amount, new_salary, new_tax_amount, new_gsis_amount, new_sss_amount, new_pagibig_amount, new_philhealth_amount, date_position_appointed, date_of_effectivity, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: employee_recognations; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.employee_recognations (recognation_id, employee_id, recognation, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: employee_recognations_temps; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.employee_recognations_temps (recognation_id, request_id, employee_id, recognation, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: employee_references; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.employee_references (reference_id, employee_id, ref_name, ref_address, ref_occupation, ref_contact_no, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: employee_references_temps; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.employee_references_temps (reference_id, request_id, employee_id, ref_name, ref_address, ref_occupation, ref_contact_no, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: employee_requests; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.employee_requests (id, employee_id, status_id, request_date, created_at, updated_at, remarks) FROM stdin;
\.


--
-- Data for Name: employee_skills; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.employee_skills (skill_id, employee_id, skill, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: employee_skills_temps; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.employee_skills_temps (skill_id, request_id, employee_id, skill, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: employee_trainings; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.employee_trainings (training_id, employee_id, training, training_from, training_to, hours, sponsored_by, learning_id, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: employee_trainings_temps; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.employee_trainings_temps (training_id, request_id, employee_id, training, training_from, training_to, hours, sponsored_by, learning_id, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: employees; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.employees (id, photo, employee_no, access_no, name_prefix_id, first_name, middle_name, last_name, name_suffix_id, birth_place, birthdate, age, gender_id, height, weight, email, mobile_no, telephone_no, citizenship_id, civil_status_id, religion_id, is_dual_citizent, by_birth, by_naturalization, indicate_country, ra_postal_id, ra_house_no, ra_barangay, ra_street, ra_village, pa_postal_id, pa_house_no, pa_barangay, pa_street, pa_village, father_name_prefix_id, father_first_name, father_middle_name, father_last_name, father_name_suffix_id, mother_name_prefix_id, mother_first_name, mother_middle_name, mother_last_name, mother_name_suffix_id, spouse_name_prefix_id, spouse_first_name, spouse_middle_name, spouse_last_name, spouse_name_suffix_id, spouse_occupation, spouse_employer, spouse_business_address, company_id, branch_id, department_id, work_schedule_id, employment_type_id, position_id, plantilla_id, is_shifting, is_plantilla, is_employee, is_teaching, date_hired, tin_no, gsis_no, sss_no, pagibig_no, philhealth_no, salary, tax_amount, gsis_amount, sss_amount, pagibig_amount, philhealth_amount, payroll_interval_id, active, created_at, updated_at, blood_type_id, ra_region, ra_province, ra_city, pa_region, pa_province, pa_city, salary_grade_id, salary_step_id, ra_region_name, ra_province_name, ra_city_name, pa_region_name, pa_province_name, pa_city_name, end_date, date_applied, application_status_id, position_applied_id, division_id, section_id, account_no) FROM stdin;
\.


--
-- Data for Name: employees_temps; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.employees_temps (id, request_id, employee_id, photo, employee_no, access_no, name_prefix_id, first_name, middle_name, last_name, name_suffix_id, birth_place, birthdate, age, gender_id, height, weight, blood_type, email, mobile_no, telephone_no, citizenship_id, civil_status_id, religion_id, is_dual_citizent, by_birth, by_naturalization, indicate_country, ra_postal_id, ra_house_no, ra_barangay, ra_street, ra_village, pa_postal_id, pa_house_no, pa_barangay, pa_street, pa_village, father_name_prefix_id, father_first_name, father_middle_name, father_last_name, father_name_suffix_id, mother_name_prefix_id, mother_first_name, mother_middle_name, mother_last_name, mother_name_suffix_id, spouse_name_prefix_id, spouse_first_name, spouse_middle_name, spouse_last_name, spouse_name_suffix_id, spouse_occupation, spouse_employer, spouse_business_address, company_id, branch_id, department_id, work_schedule_id, employment_type_id, position_id, plantilla_id, is_shifting, is_plantilla, is_employee, is_teaching, date_hired, tin_no, gsis_no, sss_no, pagibig_no, philhealth_no, salary, tax_amount, gsis_amount, sss_amount, pagibig_amount, philhealth_amount, payroll_interval_id, active, blood_type_id, ra_region, ra_province, ra_city, pa_region, pa_province, pa_city, ra_region_name, ra_province_name, ra_city_name, pa_region_name, pa_province_name, pa_city_name, salary_grade_id, salary_step_id, end_date, date_applied, application_status_id, position_applied_id, division_id, section_id, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: employment_types; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.employment_types (id, name, active, created_at, updated_at, with_end_contract) FROM stdin;
\.


--
-- Data for Name: failed_jobs; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.failed_jobs (id, connection, queue, payload, exception, failed_at) FROM stdin;
\.


--
-- Data for Name: fix_schedules; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.fix_schedules (id, name, no_late, no_undertime, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: fix_schedules_details; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.fix_schedules_details (id, fix_schedule_id, day_id, am_in, am_out, break_in, break_out, pm_in, pm_out, grace_period, flexi_hours, work_hours, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: genders; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.genders (id, name, active, created_at, updated_at) FROM stdin;
1	Female	t	\N	\N
2	Male	t	\N	\N
\.


--
-- Data for Name: gsis; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.gsis (id, year, multiplier, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: holiday_tagging_details; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.holiday_tagging_details (id, holiday_tagging_id, holiday_id, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: holiday_tagging_headers; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.holiday_tagging_headers (id, branch_id, holiday_type_id, created_at, updated_at, year) FROM stdin;
\.


--
-- Data for Name: holiday_types; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.holiday_types (id, name, rate, active, created_at, updated_at, absent_with_pay) FROM stdin;
\.


--
-- Data for Name: holidays; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.holidays (id, name, holiday_type, branch, date, active, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: incomes; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.incomes (id, name, is_taxable, active, created_at, updated_at, is_time_related) FROM stdin;
\.


--
-- Data for Name: ipcr; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.ipcr (id, employee_id, numerical_rating, adjectival_rating, date_from, date_to, year, progress, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: ipcr_details; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.ipcr_details (id, ipcr_header_id, employee_id, division_id, rating, adjectival_rating, attachment, created_at, updated_at, progress) FROM stdin;
\.


--
-- Data for Name: ipcr_headers; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.ipcr_headers (id, semester_id, department_id, division_id, is_posted, created_at, updated_at, year, month_from, month_to) FROM stdin;
\.


--
-- Data for Name: learnings; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.learnings (id, name, active, created_at, updated_at) FROM stdin;
1	Training	t	\N	\N
2	Managerial	t	\N	\N
3	Supervisory	t	\N	\N
\.


--
-- Data for Name: leave_credits; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.leave_credits (id, employee_id, leave_type_id, credits, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: leave_details; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.leave_details (id, leave_id, leave_date, with_pay, without_pay, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: leave_headers; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.leave_headers (id, employee_id, leave_type_id, day_type_id, date_from, date_to, reason, created_at, updated_at, approved, disapproved, processed_by, processed_date, remarks) FROM stdin;
\.


--
-- Data for Name: leave_types; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.leave_types (id, name, active, created_at, updated_at, service_credit) FROM stdin;
\.


--
-- Data for Name: loan_applications; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.loan_applications (id, deduction_id, employee_id, loan_amount, loan_amortization, remarks, effectivity_date, end_date, is_approve, is_disapprove, created_at, updated_at, payment, balance) FROM stdin;
\.


--
-- Data for Name: menus; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.menus (id, menu, description, active, created_at, updated_at, menu_key, module_id, status) FROM stdin;
1	Employee Records	Contains Employee profile informations.	t	\N	\N	employee_record                                                                                                                                                                                                                                           	1	f
2	Promotion	Create process for employee promotion and  movement.	t	\N	\N	promotion                                                                                                                                                                                                                                                 	1	f
3	Step Increment	Create process  to increment regular employee's salary step. 	t	\N	\N	step_increment                                                                                                                                                                                                                                            	1	f
4	Fixed Schedule	Create fixed schedules for employees.	t	\N	\N	fixed_schedule                                                                                                                                                                                                                                            	2	f
5	Shifting Schedule	Create shifting schedule for employess.	t	\N	\N	shifting_schedule                                                                                                                                                                                                                                         	2	f
6	Assign Schedule	Create process to assign employees for specific schedule. 	t	\N	\N	assign_schedule                                                                                                                                                                                                                                           	2	f
7	Payroll Periods	Create payroll periods process.	t	\N	\N	payroll_period                                                                                                                                                                                                                                            	3	f
8	Payitem Schedule	Assign income and deduction for every payroll.	t	\N	\N	payitem_schedule                                                                                                                                                                                                                                          	3	f
9	Payroll Process	Create employee payroll's.	t	\N	\N	payroll_process                                                                                                                                                                                                                                           	3	f
10	User's List	Display list of users and contain process to edit access rights.	t	\N	\N	user_list                                                                                                                                                                                                                                                 	4	f
11	Position Setup	Create maintenance data for  employee positions.	t	\N	\N	positions                                                                                                                                                                                                                                                 	4	f
12	Department Setup	Create Department maintenance data.	t	\N	\N	departments                                                                                                                                                                                                                                               	4	f
13	Plantilla Setup	Create Plantilla position.	t	\N	\N	plantilla_setup                                                                                                                                                                                                                                           	4	f
14	Income Setup	Create income maintenance data.	t	\N	\N	income_types                                                                                                                                                                                                                                              	4	f
15	Deduction Setup	Create deduction maintenance data.	t	\N	\N	deduction_type                                                                                                                                                                                                                                            	4	f
16	User Activities	Display all users system activity.	t	\N	\N	user_activities                                                                                                                                                                                                                                           	4	f
17	Off-Boarding	Process employee off-boarding.	t	\N	\N	off-boarding                                                                                                                                                                                                                                              	1	f
18	Employment Types	Create type of emplyment data maintenance.	t	\N	\N	employment_type                                                                                                                                                                                                                                           	4	f
19	Civil Status	Create civil status maintenance data.	t	\N	\N	civil_status                                                                                                                                                                                                                                              	4	f
20	Citizenship Setup	Create citizenship maintenance data.	t	\N	\N	citizenship_setup                                                                                                                                                                                                                                         	4	f
21	Gender Setup	Create gender maintenance data.	t	\N	\N	gender_setup                                                                                                                                                                                                                                              	4	f
22	Religion Setup	Create religion maintenance data. 	t	\N	\N	religion_setup                                                                                                                                                                                                                                            	4	f
23	Name Prefix Setup	Create name prefix maintenance data.	t	\N	\N	name_prefix                                                                                                                                                                                                                                               	4	f
24	Name Suffix Setup	Create name suffix maintenance data.	t	\N	\N	name_suffix                                                                                                                                                                                                                                               	4	f
25	Eligibility Setup	Create eligibility maintenance data.	t	\N	\N	eligibility_setup                                                                                                                                                                                                                                         	4	f
26	Learning and Development Setup	Create learning and development maintenance data.	t	\N	\N	learning_setup                                                                                                                                                                                                                                            	4	f
27	Salary Schedule Setup	Create salary schedule maintenance data.	t	\N	\N	salary_schedule                                                                                                                                                                                                                                           	4	f
28	Tax Table Setup	Maintain tax table data.	t	\N	\N	tax_table                                                                                                                                                                                                                                                 	4	f
29	PhilHealth Table Setup	Maintain philhealth data.	t	\N	\N	philhealth_table                                                                                                                                                                                                                                          	4	f
30	GSIS Table Setup	Maintenance gsis table data.	t	\N	\N	gsis_table                                                                                                                                                                                                                                                	4	f
31	Salary Step Setup	Create salary step maintenance data.	t	\N	\N	salary_step                                                                                                                                                                                                                                               	4	f
32	Salary Grade Setup	Create salary grade maintenance data.	t	\N	\N	salary_grade                                                                                                                                                                                                                                              	4	f
33	Company Setup	Create company information.	t	\N	\N	company_setup                                                                                                                                                                                                                                             	4	f
34	Branch Setup	Create branch information.	t	\N	\N	branch_setup                                                                                                                                                                                                                                              	4	f
35	Blood Type Setup	Create blood type maintenance data.	t	\N	\N	blood_type                                                                                                                                                                                                                                                	4	f
36	Payroll Interval Setup	Create payroll interval maintenance data.	t	\N	\N	payroll_interval_setup                                                                                                                                                                                                                                    	4	f
37	Promotion Types Setup	Create promotion maintenance data.	t	\N	\N	promotion_types                                                                                                                                                                                                                                           	4	f
38	Off-Boarding Types Setup	Create off-boarding type maintenance data.	t	\N	\N	offboarding_types                                                                                                                                                                                                                                         	4	f
39	Overtime Types Setup	Create overtime types maintenance data.	t	\N	\N	overtime_types                                                                                                                                                                                                                                            	4	f
40	Holiday Types Setup	Create holiday types maintenance data.	t	\N	\N	holiday_types                                                                                                                                                                                                                                             	4	f
41	Holidays Setup	Create holidays maintenance data.	t	\N	\N	holidays_setup                                                                                                                                                                                                                                            	4	f
42	Holiday Taggings Setup	Create holiday taggings maintenance data.	t	\N	\N	holiday_taggings_setup                                                                                                                                                                                                                                    	4	f
43	Leave Types Setup	Create leave types maintenance data.	t	\N	\N	leave_types                                                                                                                                                                                                                                               	4	f
44	Official Business Types Setup	Create official business types maintenance data.	t	\N	\N	official_business_types                                                                                                                                                                                                                                   	4	f
45	Holiday Tagging Setup	Create holiday tagging maintenance data.	t	\N	\N	holiday_taggings                                                                                                                                                                                                                                          	4	f
46	Leave Credits	Create leave credit balances.	t	\N	\N	leave_credits                                                                                                                                                                                                                                             	2	f
47	Payroll Cut-off Setup	Create payroll cutoff maintenance data.	t	\N	\N	payroll_cutoffs                                                                                                                                                                                                                                           	4	f
48	Process Attendance	Process employee daily time records.	t	\N	\N	process_attendance                                                                                                                                                                                                                                        	2	f
49	Income Type Setup 	Create income type maintenance data.	f	\N	\N	income_types                                                                                                                                                                                                                                              	4	f
50	Time Keeping Setup	Create time keeping setup.	t	\N	\N	time_keeping_setups                                                                                                                                                                                                                                       	4	f
51	Leave Monitoring	Monitor leave application and approvals.	t	\N	\N	leave_approvals                                                                                                                                                                                                                                           	2	f
52	Loan Application	Process loan application.	t	\N	\N	loan_applications                                                                                                                                                                                                                                         	3	f
53	Income and Deduction Setup	Create income and deduction schedule.	t	\N	\N	payroll_income_deductions                                                                                                                                                                                                                                 	3	f
54	Overtime Monitoring	Manage Overtime Applications	t	\N	\N	overtime_approvals                                                                                                                                                                                                                                        	2	f
55	Official Business Monitoring	Manage Official Business Applications	t	\N	\N	official_business_approvals                                                                                                                                                                                                                               	2	f
56	Work Cancellation	Create work cancellation.	t	\N	\N	work_cancellations                                                                                                                                                                                                                                        	2	f
57	Salary Adjustment	Process Salary Adjustment.	t	\N	\N	salary_adjustments                                                                                                                                                                                                                                        	1	f
58	Rating Types	Create ipcr rating types. 	t	\N	\N	rating                                                                                                                                                                                                                                                    	4	f
59	Semetral Rating	Create semester rating.	t	\N	\N	semester_rating                                                                                                                                                                                                                                           	4	f
60	Division Setup	Create division setup.	t	\N	\N	divisions                                                                                                                                                                                                                                                 	4	f
61	Section Setup	Create section setup.	t	\N	\N	sections                                                                                                                                                                                                                                                  	4	f
62	Applicant Records	List of Applicants.	t	\N	\N	applicants                                                                                                                                                                                                                                                	1	f
63	SSS Table Setup	Create SSS Table data.	t	\N	\N	sss_table                                                                                                                                                                                                                                                 	4	f
64	IPCR	Create IPCR Records.	t	\N	\N	ipcr                                                                                                                                                                                                                                                      	1	f
65	Review 201 Updates	Process 201 Update Request.	t	\N	\N	review_201_updates                                                                                                                                                                                                                                        	1	f
66	Export Employee Data	Export Employee Data	t	\N	\N	export_employee_data                                                                                                                                                                                                                                      	1	f
67	Non-Plantilla Setup	Post Non-Plantilla positions.	t	\N	\N	non_plantilla_setup                                                                                                                                                                                                                                       	4	f
68	PACSVAL	PACSVAL Export	t	\N	\N	pacsval                                                                                                                                                                                                                                                   	3	f
\.


--
-- Data for Name: migrations; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.migrations (id, migration, batch) FROM stdin;
1	2014_10_12_000000_create_users_table	1
2	2014_10_12_100000_create_password_resets_table	1
3	2019_08_19_000000_create_failed_jobs_table	1
4	2021_09_07_033650_add_photo_to_users_table	1
6	2021_09_09_093812_add_admin_to_users_table	2
8	2021_09_10_104821_create_menus_table	3
9	2021_09_10_105907_create_access_table	4
10	2021_09_14_072236_create_audit_table	5
11	2021_09_14_085042_create_departments_table	6
12	2021_09_14_085947_add_active_to_departments_table	7
13	2021_09_15_023538_create_positions_table	8
14	2021_09_15_043756_create_employment_types_table	9
15	2021_09_15_064022_create_civil_status_table	10
16	2021_09_15_073535_create_citizenships_table	11
17	2021_09_15_082634_create_genders_table	12
18	2021_09_15_085058_create_religions_table	13
19	2021_09_15_092153_create_name_prefixes_table	14
20	2021_09_15_095217_create_name_suffixes_table	15
21	2021_09_15_101734_create_eligibilities_table	16
22	2021_09_15_103858_create_learnings_table	17
23	2021_09_16_031831_create_plantillas_table	18
24	2021_09_16_032400_create_salary_steps_table	19
25	2021_09_16_032419_create_salary_grades_table	19
26	2021_09_16_060220_create_salary_schedules_table	20
27	2021_09_16_071830_create_salary_schedules_details_table	21
28	2021_09_16_102408_create_tax_tables_table	22
29	2021_09_16_124618_create_philhealths_table	23
30	2021_09_16_132345_create_gsis_table	24
31	2021_09_16_140254_create_employees_table	25
32	2021_09_17_063923_create_companies_table	25
33	2021_09_17_075956_create_branches_table	26
34	2021_09_20_094534_create_blood_types_table	27
35	2021_09_20_095111_add_blood_type_id_to_employees_table	28
36	2021_09_21_101328_add_address_fields_to_employees_table	29
37	2021_09_21_114633_create_payroll_intervals_table	30
38	2021_09_22_174843_create_employee_educations_table	31
39	2021_09_22_180232_create_employee_children_table	32
40	2021_09_22_182130_create_service_records_table	33
41	2021_09_23_092059_create_employee_employment_records_table	34
42	2021_09_23_093610_create_employee_examinations_table	35
44	2021_09_23_095527_create_employee_trainings_table	36
45	2021_09_23_101136_create_employee_organizations_table	37
46	2021_09_23_103132_create_employee_recognations_table	38
47	2021_09_23_103257_create_employee_skills_table	38
48	2021_09_23_103336_create_employee_memberships_table	38
49	2021_09_23_103810_create_employee_references_table	38
50	2021_09_23_161255_add_eligibility_to_employee_examinations_table	39
51	2021_09_25_090604_create_employee_promotions_table	40
52	2021_09_25_094929_create_promotion_natures_table	41
53	2021_09_25_174726_create_offboarding_natures_table	42
54	2021_09_25_174922_create_employee_offboardings_table	43
56	2021_09_26_142846_add_step_to_employees_table	44
57	2021_09_28_015050_create_schedule_days_table	45
58	2021_09_29_020847_create_overtime_types_table	46
59	2021_09_29_053937_create_fix_schedules_table	47
60	2021_09_29_064314_create_fix_schdules_details_table	48
61	2021_09_29_071809_create_leave_types_table	48
62	2021_09_29_071826_create_official_business_types_table	48
69	2021_10_11_015122_add_approved_to_leave_headers_table	49
70	2021_10_12_073824_create_shift_schedules_headers_table	49
71	2021_10_13_014252_create_shift_schedules_details_table	49
72	2021_10_13_100801_add_fields_to_payroll_intervals_table	50
73	2021_10_14_030224_create_payroll_cutoffs_table	50
74	2021_10_14_054107_create_payroll_periods_table	50
75	2021_10_18_030814_add_fields_to_overtime_types_table	50
76	2021_10_18_053539_create_time_keeping_setups_table	50
77	2021_10_18_071240_add_fields_to_overtime_applications_table	50
78	2021_10_19_025708_create_deductions_table	50
79	2021_10_19_025718_create_incomes_table	50
80	2021_10_20_082128_create_biometric_logs_table	50
81	2021_10_20_090619_create_time_data_table	50
82	2021_10_21_054526_add_fields_to_time_data_table	50
83	2021_10_25_050017_add_ot_hours_to_time_data_table	51
84	2021_10_21_014722_add_fields_to_plantillas_table	52
85	2021_10_21_100054_create_payroll_item_schedule_header_table	52
86	2021_10_22_085630_create_payroll_item_schedule_details_table	52
87	2021_10_26_053716_add_holiday_id_to_time_data_table	53
88	2021_10_26_060617_add_absent_with_pay_to_holiday_types_table	53
89	2021_10_26_023058_create_payroll_incomes_table	54
90	2021_10_26_025140_create_payroll_deductions_table	54
91	2021_10_27_083549_create_loan_applications_table	55
92	2021_10_27_134256_create_payroll_summaries_table	55
93	2021_11_02_051453_add_with_end_contract_to_employment_types_table	56
94	2021_11_02_054628_add_end_date_to_employees_table	56
95	2021_11_02_113254_add_with_holiday_pay_to_time_keeping_setups_table	56
96	2021_11_03_064257_create_work_cancellations_table	57
97	2021_10_20_030225_create_adjectival_ratings_table	58
98	2021_10_26_013817_create_ipcr_table	58
99	2021_11_04_012947_add_service_credit_to_leave_types_table	58
100	2021_11_04_023917_add_is_time_related_to_incomes_table	58
101	2021_11_04_052030_create_divisions_table	58
102	2021_11_04_052401_create_semester_ratings_table	58
103	2021_11_04_062316_create_ipcr_headers_table	58
104	2021_11_04_062343_create_ipcr_details_table	58
105	2021_11_04_084256_add_balance_to_loan_applications_table	58
106	2021_11_05_025838_create_sections_table	59
107	2021_11_09_030100_add_fields_to_work_cancellations_table	59
108	2021_11_12_034626_add_description_to_positions_table	60
109	2021_11_12_035705_add_applicant_fields_to_employees_table	60
110	2021_11_12_040406_create_application_status_table	60
111	2021_11_19_025534_create_sss_table	61
112	2021_11_08_015012_add_year_to_ipcr_headers_table	62
113	2021_11_08_022942_add_progress_to_ipcr_details_table	62
114	2021_11_11_053757_add_division_section_id_to_employees_table	62
115	2021_11_22_064602_create_months_table	62
116	2022_02_11_012140_create_employee_requests_table	63
117	2022_02_11_025053_create_employees_temps_table	63
118	2022_02_17_052054_create_employee_children_temps_table	63
119	2022_02_17_054155_create_employee_educations_temps_table	63
120	2022_02_17_055022_create_service_records_temps_table	63
121	2022_02_17_055629_create_employee_employment_records_temps_table	63
122	2022_02_17_060344_create_employee_examinations_temps_table	63
123	2022_02_17_060921_create_employee_trainings_temps_table	63
124	2022_02_17_061709_create_employee_organizations_temps_table	63
125	2022_02_17_062347_create_employee_recognations_temps_table	63
126	2022_02_17_062851_create_employee_skills_temps_table	63
127	2022_02_17_063340_create_employee_memberships_temps_table	63
128	2022_02_17_063918_create_employee_references_temps_table	63
129	2022_02_18_075204_create_notifications_table	64
130	2022_02_28_062041_add_change_password_to_users_table	65
131	2022_02_28_065015_add_remarks_to_leave_headers_table	65
132	2022_02_28_084644_add_remarks_to_official_business_applicationss_table	65
133	2022_02_28_090200_add_remarks_to_overtime_applications_table	65
134	2022_02_28_092012_add_remarks_to_employee_requests_table	65
135	2022_04_17_121432_create_non_plantillas_table	66
136	2022_04_17_154945_create_applicant_headers_table	66
137	2022_04_17_161139_create_applicant_details_table	66
138	2022_04_18_063837_add_is_applicant_to_users_table	66
139	2022_05_03_225653_add_field_to_non_plantillas_table	67
140	2022_05_07_033957_create_salary_adjustments_table	68
141	2022_05_09_025347_create_employee_dependents_table	68
142	2022_05_17_062243_add_account_no_to_employees_table	69
\.


--
-- Data for Name: months; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.months (id, name) FROM stdin;
1	January
2	February
3	March
4	April
5	May
6	June
7	July
8	August
9	September
10	October
11	November
12	December
\.


--
-- Data for Name: name_prefixes; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.name_prefixes (id, name, active, created_at, updated_at) FROM stdin;
1	Mr.	t	\N	\N
2	Ms.	t	\N	\N
3	Mrs.	t	\N	\N
4	Dr.	t	\N	\N
5	Atty.	t	\N	\N
6	Cpt.	t	\N	\N
7	Engr.	t	\N	\N
\.


--
-- Data for Name: name_suffixes; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.name_suffixes (id, name, active, created_at, updated_at) FROM stdin;
1	Jr.	t	\N	\N
2	Sr.	t	\N	\N
3	I.	t	\N	\N
4	II.	t	\N	\N
5	III.	t	\N	\N
6	IV.	t	\N	\N
7	V.	t	\N	\N
\.


--
-- Data for Name: non_plantillas; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.non_plantillas (id, position_id, salary_step_id, salary_grade_id, salary, vacant, department_id, description, qualification, eligibility, education, experience, training, publication_from, publication_to, status, created_at, updated_at, employee_type_id, number_of_months) FROM stdin;
\.


--
-- Data for Name: notifications; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.notifications (id, type, notifiable_type, notifiable_id, data, read_at, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: offboarding_natures; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.offboarding_natures (id, name, active, created_at, updated_at) FROM stdin;
1	Resignation	t	\N	\N
2	Termination	t	\N	\N
3	TEST	t	\N	\N
\.


--
-- Data for Name: official_business_applications; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.official_business_applications (id, employee_id, date, date_time_from, date_time_to, client, purpose, approved, disapproved, created_at, updated_at, remarks) FROM stdin;
\.


--
-- Data for Name: official_business_types; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.official_business_types (id, name, active, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: overtime_applications; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.overtime_applications (id, employee_id, overtime_type_id, date, date_time_from, date_time_to, total_hours, service_credits, payroll, remarks, approved, disapproved, created_at, updated_at, ot_amount, nd_amount, disapprove_remarks) FROM stdin;
\.


--
-- Data for Name: overtime_types; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.overtime_types (id, name, rate, active, created_at, updated_at, nd_from, nd_to, nd_rating) FROM stdin;
\.


--
-- Data for Name: password_resets; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.password_resets (email, token, created_at) FROM stdin;
\.


--
-- Data for Name: payroll_cutoffs; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.payroll_cutoffs (id, name, payroll_interval_id, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: payroll_deductions; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.payroll_deductions (id, payroll_period_id, employment_id, deduction_id, employee_id, amount, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: payroll_incomes; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.payroll_incomes (id, payroll_period_id, employment_id, income_id, employee_id, amount, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: payroll_intervals; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.payroll_intervals (id, name, active, created_at, updated_at, day_interval, month_frequency, year_frequency) FROM stdin;
\.


--
-- Data for Name: payroll_item_schedule_details; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.payroll_item_schedule_details (id, payroll_item_schedule_header_id, income_id, deduction_id, active, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: payroll_item_schedule_headers; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.payroll_item_schedule_headers (id, payroll_interval_type_id, payroll_period_type_id, employment_type_id, sss, pagibig, philhealth, gsis, tax, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: payroll_periods; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.payroll_periods (id, payroll_interval_id, payroll_cutoff_id, attendance_start_date, attendance_end_date, payroll_start_date, payroll_end_date, release_date, posted, active, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: payroll_summaries; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.payroll_summaries (id, payroll_period_id, employee_id, salary, gsis, sss, pagibig, philhealth, tax, late_amount, ut_amount, absent_amount, holiday_amount, ot_amount, nd_amount, total_income, total_deduction, gross_amount, net_pay, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: philhealths; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.philhealths (id, year, multiplier, income_floor, income_ceiling, fix_rate, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: plantillas; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.plantillas (id, code, position_id, salary_step_id, salary_grade_id, employee_id, active, created_at, updated_at, department_id, eligibility, experience, training, education, unit, publication_from, publication_to, status) FROM stdin;
\.


--
-- Data for Name: positions; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.positions (id, name, active, created_at, updated_at, description) FROM stdin;
\.


--
-- Data for Name: promotion_natures; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.promotion_natures (id, name, active, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: religions; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.religions (id, name, active, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: salary_adjustments; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.salary_adjustments (id, employee_id, old_salary, old_salary_grade_id, old_salary_step_id, new_salary, new_salary_grade_id, new_salary_step_id, salary_schedule_id, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: salary_grades; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.salary_grades (id, name, active, created_at, updated_at) FROM stdin;
1	Salary Grade 1	t	\N	\N
2	Salary Grade 2	t	\N	\N
3	Salary Grade 3	t	\N	\N
4	Salary Grade 4	t	\N	\N
5	Salary Grade 5	t	\N	\N
6	Salary Grade 6	t	\N	\N
7	Salary Grade 7	t	\N	\N
8	Salary Grade 8	t	\N	\N
9	Salary Grade 9	t	\N	\N
10	Salary Grade 10	t	\N	\N
11	Salary Grade 11	t	\N	\N
12	Salary Grade 12	t	\N	\N
13	Salary Grade 13	t	\N	\N
14	Salary Grade 14	t	\N	\N
15	Salary Grade 15	t	\N	\N
16	Salary Grade 16	t	\N	\N
17	Salary Grade 17	t	\N	\N
18	Salary Grade 18	t	\N	\N
19	Salary Grade 19	t	\N	\N
20	Salary Grade 20	t	\N	\N
21	Salary Grade 21	t	\N	\N
22	Salary Grade 22	t	\N	\N
23	Salary Grade 23	t	\N	\N
24	Salary Grade 24	t	\N	\N
25	Salary Grade 25	t	\N	\N
26	Salary Grade 26	t	\N	\N
27	Salary Grade 27	t	\N	\N
28	Salary Grade 28	t	\N	\N
29	Salary Grade 29	t	\N	\N
30	Salary Grade 30	t	\N	\N
31	Salary Grade 31	t	\N	\N
32	Salary Grade 32	t	\N	\N
33	Salary Grade 33	t	\N	\N
\.


--
-- Data for Name: salary_schedules; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.salary_schedules (id, name, enabling_law, effectivity, active, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: salary_schedules_details; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.salary_schedules_details (id, salary_schedule_id, salary_grade_id, salary_step_id, amount, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: salary_steps; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.salary_steps (id, name, active, created_at, updated_at) FROM stdin;
1	Step 1	t	\N	\N
2	Step 2	t	\N	\N
3	Step 3	t	\N	\N
4	Step 4	t	\N	\N
5	Step 5	t	\N	\N
6	Step 6	t	\N	\N
7	Step 7	t	\N	\N
8	Step 8	t	\N	\N
\.


--
-- Data for Name: schedule_days; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.schedule_days (id, name, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: sections; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.sections (id, code, name, active, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: semester_ratings; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.semester_ratings (id, name, active, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: service_records; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.service_records (service_record_id, employee_id, start_date, end_date, designation, employment_type, annual_salary, place_of_assignment, leave_without_pay, separation_date, cause, branch, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: service_records_temps; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.service_records_temps (service_record_id, request_id, employee_id, start_date, end_date, designation, employment_type, annual_salary, place_of_assignment, leave_without_pay, separation_date, cause, branch, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: shift_schedules_details; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.shift_schedules_details (id, shift_schedule_id, shift_date, am_in, am_out, break_in, break_out, pm_in, pm_out, grace_period, flexi_hours, work_hours, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: shift_schedules_headers; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.shift_schedules_headers (id, name, date_from, date_to, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: sss; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.sss (id, min_income, max_income, "ER", "EE", "MPF_ER", "MPF_EE", "WISP", created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: step_increments; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.step_increments (id, employee_id, effectivity_date, current_salary_grade_id, current_salary_step_id, current_salary, new_salary_grade_id, new_salary_step_id, new_salary, new_tax_amount, new_gsis_amount, new_sss_amount, new_pagibig_amount, new_philhealth_amount, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: tax_tables; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.tax_tables (id, percentage, min_amount, max_amount, base_tax, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: time_data; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.time_data (id, employee_id, payroll_period_id, date, am_in, am_out, break_in, break_out, pm_in, pm_out, work_hours, late, undertime, absent, leave, is_ob, ob_id, is_holiday, holiday_id, holiday_pay, is_ot, ot_id, ot_pay, nd_pay, remarks, created_at, updated_at, is_shifting, work_schedule_id, ob_hours, ot_hours, holiday_type_id, overtime_type_id) FROM stdin;
\.


--
-- Data for Name: time_keeping_setups; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.time_keeping_setups (id, employment_type_id, work_days, work_hours, created_at, updated_at, with_holiday_pay) FROM stdin;
\.


--
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.users (id, name, email, email_verified_at, password, remember_token, created_at, updated_at, active, locked, locked_date, employee_no, professor_no, photo, with_hrm_access, with_hrt_access, with_hrp_access, with_cpm_access, is_admin, has_change_password, is_applicant) FROM stdin;
1	Administrator	msu.hris@gmail.com	2022-06-17 02:59:12	$2y$10$ZqsbtWdx2WlBZNwq0oaDd.O6/US24GoAX59GrCw4mKRKqqb8xUUOO	\N	2022-06-17 02:58:39	2022-06-17 03:55:23	\N	f	\N	\N	\N		t	t	t	t	t	t	f
\.


--
-- Data for Name: work_cancellations; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.work_cancellations (id, date_from, date_to, with_pay, reason, created_at, updated_at, time_from, time_to) FROM stdin;
\.


--
-- Name: access_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.access_id_seq', 68, true);


--
-- Name: adjectival_ratings_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.adjectival_ratings_id_seq', 1, false);


--
-- Name: applicant_details_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.applicant_details_id_seq', 1, false);


--
-- Name: applicant_headers_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.applicant_headers_id_seq', 1, false);


--
-- Name: application_status_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.application_status_id_seq', 1, false);


--
-- Name: audit_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.audit_id_seq', 2, true);


--
-- Name: biometric_logs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.biometric_logs_id_seq', 1, false);


--
-- Name: blood_types_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.blood_types_id_seq', 1, false);


--
-- Name: branches_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.branches_id_seq', 1, false);


--
-- Name: citizenships_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.citizenships_id_seq', 1, false);


--
-- Name: civil_status_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.civil_status_id_seq', 1, false);


--
-- Name: companies_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.companies_id_seq', 1, false);


--
-- Name: deductions_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.deductions_id_seq', 1, false);


--
-- Name: departments_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.departments_id_seq', 1, false);


--
-- Name: divisions_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.divisions_id_seq', 1, false);


--
-- Name: eligibilities_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.eligibilities_id_seq', 1, false);


--
-- Name: employee_children_children_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.employee_children_children_id_seq', 1, false);


--
-- Name: employee_children_temps_children_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.employee_children_temps_children_id_seq', 1, false);


--
-- Name: employee_dependents_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.employee_dependents_id_seq', 1, false);


--
-- Name: employee_educations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.employee_educations_id_seq', 1, false);


--
-- Name: employee_educations_temps_education_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.employee_educations_temps_education_id_seq', 1, false);


--
-- Name: employee_employment_records_employment_record_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.employee_employment_records_employment_record_id_seq', 1, false);


--
-- Name: employee_employment_records_temps_employment_record_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.employee_employment_records_temps_employment_record_id_seq', 1, false);


--
-- Name: employee_examinations_examination_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.employee_examinations_examination_id_seq', 1, false);


--
-- Name: employee_examinations_temps_examination_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.employee_examinations_temps_examination_id_seq', 1, false);


--
-- Name: employee_memberships_membership_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.employee_memberships_membership_id_seq', 1, false);


--
-- Name: employee_memberships_temps_membership_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.employee_memberships_temps_membership_id_seq', 1, false);


--
-- Name: employee_offboardings_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.employee_offboardings_id_seq', 1, false);


--
-- Name: employee_organizations_organization_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.employee_organizations_organization_id_seq', 1, false);


--
-- Name: employee_organizations_temps_organization_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.employee_organizations_temps_organization_id_seq', 1, false);


--
-- Name: employee_promotions_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.employee_promotions_id_seq', 1, false);


--
-- Name: employee_recognations_recognation_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.employee_recognations_recognation_id_seq', 1, false);


--
-- Name: employee_recognations_temps_recognation_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.employee_recognations_temps_recognation_id_seq', 1, false);


--
-- Name: employee_references_reference_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.employee_references_reference_id_seq', 1, false);


--
-- Name: employee_references_temps_reference_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.employee_references_temps_reference_id_seq', 1, false);


--
-- Name: employee_requests_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.employee_requests_id_seq', 1, false);


--
-- Name: employee_skills_skill_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.employee_skills_skill_id_seq', 1, false);


--
-- Name: employee_skills_temps_skill_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.employee_skills_temps_skill_id_seq', 1, false);


--
-- Name: employee_trainings_temps_training_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.employee_trainings_temps_training_id_seq', 1, false);


--
-- Name: employee_trainings_training_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.employee_trainings_training_id_seq', 1, false);


--
-- Name: employees_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.employees_id_seq', 1, false);


--
-- Name: employees_temps_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.employees_temps_id_seq', 1, false);


--
-- Name: employment_types_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.employment_types_id_seq', 1, false);


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.failed_jobs_id_seq', 1, false);


--
-- Name: fix_schdules_details_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.fix_schdules_details_id_seq', 1, false);


--
-- Name: fix_schedules_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.fix_schedules_id_seq', 1, false);


--
-- Name: genders_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.genders_id_seq', 1, false);


--
-- Name: gsis_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.gsis_id_seq', 1, false);


--
-- Name: holiday_tagging_details_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.holiday_tagging_details_id_seq', 1, false);


--
-- Name: holiday_tagging_headers_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.holiday_tagging_headers_id_seq', 1, false);


--
-- Name: incomes_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.incomes_id_seq', 1, false);


--
-- Name: ipcr_details_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.ipcr_details_id_seq', 1, false);


--
-- Name: ipcr_headers_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.ipcr_headers_id_seq', 1, false);


--
-- Name: ipcr_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.ipcr_id_seq', 1, false);


--
-- Name: learnings_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.learnings_id_seq', 1, false);


--
-- Name: leave_credits_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.leave_credits_id_seq', 1, false);


--
-- Name: leave_details_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.leave_details_id_seq', 1, false);


--
-- Name: leave_headers_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.leave_headers_id_seq', 1, false);


--
-- Name: loan_applications_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.loan_applications_id_seq', 1, false);


--
-- Name: menus_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.menus_id_seq', 1, false);


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.migrations_id_seq', 1, false);


--
-- Name: name_prefixes_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.name_prefixes_id_seq', 1, false);


--
-- Name: name_suffixes_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.name_suffixes_id_seq', 1, false);


--
-- Name: non_plantillas_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.non_plantillas_id_seq', 1, false);


--
-- Name: offboarding_natures_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.offboarding_natures_id_seq', 1, false);


--
-- Name: official_business_applications_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.official_business_applications_id_seq', 1, false);


--
-- Name: overtime_applications_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.overtime_applications_id_seq', 1, false);


--
-- Name: overtime_types_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.overtime_types_id_seq', 1, false);


--
-- Name: payroll_cutoffs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.payroll_cutoffs_id_seq', 1, false);


--
-- Name: payroll_deductions_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.payroll_deductions_id_seq', 1, false);


--
-- Name: payroll_incomes_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.payroll_incomes_id_seq', 1, false);


--
-- Name: payroll_intervals_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.payroll_intervals_id_seq', 1, false);


--
-- Name: payroll_item_schedule_details_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.payroll_item_schedule_details_id_seq', 1, false);


--
-- Name: payroll_item_schedule_headers_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.payroll_item_schedule_headers_id_seq', 1, false);


--
-- Name: payroll_periods_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.payroll_periods_id_seq', 1, false);


--
-- Name: payroll_summaries_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.payroll_summaries_id_seq', 1, false);


--
-- Name: philhealths_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.philhealths_id_seq', 1, false);


--
-- Name: plantillas_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.plantillas_id_seq', 1, false);


--
-- Name: positions_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.positions_id_seq', 1, false);


--
-- Name: promotion_natures_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.promotion_natures_id_seq', 1, false);


--
-- Name: religions_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.religions_id_seq', 1, false);


--
-- Name: salary_adjustments_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.salary_adjustments_id_seq', 1, false);


--
-- Name: salary_grades_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.salary_grades_id_seq', 1, false);


--
-- Name: salary_schedules_details_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.salary_schedules_details_id_seq', 1, false);


--
-- Name: salary_schedules_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.salary_schedules_id_seq', 1, false);


--
-- Name: salary_steps_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.salary_steps_id_seq', 1, false);


--
-- Name: schedule_days_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.schedule_days_id_seq', 1, false);


--
-- Name: sections_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.sections_id_seq', 1, false);


--
-- Name: semester_ratings_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.semester_ratings_id_seq', 1, false);


--
-- Name: service_records_service_record_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.service_records_service_record_id_seq', 1, false);


--
-- Name: service_records_temps_service_record_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.service_records_temps_service_record_id_seq', 1, false);


--
-- Name: shift_schedules_details_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.shift_schedules_details_id_seq', 1, false);


--
-- Name: shift_schedules_headers_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.shift_schedules_headers_id_seq', 1, false);


--
-- Name: sss_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.sss_id_seq', 1, false);


--
-- Name: step_increments_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.step_increments_id_seq', 1, false);


--
-- Name: tax_tables_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.tax_tables_id_seq', 1, false);


--
-- Name: time_data_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.time_data_id_seq', 1, false);


--
-- Name: time_keeping_setups_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.time_keeping_setups_id_seq', 1, false);


--
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.users_id_seq', 1, true);


--
-- Name: work_cancellations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.work_cancellations_id_seq', 1, false);


--
-- Name: access access_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.access
    ADD CONSTRAINT access_pkey PRIMARY KEY (id);


--
-- Name: adjectival_ratings adjectival_ratings_adjectival_rating_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.adjectival_ratings
    ADD CONSTRAINT adjectival_ratings_adjectival_rating_unique UNIQUE (adjectival_rating);


--
-- Name: adjectival_ratings adjectival_ratings_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.adjectival_ratings
    ADD CONSTRAINT adjectival_ratings_pkey PRIMARY KEY (id);


--
-- Name: applicant_details applicant_details_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.applicant_details
    ADD CONSTRAINT applicant_details_pkey PRIMARY KEY (id);


--
-- Name: applicant_headers applicant_headers_applicant_no_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.applicant_headers
    ADD CONSTRAINT applicant_headers_applicant_no_unique UNIQUE (applicant_no);


--
-- Name: applicant_headers applicant_headers_email_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.applicant_headers
    ADD CONSTRAINT applicant_headers_email_unique UNIQUE (email);


--
-- Name: applicant_headers applicant_headers_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.applicant_headers
    ADD CONSTRAINT applicant_headers_pkey PRIMARY KEY (id);


--
-- Name: application_status application_status_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.application_status
    ADD CONSTRAINT application_status_pkey PRIMARY KEY (id);


--
-- Name: audits audit_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.audits
    ADD CONSTRAINT audit_pkey PRIMARY KEY (id);


--
-- Name: biometric_logs biometric_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.biometric_logs
    ADD CONSTRAINT biometric_logs_pkey PRIMARY KEY (id);


--
-- Name: blood_types blood_types_name_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.blood_types
    ADD CONSTRAINT blood_types_name_unique UNIQUE (name);


--
-- Name: blood_types blood_types_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.blood_types
    ADD CONSTRAINT blood_types_pkey PRIMARY KEY (id);


--
-- Name: branches branches_name_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.branches
    ADD CONSTRAINT branches_name_unique UNIQUE (name);


--
-- Name: branches branches_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.branches
    ADD CONSTRAINT branches_pkey PRIMARY KEY (id);


--
-- Name: citizenships citizenships_name_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.citizenships
    ADD CONSTRAINT citizenships_name_unique UNIQUE (name);


--
-- Name: citizenships citizenships_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.citizenships
    ADD CONSTRAINT citizenships_pkey PRIMARY KEY (id);


--
-- Name: civil_status civil_status_name_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.civil_status
    ADD CONSTRAINT civil_status_name_unique UNIQUE (name);


--
-- Name: civil_status civil_status_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.civil_status
    ADD CONSTRAINT civil_status_pkey PRIMARY KEY (id);


--
-- Name: companies companies_name_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.companies
    ADD CONSTRAINT companies_name_unique UNIQUE (name);


--
-- Name: companies companies_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.companies
    ADD CONSTRAINT companies_pkey PRIMARY KEY (id);


--
-- Name: deductions deductions_name_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.deductions
    ADD CONSTRAINT deductions_name_unique UNIQUE (name);


--
-- Name: deductions deductions_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.deductions
    ADD CONSTRAINT deductions_pkey PRIMARY KEY (id);


--
-- Name: departments departments_name_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.departments
    ADD CONSTRAINT departments_name_unique UNIQUE (name);


--
-- Name: departments departments_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.departments
    ADD CONSTRAINT departments_pkey PRIMARY KEY (id);


--
-- Name: divisions divisions_name_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.divisions
    ADD CONSTRAINT divisions_name_unique UNIQUE (name);


--
-- Name: divisions divisions_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.divisions
    ADD CONSTRAINT divisions_pkey PRIMARY KEY (id);


--
-- Name: eligibilities eligibilities_name_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.eligibilities
    ADD CONSTRAINT eligibilities_name_unique UNIQUE (name);


--
-- Name: eligibilities eligibilities_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.eligibilities
    ADD CONSTRAINT eligibilities_pkey PRIMARY KEY (id);


--
-- Name: employee_children employee_children_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.employee_children
    ADD CONSTRAINT employee_children_pkey PRIMARY KEY (children_id);


--
-- Name: employee_children_temps employee_children_temps_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.employee_children_temps
    ADD CONSTRAINT employee_children_temps_pkey PRIMARY KEY (children_id);


--
-- Name: employee_dependents employee_dependents_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.employee_dependents
    ADD CONSTRAINT employee_dependents_pkey PRIMARY KEY (id);


--
-- Name: employee_educations employee_educations_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.employee_educations
    ADD CONSTRAINT employee_educations_pkey PRIMARY KEY (education_id);


--
-- Name: employee_educations_temps employee_educations_temps_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.employee_educations_temps
    ADD CONSTRAINT employee_educations_temps_pkey PRIMARY KEY (education_id);


--
-- Name: employee_employment_records employee_employment_records_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.employee_employment_records
    ADD CONSTRAINT employee_employment_records_pkey PRIMARY KEY (employment_record_id);


--
-- Name: employee_employment_records_temps employee_employment_records_temps_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.employee_employment_records_temps
    ADD CONSTRAINT employee_employment_records_temps_pkey PRIMARY KEY (employment_record_id);


--
-- Name: employee_examinations employee_examinations_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.employee_examinations
    ADD CONSTRAINT employee_examinations_pkey PRIMARY KEY (examination_id);


--
-- Name: employee_examinations_temps employee_examinations_temps_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.employee_examinations_temps
    ADD CONSTRAINT employee_examinations_temps_pkey PRIMARY KEY (examination_id);


--
-- Name: employee_memberships employee_memberships_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.employee_memberships
    ADD CONSTRAINT employee_memberships_pkey PRIMARY KEY (membership_id);


--
-- Name: employee_memberships_temps employee_memberships_temps_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.employee_memberships_temps
    ADD CONSTRAINT employee_memberships_temps_pkey PRIMARY KEY (membership_id);


--
-- Name: employee_offboardings employee_offboardings_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.employee_offboardings
    ADD CONSTRAINT employee_offboardings_pkey PRIMARY KEY (id);


--
-- Name: employee_organizations employee_organizations_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.employee_organizations
    ADD CONSTRAINT employee_organizations_pkey PRIMARY KEY (organization_id);


--
-- Name: employee_organizations_temps employee_organizations_temps_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.employee_organizations_temps
    ADD CONSTRAINT employee_organizations_temps_pkey PRIMARY KEY (organization_id);


--
-- Name: employee_promotions employee_promotions_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.employee_promotions
    ADD CONSTRAINT employee_promotions_pkey PRIMARY KEY (id);


--
-- Name: employee_recognations employee_recognations_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.employee_recognations
    ADD CONSTRAINT employee_recognations_pkey PRIMARY KEY (recognation_id);


--
-- Name: employee_recognations_temps employee_recognations_temps_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.employee_recognations_temps
    ADD CONSTRAINT employee_recognations_temps_pkey PRIMARY KEY (recognation_id);


--
-- Name: employee_references employee_references_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.employee_references
    ADD CONSTRAINT employee_references_pkey PRIMARY KEY (reference_id);


--
-- Name: employee_references_temps employee_references_temps_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.employee_references_temps
    ADD CONSTRAINT employee_references_temps_pkey PRIMARY KEY (reference_id);


--
-- Name: employee_requests employee_requests_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.employee_requests
    ADD CONSTRAINT employee_requests_pkey PRIMARY KEY (id);


--
-- Name: employee_skills employee_skills_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.employee_skills
    ADD CONSTRAINT employee_skills_pkey PRIMARY KEY (skill_id);


--
-- Name: employee_skills_temps employee_skills_temps_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.employee_skills_temps
    ADD CONSTRAINT employee_skills_temps_pkey PRIMARY KEY (skill_id);


--
-- Name: employee_trainings employee_trainings_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.employee_trainings
    ADD CONSTRAINT employee_trainings_pkey PRIMARY KEY (training_id);


--
-- Name: employee_trainings_temps employee_trainings_temps_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.employee_trainings_temps
    ADD CONSTRAINT employee_trainings_temps_pkey PRIMARY KEY (training_id);


--
-- Name: employees employees_access_no_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.employees
    ADD CONSTRAINT employees_access_no_unique UNIQUE (access_no);


--
-- Name: employees employees_email_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.employees
    ADD CONSTRAINT employees_email_unique UNIQUE (email);


--
-- Name: employees employees_employee_no_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.employees
    ADD CONSTRAINT employees_employee_no_unique UNIQUE (employee_no);


--
-- Name: employees employees_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.employees
    ADD CONSTRAINT employees_pkey PRIMARY KEY (id);


--
-- Name: employees_temps employees_temps_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.employees_temps
    ADD CONSTRAINT employees_temps_pkey PRIMARY KEY (id);


--
-- Name: employment_types employment_types_name_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.employment_types
    ADD CONSTRAINT employment_types_name_unique UNIQUE (name);


--
-- Name: employment_types employment_types_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.employment_types
    ADD CONSTRAINT employment_types_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- Name: fix_schedules_details fix_schdules_details_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.fix_schedules_details
    ADD CONSTRAINT fix_schdules_details_pkey PRIMARY KEY (id);


--
-- Name: fix_schedules fix_schedules_name_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.fix_schedules
    ADD CONSTRAINT fix_schedules_name_unique UNIQUE (name);


--
-- Name: fix_schedules fix_schedules_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.fix_schedules
    ADD CONSTRAINT fix_schedules_pkey PRIMARY KEY (id);


--
-- Name: genders genders_name_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.genders
    ADD CONSTRAINT genders_name_unique UNIQUE (name);


--
-- Name: genders genders_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.genders
    ADD CONSTRAINT genders_pkey PRIMARY KEY (id);


--
-- Name: gsis gsis_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.gsis
    ADD CONSTRAINT gsis_pkey PRIMARY KEY (id);


--
-- Name: gsis gsis_year_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.gsis
    ADD CONSTRAINT gsis_year_unique UNIQUE (year);


--
-- Name: holiday_tagging_details holiday_tagging_details_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.holiday_tagging_details
    ADD CONSTRAINT holiday_tagging_details_pkey PRIMARY KEY (id);


--
-- Name: holiday_tagging_headers holiday_tagging_headers_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.holiday_tagging_headers
    ADD CONSTRAINT holiday_tagging_headers_pkey PRIMARY KEY (id);


--
-- Name: holiday_types holiday_types_name_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.holiday_types
    ADD CONSTRAINT holiday_types_name_unique UNIQUE (name);


--
-- Name: holiday_types holiday_types_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.holiday_types
    ADD CONSTRAINT holiday_types_pkey PRIMARY KEY (id);


--
-- Name: holidays holidays_name_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.holidays
    ADD CONSTRAINT holidays_name_unique UNIQUE (name);


--
-- Name: holidays holidays_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.holidays
    ADD CONSTRAINT holidays_pkey PRIMARY KEY (id);


--
-- Name: incomes incomes_name_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.incomes
    ADD CONSTRAINT incomes_name_unique UNIQUE (name);


--
-- Name: incomes incomes_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.incomes
    ADD CONSTRAINT incomes_pkey PRIMARY KEY (id);


--
-- Name: ipcr_details ipcr_details_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ipcr_details
    ADD CONSTRAINT ipcr_details_pkey PRIMARY KEY (id);


--
-- Name: ipcr_headers ipcr_headers_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ipcr_headers
    ADD CONSTRAINT ipcr_headers_pkey PRIMARY KEY (id);


--
-- Name: ipcr ipcr_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ipcr
    ADD CONSTRAINT ipcr_pkey PRIMARY KEY (id);


--
-- Name: learnings learnings_name_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.learnings
    ADD CONSTRAINT learnings_name_unique UNIQUE (name);


--
-- Name: learnings learnings_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.learnings
    ADD CONSTRAINT learnings_pkey PRIMARY KEY (id);


--
-- Name: leave_credits leave_credits_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.leave_credits
    ADD CONSTRAINT leave_credits_pkey PRIMARY KEY (id);


--
-- Name: leave_details leave_details_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.leave_details
    ADD CONSTRAINT leave_details_pkey PRIMARY KEY (id);


--
-- Name: leave_headers leave_headers_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.leave_headers
    ADD CONSTRAINT leave_headers_pkey PRIMARY KEY (id);


--
-- Name: leave_types leave_types_name_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.leave_types
    ADD CONSTRAINT leave_types_name_unique UNIQUE (name);


--
-- Name: leave_types leave_types_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.leave_types
    ADD CONSTRAINT leave_types_pkey PRIMARY KEY (id);


--
-- Name: loan_applications loan_applications_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.loan_applications
    ADD CONSTRAINT loan_applications_pkey PRIMARY KEY (id);


--
-- Name: menus menus_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.menus
    ADD CONSTRAINT menus_pkey PRIMARY KEY (id);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: months months_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.months
    ADD CONSTRAINT months_pkey PRIMARY KEY (id);


--
-- Name: name_prefixes name_prefixes_name_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.name_prefixes
    ADD CONSTRAINT name_prefixes_name_unique UNIQUE (name);


--
-- Name: name_prefixes name_prefixes_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.name_prefixes
    ADD CONSTRAINT name_prefixes_pkey PRIMARY KEY (id);


--
-- Name: name_suffixes name_suffixes_name_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.name_suffixes
    ADD CONSTRAINT name_suffixes_name_unique UNIQUE (name);


--
-- Name: name_suffixes name_suffixes_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.name_suffixes
    ADD CONSTRAINT name_suffixes_pkey PRIMARY KEY (id);


--
-- Name: non_plantillas non_plantillas_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.non_plantillas
    ADD CONSTRAINT non_plantillas_pkey PRIMARY KEY (id);


--
-- Name: notifications notifications_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.notifications
    ADD CONSTRAINT notifications_pkey PRIMARY KEY (id);


--
-- Name: offboarding_natures offboarding_natures_name_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.offboarding_natures
    ADD CONSTRAINT offboarding_natures_name_unique UNIQUE (name);


--
-- Name: offboarding_natures offboarding_natures_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.offboarding_natures
    ADD CONSTRAINT offboarding_natures_pkey PRIMARY KEY (id);


--
-- Name: official_business_applications official_business_applications_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.official_business_applications
    ADD CONSTRAINT official_business_applications_pkey PRIMARY KEY (id);


--
-- Name: official_business_types official_business_types_name_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.official_business_types
    ADD CONSTRAINT official_business_types_name_unique UNIQUE (name);


--
-- Name: official_business_types official_business_types_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.official_business_types
    ADD CONSTRAINT official_business_types_pkey PRIMARY KEY (id);


--
-- Name: overtime_applications overtime_applications_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.overtime_applications
    ADD CONSTRAINT overtime_applications_pkey PRIMARY KEY (id);


--
-- Name: overtime_types overtime_types_name_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.overtime_types
    ADD CONSTRAINT overtime_types_name_unique UNIQUE (name);


--
-- Name: overtime_types overtime_types_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.overtime_types
    ADD CONSTRAINT overtime_types_pkey PRIMARY KEY (id);


--
-- Name: payroll_cutoffs payroll_cutoffs_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payroll_cutoffs
    ADD CONSTRAINT payroll_cutoffs_pkey PRIMARY KEY (id);


--
-- Name: payroll_deductions payroll_deductions_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payroll_deductions
    ADD CONSTRAINT payroll_deductions_pkey PRIMARY KEY (id);


--
-- Name: payroll_incomes payroll_incomes_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payroll_incomes
    ADD CONSTRAINT payroll_incomes_pkey PRIMARY KEY (id);


--
-- Name: payroll_intervals payroll_intervals_name_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payroll_intervals
    ADD CONSTRAINT payroll_intervals_name_unique UNIQUE (name);


--
-- Name: payroll_intervals payroll_intervals_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payroll_intervals
    ADD CONSTRAINT payroll_intervals_pkey PRIMARY KEY (id);


--
-- Name: payroll_item_schedule_details payroll_item_schedule_details_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payroll_item_schedule_details
    ADD CONSTRAINT payroll_item_schedule_details_pkey PRIMARY KEY (id);


--
-- Name: payroll_item_schedule_headers payroll_item_schedule_headers_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payroll_item_schedule_headers
    ADD CONSTRAINT payroll_item_schedule_headers_pkey PRIMARY KEY (id);


--
-- Name: payroll_periods payroll_periods_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payroll_periods
    ADD CONSTRAINT payroll_periods_pkey PRIMARY KEY (id);


--
-- Name: payroll_summaries payroll_summaries_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payroll_summaries
    ADD CONSTRAINT payroll_summaries_pkey PRIMARY KEY (id);


--
-- Name: philhealths philhealths_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.philhealths
    ADD CONSTRAINT philhealths_pkey PRIMARY KEY (id);


--
-- Name: philhealths philhealths_year_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.philhealths
    ADD CONSTRAINT philhealths_year_unique UNIQUE (year);


--
-- Name: plantillas plantillas_code_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.plantillas
    ADD CONSTRAINT plantillas_code_unique UNIQUE (code);


--
-- Name: plantillas plantillas_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.plantillas
    ADD CONSTRAINT plantillas_pkey PRIMARY KEY (id);


--
-- Name: positions positions_name_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.positions
    ADD CONSTRAINT positions_name_unique UNIQUE (name);


--
-- Name: positions positions_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.positions
    ADD CONSTRAINT positions_pkey PRIMARY KEY (id);


--
-- Name: promotion_natures promotion_natures_name_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.promotion_natures
    ADD CONSTRAINT promotion_natures_name_unique UNIQUE (name);


--
-- Name: promotion_natures promotion_natures_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.promotion_natures
    ADD CONSTRAINT promotion_natures_pkey PRIMARY KEY (id);


--
-- Name: religions religions_name_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.religions
    ADD CONSTRAINT religions_name_unique UNIQUE (name);


--
-- Name: religions religions_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.religions
    ADD CONSTRAINT religions_pkey PRIMARY KEY (id);


--
-- Name: salary_adjustments salary_adjustments_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.salary_adjustments
    ADD CONSTRAINT salary_adjustments_pkey PRIMARY KEY (id);


--
-- Name: salary_grades salary_grades_name_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.salary_grades
    ADD CONSTRAINT salary_grades_name_unique UNIQUE (name);


--
-- Name: salary_grades salary_grades_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.salary_grades
    ADD CONSTRAINT salary_grades_pkey PRIMARY KEY (id);


--
-- Name: salary_schedules_details salary_schedules_details_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.salary_schedules_details
    ADD CONSTRAINT salary_schedules_details_pkey PRIMARY KEY (id);


--
-- Name: salary_schedules salary_schedules_name_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.salary_schedules
    ADD CONSTRAINT salary_schedules_name_unique UNIQUE (name);


--
-- Name: salary_schedules salary_schedules_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.salary_schedules
    ADD CONSTRAINT salary_schedules_pkey PRIMARY KEY (id);


--
-- Name: salary_steps salary_steps_name_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.salary_steps
    ADD CONSTRAINT salary_steps_name_unique UNIQUE (name);


--
-- Name: salary_steps salary_steps_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.salary_steps
    ADD CONSTRAINT salary_steps_pkey PRIMARY KEY (id);


--
-- Name: schedule_days schedule_days_name_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.schedule_days
    ADD CONSTRAINT schedule_days_name_unique UNIQUE (name);


--
-- Name: schedule_days schedule_days_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.schedule_days
    ADD CONSTRAINT schedule_days_pkey PRIMARY KEY (id);


--
-- Name: sections sections_name_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sections
    ADD CONSTRAINT sections_name_unique UNIQUE (name);


--
-- Name: sections sections_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sections
    ADD CONSTRAINT sections_pkey PRIMARY KEY (id);


--
-- Name: semester_ratings semester_ratings_name_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.semester_ratings
    ADD CONSTRAINT semester_ratings_name_unique UNIQUE (name);


--
-- Name: semester_ratings semester_ratings_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.semester_ratings
    ADD CONSTRAINT semester_ratings_pkey PRIMARY KEY (id);


--
-- Name: service_records service_records_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.service_records
    ADD CONSTRAINT service_records_pkey PRIMARY KEY (service_record_id);


--
-- Name: service_records_temps service_records_temps_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.service_records_temps
    ADD CONSTRAINT service_records_temps_pkey PRIMARY KEY (service_record_id);


--
-- Name: shift_schedules_details shift_schedules_details_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.shift_schedules_details
    ADD CONSTRAINT shift_schedules_details_pkey PRIMARY KEY (id);


--
-- Name: shift_schedules_headers shift_schedules_headers_name_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.shift_schedules_headers
    ADD CONSTRAINT shift_schedules_headers_name_unique UNIQUE (name);


--
-- Name: shift_schedules_headers shift_schedules_headers_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.shift_schedules_headers
    ADD CONSTRAINT shift_schedules_headers_pkey PRIMARY KEY (id);


--
-- Name: sss sss_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sss
    ADD CONSTRAINT sss_pkey PRIMARY KEY (id);


--
-- Name: step_increments step_increments_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.step_increments
    ADD CONSTRAINT step_increments_pkey PRIMARY KEY (id);


--
-- Name: tax_tables tax_tables_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tax_tables
    ADD CONSTRAINT tax_tables_pkey PRIMARY KEY (id);


--
-- Name: time_data time_data_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.time_data
    ADD CONSTRAINT time_data_pkey PRIMARY KEY (id);


--
-- Name: time_keeping_setups time_keeping_setups_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.time_keeping_setups
    ADD CONSTRAINT time_keeping_setups_pkey PRIMARY KEY (id);


--
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: work_cancellations work_cancellations_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.work_cancellations
    ADD CONSTRAINT work_cancellations_pkey PRIMARY KEY (id);


--
-- Name: notifications_notifiable_type_notifiable_id_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX notifications_notifiable_type_notifiable_id_index ON public.notifications USING btree (notifiable_type, notifiable_id);


--
-- Name: password_resets_email_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX password_resets_email_index ON public.password_resets USING btree (email);


--
-- Name: payroll_cutoffs payroll_cutoffs_payroll_interval_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payroll_cutoffs
    ADD CONSTRAINT payroll_cutoffs_payroll_interval_id_foreign FOREIGN KEY (payroll_interval_id) REFERENCES public.payroll_intervals(id);


--
-- PostgreSQL database dump complete
--

