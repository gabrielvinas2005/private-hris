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

SELECT pg_catalog.set_config ('search_path', '', false);

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

CREATE SEQUENCE public.access_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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
    numerical_rating1 numeric(8, 2) NOT NULL,
    numerical_rating2 numeric(8, 2) NOT NULL,
    adjectival_rating character varying(255) NOT NULL,
    active boolean DEFAULT true,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);

ALTER TABLE public.adjectival_ratings OWNER TO postgres;

--
-- Name: adjectival_ratings_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.adjectival_ratings_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.applicant_details_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.applicant_headers_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.application_status_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.audit_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.biometric_logs_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.blood_types_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.branches_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.citizenships_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.civil_status_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.companies_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.deductions_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.departments_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.divisions_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.eligibilities_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.employee_children_children_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.employee_children_temps_children_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.employee_dependents_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.employee_educations_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.employee_educations_temps_education_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.employee_employment_records_employment_record_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.employee_employment_records_temps_employment_record_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.employee_examinations_examination_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.employee_examinations_temps_examination_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.employee_memberships_membership_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.employee_memberships_temps_membership_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.employee_offboardings_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.employee_organizations_organization_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.employee_organizations_temps_organization_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.employee_promotions_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.employee_recognations_recognation_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.employee_recognations_temps_recognation_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.employee_references_reference_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.employee_references_temps_reference_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.employee_requests_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.employee_skills_skill_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.employee_skills_temps_skill_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.employee_trainings_temps_training_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

ALTER TABLE public.employee_trainings_temps_training_id_seq OWNER TO postgres;

--
-- Name: employee_trainings_temps_training_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.employee_trainings_temps_training_id_seq OWNED BY public.employee_trainings_temps.training_id;

--
-- Name: employee_trainings_training_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.employee_trainings_training_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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
    middle_name character varying(255),
    last_name character varying(255) NOT NULL,
    name_suffix_id integer DEFAULT 0 NOT NULL,
    birth_place character varying(255),
    birthdate date,
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

CREATE SEQUENCE public.employees_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.employees_temps_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.employment_types_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.failed_jobs_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.fix_schdules_details_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.fix_schedules_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.genders_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.gsis_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

ALTER TABLE public.holiday_tagging_details
ALTER COLUMN id
ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.holiday_tagging_details_id_seq START
    WITH
        1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1
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

ALTER TABLE public.holiday_tagging_headers
ALTER COLUMN id
ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.holiday_tagging_headers_id_seq START
    WITH
        1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1
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

CREATE SEQUENCE public.incomes_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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
    numerical_rating numeric(8, 2) NOT NULL,
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
    rating numeric(8, 2) NOT NULL,
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

CREATE SEQUENCE public.ipcr_details_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.ipcr_headers_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

ALTER TABLE public.ipcr_headers_id_seq OWNER TO postgres;

--
-- Name: ipcr_headers_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.ipcr_headers_id_seq OWNED BY public.ipcr_headers.id;

--
-- Name: ipcr_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.ipcr_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.learnings_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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
    credits numeric(8, 3) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);

ALTER TABLE public.leave_credits OWNER TO postgres;

--
-- Name: leave_credits_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.leave_credits_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.leave_details_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.leave_headers_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.loan_applications_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.menus_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.migrations_id_seq AS integer START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.name_prefixes_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.name_suffixes_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.non_plantillas_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.offboarding_natures_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

ALTER TABLE public.official_business_applications
ALTER COLUMN id
ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.official_business_applications_id_seq START
    WITH
        1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1
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

ALTER TABLE public.overtime_applications
ALTER COLUMN id
ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.overtime_applications_id_seq START
    WITH
        1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1
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

CREATE SEQUENCE public.overtime_types_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.payroll_cutoffs_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.payroll_deductions_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.payroll_incomes_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.payroll_intervals_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.payroll_item_schedule_details_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.payroll_item_schedule_headers_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.payroll_periods_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.payroll_summaries_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.philhealths_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.plantillas_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.positions_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.promotion_natures_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.religions_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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
    old_salary numeric(8, 2),
    old_salary_grade_id integer,
    old_salary_step_id integer,
    new_salary numeric(8, 2) NOT NULL,
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

CREATE SEQUENCE public.salary_adjustments_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.salary_grades_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.salary_schedules_details_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

ALTER TABLE public.salary_schedules_details_id_seq OWNER TO postgres;

--
-- Name: salary_schedules_details_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.salary_schedules_details_id_seq OWNED BY public.salary_schedules_details.id;

--
-- Name: salary_schedules_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.salary_schedules_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.salary_steps_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.schedule_days_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.sections_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.semester_ratings_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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
    annual_salary numeric(18,2) DEFAULT '0'::numeric,
    place_of_assignment character varying(255),
    leave_without_pay numeric(18,2) DEFAULT '1'::numeric,
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

CREATE SEQUENCE public.service_records_service_record_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.service_records_temps_service_record_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.shift_schedules_details_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.shift_schedules_headers_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.sss_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.step_increments_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.tax_tables_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.time_data_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.time_keeping_setups_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.users_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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

