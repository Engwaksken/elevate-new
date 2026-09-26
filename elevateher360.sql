-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 26, 2026 at 02:18 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `elevateher360`
--

-- --------------------------------------------------------

--
-- Table structure for table `activities`
--

CREATE TABLE `activities` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `workplan_id` bigint(20) UNSIGNED NOT NULL,
  `milestone_id` bigint(20) UNSIGNED DEFAULT NULL,
  `programme_id` bigint(20) UNSIGNED DEFAULT NULL,
  `project_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cohort_id` bigint(20) UNSIGNED DEFAULT NULL,
  `activity_code` varchar(255) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `responsible_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `budget` decimal(15,2) DEFAULT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'UGX',
  `funding_source` varchar(255) DEFAULT NULL,
  `priority` varchar(255) NOT NULL DEFAULT 'medium',
  `status` enum('planned','not_started','in_progress','delayed','completed','cancelled') NOT NULL DEFAULT 'planned',
  `progress_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `expected_output` text DEFAULT NULL,
  `actual_output` text DEFAULT NULL,
  `challenges` text DEFAULT NULL,
  `lessons_learned` text DEFAULT NULL,
  `next_action` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `activity_assignments`
--

CREATE TABLE `activity_assignments` (
  `activity_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `activity_indicator`
--

CREATE TABLE `activity_indicator` (
  `activity_id` bigint(20) UNSIGNED NOT NULL,
  `indicator_id` bigint(20) UNSIGNED NOT NULL,
  `contribution_type` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ai_integrations`
--

CREATE TABLE `ai_integrations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `feature` varchar(255) NOT NULL,
  `provider` varchar(255) NOT NULL DEFAULT 'openai',
  `model` varchar(255) DEFAULT NULL,
  `endpoint` varchar(255) DEFAULT NULL,
  `encrypted_api_key` text DEFAULT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 0,
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`settings`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ai_integrations`
--

INSERT INTO `ai_integrations` (`id`, `feature`, `provider`, `model`, `endpoint`, `encrypted_api_key`, `enabled`, `settings`, `created_at`, `updated_at`) VALUES
(1, 'career_ai', 'gemini', 'gemini-3.5-flash', NULL, 'eyJpdiI6IjBPUnZKUTZjMHNjVnpKK0d2ZE5lZXc9PSIsInZhbHVlIjoiMFVPN3d6bXhOTUw5RDZLZU9ZaldPaVdCdXdQdkR1UCtuc0MzVkVBUThPajdGNVN6eU5sT1ZFdmVWZkI5bHpzeENvNks0SXFCNUVITXJUa3RmSnBhQVE9PSIsIm1hYyI6IjIxYjg3NmU0OTJjNzY0NWVlZjdjMTlhMjVhOTE4MGM5ZjVmYWYwYzIzZTE3ZWRkMTc3YzJlZDFjYTA2NjJlNmEiLCJ0YWciOiIifQ==', 1, '{\"daily_limit\":1000,\"user_daily_limit\":\"20\"}', '2026-09-24 06:08:47', '2026-09-24 06:08:47');

-- --------------------------------------------------------

--
-- Table structure for table `ai_usage_logs`
--

CREATE TABLE `ai_usage_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `feature` varchar(255) NOT NULL,
  `provider` varchar(255) DEFAULT NULL,
  `model` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'success',
  `input_tokens` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `output_tokens` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `duration_ms` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `error_code` varchar(255) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ai_usage_logs`
--

INSERT INTO `ai_usage_logs` (`id`, `user_id`, `feature`, `provider`, `model`, `status`, `input_tokens`, `output_tokens`, `duration_ms`, `error_code`, `metadata`, `created_at`, `updated_at`) VALUES
(1, 4, 'ats_review', 'gemini', 'gemini-3.5-flash', 'success', 0, 0, 31178, NULL, NULL, '2026-09-26 06:52:29', '2026-09-26 06:52:29'),
(2, 4, 'resume_improvement', 'gemini', 'gemini-3.5-flash', 'failed', 0, 0, 1877, 'RequestException', NULL, '2026-09-26 06:52:31', '2026-09-26 06:52:31');

-- --------------------------------------------------------

--
-- Table structure for table `api_clients`
--

CREATE TABLE `api_clients` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `client_key` varchar(255) NOT NULL,
  `client_secret_hash` varchar(255) NOT NULL,
  `allowed_scopes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`allowed_scopes`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `appraisals`
--

CREATE TABLE `appraisals` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `appraisal_cycle_id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `manager_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `hr_kpi_template_id` bigint(20) UNSIGNED DEFAULT NULL,
  `status` enum('goal_setting','self_assessment','manager_review','calibration','acknowledgement','completed') NOT NULL DEFAULT 'goal_setting',
  `self_score` decimal(5,2) DEFAULT NULL,
  `manager_score` decimal(5,2) DEFAULT NULL,
  `final_score` decimal(5,2) DEFAULT NULL,
  `employee_comments` text DEFAULT NULL,
  `manager_comments` text DEFAULT NULL,
  `development_plan` text DEFAULT NULL,
  `employee_acknowledged_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `completion_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `performance_percent` decimal(5,2) DEFAULT NULL,
  `employee_submitted_at` timestamp NULL DEFAULT NULL,
  `manager_submitted_at` timestamp NULL DEFAULT NULL,
  `manager_acknowledged_at` timestamp NULL DEFAULT NULL,
  `manager_acknowledgement_name` varchar(255) DEFAULT NULL,
  `employee_acknowledgement_name` varchar(255) DEFAULT NULL,
  `hr_finalised_at` timestamp NULL DEFAULT NULL,
  `hr_finalised_by` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `appraisals`
--

INSERT INTO `appraisals` (`id`, `appraisal_cycle_id`, `employee_id`, `manager_user_id`, `hr_kpi_template_id`, `status`, `self_score`, `manager_score`, `final_score`, `employee_comments`, `manager_comments`, `development_plan`, `employee_acknowledged_at`, `created_at`, `updated_at`, `completion_percent`, `performance_percent`, `employee_submitted_at`, `manager_submitted_at`, `manager_acknowledged_at`, `manager_acknowledgement_name`, `employee_acknowledgement_name`, `hr_finalised_at`, `hr_finalised_by`) VALUES
(1, 1, 1, 5, 1, 'self_assessment', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-25 06:32:41', '2026-09-25 06:32:41', 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `appraisal_cycles`
--

CREATE TABLE `appraisal_cycles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `cycle_type` enum('probation','mid_year','annual','special') NOT NULL DEFAULT 'annual',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `self_assessment_due` date DEFAULT NULL,
  `manager_review_due` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `appraisal_cycles`
--

INSERT INTO `appraisal_cycles` (`id`, `name`, `cycle_type`, `start_date`, `end_date`, `self_assessment_due`, `manager_review_due`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Q1', 'mid_year', '2026-01-12', '2026-06-30', '2026-07-06', NULL, 1, '2026-09-24 15:49:09', '2026-09-24 15:49:09');

-- --------------------------------------------------------

--
-- Table structure for table `appraisal_kpi_scores`
--

CREATE TABLE `appraisal_kpi_scores` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `appraisal_id` bigint(20) UNSIGNED NOT NULL,
  `hr_kpi_template_item_id` bigint(20) UNSIGNED NOT NULL,
  `employee_rating` decimal(5,2) DEFAULT NULL,
  `manager_rating` decimal(5,2) DEFAULT NULL,
  `agreed_rating` decimal(5,2) DEFAULT NULL,
  `okr_percent` decimal(6,2) DEFAULT NULL,
  `employee_comment` text DEFAULT NULL,
  `manager_comment` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `evidence_note` text DEFAULT NULL,
  `evidence_url` varchar(1000) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `appraisal_kpi_scores`
--

INSERT INTO `appraisal_kpi_scores` (`id`, `appraisal_id`, `hr_kpi_template_item_id`, `employee_rating`, `manager_rating`, `agreed_rating`, `okr_percent`, `employee_comment`, `manager_comment`, `created_at`, `updated_at`, `evidence_note`, `evidence_url`) VALUES
(1, 1, 6, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-25 06:32:41', '2026-09-25 06:32:41', NULL, NULL),
(2, 1, 8, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-25 06:32:41', '2026-09-25 06:32:41', NULL, NULL),
(3, 1, 10, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-25 06:32:41', '2026-09-25 06:32:41', NULL, NULL),
(4, 1, 12, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-25 06:32:41', '2026-09-25 06:32:41', NULL, NULL),
(5, 1, 13, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-25 06:32:41', '2026-09-25 06:32:41', NULL, NULL),
(6, 1, 15, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-25 06:32:41', '2026-09-25 06:32:41', NULL, NULL),
(7, 1, 16, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-25 06:32:41', '2026-09-25 06:32:41', NULL, NULL),
(8, 1, 18, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-25 06:32:41', '2026-09-25 06:32:41', NULL, NULL),
(9, 1, 19, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-25 06:32:41', '2026-09-25 06:32:41', NULL, NULL),
(10, 1, 22, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-25 06:32:41', '2026-09-25 06:32:41', NULL, NULL),
(11, 1, 23, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-25 06:32:41', '2026-09-25 06:32:41', NULL, NULL),
(12, 1, 24, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-25 06:32:41', '2026-09-25 06:32:41', NULL, NULL),
(13, 1, 26, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-25 06:32:41', '2026-09-25 06:32:41', NULL, NULL),
(14, 1, 27, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-25 06:32:41', '2026-09-25 06:32:41', NULL, NULL),
(15, 1, 28, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-25 06:32:41', '2026-09-25 06:32:41', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `appraisal_kpi_weekly_updates`
--

CREATE TABLE `appraisal_kpi_weekly_updates` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `appraisal_id` bigint(20) UNSIGNED NOT NULL,
  `hr_kpi_template_item_id` bigint(20) UNSIGNED NOT NULL,
  `week_number` tinyint(3) UNSIGNED NOT NULL,
  `actual_target` varchar(255) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `appraisal_objectives`
--

CREATE TABLE `appraisal_objectives` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `appraisal_id` bigint(20) UNSIGNED NOT NULL,
  `workplan_id` bigint(20) UNSIGNED DEFAULT NULL,
  `milestone_id` bigint(20) UNSIGNED DEFAULT NULL,
  `activity_id` bigint(20) UNSIGNED DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `expected_result` text DEFAULT NULL,
  `weight` decimal(5,2) NOT NULL DEFAULT 1.00,
  `self_rating` decimal(5,2) DEFAULT NULL,
  `manager_rating` decimal(5,2) DEFAULT NULL,
  `employee_evidence` text DEFAULT NULL,
  `manager_feedback` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assessments`
--

CREATE TABLE `assessments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `course_id` bigint(20) UNSIGNED NOT NULL,
  `course_module_id` bigint(20) UNSIGNED DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `type` enum('quiz','assignment','exam') NOT NULL DEFAULT 'quiz',
  `instructions` text DEFAULT NULL,
  `pass_mark` decimal(5,2) NOT NULL DEFAULT 50.00,
  `max_attempts` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `opens_at` timestamp NULL DEFAULT NULL,
  `due_at` timestamp NULL DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assessment_answers`
--

CREATE TABLE `assessment_answers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `assessment_attempt_id` bigint(20) UNSIGNED NOT NULL,
  `assessment_question_id` bigint(20) UNSIGNED NOT NULL,
  `answer_text` longtext DEFAULT NULL,
  `answer_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`answer_json`)),
  `awarded_marks` decimal(6,2) DEFAULT NULL,
  `grader_feedback` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assessment_attempts`
--

CREATE TABLE `assessment_attempts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `assessment_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `attempt_number` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `score` decimal(6,2) DEFAULT NULL,
  `percentage` decimal(5,2) DEFAULT NULL,
  `status` enum('started','submitted','graded') NOT NULL DEFAULT 'started',
  `started_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `submitted_at` timestamp NULL DEFAULT NULL,
  `graded_at` timestamp NULL DEFAULT NULL,
  `graded_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assessment_questions`
--

CREATE TABLE `assessment_questions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `assessment_id` bigint(20) UNSIGNED NOT NULL,
  `question_type` enum('multiple_choice','true_false','short_text','long_text') NOT NULL DEFAULT 'multiple_choice',
  `question_text` text NOT NULL,
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`options`)),
  `correct_answer` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`correct_answer`)),
  `marks` decimal(6,2) NOT NULL DEFAULT 1.00,
  `position` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assets`
--

CREATE TABLE `assets` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `asset_code` varchar(255) NOT NULL,
  `asset_tag` varchar(255) DEFAULT NULL,
  `asset_category_id` bigint(20) UNSIGNED DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `brand` varchar(255) DEFAULT NULL,
  `model` varchar(255) DEFAULT NULL,
  `serial_number` varchar(255) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `purchase_price` decimal(15,2) DEFAULT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'UGX',
  `supplier_id` bigint(20) UNSIGNED DEFAULT NULL,
  `purchase_order_id` bigint(20) UNSIGNED DEFAULT NULL,
  `goods_receipt_id` bigint(20) UNSIGNED DEFAULT NULL,
  `programme_id` bigint(20) UNSIGNED DEFAULT NULL,
  `project_id` bigint(20) UNSIGNED DEFAULT NULL,
  `funding_source` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `custodian_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `condition` varchar(255) NOT NULL DEFAULT 'good',
  `warranty_end_date` date DEFAULT NULL,
  `status` enum('available','assigned','in_use','under_maintenance','damaged','lost','retired','disposed') NOT NULL DEFAULT 'available',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `asset_assignments`
--

CREATE TABLE `asset_assignments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `asset_id` bigint(20) UNSIGNED NOT NULL,
  `assigned_to_user_id` bigint(20) UNSIGNED NOT NULL,
  `assigned_date` date NOT NULL,
  `expected_return_date` date DEFAULT NULL,
  `returned_date` date DEFAULT NULL,
  `assignment_notes` text DEFAULT NULL,
  `return_condition` text DEFAULT NULL,
  `assigned_by` bigint(20) UNSIGNED DEFAULT NULL,
  `received_back_by` bigint(20) UNSIGNED DEFAULT NULL,
  `status` enum('active','returned','lost','damaged') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `asset_categories`
--

CREATE TABLE `asset_categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `asset_disposals`
--

CREATE TABLE `asset_disposals` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `asset_id` bigint(20) UNSIGNED NOT NULL,
  `requested_date` date NOT NULL,
  `reason` text NOT NULL,
  `disposal_method` varchar(255) DEFAULT NULL,
  `disposal_value` decimal(15,2) DEFAULT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'UGX',
  `status` enum('requested','approved','completed','rejected') NOT NULL DEFAULT 'requested',
  `requested_by` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `asset_maintenance`
--

CREATE TABLE `asset_maintenance` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `asset_id` bigint(20) UNSIGNED NOT NULL,
  `reported_date` date NOT NULL,
  `maintenance_type` varchar(255) DEFAULT NULL,
  `issue_description` text DEFAULT NULL,
  `supplier_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cost` decimal(15,2) DEFAULT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'UGX',
  `completed_date` date DEFAULT NULL,
  `resolution` text DEFAULT NULL,
  `status` enum('reported','in_progress','completed','cancelled') NOT NULL DEFAULT 'reported',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `asset_stocktakes`
--

CREATE TABLE `asset_stocktakes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `stocktake_date` date NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `conducted_by` bigint(20) UNSIGNED DEFAULT NULL,
  `status` enum('draft','in_progress','completed') NOT NULL DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `asset_stocktake_items`
--

CREATE TABLE `asset_stocktake_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `asset_stocktake_id` bigint(20) UNSIGNED NOT NULL,
  `asset_id` bigint(20) UNSIGNED NOT NULL,
  `found` tinyint(1) NOT NULL DEFAULT 0,
  `observed_condition` varchar(255) DEFAULT NULL,
  `observed_location` varchar(255) DEFAULT NULL,
  `variance_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `asset_transfers`
--

CREATE TABLE `asset_transfers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `asset_id` bigint(20) UNSIGNED NOT NULL,
  `from_location` varchar(255) DEFAULT NULL,
  `to_location` varchar(255) DEFAULT NULL,
  `from_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `to_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `transfer_date` date NOT NULL,
  `reason` text DEFAULT NULL,
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance_records`
--

CREATE TABLE `attendance_records` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `attendance_session_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `status` enum('present','absent','late','excused') NOT NULL DEFAULT 'present',
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance_sessions`
--

CREATE TABLE `attendance_sessions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `course_id` bigint(20) UNSIGNED NOT NULL,
  `cohort_id` bigint(20) UNSIGNED DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `session_date` date NOT NULL,
  `starts_at` time DEFAULT NULL,
  `ends_at` time DEFAULT NULL,
  `venue` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `module` varchar(255) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `auditable_type` varchar(255) DEFAULT NULL,
  `auditable_id` bigint(20) UNSIGNED DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `occurred_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `module`, `action`, `auditable_type`, `auditable_id`, `old_values`, `new_values`, `ip_address`, `user_agent`, `occurred_at`) VALUES
(1, 4, 'auth', 'participant_registered', 'App\\Models\\User', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-22 13:09:58'),
(2, 5, 'branches', 'created', 'App\\Models\\Branch', 1, NULL, '{\"name\":\"Kampala\",\"code\":\"KLA\",\"district\":\"Kampala\",\"country\":\"Uganda\",\"is_active\":true,\"updated_at\":\"2026-09-24T05:59:02.000000Z\",\"created_at\":\"2026-09-24T05:59:02.000000Z\",\"id\":1}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 05:59:02'),
(3, 5, 'programmes', 'created', 'App\\Models\\Programme', 1, NULL, '{\"name\":\"DE\",\"code\":null,\"description\":\"DE\",\"start_date\":\"2025-05-23T21:00:00.000000Z\",\"end_date\":\"2027-05-30T21:00:00.000000Z\",\"status\":\"active\",\"updated_at\":\"2026-09-24T06:01:47.000000Z\",\"created_at\":\"2026-09-24T06:01:47.000000Z\",\"id\":1}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 06:01:47'),
(4, 5, 'cohorts', 'created', 'App\\Models\\Cohort', 1, NULL, '{\"programme_id\":\"1\",\"project_id\":null,\"branch_id\":\"1\",\"name\":\"Kampala Cohort 1\",\"code\":\"KLA-C1-2026\",\"start_date\":\"2026-05-31T21:00:00.000000Z\",\"end_date\":\"2026-09-29T21:00:00.000000Z\",\"status\":\"active\",\"updated_at\":\"2026-09-24T06:15:24.000000Z\",\"created_at\":\"2026-09-24T06:15:24.000000Z\",\"id\":1}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 06:15:24'),
(5, 5, 'courses', 'created', 'App\\Models\\Course', 1, NULL, '{\"programme_id\":\"1\",\"project_id\":null,\"branch_id\":\"1\",\"title\":\"Digital Marketing\",\"code\":null,\"summary\":null,\"description\":null,\"delivery_mode\":\"blended\",\"start_date\":\"2026-05-31T21:00:00.000000Z\",\"end_date\":\"2026-09-29T21:00:00.000000Z\",\"duration_hours\":\"24\",\"pass_mark\":\"50.00\",\"status\":\"published\",\"self_enrolment_enabled\":false,\"created_by\":5,\"updated_at\":\"2026-09-24T09:18:34.000000Z\",\"created_at\":\"2026-09-24T09:18:34.000000Z\",\"id\":1}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 09:18:34'),
(6, 5, 'Planning & Delivery', 'created', 'App\\Models\\Workplan', 1, '[]', '{\"programme_id\":\"1\",\"project_id\":null,\"cohort_id\":\"1\",\"title\":\"Workplan\",\"financial_year\":\"2026\\/27\",\"period_type\":\"annual\",\"start_date\":\"2026-01-01 00:00:00\",\"end_date\":\"2026-12-18 00:00:00\",\"responsible_user_id\":\"5\",\"description\":null,\"created_by\":5,\"status\":\"draft\",\"progress_percent\":0,\"updated_at\":\"2026-09-24 16:04:19\",\"created_at\":\"2026-09-24 16:04:19\",\"id\":1}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 13:04:19'),
(7, 5, 'Planning & Delivery', 'updated', 'App\\Models\\Workplan', 1, '{\"status\":\"draft\",\"updated_at\":\"2026-09-24T13:04:19.000000Z\"}', '{\"status\":\"submitted\",\"updated_at\":\"2026-09-24 16:04:57\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 13:04:57'),
(8, 5, 'users', 'created', 'App\\Models\\User', 6, NULL, '{\"name\":\"Waks Kenneth\",\"email\":\"wakskenneth@gmail.com\",\"phone\":\"+256784675790\",\"user_type\":\"staff\",\"status\":\"active\",\"email_verified_at\":\"2026-09-24T14:05:59.000000Z\",\"updated_at\":\"2026-09-24T14:05:59.000000Z\",\"created_at\":\"2026-09-24T14:05:59.000000Z\",\"id\":6,\"roles\":[{\"id\":10,\"name\":\"Instructor \\/ Trainer\",\"slug\":\"instructor\",\"description\":null,\"is_system\":true,\"created_at\":\"2026-09-22T08:27:34.000000Z\",\"updated_at\":\"2026-09-22T08:27:34.000000Z\",\"pivot\":{\"user_id\":6,\"role_id\":10,\"created_at\":\"2026-09-24T14:05:59.000000Z\",\"updated_at\":\"2026-09-24T14:05:59.000000Z\"}}]}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 14:05:59'),
(9, 5, 'users', 'updated', 'App\\Models\\User', 6, '{\"id\":6,\"name\":\"Waks Kenneth\",\"email\":\"wakskenneth@gmail.com\",\"user_type\":\"staff\",\"status\":\"active\",\"phone\":\"+256784675790\",\"email_verified_at\":\"2026-09-24T14:05:59.000000Z\",\"created_at\":\"2026-09-24T14:05:59.000000Z\",\"updated_at\":\"2026-09-24T14:05:59.000000Z\",\"last_login_at\":null,\"mfa_enabled\":0,\"mfa_secret\":null,\"mfa_recovery_codes\":null,\"mfa_confirmed_at\":null,\"roles\":[{\"id\":10,\"name\":\"Instructor \\/ Trainer\",\"slug\":\"instructor\",\"description\":null,\"is_system\":true,\"created_at\":\"2026-09-22T08:27:34.000000Z\",\"updated_at\":\"2026-09-22T08:27:34.000000Z\",\"pivot\":{\"user_id\":6,\"role_id\":10,\"created_at\":\"2026-09-24T14:05:59.000000Z\",\"updated_at\":\"2026-09-24T14:05:59.000000Z\"}}]}', '{\"id\":6,\"name\":\"Waks Kenneth\",\"email\":\"wakskenneth@gmail.com\",\"user_type\":\"staff\",\"status\":\"active\",\"phone\":\"+256784675790\",\"email_verified_at\":\"2026-09-24T14:05:59.000000Z\",\"created_at\":\"2026-09-24T14:05:59.000000Z\",\"updated_at\":\"2026-09-24T14:05:59.000000Z\",\"last_login_at\":null,\"mfa_enabled\":0,\"mfa_secret\":null,\"mfa_recovery_codes\":null,\"mfa_confirmed_at\":null,\"roles\":[{\"id\":6,\"name\":\"M&E \\/ MEAL Lead\",\"slug\":\"meal-lead\",\"description\":null,\"is_system\":true,\"created_at\":\"2026-09-22T08:27:34.000000Z\",\"updated_at\":\"2026-09-22T08:27:34.000000Z\",\"pivot\":{\"user_id\":6,\"role_id\":6,\"created_at\":\"2026-09-25T06:29:25.000000Z\",\"updated_at\":\"2026-09-25T06:29:25.000000Z\"}}]}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 06:29:25'),
(10, 5, 'Human Resources', 'created', 'App\\Models\\Employee', 1, '[]', '{\"user_id\":\"6\",\"employee_number\":\"0001\",\"department_id\":null,\"position_id\":null,\"supervisor_user_id\":\"5\",\"employment_type\":\"Full Time\",\"work_location\":\"Kampala\",\"start_date\":\"2026-07-01 00:00:00\",\"probation_end_date\":null,\"status\":\"active\",\"updated_at\":\"2026-09-25 09:30:40\",\"created_at\":\"2026-09-25 09:30:40\",\"id\":1}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 06:30:40'),
(11, 5, 'Human Resources', 'created', 'App\\Models\\Appraisal', 1, '[]', '{\"appraisal_cycle_id\":\"1\",\"employee_id\":\"1\",\"manager_user_id\":\"5\",\"hr_kpi_template_id\":\"1\",\"status\":\"self_assessment\",\"updated_at\":\"2026-09-25 09:32:41\",\"created_at\":\"2026-09-25 09:32:41\",\"id\":1}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 06:32:41'),
(12, 5, 'branches', 'created', 'App\\Models\\Branch', 2, NULL, '{\"name\":\"Mbarara\",\"code\":\"MBR\",\"district\":\"Mbarara\",\"country\":\"Uganda\",\"is_active\":true,\"updated_at\":\"2026-09-25T06:38:31.000000Z\",\"created_at\":\"2026-09-25T06:38:31.000000Z\",\"id\":2}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 06:38:31'),
(13, 5, 'Human Resources', 'created', 'App\\Models\\Employee', 2, '[]', '{\"user_id\":\"5\",\"employee_number\":\"0002\",\"department_id\":null,\"position_id\":null,\"supervisor_user_id\":null,\"employment_type\":null,\"work_location\":\"Kampala\",\"start_date\":null,\"probation_end_date\":null,\"status\":\"active\",\"updated_at\":\"2026-09-25 15:01:14\",\"created_at\":\"2026-09-25 15:01:14\",\"id\":2}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 12:01:14'),
(14, 5, 'roles', 'permissions_updated', 'App\\Models\\Role', 15, '{\"id\":15,\"name\":\"HR\",\"slug\":\"hr\",\"description\":null,\"is_system\":true,\"created_at\":\"2026-09-22T08:27:34.000000Z\",\"updated_at\":\"2026-09-22T08:27:34.000000Z\",\"permissions\":[]}', '{\"id\":15,\"name\":\"HR\",\"slug\":\"hr\",\"description\":null,\"is_system\":true,\"created_at\":\"2026-09-22T08:27:34.000000Z\",\"updated_at\":\"2026-09-22T08:27:34.000000Z\",\"permissions\":[{\"id\":17,\"name\":\"Mentors View\",\"slug\":\"mentors.view\",\"module\":\"mentors\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":15,\"permission_id\":17,\"created_at\":\"2026-09-25T13:03:37.000000Z\",\"updated_at\":\"2026-09-25T13:03:37.000000Z\"}},{\"id\":18,\"name\":\"Mentors Manage\",\"slug\":\"mentors.manage\",\"module\":\"mentors\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":15,\"permission_id\":18,\"created_at\":\"2026-09-25T13:03:37.000000Z\",\"updated_at\":\"2026-09-25T13:03:37.000000Z\"}},{\"id\":20,\"name\":\"Jobs View\",\"slug\":\"jobs.view\",\"module\":\"jobs\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":15,\"permission_id\":20,\"created_at\":\"2026-09-25T13:03:37.000000Z\",\"updated_at\":\"2026-09-25T13:03:37.000000Z\"}},{\"id\":21,\"name\":\"Jobs Manage\",\"slug\":\"jobs.manage\",\"module\":\"jobs\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":15,\"permission_id\":21,\"created_at\":\"2026-09-25T13:03:37.000000Z\",\"updated_at\":\"2026-09-25T13:03:37.000000Z\"}},{\"id\":22,\"name\":\"Employers Approve\",\"slug\":\"employers.approve\",\"module\":\"employers\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":15,\"permission_id\":22,\"created_at\":\"2026-09-25T13:03:37.000000Z\",\"updated_at\":\"2026-09-25T13:03:37.000000Z\"}},{\"id\":29,\"name\":\"Activities Manage\",\"slug\":\"activities.manage\",\"module\":\"activities\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":15,\"permission_id\":29,\"created_at\":\"2026-09-25T13:03:37.000000Z\",\"updated_at\":\"2026-09-25T13:03:37.000000Z\"}},{\"id\":30,\"name\":\"Tasks Manage\",\"slug\":\"tasks.manage\",\"module\":\"tasks\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":15,\"permission_id\":30,\"created_at\":\"2026-09-25T13:03:37.000000Z\",\"updated_at\":\"2026-09-25T13:03:37.000000Z\"}},{\"id\":36,\"name\":\"Calendar Manage\",\"slug\":\"calendar.manage\",\"module\":\"calendar\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":15,\"permission_id\":36,\"created_at\":\"2026-09-25T13:03:37.000000Z\",\"updated_at\":\"2026-09-25T13:03:37.000000Z\"}},{\"id\":37,\"name\":\"Hr View\",\"slug\":\"hr.view\",\"module\":\"hr\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":15,\"permission_id\":37,\"created_at\":\"2026-09-25T13:03:37.000000Z\",\"updated_at\":\"2026-09-25T13:03:37.000000Z\"}},{\"id\":38,\"name\":\"Hr Manage\",\"slug\":\"hr.manage\",\"module\":\"hr\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":15,\"permission_id\":38,\"created_at\":\"2026-09-25T13:03:37.000000Z\",\"updated_at\":\"2026-09-25T13:03:37.000000Z\"}},{\"id\":39,\"name\":\"Leave View\",\"slug\":\"leave.view\",\"module\":\"leave\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":15,\"permission_id\":39,\"created_at\":\"2026-09-25T13:03:37.000000Z\",\"updated_at\":\"2026-09-25T13:03:37.000000Z\"}},{\"id\":40,\"name\":\"Leave Approve\",\"slug\":\"leave.approve\",\"module\":\"leave\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":15,\"permission_id\":40,\"created_at\":\"2026-09-25T13:03:37.000000Z\",\"updated_at\":\"2026-09-25T13:03:37.000000Z\"}},{\"id\":41,\"name\":\"Appraisals View\",\"slug\":\"appraisals.view\",\"module\":\"appraisals\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":15,\"permission_id\":41,\"created_at\":\"2026-09-25T13:03:37.000000Z\",\"updated_at\":\"2026-09-25T13:03:37.000000Z\"}},{\"id\":42,\"name\":\"Appraisals Manage\",\"slug\":\"appraisals.manage\",\"module\":\"appraisals\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":15,\"permission_id\":42,\"created_at\":\"2026-09-25T13:03:37.000000Z\",\"updated_at\":\"2026-09-25T13:03:37.000000Z\"}},{\"id\":43,\"name\":\"Staff Exit Manage\",\"slug\":\"staff_exit.manage\",\"module\":\"staff_exit\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":15,\"permission_id\":43,\"created_at\":\"2026-09-25T13:03:37.000000Z\",\"updated_at\":\"2026-09-25T13:03:37.000000Z\"}},{\"id\":44,\"name\":\"Assets View\",\"slug\":\"assets.view\",\"module\":\"assets\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":15,\"permission_id\":44,\"created_at\":\"2026-09-25T13:03:37.000000Z\",\"updated_at\":\"2026-09-25T13:03:37.000000Z\"}},{\"id\":45,\"name\":\"Assets Manage\",\"slug\":\"assets.manage\",\"module\":\"assets\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":15,\"permission_id\":45,\"created_at\":\"2026-09-25T13:03:37.000000Z\",\"updated_at\":\"2026-09-25T13:03:37.000000Z\"}},{\"id\":46,\"name\":\"Assets Dispose\",\"slug\":\"assets.dispose\",\"module\":\"assets\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":15,\"permission_id\":46,\"created_at\":\"2026-09-25T13:03:37.000000Z\",\"updated_at\":\"2026-09-25T13:03:37.000000Z\"}}]}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 13:03:37'),
(15, 5, 'roles', 'permissions_updated', 'App\\Models\\Role', 2, '{\"id\":2,\"name\":\"Administrator\",\"slug\":\"administrator\",\"description\":null,\"is_system\":true,\"created_at\":\"2026-09-22T08:27:34.000000Z\",\"updated_at\":\"2026-09-22T08:27:34.000000Z\",\"permissions\":[]}', '{\"id\":2,\"name\":\"Administrator\",\"slug\":\"administrator\",\"description\":null,\"is_system\":true,\"created_at\":\"2026-09-22T08:27:34.000000Z\",\"updated_at\":\"2026-09-22T08:27:34.000000Z\",\"permissions\":[{\"id\":1,\"name\":\"Users View\",\"slug\":\"users.view\",\"module\":\"users\",\"description\":null,\"created_at\":\"2026-09-22T08:27:34.000000Z\",\"updated_at\":\"2026-09-22T08:27:34.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":1,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":2,\"name\":\"Users Create\",\"slug\":\"users.create\",\"module\":\"users\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":2,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":3,\"name\":\"Users Edit\",\"slug\":\"users.edit\",\"module\":\"users\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":3,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":4,\"name\":\"Users Delete\",\"slug\":\"users.delete\",\"module\":\"users\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":4,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":5,\"name\":\"Roles Manage\",\"slug\":\"roles.manage\",\"module\":\"roles\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":5,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":6,\"name\":\"Permissions Manage\",\"slug\":\"permissions.manage\",\"module\":\"permissions\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":6,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":7,\"name\":\"Programmes View\",\"slug\":\"programmes.view\",\"module\":\"programmes\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":7,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":8,\"name\":\"Programmes Manage\",\"slug\":\"programmes.manage\",\"module\":\"programmes\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":8,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":9,\"name\":\"Cohorts View\",\"slug\":\"cohorts.view\",\"module\":\"cohorts\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":9,\"created_at\":\"2026-09-25T13:03:50.000000Z\",\"updated_at\":\"2026-09-25T13:03:50.000000Z\"}},{\"id\":10,\"name\":\"Cohorts Manage\",\"slug\":\"cohorts.manage\",\"module\":\"cohorts\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":10,\"created_at\":\"2026-09-25T13:03:50.000000Z\",\"updated_at\":\"2026-09-25T13:03:50.000000Z\"}},{\"id\":11,\"name\":\"Courses View\",\"slug\":\"courses.view\",\"module\":\"courses\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":11,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":12,\"name\":\"Courses Create\",\"slug\":\"courses.create\",\"module\":\"courses\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":12,\"created_at\":\"2026-09-25T13:03:50.000000Z\",\"updated_at\":\"2026-09-25T13:03:50.000000Z\"}},{\"id\":13,\"name\":\"Courses Edit\",\"slug\":\"courses.edit\",\"module\":\"courses\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":13,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":14,\"name\":\"Courses Delete\",\"slug\":\"courses.delete\",\"module\":\"courses\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":14,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":15,\"name\":\"Students View\",\"slug\":\"students.view\",\"module\":\"students\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":15,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":16,\"name\":\"Students Edit\",\"slug\":\"students.edit\",\"module\":\"students\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":16,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":17,\"name\":\"Mentors View\",\"slug\":\"mentors.view\",\"module\":\"mentors\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":17,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":18,\"name\":\"Mentors Manage\",\"slug\":\"mentors.manage\",\"module\":\"mentors\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":18,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":19,\"name\":\"Mentorship Match\",\"slug\":\"mentorship.match\",\"module\":\"mentorship\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":19,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":20,\"name\":\"Jobs View\",\"slug\":\"jobs.view\",\"module\":\"jobs\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":20,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":21,\"name\":\"Jobs Manage\",\"slug\":\"jobs.manage\",\"module\":\"jobs\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":21,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":22,\"name\":\"Employers Approve\",\"slug\":\"employers.approve\",\"module\":\"employers\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":22,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":23,\"name\":\"Library Manage\",\"slug\":\"library.manage\",\"module\":\"library\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":23,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":24,\"name\":\"Workplans View\",\"slug\":\"workplans.view\",\"module\":\"workplans\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":24,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":25,\"name\":\"Workplans Create\",\"slug\":\"workplans.create\",\"module\":\"workplans\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":25,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":26,\"name\":\"Workplans Edit\",\"slug\":\"workplans.edit\",\"module\":\"workplans\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":26,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":27,\"name\":\"Workplans Approve\",\"slug\":\"workplans.approve\",\"module\":\"workplans\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":27,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":28,\"name\":\"Milestones Manage\",\"slug\":\"milestones.manage\",\"module\":\"milestones\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":28,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":29,\"name\":\"Activities Manage\",\"slug\":\"activities.manage\",\"module\":\"activities\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":29,\"created_at\":\"2026-09-25T13:03:50.000000Z\",\"updated_at\":\"2026-09-25T13:03:50.000000Z\"}},{\"id\":30,\"name\":\"Tasks Manage\",\"slug\":\"tasks.manage\",\"module\":\"tasks\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":30,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":31,\"name\":\"Indicators View\",\"slug\":\"indicators.view\",\"module\":\"indicators\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":31,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":32,\"name\":\"Indicators Manage\",\"slug\":\"indicators.manage\",\"module\":\"indicators\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":32,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":33,\"name\":\"Indicators Verify\",\"slug\":\"indicators.verify\",\"module\":\"indicators\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":33,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":34,\"name\":\"Meal View\",\"slug\":\"meal.view\",\"module\":\"meal\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":34,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":35,\"name\":\"Meal Manage\",\"slug\":\"meal.manage\",\"module\":\"meal\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":35,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":36,\"name\":\"Calendar Manage\",\"slug\":\"calendar.manage\",\"module\":\"calendar\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":36,\"created_at\":\"2026-09-25T13:03:50.000000Z\",\"updated_at\":\"2026-09-25T13:03:50.000000Z\"}},{\"id\":37,\"name\":\"Hr View\",\"slug\":\"hr.view\",\"module\":\"hr\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":37,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":38,\"name\":\"Hr Manage\",\"slug\":\"hr.manage\",\"module\":\"hr\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":38,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":39,\"name\":\"Leave View\",\"slug\":\"leave.view\",\"module\":\"leave\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":39,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":40,\"name\":\"Leave Approve\",\"slug\":\"leave.approve\",\"module\":\"leave\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":40,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":41,\"name\":\"Appraisals View\",\"slug\":\"appraisals.view\",\"module\":\"appraisals\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":41,\"created_at\":\"2026-09-25T13:03:50.000000Z\",\"updated_at\":\"2026-09-25T13:03:50.000000Z\"}},{\"id\":42,\"name\":\"Appraisals Manage\",\"slug\":\"appraisals.manage\",\"module\":\"appraisals\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":42,\"created_at\":\"2026-09-25T13:03:50.000000Z\",\"updated_at\":\"2026-09-25T13:03:50.000000Z\"}},{\"id\":43,\"name\":\"Staff Exit Manage\",\"slug\":\"staff_exit.manage\",\"module\":\"staff_exit\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":43,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":44,\"name\":\"Assets View\",\"slug\":\"assets.view\",\"module\":\"assets\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":44,\"created_at\":\"2026-09-25T13:03:50.000000Z\",\"updated_at\":\"2026-09-25T13:03:50.000000Z\"}},{\"id\":45,\"name\":\"Assets Manage\",\"slug\":\"assets.manage\",\"module\":\"assets\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":45,\"created_at\":\"2026-09-25T13:03:50.000000Z\",\"updated_at\":\"2026-09-25T13:03:50.000000Z\"}},{\"id\":46,\"name\":\"Assets Dispose\",\"slug\":\"assets.dispose\",\"module\":\"assets\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":46,\"created_at\":\"2026-09-25T13:03:50.000000Z\",\"updated_at\":\"2026-09-25T13:03:50.000000Z\"}},{\"id\":47,\"name\":\"Procurement View\",\"slug\":\"procurement.view\",\"module\":\"procurement\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":47,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":48,\"name\":\"Procurement Create\",\"slug\":\"procurement.create\",\"module\":\"procurement\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":48,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":49,\"name\":\"Procurement Approve\",\"slug\":\"procurement.approve\",\"module\":\"procurement\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":49,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":50,\"name\":\"Procurement Receive\",\"slug\":\"procurement.receive\",\"module\":\"procurement\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":50,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":51,\"name\":\"Reports View\",\"slug\":\"reports.view\",\"module\":\"reports\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":51,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":52,\"name\":\"Reports Export\",\"slug\":\"reports.export\",\"module\":\"reports\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":52,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":53,\"name\":\"Settings Manage\",\"slug\":\"settings.manage\",\"module\":\"settings\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":53,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}}]}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 13:03:51'),
(16, 5, 'roles', 'permissions_updated', 'App\\Models\\Role', 10, '{\"id\":10,\"name\":\"Instructor \\/ Trainer\",\"slug\":\"instructor\",\"description\":null,\"is_system\":true,\"created_at\":\"2026-09-22T08:27:34.000000Z\",\"updated_at\":\"2026-09-22T08:27:34.000000Z\",\"permissions\":[]}', '{\"id\":10,\"name\":\"Instructor \\/ Trainer\",\"slug\":\"instructor\",\"description\":null,\"is_system\":true,\"created_at\":\"2026-09-22T08:27:34.000000Z\",\"updated_at\":\"2026-09-22T08:27:34.000000Z\",\"permissions\":[{\"id\":9,\"name\":\"Cohorts View\",\"slug\":\"cohorts.view\",\"module\":\"cohorts\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":10,\"permission_id\":9,\"created_at\":\"2026-09-25T13:06:47.000000Z\",\"updated_at\":\"2026-09-25T13:06:47.000000Z\"}},{\"id\":10,\"name\":\"Cohorts Manage\",\"slug\":\"cohorts.manage\",\"module\":\"cohorts\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":10,\"permission_id\":10,\"created_at\":\"2026-09-25T13:06:47.000000Z\",\"updated_at\":\"2026-09-25T13:06:47.000000Z\"}},{\"id\":11,\"name\":\"Courses View\",\"slug\":\"courses.view\",\"module\":\"courses\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":10,\"permission_id\":11,\"created_at\":\"2026-09-25T13:06:47.000000Z\",\"updated_at\":\"2026-09-25T13:06:47.000000Z\"}},{\"id\":20,\"name\":\"Jobs View\",\"slug\":\"jobs.view\",\"module\":\"jobs\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":10,\"permission_id\":20,\"created_at\":\"2026-09-25T13:06:47.000000Z\",\"updated_at\":\"2026-09-25T13:06:47.000000Z\"}},{\"id\":36,\"name\":\"Calendar Manage\",\"slug\":\"calendar.manage\",\"module\":\"calendar\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":10,\"permission_id\":36,\"created_at\":\"2026-09-25T13:06:47.000000Z\",\"updated_at\":\"2026-09-25T13:06:47.000000Z\"}},{\"id\":41,\"name\":\"Appraisals View\",\"slug\":\"appraisals.view\",\"module\":\"appraisals\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":10,\"permission_id\":41,\"created_at\":\"2026-09-25T13:06:47.000000Z\",\"updated_at\":\"2026-09-25T13:06:47.000000Z\"}}]}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 13:06:47'),
(17, 5, 'roles', 'permissions_updated', 'App\\Models\\Role', 10, '{\"id\":10,\"name\":\"Instructor \\/ Trainer\",\"slug\":\"instructor\",\"description\":null,\"is_system\":true,\"created_at\":\"2026-09-22T08:27:34.000000Z\",\"updated_at\":\"2026-09-22T08:27:34.000000Z\",\"permissions\":[{\"id\":9,\"name\":\"Cohorts View\",\"slug\":\"cohorts.view\",\"module\":\"cohorts\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":10,\"permission_id\":9,\"created_at\":\"2026-09-25T13:06:47.000000Z\",\"updated_at\":\"2026-09-25T13:06:47.000000Z\"}},{\"id\":10,\"name\":\"Cohorts Manage\",\"slug\":\"cohorts.manage\",\"module\":\"cohorts\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":10,\"permission_id\":10,\"created_at\":\"2026-09-25T13:06:47.000000Z\",\"updated_at\":\"2026-09-25T13:06:47.000000Z\"}},{\"id\":11,\"name\":\"Courses View\",\"slug\":\"courses.view\",\"module\":\"courses\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":10,\"permission_id\":11,\"created_at\":\"2026-09-25T13:06:47.000000Z\",\"updated_at\":\"2026-09-25T13:06:47.000000Z\"}},{\"id\":20,\"name\":\"Jobs View\",\"slug\":\"jobs.view\",\"module\":\"jobs\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":10,\"permission_id\":20,\"created_at\":\"2026-09-25T13:06:47.000000Z\",\"updated_at\":\"2026-09-25T13:06:47.000000Z\"}},{\"id\":36,\"name\":\"Calendar Manage\",\"slug\":\"calendar.manage\",\"module\":\"calendar\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":10,\"permission_id\":36,\"created_at\":\"2026-09-25T13:06:47.000000Z\",\"updated_at\":\"2026-09-25T13:06:47.000000Z\"}},{\"id\":41,\"name\":\"Appraisals View\",\"slug\":\"appraisals.view\",\"module\":\"appraisals\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":10,\"permission_id\":41,\"created_at\":\"2026-09-25T13:06:47.000000Z\",\"updated_at\":\"2026-09-25T13:06:47.000000Z\"}}]}', '{\"id\":10,\"name\":\"Instructor \\/ Trainer\",\"slug\":\"instructor\",\"description\":null,\"is_system\":true,\"created_at\":\"2026-09-22T08:27:34.000000Z\",\"updated_at\":\"2026-09-22T08:27:34.000000Z\",\"permissions\":[{\"id\":9,\"name\":\"Cohorts View\",\"slug\":\"cohorts.view\",\"module\":\"cohorts\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":10,\"permission_id\":9,\"created_at\":\"2026-09-25T13:06:47.000000Z\",\"updated_at\":\"2026-09-25T13:06:47.000000Z\"}},{\"id\":10,\"name\":\"Cohorts Manage\",\"slug\":\"cohorts.manage\",\"module\":\"cohorts\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":10,\"permission_id\":10,\"created_at\":\"2026-09-25T13:06:47.000000Z\",\"updated_at\":\"2026-09-25T13:06:47.000000Z\"}},{\"id\":11,\"name\":\"Courses View\",\"slug\":\"courses.view\",\"module\":\"courses\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":10,\"permission_id\":11,\"created_at\":\"2026-09-25T13:06:47.000000Z\",\"updated_at\":\"2026-09-25T13:06:47.000000Z\"}},{\"id\":15,\"name\":\"Students View\",\"slug\":\"students.view\",\"module\":\"students\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":10,\"permission_id\":15,\"created_at\":\"2026-09-25T13:07:06.000000Z\",\"updated_at\":\"2026-09-25T13:07:06.000000Z\"}},{\"id\":16,\"name\":\"Students Edit\",\"slug\":\"students.edit\",\"module\":\"students\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":10,\"permission_id\":16,\"created_at\":\"2026-09-25T13:07:06.000000Z\",\"updated_at\":\"2026-09-25T13:07:06.000000Z\"}},{\"id\":20,\"name\":\"Jobs View\",\"slug\":\"jobs.view\",\"module\":\"jobs\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":10,\"permission_id\":20,\"created_at\":\"2026-09-25T13:06:47.000000Z\",\"updated_at\":\"2026-09-25T13:06:47.000000Z\"}},{\"id\":36,\"name\":\"Calendar Manage\",\"slug\":\"calendar.manage\",\"module\":\"calendar\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":10,\"permission_id\":36,\"created_at\":\"2026-09-25T13:06:47.000000Z\",\"updated_at\":\"2026-09-25T13:06:47.000000Z\"}},{\"id\":41,\"name\":\"Appraisals View\",\"slug\":\"appraisals.view\",\"module\":\"appraisals\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":10,\"permission_id\":41,\"created_at\":\"2026-09-25T13:06:47.000000Z\",\"updated_at\":\"2026-09-25T13:06:47.000000Z\"}}]}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 13:07:06'),
(18, 5, 'roles', 'permissions_updated', 'App\\Models\\Role', 6, '{\"id\":6,\"name\":\"M&E \\/ MEAL Lead\",\"slug\":\"meal-lead\",\"description\":null,\"is_system\":true,\"created_at\":\"2026-09-22T08:27:34.000000Z\",\"updated_at\":\"2026-09-22T08:27:34.000000Z\",\"permissions\":[]}', '{\"id\":6,\"name\":\"M&E \\/ MEAL Lead\",\"slug\":\"meal-lead\",\"description\":null,\"is_system\":true,\"created_at\":\"2026-09-22T08:27:34.000000Z\",\"updated_at\":\"2026-09-22T08:27:34.000000Z\",\"permissions\":[{\"id\":15,\"name\":\"Students View\",\"slug\":\"students.view\",\"module\":\"students\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":6,\"permission_id\":15,\"created_at\":\"2026-09-25T13:07:56.000000Z\",\"updated_at\":\"2026-09-25T13:07:56.000000Z\"}},{\"id\":34,\"name\":\"Meal View\",\"slug\":\"meal.view\",\"module\":\"meal\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":6,\"permission_id\":34,\"created_at\":\"2026-09-25T13:07:56.000000Z\",\"updated_at\":\"2026-09-25T13:07:56.000000Z\"}},{\"id\":35,\"name\":\"Meal Manage\",\"slug\":\"meal.manage\",\"module\":\"meal\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":6,\"permission_id\":35,\"created_at\":\"2026-09-25T13:07:56.000000Z\",\"updated_at\":\"2026-09-25T13:07:56.000000Z\"}}]}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 13:07:56');
INSERT INTO `audit_logs` (`id`, `user_id`, `module`, `action`, `auditable_type`, `auditable_id`, `old_values`, `new_values`, `ip_address`, `user_agent`, `occurred_at`) VALUES
(19, 5, 'roles', 'permissions_updated', 'App\\Models\\Role', 8, '{\"id\":8,\"name\":\"Operations Lead\",\"slug\":\"operations-lead\",\"description\":null,\"is_system\":true,\"created_at\":\"2026-09-22T08:27:34.000000Z\",\"updated_at\":\"2026-09-22T08:27:34.000000Z\",\"permissions\":[]}', '{\"id\":8,\"name\":\"Operations Lead\",\"slug\":\"operations-lead\",\"description\":null,\"is_system\":true,\"created_at\":\"2026-09-22T08:27:34.000000Z\",\"updated_at\":\"2026-09-22T08:27:34.000000Z\",\"permissions\":[{\"id\":7,\"name\":\"Programmes View\",\"slug\":\"programmes.view\",\"module\":\"programmes\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":8,\"permission_id\":7,\"created_at\":\"2026-09-25T13:09:10.000000Z\",\"updated_at\":\"2026-09-25T13:09:10.000000Z\"}},{\"id\":8,\"name\":\"Programmes Manage\",\"slug\":\"programmes.manage\",\"module\":\"programmes\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":8,\"permission_id\":8,\"created_at\":\"2026-09-25T13:09:10.000000Z\",\"updated_at\":\"2026-09-25T13:09:10.000000Z\"}},{\"id\":9,\"name\":\"Cohorts View\",\"slug\":\"cohorts.view\",\"module\":\"cohorts\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":8,\"permission_id\":9,\"created_at\":\"2026-09-25T13:09:10.000000Z\",\"updated_at\":\"2026-09-25T13:09:10.000000Z\"}},{\"id\":10,\"name\":\"Cohorts Manage\",\"slug\":\"cohorts.manage\",\"module\":\"cohorts\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":8,\"permission_id\":10,\"created_at\":\"2026-09-25T13:09:10.000000Z\",\"updated_at\":\"2026-09-25T13:09:10.000000Z\"}},{\"id\":11,\"name\":\"Courses View\",\"slug\":\"courses.view\",\"module\":\"courses\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":8,\"permission_id\":11,\"created_at\":\"2026-09-25T13:09:10.000000Z\",\"updated_at\":\"2026-09-25T13:09:10.000000Z\"}},{\"id\":12,\"name\":\"Courses Create\",\"slug\":\"courses.create\",\"module\":\"courses\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":8,\"permission_id\":12,\"created_at\":\"2026-09-25T13:09:10.000000Z\",\"updated_at\":\"2026-09-25T13:09:10.000000Z\"}},{\"id\":13,\"name\":\"Courses Edit\",\"slug\":\"courses.edit\",\"module\":\"courses\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":8,\"permission_id\":13,\"created_at\":\"2026-09-25T13:09:10.000000Z\",\"updated_at\":\"2026-09-25T13:09:10.000000Z\"}},{\"id\":14,\"name\":\"Courses Delete\",\"slug\":\"courses.delete\",\"module\":\"courses\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":8,\"permission_id\":14,\"created_at\":\"2026-09-25T13:09:10.000000Z\",\"updated_at\":\"2026-09-25T13:09:10.000000Z\"}},{\"id\":15,\"name\":\"Students View\",\"slug\":\"students.view\",\"module\":\"students\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":8,\"permission_id\":15,\"created_at\":\"2026-09-25T13:09:10.000000Z\",\"updated_at\":\"2026-09-25T13:09:10.000000Z\"}},{\"id\":16,\"name\":\"Students Edit\",\"slug\":\"students.edit\",\"module\":\"students\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":8,\"permission_id\":16,\"created_at\":\"2026-09-25T13:09:10.000000Z\",\"updated_at\":\"2026-09-25T13:09:10.000000Z\"}},{\"id\":17,\"name\":\"Mentors View\",\"slug\":\"mentors.view\",\"module\":\"mentors\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":8,\"permission_id\":17,\"created_at\":\"2026-09-25T13:09:10.000000Z\",\"updated_at\":\"2026-09-25T13:09:10.000000Z\"}},{\"id\":18,\"name\":\"Mentors Manage\",\"slug\":\"mentors.manage\",\"module\":\"mentors\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":8,\"permission_id\":18,\"created_at\":\"2026-09-25T13:09:10.000000Z\",\"updated_at\":\"2026-09-25T13:09:10.000000Z\"}},{\"id\":19,\"name\":\"Mentorship Match\",\"slug\":\"mentorship.match\",\"module\":\"mentorship\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":8,\"permission_id\":19,\"created_at\":\"2026-09-25T13:09:10.000000Z\",\"updated_at\":\"2026-09-25T13:09:10.000000Z\"}},{\"id\":20,\"name\":\"Jobs View\",\"slug\":\"jobs.view\",\"module\":\"jobs\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":8,\"permission_id\":20,\"created_at\":\"2026-09-25T13:09:10.000000Z\",\"updated_at\":\"2026-09-25T13:09:10.000000Z\"}},{\"id\":21,\"name\":\"Jobs Manage\",\"slug\":\"jobs.manage\",\"module\":\"jobs\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":8,\"permission_id\":21,\"created_at\":\"2026-09-25T13:09:10.000000Z\",\"updated_at\":\"2026-09-25T13:09:10.000000Z\"}},{\"id\":22,\"name\":\"Employers Approve\",\"slug\":\"employers.approve\",\"module\":\"employers\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":8,\"permission_id\":22,\"created_at\":\"2026-09-25T13:09:10.000000Z\",\"updated_at\":\"2026-09-25T13:09:10.000000Z\"}},{\"id\":23,\"name\":\"Library Manage\",\"slug\":\"library.manage\",\"module\":\"library\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":8,\"permission_id\":23,\"created_at\":\"2026-09-25T13:09:10.000000Z\",\"updated_at\":\"2026-09-25T13:09:10.000000Z\"}},{\"id\":24,\"name\":\"Workplans View\",\"slug\":\"workplans.view\",\"module\":\"workplans\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":8,\"permission_id\":24,\"created_at\":\"2026-09-25T13:09:10.000000Z\",\"updated_at\":\"2026-09-25T13:09:10.000000Z\"}},{\"id\":25,\"name\":\"Workplans Create\",\"slug\":\"workplans.create\",\"module\":\"workplans\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":8,\"permission_id\":25,\"created_at\":\"2026-09-25T13:09:10.000000Z\",\"updated_at\":\"2026-09-25T13:09:10.000000Z\"}},{\"id\":26,\"name\":\"Workplans Edit\",\"slug\":\"workplans.edit\",\"module\":\"workplans\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":8,\"permission_id\":26,\"created_at\":\"2026-09-25T13:09:10.000000Z\",\"updated_at\":\"2026-09-25T13:09:10.000000Z\"}},{\"id\":27,\"name\":\"Workplans Approve\",\"slug\":\"workplans.approve\",\"module\":\"workplans\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":8,\"permission_id\":27,\"created_at\":\"2026-09-25T13:09:10.000000Z\",\"updated_at\":\"2026-09-25T13:09:10.000000Z\"}},{\"id\":28,\"name\":\"Milestones Manage\",\"slug\":\"milestones.manage\",\"module\":\"milestones\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":8,\"permission_id\":28,\"created_at\":\"2026-09-25T13:09:10.000000Z\",\"updated_at\":\"2026-09-25T13:09:10.000000Z\"}},{\"id\":29,\"name\":\"Activities Manage\",\"slug\":\"activities.manage\",\"module\":\"activities\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":8,\"permission_id\":29,\"created_at\":\"2026-09-25T13:09:10.000000Z\",\"updated_at\":\"2026-09-25T13:09:10.000000Z\"}},{\"id\":30,\"name\":\"Tasks Manage\",\"slug\":\"tasks.manage\",\"module\":\"tasks\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":8,\"permission_id\":30,\"created_at\":\"2026-09-25T13:09:10.000000Z\",\"updated_at\":\"2026-09-25T13:09:10.000000Z\"}},{\"id\":31,\"name\":\"Indicators View\",\"slug\":\"indicators.view\",\"module\":\"indicators\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":8,\"permission_id\":31,\"created_at\":\"2026-09-25T13:09:10.000000Z\",\"updated_at\":\"2026-09-25T13:09:10.000000Z\"}},{\"id\":32,\"name\":\"Indicators Manage\",\"slug\":\"indicators.manage\",\"module\":\"indicators\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":8,\"permission_id\":32,\"created_at\":\"2026-09-25T13:09:10.000000Z\",\"updated_at\":\"2026-09-25T13:09:10.000000Z\"}},{\"id\":33,\"name\":\"Indicators Verify\",\"slug\":\"indicators.verify\",\"module\":\"indicators\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":8,\"permission_id\":33,\"created_at\":\"2026-09-25T13:09:10.000000Z\",\"updated_at\":\"2026-09-25T13:09:10.000000Z\"}},{\"id\":34,\"name\":\"Meal View\",\"slug\":\"meal.view\",\"module\":\"meal\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":8,\"permission_id\":34,\"created_at\":\"2026-09-25T13:09:10.000000Z\",\"updated_at\":\"2026-09-25T13:09:10.000000Z\"}},{\"id\":35,\"name\":\"Meal Manage\",\"slug\":\"meal.manage\",\"module\":\"meal\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":8,\"permission_id\":35,\"created_at\":\"2026-09-25T13:09:10.000000Z\",\"updated_at\":\"2026-09-25T13:09:10.000000Z\"}},{\"id\":36,\"name\":\"Calendar Manage\",\"slug\":\"calendar.manage\",\"module\":\"calendar\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":8,\"permission_id\":36,\"created_at\":\"2026-09-25T13:09:10.000000Z\",\"updated_at\":\"2026-09-25T13:09:10.000000Z\"}},{\"id\":37,\"name\":\"Hr View\",\"slug\":\"hr.view\",\"module\":\"hr\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":8,\"permission_id\":37,\"created_at\":\"2026-09-25T13:09:10.000000Z\",\"updated_at\":\"2026-09-25T13:09:10.000000Z\"}},{\"id\":38,\"name\":\"Hr Manage\",\"slug\":\"hr.manage\",\"module\":\"hr\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":8,\"permission_id\":38,\"created_at\":\"2026-09-25T13:09:10.000000Z\",\"updated_at\":\"2026-09-25T13:09:10.000000Z\"}},{\"id\":39,\"name\":\"Leave View\",\"slug\":\"leave.view\",\"module\":\"leave\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":8,\"permission_id\":39,\"created_at\":\"2026-09-25T13:09:10.000000Z\",\"updated_at\":\"2026-09-25T13:09:10.000000Z\"}},{\"id\":40,\"name\":\"Leave Approve\",\"slug\":\"leave.approve\",\"module\":\"leave\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":8,\"permission_id\":40,\"created_at\":\"2026-09-25T13:09:10.000000Z\",\"updated_at\":\"2026-09-25T13:09:10.000000Z\"}},{\"id\":41,\"name\":\"Appraisals View\",\"slug\":\"appraisals.view\",\"module\":\"appraisals\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":8,\"permission_id\":41,\"created_at\":\"2026-09-25T13:09:10.000000Z\",\"updated_at\":\"2026-09-25T13:09:10.000000Z\"}},{\"id\":42,\"name\":\"Appraisals Manage\",\"slug\":\"appraisals.manage\",\"module\":\"appraisals\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":8,\"permission_id\":42,\"created_at\":\"2026-09-25T13:09:10.000000Z\",\"updated_at\":\"2026-09-25T13:09:10.000000Z\"}},{\"id\":43,\"name\":\"Staff Exit Manage\",\"slug\":\"staff_exit.manage\",\"module\":\"staff_exit\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":8,\"permission_id\":43,\"created_at\":\"2026-09-25T13:09:10.000000Z\",\"updated_at\":\"2026-09-25T13:09:10.000000Z\"}},{\"id\":44,\"name\":\"Assets View\",\"slug\":\"assets.view\",\"module\":\"assets\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":8,\"permission_id\":44,\"created_at\":\"2026-09-25T13:09:10.000000Z\",\"updated_at\":\"2026-09-25T13:09:10.000000Z\"}},{\"id\":45,\"name\":\"Assets Manage\",\"slug\":\"assets.manage\",\"module\":\"assets\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":8,\"permission_id\":45,\"created_at\":\"2026-09-25T13:09:10.000000Z\",\"updated_at\":\"2026-09-25T13:09:10.000000Z\"}},{\"id\":46,\"name\":\"Assets Dispose\",\"slug\":\"assets.dispose\",\"module\":\"assets\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":8,\"permission_id\":46,\"created_at\":\"2026-09-25T13:09:10.000000Z\",\"updated_at\":\"2026-09-25T13:09:10.000000Z\"}},{\"id\":47,\"name\":\"Procurement View\",\"slug\":\"procurement.view\",\"module\":\"procurement\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":8,\"permission_id\":47,\"created_at\":\"2026-09-25T13:09:10.000000Z\",\"updated_at\":\"2026-09-25T13:09:10.000000Z\"}},{\"id\":48,\"name\":\"Procurement Create\",\"slug\":\"procurement.create\",\"module\":\"procurement\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":8,\"permission_id\":48,\"created_at\":\"2026-09-25T13:09:10.000000Z\",\"updated_at\":\"2026-09-25T13:09:10.000000Z\"}},{\"id\":49,\"name\":\"Procurement Approve\",\"slug\":\"procurement.approve\",\"module\":\"procurement\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":8,\"permission_id\":49,\"created_at\":\"2026-09-25T13:09:10.000000Z\",\"updated_at\":\"2026-09-25T13:09:10.000000Z\"}},{\"id\":50,\"name\":\"Procurement Receive\",\"slug\":\"procurement.receive\",\"module\":\"procurement\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":8,\"permission_id\":50,\"created_at\":\"2026-09-25T13:09:10.000000Z\",\"updated_at\":\"2026-09-25T13:09:10.000000Z\"}},{\"id\":51,\"name\":\"Reports View\",\"slug\":\"reports.view\",\"module\":\"reports\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":8,\"permission_id\":51,\"created_at\":\"2026-09-25T13:09:10.000000Z\",\"updated_at\":\"2026-09-25T13:09:10.000000Z\"}},{\"id\":52,\"name\":\"Reports Export\",\"slug\":\"reports.export\",\"module\":\"reports\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":8,\"permission_id\":52,\"created_at\":\"2026-09-25T13:09:10.000000Z\",\"updated_at\":\"2026-09-25T13:09:10.000000Z\"}}]}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 13:09:10'),
(20, 5, 'roles', 'permissions_updated', 'App\\Models\\Role', 4, '{\"id\":4,\"name\":\"Program Manager\",\"slug\":\"program-manager\",\"description\":null,\"is_system\":true,\"created_at\":\"2026-09-22T08:27:34.000000Z\",\"updated_at\":\"2026-09-22T08:27:34.000000Z\",\"permissions\":[]}', '{\"id\":4,\"name\":\"Program Manager\",\"slug\":\"program-manager\",\"description\":null,\"is_system\":true,\"created_at\":\"2026-09-22T08:27:34.000000Z\",\"updated_at\":\"2026-09-22T08:27:34.000000Z\",\"permissions\":[{\"id\":6,\"name\":\"Permissions Manage\",\"slug\":\"permissions.manage\",\"module\":\"permissions\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":4,\"permission_id\":6,\"created_at\":\"2026-09-25T13:09:46.000000Z\",\"updated_at\":\"2026-09-25T13:09:46.000000Z\"}},{\"id\":7,\"name\":\"Programmes View\",\"slug\":\"programmes.view\",\"module\":\"programmes\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":4,\"permission_id\":7,\"created_at\":\"2026-09-25T13:09:46.000000Z\",\"updated_at\":\"2026-09-25T13:09:46.000000Z\"}},{\"id\":8,\"name\":\"Programmes Manage\",\"slug\":\"programmes.manage\",\"module\":\"programmes\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":4,\"permission_id\":8,\"created_at\":\"2026-09-25T13:09:46.000000Z\",\"updated_at\":\"2026-09-25T13:09:46.000000Z\"}},{\"id\":9,\"name\":\"Cohorts View\",\"slug\":\"cohorts.view\",\"module\":\"cohorts\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":4,\"permission_id\":9,\"created_at\":\"2026-09-25T13:09:46.000000Z\",\"updated_at\":\"2026-09-25T13:09:46.000000Z\"}},{\"id\":10,\"name\":\"Cohorts Manage\",\"slug\":\"cohorts.manage\",\"module\":\"cohorts\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":4,\"permission_id\":10,\"created_at\":\"2026-09-25T13:09:46.000000Z\",\"updated_at\":\"2026-09-25T13:09:46.000000Z\"}},{\"id\":11,\"name\":\"Courses View\",\"slug\":\"courses.view\",\"module\":\"courses\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":4,\"permission_id\":11,\"created_at\":\"2026-09-25T13:09:46.000000Z\",\"updated_at\":\"2026-09-25T13:09:46.000000Z\"}},{\"id\":12,\"name\":\"Courses Create\",\"slug\":\"courses.create\",\"module\":\"courses\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":4,\"permission_id\":12,\"created_at\":\"2026-09-25T13:09:46.000000Z\",\"updated_at\":\"2026-09-25T13:09:46.000000Z\"}},{\"id\":13,\"name\":\"Courses Edit\",\"slug\":\"courses.edit\",\"module\":\"courses\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":4,\"permission_id\":13,\"created_at\":\"2026-09-25T13:09:46.000000Z\",\"updated_at\":\"2026-09-25T13:09:46.000000Z\"}},{\"id\":14,\"name\":\"Courses Delete\",\"slug\":\"courses.delete\",\"module\":\"courses\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":4,\"permission_id\":14,\"created_at\":\"2026-09-25T13:09:46.000000Z\",\"updated_at\":\"2026-09-25T13:09:46.000000Z\"}},{\"id\":15,\"name\":\"Students View\",\"slug\":\"students.view\",\"module\":\"students\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":4,\"permission_id\":15,\"created_at\":\"2026-09-25T13:09:46.000000Z\",\"updated_at\":\"2026-09-25T13:09:46.000000Z\"}},{\"id\":16,\"name\":\"Students Edit\",\"slug\":\"students.edit\",\"module\":\"students\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":4,\"permission_id\":16,\"created_at\":\"2026-09-25T13:09:46.000000Z\",\"updated_at\":\"2026-09-25T13:09:46.000000Z\"}},{\"id\":17,\"name\":\"Mentors View\",\"slug\":\"mentors.view\",\"module\":\"mentors\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":4,\"permission_id\":17,\"created_at\":\"2026-09-25T13:09:46.000000Z\",\"updated_at\":\"2026-09-25T13:09:46.000000Z\"}},{\"id\":18,\"name\":\"Mentors Manage\",\"slug\":\"mentors.manage\",\"module\":\"mentors\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":4,\"permission_id\":18,\"created_at\":\"2026-09-25T13:09:46.000000Z\",\"updated_at\":\"2026-09-25T13:09:46.000000Z\"}},{\"id\":19,\"name\":\"Mentorship Match\",\"slug\":\"mentorship.match\",\"module\":\"mentorship\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":4,\"permission_id\":19,\"created_at\":\"2026-09-25T13:09:46.000000Z\",\"updated_at\":\"2026-09-25T13:09:46.000000Z\"}},{\"id\":20,\"name\":\"Jobs View\",\"slug\":\"jobs.view\",\"module\":\"jobs\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":4,\"permission_id\":20,\"created_at\":\"2026-09-25T13:09:46.000000Z\",\"updated_at\":\"2026-09-25T13:09:46.000000Z\"}},{\"id\":21,\"name\":\"Jobs Manage\",\"slug\":\"jobs.manage\",\"module\":\"jobs\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":4,\"permission_id\":21,\"created_at\":\"2026-09-25T13:09:46.000000Z\",\"updated_at\":\"2026-09-25T13:09:46.000000Z\"}},{\"id\":22,\"name\":\"Employers Approve\",\"slug\":\"employers.approve\",\"module\":\"employers\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":4,\"permission_id\":22,\"created_at\":\"2026-09-25T13:09:46.000000Z\",\"updated_at\":\"2026-09-25T13:09:46.000000Z\"}},{\"id\":23,\"name\":\"Library Manage\",\"slug\":\"library.manage\",\"module\":\"library\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":4,\"permission_id\":23,\"created_at\":\"2026-09-25T13:09:46.000000Z\",\"updated_at\":\"2026-09-25T13:09:46.000000Z\"}},{\"id\":24,\"name\":\"Workplans View\",\"slug\":\"workplans.view\",\"module\":\"workplans\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":4,\"permission_id\":24,\"created_at\":\"2026-09-25T13:09:46.000000Z\",\"updated_at\":\"2026-09-25T13:09:46.000000Z\"}},{\"id\":25,\"name\":\"Workplans Create\",\"slug\":\"workplans.create\",\"module\":\"workplans\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":4,\"permission_id\":25,\"created_at\":\"2026-09-25T13:09:46.000000Z\",\"updated_at\":\"2026-09-25T13:09:46.000000Z\"}},{\"id\":26,\"name\":\"Workplans Edit\",\"slug\":\"workplans.edit\",\"module\":\"workplans\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":4,\"permission_id\":26,\"created_at\":\"2026-09-25T13:09:46.000000Z\",\"updated_at\":\"2026-09-25T13:09:46.000000Z\"}},{\"id\":27,\"name\":\"Workplans Approve\",\"slug\":\"workplans.approve\",\"module\":\"workplans\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":4,\"permission_id\":27,\"created_at\":\"2026-09-25T13:09:46.000000Z\",\"updated_at\":\"2026-09-25T13:09:46.000000Z\"}},{\"id\":28,\"name\":\"Milestones Manage\",\"slug\":\"milestones.manage\",\"module\":\"milestones\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":4,\"permission_id\":28,\"created_at\":\"2026-09-25T13:09:46.000000Z\",\"updated_at\":\"2026-09-25T13:09:46.000000Z\"}},{\"id\":29,\"name\":\"Activities Manage\",\"slug\":\"activities.manage\",\"module\":\"activities\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":4,\"permission_id\":29,\"created_at\":\"2026-09-25T13:09:46.000000Z\",\"updated_at\":\"2026-09-25T13:09:46.000000Z\"}},{\"id\":30,\"name\":\"Tasks Manage\",\"slug\":\"tasks.manage\",\"module\":\"tasks\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":4,\"permission_id\":30,\"created_at\":\"2026-09-25T13:09:46.000000Z\",\"updated_at\":\"2026-09-25T13:09:46.000000Z\"}},{\"id\":31,\"name\":\"Indicators View\",\"slug\":\"indicators.view\",\"module\":\"indicators\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":4,\"permission_id\":31,\"created_at\":\"2026-09-25T13:09:46.000000Z\",\"updated_at\":\"2026-09-25T13:09:46.000000Z\"}},{\"id\":32,\"name\":\"Indicators Manage\",\"slug\":\"indicators.manage\",\"module\":\"indicators\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":4,\"permission_id\":32,\"created_at\":\"2026-09-25T13:09:46.000000Z\",\"updated_at\":\"2026-09-25T13:09:46.000000Z\"}},{\"id\":33,\"name\":\"Indicators Verify\",\"slug\":\"indicators.verify\",\"module\":\"indicators\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":4,\"permission_id\":33,\"created_at\":\"2026-09-25T13:09:46.000000Z\",\"updated_at\":\"2026-09-25T13:09:46.000000Z\"}},{\"id\":34,\"name\":\"Meal View\",\"slug\":\"meal.view\",\"module\":\"meal\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":4,\"permission_id\":34,\"created_at\":\"2026-09-25T13:09:46.000000Z\",\"updated_at\":\"2026-09-25T13:09:46.000000Z\"}},{\"id\":35,\"name\":\"Meal Manage\",\"slug\":\"meal.manage\",\"module\":\"meal\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":4,\"permission_id\":35,\"created_at\":\"2026-09-25T13:09:46.000000Z\",\"updated_at\":\"2026-09-25T13:09:46.000000Z\"}},{\"id\":36,\"name\":\"Calendar Manage\",\"slug\":\"calendar.manage\",\"module\":\"calendar\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":4,\"permission_id\":36,\"created_at\":\"2026-09-25T13:09:46.000000Z\",\"updated_at\":\"2026-09-25T13:09:46.000000Z\"}},{\"id\":37,\"name\":\"Hr View\",\"slug\":\"hr.view\",\"module\":\"hr\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":4,\"permission_id\":37,\"created_at\":\"2026-09-25T13:09:46.000000Z\",\"updated_at\":\"2026-09-25T13:09:46.000000Z\"}},{\"id\":38,\"name\":\"Hr Manage\",\"slug\":\"hr.manage\",\"module\":\"hr\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":4,\"permission_id\":38,\"created_at\":\"2026-09-25T13:09:46.000000Z\",\"updated_at\":\"2026-09-25T13:09:46.000000Z\"}},{\"id\":39,\"name\":\"Leave View\",\"slug\":\"leave.view\",\"module\":\"leave\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":4,\"permission_id\":39,\"created_at\":\"2026-09-25T13:09:46.000000Z\",\"updated_at\":\"2026-09-25T13:09:46.000000Z\"}},{\"id\":40,\"name\":\"Leave Approve\",\"slug\":\"leave.approve\",\"module\":\"leave\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":4,\"permission_id\":40,\"created_at\":\"2026-09-25T13:09:46.000000Z\",\"updated_at\":\"2026-09-25T13:09:46.000000Z\"}},{\"id\":41,\"name\":\"Appraisals View\",\"slug\":\"appraisals.view\",\"module\":\"appraisals\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":4,\"permission_id\":41,\"created_at\":\"2026-09-25T13:09:46.000000Z\",\"updated_at\":\"2026-09-25T13:09:46.000000Z\"}},{\"id\":42,\"name\":\"Appraisals Manage\",\"slug\":\"appraisals.manage\",\"module\":\"appraisals\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":4,\"permission_id\":42,\"created_at\":\"2026-09-25T13:09:46.000000Z\",\"updated_at\":\"2026-09-25T13:09:46.000000Z\"}},{\"id\":43,\"name\":\"Staff Exit Manage\",\"slug\":\"staff_exit.manage\",\"module\":\"staff_exit\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":4,\"permission_id\":43,\"created_at\":\"2026-09-25T13:09:46.000000Z\",\"updated_at\":\"2026-09-25T13:09:46.000000Z\"}},{\"id\":44,\"name\":\"Assets View\",\"slug\":\"assets.view\",\"module\":\"assets\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":4,\"permission_id\":44,\"created_at\":\"2026-09-25T13:09:46.000000Z\",\"updated_at\":\"2026-09-25T13:09:46.000000Z\"}},{\"id\":45,\"name\":\"Assets Manage\",\"slug\":\"assets.manage\",\"module\":\"assets\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":4,\"permission_id\":45,\"created_at\":\"2026-09-25T13:09:46.000000Z\",\"updated_at\":\"2026-09-25T13:09:46.000000Z\"}},{\"id\":46,\"name\":\"Assets Dispose\",\"slug\":\"assets.dispose\",\"module\":\"assets\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":4,\"permission_id\":46,\"created_at\":\"2026-09-25T13:09:46.000000Z\",\"updated_at\":\"2026-09-25T13:09:46.000000Z\"}},{\"id\":47,\"name\":\"Procurement View\",\"slug\":\"procurement.view\",\"module\":\"procurement\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":4,\"permission_id\":47,\"created_at\":\"2026-09-25T13:09:46.000000Z\",\"updated_at\":\"2026-09-25T13:09:46.000000Z\"}},{\"id\":48,\"name\":\"Procurement Create\",\"slug\":\"procurement.create\",\"module\":\"procurement\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":4,\"permission_id\":48,\"created_at\":\"2026-09-25T13:09:46.000000Z\",\"updated_at\":\"2026-09-25T13:09:46.000000Z\"}},{\"id\":49,\"name\":\"Procurement Approve\",\"slug\":\"procurement.approve\",\"module\":\"procurement\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":4,\"permission_id\":49,\"created_at\":\"2026-09-25T13:09:46.000000Z\",\"updated_at\":\"2026-09-25T13:09:46.000000Z\"}},{\"id\":50,\"name\":\"Procurement Receive\",\"slug\":\"procurement.receive\",\"module\":\"procurement\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":4,\"permission_id\":50,\"created_at\":\"2026-09-25T13:09:46.000000Z\",\"updated_at\":\"2026-09-25T13:09:46.000000Z\"}},{\"id\":51,\"name\":\"Reports View\",\"slug\":\"reports.view\",\"module\":\"reports\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":4,\"permission_id\":51,\"created_at\":\"2026-09-25T13:09:46.000000Z\",\"updated_at\":\"2026-09-25T13:09:46.000000Z\"}},{\"id\":52,\"name\":\"Reports Export\",\"slug\":\"reports.export\",\"module\":\"reports\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":4,\"permission_id\":52,\"created_at\":\"2026-09-25T13:09:46.000000Z\",\"updated_at\":\"2026-09-25T13:09:46.000000Z\"}}]}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 13:09:46'),
(21, 5, 'MEAL', 'created', 'App\\Models\\ResultsFramework', 1, '[]', '{\"programme_id\":\"1\",\"project_id\":null,\"title\":\"DE Program\",\"description\":\"Testing\",\"updated_at\":\"2026-09-25 16:27:53\",\"created_at\":\"2026-09-25 16:27:53\",\"id\":1}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 13:27:53'),
(22, 5, 'MEAL', 'created', 'App\\Models\\Result', 1, '[]', '{\"parent_id\":null,\"result_level\":\"impact\",\"title\":\"Impact\",\"description\":null,\"results_framework_id\":1,\"updated_at\":\"2026-09-25 16:28:20\",\"created_at\":\"2026-09-25 16:28:20\",\"id\":1}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 13:28:20');
INSERT INTO `audit_logs` (`id`, `user_id`, `module`, `action`, `auditable_type`, `auditable_id`, `old_values`, `new_values`, `ip_address`, `user_agent`, `occurred_at`) VALUES
(23, 5, 'roles', 'permissions_updated', 'App\\Models\\Role', 2, '{\"id\":2,\"name\":\"Administrator\",\"slug\":\"administrator\",\"description\":null,\"is_system\":true,\"created_at\":\"2026-09-22T08:27:34.000000Z\",\"updated_at\":\"2026-09-22T08:27:34.000000Z\",\"permissions\":[{\"id\":1,\"name\":\"Users View\",\"slug\":\"users.view\",\"module\":\"users\",\"description\":null,\"created_at\":\"2026-09-22T08:27:34.000000Z\",\"updated_at\":\"2026-09-22T08:27:34.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":1,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":2,\"name\":\"Users Create\",\"slug\":\"users.create\",\"module\":\"users\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":2,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":3,\"name\":\"Users Edit\",\"slug\":\"users.edit\",\"module\":\"users\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":3,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":4,\"name\":\"Users Delete\",\"slug\":\"users.delete\",\"module\":\"users\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":4,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":5,\"name\":\"Roles Manage\",\"slug\":\"roles.manage\",\"module\":\"roles\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":5,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":6,\"name\":\"Permissions Manage\",\"slug\":\"permissions.manage\",\"module\":\"permissions\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":6,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":7,\"name\":\"Programmes View\",\"slug\":\"programmes.view\",\"module\":\"programmes\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":7,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":8,\"name\":\"Programmes Manage\",\"slug\":\"programmes.manage\",\"module\":\"programmes\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":8,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":9,\"name\":\"Cohorts View\",\"slug\":\"cohorts.view\",\"module\":\"cohorts\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":9,\"created_at\":\"2026-09-25T13:03:50.000000Z\",\"updated_at\":\"2026-09-25T13:03:50.000000Z\"}},{\"id\":10,\"name\":\"Cohorts Manage\",\"slug\":\"cohorts.manage\",\"module\":\"cohorts\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":10,\"created_at\":\"2026-09-25T13:03:50.000000Z\",\"updated_at\":\"2026-09-25T13:03:50.000000Z\"}},{\"id\":11,\"name\":\"Courses View\",\"slug\":\"courses.view\",\"module\":\"courses\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":11,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":12,\"name\":\"Courses Create\",\"slug\":\"courses.create\",\"module\":\"courses\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":12,\"created_at\":\"2026-09-25T13:03:50.000000Z\",\"updated_at\":\"2026-09-25T13:03:50.000000Z\"}},{\"id\":13,\"name\":\"Courses Edit\",\"slug\":\"courses.edit\",\"module\":\"courses\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":13,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":14,\"name\":\"Courses Delete\",\"slug\":\"courses.delete\",\"module\":\"courses\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":14,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":15,\"name\":\"Students View\",\"slug\":\"students.view\",\"module\":\"students\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":15,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":16,\"name\":\"Students Edit\",\"slug\":\"students.edit\",\"module\":\"students\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":16,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":17,\"name\":\"Mentors View\",\"slug\":\"mentors.view\",\"module\":\"mentors\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":17,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":18,\"name\":\"Mentors Manage\",\"slug\":\"mentors.manage\",\"module\":\"mentors\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":18,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":19,\"name\":\"Mentorship Match\",\"slug\":\"mentorship.match\",\"module\":\"mentorship\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":19,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":20,\"name\":\"Jobs View\",\"slug\":\"jobs.view\",\"module\":\"jobs\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":20,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":21,\"name\":\"Jobs Manage\",\"slug\":\"jobs.manage\",\"module\":\"jobs\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":21,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":22,\"name\":\"Employers Approve\",\"slug\":\"employers.approve\",\"module\":\"employers\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":22,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":23,\"name\":\"Library Manage\",\"slug\":\"library.manage\",\"module\":\"library\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":23,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":24,\"name\":\"Workplans View\",\"slug\":\"workplans.view\",\"module\":\"workplans\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":24,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":25,\"name\":\"Workplans Create\",\"slug\":\"workplans.create\",\"module\":\"workplans\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":25,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":26,\"name\":\"Workplans Edit\",\"slug\":\"workplans.edit\",\"module\":\"workplans\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":26,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":27,\"name\":\"Workplans Approve\",\"slug\":\"workplans.approve\",\"module\":\"workplans\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":27,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":28,\"name\":\"Milestones Manage\",\"slug\":\"milestones.manage\",\"module\":\"milestones\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":28,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":29,\"name\":\"Activities Manage\",\"slug\":\"activities.manage\",\"module\":\"activities\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":29,\"created_at\":\"2026-09-25T13:03:50.000000Z\",\"updated_at\":\"2026-09-25T13:03:50.000000Z\"}},{\"id\":30,\"name\":\"Tasks Manage\",\"slug\":\"tasks.manage\",\"module\":\"tasks\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":30,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":31,\"name\":\"Indicators View\",\"slug\":\"indicators.view\",\"module\":\"indicators\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":31,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":32,\"name\":\"Indicators Manage\",\"slug\":\"indicators.manage\",\"module\":\"indicators\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":32,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":33,\"name\":\"Indicators Verify\",\"slug\":\"indicators.verify\",\"module\":\"indicators\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":33,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":34,\"name\":\"Meal View\",\"slug\":\"meal.view\",\"module\":\"meal\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":34,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":35,\"name\":\"Meal Manage\",\"slug\":\"meal.manage\",\"module\":\"meal\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":35,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":36,\"name\":\"Calendar Manage\",\"slug\":\"calendar.manage\",\"module\":\"calendar\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":36,\"created_at\":\"2026-09-25T13:03:50.000000Z\",\"updated_at\":\"2026-09-25T13:03:50.000000Z\"}},{\"id\":37,\"name\":\"Hr View\",\"slug\":\"hr.view\",\"module\":\"hr\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":37,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":38,\"name\":\"Hr Manage\",\"slug\":\"hr.manage\",\"module\":\"hr\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":38,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":39,\"name\":\"Leave View\",\"slug\":\"leave.view\",\"module\":\"leave\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":39,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":40,\"name\":\"Leave Approve\",\"slug\":\"leave.approve\",\"module\":\"leave\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":40,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":41,\"name\":\"Appraisals View\",\"slug\":\"appraisals.view\",\"module\":\"appraisals\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":41,\"created_at\":\"2026-09-25T13:03:50.000000Z\",\"updated_at\":\"2026-09-25T13:03:50.000000Z\"}},{\"id\":42,\"name\":\"Appraisals Manage\",\"slug\":\"appraisals.manage\",\"module\":\"appraisals\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":42,\"created_at\":\"2026-09-25T13:03:50.000000Z\",\"updated_at\":\"2026-09-25T13:03:50.000000Z\"}},{\"id\":43,\"name\":\"Staff Exit Manage\",\"slug\":\"staff_exit.manage\",\"module\":\"staff_exit\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":43,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":44,\"name\":\"Assets View\",\"slug\":\"assets.view\",\"module\":\"assets\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":44,\"created_at\":\"2026-09-25T13:03:50.000000Z\",\"updated_at\":\"2026-09-25T13:03:50.000000Z\"}},{\"id\":45,\"name\":\"Assets Manage\",\"slug\":\"assets.manage\",\"module\":\"assets\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":45,\"created_at\":\"2026-09-25T13:03:50.000000Z\",\"updated_at\":\"2026-09-25T13:03:50.000000Z\"}},{\"id\":46,\"name\":\"Assets Dispose\",\"slug\":\"assets.dispose\",\"module\":\"assets\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":46,\"created_at\":\"2026-09-25T13:03:50.000000Z\",\"updated_at\":\"2026-09-25T13:03:50.000000Z\"}},{\"id\":47,\"name\":\"Procurement View\",\"slug\":\"procurement.view\",\"module\":\"procurement\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":47,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":48,\"name\":\"Procurement Create\",\"slug\":\"procurement.create\",\"module\":\"procurement\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":48,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":49,\"name\":\"Procurement Approve\",\"slug\":\"procurement.approve\",\"module\":\"procurement\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":49,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":50,\"name\":\"Procurement Receive\",\"slug\":\"procurement.receive\",\"module\":\"procurement\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":50,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":51,\"name\":\"Reports View\",\"slug\":\"reports.view\",\"module\":\"reports\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":51,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":52,\"name\":\"Reports Export\",\"slug\":\"reports.export\",\"module\":\"reports\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":52,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":53,\"name\":\"Settings Manage\",\"slug\":\"settings.manage\",\"module\":\"settings\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":53,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}}]}', '{\"id\":2,\"name\":\"Administrator\",\"slug\":\"administrator\",\"description\":null,\"is_system\":true,\"created_at\":\"2026-09-22T08:27:34.000000Z\",\"updated_at\":\"2026-09-22T08:27:34.000000Z\",\"permissions\":[{\"id\":1,\"name\":\"Users View\",\"slug\":\"users.view\",\"module\":\"users\",\"description\":null,\"created_at\":\"2026-09-22T08:27:34.000000Z\",\"updated_at\":\"2026-09-22T08:27:34.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":1,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":2,\"name\":\"Users Create\",\"slug\":\"users.create\",\"module\":\"users\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":2,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":3,\"name\":\"Users Edit\",\"slug\":\"users.edit\",\"module\":\"users\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":3,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":4,\"name\":\"Users Delete\",\"slug\":\"users.delete\",\"module\":\"users\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":4,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":5,\"name\":\"Roles Manage\",\"slug\":\"roles.manage\",\"module\":\"roles\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":5,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":6,\"name\":\"Permissions Manage\",\"slug\":\"permissions.manage\",\"module\":\"permissions\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":6,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":7,\"name\":\"Programmes View\",\"slug\":\"programmes.view\",\"module\":\"programmes\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":7,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":8,\"name\":\"Programmes Manage\",\"slug\":\"programmes.manage\",\"module\":\"programmes\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":8,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":9,\"name\":\"Cohorts View\",\"slug\":\"cohorts.view\",\"module\":\"cohorts\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":9,\"created_at\":\"2026-09-25T13:03:50.000000Z\",\"updated_at\":\"2026-09-25T13:03:50.000000Z\"}},{\"id\":10,\"name\":\"Cohorts Manage\",\"slug\":\"cohorts.manage\",\"module\":\"cohorts\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":10,\"created_at\":\"2026-09-25T13:03:50.000000Z\",\"updated_at\":\"2026-09-25T13:03:50.000000Z\"}},{\"id\":11,\"name\":\"Courses View\",\"slug\":\"courses.view\",\"module\":\"courses\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":11,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":12,\"name\":\"Courses Create\",\"slug\":\"courses.create\",\"module\":\"courses\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":12,\"created_at\":\"2026-09-25T13:03:50.000000Z\",\"updated_at\":\"2026-09-25T13:03:50.000000Z\"}},{\"id\":13,\"name\":\"Courses Edit\",\"slug\":\"courses.edit\",\"module\":\"courses\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":13,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":14,\"name\":\"Courses Delete\",\"slug\":\"courses.delete\",\"module\":\"courses\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":14,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":15,\"name\":\"Students View\",\"slug\":\"students.view\",\"module\":\"students\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":15,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":16,\"name\":\"Students Edit\",\"slug\":\"students.edit\",\"module\":\"students\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":16,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":17,\"name\":\"Mentors View\",\"slug\":\"mentors.view\",\"module\":\"mentors\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":17,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":18,\"name\":\"Mentors Manage\",\"slug\":\"mentors.manage\",\"module\":\"mentors\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":18,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":19,\"name\":\"Mentorship Match\",\"slug\":\"mentorship.match\",\"module\":\"mentorship\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":19,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":20,\"name\":\"Jobs View\",\"slug\":\"jobs.view\",\"module\":\"jobs\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":20,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":21,\"name\":\"Jobs Manage\",\"slug\":\"jobs.manage\",\"module\":\"jobs\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":21,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":22,\"name\":\"Employers Approve\",\"slug\":\"employers.approve\",\"module\":\"employers\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":22,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":23,\"name\":\"Library Manage\",\"slug\":\"library.manage\",\"module\":\"library\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":23,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":24,\"name\":\"Workplans View\",\"slug\":\"workplans.view\",\"module\":\"workplans\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":24,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":25,\"name\":\"Workplans Create\",\"slug\":\"workplans.create\",\"module\":\"workplans\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":25,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":26,\"name\":\"Workplans Edit\",\"slug\":\"workplans.edit\",\"module\":\"workplans\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":26,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":27,\"name\":\"Workplans Approve\",\"slug\":\"workplans.approve\",\"module\":\"workplans\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":27,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":28,\"name\":\"Milestones Manage\",\"slug\":\"milestones.manage\",\"module\":\"milestones\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":28,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":29,\"name\":\"Activities Manage\",\"slug\":\"activities.manage\",\"module\":\"activities\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":29,\"created_at\":\"2026-09-25T13:03:50.000000Z\",\"updated_at\":\"2026-09-25T13:03:50.000000Z\"}},{\"id\":30,\"name\":\"Tasks Manage\",\"slug\":\"tasks.manage\",\"module\":\"tasks\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":30,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":31,\"name\":\"Indicators View\",\"slug\":\"indicators.view\",\"module\":\"indicators\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":31,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":32,\"name\":\"Indicators Manage\",\"slug\":\"indicators.manage\",\"module\":\"indicators\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":32,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":33,\"name\":\"Indicators Verify\",\"slug\":\"indicators.verify\",\"module\":\"indicators\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":33,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":34,\"name\":\"Meal View\",\"slug\":\"meal.view\",\"module\":\"meal\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":34,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":35,\"name\":\"Meal Manage\",\"slug\":\"meal.manage\",\"module\":\"meal\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":35,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":36,\"name\":\"Calendar Manage\",\"slug\":\"calendar.manage\",\"module\":\"calendar\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":36,\"created_at\":\"2026-09-25T13:03:50.000000Z\",\"updated_at\":\"2026-09-25T13:03:50.000000Z\"}},{\"id\":37,\"name\":\"Hr View\",\"slug\":\"hr.view\",\"module\":\"hr\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":37,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":38,\"name\":\"Hr Manage\",\"slug\":\"hr.manage\",\"module\":\"hr\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":38,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":39,\"name\":\"Leave View\",\"slug\":\"leave.view\",\"module\":\"leave\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":39,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":40,\"name\":\"Leave Approve\",\"slug\":\"leave.approve\",\"module\":\"leave\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":40,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":41,\"name\":\"Appraisals View\",\"slug\":\"appraisals.view\",\"module\":\"appraisals\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":41,\"created_at\":\"2026-09-25T13:03:50.000000Z\",\"updated_at\":\"2026-09-25T13:03:50.000000Z\"}},{\"id\":42,\"name\":\"Appraisals Manage\",\"slug\":\"appraisals.manage\",\"module\":\"appraisals\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":42,\"created_at\":\"2026-09-25T13:03:50.000000Z\",\"updated_at\":\"2026-09-25T13:03:50.000000Z\"}},{\"id\":43,\"name\":\"Staff Exit Manage\",\"slug\":\"staff_exit.manage\",\"module\":\"staff_exit\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":43,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":44,\"name\":\"Assets View\",\"slug\":\"assets.view\",\"module\":\"assets\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":44,\"created_at\":\"2026-09-25T13:03:50.000000Z\",\"updated_at\":\"2026-09-25T13:03:50.000000Z\"}},{\"id\":45,\"name\":\"Assets Manage\",\"slug\":\"assets.manage\",\"module\":\"assets\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":45,\"created_at\":\"2026-09-25T13:03:50.000000Z\",\"updated_at\":\"2026-09-25T13:03:50.000000Z\"}},{\"id\":46,\"name\":\"Assets Dispose\",\"slug\":\"assets.dispose\",\"module\":\"assets\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":46,\"created_at\":\"2026-09-25T13:03:50.000000Z\",\"updated_at\":\"2026-09-25T13:03:50.000000Z\"}},{\"id\":47,\"name\":\"Procurement View\",\"slug\":\"procurement.view\",\"module\":\"procurement\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":47,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":48,\"name\":\"Procurement Create\",\"slug\":\"procurement.create\",\"module\":\"procurement\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":48,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":49,\"name\":\"Procurement Approve\",\"slug\":\"procurement.approve\",\"module\":\"procurement\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":49,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":50,\"name\":\"Procurement Receive\",\"slug\":\"procurement.receive\",\"module\":\"procurement\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":50,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":51,\"name\":\"Reports View\",\"slug\":\"reports.view\",\"module\":\"reports\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":51,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":52,\"name\":\"Reports Export\",\"slug\":\"reports.export\",\"module\":\"reports\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":52,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}},{\"id\":53,\"name\":\"Settings Manage\",\"slug\":\"settings.manage\",\"module\":\"settings\",\"description\":null,\"created_at\":\"2026-09-22T08:27:35.000000Z\",\"updated_at\":\"2026-09-22T08:27:35.000000Z\",\"pivot\":{\"role_id\":2,\"permission_id\":53,\"created_at\":\"2026-09-25T13:03:51.000000Z\",\"updated_at\":\"2026-09-25T13:03:51.000000Z\"}}]}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-25 14:08:23');

-- --------------------------------------------------------

--
-- Table structure for table `branches`
--

CREATE TABLE `branches` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) DEFAULT NULL,
  `district` varchar(255) DEFAULT NULL,
  `country` varchar(255) NOT NULL DEFAULT 'Uganda',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `branches`
--

INSERT INTO `branches` (`id`, `name`, `code`, `district`, `country`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Kampala', 'KLA', 'Kampala', 'Uganda', 1, '2026-09-24 05:59:02', '2026-09-24 05:59:02'),
(2, 'Mbarara', 'MBR', 'Mbarara', 'Uganda', 1, '2026-09-25 06:38:31', '2026-09-25 06:38:31');

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cache`
--

INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
('laravel-cache-setting:branding.accent_color', 's:7:\"#d4af37\";', 1790414094),
('laravel-cache-setting:branding.favicon_path', 's:53:\"branding/5IkE1YNPxPtnP7AYGPVjsu8ZJtjUdgR2Nox8bEac.png\";', 1790414094),
('laravel-cache-setting:branding.font_family', 's:7:\"DM Sans\";', 1790414094),
('laravel-cache-setting:branding.font_size', 'i:16;', 1790414094),
('laravel-cache-setting:branding.primary_color', 's:7:\"#800000\";', 1790414094),
('laravel-cache-setting:branding.secondary_color', 's:7:\"#ffffff\";', 1790414094),
('laravel-cache-setting:maintenance.enabled', 'b:0;', 1790414030);

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `calendar_attendees`
--

CREATE TABLE `calendar_attendees` (
  `calendar_event_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `response_status` varchar(255) NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `calendar_events`
--

CREATE TABLE `calendar_events` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `eventable_type` varchar(255) DEFAULT NULL,
  `eventable_id` bigint(20) UNSIGNED DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `event_type` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `venue` varchar(255) DEFAULT NULL,
  `meeting_link` varchar(255) DEFAULT NULL,
  `starts_at` datetime NOT NULL,
  `ends_at` datetime DEFAULT NULL,
  `all_day` tinyint(1) NOT NULL DEFAULT 0,
  `responsible_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `programme_id` bigint(20) UNSIGNED DEFAULT NULL,
  `project_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cohort_id` bigint(20) UNSIGNED DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'scheduled',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `certificates`
--

CREATE TABLE `certificates` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `course_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `certificate_number` varchar(255) NOT NULL,
  `issued_on` date NOT NULL,
  `pdf_path` varchar(255) DEFAULT NULL,
  `verification_token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cohorts`
--

CREATE TABLE `cohorts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `programme_id` bigint(20) UNSIGNED DEFAULT NULL,
  `project_id` bigint(20) UNSIGNED DEFAULT NULL,
  `branch_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('planned','open','active','completed','cancelled') NOT NULL DEFAULT 'planned',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cohorts`
--

INSERT INTO `cohorts` (`id`, `programme_id`, `project_id`, `branch_id`, `name`, `code`, `start_date`, `end_date`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 1, 'Kampala Cohort 1', 'KLA-C1-2026', '2026-06-01', '2026-09-30', 'active', '2026-09-24 06:15:24', '2026-09-24 06:15:24');

-- --------------------------------------------------------

--
-- Table structure for table `cohort_module_releases`
--

CREATE TABLE `cohort_module_releases` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `course_module_id` bigint(20) UNSIGNED NOT NULL,
  `cohort_id` bigint(20) UNSIGNED NOT NULL,
  `is_released` tinyint(1) NOT NULL DEFAULT 0,
  `released_at` timestamp NULL DEFAULT NULL,
  `released_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `consents`
--

CREATE TABLE `consents` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `consent_type` varchar(255) NOT NULL,
  `policy_version` varchar(255) DEFAULT NULL,
  `accepted` tinyint(1) NOT NULL DEFAULT 0,
  `accepted_at` timestamp NULL DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `consents`
--

INSERT INTO `consents` (`id`, `user_id`, `consent_type`, `policy_version`, `accepted`, `accepted_at`, `ip_address`, `metadata`, `created_at`, `updated_at`) VALUES
(1, 4, 'privacy_policy', '1.0', 1, '2026-09-22 13:09:58', '127.0.0.1', NULL, '2026-09-22 13:09:58', '2026-09-22 13:09:58'),
(2, 4, 'terms', '1.0', 1, '2026-09-22 13:09:58', '127.0.0.1', NULL, '2026-09-22 13:09:58', '2026-09-22 13:09:58');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `programme_id` bigint(20) UNSIGNED DEFAULT NULL,
  `project_id` bigint(20) UNSIGNED DEFAULT NULL,
  `branch_id` bigint(20) UNSIGNED DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `code` varchar(255) DEFAULT NULL,
  `summary` text DEFAULT NULL,
  `description` longtext DEFAULT NULL,
  `thumbnail_path` varchar(255) DEFAULT NULL,
  `delivery_mode` enum('online','in_person','blended') NOT NULL DEFAULT 'blended',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `duration_hours` int(10) UNSIGNED DEFAULT NULL,
  `pass_mark` decimal(5,2) NOT NULL DEFAULT 50.00,
  `self_enrolment_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('draft','published','archived') NOT NULL DEFAULT 'draft',
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `programme_id`, `project_id`, `branch_id`, `title`, `code`, `summary`, `description`, `thumbnail_path`, `delivery_mode`, `start_date`, `end_date`, `duration_hours`, `pass_mark`, `self_enrolment_enabled`, `status`, `created_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, NULL, 1, 'Digital Marketing', NULL, NULL, NULL, NULL, 'blended', '2026-06-01', '2026-09-30', 24, 50.00, 0, 'published', 5, '2026-09-24 09:18:34', '2026-09-24 09:18:34', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `course_applications`
--

CREATE TABLE `course_applications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `course_call_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `assessment_attempt_id` bigint(20) UNSIGNED DEFAULT NULL,
  `status` enum('draft','submitted','shortlisted','approved','waitlisted','rejected') NOT NULL DEFAULT 'draft',
  `application_score` decimal(6,2) DEFAULT NULL,
  `entry_assessment_score` decimal(6,2) DEFAULT NULL,
  `reviewer_comments` text DEFAULT NULL,
  `reviewed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `enrolled_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course_application_answers`
--

CREATE TABLE `course_application_answers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `course_application_id` bigint(20) UNSIGNED NOT NULL,
  `course_call_question_id` bigint(20) UNSIGNED NOT NULL,
  `answer_text` longtext DEFAULT NULL,
  `answer_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`answer_json`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course_calls`
--

CREATE TABLE `course_calls` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `course_id` bigint(20) UNSIGNED DEFAULT NULL,
  `programme_id` bigint(20) UNSIGNED DEFAULT NULL,
  `project_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cohort_id` bigint(20) UNSIGNED DEFAULT NULL,
  `entry_assessment_id` bigint(20) UNSIGNED DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` longtext DEFAULT NULL,
  `eligibility_criteria` longtext DEFAULT NULL,
  `available_slots` int(10) UNSIGNED DEFAULT NULL,
  `opens_at` timestamp NULL DEFAULT NULL,
  `closes_at` timestamp NULL DEFAULT NULL,
  `status` enum('draft','published','closed','archived') NOT NULL DEFAULT 'draft',
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `course_calls`
--

INSERT INTO `course_calls` (`id`, `course_id`, `programme_id`, `project_id`, `cohort_id`, `entry_assessment_id`, `title`, `description`, `eligibility_criteria`, `available_slots`, `opens_at`, `closes_at`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 1, NULL, 1, NULL, 'Elevate Her 360', 'Description', 'Eligibility Criteria', 200, '2026-09-26 05:17:00', '2026-10-31 05:17:00', 'published', 5, '2026-09-26 05:18:07', '2026-09-26 05:47:03');

-- --------------------------------------------------------

--
-- Table structure for table `course_call_course`
--

CREATE TABLE `course_call_course` (
  `course_call_id` bigint(20) UNSIGNED NOT NULL,
  `course_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `course_call_course`
--

INSERT INTO `course_call_course` (`course_call_id`, `course_id`, `created_at`, `updated_at`) VALUES
(1, 1, '2026-09-26 05:44:53', '2026-09-26 05:44:53');

-- --------------------------------------------------------

--
-- Table structure for table `course_call_questions`
--

CREATE TABLE `course_call_questions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `course_call_id` bigint(20) UNSIGNED NOT NULL,
  `question_type` varchar(255) NOT NULL DEFAULT 'short_text',
  `question_text` text NOT NULL,
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`options`)),
  `is_required` tinyint(1) NOT NULL DEFAULT 0,
  `position` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `course_call_questions`
--

INSERT INTO `course_call_questions` (`id`, `course_call_id`, `question_type`, `question_text`, `options`, `is_required`, `position`, `created_at`, `updated_at`) VALUES
(1, 1, 'short_text', 'Question 1', NULL, 1, 1, '2026-09-26 05:19:11', '2026-09-26 05:19:11');

-- --------------------------------------------------------

--
-- Table structure for table `course_cohort`
--

CREATE TABLE `course_cohort` (
  `course_id` bigint(20) UNSIGNED NOT NULL,
  `cohort_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course_cohort_learning_settings`
--

CREATE TABLE `course_cohort_learning_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `course_id` bigint(20) UNSIGNED NOT NULL,
  `cohort_id` bigint(20) UNSIGNED NOT NULL,
  `sequential_modules` tinyint(1) NOT NULL DEFAULT 1,
  `instructor_release_required` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course_instructors`
--

CREATE TABLE `course_instructors` (
  `course_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `is_lead` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course_modules`
--

CREATE TABLE `course_modules` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `course_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `position` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `is_published` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `course_modules`
--

INSERT INTO `course_modules` (`id`, `course_id`, `title`, `description`, `position`, `is_published`, `created_at`, `updated_at`) VALUES
(1, 1, 'Module 1', 'Module 1', 1, 1, '2026-09-24 13:12:16', '2026-09-24 13:12:16');

-- --------------------------------------------------------

--
-- Table structure for table `cover_letters`
--

CREATE TABLE `cover_letters` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `resume_id` bigint(20) UNSIGNED DEFAULT NULL,
  `job_id` bigint(20) UNSIGNED DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `employer_name` varchar(255) DEFAULT NULL,
  `job_title` varchar(255) DEFAULT NULL,
  `recipient_name` varchar(255) DEFAULT NULL,
  `body` longtext DEFAULT NULL,
  `source` varchar(255) NOT NULL DEFAULT 'manual',
  `ai_generated` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cover_letter_uploads`
--

CREATE TABLE `cover_letter_uploads` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `cover_letter_id` bigint(20) UNSIGNED DEFAULT NULL,
  `original_name` varchar(255) NOT NULL,
  `stored_name` varchar(255) NOT NULL,
  `mime_type` varchar(120) NOT NULL,
  `file_size` bigint(20) UNSIGNED NOT NULL,
  `path` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'uploaded',
  `extracted_text` longtext DEFAULT NULL,
  `parsed_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`parsed_data`)),
  `parsing_error` text DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `deliverables`
--

CREATE TABLE `deliverables` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `activity_id` bigint(20) UNSIGNED DEFAULT NULL,
  `milestone_id` bigint(20) UNSIGNED DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `owner_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('not_started','in_progress','returned_for_revision','completed','overdue') NOT NULL DEFAULT 'not_started',
  `progress_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) DEFAULT NULL,
  `head_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `employee_number` varchar(255) NOT NULL,
  `department_id` bigint(20) UNSIGNED DEFAULT NULL,
  `position_id` bigint(20) UNSIGNED DEFAULT NULL,
  `supervisor_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `employment_type` varchar(255) DEFAULT NULL,
  `work_location` varchar(255) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `probation_end_date` date DEFAULT NULL,
  `status` enum('active','probation','on_leave','suspended','exiting','exited') NOT NULL DEFAULT 'active',
  `emergency_contact_name` varchar(255) DEFAULT NULL,
  `emergency_contact_phone` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `user_id`, `employee_number`, `department_id`, `position_id`, `supervisor_user_id`, `employment_type`, `work_location`, `start_date`, `probation_end_date`, `status`, `emergency_contact_name`, `emergency_contact_phone`, `created_at`, `updated_at`) VALUES
(1, 6, '0001', NULL, NULL, 5, 'Full Time', 'Kampala', '2026-07-01', NULL, 'active', NULL, NULL, '2026-09-25 06:30:40', '2026-09-25 06:30:40'),
(2, 5, '0002', NULL, NULL, NULL, NULL, 'Kampala', NULL, NULL, 'active', NULL, NULL, '2026-09-25 12:01:14', '2026-09-25 12:01:14');

-- --------------------------------------------------------

--
-- Table structure for table `employee_documents`
--

CREATE TABLE `employee_documents` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `document_type` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `path` varchar(255) NOT NULL,
  `uploaded_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employers`
--

CREATE TABLE `employers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `owner_user_id` bigint(20) UNSIGNED NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `company_type` varchar(255) DEFAULT NULL,
  `industry` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected','suspended') NOT NULL DEFAULT 'pending',
  `approved_at` timestamp NULL DEFAULT NULL,
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employment_contracts`
--

CREATE TABLE `employment_contracts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `contract_type` varchar(255) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `gross_salary` decimal(15,2) DEFAULT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'UGX',
  `document_path` varchar(255) DEFAULT NULL,
  `status` enum('draft','active','expired','terminated') NOT NULL DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employment_contracts`
--

INSERT INTO `employment_contracts` (`id`, `employee_id`, `contract_type`, `start_date`, `end_date`, `gross_salary`, `currency`, `document_path`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'IT', '2026-07-08', '2026-12-31', 3000000.00, 'UGX', NULL, 'active', '2026-09-25 06:31:34', '2026-09-25 06:31:34');

-- --------------------------------------------------------

--
-- Table structure for table `enrolments`
--

CREATE TABLE `enrolments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `course_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `cohort_id` bigint(20) UNSIGNED DEFAULT NULL,
  `source_type` varchar(255) DEFAULT NULL,
  `source_id` bigint(20) UNSIGNED DEFAULT NULL,
  `status` enum('enrolled','in_progress','completed','withdrawn','failed') NOT NULL DEFAULT 'enrolled',
  `enrolled_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `progress_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `final_score` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `event_type` varchar(255) NOT NULL DEFAULT 'training',
  `description` text DEFAULT NULL,
  `starts_at` datetime NOT NULL,
  `ends_at` datetime DEFAULT NULL,
  `venue` varchar(255) DEFAULT NULL,
  `district` varchar(255) DEFAULT NULL,
  `delivery_mode` varchar(255) NOT NULL DEFAULT 'physical',
  `meeting_url` varchar(255) DEFAULT NULL,
  `capacity` int(10) UNSIGNED DEFAULT NULL,
  `registration_required` tinyint(1) NOT NULL DEFAULT 1,
  `is_published` tinyint(1) NOT NULL DEFAULT 0,
  `cohort_id` bigint(20) UNSIGNED DEFAULT NULL,
  `course_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `checkin_token` char(36) DEFAULT NULL,
  `feedback_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `certificate_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `certificate_requires_feedback` tinyint(1) NOT NULL DEFAULT 0,
  `certificate_title` varchar(255) DEFAULT NULL,
  `certificate_signatory_name` varchar(255) DEFAULT NULL,
  `certificate_signatory_title` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `title`, `event_type`, `description`, `starts_at`, `ends_at`, `venue`, `district`, `delivery_mode`, `meeting_url`, `capacity`, `registration_required`, `is_published`, `cohort_id`, `course_id`, `created_by`, `created_at`, `updated_at`, `deleted_at`, `checkin_token`, `feedback_enabled`, `certificate_enabled`, `certificate_requires_feedback`, `certificate_title`, `certificate_signatory_name`, `certificate_signatory_title`) VALUES
(1, 'Orientation Cohort 1', 'training', 'Testing', '2026-09-25 14:30:00', '2026-09-25 16:30:00', 'WITU Hub', 'Kampala', 'physical', NULL, NULL, 1, 1, 1, NULL, 5, '2026-09-25 11:31:25', '2026-09-25 11:33:37', NULL, NULL, 1, 1, 1, 'Certificate of Attendance', 'Mrs. Joan Babirye', 'Programs Lead');

-- --------------------------------------------------------

--
-- Table structure for table `event_attendance_records`
--

CREATE TABLE `event_attendance_records` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `event_id` bigint(20) UNSIGNED NOT NULL,
  `event_registration_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `attendance_status` varchar(255) NOT NULL DEFAULT 'present',
  `check_in_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `recorded_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `event_certificates`
--

CREATE TABLE `event_certificates` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `event_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `event_attendance_record_id` bigint(20) UNSIGNED DEFAULT NULL,
  `certificate_code` char(36) NOT NULL,
  `issued_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `issued_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `event_feedback_responses`
--

CREATE TABLE `event_feedback_responses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `event_id` bigint(20) UNSIGNED NOT NULL,
  `event_registration_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `overall_rating` tinyint(3) UNSIGNED NOT NULL,
  `relevance_rating` tinyint(3) UNSIGNED NOT NULL,
  `facilitation_rating` tinyint(3) UNSIGNED NOT NULL,
  `organisation_rating` tinyint(3) UNSIGNED NOT NULL,
  `recommend_rating` tinyint(3) UNSIGNED NOT NULL,
  `key_learning` text DEFAULT NULL,
  `what_worked` text DEFAULT NULL,
  `what_to_improve` text DEFAULT NULL,
  `additional_comments` text DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `event_registrations`
--

CREATE TABLE `event_registrations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `event_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'registered',
  `registered_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `event_reminders`
--

CREATE TABLE `event_reminders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `event_id` bigint(20) UNSIGNED NOT NULL,
  `minutes_before` int(10) UNSIGNED NOT NULL,
  `delivery_method` varchar(255) NOT NULL DEFAULT 'both',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `event_reminder_deliveries`
--

CREATE TABLE `event_reminder_deliveries` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `event_reminder_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `channel` varchar(255) NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exit_clearances`
--

CREATE TABLE `exit_clearances` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `staff_exit_id` bigint(20) UNSIGNED NOT NULL,
  `clearance_area` varchar(255) NOT NULL,
  `responsible_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `status` enum('pending','cleared','blocked') NOT NULL DEFAULT 'pending',
  `remarks` text DEFAULT NULL,
  `cleared_at` timestamp NULL DEFAULT NULL,
  `cleared_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exit_interviews`
--

CREATE TABLE `exit_interviews` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `staff_exit_id` bigint(20) UNSIGNED NOT NULL,
  `reason_for_leaving` text DEFAULT NULL,
  `what_worked_well` text DEFAULT NULL,
  `challenges` text DEFAULT NULL,
  `recommendations` text DEFAULT NULL,
  `rehire_eligible` tinyint(1) DEFAULT NULL,
  `conducted_by` bigint(20) UNSIGNED DEFAULT NULL,
  `conducted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `goods_receipts`
--

CREATE TABLE `goods_receipts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `receipt_number` varchar(255) NOT NULL,
  `purchase_order_id` bigint(20) UNSIGNED NOT NULL,
  `received_date` date NOT NULL,
  `received_by` bigint(20) UNSIGNED DEFAULT NULL,
  `delivery_note_reference` text DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `status` enum('draft','confirmed','rejected') NOT NULL DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `goods_receipt_items`
--

CREATE TABLE `goods_receipt_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `goods_receipt_id` bigint(20) UNSIGNED NOT NULL,
  `purchase_order_item_id` bigint(20) UNSIGNED NOT NULL,
  `quantity_received` decimal(12,2) NOT NULL,
  `quantity_accepted` decimal(12,2) NOT NULL,
  `quantity_rejected` decimal(12,2) NOT NULL DEFAULT 0.00,
  `condition_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `handover_items`
--

CREATE TABLE `handover_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `staff_exit_id` bigint(20) UNSIGNED NOT NULL,
  `category` varchar(255) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `assigned_to` bigint(20) UNSIGNED DEFAULT NULL,
  `status` enum('pending','in_progress','completed') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hr_kpi_templates`
--

CREATE TABLE `hr_kpi_templates` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `source_file` varchar(255) DEFAULT NULL,
  `source_sheet` varchar(255) DEFAULT NULL,
  `template_type` varchar(255) NOT NULL DEFAULT 'performance_appraisal',
  `quarter` varchar(255) DEFAULT NULL,
  `year` smallint(5) UNSIGNED DEFAULT NULL,
  `uploaded_by` bigint(20) UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `hr_kpi_templates`
--

INSERT INTO `hr_kpi_templates` (`id`, `name`, `source_file`, `source_sheet`, `template_type`, `quarter`, `year`, `uploaded_by`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Performance Appraisal', 'performance Appraisal.xlsx', 'Performance Appraisal- Q1 (2)', 'performance_appraisal', NULL, 2026, 5, 1, '2026-09-24 15:23:40', '2026-09-24 15:23:40'),
(2, 'OKR-KPI Scorecard', 'OKR Scorecard.xlsx', 'OKR Scorecard Q1 2025.', 'okr_scorecard', NULL, 2026, 5, 1, '2026-09-24 15:30:11', '2026-09-24 15:30:11'),
(3, 'Behavioural Competence', 'Behavioral.xlsx', 'Behavioral Xteristics', 'behavioral', NULL, 2026, 5, 1, '2026-09-24 15:44:52', '2026-09-24 15:44:52');

-- --------------------------------------------------------

--
-- Table structure for table `hr_kpi_template_items`
--

CREATE TABLE `hr_kpi_template_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `hr_kpi_template_id` bigint(20) UNSIGNED NOT NULL,
  `item_type` varchar(255) NOT NULL,
  `section` varchar(255) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `weight` decimal(8,2) DEFAULT NULL,
  `position` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `hr_kpi_template_items`
--

INSERT INTO `hr_kpi_template_items` (`id`, `hr_kpi_template_id`, `item_type`, `section`, `title`, `weight`, `position`, `meta`, `created_at`, `updated_at`) VALUES
(1, 1, 'kra', 'Assessment against Key Result Areas', 'Assessment against Key Result Areas', NULL, 1, '{\"rating_scale\":[1,2,3,4,5]}', '2026-09-24 15:23:40', '2026-09-24 15:23:40'),
(2, 1, 'kra', 'KRA 1- Write it here', 'KRA 1- Write it here', NULL, 2, '{\"rating_scale\":[1,2,3,4,5]}', '2026-09-24 15:23:40', '2026-09-24 15:23:40'),
(3, 1, 'kra', 'Key Result 2:', 'Key Result 2:', NULL, 3, '{\"rating_scale\":[1,2,3,4,5]}', '2026-09-24 15:23:40', '2026-09-24 15:23:40'),
(4, 1, 'kra', 'Key Result Area 3:', 'Key Result Area 3:', NULL, 4, '{\"rating_scale\":[1,2,3,4,5]}', '2026-09-24 15:23:40', '2026-09-24 15:23:40'),
(5, 1, 'kra', 'Key Result 4:', 'Key Result 4:', NULL, 5, '{\"rating_scale\":[1,2,3,4,5]}', '2026-09-24 15:23:40', '2026-09-24 15:23:40'),
(6, 1, 'kpi', 'Key Result 4:', 'KPI 3', NULL, 6, '{\"rating_scale\":[1,2,3,4,5],\"employee_rating\":true,\"manager_rating\":true,\"agreed_rating\":true}', '2026-09-24 15:23:40', '2026-09-24 15:23:40'),
(7, 1, 'kra', 'Key Result Area 5:', 'Key Result Area 5:', NULL, 7, '{\"rating_scale\":[1,2,3,4,5]}', '2026-09-24 15:23:40', '2026-09-24 15:23:40'),
(8, 1, 'kpi', 'Key Result Area 5:', 'KPI 3', NULL, 8, '{\"rating_scale\":[1,2,3,4,5],\"employee_rating\":true,\"manager_rating\":true,\"agreed_rating\":true}', '2026-09-24 15:23:40', '2026-09-24 15:23:40'),
(9, 1, 'kra', 'Key Result Area 6:', 'Key Result Area 6:', NULL, 9, '{\"rating_scale\":[1,2,3,4,5]}', '2026-09-24 15:23:40', '2026-09-24 15:23:40'),
(10, 1, 'kpi', 'Key Result Area 6:', 'KPI 3', NULL, 10, '{\"rating_scale\":[1,2,3,4,5],\"employee_rating\":true,\"manager_rating\":true,\"agreed_rating\":true}', '2026-09-24 15:23:40', '2026-09-24 15:23:40'),
(11, 1, 'kra', 'Key Result Area 7:', 'Key Result Area 7:', NULL, 11, '{\"rating_scale\":[1,2,3,4,5]}', '2026-09-24 15:23:40', '2026-09-24 15:23:40'),
(12, 1, 'kpi', 'Key Result Area 7:', 'KPI 2', NULL, 12, '{\"rating_scale\":[1,2,3,4,5],\"employee_rating\":true,\"manager_rating\":true,\"agreed_rating\":true}', '2026-09-24 15:23:40', '2026-09-24 15:23:40'),
(13, 1, 'kpi', 'Key Result Area 7:', 'KPI 3', NULL, 13, '{\"rating_scale\":[1,2,3,4,5],\"employee_rating\":true,\"manager_rating\":true,\"agreed_rating\":true}', '2026-09-24 15:23:40', '2026-09-24 15:23:40'),
(14, 1, 'kra', 'Key Result Area 8:', 'Key Result Area 8:', NULL, 14, '{\"rating_scale\":[1,2,3,4,5]}', '2026-09-24 15:23:40', '2026-09-24 15:23:40'),
(15, 1, 'kpi', 'Key Result Area 8:', 'KPI 2', NULL, 15, '{\"rating_scale\":[1,2,3,4,5],\"employee_rating\":true,\"manager_rating\":true,\"agreed_rating\":true}', '2026-09-24 15:23:40', '2026-09-24 15:23:40'),
(16, 1, 'kpi', 'Key Result Area 8:', 'KPI 3', NULL, 16, '{\"rating_scale\":[1,2,3,4,5],\"employee_rating\":true,\"manager_rating\":true,\"agreed_rating\":true}', '2026-09-24 15:23:40', '2026-09-24 15:23:40'),
(17, 1, 'kra', 'Key Result Area 9:', 'Key Result Area 9:', NULL, 17, '{\"rating_scale\":[1,2,3,4,5]}', '2026-09-24 15:23:40', '2026-09-24 15:23:40'),
(18, 1, 'kpi', 'Key Result Area 9:', 'KPI 2', NULL, 18, '{\"rating_scale\":[1,2,3,4,5],\"employee_rating\":true,\"manager_rating\":true,\"agreed_rating\":true}', '2026-09-24 15:23:40', '2026-09-24 15:23:40'),
(19, 1, 'kpi', 'Key Result Area 9:', 'KPI 3', NULL, 19, '{\"rating_scale\":[1,2,3,4,5],\"employee_rating\":true,\"manager_rating\":true,\"agreed_rating\":true}', '2026-09-24 15:23:40', '2026-09-24 15:23:40'),
(20, 1, 'kra', 'Key Result Area 10:', 'Key Result Area 10:', NULL, 20, '{\"rating_scale\":[1,2,3,4,5]}', '2026-09-24 15:23:40', '2026-09-24 15:23:40'),
(21, 1, 'kra', 'Key Result Area 11', 'Key Result Area 11', NULL, 21, '{\"rating_scale\":[1,2,3,4,5]}', '2026-09-24 15:23:40', '2026-09-24 15:23:40'),
(22, 1, 'kpi', 'Key Result Area 11', 'KPI 1', NULL, 22, '{\"rating_scale\":[1,2,3,4,5],\"employee_rating\":true,\"manager_rating\":true,\"agreed_rating\":true}', '2026-09-24 15:23:40', '2026-09-24 15:23:40'),
(23, 1, 'kpi', 'Key Result Area 11', 'KPI 2', NULL, 23, '{\"rating_scale\":[1,2,3,4,5],\"employee_rating\":true,\"manager_rating\":true,\"agreed_rating\":true}', '2026-09-24 15:23:40', '2026-09-24 15:23:40'),
(24, 1, 'kpi', 'Key Result Area 11', 'KPI 3', NULL, 24, '{\"rating_scale\":[1,2,3,4,5],\"employee_rating\":true,\"manager_rating\":true,\"agreed_rating\":true}', '2026-09-24 15:23:40', '2026-09-24 15:23:40'),
(25, 1, 'kra', 'Key Result Area 12', 'Key Result Area 12', NULL, 25, '{\"rating_scale\":[1,2,3,4,5]}', '2026-09-24 15:23:40', '2026-09-24 15:23:40'),
(26, 1, 'kpi', 'Key Result Area 12', 'KPI 1', NULL, 26, '{\"rating_scale\":[1,2,3,4,5],\"employee_rating\":true,\"manager_rating\":true,\"agreed_rating\":true}', '2026-09-24 15:23:40', '2026-09-24 15:23:40'),
(27, 1, 'kpi', 'Key Result Area 12', 'KPI 2', NULL, 27, '{\"rating_scale\":[1,2,3,4,5],\"employee_rating\":true,\"manager_rating\":true,\"agreed_rating\":true}', '2026-09-24 15:23:40', '2026-09-24 15:23:40'),
(28, 1, 'kpi', 'Key Result Area 12', 'KPI 3', NULL, 28, '{\"rating_scale\":[1,2,3,4,5],\"employee_rating\":true,\"manager_rating\":true,\"agreed_rating\":true}', '2026-09-24 15:23:40', '2026-09-24 15:23:40'),
(29, 2, 'okr', 'Objective', 'Objective — Activities — Key Result', NULL, 1, '{\"objective\":\"Objective\",\"activity\":\"Activities\",\"key_result\":\"Key Result\",\"weekly_tracking\":true}', '2026-09-24 15:30:11', '2026-09-24 15:30:11'),
(30, 2, 'okr', 'Strategy Development, Innovation and Implementation', 'Strategy Development, Innovation and Implementation', 20.00, 2, '{\"objective\":\"Strategy Development, Innovation and Implementation\",\"activity\":null,\"key_result\":null,\"weekly_tracking\":true}', '2026-09-24 15:30:11', '2026-09-24 15:30:11'),
(31, 2, 'okr', 'Develop, direct and manage program  plan and schedule.', 'Develop, direct and manage program  plan and schedule.', 30.00, 3, '{\"objective\":\"Develop, direct and manage program  plan and schedule.\",\"activity\":null,\"key_result\":null,\"weekly_tracking\":true}', '2026-09-24 15:30:11', '2026-09-24 15:30:11'),
(32, 2, 'okr', 'Staff Development and Management', 'Staff Development and Management', 10.00, 4, '{\"objective\":\"Staff Development and Management\",\"activity\":null,\"key_result\":null,\"weekly_tracking\":true}', '2026-09-24 15:30:11', '2026-09-24 15:30:11'),
(33, 2, 'okr', 'Fundraising & Financial Managment', 'Fundraising & Financial Managment', 30.00, 5, '{\"objective\":\"Fundraising & Financial Managment\",\"activity\":null,\"key_result\":null,\"weekly_tracking\":true}', '2026-09-24 15:30:11', '2026-09-24 15:30:11'),
(34, 2, 'okr', 'Risk and Quality Assurance', 'Risk and Quality Assurance', 10.00, 6, '{\"objective\":\"Risk and Quality Assurance\",\"activity\":null,\"key_result\":null,\"weekly_tracking\":true}', '2026-09-24 15:30:11', '2026-09-24 15:30:11'),
(35, 2, 'okr', NULL, '\"What should we prioritize over all else\"', NULL, 7, '{\"objective\":null,\"activity\":\"\\\"What should we prioritize over all else\\\"\",\"key_result\":null,\"weekly_tracking\":true}', '2026-09-24 15:30:11', '2026-09-24 15:30:11'),
(36, 2, 'okr', NULL, '\"Where do I need to go\"', NULL, 8, '{\"objective\":null,\"activity\":\"\\\"Where do I need to go\\\"\",\"key_result\":null,\"weekly_tracking\":true}', '2026-09-24 15:30:11', '2026-09-24 15:30:11'),
(37, 2, 'okr', NULL, '\"How do I know I\'m getting there\"', NULL, 9, '{\"objective\":null,\"activity\":\"\\\"How do I know I\'m getting there\\\"\",\"key_result\":null,\"weekly_tracking\":true}', '2026-09-24 15:30:11', '2026-09-24 15:30:11'),
(38, 2, 'okr', NULL, '“What will I do to get there”', NULL, 10, '{\"objective\":null,\"activity\":\"\\u201cWhat will I do to get there\\u201d\",\"key_result\":null,\"weekly_tracking\":true}', '2026-09-24 15:30:11', '2026-09-24 15:30:11'),
(39, 3, 'behavioral', 'Understands the values of Hive and acts in accordance with them', 'Understands the values of Hive and acts in accordance with them', NULL, 1, '{\"group\":true,\"rating_scale\":[\"Always\",\"Occasionally\",\"Never\"]}', '2026-09-24 15:44:52', '2026-09-24 15:44:52'),
(40, 3, 'behavioral', 'Understands the values of Hive and acts in accordance with them', 'Hard work', NULL, 2, '{\"rating_scale\":[\"Always\",\"Occasionally\",\"Never\"]}', '2026-09-24 15:44:52', '2026-09-24 15:44:52'),
(41, 3, 'behavioral', 'Understands the values of Hive and acts in accordance with them', 'Confidentiality', NULL, 3, '{\"rating_scale\":[\"Always\",\"Occasionally\",\"Never\"]}', '2026-09-24 15:44:52', '2026-09-24 15:44:52'),
(42, 3, 'behavioral', 'Understands the values of Hive and acts in accordance with them', 'Teamwork', NULL, 4, '{\"rating_scale\":[\"Always\",\"Occasionally\",\"Never\"]}', '2026-09-24 15:44:52', '2026-09-24 15:44:52'),
(43, 3, 'behavioral', 'Understands the values of Hive and acts in accordance with them', 'Quality', NULL, 5, '{\"rating_scale\":[\"Always\",\"Occasionally\",\"Never\"]}', '2026-09-24 15:44:52', '2026-09-24 15:44:52'),
(44, 3, 'behavioral', 'Understands the values of Hive and acts in accordance with them', 'Steadfastness', NULL, 6, '{\"rating_scale\":[\"Always\",\"Occasionally\",\"Never\"]}', '2026-09-24 15:44:52', '2026-09-24 15:44:52'),
(45, 3, 'behavioral', 'Understands the values of Hive and acts in accordance with them', 'Creativity', NULL, 7, '{\"rating_scale\":[\"Always\",\"Occasionally\",\"Never\"]}', '2026-09-24 15:44:53', '2026-09-24 15:44:53'),
(46, 3, 'behavioral', 'Understands the values of Hive and acts in accordance with them', 'Strategic Direction', NULL, 8, '{\"rating_scale\":[\"Always\",\"Occasionally\",\"Never\"]}', '2026-09-24 15:44:53', '2026-09-24 15:44:53'),
(47, 3, 'behavioral', 'Acting as a team player', 'Acting as a team player', NULL, 9, '{\"group\":true,\"rating_scale\":[\"Always\",\"Occasionally\",\"Never\"]}', '2026-09-24 15:44:53', '2026-09-24 15:44:53'),
(48, 3, 'behavioral', 'Acting as a team player', 'Shares Ideas and best practices', NULL, 10, '{\"rating_scale\":[\"Always\",\"Occasionally\",\"Never\"]}', '2026-09-24 15:44:53', '2026-09-24 15:44:53'),
(49, 3, 'behavioral', 'Acting as a team player', 'Listens to people and understands what they are saying', NULL, 11, '{\"rating_scale\":[\"Always\",\"Occasionally\",\"Never\"]}', '2026-09-24 15:44:53', '2026-09-24 15:44:53'),
(50, 3, 'behavioral', 'Acting as a team player', 'Values other people\'s input and takes constructive criticism', NULL, 12, '{\"rating_scale\":[\"Always\",\"Occasionally\",\"Never\"]}', '2026-09-24 15:44:53', '2026-09-24 15:44:53'),
(51, 3, 'behavioral', 'Acting as a team player', 'Positively encourages  others and builds team spirit', NULL, 13, '{\"rating_scale\":[\"Always\",\"Occasionally\",\"Never\"]}', '2026-09-24 15:44:53', '2026-09-24 15:44:53'),
(52, 3, 'behavioral', 'Acting as a team player', 'Achieves best results when he/she works on her own', NULL, 14, '{\"rating_scale\":[\"Always\",\"Occasionally\",\"Never\"]}', '2026-09-24 15:44:53', '2026-09-24 15:44:53'),
(53, 3, 'behavioral', 'Acting as a team player', 'Does not in any way build team spirit', NULL, 15, '{\"rating_scale\":[\"Always\",\"Occasionally\",\"Never\"]}', '2026-09-24 15:44:53', '2026-09-24 15:44:53'),
(54, 3, 'behavioral', 'Conflict management, resolution and integrity', 'Conflict management, resolution and integrity', NULL, 16, '{\"group\":true,\"rating_scale\":[\"Always\",\"Occasionally\",\"Never\"]}', '2026-09-24 15:44:53', '2026-09-24 15:44:53'),
(55, 3, 'behavioral', 'Conflict management, resolution and integrity', 'Remains objective in the face of conflict', NULL, 17, '{\"rating_scale\":[\"Always\",\"Occasionally\",\"Never\"]}', '2026-09-24 15:44:53', '2026-09-24 15:44:53'),
(56, 3, 'behavioral', 'Conflict management, resolution and integrity', 'Works to achieve win-win situations in the face of conflict', NULL, 18, '{\"rating_scale\":[\"Always\",\"Occasionally\",\"Never\"]}', '2026-09-24 15:44:53', '2026-09-24 15:44:53'),
(57, 3, 'behavioral', 'Conflict management, resolution and integrity', 'Able to differentiate confrontation from  honesty', NULL, 19, '{\"rating_scale\":[\"Always\",\"Occasionally\",\"Never\"]}', '2026-09-24 15:44:53', '2026-09-24 15:44:53'),
(58, 3, 'behavioral', 'Conflict management, resolution and integrity', 'Confrontational in the face of conflict', NULL, 20, '{\"rating_scale\":[\"Always\",\"Occasionally\",\"Never\"]}', '2026-09-24 15:44:53', '2026-09-24 15:44:53'),
(59, 3, 'behavioral', 'Conflict management, resolution and integrity', 'Accepts responsibility for mistakes', NULL, 21, '{\"rating_scale\":[\"Always\",\"Occasionally\",\"Never\"]}', '2026-09-24 15:44:53', '2026-09-24 15:44:53'),
(60, 3, 'behavioral', 'Conflict management, resolution and integrity', 'Open and honest about work situations', NULL, 22, '{\"rating_scale\":[\"Always\",\"Occasionally\",\"Never\"]}', '2026-09-24 15:44:53', '2026-09-24 15:44:53'),
(61, 3, 'behavioral', 'Relationship Building', 'Relationship Building', NULL, 23, '{\"group\":true,\"rating_scale\":[\"Always\",\"Occasionally\",\"Never\"]}', '2026-09-24 15:44:53', '2026-09-24 15:44:53'),
(62, 3, 'behavioral', 'Relationship Building', 'Respects other WITU staff', NULL, 24, '{\"rating_scale\":[\"Always\",\"Occasionally\",\"Never\"]}', '2026-09-24 15:44:53', '2026-09-24 15:44:53'),
(63, 3, 'behavioral', 'Relationship Building', 'Respects WITU\'s clients', NULL, 25, '{\"rating_scale\":[\"Always\",\"Occasionally\",\"Never\"]}', '2026-09-24 15:44:53', '2026-09-24 15:44:53'),
(64, 3, 'behavioral', 'Relationship Building', 'Is at least non judgemental of the views of WITU\'s clients', NULL, 26, '{\"rating_scale\":[\"Always\",\"Occasionally\",\"Never\"]}', '2026-09-24 15:44:53', '2026-09-24 15:44:53'),
(65, 3, 'behavioral', 'Relationship Building', 'Offers constructive criticism when necessary', NULL, 27, '{\"rating_scale\":[\"Always\",\"Occasionally\",\"Never\"]}', '2026-09-24 15:44:53', '2026-09-24 15:44:53'),
(66, 3, 'behavioral', 'Being Creative and uses own initiative', 'Being Creative and uses own initiative', NULL, 28, '{\"group\":true,\"rating_scale\":[\"Always\",\"Occasionally\",\"Never\"]}', '2026-09-24 15:44:53', '2026-09-24 15:44:53'),
(67, 3, 'behavioral', 'Being Creative and uses own initiative', 'Leads/ shows by example', NULL, 29, '{\"rating_scale\":[\"Always\",\"Occasionally\",\"Never\"]}', '2026-09-24 15:44:53', '2026-09-24 15:44:53'),
(68, 3, 'behavioral', 'Being Creative and uses own initiative', 'Constantly strives for better ways of doing things', NULL, 30, '{\"rating_scale\":[\"Always\",\"Occasionally\",\"Never\"]}', '2026-09-24 15:44:53', '2026-09-24 15:44:53'),
(69, 3, 'behavioral', 'Being Creative and uses own initiative', 'Freely shares ideas for new ways of doing things', NULL, 31, '{\"rating_scale\":[\"Always\",\"Occasionally\",\"Never\"]}', '2026-09-24 15:44:53', '2026-09-24 15:44:53');

-- --------------------------------------------------------

--
-- Table structure for table `indicators`
--

CREATE TABLE `indicators` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `programme_id` bigint(20) UNSIGNED DEFAULT NULL,
  `project_id` bigint(20) UNSIGNED DEFAULT NULL,
  `result_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) DEFAULT NULL,
  `definition` text DEFAULT NULL,
  `result_level` enum('impact','outcome','output','activity') NOT NULL DEFAULT 'output',
  `indicator_type` enum('number','percentage','rate','ratio','currency','binary','text') NOT NULL DEFAULT 'number',
  `unit_of_measure` varchar(255) DEFAULT NULL,
  `baseline_numeric` decimal(18,4) DEFAULT NULL,
  `baseline_text` text DEFAULT NULL,
  `frequency` varchar(255) DEFAULT NULL,
  `data_source` text DEFAULT NULL,
  `means_of_verification` text DEFAULT NULL,
  `responsible_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `disaggregation` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`disaggregation`)),
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('draft','active','inactive','closed') NOT NULL DEFAULT 'draft',
  `calculation_key` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `indicator_results`
--

CREATE TABLE `indicator_results` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `indicator_id` bigint(20) UNSIGNED NOT NULL,
  `indicator_target_id` bigint(20) UNSIGNED DEFAULT NULL,
  `reporting_period` varchar(255) DEFAULT NULL,
  `actual_numeric` decimal(18,4) DEFAULT NULL,
  `actual_text` text DEFAULT NULL,
  `data_source` text DEFAULT NULL,
  `evidence_note` text DEFAULT NULL,
  `verification_status` enum('draft','submitted','verified','rejected','needs_correction') NOT NULL DEFAULT 'draft',
  `entered_by` bigint(20) UNSIGNED DEFAULT NULL,
  `verified_by` bigint(20) UNSIGNED DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `indicator_targets`
--

CREATE TABLE `indicator_targets` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `indicator_id` bigint(20) UNSIGNED NOT NULL,
  `period_type` varchar(255) NOT NULL,
  `period_label` varchar(255) NOT NULL,
  `period_start` date DEFAULT NULL,
  `period_end` date DEFAULT NULL,
  `programme_id` bigint(20) UNSIGNED DEFAULT NULL,
  `project_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cohort_id` bigint(20) UNSIGNED DEFAULT NULL,
  `branch_id` bigint(20) UNSIGNED DEFAULT NULL,
  `target_numeric` decimal(18,4) DEFAULT NULL,
  `target_text` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `issues`
--

CREATE TABLE `issues` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `issueable_type` varchar(255) DEFAULT NULL,
  `issueable_id` bigint(20) UNSIGNED DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `severity` varchar(255) NOT NULL DEFAULT 'medium',
  `owner_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'open',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employer_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `category` varchar(255) DEFAULT NULL,
  `industry` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `employment_type` enum('full_time','part_time','contract','internship','temporary','volunteer') DEFAULT NULL,
  `work_arrangement` enum('onsite','remote','hybrid') DEFAULT NULL,
  `experience_level` varchar(255) DEFAULT NULL,
  `education_level` varchar(255) DEFAULT NULL,
  `salary_min` decimal(15,2) DEFAULT NULL,
  `salary_max` decimal(15,2) DEFAULT NULL,
  `salary_currency` varchar(3) NOT NULL DEFAULT 'UGX',
  `description` longtext DEFAULT NULL,
  `responsibilities` longtext DEFAULT NULL,
  `requirements` longtext DEFAULT NULL,
  `skills` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`skills`)),
  `application_deadline` date DEFAULT NULL,
  `positions` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `status` enum('draft','pending_approval','published','closed','expired','rejected') NOT NULL DEFAULT 'draft',
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_applications`
--

CREATE TABLE `job_applications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `job_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `resume_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cover_letter` text DEFAULT NULL,
  `status` enum('submitted','under_review','shortlisted','interview','offer','hired','rejected','withdrawn') NOT NULL DEFAULT 'submitted',
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_application_status_history`
--

CREATE TABLE `job_application_status_history` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `job_application_id` bigint(20) UNSIGNED NOT NULL,
  `status` varchar(255) NOT NULL,
  `notes` text DEFAULT NULL,
  `changed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_interviews`
--

CREATE TABLE `job_interviews` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `job_application_id` bigint(20) UNSIGNED NOT NULL,
  `scheduled_at` datetime NOT NULL,
  `venue` varchar(255) DEFAULT NULL,
  `meeting_link` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('scheduled','completed','cancelled','no_show') NOT NULL DEFAULT 'scheduled',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_offers`
--

CREATE TABLE `job_offers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `job_application_id` bigint(20) UNSIGNED NOT NULL,
  `salary_amount` decimal(15,2) DEFAULT NULL,
  `salary_currency` varchar(3) NOT NULL DEFAULT 'UGX',
  `start_date` date DEFAULT NULL,
  `offer_notes` text DEFAULT NULL,
  `status` enum('draft','sent','accepted','declined','withdrawn') NOT NULL DEFAULT 'draft',
  `responded_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_recommendations`
--

CREATE TABLE `job_recommendations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `job_id` bigint(20) UNSIGNED NOT NULL,
  `match_score` decimal(5,2) NOT NULL DEFAULT 0.00,
  `matching_factors` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`matching_factors`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `learning_files`
--

CREATE TABLE `learning_files` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `course_id` bigint(20) UNSIGNED DEFAULT NULL,
  `lesson_id` bigint(20) UNSIGNED DEFAULT NULL,
  `original_name` varchar(255) NOT NULL,
  `stored_name` varchar(255) NOT NULL,
  `disk` varchar(255) NOT NULL DEFAULT 'local',
  `path` varchar(255) NOT NULL,
  `mime_type` varchar(255) DEFAULT NULL,
  `size_bytes` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `uploaded_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_balances`
--

CREATE TABLE `leave_balances` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `leave_type_id` bigint(20) UNSIGNED NOT NULL,
  `year` varchar(255) NOT NULL,
  `opening_balance` decimal(7,2) NOT NULL DEFAULT 0.00,
  `accrued` decimal(7,2) NOT NULL DEFAULT 0.00,
  `used` decimal(7,2) NOT NULL DEFAULT 0.00,
  `adjustments` decimal(7,2) NOT NULL DEFAULT 0.00,
  `remaining` decimal(7,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_requests`
--

CREATE TABLE `leave_requests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `leave_type_id` bigint(20) UNSIGNED NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `days_requested` decimal(7,2) NOT NULL,
  `reason` text DEFAULT NULL,
  `handover_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `status` enum('draft','submitted','supervisor_approved','hr_approved','rejected','cancelled') NOT NULL DEFAULT 'submitted',
  `supervisor_approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `supervisor_approved_at` timestamp NULL DEFAULT NULL,
  `hr_approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `hr_approved_at` timestamp NULL DEFAULT NULL,
  `decision_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_types`
--

CREATE TABLE `leave_types` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `default_days` decimal(6,2) DEFAULT NULL,
  `requires_attachment` tinyint(1) NOT NULL DEFAULT 0,
  `is_paid` tinyint(1) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lessons`
--

CREATE TABLE `lessons` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `course_module_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` longtext DEFAULT NULL,
  `content_type` enum('text','video','file','link','mixed') NOT NULL DEFAULT 'text',
  `video_url` varchar(255) DEFAULT NULL,
  `external_url` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `estimated_minutes` int(10) UNSIGNED DEFAULT NULL,
  `position` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `is_published` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lessons`
--

INSERT INTO `lessons` (`id`, `course_module_id`, `title`, `content`, `content_type`, `video_url`, `external_url`, `file_path`, `estimated_minutes`, `position`, `is_published`, `created_at`, `updated_at`) VALUES
(1, 1, 'Lesson 1', 'lesson', 'file', NULL, NULL, NULL, 30, 1, 1, '2026-09-24 13:13:20', '2026-09-24 13:13:20'),
(2, 1, 'Lesson2', 'Lesson 2', 'text', NULL, NULL, NULL, 30, 2, 1, '2026-09-24 13:13:56', '2026-09-24 13:13:56');

-- --------------------------------------------------------

--
-- Table structure for table `lesson_progress`
--

CREATE TABLE `lesson_progress` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `lesson_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `first_opened_at` timestamp NULL DEFAULT NULL,
  `last_opened_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `time_spent_seconds` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `library_bookmarks`
--

CREATE TABLE `library_bookmarks` (
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `library_resource_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `library_categories`
--

CREATE TABLE `library_categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `library_downloads`
--

CREATE TABLE `library_downloads` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `library_resource_id` bigint(20) UNSIGNED NOT NULL,
  `downloaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `library_resources`
--

CREATE TABLE `library_resources` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `library_category_id` bigint(20) UNSIGNED DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `author` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `cover_image_path` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `external_url` varchar(255) DEFAULT NULL,
  `language` varchar(255) NOT NULL DEFAULT 'English',
  `publication_date` date DEFAULT NULL,
  `access_level` enum('public','authenticated','staff') NOT NULL DEFAULT 'authenticated',
  `views_count` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `downloads_count` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_audits`
--

CREATE TABLE `login_audits` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `successful` tinyint(1) NOT NULL DEFAULT 0,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `failure_reason` varchar(255) DEFAULT NULL,
  `attempted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mentee_profiles`
--

CREATE TABLE `mentee_profiles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `programme_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cohort_id` bigint(20) UNSIGNED DEFAULT NULL,
  `career_goals` text DEFAULT NULL,
  `skills` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`skills`)),
  `support_needs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`support_needs`)),
  `preferred_mentor_areas` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`preferred_mentor_areas`)),
  `availability` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`availability`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mentorship_goals`
--

CREATE TABLE `mentorship_goals` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `mentor_match_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `target_date` date DEFAULT NULL,
  `progress_percent` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `status` enum('not_started','in_progress','completed','cancelled') NOT NULL DEFAULT 'not_started',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mentorship_reminders`
--

CREATE TABLE `mentorship_reminders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `mentorship_session_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `remind_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mentorship_sessions`
--

CREATE TABLE `mentorship_sessions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `mentor_match_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `agenda` text DEFAULT NULL,
  `scheduled_at` datetime NOT NULL,
  `duration_minutes` int(10) UNSIGNED DEFAULT NULL,
  `meeting_link` varchar(255) DEFAULT NULL,
  `venue` varchar(255) DEFAULT NULL,
  `status` enum('scheduled','completed','cancelled','missed') NOT NULL DEFAULT 'scheduled',
  `mentor_attended` tinyint(1) DEFAULT NULL,
  `mentee_attended` tinyint(1) DEFAULT NULL,
  `session_notes` text DEFAULT NULL,
  `agreed_actions` text DEFAULT NULL,
  `next_session_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mentor_matches`
--

CREATE TABLE `mentor_matches` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `mentor_user_id` bigint(20) UNSIGNED NOT NULL,
  `mentee_user_id` bigint(20) UNSIGNED NOT NULL,
  `programme_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cohort_id` bigint(20) UNSIGNED DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('pending','active','paused','completed','cancelled') NOT NULL DEFAULT 'pending',
  `matched_by` bigint(20) UNSIGNED DEFAULT NULL,
  `matching_score` decimal(5,2) DEFAULT NULL,
  `matching_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mentor_profiles`
--

CREATE TABLE `mentor_profiles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `organisation` varchar(255) DEFAULT NULL,
  `job_title` varchar(255) DEFAULT NULL,
  `industry` varchar(255) DEFAULT NULL,
  `years_experience` int(10) UNSIGNED DEFAULT NULL,
  `professional_bio` text DEFAULT NULL,
  `skills` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`skills`)),
  `languages` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`languages`)),
  `mentoring_areas` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`mentoring_areas`)),
  `linkedin_url` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `availability` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`availability`)),
  `status` enum('pending','approved','inactive','rejected') NOT NULL DEFAULT 'pending',
  `approved_at` timestamp NULL DEFAULT NULL,
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2026_09_21_000001_create_roles_and_permissions_tables', 1),
(5, '2026_09_21_000002_extend_users_and_create_profiles', 1),
(6, '2026_09_21_000003_create_organisation_structure_tables', 1),
(7, '2026_09_21_000004_create_consents_and_audit_logs', 1),
(8, '2026_09_21_010001_create_user_security_and_mfa_tables', 1),
(9, '2026_09_21_010002_create_migration_staging_tables', 1),
(10, '2026_09_21_010003_create_elearning_core_tables', 1),
(11, '2026_09_21_020001_add_learning_files_notifications_and_mentorship', 1),
(12, '2026_09_21_025001_create_resume_and_library_tables', 1),
(13, '2026_09_21_030001_complete_mentorship_and_create_jobs_module', 1),
(14, '2026_09_21_035001_create_job_recommendations_table', 1),
(15, '2026_09_21_050001_create_workplans_me_calendar_tables', 1),
(16, '2026_09_21_060001_create_hr_people_tables', 1),
(17, '2026_09_21_070001_create_assets_procurement_tables', 1),
(18, '2026_09_21_080001_create_system_completion_tables', 1),
(19, '2026_09_22_160000_add_branch_id_to_profiles_table', 2),
(20, '2026_09_22_200000_create_resume_ai_centre_tables', 3),
(21, '2026_09_23_120000_extend_career_documents_and_pwd', 4),
(22, '2026_09_24_140300_create_asset_disposals_table', 5),
(23, '2026_09_24_230000_add_hr_kpi_and_module_locking', 6),
(24, '2026_09_24_235500_add_staff_appraisal_workflow', 7),
(25, '2026_09_25_080000_create_events_attendance_tables', 8),
(26, '2026_09_25_090000_add_event_operations_features', 8),
(27, '2026_09_25_100000_add_event_feedback_certificates', 9),
(28, '2026_09_25_101500_repair_course_cohort_table', 10),
(29, '2026_09_25_170000_create_course_calls_surveys_settings_foundation', 11),
(30, '2026_09_25_183000_create_platform_backups_table', 12),
(31, '2026_09_26_073500_make_course_calls_general_multi_course', 13);

-- --------------------------------------------------------

--
-- Table structure for table `migration_batches`
--

CREATE TABLE `migration_batches` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `source_system` varchar(255) NOT NULL,
  `batch_name` varchar(255) NOT NULL,
  `source_file` varchar(255) DEFAULT NULL,
  `total_rows` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `processed_rows` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `successful_rows` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `failed_rows` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `status` enum('draft','validated','processing','completed','failed') NOT NULL DEFAULT 'draft',
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migration_staging_records`
--

CREATE TABLE `migration_staging_records` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `migration_batch_id` bigint(20) UNSIGNED NOT NULL,
  `source_table` varchar(255) DEFAULT NULL,
  `source_record_id` varchar(255) DEFAULT NULL,
  `entity_type` varchar(255) NOT NULL,
  `source_payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`source_payload`)),
  `normalised_payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`normalised_payload`)),
  `match_status` varchar(255) DEFAULT NULL,
  `matched_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `validation_errors` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`validation_errors`)),
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `milestones`
--

CREATE TABLE `milestones` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `workplan_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `responsible_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `expected_result` text DEFAULT NULL,
  `weight` decimal(5,2) NOT NULL DEFAULT 1.00,
  `progress_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `status` enum('not_started','in_progress','at_risk','delayed','completed','cancelled') NOT NULL DEFAULT 'not_started',
  `priority` varchar(255) NOT NULL DEFAULT 'medium',
  `dependencies` text DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `participant_outcomes`
--

CREATE TABLE `participant_outcomes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `programme_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cohort_id` bigint(20) UNSIGNED DEFAULT NULL,
  `outcome_type` enum('new_self_employment','additional_self_employment','improved_self_employment','new_wage_employment','additional_wage_employment','improved_wage_employment','other') NOT NULL,
  `organisation_name` varchar(255) DEFAULT NULL,
  `job_title` varchar(255) DEFAULT NULL,
  `outcome_date` date DEFAULT NULL,
  `income_amount` decimal(15,2) DEFAULT NULL,
  `income_currency` varchar(3) NOT NULL DEFAULT 'UGX',
  `notes` text DEFAULT NULL,
  `verification_status` enum('draft','submitted','verified','rejected') NOT NULL DEFAULT 'draft',
  `verified_by` bigint(20) UNSIGNED DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `module` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `slug`, `module`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Users View', 'users.view', 'users', NULL, '2026-09-22 08:27:34', '2026-09-22 08:27:34'),
(2, 'Users Create', 'users.create', 'users', NULL, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(3, 'Users Edit', 'users.edit', 'users', NULL, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(4, 'Users Delete', 'users.delete', 'users', NULL, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(5, 'Roles Manage', 'roles.manage', 'roles', NULL, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(6, 'Permissions Manage', 'permissions.manage', 'permissions', NULL, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(7, 'Programmes View', 'programmes.view', 'programmes', NULL, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(8, 'Programmes Manage', 'programmes.manage', 'programmes', NULL, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(9, 'Cohorts View', 'cohorts.view', 'cohorts', NULL, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(10, 'Cohorts Manage', 'cohorts.manage', 'cohorts', NULL, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(11, 'Courses View', 'courses.view', 'courses', NULL, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(12, 'Courses Create', 'courses.create', 'courses', NULL, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(13, 'Courses Edit', 'courses.edit', 'courses', NULL, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(14, 'Courses Delete', 'courses.delete', 'courses', NULL, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(15, 'Students View', 'students.view', 'students', NULL, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(16, 'Students Edit', 'students.edit', 'students', NULL, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(17, 'Mentors View', 'mentors.view', 'mentors', NULL, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(18, 'Mentors Manage', 'mentors.manage', 'mentors', NULL, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(19, 'Mentorship Match', 'mentorship.match', 'mentorship', NULL, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(20, 'Jobs View', 'jobs.view', 'jobs', NULL, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(21, 'Jobs Manage', 'jobs.manage', 'jobs', NULL, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(22, 'Employers Approve', 'employers.approve', 'employers', NULL, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(23, 'Library Manage', 'library.manage', 'library', NULL, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(24, 'Workplans View', 'workplans.view', 'workplans', NULL, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(25, 'Workplans Create', 'workplans.create', 'workplans', NULL, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(26, 'Workplans Edit', 'workplans.edit', 'workplans', NULL, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(27, 'Workplans Approve', 'workplans.approve', 'workplans', NULL, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(28, 'Milestones Manage', 'milestones.manage', 'milestones', NULL, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(29, 'Activities Manage', 'activities.manage', 'activities', NULL, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(30, 'Tasks Manage', 'tasks.manage', 'tasks', NULL, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(31, 'Indicators View', 'indicators.view', 'indicators', NULL, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(32, 'Indicators Manage', 'indicators.manage', 'indicators', NULL, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(33, 'Indicators Verify', 'indicators.verify', 'indicators', NULL, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(34, 'Meal View', 'meal.view', 'meal', NULL, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(35, 'Meal Manage', 'meal.manage', 'meal', NULL, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(36, 'Calendar Manage', 'calendar.manage', 'calendar', NULL, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(37, 'Hr View', 'hr.view', 'hr', NULL, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(38, 'Hr Manage', 'hr.manage', 'hr', NULL, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(39, 'Leave View', 'leave.view', 'leave', NULL, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(40, 'Leave Approve', 'leave.approve', 'leave', NULL, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(41, 'Appraisals View', 'appraisals.view', 'appraisals', NULL, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(42, 'Appraisals Manage', 'appraisals.manage', 'appraisals', NULL, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(43, 'Staff Exit Manage', 'staff_exit.manage', 'staff_exit', NULL, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(44, 'Assets View', 'assets.view', 'assets', NULL, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(45, 'Assets Manage', 'assets.manage', 'assets', NULL, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(46, 'Assets Dispose', 'assets.dispose', 'assets', NULL, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(47, 'Procurement View', 'procurement.view', 'procurement', NULL, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(48, 'Procurement Create', 'procurement.create', 'procurement', NULL, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(49, 'Procurement Approve', 'procurement.approve', 'procurement', NULL, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(50, 'Procurement Receive', 'procurement.receive', 'procurement', NULL, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(51, 'Reports View', 'reports.view', 'reports', NULL, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(52, 'Reports Export', 'reports.export', 'reports', NULL, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(53, 'Settings Manage', 'settings.manage', 'settings', NULL, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(54, 'Course Calls View', 'course_calls.view', 'course_calls', NULL, '2026-09-25 15:15:08', '2026-09-25 15:15:08'),
(55, 'Course Calls Manage', 'course_calls.manage', 'course_calls', NULL, '2026-09-25 15:15:08', '2026-09-25 15:15:08'),
(56, 'Applications Review', 'applications.review', 'course_calls', NULL, '2026-09-25 15:15:08', '2026-09-25 15:15:08'),
(57, 'Entry Assessments Review', 'entry_assessments.review', 'course_calls', NULL, '2026-09-25 15:15:08', '2026-09-25 15:15:08'),
(58, 'Surveys View', 'surveys.view', 'surveys', NULL, '2026-09-25 15:15:08', '2026-09-25 15:15:08'),
(59, 'Surveys Manage', 'surveys.manage', 'surveys', NULL, '2026-09-25 15:15:08', '2026-09-25 15:15:08'),
(60, 'Survey Responses View', 'survey_responses.view', 'surveys', NULL, '2026-09-25 15:15:08', '2026-09-25 15:15:08'),
(61, 'Survey Responses Export', 'survey_responses.export', 'surveys', NULL, '2026-09-25 15:15:08', '2026-09-25 15:15:08'),
(62, 'Settings Branding', 'settings.branding', 'settings', NULL, '2026-09-25 15:15:08', '2026-09-25 15:15:08'),
(63, 'Settings Backups', 'settings.backups', 'settings', NULL, '2026-09-25 15:15:08', '2026-09-25 15:15:08'),
(64, 'Settings Maintenance', 'settings.maintenance', 'settings', NULL, '2026-09-25 15:15:08', '2026-09-25 15:15:08');

-- --------------------------------------------------------

--
-- Table structure for table `permission_role`
--

CREATE TABLE `permission_role` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permission_role`
--

INSERT INTO `permission_role` (`permission_id`, `role_id`, `created_at`, `updated_at`) VALUES
(1, 1, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(1, 2, '2026-09-25 13:03:51', '2026-09-25 13:03:51'),
(1, 20, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(1, 26, '2026-09-23 15:01:13', '2026-09-23 15:01:13'),
(2, 1, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(2, 2, '2026-09-25 13:03:51', '2026-09-25 13:03:51'),
(2, 26, '2026-09-23 15:01:13', '2026-09-23 15:01:13'),
(3, 1, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(3, 2, '2026-09-25 13:03:51', '2026-09-25 13:03:51'),
(3, 26, '2026-09-23 15:01:13', '2026-09-23 15:01:13'),
(4, 1, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(4, 2, '2026-09-25 13:03:51', '2026-09-25 13:03:51'),
(4, 26, '2026-09-23 15:01:13', '2026-09-23 15:01:13'),
(5, 1, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(5, 2, '2026-09-25 13:03:51', '2026-09-25 13:03:51'),
(5, 26, '2026-09-23 15:01:13', '2026-09-23 15:01:13'),
(6, 1, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(6, 2, '2026-09-25 13:03:51', '2026-09-25 13:03:51'),
(6, 4, '2026-09-25 13:09:46', '2026-09-25 13:09:46'),
(6, 26, '2026-09-23 15:01:13', '2026-09-23 15:01:13'),
(7, 1, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(7, 2, '2026-09-25 13:03:51', '2026-09-25 13:03:51'),
(7, 4, '2026-09-25 13:09:46', '2026-09-25 13:09:46'),
(7, 8, '2026-09-25 13:09:10', '2026-09-25 13:09:10'),
(7, 20, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(7, 26, '2026-09-23 15:01:13', '2026-09-23 15:01:13'),
(8, 1, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(8, 2, '2026-09-25 13:03:51', '2026-09-25 13:03:51'),
(8, 4, '2026-09-25 13:09:46', '2026-09-25 13:09:46'),
(8, 8, '2026-09-25 13:09:10', '2026-09-25 13:09:10'),
(8, 26, '2026-09-23 15:01:13', '2026-09-23 15:01:13'),
(9, 1, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(9, 2, '2026-09-25 13:03:50', '2026-09-25 13:03:50'),
(9, 4, '2026-09-25 13:09:46', '2026-09-25 13:09:46'),
(9, 8, '2026-09-25 13:09:10', '2026-09-25 13:09:10'),
(9, 10, '2026-09-25 13:06:47', '2026-09-25 13:06:47'),
(9, 20, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(9, 26, '2026-09-23 15:01:13', '2026-09-23 15:01:13'),
(10, 1, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(10, 2, '2026-09-25 13:03:50', '2026-09-25 13:03:50'),
(10, 4, '2026-09-25 13:09:46', '2026-09-25 13:09:46'),
(10, 8, '2026-09-25 13:09:10', '2026-09-25 13:09:10'),
(10, 10, '2026-09-25 13:06:47', '2026-09-25 13:06:47'),
(10, 26, '2026-09-23 15:01:13', '2026-09-23 15:01:13'),
(11, 1, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(11, 2, '2026-09-25 13:03:51', '2026-09-25 13:03:51'),
(11, 4, '2026-09-25 13:09:46', '2026-09-25 13:09:46'),
(11, 8, '2026-09-25 13:09:10', '2026-09-25 13:09:10'),
(11, 10, '2026-09-25 13:06:47', '2026-09-25 13:06:47'),
(11, 20, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(11, 26, '2026-09-23 15:01:13', '2026-09-23 15:01:13'),
(12, 1, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(12, 2, '2026-09-25 13:03:50', '2026-09-25 13:03:50'),
(12, 4, '2026-09-25 13:09:46', '2026-09-25 13:09:46'),
(12, 8, '2026-09-25 13:09:10', '2026-09-25 13:09:10'),
(12, 26, '2026-09-23 15:01:13', '2026-09-23 15:01:13'),
(13, 1, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(13, 2, '2026-09-25 13:03:51', '2026-09-25 13:03:51'),
(13, 4, '2026-09-25 13:09:46', '2026-09-25 13:09:46'),
(13, 8, '2026-09-25 13:09:10', '2026-09-25 13:09:10'),
(13, 26, '2026-09-23 15:01:13', '2026-09-23 15:01:13'),
(14, 1, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(14, 2, '2026-09-25 13:03:51', '2026-09-25 13:03:51'),
(14, 4, '2026-09-25 13:09:46', '2026-09-25 13:09:46'),
(14, 8, '2026-09-25 13:09:10', '2026-09-25 13:09:10'),
(14, 26, '2026-09-23 15:01:13', '2026-09-23 15:01:13'),
(15, 1, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(15, 2, '2026-09-25 13:03:51', '2026-09-25 13:03:51'),
(15, 4, '2026-09-25 13:09:46', '2026-09-25 13:09:46'),
(15, 6, '2026-09-25 13:07:56', '2026-09-25 13:07:56'),
(15, 8, '2026-09-25 13:09:10', '2026-09-25 13:09:10'),
(15, 10, '2026-09-25 13:07:06', '2026-09-25 13:07:06'),
(15, 20, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(15, 26, '2026-09-23 15:01:13', '2026-09-23 15:01:13'),
(16, 1, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(16, 2, '2026-09-25 13:03:51', '2026-09-25 13:03:51'),
(16, 4, '2026-09-25 13:09:46', '2026-09-25 13:09:46'),
(16, 8, '2026-09-25 13:09:10', '2026-09-25 13:09:10'),
(16, 10, '2026-09-25 13:07:06', '2026-09-25 13:07:06'),
(16, 26, '2026-09-23 15:01:13', '2026-09-23 15:01:13'),
(17, 1, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(17, 2, '2026-09-25 13:03:51', '2026-09-25 13:03:51'),
(17, 4, '2026-09-25 13:09:46', '2026-09-25 13:09:46'),
(17, 8, '2026-09-25 13:09:10', '2026-09-25 13:09:10'),
(17, 15, '2026-09-25 13:03:37', '2026-09-25 13:03:37'),
(17, 20, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(17, 26, '2026-09-23 15:01:13', '2026-09-23 15:01:13'),
(18, 1, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(18, 2, '2026-09-25 13:03:51', '2026-09-25 13:03:51'),
(18, 4, '2026-09-25 13:09:46', '2026-09-25 13:09:46'),
(18, 8, '2026-09-25 13:09:10', '2026-09-25 13:09:10'),
(18, 15, '2026-09-25 13:03:37', '2026-09-25 13:03:37'),
(18, 26, '2026-09-23 15:01:13', '2026-09-23 15:01:13'),
(19, 1, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(19, 2, '2026-09-25 13:03:51', '2026-09-25 13:03:51'),
(19, 4, '2026-09-25 13:09:46', '2026-09-25 13:09:46'),
(19, 8, '2026-09-25 13:09:10', '2026-09-25 13:09:10'),
(19, 26, '2026-09-23 15:01:13', '2026-09-23 15:01:13'),
(20, 1, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(20, 2, '2026-09-25 13:03:51', '2026-09-25 13:03:51'),
(20, 4, '2026-09-25 13:09:46', '2026-09-25 13:09:46'),
(20, 8, '2026-09-25 13:09:10', '2026-09-25 13:09:10'),
(20, 10, '2026-09-25 13:06:47', '2026-09-25 13:06:47'),
(20, 15, '2026-09-25 13:03:37', '2026-09-25 13:03:37'),
(20, 20, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(20, 26, '2026-09-23 15:01:13', '2026-09-23 15:01:13'),
(21, 1, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(21, 2, '2026-09-25 13:03:51', '2026-09-25 13:03:51'),
(21, 4, '2026-09-25 13:09:46', '2026-09-25 13:09:46'),
(21, 8, '2026-09-25 13:09:10', '2026-09-25 13:09:10'),
(21, 15, '2026-09-25 13:03:37', '2026-09-25 13:03:37'),
(21, 26, '2026-09-23 15:01:13', '2026-09-23 15:01:13'),
(22, 1, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(22, 2, '2026-09-25 13:03:51', '2026-09-25 13:03:51'),
(22, 4, '2026-09-25 13:09:46', '2026-09-25 13:09:46'),
(22, 8, '2026-09-25 13:09:10', '2026-09-25 13:09:10'),
(22, 15, '2026-09-25 13:03:37', '2026-09-25 13:03:37'),
(22, 26, '2026-09-23 15:01:13', '2026-09-23 15:01:13'),
(23, 1, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(23, 2, '2026-09-25 13:03:51', '2026-09-25 13:03:51'),
(23, 4, '2026-09-25 13:09:46', '2026-09-25 13:09:46'),
(23, 8, '2026-09-25 13:09:10', '2026-09-25 13:09:10'),
(23, 26, '2026-09-23 15:01:13', '2026-09-23 15:01:13'),
(24, 1, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(24, 2, '2026-09-25 13:03:51', '2026-09-25 13:03:51'),
(24, 4, '2026-09-25 13:09:46', '2026-09-25 13:09:46'),
(24, 8, '2026-09-25 13:09:10', '2026-09-25 13:09:10'),
(24, 20, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(24, 26, '2026-09-23 15:01:13', '2026-09-23 15:01:13'),
(25, 1, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(25, 2, '2026-09-25 13:03:51', '2026-09-25 13:03:51'),
(25, 4, '2026-09-25 13:09:46', '2026-09-25 13:09:46'),
(25, 8, '2026-09-25 13:09:10', '2026-09-25 13:09:10'),
(25, 26, '2026-09-23 15:01:13', '2026-09-23 15:01:13'),
(26, 1, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(26, 2, '2026-09-25 13:03:51', '2026-09-25 13:03:51'),
(26, 4, '2026-09-25 13:09:46', '2026-09-25 13:09:46'),
(26, 8, '2026-09-25 13:09:10', '2026-09-25 13:09:10'),
(26, 26, '2026-09-23 15:01:13', '2026-09-23 15:01:13'),
(27, 1, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(27, 2, '2026-09-25 13:03:51', '2026-09-25 13:03:51'),
(27, 4, '2026-09-25 13:09:46', '2026-09-25 13:09:46'),
(27, 8, '2026-09-25 13:09:10', '2026-09-25 13:09:10'),
(27, 26, '2026-09-23 15:01:13', '2026-09-23 15:01:13'),
(28, 1, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(28, 2, '2026-09-25 13:03:51', '2026-09-25 13:03:51'),
(28, 4, '2026-09-25 13:09:46', '2026-09-25 13:09:46'),
(28, 8, '2026-09-25 13:09:10', '2026-09-25 13:09:10'),
(28, 26, '2026-09-23 15:01:13', '2026-09-23 15:01:13'),
(29, 1, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(29, 2, '2026-09-25 13:03:50', '2026-09-25 13:03:50'),
(29, 4, '2026-09-25 13:09:46', '2026-09-25 13:09:46'),
(29, 8, '2026-09-25 13:09:10', '2026-09-25 13:09:10'),
(29, 15, '2026-09-25 13:03:37', '2026-09-25 13:03:37'),
(29, 26, '2026-09-23 15:01:13', '2026-09-23 15:01:13'),
(30, 1, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(30, 2, '2026-09-25 13:03:51', '2026-09-25 13:03:51'),
(30, 4, '2026-09-25 13:09:46', '2026-09-25 13:09:46'),
(30, 8, '2026-09-25 13:09:10', '2026-09-25 13:09:10'),
(30, 15, '2026-09-25 13:03:37', '2026-09-25 13:03:37'),
(30, 26, '2026-09-23 15:01:13', '2026-09-23 15:01:13'),
(31, 1, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(31, 2, '2026-09-25 13:03:51', '2026-09-25 13:03:51'),
(31, 4, '2026-09-25 13:09:46', '2026-09-25 13:09:46'),
(31, 8, '2026-09-25 13:09:10', '2026-09-25 13:09:10'),
(31, 20, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(31, 26, '2026-09-23 15:01:13', '2026-09-23 15:01:13'),
(32, 1, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(32, 2, '2026-09-25 13:03:51', '2026-09-25 13:03:51'),
(32, 4, '2026-09-25 13:09:46', '2026-09-25 13:09:46'),
(32, 8, '2026-09-25 13:09:10', '2026-09-25 13:09:10'),
(32, 26, '2026-09-23 15:01:13', '2026-09-23 15:01:13'),
(33, 1, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(33, 2, '2026-09-25 13:03:51', '2026-09-25 13:03:51'),
(33, 4, '2026-09-25 13:09:46', '2026-09-25 13:09:46'),
(33, 8, '2026-09-25 13:09:10', '2026-09-25 13:09:10'),
(33, 26, '2026-09-23 15:01:13', '2026-09-23 15:01:13'),
(34, 1, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(34, 2, '2026-09-25 13:03:51', '2026-09-25 13:03:51'),
(34, 4, '2026-09-25 13:09:46', '2026-09-25 13:09:46'),
(34, 6, '2026-09-25 13:07:56', '2026-09-25 13:07:56'),
(34, 8, '2026-09-25 13:09:10', '2026-09-25 13:09:10'),
(34, 20, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(34, 26, '2026-09-23 15:01:13', '2026-09-23 15:01:13'),
(35, 1, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(35, 2, '2026-09-25 13:03:51', '2026-09-25 13:03:51'),
(35, 4, '2026-09-25 13:09:46', '2026-09-25 13:09:46'),
(35, 6, '2026-09-25 13:07:56', '2026-09-25 13:07:56'),
(35, 8, '2026-09-25 13:09:10', '2026-09-25 13:09:10'),
(35, 26, '2026-09-23 15:01:13', '2026-09-23 15:01:13'),
(36, 1, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(36, 2, '2026-09-25 13:03:50', '2026-09-25 13:03:50'),
(36, 4, '2026-09-25 13:09:46', '2026-09-25 13:09:46'),
(36, 8, '2026-09-25 13:09:10', '2026-09-25 13:09:10'),
(36, 10, '2026-09-25 13:06:47', '2026-09-25 13:06:47'),
(36, 15, '2026-09-25 13:03:37', '2026-09-25 13:03:37'),
(36, 26, '2026-09-23 15:01:13', '2026-09-23 15:01:13'),
(37, 1, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(37, 2, '2026-09-25 13:03:51', '2026-09-25 13:03:51'),
(37, 4, '2026-09-25 13:09:46', '2026-09-25 13:09:46'),
(37, 8, '2026-09-25 13:09:10', '2026-09-25 13:09:10'),
(37, 15, '2026-09-25 13:03:37', '2026-09-25 13:03:37'),
(37, 20, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(37, 26, '2026-09-23 15:01:13', '2026-09-23 15:01:13'),
(38, 1, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(38, 2, '2026-09-25 13:03:51', '2026-09-25 13:03:51'),
(38, 4, '2026-09-25 13:09:46', '2026-09-25 13:09:46'),
(38, 8, '2026-09-25 13:09:10', '2026-09-25 13:09:10'),
(38, 15, '2026-09-25 13:03:37', '2026-09-25 13:03:37'),
(38, 26, '2026-09-23 15:01:13', '2026-09-23 15:01:13'),
(39, 1, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(39, 2, '2026-09-25 13:03:51', '2026-09-25 13:03:51'),
(39, 4, '2026-09-25 13:09:46', '2026-09-25 13:09:46'),
(39, 8, '2026-09-25 13:09:10', '2026-09-25 13:09:10'),
(39, 15, '2026-09-25 13:03:37', '2026-09-25 13:03:37'),
(39, 20, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(39, 26, '2026-09-23 15:01:13', '2026-09-23 15:01:13'),
(40, 1, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(40, 2, '2026-09-25 13:03:51', '2026-09-25 13:03:51'),
(40, 4, '2026-09-25 13:09:46', '2026-09-25 13:09:46'),
(40, 8, '2026-09-25 13:09:10', '2026-09-25 13:09:10'),
(40, 15, '2026-09-25 13:03:37', '2026-09-25 13:03:37'),
(40, 26, '2026-09-23 15:01:13', '2026-09-23 15:01:13'),
(41, 1, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(41, 2, '2026-09-25 13:03:50', '2026-09-25 13:03:50'),
(41, 4, '2026-09-25 13:09:46', '2026-09-25 13:09:46'),
(41, 8, '2026-09-25 13:09:10', '2026-09-25 13:09:10'),
(41, 10, '2026-09-25 13:06:47', '2026-09-25 13:06:47'),
(41, 15, '2026-09-25 13:03:37', '2026-09-25 13:03:37'),
(41, 20, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(41, 26, '2026-09-23 15:01:13', '2026-09-23 15:01:13'),
(42, 1, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(42, 2, '2026-09-25 13:03:50', '2026-09-25 13:03:50'),
(42, 4, '2026-09-25 13:09:46', '2026-09-25 13:09:46'),
(42, 8, '2026-09-25 13:09:10', '2026-09-25 13:09:10'),
(42, 15, '2026-09-25 13:03:37', '2026-09-25 13:03:37'),
(42, 26, '2026-09-23 15:01:13', '2026-09-23 15:01:13'),
(43, 1, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(43, 2, '2026-09-25 13:03:51', '2026-09-25 13:03:51'),
(43, 4, '2026-09-25 13:09:46', '2026-09-25 13:09:46'),
(43, 8, '2026-09-25 13:09:10', '2026-09-25 13:09:10'),
(43, 15, '2026-09-25 13:03:37', '2026-09-25 13:03:37'),
(43, 26, '2026-09-23 15:01:13', '2026-09-23 15:01:13'),
(44, 1, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(44, 2, '2026-09-25 13:03:50', '2026-09-25 13:03:50'),
(44, 4, '2026-09-25 13:09:46', '2026-09-25 13:09:46'),
(44, 8, '2026-09-25 13:09:10', '2026-09-25 13:09:10'),
(44, 15, '2026-09-25 13:03:37', '2026-09-25 13:03:37'),
(44, 20, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(44, 26, '2026-09-23 15:01:13', '2026-09-23 15:01:13'),
(45, 1, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(45, 2, '2026-09-25 13:03:50', '2026-09-25 13:03:50'),
(45, 4, '2026-09-25 13:09:46', '2026-09-25 13:09:46'),
(45, 8, '2026-09-25 13:09:10', '2026-09-25 13:09:10'),
(45, 15, '2026-09-25 13:03:37', '2026-09-25 13:03:37'),
(45, 26, '2026-09-23 15:01:13', '2026-09-23 15:01:13'),
(46, 1, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(46, 2, '2026-09-25 13:03:50', '2026-09-25 13:03:50'),
(46, 4, '2026-09-25 13:09:46', '2026-09-25 13:09:46'),
(46, 8, '2026-09-25 13:09:10', '2026-09-25 13:09:10'),
(46, 15, '2026-09-25 13:03:37', '2026-09-25 13:03:37'),
(46, 26, '2026-09-23 15:01:13', '2026-09-23 15:01:13'),
(47, 1, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(47, 2, '2026-09-25 13:03:51', '2026-09-25 13:03:51'),
(47, 4, '2026-09-25 13:09:46', '2026-09-25 13:09:46'),
(47, 8, '2026-09-25 13:09:10', '2026-09-25 13:09:10'),
(47, 20, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(47, 26, '2026-09-23 15:01:13', '2026-09-23 15:01:13'),
(48, 1, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(48, 2, '2026-09-25 13:03:51', '2026-09-25 13:03:51'),
(48, 4, '2026-09-25 13:09:46', '2026-09-25 13:09:46'),
(48, 8, '2026-09-25 13:09:10', '2026-09-25 13:09:10'),
(48, 26, '2026-09-23 15:01:13', '2026-09-23 15:01:13'),
(49, 1, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(49, 2, '2026-09-25 13:03:51', '2026-09-25 13:03:51'),
(49, 4, '2026-09-25 13:09:46', '2026-09-25 13:09:46'),
(49, 8, '2026-09-25 13:09:10', '2026-09-25 13:09:10'),
(49, 26, '2026-09-23 15:01:13', '2026-09-23 15:01:13'),
(50, 1, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(50, 2, '2026-09-25 13:03:51', '2026-09-25 13:03:51'),
(50, 4, '2026-09-25 13:09:46', '2026-09-25 13:09:46'),
(50, 8, '2026-09-25 13:09:10', '2026-09-25 13:09:10'),
(50, 26, '2026-09-23 15:01:13', '2026-09-23 15:01:13'),
(51, 1, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(51, 2, '2026-09-25 13:03:51', '2026-09-25 13:03:51'),
(51, 4, '2026-09-25 13:09:46', '2026-09-25 13:09:46'),
(51, 8, '2026-09-25 13:09:10', '2026-09-25 13:09:10'),
(51, 20, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(51, 26, '2026-09-23 15:01:13', '2026-09-23 15:01:13'),
(52, 1, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(52, 2, '2026-09-25 13:03:51', '2026-09-25 13:03:51'),
(52, 4, '2026-09-25 13:09:46', '2026-09-25 13:09:46'),
(52, 8, '2026-09-25 13:09:10', '2026-09-25 13:09:10'),
(52, 26, '2026-09-23 15:01:13', '2026-09-23 15:01:13'),
(53, 1, '2026-09-22 08:27:35', '2026-09-22 08:27:35'),
(53, 2, '2026-09-25 13:03:51', '2026-09-25 13:03:51'),
(53, 26, '2026-09-23 15:01:13', '2026-09-23 15:01:13'),
(54, 1, '2026-09-25 15:15:10', '2026-09-25 15:15:10'),
(54, 2, '2026-09-26 06:56:56', '2026-09-26 06:56:56'),
(54, 3, '2026-09-26 06:56:57', '2026-09-26 06:56:57'),
(54, 4, '2026-09-26 06:56:56', '2026-09-26 06:56:56'),
(54, 5, '2026-09-26 06:56:57', '2026-09-26 06:56:57'),
(54, 10, '2026-09-26 06:56:57', '2026-09-26 06:56:57'),
(54, 26, '2026-09-25 15:15:10', '2026-09-25 15:15:10'),
(55, 1, '2026-09-25 15:15:10', '2026-09-25 15:15:10'),
(55, 2, '2026-09-26 06:56:56', '2026-09-26 06:56:56'),
(55, 3, '2026-09-26 06:56:57', '2026-09-26 06:56:57'),
(55, 4, '2026-09-26 06:56:56', '2026-09-26 06:56:56'),
(55, 26, '2026-09-25 15:15:10', '2026-09-25 15:15:10'),
(56, 1, '2026-09-25 15:15:10', '2026-09-25 15:15:10'),
(56, 2, '2026-09-26 06:56:56', '2026-09-26 06:56:56'),
(56, 3, '2026-09-26 06:56:57', '2026-09-26 06:56:57'),
(56, 4, '2026-09-26 06:56:56', '2026-09-26 06:56:56'),
(56, 5, '2026-09-26 06:56:57', '2026-09-26 06:56:57'),
(56, 10, '2026-09-26 06:56:57', '2026-09-26 06:56:57'),
(56, 26, '2026-09-25 15:15:10', '2026-09-25 15:15:10'),
(57, 1, '2026-09-25 15:15:10', '2026-09-25 15:15:10'),
(57, 2, '2026-09-26 06:56:56', '2026-09-26 06:56:56'),
(57, 3, '2026-09-26 06:56:57', '2026-09-26 06:56:57'),
(57, 4, '2026-09-26 06:56:57', '2026-09-26 06:56:57'),
(57, 5, '2026-09-26 06:56:57', '2026-09-26 06:56:57'),
(57, 10, '2026-09-26 06:56:57', '2026-09-26 06:56:57'),
(57, 26, '2026-09-25 15:15:10', '2026-09-25 15:15:10'),
(58, 1, '2026-09-25 15:15:10', '2026-09-25 15:15:10'),
(58, 2, '2026-09-26 06:56:56', '2026-09-26 06:56:56'),
(58, 6, '2026-09-26 06:56:57', '2026-09-26 06:56:57'),
(58, 7, '2026-09-26 06:56:57', '2026-09-26 06:56:57'),
(58, 26, '2026-09-25 15:15:10', '2026-09-25 15:15:10'),
(59, 1, '2026-09-25 15:15:10', '2026-09-25 15:15:10'),
(59, 2, '2026-09-26 06:56:56', '2026-09-26 06:56:56'),
(59, 6, '2026-09-26 06:56:57', '2026-09-26 06:56:57'),
(59, 7, '2026-09-26 06:56:57', '2026-09-26 06:56:57'),
(59, 26, '2026-09-25 15:15:10', '2026-09-25 15:15:10'),
(60, 1, '2026-09-25 15:15:10', '2026-09-25 15:15:10'),
(60, 2, '2026-09-26 06:56:56', '2026-09-26 06:56:56'),
(60, 6, '2026-09-26 06:56:57', '2026-09-26 06:56:57'),
(60, 7, '2026-09-26 06:56:57', '2026-09-26 06:56:57'),
(60, 26, '2026-09-25 15:15:10', '2026-09-25 15:15:10'),
(61, 1, '2026-09-25 15:15:10', '2026-09-25 15:15:10'),
(61, 2, '2026-09-26 06:56:56', '2026-09-26 06:56:56'),
(61, 6, '2026-09-26 06:56:57', '2026-09-26 06:56:57'),
(61, 7, '2026-09-26 06:56:57', '2026-09-26 06:56:57'),
(61, 26, '2026-09-25 15:15:10', '2026-09-25 15:15:10'),
(62, 1, '2026-09-25 15:15:10', '2026-09-25 15:15:10'),
(62, 2, '2026-09-26 06:56:56', '2026-09-26 06:56:56'),
(62, 26, '2026-09-25 15:15:10', '2026-09-25 15:15:10'),
(63, 1, '2026-09-25 15:15:10', '2026-09-25 15:15:10'),
(63, 2, '2026-09-26 06:56:56', '2026-09-26 06:56:56'),
(63, 26, '2026-09-25 15:15:10', '2026-09-25 15:15:10'),
(64, 1, '2026-09-25 15:15:10', '2026-09-25 15:15:10'),
(64, 2, '2026-09-26 06:56:56', '2026-09-26 06:56:56'),
(64, 26, '2026-09-25 15:15:10', '2026-09-25 15:15:10');

-- --------------------------------------------------------

--
-- Table structure for table `platform_backups`
--

CREATE TABLE `platform_backups` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `destination` varchar(255) NOT NULL DEFAULT 'local',
  `filename` varchar(255) NOT NULL,
  `path` varchar(255) DEFAULT NULL,
  `size_bytes` bigint(20) UNSIGNED DEFAULT NULL,
  `status` enum('pending','completed','failed') NOT NULL DEFAULT 'pending',
  `error_message` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `positions`
--

CREATE TABLE `positions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `department_id` bigint(20) UNSIGNED DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `grade` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `procurement_plans`
--

CREATE TABLE `procurement_plans` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `programme_id` bigint(20) UNSIGNED DEFAULT NULL,
  `project_id` bigint(20) UNSIGNED DEFAULT NULL,
  `workplan_id` bigint(20) UNSIGNED DEFAULT NULL,
  `financial_year` varchar(255) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `estimated_budget` decimal(15,2) DEFAULT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'UGX',
  `status` enum('draft','submitted','approved','closed') NOT NULL DEFAULT 'draft',
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `profiles`
--

CREATE TABLE `profiles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `branch_id` bigint(20) UNSIGNED DEFAULT NULL,
  `surname` varchar(255) DEFAULT NULL,
  `given_name` varchar(255) DEFAULT NULL,
  `other_name` varchar(255) DEFAULT NULL,
  `gender` enum('female','male','other','prefer_not_to_say') DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `district` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `is_pwd` tinyint(1) NOT NULL DEFAULT 0,
  `disability_types` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`disability_types`)),
  `disability_other` varchar(255) DEFAULT NULL,
  `education_level` varchar(255) DEFAULT NULL,
  `employment_status` varchar(255) DEFAULT NULL,
  `career_interests` text DEFAULT NULL,
  `preferred_language` varchar(255) NOT NULL DEFAULT 'English',
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `profiles`
--

INSERT INTO `profiles` (`id`, `user_id`, `branch_id`, `surname`, `given_name`, `other_name`, `gender`, `date_of_birth`, `country`, `district`, `location`, `is_pwd`, `disability_types`, `disability_other`, `education_level`, `employment_status`, `career_interests`, `preferred_language`, `metadata`, `created_at`, `updated_at`) VALUES
(3, 4, NULL, 'Waks', 'Kenneth', NULL, 'male', '1999-06-09', 'Uganda', 'Kampala', NULL, 0, NULL, NULL, NULL, NULL, 'Software development', 'English', NULL, '2026-09-22 13:09:58', '2026-09-22 13:09:58');

-- --------------------------------------------------------

--
-- Table structure for table `programmes`
--

CREATE TABLE `programmes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('draft','active','completed','on_hold','cancelled') NOT NULL DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `programmes`
--

INSERT INTO `programmes` (`id`, `name`, `code`, `description`, `start_date`, `end_date`, `status`, `created_at`, `updated_at`) VALUES
(1, 'DE', NULL, 'DE', '2025-05-24', '2027-05-31', 'active', '2026-09-24 06:01:47', '2026-09-24 06:01:47');

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `programme_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('draft','active','completed','on_hold','cancelled') NOT NULL DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

CREATE TABLE `purchase_orders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `po_number` varchar(255) NOT NULL,
  `purchase_request_id` bigint(20) UNSIGNED NOT NULL,
  `supplier_id` bigint(20) UNSIGNED NOT NULL,
  `order_date` date NOT NULL,
  `expected_delivery_date` date DEFAULT NULL,
  `subtotal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `currency` varchar(3) NOT NULL DEFAULT 'UGX',
  `status` enum('draft','issued','partially_received','received','cancelled','closed') NOT NULL DEFAULT 'draft',
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order_items`
--

CREATE TABLE `purchase_order_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `purchase_order_id` bigint(20) UNSIGNED NOT NULL,
  `purchase_request_item_id` bigint(20) UNSIGNED DEFAULT NULL,
  `item_name` varchar(255) NOT NULL,
  `specification` text DEFAULT NULL,
  `quantity` decimal(12,2) NOT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  `line_total` decimal(15,2) NOT NULL,
  `is_asset` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_requests`
--

CREATE TABLE `purchase_requests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `request_number` varchar(255) NOT NULL,
  `procurement_plan_id` bigint(20) UNSIGNED DEFAULT NULL,
  `programme_id` bigint(20) UNSIGNED DEFAULT NULL,
  `project_id` bigint(20) UNSIGNED DEFAULT NULL,
  `workplan_id` bigint(20) UNSIGNED DEFAULT NULL,
  `activity_id` bigint(20) UNSIGNED DEFAULT NULL,
  `requester_user_id` bigint(20) UNSIGNED NOT NULL,
  `department` varchar(255) DEFAULT NULL,
  `required_date` date DEFAULT NULL,
  `funding_source` varchar(255) DEFAULT NULL,
  `justification` text DEFAULT NULL,
  `estimated_total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `currency` varchar(3) NOT NULL DEFAULT 'UGX',
  `status` enum('draft','submitted','manager_approved','finance_approved','procurement_review','approved','rejected','sourcing','ordered','received','closed','cancelled') NOT NULL DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_request_approvals`
--

CREATE TABLE `purchase_request_approvals` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `purchase_request_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `approval_stage` varchar(255) NOT NULL,
  `decision` enum('approved','rejected','returned') NOT NULL,
  `comments` text DEFAULT NULL,
  `acted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_request_items`
--

CREATE TABLE `purchase_request_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `purchase_request_id` bigint(20) UNSIGNED NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `specification` text DEFAULT NULL,
  `quantity` decimal(12,2) NOT NULL,
  `unit` varchar(255) DEFAULT NULL,
  `estimated_unit_cost` decimal(15,2) DEFAULT NULL,
  `estimated_total` decimal(15,2) DEFAULT NULL,
  `is_asset` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `queue_jobs`
--

CREATE TABLE `queue_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `queue_jobs`
--

INSERT INTO `queue_jobs` (`id`, `queue`, `payload`, `attempts`, `reserved_at`, `available_at`, `created_at`) VALUES
(2, 'default', '{\"uuid\":\"dafa53bb-7dec-4556-82a2-09633e26441e\",\"displayName\":\"App\\\\Jobs\\\\ParseResumeUpload\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\ParseResumeUpload\",\"command\":\"O:26:\\\"App\\\\Jobs\\\\ParseResumeUpload\\\":1:{s:8:\\\"uploadId\\\";i:2;}\",\"batchId\":null},\"createdAt\":1790155897,\"delay\":null}', 0, NULL, 1790155897, 1790155897);

-- --------------------------------------------------------

--
-- Table structure for table `quotations`
--

CREATE TABLE `quotations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `purchase_request_id` bigint(20) UNSIGNED NOT NULL,
  `supplier_id` bigint(20) UNSIGNED NOT NULL,
  `quotation_number` varchar(255) DEFAULT NULL,
  `quotation_date` date DEFAULT NULL,
  `valid_until` date DEFAULT NULL,
  `subtotal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `currency` varchar(3) NOT NULL DEFAULT 'UGX',
  `document_path` varchar(255) DEFAULT NULL,
  `status` enum('received','evaluated','selected','not_selected') NOT NULL DEFAULT 'received',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quotation_evaluations`
--

CREATE TABLE `quotation_evaluations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `quotation_id` bigint(20) UNSIGNED NOT NULL,
  `evaluated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `technical_score` decimal(5,2) DEFAULT NULL,
  `financial_score` decimal(5,2) DEFAULT NULL,
  `overall_score` decimal(5,2) DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `recommended` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quotation_items`
--

CREATE TABLE `quotation_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `quotation_id` bigint(20) UNSIGNED NOT NULL,
  `purchase_request_item_id` bigint(20) UNSIGNED DEFAULT NULL,
  `item_name` varchar(255) NOT NULL,
  `quantity` decimal(12,2) NOT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  `line_total` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `report_exports`
--

CREATE TABLE `report_exports` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `requested_by` bigint(20) UNSIGNED DEFAULT NULL,
  `report_type` varchar(255) NOT NULL,
  `format` varchar(255) NOT NULL,
  `filters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`filters`)),
  `path` varchar(255) DEFAULT NULL,
  `status` enum('queued','processing','completed','failed') NOT NULL DEFAULT 'queued',
  `failure_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `results`
--

CREATE TABLE `results` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `results_framework_id` bigint(20) UNSIGNED NOT NULL,
  `parent_id` bigint(20) UNSIGNED DEFAULT NULL,
  `result_level` enum('impact','outcome','output') NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `results`
--

INSERT INTO `results` (`id`, `results_framework_id`, `parent_id`, `result_level`, `title`, `description`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 'impact', 'Impact', NULL, '2026-09-25 13:28:20', '2026-09-25 13:28:20');

-- --------------------------------------------------------

--
-- Table structure for table `results_frameworks`
--

CREATE TABLE `results_frameworks` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `programme_id` bigint(20) UNSIGNED DEFAULT NULL,
  `project_id` bigint(20) UNSIGNED DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `results_frameworks`
--

INSERT INTO `results_frameworks` (`id`, `programme_id`, `project_id`, `title`, `description`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 'DE Program', 'Testing', '2026-09-25 13:27:53', '2026-09-25 13:27:53');

-- --------------------------------------------------------

--
-- Table structure for table `resumes`
--

CREATE TABLE `resumes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL DEFAULT 'My Resume',
  `template` varchar(255) NOT NULL DEFAULT 'classic',
  `professional_summary` text DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `source` varchar(255) NOT NULL DEFAULT 'manual',
  `ai_enhanced` tinyint(1) NOT NULL DEFAULT 0,
  `completion_percent` tinyint(3) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `resumes`
--

INSERT INTO `resumes` (`id`, `user_id`, `title`, `template`, `professional_summary`, `is_default`, `created_at`, `updated_at`, `source`, `ai_enhanced`, `completion_percent`) VALUES
(1, 4, 'testing', 'executive', NULL, 0, '2026-09-22 15:46:02', '2026-09-26 04:46:34', 'manual', 0, 0),
(2, 4, 'Imported Resume', 'modern', NULL, 0, '2026-09-26 06:50:51', '2026-09-26 06:50:51', 'uploaded', 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `resume_certifications`
--

CREATE TABLE `resume_certifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `resume_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `issuer` varchar(255) DEFAULT NULL,
  `issued_on` date DEFAULT NULL,
  `expires_on` date DEFAULT NULL,
  `credential_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `resume_education`
--

CREATE TABLE `resume_education` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `resume_id` bigint(20) UNSIGNED NOT NULL,
  `institution` varchar(255) NOT NULL,
  `qualification` varchar(255) NOT NULL,
  `field_of_study` varchar(255) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `description` text DEFAULT NULL,
  `position` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `resume_experiences`
--

CREATE TABLE `resume_experiences` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `resume_id` bigint(20) UNSIGNED NOT NULL,
  `job_title` varchar(255) NOT NULL,
  `organisation` varchar(255) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `is_current` tinyint(1) NOT NULL DEFAULT 0,
  `description` text DEFAULT NULL,
  `position` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `resume_languages`
--

CREATE TABLE `resume_languages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `resume_id` bigint(20) UNSIGNED NOT NULL,
  `language` varchar(255) NOT NULL,
  `proficiency` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `resume_projects`
--

CREATE TABLE `resume_projects` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `resume_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `resume_skills`
--

CREATE TABLE `resume_skills` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `resume_id` bigint(20) UNSIGNED NOT NULL,
  `skill` varchar(255) NOT NULL,
  `level` varchar(255) DEFAULT NULL,
  `position` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `resume_uploads`
--

CREATE TABLE `resume_uploads` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `resume_id` bigint(20) UNSIGNED DEFAULT NULL,
  `original_name` varchar(255) NOT NULL,
  `stored_name` varchar(255) NOT NULL,
  `mime_type` varchar(120) NOT NULL,
  `file_size` bigint(20) UNSIGNED NOT NULL,
  `path` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'uploaded',
  `extracted_text` longtext DEFAULT NULL,
  `parsed_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`parsed_data`)),
  `parsing_error` text DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `resume_uploads`
--

INSERT INTO `resume_uploads` (`id`, `user_id`, `resume_id`, `original_name`, `stored_name`, `mime_type`, `file_size`, `path`, `status`, `extracted_text`, `parsed_data`, `parsing_error`, `processed_at`, `created_at`, `updated_at`) VALUES
(1, 4, 2, 'WAKABALA KENNETH RESUME.pdf', '299cb248-3f8e-4f74-8392-9dd61eeae6eb.pdf', 'application/pdf', 137823, 'private/resume-uploads/4/299cb248-3f8e-4f74-8392-9dd61eeae6eb.pdf', 'ready', 'WAKABALA KENNETH \nRONGORO VILLAGE, MBALE DISTRICT, UGANDA \nTel: +256-784675790/+256-704145972, Email: wakskenneth@gmail.com \nCAREER OBJECTIVE \nA proactive individual with a focused approach to challenges, innovations, dedicated to learn and to develop \nnew skills by honing my knowledge deeply in various Systems, Software developments, Data and \nDatabases, Networks, General IT Technical User support and Business. \nEDUCATION \nBachelor’s Degree: Computer Engineering, Busitema University Main Campus, Uganda       2013-2017 \nA Level Certificate: Mbale Secondary School, Mbale, Uganda                                2011-2012 \nO Level Certificate Mbale Secondary School, Mbale, Uganda                                2007-2010 \nWORK EXPERIENCE \nTechnical IT Officer: Hive Colab Limited, Kampala, Uganda                                              2021- Present \nAs a Technical IT Officer, I am responsible for Network management, Hardware and software installation \nand maintenance, Software development,  training both students and staff computer skills, and Field \nTechnician; this involves going to the field on projects that requires a technical person. \nSoftware Developer: Tradelance Limited, Bugolobi, Uganda                                            2020-2021 \nAs a Software Developer, I was responsible for android app and website development, managing loans \nsystem, project planning, computer repairing and maintenance. \nSystems Monitoring administrator: Divine Tech. research center Ltd, Uganda                      2018-2020 \nI was responsible for Software developments, Computer repairing and maintenance, Computer hardware \nand software installations, Managing internet, Installing CCTV cameras and data analysis using SPSS. \nResearch Assistant: True North Consultant, Uganda     7-18\nth\n Oct 2019 \nAs a research assistant, I collected data about children of age; 6-59 months, 12-23 months, 6-23 months & \nYouth health behaviors Survey. \nData Collector: Ichuli Africa, Kampala, Uganda            2-30\nth\n July 2018 \nAs a data collector, I was responsible for collecting data about students and teachers attendance and \nperformance in various primary schools in Manafwa district.\n\nSystems Development trainee: Maknova Technologies Ltd, Uganda         May-August 2016 \nAs a trainee, I managed to simulate and prototype various embedded systems using Arduino, also learnt \nproject planning, systems administration and computer repairing and maintenance. \nSite Technician: Cain-Link Technology Ltd, Kampala, Uganda          May-August 2015 \nAs a site technician, I was responsible of supervising Telecom Site installations, swapping and configuring \nthe site after my workmates finished installations, making reports for each site and reporting all site issues \nto Site head supervisor. \nTECHNICAL SKILLS \nComputer repairing and maintenance, UNIX (Windows and Linux Operating systems), Database \nManagement and Troubleshooting, Data backup and Data analysis using SPSS, Microsoft office suites, \nEmbedded systems development, Website design and development in HTML, CSS, PHP, JavaScript and \nMySQL, Networking (LAN and WAN) and Server. \nPERSONAL SKILLS \nHonest and trustworthy, Time management and respect for work deadlines, Team player and respectful, \nSelf-driven and Flexible. \nHONOURS AND AWARD \n• Sports Secretary of Busitema University Bamassaba Student Association                   (2016-2017). \n• Care group Hall leader in Christian Union of Busitema University                              (2015-2017). \n• Project Manager of Busitema University Computer Engineering Student Association (2015-2017).  \n• Computer Engineering Coordinator                                                                               (2013-2017). \nINTERESTS  \nPlaying and watching football, listening and watching news and talk shows and traveling. \nREFEREES \nMr. Arineitwe Joshua, HOD, Busitema University Faculty of Engineering, +256 782518874, \narineitwejoshua@yahoo.com \nMr. Eboku Steven, ICT Manager, Tradelance (U) Limited, +256785362006, ebokustephen@yahoo.com \nMrs. Komugisha Susan, Design, monitoring and evaluation officer, Mbale-Butelaja Cluster, World Vision, \n+256 782 645044, susan_komugisha@wvi.org', '{\"professional_summary\":null,\"experiences\":[],\"education\":[],\"skills\":[]}', NULL, '2026-09-23 08:41:16', '2026-09-23 08:41:14', '2026-09-26 06:50:51');

-- --------------------------------------------------------

--
-- Table structure for table `resume_versions`
--

CREATE TABLE `resume_versions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `resume_id` bigint(20) UNSIGNED NOT NULL,
  `version_number` int(10) UNSIGNED NOT NULL,
  `source` varchar(255) NOT NULL DEFAULT 'participant_edit',
  `snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`snapshot`)),
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `risks`
--

CREATE TABLE `risks` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `riskable_type` varchar(255) DEFAULT NULL,
  `riskable_id` bigint(20) UNSIGNED DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `severity` varchar(255) NOT NULL DEFAULT 'medium',
  `likelihood` varchar(255) NOT NULL DEFAULT 'medium',
  `mitigation` text DEFAULT NULL,
  `owner_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'open',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `slug`, `description`, `is_system`, `created_at`, `updated_at`) VALUES
(1, 'Super Administrator', 'super-administrator', NULL, 1, '2026-09-22 08:27:34', '2026-09-22 08:27:34'),
(2, 'Administrator', 'administrator', NULL, 1, '2026-09-22 08:27:34', '2026-09-22 08:27:34'),
(3, 'Programs Lead', 'programs-lead', NULL, 1, '2026-09-22 08:27:34', '2026-09-22 08:27:34'),
(4, 'Program Manager', 'program-manager', NULL, 1, '2026-09-22 08:27:34', '2026-09-22 08:27:34'),
(5, 'Program Officer', 'program-officer', NULL, 1, '2026-09-22 08:27:34', '2026-09-22 08:27:34'),
(6, 'M&E / MEAL Lead', 'meal-lead', NULL, 1, '2026-09-22 08:27:34', '2026-09-22 08:27:34'),
(7, 'M&E Officer', 'me-officer', NULL, 1, '2026-09-22 08:27:34', '2026-09-22 08:27:34'),
(8, 'Operations Lead', 'operations-lead', NULL, 1, '2026-09-22 08:27:34', '2026-09-22 08:27:34'),
(9, 'Operations Officer', 'operations-officer', NULL, 1, '2026-09-22 08:27:34', '2026-09-22 08:27:34'),
(10, 'Instructor / Trainer', 'instructor', NULL, 1, '2026-09-22 08:27:34', '2026-09-22 08:27:34'),
(11, 'Mentorship Coordinator', 'mentorship-coordinator', NULL, 1, '2026-09-22 08:27:34', '2026-09-22 08:27:34'),
(12, 'Career Coach', 'career-coach', NULL, 1, '2026-09-22 08:27:34', '2026-09-22 08:27:34'),
(13, 'Jobs / Placement Officer', 'placement-officer', NULL, 1, '2026-09-22 08:27:34', '2026-09-22 08:27:34'),
(14, 'Library Administrator', 'library-administrator', NULL, 1, '2026-09-22 08:27:34', '2026-09-22 08:27:34'),
(15, 'HR', 'hr', NULL, 1, '2026-09-22 08:27:34', '2026-09-22 08:27:34'),
(16, 'Procurement Officer', 'procurement-officer', NULL, 1, '2026-09-22 08:27:34', '2026-09-22 08:27:34'),
(17, 'Asset / Stores Officer', 'asset-stores-officer', NULL, 1, '2026-09-22 08:27:34', '2026-09-22 08:27:34'),
(18, 'Finance', 'finance', NULL, 1, '2026-09-22 08:27:34', '2026-09-22 08:27:34'),
(19, 'Consultant', 'consultant', NULL, 1, '2026-09-22 08:27:34', '2026-09-22 08:27:34'),
(20, 'Viewer', 'viewer', NULL, 1, '2026-09-22 08:27:34', '2026-09-22 08:27:34'),
(21, 'Student / Learner', 'student', NULL, 1, '2026-09-22 08:27:34', '2026-09-22 08:27:34'),
(22, 'Graduate / Alumni', 'alumni', NULL, 1, '2026-09-22 08:27:34', '2026-09-22 08:27:34'),
(23, 'Job Seeker', 'job-seeker', NULL, 1, '2026-09-22 08:27:34', '2026-09-22 08:27:34'),
(24, 'Mentor', 'mentor', NULL, 1, '2026-09-22 08:27:34', '2026-09-22 08:27:34'),
(25, 'Employer', 'employer', NULL, 1, '2026-09-22 08:27:34', '2026-09-22 08:27:34'),
(26, 'Super Admin', 'super-admin', 'Full system administration access', 1, '2026-09-23 15:01:13', '2026-09-23 15:01:13');

-- --------------------------------------------------------

--
-- Table structure for table `role_user`
--

CREATE TABLE `role_user` (
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_user`
--

INSERT INTO `role_user` (`role_id`, `user_id`, `created_at`, `updated_at`) VALUES
(6, 6, '2026-09-25 06:29:25', '2026-09-25 06:29:25'),
(21, 4, '2026-09-22 13:09:58', '2026-09-22 13:09:58'),
(26, 5, '2026-09-23 15:01:14', '2026-09-23 15:01:14');

-- --------------------------------------------------------

--
-- Table structure for table `saved_jobs`
--

CREATE TABLE `saved_jobs` (
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `job_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `scheduled_reminders`
--

CREATE TABLE `scheduled_reminders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `channel` varchar(255) NOT NULL DEFAULT 'in_app',
  `type` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `action_url` varchar(255) DEFAULT NULL,
  `send_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `sent_at` timestamp NULL DEFAULT NULL,
  `status` enum('pending','sent','failed','cancelled') NOT NULL DEFAULT 'pending',
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payload`)),
  `failure_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `security_events`
--

CREATE TABLE `security_events` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `event_type` varchar(255) NOT NULL,
  `severity` varchar(255) NOT NULL DEFAULT 'info',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `context` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`context`)),
  `occurred_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('Nlr2ah7Bmy4xkTLZlMMbZYCW6Wkrph3482aF7c6y', 4, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'YTo1OntzOjY6Il90b2tlbiI7czo0MDoiMzNRbnpUNm9mZHhqaG9FS0VDakllRHRYU1p1MTFCaENJUkZDRG1KOSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mzc6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9hZG1pbi9kYXNoYm9hcmQiO3M6NToicm91dGUiO3M6MTU6ImFkbWluLmRhc2hib2FyZCI7fXM6MzoidXJsIjthOjA6e31zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aTo0O30=', 1790413896);

-- --------------------------------------------------------

--
-- Table structure for table `staff_exits`
--

CREATE TABLE `staff_exits` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `exit_type` enum('resignation','end_of_contract','termination','retirement','transfer','new_organisation','other') NOT NULL,
  `notice_date` date DEFAULT NULL,
  `last_working_date` date NOT NULL,
  `reason` text DEFAULT NULL,
  `destination_organisation` varchar(255) DEFAULT NULL,
  `new_role` varchar(255) DEFAULT NULL,
  `destination_sector` varchar(255) DEFAULT NULL,
  `handover_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `status` enum('initiated','handover','clearance','access_revoke','completed','cancelled') NOT NULL DEFAULT 'initiated',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `category` varchar(255) DEFAULT NULL,
  `registration_number` varchar(255) DEFAULT NULL,
  `tin` varchar(255) DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `bank_account_name` varchar(255) DEFAULT NULL,
  `bank_account_number_encrypted` text DEFAULT NULL,
  `status` enum('pending','approved','suspended','inactive') NOT NULL DEFAULT 'pending',
  `performance_score` decimal(5,2) DEFAULT NULL,
  `performance_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `surveys`
--

CREATE TABLE `surveys` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `slug` varchar(255) NOT NULL,
  `access_type` enum('public','authenticated','course','cohort','selected') NOT NULL DEFAULT 'authenticated',
  `course_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cohort_id` bigint(20) UNSIGNED DEFAULT NULL,
  `programme_id` bigint(20) UNSIGNED DEFAULT NULL,
  `project_id` bigint(20) UNSIGNED DEFAULT NULL,
  `event_id` bigint(20) UNSIGNED DEFAULT NULL,
  `allow_draft` tinyint(1) NOT NULL DEFAULT 1,
  `anonymous_allowed` tinyint(1) NOT NULL DEFAULT 0,
  `response_limit` int(10) UNSIGNED DEFAULT NULL,
  `opens_at` timestamp NULL DEFAULT NULL,
  `closes_at` timestamp NULL DEFAULT NULL,
  `status` enum('draft','published','closed','archived') NOT NULL DEFAULT 'draft',
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `surveys`
--

INSERT INTO `surveys` (`id`, `title`, `description`, `slug`, `access_type`, `course_id`, `cohort_id`, `programme_id`, `project_id`, `event_id`, `allow_draft`, `anonymous_allowed`, `response_limit`, `opens_at`, `closes_at`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Baseline', 'Description', 'baseline-bexugf', 'public', NULL, 1, NULL, NULL, NULL, 1, 0, NULL, '2026-09-26 05:24:00', '2026-09-30 05:24:00', 'published', 5, '2026-09-26 05:24:36', '2026-09-26 05:24:36');

-- --------------------------------------------------------

--
-- Table structure for table `survey_answers`
--

CREATE TABLE `survey_answers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `survey_response_id` bigint(20) UNSIGNED NOT NULL,
  `survey_question_id` bigint(20) UNSIGNED NOT NULL,
  `answer_text` longtext DEFAULT NULL,
  `answer_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`answer_json`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `survey_assignments`
--

CREATE TABLE `survey_assignments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `survey_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `survey_questions`
--

CREATE TABLE `survey_questions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `survey_id` bigint(20) UNSIGNED NOT NULL,
  `survey_section_id` bigint(20) UNSIGNED DEFAULT NULL,
  `question_type` varchar(255) NOT NULL,
  `question_text` text NOT NULL,
  `hint` text DEFAULT NULL,
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`options`)),
  `validation_rules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`validation_rules`)),
  `conditional_logic` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`conditional_logic`)),
  `is_required` tinyint(1) NOT NULL DEFAULT 0,
  `position` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `survey_questions`
--

INSERT INTO `survey_questions` (`id`, `survey_id`, `survey_section_id`, `question_type`, `question_text`, `hint`, `options`, `validation_rules`, `conditional_logic`, `is_required`, `position`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'short_text', 'What is a computer?', NULL, NULL, NULL, NULL, 1, 1, '2026-09-26 05:26:11', '2026-09-26 05:26:11');

-- --------------------------------------------------------

--
-- Table structure for table `survey_responses`
--

CREATE TABLE `survey_responses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `survey_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `respondent_token` varchar(255) DEFAULT NULL,
  `status` enum('draft','submitted') NOT NULL DEFAULT 'draft',
  `started_at` timestamp NULL DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `survey_sections`
--

CREATE TABLE `survey_sections` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `survey_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `position` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `survey_sections`
--

INSERT INTO `survey_sections` (`id`, `survey_id`, `title`, `description`, `position`, `created_at`, `updated_at`) VALUES
(1, 1, 'Section 1', 'Section 1 Description', 1, '2026-09-26 05:25:20', '2026-09-26 05:25:20');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `group` varchar(255) NOT NULL DEFAULT 'general',
  `key` varchar(255) NOT NULL,
  `value` longtext DEFAULT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'string',
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `is_encrypted` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `group`, `key`, `value`, `type`, `is_public`, `is_encrypted`, `created_at`, `updated_at`) VALUES
(1, 'general', 'Elevate Her 360', '', 'string', 1, 0, '2026-09-24 15:47:07', '2026-09-24 15:47:07'),
(2, 'branding', 'branding.system_name', 'ElevateHer360', 'string', 1, 0, '2026-09-26 05:21:56', '2026-09-26 05:21:56'),
(3, 'branding', 'branding.short_name', 'E360', 'string', 1, 0, '2026-09-26 05:21:56', '2026-09-26 05:21:56'),
(4, 'branding', 'branding.primary_color', '#800000', 'string', 1, 0, '2026-09-26 05:21:56', '2026-09-26 05:21:56'),
(5, 'branding', 'branding.secondary_color', '#ffffff', 'string', 1, 0, '2026-09-26 05:21:56', '2026-09-26 05:21:56'),
(6, 'branding', 'branding.accent_color', '#d4af37', 'string', 1, 0, '2026-09-26 05:21:56', '2026-09-26 05:21:56'),
(7, 'branding', 'branding.font_family', 'DM Sans', 'string', 1, 0, '2026-09-26 05:21:56', '2026-09-26 05:21:56'),
(8, 'branding', 'branding.font_size', '16', 'integer', 1, 0, '2026-09-26 05:21:56', '2026-09-26 05:21:56'),
(9, 'branding', 'branding.logo_path', 'branding/6QpyEvCUaoXezmZjhAQlaBTIPHKlLziCaWCoICn7.png', 'string', 1, 0, '2026-09-26 05:21:56', '2026-09-26 05:21:56'),
(10, 'branding', 'branding.favicon_path', 'branding/5IkE1YNPxPtnP7AYGPVjsu8ZJtjUdgR2Nox8bEac.png', 'string', 1, 0, '2026-09-26 05:21:56', '2026-09-26 05:21:56');

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `activity_id` bigint(20) UNSIGNED DEFAULT NULL,
  `milestone_id` bigint(20) UNSIGNED DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `assigned_to` bigint(20) UNSIGNED DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `priority` varchar(255) NOT NULL DEFAULT 'medium',
  `status` enum('not_started','in_progress','returned_for_revision','completed','overdue') NOT NULL DEFAULT 'not_started',
  `progress_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `reminder_at` timestamp NULL DEFAULT NULL,
  `escalation_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `user_type` enum('participant','staff') NOT NULL DEFAULT 'participant',
  `status` enum('active','inactive','suspended','pending') NOT NULL DEFAULT 'pending',
  `phone` varchar(30) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `mfa_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `mfa_secret` text DEFAULT NULL,
  `mfa_recovery_codes` text DEFAULT NULL,
  `mfa_confirmed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `user_type`, `status`, `phone`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `last_login_at`, `mfa_enabled`, `mfa_secret`, `mfa_recovery_codes`, `mfa_confirmed_at`) VALUES
(4, 'Kenneth Waks', 'wakskenneth1@gmail.com', 'participant', 'active', '+256784675790', NULL, '$2y$12$Tcln9pXt6/5BE72g9GRVN.5yRbbfnJu/0F4YGyGLgfncKIxSP/duW', NULL, '2026-09-22 13:09:58', '2026-09-26 06:49:56', '2026-09-26 06:49:56', 0, NULL, NULL, NULL),
(5, 'Super Administrator', 'admin@witu.org', 'staff', 'active', NULL, '2026-09-23 15:01:14', '$2y$12$uZmZYPLu1QihXszuAaXcE./whq10DIyQNltX7gINK5PEuOMbAvJkm', '2yt4DikL6hEdJEjHaZkQdBdkv8BWkIxRcDxDGH1BxdQFWvKFUsSxSiJG9G1p', '2026-09-23 15:01:14', '2026-09-26 06:40:30', '2026-09-26 06:40:30', 0, NULL, NULL, NULL),
(6, 'Waks Kenneth', 'wakskenneth@gmail.com', 'staff', 'active', '+256784675790', '2026-09-24 14:05:59', '$2y$12$GaZoTG7J4vPW9e6/mJZLdu9a73xAD6XWsZLtwtmCHMVc3CbjNDzqy', NULL, '2026-09-24 14:05:59', '2026-09-24 14:05:59', NULL, 0, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_notifications`
--

CREATE TABLE `user_notifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `type` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `action_url` varchar(255) DEFAULT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_notifications`
--

INSERT INTO `user_notifications` (`id`, `user_id`, `type`, `title`, `message`, `action_url`, `data`, `read_at`, `created_at`, `updated_at`) VALUES
(1, 6, 'appraisal', 'Appraisal updated', 'Your appraisal is now self assessment.', '/notifications', '{\"model\":\"App\\\\Models\\\\Appraisal\",\"id\":1,\"event\":\"created\"}', NULL, '2026-09-25 06:32:41', '2026-09-25 06:32:41');

-- --------------------------------------------------------

--
-- Table structure for table `workplans`
--

CREATE TABLE `workplans` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `programme_id` bigint(20) UNSIGNED DEFAULT NULL,
  `project_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cohort_id` bigint(20) UNSIGNED DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `financial_year` varchar(255) DEFAULT NULL,
  `period_type` enum('annual','quarterly','monthly','programme','project','department','staff') NOT NULL DEFAULT 'annual',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `responsible_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('draft','submitted','under_review','approved','in_progress','on_hold','completed','cancelled') NOT NULL DEFAULT 'draft',
  `progress_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `workplans`
--

INSERT INTO `workplans` (`id`, `programme_id`, `project_id`, `cohort_id`, `title`, `financial_year`, `period_type`, `start_date`, `end_date`, `responsible_user_id`, `description`, `status`, `progress_percent`, `created_by`, `approved_by`, `approved_at`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 1, 'Workplan', '2026/27', 'annual', '2026-01-01', '2026-12-18', 5, NULL, 'submitted', 0.00, 5, NULL, NULL, '2026-09-24 13:04:19', '2026-09-24 13:04:57');

-- --------------------------------------------------------

--
-- Table structure for table `workplan_approvals`
--

CREATE TABLE `workplan_approvals` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `workplan_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `action` enum('submitted','approved','returned','rejected') NOT NULL,
  `comments` text DEFAULT NULL,
  `acted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activities`
--
ALTER TABLE `activities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `activities_workplan_id_foreign` (`workplan_id`),
  ADD KEY `activities_milestone_id_foreign` (`milestone_id`),
  ADD KEY `activities_programme_id_foreign` (`programme_id`),
  ADD KEY `activities_project_id_foreign` (`project_id`),
  ADD KEY `activities_cohort_id_foreign` (`cohort_id`),
  ADD KEY `activities_responsible_user_id_foreign` (`responsible_user_id`),
  ADD KEY `activities_activity_code_index` (`activity_code`);

--
-- Indexes for table `activity_assignments`
--
ALTER TABLE `activity_assignments`
  ADD PRIMARY KEY (`activity_id`,`user_id`),
  ADD KEY `activity_assignments_user_id_foreign` (`user_id`);

--
-- Indexes for table `activity_indicator`
--
ALTER TABLE `activity_indicator`
  ADD PRIMARY KEY (`activity_id`,`indicator_id`),
  ADD KEY `activity_indicator_indicator_id_foreign` (`indicator_id`);

--
-- Indexes for table `ai_integrations`
--
ALTER TABLE `ai_integrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ai_integrations_feature_unique` (`feature`);

--
-- Indexes for table `ai_usage_logs`
--
ALTER TABLE `ai_usage_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ai_usage_logs_user_id_foreign` (`user_id`);

--
-- Indexes for table `api_clients`
--
ALTER TABLE `api_clients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `api_clients_client_key_unique` (`client_key`);

--
-- Indexes for table `appraisals`
--
ALTER TABLE `appraisals`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `appraisals_appraisal_cycle_id_employee_id_unique` (`appraisal_cycle_id`,`employee_id`),
  ADD KEY `appraisals_employee_id_foreign` (`employee_id`),
  ADD KEY `appraisals_manager_user_id_foreign` (`manager_user_id`),
  ADD KEY `appraisals_hr_kpi_template_id_foreign` (`hr_kpi_template_id`),
  ADD KEY `appraisals_hr_finalised_by_foreign` (`hr_finalised_by`);

--
-- Indexes for table `appraisal_cycles`
--
ALTER TABLE `appraisal_cycles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `appraisal_kpi_scores`
--
ALTER TABLE `appraisal_kpi_scores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `appraisal_kpi_unique` (`appraisal_id`,`hr_kpi_template_item_id`),
  ADD KEY `appraisal_kpi_scores_hr_kpi_template_item_id_foreign` (`hr_kpi_template_item_id`);

--
-- Indexes for table `appraisal_kpi_weekly_updates`
--
ALTER TABLE `appraisal_kpi_weekly_updates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `appraisal_kpi_week_unique` (`appraisal_id`,`hr_kpi_template_item_id`,`week_number`),
  ADD KEY `appraisal_kpi_weekly_updates_hr_kpi_template_item_id_foreign` (`hr_kpi_template_item_id`);

--
-- Indexes for table `appraisal_objectives`
--
ALTER TABLE `appraisal_objectives`
  ADD PRIMARY KEY (`id`),
  ADD KEY `appraisal_objectives_appraisal_id_foreign` (`appraisal_id`),
  ADD KEY `appraisal_objectives_workplan_id_foreign` (`workplan_id`),
  ADD KEY `appraisal_objectives_milestone_id_foreign` (`milestone_id`),
  ADD KEY `appraisal_objectives_activity_id_foreign` (`activity_id`);

--
-- Indexes for table `assessments`
--
ALTER TABLE `assessments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assessments_course_id_foreign` (`course_id`),
  ADD KEY `assessments_course_module_id_foreign` (`course_module_id`);

--
-- Indexes for table `assessment_answers`
--
ALTER TABLE `assessment_answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assessment_answers_assessment_attempt_id_foreign` (`assessment_attempt_id`),
  ADD KEY `assessment_answers_assessment_question_id_foreign` (`assessment_question_id`);

--
-- Indexes for table `assessment_attempts`
--
ALTER TABLE `assessment_attempts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `assessment_attempts_assessment_id_user_id_attempt_number_unique` (`assessment_id`,`user_id`,`attempt_number`),
  ADD KEY `assessment_attempts_user_id_foreign` (`user_id`),
  ADD KEY `assessment_attempts_graded_by_foreign` (`graded_by`);

--
-- Indexes for table `assessment_questions`
--
ALTER TABLE `assessment_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assessment_questions_assessment_id_foreign` (`assessment_id`);

--
-- Indexes for table `assets`
--
ALTER TABLE `assets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `assets_asset_code_unique` (`asset_code`),
  ADD UNIQUE KEY `assets_asset_tag_unique` (`asset_tag`),
  ADD KEY `assets_asset_category_id_foreign` (`asset_category_id`),
  ADD KEY `assets_supplier_id_foreign` (`supplier_id`),
  ADD KEY `assets_purchase_order_id_foreign` (`purchase_order_id`),
  ADD KEY `assets_goods_receipt_id_foreign` (`goods_receipt_id`),
  ADD KEY `assets_programme_id_foreign` (`programme_id`),
  ADD KEY `assets_project_id_foreign` (`project_id`),
  ADD KEY `assets_custodian_user_id_foreign` (`custodian_user_id`),
  ADD KEY `assets_serial_number_index` (`serial_number`);

--
-- Indexes for table `asset_assignments`
--
ALTER TABLE `asset_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `asset_assignments_asset_id_foreign` (`asset_id`),
  ADD KEY `asset_assignments_assigned_to_user_id_foreign` (`assigned_to_user_id`),
  ADD KEY `asset_assignments_assigned_by_foreign` (`assigned_by`),
  ADD KEY `asset_assignments_received_back_by_foreign` (`received_back_by`);

--
-- Indexes for table `asset_categories`
--
ALTER TABLE `asset_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `asset_categories_name_unique` (`name`),
  ADD UNIQUE KEY `asset_categories_code_unique` (`code`);

--
-- Indexes for table `asset_disposals`
--
ALTER TABLE `asset_disposals`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `asset_disposals_asset_id_unique` (`asset_id`),
  ADD KEY `asset_disposals_requested_by_foreign` (`requested_by`),
  ADD KEY `asset_disposals_approved_by_foreign` (`approved_by`);

--
-- Indexes for table `asset_maintenance`
--
ALTER TABLE `asset_maintenance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `asset_maintenance_asset_id_foreign` (`asset_id`),
  ADD KEY `asset_maintenance_supplier_id_foreign` (`supplier_id`);

--
-- Indexes for table `asset_stocktakes`
--
ALTER TABLE `asset_stocktakes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `asset_stocktakes_conducted_by_foreign` (`conducted_by`);

--
-- Indexes for table `asset_stocktake_items`
--
ALTER TABLE `asset_stocktake_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `asset_stocktake_items_asset_stocktake_id_asset_id_unique` (`asset_stocktake_id`,`asset_id`),
  ADD KEY `asset_stocktake_items_asset_id_foreign` (`asset_id`);

--
-- Indexes for table `asset_transfers`
--
ALTER TABLE `asset_transfers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `asset_transfers_asset_id_foreign` (`asset_id`),
  ADD KEY `asset_transfers_from_user_id_foreign` (`from_user_id`),
  ADD KEY `asset_transfers_to_user_id_foreign` (`to_user_id`),
  ADD KEY `asset_transfers_approved_by_foreign` (`approved_by`);

--
-- Indexes for table `attendance_records`
--
ALTER TABLE `attendance_records`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `attendance_records_attendance_session_id_user_id_unique` (`attendance_session_id`,`user_id`),
  ADD KEY `attendance_records_user_id_foreign` (`user_id`);

--
-- Indexes for table `attendance_sessions`
--
ALTER TABLE `attendance_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `attendance_sessions_course_id_foreign` (`course_id`),
  ADD KEY `attendance_sessions_cohort_id_foreign` (`cohort_id`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `audit_logs_user_id_foreign` (`user_id`),
  ADD KEY `audit_logs_auditable_type_auditable_id_index` (`auditable_type`,`auditable_id`),
  ADD KEY `audit_logs_module_index` (`module`),
  ADD KEY `audit_logs_action_index` (`action`),
  ADD KEY `audit_logs_occurred_at_index` (`occurred_at`);

--
-- Indexes for table `branches`
--
ALTER TABLE `branches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `branches_code_unique` (`code`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_expiration_index` (`expiration`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_locks_expiration_index` (`expiration`);

--
-- Indexes for table `calendar_attendees`
--
ALTER TABLE `calendar_attendees`
  ADD PRIMARY KEY (`calendar_event_id`,`user_id`),
  ADD KEY `calendar_attendees_user_id_foreign` (`user_id`);

--
-- Indexes for table `calendar_events`
--
ALTER TABLE `calendar_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `calendar_events_eventable_type_eventable_id_index` (`eventable_type`,`eventable_id`),
  ADD KEY `calendar_events_responsible_user_id_foreign` (`responsible_user_id`),
  ADD KEY `calendar_events_programme_id_foreign` (`programme_id`),
  ADD KEY `calendar_events_project_id_foreign` (`project_id`),
  ADD KEY `calendar_events_cohort_id_foreign` (`cohort_id`),
  ADD KEY `calendar_events_event_type_index` (`event_type`);

--
-- Indexes for table `certificates`
--
ALTER TABLE `certificates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `certificates_course_id_user_id_unique` (`course_id`,`user_id`),
  ADD UNIQUE KEY `certificates_certificate_number_unique` (`certificate_number`),
  ADD UNIQUE KEY `certificates_verification_token_unique` (`verification_token`),
  ADD KEY `certificates_user_id_foreign` (`user_id`);

--
-- Indexes for table `cohorts`
--
ALTER TABLE `cohorts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cohorts_code_unique` (`code`),
  ADD KEY `cohorts_programme_id_foreign` (`programme_id`),
  ADD KEY `cohorts_project_id_foreign` (`project_id`),
  ADD KEY `cohorts_branch_id_foreign` (`branch_id`);

--
-- Indexes for table `cohort_module_releases`
--
ALTER TABLE `cohort_module_releases`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cohort_module_release_unique` (`course_module_id`,`cohort_id`),
  ADD KEY `cohort_module_releases_cohort_id_foreign` (`cohort_id`),
  ADD KEY `cohort_module_releases_released_by_foreign` (`released_by`);

--
-- Indexes for table `consents`
--
ALTER TABLE `consents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `consents_user_id_consent_type_index` (`user_id`,`consent_type`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `courses_code_unique` (`code`),
  ADD KEY `courses_programme_id_foreign` (`programme_id`),
  ADD KEY `courses_project_id_foreign` (`project_id`),
  ADD KEY `courses_branch_id_foreign` (`branch_id`),
  ADD KEY `courses_created_by_foreign` (`created_by`);

--
-- Indexes for table `course_applications`
--
ALTER TABLE `course_applications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `course_applications_course_call_id_user_id_unique` (`course_call_id`,`user_id`),
  ADD KEY `course_applications_user_id_foreign` (`user_id`),
  ADD KEY `course_applications_assessment_attempt_id_foreign` (`assessment_attempt_id`),
  ADD KEY `course_applications_reviewed_by_foreign` (`reviewed_by`);

--
-- Indexes for table `course_application_answers`
--
ALTER TABLE `course_application_answers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `course_application_answer_unique` (`course_application_id`,`course_call_question_id`),
  ADD KEY `course_application_answers_course_call_question_id_foreign` (`course_call_question_id`);

--
-- Indexes for table `course_calls`
--
ALTER TABLE `course_calls`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_calls_course_id_foreign` (`course_id`),
  ADD KEY `course_calls_programme_id_foreign` (`programme_id`),
  ADD KEY `course_calls_project_id_foreign` (`project_id`),
  ADD KEY `course_calls_cohort_id_foreign` (`cohort_id`),
  ADD KEY `course_calls_entry_assessment_id_foreign` (`entry_assessment_id`),
  ADD KEY `course_calls_created_by_foreign` (`created_by`),
  ADD KEY `course_calls_status_opens_at_closes_at_index` (`status`,`opens_at`,`closes_at`);

--
-- Indexes for table `course_call_course`
--
ALTER TABLE `course_call_course`
  ADD PRIMARY KEY (`course_call_id`,`course_id`),
  ADD KEY `course_call_course_course_id_foreign` (`course_id`);

--
-- Indexes for table `course_call_questions`
--
ALTER TABLE `course_call_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_call_questions_course_call_id_foreign` (`course_call_id`);

--
-- Indexes for table `course_cohort`
--
ALTER TABLE `course_cohort`
  ADD PRIMARY KEY (`course_id`,`cohort_id`),
  ADD KEY `course_cohort_cohort_id_foreign` (`cohort_id`);

--
-- Indexes for table `course_cohort_learning_settings`
--
ALTER TABLE `course_cohort_learning_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `course_cohort_settings_unique` (`course_id`,`cohort_id`),
  ADD KEY `course_cohort_learning_settings_cohort_id_foreign` (`cohort_id`);

--
-- Indexes for table `course_instructors`
--
ALTER TABLE `course_instructors`
  ADD PRIMARY KEY (`course_id`,`user_id`),
  ADD KEY `course_instructors_user_id_foreign` (`user_id`);

--
-- Indexes for table `course_modules`
--
ALTER TABLE `course_modules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_modules_course_id_foreign` (`course_id`);

--
-- Indexes for table `cover_letters`
--
ALTER TABLE `cover_letters`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cover_letters_user_id_foreign` (`user_id`),
  ADD KEY `cover_letters_resume_id_foreign` (`resume_id`),
  ADD KEY `cover_letters_job_id_foreign` (`job_id`);

--
-- Indexes for table `cover_letter_uploads`
--
ALTER TABLE `cover_letter_uploads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cover_letter_uploads_cover_letter_id_foreign` (`cover_letter_id`),
  ADD KEY `cover_letter_uploads_user_id_status_index` (`user_id`,`status`);

--
-- Indexes for table `deliverables`
--
ALTER TABLE `deliverables`
  ADD PRIMARY KEY (`id`),
  ADD KEY `deliverables_activity_id_foreign` (`activity_id`),
  ADD KEY `deliverables_milestone_id_foreign` (`milestone_id`),
  ADD KEY `deliverables_owner_user_id_foreign` (`owner_user_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `departments_name_unique` (`name`),
  ADD UNIQUE KEY `departments_code_unique` (`code`),
  ADD KEY `departments_head_user_id_foreign` (`head_user_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employees_user_id_unique` (`user_id`),
  ADD UNIQUE KEY `employees_employee_number_unique` (`employee_number`),
  ADD KEY `employees_department_id_foreign` (`department_id`),
  ADD KEY `employees_position_id_foreign` (`position_id`),
  ADD KEY `employees_supervisor_user_id_foreign` (`supervisor_user_id`);

--
-- Indexes for table `employee_documents`
--
ALTER TABLE `employee_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_documents_employee_id_foreign` (`employee_id`),
  ADD KEY `employee_documents_uploaded_by_foreign` (`uploaded_by`);

--
-- Indexes for table `employers`
--
ALTER TABLE `employers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employers_owner_user_id_foreign` (`owner_user_id`),
  ADD KEY `employers_approved_by_foreign` (`approved_by`);

--
-- Indexes for table `employment_contracts`
--
ALTER TABLE `employment_contracts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employment_contracts_employee_id_foreign` (`employee_id`);

--
-- Indexes for table `enrolments`
--
ALTER TABLE `enrolments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `enrolments_course_id_user_id_unique` (`course_id`,`user_id`),
  ADD KEY `enrolments_user_id_foreign` (`user_id`),
  ADD KEY `enrolments_cohort_id_foreign` (`cohort_id`),
  ADD KEY `enrolments_course_id_status_index` (`course_id`,`status`),
  ADD KEY `enrolments_source_type_index` (`source_type`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `events_checkin_token_unique` (`checkin_token`),
  ADD KEY `events_cohort_id_foreign` (`cohort_id`),
  ADD KEY `events_course_id_foreign` (`course_id`),
  ADD KEY `events_created_by_foreign` (`created_by`);

--
-- Indexes for table `event_attendance_records`
--
ALTER TABLE `event_attendance_records`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `event_attendance_registration_unique` (`event_id`,`event_registration_id`),
  ADD KEY `event_attendance_records_event_registration_id_foreign` (`event_registration_id`),
  ADD KEY `event_attendance_records_user_id_foreign` (`user_id`),
  ADD KEY `event_attendance_records_recorded_by_foreign` (`recorded_by`);

--
-- Indexes for table `event_certificates`
--
ALTER TABLE `event_certificates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `event_certificate_unique` (`event_id`,`user_id`),
  ADD UNIQUE KEY `event_certificates_certificate_code_unique` (`certificate_code`),
  ADD KEY `event_certificates_user_id_foreign` (`user_id`),
  ADD KEY `event_certificates_event_attendance_record_id_foreign` (`event_attendance_record_id`),
  ADD KEY `event_certificates_issued_by_foreign` (`issued_by`);

--
-- Indexes for table `event_feedback_responses`
--
ALTER TABLE `event_feedback_responses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `event_feedback_user_unique` (`event_id`,`user_id`),
  ADD KEY `event_feedback_responses_event_registration_id_foreign` (`event_registration_id`),
  ADD KEY `event_feedback_responses_user_id_foreign` (`user_id`);

--
-- Indexes for table `event_registrations`
--
ALTER TABLE `event_registrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `event_registrations_event_id_user_id_unique` (`event_id`,`user_id`),
  ADD KEY `event_registrations_user_id_foreign` (`user_id`);

--
-- Indexes for table `event_reminders`
--
ALTER TABLE `event_reminders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `event_reminder_unique` (`event_id`,`minutes_before`);

--
-- Indexes for table `event_reminder_deliveries`
--
ALTER TABLE `event_reminder_deliveries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `event_reminder_delivery_unique` (`event_reminder_id`,`user_id`,`channel`),
  ADD KEY `event_reminder_deliveries_user_id_foreign` (`user_id`);

--
-- Indexes for table `exit_clearances`
--
ALTER TABLE `exit_clearances`
  ADD PRIMARY KEY (`id`),
  ADD KEY `exit_clearances_staff_exit_id_foreign` (`staff_exit_id`),
  ADD KEY `exit_clearances_responsible_user_id_foreign` (`responsible_user_id`),
  ADD KEY `exit_clearances_cleared_by_foreign` (`cleared_by`);

--
-- Indexes for table `exit_interviews`
--
ALTER TABLE `exit_interviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `exit_interviews_staff_exit_id_unique` (`staff_exit_id`),
  ADD KEY `exit_interviews_conducted_by_foreign` (`conducted_by`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `goods_receipts`
--
ALTER TABLE `goods_receipts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `goods_receipts_receipt_number_unique` (`receipt_number`),
  ADD KEY `goods_receipts_purchase_order_id_foreign` (`purchase_order_id`),
  ADD KEY `goods_receipts_received_by_foreign` (`received_by`);

--
-- Indexes for table `goods_receipt_items`
--
ALTER TABLE `goods_receipt_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `goods_receipt_items_goods_receipt_id_foreign` (`goods_receipt_id`),
  ADD KEY `goods_receipt_items_purchase_order_item_id_foreign` (`purchase_order_item_id`);

--
-- Indexes for table `handover_items`
--
ALTER TABLE `handover_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `handover_items_staff_exit_id_foreign` (`staff_exit_id`),
  ADD KEY `handover_items_assigned_to_foreign` (`assigned_to`);

--
-- Indexes for table `hr_kpi_templates`
--
ALTER TABLE `hr_kpi_templates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `hr_kpi_templates_uploaded_by_foreign` (`uploaded_by`);

--
-- Indexes for table `hr_kpi_template_items`
--
ALTER TABLE `hr_kpi_template_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `hr_kpi_template_items_hr_kpi_template_id_foreign` (`hr_kpi_template_id`);

--
-- Indexes for table `indicators`
--
ALTER TABLE `indicators`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `indicators_code_unique` (`code`),
  ADD KEY `indicators_programme_id_foreign` (`programme_id`),
  ADD KEY `indicators_project_id_foreign` (`project_id`),
  ADD KEY `indicators_result_id_foreign` (`result_id`),
  ADD KEY `indicators_responsible_user_id_foreign` (`responsible_user_id`);

--
-- Indexes for table `indicator_results`
--
ALTER TABLE `indicator_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `indicator_results_indicator_id_foreign` (`indicator_id`),
  ADD KEY `indicator_results_indicator_target_id_foreign` (`indicator_target_id`),
  ADD KEY `indicator_results_entered_by_foreign` (`entered_by`),
  ADD KEY `indicator_results_verified_by_foreign` (`verified_by`);

--
-- Indexes for table `indicator_targets`
--
ALTER TABLE `indicator_targets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `indicator_targets_indicator_id_foreign` (`indicator_id`),
  ADD KEY `indicator_targets_programme_id_foreign` (`programme_id`),
  ADD KEY `indicator_targets_project_id_foreign` (`project_id`),
  ADD KEY `indicator_targets_cohort_id_foreign` (`cohort_id`),
  ADD KEY `indicator_targets_branch_id_foreign` (`branch_id`);

--
-- Indexes for table `issues`
--
ALTER TABLE `issues`
  ADD PRIMARY KEY (`id`),
  ADD KEY `issues_issueable_type_issueable_id_index` (`issueable_type`,`issueable_id`),
  ADD KEY `issues_owner_user_id_foreign` (`owner_user_id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_employer_id_foreign` (`employer_id`);

--
-- Indexes for table `job_applications`
--
ALTER TABLE `job_applications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `job_applications_job_id_user_id_unique` (`job_id`,`user_id`),
  ADD KEY `job_applications_user_id_foreign` (`user_id`),
  ADD KEY `job_applications_resume_id_foreign` (`resume_id`);

--
-- Indexes for table `job_application_status_history`
--
ALTER TABLE `job_application_status_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `job_application_status_history_job_application_id_foreign` (`job_application_id`),
  ADD KEY `job_application_status_history_changed_by_foreign` (`changed_by`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `job_interviews`
--
ALTER TABLE `job_interviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `job_interviews_job_application_id_foreign` (`job_application_id`);

--
-- Indexes for table `job_offers`
--
ALTER TABLE `job_offers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `job_offers_job_application_id_foreign` (`job_application_id`);

--
-- Indexes for table `job_recommendations`
--
ALTER TABLE `job_recommendations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `job_recommendations_user_id_job_id_unique` (`user_id`,`job_id`),
  ADD KEY `job_recommendations_job_id_foreign` (`job_id`);

--
-- Indexes for table `learning_files`
--
ALTER TABLE `learning_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `learning_files_course_id_foreign` (`course_id`),
  ADD KEY `learning_files_lesson_id_foreign` (`lesson_id`),
  ADD KEY `learning_files_uploaded_by_foreign` (`uploaded_by`);

--
-- Indexes for table `leave_balances`
--
ALTER TABLE `leave_balances`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `leave_balances_employee_id_leave_type_id_year_unique` (`employee_id`,`leave_type_id`,`year`),
  ADD KEY `leave_balances_leave_type_id_foreign` (`leave_type_id`);

--
-- Indexes for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `leave_requests_employee_id_foreign` (`employee_id`),
  ADD KEY `leave_requests_leave_type_id_foreign` (`leave_type_id`),
  ADD KEY `leave_requests_handover_user_id_foreign` (`handover_user_id`),
  ADD KEY `leave_requests_supervisor_approved_by_foreign` (`supervisor_approved_by`),
  ADD KEY `leave_requests_hr_approved_by_foreign` (`hr_approved_by`);

--
-- Indexes for table `leave_types`
--
ALTER TABLE `leave_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `leave_types_name_unique` (`name`),
  ADD UNIQUE KEY `leave_types_code_unique` (`code`);

--
-- Indexes for table `lessons`
--
ALTER TABLE `lessons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lessons_course_module_id_foreign` (`course_module_id`);

--
-- Indexes for table `lesson_progress`
--
ALTER TABLE `lesson_progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `lesson_progress_lesson_id_user_id_unique` (`lesson_id`,`user_id`),
  ADD KEY `lesson_progress_user_id_foreign` (`user_id`);

--
-- Indexes for table `library_bookmarks`
--
ALTER TABLE `library_bookmarks`
  ADD PRIMARY KEY (`user_id`,`library_resource_id`),
  ADD KEY `library_bookmarks_library_resource_id_foreign` (`library_resource_id`);

--
-- Indexes for table `library_categories`
--
ALTER TABLE `library_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `library_categories_name_unique` (`name`),
  ADD UNIQUE KEY `library_categories_slug_unique` (`slug`);

--
-- Indexes for table `library_downloads`
--
ALTER TABLE `library_downloads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `library_downloads_user_id_foreign` (`user_id`),
  ADD KEY `library_downloads_library_resource_id_foreign` (`library_resource_id`);

--
-- Indexes for table `library_resources`
--
ALTER TABLE `library_resources`
  ADD PRIMARY KEY (`id`),
  ADD KEY `library_resources_library_category_id_foreign` (`library_category_id`),
  ADD KEY `library_resources_created_by_foreign` (`created_by`);

--
-- Indexes for table `login_audits`
--
ALTER TABLE `login_audits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `login_audits_user_id_foreign` (`user_id`),
  ADD KEY `login_audits_email_index` (`email`),
  ADD KEY `login_audits_successful_index` (`successful`),
  ADD KEY `login_audits_attempted_at_index` (`attempted_at`);

--
-- Indexes for table `mentee_profiles`
--
ALTER TABLE `mentee_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `mentee_profiles_user_id_unique` (`user_id`),
  ADD KEY `mentee_profiles_programme_id_foreign` (`programme_id`),
  ADD KEY `mentee_profiles_cohort_id_foreign` (`cohort_id`);

--
-- Indexes for table `mentorship_goals`
--
ALTER TABLE `mentorship_goals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mentorship_goals_mentor_match_id_foreign` (`mentor_match_id`);

--
-- Indexes for table `mentorship_reminders`
--
ALTER TABLE `mentorship_reminders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `mentor_reminder_unique` (`mentorship_session_id`,`user_id`,`remind_at`),
  ADD KEY `mentorship_reminders_user_id_foreign` (`user_id`),
  ADD KEY `mentorship_reminders_remind_at_index` (`remind_at`);

--
-- Indexes for table `mentorship_sessions`
--
ALTER TABLE `mentorship_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mentorship_sessions_mentor_match_id_foreign` (`mentor_match_id`);

--
-- Indexes for table `mentor_matches`
--
ALTER TABLE `mentor_matches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `mentor_match_unique` (`mentor_user_id`,`mentee_user_id`,`programme_id`,`cohort_id`),
  ADD KEY `mentor_matches_mentee_user_id_foreign` (`mentee_user_id`),
  ADD KEY `mentor_matches_programme_id_foreign` (`programme_id`),
  ADD KEY `mentor_matches_cohort_id_foreign` (`cohort_id`),
  ADD KEY `mentor_matches_matched_by_foreign` (`matched_by`);

--
-- Indexes for table `mentor_profiles`
--
ALTER TABLE `mentor_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `mentor_profiles_user_id_unique` (`user_id`),
  ADD KEY `mentor_profiles_approved_by_foreign` (`approved_by`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migration_batches`
--
ALTER TABLE `migration_batches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `migration_batches_created_by_foreign` (`created_by`);

--
-- Indexes for table `migration_staging_records`
--
ALTER TABLE `migration_staging_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `migration_staging_records_matched_user_id_foreign` (`matched_user_id`),
  ADD KEY `migration_staging_records_migration_batch_id_entity_type_index` (`migration_batch_id`,`entity_type`),
  ADD KEY `migration_staging_records_entity_type_index` (`entity_type`),
  ADD KEY `migration_staging_records_match_status_index` (`match_status`);

--
-- Indexes for table `milestones`
--
ALTER TABLE `milestones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `milestones_workplan_id_foreign` (`workplan_id`),
  ADD KEY `milestones_responsible_user_id_foreign` (`responsible_user_id`);

--
-- Indexes for table `participant_outcomes`
--
ALTER TABLE `participant_outcomes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `participant_outcomes_user_id_foreign` (`user_id`),
  ADD KEY `participant_outcomes_programme_id_foreign` (`programme_id`),
  ADD KEY `participant_outcomes_cohort_id_foreign` (`cohort_id`),
  ADD KEY `participant_outcomes_verified_by_foreign` (`verified_by`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permissions_name_unique` (`name`),
  ADD UNIQUE KEY `permissions_slug_unique` (`slug`),
  ADD KEY `permissions_module_index` (`module`);

--
-- Indexes for table `permission_role`
--
ALTER TABLE `permission_role`
  ADD PRIMARY KEY (`permission_id`,`role_id`),
  ADD KEY `permission_role_role_id_foreign` (`role_id`);

--
-- Indexes for table `platform_backups`
--
ALTER TABLE `platform_backups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `platform_backups_created_by_foreign` (`created_by`);

--
-- Indexes for table `positions`
--
ALTER TABLE `positions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `positions_department_id_foreign` (`department_id`);

--
-- Indexes for table `procurement_plans`
--
ALTER TABLE `procurement_plans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `procurement_plans_programme_id_foreign` (`programme_id`),
  ADD KEY `procurement_plans_project_id_foreign` (`project_id`),
  ADD KEY `procurement_plans_workplan_id_foreign` (`workplan_id`),
  ADD KEY `procurement_plans_created_by_foreign` (`created_by`),
  ADD KEY `procurement_plans_approved_by_foreign` (`approved_by`);

--
-- Indexes for table `profiles`
--
ALTER TABLE `profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `profiles_user_id_unique` (`user_id`),
  ADD KEY `profiles_branch_id_foreign` (`branch_id`);

--
-- Indexes for table `programmes`
--
ALTER TABLE `programmes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `programmes_code_unique` (`code`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `projects_code_unique` (`code`),
  ADD KEY `projects_programme_id_foreign` (`programme_id`);

--
-- Indexes for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `purchase_orders_po_number_unique` (`po_number`),
  ADD KEY `purchase_orders_purchase_request_id_foreign` (`purchase_request_id`),
  ADD KEY `purchase_orders_supplier_id_foreign` (`supplier_id`),
  ADD KEY `purchase_orders_created_by_foreign` (`created_by`);

--
-- Indexes for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `purchase_order_items_purchase_order_id_foreign` (`purchase_order_id`),
  ADD KEY `purchase_order_items_purchase_request_item_id_foreign` (`purchase_request_item_id`);

--
-- Indexes for table `purchase_requests`
--
ALTER TABLE `purchase_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `purchase_requests_request_number_unique` (`request_number`),
  ADD KEY `purchase_requests_procurement_plan_id_foreign` (`procurement_plan_id`),
  ADD KEY `purchase_requests_programme_id_foreign` (`programme_id`),
  ADD KEY `purchase_requests_project_id_foreign` (`project_id`),
  ADD KEY `purchase_requests_workplan_id_foreign` (`workplan_id`),
  ADD KEY `purchase_requests_activity_id_foreign` (`activity_id`),
  ADD KEY `purchase_requests_requester_user_id_foreign` (`requester_user_id`);

--
-- Indexes for table `purchase_request_approvals`
--
ALTER TABLE `purchase_request_approvals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `purchase_request_approvals_purchase_request_id_foreign` (`purchase_request_id`),
  ADD KEY `purchase_request_approvals_user_id_foreign` (`user_id`);

--
-- Indexes for table `purchase_request_items`
--
ALTER TABLE `purchase_request_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `purchase_request_items_purchase_request_id_foreign` (`purchase_request_id`);

--
-- Indexes for table `queue_jobs`
--
ALTER TABLE `queue_jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `queue_jobs_queue_index` (`queue`);

--
-- Indexes for table `quotations`
--
ALTER TABLE `quotations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quotations_purchase_request_id_foreign` (`purchase_request_id`),
  ADD KEY `quotations_supplier_id_foreign` (`supplier_id`);

--
-- Indexes for table `quotation_evaluations`
--
ALTER TABLE `quotation_evaluations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quotation_evaluations_quotation_id_foreign` (`quotation_id`),
  ADD KEY `quotation_evaluations_evaluated_by_foreign` (`evaluated_by`);

--
-- Indexes for table `quotation_items`
--
ALTER TABLE `quotation_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quotation_items_quotation_id_foreign` (`quotation_id`),
  ADD KEY `quotation_items_purchase_request_item_id_foreign` (`purchase_request_item_id`);

--
-- Indexes for table `report_exports`
--
ALTER TABLE `report_exports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `report_exports_requested_by_foreign` (`requested_by`);

--
-- Indexes for table `results`
--
ALTER TABLE `results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `results_results_framework_id_foreign` (`results_framework_id`),
  ADD KEY `results_parent_id_foreign` (`parent_id`);

--
-- Indexes for table `results_frameworks`
--
ALTER TABLE `results_frameworks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `results_frameworks_programme_id_foreign` (`programme_id`),
  ADD KEY `results_frameworks_project_id_foreign` (`project_id`);

--
-- Indexes for table `resumes`
--
ALTER TABLE `resumes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `resumes_user_id_foreign` (`user_id`);

--
-- Indexes for table `resume_certifications`
--
ALTER TABLE `resume_certifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `resume_certifications_resume_id_foreign` (`resume_id`);

--
-- Indexes for table `resume_education`
--
ALTER TABLE `resume_education`
  ADD PRIMARY KEY (`id`),
  ADD KEY `resume_education_resume_id_foreign` (`resume_id`);

--
-- Indexes for table `resume_experiences`
--
ALTER TABLE `resume_experiences`
  ADD PRIMARY KEY (`id`),
  ADD KEY `resume_experiences_resume_id_foreign` (`resume_id`);

--
-- Indexes for table `resume_languages`
--
ALTER TABLE `resume_languages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `resume_languages_resume_id_foreign` (`resume_id`);

--
-- Indexes for table `resume_projects`
--
ALTER TABLE `resume_projects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `resume_projects_resume_id_foreign` (`resume_id`);

--
-- Indexes for table `resume_skills`
--
ALTER TABLE `resume_skills`
  ADD PRIMARY KEY (`id`),
  ADD KEY `resume_skills_resume_id_foreign` (`resume_id`);

--
-- Indexes for table `resume_uploads`
--
ALTER TABLE `resume_uploads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `resume_uploads_user_id_foreign` (`user_id`),
  ADD KEY `resume_uploads_resume_id_foreign` (`resume_id`);

--
-- Indexes for table `resume_versions`
--
ALTER TABLE `resume_versions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `resume_versions_resume_id_version_number_unique` (`resume_id`,`version_number`),
  ADD KEY `resume_versions_created_by_foreign` (`created_by`);

--
-- Indexes for table `risks`
--
ALTER TABLE `risks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `risks_riskable_type_riskable_id_index` (`riskable_type`,`riskable_id`),
  ADD KEY `risks_owner_user_id_foreign` (`owner_user_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_name_unique` (`name`),
  ADD UNIQUE KEY `roles_slug_unique` (`slug`);

--
-- Indexes for table `role_user`
--
ALTER TABLE `role_user`
  ADD PRIMARY KEY (`role_id`,`user_id`),
  ADD KEY `role_user_user_id_foreign` (`user_id`);

--
-- Indexes for table `saved_jobs`
--
ALTER TABLE `saved_jobs`
  ADD PRIMARY KEY (`user_id`,`job_id`),
  ADD KEY `saved_jobs_job_id_foreign` (`job_id`);

--
-- Indexes for table `scheduled_reminders`
--
ALTER TABLE `scheduled_reminders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `scheduled_reminders_user_id_foreign` (`user_id`),
  ADD KEY `scheduled_reminders_type_index` (`type`),
  ADD KEY `scheduled_reminders_send_at_index` (`send_at`),
  ADD KEY `scheduled_reminders_status_index` (`status`);

--
-- Indexes for table `security_events`
--
ALTER TABLE `security_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `security_events_user_id_foreign` (`user_id`),
  ADD KEY `security_events_event_type_index` (`event_type`),
  ADD KEY `security_events_severity_index` (`severity`),
  ADD KEY `security_events_occurred_at_index` (`occurred_at`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `staff_exits`
--
ALTER TABLE `staff_exits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `staff_exits_employee_id_foreign` (`employee_id`),
  ADD KEY `staff_exits_handover_user_id_foreign` (`handover_user_id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `surveys`
--
ALTER TABLE `surveys`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `surveys_slug_unique` (`slug`),
  ADD KEY `surveys_course_id_foreign` (`course_id`),
  ADD KEY `surveys_cohort_id_foreign` (`cohort_id`),
  ADD KEY `surveys_programme_id_foreign` (`programme_id`),
  ADD KEY `surveys_project_id_foreign` (`project_id`),
  ADD KEY `surveys_event_id_foreign` (`event_id`),
  ADD KEY `surveys_created_by_foreign` (`created_by`),
  ADD KEY `surveys_status_opens_at_closes_at_index` (`status`,`opens_at`,`closes_at`);

--
-- Indexes for table `survey_answers`
--
ALTER TABLE `survey_answers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `survey_answer_unique` (`survey_response_id`,`survey_question_id`),
  ADD KEY `survey_answers_survey_question_id_foreign` (`survey_question_id`);

--
-- Indexes for table `survey_assignments`
--
ALTER TABLE `survey_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `survey_assignments_survey_id_user_id_unique` (`survey_id`,`user_id`),
  ADD KEY `survey_assignments_user_id_foreign` (`user_id`);

--
-- Indexes for table `survey_questions`
--
ALTER TABLE `survey_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `survey_questions_survey_id_foreign` (`survey_id`),
  ADD KEY `survey_questions_survey_section_id_foreign` (`survey_section_id`);

--
-- Indexes for table `survey_responses`
--
ALTER TABLE `survey_responses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `survey_responses_user_id_foreign` (`user_id`),
  ADD KEY `survey_responses_survey_id_user_id_status_index` (`survey_id`,`user_id`,`status`),
  ADD KEY `survey_responses_respondent_token_index` (`respondent_token`);

--
-- Indexes for table `survey_sections`
--
ALTER TABLE `survey_sections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `survey_sections_survey_id_foreign` (`survey_id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `system_settings_key_unique` (`key`),
  ADD KEY `system_settings_group_index` (`group`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tasks_activity_id_foreign` (`activity_id`),
  ADD KEY `tasks_milestone_id_foreign` (`milestone_id`),
  ADD KEY `tasks_assigned_to_foreign` (`assigned_to`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD KEY `users_phone_index` (`phone`);

--
-- Indexes for table `user_notifications`
--
ALTER TABLE `user_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_notifications_user_id_read_at_index` (`user_id`,`read_at`),
  ADD KEY `user_notifications_type_index` (`type`);

--
-- Indexes for table `workplans`
--
ALTER TABLE `workplans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `workplans_programme_id_foreign` (`programme_id`),
  ADD KEY `workplans_project_id_foreign` (`project_id`),
  ADD KEY `workplans_cohort_id_foreign` (`cohort_id`),
  ADD KEY `workplans_responsible_user_id_foreign` (`responsible_user_id`),
  ADD KEY `workplans_created_by_foreign` (`created_by`),
  ADD KEY `workplans_approved_by_foreign` (`approved_by`);

--
-- Indexes for table `workplan_approvals`
--
ALTER TABLE `workplan_approvals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `workplan_approvals_workplan_id_foreign` (`workplan_id`),
  ADD KEY `workplan_approvals_user_id_foreign` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activities`
--
ALTER TABLE `activities`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ai_integrations`
--
ALTER TABLE `ai_integrations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `ai_usage_logs`
--
ALTER TABLE `ai_usage_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `api_clients`
--
ALTER TABLE `api_clients`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `appraisals`
--
ALTER TABLE `appraisals`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `appraisal_cycles`
--
ALTER TABLE `appraisal_cycles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `appraisal_kpi_scores`
--
ALTER TABLE `appraisal_kpi_scores`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `appraisal_kpi_weekly_updates`
--
ALTER TABLE `appraisal_kpi_weekly_updates`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `appraisal_objectives`
--
ALTER TABLE `appraisal_objectives`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `assessments`
--
ALTER TABLE `assessments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `assessment_answers`
--
ALTER TABLE `assessment_answers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `assessment_attempts`
--
ALTER TABLE `assessment_attempts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `assessment_questions`
--
ALTER TABLE `assessment_questions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `assets`
--
ALTER TABLE `assets`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `asset_assignments`
--
ALTER TABLE `asset_assignments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `asset_categories`
--
ALTER TABLE `asset_categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `asset_disposals`
--
ALTER TABLE `asset_disposals`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `asset_maintenance`
--
ALTER TABLE `asset_maintenance`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `asset_stocktakes`
--
ALTER TABLE `asset_stocktakes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `asset_stocktake_items`
--
ALTER TABLE `asset_stocktake_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `asset_transfers`
--
ALTER TABLE `asset_transfers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance_records`
--
ALTER TABLE `attendance_records`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance_sessions`
--
ALTER TABLE `attendance_sessions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `branches`
--
ALTER TABLE `branches`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `calendar_events`
--
ALTER TABLE `calendar_events`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `certificates`
--
ALTER TABLE `certificates`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cohorts`
--
ALTER TABLE `cohorts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `cohort_module_releases`
--
ALTER TABLE `cohort_module_releases`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `consents`
--
ALTER TABLE `consents`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `course_applications`
--
ALTER TABLE `course_applications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `course_application_answers`
--
ALTER TABLE `course_application_answers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `course_calls`
--
ALTER TABLE `course_calls`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `course_call_questions`
--
ALTER TABLE `course_call_questions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `course_cohort_learning_settings`
--
ALTER TABLE `course_cohort_learning_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `course_modules`
--
ALTER TABLE `course_modules`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `cover_letters`
--
ALTER TABLE `cover_letters`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cover_letter_uploads`
--
ALTER TABLE `cover_letter_uploads`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `deliverables`
--
ALTER TABLE `deliverables`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `employee_documents`
--
ALTER TABLE `employee_documents`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employers`
--
ALTER TABLE `employers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employment_contracts`
--
ALTER TABLE `employment_contracts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `enrolments`
--
ALTER TABLE `enrolments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `event_attendance_records`
--
ALTER TABLE `event_attendance_records`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `event_certificates`
--
ALTER TABLE `event_certificates`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `event_feedback_responses`
--
ALTER TABLE `event_feedback_responses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `event_registrations`
--
ALTER TABLE `event_registrations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `event_reminders`
--
ALTER TABLE `event_reminders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `event_reminder_deliveries`
--
ALTER TABLE `event_reminder_deliveries`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exit_clearances`
--
ALTER TABLE `exit_clearances`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exit_interviews`
--
ALTER TABLE `exit_interviews`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `goods_receipts`
--
ALTER TABLE `goods_receipts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `goods_receipt_items`
--
ALTER TABLE `goods_receipt_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `handover_items`
--
ALTER TABLE `handover_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hr_kpi_templates`
--
ALTER TABLE `hr_kpi_templates`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `hr_kpi_template_items`
--
ALTER TABLE `hr_kpi_template_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

--
-- AUTO_INCREMENT for table `indicators`
--
ALTER TABLE `indicators`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `indicator_results`
--
ALTER TABLE `indicator_results`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `indicator_targets`
--
ALTER TABLE `indicator_targets`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `issues`
--
ALTER TABLE `issues`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `job_applications`
--
ALTER TABLE `job_applications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `job_application_status_history`
--
ALTER TABLE `job_application_status_history`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `job_interviews`
--
ALTER TABLE `job_interviews`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `job_offers`
--
ALTER TABLE `job_offers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `job_recommendations`
--
ALTER TABLE `job_recommendations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `learning_files`
--
ALTER TABLE `learning_files`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leave_balances`
--
ALTER TABLE `leave_balances`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leave_requests`
--
ALTER TABLE `leave_requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leave_types`
--
ALTER TABLE `leave_types`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lessons`
--
ALTER TABLE `lessons`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `lesson_progress`
--
ALTER TABLE `lesson_progress`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `library_categories`
--
ALTER TABLE `library_categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `library_downloads`
--
ALTER TABLE `library_downloads`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `library_resources`
--
ALTER TABLE `library_resources`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login_audits`
--
ALTER TABLE `login_audits`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mentee_profiles`
--
ALTER TABLE `mentee_profiles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mentorship_goals`
--
ALTER TABLE `mentorship_goals`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mentorship_reminders`
--
ALTER TABLE `mentorship_reminders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mentorship_sessions`
--
ALTER TABLE `mentorship_sessions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mentor_matches`
--
ALTER TABLE `mentor_matches`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mentor_profiles`
--
ALTER TABLE `mentor_profiles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `migration_batches`
--
ALTER TABLE `migration_batches`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migration_staging_records`
--
ALTER TABLE `migration_staging_records`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `milestones`
--
ALTER TABLE `milestones`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `participant_outcomes`
--
ALTER TABLE `participant_outcomes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT for table `platform_backups`
--
ALTER TABLE `platform_backups`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `positions`
--
ALTER TABLE `positions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `procurement_plans`
--
ALTER TABLE `procurement_plans`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `profiles`
--
ALTER TABLE `profiles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `programmes`
--
ALTER TABLE `programmes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_requests`
--
ALTER TABLE `purchase_requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_request_approvals`
--
ALTER TABLE `purchase_request_approvals`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_request_items`
--
ALTER TABLE `purchase_request_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `queue_jobs`
--
ALTER TABLE `queue_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `quotations`
--
ALTER TABLE `quotations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quotation_evaluations`
--
ALTER TABLE `quotation_evaluations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quotation_items`
--
ALTER TABLE `quotation_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `report_exports`
--
ALTER TABLE `report_exports`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `results`
--
ALTER TABLE `results`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `results_frameworks`
--
ALTER TABLE `results_frameworks`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `resumes`
--
ALTER TABLE `resumes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `resume_certifications`
--
ALTER TABLE `resume_certifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `resume_education`
--
ALTER TABLE `resume_education`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `resume_experiences`
--
ALTER TABLE `resume_experiences`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `resume_languages`
--
ALTER TABLE `resume_languages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `resume_projects`
--
ALTER TABLE `resume_projects`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `resume_skills`
--
ALTER TABLE `resume_skills`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `resume_uploads`
--
ALTER TABLE `resume_uploads`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `resume_versions`
--
ALTER TABLE `resume_versions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `risks`
--
ALTER TABLE `risks`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `scheduled_reminders`
--
ALTER TABLE `scheduled_reminders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `security_events`
--
ALTER TABLE `security_events`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_exits`
--
ALTER TABLE `staff_exits`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `surveys`
--
ALTER TABLE `surveys`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `survey_answers`
--
ALTER TABLE `survey_answers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `survey_assignments`
--
ALTER TABLE `survey_assignments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `survey_questions`
--
ALTER TABLE `survey_questions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `survey_responses`
--
ALTER TABLE `survey_responses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `survey_sections`
--
ALTER TABLE `survey_sections`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `user_notifications`
--
ALTER TABLE `user_notifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `workplans`
--
ALTER TABLE `workplans`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `workplan_approvals`
--
ALTER TABLE `workplan_approvals`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activities`
--
ALTER TABLE `activities`
  ADD CONSTRAINT `activities_cohort_id_foreign` FOREIGN KEY (`cohort_id`) REFERENCES `cohorts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `activities_milestone_id_foreign` FOREIGN KEY (`milestone_id`) REFERENCES `milestones` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `activities_programme_id_foreign` FOREIGN KEY (`programme_id`) REFERENCES `programmes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `activities_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `activities_responsible_user_id_foreign` FOREIGN KEY (`responsible_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `activities_workplan_id_foreign` FOREIGN KEY (`workplan_id`) REFERENCES `workplans` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `activity_assignments`
--
ALTER TABLE `activity_assignments`
  ADD CONSTRAINT `activity_assignments_activity_id_foreign` FOREIGN KEY (`activity_id`) REFERENCES `activities` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `activity_assignments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `activity_indicator`
--
ALTER TABLE `activity_indicator`
  ADD CONSTRAINT `activity_indicator_activity_id_foreign` FOREIGN KEY (`activity_id`) REFERENCES `activities` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `activity_indicator_indicator_id_foreign` FOREIGN KEY (`indicator_id`) REFERENCES `indicators` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ai_usage_logs`
--
ALTER TABLE `ai_usage_logs`
  ADD CONSTRAINT `ai_usage_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `appraisals`
--
ALTER TABLE `appraisals`
  ADD CONSTRAINT `appraisals_appraisal_cycle_id_foreign` FOREIGN KEY (`appraisal_cycle_id`) REFERENCES `appraisal_cycles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appraisals_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appraisals_hr_finalised_by_foreign` FOREIGN KEY (`hr_finalised_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `appraisals_hr_kpi_template_id_foreign` FOREIGN KEY (`hr_kpi_template_id`) REFERENCES `hr_kpi_templates` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `appraisals_manager_user_id_foreign` FOREIGN KEY (`manager_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `appraisal_kpi_scores`
--
ALTER TABLE `appraisal_kpi_scores`
  ADD CONSTRAINT `appraisal_kpi_scores_appraisal_id_foreign` FOREIGN KEY (`appraisal_id`) REFERENCES `appraisals` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appraisal_kpi_scores_hr_kpi_template_item_id_foreign` FOREIGN KEY (`hr_kpi_template_item_id`) REFERENCES `hr_kpi_template_items` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `appraisal_kpi_weekly_updates`
--
ALTER TABLE `appraisal_kpi_weekly_updates`
  ADD CONSTRAINT `appraisal_kpi_weekly_updates_appraisal_id_foreign` FOREIGN KEY (`appraisal_id`) REFERENCES `appraisals` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appraisal_kpi_weekly_updates_hr_kpi_template_item_id_foreign` FOREIGN KEY (`hr_kpi_template_item_id`) REFERENCES `hr_kpi_template_items` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `appraisal_objectives`
--
ALTER TABLE `appraisal_objectives`
  ADD CONSTRAINT `appraisal_objectives_activity_id_foreign` FOREIGN KEY (`activity_id`) REFERENCES `activities` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `appraisal_objectives_appraisal_id_foreign` FOREIGN KEY (`appraisal_id`) REFERENCES `appraisals` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appraisal_objectives_milestone_id_foreign` FOREIGN KEY (`milestone_id`) REFERENCES `milestones` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `appraisal_objectives_workplan_id_foreign` FOREIGN KEY (`workplan_id`) REFERENCES `workplans` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `assessments`
--
ALTER TABLE `assessments`
  ADD CONSTRAINT `assessments_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assessments_course_module_id_foreign` FOREIGN KEY (`course_module_id`) REFERENCES `course_modules` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `assessment_answers`
--
ALTER TABLE `assessment_answers`
  ADD CONSTRAINT `assessment_answers_assessment_attempt_id_foreign` FOREIGN KEY (`assessment_attempt_id`) REFERENCES `assessment_attempts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assessment_answers_assessment_question_id_foreign` FOREIGN KEY (`assessment_question_id`) REFERENCES `assessment_questions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `assessment_attempts`
--
ALTER TABLE `assessment_attempts`
  ADD CONSTRAINT `assessment_attempts_assessment_id_foreign` FOREIGN KEY (`assessment_id`) REFERENCES `assessments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assessment_attempts_graded_by_foreign` FOREIGN KEY (`graded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `assessment_attempts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `assessment_questions`
--
ALTER TABLE `assessment_questions`
  ADD CONSTRAINT `assessment_questions_assessment_id_foreign` FOREIGN KEY (`assessment_id`) REFERENCES `assessments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `assets`
--
ALTER TABLE `assets`
  ADD CONSTRAINT `assets_asset_category_id_foreign` FOREIGN KEY (`asset_category_id`) REFERENCES `asset_categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `assets_custodian_user_id_foreign` FOREIGN KEY (`custodian_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `assets_goods_receipt_id_foreign` FOREIGN KEY (`goods_receipt_id`) REFERENCES `goods_receipts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `assets_programme_id_foreign` FOREIGN KEY (`programme_id`) REFERENCES `programmes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `assets_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `assets_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `assets_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `asset_assignments`
--
ALTER TABLE `asset_assignments`
  ADD CONSTRAINT `asset_assignments_asset_id_foreign` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `asset_assignments_assigned_by_foreign` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `asset_assignments_assigned_to_user_id_foreign` FOREIGN KEY (`assigned_to_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `asset_assignments_received_back_by_foreign` FOREIGN KEY (`received_back_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `asset_disposals`
--
ALTER TABLE `asset_disposals`
  ADD CONSTRAINT `asset_disposals_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `asset_disposals_asset_id_foreign` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `asset_disposals_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `asset_maintenance`
--
ALTER TABLE `asset_maintenance`
  ADD CONSTRAINT `asset_maintenance_asset_id_foreign` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `asset_maintenance_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `asset_stocktakes`
--
ALTER TABLE `asset_stocktakes`
  ADD CONSTRAINT `asset_stocktakes_conducted_by_foreign` FOREIGN KEY (`conducted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `asset_stocktake_items`
--
ALTER TABLE `asset_stocktake_items`
  ADD CONSTRAINT `asset_stocktake_items_asset_id_foreign` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `asset_stocktake_items_asset_stocktake_id_foreign` FOREIGN KEY (`asset_stocktake_id`) REFERENCES `asset_stocktakes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `asset_transfers`
--
ALTER TABLE `asset_transfers`
  ADD CONSTRAINT `asset_transfers_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `asset_transfers_asset_id_foreign` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `asset_transfers_from_user_id_foreign` FOREIGN KEY (`from_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `asset_transfers_to_user_id_foreign` FOREIGN KEY (`to_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `attendance_records`
--
ALTER TABLE `attendance_records`
  ADD CONSTRAINT `attendance_records_attendance_session_id_foreign` FOREIGN KEY (`attendance_session_id`) REFERENCES `attendance_sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_records_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `attendance_sessions`
--
ALTER TABLE `attendance_sessions`
  ADD CONSTRAINT `attendance_sessions_cohort_id_foreign` FOREIGN KEY (`cohort_id`) REFERENCES `cohorts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `attendance_sessions_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `calendar_attendees`
--
ALTER TABLE `calendar_attendees`
  ADD CONSTRAINT `calendar_attendees_calendar_event_id_foreign` FOREIGN KEY (`calendar_event_id`) REFERENCES `calendar_events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `calendar_attendees_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `calendar_events`
--
ALTER TABLE `calendar_events`
  ADD CONSTRAINT `calendar_events_cohort_id_foreign` FOREIGN KEY (`cohort_id`) REFERENCES `cohorts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `calendar_events_programme_id_foreign` FOREIGN KEY (`programme_id`) REFERENCES `programmes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `calendar_events_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `calendar_events_responsible_user_id_foreign` FOREIGN KEY (`responsible_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `certificates`
--
ALTER TABLE `certificates`
  ADD CONSTRAINT `certificates_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `certificates_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cohorts`
--
ALTER TABLE `cohorts`
  ADD CONSTRAINT `cohorts_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `cohorts_programme_id_foreign` FOREIGN KEY (`programme_id`) REFERENCES `programmes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `cohorts_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `cohort_module_releases`
--
ALTER TABLE `cohort_module_releases`
  ADD CONSTRAINT `cohort_module_releases_cohort_id_foreign` FOREIGN KEY (`cohort_id`) REFERENCES `cohorts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cohort_module_releases_course_module_id_foreign` FOREIGN KEY (`course_module_id`) REFERENCES `course_modules` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cohort_module_releases_released_by_foreign` FOREIGN KEY (`released_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `consents`
--
ALTER TABLE `consents`
  ADD CONSTRAINT `consents_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `courses_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `courses_programme_id_foreign` FOREIGN KEY (`programme_id`) REFERENCES `programmes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `courses_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `course_applications`
--
ALTER TABLE `course_applications`
  ADD CONSTRAINT `course_applications_assessment_attempt_id_foreign` FOREIGN KEY (`assessment_attempt_id`) REFERENCES `assessment_attempts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `course_applications_course_call_id_foreign` FOREIGN KEY (`course_call_id`) REFERENCES `course_calls` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `course_applications_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `course_applications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `course_application_answers`
--
ALTER TABLE `course_application_answers`
  ADD CONSTRAINT `course_application_answers_course_application_id_foreign` FOREIGN KEY (`course_application_id`) REFERENCES `course_applications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `course_application_answers_course_call_question_id_foreign` FOREIGN KEY (`course_call_question_id`) REFERENCES `course_call_questions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `course_calls`
--
ALTER TABLE `course_calls`
  ADD CONSTRAINT `course_calls_cohort_id_foreign` FOREIGN KEY (`cohort_id`) REFERENCES `cohorts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `course_calls_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `course_calls_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `course_calls_entry_assessment_id_foreign` FOREIGN KEY (`entry_assessment_id`) REFERENCES `assessments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `course_calls_programme_id_foreign` FOREIGN KEY (`programme_id`) REFERENCES `programmes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `course_calls_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `course_call_course`
--
ALTER TABLE `course_call_course`
  ADD CONSTRAINT `course_call_course_course_call_id_foreign` FOREIGN KEY (`course_call_id`) REFERENCES `course_calls` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `course_call_course_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `course_call_questions`
--
ALTER TABLE `course_call_questions`
  ADD CONSTRAINT `course_call_questions_course_call_id_foreign` FOREIGN KEY (`course_call_id`) REFERENCES `course_calls` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `course_cohort`
--
ALTER TABLE `course_cohort`
  ADD CONSTRAINT `course_cohort_cohort_id_foreign` FOREIGN KEY (`cohort_id`) REFERENCES `cohorts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `course_cohort_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `course_cohort_learning_settings`
--
ALTER TABLE `course_cohort_learning_settings`
  ADD CONSTRAINT `course_cohort_learning_settings_cohort_id_foreign` FOREIGN KEY (`cohort_id`) REFERENCES `cohorts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `course_cohort_learning_settings_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `course_instructors`
--
ALTER TABLE `course_instructors`
  ADD CONSTRAINT `course_instructors_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `course_instructors_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `course_modules`
--
ALTER TABLE `course_modules`
  ADD CONSTRAINT `course_modules_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cover_letters`
--
ALTER TABLE `cover_letters`
  ADD CONSTRAINT `cover_letters_job_id_foreign` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `cover_letters_resume_id_foreign` FOREIGN KEY (`resume_id`) REFERENCES `resumes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `cover_letters_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cover_letter_uploads`
--
ALTER TABLE `cover_letter_uploads`
  ADD CONSTRAINT `cover_letter_uploads_cover_letter_id_foreign` FOREIGN KEY (`cover_letter_id`) REFERENCES `cover_letters` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `cover_letter_uploads_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `deliverables`
--
ALTER TABLE `deliverables`
  ADD CONSTRAINT `deliverables_activity_id_foreign` FOREIGN KEY (`activity_id`) REFERENCES `activities` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `deliverables_milestone_id_foreign` FOREIGN KEY (`milestone_id`) REFERENCES `milestones` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `deliverables_owner_user_id_foreign` FOREIGN KEY (`owner_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `departments`
--
ALTER TABLE `departments`
  ADD CONSTRAINT `departments_head_user_id_foreign` FOREIGN KEY (`head_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `employees_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `employees_position_id_foreign` FOREIGN KEY (`position_id`) REFERENCES `positions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `employees_supervisor_user_id_foreign` FOREIGN KEY (`supervisor_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `employees_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_documents`
--
ALTER TABLE `employee_documents`
  ADD CONSTRAINT `employee_documents_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employee_documents_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `employers`
--
ALTER TABLE `employers`
  ADD CONSTRAINT `employers_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `employers_owner_user_id_foreign` FOREIGN KEY (`owner_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `employment_contracts`
--
ALTER TABLE `employment_contracts`
  ADD CONSTRAINT `employment_contracts_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `enrolments`
--
ALTER TABLE `enrolments`
  ADD CONSTRAINT `enrolments_cohort_id_foreign` FOREIGN KEY (`cohort_id`) REFERENCES `cohorts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `enrolments_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `enrolments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_cohort_id_foreign` FOREIGN KEY (`cohort_id`) REFERENCES `cohorts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `events_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `events_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `event_attendance_records`
--
ALTER TABLE `event_attendance_records`
  ADD CONSTRAINT `event_attendance_records_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_attendance_records_event_registration_id_foreign` FOREIGN KEY (`event_registration_id`) REFERENCES `event_registrations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `event_attendance_records_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `event_attendance_records_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `event_certificates`
--
ALTER TABLE `event_certificates`
  ADD CONSTRAINT `event_certificates_event_attendance_record_id_foreign` FOREIGN KEY (`event_attendance_record_id`) REFERENCES `event_attendance_records` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `event_certificates_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_certificates_issued_by_foreign` FOREIGN KEY (`issued_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `event_certificates_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `event_feedback_responses`
--
ALTER TABLE `event_feedback_responses`
  ADD CONSTRAINT `event_feedback_responses_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_feedback_responses_event_registration_id_foreign` FOREIGN KEY (`event_registration_id`) REFERENCES `event_registrations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `event_feedback_responses_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `event_registrations`
--
ALTER TABLE `event_registrations`
  ADD CONSTRAINT `event_registrations_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_registrations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `event_reminders`
--
ALTER TABLE `event_reminders`
  ADD CONSTRAINT `event_reminders_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `event_reminder_deliveries`
--
ALTER TABLE `event_reminder_deliveries`
  ADD CONSTRAINT `event_reminder_deliveries_event_reminder_id_foreign` FOREIGN KEY (`event_reminder_id`) REFERENCES `event_reminders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_reminder_deliveries_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `exit_clearances`
--
ALTER TABLE `exit_clearances`
  ADD CONSTRAINT `exit_clearances_cleared_by_foreign` FOREIGN KEY (`cleared_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `exit_clearances_responsible_user_id_foreign` FOREIGN KEY (`responsible_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `exit_clearances_staff_exit_id_foreign` FOREIGN KEY (`staff_exit_id`) REFERENCES `staff_exits` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `exit_interviews`
--
ALTER TABLE `exit_interviews`
  ADD CONSTRAINT `exit_interviews_conducted_by_foreign` FOREIGN KEY (`conducted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `exit_interviews_staff_exit_id_foreign` FOREIGN KEY (`staff_exit_id`) REFERENCES `staff_exits` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `goods_receipts`
--
ALTER TABLE `goods_receipts`
  ADD CONSTRAINT `goods_receipts_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `goods_receipts_received_by_foreign` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `goods_receipt_items`
--
ALTER TABLE `goods_receipt_items`
  ADD CONSTRAINT `goods_receipt_items_goods_receipt_id_foreign` FOREIGN KEY (`goods_receipt_id`) REFERENCES `goods_receipts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `goods_receipt_items_purchase_order_item_id_foreign` FOREIGN KEY (`purchase_order_item_id`) REFERENCES `purchase_order_items` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `handover_items`
--
ALTER TABLE `handover_items`
  ADD CONSTRAINT `handover_items_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `handover_items_staff_exit_id_foreign` FOREIGN KEY (`staff_exit_id`) REFERENCES `staff_exits` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `hr_kpi_templates`
--
ALTER TABLE `hr_kpi_templates`
  ADD CONSTRAINT `hr_kpi_templates_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `hr_kpi_template_items`
--
ALTER TABLE `hr_kpi_template_items`
  ADD CONSTRAINT `hr_kpi_template_items_hr_kpi_template_id_foreign` FOREIGN KEY (`hr_kpi_template_id`) REFERENCES `hr_kpi_templates` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `indicators`
--
ALTER TABLE `indicators`
  ADD CONSTRAINT `indicators_programme_id_foreign` FOREIGN KEY (`programme_id`) REFERENCES `programmes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `indicators_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `indicators_responsible_user_id_foreign` FOREIGN KEY (`responsible_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `indicators_result_id_foreign` FOREIGN KEY (`result_id`) REFERENCES `results` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `indicator_results`
--
ALTER TABLE `indicator_results`
  ADD CONSTRAINT `indicator_results_entered_by_foreign` FOREIGN KEY (`entered_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `indicator_results_indicator_id_foreign` FOREIGN KEY (`indicator_id`) REFERENCES `indicators` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `indicator_results_indicator_target_id_foreign` FOREIGN KEY (`indicator_target_id`) REFERENCES `indicator_targets` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `indicator_results_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `indicator_targets`
--
ALTER TABLE `indicator_targets`
  ADD CONSTRAINT `indicator_targets_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `indicator_targets_cohort_id_foreign` FOREIGN KEY (`cohort_id`) REFERENCES `cohorts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `indicator_targets_indicator_id_foreign` FOREIGN KEY (`indicator_id`) REFERENCES `indicators` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `indicator_targets_programme_id_foreign` FOREIGN KEY (`programme_id`) REFERENCES `programmes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `indicator_targets_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `issues`
--
ALTER TABLE `issues`
  ADD CONSTRAINT `issues_owner_user_id_foreign` FOREIGN KEY (`owner_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `jobs`
--
ALTER TABLE `jobs`
  ADD CONSTRAINT `jobs_employer_id_foreign` FOREIGN KEY (`employer_id`) REFERENCES `employers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `job_applications`
--
ALTER TABLE `job_applications`
  ADD CONSTRAINT `job_applications_job_id_foreign` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `job_applications_resume_id_foreign` FOREIGN KEY (`resume_id`) REFERENCES `resumes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `job_applications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `job_application_status_history`
--
ALTER TABLE `job_application_status_history`
  ADD CONSTRAINT `job_application_status_history_changed_by_foreign` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `job_application_status_history_job_application_id_foreign` FOREIGN KEY (`job_application_id`) REFERENCES `job_applications` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `job_interviews`
--
ALTER TABLE `job_interviews`
  ADD CONSTRAINT `job_interviews_job_application_id_foreign` FOREIGN KEY (`job_application_id`) REFERENCES `job_applications` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `job_offers`
--
ALTER TABLE `job_offers`
  ADD CONSTRAINT `job_offers_job_application_id_foreign` FOREIGN KEY (`job_application_id`) REFERENCES `job_applications` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `job_recommendations`
--
ALTER TABLE `job_recommendations`
  ADD CONSTRAINT `job_recommendations_job_id_foreign` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `job_recommendations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `learning_files`
--
ALTER TABLE `learning_files`
  ADD CONSTRAINT `learning_files_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `learning_files_lesson_id_foreign` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `learning_files_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `leave_balances`
--
ALTER TABLE `leave_balances`
  ADD CONSTRAINT `leave_balances_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `leave_balances_leave_type_id_foreign` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD CONSTRAINT `leave_requests_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `leave_requests_handover_user_id_foreign` FOREIGN KEY (`handover_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `leave_requests_hr_approved_by_foreign` FOREIGN KEY (`hr_approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `leave_requests_leave_type_id_foreign` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `leave_requests_supervisor_approved_by_foreign` FOREIGN KEY (`supervisor_approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `lessons`
--
ALTER TABLE `lessons`
  ADD CONSTRAINT `lessons_course_module_id_foreign` FOREIGN KEY (`course_module_id`) REFERENCES `course_modules` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lesson_progress`
--
ALTER TABLE `lesson_progress`
  ADD CONSTRAINT `lesson_progress_lesson_id_foreign` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lesson_progress_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `library_bookmarks`
--
ALTER TABLE `library_bookmarks`
  ADD CONSTRAINT `library_bookmarks_library_resource_id_foreign` FOREIGN KEY (`library_resource_id`) REFERENCES `library_resources` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `library_bookmarks_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `library_downloads`
--
ALTER TABLE `library_downloads`
  ADD CONSTRAINT `library_downloads_library_resource_id_foreign` FOREIGN KEY (`library_resource_id`) REFERENCES `library_resources` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `library_downloads_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `library_resources`
--
ALTER TABLE `library_resources`
  ADD CONSTRAINT `library_resources_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `library_resources_library_category_id_foreign` FOREIGN KEY (`library_category_id`) REFERENCES `library_categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `login_audits`
--
ALTER TABLE `login_audits`
  ADD CONSTRAINT `login_audits_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `mentee_profiles`
--
ALTER TABLE `mentee_profiles`
  ADD CONSTRAINT `mentee_profiles_cohort_id_foreign` FOREIGN KEY (`cohort_id`) REFERENCES `cohorts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `mentee_profiles_programme_id_foreign` FOREIGN KEY (`programme_id`) REFERENCES `programmes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `mentee_profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `mentorship_goals`
--
ALTER TABLE `mentorship_goals`
  ADD CONSTRAINT `mentorship_goals_mentor_match_id_foreign` FOREIGN KEY (`mentor_match_id`) REFERENCES `mentor_matches` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `mentorship_reminders`
--
ALTER TABLE `mentorship_reminders`
  ADD CONSTRAINT `mentorship_reminders_mentorship_session_id_foreign` FOREIGN KEY (`mentorship_session_id`) REFERENCES `mentorship_sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mentorship_reminders_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `mentorship_sessions`
--
ALTER TABLE `mentorship_sessions`
  ADD CONSTRAINT `mentorship_sessions_mentor_match_id_foreign` FOREIGN KEY (`mentor_match_id`) REFERENCES `mentor_matches` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `mentor_matches`
--
ALTER TABLE `mentor_matches`
  ADD CONSTRAINT `mentor_matches_cohort_id_foreign` FOREIGN KEY (`cohort_id`) REFERENCES `cohorts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `mentor_matches_matched_by_foreign` FOREIGN KEY (`matched_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `mentor_matches_mentee_user_id_foreign` FOREIGN KEY (`mentee_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mentor_matches_mentor_user_id_foreign` FOREIGN KEY (`mentor_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mentor_matches_programme_id_foreign` FOREIGN KEY (`programme_id`) REFERENCES `programmes` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `mentor_profiles`
--
ALTER TABLE `mentor_profiles`
  ADD CONSTRAINT `mentor_profiles_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `mentor_profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `migration_batches`
--
ALTER TABLE `migration_batches`
  ADD CONSTRAINT `migration_batches_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `migration_staging_records`
--
ALTER TABLE `migration_staging_records`
  ADD CONSTRAINT `migration_staging_records_matched_user_id_foreign` FOREIGN KEY (`matched_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `migration_staging_records_migration_batch_id_foreign` FOREIGN KEY (`migration_batch_id`) REFERENCES `migration_batches` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `milestones`
--
ALTER TABLE `milestones`
  ADD CONSTRAINT `milestones_responsible_user_id_foreign` FOREIGN KEY (`responsible_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `milestones_workplan_id_foreign` FOREIGN KEY (`workplan_id`) REFERENCES `workplans` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `participant_outcomes`
--
ALTER TABLE `participant_outcomes`
  ADD CONSTRAINT `participant_outcomes_cohort_id_foreign` FOREIGN KEY (`cohort_id`) REFERENCES `cohorts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `participant_outcomes_programme_id_foreign` FOREIGN KEY (`programme_id`) REFERENCES `programmes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `participant_outcomes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `participant_outcomes_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `permission_role`
--
ALTER TABLE `permission_role`
  ADD CONSTRAINT `permission_role_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `permission_role_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `platform_backups`
--
ALTER TABLE `platform_backups`
  ADD CONSTRAINT `platform_backups_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `positions`
--
ALTER TABLE `positions`
  ADD CONSTRAINT `positions_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `procurement_plans`
--
ALTER TABLE `procurement_plans`
  ADD CONSTRAINT `procurement_plans_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `procurement_plans_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `procurement_plans_programme_id_foreign` FOREIGN KEY (`programme_id`) REFERENCES `programmes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `procurement_plans_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `procurement_plans_workplan_id_foreign` FOREIGN KEY (`workplan_id`) REFERENCES `workplans` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `profiles`
--
ALTER TABLE `profiles`
  ADD CONSTRAINT `profiles_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `projects`
--
ALTER TABLE `projects`
  ADD CONSTRAINT `projects_programme_id_foreign` FOREIGN KEY (`programme_id`) REFERENCES `programmes` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD CONSTRAINT `purchase_orders_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `purchase_orders_purchase_request_id_foreign` FOREIGN KEY (`purchase_request_id`) REFERENCES `purchase_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_orders_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`);

--
-- Constraints for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD CONSTRAINT `purchase_order_items_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_order_items_purchase_request_item_id_foreign` FOREIGN KEY (`purchase_request_item_id`) REFERENCES `purchase_request_items` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `purchase_requests`
--
ALTER TABLE `purchase_requests`
  ADD CONSTRAINT `purchase_requests_activity_id_foreign` FOREIGN KEY (`activity_id`) REFERENCES `activities` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `purchase_requests_procurement_plan_id_foreign` FOREIGN KEY (`procurement_plan_id`) REFERENCES `procurement_plans` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `purchase_requests_programme_id_foreign` FOREIGN KEY (`programme_id`) REFERENCES `programmes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `purchase_requests_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `purchase_requests_requester_user_id_foreign` FOREIGN KEY (`requester_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_requests_workplan_id_foreign` FOREIGN KEY (`workplan_id`) REFERENCES `workplans` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `purchase_request_approvals`
--
ALTER TABLE `purchase_request_approvals`
  ADD CONSTRAINT `purchase_request_approvals_purchase_request_id_foreign` FOREIGN KEY (`purchase_request_id`) REFERENCES `purchase_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_request_approvals_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `purchase_request_items`
--
ALTER TABLE `purchase_request_items`
  ADD CONSTRAINT `purchase_request_items_purchase_request_id_foreign` FOREIGN KEY (`purchase_request_id`) REFERENCES `purchase_requests` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `quotations`
--
ALTER TABLE `quotations`
  ADD CONSTRAINT `quotations_purchase_request_id_foreign` FOREIGN KEY (`purchase_request_id`) REFERENCES `purchase_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `quotations_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `quotation_evaluations`
--
ALTER TABLE `quotation_evaluations`
  ADD CONSTRAINT `quotation_evaluations_evaluated_by_foreign` FOREIGN KEY (`evaluated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `quotation_evaluations_quotation_id_foreign` FOREIGN KEY (`quotation_id`) REFERENCES `quotations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `quotation_items`
--
ALTER TABLE `quotation_items`
  ADD CONSTRAINT `quotation_items_purchase_request_item_id_foreign` FOREIGN KEY (`purchase_request_item_id`) REFERENCES `purchase_request_items` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `quotation_items_quotation_id_foreign` FOREIGN KEY (`quotation_id`) REFERENCES `quotations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `report_exports`
--
ALTER TABLE `report_exports`
  ADD CONSTRAINT `report_exports_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `results`
--
ALTER TABLE `results`
  ADD CONSTRAINT `results_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `results` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `results_results_framework_id_foreign` FOREIGN KEY (`results_framework_id`) REFERENCES `results_frameworks` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `results_frameworks`
--
ALTER TABLE `results_frameworks`
  ADD CONSTRAINT `results_frameworks_programme_id_foreign` FOREIGN KEY (`programme_id`) REFERENCES `programmes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `results_frameworks_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `resumes`
--
ALTER TABLE `resumes`
  ADD CONSTRAINT `resumes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `resume_certifications`
--
ALTER TABLE `resume_certifications`
  ADD CONSTRAINT `resume_certifications_resume_id_foreign` FOREIGN KEY (`resume_id`) REFERENCES `resumes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `resume_education`
--
ALTER TABLE `resume_education`
  ADD CONSTRAINT `resume_education_resume_id_foreign` FOREIGN KEY (`resume_id`) REFERENCES `resumes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `resume_experiences`
--
ALTER TABLE `resume_experiences`
  ADD CONSTRAINT `resume_experiences_resume_id_foreign` FOREIGN KEY (`resume_id`) REFERENCES `resumes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `resume_languages`
--
ALTER TABLE `resume_languages`
  ADD CONSTRAINT `resume_languages_resume_id_foreign` FOREIGN KEY (`resume_id`) REFERENCES `resumes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `resume_projects`
--
ALTER TABLE `resume_projects`
  ADD CONSTRAINT `resume_projects_resume_id_foreign` FOREIGN KEY (`resume_id`) REFERENCES `resumes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `resume_skills`
--
ALTER TABLE `resume_skills`
  ADD CONSTRAINT `resume_skills_resume_id_foreign` FOREIGN KEY (`resume_id`) REFERENCES `resumes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `resume_uploads`
--
ALTER TABLE `resume_uploads`
  ADD CONSTRAINT `resume_uploads_resume_id_foreign` FOREIGN KEY (`resume_id`) REFERENCES `resumes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `resume_uploads_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `resume_versions`
--
ALTER TABLE `resume_versions`
  ADD CONSTRAINT `resume_versions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `resume_versions_resume_id_foreign` FOREIGN KEY (`resume_id`) REFERENCES `resumes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `risks`
--
ALTER TABLE `risks`
  ADD CONSTRAINT `risks_owner_user_id_foreign` FOREIGN KEY (`owner_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `role_user`
--
ALTER TABLE `role_user`
  ADD CONSTRAINT `role_user_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `saved_jobs`
--
ALTER TABLE `saved_jobs`
  ADD CONSTRAINT `saved_jobs_job_id_foreign` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `saved_jobs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `scheduled_reminders`
--
ALTER TABLE `scheduled_reminders`
  ADD CONSTRAINT `scheduled_reminders_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `security_events`
--
ALTER TABLE `security_events`
  ADD CONSTRAINT `security_events_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `staff_exits`
--
ALTER TABLE `staff_exits`
  ADD CONSTRAINT `staff_exits_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `staff_exits_handover_user_id_foreign` FOREIGN KEY (`handover_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `surveys`
--
ALTER TABLE `surveys`
  ADD CONSTRAINT `surveys_cohort_id_foreign` FOREIGN KEY (`cohort_id`) REFERENCES `cohorts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `surveys_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `surveys_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `surveys_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `surveys_programme_id_foreign` FOREIGN KEY (`programme_id`) REFERENCES `programmes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `surveys_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `survey_answers`
--
ALTER TABLE `survey_answers`
  ADD CONSTRAINT `survey_answers_survey_question_id_foreign` FOREIGN KEY (`survey_question_id`) REFERENCES `survey_questions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `survey_answers_survey_response_id_foreign` FOREIGN KEY (`survey_response_id`) REFERENCES `survey_responses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `survey_assignments`
--
ALTER TABLE `survey_assignments`
  ADD CONSTRAINT `survey_assignments_survey_id_foreign` FOREIGN KEY (`survey_id`) REFERENCES `surveys` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `survey_assignments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `survey_questions`
--
ALTER TABLE `survey_questions`
  ADD CONSTRAINT `survey_questions_survey_id_foreign` FOREIGN KEY (`survey_id`) REFERENCES `surveys` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `survey_questions_survey_section_id_foreign` FOREIGN KEY (`survey_section_id`) REFERENCES `survey_sections` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `survey_responses`
--
ALTER TABLE `survey_responses`
  ADD CONSTRAINT `survey_responses_survey_id_foreign` FOREIGN KEY (`survey_id`) REFERENCES `surveys` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `survey_responses_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `survey_sections`
--
ALTER TABLE `survey_sections`
  ADD CONSTRAINT `survey_sections_survey_id_foreign` FOREIGN KEY (`survey_id`) REFERENCES `surveys` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_activity_id_foreign` FOREIGN KEY (`activity_id`) REFERENCES `activities` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tasks_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tasks_milestone_id_foreign` FOREIGN KEY (`milestone_id`) REFERENCES `milestones` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_notifications`
--
ALTER TABLE `user_notifications`
  ADD CONSTRAINT `user_notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `workplans`
--
ALTER TABLE `workplans`
  ADD CONSTRAINT `workplans_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `workplans_cohort_id_foreign` FOREIGN KEY (`cohort_id`) REFERENCES `cohorts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `workplans_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `workplans_programme_id_foreign` FOREIGN KEY (`programme_id`) REFERENCES `programmes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `workplans_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `workplans_responsible_user_id_foreign` FOREIGN KEY (`responsible_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `workplan_approvals`
--
ALTER TABLE `workplan_approvals`
  ADD CONSTRAINT `workplan_approvals_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `workplan_approvals_workplan_id_foreign` FOREIGN KEY (`workplan_id`) REFERENCES `workplans` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