CREATE SEQUENCE public.work_cancellations_id_seq START
WITH
    1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

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
2	Not Qualified	\N	\Ninformation
3	Not to Proceed	\N	\Ninformation
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
1	A	t	2022-06-20 14:03:54	1900-01-01 00:00:00
2	A-	t	2022-06-20 14:03:54	1900-01-01 00:00:00
3	A+	t	2022-06-20 14:03:54	1900-01-01 00:00:00
4	AB	t	2022-06-20 14:03:54	1900-01-01 00:00:00
5	AB+	t	2022-06-20 14:03:54	1900-01-01 00:00:00
6	B	t	2022-06-20 14:03:54	1900-01-01 00:00:00
7	B+	t	2022-06-20 14:03:54	1900-01-01 00:00:00
8	O	t	2022-06-20 14:03:54	1900-01-01 00:00:00
9	O+	t	2022-06-20 14:03:54	1900-01-01 00:00:00
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
1	Single	t	2022-06-20 12:04:08	1900-01-01 00:00:00
2	Married	t	2022-06-20 12:04:08	1900-01-01 00:00:00
3	Separated	t	2022-06-20 12:04:08	1900-01-01 00:00:00
4	Widow	t	2022-06-20 12:04:08	1900-01-01 00:00:00
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
1		Aga Khan Museum		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
2		Audio-Visual Center		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
3		Auxiliary Services Office		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
4		Board of Regents		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
5		Campus Accounting Office		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
6		Campus Budget Office		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
7		Cashier Office		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
8		Center for Women Studies		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
9		COL Extn (For Enrolment Purposes Only		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
10		College of Agriculture		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
11		College of Business Administration and Accountancy		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
12		College of Educatio		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
13		College of Engineering		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
14		College of Fisheries		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
15		College of Forestry and Environmental Studies		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
16		College of Health Sciences		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
17		College of Hotel and Restaurant Management		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
18		College of Information Technology		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
19		College of Law		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
20		College of Medicine		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
21		College of Natural Sciences and Mathematics		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
22		College of Public Affairs		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
23		College of Social Sciences and Humanities		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
24		College of Sports, Physical Education and Recreatio		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
25		Community Relations Office		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
26		Cultural Affairs Office		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
27		Division of Student Affairs		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
28		FFI - Ceramics Development Center		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
29		Finance Department		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
30		Fire Department		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
31		Food Service Unit		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
32		Graduate Studies		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
33		Housing Management Divisio		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
34		Human Resource Development Office		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
35		Information and Communications Technology Center		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
36		Institute of Science Educatio		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
37		Institutional Research and Evaluation Office		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
38		Integrated Laboratory School		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
39		Internal Control Office		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
40		King Faisal Center for Islamic, Arabic and Asian Studies		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
41		Lump Sum Items - Advanced Education Services		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
42		Lump Sum Items - Extension Services		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
43		Lump Sum Items - General Administratio		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
44		Mamitua Saber Research Center		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
45		Manila Information and Liaisoning Office		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
46		Medical Services and Hospital		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
47		Motor Pool and Water System		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
48		MSU Balindong Community High School		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
49		MSU Balo-i Community High School		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
50		MSU Binidayan Community High School		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
51		MSU Buug College		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
52		MSU Karomatan Community High School		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
53		MSU Lanao National College of Arts and Trade		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
54		MSU Lanao Norte Agricultural College		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
55		MSU Lopez Jaena Community High School		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
56		MSU Malabang Community High School		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
57		MSU Marantao Community High School		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
58		MSU Masiu Community High School		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
59		MSU Saguiaran Community High School		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
60		MSU Senior High School		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
61		MSU Siawadatu Agricultural High School		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
62		MSU Tamparan Community High School		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
63		MSU Taraka Community High School		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
64		MSU Tugaya Community High School		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
65		MSU Wao Community High School		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
66		National Service Training Program Office		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
67		Natural Science Museum		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
68		Office of the Administrative Services		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
69		Office of the Admission Divisio		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
70		Office of the Alumni Relations		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
71		Office of the Assistant Vice Chancellor for Academic Affairs		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
72		Office of the Chancellor		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
73		Office of the Executive Vice President		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
74		Office of the President		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
75		Office of the Registrar		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
76		Office of the Vice Chancellor for Academic Affairs		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
77		Office of the Vice Chancellor for Administration and Finance		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
78		Office of the Vice Chancellor for Research and Extensio		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
79		Office of the Vice President for Academic Affairs		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
80		Office of the Vice President for Administration and Finance		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
81		Office of the Vice President for Planning and Development		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
82		Peace Center		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
83		Personnel Management and Training Divisio		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
84		Philippine Carabao Center at MSU		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
85		Physical Plant Divisio		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
86		Pre University Center		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
87		Property Office		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
88		Radio and Telecommunication System		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
89		Resident Auditor Office		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
90		Security Department		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
91		Shariah Center		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
92		Sports Scholarship Development Office		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
93		Supply Management Office		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
94		System Accounting Office		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
95		System Budget Office		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
96		Technology and Innovation Center		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
97		University Extension Services Center		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
98		University Legal Services		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
99		University Library		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
100		University Press and Information Office		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
101		University Training Center		f	2022-06-20 13:43:23	1900-01-01 00:00:00	t	0
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
1	CAREER CIVIL SERVICE-PROFESSIONAL	t	2022-06-21 08:52:33	\N
2	CAREER SERVICE PROF	t	2022-06-21 08:52:33	\N
3	CAREER SERVICE PROFESSIONAL	t	2022-06-21 08:52:33	\N
4	CAREER SERVICE PROFESSIONAL EXAM (LOCAL)	t	2022-06-21 08:52:33	\N
5	CAREER SERVICE PROFESSIONAL EXAM (NATIONAL))	t	2022-06-21 08:52:33	\N
6	CAREER SERVICE PROFESSITIONAL 	t	2022-06-21 08:52:33	\N
7	CARPENTRY NC 11	t	2022-06-21 08:52:33	\N
8	CE	t	2022-06-21 08:52:33	\N
9	CIVIL ENGINEER BOARD EXAMINATIO	t	2022-06-21 08:52:33	\N
10	CIVIL ENGINEERS BOARD EXAMINATIO	t	2022-06-21 08:52:33	\N
11	CIVIL SERVICE CAREER SUB PROFESSIONAL	t	2022-06-21 08:52:33	\N
12	CIVIL SERVICE ELIGIBILITY FOR BARANGAY OFFICIAL	t	2022-06-21 08:52:33	\N
13	CSC-TESDA	t	2022-06-21 08:52:33	\N
14	DOCTOR OF ENGINEERING	t	2022-06-21 08:52:33	\N
15	FISHERIES TECHNOLOGIST BOARD EXAMAMINATIO	t	2022-06-21 08:52:33	\N
16	LICENSURE  EXAMINATION FOR TEACHERS (LET)	t	2022-06-21 08:52:33	\N
17	LICENSURE EXAMINATION FOR TEACHERS	t	2022-06-21 08:52:33	\N
18	MASTER OF SCIENCE IN CHEMICAL ENGINEERING	t	2022-06-21 08:52:33	\N
19	MECHANICAL ENGINEERING BOARD EXAM	t	2022-06-21 08:52:33	\N
20	NATIONAL TVET TRAINER CERTIFICATE	t	2022-06-21 08:52:33	\N
21	NC II MASONRY	t	2022-06-21 08:52:33	\N
22	PBET/LET	t	2022-06-21 08:52:33	\N
23	PD 907	t	2022-06-21 08:52:33	\N
24	PROFESSIONAL BOARD EXAMINATION FOR TEACHER	t	2022-06-21 08:52:33	\N
25	PROFESSIONAL CIVIL SERVICE ELIGIBILITY	t	2022-06-21 08:52:33	\N
26	PROFESSIONAL ELECTRICAL ENGINEER	t	2022-06-21 08:52:33	\N
27	PROFESSIONAL LICENSE	t	2022-06-21 08:52:33	\N
28	RA 1080	t	2022-06-21 08:52:33	\N
29	RA 1080, CS PROF, TEACHERS BOARD	t	2022-06-21 08:52:33	\N
30	REGISTERED CIVIL AND GEODETIC ENGINEER	t	2022-06-21 08:52:33	\N
31	REGISTERED ELECTRICAL ENGINEER	t	2022-06-21 08:52:33	\N
32	REGISTERED FORESTER/ CSC PROFESSIONAL	t	2022-06-21 08:52:33	\N
33	REGISTERED PROFESSIONAL AGRICULTURAL ENGINEER	t	2022-06-21 08:52:33	\N
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
1	4	0	Mindanao State University - Main Campus	BS Agricultural Business Management	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
2	4	0	Mindanao State University - Main Campus	MS Farming System	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
3	5	0	Melbourne University	M Engineering Science	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
4	5	0	UP-Los Baños	MS Agricultural Engineering	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
5	7	0	Gregorio Araneta University	Ph.D Agricultural Extensio	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
6	7	0	Gregorio Araneta University Foundatio	BSA Extensio	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
7	7	0	Gregorio Araneta University Foundatio	MSA Extensio	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
8	8	0	Mindanao State University - Main Campus	BS Agri. Business	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
9	8	0	UP Los Baños	MS Agribusiness Management	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
10	9	0	Central Luzon State University	Ph.D Animal Science	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
11	9	0	Gregorio Araneta University Foundatio	MS Animal Science	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
12	9	0	Mindanao State University - Main Campus	BSA Animal Husbandry	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
13	10	0	Mindanao State University - Main Campus	BSA Agronomy	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
14	10	0	University of Ghent/University of Pertania, Malaysia	Ph.D Soils	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
15	10	0	UP-Los Baños	MS Soils	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
16	11	0	Mindanao State University - Main Campus	B.S. in Agricultural Engineering	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
17	11	0	Mindanao State University - Main Campus	B.S. in Civil Engineering	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
18	11	0	North Carolina State University, Raleigh, NC, U.S.A.	Ph.D in Biological and Agricultural Engineering	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
19	11	0	Oklahoma State University, Stillwater, OK, U.S.A.	M.S. in Biosystems and Agricultural Engineering	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
20	12	0	Central Luzon State University	Ph.D Crop Science	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
21	12	0	Gregorio Araneta University Foundatio	MSA Agronomy	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
22	12	0	Lanao Agricultural College	BSA Agronomy	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
23	13	0	Mindanao State University - Main Campus	BS Agricultural Engineering	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
24	15	0	Gregorio Araneta University	MS Agricultural Economics	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
25	15	0	Gregorio Araneta University Foundatio	BS Agricultural Administratio	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
26	15	0	UP-Los Baños	Ph.D Extensio	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
27	16	0	Barnevelo College, The Netherlands	Diploma in Animal Husbandry	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
28	16	0	Central Mindanao University	MS Animal Science	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
29	16	0	Mindanao State University - Main Campus	BSA Animal Science	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
30	16	0	Mindanao State University - Main Campus	MA Educatio	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
31	17	0	UP-Dilima	Doctor of Veterinary Medicine	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
32	17	0	UP-Los Baños	MS Animal Science	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
33	17	0	UP-Los Baños	Ph.D Animal Science	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
34	18	0	Central Mindanao University	Doctor Veterinary Medicine	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
35	19	0	Asian Institute of Technology	M Engineering	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
36	20	0	Mindanao State University - Main Campus	BSA Agronomy	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
37	20	0	University of Ghent	MS Soils	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
38	20	0	University of Ghent	Ph.D Soils	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
39	21	0	Mindanao State University - Main Campus	BS AGRICULTURE (Animal Husbandry)	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
40	21	0	Mindanao State University - Main Campus	BS Animal Husbandry	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
41	21	0	UP-Los Baños	MS Animal Science	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
42	21	0	UP-Los Baños	PhD Animal Science	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
43	22	0	Mindanao State University - Main Campus	BSA Agronomy	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
44	22	0	Pangasinan State University	MSA Farming System	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
45	23	0	Dansalan College	BS Agriculture	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
46	23	0	Mindanao State University - Main Campus	CGM	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
47	23	0	UP-Los Baños	MM Agribusiness Managment	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
48	24	0	Mindanao State University - Main Campus	BSBA Marketing	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
49	24	0	Mindanao State University - Main Campus	MBA	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
50	24	0	Mindanao State University - Main Campus	MPA	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
51	25	0	Central Mindanao University	MS Plant Pathology	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
52	25	0	Central Mindanao University	Ph.D. Plant Pathology	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
53	25	0	Mindanao State University - Main Campus	BSA Plant Pathology	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
54	26	0	Central Mindanao University	Ph.D Agricultural Educatio	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
55	26	0	Mindanao State University - Main Campus	BSA Educatio	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
56	26	0	Mindanao State University - Main Campus	M Public Administratio	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
57	27	0	Central Mindanao University	Ph.D Soil Science	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
58	27	0	Gregorio Araneta University Foundatio	BSA Agronomy	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
59	27	0	Gregorio Araneta University Foundatio	MSA Plant Science	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
60	28	0	Mindanao State University - Main Campus	BSA Animal Husbandry	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
61	28	0	UPLB	MS Animal Science	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
62	28	0	UPLB	Ph.D Animal Science (CAR)	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
63	29	0	Mindanao State University - Main Campus	BS Agriculture (Agronomy)	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
64	29	0	University of Queensland,Australia	MS Agricultural Studies	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
65	30	0	Mindanao State University - Main Campus	BS Mechanical Engineering	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
66	30	0	UP-Los Baños	MS Agricultural Engineering	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
67	31	0	CLSU	MS Animal Science	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
68	31	0	CMU	PhD in Animal Science	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
69	31	0	Mindanao State University - Main Campus	BS Animal Science	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
70	32	0	Mindanao State University - Main Campus	BS Agricultural Engineering	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
71	32	0	UP Los Baños	MS Agricultural Engineering	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
72	32	0	UP Los Baños	MS Agromet	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
73	33	0	Bicol University	BSA (Agronomy)	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
74	33	0	Central Luzon State University	MS Horticulture	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
75	34	0	Mindanao State University - Main Campus	Bachelor of Laws	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
76	34	0	Mindanao State University - Main Campus	BS Animal Science	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
77	34	0	Mindanao State University - Main Campus	MS Animal Science	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
78	36	0	Mindanao Polytechnic State College	Ph.D Educational Management	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
79	36	0	Mindanao State University - Main Campus	BSA Agronomy	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
80	36	0	Mindanao State University - Main Campus	Certificate of Governmental Management	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
81	36	0	Mindanao State University - Main Campus	M Public Administratio	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
82	37	0	Mindanao State University - Main Campus	BSA Agronomy	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
83	37	0	UP Los Baños	MS Entomology	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
84	37	0	UP Los Baños	Ph.D Entomology	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
85	38	0	Mindanao State University - Main Campus	BSA Agronomy	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
86	38	0	Mindanao State University - Main Campus	MS Farming Systems	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
87	40	0			0	0	\N	\N	\N	2022-06-21 10:20:39	\N
88	40	0	Asian Institute of Technology	M Engineering	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
89	40	0	Visayas College of Agriculture	BS Agricultural Engineering	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
90	41	0	Central Mindanao University	Ph.D Agronomy	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
91	41	0	Mindanao State University - Main Campus	BSA Agronomy	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
92	42	0	Mindanao State University - Main Campus	BSA Animal Husbandry	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
93	42	0	UP-Los Baños	MS Animal Science	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
94	42	0	UP-Los Baños	Ph.D Animal Science	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
95	44	0	Mindanao State University - Main Campus	BSA-Animal Science	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
96	44	0	Mindanao State University - Main Campus	MS Animal Science	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
97	44	0	University of the Philippines Los Banos	PhD Animal Science	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
98	45	0	Liceo de Cagayan University	Master in Management	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
99	45	0	Mindanao State University - Main Campus	Bachelor of Laws	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
100	45	0	Mindanao State University - Main Campus	BSBA Management	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
101	46	0	Mindanao State University - Iligan Institute of Technology	MBM General Management	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
102	46	0	Mindanao State University - Main Campus	BSBA Accounting	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
103	47	0	Mindanao State University - Main Campus	BSBA Economics	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
104	47	0	UP- Diliman Sch. of Economics	MA Economics,	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
105	48	0	Mindanao State University - Main Campus	BSBA Accounting	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
106	48	0	UST (CAR)	MS Accounting & Taxatio	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
107	50	0	Mindanao State University - Iligan Institute of Technology	MBA	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
108	50	0	Mindanao State University - Main Campus	BSBA (Marketing)	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
109	50	0	University of Sto. Tomas	Ph.D. Commerce	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
110	54	0	Liceo de  Cagayan University	Masters in Management	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
111	54	0	Mindanao State University - Iligan Institute of Technology	BSBA	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
112	57	0	Mindanao State University - Iligan Institute of Technology	Master in Business Administratio	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
113	57	0	Mindanao State University - Main Campus	BSBA (Marketing)	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
114	57	0	University of San Jose Recoletos	Doctor of Management (Human Resources Management)	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
115	59	0	University of Sto. Thomas	MSC Accounting & Taxatio	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
116	61	0	Mindanao State University - Main Campus	BSBA (Economics)	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
117	61	0	UP-Dilima	(CAR) Ph.D. Economics,	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
118	61	0	UP-Dilima	MA Economics	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
119	65	0	Mindanao State University - Main Campus	BSBA Economics	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
120	65	0	San Nicolas College , Surigao City	High Schooll Graduate	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
121	65	0	Xavier University - Ateneo de Cagaya	MS Agricultural Economics (CAR)	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
122	66	0	Mindanao State University - Main Campus	BSBA Marketing	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
123	66	0	Mindanao State University - Main Campus	Master in Public Administration (27 Units)	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
124	66	0	University of San Jose Recoletos	Master in Business Administratio	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
125	68	0	Mindanao State University - Main Campus	BSBA (Marketing)	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
126	68	0	University of Santo Tomas	MA Business Administration 	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
127	69	0	Mindanao State University - Main Campus	BSBA Accounting	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
128	74	0	Mindanao State University - Iligan Institute of Technology	BS Computer Science	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
129	74	0	Mindanao State University - Iligan Institute of Technology	MS Computer Applications (units only)	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
130	76	0	Cairo University, Egypt	Master in Business Administratio	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
131	76	0	PWU	BS Business Administratio	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
132	78	0	Mindanao State University - Main Campus	BSBA (Marketing)	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
133	78	0	UP Dilima	Doctor of Business Administratio	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
134	78	0	UP Dilima	Master of Business Administration 	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
135	79	0	Jamiatul Philippine Al-Islamia, Marawi City	BSEEd. (Social Studies)	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
136	79	0	Mindanao State University - Main Campus	MAT (Education)	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
137	79	0	UP-Dilima	Ed. D. (Social Studies)	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
138	80	0	DLSU-Manila	Ph.D. Counseling, Psychology	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
139	80	0	Mindanao State University - Main Campus	BS Elementary Educatio	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
140	80	0	Mindanao State University - Main Campus	Ph.D. Educational Management	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
141	80	0	UP Dilima	MAEd. Guidance and Counseling	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
142	80	0	UP Dilima	MAEd. School Administratio	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
143	81	0	Mindanao State University - Main Campus	Ph.D. Educational Management	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
144	81	0	Pangarungan Islamic Colleges	BS Elementary Educatio	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
145	81	0	UP Dilima	Ed.D Guidance	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
146	82	0	University of Manila	BS Elementary Educatio	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
147	82	0	University of Manila	MAEd. Guidance and Counseling	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
148	82	0	UP Dilima	Ph.D. Guidance and Counseling	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
149	83	0	Mindanao State University - Main Campus	MAT General Educatio	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
150	83	0	Mindanao State University - Main Campus	Ph.D Educational Management	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
151	83	0	University of San Carlos	BS Nutrition and Dietetics	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
152	84	0	Mindanao State University - Main Campus	BS Education 	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
153	84	0	UP Diliman 	Master in Home Economics 	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
154	85	0	Mindanao State University - Main Campus	BSEEd	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
155	85	0	University of Hawaii	MA Ed. Curriculum and Instruction, Reading	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
156	85	0	University of San Carlos	Ed.D School Administratio	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
157	86	0	De la Salle University	Ph.D Educatio	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
158	86	0	Mindanao State University - Main Campus	MAT General Educatio	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
159	86	0	University of Manila	BSE English	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
160	87	0	Mindanao State University - Main Campus	CGM	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
161	87	0	Mindanao State University - Main Campus	MPA Organization and Management	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
162	87	0	St. Peters College	AB-BSE English and History	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
163	87	0	UP Dilima	Ed.D Social Studies	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
164	89	0	Mindanao Polytechnic State College 	Ph.D. Educational Planning and Management	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
165	89	0	Mindanao State University - Main Campus	AB Psychology	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
166	89	0	Mindanao State University - Main Campus	MA Ed. Guidance and Counselling	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
167	90	0	Mindanao State University - Main Campus	MAT General Educatio	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
168	90	0	Mindanao State University - Main Campus	Ph.D. Educatio	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
169	90	0	Misamis University	AB English	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
170	90	0	Misamis University	BSE English	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
171	91	0	De La Salle  University	PhD in Science Educatio	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
172	91	0	Mindanao State University - Main Campus	BSE Biology and Home Economics	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
173	91	0	Mindanao State University - Main Campus	MA in Teaching General Science	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
174	92	0	Mindanao Polytechnic State College	Ph.D. Educational Planning and Management	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
175	92	0	Mindanao State University - Main Campus	BSE Home Economics	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
176	92	0	Mindanao State University - Main Campus	MAT General Educatio	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
177	93	0	Mindanao State University - Iligan Institute of Technology	Ph.D Biology	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
178	93	0	Mindanao State University - Main Campus	BS Biology Pre-Medicine	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
179	93	0	Mindanao State University - Main Campus	MAT General Science	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
180	96	0	Cebu Normal University	Ph. D. Educatio	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
181	96	0	Stella Maris College	BSE (Biology)	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
182	96	0	University of San Carlos	Doctor of Public Administratio	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
183	96	0	University of San Carlos	Ed. D.	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
184	97	0	Mindanao State University - Main Campus	BSEEd (Home Economics)	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
185	97	0	Mindanao State University - Main Campus	MAT Teaching (Gen. Education)	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
186	97	0	UP-Diliman 	Ph.D. Home Economics (CAR)	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
187	98	0	Central Mindanao University	PhD Educational Administratio	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
188	98	0	Mindanao State University - Main Campus	Master of Arts in Education (MAEd) - Reading	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
189	98	0	University of Melbourne, Melbourne (Victoria, Australia)	Master of Assessment & Evaluatio	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
190	99	0	University of the East	BSEEd	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
191	99	0	University of the East	MAEd. (School Administration)	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
192	99	0	Xavier University - Ateneo de Cagaya	Ph. D. Educational Management	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
193	102	0	Don Mariano Marcos Memorial Polytechnic State College	Ph.D. Educational Planing & Management, 	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
194	102	0	Mindanao State University - Main Campus	BSE (English/ History),	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
195	102	0	Mindanao State University - Main Campus	MA Teaching,	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
196	103	0	Cebu State college	Ed.D. (Pre-Elementary Education)	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
197	103	0	Philippine Normal College	BS Elementary Educatio	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
198	103	0	UP-Dilima	MAT (Special Education)	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
199	104	0	Jamiatul Philippine Al-Islamia	BS Elementary Educatio	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
200	104	0	Mindanao State University - Main Campus	MAT General Educatio	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
201	104	0	Xavier University - Ateneo de Cagaya	Ph.D. Educatio	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
202	108	0	Mindanao State University - Iligan Institute of Technology	BSE (Chemistry)	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
203	108	0	Mindanao State University - Iligan Institute of Technology	MS (Educational- Chemistry) - CAR	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
204	109	0	International Islamic University Malaysia, Kuala Lumpur	PhD in Education major in Curriculum & Instructio	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
205	109	0	Mindanao State University - Main Campus	AB History (CSSH)	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
206	109	0	Mindanao State University - Main Campus	AB Islamic Studies major in Shariah	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
207	109	0	Mindanao State University - Main Campus	Master of Arts in Education major in School Adm	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
208	110	0	Cebu School of Arts and Trades	MEd.	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
209	110	0	Philippine College of Arts and Trades	BSIE	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
210	111	0	Royal Melbourne Institue of Technology University, Australia	Ph.D. Mechanical Engineering (Solar Energy)	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
211	111	0	UP-Dilima	MS Mechanical Engineering, 	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
212	113	0	CSAT-Cebu	BSIE	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
213	113	0	CSCST-Cebu	MAV.Ed.	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
214	114	0	Mindanao State University - Main Campus	BS Civil Engineering	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
215	114	0	UP-Dilima	M Environmental Planning 	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
216	115	0	Mindanao State University - Main Campus	BS Chemical Engineering	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
217	115	0	University of Ghent	Advance Studies	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
218	116	0	Georgia Tech, Atlanta, Georgia, USA	Ph.D. Electrical Engneering (Electronic)	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
219	116	0	Georgia Tech, USA	MS Electrical Engineering	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
220	116	0	Mindanao State University - Main Campus	BS Electrical Engineering	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
221	117	0	Mindanao State University - Main Campus	BS Civil Engineering	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
222	118	0	Mindanao State University - Main Campus	Bs Electrical Engineering	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
223	119	0	Mindanao State University - Main Campus	BS Civil Engineering	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
224	119	0	UP Dilima	MS Civil Engineering (CAR)	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
225	120	0	Don Mariano Polytechnic State College	MA Industrial Education - Practical Arts (CAR)	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
226	120	0	Mindanao State University - Main Campus	Diploma in Eng'g Technology	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
227	120	0	Mindanao State University - Main Campus	Prof. Diploma in Physical Educatio	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
228	120	0	MSU LNCAT	BS Industrial Educatio	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
229	120	0	Northern Mindanao Polytechnic State College	MA Industrial Education - Industrial Management	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
230	121	0	Mindanao State University - Main Campus	Certificate in Governmental Management	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
231	121	0	Mindanao State University - Main Campus	Master in Public Administratio	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
232	121	0	Negros Oriental State University	Bachelor of Science in Industrial Technology	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
233	121	0	Xavier University - Ateneo de Cagaya	Doctor of Philosophy in Educational Management	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
234	122	0	Mindanao State University - Main Campus	BS Chemicaln Engineering	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
235	122	0	UP-Dilima	MS Energy Engineering	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
236	125	0	UP-Dilima	MS Electrical Engineering	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
237	126	0	Mindanao State University - Main Campus	BS Mechanical Engineering	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
238	127	0	Mindanao State University - Iligan Institute of Technology	Master in Teaching Technology Major in Building Construction Technology (C.A.R.)	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
239	127	0	Mindanao State University - Main Campus	Bachelor of Science in Civil Engineering	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
240	127	0	Public Affairs Graduate School, MSU Campus,Marawi City 	Master in Public Administration Field of Specialization: Organization and Management (CAR)	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
241	131	0	CSCST	MTE	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
242	131	0	Maigo School of Arts and Trade	BS Industrial Engineering	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
243	132	0	Mindanao State University - Iligan Institute of Technology	MS Computer Applicatio	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
244	132	0	Mindanao State University - Main Campus	BS Mechanical Engineering	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
245	132	0	Saga University	Ph.D. (Robotics and Intelligent Systems)	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
246	134	0	Mindanao State University - Main Campus	BS Electrical Engineering	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
247	135	0	Mindanao State University - Iligan Institute of Technology	Doctor of Engineering	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
248	135	0	Mindanao State University - Iligan Institute of Technology	MS Civil Engineering	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
249	135	0	Mindanao State University - Main Campus	BS Civil Engineering	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
250	136	0	De La Salle University-Manila	Ph D. Chemical  Engineering (CAR)	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
251	136	0	Mindanao State University - Main Campus	BS Chemical Engineering	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
252	136	0	UP Dilima	MS Energy Engineering	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
253	138	0	Mindanao State University - Main Campus	BS Civil Engineering	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
254	138	0	Mindanao State University - Main Campus	LLB	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
255	139	0	Mindanao State University - Main Campus	BS Mechanical Engineering	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
256	139	0	UP-Dilima	MS Mechanical Engineering	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
257	140	0	Mindanao State University - Main Campus	BS Civil Engineering	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
258	141	0	Asian Institute of Technology	MSc in Geotechnics	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
259	141	0	Asian Institute of Technology	PhD in Geotechnics	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
260	142	0	Mindanao University of Science & technology	Doctor in Technology Education (Graduating)	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
261	142	0	MPSC- Cagayan de Oro	M Industrial Tech, MIT	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
262	142	0	MPSC- Cagayan de Oro	MA Industrial Ed(Earned Units)	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
263	144	0	Mindanao State University - Main Campus	BS in Mechanical Engineering	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
264	144	0	UP Dilima	Master of Science in Mechanical Engineering	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
265	144	0	UP Dilima	Ph.D in Material Science & Engineering	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
266	145	0	Mindanao State University - Main Campus	BS Civil Engineering	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
267	145	0	Mindanao State University - Main Campus	CGM,	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
268	145	0	Mindanao State University - Main Campus	MPA,	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
269	145	0	University Kabangsaan, Malaysia	MS Civil and Structurl Engineering	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
270	146	0	IISEE, Tokyo, Japa	Post Graduate Diploma,Earthquake Engineering	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
271	146	0	Mindanao State University - Main Campus	BS Civil Engineering	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
272	147	0	Mindanao State University - Main Campus	BS Electrical Engineering	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
273	147	0	UP Dilima	MS Electrical Eng'g (Computer & Communication)	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
274	148	0	Mindanao State University - Main Campus	B.S. IN CIVIL ENGINEERING	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
275	148	0	UNIV. OF THE PHIL. ,DILIMAN , Q.C.	MS CE-CAR	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
276	149	0	Mindanao State University - Main Campus	BS Electical Engineering	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
277	152	0	Mindanao State University - Iligan Institute of Technology	Master of Engineering	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
278	153	0	Don Mariano Marcos Memorial Polytechnic State College	Bachelor of Science in Industrial Educatio	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
279	153	0	Saint Joseph High School	Secondary	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
280	153	0	University of Science and Technology of Southern Philippines	Master in Technician Teacher Educatio	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
281	154	0	Mindanao State University - Main Campus	BS Mechanical Engineering	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
282	155	0	Mindanao State University - Main Campus	BS Electrical  Engineering	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
283	155	0	University of Tazmania, Australia	Master of Technology in Power Engineering and Process Control	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
284	155	0	UP Dilima	Master of Electrical Engineering	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
285	156	0	Mindanao State University - Main Campus	BS Civil Engineering	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
286	157	0	ESSC	BS Industrial Engineering	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
287	157	0	Mindanao State University - Main Campus	Ph.D. Educational Management	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
288	157	0	MPSC	MA Industrial Engineering	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
289	159	0	Mindanao State University - Main Campus	BS Civil Engineering 	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
290	159	0	UP-Dilima	MA ( Urban and Regional Planning)	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
291	161	0	Cebu Institute of Technology	BS Mechanical Engineering	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
292	161	0	Mindanao State University - Iligan Institute of Technology	MTT	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
293	163	0	Mindanao State University - Main Campus	BS Civil Engineering	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
294	163	0	Mindanao State University - Main Campus	Certificaten in Governmental Management,	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
295	163	0	UP-Dilima	MS Civil Engineering	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
296	165	0	Mindanao State University - Main Campus	BS Chemical Engineering 	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
297	165	0	UP-Dilima	MS Environmental Engineering	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
298	167	0	MSAT	BS Industrial Engineering	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
299	168	0	Mindanao State University - Iligan Institute of Technology	Doctor of Engineering (Material Science & engineering)	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
300	168	0	Mindanao State University - Main Campus	BS Chemical Engineering	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
301	168	0	UP-Dilima	MS Chemical Engineering 	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
302	172	0	Mindanao State University - Main Campus	BS Fisheries 	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
303	172	0	UP-Visayas	MS Fisheries (Aquaculture)	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
304	173	0	Central Mindanao University	MS Food Science (CAR)	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
305	173	0	Dipolog School of Fisheries	BS Fisheries	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
306	176	0	Mindanao State University - Main Campus	BS Fisheries	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
307	176	0	University of San Carlos 	MS Marine Biology 	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
308	177	0	Mindanao State University - Main Campus	BS Inland Fisheries	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
309	177	0	University of San Carlos 	MS Marine Biology	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
310	178	0	Mindanao State University - Main Campus	BS Fisheries	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
311	178	0	MSU-Naawa	MS Marine Biology	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
312	178	0	UP Visayas	PhD Fisheries (CAR)	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
313	180	0	Mindanao State University - Main Campus	BS Biology,	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
314	180	0	Mindanao State University - Main Campus	CGM, 	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
315	180	0	UP-Dilima	MS Biology,	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
316	180	0	UP-Dilima	Ph.D. Biology,	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
317	183	0	Mindanao State University - Main Campus	BS Fisheries (Aquaculture)	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
318	183	0	UP Los Baños	MS Marine Biology	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
319	184	0	Central  Luzon State University	(CAR) MS Aquaculture	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
320	184	0	Mindanao State University - Main Campus	BS Fisheries	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
321	184	0	Mindanao State University - Main Campus	M Public Administration,	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
322	186	0	Central Luzon State University	MS Fisheries - Aquaculture	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
323	186	0	Mindanao State University - Main Campus	Bachelor of Laws	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
324	186	0	Mindanao State University - Main Campus	BS Fisheries	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
325	186	0	Mindanao State University - Main Campus	Master in Public Administratio	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
326	187	0	Mindanao State University - Main Campus	BS Biology	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
327	187	0	Mindanao State University - Main Campus	MAT General Science	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
328	189	0	Mindanao State University - Main Campus	BS Forestry	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
329	189	0	University of Ghent, Belgium	MS Environmental Sanitatio	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
330	189	0	University of Ghent, Belgium	Ph.D. Applied Biological Science	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
331	190	0	Mindanao State University - Main Campus	BS Forestry	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
332	190	0	Mindanao State University - Main Campus	LlB	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
333	190	0	UPLB	MS Forestry	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
334	191	0	Asian Institute of Technology	MS Forestry (Natural Resource Development and Management	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
335	191	0	Mindanao State University - Iligan Institute of Technology	PhD in Biology	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
336	191	0	Mindanao State University - Main Campus	BS Forestry	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
337	191	0	Mindanao State University - Main Campus	Certificate in Governmental Management	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
338	195	0	Mindanao State University - Main Campus	BS Forestry	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
339	195	0	Mindanao State University - Main Campus	Certificate in Governmental Management	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
340	195	0	UPLB	MS Forest Biological Sciences	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
341	195	0	UPLB	Ph.D. Forestry (Forest Biological Sciences)	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
342	197	0	Mindanao State University - Main Campus	B.S.  in Forestry	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
343	198	0	Mindanao State University - Main Campus	BS Agriculture	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
344	198	0	Mindanao State University - Main Campus	Diploma in Forestry Technology	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
345	198	0	UPLB	MS Forestry	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
346	198	0	UPLB	Ph.D. Forestry	0	0	\N	\N	\N	2022-06-21 10:20:39	\N
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
1	11	\N	\N	\N	0.00	\N	Probationary	INSTRUCTOR II	0	2022-06-21 10:36:47	\N
2	11	\N	\N	\N	0.00	\N	Permanent	ASSOCIATE PROFESSOR III	0	2022-06-21 10:36:47	\N
3	28	\N	\N	\N	0.00	\N	Permanent	ASSOC. PROF. V	0	2022-06-21 10:36:47	\N
4	34	\N	\N	\N	0.00	\N	Probationary	INSTRUCTOR 1	0	2022-06-21 10:36:47	\N
5	66	\N	\N	\N	0.00	\N	Regular	PACKING PLANT SUPERVISOR	0	2022-06-21 10:36:47	\N
6	66	\N	\N	\N	0.00	\N	Permanent	ASSOCIATE PROF. V	0	2022-06-21 10:36:47	\N
7	98	\N	\N	\N	0.00	\N	Permanent	ASSOCIATE PROFESSOR V	0	2022-06-21 10:36:47	\N
8	98	\N	\N	\N	0.00	\N	Designatio	DIRECTOR	0	2022-06-21 10:36:47	\N
9	118	\N	\N	\N	0.00	\N	Permanent	INSTRUCTOR III	0	2022-06-21 10:36:47	\N
10	120	\N	\N	\N	0.00	\N	Permannent	HIGH SCHOOL PRINCIPAL	0	2022-06-21 10:36:47	\N
11	120	\N	\N	\N	23159.00	\N	Permanent	ASST. PROFESSOR IV	0	2022-06-21 10:36:47	\N
12	120	\N	\N	\N	0.00	\N		ASST. PROF. IV	0	2022-06-21 10:36:47	\N
13	120	\N	\N	\N	27360.00	\N	Permanent	ASST. PROF. IV	0	2022-06-21 10:36:47	\N
14	120	\N	\N	\N	32000.00	\N	Permanent	ASST. PROF. IV	0	2022-06-21 10:36:47	\N
15	121	\N	\N	\N	0.00	\N	Permanent	ASSISTANT PROFESSOR IV	0	2022-06-21 10:36:47	\N
16	121	\N	\N	\N	0.00	\N	Permanent	DIRECTOR	0	2022-06-21 10:36:47	\N
17	127	\N	\N	\N	0.00	\N	Provisionary	ASST. PROF. IV	0	2022-06-21 10:36:47	\N
18	131	\N	\N	\N	44000.00	\N	Permanent	ASSC. PROF. V	0	2022-06-21 10:36:47	\N
19	142	\N	\N	\N	5000.00	\N	Regular	AUTO INSTRUCTOR	0	2022-06-21 10:36:47	\N
20	142	\N	\N	\N	0.00	\N	Contractual/Permanent	INSTRUCTOR III	0	2022-06-21 10:36:47	\N
21	145	\N	\N	\N	20642.00	\N	Permanent	ASST, PROF 1V	0	2022-06-21 10:36:47	\N
22	148	\N	\N	\N	0.00	\N	Permanent	CHAIRMAN-ASSOC. PROF.V	0	2022-06-21 10:36:47	\N
23	157	\N	\N	\N	0.00	\N	Permanent	ASSIST PROF. IV	0	2022-06-21 10:36:47	\N
24	160	\N	\N	\N	0.00	\N	Permanent	SECONDARY SCHOOL TEACHER	0	2022-06-21 10:36:47	\N
25	160	\N	\N	\N	0.00	\N	Permanent	ASST. PROF. 1	0	2022-06-21 10:36:47	\N
26	168	\N	\N	\N	0.00	\N	Probationary	SANITATION SUPERVISOR	0	2022-06-21 10:36:47	\N
27	168	\N	\N	\N	0.00	\N	Permanent	COLLEGE RESEARCH COORDINATOR	0	2022-06-21 10:36:47	\N
28	168	\N	\N	\N	0.00	\N	Contractual	TEACHER 1	0	2022-06-21 10:36:47	\N
29	168	\N	\N	\N	0.00	\N	Permanent	PROFESSOR VI	0	2022-06-21 10:36:47	\N
30	178	\N	\N	\N	0.00	\N	Probationary	INSTRUCTOR II	0	2022-06-21 10:36:47	\N
31	178	\N	\N	\N	0.00	\N	Permanent	ASSISTANT PROFESSOR IV	0	2022-06-21 10:36:47	\N
32	197	\N	\N	\N	0.00	\N	Permanent	ASSISTANT PROF. IV	0	2022-06-21 10:36:47	\N
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
1	11	79.86	\N	Cagayan de Oro City	\N	\N	2022-06-21 09:33:48	\N	33
2	21	0.82	\N	Iligan City	\N	\N	2022-06-21 09:33:48	\N	2
3	28	0.00	\N	Iligan City	\N	\N	2022-06-21 09:33:48	\N	4
4	28	0.00	\N	Pagadian City, Zamboanga del Sur	\N	\N	2022-06-21 09:33:48	\N	5
5	34	0.00	\N		\N	\N	2022-06-21 09:33:48	\N	23
6	44	0.00	\N		\N	\N	2022-06-21 09:33:48	\N	28
7	54	0.00	\N	Iligan City	1.6151e+014	\N	2022-06-21 09:33:48	\N	20
8	54	0.00	\N	Iligan City	104146	\N	2022-06-21 09:33:48	\N	1
9	98	0.00	\N	Cagayan de Oro City	\N	\N	2022-06-21 09:33:48	\N	16
10	118	85.95	\N	2nd Place	\N	\N	2022-06-21 09:33:48	\N	31
11	120	0.70	\N	Iligan City	\N	\N	2022-06-21 09:33:48	\N	24
12	120	83.87	\N	Cagayan de Oro City	\N	\N	2022-06-21 09:33:48	\N	6
13	127	73.46	\N	Iligan City	\N	\N	2022-06-21 09:33:48	\N	11
14	131	0.71	\N	Iligan City	\N	\N	2022-06-21 09:33:48	\N	24
15	132	81.00	\N	Cebu City	\N	\N	2022-06-21 09:33:48	\N	19
16	134	0.00	\N	Manila	\N	\N	2022-06-21 09:33:48	\N	26
17	135	85.00	\N	Manila	44717	\N	2022-06-21 09:33:48	\N	10
18	141	0.00	\N		78417	\N	2022-06-21 09:33:48	\N	27
19	142	0.00	\N	Cagayan de Oro City	524417	\N	2022-06-21 09:33:48	\N	22
20	142	89.00	\N	Cagayan de Oro City	\N	\N	2022-06-21 09:33:48	\N	13
21	145	82.04	\N	Manila	56957	\N	2022-06-21 09:33:48	\N	8
22	148	86.80	\N	MANILA, PHILIPPINES	33900	\N	2022-06-21 09:33:48	\N	9
23	148	88.60	\N	MANILA, PHILIPPINES	33900	\N	2022-06-21 09:33:48	\N	9
24	150	0.00	\N	Manila	\N	\N	2022-06-21 09:33:48	\N	30
25	157	0.00	\N		\N	\N	2022-06-21 09:33:48	\N	7
26	157	0.00	\N		\N	\N	2022-06-21 09:33:48	\N	12
27	157	0.00	\N		\N	\N	2022-06-21 09:33:48	\N	21
28	157	76.00	\N	Tacloban City	\N	\N	2022-06-21 09:33:48	\N	17
29	168	0.00	\N	MSU-IIT, Iligan City	\N	\N	2022-06-21 09:33:48	\N	14
30	168	0.00	\N	UP Diliman, Quezon City	\N	\N	2022-06-21 09:33:48	\N	18
31	168	80.10	\N	Iligan City	\N	\N	2022-06-21 09:33:48	\N	25
32	178	78.75	\N	Davao City	986	\N	2022-06-21 09:33:48	\N	15
33	178	80.52	\N	Iligan City	\N	\N	2022-06-21 09:33:48	\N	3
34	192	0.00	\N		\N	\N	2022-06-21 09:33:48	\N	29
35	197	0.83	\N	Manila	3089	\N	2022-06-21 09:33:48	\N	32
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
1	11	American Society Of Agricultural Engineers	2022-06-21 10:13:26	\N
2	11	Msu Fastballers Club	2022-06-21 10:13:26	\N
3	11	Philippine Society Of Agricultural Engineers	2022-06-21 10:13:26	\N
4	21	Psas, Msu-Shs	2022-06-21 10:13:26	\N
5	28	Msuaa, Upaa,  Association Of Philippine Agriculturist	2022-06-21 10:13:26	\N
6	37	Gamma Sigma Delta Honor Society Uplb Chapter	2022-06-21 10:13:26	\N
7	37	Philippine Association Of Entomologists, Inc (Pae)	2022-06-21 10:13:26	\N
8	65	Buklod Mpc	2022-06-21 10:13:26	\N
9	65	Fmaa	2022-06-21 10:13:26	\N
10	65	Order Of The Eastern Star (Oes)	2022-06-21 10:13:26	\N
11	65	Sac-Jp	2022-06-21 10:13:26	\N
12	65	Secular Franciscan Order (Ofs)	2022-06-21 10:13:26	\N
13	66	Josenians, Inc. 1999	2022-06-21 10:13:26	\N
14	66	Karancho, Inc., Manticao Chapter 2009	2022-06-21 10:13:26	\N
15	66	Msu Alumni Association 1986	2022-06-21 10:13:26	\N
16	66	Msu Faculty Alumni Association 1986	2022-06-21 10:13:26	\N
17	66	Tau  Delta Phi Frat/Sor 1910 Gamma Rho Chapter	2022-06-21 10:13:26	\N
18	98	Philippine Association For Teacher Education (Pafte - Armm)	2022-06-21 10:13:26	\N
19	98	Philippine Educational Measurement & Evaluation Association (Pemea)	2022-06-21 10:13:26	\N
20	98	Philippine Home Economics Association (Phea)	2022-06-21 10:13:26	\N
21	98	Philippines - Australia Alumni Association, Inc (Pa3I)	2022-06-21 10:13:26	\N
22	120	Gamma Sigma Phi	2022-06-21 10:13:26	\N
23	120	Msu-Main Alumni Associatio	2022-06-21 10:13:26	\N
24	120	Msu-Reguard	2022-06-21 10:13:26	\N
25	127	Mindanao State University Karate Club	2022-06-21 10:13:26	\N
26	127	Msu Skylarks	2022-06-21 10:13:26	\N
27	132	Institute Of Electrical And Electronics Engineers (Ieee)	2022-06-21 10:13:26	\N
28	132	Philippine Society Of Mechanical Engineers (Psme)	2022-06-21 10:13:26	\N
29	132	Society Of Intrumentation And Control Engineers (Sice)	2022-06-21 10:13:26	\N
30	134	Gamma Phi Omicro	2022-06-21 10:13:26	\N
31	135	Philippine Institute Of Civil Engineers	2022-06-21 10:13:26	\N
32	141	Philippine Institute Of Civil Engineers	2022-06-21 10:13:26	\N
33	142	International Automotive Technicia'S Network (Iatn)	2022-06-21 10:13:26	\N
34	145	Beta Sigma Frat, Pice	2022-06-21 10:13:26	\N
35	150	Geodetic Engineer'S Of The Philippines	2022-06-21 10:13:26	\N
36	150	Geodetic Engineer'S Of The Philippines Inc.	2022-06-21 10:13:26	\N
37	153	Msu Faculty Unio	2022-06-21 10:13:26	\N
38	160	Msu-Faculty Unio	2022-06-21 10:13:26	\N
39	168	Aqra	2022-06-21 10:13:26	\N
40	168	Couples For Christ , Lanao Del Norte	2022-06-21 10:13:26	\N
41	168	Nrcp	2022-06-21 10:13:26	\N
42	168	Scei	2022-06-21 10:13:26	\N
43	185	Msu Alumni Associatio	2022-06-21 10:13:26	\N
44	191	Forestry Ecological Society	2022-06-21 10:13:26	\N
45	191	Msu Alumni Associatio	2022-06-21 10:13:26	\N
46	191	Society Of Filipino Foresters, Inc.	2022-06-21 10:13:26	\N
47	197	Roman Sigma Fraternity And Sorority	2022-06-21 10:13:26	\N
48	197	Society Of Filipino Foresters	2022-06-21 10:13:26	\N
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
1	11	American Society of Agricultural Engineers	\N	\N	0.00		2022-06-21 10:01:00	\N
2	11	MSU FASTBALLERS CLUB	\N	\N	0.00		2022-06-21 10:01:00	\N
3	11	Philippine Society of Agricultural Engineers	\N	\N	0.00		2022-06-21 10:01:00	\N
4	21	PSAS, MSU-SHS	\N	\N	0.00		2022-06-21 10:01:00	\N
5	28	MSUAA, UPAA,  Association of Philippine Agriculturist	\N	\N	0.00		2022-06-21 10:01:00	\N
6	37	Gamma Sigma Delta Honor Society UPLB Chapter	\N	\N	0.00		2022-06-21 10:01:00	\N
7	37	Philippine Association Of Entomologists, Inc (PAE)	\N	\N	0.00		2022-06-21 10:01:00	\N
8	65	Buklod MPC	\N	\N	0.00		2022-06-21 10:01:00	\N
9	65	FMAA	\N	\N	0.00		2022-06-21 10:01:00	\N
10	65	Order of the Eastern Star (OES)	\N	\N	0.00		2022-06-21 10:01:00	\N
11	65	SAC-JP	\N	\N	0.00		2022-06-21 10:01:00	\N
12	65	Secular Franciscan Order (OFS)	\N	\N	0.00		2022-06-21 10:01:00	\N
13	66	Josenians, Inc. 1999	\N	\N	0.00		2022-06-21 10:01:00	\N
14	66	KARANCHO, INC., MANTICAO CHAPTER 2009	\N	\N	0.00		2022-06-21 10:01:00	\N
15	66	MSU Alumni Association 1986	\N	\N	0.00		2022-06-21 10:01:00	\N
16	66	MSU Faculty Alumni Association 1986	\N	\N	0.00		2022-06-21 10:01:00	\N
17	66	tau  delta phi frat/sor 1910 gamma rho chapter	\N	\N	0.00		2022-06-21 10:01:00	\N
18	98	Philippine Association for Teacher Education (PAFTE - ARMM)	\N	\N	0.00		2022-06-21 10:01:00	\N
19	98	Philippine Educational Measurement & Evaluation Association (PEMEA)	\N	\N	0.00		2022-06-21 10:01:00	\N
20	98	Philippine Home Economics Association (PHEA)	\N	\N	0.00		2022-06-21 10:01:00	\N
21	98	Philippines - Australia Alumni Association, Inc (PA3i)	\N	\N	0.00		2022-06-21 10:01:00	\N
22	120	Gamma Sigma Phi	\N	\N	0.00		2022-06-21 10:01:00	\N
23	120	MSU-Main Alumni Associatio	\N	\N	0.00		2022-06-21 10:01:00	\N
24	120	MSU-REGUARD	\N	\N	0.00		2022-06-21 10:01:00	\N
25	127	Mindanao State University Karate Club	\N	\N	0.00		2022-06-21 10:01:00	\N
26	127	MSU Skylarks	\N	\N	0.00		2022-06-21 10:01:00	\N
28	132	Philippine Society of Mechanical Engineers (PSME)	\N	\N	0.00		2022-06-21 10:01:00	\N
29	132	Society of Intrumentation and Control Engineers (SICE)	\N	\N	0.00		2022-06-21 10:01:00	\N
30	134	Gamma Phi Omicro	\N	\N	0.00		2022-06-21 10:01:00	\N
31	135	Philippine Institute of Civil Engineers	\N	\N	0.00		2022-06-21 10:01:00	\N
32	141	Philippine Institute of Civil Engineers	\N	\N	0.00		2022-06-21 10:01:00	\N
33	142	International Automotive Technicia's Network (iATN)	\N	\N	0.00		2022-06-21 10:01:00	\N
34	145	Beta SIgma Frat, PICE	\N	\N	0.00		2022-06-21 10:01:00	\N
35	150	Geodetic Engineer's of the Philippines	\N	\N	0.00		2022-06-21 10:01:00	\N
36	150	Geodetic Engineer's of the Philippines Inc.	\N	\N	0.00		2022-06-21 10:01:00	\N
37	153	MSU Faculty Unio	\N	\N	0.00		2022-06-21 10:01:00	\N
38	160	MSU-Faculty Unio	\N	\N	0.00		2022-06-21 10:01:00	\N
39	168	AQRA	\N	\N	0.00		2022-06-21 10:01:00	\N
40	168	Couples for Christ , Lanao del Norte	\N	\N	0.00		2022-06-21 10:01:00	\N
41	168	NRCP	\N	\N	0.00		2022-06-21 10:01:00	\N
42	168	SCEI	\N	\N	0.00		2022-06-21 10:01:00	\N
43	185	MSU Alumni Associatio	\N	\N	0.00		2022-06-21 10:01:00	\N
44	191	Forestry Ecological Society	\N	\N	0.00		2022-06-21 10:01:00	\N
45	191	MSU Alumni Associatio	\N	\N	0.00		2022-06-21 10:01:00	\N
46	191	Society of Filipino Foresters, Inc.	\N	\N	0.00		2022-06-21 10:01:00	\N
47	197	Roman Sigma Fraternity and Sorority	\N	\N	0.00		2022-06-21 10:01:00	\N
48	197	Society of Filipino Foresters	\N	\N	0.00		2022-06-21 10:01:00	\N
27	132	Institute of Electrical and Electronics Engineers (IEEE)	\N	\N	0.00		2022-06-21 10:01:00	\N
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
1	21	Computer	2022-06-21 10:07:41	\N
2	98	Documenter	2022-06-21 10:07:41	\N
3	120	Industrial Management	2022-06-21 10:07:41	\N
4	120	Machinist, Welder, Physical Fitness	2022-06-21 10:07:41	\N
5	120	Machinist, Welders	2022-06-21 10:07:41	\N
6	127	Research	2022-06-21 10:07:41	\N
7	153	Invent And Innovate Electrical/Electronics Devices	2022-06-21 10:07:41	\N
8	157	Carpentry, Masonry,Tile Setting, Plumbing	2022-06-21 10:07:41	\N
9	192	Editing Written Articles	2022-06-21 10:07:41	\N
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
1	21	Group Training Course in Poultry Development	\N	\N	0.00	Colombo Plan-JICA	0	2022-06-21 09:52:40	\N
2	54	2019 Finance educators Training Program	\N	\N	20.00	Ateneo de Davao, CIBI, FINEX	0	2022-06-21 09:52:40	\N
3	54	Equipping Business Administration Faculty Members with 21st Century Pedagogical Skills and Competencies for Teaching Innovation and Creativity 	\N	\N	24.00	Miriam College, MSU-IIT, CHED	0	2022-06-21 09:52:40	\N
4	54	Model Canvass and Business Pitching Seminar and Workshop	\N	\N	10.00	DTI, MSU-IIT	0	2022-06-21 09:52:40	\N
5	54	Senior High School Teachers Training for HEIs in ARMM	\N	\N	40.00	CHED-ARMM	0	2022-06-21 09:52:40	\N
6	54	The 2nd SEAITE Symposium 2019	\N	\N	24.00	SUT, Coventry University, and NXPO, Bangkok, Thailand	0	2022-06-21 09:52:40	\N
7	54	Whole Child and 21st century Learning	\N	\N	30.00	REX Bookstore, CHED	0	2022-06-21 09:52:40	\N
8	98	National Training Programme for Teacher Educators on ICT-Pedagogy Integratio	\N	\N	24.00	UNESCO ASIA AND PACIFIC REGIONAL BUREAU FOR EDUCATION Asia-Pacific Programme of Educational Innovation for Development and UP-NISMED	0	2022-06-21 09:52:40	\N
9	118	Cisco	\N	\N	0.00		0	2022-06-21 09:52:40	\N
10	120	International Conference for Vocational and Technology 	\N	\N	0.00		0	2022-06-21 09:52:40	\N
11	120	INTERNATIONAL COST REDUCTIO	\N	\N	0.00	Internation Executive Management	0	2022-06-21 09:52:40	\N
12	127	Basic Occupational Safety and Health (BOSH) conducted at Tabe’s Place, Tibanga Iligan City 	\N	\N	40.00	MSRS Safety Health and Environment Training Services Inc./No Sponsor, personal initiative.	0	2022-06-21 09:52:40	\N
13	127	Construction Occupational Safety and Health (COSH) conducted at GTC, Iligan City	\N	\N	40.00	MSRS Safety Health and Environment Training Services Inc./No Sponsor, personal initiative.	0	2022-06-21 09:52:40	\N
14	127	PAGE 10 ANNUAL CONFERENCE 2015: Quality Graduate School Research as Response to ASEAN 2015 conducted at Pearlmont Hotel, Cagayan de Oro City	\N	\N	8.00	Philippine Association for Graduate Education (PAGE) Region 10/No sponsor, personal initiative.	0	2022-06-21 09:52:40	\N
15	127	Two-Day Seminar-Writeshop on Syllabus Design conducted at MSU Main Campus, Marawi City 	\N	\N	16.00	Office of the President/Office for Vice Chancellor for Academic Affairs	0	2022-06-21 09:52:40	\N
16	142	Auto Trainors Dev Prog II	\N	\N	140.00	Technical Eduations Skills Dev Authority (Region-10)	0	2022-06-21 09:52:40	\N
17	142	Competency Assessment Dev Program	\N	\N	100.00	Technical Education Skills Dev Authority (Region-12)	0	2022-06-21 09:52:40	\N
18	168	15th Mindanao Regional Cluster Gen, Membership assembly	\N	\N	8.00	NRCP	0	2022-06-21 09:52:40	\N
19	168	DRR Training Workshop	\N	\N	\N	PCHRD	0	2022-06-21 09:52:40	\N
20	168	DRR Training Workshop Phase 2	\N	\N	\N	PCHRD	0	2022-06-21 09:52:40	\N
21	168	DRR Training Workshop Phase 3	\N	\N	\N	PCHRD	0	2022-06-21 09:52:40	\N
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
4		10001	10001	0	Roland	Cinco	Almorado	0	Alicia, Zamboanga Sibugay	\N	0	0	0.00	74.00	10001@	09209690270/09177210928		1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	10	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	4							0	0							\N	\N	0	0	0	0
5		10002	10002	0	Mangompia	Umba	Angod	0	\N	\N	0	0	0.00	0.00	10002@			1	1	0	f	f	f		0					0					0				0	0				0	0				0				0	0	10	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
6		10003	10003	0	Mariano	P	Badinas	0	\N	\N	0	0	0.00	0.00	10003@			1	1	0	f	f	f		0					0					0				0	0				0	0				0				0	0	10	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
7		10004	10004	0	Dimalutang	T	Bangcola	0	\N	\N	0	0	0.00	0.00	10004@		233-7910	1	1	0	f	f	f		0					0					0				0	0				0	0				0				0	0	10	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
8		10005	10005	0	Rosa	Valdez	Bantuas	0	Butuan City	\N	0	0	0.00	0.00	10005@	09175484455		1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	10	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	1							0	0							\N	\N	0	0	0	0
9		10006	10006	0	Simbanatao	S	Batuga	0	\N	\N	0	0	0.00	0.00	10006@		520-665	1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	10	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
10		10007	10007	0	Renato	D	Boniao	0	\N	\N	0	0	0.00	0.00	10007@			1	1	0	f	f	f		0					0					0				0	0				0	0				0				0	0	10	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
11		10008	10008	0	Julia	Fernandez	Cacho	0	Pongtod, San Agustin, Surigao Del Sur	\N	0	0	0.00	0.00	10008@	09067306614		1	2	0	f	f	f		0					0					0	Alejandro	P	Cacho	0	0	Elcita	A	Fernandez	0	0	Meraluna	C	Cacho	0	Clinical Instructor			0	0	10	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
12		10009	10009	0	Amer	I	Comadug	0	\N	\N	0	0	0.00	0.00	10009@			1	1	0	f	f	f		0					0					0				0	0				0	0				0				0	0	10	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
13		10010	10010	0	Remedios	Rapirap	Anub	0	Tambulig, Zamboanga Del Sur	\N	0	0	0.00	74.00	10010@	09494865276		1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	10	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	8							0	0							\N	\N	0	0	0	0
14		10011	10011	0	Pacita	D	Dayao	0	Marawi City	\N	0	0	0.00	0.00	10011@	09269928685		1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	10	0	0	0	0	f	f	t	f	\N	1.48312e+008	B49X6PDD017				0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
15		10012	10012	0	Cosai	M	Derico	0	Lumbatan Lanao Del Sur	\N	0	0	0.00	0.00	10012@	09994416729		1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	10	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
16		10013	10013	0	Policarpio	V	Dingal	0	Sto. Niño, Lloy, Zamboanga Del Norte	\N	0	0	0.00	0.00	10013@		(063) 221-2442	1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	10	0	0	0	0	f	f	t	f	\N	0	CM 00003103997				0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
17		10014	10014	0	Clenio	L	Dumlao	0	\N	\N	0	0	0.00	0.00	10014@		(063) 516-108	1	1	0	f	f	f		0					0					0				0	0				0	0				0				0	0	10	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
18		10015	10015	0	Liza	L	Ebuna	0	\N	\N	0	0	0.00	0.00	10015@		(063) 223-6565	1	1	0	f	f	f		0					0					0				0	0				0	0				0				0	0	10	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
19		10016	10016	0	Francisco	R	Escalante	0	\N	\N	0	0	0.00	0.00	10016@			1	1	0	f	f	f		0					0					0				0	0				0	0				0				0	0	10	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
20		10017	10017	0	Rosa Villa	Barriga	Estoista	0	Samal, Davao Del Norte	\N	0	0	0.00	60.00	10017@		(063) 225-5275	1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	10	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	9							0	0							\N	\N	0	0	0	0
21		10018	10018	0	Elenita	C	Gay	0	Lanao, Kidapawan City, N. Cotabato	\N	0	0	0.00	59.00	10018@			1	2	0	f	f	f		0					0					0	Ceferino	E	Cagayao	0	0	Concepcio		Pajaro	0	0				0				0	0	10	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	8							0	0							\N	\N	0	0	0	0
22		10019	10019	0	Bernardita	L	Calipusa	0	San Pablo, Zamboanga Del Sur	\N	0	0	0.00	0.00	10019@	09128837120		1	1	0	f	f	f		0					0					0				0	0				0	0				0				0	0	10	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
23		10020	10020	0	Nida	Abarquez	Ilupa	0	Marawi City	\N	0	0	0.00	60.00	10020@	09207236194		1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	10	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
24		10021	10021	0	Fatima	Abdulhamid	Khalid	0	Kumalarang, Basila	\N	0	0	0.00	0.00	10021@	09182154046		1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	10	0	0	0	0	f	f	t	f	\N	9.35125e+007	B54V9FAK016				0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
25		10022	10022	0	Bagani	Saromantang	Macabalang	0	Kialdan, Marantao, Lanao Del Sur	\N	0	0	0.00	0.00	10022@	09173332721		1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	10	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
26		10023	10023	0	Pangandag	M	Magolama	0	\N	\N	0	0	0.00	0.00	10023@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	10	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
27		10025	10025	0	Camar	S	Mikunug	0	\N	\N	0	0	0.00	0.00	10025@			1	1	0	f	f	f		0					0					0				0	0				0	0				0				0	0	10	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
28		10026	10026	0	Edwi	Chua	Mituda	0	Concepcion, Tabina, Zamboanga Del Sur	\N	0	0	0.00	60.00	10026@	0920858866		1	2	0	f	f	f		0					0					0	Alejandro	Q	Mituda	0	0	Fe	Pagute	Chua	0	0	Ailee		Castellano-Mituda	0	GovernmentEmployee			0	0	10	0	0	0	0	f	f	t	f	\N	0			12050013910	15-000035049-6	0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	8							0	0							\N	\N	0	0	0	0
29		10027	10027	0	Sunnie	Cleme	Noel	0	Daanglunsod, Katipunan, Zamboanga Del Norte	\N	0	0	0.00	80.00	10027@	09185115174		1	1	0	f	f	f		0					0					0				0	0				0	0				0				0	0	10	0	0	0	0	f	f	t	f	\N	0			1950 0026 5622		0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	8							0	0							\N	\N	0	0	0	0
30		10028	10028	0	Rasid	Mangco	Paca	0	Baloi, Lanao Del Norte	\N	0	0	0.00	0.00	10028@	09102472169		1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	10	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
31		10029	10029	0	Rogelio	Lucero	Pagariga	0	Camaling, Tarlac	\N	0	0	0.00	0.00	10029@	09197910851		1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	10	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
32		10030	10030	0	Artemio	Antiquina	Perez	0	Mahayag, Zamboanga Del Sur	\N	0	0	0.00	0.00	10030@	0492494281		1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	10	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
33		10031	10031	0	Celeste		Querijero	0	Bacolod.Libon .Albay	\N	0	0	0.00	0.00	10031@	09475698098		1	0	0	f	f	f		0					0					0				0	0				0	0				0				0	0	10	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
34		10032	10032	0	Warlita	Repole	Laborada	0	Molave, Zamboanga Del Sur	\N	0	0	0.00	55.00	10032@			1	2	0	f	f	f		0					0					0	Esteba	M	Repole	0	0	Julieta	Zanoria	Olila	0	0	Gezer	R	Laborada	0	Businessma			0	0	10	0	0	0	0	f	f	t	f	\N	0			1.2021252721e+011	14-200714514-3	0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	8							0	0							\N	\N	0	0	0	0
35		10033	10033	0	Saira	Paca	Ringia	0	Baloi, Ld	\N	0	0	0.00	0.00	10033@	09205137989		1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	10	0	0	0	0	f	f	t	f	\N	0	354H55PR016				0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
36		10034	10034	0	Mario	E	Rodriguez	0	\N	\N	0	0	0.00	0.00	10034@		223-9330	1	1	0	f	f	f		0					0					0				0	0				0	0				0				0	0	10	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
37		10035	10035	0	Emma	Mituda	Sabado	0	Tabina, Zamboanga Del Sur	\N	0	0	5.00	78.00	10035@	09088949242		1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	10	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	6							0	0							\N	\N	0	0	0	0
38		10036	10036	0	Gerlie	Salvador	Jambaro	0	Rivera, Ibajay, Akla	\N	0	0	0.00	0.00	10036@	09359109159		1	2	0	f	f	f		0					0					0				0	0				0	0	Ruffy	A	Jambaro	0				0	0	10	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	3							0	0							\N	\N	0	0	0	0
39		10037	10037	0	Joseph Jr	M	Sanguila	0	Kauswagan, Ld	\N	0	0	0.00	0.00	10037@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	10	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
40		10038	10038	0	Arthur	Ituriaga	Tambong	0		\N	0	0	0.00	0.00	10038@			1	0	0	f	f	f		0					0					0				0	0				0	0				0				0	0	10	0	0	0	0	f	f	t	f	\N	0	CM 3121582				0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
41		10039	10039	0	Erlinda	Plaza	Tatil	0	Cantilan, Surigao Del Sur	\N	0	0	0.00	0.00	10039@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	10	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
42		10040	10040	0	Hernie	C	Tiamting	0	Pagadian City	\N	0	0	0.00	0.00	10040@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	10	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
43		10041	10041	0	Jane	Bagnol	Tranquila	0	\N	\N	0	0	0.00	0.00	10041@			1	1	0	f	f	f		0					0					0				0	0				0	0				0				0	0	10	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
44		10042	10042	0	Jesse Jay	Orellana	Villanueva	0	Marawi City	\N	0	0	0.00	64.00	10042@	09567633872		1	1	0	f	f	f		0					0					0	Rodolfo Sr.	M	Villanueva	0	0	Arline Fe	O	Villanueva	0	0				0	n/a			0	0	10	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	8							0	0							\N	\N	0	0	0	0
45		10043	10043	0	Melgie Jeoffrey	Apat	Alas	0	Oroquieta City	\N	0	0	0.00	0.00	10043@	09205054888		1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	11	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
46		10044	10044	0	Griselda	Zerna	Albao	0	\N	\N	0	0	0.00	0.00	10044@			1	0	0	f	f	f		0					0					0				0	0				0	0				0				0	0	11	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
47		10045	10045	0	Salic	Somalug	Amer	0	Balindong, Lanao Del Sur	\N	0	0	0.00	0.00	10045@		(063) 221-785	1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	11	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
48		10046	10046	0	Imara	Campong	Andam	0	Iligan City	\N	0	0	0.00	0.00	10046@	09192349229		1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	11	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
49		10047	10047	0	Ernilda	Villanueva	Angod	0	Sampaloc, Manila	\N	0	0	0.00	0.00	10047@			1	2	0	f	f	f		0					0					0				0	0				0	0	Mangompia	U	Ungod	0				0	0	11	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
50		10049	10049	0	Perla	Manzano	Calunod	0	\N	\N	0	0	0.00	0.00	10049@			1	1	0	f	f	f		0					0					0				0	0				0	0				0				0	0	11	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
51		10050	10050	0	Renato	C	Dela Calzada	0	\N	\N	0	0	0.00	0.00	10050@			1	1	0	f	f	f		0					0					0				0	0				0	0				0				0	0	11	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
52		10051	10051	0	Maria Stella	G	Codas	0	Ozamiz City	\N	0	0	0.00	0.00	10051@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	11	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
53		10052	10052	0	Virgie	Magtahas	Dal	0	\N	\N	0	0	0.00	0.00	10052@			1	1	0	f	f	f		0					0					0				0	0				0	0				0				0	0	11	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
54		10053	10053	0	Anna Mitos	Jimenez	Dizo	0	Marawi City	\N	0	0	0.00	0.00	10053@	063-302-0818		1	2	0	f	f	f		0					0					0	Andres	Valdez	Jimenez	0	0	Exaltacio	Tunog	Urba	0	0	Leovino	E	Dizo	0	Teaching			0	0	11	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	1							0	0							\N	\N	0	0	0	0
55		10054	10054	0	Mary Fe	Escalona	Egam	0	Valderrama, Antique	\N	0	0	5.00	65.00	10054@	09228267892		1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	11	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	8							0	0							\N	\N	0	0	0	0
56		10055	10055	0	Bolawa	Magarang	Galawa	0	Marawi City	\N	0	0	0.00	0.00	10055@	09998842739		1	1	0	f	f	f		0					0					0				0	0				0	0				0				0	0	11	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
57		10056	10056	0	Maria Elena	Reyes	Garces-Burrill	0	Lucena City, Quezo	\N	0	0	0.00	0.00	10056@		(063) 221-7519	1	1	0	f	f	f		0					0					0				0	0				0	0				0				0	0	11	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
58		10057	10057	0	Maria Gemma The.		Gavine	0	\N	\N	0	0	0.00	0.00	10057@			1	1	0	f	f	f		0					0					0				0	0				0	0				0				0	0	11	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
59		10058	10058	0	Marily	U	Gloria	0	\N	\N	0	0	0.00	0.00	10058@			1	1	0	f	f	f		0					0					0				0	0				0	0				0				0	0	11	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
60		10059	10059	0	Damiana	Deloso	Goles	0	\N	\N	0	0	0.00	0.00	10059@			1	1	0	f	f	f		0					0					0				0	0				0	0				0				0	0	11	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
61		10060	10060	0	Oscar	Labano	Gonzaga	0	Sindangan, Zamboanga Del Norte	\N	0	0	0.00	0.00	10060@		(063) 221-46-82	1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	11	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
62		10061	10061	0	Angelina	Camiguing	Igos	0	\N	\N	0	0	0.00	0.00	10061@			1	1	0	f	f	f		0					0					0				0	0				0	0				0				0	0	11	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
63		10062	10062	0	Alisa Normita		Macawaris	0	\N	\N	0	0	0.00	0.00	10062@			1	1	0	f	f	f		0					0					0				0	0				0	0				0				0	0	11	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
64		10063	10063	0	Naima	D.	Mala	0	Marawi City	\N	0	0	0.00	0.00	10063@			1	1	0	f	f	f		0					0					0				0	0				0	0				0				0	0	16	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
65		10064	10064	0	Corazon Concepcio	Mondaya	Mangele	0	Cebu City	\N	0	0	0.00	70.00	10064@	09184311031/09171504021		1	4	0	f	f	f		0					0					0	Manolito	E	Mondaya	0	0	Socorro	C	Gutilba	0	0	Didu	Z	Mangele	0	N/A			0	0	11	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	7							0	0							\N	\N	0	0	0	0
66		10065	10065	0	Eugenio Jr	Justalero	Manulat	0	Davao City	\N	0	0	0.00	165.00	10065@	09217354818/09392051661		1	2	0	f	f	f		0					0					0	Eugenio Sr.	Y	Manulat	0	0	Estelita	G	Justalero	0	0	Fe	M	Manulat	0	Academic Staff			0	0	11	0	0	0	0	f	f	t	f	\N	0			1205 001269 01	15-000034059-8	0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	1							0	0							\N	\N	0	0	0	0
67		10066	10066	0	Flora	T	Manulat	0	\N	\N	0	0	0.00	0.00	10066@			1	1	0	f	f	f		0					0					0				0	0				0	0				0				0	0	11	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
68		10067	10067	0	Rhede Nelso	Justalero	Manulat	0	\N	\N	0	0	0.00	0.00	10067@		(063) 221-8688	1	1	0	f	f	f		0					0					0				0	0				0	0				0				0	0	11	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
69		10068	10068	0	Alcasar	Pacalna	Maongco	0	Tugaya, Lanao Del Sur	\N	0	0	0.00	0.00	10068@	09063818481		1	2	0	f	f	f		0					0					0				0	0				0	0	Sanawia	Y	Adap-Maongco	0	Government Employee			0	0	11	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	7							0	0							\N	\N	0	0	0	0
70		10069	10069	0	Papala	Pangadapu	Masorong	0	Marawi City	\N	0	0	0.00	0.00	10069@			1	2	0	f	f	f		0					0					0	Acmad		Pangadapu	0	0	Amina		Hadjiadato	0	0	Rashdi	P	Masorong	0	Chief Engineer			0	0	11	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
71		10070	10070	0	Ede Paul		Miano	0	\N	\N	0	0	0.00	0.00	10070@			1	1	0	f	f	f		0					0					0				0	0				0	0				0				0	0	11	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
72		10071	10071	0	Macacuna	A	Moslem	0	\N	\N	0	0	0.00	0.00	10071@			1	1	0	f	f	f		0					0					0				0	0				0	0				0				0	0	19	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
73		10072	10072	0	Omar Sharief	Mambuay	Naga	0	Msu Marawi City	\N	0	0	0.00	0.00	10072@			1	1	0	f	f	f		0					0					0				0	0				0	0				0				0	0	11	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
74		10073	10073	0	Vincent Marc	Ligad	Palomares	0	Caloocan City	\N	0	0	0.00	0.00	10073@			1	1	0	f	f	f		0					0					0				0	0				0	0				0				0	0	18	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
75		10074	10074	0	Potre	Pangadapu	Riga	0	\N	\N	0	0	0.00	0.00	10074@			1	1	0	f	f	f		0					0					0				0	0				0	0				0				0	0	11	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
76		10076	10076	0	Marlene	Hofer	Tamano	0	\N	\N	0	0	0.00	0.00	10076@			1	1	0	f	f	f		0					0					0				0	0				0	0				0				0	0	11	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
77		10077	10077	0	Melanie	Ali	Umpa	0	\N	\N	0	0	0.00	0.00	10077@			1	1	0	f	f	f		0					0					0				0	0				0	0				0				0	0	11	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
78		10078	10078	0	Merlyn	Sijo	Ta	0	\N	\N	0	0	0.00	0.00	10078@			1	1	0	f	f	f		0					0					0				0	0				0	0				0				0	0	11	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
79		10080	10080	0	Dayang	Pangandama	Ali	0	Batangan, Bubong, Lanao Del Sur	\N	0	0	0.00	0.00	10080@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	12	0	0	0	0	f	f	t	f	\N	0	85DK2DPA028				0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
80		10081	10081	0	Mary	Samad	Ambor	0	\N	\N	0	0	0.00	0.00	10081@			1	1	0	f	f	f		0					0					0				0	0				0	0				0				0	0	12	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
81		10082	10082	0	Raga	Manonggiring	Bacarat	0	Taraka, Lanao Del Sur	\N	0	0	0.00	0.00	10082@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	12	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
82		10083	10083	0	Leling	Ongkay	Basma	0	\N	\N	0	0	0.00	0.00	10083@			1	1	0	f	f	f		0					0					0				0	0				0	0				0				0	0	12	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
83		10084	10084	0	Arlene	Alegado	Botones	0	Lagang, Carcar, Cebu	\N	0	0	0.00	0.00	10084@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	12	0	0	0	0	f	f	t	f	\N	1.44386e+008	59012602370				0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
84		10085	10085	0	Mindaya	Macapodi	Cali	0	Marawi City	\N	0	0	0.00	0.00	10085@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	12	0	0	0	0	f	f	t	f	\N	0	B56TJMMCOIT				0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
85		10086	10086	0	Virginia	Lagarto	Chua	0	Leyte	\N	0	0	0.00	0.00	10086@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	12	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
86		10087	10087	0	Abolais	B	Deca	0	\N	\N	0	0	0.00	0.00	10087@			1	1	0	f	f	f		0					0					0				0	0				0	0				0				0	0	12	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
87		10088	10088	0	Nainobai	D	Disomangcop	0	Pantar, Baloi, Lanao Del Norte	\N	0	0	0.00	0.00	10088@			1	1	0	f	f	f		0					0					0				0	0				0	0				0				0	0	12	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
88		10089	10089	0	Teodoro	Matura	Drilo	0	Cotabato City	\N	0	0	0.00	0.00	10089@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	12	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
89		10090	10090	0	Edgar Alvi	Garcia	Flores	0	Msu Campus, Marawi City	\N	0	0	0.00	0.00	10090@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	12	0	0	0	0	f	f	t	f	\N	0	4.4045276297e+015				0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
90		10091	10091	0	Erlinda	Mondoy	Gatab	0	\N	\N	0	0	0.00	0.00	10091@			1	1	0	f	f	f		0					0					0				0	0				0	0				0				0	0	12	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
91		10092	10092	0	Pendililang	Balangue	Gunting	0	Lumbatan, Lanao Del Sur	\N	0	0	0.00	0.00	10092@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	12	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
92		10093	10093	0	Aida Hafiza	Madale	Macada-Ag	0	Tuca, Marawi City	\N	0	0	0.00	0.00	10093@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	12	0	0	0	0	f	f	t	f	\N	1.44386e+008	50080602356				0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
93		10094	10094	0	Potresam	Pangandama	Macaraya	0	Banggolo, Marawi City	\N	0	0	0.00	0.00	10094@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	12	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
94		10095	10095	0	Asanga	Tima	Madale	0	\N	\N	0	0	0.00	0.00	10095@			1	1	0	f	f	f		0					0					0				0	0				0	0				0				0	0	12	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
95		10096	10096	0	Ibrahim	Desuma	Mangondato	0	Cawayan, Marantao, Lds.	\N	0	0	0.00	0.00	10096@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	12	0	0	0	0	f	f	t	f	\N	9.38346e+008	3130618				0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
96		10097	10097	0	Solomo	U.	Molina	0	\N	\N	0	0	0.00	0.00	10097@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	12	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
97		10098	10098	0	Hadji Rocaya	Mambuay	Naga	0	Saduc, Marawi City	\N	0	0	0.00	0.00	10098@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	12	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	8							0	0							\N	\N	0	0	0	0
98		10099	10099	0	Minerva Saminah	Mambuay	Naga	0	Marawi City	\N	0	0	0.00	0.00	10099@			1	2	0	f	f	f		0					0					0	Pipalawa	O	Naga	0	0	Hadji Rocaya	Naga	Mambuay	0	0	Gamal Apunung	Gandarosa	Laguindab	0	Project Secretary			0	0	12	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	1							0	0							\N	\N	0	0	0	0
99		10100	10100	0	Erlinda	De Los Santos	Ola-Casa	0	Manila	\N	0	0	0.00	0.00	10100@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	12	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
100		10101	10101	0	Herminia	Nepumoceno	Olandesca	0	\N	\N	0	0	0.00	0.00	10101@			1	1	0	f	f	f		0					0					0				0	0				0	0				0				0	0	12	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
101		10102	10102	0	Racma	Usuda	Pacasum	0	Marawi City	\N	0	0	0.00	0.00	10102@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	12	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
102		10104	10104	0	Lolita	D	Rodriguez	0	\N	\N	0	0	0.00	0.00	10104@			1	1	0	f	f	f		0					0					0				0	0				0	0				0				0	0	12	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
103		10105	10105	0	Azucena	Alicio	Ruiz	0	Cataingan, Masbate	\N	0	0	0.00	0.00	10105@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	12	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
104		10106	10106	0	Mariam	D	Ditucalan-Salic	0	\N	\N	0	0	0.00	0.00	10106@			1	1	0	f	f	f		0					0					0				0	0				0	0				0				0	0	12	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
105		10108	10108	0	Maimona		Sumpinga	0	\N	\N	0	0	0.00	0.00	10108@			1	1	0	f	f	f		0					0					0				0	0				0	0				0				0	0	12	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
106		10109	10109	0	Sapia	Ali	Umpa	0	Marawi City	\N	0	0	0.00	0.00	10109@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	12	0	0	0	0	f	f	t	f	\N	0	54061903138				0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
107		10110	10110	0	Lumna		Zama	0	\N	\N	0	0	0.00	0.00	10110@			1	1	0	f	f	f		0					0					0				0	0				0	0				0				0	0	12	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
108		10111	10111	0	Lotis	Alap-Ap	Baoc	0	Dalipuga, Iligan City	\N	0	0	0.00	0.00	10111@			1	1	0	f	f	f		0					0					0				0	0				0	0				0				0	0	12	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
109		10112	10112	0	Wardah	Dirampatu	Guimba	0	Msu, Marawi City	\N	0	0	0.00	0.00	10112@			1	0	0	f	f	f		0					0					0	Mohammad	Guimba	Poinga	0	0	Cadidia	Dirampatu	Poinga	0	0				0				0	0	12	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
110		10113	10113	0	Perfecto Sr	T.	Abanera	0	\N	\N	0	0	0.00	0.00	10113@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	12	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
111		10114	10114	0	Feliciano	Braza	Alagao	0	\N	\N	0	0	0.00	0.00	10114@			1	1	0	f	f	f		0					0					0				0	0				0	0				0				0	0	13	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
112		10115	10115	0	Florencia	Villeguez	Alagao	0	Koronadal City	\N	0	0	0.00	0.00	10115@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	13	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
113		10116	10116	0	Romeo	Sale	Aldonza	0	Guiunan Samar	\N	0	0	0.00	0.00	10116@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	13	0	0	0	0	f	f	t	f	\N	0	CM-3121818				0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
114		10117	10117	0	Abdullah	Dangcal	Alonto	0	Lolong, Marawi City	\N	0	0	0.00	0.00	10117@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	13	0	0	0	0	f	f	t	f	\N	0	B49AGADA014				0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
115		10118	10118	0	Charlita	Morata	Amparado	0	Del Carmen, Surigao Del Norte	\N	0	0	0.00	0.00	10118@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	13	0	0	0	0	f	f	t	f	\N	0	B56Z7CMA019				0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
116		10119	10119	0	Romeo	Lim	Asibal	0	Gen.San City	\N	0	0	0.00	0.00	10119@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	13	0	0	0	0	f	f	t	f	\N	0	4404-5276-3117-647				0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
117		10120	10120	0	Rodrigo	Sugabo	Baid	0	Dipolog City	\N	0	0	0.00	62.00	10120@			1	2	0	f	f	f		0					0					0	Cresencio	Andag	Baid	0	0	Albertona	Jumalo	Sugabo	0	0	Mildred		Icalina-Baid	0	DepEd Teacher			0	0	13	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	8							0	0							\N	\N	0	0	0	0
118		10121	10121	0	Erwi	Tibor	Balangao	0	Lopes Jaena, Mis. Occ.	\N	0	0	0.00	0.00	10121@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	13	0	0	0	0	f	f	t	f	\N	0	CM-5909998		1950-0025-3944	19-000828412-5	0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
119		10122	10122	0	Tailisa	Totoda	Barosa	0	Balabagan, Lanao Del Sur	\N	0	0	0.00	0.00	10122@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	13	0	0	0	0	f	f	t	f	\N	0	3121282				0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
120		10123	10123	0	Sambitory	Ali	Bazar	0	Cormatan, Madalum, Lanao Del Sur	\N	0	0	0.00	60.00	10123@			1	2	0	f	f	f		0					0					0	Bazar		Alapa	0	0	Ditopor	Ali	Bazar	0	0	Bedorie	M	Bazar	0	Housewife			0	0	13	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	8							0	0							\N	\N	0	0	0	0
121		10124	10124	0	Jacinto	A	Belotindos	0	Odiongan, Tanjay Negros Oriental	\N	0	0	0.00	113.00	10124@			1	2	0	f	f	f		0					0					0	Jesus		Belotindos	0	0	Eufracia		Aguilar	0	0	Charly	S	Belotindos	0	Businesswoma			0	0	13	0	0	0	0	f	f	t	f	\N	0	CM-3187143				0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	1							0	0							\N	\N	0	0	0	0
122		10125	10125	0	Brenda	Gotostos	Cabanilla	0	Dapa, Surigao Del Norte	\N	0	0	0.00	57.00	10125@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	13	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	8							0	0							\N	\N	0	0	0	0
123		10126	10126	0	Henry	Birao	Cabanilla	0	Baluarte, Tagoloan, Misamis Oriental	\N	0	0	0.00	0.00	10126@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	13	0	0	0	0	f	f	t	f	\N	0	B57US-HBC-01-2				0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
124		10127	10127	0	Ernesto	G	Caluza	0	Naguilian, La Unio	\N	0	0	0.00	0.00	10127@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	13	0	0	0	0	f	f	t	f	\N	0	CM-3119450				0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
125		10128	10128	0	Gregor	Cape	Castillano	0	Noralah, South Cotabato	\N	0	0	0.00	0.00	10128@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	13	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
126		10129	10129	0	Jose Jr.	Rubenecia	Congreso	0	Las Navas, Northern Samar	\N	0	0	0.00	0.00	10129@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	13	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
127		10130	10130	0	Chona	Documento	Cuizo	0	Centro Sinacaban, Misamis Occidental	\N	0	0	0.00	52.00	10130@			1	2	0	f	f	f		0					0					0	Emilio	Docor	Documento	0	0	Felisa	Cajeta	Simbajo	0	0	Silvano	G	Cuizo	0	Teaching			0	0	13	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	8							0	0							\N	\N	0	0	0	0
128		10131	10131	0	Silvano	G	Cuizo	0	Guiun, Samar	\N	0	0	0.00	0.00	10131@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	13	0	0	0	0	f	f	t	f	\N	0	CM-3130497				0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
129		10132	10132	0	Abdullah	Manalusug	Datu-Dacula	0	\N	\N	0	0	0.00	0.00	10132@			1	1	0	f	f	f		0					0					0				0	0				0	0				0				0	0	13	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
130		10133	10133	0	Denda		Desamparado	0	\N	\N	0	0	0.00	0.00	10133@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	13	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
131		10134	10134	0	Gilbert, Sr.	Tahod	Gelig	0	Cmr, Maigo, Lanao Del Norte	\N	0	0	0.00	0.00	10134@			1	2	0	f	f	f		0					0					0	Mario	R	Gelig	0	0	Rosita	D	Tahud	0	0	Susa	B	Gelig	0	Housewife			0	0	13	0	0	0	0	f	f	t	f	\N	1.74489e+008	CM-5904236				0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
132		10135	10135	0	Sherwi	Abajo	Guirnaldo	0	Pagadian City, Philippines	\N	0	0	0.00	70.00	10135@			1	2	0	f	f	f		0					0					0	Teodorico Sr.	H	Guirnaldo	0	0				0	0	Onella	M	Guirnaldo	0				0	0	13	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
133		10136	10136	0	Sihawi	Abdulhamid	Khalid	0	Marawi City	\N	0	0	0.00	70.00	10136@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	13	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	6							0	0							\N	\N	0	0	0	0
134		10137	10137	0	Desiderio	Mijares	Kha	0	Davao City	\N	0	0	0.00	56.00	10137@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	13	0	0	0	0	f	f	t	f	\N	0	B56JPDMK017				0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
135		10138	10138	0	Milo	Parami	Labada	0	Ozamis City, Mis. Occ.	\N	0	0	0.00	67.00	10138@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	13	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	6							0	0							\N	\N	0	0	0	0
136		10139	10139	0	Honorina	Logatima	Lacar	0	Cansuhay, Duero, Bohol	\N	0	0	0.00	0.00	10139@			1	1	0	f	f	f		0					0					0				0	0				0	0				0				0	0	13	0	0	0	0	f	f	t	f	\N	0	CM-3120757				0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
137		10140	10140	0	Alex		Ladaga	0	\N	\N	0	0	0.00	0.00	10140@			1	1	0	f	f	f		0					0					0				0	0				0	0				0				0	0	13	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
138		10141	10141	0	Ricardo	Vellesis	Lagos	0	Tantangan, South Cotabato	\N	0	0	0.00	0.00	10141@			1	1	0	f	f	f		0					0					0				0	0				0	0				0				0	0	13	0	0	0	0	f	f	t	f	\N	0	CM-5909899				0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
139		10142	10142	0	Abdullah	Diabo	Lomondaya	0	Lapayan, Kauswagan, Lanao Del Norte	\N	0	0	0.00	0.00	10142@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	13	0	0	0	0	f	f	t	f	\N	0	CM-3120315				0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
140		10143	10143	0	Samina	Maruhom	Lomondaya	0	Kalawi, Lanao Del Sur	\N	0	0	0.00	0.00	10143@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	13	0	0	0	0	f	f	t	f	\N	0	CM-3130232				0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
141		10144	10144	0	Gle	Adobas	Lorenzo	0	Cabdbaran, Agusan Del Norter	\N	0	0	0.00	0.00	10144@			1	2	0	f	f	f		0					0					0	Deogracias	Cacio	Lorenzo	0	0	Nelia	Fernandez	Adobas	0	0	Mary Cris		Sabangan-Lorenzo	0				0	0	13	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
142		10145	10145	0	Nehemias	Lorono	Madjos	0	Lourdes, Alubijid, Misamis Oriental	\N	0	0	0.00	75.00	10145@			1	1	0	f	f	f		0					0					0	Artemio	Jangao	Madjos	0	0	Natividad	Ragmac	Lorono	0	0				0	N/A			0	0	13	0	0	0	0	f	f	t	f	\N	0	CM-5900027		1202-042341	15-00005606-0	0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	4							0	0							\N	\N	0	0	0	0
143		10146	10146	0	Karla	M	Khalid	0	Pasay City, Manila	\N	0	0	0.00	0.00	10146@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	13	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
144		10147	10147	0	Pepe	Laurente	Madrid	0	Alubijid, Misamis Oriental	\N	0	0	0.00	0.00	10147@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	13	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
145		10148	10148	0	Casa	Macawaris	Mala	0	Saguiaran, Lanao Del Sur	\N	0	0	0.00	165.00	10148@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	13	0	0	0	0	f	f	t	f	\N	0	CM-590907		1950-0026-2702		0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	4							0	0							\N	\N	0	0	0	0
146		10149	10149	0	Medior	Palaguya	Mamoko	0	\N	\N	0	0	0.00	0.00	10149@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	13	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
147		10150	10150	0	Adna	Marohom	Marohomsalic	0	Dr. Uy Hospital, Iligan City	\N	0	0	0.00	0.00	10150@			1	1	0	f	f	f		0					0					0				0	0				0	0				0				0	0	13	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
148		10151	10151	0	Antonio	Fiel	Mendoza	0	Tukuran, Zamboanga Del Sur	\N	0	0	0.00	73.00	10151@			1	2	0	f	f	f		0					0					0	Florencio	P	Mendoza	0	0	Felecitas	T	Fiel	0	0	Hernestina		Mendoza	0	HOUSEWIFE			0	0	13	0	0	0	0	f	f	t	f	\N	0	54041403058		1950-0026-4534	15-000063593-8	0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	6							0	0							\N	\N	0	0	0	0
149		10152	10152	0	Arthur	Balura	Minoza	0	Malaybalay City	\N	0	0	0.00	0.00	10152@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	13	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
150		10153	10153	0	Eusebio	Villanueva	Olandag	0	Calamba Miss Occ	\N	0	0	0.00	0.00	10153@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	13	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	1							0	0							\N	\N	0	0	0	0
151		10154	10154	0	Nerie	Gotostos	Oporto	0	Dapa, Surigao Del Norte	\N	0	0	0.00	0.00	10154@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	13	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
152		10155	10155	0	Conrado Jr	Fernandez	Ostia	0	\N	\N	0	0	0.00	0.00	10155@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	13	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
153		10156	10156	0	Salis	Sua	Palasa	0	Cogon, Elsalvador Misamis Oriental	\N	0	0	0.00	80.00	10156@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	13	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	1							0	0							\N	\N	0	0	0	0
154		10157	10157	0	Mamerto	Hortinela	Radaza	0	Davao City	\N	0	0	0.00	0.00	10157@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	13	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
155		10158	10158	0	Arnold	Estrada	Ramayla	0	Hibaiyo, Guihulngan, Negros Oriental	\N	0	0	0.00	0.00	10158@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	13	0	0	0	0	f	f	t	f	\N	0	B54QHAER013				0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
156		10159	10159	0	Lucyl	Climaco	Ramayla	0	Bacolod, Cagwait, Surigao Del Sur	\N	0	0	0.00	50.00	10159@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	13	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	8							0	0							\N	\N	0	0	0	0
157		10160	10160	0	Danilo	Cablao	Ranido	0	Cantahay, Guiuan, Eastern Samar	\N	0	0	0.00	55.00	10160@			1	2	0	f	f	f		0					0					0				0	0				0	0	Rosely	D	Ranido	0				0	0	13	0	0	0	0	f	f	t	f	\N	0	CM-5900635				0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	8							0	0							\N	\N	0	0	0	0
158		10161	10161	0	Arnel		Rempillo	0	\N	\N	0	0	0.00	0.00	10161@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	13	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
159		10162	10162	0	Cesario Jr	Napoles	Samong	0	Isabela, Basilan Province	\N	0	0	0.00	0.00	10162@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	13	0	0	0	0	f	f	t	f	\N	0	B50NECNS028				0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
160		10163	10163	0	Benjami	O	Sanchez	0	Bacani, Clarin, Bohol	\N	0	0	0.00	63.00	10163@			1	2	0	f	f	f		0					0					0	Victor	Cortez	Sanchez	0	0	Natividad	Soronio	Ompad-Sanchez	0	0	Ophelia	A	Rico-Sanchez	0	Housewife			0	0	13	0	0	0	0	f	f	t	f	\N	0	52021102491			20-000008980-9	0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	4							0	0							\N	\N	0	0	0	0
161		10164	10164	0	Felix	Rone	Santiago	0	Sibutad, Zamboanga Del Norte	\N	0	0	0.00	0.00	10164@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	13	0	0	0	0	f	f	t	f	\N	0	CM-5003155				0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
162		10165	10165	0	Albino	D	Sarucam	0	Toledo City, Cebu	\N	0	0	0.00	0.00	10165@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	13	0	0	0	0	f	f	t	f	\N	0	LP-51020502758				0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
163		10166	10166	0	Udtog	Manaloco	Tago	0	Lumbatan, Lds	\N	0	0	0.00	0.00	10166@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	13	0	0	0	0	f	f	t	f	\N	0	B59JVUMP012				0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
164		10168	10168	0	Saveniano		Torres	0	\N	\N	0	0	0.00	0.00	10168@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	13	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
165		10169	10169	0	Felizardo	Nakila	Tuazo	0	Cabadbaran, Agusan Del Norte	\N	0	0	0.00	0.00	10169@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	13	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
166		10170	10170	0	Ruel	S.	Uba	0	Dimataling, Zamboanga Del Sur	\N	0	0	0.00	0.00	10170@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	13	0	0	0	0	f	f	t	f	\N	0	CM-3176999				0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
167		10171	10171	0	Pablo	Sarsona	Venenoso	0	Cmr, Maigo, Lanao Del Norte	\N	0	0	0.00	0.00	10171@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	13	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
168		10172	10172	0	Erlinda	Olivar	Yape	0	Sagay, Camigui	\N	0	0	0.00	60.00	10172@			1	2	0	f	f	f		0					0					0	Susano	B	Olivar	0	0	Remedios	Sumanpa	Barsobia	0	0	Ceclilio	U	Yape	0	Civil engineer			0	0	13	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	8							0	0							\N	\N	0	0	0	0
169		10173	10173	0	Amor	Tabungar	Abrenica	0	Pagadian, Zambo Sur	\N	0	0	0.00	0.00	10173@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	14	0	0	0	0	f	f	t	f	\N	0	3169129				0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
170		10174	10174	0	Abel	D	Belga	0	Naawan, Mis. Or.	\N	0	0	0.00	0.00	10174@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	14	0	0	0	0	f	f	t	f	\N	0	571102769				0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
171		10175	10175	0	Else	J	Buca	0	Dipolog City	\N	0	0	0.00	0.00	10175@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	14	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	9							0	0							\N	\N	0	0	0	0
172		10176	10176	0	Emiliana	M	Caday	0	Bato, Davao City	\N	0	0	0.00	0.00	10176@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	14	0	0	0	0	f	f	t	f	\N	0	54072102561				0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
173		10177	10177	0	Lilia	Lazarraga	Columnas	0	Margosatubig, Zamboanga Del Sur	\N	0	0	0.00	0.00	10177@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	14	0	0	0	0	f	f	t	f	\N	0	55081003342				0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
174		10178	10178	0	Nena	Sahay	Dimaano	0	Cabadbaran, Agusan Del Norte	\N	0	0	0.00	0.00	10178@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	14	0	0	0	0	f	f	t	f	\N	0	4.404527775e+015				0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
175		10179	10179	0	Eva Marie	Sijo	Emam	0	Medina, Mis. Or.	\N	0	0	0.00	0.00	10179@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	14	0	0	0	0	f	f	t	f	\N	1.57626e+008	5.6111000297e+011				0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
176		10180	10180	0	Leticia	C	Escudero	0	\N	\N	0	0	0.00	0.00	10180@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	14	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
177		10181	10181	0	Pedro	Tenorio	Escudero	0	Parang, Maguindanao	\N	0	0	0.00	0.00	10181@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	14	0	0	0	0	f	f	t	f	\N	0	5901890				0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
178		10182	10182	0	Alejandro Jr	Mendez	Gonzaga	0	Iligan City	\N	0	0	0.00	65.00	10182@			1	2	0	f	f	f		0					0					0	Alejandro Sr.	P	Gonzaga	0	0	Nimfa	Mendez	Gonzaga	0	0	Jasmi	R	Gonzaga	0	Housewife			0	0	14	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	4							0	0							\N	\N	0	0	0	0
179		10183	10183	0	Oscar		Gripaldo	0	\N	\N	0	0	0.00	0.00	10183@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	14	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
180		10184	10184	0	Julieta	P	Lagmay	0	Aurora, Zaboanga Del Sur	\N	0	0	0.00	0.00	10184@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	14	0	0	0	0	f	f	t	f	\N	0	51112102373				0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
181		10185	10185	0	Lenie	Salvaña	Lopez	0	Salay, Mis. Or.	\N	0	0	0.00	0.00	10185@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	14	0	0	0	0	f	f	t	f	\N	0	3188018				0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
182		10186	10186	0	Evely	C	Moldez	0	Marawi City	\N	0	0	0.00	0.00	10186@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	14	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
183		10187	10187	0	Roma	P	Rosagaro	0	Caibiran Leyte	\N	0	0	0.00	0.00	10187@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	14	0	0	0	0	f	f	t	f	\N	0	43100601908				0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
184		10188	10188	0	Misael	M	Sanguila	0	Kuswagan, Lanao Del Norte	\N	0	0	0.00	75.00	10188@			1	1	0	f	f	f		0					0					0				0	0				0	0				0				0	0	14	0	0	0	0	f	f	t	f	\N	0	LP60091302805				0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	8							0	0							\N	\N	0	0	0	0
185		10189	10189	0	Madid	A	Sheik	0	Marawi City, Lanao Del Sur	\N	0	0	0.00	0.00	10189@			1	2	0	f	f	f		0					0					0	Mamitua		Saber	0	0	Orfia		Alicer	0	0	Maadurah	A	Saber	0	Housewife			0	0	14	0	0	0	0	f	f	t	f	\N	0	CM-3198123				0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
186		10190	10190	0	Rube	Belga	Silang	0	Pangil, Laguna	\N	0	0	0.00	0.00	10190@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	14	0	0	0	0	f	f	t	f	\N	0	3188503				0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
187		10191	10191	0	Merlinda	Esteba	Villa	0	Ipil, Zamboanga Sibugay	\N	0	0	0.00	0.00	10191@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	14	0	0	0	0	f	f	t	f	\N	0	52122803210				0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
188		10192	10192	0	Gladys		Boransing	0	Linamon, Lanao Del Norte	\N	0	0	0.00	0.00	10192@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	14	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
189		10194	10194	0	Ruben Jr	F	Amparado	0	\N	\N	0	0	0.00	0.00	10194@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	15	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
190		10195	10195	0	Antonio	C	Amponga	0	\N	\N	0	0	0.00	0.00	10195@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	15	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
191		10196	10196	0	Nelieta	Arnejo	Bedoya	0	Dumalinao, Zamboanga Del Sur	\N	0	0	0.00	56.00	10196@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	15	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	8							0	0							\N	\N	0	0	0	0
192		10197	10197	0	Evely	Varquez	Bigcas	0	Oroquieta City, Mis Occ	\N	0	0	0.00	45.00	10197@			1	1	0	f	f	f		0					0					0				0	0				0	0				0				0	0	15	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	8							0	0							\N	\N	0	0	0	0
193		10198	10198	0	Gideo	D	Binobo	0	\N	\N	0	0	0.00	0.00	10198@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	15	0	0	0	0	f	f	t	f	\N	0	CM-3173565				0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
194		10199	10199	0	Romeo, Jr.	G	Bornales	0	\N	\N	0	0	0.00	0.00	10199@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	15	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
195		10200	10200	0	Caharodi	A	Cali	0	\N	\N	0	0	0.00	0.00	10200@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	15	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
196		10201	10201	0	Edgardo	Malayao	Daquipil	0	\N	\N	0	0	0.00	0.00	10201@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	15	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
197		10202	10202	0	Elias	Madlos	Egam	0	Dumalinao, Zamboanga Del Sur	\N	0	0	0.00	68.00	10202@			1	2	0	f	f	f		0					0					0	Pastor	B	Egam	0	0	Arcadia	Serapica	Madlos	0	0	Mary Fe	Escalona	Egam	0	Faculty Member			0	0	15	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	1							0	0							\N	\N	0	0	0	0
198		10203	10203	0	Mariano	P	Flores	0	\N	\N	0	0	0.00	0.00	10203@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	15	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
199		10204	10204	0	Gerardo	Arce	Gavine	0	\N	\N	0	0	0.00	0.00	10204@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	15	0	0	0	0	f	f	t	f	\N	0	CM-3185481				0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
200		10205	10205	0	Abdul Nasser	Disalonga	Lomantong	0	Saguiaran, Lanao Del Sur	\N	0	0	0.00	62.00	10205@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	15	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
201		10206	10206	0	Gloria		Manarpaac	0	\N	\N	0	0	0.00	0.00	10206@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	15	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
202		10207	10207	0	Joseph	Manalo	Manlisis	0	\N	\N	0	0	0.00	0.00	10207@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	15	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
203		10208	10208	0	Urbano	Tangal	Mante	0	\N	\N	0	0	0.00	0.00	10208@			1	2	0	f	f	f		0					0					0				0	0				0	0				0				0	0	15	0	0	0	0	f	f	t	f	\N	0					0.00	0.00	0.00	0.00	0.00	0.00	0	t	2022-06-20 15:20:39	\N	0							0	0							\N	\N	0	0	0	0
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
-- Data for Name: loan_applications; Type: TAinformationema: public; Owner: postgres
--


COPY public.loan_applications (id, deduction_id, employee_id, loan_amount, loan_amortization, remarks, effectivity_date, end_date, is_approve, is_disapprove, created_at, updated_at, payment, balance) FROM stdin;
\.

--
-- Data for Name: menus; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.menus (
    id,
    menu,
    description,
    active,
    created_at,
    updated_at,
    menu_key,
    module_id,
    status
)
FROM stdin;

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
1	4	\N	\N	Assoc Prof V	Regular	885732.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
2	4	\N	\N	Assoc Prof V	Regular	904308.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
3	4	\N	\N	Assoc Prof V	Regular	922884.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
4	4	\N	\N	Asst. Prof. IV	Regular	376212.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
5	4	\N	\N	Asst. Prof. IV	Regular	405972.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
6	4	\N	\N	Asst. Prof. IV	Regular	433332.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
7	4	\N	\N	Asst. Prof. IV	Regular	462516.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
8	4	\N	\N	Instructor I	Regular	134004.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
9	4	\N	\N	Instructor I	Regular	147408.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
10	4	\N	\N	Instructor I	Regular	162144.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
11	4	\N	\N	Instructor I	Regular	181428.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
12	4	\N	\N	Instructor I	Regular	200712.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
13	4	\N	\N	Instructor I	Regular	219996.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
14	4	\N	\N	Instructor I	Regular	239280.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
15	5	\N	\N	PROF III	Regular	360960.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
16	5	\N	\N	PROF III	Regular	452592.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
17	5	\N	\N	Professor 3	Regular	291036.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
18	5	\N	\N	Professor 3	Regular	320136.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
19	5	\N	\N	Professor III	Regular	328140.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
20	6	\N	\N	Associate Professor 5	Regular	258732.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
21	7	\N	\N	Professor 2	Regular	301356.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
22	8	\N	\N	Assistant Professor V	Regular	377604.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
23	8	\N	\N	Assistant Professor V	Regular	442140.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
24	8	\N	\N	Assistant Professor V	Regular	506676.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
25	8	\N	\N	Assistant Professor V	Regular	577500.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
26	8	\N	\N	Assistant Professor V	Regular	583848.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
27	8	\N	\N	Assistant Professor V	Regular	661212.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
28	8	\N	\N	Assistant Professor V	Regular	748824.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
29	8	\N	\N	Assistant Professor V	Regular	848040.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
30	8	\N	\N	Assistant Professor V	Regular	860760.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
31	8	\N	\N	Assistant Professor V	Regular	976080.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
32	8	\N	\N	Assistant Professor V	Regular	995604.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
33	8	\N	\N	Assistant Professor V	Regular	1015128.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
34	8	\N	\N	Assoc Prof V	Regular	252420.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
35	8	\N	\N	Assoc Prof V	Regular	277668.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
36	8	\N	\N	Assoc Prof V	Regular	305436.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
37	8	\N	\N	Associate Professor 5	Regular	246252.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
38	9	\N	\N	Lecturer	Contractual	0.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
39	9	\N	\N	PROF 6	Regular	377040.00	Coll of Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
40	9	\N	\N	PROF 6	Regular	504492.00	Coll of Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
41	9	\N	\N	PROF 6	Regular	631932.00	Coll of Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
42	9	\N	\N	PROF 6	Regular	759384.00	Coll of Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
43	9	\N	\N	Prof.VI	Regular	769056.00	Coll of Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
44	9	\N	\N	Prof.VI	Regular	896592.00	Coll of Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
45	9	\N	\N	Professor 6	Regular	303996.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
46	9	\N	\N	Professor 6	Regular	334392.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
47	9	\N	\N	Professor 6	Regular	342768.00	Coll of Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
48	10	\N	\N	Associate Professor 5	Contractual	60984.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
49	11	\N	\N	Assoc. Prof. III	Regular	222120.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
50	11	\N	\N	Assoc. Prof. III	Regular	244332.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
51	11	\N	\N	Assoc. Prof. III	Regular	268764.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
52	11	\N	\N	Instructor 2	Regular	142044.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
53	12	\N	\N	Prof IV	Regular	357324.00	Coll of Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
54	12	\N	\N	Prof IV	Regular	460164.00	Coll of Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
55	12	\N	\N	Prof IV	Regular	563004.00	Coll of Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
56	12	\N	\N	Prof IV	Regular	665844.00	Coll of Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
57	12	\N	\N	Prof. IV	Regular	674412.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
58	12	\N	\N	Prof. IV	Regular	777132.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
59	12	\N	\N	Prof. VI	Regular	877188.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
60	12	\N	\N	Prof. VI	Regular	1058568.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
61	12	\N	\N	Prof. VI	Regular	1277448.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
62	12	\N	\N	Prof. VI	Regular	1294896.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
63	12	\N	\N	Prof. VI	Regular	1564704.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
64	12	\N	\N	Prof. VI	Regular	1890732.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
65	12	\N	\N	Prof. VI	Regular	1928544.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
66	12	\N	\N	Prof. VI	Regular	1960020.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
67	12	\N	\N	Prof. VI	Regular	1998444.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
68	12	\N	\N	Professor IV	Regular	288096.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
69	12	\N	\N	Professor IV	Regular	316908.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
70	12	\N	\N	Professor IV	Regular	348600.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
71	13	\N	\N	Asst Prof. 4	Regular	376212.00	Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
72	13	\N	\N	Asst Prof. 4	Regular	405972.00	Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
73	13	\N	\N	Asst Prof. 4	Regular	433332.00	Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
74	13	\N	\N	Asst Prof. 4	Regular	462516.00	Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
75	13	\N	\N	Asst. Prof. 4	Regular	468084.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
76	13	\N	\N	Asst. Prof. 4	Regular	499800.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
77	13	\N	\N	Asst. Prof. 4	Regular	518064.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
78	13	\N	\N	Asst. Prof. 4	Regular	536328.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
79	13	\N	\N	INST III	Regular	149232.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
80	13	\N	\N	INST III	Regular	164160.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
81	13	\N	\N	Instr II	Regular	185088.00	Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
82	13	\N	\N	Instr II	Regular	205068.00	Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
83	13	\N	\N	Instr II	Regular	225060.00	Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
84	13	\N	\N	Instr II	Regular	245040.00	Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
85	13	\N	\N	Instructor 2	Regular	142044.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
86	13	\N	\N	Instructor II	Regular	168264.00	Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
87	14	\N	\N	AP 4	Regular	253908.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
88	14	\N	\N	AP 4	Regular	288684.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
89	14	\N	\N	AP 4	Regular	323472.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
90	14	\N	\N	AP 4	Regular	328788.00	AGGIe	\N	\N	\N	\N	2022-06-21 10:53:32	\N
91	14	\N	\N	AP 4	Regular	363072.00	AGGIe	\N	\N	\N	\N	2022-06-21 10:53:32	\N
92	14	\N	\N	AP 4	Regular	397356.00	AGGIe	\N	\N	\N	\N	2022-06-21 10:53:32	\N
93	14	\N	\N	Assistant Professor 4	Regular	204708.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
94	14	\N	\N	Assistant Professor 4	Regular	225180.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
95	14	\N	\N	Assistant Professor 4	Regular	230820.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
96	15	\N	\N	PROF 6	Regular	406032.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
97	15	\N	\N	PROF 6	Regular	533628.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
98	15	\N	\N	PROF 6	Regular	661224.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
99	15	\N	\N	Prof 6	Regular	671340.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
100	15	\N	\N	Prof 6	Regular	798912.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
101	15	\N	\N	Prof 6	Regular	926496.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
102	15	\N	\N	Prof VI	Regular	327372.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
103	15	\N	\N	Prof VI	Regular	360108.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
104	15	\N	\N	Professor 6	Regular	369120.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
105	16	\N	\N	Lecturer		4164.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
106	16	\N	\N	PROF 4	Regular	375384.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
107	16	\N	\N	PROF 4	Regular	477960.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
108	16	\N	\N	Professor 4	Regular	302676.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
109	16	\N	\N	Professor 4	Regular	332940.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
110	16	\N	\N	Professor 4	Regular	341256.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
111	17	\N	\N	Prof. Emeritus	Contractual	186678.00	MSU OVPAA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
112	17	\N	\N	Prof. Emeritus	Contractual	186678.00	OVPAA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
113	17	\N	\N	Professor 6	Regular	96000.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
114	18	\N	\N	Assistant Professor	Regular	150552.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
115	18	\N	\N	Assistant Professor	Regular	165612.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
116	18	\N	\N	Assistant Professor	Regular	182172.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
117	18	\N	\N	Assistant Professor	Regular	205764.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
118	18	\N	\N	Assistant Professor	Regular	229344.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
119	18	\N	\N	Assistant Professor	Regular	252936.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
120	18	\N	\N	Assistant Professor	Regular	276528.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
121	19	\N	\N	PROFESSOR III	Contractual	60984.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
122	20	\N	\N	Prof. V	Regular	299616.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
123	20	\N	\N	Prof. V	Regular	329580.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
124	20	\N	\N	Prof. V	Regular	362544.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
125	20	\N	\N	Prof. V	Regular	477192.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
126	20	\N	\N	Prof. V	Regular	486252.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
127	20	\N	\N	Prof. V	Regular	600888.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
128	20	\N	\N	Prof. V	Regular	715536.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
129	20	\N	\N	Prof. V	Regular	830172.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
130	20	\N	\N	Prof. V	Regular	839304.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
131	20	\N	\N	Prof. VI	Regular	877188.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
132	20	\N	\N	Prof. VI	Regular	1058568.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
133	20	\N	\N	Prof. VI	Regular	1277448.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
134	20	\N	\N	Prof. VI	Regular	1541604.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
135	20	\N	\N	Prof. VI	Regular	1564704.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
136	20	\N	\N	Prof. VI	Regular	1890732.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
137	20	\N	\N	Prof. VI	Regular	1928544.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
138	20	\N	\N	Professor 5	Regular	292308.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
139	21	\N	\N	Assoc. Prof. V	Regular	258732.00	Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
140	21	\N	\N	Assoc. Prof. V	Regular	284604.00	Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
141	21	\N	\N	Assoc. Prof. V	Regular	313068.00	Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
142	21	\N	\N	Assoc. Prof. V	Regular	377604.00	Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
143	21	\N	\N	Assoc. Prof. V	Regular	873660.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
144	21	\N	\N	Assoc. Prof. V	Regular	992016.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
145	21	\N	\N	Assoc.Prof V	Regular	385032.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
146	21	\N	\N	Assoc.Prof V	Regular	449184.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
147	21	\N	\N	Assoc.Prof V	Regular	513336.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
148	21	\N	\N	Assoc.Prof V	Regular	577500.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
149	21	\N	\N	Assoc.Prof V	Regular	583848.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
150	21	\N	\N	Assoc.Prof V	Regular	661212.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
151	21	\N	\N	Assoc.Prof. V	Regular	669372.00	Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
152	21	\N	\N	Assoc.Prof. V	Regular	759060.00	Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
153	21	\N	\N	Assoc.Prof. V	Regular	860760.00	Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
154	21	\N	\N	Assoc.Prof. V	Regular	1011852.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
155	21	\N	\N	Associate Professor 5	Regular	252420.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
156	22	\N	\N	Assistant Professor 3	Regular	188400.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
157	22	\N	\N	Assistant Professor 3	Regular	207240.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
158	22	\N	\N	Assoc. Prof. V	Regular	885732.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
159	22	\N	\N	Assoc. Prof. V	Regular	904308.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
160	22	\N	\N	Assoc. Prof. V	Regular	922884.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
161	22	\N	\N	Asst. Pro III	Regular	233652.00	AGRICULTURE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
162	22	\N	\N	Asst. Pro III	Regular	265224.00	AGRICULTURE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
163	22	\N	\N	Asst. Pro III	Regular	296808.00	AGRICULTURE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
164	22	\N	\N	Asst. Pro III	Regular	328392.00	AGRICULTURE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
165	22	\N	\N	Asst. Pro III	Regular	359964.00	AGRICULTURE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
166	22	\N	\N	Asst. Pro III	Regular	382716.00	AGRICULTURE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
167	22	\N	\N	Asst. Pro III	Regular	395892.00	Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
168	22	\N	\N	Asst. Pro III	Regular	421356.00	Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
169	22	\N	\N	Asst. Pro III	Regular	448452.00	Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
170	22	\N	\N	Asst. Prof. III	Regular	453852.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
171	22	\N	\N	Asstistant Professor III	Regular	212412.00	AGRICULTURE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
172	23	\N	\N	Assoc Prof V	Regular	583848.00	Col. of Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
173	23	\N	\N	Assoc Prof. V	Regular	258732.00	Coll. of Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
174	23	\N	\N	Assoc Prof. V	Regular	284604.00	Coll. of Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
175	23	\N	\N	Assoc Prof. V	Regular	313068.00	Coll. of Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
176	23	\N	\N	Assoc Prof. V	Regular	377604.00	Coll. of Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
177	23	\N	\N	Assoc Prof. V	Regular	442140.00	Coll. of Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
178	23	\N	\N	Assoc Prof. V	Regular	506676.00	Coll. of Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
179	23	\N	\N	Assoc Prof. V	Regular	577500.00	Coll. of Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
180	23	\N	\N	Associate Professor 5	Regular	252420.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
181	23	\N	\N	Prof. VI	Regular	877188.00	Coll. of Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
182	23	\N	\N	Prof. VI	Regular	1058568.00	Coll. of Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
183	23	\N	\N	Prof. VI	Regular	1071612.00	Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
184	23	\N	\N	Prof. VI	Regular	1294896.00	Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
185	23	\N	\N	Prof. VI	Regular	1564704.00	Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
186	23	\N	\N	Prof. VI	Regular	1890732.00	Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
187	23	\N	\N	Prof. VI	Regular	1921584.00	Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
188	23	\N	\N	Prof. VI	Regular	1960020.00	Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
189	23	\N	\N	Prof. VI	Regular	1998444.00	Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
190	24	\N	\N	AP 4	Regular	253908.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
191	24	\N	\N	AP 4	Regular	288684.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
192	24	\N	\N	AP 4	Regular	323472.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
193	24	\N	\N	AP 4	Regular	328788.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
194	24	\N	\N	AP 4	Regular	363072.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
195	24	\N	\N	AP 4	Regular	397356.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
196	24	\N	\N	AP 4	Regular	401736.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
197	24	\N	\N	AP 4	Regular	429540.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
198	24	\N	\N	AP 4	Regular	434412.00	Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
199	24	\N	\N	AP 4	Regular	464628.00	Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
200	24	\N	\N	AP 4	Regular	496956.00	Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
201	24	\N	\N	Assistant Professor 4	Regular	230820.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
202	24	\N	\N	Associate Professor 4	Regular	204708.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
203	24	\N	\N	Associate Professor 4	Regular	225180.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
204	25	\N	\N	Lecturer	Contractual	0.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
205	25	\N	\N	Prof IV	Regular	295308.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
206	25	\N	\N	Prof IV	Regular	324840.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
207	25	\N	\N	Prof IV	Regular	357324.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
208	25	\N	\N	Prof IV	Regular	460164.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
209	25	\N	\N	Prof. IV	Regular	468960.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
210	25	\N	\N	Prof. IV	Regular	571692.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
211	25	\N	\N	Prof. IV	Regular	674412.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
212	25	\N	\N	Prof. IV	Regular	777132.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
213	25	\N	\N	Professor 4	Regular	288096.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
214	26	\N	\N	Prof III	Regular	343572.00	Coll of Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
215	26	\N	\N	Prof III	Regular	435612.00	Coll of Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
216	26	\N	\N	Prof III	Regular	527652.00	Coll of Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
217	26	\N	\N	Prof III	Regular	619704.00	Coll of Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
218	26	\N	\N	Prof. III	Regular	627720.00	Coll of Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
219	26	\N	\N	Prof. VI	Regular	877188.00	Coll of Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
220	26	\N	\N	Prof. VI	Regular	1588152.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
221	26	\N	\N	Prof. VI	Regular	1921584.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
222	26	\N	\N	Prof. VI	Regular	1960020.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
223	26	\N	\N	Prof. VI	Regular	1998444.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
224	26	\N	\N	Prof.VI	Regular	886836.00	Coll of Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
225	26	\N	\N	Prof.VI	Regular	1071612.00	Coll of Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
226	26	\N	\N	Prof.VI	Regular	1294896.00	Coll of Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
227	26	\N	\N	Prof.VI	Regular	1564704.00	Coll of Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
228	26	\N	\N	Professor 3	Regular	277008.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
229	26	\N	\N	Professor 3	Regular	304704.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
230	26	\N	\N	Professor 3	Regular	335172.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
231	27	\N	\N	Prof 3	Regular	277008.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
232	27	\N	\N	Prof 3	Regular	304704.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
233	27	\N	\N	Prof 3	Regular	335172.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
234	27	\N	\N	Prof III	Regular	343572.00	Coll of Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
235	27	\N	\N	Prof III	Regular	435612.00	Coll of Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
236	27	\N	\N	Prof III	Regular	527652.00	Coll of Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
237	28	\N	\N	Assistant Professor 5	Regular	291696.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
238	28	\N	\N	Assistant Professor 5	Regular	320868.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
239	28	\N	\N	Assistant Professor 5	Regular	385032.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
240	28	\N	\N	Associate Professor 5	Regular	258732.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
241	28	\N	\N	Associate Professor 5	Regular	284604.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
242	29	\N	\N	Assistant Professor 5	Regular	306480.00	AGRICULTURE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
243	29	\N	\N	Assistant Professor 5	Regular	314148.00	AGRICULTURE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
244	29	\N	\N	Assistant Professor 5	Regular	345564.00	AGRICULTURE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
245	29	\N	\N	Assistant Professor 5	Regular	408360.00	AGRICULTURE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
246	29	\N	\N	Assistant Professor 5	Regular	471168.00	AGRICULTURE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
247	29	\N	\N	Assistant Professor 5	Regular	533976.00	AGRICULTURE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
248	29	\N	\N	Assistant Professor 5	Regular	596772.00	AGRICULTURE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
249	29	\N	\N	Assistant Professor 5	Regular	677616.00	AGRICULTURE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
250	29	\N	\N	Assistant Professor 5	Regular	769416.00	AGRICULTURE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
251	29	\N	\N	Assistant Professor 5	Regular	873660.00	AGRICULTURE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
252	29	\N	\N	Assistant Professor 5	Regular	992016.00	AGRICULTURE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
253	29	\N	\N	Associate Professor 5	Regular	271824.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
254	29	\N	\N	Associate Professor 5	Regular	299004.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
255	30	\N	\N	Asooc Prof II	Regular	453456.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
256	30	\N	\N	Assistant Professor 4	Regular	213588.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
257	30	\N	\N	Assistant Professor 4	Regular	234948.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
258	30	\N	\N	Assistant Professor 4	Regular	258444.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
259	30	\N	\N	Assoc. Prof. II	Regular	348624.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
260	30	\N	\N	Assoc. Prof. II	Regular	393720.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
261	30	\N	\N	Assoc. Prof. II	Regular	438804.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
262	30	\N	\N	Assoc. Prof. II	Regular	501192.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
263	30	\N	\N	Asst. Prof.4	Regular	286140.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
264	30	\N	\N	Prof. VI	Regular	1934772.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
265	30	\N	\N	VP	Regular	969120.00	OVPPD	\N	\N	\N	\N	2022-06-21 10:53:32	\N
266	30	\N	\N	VP	Regular	1156356.00	OVPPD	\N	\N	\N	\N	2022-06-21 10:53:32	\N
267	30	\N	\N	VP	Regular	1379772.00	OVPPD	\N	\N	\N	\N	2022-06-21 10:53:32	\N
268	30	\N	\N	VP	Regular	1646340.00	OVPPD	\N	\N	\N	\N	2022-06-21 10:53:32	\N
269	30	\N	\N	VP	Regular	1673208.00	OVPPD	\N	\N	\N	\N	2022-06-21 10:53:32	\N
270	30	\N	\N	VP	Regular	1706676.00	OVPPD	\N	\N	\N	\N	2022-06-21 10:53:32	\N
271	30	\N	\N	VP	Regular	1740132.00	OVPPD	\N	\N	\N	\N	2022-06-21 10:53:32	\N
272	31	\N	\N	Associate Professor 5	Regular	258732.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
273	31	\N	\N	Prof. VI	Regular	303996.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
274	31	\N	\N	Prof. VI	Regular	334392.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
275	31	\N	\N	Prof. VI	Regular	367836.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
276	31	\N	\N	Prof. VI	Regular	377040.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
277	31	\N	\N	Prof. VI	Regular	504492.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
278	31	\N	\N	Prof. VI	Regular	631932.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
279	31	\N	\N	Prof. VI	Regular	759384.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
280	32	\N	\N	ASSOCIATE PROFESSOR II	Contractual	0.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
281	33	\N	\N	Assistant Professor 2	Regular	265824.00	AGRICULTURE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
282	33	\N	\N	Assistant Professor 2	Regular	292404.00	AGRICULTURE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
283	33	\N	\N	Associate Professor 2	Regular	235764.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
284	33	\N	\N	Associate Professor 2	Regular	259344.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
285	33	\N	\N	PROF 6	Regular	886836.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
286	33	\N	\N	Professor VI	Regular	367836.00	Coll of Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
287	33	\N	\N	Professor VI	Regular	495168.00	Coll of Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
288	33	\N	\N	Professor VI	Regular	622512.00	Coll of Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
289	33	\N	\N	Professor VI	Regular	749856.00	Coll of Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
290	33	\N	\N	Professor VI	Regular	877188.00	Coll of Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
291	33	\N	\N	Professor VI	Regular	1058568.00	Coll of Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
292	33	\N	\N	Professor VI	Regular	1312584.00	Coll of Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
293	33	\N	\N	Professor VI	Regular	1588152.00	Coll of Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
294	33	\N	\N	Professor VI	Regular	1921584.00	Coll of Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
295	33	\N	\N	Professor VI	Regular	1960020.00	Coll of Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
296	33	\N	\N	Professor VI	Regular	2064216.00	Coll of Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
297	34	\N	\N	Asst.Prof IV	Regular	376212.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
298	34	\N	\N	Asst.Prof IV	Regular	401424.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
299	34	\N	\N	Instructor I	Regular	134004.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
300	34	\N	\N	Instructor I	Regular	147408.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
301	34	\N	\N	Instructor I	Regular	162144.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
302	34	\N	\N	Instructor I	Regular	181428.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
303	34	\N	\N	Instructor I	Regular	200712.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
304	34	\N	\N	Instructor I	Regular	219996.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
305	35	\N	\N	Assistant Professor 5	Regular	291696.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
306	35	\N	\N	Assistant Professor 5	Regular	320868.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
307	35	\N	\N	Assistant Professor 5	Regular	385032.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
308	35	\N	\N	Assistant Professor 5	Regular	449184.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
309	35	\N	\N	Assistant Professor 5	Regular	456384.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
310	35	\N	\N	Assistant Professor 5	Regular	520116.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
311	35	\N	\N	Assistant Professor 5	Regular	583848.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
312	35	\N	\N	Assistant Professor V	Regular	590280.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
313	35	\N	\N	Associate Professor 5	Regular	258732.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
314	35	\N	\N	Associate Professor 5	Regular	284604.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
315	35	\N	\N	Lecturer	Contractual	0.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
316	36	\N	\N	Consultant	Contractual	60984.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
317	37	\N	\N	Prof VI	Regular	877188.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
318	37	\N	\N	Prof. II	Regular	279852.00		\N	\N	\N	\N	2022-06-21 10:53:32	\N
319	37	\N	\N	Prof. II	Regular	307836.00		\N	\N	\N	\N	2022-06-21 10:53:32	\N
320	37	\N	\N	Prof. II	Regular	338616.00		\N	\N	\N	\N	2022-06-21 10:53:32	\N
321	37	\N	\N	Prof. II	Regular	420528.00		\N	\N	\N	\N	2022-06-21 10:53:32	\N
322	37	\N	\N	Prof. III	Regular	435612.00	Collega of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
323	37	\N	\N	Prof. III	Regular	527652.00	Collega of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
324	37	\N	\N	Prof. III	Regular	619704.00	Collega of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
325	37	\N	\N	Prof. III	Regular	711744.00	Collega of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
326	37	\N	\N	Prof. VI	Regular	1071612.00	Collega of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
327	37	\N	\N	Prof. VI	Regular	1294896.00	Collega of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
328	37	\N	\N	Prof. VI	Regular	1564704.00	Collega of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
329	37	\N	\N	Prof. VI	Regular	1921584.00	Collega of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
330	37	\N	\N	Prof. VI	Regular	1960020.00	Collega of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
331	37	\N	\N	Prof. VI	Regular	1992012.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
332	37	\N	\N	Prof. VI	Regular	2031072.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
333	37	\N	\N	Professor 2	Regular	273012.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
334	38	\N	\N	Asst Prof. IV	Regular	493680.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
335	38	\N	\N	Asst Prof. IV	Regular	511944.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
336	38	\N	\N	Asst Prof. IV	Regular	530208.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
337	38	\N	\N	Asst. Prof. IV	Regular	401424.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
338	38	\N	\N	Asst. Prof. IV	Regular	428316.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
339	38	\N	\N	Asst. Prof. IV	Regular	457020.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
340	38	\N	\N	Asst. Prof. IV	Regular	487644.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
341	38	\N	\N	Instructor I	Regular	134004.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
342	38	\N	\N	Instructor I	Regular	147408.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
343	38	\N	\N	Instructor I	Regular	162144.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
344	38	\N	\N	Instructor I	Regular	181428.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
345	38	\N	\N	Instructor I	Regular	200712.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
346	38	\N	\N	Instructor I	Regular	219996.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
347	38	\N	\N	Instructor I	Regular	239280.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
348	38	\N	\N	Instructor I	Regular	247812.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
349	39	\N	\N	Iinstructor 3	Regular	150552.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
350	39	\N	\N	Iinstructor 3	Regular	165612.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
351	39	\N	\N	Iinstructor 3	Regular	182172.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
352	39	\N	\N	Iinstructor 3	Regular	205764.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
353	39	\N	\N	Iinstructor 3	Regular	229344.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
354	39	\N	\N	Iinstructor 3	Regular	252936.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
355	39	\N	\N	Iinstructor 3	Regular	276528.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
356	40	\N	\N	Assistant Professor 5	Regular	291696.00	AGGEI	\N	\N	\N	\N	2022-06-21 10:53:32	\N
357	40	\N	\N	Assistant Professor 5	Regular	320868.00	AGGEI	\N	\N	\N	\N	2022-06-21 10:53:32	\N
358	40	\N	\N	Assistant Professor 5	Regular	385032.00	AGGEI	\N	\N	\N	\N	2022-06-21 10:53:32	\N
359	40	\N	\N	Associate Professor 5	Regular	258732.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
360	40	\N	\N	Associate Professor 5	Regular	284604.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
361	40	\N	\N	Prof. III	Regular	419340.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
362	40	\N	\N	Prof. III	Regular	511668.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
363	40	\N	\N	Prof. III	Regular	604008.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
364	40	\N	\N	Prof. III	Regular	696336.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
365	41	\N	\N	Prof. VI	Regular	877188.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
366	41	\N	\N	Professor 5	Regular	292308.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
367	41	\N	\N	Professor 5	Regular	321540.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
368	41	\N	\N	Professor 5	Regular	353700.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
369	41	\N	\N	Professor 5	Regular	468312.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
370	41	\N	\N	Professor 5	Regular	582948.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
371	41	\N	\N	Professor 5	Regular	697584.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
372	41	\N	\N	Professor 5	Regular	812208.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
373	42	\N	\N	Professor 6	Regular	352548.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
374	42	\N	\N	Professor 6	Regular	387804.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
375	42	\N	\N	Professor 6	Regular	426588.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
376	43	\N	\N	Assoc Prof. II	Regular	393720.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
377	43	\N	\N	Assoc Prof. II	Regular	438804.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
378	43	\N	\N	Assoc Prof. II	Regular	477216.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
379	43	\N	\N	Assoc.Prof. II	Regular	613860.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
380	43	\N	\N	Assoc.Prof. II	Regular	632436.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
381	43	\N	\N	Assoc.Prof. II	Regular	651012.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
382	43	\N	\N	Assoc.Prof. II.	Regular	519000.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
383	43	\N	\N	Assoc.Prof. II.	Regular	564444.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
384	43	\N	\N	Instructor I	Regular	213588.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
385	43	\N	\N	Instructor I	Regular	234948.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
386	43	\N	\N	Instructor I	Regular	258444.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
387	43	\N	\N	Instructor I	Regular	303540.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
388	43	\N	\N	Instructor I	Regular	348624.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
389	44	\N	\N	Assoc. Prof. V	Regular	552768.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
390	44	\N	\N	Assoc. Prof. V	Regular	621912.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
391	44	\N	\N	Assoc. Prof. V	Regular	709272.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
392	44	\N	\N	Assoc. Prof. V	Regular	799044.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
393	44	\N	\N	Assoc. Prof. V	Regular	900180.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
394	44	\N	\N	Assoc. Prof. V	Regular	918756.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
395	44	\N	\N	Asst. Prof. IV	Regular	266568.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
396	44	\N	\N	Asst. Prof. IV	Regular	303108.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
397	44	\N	\N	Asst. Prof. IV	Regular	339660.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
398	44	\N	\N	Asst. Prof. IV	Regular	380352.00	AGGIE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
399	44	\N	\N	Instructor 1	Regular	134004.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
400	44	\N	\N	Instructor 1	Regular	147408.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
401	44	\N	\N	Instructor 1	Regular	162144.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
402	44	\N	\N	Instructor 1	Regular	181428.00	College of Agriculture	\N	\N	\N	\N	2022-06-21 10:53:32	\N
403	44	\N	\N	Instructor I	Regular	162144.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
404	44	\N	\N	Prof. V	Regular	1679268.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
405	44	\N	\N	Prof. V	Regular	1712196.00	Aggie	\N	\N	\N	\N	2022-06-21 10:53:32	\N
406	45	\N	\N	INSTRUCTOR I	Regular	134004.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
560	60	\N	\N	AP 4	Regular	401736.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
407	45	\N	\N	INSTRUCTOR I	Regular	147408.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
408	45	\N	\N	INSTRUCTOR I	Regular	162144.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
409	45	\N	\N	INSTRUCTOR I	Regular	181428.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
410	45	\N	\N	INSTRUCTOR I	Regular	200712.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
411	46	\N	\N	ASSOCIATE PROFESSOR V	Contractual	60984.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
412	47	\N	\N	Assoc. Prof. III	Regular	415560.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
413	47	\N	\N	Assoc. Prof. III	Regular	463596.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
414	47	\N	\N	Assoc. Prof. III	Regular	511632.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
415	47	\N	\N	ASSOCIATE PROFESSOR III	Regular	257604.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
416	47	\N	\N	ASSOCIATE PROFESSOR III	Regular	283368.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
417	47	\N	\N	ASSOCIATE PROFESSOR III	Regular	319476.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
418	47	\N	\N	ASSOCIATE PROFESSOR III	Regular	367512.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
419	48	\N	\N	A P IV	Regular	219684.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
420	48	\N	\N	A P IV	Regular	241656.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
421	48	\N	\N	ASSISTANT PROFESSOR IV	Regular	199716.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
422	48	\N	\N	Assistant Professor V	Regular	290688.00	Coll of BA and Accountancy	\N	\N	\N	\N	2022-06-21 10:53:32	\N
423	48	\N	\N	Assistant Professor V	Regular	356208.00	Coll of BA and Accountancy	\N	\N	\N	\N	2022-06-21 10:53:32	\N
424	48	\N	\N	Assistant Professor V	Regular	421728.00	Coll of BA and Accountancy	\N	\N	\N	\N	2022-06-21 10:53:32	\N
425	48	\N	\N	Assistant Professor V	Regular	487248.00	Coll of BA and Accountancy	\N	\N	\N	\N	2022-06-21 10:53:32	\N
426	48	\N	\N	Assistant Professor V	Regular	552768.00	Coll of BA and Accountancy	\N	\N	\N	\N	2022-06-21 10:53:32	\N
427	48	\N	\N	Assistant Professor V	Regular	558852.00	MSU CBAA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
428	48	\N	\N	Assistant Professor V	Regular	621912.00	Coll of BA and Accountancy	\N	\N	\N	\N	2022-06-21 10:53:32	\N
429	48	\N	\N	Assistant Professor V	Regular	637344.00	MSU CBAA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
430	48	\N	\N	Assistant Professor V	Regular	718956.00	MSU CBAA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
431	48	\N	\N	Assistant Professor V	Regular	823176.00	MSU CBAA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
432	48	\N	\N	Assistant Professor V	Regular	929808.00	MSU CBAA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
433	48	\N	\N	Assistant Professor V	Regular	948408.00	MSU CBAA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
434	48	\N	\N	Assistant Professor V	Regular	982788.00	MSU CBAA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
435	49	\N	\N	AP 4	Regular	253908.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
436	49	\N	\N	AP 4	Regular	288684.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
437	49	\N	\N	AP 4	Regular	323472.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
438	49	\N	\N	AP 4	Regular	328788.00	MSU CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
439	49	\N	\N	AP 4	Regular	363072.00	MSU CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
440	49	\N	\N	AP 4	Regular	397356.00	MSU CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
441	49	\N	\N	Assistant Professor 4	Regular	230820.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
442	49	\N	\N	ASSISTANT PROFESSOR IV	Regular	190092.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
443	49	\N	\N	Asst. Prof. IV	Regular	204708.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
444	49	\N	\N	Asst. Prof. IV	Regular	225180.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
445	50	\N	\N	PROF 6	Regular	406032.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
446	50	\N	\N	PROF 6	Regular	533628.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
447	50	\N	\N	PROF 6	Regular	661224.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
448	50	\N	\N	PROF 6	Regular	671340.00	MSU CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
449	50	\N	\N	PROF 6	Regular	798912.00	MSU CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
450	50	\N	\N	PROF 6	Regular	926496.00	MSU CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
451	50	\N	\N	Prof.. 6	Regular	936696.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
452	50	\N	\N	PROFESSOR 6	Regular	327372.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
453	50	\N	\N	PROFESSOR 6	Regular	360108.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
454	50	\N	\N	Professor 6	Regular	369120.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
455	51	\N	\N	Asst. Prof.	Regular	401424.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
456	51	\N	\N	Asst. Prof.	Regular	428316.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
457	51	\N	\N	Asst. Prof.	Regular	457020.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
458	51	\N	\N	Asst. Prof.	Regular	487644.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
459	51	\N	\N	Asst. Prof. IV	Regular	376212.00	CBAA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
460	51	\N	\N	INSTRUCTOR II	Regular	149232.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
461	51	\N	\N	INSTRUCTOR II	Regular	164160.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
462	51	\N	\N	INSTRUCTOR II	Regular	180576.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
463	51	\N	\N	INSTRUCTOR II	Regular	201036.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
464	51	\N	\N	INSTRUCTOR II	Regular	221484.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
465	51	\N	\N	INSTRUCTOR II	Regular	241944.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
466	51	\N	\N	INSTRUCTOR II	Regular	262404.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
467	52	\N	\N	Asst Prof. IV	Regular	524244.00	MSU CBAA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
468	52	\N	\N	Asst Prof. IV	Regular	542508.00	MSU CBAA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
469	52	\N	\N	Asst. Prof. IV	Regular	266568.00	MSU CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
470	52	\N	\N	Asst. Prof. IV	Regular	303108.00	MSU CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
471	52	\N	\N	Asst. Prof. IV	Regular	339660.00	MSU CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
472	52	\N	\N	Asst. Prof. IV	Regular	376212.00	MSU CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
473	52	\N	\N	Asst. Prof. IV	Regular	401424.00	MSU CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
474	52	\N	\N	Asst. Prof. IV	Regular	428316.00	MSU CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
475	52	\N	\N	Asst. Prof. IV	Regular	457020.00	MSU CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
476	52	\N	\N	Asst. Prof. IV	Regular	487644.00	MSU CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
477	52	\N	\N	Asst. Prof. IV	Regular	505908.00	MSU CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
478	52	\N	\N	INSTRUCTOR I	Regular	134004.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
479	52	\N	\N	INSTRUCTOR I	Regular	147408.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
561	60	\N	\N	AP 4	Regular	429540.00	MSU CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
480	52	\N	\N	INSTRUCTOR I	Regular	162144.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
481	52	\N	\N	INSTRUCTOR I	Regular	181428.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
482	53	\N	\N	AP 4`	Regular	253908.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
483	53	\N	\N	Assistant Professor 4`	Regular	230820.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
484	53	\N	\N	ASSISTANT PROFESSOR IV	Regular	204708.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
485	53	\N	\N	ASSISTANT PROFESSOR IV	Regular	225180.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
486	53	\N	\N	Assistant Professor V	Regular	290688.00	Coll of BA and Accountancy	\N	\N	\N	\N	2022-06-21 10:53:32	\N
487	53	\N	\N	Assistant Professor V	Regular	356208.00	Coll of BA and Accountancy	\N	\N	\N	\N	2022-06-21 10:53:32	\N
488	53	\N	\N	Assistant Professor V	Regular	421728.00	Coll of BA and Accountancy	\N	\N	\N	\N	2022-06-21 10:53:32	\N
489	53	\N	\N	Assistant Professor V	Regular	487248.00	Coll of BA and Accountancy	\N	\N	\N	\N	2022-06-21 10:53:32	\N
490	53	\N	\N	Assistant Professor V	Regular	552768.00	Coll of BA and Accountancy	\N	\N	\N	\N	2022-06-21 10:53:32	\N
491	53	\N	\N	Assistant Professor V	Regular	558852.00	MSU CBAA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
492	53	\N	\N	Assistant Professor V	Regular	637344.00	Coll of BA and Accountancy	\N	\N	\N	\N	2022-06-21 10:53:32	\N
493	53	\N	\N	Assistant Professor V	Regular	718956.00	Coll of BA and Accountancy	\N	\N	\N	\N	2022-06-21 10:53:32	\N
494	53	\N	\N	Assistant Professor V	Regular	811020.00	Coll of BA and Accountancy	\N	\N	\N	\N	2022-06-21 10:53:32	\N
495	53	\N	\N	Assistant Professor V	Regular	823176.00	CBAA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
496	53	\N	\N	Assistant Professor V	Regular	929808.00	CBAA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
497	54	\N	\N	A P 4	Regular	253908.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
498	54	\N	\N	A P 4	Regular	288684.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
499	54	\N	\N	A P 4	Regular	323472.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
500	54	\N	\N	AP 4	Regular	328788.00	MSU CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
501	54	\N	\N	AP 4	Regular	363072.00	MSU CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
502	54	\N	\N	AP 4	Regular	397356.00	MSU CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
503	54	\N	\N	Assistant Professor 4	Regular	230820.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
504	54	\N	\N	ASSISTANT PROFESSOR IV	Regular	204708.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
505	54	\N	\N	ASSISTANT PROFESSOR IV	Regular	225180.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
506	54	\N	\N	Assoc Prof. V	Regular	629592.00	MSU CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
507	54	\N	\N	Assoc Prof. V	Regular	709272.00	MSU CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
508	54	\N	\N	Assoc Prof. V	Regular	799044.00	MSU CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
509	54	\N	\N	Assoc Prof. V	Regular	811020.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
510	54	\N	\N	Assoc Prof. V	Regular	914880.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
511	54	\N	\N	Assoc Prof. V	Regular	933456.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
512	54	\N	\N	Assoc Prof. V	Regular	952032.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
513	54	\N	\N	Assoc. Prof. V	Regular	552768.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
514	54	\N	\N	Assoc.Prof. V	Regular	966996.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
515	55	\N	\N	ASSISTANT PROFESSOR IV	Regular	204708.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
516	55	\N	\N	Assoc Prof V	Regular	240240.00	C B A	\N	\N	\N	\N	2022-06-21 10:53:32	\N
517	55	\N	\N	Assoc Prof V	Regular	264264.00	C B A	\N	\N	\N	\N	2022-06-21 10:53:32	\N
518	55	\N	\N	Assoc Prof V	Regular	290688.00	C B A	\N	\N	\N	\N	2022-06-21 10:53:32	\N
519	55	\N	\N	Assoc Prof V	Regular	356208.00	C B A	\N	\N	\N	\N	2022-06-21 10:53:32	\N
520	55	\N	\N	Assoc Prof. V	Regular	944988.00	CBAA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
521	55	\N	\N	Assoc Prof. V	Regular	963888.00	CBAA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
522	55	\N	\N	Assoc Prof. V	Regular	982788.00	CBAA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
523	55	\N	\N	Assoc. Prof. V	Regular	637344.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
524	55	\N	\N	Assoc. Prof. V	Regular	718956.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
525	55	\N	\N	Assoc. Prof. V	Regular	728784.00	CBAA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
526	55	\N	\N	Assoc. Prof. V	Regular	823176.00	CBAA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
527	55	\N	\N	Assoc. Prof. V	Regular	929808.00	CBAA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
528	55	\N	\N	Asst. Prof. V	Regular	363180.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
529	55	\N	\N	Asst. Prof. V	Regular	428412.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
530	55	\N	\N	Asst. Prof. V	Regular	493632.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
531	55	\N	\N	Asst. Prof. V	Regular	558852.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
532	55	\N	\N	Asst. Prof. V	Regular	564996.00	CBAA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
533	56	\N	\N	Assistant Professor 5	Regular	270876.00	C B A	\N	\N	\N	\N	2022-06-21 10:53:32	\N
534	56	\N	\N	Assistant Professor 5	Regular	297960.00	C B A	\N	\N	\N	\N	2022-06-21 10:53:32	\N
535	56	\N	\N	Assistant Professor 5	Regular	363180.00	C B A	\N	\N	\N	\N	2022-06-21 10:53:32	\N
536	56	\N	\N	Assistant Professor 5	Regular	435216.00	C B A	\N	\N	\N	\N	2022-06-21 10:53:32	\N
537	56	\N	\N	Assistant Professor 5	Regular	500112.00	C B A	\N	\N	\N	\N	2022-06-21 10:53:32	\N
538	56	\N	\N	Assistant Professor 5	Regular	564996.00	C B A	\N	\N	\N	\N	2022-06-21 10:53:32	\N
539	56	\N	\N	Assistant Professor 5	Regular	571212.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
540	56	\N	\N	Assistant Professor 5	Regular	653160.00	C B A	\N	\N	\N	\N	2022-06-21 10:53:32	\N
541	56	\N	\N	Assistant Professor 5	Regular	738732.00	C B A	\N	\N	\N	\N	2022-06-21 10:53:32	\N
542	56	\N	\N	Assistant Professor 5	Regular	835524.00	C B A	\N	\N	\N	\N	2022-06-21 10:53:32	\N
543	56	\N	\N	Assistant Professor 5	Regular	944988.00	C B A	\N	\N	\N	\N	2022-06-21 10:53:32	\N
544	56	\N	\N	Assistant Professor 5	Regular	960408.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
545	56	\N	\N	Assistant Professor 5	Regular	979620.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
546	56	\N	\N	Assistant Professor 5	Regular	998820.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
547	56	\N	\N	ASSISTANT PROFESSOR IV	Regular	240240.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
548	56	\N	\N	Assistant Professor V	Regular	264264.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
549	56	\N	\N	Assoc. Prof. V	Regular	370332.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
550	57	\N	\N	PROFESSOR IV	Contractual	60984.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
551	58	\N	\N	ASSISTANT PROFESSOR II	Contractual	0.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
552	58	\N	\N	Lecturer	Contractual	0.00	LAW	\N	\N	\N	\N	2022-06-21 10:53:32	\N
553	59	\N	\N	ASSOCIATE PROFESSOR V	Contractual	60984.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
554	60	\N	\N	AP 4	Regular	253908.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
555	60	\N	\N	AP 4	Regular	288684.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
556	60	\N	\N	AP 4	Regular	323472.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
557	60	\N	\N	AP 4	Regular	328788.00	MSU CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
558	60	\N	\N	AP 4	Regular	363072.00	MSU CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
559	60	\N	\N	AP 4	Regular	397356.00	MSU CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
562	60	\N	\N	AP 4	Regular	434412.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
563	60	\N	\N	AP 4	Regular	464628.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
564	60	\N	\N	AP 4	Regular	496956.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
565	60	\N	\N	AP 4	Regular	531528.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
566	60	\N	\N	AP 4	Regular	549792.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
567	60	\N	\N	AP 4	Regular	568056.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
568	60	\N	\N	Assistant Professor 4	Regular	230820.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
569	60	\N	\N	ASSISTANT PROFESSOR IV	Regular	204708.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
570	60	\N	\N	ASSISTANT PROFESSOR IV	Regular	225180.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
571	61	\N	\N	Assistant Professor 5	Regular	291696.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
572	61	\N	\N	Assistant Professor 5	Regular	320868.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
573	61	\N	\N	Assistant Professor 5	Regular	385032.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
574	61	\N	\N	Assistant Professor 5	Regular	449184.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
575	61	\N	\N	Assistant Professor 5	Regular	456384.00	MSU CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
576	61	\N	\N	Assistant Professor 5	Regular	520116.00	MSU CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
577	61	\N	\N	Assistant Professor 5	Regular	583848.00	MSU CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
578	61	\N	\N	ASSOCIATE PROFESSOR V	Regular	258732.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
579	61	\N	\N	ASSOCIATE PROFESSOR V	Regular	284604.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
580	62	\N	\N	A P  4	Regular	253908.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
581	62	\N	\N	A P  4	Regular	288684.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
582	62	\N	\N	A P  4	Regular	323472.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
583	62	\N	\N	AP 4	Regular	328788.00	MSU CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
584	62	\N	\N	AP 4	Regular	363072.00	MSU CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
585	62	\N	\N	AP 4	Regular	397356.00	MSU CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
586	62	\N	\N	AP 4	Regular	401736.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
587	62	\N	\N	AP 4	Regular	429540.00	MSU CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
588	62	\N	\N	Assistant Professor 4	Regular	230820.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
589	62	\N	\N	ASSOCIATE PROFESSOR IV	Regular	204708.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
590	62	\N	\N	ASSOCIATE PROFESSOR IV	Regular	225180.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
591	63	\N	\N	INSTRUCTOR I	Contractual	0.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
592	64	\N	\N	Assoc Prof. V	Regular	900180.00	CHS	\N	\N	\N	\N	2022-06-21 10:53:32	\N
593	64	\N	\N	Assoc. Prof. V	Regular	621912.00	CHS	\N	\N	\N	\N	2022-06-21 10:53:32	\N
594	64	\N	\N	Assoc. Prof. V	Regular	699720.00	CHS	\N	\N	\N	\N	2022-06-21 10:53:32	\N
595	64	\N	\N	Assoc. Prof. V	Regular	787248.00	CHS	\N	\N	\N	\N	2022-06-21 10:53:32	\N
596	64	\N	\N	Assoc. Prof. V	Regular	885732.00	CHS	\N	\N	\N	\N	2022-06-21 10:53:32	\N
597	64	\N	\N	Asst Prof III	Regular	216984.00	College of Health Sciences	\N	\N	\N	\N	2022-06-21 10:53:32	\N
598	64	\N	\N	Asst Prof III	Regular	249828.00	College of Health Sciences	\N	\N	\N	\N	2022-06-21 10:53:32	\N
599	64	\N	\N	Asst Prof III	Regular	282660.00	College of Health Sciences	\N	\N	\N	\N	2022-06-21 10:53:32	\N
600	64	\N	\N	Asst Prof III	Regular	315504.00	College of Health Sciences	\N	\N	\N	\N	2022-06-21 10:53:32	\N
601	64	\N	\N	Asst Prof IV	Regular	380352.00	MSU CSH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
602	64	\N	\N	Asst. Prof. III	Contractual	179328.00	College of Health Science	\N	\N	\N	\N	2022-06-21 10:53:32	\N
603	64	\N	\N	Asst. Prof. IV	Regular	339660.00	MSU CSH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
604	64	\N	\N	Asst. Prof. IV	Regular	376212.00	MSU CSH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
605	64	\N	\N	Asst. Prof. IV	Regular	405972.00	MSU CSH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
606	64	\N	\N	Prof. V	Regular	1679268.00	CHS	\N	\N	\N	\N	2022-06-21 10:53:32	\N
607	64	\N	\N	Prof. V	Regular	1712196.00	CHS	\N	\N	\N	\N	2022-06-21 10:53:32	\N
608	65	\N	\N	AP 4	Regular	253908.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
609	65	\N	\N	AP 4	Regular	288684.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
610	65	\N	\N	AP 4	Regular	323472.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
611	65	\N	\N	AP 4	Regular	328788.00	MSU CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
612	65	\N	\N	AP 4	Regular	363072.00	MSU CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
613	65	\N	\N	AP 4	Regular	397356.00	MSU CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
614	65	\N	\N	AP 4	Regular	401736.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
615	65	\N	\N	AP 4	Regular	429540.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
616	65	\N	\N	AP 4	Regular	429540.00	MSU CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
617	65	\N	\N	AP 4	Regular	464628.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
618	65	\N	\N	AP 4	Regular	496956.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
619	65	\N	\N	AP 4	Regular	531528.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
620	65	\N	\N	AP 4	Regular	549792.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
621	65	\N	\N	AP 4	Regular	568056.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
622	65	\N	\N	Assistant Professor 4	Regular	230820.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
623	65	\N	\N	ASSISTANT PROFESSOR IV	Regular	204708.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
624	65	\N	\N	ASSISTANT PROFESSOR IV	Regular	225180.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
625	66	\N	\N	Assistant Professor V	Regular	377604.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
626	66	\N	\N	Assistant Professor V	Regular	442140.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
627	66	\N	\N	Assistant Professor V	Regular	506676.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
628	66	\N	\N	Assistant Professor V	Regular	577500.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
629	66	\N	\N	Assoc Prof V	Casual	252420.00		\N	\N	\N	\N	2022-06-21 10:53:32	\N
630	66	\N	\N	ASSOCIATE PROFESSOR V	Regular	246252.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
631	66	\N	\N	ASSOCIATE PROFESSOR V	Regular	277668.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
632	66	\N	\N	ASSOCIATE PROFESSOR V	Regular	305436.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
633	67	\N	\N	ASSO P 5	Regular	240240.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
634	67	\N	\N	Assoc Prof V	Regular	564996.00	CBAA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
635	67	\N	\N	Assoc. Prof V	Regular	264264.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
636	67	\N	\N	Assoc. Prof V	Regular	290688.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
637	67	\N	\N	Assoc. Prof V	Regular	356208.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
638	67	\N	\N	Assoc. Prof V	Regular	428412.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
639	67	\N	\N	Assoc. Prof V	Regular	493632.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
640	67	\N	\N	Assoc. Prof V	Regular	558852.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
641	67	\N	\N	Assoc. Prof V	Regular	637344.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
642	67	\N	\N	Assoc. Prof. V	Regular	363180.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
643	67	\N	\N	Assoc.Prof. V	Regular	645204.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
644	67	\N	\N	Assoc.Prof. V	Regular	728784.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
645	67	\N	\N	Assoc.Prof. V	Regular	823176.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
646	68	\N	\N	ASSOCIATE PROFESSOR V	Contractual	60984.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
647	69	\N	\N	Asst. Prof. IV	Regular	401424.00	CBAA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
648	69	\N	\N	Asst. Prof. IV	Regular	428316.00	CBAA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
649	69	\N	\N	Asst. Prof. IV	Regular	457020.00	CBAA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
650	69	\N	\N	Asst. Prof. IV	Regular	487644.00	CBAA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
651	69	\N	\N	Asst. Prof. IV	Regular	505908.00	CBAA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
652	69	\N	\N	Asst. Prof. IV	Regular	524172.00	CBAA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
653	69	\N	\N	Inst. II	Regular	276540.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
654	69	\N	\N	INSTRUCTOR II	Regular	152964.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
655	69	\N	\N	INSTRUCTOR II	Regular	168264.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
656	69	\N	\N	INSTRUCTOR II	Regular	185088.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
657	69	\N	\N	INSTRUCTOR II	Regular	205068.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
658	69	\N	\N	INSTRUCTOR II	Regular	225060.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
659	69	\N	\N	INSTRUCTOR II	Regular	245040.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
660	69	\N	\N	INSTRUCTOR II	Regular	265032.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
661	70	\N	\N	AP 4	Regular	253908.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
662	70	\N	\N	AP 4	Regular	288684.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
663	70	\N	\N	AP 4	Regular	323472.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
664	70	\N	\N	AP 4	Regular	328788.00	MSU CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
665	70	\N	\N	AP 4	Regular	363072.00	MSU CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
666	70	\N	\N	Assistant Professor 4	Regular	230820.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
667	70	\N	\N	ASSISTANT PROFESSOR IV	Regular	204708.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
668	70	\N	\N	ASSISTANT PROFESSOR IV	Regular	225180.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
669	70	\N	\N	Assoc Prof. V	Regular	811020.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
670	70	\N	\N	Assoc Prof. V	Regular	914880.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
671	70	\N	\N	Assoc Prof. V	Regular	933456.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
672	70	\N	\N	Assoc Prof. V	Regular	952032.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
673	70	\N	\N	Assoc. Prof. V	Regular	487248.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
674	70	\N	\N	Assoc. Prof. V	Regular	552768.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
675	70	\N	\N	Assoc. Prof. V	Regular	621912.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
676	70	\N	\N	Assoc. Prof. V	Regular	709272.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
677	70	\N	\N	Assoc. Prof. V	Regular	799044.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
678	71	\N	\N	INSTRUCTOR III	Regular	150552.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
679	71	\N	\N	INSTRUCTOR III	Regular	165612.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
680	71	\N	\N	INSTRUCTOR III	Regular	182172.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
681	72	\N	\N	Prof V	Regular	371604.00	Coll of Business Admi	\N	\N	\N	\N	2022-06-21 10:53:32	\N
682	72	\N	\N	Prof V	Regular	486252.00	Coll of Law	\N	\N	\N	\N	2022-06-21 10:53:32	\N
683	72	\N	\N	Prof V	Regular	600888.00	Coll of Law	\N	\N	\N	\N	2022-06-21 10:53:32	\N
684	72	\N	\N	Prof V	Regular	715536.00	Coll of Law	\N	\N	\N	\N	2022-06-21 10:53:32	\N
685	72	\N	\N	Prof V	Regular	830172.00	Coll of Law	\N	\N	\N	\N	2022-06-21 10:53:32	\N
686	72	\N	\N	Prof V	Regular	1017804.00	Coll of Law	\N	\N	\N	\N	2022-06-21 10:53:32	\N
687	72	\N	\N	Prof V	Regular	1220832.00	Coll of Law	\N	\N	\N	\N	2022-06-21 10:53:32	\N
688	72	\N	\N	Prof. V	Regular	848532.00	Coll. of Law	\N	\N	\N	\N	2022-06-21 10:53:32	\N
689	72	\N	\N	PROFESSOR V	Regular	299616.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
690	72	\N	\N	PROFESSOR V	Regular	329580.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
691	72	\N	\N	PROFESSOR V	Regular	362544.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
692	73	\N	\N	INSTRUCTOR I	Regular	134004.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
693	73	\N	\N	INSTRUCTOR I	Regular	147408.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
694	73	\N	\N	INSTRUCTOR I	Regular	162144.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
695	73	\N	\N	INSTRUCTOR I	Regular	181428.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
696	73	\N	\N	INSTRUCTOR I	Regular	200712.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
697	73	\N	\N	INSTRUCTOR I	Regular	219996.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
698	73	\N	\N	INSTRUCTOR I	Regular	239280.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
699	74	\N	\N	Computer Programmer I	Contractual	142044.00	College of Information Technology	\N	\N	\N	\N	2022-06-21 10:53:32	\N
700	75	\N	\N	Assistant Professor 5	Regular	270876.00	C B A	\N	\N	\N	\N	2022-06-21 10:53:32	\N
701	75	\N	\N	Assistant Professor 5	Regular	297960.00	C B A	\N	\N	\N	\N	2022-06-21 10:53:32	\N
702	75	\N	\N	Assistant Professor 5	Regular	363180.00	C B A	\N	\N	\N	\N	2022-06-21 10:53:32	\N
703	75	\N	\N	Assistant Professor V	Regular	370332.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
704	75	\N	\N	Assistant Professor V	Regular	435216.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
705	75	\N	\N	Assistant Professor V	Regular	500112.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
706	75	\N	\N	Assistant Professor V	Regular	564996.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
707	75	\N	\N	Assistant Professor V	Regular	571212.00	CBAA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
708	75	\N	\N	Assoc Prof V	Regular	240240.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
709	75	\N	\N	Assoc Prof V	Regular	264264.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
710	76	\N	\N	PROF II	Regular	347064.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
711	76	\N	\N	PROFESSOR I	Regular	279852.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
712	76	\N	\N	PROFESSOR I	Regular	307836.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
713	76	\N	\N	Professor II	Regular	315516.00	CBA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
714	77	\N	\N	INSTRUCTOR I	Regular	134004.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
715	77	\N	\N	INSTRUCTOR I	Regular	147408.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
716	77	\N	\N	INSTRUCTOR I	Regular	162144.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
717	78	\N	\N	Assoc. Prof. V	Regular	284604.00	C B A	\N	\N	\N	\N	2022-06-21 10:53:32	\N
718	78	\N	\N	Assoc. Prof. V	Regular	313068.00	C B A	\N	\N	\N	\N	2022-06-21 10:53:32	\N
719	78	\N	\N	Assoc. Prof. V	Regular	377604.00	C B A	\N	\N	\N	\N	2022-06-21 10:53:32	\N
720	78	\N	\N	ASSOCIATE PROFESSOR V	Regular	252420.00	College of Business Administratio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
721	78	\N	\N	Prof IV	Regular	443076.00	C B A	\N	\N	\N	\N	2022-06-21 10:53:32	\N
722	78	\N	\N	Prof IV	Regular	546060.00	C B A	\N	\N	\N	\N	2022-06-21 10:53:32	\N
723	78	\N	\N	Prof IV	Regular	649056.00	C B A	\N	\N	\N	\N	2022-06-21 10:53:32	\N
724	78	\N	\N	Prof. VI	Regular	877188.00	CBAA	\N	\N	\N	\N	2022-06-21 10:53:32	\N
725	78	\N	\N	SUC VP IV	Regular	697584.00	OVPAF	\N	\N	\N	\N	2022-06-21 10:53:32	\N
726	78	\N	\N	SUC VP IV	Regular	812208.00	OVPAF	\N	\N	\N	\N	2022-06-21 10:53:32	\N
727	78	\N	\N	SUC VP IV	Regular	1058568.00	OVPAF	\N	\N	\N	\N	2022-06-21 10:53:32	\N
728	78	\N	\N	SUC VP IV	Regular	1348680.00	OVPAF	\N	\N	\N	\N	2022-06-21 10:53:32	\N
729	79	\N	\N	PROF IV	Regular	288096.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
730	79	\N	\N	Prof. IV	Regular	571692.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
731	79	\N	\N	Prof. IV	Regular	674412.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
732	79	\N	\N	Prof. IV	Regular	777132.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
733	79	\N	\N	Prof. IV	Regular	785688.00	Col. of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
734	79	\N	\N	Prof. VI	Regular	877188.00	Coll. of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
735	79	\N	\N	Professor IV	Regular	324840.00	Coll of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
736	79	\N	\N	Professor IV	Regular	357324.00	Coll of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
737	79	\N	\N	Professor IV	Regular	460164.00	Coll of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
738	80	\N	\N	PROFESSOR V	Regular	314784.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
739	80	\N	\N	PROFESSOR V	Regular	346260.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
740	80	\N	\N	PROFESSOR V	Regular	380892.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
741	81	\N	\N	PROF 4	Regular	375384.00	EDUCATIO	\N	\N	\N	\N	2022-06-21 10:53:32	\N
742	81	\N	\N	PROF 4	Regular	477960.00	EDUCATIO	\N	\N	\N	\N	2022-06-21 10:53:32	\N
743	81	\N	\N	Prof. VI	Regular	495168.00	EDUCATIO	\N	\N	\N	\N	2022-06-21 10:53:32	\N
744	81	\N	\N	Prof. VI	Regular	622512.00	EDUCATIO	\N	\N	\N	\N	2022-06-21 10:53:32	\N
745	81	\N	\N	Prof. VI	Regular	749856.00	EDUCATIO	\N	\N	\N	\N	2022-06-21 10:53:32	\N
746	81	\N	\N	Prof. VI	Regular	877188.00	EDUCATIO	\N	\N	\N	\N	2022-06-21 10:53:32	\N
747	81	\N	\N	Prof. VI	Regular	1084824.00	EDUCATIO	\N	\N	\N	\N	2022-06-21 10:53:32	\N
748	81	\N	\N	Prof. VI	Regular	1312584.00	EDUCATIO	\N	\N	\N	\N	2022-06-21 10:53:32	\N
749	81	\N	\N	Prof. VI	Regular	1588152.00	EDUCATIO	\N	\N	\N	\N	2022-06-21 10:53:32	\N
750	81	\N	\N	Prof. VI	Regular	1952952.00	EDUCATIO	\N	\N	\N	\N	2022-06-21 10:53:32	\N
751	81	\N	\N	Professor 4	Regular	341256.00	EDUCATIO	\N	\N	\N	\N	2022-06-21 10:53:32	\N
752	81	\N	\N	PROFESSOR IV	Regular	302676.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
753	81	\N	\N	PROFESSOR IV	Regular	332940.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
754	81	\N	\N	Professor VI	Regular	886836.00	Coll of Educ.	\N	\N	\N	\N	2022-06-21 10:53:32	\N
755	82	\N	\N	ASSOCIATE PROFESSOR IV	Regular	248772.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
756	83	\N	\N	Assistant Professor 5	Regular	291696.00	EDUCATIO	\N	\N	\N	\N	2022-06-21 10:53:32	\N
757	83	\N	\N	ASSOCIATE PROFESSOR V	Regular	258732.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
758	83	\N	\N	ASSOCIATE PROFESSOR V	Regular	284604.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
759	83	\N	\N	Prof VI	Regular	334392.00	Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
760	83	\N	\N	Prof VI	Regular	367836.00	Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
761	83	\N	\N	Prof VI	Regular	495168.00	Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
762	83	\N	\N	Prof VI	Regular	622512.00	Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
763	83	\N	\N	Prof VI	Regular	749856.00	Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
764	83	\N	\N	Prof. VI	Regular	886836.00	Educ.	\N	\N	\N	\N	2022-06-21 10:53:32	\N
765	83	\N	\N	Prof. VI	Regular	896592.00	Educ.	\N	\N	\N	\N	2022-06-21 10:53:32	\N
766	83	\N	\N	Prof. VI	Regular	1084824.00	Educ.	\N	\N	\N	\N	2022-06-21 10:53:32	\N
767	83	\N	\N	Prof. VI	Regular	1348680.00	Educ.	\N	\N	\N	\N	2022-06-21 10:53:32	\N
768	83	\N	\N	Prof. VI	Regular	1636116.00	Educ.	\N	\N	\N	\N	2022-06-21 10:53:32	\N
769	83	\N	\N	Prof. VI	Regular	1984824.00	Educ.	\N	\N	\N	\N	2022-06-21 10:53:32	\N
770	83	\N	\N	Prof. VI	Regular	2024520.00	Educ.	\N	\N	\N	\N	2022-06-21 10:53:32	\N
771	83	\N	\N	Prof. VI	Regular	2064216.00	Educ.	\N	\N	\N	\N	2022-06-21 10:53:32	\N
772	84	\N	\N	AP IV	Regular	434412.00	EDU	\N	\N	\N	\N	2022-06-21 10:53:32	\N
773	84	\N	\N	AP IV	Regular	464628.00	EDU	\N	\N	\N	\N	2022-06-21 10:53:32	\N
774	84	\N	\N	AP IV	Regular	496956.00	EDU	\N	\N	\N	\N	2022-06-21 10:53:32	\N
775	84	\N	\N	AP IV	Regular	531528.00	EDU	\N	\N	\N	\N	2022-06-21 10:53:32	\N
776	84	\N	\N	ASSISTANT PROF IV	Regular	277380.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
777	84	\N	\N	ASSISTANT PROF IV	Regular	313092.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
778	84	\N	\N	ASSISTANT PROFESSOR IV	Regular	199716.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
779	84	\N	\N	ASSISTANT PROFESSOR IV	Regular	219684.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
780	84	\N	\N	ASSISTANT PROFESSOR IV	Regular	241656.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
781	84	\N	\N	Asst. Prof. IV	Regular	348816.00	MSU College Of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
782	84	\N	\N	Asst. Prof. IV	Regular	384528.00	MSU College Of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
783	84	\N	\N	Asst. Prof. IV	Regular	401736.00	Educ.	\N	\N	\N	\N	2022-06-21 10:53:32	\N
784	84	\N	\N	Asst. Prof. IV	Regular	429540.00	MSU College Of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
785	85	\N	\N	PROFESSOR VI	Contractual	60984.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
786	86	\N	\N	ASSOCIATE PROFESSOR V	Contractual	0.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
787	87	\N	\N	Lecturer	Contractual	0.00	Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
788	87	\N	\N	Prof. VI	Regular	809172.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
789	87	\N	\N	Prof. VI	Regular	936696.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
790	87	\N	\N	PROFESSOR VI	Regular	335568.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
791	87	\N	\N	PROFESSOR VI	Regular	369120.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
792	87	\N	\N	PROFESSOR VI	Regular	416184.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
793	87	\N	\N	PROFESSOR VI	Regular	543768.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
794	87	\N	\N	PROFESSOR VI	Regular	671340.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
795	88	\N	\N	Assistant Professor 5	Regular	264264.00	EDUCATIO	\N	\N	\N	\N	2022-06-21 10:53:32	\N
796	88	\N	\N	Assistant Professor 5	Regular	290688.00	EDUCATIO	\N	\N	\N	\N	2022-06-21 10:53:32	\N
797	88	\N	\N	Assistant Professor 5	Regular	356208.00	EDUCATIO	\N	\N	\N	\N	2022-06-21 10:53:32	\N
798	88	\N	\N	Assistant Professor 5	Regular	421728.00	EDUCATIO	\N	\N	\N	\N	2022-06-21 10:53:32	\N
799	88	\N	\N	Assistant Professor 5	Regular	487248.00	EDUCATIO	\N	\N	\N	\N	2022-06-21 10:53:32	\N
800	88	\N	\N	Assistant Professor 5	Regular	552768.00	EDUCATIO	\N	\N	\N	\N	2022-06-21 10:53:32	\N
801	88	\N	\N	Assistant Professor 5	Regular	558852.00	EDUC	\N	\N	\N	\N	2022-06-21 10:53:32	\N
802	88	\N	\N	Assistant Professor 5	Regular	564996.00	EDUCATIO	\N	\N	\N	\N	2022-06-21 10:53:32	\N
803	88	\N	\N	Assistant Professor 5	Regular	637344.00	EDUCATIO	\N	\N	\N	\N	2022-06-21 10:53:32	\N
804	88	\N	\N	Assistant Professor 5	Regular	718956.00	EDUCATIO	\N	\N	\N	\N	2022-06-21 10:53:32	\N
805	88	\N	\N	Assistant Professor V	Regular	738732.00	EDUCATIO	\N	\N	\N	\N	2022-06-21 10:53:32	\N
806	88	\N	\N	Assistant Professor V	Regular	835524.00	EDUCATIO	\N	\N	\N	\N	2022-06-21 10:53:32	\N
807	88	\N	\N	Assistant Professor V	Regular	944988.00	EDUCATIO	\N	\N	\N	\N	2022-06-21 10:53:32	\N
808	88	\N	\N	Assistant Professor V	Regular	963888.00	EDUCATIO	\N	\N	\N	\N	2022-06-21 10:53:32	\N
809	88	\N	\N	Assistant Professor V	Regular	982788.00	EDUCATIO	\N	\N	\N	\N	2022-06-21 10:53:32	\N
810	88	\N	\N	Instr. II	Regular	152964.00	Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
811	88	\N	\N	Instr. II	Regular	168264.00	Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
812	88	\N	\N	INSTRUCTOR II	Regular	145608.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
813	88	\N	\N	Instructor II	Regular	172464.00	EDUC	\N	\N	\N	\N	2022-06-21 10:53:32	\N
814	89	\N	\N	Asst. Prof IV	Regular	380352.00	educ	\N	\N	\N	\N	2022-06-21 10:53:32	\N
815	89	\N	\N	Asst. Prof. IV	Regular	266568.00	EDUCATIO	\N	\N	\N	\N	2022-06-21 10:53:32	\N
816	89	\N	\N	Asst. Prof. IV	Regular	303108.00	Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
817	89	\N	\N	Asst. Prof. IV	Regular	339660.00	Coll of Educ	\N	\N	\N	\N	2022-06-21 10:53:32	\N
818	89	\N	\N	Asst. Prof. IV	Regular	376212.00	Coll of Educ	\N	\N	\N	\N	2022-06-21 10:53:32	\N
819	89	\N	\N	Asst. Prof. IV	Regular	410580.00	Coll of Educ	\N	\N	\N	\N	2022-06-21 10:53:32	\N
820	89	\N	\N	Asst. Prof. IV	Regular	438384.00	Coll of Educ	\N	\N	\N	\N	2022-06-21 10:53:32	\N
821	89	\N	\N	Asst. Prof. IV	Regular	468084.00	Coll of Educ	\N	\N	\N	\N	2022-06-21 10:53:32	\N
822	89	\N	\N	Asst. Prof. IV	Regular	499800.00	Coll of Educ	\N	\N	\N	\N	2022-06-21 10:53:32	\N
823	89	\N	\N	Asst. Prof. IV	Regular	518064.00	Coll of Educ	\N	\N	\N	\N	2022-06-21 10:53:32	\N
824	89	\N	\N	Asst. Prof. IV	Regular	542508.00	Coll of Educ	\N	\N	\N	\N	2022-06-21 10:53:32	\N
825	89	\N	\N	Instr III	Regular	158184.00	Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
826	89	\N	\N	Instr III	Regular	174000.00	Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
827	89	\N	\N	Instr III	Regular	191400.00	Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
828	89	\N	\N	Instr III	Regular	214068.00	Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
829	89	\N	\N	INSTRUCTOR III	Regular	150552.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
830	90	\N	\N	Assistant Professor 5	Regular	291696.00	EDUCATIO	\N	\N	\N	\N	2022-06-21 10:53:32	\N
831	90	\N	\N	ASSOCIATE PROFESSOR V	Regular	258732.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
832	90	\N	\N	ASSOCIATE PROFESSOR V	Regular	284604.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
833	91	\N	\N	Lecturer	Contractual	4488.00	Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
834	91	\N	\N	Prof V	Regular	848532.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
835	91	\N	\N	Prof VI	Regular	877188.00	Coll. of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
836	91	\N	\N	Prof. V	Regular	299616.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
837	91	\N	\N	Prof. V	Regular	610092.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
838	91	\N	\N	Prof. V	Regular	724704.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
839	91	\N	\N	Prof. V	Regular	839304.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
840	91	\N	\N	Professor V	Regular	337824.00	Coll of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
841	91	\N	\N	Professor V	Regular	371604.00	Coll of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
842	91	\N	\N	Professor V	Regular	486252.00	Coll of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
843	92	\N	\N	PROF 6	Regular	406032.00	EDUCATIO	\N	\N	\N	\N	2022-06-21 10:53:32	\N
844	92	\N	\N	PROF 6	Regular	533628.00	EDUCATIO	\N	\N	\N	\N	2022-06-21 10:53:32	\N
845	92	\N	\N	PROF 6	Regular	661224.00	EDUCATIO	\N	\N	\N	\N	2022-06-21 10:53:32	\N
846	92	\N	\N	PROF 6	Regular	671340.00	EDUCATIO	\N	\N	\N	\N	2022-06-21 10:53:32	\N
847	92	\N	\N	PROF 6	Regular	798912.00	EDUCATIO	\N	\N	\N	\N	2022-06-21 10:53:32	\N
848	92	\N	\N	Professor 6	Regular	369120.00	EDUCATIO	\N	\N	\N	\N	2022-06-21 10:53:32	\N
849	92	\N	\N	PROFESSOR VI	Regular	327372.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
850	92	\N	\N	PROFESSOR VI	Regular	360108.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
851	93	\N	\N	AP IV	Regular	230820.00	Coll of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
852	93	\N	\N	AP IV	Regular	253908.00	Coll of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
853	93	\N	\N	AP IV	Regular	288684.00	Coll of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
854	93	\N	\N	AP IV	Regular	323472.00	Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
855	93	\N	\N	AP IV	Regular	328788.00	Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
856	93	\N	\N	AP IV	Regular	363072.00	Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
857	93	\N	\N	AP IV	Regular	397356.00	Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
858	93	\N	\N	AP IV	Regular	424716.00	Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
859	93	\N	\N	AP IV	Regular	453948.00	Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
860	93	\N	\N	AP IV	Regular	485196.00	Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
861	93	\N	\N	ASSISTANT PROFESSOR IV	Regular	190092.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
862	93	\N	\N	ASSISTANT PROFESSOR IV	Regular	209100.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
863	94	\N	\N	ASSOCIATE PROFESSOR III	Regular	222120.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
864	94	\N	\N	Consultant	Contractual	120000.00	MSU OP	\N	\N	\N	\N	2022-06-21 10:53:32	\N
865	94	\N	\N	Consultant	Contractual	180000.00	OP	\N	\N	\N	\N	2022-06-21 10:53:32	\N
866	95	\N	\N	PROF 4	Regular	375384.00	EDUCATIO	\N	\N	\N	\N	2022-06-21 10:53:32	\N
867	95	\N	\N	PROF 4	Regular	477960.00	EDUCATIO	\N	\N	\N	\N	2022-06-21 10:53:32	\N
868	95	\N	\N	PROF 4	Regular	580536.00	EDUCATIO	\N	\N	\N	\N	2022-06-21 10:53:32	\N
869	95	\N	\N	PROF 4	Regular	589548.00	MSU EDUCATIO	\N	\N	\N	\N	2022-06-21 10:53:32	\N
870	95	\N	\N	PROF 4	Regular	691944.00	MSU EDUCATIO	\N	\N	\N	\N	2022-06-21 10:53:32	\N
871	95	\N	\N	PROF 4	Regular	794328.00	MSU EDUCATIO	\N	\N	\N	\N	2022-06-21 10:53:32	\N
872	95	\N	\N	PROF VI	Regular	1058568.00	MSU EDUCATIO	\N	\N	\N	\N	2022-06-21 10:53:32	\N
873	95	\N	\N	Prof. 4	Regular	803064.00	Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
874	95	\N	\N	Prof. VI	Regular	877188.00	Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
875	95	\N	\N	Professor 4	Regular	341256.00	EDUCATIO	\N	\N	\N	\N	2022-06-21 10:53:32	\N
876	95	\N	\N	PROFESSOR IV	Regular	302676.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
877	95	\N	\N	PROFESSOR IV	Regular	332940.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
878	96	\N	\N	PROFESSOR VI	Contractual	0.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
879	97	\N	\N	ASSISTANT PROF. IV	Regular	306588.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
880	97	\N	\N	ASSISTANT PROF. IV	Regular	339780.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
881	97	\N	\N	ASSISTANT PROF. IV	Regular	372960.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
882	97	\N	\N	ASSISTANT PROF. IV	Regular	406152.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
883	97	\N	\N	ASSISTANT PROF. IV	Regular	434412.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
884	97	\N	\N	ASSISTANT PROF. IV	Regular	464628.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
885	97	\N	\N	ASSISTANT PROF. IV	Regular	496956.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
886	97	\N	\N	ASSISTANT PROF. IV	Regular	531528.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
887	97	\N	\N	ASSISTANT PROFESSOR IV	Regular	225960.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
888	97	\N	\N	ASSISTANT PROFESSOR IV	Regular	248556.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
889	97	\N	\N	ASSISTANT PROFESSOR IV	Regular	273408.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
890	97	\N	\N	Asst. Prof. IV	Regular	549792.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
891	98	\N	\N	Ass. Prof V	Regular	356208.00	Educ	\N	\N	\N	\N	2022-06-21 10:53:32	\N
892	98	\N	\N	Ass. Prof V	Regular	421728.00	Educ	\N	\N	\N	\N	2022-06-21 10:53:32	\N
893	98	\N	\N	Ass. Prof V	Regular	487248.00	Educ	\N	\N	\N	\N	2022-06-21 10:53:32	\N
894	98	\N	\N	Ass. Prof V	Regular	552768.00	Educ	\N	\N	\N	\N	2022-06-21 10:53:32	\N
895	98	\N	\N	Ass. Prof V	Regular	629592.00	Educ	\N	\N	\N	\N	2022-06-21 10:53:32	\N
896	98	\N	\N	Ass. Prof V	Regular	709272.00	Educ	\N	\N	\N	\N	2022-06-21 10:53:32	\N
897	98	\N	\N	Assoc Prof. V	Regular	966996.00	Educ	\N	\N	\N	\N	2022-06-21 10:53:32	\N
898	98	\N	\N	Assoc. Prof. V	Regular	718956.00	Educ	\N	\N	\N	\N	2022-06-21 10:53:32	\N
899	98	\N	\N	Assoc. Prof. V	Regular	811020.00	Educ	\N	\N	\N	\N	2022-06-21 10:53:32	\N
900	98	\N	\N	Assoc. Prof. V	Regular	914880.00	Educ	\N	\N	\N	\N	2022-06-21 10:53:32	\N
901	98	\N	\N	Assoc. Prof. V	Regular	933456.00	Educ	\N	\N	\N	\N	2022-06-21 10:53:32	\N
902	98	\N	\N	Assoc. Prof. V	Regular	952032.00	Educ	\N	\N	\N	\N	2022-06-21 10:53:32	\N
903	98	\N	\N	Asst. Prof. V	Regular	558852.00	Educ.	\N	\N	\N	\N	2022-06-21 10:53:32	\N
904	98	\N	\N	Instructor 1	Regular	140796.00	Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
905	98	\N	\N	Instructor 1	Regular	154872.00	Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
906	98	\N	\N	Instructor 1	Regular	170364.00	Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
907	98	\N	\N	Instructor 1	Regular	174624.00	Educ	\N	\N	\N	\N	2022-06-21 10:53:32	\N
908	98	\N	\N	INSTRUCTOR I	Regular	137352.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
909	99	\N	\N	Lecturer	Contractual	0.00	Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
910	99	\N	\N	PROF 6	Regular	406032.00	EDUCATIO	\N	\N	\N	\N	2022-06-21 10:53:32	\N
911	99	\N	\N	PROF 6	Regular	533628.00	EDUCATIO	\N	\N	\N	\N	2022-06-21 10:53:32	\N
912	99	\N	\N	PROF 6	Regular	661224.00	EDUCATIO	\N	\N	\N	\N	2022-06-21 10:53:32	\N
913	99	\N	\N	PROF 6	Regular	671340.00	EDUCATIO	\N	\N	\N	\N	2022-06-21 10:53:32	\N
914	99	\N	\N	PROF 6	Regular	798912.00	EDUCATIO	\N	\N	\N	\N	2022-06-21 10:53:32	\N
915	99	\N	\N	PROF 6	Regular	926496.00	EDUCATIO	\N	\N	\N	\N	2022-06-21 10:53:32	\N
916	99	\N	\N	Professor 6	Regular	369120.00	EDUCATIO	\N	\N	\N	\N	2022-06-21 10:53:32	\N
917	99	\N	\N	PROFESSOR VI	Regular	327372.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
918	99	\N	\N	PROFESSOR VI	Regular	360108.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
919	100	\N	\N	PROFESSOR VI	Regular	327372.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
920	101	\N	\N	Assistant Professor 4	Regular	332256.00	EDUCATION 	\N	\N	\N	\N	2022-06-21 10:53:32	\N
921	101	\N	\N	Assistant Professor 4	Regular	387336.00	EDUCATIO	\N	\N	\N	\N	2022-06-21 10:53:32	\N
922	101	\N	\N	Assistant Professor 4	Regular	442404.00	EDUCATIO	\N	\N	\N	\N	2022-06-21 10:53:32	\N
923	101	\N	\N	Assistant Professor 4	Regular	497484.00	EDUCATIO	\N	\N	\N	\N	2022-06-21 10:53:32	\N
924	101	\N	\N	Assistant Professor IV	Regular	294696.00	Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
925	101	\N	\N	ASSOCIATE PROFESSOR IV	Regular	261372.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
926	101	\N	\N	ASSOCIATE PROFESSOR IV	Regular	287508.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
927	101	\N	\N	Prof. V	Regular	697584.00	EDUCATIO	\N	\N	\N	\N	2022-06-21 10:53:32	\N
928	101	\N	\N	Prof. V	Regular	812208.00	EDUCATIO	\N	\N	\N	\N	2022-06-21 10:53:32	\N
929	101	\N	\N	Prof. VI	Regular	877188.00	Coll. of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
930	102	\N	\N	PROFESSOR VI	Contractual	60984.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
931	103	\N	\N	PROFESSOR VI	Regular	303996.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
932	104	\N	\N	ASSOCIATE PROFESSOR V	Contractual	0.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
933	105	\N	\N	PROFESSOR III	Contractual	60984.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
934	106	\N	\N	ASSISTANT PROFESSOR I	Regular	225960.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
935	106	\N	\N	ASSISTANT PROFESSOR I	Regular	248556.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
936	106	\N	\N	ASSISTANT PROFESSOR I	Regular	273408.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
937	106	\N	\N	ASSISTANT PROFESSOR I	Regular	306588.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
938	106	\N	\N	ASSISTANT PROFESSOR I	Regular	339780.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
939	106	\N	\N	ASSISTANT PROFESSOR I	Regular	372960.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
940	106	\N	\N	ASSISTANT PROFESSOR I	Regular	406152.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
941	107	\N	\N	PROFESSOR VI	Contractual	0.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
942	108	\N	\N	INSTRUCTOR I	Contractual	60984.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
943	109	\N	\N	AP IV	Regular	376212.00	Coll. of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
944	109	\N	\N	AP IV	Regular	380352.00	Educ.	\N	\N	\N	\N	2022-06-21 10:53:32	\N
945	109	\N	\N	AP IV	Regular	405972.00	Educ.	\N	\N	\N	\N	2022-06-21 10:53:32	\N
946	109	\N	\N	Assoc. Prof. V	Regular	699720.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
947	109	\N	\N	Assoc. Prof. V	Regular	787248.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
948	109	\N	\N	Assoc. Prof. V	Regular	885732.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
949	109	\N	\N	Assoc. Prof. V	Regular	904308.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
950	109	\N	\N	Assoc. Prof. V	Regular	922884.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
951	109	\N	\N	Inst. III	Regular	229344.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
952	109	\N	\N	Inst. III	Regular	252936.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
953	109	\N	\N	Inst. III	Regular	284904.00	Educ.	\N	\N	\N	\N	2022-06-21 10:53:32	\N
954	109	\N	\N	INSTRUCTOR III	Regular	150552.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
955	109	\N	\N	INSTRUCTOR III	Regular	165612.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
956	109	\N	\N	INSTRUCTOR III	Regular	182172.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
957	109	\N	\N	INSTRUCTOR III	Regular	205764.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
958	110	\N	\N	PROFESSOR VI	Contractual	0.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
959	111	\N	\N	PROFESSOR IV	Contractual	60984.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
960	112	\N	\N	AP 4	Regular	253908.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
961	112	\N	\N	AP 4	Regular	288684.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
962	112	\N	\N	AP 4	Regular	323472.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
963	112	\N	\N	AP 4	Regular	328788.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
964	112	\N	\N	AP 4	Regular	363072.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
965	112	\N	\N	AP 4	Regular	397356.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
966	112	\N	\N	AP 4	Regular	401736.00	Eng'g.	\N	\N	\N	\N	2022-06-21 10:53:32	\N
967	112	\N	\N	AP 4	Regular	429540.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
968	112	\N	\N	AP 4	Regular	459264.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
969	112	\N	\N	AP 4	Regular	464628.00	Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
970	112	\N	\N	AP 4	Regular	496956.00	Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
971	112	\N	\N	Assistant Professor 4	Regular	230820.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
972	112	\N	\N	ASSISTANT PROFESSOR IV	Regular	204708.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
973	112	\N	\N	ASSISTANT PROFESSOR IV	Regular	225180.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
974	113	\N	\N	Assistant Professor 5	Regular	291696.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
975	113	\N	\N	Assistant Professor 5	Regular	320868.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
976	113	\N	\N	Assistant Professor 5	Regular	385032.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
977	113	\N	\N	Assistant Professor 5	Regular	449184.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
978	113	\N	\N	Assistant Professor 5	Regular	456384.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
979	113	\N	\N	Assistant Professor 5	Regular	520116.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
980	113	\N	\N	Assistant Professor 5	Regular	583848.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
981	113	\N	\N	ASSOCIATE PROFESSOR V	Regular	258732.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
982	113	\N	\N	ASSOCIATE PROFESSOR V	Regular	284604.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
983	114	\N	\N	VP-PPD	Regular	715536.00	Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
984	114	\N	\N	VP-PPD	Regular	830172.00	Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
985	114	\N	\N	VP-VPPD Asoc.. Prof V	Regular	292308.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
986	114	\N	\N	VP-VPPD Asoc.. Prof V	Regular	321540.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
987	114	\N	\N	VP-VPPD Asoc.. Prof V	Regular	353700.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
988	114	\N	\N	VP-VPPD Asoc.. Prof V	Regular	468312.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
989	114	\N	\N	VP-VPPD Asoc.. Prof V	Regular	582948.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
990	114	\N	\N	VP-VPPD Asoc.. Prof V	Regular	697584.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
991	115	\N	\N	AP IV	Regular	339660.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
992	115	\N	\N	AP IV	Regular	376212.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
993	115	\N	\N	INSTRUCTOR III	Regular	150552.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
994	115	\N	\N	INSTRUCTOR III	Regular	165612.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
995	115	\N	\N	INSTRUCTOR III	Regular	182172.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
996	115	\N	\N	INSTRUCTOR III	Regular	205764.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
997	115	\N	\N	INSTRUCTOR III	Regular	229344.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
998	116	\N	\N	Lecturer		4164.00	Coll.Engr.	\N	\N	\N	\N	2022-06-21 10:53:32	\N
999	116	\N	\N	PROF 6	Regular	406032.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1000	116	\N	\N	PROF 6	Regular	533628.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1001	116	\N	\N	PROF 6	Regular	661224.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1002	116	\N	\N	PROF 6	Regular	671340.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1003	116	\N	\N	PROF 6	Regular	798912.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1004	116	\N	\N	PROF 6	Regular	926496.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1005	116	\N	\N	Prof 6	Regular	926496.00	Eng'g.	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1006	116	\N	\N	PROF 6	Regular	1125432.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1007	116	\N	\N	PROF 6	Regular	1367100.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1008	116	\N	\N	Prof VI	Regular	327372.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1009	116	\N	\N	Prof VI	Regular	360108.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1010	116	\N	\N	Prof. 6	Regular	1404696.00	Engieering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1011	116	\N	\N	Professor 6	Regular	369120.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1012	117	\N	\N	Assistant Professor 5	Regular	583848.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1013	117	\N	\N	Assoc Prof V,	Regular	1011852.00	COE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1014	117	\N	\N	Assoc Prof V,	Regular	1031700.00	COE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1015	117	\N	\N	Assoc Prof. V	Regular	258732.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1016	117	\N	\N	Assoc Prof. V	Regular	284604.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1017	117	\N	\N	Assoc Prof. V	Regular	313068.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1018	117	\N	\N	Assoc Prof. V	Regular	377604.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1019	117	\N	\N	Assoc Prof. V	Regular	442140.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1020	117	\N	\N	Assoc Prof. V	Regular	506676.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1021	117	\N	\N	Assoc Prof. V	Regular	571212.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1022	117	\N	\N	Assoc Prof. V	Regular	860760.00	COE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1023	117	\N	\N	Assoc Prof. V	Regular	976080.00	COE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1024	117	\N	\N	Assoc Prof. V	Regular	995604.00	COE	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1025	117	\N	\N	Assoc. Prof. V	Regular	661212.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1026	117	\N	\N	Assoc. Prof. V	Regular	748824.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1027	117	\N	\N	Assoc. Prof. V	Regular	848040.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1028	118	\N	\N	Inst. III	Regular	287748.00	Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1029	118	\N	\N	Instr III	Regular	182820.00	Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1030	118	\N	\N	Instr III	Regular	201108.00	Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1031	118	\N	\N	Instr III	Regular	222756.00	Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1032	118	\N	\N	Instr III	Regular	244428.00	Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1033	118	\N	\N	Instr III	Regular	266088.00	Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1034	118	\N	\N	Instr III	Regular	287748.00	Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1035	118	\N	\N	INSTRUCTOR III	Regular	162144.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1036	118	\N	\N	INSTRUCTOR III	Regular	178356.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1037	119	\N	\N	Assistant Professor 3	Regular	269700.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1038	119	\N	\N	Assistant Professor 3	Regular	296676.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1039	119	\N	\N	Assistant Professor 3	Regular	346272.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1040	119	\N	\N	Assistant Professor 3	Regular	395892.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1041	119	\N	\N	Assistant Professor 3	Regular	402324.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1042	119	\N	\N	Assistant Professor 3	Regular	451440.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1043	119	\N	\N	Assistant Professor 3	Regular	500556.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1044	119	\N	\N	Assistant Professor 3	Regular	506064.00	Eng'g.	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1045	119	\N	\N	Assistant Professor 3	Regular	561036.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1046	119	\N	\N	ASSOCIATE PROFESSOR III	Regular	239208.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1047	119	\N	\N	ASSOCIATE PROFESSOR III	Regular	263124.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1048	120	\N	\N	A P  IV	Regular	209832.00	Eng'g	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1049	120	\N	\N	A P  IV	Regular	230820.00	Eng'g	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1050	120	\N	\N	A P  IV	Regular	253908.00	Eng'g	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1051	120	\N	\N	A.P IV	Regular	260220.00	Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1052	120	\N	\N	A.P IV	Regular	294504.00	Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1053	120	\N	\N	A.P IV	Regular	328788.00	Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1054	120	\N	\N	A.P IV	Regular	363072.00	Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1055	120	\N	\N	A.P. IV	Regular	401736.00	Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1056	120	\N	\N	A.P. IV	Regular	429540.00	Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1057	120	\N	\N	A.P. IV	Regular	459264.00	Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1058	120	\N	\N	A.P. IV	Regular	491040.00	Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1059	120	\N	\N	A.P. IV	Regular	496956.00	Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1060	120	\N	\N	A.P. IV	Regular	531528.00	Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1061	120	\N	\N	A.P. IV	Regular	549792.00	Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1062	120	\N	\N	ASSISTANT PROFESSOR IV	Regular	204708.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1063	120	\N	\N	Asst. Prof. IV	Contractual	549792.00	Coll.of Eng'g.	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1064	121	\N	\N	AP 4	Regular	253908.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1065	121	\N	\N	AP 4	Regular	288684.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1066	121	\N	\N	AP 4	Regular	323472.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1067	121	\N	\N	AP 4	Regular	328788.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1068	121	\N	\N	AP 4	Regular	363072.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1069	121	\N	\N	AP 4	Regular	397356.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1070	121	\N	\N	AP 4	Regular	401736.00	Eng'g.	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1071	121	\N	\N	Assistant Professor 4	Regular	230820.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1072	121	\N	\N	ASSISTANT PROFESSOR IV	Regular	204708.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1073	121	\N	\N	ASSISTANT PROFESSOR IV	Regular	225180.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1074	122	\N	\N	Assistant Professor 5	Regular	291696.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1075	122	\N	\N	Assistant Professor 5	Regular	320868.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1076	122	\N	\N	Assistant Professor 5	Regular	385032.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1077	122	\N	\N	Assistant Professor 5	Regular	449184.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1078	122	\N	\N	Assistant Professor 5	Regular	456384.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1079	122	\N	\N	Assistant Professor 5	Regular	520116.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1080	122	\N	\N	Assistant Professor 5	Regular	583848.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1081	122	\N	\N	Assistant Professor 5	Regular	590280.00	Eng'g.	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1082	122	\N	\N	Assistant Professor 5	Regular	669372.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1083	122	\N	\N	Assistant Professor 5	Regular	759060.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1084	122	\N	\N	Assistant Professor 5	Regular	769416.00	Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1085	122	\N	\N	ASSOCIATE PROFESSOR V	Regular	258732.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1086	122	\N	\N	ASSOCIATE PROFESSOR V	Regular	284604.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1087	123	\N	\N	A P  4	Regular	253908.00	EMG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1088	123	\N	\N	A P  4	Regular	288684.00	EMG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1089	123	\N	\N	A P  4	Regular	323472.00	EMG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1090	123	\N	\N	A P 4	Regular	328788.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1091	123	\N	\N	A P 4	Regular	363072.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1092	123	\N	\N	A P 4	Regular	397356.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1093	123	\N	\N	A P 4	Regular	429540.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1094	123	\N	\N	A P 4	Regular	459264.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1095	123	\N	\N	AP 4	Regular	401736.00	Eng'g.	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1096	123	\N	\N	AP 4	Regular	464628.00	Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1097	123	\N	\N	AP 4	Regular	496956.00	Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1098	123	\N	\N	Assistant Professor 4	Regular	230820.00	EMG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1099	123	\N	\N	Assistant Professor IV	Regular	204708.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1100	123	\N	\N	Assistant Professor IV	Regular	225180.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1101	124	\N	\N	AP  4	Regular	253908.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1102	124	\N	\N	AP  4	Regular	288684.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1103	124	\N	\N	Assistant Professor  4	Regular	230820.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1104	124	\N	\N	ASSISTANT PROFESSOR IV	Regular	204708.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1105	124	\N	\N	ASSISTANT PROFESSOR IV	Regular	225180.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1106	125	\N	\N	Assoc. Prof V	Regular	377604.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1107	125	\N	\N	Assoc. Prof V	Regular	442140.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1108	125	\N	\N	Assoc. Prof V	Regular	506676.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1109	125	\N	\N	Assoc. Prof V	Regular	571212.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1110	125	\N	\N	Assoc. Prof V	Regular	661212.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1111	125	\N	\N	Assoc. Prof V	Regular	748824.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1112	125	\N	\N	Assoc. Prof V	Regular	848040.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1113	125	\N	\N	Assoc. Prof V	Regular	976080.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1114	125	\N	\N	Assoc. Prof. V	Regular	583848.00	Eng'g.	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1115	125	\N	\N	Assoc. Prof. V	Regular	992016.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1116	125	\N	\N	Assoc. Prof. V	Regular	1011852.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1117	125	\N	\N	Assoc. Prof. V	Regular	1031700.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1118	125	\N	\N	ASSOCIATE PROFESSOR V	Regular	252420.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1119	125	\N	\N	ASSOCIATE PROFESSOR V	Regular	277668.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1120	125	\N	\N	ASSOCIATE PROFESSOR V	Regular	305436.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1121	125	\N	\N	ASSOCIATE PROFESSOR V	Regular	370332.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1122	126	\N	\N	INSTRUCTOR III	Regular	150552.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1123	127	\N	\N	ASSISTANT PROFESSOR IV	Regular	190092.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1124	127	\N	\N	ASSISTANT PROFESSOR IV	Regular	209100.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1125	127	\N	\N	ASSISTANT PROFESSOR IV	Regular	230016.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1126	127	\N	\N	ASSISTANT PROFESSOR IV	Regular	266568.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1127	127	\N	\N	ASSISTANT PROFESSOR IV	Regular	303108.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1128	127	\N	\N	ASSISTANT PROFESSOR IV	Regular	339660.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1129	127	\N	\N	Asst. Prof. IV	Regular	376212.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1130	127	\N	\N	Asst. Prof. IV	Regular	401424.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1131	127	\N	\N	Asst. Prof. IV	Regular	428316.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1132	127	\N	\N	Asst. Prof. IV	Regular	457020.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1133	127	\N	\N	Asst. Prof. IV	Regular	487644.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1134	127	\N	\N	Asst. Prof. IV	Regular	505908.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1135	127	\N	\N	Asst. Prof. IV	Regular	524172.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1136	128	\N	\N	AP 4	Regular	253908.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1137	128	\N	\N	AP 4	Regular	288684.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1138	128	\N	\N	AP 4	Regular	323472.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1139	128	\N	\N	AP 4	Regular	328788.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1140	128	\N	\N	AP 4	Regular	363072.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1141	128	\N	\N	AP 4	Regular	397356.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1142	128	\N	\N	AP 4	Regular	401736.00	Ang'g.	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1143	128	\N	\N	Assistant Professor 4	Regular	230820.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1144	128	\N	\N	ASSISTANT PROFESSOR IV	Regular	204708.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1145	128	\N	\N	ASSISTANT PROFESSOR IV	Regular	225180.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1146	128	\N	\N	Asst,.Prof. IV	Regular	401736.00	Eng'g.	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1147	128	\N	\N	Asst. Prof. 4	Regular	429540.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1148	128	\N	\N	Asst. Prof. 4	Regular	459264.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1149	128	\N	\N	Asst. Prof. 4	Regular	464628.00	Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1150	128	\N	\N	Asst. Prof. 4	Regular	496956.00	Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1151	128	\N	\N	Asst. Prof. 4	Regular	531528.00	Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1152	128	\N	\N	Asst. Prof. 4	Regular	549792.00	Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1153	128	\N	\N	Asst. Prof. IV	Contractual	549792.00	Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1154	128	\N	\N	Asst. Prof. IV	Regular	401736.00	Eng'g.	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1155	129	\N	\N	INSTRUCTOR I	Regular	142044.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1156	129	\N	\N	INSTRUCTOR I	Regular	156252.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1157	130	\N	\N	ASSOCIATE PROFESSOR V	Contractual	0.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1158	131	\N	\N	Assistant Professor 5	Regular	270876.00	Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1159	131	\N	\N	Assistant Professor 5	Regular	277668.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1160	131	\N	\N	Assistant Professor 5	Regular	305436.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1161	131	\N	\N	Assistant Professor 5	Regular	370332.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1162	131	\N	\N	Assistant Professor 5	Regular	435216.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1163	131	\N	\N	Assistant Professor 5	Regular	442140.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1164	131	\N	\N	Assistant Professor 5	Regular	506676.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1165	131	\N	\N	Assistant Professor 5	Regular	571212.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1166	131	\N	\N	Assistant Professor 5	Regular	577500.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1167	131	\N	\N	Assistant Professor 5	Regular	653160.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1168	131	\N	\N	Assistant Professor 5	Regular	748824.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1169	131	\N	\N	Assistant Professor 5	Regular	848040.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1170	131	\N	\N	Assistant Professor 5	Regular	960408.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1171	131	\N	\N	Assistant Professor 5	Regular	979620.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1172	131	\N	\N	Assistant Professor 5	Regular	998820.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1173	131	\N	\N	ASSOCIATE PROFESSOR V	Regular	240240.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1174	131	\N	\N	ASSOCIATE PROFESSOR V	Regular	264264.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1175	132	\N	\N	ASSOCIATE PROFESSOR I	Regular	201504.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1176	132	\N	\N	Prof II	Regular	259860.00	Coll of Eng'g	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1177	132	\N	\N	Prof II	Regular	285852.00	Coll of Eng'g	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1178	132	\N	\N	Prof II	Regular	314436.00	Coll of Eng'g	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1179	132	\N	\N	Prof II	Regular	397020.00	Coll of Eng'g	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1180	132	\N	\N	Prof II	Regular	487080.00	Coll of Eng'g	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1181	132	\N	\N	Prof II	Regular	569472.00	Coll of Eng'g	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1182	132	\N	\N	Prof II	Regular	651852.00	Coll of Eng'g	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1183	132	\N	\N	Prof. II	Regular	659016.00	Eng'g.	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1184	133	\N	\N	Asso Prof 1	Regular	201504.00	Eng'g	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1185	133	\N	\N	Asso Prof 1	Regular	221652.00	Eng'g	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1186	133	\N	\N	Asso Prof 1	Regular	243816.00	Eng'g	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1187	133	\N	\N	Asso Prof 1	Regular	284436.00	Eng'g	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1188	133	\N	\N	Asso Prof 1	Regular	325056.00	Eng'g	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1189	133	\N	\N	Asso Prof 1	Regular	365688.00	Eng'g	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1190	133	\N	\N	Assoc  Prof. I	Regular	570264.00	Eng'g	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1191	133	\N	\N	Assoc  Prof. I	Regular	588528.00	Eng'g	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1192	133	\N	\N	Assoc  Prof. I	Regular	606792.00	Eng'g	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1193	133	\N	\N	Assoc. Prof. 1	Regular	370560.00	Eng'g	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1194	133	\N	\N	Assoc. Prof. 1	Regular	410772.00	Eng'g	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1195	133	\N	\N	Assoc. Prof. 1	Regular	447744.00	Eng'g	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1196	133	\N	\N	Assoc. Prof. 1	Regular	482724.00	Eng'g	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1197	133	\N	\N	Assoc. Prof. 1	Regular	520452.00	Eng'g	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1198	133	\N	\N	Assoc. Prof. 1	Regular	561108.00	Eng'g	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1199	133	\N	\N	Assoc. Prof. I	Regular	415296.00	Eng'g.	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1200	133	\N	\N	INSTRUCTOR III	Regular	150552.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1201	134	\N	\N	Assistant Professor 5	Regular	291696.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1202	134	\N	\N	Assistant Professor 5	Regular	320868.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1203	134	\N	\N	Assistant Professor 5	Regular	385032.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1204	134	\N	\N	Assistant Professor 5	Regular	449184.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1205	134	\N	\N	Assistant Professor 5	Regular	456384.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1206	134	\N	\N	Assistant Professor 5	Regular	520116.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1207	134	\N	\N	Assistant Professor 5	Regular	583848.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1208	134	\N	\N	Assistant Professor 5	Regular	590280.00	Eng'g.	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1209	134	\N	\N	ASSOCIATE PROFESSOR V	Regular	258732.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1210	134	\N	\N	ASSOCIATE PROFESSOR V	Regular	284604.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1211	135	\N	\N	Assistant Professor 5	Regular	571212.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1212	135	\N	\N	Assoc.Prof. V	Regular	645204.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1213	135	\N	\N	Assoc.Prof. V	Regular	748824.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1214	135	\N	\N	Assoc.Prof. V	Regular	848040.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1215	135	\N	\N	Assoc.Prof. V	Regular	960408.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1216	135	\N	\N	Assoc.Prof. V	Regular	979620.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1217	135	\N	\N	ASSOCIATE PROFESSOR V	Regular	252420.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1218	135	\N	\N	ASSOCIATE PROFESSOR V	Regular	277668.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1219	135	\N	\N	ASSOCIATE PROFESSOR V	Regular	305436.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1220	135	\N	\N	ASSOCIATE PROFESSOR V	Regular	370332.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1221	135	\N	\N	ASSOCIATE PROFESSOR V	Regular	435216.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1222	135	\N	\N	ASSOCIATE PROFESSOR V	Regular	500112.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1223	135	\N	\N	ASSOCIATE PROFESSOR V	Regular	564996.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1224	135	\N	\N	Instructor III	Contractual	979620.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1225	136	\N	\N	AP  4	Regular	253908.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1226	136	\N	\N	Assistant Professor  4	Regular	230820.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1227	136	\N	\N	ASSISTANT PROFESSOR IV	Regular	204708.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1228	136	\N	\N	ASSISTANT PROFESSOR IV	Regular	225180.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1229	137	\N	\N	ASSOCIATE PROFESSOR II	Contractual	60984.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1230	138	\N	\N	INSTRUCTOR III	Regular	178968.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1231	138	\N	\N	INSTRUCTOR III	Regular	196860.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1232	138	\N	\N	INSTRUCTOR III	Regular	216552.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1233	138	\N	\N	INSTRUCTOR III	Regular	236532.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1234	138	\N	\N	INSTRUCTOR III	Regular	256512.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1235	138	\N	\N	INSTRUCTOR III	Regular	276492.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1236	138	\N	\N	INSTRUCTOR III	Regular	296472.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1237	138	\N	\N	INSTRUCTOR III	Regular	311868.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1238	138	\N	\N	INSTRUCTOR III	Regular	328068.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1239	138	\N	\N	INSTRUCTOR III	Regular	345108.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1240	138	\N	\N	INSTRUCTOR III	Regular	363036.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1241	138	\N	\N	INSTRUCTOR III	Regular	381300.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1242	138	\N	\N	INSTRUCTOR III	Regular	399564.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1243	139	\N	\N	Assistant Professor 5	Regular	291696.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1244	139	\N	\N	Assistant Professor 5	Regular	320868.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1245	139	\N	\N	Assistant Professor 5	Regular	385032.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1246	139	\N	\N	Assistant Professor 5	Regular	449184.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1247	139	\N	\N	Assistant Professor 5	Regular	456384.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1248	139	\N	\N	Assistant Professor 5	Regular	520116.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1249	139	\N	\N	Assistant Professor 5	Regular	583848.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1250	139	\N	\N	Assistant Professor 5	Regular	590280.00	Eng'g.	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1251	139	\N	\N	Assistant Professor 5	Regular	669372.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1252	139	\N	\N	Assistant Professor 5	Regular	759060.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1253	139	\N	\N	Assistant Professor 5	Regular	769416.00	Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1254	139	\N	\N	Assistant Professor 5	Regular	873660.00	Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1255	139	\N	\N	Assistant Professor 5	Regular	992016.00	Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1256	139	\N	\N	Assistant Professor 5	Regular	1011852.00	Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1257	139	\N	\N	Assistant Professor 5	Regular	1031700.00	Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1258	139	\N	\N	ASSOCIATE PROFESSOR V	Regular	258732.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1259	139	\N	\N	ASSOCIATE PROFESSOR V	Regular	284604.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1260	140	\N	\N	AP 4	Regular	253908.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1261	140	\N	\N	AP 4	Regular	288684.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1262	140	\N	\N	AP 4	Regular	323472.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1263	140	\N	\N	AP 4	Regular	328788.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1264	140	\N	\N	AP 4	Regular	363072.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1265	140	\N	\N	AP 4	Regular	397356.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1266	140	\N	\N	AP 4	Regular	401736.00	Eng'g.	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1267	140	\N	\N	AP 4	Regular	429540.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1268	140	\N	\N	AP 4	Regular	459264.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1269	140	\N	\N	AP 4	Regular	464628.00	Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1270	140	\N	\N	AP 4	Regular	496956.00	Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1271	140	\N	\N	AP 4	Regular	531528.00	Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1272	140	\N	\N	AP 4	Regular	549792.00	Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1273	140	\N	\N	AP 4	Regular	568056.00	Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1274	140	\N	\N	Assistant Professor 4	Regular	230820.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1275	140	\N	\N	ASSISTANT PROFESSOR IV	Regular	204708.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1276	140	\N	\N	ASSISTANT PROFESSOR IV	Regular	225180.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1277	141	\N	\N	INSTRUCTOR III	Regular	150552.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1278	141	\N	\N	Prof III	Regular	270252.00	Coll of Eng'g	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1279	141	\N	\N	Prof III	Regular	297276.00	Coll of Eng'g	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1280	141	\N	\N	Prof III	Regular	327000.00	Coll of Eng'g	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1281	141	\N	\N	Prof III	Regular	419340.00	Coll of Eng'g	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1282	141	\N	\N	Prof III	Regular	427380.00	Coll of Eng'g	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1283	141	\N	\N	Prof III	Regular	519588.00	Coll of Eng'g	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1284	141	\N	\N	Prof III	Regular	611784.00	Coll of Eng'g	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1285	141	\N	\N	Prof III	Regular	703992.00	Coll of Eng'g	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1286	141	\N	\N	Prof III	Regular	832428.00	Coll of Eng'g	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1287	141	\N	\N	Prof. III	Regular	711744.00	Eng'g	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1288	141	\N	\N	Prof. VI	Regular	1058568.00	Coll.Engr.	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1289	141	\N	\N	Prof. VI	Regular	1277448.00	Coll.Engr.	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1290	141	\N	\N	Prof. VI	Regular	1541604.00	Coll.Engr.	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1291	141	\N	\N	Prof. VI	Regular	1860360.00	Coll.Engr.	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1292	141	\N	\N	Prof. VI	Regular	1890732.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1293	141	\N	\N	Prof. VI	Regular	1928544.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1294	141	\N	\N	Prof. VI	Regular	1966356.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1295	142	\N	\N	Asst Prof IV	Regular	190092.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1296	142	\N	\N	Asst Prof. IV	Regular	194844.00	Coll of Eng'g	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1297	142	\N	\N	Asst Prof. IV	Regular	214332.00	Coll of Eng'g	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1298	142	\N	\N	Asst Prof. IV	Regular	235764.00	Coll of Eng'g	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1299	142	\N	\N	Asst Prof. IV	Regular	271908.00	Coll of Eng'g	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1300	142	\N	\N	Asst Prof. IV	Regular	308052.00	Coll of Eng'g	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1301	142	\N	\N	Asst. Prof IV	Regular	313092.00	Coll of Eng'g	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1302	142	\N	\N	Asst. Prof IV	Regular	348816.00	Coll of Eng'g	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1303	142	\N	\N	Asst. Prof IV	Regular	384528.00	Coll of Eng'g	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1304	143	\N	\N	Assoc Prof. II	Regular	213588.00	Coll of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1305	143	\N	\N	Assoc Prof. II	Regular	234948.00	Coll of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1306	143	\N	\N	Assoc Prof. II	Regular	258444.00	Coll of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1307	143	\N	\N	Assoc Prof. II	Regular	303540.00	Coll of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1308	143	\N	\N	Assoc Prof. II	Regular	348624.00	Coll of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1309	143	\N	\N	Assoc Prof. II	Regular	393720.00	Coll of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1310	143	\N	\N	Assoc. Prof. II	Regular	448512.00	Eng'g.	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1311	143	\N	\N	Assoc. Prof. II	Regular	590208.00	Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1312	143	\N	\N	Assoc. Prof. II	Regular	644400.00	Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1313	143	\N	\N	Assoc. Prof. II	Regular	662976.00	Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1314	143	\N	\N	Assoc. Prof. II	Regular	673500.00	Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1315	143	\N	\N	Assoc. Prof. II	Regular	692076.00	Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1316	143	\N	\N	Assoc. Prof.II	Regular	398952.00	Coll of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1317	143	\N	\N	Assoc. Prof.II	Regular	443640.00	Coll of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1318	143	\N	\N	Assoc. Prof.II	Regular	489060.00	Coll of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1319	143	\N	\N	Assoc. Prof.II	Regular	533280.00	Coll of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1320	143	\N	\N	Assoc. Prof.II	Regular	581484.00	Coll of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1321	143	\N	\N	INSTRUCTOR III	Regular	150552.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1322	144	\N	\N	PROFESSOR VI	Regular	303996.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1323	145	\N	\N	ASSISTANT PROFESSOR IV	Regular	204708.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1324	145	\N	\N	ASSISTANT PROFESSOR IV	Regular	225180.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1325	145	\N	\N	ASSISTANT PROFESSOR IV	Regular	247704.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1326	145	\N	\N	ASSISTANT PROFESSOR IV	Regular	282972.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1327	145	\N	\N	ASSISTANT PROFESSOR IV	Regular	318228.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1328	145	\N	\N	ASSISTANT PROFESSOR IV	Regular	353496.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1329	145	\N	\N	ASSISTANT PROFESSOR IV	Regular	388764.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1330	145	\N	\N	ASSISTANT PROFESSOR IV	Regular	429540.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1331	145	\N	\N	Assoc. Prof. V	Regular	621912.00	Coll.Engr.	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1332	145	\N	\N	Assoc. Prof. V	Regular	699720.00	Coll.Engr.	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1333	145	\N	\N	Assoc. Prof. V	Regular	787248.00	Coll.Engr.	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1334	145	\N	\N	Assoc. Prof. V	Regular	885732.00	Coll.Engr.	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1335	145	\N	\N	Assoc. Prof. V	Regular	918756.00	Coll.Engr.	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1336	145	\N	\N	Assoc. Prof. V	Regular	937332.00	Coll.Engr.	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1337	145	\N	\N	Asst. Pro IV	Regular	401736.00	Eng'g.	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1338	146	\N	\N	PROFESSOR VI	Regular	327372.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1339	147	\N	\N	INSTRUCTOR III	Regular	150552.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1340	148	\N	\N	Assistant Professor 5	Regular	291696.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1341	148	\N	\N	Assistant Professor 5	Regular	320868.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1342	148	\N	\N	Assistant Professor 5	Regular	385032.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1343	148	\N	\N	Assistant Professor 5	Regular	449184.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1344	148	\N	\N	Assistant Professor 5	Regular	456384.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1345	148	\N	\N	Assistant Professor 5	Regular	520116.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1346	148	\N	\N	Assistant Professor 5	Regular	583848.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1347	148	\N	\N	Assistant Professor 5	Regular	590280.00	Eng'g.	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1348	148	\N	\N	ASSOCIATE PROFESSOR V	Regular	258732.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1349	148	\N	\N	ASSOCIATE PROFESSOR V	Regular	284604.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1350	149	\N	\N	AP 4	Regular	253908.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1351	149	\N	\N	AP 4	Regular	288684.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1352	149	\N	\N	AP 4	Regular	323472.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1353	149	\N	\N	AP 4	Regular	328788.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1354	149	\N	\N	AP 4	Regular	363072.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1355	149	\N	\N	AP 4	Regular	397356.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1356	149	\N	\N	AP 4	Regular	401736.00	Eng'g.	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1357	149	\N	\N	AP 4	Regular	429540.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1358	149	\N	\N	AP 4	Regular	459264.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1359	149	\N	\N	AP 4	Regular	464628.00	Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1360	149	\N	\N	AP 4	Regular	496956.00	Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1361	149	\N	\N	AP 4	Regular	531528.00	Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1362	149	\N	\N	AP 4	Regular	549792.00	Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1363	149	\N	\N	AP 4	Regular	568056.00	Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1364	149	\N	\N	Assistant Professor 4	Regular	230820.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1365	149	\N	\N	Assistant Professor IV	Regular	204708.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1366	149	\N	\N	Assistant Professor IV	Regular	225180.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1367	150	\N	\N	AP 4	Regular	253908.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1368	150	\N	\N	AP 4	Regular	288684.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1369	150	\N	\N	AP 4	Regular	323472.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1370	150	\N	\N	AP 4	Regular	328788.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1371	150	\N	\N	AP 4	Regular	363072.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1372	150	\N	\N	AP 4	Regular	397356.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1373	150	\N	\N	AP 4	Regular	401736.00	Eng'g.	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1374	150	\N	\N	AP 4	Regular	429540.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1375	150	\N	\N	AP 4	Regular	459264.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1376	150	\N	\N	Assistant Professor 4	Regular	230820.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1377	150	\N	\N	ASSISTANT PROFESSOR IV	Regular	204708.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1378	150	\N	\N	ASSISTANT PROFESSOR IV	Regular	225180.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1379	151	\N	\N	AP  4`	Regular	253908.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1380	151	\N	\N	AP  4`	Regular	288684.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1381	151	\N	\N	AP  4`	Regular	323472.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1382	151	\N	\N	AP 4	Regular	328788.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1383	151	\N	\N	AP 4	Regular	363072.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1384	151	\N	\N	AP 4	Regular	397356.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1385	151	\N	\N	AP 4	Regular	401736.00	Eng'g.	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1386	151	\N	\N	AP 4	Regular	429540.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1387	151	\N	\N	AP 4	Regular	459264.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1388	151	\N	\N	AP 4	Regular	464628.00	Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1389	151	\N	\N	AP 4	Regular	496956.00	Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1390	151	\N	\N	AP 4	Regular	531528.00	Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1391	151	\N	\N	Assistant Professor  4`	Regular	230820.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1392	151	\N	\N	ASSISTANT PROFESSOR IV	Regular	204708.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1393	151	\N	\N	ASSISTANT PROFESSOR IV	Regular	225180.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1394	152	\N	\N	AP IV	Regular	241656.00	Coll of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1395	152	\N	\N	AP IV	Regular	277380.00	Coll of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1396	152	\N	\N	INST III	Regular	190092.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1397	152	\N	\N	INST III	Regular	209100.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1398	152	\N	\N	INST III	Regular	230016.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1399	153	\N	\N	Asst Prof. IV	Regular	505908.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1400	153	\N	\N	Asst Prof. IV	Regular	524172.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1401	153	\N	\N	INSTRUCTOR II	Regular	149232.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1402	153	\N	\N	INSTRUCTOR II	Regular	164160.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1403	153	\N	\N	INSTRUCTOR II	Regular	180576.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1404	153	\N	\N	INSTRUCTOR II	Regular	201036.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1405	153	\N	\N	INSTRUCTOR II	Regular	221484.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1406	153	\N	\N	INSTRUCTOR II	Regular	241944.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1407	153	\N	\N	INSTRUCTOR II	Regular	262404.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1408	153	\N	\N	INSTRUCTOR II	Regular	273648.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1409	153	\N	\N	INSTRUCTOR II	Regular	285360.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1410	153	\N	\N	INSTRUCTOR II	Regular	297588.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1411	153	\N	\N	INSTRUCTOR II	Regular	310332.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1412	153	\N	\N	INSTRUCTOR II	Regular	328596.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1413	154	\N	\N	INSTRUCTOR I	Regular	134004.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1414	154	\N	\N	INSTRUCTOR I	Regular	147408.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1415	154	\N	\N	INSTRUCTOR I	Regular	162144.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1416	154	\N	\N	INSTRUCTOR I	Regular	181428.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1417	154	\N	\N	INSTRUCTOR I	Regular	200712.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1418	154	\N	\N	INSTRUCTOR I	Regular	219996.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1419	154	\N	\N	INSTRUCTOR I	Regular	239280.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1420	154	\N	\N	INSTRUCTOR I	Regular	247812.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1421	154	\N	\N	INSTRUCTOR I	Regular	256644.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1422	154	\N	\N	INSTRUCTOR I	Regular	265788.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1423	154	\N	\N	INSTRUCTOR I	Regular	275256.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1424	154	\N	\N	INSTRUCTOR I	Regular	293940.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1425	154	\N	\N	Instructor III	Regular	351324.00	MSU-College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1426	154	\N	\N	Instructor III	Regular	369588.00	MSU-College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1427	155	\N	\N	Assistant Professor 5	Regular	291696.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1428	155	\N	\N	Assistant Professor 5	Regular	320868.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1429	155	\N	\N	Assistant Professor 5	Regular	385032.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1430	155	\N	\N	Assistant Professor 5	Regular	449184.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1431	155	\N	\N	Assistant Professor 5	Regular	456384.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1432	155	\N	\N	Assistant Professor 5	Regular	520116.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1433	155	\N	\N	Assistant Professor 5	Regular	583848.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1434	155	\N	\N	Assistant Professor 5	Regular	590280.00	Eng'g.	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1435	155	\N	\N	Assistant Professor 5	Regular	669372.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1436	155	\N	\N	Assistant Professor 5	Regular	759060.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1437	155	\N	\N	Assistant Professor 5	Regular	769416.00	Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1438	155	\N	\N	Assistant Professor 5	Regular	873660.00	Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1439	155	\N	\N	Assistant Professor 5	Regular	992016.00	Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1440	155	\N	\N	ASSOCIATE PROFESSOR V	Regular	258732.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1441	155	\N	\N	ASSOCIATE PROFESSOR V	Regular	284604.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1442	156	\N	\N	AP IV	Regular	376212.00	Coll. of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1443	156	\N	\N	AP IV	Regular	405972.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1444	156	\N	\N	AP IV	Regular	433332.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1445	156	\N	\N	AP IV	Regular	462516.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1446	156	\N	\N	AP IV	Regular	468084.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1447	156	\N	\N	AP IV	Regular	499800.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1448	156	\N	\N	AP IV	Regular	518064.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1449	156	\N	\N	AP IV	Regular	536328.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1450	156	\N	\N	AP IV	Regular	542508.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1451	156	\N	\N	Inst III	Regular	248388.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1452	156	\N	\N	Inst III	Regular	269508.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1453	156	\N	\N	Inst III	Regular	290628.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1454	156	\N	\N	INSTRUCTOR III	Regular	170352.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1455	156	\N	\N	INSTRUCTOR III	Regular	187392.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1456	156	\N	\N	INSTRUCTOR III	Regular	206136.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1457	156	\N	\N	INSTRUCTOR III	Regular	227256.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1458	157	\N	\N	Asst. Prof.IV	Regular	266568.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1459	157	\N	\N	Asst. Prof.IV	Regular	303108.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1460	157	\N	\N	Asst. Prof.IV	Regular	339660.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1461	157	\N	\N	Asst. Prof.IV	Regular	376212.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1462	157	\N	\N	Inst. II	Regular	152964.00	Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1463	157	\N	\N	Inst. II	Regular	168264.00	Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1464	157	\N	\N	Inst. II	Regular	185088.00	Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1465	157	\N	\N	Inst. II	Regular	205068.00	Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1466	157	\N	\N	INSTRUCTOR II	Regular	149232.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1467	158	\N	\N	INSTRUCTOR III	Contractual	0.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1468	159	\N	\N	Assistant Professor V	Regular	305436.00	Coll of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1469	159	\N	\N	Assistant Professor V	Regular	370332.00	Coll of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1470	159	\N	\N	Assistant Professor V	Regular	435216.00	Coll of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1471	159	\N	\N	Assistant Professor V	Regular	500112.00	Coll of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1472	159	\N	\N	Assistant Professor V	Regular	506676.00	Coll. of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1473	159	\N	\N	ASSOCIATE PROFESSOR V	Regular	246252.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1474	159	\N	\N	ASSOCIATE PROFESSOR V	Regular	270876.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1475	159	\N	\N	ASSOCIATE PROFESSOR V	Regular	297960.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1476	160	\N	\N	ASSISTANT PROFESSOR I	Regular	180576.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1477	160	\N	\N	ASSISTANT PROFESSOR I	Regular	198636.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1478	160	\N	\N	ASSISTANT PROFESSOR I	Regular	218496.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1479	160	\N	\N	ASSISTANT PROFESSOR I	Regular	242736.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1480	160	\N	\N	ASSISTANT PROFESSOR I	Regular	266964.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1481	160	\N	\N	Asst. Prof 1	Regular	295176.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1482	160	\N	\N	Asst. Prof 1	Regular	318912.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1483	160	\N	\N	Asst. Prof 1	Regular	340128.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1484	160	\N	\N	Asst. Prof 1	Regular	358824.00	College of Educatio	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1485	160	\N	\N	Asst. Prof. I	Regular	322416.00	Eng'g.	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1486	161	\N	\N	ASSISTANT PROFESSOR IV	Regular	190092.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1487	161	\N	\N	ASSISTANT PROFESSOR IV	Regular	209100.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1488	161	\N	\N	ASSISTANT PROFESSOR IV	Regular	230016.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1489	161	\N	\N	ASSISTANT PROFESSOR IV	Regular	266568.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1490	161	\N	\N	ASSISTANT PROFESSOR IV	Regular	303108.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1491	161	\N	\N	ASSISTANT PROFESSOR IV	Regular	339660.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1492	161	\N	\N	Asst. Prof. IV	Regular	376212.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1493	161	\N	\N	Asst. Prof. IV	Regular	401424.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1494	162	\N	\N	Assistant Professor 5	Regular	291696.00	FORESTRY	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1495	162	\N	\N	Assistant Professor 5	Regular	320868.00	FORESTRY	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1496	162	\N	\N	Assistant Professor 5	Regular	385032.00	FORESTRY	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1497	162	\N	\N	Assistant Professor 5	Regular	449184.00	FORESTRY	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1498	162	\N	\N	Assistant Professor 5	Regular	456384.00	MSU ILS	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1499	162	\N	\N	Assistant Professor 5	Regular	520116.00	MSU ILS	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1500	162	\N	\N	Assistant Professor 5	Regular	583848.00	MSU ILS	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1501	162	\N	\N	Assistant Professor 5	Regular	590280.00	Eng'g.	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1502	162	\N	\N	ASSOCIATE PROFESSOR V	Regular	258732.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1503	162	\N	\N	ASSOCIATE PROFESSOR V	Regular	284604.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1504	162	\N	\N	Lecturer	Regular	0.00	Eng'g	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1505	163	\N	\N	Assistant Professor 5	Regular	291696.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1506	163	\N	\N	Assistant Professor 5	Regular	320868.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1507	163	\N	\N	Assistant Professor 5	Regular	385032.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1508	163	\N	\N	Assistant Professor 5	Regular	449184.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1509	163	\N	\N	Assistant Professor 5	Regular	456384.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1510	163	\N	\N	ASSOCIATE PROFESSOR V	Regular	258732.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1511	163	\N	\N	ASSOCIATE PROFESSOR V	Regular	284604.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1512	164	\N	\N	ASSOCIATE PROFESSOR III	Regular	245184.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1513	164	\N	\N	ASSOCIATE PROFESSOR III	Regular	269700.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1514	164	\N	\N	ASSOCIATE PROFESSOR III	Regular	296676.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1515	165	\N	\N	Assistant Professor II	Regular	272460.00	Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1516	165	\N	\N	Assistant Professor II	Regular	279288.00	ENGINEERING	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1517	165	\N	\N	Assistant Professor II	Regular	307212.00	ENGINEERING	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1518	165	\N	\N	Assistant Professor II	Regular	348840.00	ENGINEERING	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1519	165	\N	\N	Assistant Professor II	Regular	390468.00	ENGINEERING	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1520	165	\N	\N	Assistant Professor II	Regular	432108.00	ENGINEERING	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1521	165	\N	\N	Assistant Professor II	Regular	473736.00	ENGINEERING	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1522	165	\N	\N	Assistant Professor II	Regular	519960.00	ENGINEERING	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1523	165	\N	\N	Assistant Professor II	Regular	570708.00	ENGINEERING	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1524	165	\N	\N	Assistant Professor II	Regular	626388.00	ENGINEERING	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1525	165	\N	\N	Assistant Professor II	Regular	687516.00	ENGINEERING	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1526	165	\N	\N	Assistant Professor II	Regular	706092.00	ENGINEERING	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1527	165	\N	\N	Assistant Professor II	Regular	724668.00	Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1528	165	\N	\N	ASSOCIATE PROFESSOR II	Regular	241656.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1529	165	\N	\N	ASSOCIATE PROFESSOR II	Regular	265824.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1530	166	\N	\N	INSTRUCTOR III	Regular	170352.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1531	166	\N	\N	INSTRUCTOR III	Regular	187392.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1532	166	\N	\N	INSTRUCTOR III	Regular	206136.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1533	166	\N	\N	INSTRUCTOR III	Regular	227256.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1534	166	\N	\N	INSTRUCTOR III	Regular	248388.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1535	166	\N	\N	INSTRUCTOR III	Regular	269508.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1536	166	\N	\N	INSTRUCTOR III	Regular	290628.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1537	166	\N	\N	INSTRUCTOR III	Regular	305364.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1538	166	\N	\N	INSTRUCTOR III	Regular	320844.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1539	166	\N	\N	INSTRUCTOR III	Regular	337116.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1540	166	\N	\N	INSTRUCTOR III	Regular	354204.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1541	166	\N	\N	INSTRUCTOR III	Regular	372468.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1542	167	\N	\N	INSTRUCTOR III	Regular	170352.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1543	167	\N	\N	INSTRUCTOR III	Regular	187392.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1544	168	\N	\N	Assistant Professor 2	Regular	259344.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1545	168	\N	\N	Assistant Professor 2	Regular	285276.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1546	168	\N	\N	Assistant Professor 2	Regular	328572.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1547	168	\N	\N	Assistant Professor 2	Regular	371856.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1548	168	\N	\N	Assistant Professor 2	Regular	377940.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1549	168	\N	\N	Assistant Professor 2	Regular	420708.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1550	168	\N	\N	Assistant Professor 2	Regular	463476.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1551	168	\N	\N	Assistant Professor II	Regular	468576.00	Eng'g.	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1552	168	\N	\N	ASSOCIATE PROFESSOR II	Regular	230016.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1553	168	\N	\N	ASSOCIATE PROFESSOR II	Regular	253020.00	College of Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1554	168	\N	\N	Prof. VI	Regular	877188.00	Eng'g	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1555	168	\N	\N	Prof. VI	Regular	1058568.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1556	168	\N	\N	Prof. VI	Regular	1277448.00	ENG'G	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1557	168	\N	\N	Prof. VI	Regular	1294896.00	Engr.	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1558	168	\N	\N	Prof. VI	Regular	1564704.00	Engr.	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1559	168	\N	\N	Prof. VI	Regular	1890732.00	Engr.	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1560	168	\N	\N	Prof. VI	Regular	1928544.00	Engr.	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1561	168	\N	\N	Prof. VI	Regular	1960020.00	Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1562	168	\N	\N	Prof. VI	Regular	1998444.00	Engineering	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1563	169	\N	\N	AP 4	Regular	253908.00	FISH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1564	169	\N	\N	AP 4	Regular	288684.00	FISH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1565	169	\N	\N	AP 4	Regular	323472.00	FISH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1566	169	\N	\N	AP 4	Regular	328788.00	FISH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1567	169	\N	\N	AP 4	Regular	363072.00	FISH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1568	169	\N	\N	AP 4	Regular	397356.00	FISH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1569	169	\N	\N	AP 4	Regular	401736.00	Fish.	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1570	169	\N	\N	AP 4	Regular	429540.00	FISH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1571	169	\N	\N	AP 4	Regular	434412.00	Fish	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1572	169	\N	\N	AP 4	Regular	464628.00	Fish	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1573	169	\N	\N	AP 4	Regular	496956.00	Fish	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1574	169	\N	\N	AP 4	Regular	531528.00	Fish	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1575	169	\N	\N	Assistant Professor 4	Regular	230820.00	FISH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1576	169	\N	\N	ASSISTANT PROFESSOR IV	Regular	204708.00	College of Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1577	169	\N	\N	ASSISTANT PROFESSOR IV	Regular	225180.00	College of Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1578	170	\N	\N	A P  4	Regular	253908.00	FISH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1579	170	\N	\N	A P  4	Regular	288684.00	FISH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1580	170	\N	\N	A P  4	Regular	323472.00	FISH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1581	170	\N	\N	AP 4	Regular	328788.00	FISH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1582	170	\N	\N	AP 4	Regular	363072.00	FISH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1583	170	\N	\N	AP 4	Regular	397356.00	FISH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1584	170	\N	\N	AP 4	Regular	401736.00	Fish.	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1585	170	\N	\N	AP 4	Regular	429540.00	FISH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1586	170	\N	\N	AP 4	Regular	434412.00	Fish	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1587	170	\N	\N	AP 4	Regular	464628.00	Fish	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1588	170	\N	\N	AP 4	Regular	496956.00	Fish	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1589	170	\N	\N	Assistant Professor 4	Regular	230820.00	FISH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1590	170	\N	\N	ASSISTANT PROFESSOR IV	Regular	204708.00	College of Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1591	170	\N	\N	ASSISTANT PROFESSOR IV	Regular	225180.00	College of Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1592	171	\N	\N	Inst I	Regular	266784.00	Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1593	171	\N	\N	Inst I	Regular	277440.00	Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1594	171	\N	\N	Inst III	Regular	355452.00	Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1595	171	\N	\N	Inst III	Regular	373716.00	Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1596	171	\N	\N	Inst. 1	Regular	251496.00	Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1597	171	\N	\N	Inst. 1	Regular	261228.00	Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1598	171	\N	\N	INSTRUCTOR I	Regular	144312.00	College of Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1599	171	\N	\N	Instructor I	Regular	147912.00	Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1600	171	\N	\N	Instructor I	Regular	162708.00	Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1601	171	\N	\N	Instructor I	Regular	178980.00	Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1602	171	\N	\N	Instructor I	Regular	183468.00	Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1603	171	\N	\N	Instructor I	Regular	200472.00	Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1604	171	\N	\N	Instructor I	Regular	217476.00	Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1605	171	\N	\N	Instructor I	Regular	234492.00	Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1606	171	\N	\N	Instructor III	Regular	303480.00	Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1607	171	\N	\N	Instructor III	Regular	317928.00	Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1608	171	\N	\N	Instructor III	Regular	333060.00	Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1609	171	\N	\N	Instructor III	Regular	351324.00	Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1610	172	\N	\N	Assistant Professor 5	Regular	291696.00	FISH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1611	172	\N	\N	Assistant Professor 5	Regular	320868.00	FISH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1612	172	\N	\N	Assistant Professor 5	Regular	385032.00	FISH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1613	172	\N	\N	Assistant Professor 5	Regular	449184.00	FISH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1614	172	\N	\N	Assistant Professor 5	Regular	456384.00	FISH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1615	172	\N	\N	Assistant Professor 5	Regular	520116.00	FISH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1616	172	\N	\N	Assistant Professor 5	Regular	583848.00	FISH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1617	172	\N	\N	Assistant Professor 5	Regular	590280.00	fISH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1618	172	\N	\N	Assistant Professor 5	Regular	669372.00	FISH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1619	172	\N	\N	Assistant Professor 5	Regular	677616.00	Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1620	172	\N	\N	Assistant Professor 5	Regular	769416.00	Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1621	172	\N	\N	ASSOCIATE PROFESSOR V	Regular	258732.00	College of Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1622	172	\N	\N	ASSOCIATE PROFESSOR V	Regular	284604.00	College of Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1623	173	\N	\N	Instr. III	Regular	196860.00	FISHERIES	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1624	173	\N	\N	Instr. III	Regular	216552.00	FISHERIES	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1625	173	\N	\N	Instr. III	Regular	236532.00	FISHERIES	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1626	173	\N	\N	Instr. III	Regular	256512.00	FISHERIES	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1627	173	\N	\N	Instr. III	Regular	276492.00	FISHERIES	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1628	173	\N	\N	Instr. III	Regular	296472.00	FISHERIES	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1629	173	\N	\N	Instr. III	Regular	311868.00	FISHERIES	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1630	173	\N	\N	Instr. III	Regular	328068.00	FISHERIES	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1631	173	\N	\N	Instr. III	Regular	345108.00	FISHERIES	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1632	173	\N	\N	Instr. III	Regular	363036.00	FISHERIES	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1633	173	\N	\N	Instr. III	Regular	381300.00	FISHERIES	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1634	173	\N	\N	INSTRUCTOR III	Regular	170352.00	College of Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1635	173	\N	\N	INSTRUCTOR III	Regular	187392.00	College of Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1636	173	\N	\N	Instructor III	Regular	192072.00	Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1637	174	\N	\N	A P  4	Regular	253908.00	FISH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1638	174	\N	\N	A P  4	Regular	288684.00	FISH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1639	174	\N	\N	A P  4	Regular	323472.00	FISH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1640	174	\N	\N	AP 4	Regular	328788.00	FISH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1641	174	\N	\N	AP 4	Regular	363072.00	FISH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1642	174	\N	\N	AP 4	Regular	397356.00	FISH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1643	174	\N	\N	Assistant Professor 4	Regular	230820.00	FISH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1644	174	\N	\N	ASSISTANT PROFESSOR IV	Regular	204708.00	College of Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1645	174	\N	\N	ASSISTANT PROFESSOR IV	Regular	225180.00	College of Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1646	175	\N	\N	AP 4	Regular	253908.00	FISH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1647	175	\N	\N	AP 4	Regular	288684.00	FISH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1648	175	\N	\N	AP 4	Regular	323472.00	FISH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1649	175	\N	\N	AP 4	Regular	328788.00	FISH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1650	175	\N	\N	AP 4	Regular	363072.00	FISH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1651	175	\N	\N	AP 4	Regular	397356.00	FISH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1652	175	\N	\N	AP 4	Regular	401736.00	Fish	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1653	175	\N	\N	AP 4	Regular	429540.00	FISH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1654	175	\N	\N	AP 4	Regular	434412.00	Fishj	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1655	175	\N	\N	AP 4	Regular	464628.00	Fishj	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1656	175	\N	\N	AP 4	Regular	496956.00	Fishj	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1657	175	\N	\N	AP 4	Regular	531528.00	Fishj	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1658	175	\N	\N	AP 4	Regular	549792.00	Fishj	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1659	175	\N	\N	AP 4	Regular	568056.00	Fishj	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1660	175	\N	\N	Assistant Professor 4	Regular	230820.00	FISH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1661	175	\N	\N	ASSISTANT PROFESSOR IV	Regular	204708.00	College of Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1662	175	\N	\N	ASSISTANT PROFESSOR IV	Regular	225180.00	College of Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1663	176	\N	\N	ASSOCIATE PROFESSOR V	Regular	258732.00	College of Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1664	177	\N	\N	Lecturer	Contractual	0.00	FISHERIES	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1665	177	\N	\N	PROF 6	Regular	406032.00	FISH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1666	177	\N	\N	PROF 6	Regular	533628.00	FISH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1667	177	\N	\N	PROF 6	Regular	661224.00	FISH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1668	177	\N	\N	PROF 6	Regular	671340.00	FISH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1669	177	\N	\N	PROF 6	Regular	798912.00	FISH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1670	177	\N	\N	Professor 6	Regular	369120.00	FISH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1671	177	\N	\N	PROFESSOR VI	Regular	327372.00	College of Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1672	177	\N	\N	PROFESSOR VI	Regular	360108.00	College of Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1673	178	\N	\N	Asst Prof. IV	Regular	493680.00	Fishieries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1674	178	\N	\N	Asst Prof. IV	Regular	511944.00	Fishieries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1675	178	\N	\N	Asst Prof. IV	Regular	530208.00	Fishieries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1676	178	\N	\N	Asst.Prof. IV	Regular	401424.00	Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1677	178	\N	\N	Asst.Prof. IV	Regular	428316.00	Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1678	178	\N	\N	Asst.Prof. IV	Regular	457020.00	Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1679	178	\N	\N	Asst.Prof. IV	Regular	487644.00	Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1680	178	\N	\N	INSTRUCTOR II	Regular	142044.00	College of Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1681	178	\N	\N	INSTRUCTOR II	Regular	156252.00	College of Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1682	178	\N	\N	INSTRUCTOR II	Regular	171876.00	College of Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1683	178	\N	\N	INSTRUCTOR II	Regular	193212.00	College of Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1684	178	\N	\N	INSTRUCTOR II	Regular	214560.00	College of Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1685	178	\N	\N	INSTRUCTOR II	Regular	235896.00	College of Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1686	178	\N	\N	INSTRUCTOR II	Regular	257232.00	College of Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1687	178	\N	\N	INSTRUCTOR II	Regular	267936.00	College of Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1688	179	\N	\N	ASSISTANT PROFESSOR IV	Contractual	0.00	College of Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1689	180	\N	\N	Prof 5	Regular	337824.00	fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1690	180	\N	\N	Prof 5	Regular	371604.00	fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1691	180	\N	\N	Prof 5	Regular	486252.00	fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1692	180	\N	\N	Prof 5	Regular	600888.00	fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1693	180	\N	\N	Prof. 5	Regular	610092.00	Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1694	180	\N	\N	Prof. 5	Regular	724704.00	Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1695	180	\N	\N	Prof. 5	Regular	839304.00	Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1696	180	\N	\N	Prof. V	Regular	299616.00	College of Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1697	180	\N	\N	Prof. V	Regular	329580.00	College of Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1698	180	\N	\N	Prof. VI	Regular	877188.00	Fishiries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1699	181	\N	\N	A P  4	Regular	253908.00	FISH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1700	181	\N	\N	A P  4	Regular	288684.00	FISH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1701	181	\N	\N	A P  4	Regular	323472.00	FISH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1702	181	\N	\N	AP 4	Regular	328788.00	FISH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1703	181	\N	\N	AP 4	Regular	363072.00	FISH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1704	181	\N	\N	AP 4	Regular	397356.00	FISH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1705	181	\N	\N	AP 4	Regular	401736.00	Fish.	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1706	181	\N	\N	AP 4	Regular	429540.00	FISH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1707	181	\N	\N	Assistant Professor 4	Regular	230820.00	FISH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1708	181	\N	\N	ASSISTANT PROFESSOR IV	Regular	204708.00	College of Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1709	181	\N	\N	ASSISTANT PROFESSOR IV	Regular	225180.00	College of Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1710	182	\N	\N	Assistant Professor V	Regular	270876.00	Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1711	182	\N	\N	Assistant Professor V	Regular	297960.00	Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1712	182	\N	\N	Assistant Professor V	Regular	363180.00	Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1713	182	\N	\N	Assistant Professor V	Regular	428412.00	Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1714	182	\N	\N	Assistant Professor V	Regular	435216.00	Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1715	182	\N	\N	Assistant Professor V	Regular	500112.00	Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1716	182	\N	\N	Assistant Professor V	Regular	564996.00	Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1717	182	\N	\N	Assistant Professor V	Regular	577500.00	Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1718	182	\N	\N	Assistant Professor V	Regular	653160.00	Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1719	182	\N	\N	Assistant Professor V	Regular	661212.00	Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1720	182	\N	\N	Assistant Professor V	Regular	748824.00	Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1721	182	\N	\N	Assistant Professor V	Regular	848040.00	Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1722	182	\N	\N	Assistant Professor V	Regular	960408.00	Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1723	182	\N	\N	Assistant Professor V	Regular	979620.00	Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1724	182	\N	\N	Assistant Professor V	Regular	998820.00	Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1725	182	\N	\N	ASSO PROFESSOR V	Regular	240240.00	College of Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1726	182	\N	\N	ASSO PROFESSOR V	Regular	264264.00	College of Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1727	183	\N	\N	ASSISTANT PROFESSOR IV	Regular	209832.00	College of Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1728	183	\N	\N	ASSISTANT PROFESSOR IV	Regular	230820.00	College of Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1729	183	\N	\N	ASSISTANT PROFESSOR IV	Regular	260220.00	College of Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1730	184	\N	\N	Assistant Professor IV	Regular	344244.00	College of Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1731	184	\N	\N	Assistant Professor IV	Regular	401988.00	College of Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1732	184	\N	\N	Assistant Professor IV	Regular	459720.00	College of Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1733	184	\N	\N	Assistant Professor IV	Regular	523152.00	College of Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1734	184	\N	\N	Assistant Professor IV	Regular	583500.00	College of Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1735	184	\N	\N	Assistant Professor IV	Regular	650808.00	College of Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1736	184	\N	\N	Assistant Professor IV	Regular	725892.00	College of Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1737	184	\N	\N	Assistant Professor IV	Regular	809628.00	College of Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1738	184	\N	\N	Assistant Professor IV	Regular	828204.00	College of Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1739	184	\N	\N	Assistant Professor IV	Regular	841416.00	Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1740	184	\N	\N	Assistant Professor IV	Regular	859992.00	Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1741	184	\N	\N	ASSO PROF IV	Regular	231012.00	College of Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1742	184	\N	\N	ASSO PROF IV	Regular	254112.00	College of Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1743	184	\N	\N	ASSO PROF IV	Regular	279528.00	College of Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1744	184	\N	\N	ASSO PROF IV	Regular	337608.00	College of Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1745	185	\N	\N	Consultant	Contractual	0.00		\N	\N	\N	\N	2022-06-21 10:53:32	\N
1746	185	\N	\N	Consultant	Contractual	0.00	FISHERIES	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1747	185	\N	\N	Consultant	Contractual	120000.00	OP	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1748	185	\N	\N	PROF 6	Regular	406032.00	FISHERIES	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1749	185	\N	\N	PROF 6	Regular	533628.00	FISHERIES	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1750	185	\N	\N	Professor 6	Regular	369120.00	FISHERIES	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1751	185	\N	\N	PROFESSOR VI	Regular	327372.00	College of Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1752	185	\N	\N	PROFESSOR VI	Regular	360108.00	College of Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1753	186	\N	\N	Assistant Professor 4	Regular	280500.00	FISH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1754	186	\N	\N	Assistant Professor 4	Regular	308556.00	FISH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1755	186	\N	\N	Assistant Professor 4	Regular	365088.00	FISH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1756	186	\N	\N	Assistant Professor 4	Regular	421632.00	FISH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1757	186	\N	\N	Assistant Professor 4	Regular	428436.00	FISH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1758	186	\N	\N	Assistant Professor 4	Regular	484512.00	FISH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1759	186	\N	\N	ASSOCIATE PROFESSOR IV	Regular	248772.00	College of Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1760	186	\N	\N	ASSOCIATE PROFESSOR IV	Regular	273648.00	College of Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1761	186	\N	\N	Lecturer	Contractual	0.00	Fishiries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1762	187	\N	\N	Assistant Professor 5	Regular	291696.00	FISH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1763	187	\N	\N	Assistant Professor 5	Regular	320868.00	FISH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1764	187	\N	\N	Assistant Professor 5	Regular	385032.00	FISH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1765	187	\N	\N	Assistant Professor 5	Regular	449184.00	FISH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1766	187	\N	\N	Assistant Professor 5	Regular	456384.00	FISH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1767	187	\N	\N	Assistant Professor 5	Regular	520116.00	FISH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1768	187	\N	\N	Assistant Professor 5	Regular	583848.00	FISH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1769	187	\N	\N	Assistant Professor 5	Regular	596772.00	Fish.	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1770	187	\N	\N	Assistant Professor 5	Regular	677616.00	FISH	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1771	187	\N	\N	ASSOCIATE PROFESSOR V	Regular	258732.00	College of Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1772	187	\N	\N	ASSOCIATE PROFESSOR V	Regular	284604.00	College of Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1773	188	\N	\N	INSTRUCTOR I	Regular	134004.00	College of Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1774	188	\N	\N	INSTRUCTOR I	Regular	147408.00	College of Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1775	188	\N	\N	INSTRUCTOR I	Regular	162144.00	College of Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1776	188	\N	\N	INSTRUCTOR I	Regular	181428.00	College of Fisheries	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1777	189	\N	\N	Assoc. Prof. III	Regular	1321632.00	College of Forestry and Environmental Studies	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1778	189	\N	\N	Assoc. Prof. III	Regular	1617408.00	College of Forestry and Environmental Studies	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1779	189	\N	\N	Assoc. Prof. III	Regular	1962576.00	College of Forestry and Environmental Studies	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1780	189	\N	\N	Assoc. Prof. III	Regular	2307744.00	College of Forestry and Environmental Studies	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1781	189	\N	\N	Instructor B	Contractual	202320.00	MSU	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1782	189	\N	\N	Instructor B	Contractual	212688.00	College of Forestry and Environmental Studies	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1783	189	\N	\N	Instructor B	Contractual	235008.00	College of Forestry and Environmental Studies	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1784	189	\N	\N	Instructor B	Regular	259488.00	College of Forestry and Environmental Studies	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1785	189	\N	\N	Instructor D	Regular	233736.00	College of Forestry and Environmental Studies	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1786	189	\N	\N	Instructor D	Regular	286704.00	College of Forestry and Environmental Studies	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1787	189	\N	\N	Instructor D	Regular	367200.00	College of Forestry and Environmental Studies	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1788	189	\N	\N	Instructor D	Regular	403200.00	College of Forestry and Environmental Studies	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1789	189	\N	\N	Instructor III	Regular	589104.00	College of Forestry and Environmental Studies	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1790	189	\N	\N	Instructor III	Regular	619200.00	College of Forestry and Environmental Studies	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1791	189	\N	\N	Instructor III	Regular	720000.00	College of Forestry and Environmental Studies	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1792	189	\N	\N	Instructor III	Regular	864000.00	College of Forestry and Environmental Studies	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1793	189	\N	\N	ON SECONDMENT TO MSU-IIT		0.00		\N	\N	\N	\N	2022-06-21 10:53:32	\N
1794	189	\N	\N	Professor III	Regular	2807856.00	College of Forestry and Environmental Studies	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1795	189	\N	\N	Professor III	Regular	3088656.00	College of Forestry and Environmental Studies	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1796	189	\N	\N	Professor III	Regular	3243024.00	College of Forestry and Environmental Studies	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1797	189	\N	\N	Professor III	Regular	3324096.00	College of Forestry and Environmental Studies	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1798	190	\N	\N	Assistant Professor 5	Regular	291696.00	FORESTRY	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1799	190	\N	\N	Assistant Professor 5	Regular	320868.00	FORESTRY	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1800	190	\N	\N	Assistant Professor 5	Regular	385032.00	FORESTRY	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1801	190	\N	\N	Assistant Professor 5	Regular	449184.00	FORESTRY	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1802	190	\N	\N	Assistant Professor 5	Regular	456384.00	FORESTRY	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1803	190	\N	\N	Assistant Professor 5	Regular	520116.00	FORESTRY	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1804	190	\N	\N	Assistant Professor 5	Regular	583848.00	FORESTRY	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1805	190	\N	\N	Assistant Professor 5	Regular	590280.00	Forestry	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1806	190	\N	\N	ASSOCIATE PROFESSOR V	Regular	258732.00	College of Forestry and Environmental Sciences	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1807	190	\N	\N	ASSOCIATE PROFESSOR V	Regular	284604.00	College of Forestry and Environmental Sciences	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1808	191	\N	\N	Assistant Professor 5	Regular	291696.00	FORESTRY	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1809	191	\N	\N	Assistant Professor 5	Regular	320868.00	FORESTRY	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1810	191	\N	\N	Assistant Professor 5	Regular	385032.00	FORESTRY	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1811	191	\N	\N	Assistant Professor 5	Regular	449184.00	FORESTRY	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1812	191	\N	\N	Assistant Professor 5	Regular	456384.00	FORESTRY	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1813	191	\N	\N	Assistant Professor 5	Regular	520116.00	FORESTRY	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1814	191	\N	\N	Assistant Professor 5	Regular	583848.00	FORESTRY	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1815	191	\N	\N	Assistant Professor 5	Regular	590280.00	Forestry	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1816	191	\N	\N	Assistant Professor 5	Regular	669372.00	FORESTRY	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1817	191	\N	\N	Assistant Professor 5	Regular	759060.00	FORESTRY	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1818	191	\N	\N	Assistant Professor 5	Regular	860760.00	FORESTRY	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1819	191	\N	\N	Assistant Professor V	Regular	873660.00	FORESTRY	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1820	191	\N	\N	Assistant Professor V	Regular	992016.00	FORESTRY	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1821	191	\N	\N	Assistant Professor V	Regular	1011852.00	FORESTRY	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1822	191	\N	\N	Assistant Professor V	Regular	1031700.00	FORESTRY	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1823	191	\N	\N	ASSOCIATE PROFESSOR V	Regular	258732.00	College of Forestry and Environmental Studies	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1824	191	\N	\N	ASSOCIATE PROFESSOR V	Regular	284604.00	College of Forestry and Environmental Studies	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1825	191	\N	\N	Prof. VI	Regular	1934772.00	Forestry	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1826	192	\N	\N	Assistant Professor 4	Regular	280500.00	FORESTRY	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1827	192	\N	\N	Assistant Professor 4	Regular	308556.00	FORESTRY	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1828	192	\N	\N	Assistant Professor 4	Regular	365088.00	FORESTRY	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1829	192	\N	\N	Assistant Professor 4	Regular	421632.00	FORESTRY	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1830	192	\N	\N	Assistant Professor 4	Regular	428436.00	FORESTRY	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1831	192	\N	\N	Assistant Professor 4	Regular	484512.00	FORESTRY	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1832	192	\N	\N	Assistant Professor 4	Regular	540600.00	FORESTRY	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1833	192	\N	\N	Assistant Professor 4	Regular	546552.00	Forestry	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1834	192	\N	\N	Assistant Professor 4	Regular	612804.00	FORESTRY	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1835	192	\N	\N	Assistant Professor 4	Regular	687096.00	FORESTRY	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1836	192	\N	\N	Assistant Professor 4	Regular	770400.00	FORESTRY	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1837	192	\N	\N	Assistant Professor 4	Regular	781944.00	FORESTRY	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1838	192	\N	\N	Assistant Professor 4	Regular	877884.00	FORESTRY	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1839	192	\N	\N	Assistant Professor 4	Regular	896460.00	FORESTRY	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1840	192	\N	\N	ASSOCIATE PROFESSOR IV	Regular	248772.00	College of Forestry and Environmental Sciences	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1841	192	\N	\N	ASSOCIATE PROFESSOR IV	Regular	273648.00	College of Forestry and Environmental Sciences	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1842	192	\N	\N	Professor VI	Regular	1897572.00	MSU-Forestry	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1843	192	\N	\N	Professor VI	Regular	1934772.00	MSU-Forestry	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1844	193	\N	\N	Assistant Professor V	Regular	363180.00	College of Forestry	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1845	193	\N	\N	Assistant Professor V	Regular	428412.00	College of Forestry	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1846	193	\N	\N	Assistant Professor V	Regular	493632.00	College of Forestry	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1847	193	\N	\N	Assistant Professor V	Regular	558852.00	College of Forestry	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1848	193	\N	\N	Assistant Professor V	Regular	645204.00	College of Forestry	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1849	193	\N	\N	Assistant Professor V	Regular	728784.00	College of Forestry	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1850	193	\N	\N	Assistant Professor V	Regular	823176.00	College of Forestry	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1851	193	\N	\N	Assistant Professor V	Regular	835524.00	College of Forestry	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1852	193	\N	\N	Assistant Professor V	Regular	944988.00	College of Forestry	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1853	193	\N	\N	Assistant Professor V	Regular	963888.00	College of Forestry	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1854	193	\N	\N	Assistant Professor V	Regular	982788.00	College of Forestry	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1855	193	\N	\N	Assoc Pfof V	Regular	240240.00	College of Forestry and Environmental Sciences	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1856	193	\N	\N	Assoc Pfof V	Regular	264264.00	College of Forestry and Environmental Sciences	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1857	193	\N	\N	Assoc Pfof V	Regular	290688.00	College of Forestry and Environmental Sciences	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1858	193	\N	\N	Assoc Pfof V	Regular	356208.00	College of Forestry and Environmental Sciences	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1859	194	\N	\N	Assistant Professor V	Regular	297960.00	Forestry	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1860	194	\N	\N	Assistant Professor V	Regular	363180.00	Forestry	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1861	194	\N	\N	Assistant Professor V	Regular	428412.00	Forestry	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1862	194	\N	\N	Assistant Professor V	Regular	493632.00	Forestry	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1863	194	\N	\N	Assistant Professor V	Regular	500112.00	Forestry	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1864	194	\N	\N	Assistant Professor V	Regular	564996.00	Forestry	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1865	194	\N	\N	Assistant Professor V	Regular	637344.00	Forestry	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1866	194	\N	\N	Assoc. Prof. V	Regular	240240.00	College of Forestry and Environmental Science	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1867	194	\N	\N	Assoc. Prof. V	Regular	264264.00	College of Forestry and Environmental Science	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1868	194	\N	\N	Assoc. Prof. V	Regular	290688.00	College of Forestry and Environmental Science	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1869	194	\N	\N	Assoc. Prof. V	Regular	835524.00	CFES	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1870	194	\N	\N	Assoc.Prof. V	Regular	645204.00	CFES	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1871	194	\N	\N	Assoc.Prof. V	Regular	728784.00	CFES	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1872	194	\N	\N	Assoc.Prof. V	Regular	823176.00	CFES	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1873	194	\N	\N	ASSOCIATE PROFESSOR I	Regular	216996.00	College of Forestry and Environmental Sciences	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1874	194	\N	\N	Prof. VI	Regular	1541604.00	Forestriy	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1875	194	\N	\N	Prof. VI	Regular	1860360.00	Forestriy	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1876	194	\N	\N	Prof. VI	Regular	1897572.00	Forestriy	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1877	194	\N	\N	Prof. VI	Regular	1934772.00	Forestriy	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1878	194	\N	\N	Prof. VI	Regular	1966356.00	Forestry	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1879	195	\N	\N	PROFESSOR VI	Regular	327372.00	College of Forestry and Environmental Sciences	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1880	196	\N	\N	INSTRUCTOR III	Contractual	0.00	College of Forestry and Environmental Sciences	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1881	197	\N	\N	AP 4	Regular	328788.00	FORESTRY	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1882	197	\N	\N	AP 4	Regular	363072.00	FORESTRY	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1883	197	\N	\N	AP 4	Regular	397356.00	FORESTRY	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1884	197	\N	\N	AP 4	Regular	401736.00	Forestry	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1885	197	\N	\N	AP 4	Regular	429540.00	FORESTRY	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1886	197	\N	\N	AP 4	Regular	459264.00	FORESTRY	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1887	197	\N	\N	AP 4	Regular	491040.00	FORESTRY	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1888	197	\N	\N	AP 4	Regular	496956.00	FORESTRY	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1889	197	\N	\N	AP 4	Regular	531528.00	FORESTRY	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1890	197	\N	\N	AP 4	Regular	549792.00	FORESTRY	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1891	197	\N	\N	AP 4	Regular	568056.00	FORESTRY	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1892	197	\N	\N	AP IV	Regular	230820.00	FORESTRY	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1893	197	\N	\N	AP IV	Regular	253908.00	FORESTRY	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1894	197	\N	\N	AP IV	Regular	288684.00	FORESTRY	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1895	197	\N	\N	AP IV	Regular	323472.00	FORESTRY	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1896	197	\N	\N	ASSISTANT PROFESSOR IV	Regular	204708.00	College of Forestry and Environmental Sciences	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1897	197	\N	\N	ASSISTANT PROFESSOR IV	Regular	225180.00	College of Forestry and Environmental Sciences	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1898	198	\N	\N	PROFESSOR VI	Contractual	0.00	College of Forestry and Environmental Sciences	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1899	199	\N	\N	Prof V	Regular	291036.00	College of Forestry and Environmental Sciences	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1900	199	\N	\N	Prof V	Regular	321540.00	College of Forestry and Environmental Sciences	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1901	199	\N	\N	Prof V	Regular	362544.00	Forestry	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1902	199	\N	\N	Prof V	Regular	477192.00	Forestry	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1903	199	\N	\N	Prof V	Regular	486252.00	Forestry	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1904	199	\N	\N	Prof V	Regular	600888.00	Forestry	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1905	199	\N	\N	Prof V	Regular	715536.00	Forestry	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1906	199	\N	\N	Prof V	Regular	830172.00	Forestry	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1907	199	\N	\N	Prof V	Regular	993168.00	Forestry	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1908	199	\N	\N	Prof. V	Regular	839304.00	Forestry	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1909	199	\N	\N	Prof. VI	Regular	1058568.00	CFES	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1910	199	\N	\N	Prof. VI	Regular	1277448.00	CFES	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1911	199	\N	\N	Prof. VI	Regular	1541604.00	CFES	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1912	199	\N	\N	Professor V	Regular	329580.00	Forestry	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1913	199	\N	\N	Professor V	Regular	1005408.00	CFES	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1914	200	\N	\N	Assoc Prof. V	Regular	922884.00	Forestry	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1915	200	\N	\N	Asst Prof. IV	Regular	376212.00	Forestry	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1916	200	\N	\N	Asst Prof. IV	Regular	401424.00	College of Forestry and Environmental Sciences	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1917	200	\N	\N	Asst Prof. IV	Regular	428316.00	College of Forestry and Environmental Sciences	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1918	200	\N	\N	Asst Prof. IV	Regular	457020.00	College of Forestry and Environmental Sciences	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1919	200	\N	\N	Asst Prof. IV	Regular	487644.00	College of Forestry and Environmental Sciences	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1920	200	\N	\N	Asst. Prof. IV	Regular	487644.00	CFES	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1921	200	\N	\N	Asst.Prof. IV	Regular	493680.00	Forestry	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1922	200	\N	\N	Asst.Prof. IV	Regular	511944.00	Forestry	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1923	200	\N	\N	Asst.Prof. IV	Regular	536328.00	Forestry	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1924	200	\N	\N	Inst III	Regular	229344.00	College of Forestry and Environmental Sciences	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1925	200	\N	\N	Inst III	Regular	252936.00	College of Forestry and Environmental Sciences	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1926	200	\N	\N	Inst III	Regular	276528.00	College of Forestry and Environmental Sciences	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1927	200	\N	\N	INSTRUCTOR III	Regular	150552.00	College of Forestry and Environmental Sciences	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1928	200	\N	\N	INSTRUCTOR III	Regular	165612.00	College of Forestry and Environmental Sciences	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1929	200	\N	\N	INSTRUCTOR III	Regular	182172.00	College of Forestry and Environmental Sciences	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1930	200	\N	\N	INSTRUCTOR III	Regular	205764.00	College of Forestry and Environmental Sciences	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1931	201	\N	\N	ASSOCIATE PROFESSOR III	Contractual	0.00	College of Forestry and Environmental Sciences	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1932	202	\N	\N	INSTRUCTOR II	Regular	142044.00	College of Forestry and Environmental Sciences	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1933	202	\N	\N	INSTRUCTOR II	Regular	156252.00	College of Forestry and Environmental Sciences	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1934	202	\N	\N	INSTRUCTOR II	Regular	171876.00	College of Forestry and Environmental Sciences	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1935	202	\N	\N	INSTRUCTOR II	Regular	193212.00	College of Forestry and Environmental Sciences	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1936	202	\N	\N	INSTRUCTOR II	Regular	214560.00	College of Forestry and Environmental Sciences	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1937	202	\N	\N	INSTRUCTOR II	Regular	235896.00	College of Forestry and Environmental Sciences	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1938	203	\N	\N	INSTRUCTOR II	Regular	142044.00	College of Forestry and Environmental Sciences	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1939	203	\N	\N	INSTRUCTOR II	Regular	156252.00	College of Forestry and Environmental Sciences	\N	\N	\N	\N	2022-06-21 10:53:32	\N
1940	203	\N	\N	INSTRUCTOR II	Regular	171876.00	College of Forestry and Environmental Sciences	\N	\N	\N	\N	2022-06-21 10:53:32	\N
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