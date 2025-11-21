-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主機： 127.0.0.1
-- 產生時間： 2025-11-21 09:38:32
-- 伺服器版本： 10.4.32-MariaDB
-- PHP 版本： 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 資料庫： `topics_good`
--

-- --------------------------------------------------------

--
-- 資料表結構 `activity_records`
--

CREATE TABLE `activity_records` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `activity_date` date NOT NULL,
  `teacher_unit` varchar(255) NOT NULL,
  `teacher_name` varchar(255) NOT NULL,
  `school_name` varchar(255) NOT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `activity_type` varchar(100) NOT NULL,
  `activity_time` varchar(50) NOT NULL,
  `participants` text DEFAULT NULL,
  `activity_feedback` text DEFAULT NULL,
  `suggestion` text DEFAULT NULL,
  `uploaded_files` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`uploaded_files`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `activity_records`
--

INSERT INTO `activity_records` (`id`, `teacher_id`, `activity_date`, `teacher_unit`, `teacher_name`, `school_name`, `contact_person`, `contact_phone`, `activity_type`, `activity_time`, `participants`, `activity_feedback`, `suggestion`, `uploaded_files`, `created_at`) VALUES
(4, 13, '2025-10-07', '資訊管理科', '周建羽', '東湖國中', '', '', '來校體驗', '上班日', '國中九年級,國中八年級', '反應熱絡、詢問度高,反應冷淡', '', NULL, '2025-10-03 08:26:29'),
(5, 13, '2025-10-07', '資訊管理科', '周建羽', '東湖國中', '', '', '來校體驗', '上班日', '國中八年級', '願意參與小活動', '', NULL, '2025-10-07 05:55:26'),
(6, 14, '2025-10-07', '企業管理學系', '嚴竹華', '明湖國中', '', '', '講座分享', '上班日', '國中八年級', '願意參與小活動,願意追蹤FB、IG', '學生不受控很吵', NULL, '2025-10-07 06:03:32'),
(7, 16, '2025-10-07', '應用外語系', '英英美', '西松高中', '', '', '來校體驗', '假日', '國中八年級,國中七年級', '願意參與小活動,願意追蹤FB、IG', '學生真的很吵我想辭職了', NULL, '2025-10-07 07:30:15'),
(8, 18, '2025-10-08', '資訊管理科', '李岳倫', '南港國中', '', '', '校外參訪', '上班日', '國中九年級', '反應冷淡', '', NULL, '2025-10-08 09:33:00'),
(9, 13, '2025-10-09', '資訊管理科', '周建羽', '南港國中', '', '', '校外參訪', '上班日', '國中八年級', '反應冷淡', '學生真的很吵', NULL, '2025-10-09 05:52:02'),
(10, 16, '2025-10-15', '應用外語科', '英英美', '永吉國中', '無', '0900000000', '來校體驗', '上班日', '國中九年級', '反應熱絡、詢問度高', '無', '[\"uploads\\/1760318620_0_FACINGHD-NGNLZERO-VINYLE_1800x.webp\"]', '2025-10-13 01:23:35'),
(11, 16, '2025-10-13', '應用外語科', '英英美', '東湖國中', '', '', '校外參訪', '上班日', '國中九年級', '願意參與小活動,願意加入LINE', '學生真的很吵', NULL, '2025-10-13 01:44:34'),
(12, 14, '2025-10-22', '資訊管理科', '嚴竹華', '永吉國中', '', '', '校外參訪', '假日', '國中八年級', '反應冷淡', '學生好吵真的好吵', NULL, '2025-10-22 02:01:08'),
(13, 21, '2025-11-17', '資訊管理科', '1', '123', '', '', '來校體驗', '上班日', '國中八年級', '反應冷淡', '活動很無聊', NULL, '2025-11-17 02:14:38'),
(14, 21, '2025-11-17', '資訊管理科', '1', 'ss', '', '', '來校體驗', '上班日', '國中八年級,高中一年級', '反應冷淡', '', '[\"uploads\\/1763345849_0_4Prompt.pdf\",\"uploads\\/1763345849_1_-1.jpg\",\"uploads\\/1763345849_2_GoogleAIStudioGeminiUKN.pdf\"]', '2025-11-17 02:17:29');

-- --------------------------------------------------------

--
-- 資料表結構 `admission_applications`
--

CREATE TABLE `admission_applications` (
  `id` int(11) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `school_name` varchar(255) DEFAULT NULL,
  `student_name` varchar(255) DEFAULT NULL,
  `grade` varchar(50) DEFAULT NULL,
  `parent_name` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(50) DEFAULT NULL,
  `line_id` varchar(255) DEFAULT NULL,
  `session_id` int(11) DEFAULT NULL,
  `session_choice` varchar(255) DEFAULT NULL,
  `course_priority_1` varchar(255) DEFAULT NULL,
  `course_priority_2` varchar(255) DEFAULT NULL,
  `receive_info` tinyint(1) DEFAULT 0 COMMENT '是否願意收到升學訊息（0=否，不願意，1=是，願意）',
  `email_sent` tinyint(1) DEFAULT 0,
  `email_sent_at` timestamp NULL DEFAULT NULL,
  `reminder_sent` tinyint(1) DEFAULT 0,
  `reminder_sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `admission_applications`
--

INSERT INTO `admission_applications` (`id`, `email`, `school_name`, `student_name`, `grade`, `parent_name`, `contact_phone`, `line_id`, `session_id`, `session_choice`, `course_priority_1`, `course_priority_2`, `receive_info`, `email_sent`, `email_sent_at`, `reminder_sent`, `reminder_sent_at`, `created_at`) VALUES
(12, '110534201@stu.ukn.edu.tw', '永吉國中', 'iiii34', '123', '狂三34', '0900000000', '0', 3, '114年10月03日', '', '', 0, 1, '2025-10-03 02:33:39', 1, '2025-10-03 02:37:24', '2025-10-03 02:33:35'),
(13, '110534201@stu.ukn.edu.tw', '永吉國中', 'iiii35', '123', '狂三35', '0900000000', '0', 1, '114年10月04日', '5', '', 0, 1, '2025-10-03 02:39:45', 1, '2025-10-03 02:39:48', '2025-10-03 02:39:41'),
(14, '110534225@stu.ukn.edu.tw', 'ukn', '123', '114', '123', '0909090909', '123', 1, '114年10月04日', '5', '', 0, 1, '2025-10-03 02:43:53', 1, '2025-10-03 02:43:56', '2025-10-03 02:43:50'),
(15, '110534201@stu.ukn.edu.tw', '永吉國中', 'iiii35', '123', '狂三35', '0900000000', '0', 2, '1008', '', '', 0, 1, '2025-10-03 03:05:16', 1, '2025-10-03 03:05:19', '2025-10-03 03:05:12'),
(16, 'aa@dd.com', 'aa', 'aa', '九年級', 'aaa', '0909090909', '0', 2, '114年10月03日', '', '', 0, 1, '2025-10-03 06:42:08', 1, '2025-10-03 06:42:11', '2025-10-03 06:42:02'),
(17, 'dkldkllk@gmail.com', 's', 's', '九年級', 's', '0904848484', '0', 3, '114年10月03日', '資訊管理科', '', 0, 1, '2025-10-08 00:41:51', 0, NULL, '2025-10-08 00:41:40'),
(18, '110534236@stu.ukn.edu.tw', '永吉', '其', '九年級', '屋', '0900000000', '0', 3, '114年10月03日', '資訊管理科', '企業管理科', 0, 1, '2025-10-08 01:42:48', 1, '2025-10-08 01:42:58', '2025-10-08 01:42:41'),
(19, '1008@gmail.com', 'a', 'a', '九年級', 'a', '0904848484', '0', 2, '114年10月03日', '資訊管理科', '企業管理科', 0, 1, '2025-10-08 02:16:45', 0, NULL, '2025-10-08 02:16:30'),
(20, '1008@gmail.com', 'a', 'a', '九年級', 'a', '0904848484', '0', 2, '1008', '資訊管理科', '企業管理科', 0, 1, '2025-10-08 02:20:04', 1, '2025-10-08 02:20:08', '2025-10-08 02:19:57'),
(21, '1008@gmia.com', 's', 'q', '八年級', 'qq', '0988888888', '0', 2, '1008', '視光科', '數位影視動畫科', 0, 1, '2025-10-08 02:21:05', 1, '2025-10-08 02:21:08', '2025-10-08 02:21:01'),
(22, '110534201@stu.ukn.edu.tw', '永吉國中', '狂三', '九年級', '時崎狂三', '0900000000', '0', 3, '114年10月03日', '資訊管理科', '', 0, 1, '2025-10-14 06:34:39', 1, '2025-10-14 06:34:43', '2025-10-14 06:34:34'),
(24, '110534236@stu.ukn.edu.tw', '東湖國中', '林奕廷', '八年級', '林奕廷', '0968444555', '0', 1, '114年10月04日', '資訊管理科', '應用外語科', 0, 1, '2025-10-26 08:22:03', 0, NULL, '2025-10-26 08:21:59'),
(25, '110534201@stu.ukn.edu.tw', '永吉國中', '狂三', '九年級', '時崎狂三', '0900000000', '0', 1, '114年10月04日', '資訊管理科', '', 0, 1, '2025-10-27 02:32:31', 0, NULL, '2025-10-27 02:32:25'),
(26, '15@f.c', '1', '1', '九年級', '2', '0909090900', '22', 1, '114年10月04日', '', '', 0, 1, '2025-11-12 05:42:36', 0, NULL, '2025-11-12 05:42:33'),
(27, 'a@gmail.com', 'a', 'a', '九年級', '23', '0909090909', '0', 4, '10月15日', '護理科', '', 0, 1, '2025-11-19 01:29:28', 0, NULL, '2025-11-19 01:29:24'),
(28, 'q@tahoo.com', 'q', 's', '九年級', 'qqq', '0909090909', '0', 2, '1008', '護理科', '', 0, 1, '2025-11-19 01:30:29', 0, NULL, '2025-11-19 01:30:25');

-- --------------------------------------------------------

--
-- 資料表結構 `admission_courses`
--

CREATE TABLE `admission_courses` (
  `id` int(11) NOT NULL,
  `course_name` varchar(255) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `admission_courses`
--

INSERT INTO `admission_courses` (`id`, `course_name`, `department_id`, `is_active`, `sort_order`) VALUES
(1, '資訊管理科', NULL, 1, 0),
(2, '企業管理科', NULL, 1, 0),
(3, '護理科', NULL, 1, 0),
(4, '幼保科', NULL, 1, 2),
(5, '應用外語科', NULL, 1, 0),
(6, '視光科', NULL, 1, 0),
(7, '數位影視動畫科', NULL, 1, 1);

-- --------------------------------------------------------

--
-- 資料表結構 `admission_departments`
--

CREATE TABLE `admission_departments` (
  `id` int(11) NOT NULL,
  `department_name` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `admission_grades`
--

CREATE TABLE `admission_grades` (
  `id` int(11) NOT NULL,
  `grade_name` varchar(50) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `admission_grades`
--

INSERT INTO `admission_grades` (`id`, `grade_name`, `is_active`, `sort_order`) VALUES
(1, '九年級', 1, 0),
(2, '八年級', 1, 0),
(3, '七年級', 1, 0);

-- --------------------------------------------------------

--
-- 資料表結構 `admission_recommendations`
--

CREATE TABLE `admission_recommendations` (
  `id` int(11) NOT NULL,
  `recommender_name` varchar(100) NOT NULL COMMENT '推薦人姓名',
  `recommender_student_id` varchar(20) NOT NULL COMMENT '推薦人學號',
  `recommender_grade` varchar(10) NOT NULL COMMENT '推薦人年級',
  `recommender_department` varchar(50) NOT NULL COMMENT '推薦人科系',
  `recommender_phone` varchar(20) NOT NULL COMMENT '推薦人聯絡電話',
  `recommender_email` varchar(100) NOT NULL COMMENT '推薦人電子郵件',
  `student_name` varchar(100) NOT NULL COMMENT '被推薦學生姓名',
  `student_school` varchar(100) NOT NULL COMMENT '被推薦學生學校',
  `student_grade` varchar(10) NOT NULL COMMENT '被推薦學生年級',
  `student_phone` varchar(20) NOT NULL COMMENT '被推薦學生聯絡電話',
  `student_email` varchar(100) NOT NULL COMMENT '被推薦學生電子郵件',
  `student_line_id` varchar(50) DEFAULT NULL COMMENT '被推薦學生LINE ID',
  `recommendation_reason` text NOT NULL COMMENT '推薦理由',
  `student_interest` varchar(100) DEFAULT NULL COMMENT '學生興趣領域',
  `additional_info` text DEFAULT NULL COMMENT '其他補充資訊',
  `status` enum('pending','contacted','registered','rejected') DEFAULT 'pending' COMMENT '處理狀態',
  `enrollment_status` enum('未入學','已入學','放棄入學') DEFAULT '未入學' COMMENT '入學狀態',
  `admin_notes` text DEFAULT NULL COMMENT '管理員備註',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT '建立時間',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新時間',
  `proof_evidence` varchar(255) DEFAULT NULL COMMENT '證明文件路徑（LINE對話截圖或新生照片等）'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='招生推薦報名表';

--
-- 傾印資料表的資料 `admission_recommendations`
--

INSERT INTO `admission_recommendations` (`id`, `recommender_name`, `recommender_student_id`, `recommender_grade`, `recommender_department`, `recommender_phone`, `recommender_email`, `student_name`, `student_school`, `student_grade`, `student_phone`, `student_email`, `student_line_id`, `recommendation_reason`, `student_interest`, `additional_info`, `status`, `enrollment_status`, `admin_notes`, `created_at`, `updated_at`, `proof_evidence`) VALUES
(8, '無名', '110534201', '四年級', '視光科', '0900000000', '110534201@stu.ukn.edu.tw', '狂三', '永吉國中', '七年級', '0900000000', '', '', 'jj6', '不限定', 'j6', 'pending', '未入學', NULL, '2025-10-14 06:52:10', '2025-10-14 06:52:10', ''),
(9, '心有戚戚焉', '110534201', '五年級', '護理科', '0900000000', '110534201@stu.ukn.edu.tw', 'fsdf2', '永吉國中', '七年級', '0900000000', '', '', 'j6\r\n', '視光科', 'j6\r\n', 'pending', '未入學', NULL, '2025-10-14 06:56:29', '2025-10-14 06:56:29', ''),
(10, '心有戚戚焉', '110534202', '五年級', '護理科', '0900000000', '110534201@stu.ukn.edu.tw', 'fsdf2', '永吉國中', '七年級', '0900000000', '', '', 'j6\r\n', '視光科', 'j6\r\n', 'pending', '未入學', NULL, '2025-10-14 07:00:48', '2025-10-14 07:00:48', 'uploads/proof_evidence/68edf52083014_1760425248.jpg'),
(11, '無名', '110534210', '一年級', '幼保科', '0900000000', '110534201@stu.ukn.edu.tw', '狂三', '永吉國中', '八年級', '0900000000', '110534201@stu.ukn.edu.tw', '', '無', '不限定', '', 'pending', '未入學', NULL, '2025-10-15 06:39:39', '2025-10-15 06:39:39', 'uploads/proof_evidence/68ef41aaac10d_1760510378.jpg'),
(12, '無名', '110534210', '一年級', '幼保科', '0900000000', '110534201@stu.ukn.edu.tw', '狂三', '永吉國中', '八年級', '0900000000', '110534201@stu.ukn.edu.tw', '', '無', '不限定', '', 'pending', '未入學', NULL, '2025-10-15 06:39:45', '2025-10-15 06:39:45', 'uploads/proof_evidence/68ef41b0d8393_1760510384.jpg'),
(13, '林奕廷', '110534236', '五年級', '資訊管理科', '0987887878', '110534236@stu.ukn.edu.tw', '張莉翎', '明湖國中', '七年級', '0947587568', '', '', '呃感覺很適合', '護理科', '', 'pending', '未入學', NULL, '2025-10-17 06:52:22', '2025-10-17 06:52:22', ''),
(14, '11', '11', '三年級', '護理科', '0909099009', 'ww@l.c', 'w', 'w', '七年級', '09', '', '', 'w', '', '', 'pending', '未入學', NULL, '2025-11-21 02:03:59', '2025-11-21 02:03:59', ''),
(15, '111', '111', '三年級', '企業管理科', '00', '4@g.d', '11', '1', '', '1', '', '', '1', '企業管理科', '1212', 'pending', '未入學', NULL, '2025-11-21 02:09:10', '2025-11-21 02:09:10', 'uploads/proof_evidence/691fc9c6dc872_1763690950.jpg');

-- --------------------------------------------------------

--
-- 資料表結構 `admission_sessions`
--

CREATE TABLE `admission_sessions` (
  `id` int(11) NOT NULL,
  `session_name` varchar(255) NOT NULL,
  `session_date` date NOT NULL,
  `session_type` varchar(50) NOT NULL,
  `max_participants` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `admission_sessions`
--

INSERT INTO `admission_sessions` (`id`, `session_name`, `session_date`, `session_type`, `max_participants`, `description`, `is_active`, `created_at`) VALUES
(1, '114年10月04日', '2025-10-04', '實體', 10, '帶你了解資訊管理科', 1, '2025-10-02 10:13:51'),
(2, '1008', '2025-10-08', '實體', 15, '帶你了解視光科', 1, '2025-10-02 10:13:51'),
(3, '114年10月03日', '2025-10-09', '實體', 5, '帶你了解幼保科', 1, '2025-10-03 10:13:51'),
(4, '10月15日', '2025-10-15', '實體', 2, '帶你了解幼保科', 1, '2025-10-14 06:33:50'),
(5, '12121121', '2025-11-13', '11212', 1212, '12', 1, '2025-11-12 06:38:28');

-- --------------------------------------------------------

--
-- 資料表結構 `ai_chat_history`
--

CREATE TABLE `ai_chat_history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message_type` enum('user','ai') NOT NULL,
  `message_content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `application_statuses`
--

CREATE TABLE `application_statuses` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL COMMENT '狀態代碼',
  `name` varchar(100) NOT NULL COMMENT '狀態名稱',
  `description` text DEFAULT NULL COMMENT '狀態描述',
  `display_order` int(11) DEFAULT 0 COMMENT '顯示順序',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='申請狀態資料表';

--
-- 傾印資料表的資料 `application_statuses`
--

INSERT INTO `application_statuses` (`id`, `code`, `name`, `description`, `display_order`, `created_at`) VALUES
(1, 'PENDING', '待處理', NULL, 1, '2025-10-31 05:50:24'),
(2, 'CONTACTED', '已聯絡', NULL, 2, '2025-10-31 05:50:24'),
(3, 'ENROLLED', '已入學', NULL, 3, '2025-10-31 05:50:24'),
(4, 'APPROVED', '已核准', NULL, 4, '2025-10-31 05:50:24'),
(5, 'REJECTED', '已拒絕', NULL, 5, '2025-10-31 05:50:24');

-- --------------------------------------------------------

--
-- 資料表結構 `assignment_logs`
--

CREATE TABLE `assignment_logs` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `assigned_by` varchar(255) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `assignment_logs`
--

INSERT INTO `assignment_logs` (`id`, `student_id`, `teacher_id`, `assigned_by`, `assigned_at`) VALUES
(1, 5, 13, 'IMD', '2025-10-28 08:14:40'),
(2, 5, 18, 'IMD', '2025-10-28 08:43:31'),
(3, 7, 14, 'IMD', '2025-10-28 10:30:25'),
(4, 8, 18, 'IMD', '2025-10-29 01:18:14'),
(5, 9, 13, 'IMD', '2025-10-29 07:53:50'),
(6, 10, 18, 'IMD', '2025-10-31 00:59:18'),
(7, 10, 18, 'IMD', '2025-10-31 01:06:42');

-- --------------------------------------------------------

--
-- 資料表結構 `chat_groups_normalized`
--

CREATE TABLE `chat_groups_normalized` (
  `id` int(11) NOT NULL,
  `group_name` varchar(255) NOT NULL,
  `created_by_user_id` int(11) NOT NULL COMMENT '關聯到 user 表',
  `department_id` int(11) DEFAULT NULL COMMENT '關聯到 departments 表',
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='正規化後的聊天群組表';

-- --------------------------------------------------------

--
-- 替換檢視表以便查看 `chat_groups_view`
-- (請參考以下實際畫面)
--
CREATE TABLE `chat_groups_view` (
`id` int(11)
,`group_name` varchar(255)
,`created_by` varchar(255)
,`department` varchar(255)
,`description` text
,`created_at` timestamp
);

-- --------------------------------------------------------

--
-- 資料表結構 `chat_history`
--

CREATE TABLE `chat_history` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `role` varchar(50) DEFAULT '用戶',
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `continued_admission`
--

CREATE TABLE `continued_admission` (
  `id` int(11) NOT NULL,
  `apply_no` varchar(20) DEFAULT NULL COMMENT '報名編號（由系統自動生成）',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT '報名時間',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '最後更新時間',
  `exam_no` varchar(20) NOT NULL COMMENT '准考證號碼',
  `name` varchar(50) NOT NULL COMMENT '姓名',
  `id_number` varchar(10) NOT NULL COMMENT '身分證字號',
  `birth_year` int(11) NOT NULL COMMENT '出生年',
  `birth_month` int(11) NOT NULL COMMENT '出生月',
  `birth_day` int(11) NOT NULL COMMENT '出生日',
  `gender` enum('male','female') NOT NULL COMMENT '性別',
  `phone` varchar(20) DEFAULT NULL COMMENT '室內電話',
  `mobile` varchar(20) NOT NULL COMMENT '行動電話',
  `school_city` varchar(20) NOT NULL COMMENT '就讀縣市',
  `school_name` varchar(100) NOT NULL COMMENT '就讀國中',
  `zip_code` varchar(5) DEFAULT NULL COMMENT '郵遞區號',
  `city` varchar(20) NOT NULL COMMENT '縣/市',
  `district` varchar(20) NOT NULL COMMENT '市/區/鄉/鎮',
  `village` varchar(20) DEFAULT NULL COMMENT '村/里',
  `neighbor` varchar(10) DEFAULT NULL COMMENT '鄰',
  `road` varchar(50) NOT NULL COMMENT '路(街)',
  `section` varchar(10) DEFAULT NULL COMMENT '段',
  `lane` varchar(10) DEFAULT NULL COMMENT '巷',
  `alley` varchar(10) DEFAULT NULL COMMENT '弄',
  `house_no` varchar(10) NOT NULL COMMENT '號',
  `floor` varchar(20) DEFAULT NULL COMMENT '樓之',
  `same_address` tinyint(1) DEFAULT 0 COMMENT '通訊地址是否同戶籍地址',
  `contact_address` text DEFAULT NULL COMMENT '通訊地址（若與戶籍地址不同）',
  `guardian_name` varchar(50) NOT NULL COMMENT '監護人姓名',
  `guardian_phone` varchar(20) DEFAULT NULL COMMENT '監護人室內電話',
  `guardian_mobile` varchar(20) NOT NULL COMMENT '監護人行動電話',
  `documents` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '繳驗資料清單和文件路徑' CHECK (json_valid(`documents`)),
  `self_intro` text NOT NULL COMMENT '自傳/自我介紹',
  `skills` text NOT NULL COMMENT '興趣/專長',
  `choices` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '志願序（包含科系和優先順序）' CHECK (json_valid(`choices`)),
  `status` enum('pending','approved','rejected','waitlist') DEFAULT 'pending' COMMENT '審核狀態：pending=待審核, approved=錄取, rejected=不錄取, waitlist=備取',
  `reviewer_id` int(11) DEFAULT NULL COMMENT '審核老師ID',
  `review_notes` text DEFAULT NULL COMMENT '審核備註',
  `reviewed_at` timestamp NULL DEFAULT NULL COMMENT '審核時間'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='114年國中教育會考報名表';

--
-- 傾印資料表的資料 `continued_admission`
--

INSERT INTO `continued_admission` (`id`, `apply_no`, `created_at`, `updated_at`, `exam_no`, `name`, `id_number`, `birth_year`, `birth_month`, `birth_day`, `gender`, `phone`, `mobile`, `school_city`, `school_name`, `zip_code`, `city`, `district`, `village`, `neighbor`, `road`, `section`, `lane`, `alley`, `house_no`, `floor`, `same_address`, `contact_address`, `guardian_name`, `guardian_phone`, `guardian_mobile`, `documents`, `self_intro`, `skills`, `choices`, `status`, `reviewer_id`, `review_notes`, `reviewed_at`) VALUES
(13, NULL, '2025-10-02 02:49:48', '2025-10-17 01:15:47', 'afasfafas', '無法無天', 'Y098763399', 1990, 1, 1, 'male', '', '0900000000', '台北市', '康寧大學', '10041', '台北市', '中山區', '', '', '', '一段', '', '', '23號', '', 1, '', '無', '', '0900000000', NULL, 'asdfasf', 'asfasf', '[]', 'approved', NULL, '33', '2025-10-17 01:15:47'),
(15, NULL, '2025-10-02 02:56:11', '2025-10-15 06:04:14', '000000', '我愛羅', 'T000000000', 1990, 1, 1, 'female', '', '0900000', '台北市', '永吉國中', '10041', '台北市', '中正區', '', '', '八德路', '一段', '', '', '23號', '', 1, '', '恩恩', '', '0900000000', NULL, 'afasfas', 'qfdssv', '[]', 'approved', NULL, '資料缺漏', '2025-10-02 03:32:00'),
(17, NULL, '2025-10-02 03:11:34', '2025-10-16 05:48:09', '000000', '焉婷', 'U000101010', 1990, 1, 1, 'female', '', '0988765555', '台北市', '永吉國中', '10041', '台北市', '中正區', '', '', '八德路', '一段', '', '', '23號', '', 1, '', '嗚嗚', '', '0900000000', NULL, 'fdaf', 'afda', '[]', 'approved', NULL, '', '2025-10-16 05:48:09'),
(18, NULL, '2025-10-02 03:16:57', '2025-10-16 03:00:12', '9977667', '汪', 'Y102345678', 1990, 1, 1, 'female', '', '0900000000', '台北市', '康寧大學', '10041', '台北市', '', '', '', '八德路', '一段', '', '', '23號', '', 1, '', '凹凹', '', '0900000000', NULL, 'fssfsfs', 'afasf', '[]', 'approved', 0, '55', '2025-10-16 03:00:12'),
(19, NULL, '2025-10-02 03:23:14', '2025-10-15 06:43:07', '9283284', '蔡', 'U123456789', 1990, 1, 1, 'female', '', '0900000000', '台北市', '康寧大學', '10041', '台北市', '', '', '', '八德路', '一段', '', '', '23', '', 1, '', '超級瑪莉', '', '00999999999', '[{\"type\":\"exam\",\"filename\":\"MV5BOTk5ZDZhNGUtMDM2OS00Y2RkLWEwMmQtODg4ZTZiMGY1ZjFjXkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg\",\"path\":\"uploads\\/continued_admission\\/1759375395_68ddf023c84cf_MV5BOTk5ZDZhNGUtMDM2OS00Y2RkLWEwMmQtODg4ZTZiMGY1ZjFjXkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg\"},{\"type\":\"skill\",\"filename\":\"cB.jpg\",\"path\":\"uploads\\/continued_admission\\/1759375395_68ddf023c8f1f_cB.jpg\"}]', 'adsfasf', 'afdafda', '[]', 'approved', 2, '22', '2025-10-05 06:42:27'),
(20, NULL, '2025-10-15 05:38:10', '2025-10-16 03:26:58', '12345678', '謝丙醇', 'A123456789', 1994, 1, 12, 'male', '02-12345678', '0912334567', '台北市', '中崙', '115', '台北市', '南港區', '玉成里', '16', '松河街', '', '', '', '300', '一樓之一', 1, '', '謝秉吉吉', '02-12345678', '0912334567', '[{\"type\":\"exam\",\"filename\":\"110534225.pdf\",\"path\":\"uploads\\/continued_admission\\/1760585218_68f06602c4b36_110534225.pdf\"}]', '', '', '[\"動畫科\"]', 'waitlist', NULL, NULL, NULL),
(21, NULL, '2025-10-15 06:19:14', '2025-10-16 02:52:36', '110534201', '狂三三', 'A123456788', 1990, 1, 1, 'female', '0900000000', '0900000000', '台北市', '永和國中', '1101', '台北市', '內湖區', '', '', '康寧路', '3段', '', '', '', '', 1, '', '無', '', '', '[]', '吳', '無', '[]', 'rejected', NULL, NULL, NULL),
(22, NULL, '2025-10-15 06:22:25', '2025-10-15 06:48:40', '110534201', '狂三1016', 'A123456777', 1991, 1, 1, 'female', '0900000000', '0900000001', '台北市', '永和國中', '1101', '台北市', '內湖區', '', '', '康寧路', '3段', '', '', '', '', 1, '', '無', '', '', '[{\"type\":\"exam\",\"filename\":\"MV5BOTk5ZDZhNGUtMDM2OS00Y2RkLWEwMmQtODg4ZTZiMGY1ZjFjXkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg\",\"path\":\"uploads\\/continued_admission\\/1760509767_68ef3f47c0505_MV5BOTk5ZDZhNGUtMDM2OS00Y2RkLWEwMmQtODg4ZTZiMGY1ZjFjXkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg\"}]', '無法無天', '無', '[\"動畫科\"]', 'rejected', NULL, NULL, NULL),
(23, NULL, '2025-10-16 02:28:13', '2025-10-16 02:51:33', 'dd', 'd', 'd355555555', 55, 5, 5, 'male', '', '0909090909', '5', '5', '', '', '', '', '', '', '', '', '', '', '', 0, '', '', '', '', '[]', '', '', '[\"動畫科\"]', 'approved', NULL, NULL, NULL),
(24, NULL, '2025-10-16 03:19:06', '2025-10-16 03:47:18', '1016', '1016', 'P123444444', 444, 4, 4, 'male', '', '4444444444', '4444', '44', 'rr', 'r', 'r', 'r', 'r', 'r', 'r', 'r', 'r', 'rr', 'r', 1, '', 'rrr', '', '', '[{\"type\":\"exam\",\"filename\":\"4 招教你無腦生成神級 Prompt.pdf\",\"path\":\"uploads\\/continued_admission\\/1760584746_68f0642ae0e6a_4 招教你無腦生成神級 Prompt.pdf\"},{\"type\":\"skill\",\"filename\":\"0P176416120739753670.pdf\",\"path\":\"uploads\\/continued_admission\\/1760584746_68f0642ae1b6a_0P176416120739753670.pdf\"},{\"type\":\"leader\",\"filename\":\"7.png\",\"path\":\"uploads\\/continued_admission\\/1760584746_68f0642ae20b3_7.png\"},{\"type\":\"service\",\"filename\":\"6.png\",\"path\":\"uploads\\/continued_admission\\/1760584746_68f0642ae25e3_6.png\"},{\"type\":\"fitness\",\"filename\":\"5.png\",\"path\":\"uploads\\/continued_admission\\/1760584746_68f0642ae2b5d_5.png\"},{\"type\":\"contest\",\"filename\":\"1747906300351.jpg\",\"path\":\"uploads\\/continued_admission\\/1760584746_68f0642ae331b_1747906300351.jpg\"},{\"type\":\"other\",\"filename\":\"螢幕擷取畫面 (77).png\",\"path\":\"uploads\\/continued_admission\\/1760584746_68f0642ae38f9_螢幕擷取畫面 (77).png\"}]', 'ff', 'ff', '[\"護理科\",\"視光科\",\"幼保科\",\"動畫科\",\"應用外語科\",\"資訊管理科\",\"企業管理科\"]', 'approved', NULL, NULL, '2025-10-16 03:47:18'),
(25, NULL, '2025-10-27 07:22:05', '2025-10-27 07:22:21', 'ss', 's', 's222222222', 1991, 1, 1, 'male', '', '0900000000', '', '', '', '', '', '', '', '', '', '', '', '', '', 0, '', '', '', '', '[]', '', '', '[\"幼保科\"]', 'approved', NULL, '', '2025-10-27 07:22:21'),
(26, NULL, '2025-10-27 07:22:52', '2025-10-27 07:23:06', 'qqq', 'ww', 'q222222222', 0, 0, 0, 'female', '', '0000000000', '', '', '', '', '', '', '', '', '', '', '', '', '', 0, '', '', '', '', '[]', '', '', '[\"幼保科\"]', 'approved', NULL, '', '2025-10-27 07:23:06'),
(27, NULL, '2025-10-27 08:58:40', '2025-10-27 09:02:28', '0', '00', 'l111111111', 0, 0, 0, '', '', '0000000000', '', '', '', '', '', '', '', '', '', '', '', '', '', 0, '', '', '', '', '[]', '', '', '[\"應用外語科\"]', 'approved', NULL, '', '2025-10-27 09:02:28'),
(28, NULL, '2025-10-27 09:03:58', '2025-10-27 09:04:47', 'd', 'd', 'd000000000', 0, 0, 0, '', '', '0000000000', '', '', '', '', '', '', '', '', '', '', '', '', '', 0, '', '', '', '', '[]', '', '', '[\"動畫科\"]', 'approved', NULL, '', '2025-10-27 09:04:47'),
(29, NULL, '2025-10-27 09:04:35', '2025-10-29 07:01:50', '454', '45', 's123456789', 0, 0, 0, '', '', '0123456789', '', '', '', '', '', '', '', '', '', '', '', '', '', 0, '', '', '', '', '[]', '', '', '[\"幼保科\"]', 'approved', NULL, '', '2025-10-29 02:00:25'),
(30, NULL, '2025-10-27 09:05:50', '2025-10-27 09:06:01', '', 'e', 'e123456789', 0, 0, 0, '', '', '0123456789', '', '', '', '', '', '', '', '', '', '', '', '', '', 0, '', '', '', '', '[]', '', '', '[\"幼保科\"]', 'approved', NULL, '', '2025-10-27 09:06:01'),
(31, NULL, '2025-10-29 01:33:35', '2025-10-29 07:00:22', 'f', 'd', 'f123456789', 0, 0, 0, '', '', '0123456789', '', '', '', '', '', '', '', '', '', '', '', '', '', 0, '', '', '', '', '[]', '', '', '[\"幼保科\"]', 'approved', NULL, '', '2025-10-29 07:00:22'),
(32, NULL, '2025-10-29 01:34:33', '2025-10-29 01:40:20', 'dd', 'dd', 'd012456780', 0, 0, 0, '', '', '0123478569', '', '', '', '', '', '', '', '', '', '', '', '', '', 0, '', '', '', '', '[]', '', '', '[\"護理科\"]', 'approved', NULL, '', '2025-10-29 01:40:20'),
(33, NULL, '2025-10-29 07:02:15', '2025-10-29 07:02:15', 'ee', '44', 'a123456456', 0, 0, 0, '', '', '0123456789', '', '', '', '', '', '', '', '', '', '', '', '', '', 0, '', '', '', '', '[]', '', '', '[]', 'pending', NULL, NULL, NULL),
(34, NULL, '2025-10-29 07:02:36', '2025-10-29 07:04:21', '45', '45', 'a456123456', 0, 0, 0, '', '', '4544545454', '', '', '', '', '', '', '', '', '', '', '', '', '', 0, '', '', '', '', '[]', '', '', '[\"幼保科\"]', 'pending', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- 資料表結構 `continued_admission_choices`
--

CREATE TABLE `continued_admission_choices` (
  `id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL COMMENT '續招報名ID',
  `department_code` varchar(20) NOT NULL COMMENT '科系代碼',
  `choice_order` int(11) NOT NULL COMMENT '志願順序',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='續招報名志願選擇表';

-- --------------------------------------------------------

--
-- 替換檢視表以便查看 `cooperation_applications_view`
-- (請參考以下實際畫面)
--
CREATE TABLE `cooperation_applications_view` (
);

-- --------------------------------------------------------

--
-- 資料表結構 `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL COMMENT '科系代碼',
  `name` varchar(255) NOT NULL COMMENT '科系名稱',
  `available_systems` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT '可用學制 ["五專", "四技"]' CHECK (json_valid(`available_systems`)),
  `description` text DEFAULT NULL COMMENT '科系描述',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='科系資料表';

--
-- 傾印資料表的資料 `departments`
--

INSERT INTO `departments` (`id`, `code`, `name`, `available_systems`, `description`, `created_at`, `updated_at`) VALUES
(1, 'NURSING', '護理科', '[\"五專\"]', NULL, '2025-10-31 05:50:24', '2025-10-31 05:50:24'),
(2, 'CHILDCARE', '嬰幼兒保育科', '[\"五專\", \"四技\"]', NULL, '2025-10-31 05:50:24', '2025-10-31 05:50:24'),
(3, 'OPTOMETRY', '視光科', '[\"五專\"]', NULL, '2025-10-31 05:50:24', '2025-10-31 05:50:24'),
(4, 'DIGITAL_MEDIA', '數位影視動畫科', '[\"五專\"]', NULL, '2025-10-31 05:50:24', '2025-10-31 05:50:24'),
(5, 'IM', '資訊管理科', '[\"五專\"]', NULL, '2025-10-31 05:50:24', '2025-10-31 05:50:24'),
(6, 'BA', '企業管理科', '[\"五專\", \"四技\"]', NULL, '2025-10-31 05:50:24', '2025-10-31 05:50:24'),
(7, 'FOREIGN_LANG', '應用外語科', '[\"五專\"]', NULL, '2025-10-31 05:50:24', '2025-10-31 05:50:24'),
(8, 'LONG_TERM_CARE', '長期照護學系', '[\"四技\"]', NULL, '2025-10-31 05:50:24', '2025-10-31 05:50:24');

-- --------------------------------------------------------

--
-- 資料表結構 `department_quotas`
--

CREATE TABLE `department_quotas` (
  `id` int(11) NOT NULL,
  `department_name` varchar(100) NOT NULL COMMENT '科系名稱 (關聯 admission_courses.course_name)',
  `total_quota` int(11) NOT NULL DEFAULT 0 COMMENT '總名額',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否啟用',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='科系名額管理表';

--
-- 傾印資料表的資料 `department_quotas`
--

INSERT INTO `department_quotas` (`id`, `department_name`, `total_quota`, `is_active`, `created_at`, `updated_at`) VALUES
(1, '企業管理科', 4, 1, '2025-10-23 04:04:03', '2025-10-23 04:04:24'),
(2, '幼保科', 5, 1, '2025-10-27 07:20:56', '2025-10-29 07:03:52'),
(3, '應用外語科', 2, 1, '2025-10-27 09:02:19', '2025-10-29 01:57:36'),
(4, '數位影視動畫科', 1, 1, '2025-10-27 09:02:42', '2025-10-27 09:02:42'),
(5, '護理科', 2, 1, '2025-10-29 01:40:10', '2025-10-29 01:40:10'),
(6, '視光科', 1, 1, '2025-10-29 01:57:03', '2025-10-29 01:57:03'),
(7, '資訊管理科', 1, 1, '2025-10-29 01:57:08', '2025-10-29 01:57:12');

-- --------------------------------------------------------

--
-- 資料表結構 `education_systems`
--

CREATE TABLE `education_systems` (
  `id` int(11) NOT NULL,
  `code` varchar(20) NOT NULL COMMENT '學制代碼',
  `name` varchar(50) NOT NULL COMMENT '學制名稱',
  `years` int(11) NOT NULL COMMENT '修業年數',
  `description` text DEFAULT NULL COMMENT '學制描述',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='學制資料表';

--
-- 傾印資料表的資料 `education_systems`
--

INSERT INTO `education_systems` (`id`, `code`, `name`, `years`, `description`, `created_at`, `updated_at`) VALUES
(1, 'FIVE_YEAR', '五專', 5, NULL, '2025-10-31 05:50:24', '2025-10-31 05:50:24'),
(2, 'FOUR_YEAR', '四技', 4, NULL, '2025-10-31 05:50:24', '2025-10-31 05:50:24'),
(4, 'THREE_YEAR', '三專', 3, NULL, '2025-11-04 06:37:08', '2025-11-04 06:37:08');

-- --------------------------------------------------------

--
-- 資料表結構 `enrollment_applications`
--

CREATE TABLE `enrollment_applications` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `identity` enum('學生','家長') NOT NULL,
  `gender` enum('男','女') DEFAULT NULL,
  `phone1` varchar(50) NOT NULL,
  `phone2` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `intention1` varchar(255) DEFAULT '無特定',
  `system1` varchar(50) DEFAULT NULL,
  `department1` varchar(255) DEFAULT NULL,
  `intention2` varchar(255) DEFAULT '無特定',
  `system2` varchar(50) DEFAULT NULL,
  `department2` varchar(255) DEFAULT NULL,
  `intention3` varchar(255) DEFAULT '無特定',
  `system3` varchar(50) DEFAULT NULL,
  `department3` varchar(255) DEFAULT NULL,
  `junior_high` varchar(255) DEFAULT NULL,
  `current_grade` varchar(50) DEFAULT NULL,
  `line_id` varchar(255) DEFAULT NULL,
  `facebook` varchar(255) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `recommended_teacher` varchar(255) DEFAULT NULL,
  `status` enum('pending','contacted','enrolled') DEFAULT 'pending',
  `admin_comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `enrollment_applications`
--

INSERT INTO `enrollment_applications` (`id`, `username`, `name`, `identity`, `gender`, `phone1`, `phone2`, `email`, `intention1`, `system1`, `department1`, `intention2`, `system2`, `department2`, `intention3`, `system3`, `department3`, `junior_high`, `current_grade`, `line_id`, `facebook`, `remarks`, `recommended_teacher`, `status`, `admin_comment`, `created_at`, `updated_at`) VALUES
(1, 'student', '林奕廷', '學生', '男', '0911111111', '', '', '電機工程學系', '', '電機工程學系', '資訊工程學系', '', '資訊工程學系', '外國語文學系', '', '外國語文學系', '西松國中', '國三', '123456', '', '', '周建羽老師', 'pending', NULL, '2025-10-01 02:27:49', '2025-10-01 02:27:49'),
(2, '訪客', '賈斯伯哈哈格', '學生', '男', '0912345678', '123', '110534225@stu.ukn.edu.tw', '資訊工程學系', '五專', '視光科', '無特定', '', '無特定', '無特定', '', '', '南港', '國一', '123', '123', '', '', 'pending', NULL, '2025-10-03 06:54:56', '2025-10-03 06:54:56'),
(3, '訪客', '賈斯伯哈哈格', '學生', '男', '0912345678', '123', '110534225@stu.ukn.edu.tw', '資訊工程學系', '五專', '應用外語科', '無特定', '', '無特定', '無特定', '', '', '台北市立南港國中', '國一', '123', '123', '', 'assistant1', 'pending', NULL, '2025-10-03 06:58:38', '2025-10-03 06:58:38'),
(4, '訪客', '賈斯伯哈哈格', '學生', '男', '0912345678', '123', 'fff@gmail.com', '動畫科', '五專', '動畫科', '無特定', '', '', '無特定', '', '', '台北市立永吉國中', '', '123', '123', '', '嚴竹華', 'pending', NULL, '2025-10-07 06:22:02', '2025-10-07 06:22:02');

-- --------------------------------------------------------

--
-- 資料表結構 `enrollment_applications_normalized`
--

CREATE TABLE `enrollment_applications_normalized` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL COMMENT '關聯到 user 表',
  `username` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `identity_id` int(11) NOT NULL COMMENT '關聯到 identities 表',
  `gender_id` int(11) DEFAULT NULL COMMENT '關聯到 genders 表',
  `phone1` varchar(50) NOT NULL,
  `phone2` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `junior_high_school_id` int(11) DEFAULT NULL COMMENT '關聯到 schools 表',
  `current_grade_id` int(11) DEFAULT NULL COMMENT '關聯到 grades 表',
  `line_id` varchar(255) DEFAULT NULL,
  `facebook` varchar(255) DEFAULT NULL,
  `recommended_teacher_id` int(11) DEFAULT NULL COMMENT '關聯到 teacher 表',
  `remarks` text DEFAULT NULL,
  `status_id` int(11) NOT NULL DEFAULT 1 COMMENT '關聯到 application_statuses 表',
  `admin_id` int(11) DEFAULT NULL COMMENT '處理的管理員ID',
  `admin_comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='正規化後的就讀意願申請表';

--
-- 傾印資料表的資料 `enrollment_applications_normalized`
--

INSERT INTO `enrollment_applications_normalized` (`id`, `user_id`, `username`, `name`, `identity_id`, `gender_id`, `phone1`, `phone2`, `email`, `junior_high_school_id`, `current_grade_id`, `line_id`, `facebook`, `recommended_teacher_id`, `remarks`, `status_id`, `admin_id`, `admin_comment`, `created_at`, `updated_at`) VALUES
(1, NULL, 'student', '林奕廷', 1, 1, '0911111111', '', '', NULL, 3, '123456', '', NULL, '', 1, NULL, NULL, '2025-10-01 02:27:49', '2025-10-01 02:27:49'),
(2, NULL, '訪客', '賈斯伯哈哈格', 1, 1, '0912345678', '123', '110534225@stu.ukn.edu.tw', NULL, 1, '123', '123', NULL, '', 1, NULL, NULL, '2025-10-03 06:54:56', '2025-10-03 06:54:56'),
(3, NULL, '訪客', '賈斯伯哈哈格', 1, 1, '0912345678', '123', '110534225@stu.ukn.edu.tw', NULL, 1, '123', '123', NULL, '', 1, NULL, NULL, '2025-10-03 06:58:38', '2025-10-03 06:58:38'),
(4, NULL, '訪客', '賈斯伯哈哈格', 1, 1, '0912345678', '123', 'fff@gmail.com', NULL, NULL, '123', '123', NULL, '', 1, NULL, NULL, '2025-10-07 06:22:02', '2025-10-07 06:22:02'),
(5, 3, 'student', '林奕廷', 1, 1, '0911111111', '', '', NULL, 3, '123456', '', NULL, '', 1, NULL, NULL, '2025-10-01 02:27:49', '2025-10-01 02:27:49'),
(6, NULL, '訪客', '賈斯伯哈哈格', 1, 1, '0912345678', '123', '110534225@stu.ukn.edu.tw', NULL, 1, '123', '123', NULL, '', 1, NULL, NULL, '2025-10-03 06:54:56', '2025-10-03 06:54:56'),
(7, NULL, '訪客', '賈斯伯哈哈格', 1, 1, '0912345678', '123', '110534225@stu.ukn.edu.tw', NULL, 1, '123', '123', NULL, '', 1, NULL, NULL, '2025-10-03 06:58:38', '2025-10-03 06:58:38'),
(8, NULL, '訪客', '賈斯伯哈哈格', 1, 1, '0912345678', '123', 'fff@gmail.com', NULL, NULL, '123', '123', NULL, '', 1, NULL, NULL, '2025-10-07 06:22:02', '2025-10-07 06:22:02');

-- --------------------------------------------------------

--
-- 替換檢視表以便查看 `enrollment_applications_view`
-- (請參考以下實際畫面)
--
CREATE TABLE `enrollment_applications_view` (
`id` int(11)
,`username` varchar(255)
,`name` varchar(255)
,`identity` varchar(100)
,`gender` varchar(50)
,`phone1` varchar(50)
,`phone2` varchar(50)
,`email` varchar(255)
,`intention1` varchar(255)
,`system1` varchar(50)
,`department1` varchar(255)
,`intention2` varchar(255)
,`system2` varchar(50)
,`department2` varchar(255)
,`intention3` varchar(255)
,`system3` varchar(50)
,`department3` varchar(255)
,`junior_high` varchar(255)
,`current_grade` varchar(50)
,`line_id` varchar(255)
,`facebook` varchar(255)
,`remarks` text
,`status` varchar(100)
,`admin_comment` text
,`created_at` timestamp
,`updated_at` timestamp
);

-- --------------------------------------------------------

--
-- 資料表結構 `enrollment_contact_logs`
--

CREATE TABLE `enrollment_contact_logs` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `contact_date` date NOT NULL,
  `method` varchar(20) NOT NULL,
  `result` text NOT NULL,
  `follow_up_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `enrollment_contact_logs`
--

INSERT INTO `enrollment_contact_logs` (`id`, `student_id`, `teacher_id`, `contact_date`, `method`, `result`, `follow_up_notes`, `created_at`) VALUES
(1, 9, 13, '2025-10-30', 'Line', '有興趣來讀資管科!', '', '2025-10-30 05:03:48'),
(2, 9, 13, '2025-10-30', 'Line', 'ss', 'ss', '2025-10-30 13:16:50'),
(3, 10, 18, '2025-10-31', '電話', '他不想來', '他很吵', '2025-10-31 01:02:19');

-- --------------------------------------------------------

--
-- 資料表結構 `enrollment_intention`
--

CREATE TABLE `enrollment_intention` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL COMMENT '姓名',
  `identity` enum('學生','家長') NOT NULL COMMENT '身分別',
  `gender` enum('男','女') DEFAULT NULL COMMENT '性別',
  `phone1` varchar(20) NOT NULL COMMENT '聯絡電話1',
  `phone2` varchar(20) DEFAULT NULL COMMENT '聯絡電話2',
  `email` varchar(100) DEFAULT NULL COMMENT '電子郵件',
  `intention1` varchar(50) DEFAULT NULL COMMENT '就讀意願一',
  `intention2` varchar(50) DEFAULT NULL COMMENT '就讀意願二',
  `intention3` varchar(50) DEFAULT NULL COMMENT '就讀意願三',
  `system1` varchar(20) DEFAULT NULL COMMENT '學制一',
  `system2` varchar(20) DEFAULT NULL COMMENT '學制二',
  `system3` varchar(20) DEFAULT NULL COMMENT '學制三',
  `junior_high` varchar(200) DEFAULT NULL COMMENT '就讀或畢業國中',
  `current_grade` varchar(20) DEFAULT NULL COMMENT '目前年級',
  `line_id` varchar(100) DEFAULT NULL COMMENT 'LineID',
  `facebook` varchar(200) DEFAULT NULL COMMENT 'Facebook',
  `recommended_teacher` varchar(100) DEFAULT NULL COMMENT '推薦老師',
  `remarks` text DEFAULT NULL COMMENT '備註',
  `assigned_department` varchar(50) DEFAULT NULL,
  `assigned_teacher_id` int(11) DEFAULT NULL,
  `captcha` varchar(10) DEFAULT NULL COMMENT '驗證碼',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT '建立時間',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新時間'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='就讀意願登錄表';

--
-- 傾印資料表的資料 `enrollment_intention`
--

INSERT INTO `enrollment_intention` (`id`, `name`, `identity`, `gender`, `phone1`, `phone2`, `email`, `intention1`, `intention2`, `intention3`, `system1`, `system2`, `system3`, `junior_high`, `current_grade`, `line_id`, `facebook`, `recommended_teacher`, `remarks`, `assigned_department`, `assigned_teacher_id`, `captcha`, `created_at`, `updated_at`) VALUES
(1, '馬蓋仙', '學生', '女', '0987654321', '', 'tset@gmail.com', '無特定', '無特定', '無特定', '', '', '', '南港國中 (台北市南港區)', '國三', '123', '123', '周建羽', '', NULL, NULL, '8075', '2025-10-14 07:06:23', '2025-10-14 07:06:23'),
(2, 'qqqqqqqqqqqqq', '家長', '女', '000', '', '', '護理科', '嬰幼兒保育科', '護理科', '五專', '', '五專', '縣立恆春國中 (屏東縣)', '國三', '', '', '嚴竹華', '', NULL, NULL, '4797', '2025-10-15 01:12:38', '2025-10-15 01:12:38'),
(3, '尤思婷', '學生', '女', '0922222222', '', '', '嬰幼兒保育科', '無特定', '無特定', '五專', '', '', '新泰國中', '國三', '123456', '', '', '', NULL, NULL, '6886', '2025-10-15 01:19:40', '2025-10-15 01:19:40'),
(4, '周佳儀', '學生', '女', '0961447587', '', '', '嬰幼兒保育科', '應用外語科', '企業管理科', '五專', '五專', '五專', '五股國中 (新北市)', '國二', '123456', '', '李岳倫', '在地五股人', NULL, NULL, '5157', '2025-10-15 09:44:59', '2025-10-15 09:44:59'),
(5, '賴定宏', '學生', '男', '0954777666', '', '', '資訊管理科', '嬰幼兒保育科', '護理科', '五專', '五專', '五專', '光榮國中', '國二', '123456', '', '周建羽', '', NULL, 18, '9112', '2025-10-22 02:47:11', '2025-10-28 08:43:30'),
(6, '11', '學生', '女', '0909090000', '', '', '護理科', '視光科', '嬰幼兒保育科', '五專', '五專', '四技', '八斗國中 (基隆市中正區)', '國三', '11', '', '嚴竹華', '10', NULL, NULL, '0162', '2025-10-22 02:52:23', '2025-10-22 02:52:23'),
(7, '陳聖恩', '學生', '女', '0933333333', '', '', '資訊管理科', '嬰幼兒保育科', '護理科', '五專', '五專', '五專', '市立東湖國中 (臺北市)', '國二', '123456', '', '周建羽', '我很吵\r\n請你們都給我注意一點', NULL, 14, '6921', '2025-10-28 10:28:37', '2025-10-28 10:30:24'),
(8, '張莉翎', '學生', '女', '0938775254', '', '', '資訊管理科', '護理科', '長期照護學系', '五專', '五專', '四技', '市立明湖國中 (臺北市)', '國二', '123456', '', '李岳倫', '', NULL, 18, '6281', '2025-10-29 00:57:07', '2025-10-29 01:18:14'),
(9, '羅勻辰', '學生', '女', '0956727543', '', '', '應用外語科', '資訊管理科', '企業管理科', '五專', '五專', '五專', '市立明湖國中 (臺北市)', '國三', '123456', '', '嚴竹華', '', 'IMD', 13, '7566', '2025-10-29 06:25:50', '2025-10-29 07:53:49'),
(10, '黃家歡', '學生', '女', '123456', '', '', '資訊管理科', '嬰幼兒保育科', '無特定', '五專', '五專', '', '市立東湖國中 (臺北市)', '國一', '123456', '', '周建羽', '我很吵', NULL, 18, '1741', '2025-10-31 00:58:38', '2025-10-31 00:59:18');

-- --------------------------------------------------------

--
-- 資料表結構 `enrollment_preferences`
--

CREATE TABLE `enrollment_preferences` (
  `id` int(11) NOT NULL,
  `enrollment_application_id` int(11) NOT NULL COMMENT '關聯到 enrollment_applications_normalized',
  `preference_order` int(11) NOT NULL COMMENT '志願順序 (1, 2, 3)',
  `department_id` int(11) NOT NULL COMMENT '關聯到 departments 表',
  `education_system_id` int(11) NOT NULL COMMENT '關聯到 education_systems 表',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='就讀意願明細表';

--
-- 傾印資料表的資料 `enrollment_preferences`
--

INSERT INTO `enrollment_preferences` (`id`, `enrollment_application_id`, `preference_order`, `department_id`, `education_system_id`, `created_at`) VALUES
(2, 4, 1, 0, 1, '2025-10-31 05:52:09'),
(3, 1, 2, 0, 0, '2025-11-04 06:37:08'),
(4, 4, 2, 0, 0, '2025-11-04 06:37:08'),
(6, 1, 3, 0, 0, '2025-11-04 06:37:08'),
(7, 2, 3, 0, 0, '2025-11-04 06:37:08'),
(8, 3, 3, 0, 0, '2025-11-04 06:37:08'),
(9, 4, 3, 0, 0, '2025-11-04 06:37:08'),
(13, 1, 1, 0, 0, '2025-11-04 06:41:38'),
(14, 2, 1, 3, 1, '2025-11-04 06:41:38'),
(15, 3, 1, 7, 1, '2025-11-04 06:41:38'),
(25, 5, 1, 0, 0, '2025-11-04 07:01:50'),
(26, 6, 1, 3, 1, '2025-11-04 07:01:50'),
(27, 7, 1, 7, 1, '2025-11-04 07:01:50'),
(28, 8, 1, 0, 1, '2025-11-04 07:01:50'),
(32, 5, 2, 0, 0, '2025-11-04 07:01:50'),
(33, 8, 2, 0, 0, '2025-11-04 07:01:50'),
(35, 5, 3, 0, 0, '2025-11-04 07:01:50'),
(36, 6, 3, 0, 0, '2025-11-04 07:01:50'),
(37, 7, 3, 0, 0, '2025-11-04 07:01:50'),
(38, 8, 3, 0, 0, '2025-11-04 07:01:50');

-- --------------------------------------------------------

--
-- 資料表結構 `genders`
--

CREATE TABLE `genders` (
  `id` int(11) NOT NULL,
  `code` varchar(20) NOT NULL COMMENT '性別代碼',
  `name` varchar(50) NOT NULL COMMENT '性別名稱',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='性別資料表';

--
-- 傾印資料表的資料 `genders`
--

INSERT INTO `genders` (`id`, `code`, `name`, `created_at`) VALUES
(1, 'MALE', '男', '2025-10-31 05:50:24'),
(2, 'FEMALE', '女', '2025-10-31 05:50:24'),
(4, 'OTHER', '其他', '2025-11-04 06:37:08');

-- --------------------------------------------------------

--
-- 資料表結構 `grades`
--

CREATE TABLE `grades` (
  `id` int(11) NOT NULL,
  `code` varchar(20) NOT NULL COMMENT '年級代碼',
  `name` varchar(50) NOT NULL COMMENT '年級名稱',
  `level` int(11) NOT NULL COMMENT '年級層級 (1=國一, 2=國二, 3=國三)',
  `education_level` enum('國中','高中','專科','大學') DEFAULT '專科' COMMENT '教育層級',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='年級資料表';

--
-- 傾印資料表的資料 `grades`
--

INSERT INTO `grades` (`id`, `code`, `name`, `level`, `education_level`, `created_at`) VALUES
(1, 'GRADE_1', '國一', 1, '專科', '2025-10-31 05:50:24'),
(2, 'GRADE_2', '國二', 2, '專科', '2025-10-31 05:50:24'),
(3, 'GRADE_3', '國三', 3, '專科', '2025-10-31 05:50:24'),
(4, 'GRADUATED', '已畢業', 4, '專科', '2025-10-31 05:50:24'),
(6, 'JUNIOR_1', '國一', 1, '國中', '2025-11-04 06:41:37'),
(7, 'JUNIOR_2', '國二', 2, '國中', '2025-11-04 06:41:37'),
(8, 'JUNIOR_3', '國三', 3, '國中', '2025-11-04 06:41:37'),
(9, 'JUNIOR_GRADUATED', '國中已畢業', 4, '國中', '2025-11-04 06:41:37'),
(10, 'YEAR_1', '一年級', 1, '專科', '2025-11-04 06:41:37'),
(11, 'YEAR_2', '二年級', 2, '專科', '2025-11-04 06:41:37'),
(12, 'YEAR_3', '三年級', 3, '專科', '2025-11-04 06:41:37'),
(13, 'YEAR_4', '四年級', 4, '專科', '2025-11-04 06:41:37'),
(14, 'YEAR_5', '五年級', 5, '專科', '2025-11-04 06:41:37'),
(15, 'UNI_YEAR_1', '大一', 1, '大學', '2025-11-04 06:41:37'),
(16, 'UNI_YEAR_2', '大二', 2, '大學', '2025-11-04 06:41:37'),
(17, 'UNI_YEAR_3', '大三', 3, '大學', '2025-11-04 06:41:37'),
(18, 'UNI_YEAR_4', '大四', 4, '大學', '2025-11-04 06:41:37');

-- --------------------------------------------------------

--
-- 資料表結構 `group_chat_members`
--

CREATE TABLE `group_chat_members` (
  `id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `role` varchar(50) DEFAULT '成員',
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `group_chat_messages`
--

CREATE TABLE `group_chat_messages` (
  `id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `from_user` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `role` varchar(50) DEFAULT '用戶',
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `group_info`
--

CREATE TABLE `group_info` (
  `id` int(11) NOT NULL,
  `group_name` varchar(255) NOT NULL,
  `created_by` varchar(255) NOT NULL,
  `department` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `group_members_normalized`
--

CREATE TABLE `group_members_normalized` (
  `id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL COMMENT '關聯到 chat_groups_normalized',
  `user_id` int(11) NOT NULL COMMENT '關聯到 user 表',
  `role_type_id` int(11) NOT NULL DEFAULT 6 COMMENT '關聯到 role_types 表（預設為成員）',
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='正規化後的群組成員表';

-- --------------------------------------------------------

--
-- 替換檢視表以便查看 `group_members_view`
-- (請參考以下實際畫面)
--
CREATE TABLE `group_members_view` (
`id` int(11)
,`group_id` int(11)
,`username` varchar(255)
,`role` varchar(100)
,`joined_at` timestamp
);

-- --------------------------------------------------------

--
-- 資料表結構 `group_messages_normalized`
--

CREATE TABLE `group_messages_normalized` (
  `id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL COMMENT '關聯到 chat_groups_normalized',
  `from_user_id` int(11) NOT NULL COMMENT '關聯到 user 表',
  `message` text NOT NULL,
  `message_type_id` int(11) NOT NULL DEFAULT 1 COMMENT '關聯到 message_types 表',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='正規化後的群組訊息表';

-- --------------------------------------------------------

--
-- 替換檢視表以便查看 `group_messages_view`
-- (請參考以下實際畫面)
--
CREATE TABLE `group_messages_view` (
`id` int(11)
,`group_id` int(11)
,`from_user` varchar(255)
,`message` text
,`message_type` varchar(100)
,`timestamp` timestamp
);

-- --------------------------------------------------------

--
-- 資料表結構 `identities`
--

CREATE TABLE `identities` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL COMMENT '身份代碼',
  `name` varchar(100) NOT NULL COMMENT '身份名稱',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='身分別資料表';

--
-- 傾印資料表的資料 `identities`
--

INSERT INTO `identities` (`id`, `code`, `name`, `created_at`) VALUES
(1, 'STUDENT', '學生', '2025-10-31 05:50:24'),
(2, 'PARENT', '家長', '2025-10-31 05:50:24');

-- --------------------------------------------------------

--
-- 資料表結構 `ip_rights`
--

CREATE TABLE `ip_rights` (
  `id` int(11) NOT NULL,
  `cooperation_application_id` int(11) NOT NULL COMMENT '關聯到 cooperation_applications_normalized',
  `ip_type` enum('patent','trademark','copyright','trade_secret') NOT NULL COMMENT 'IP類型',
  `university_percentage` decimal(5,2) DEFAULT 0.00 COMMENT '大學比例',
  `company_percentage` decimal(5,2) DEFAULT 0.00 COMMENT '公司比例',
  `investigator_percentage` decimal(5,2) DEFAULT 0.00 COMMENT '研究員比例',
  `description` text DEFAULT NULL COMMENT '描述',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='智慧財產權明細表';

-- --------------------------------------------------------

--
-- 資料表結構 `junior_school_recruitment_applications`
--

CREATE TABLE `junior_school_recruitment_applications` (
  `id` int(11) NOT NULL COMMENT '申請編號',
  `school_name` varchar(100) NOT NULL COMMENT '學校名稱',
  `city` varchar(20) NOT NULL COMMENT '縣市',
  `district` varchar(20) NOT NULL COMMENT '區/鄉鎮市',
  `school_address` varchar(255) DEFAULT NULL COMMENT '學校地址',
  `contact_name` varchar(50) NOT NULL COMMENT '聯絡人姓名',
  `contact_title` varchar(50) DEFAULT NULL COMMENT '聯絡人職稱',
  `contact_phone` varchar(20) NOT NULL COMMENT '聯絡電話',
  `contact_email` varchar(120) NOT NULL COMMENT '聯絡Email',
  `preferred_date` date DEFAULT NULL COMMENT '期望招生日期',
  `preferred_time` varchar(50) DEFAULT NULL COMMENT '期望時間（例如：上午、下午、全天）',
  `target_grades` varchar(50) DEFAULT NULL COMMENT '目標年級（例如：三年級、二年級）',
  `expected_students` int(11) DEFAULT NULL COMMENT '預期參與學生人數',
  `venue_type` varchar(50) DEFAULT NULL COMMENT '場地類型（例如：禮堂、活動中心、教室）',
  `special_requirements` text DEFAULT NULL COMMENT '特殊需求',
  `remarks` text DEFAULT NULL COMMENT '備註',
  `status` enum('pending','approved','rejected','completed') DEFAULT 'pending' COMMENT '申請狀態：pending=待審核, approved=已核准, rejected=已拒絕, completed=已完成',
  `admin_comment` text DEFAULT NULL COMMENT '管理員備註',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT '申請時間',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新時間'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='國中學校招生申請表';

--
-- 傾印資料表的資料 `junior_school_recruitment_applications`
--

INSERT INTO `junior_school_recruitment_applications` (`id`, `school_name`, `city`, `district`, `school_address`, `contact_name`, `contact_title`, `contact_phone`, `contact_email`, `preferred_date`, `preferred_time`, `target_grades`, `expected_students`, `venue_type`, `special_requirements`, `remarks`, `status`, `admin_comment`, `created_at`, `updated_at`) VALUES
(1, '永吉國中', '台北市', '內湖區', '0000', '狂三三', '主任', '0900000000', '110534201@stu.uk.edu.tw', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'pending', NULL, '2025-11-06 03:17:39', '2025-11-06 03:17:39'),
(2, '永吉國中', '台北市', '內湖區', '0000', '狂三三', '主任', '0900000000', '110534201@stu.ukn.edu.tw', '2025-11-12', '上午', '三年級', 11, '教室', NULL, NULL, 'pending', NULL, '2025-11-06 03:24:24', '2025-11-06 03:24:24'),
(3, '永吉國中02', '台北市', '內湖區', '0000', '狂三三', '主任', '0900000000', '110534201@stu.ukn.edu.tw', '2025-11-12', '上午', '三年級', 11, '教室', NULL, NULL, 'pending', NULL, '2025-11-06 03:24:25', '2025-11-06 03:24:45'),
(4, '永和國中', '台北市', '內湖區', '0000', '狂三三022', '主任', '0900000000', '110534201@stu.ukn.edu.tw', '2025-11-06', '上午', '三年級', 1, '禮堂', NULL, NULL, 'pending', NULL, '2025-11-06 03:31:12', '2025-11-06 03:32:25');

-- --------------------------------------------------------

--
-- 資料表結構 `message_read_status`
--

CREATE TABLE `message_read_status` (
  `id` int(11) NOT NULL,
  `message_id` int(11) NOT NULL COMMENT '訊息ID（對應 private_chat_history.id）',
  `reader_username` varchar(255) NOT NULL COMMENT '讀取者用戶名',
  `read_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT '讀取時間'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='訊息已讀狀態記錄表';

--
-- 傾印資料表的資料 `message_read_status`
--

INSERT INTO `message_read_status` (`id`, `message_id`, `reader_username`, `read_at`) VALUES
(1, 7, '10', '2025-11-21 01:49:26');

-- --------------------------------------------------------

--
-- 資料表結構 `message_types`
--

CREATE TABLE `message_types` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL COMMENT '類型代碼',
  `name` varchar(100) NOT NULL COMMENT '類型名稱',
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='訊息類型資料表';

--
-- 傾印資料表的資料 `message_types`
--

INSERT INTO `message_types` (`id`, `code`, `name`, `description`, `created_at`) VALUES
(1, 'TEXT', '文字訊息', NULL, '2025-11-04 06:41:37'),
(2, 'IMAGE', '圖片', NULL, '2025-11-04 06:41:37'),
(3, 'FILE', '檔案', NULL, '2025-11-04 06:41:37'),
(4, 'SYSTEM', '系統訊息', NULL, '2025-11-04 06:41:37');

-- --------------------------------------------------------

--
-- 資料表結構 `notification_logs`
--

CREATE TABLE `notification_logs` (
  `id` int(11) NOT NULL,
  `recommendation_id` int(11) DEFAULT NULL,
  `notification_type` varchar(100) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `notification_logs`
--

INSERT INTO `notification_logs` (`id`, `recommendation_id`, `notification_type`, `email`, `status`, `sent_at`, `created_at`) VALUES
(1, 9, 'recommendation_success', '110534201@stu.ukn.edu.tw', 'sent', '2025-10-14 06:56:32', '2025-10-14 06:56:32'),
(2, 10, 'recommendation_success', '110534201@stu.ukn.edu.tw', 'sent', '2025-10-14 07:00:54', '2025-10-14 07:00:54'),
(3, 11, 'recommendation_success', '110534201@stu.ukn.edu.tw', 'sent', '2025-10-15 06:39:43', '2025-10-15 06:39:43'),
(4, 12, 'recommendation_success', '110534201@stu.ukn.edu.tw', 'sent', '2025-10-15 06:39:49', '2025-10-15 06:39:49'),
(5, 13, 'recommendation_success', '110534236@stu.ukn.edu.tw', 'sent', '2025-10-17 06:52:26', '2025-10-17 06:52:26'),
(6, 14, 'recommendation_success', 'ww@l.c', 'sent', '2025-11-21 02:04:03', '2025-11-21 02:04:03'),
(7, 15, 'recommendation_success', '4@g.d', 'sent', '2025-11-21 02:09:14', '2025-11-21 02:09:14');

-- --------------------------------------------------------

--
-- 資料表結構 `page_content`
--

CREATE TABLE `page_content` (
  `id` int(11) NOT NULL,
  `page_key` varchar(100) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `content` mediumtext DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `private_chat_history`
--

CREATE TABLE `private_chat_history` (
  `id` int(11) NOT NULL,
  `from_user` varchar(255) NOT NULL,
  `to_user` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `role` varchar(50) DEFAULT '用戶',
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0 COMMENT '訊息是否已讀',
  `read_at` timestamp NULL DEFAULT NULL COMMENT '訊息已讀時間'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `private_chat_history`
--

INSERT INTO `private_chat_history` (`id`, `from_user`, `to_user`, `message`, `role`, `timestamp`, `is_read`, `read_at`) VALUES
(1, 'assistant1', '尤世全110534225', '123', '老師', '2025-10-02 04:10:15', 0, NULL),
(2, 'assistant1', 'student', '這是一條測試訊息', '老師', '2025-10-02 04:18:21', 0, NULL),
(3, 'assistant1', 'student', 'API 測試訊息', '老師', '2025-10-02 04:18:21', 0, NULL),
(4, '尤世全110534225', 'assistant1', '456', '學生', '2025-10-03 02:12:27', 0, NULL),
(5, '尤世全110534225', ':D', '123123', '學生', '2025-10-03 02:23:11', 0, NULL),
(6, 'assistant3', 'assistant2', '1', '老師', '2025-10-29 01:13:36', 0, NULL),
(7, '77', '10', '123', '學生', '2025-11-21 01:48:30', 1, '2025-11-21 01:49:26');

-- --------------------------------------------------------

--
-- 資料表結構 `private_chat_history_normalized`
--

CREATE TABLE `private_chat_history_normalized` (
  `id` int(11) NOT NULL,
  `from_user_id` int(11) NOT NULL COMMENT '關聯到 user 表',
  `to_user_id` int(11) NOT NULL COMMENT '關聯到 user 表',
  `message` text NOT NULL,
  `message_type_id` int(11) NOT NULL DEFAULT 1 COMMENT '關聯到 message_types 表',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='正規化後的私聊訊息表';

--
-- 傾印資料表的資料 `private_chat_history_normalized`
--

INSERT INTO `private_chat_history_normalized` (`id`, `from_user_id`, `to_user_id`, `message`, `message_type_id`, `created_at`) VALUES
(1, 14, 7, '123', 1, '2025-10-02 04:10:15'),
(2, 14, 3, '這是一條測試訊息', 1, '2025-10-02 04:18:21'),
(3, 14, 3, 'API 測試訊息', 1, '2025-10-02 04:18:21'),
(4, 7, 14, '456', 1, '2025-10-03 02:12:27'),
(5, 7, 15, '123123', 1, '2025-10-03 02:23:11'),
(6, 18, 13, '1', 1, '2025-10-29 01:13:36');

-- --------------------------------------------------------

--
-- 替換檢視表以便查看 `private_chat_history_view`
-- (請參考以下實際畫面)
--
CREATE TABLE `private_chat_history_view` (
`id` int(11)
,`from_user` varchar(255)
,`to_user` varchar(255)
,`message` text
,`message_type` varchar(100)
,`timestamp` timestamp
);

-- --------------------------------------------------------

--
-- 資料表結構 `qa`
--

CREATE TABLE `qa` (
  `id` int(11) NOT NULL,
  `question` text NOT NULL,
  `answer` text NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `qa`
--

INSERT INTO `qa` (`id`, `question`, `answer`, `is_active`, `created_at`, `updated_at`) VALUES
(1, '康寧大學有多少科系', '總共有七個科系:\n資訊管理科、企業管理科、護理科、幼保科、應用外語科、視光科、數位影視動畫系', 1, '2025-09-30 05:44:00', '2025-10-07 00:46:27');

-- --------------------------------------------------------

--
-- 資料表結構 `role_types`
--

CREATE TABLE `role_types` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL COMMENT '角色代碼',
  `name` varchar(100) NOT NULL COMMENT '角色名稱',
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='角色類型資料表';

--
-- 傾印資料表的資料 `role_types`
--

INSERT INTO `role_types` (`id`, `code`, `name`, `description`, `created_at`) VALUES
(1, 'TEACHER', '老師', NULL, '2025-11-04 06:41:37'),
(2, 'STUDENT', '學生', NULL, '2025-11-04 06:41:37'),
(3, 'ADMIN', '管理員', NULL, '2025-11-04 06:41:37'),
(4, 'STAFF', '行政人員', NULL, '2025-11-04 06:41:37'),
(5, 'VENDOR', '廠商', NULL, '2025-11-04 06:41:37'),
(6, 'MEMBER', '成員', NULL, '2025-11-04 06:41:37');

-- --------------------------------------------------------

--
-- 資料表結構 `schools`
--

CREATE TABLE `schools` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL COMMENT '學校名稱',
  `city` varchar(50) NOT NULL COMMENT '縣市',
  `district` varchar(50) NOT NULL COMMENT '區域',
  `address` varchar(500) NOT NULL COMMENT '地址',
  `phone` varchar(50) DEFAULT NULL COMMENT '電話',
  `website` varchar(255) DEFAULT NULL COMMENT '網站',
  `type` enum('公立','私立') DEFAULT '公立' COMMENT '學校類型',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='國中學校資料表';

--
-- 傾印資料表的資料 `schools`
--

INSERT INTO `schools` (`id`, `name`, `city`, `district`, `address`, `phone`, `website`, `type`, `created_at`, `updated_at`) VALUES
(1, '台北市立中正國中', '台北市', '中正區', '台北市中正區重慶南路一段139號', NULL, NULL, '公立', '2025-10-03 03:36:44', '2025-10-03 03:36:44'),
(2, '台北市立建國中學', '台北市', '中正區', '台北市中正區南海路56號', NULL, NULL, '公立', '2025-10-03 03:36:44', '2025-10-03 03:36:44'),
(3, '台北市立金華國中', '台北市', '大安區', '台北市大安區新生南路二段32號', NULL, NULL, '公立', '2025-10-03 03:36:44', '2025-10-03 03:36:44'),
(4, '新北市立板橋國中', '新北市', '板橋區', '新北市板橋區文化路一段188號', NULL, NULL, '公立', '2025-10-03 03:36:44', '2025-10-03 03:36:44'),
(5, '新北市立新莊國中', '新北市', '新莊區', '新北市新莊區中正路211號', NULL, NULL, '公立', '2025-10-03 03:36:44', '2025-10-03 03:36:44'),
(6, '桃園市立桃園國中', '桃園市', '桃園區', '桃園市桃園區中正路107號', NULL, NULL, '公立', '2025-10-03 03:36:44', '2025-10-03 03:36:44'),
(7, '台中市立台中國中', '台中市', '中區', '台中市中區三民路二段46號', NULL, NULL, '公立', '2025-10-03 03:36:44', '2025-10-03 03:36:44'),
(8, '台南市立台南國中', '台南市', '中西區', '台南市中西區民族路二段87號', NULL, NULL, '公立', '2025-10-03 03:36:44', '2025-10-03 03:36:44'),
(9, '高雄市立高雄國中', '高雄市', '新興區', '高雄市新興區中正三路32號', NULL, NULL, '公立', '2025-10-03 03:36:44', '2025-10-03 03:36:44'),
(10, '基隆市立基隆國中', '基隆市', '中正區', '基隆市中正區中正路115號', NULL, NULL, '公立', '2025-10-03 03:36:44', '2025-10-03 03:36:44');

-- --------------------------------------------------------

--
-- 資料表結構 `schools_contacts`
--

CREATE TABLE `schools_contacts` (
  `id` int(11) NOT NULL,
  `school_id` int(11) DEFAULT NULL COMMENT '學校ID (外鍵關聯到 schools.id)',
  `school_name` varchar(100) NOT NULL COMMENT '學校名稱',
  `contact_name` varchar(50) DEFAULT NULL COMMENT '聯絡人姓名',
  `email` varchar(120) NOT NULL COMMENT '聯絡人Email',
  `city` varchar(20) DEFAULT NULL COMMENT '縣市',
  `district` varchar(20) DEFAULT NULL COMMENT '區/鄉鎮市',
  `is_active` tinyint(1) DEFAULT 1 COMMENT '是否啟用',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `school_data`
--

CREATE TABLE `school_data` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL COMMENT '學校名稱',
  `city` varchar(20) NOT NULL COMMENT '縣市',
  `district` varchar(20) NOT NULL COMMENT '區/鄉鎮市',
  `type` varchar(20) NOT NULL COMMENT '學校類型',
  `school_code` varchar(20) DEFAULT NULL COMMENT '學校代碼',
  `address` varchar(200) DEFAULT NULL COMMENT '學校地址',
  `phone` varchar(20) DEFAULT NULL COMMENT '聯絡電話',
  `website` varchar(200) DEFAULT NULL COMMENT '學校網站',
  `principal` varchar(50) DEFAULT NULL COMMENT '校長姓名',
  `student_count` int(11) DEFAULT 0 COMMENT '學生人數',
  `teacher_count` int(11) DEFAULT 0 COMMENT '教師人數',
  `established_year` year(4) DEFAULT NULL COMMENT '創校年份',
  `is_active` tinyint(1) DEFAULT 1 COMMENT '是否營運中',
  `data_source` varchar(100) DEFAULT '教育部開放資料' COMMENT '資料來源',
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '最後更新時間',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT '建立時間'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='學校基本資料表';

--
-- 傾印資料表的資料 `school_data`
--

INSERT INTO `school_data` (`id`, `name`, `city`, `district`, `type`, `school_code`, `address`, `phone`, `website`, `principal`, `student_count`, `teacher_count`, `established_year`, `is_active`, `data_source`, `last_updated`, `created_at`) VALUES
(1, '中正國中', '台北市', '中正區', '國民中學', 'TP001', '台北市中正區重慶南路一段139號', '02-2381-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:03', '2025-10-08 06:49:03'),
(2, '西松國中', '台北市', '松山區', '國民中學', 'TP002', '台北市松山區南京東路四段133號', '02-2767-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:03', '2025-10-08 06:49:03'),
(3, '永吉國中', '台北市', '信義區', '國民中學', 'TP003', '台北市信義區永吉路30巷158號', '02-2760-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:03', '2025-10-08 06:49:03'),
(4, '中崙國中', '台北市', '松山區', '國民中學', 'TP004', '台北市松山區八德路四段101號', '02-2767-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:03', '2025-10-08 06:49:03'),
(5, '信義國中', '台北市', '信義區', '國民中學', 'TP005', '台北市信義區松仁路158號', '02-2720-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:03', '2025-10-08 06:49:03'),
(6, '松山國中', '台北市', '松山區', '國民中學', 'TP006', '台北市松山區八德路四段101號', '02-2767-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:03', '2025-10-08 06:49:03'),
(7, '敦化國中', '台北市', '松山區', '國民中學', 'TP007', '台北市松山區敦化南路二段94號', '02-2771-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:03', '2025-10-08 06:49:03'),
(8, '介壽國中', '台北市', '松山區', '國民中學', 'TP008', '台北市松山區南京東路四段133號', '02-2767-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:03', '2025-10-08 06:49:03'),
(9, '南港國中', '台北市', '南港區', '國民中學', 'TP009', '台北市南港區向陽路200號', '02-2783-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:03', '2025-10-08 06:49:03'),
(10, '內湖國中', '台北市', '內湖區', '國民中學', 'TP010', '台北市內湖區內湖路二段41號', '02-2790-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:03', '2025-10-08 06:49:03'),
(11, '麗山國中', '台北市', '內湖區', '國民中學', 'TP011', '台北市內湖區內湖路二段41號', '02-2790-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:03', '2025-10-08 06:49:03'),
(12, '大直國中', '台北市', '中山區', '國民中學', 'TP012', '台北市中山區大直街62號', '02-2533-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:03', '2025-10-08 06:49:03'),
(13, '百齡國中', '台北市', '士林區', '國民中學', 'TP013', '台北市士林區承德路四段177號', '02-2881-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:03', '2025-10-08 06:49:03'),
(14, '陽明國中', '台北市', '士林區', '國民中學', 'TP014', '台北市士林區中正路510號', '02-2881-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:03', '2025-10-08 06:49:03'),
(15, '萬華國中', '台北市', '萬華區', '國民中學', 'TP015', '台北市萬華區西藏路201號', '02-2303-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:03', '2025-10-08 06:49:03'),
(16, '大理國中', '台北市', '萬華區', '國民中學', 'TP016', '台北市萬華區大理街170號', '02-2303-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:03', '2025-10-08 06:49:03'),
(17, '華江國中', '台北市', '萬華區', '國民中學', 'TP017', '台北市萬華區環河南路二段250號', '02-2303-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:03', '2025-10-08 06:49:03'),
(18, '成淵國中', '台北市', '大同區', '國民中學', 'TP018', '台北市大同區承德路二段235號', '02-2553-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:03', '2025-10-08 06:49:03'),
(19, '雙園國中', '台北市', '萬華區', '國民中學', 'TP019', '台北市萬華區環河南路二段250號', '02-2303-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:03', '2025-10-08 06:49:03'),
(20, '龍山國中', '台北市', '萬華區', '國民中學', 'TP020', '台北市萬華區環河南路二段250號', '02-2303-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:03', '2025-10-08 06:49:03'),
(21, '板橋國中', '新北市', '板橋區', '國民中學', 'NT001', '新北市板橋區文化路一段188號', '02-2968-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:03', '2025-10-08 06:49:03'),
(22, '海山國中', '新北市', '板橋區', '國民中學', 'NT002', '新北市板橋區文化路一段188號', '02-2968-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:03', '2025-10-08 06:49:03'),
(23, '新莊國中', '新北市', '新莊區', '國民中學', 'NT003', '新北市新莊區中正路211號', '02-2991-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:03', '2025-10-08 06:49:03'),
(24, '丹鳳國中', '新北市', '新莊區', '國民中學', 'NT004', '新北市新莊區中正路211號', '02-2991-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:03', '2025-10-08 06:49:03'),
(25, '泰山國中', '新北市', '泰山區', '國民中學', 'NT005', '新北市泰山區明志路二段84號', '02-2909-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:03', '2025-10-08 06:49:03'),
(26, '林口國中', '新北市', '林口區', '國民中學', 'NT006', '新北市林口區文化一路一段20號', '02-2601-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:03', '2025-10-08 06:49:03'),
(27, '五股國中', '新北市', '五股區', '國民中學', 'NT007', '新北市五股區成泰路一段175號', '02-2291-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:03', '2025-10-08 06:49:03'),
(28, '蘆洲國中', '新北市', '蘆洲區', '國民中學', 'NT008', '新北市蘆洲區中正路243號', '02-2281-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:03', '2025-10-08 06:49:03'),
(29, '三重國中', '新北市', '三重區', '國民中學', 'NT009', '新北市三重區重新路四段92號', '02-2971-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:03', '2025-10-08 06:49:03'),
(30, '永和國中', '新北市', '永和區', '國民中學', 'NT010', '新北市永和區永和路二段100號', '02-2921-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:03', '2025-10-08 06:49:03'),
(31, '永平國中', '新北市', '永和區', '國民中學', 'NT011', '新北市永和區永和路二段100號', '02-2921-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:03', '2025-10-08 06:49:03'),
(32, '中和國中', '新北市', '中和區', '國民中學', 'NT012', '新北市中和區中和路60號', '02-2248-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:03', '2025-10-08 06:49:03'),
(33, '錦和國中', '新北市', '中和區', '國民中學', 'NT013', '新北市中和區中和路60號', '02-2248-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:03', '2025-10-08 06:49:03'),
(34, '新店國中', '新北市', '新店區', '國民中學', 'NT014', '新北市新店區中正路54號', '02-2911-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:03', '2025-10-08 06:49:03'),
(35, '安康國中', '新北市', '新店區', '國民中學', 'NT015', '新北市新店區中正路54號', '02-2911-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:03', '2025-10-08 06:49:03'),
(36, '桃園國中', '桃園市', '桃園區', '國民中學', 'TY001', '桃園市桃園區中正路147號', '03-332-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:03', '2025-10-08 06:49:03'),
(37, '中壢國中', '桃園市', '中壢區', '國民中學', 'TY002', '桃園市中壢區中央路二段136號', '03-425-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:03', '2025-10-08 06:49:03'),
(38, '大園國中', '桃園市', '大園區', '國民中學', 'TY003', '桃園市大園區中正東路二段303號', '03-386-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:03', '2025-10-08 06:49:03'),
(39, '蘆竹國中', '桃園市', '蘆竹區', '國民中學', 'TY004', '桃園市蘆竹區南崁路二段313號', '03-352-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:03', '2025-10-08 06:49:03'),
(40, '南崁國中', '桃園市', '蘆竹區', '國民中學', 'TY005', '桃園市蘆竹區南崁路二段313號', '03-352-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:03', '2025-10-08 06:49:03'),
(41, '龜山國中', '桃園市', '龜山區', '國民中學', 'TY006', '桃園市龜山區萬壽路二段920號', '03-329-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:03', '2025-10-08 06:49:03'),
(42, '八德國中', '桃園市', '八德區', '國民中學', 'TY007', '桃園市八德區興豐路131號', '03-368-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:03', '2025-10-08 06:49:03'),
(43, '大溪國中', '桃園市', '大溪區', '國民中學', 'TY008', '桃園市大溪區員林路一段29號', '03-388-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:03', '2025-10-08 06:49:03'),
(44, '復興國中', '桃園市', '復興區', '國民中學', 'TY009', '桃園市復興區中正路20號', '03-382-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:03', '2025-10-08 06:49:03'),
(45, '龍潭國中', '桃園市', '龍潭區', '國民中學', 'TY010', '桃園市龍潭區中正路210號', '03-479-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:03', '2025-10-08 06:49:03'),
(46, '基隆國中', '基隆市', '中正區', '國民中學', 'KL001', '基隆市中正區中正路116號', '02-2422-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:03', '2025-10-08 06:49:03'),
(47, '安樂國中', '基隆市', '安樂區', '國民中學', 'KL002', '基隆市安樂區安樂路二段164號', '02-2422-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:03', '2025-10-08 06:49:03'),
(48, '八斗國中', '基隆市', '中正區', '國民中學', 'KL003', '基隆市中正區中正路116號', '02-2422-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:03', '2025-10-08 06:49:03'),
(49, '正濱國中', '基隆市', '中正區', '國民中學', 'KL004', '基隆市中正區中正路116號', '02-2422-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:03', '2025-10-08 06:49:03'),
(50, '信義國中', '基隆市', '信義區', '國民中學', 'KL005', '基隆市信義區東信路324號', '02-2422-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:03', '2025-10-08 06:49:03'),
(51, '新竹國中', '新竹市', '東區', '國民中學', 'HSC001', '新竹市東區東門街32號', '03-522-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:03', '2025-10-08 06:49:03'),
(52, '光復國中', '新竹市', '東區', '國民中學', 'HSC002', '新竹市東區東門街32號', '03-522-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:03', '2025-10-08 06:49:03'),
(53, '香山國中', '新竹市', '香山區', '國民中學', 'HSC003', '新竹市香山區香北路168號', '03-538-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:03', '2025-10-08 06:49:03'),
(54, '成德國中', '新竹市', '北區', '國民中學', 'HSC004', '新竹市北區西大路888號', '03-522-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:03', '2025-10-08 06:49:03'),
(55, '建功國中', '新竹市', '東區', '國民中學', 'HSC005', '新竹市東區東門街32號', '03-522-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:03', '2025-10-08 06:49:03'),
(56, '竹北國中', '新竹縣', '竹北市', '國民中學', 'HSH001', '新竹縣竹北市光明六路10號', '03-555-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:03', '2025-10-08 06:49:03'),
(57, '六家國中', '新竹縣', '竹北市', '國民中學', 'HSH002', '新竹縣竹北市光明六路10號', '03-555-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:04', '2025-10-08 06:49:04'),
(58, '湖口國中', '新竹縣', '湖口鄉', '國民中學', 'HSH003', '新竹縣湖口鄉湖口老街58號', '03-599-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:04', '2025-10-08 06:49:04'),
(59, '新湖國中', '新竹縣', '湖口鄉', '國民中學', 'HSH004', '新竹縣湖口鄉湖口老街58號', '03-599-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:04', '2025-10-08 06:49:04'),
(60, '新豐國中', '新竹縣', '新豐鄉', '國民中學', 'HSH005', '新竹縣新豐鄉新豐村15鄰81號', '03-559-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:04', '2025-10-08 06:49:04'),
(61, '台中一中', '台中市', '北區', '國民中學', 'TC001', '台中市北區育才街2號', '04-2222-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:04', '2025-10-08 06:49:04'),
(62, '台中女中', '台中市', '西區', '國民中學', 'TC002', '台中市西區自由路一段95號', '04-2222-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:04', '2025-10-08 06:49:04'),
(63, '文華國中', '台中市', '西屯區', '國民中學', 'TC003', '台中市西屯區寧夏路240號', '04-2222-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:04', '2025-10-08 06:49:04'),
(64, '大業國中', '台中市', '南屯區', '國民中學', 'TC004', '台中市南屯區大業路100號', '04-2222-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:04', '2025-10-08 06:49:04'),
(65, '惠文國中', '台中市', '南屯區', '國民中學', 'TC005', '台中市南屯區大業路100號', '04-2222-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:04', '2025-10-08 06:49:04'),
(66, '台南一中', '台南市', '東區', '國民中學', 'TN001', '台南市東區民族路一段1號', '06-237-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:04', '2025-10-08 06:49:04'),
(67, '台南女中', '台南市', '中西區', '國民中學', 'TN002', '台南市中西區大埔街97號', '06-237-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:04', '2025-10-08 06:49:04'),
(68, '建興國中', '台南市', '中西區', '國民中學', 'TN003', '台南市中西區大埔街97號', '06-237-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:04', '2025-10-08 06:49:04'),
(69, '復興國中', '台南市', '東區', '國民中學', 'TN004', '台南市東區民族路一段1號', '06-237-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:04', '2025-10-08 06:49:04'),
(70, '大成國中', '台南市', '南區', '國民中學', 'TN005', '台南市南區大成路一段5號', '06-237-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:04', '2025-10-08 06:49:04'),
(71, '高雄中學', '高雄市', '三民區', '國民中學', 'KS001', '高雄市三民區建國三路50號', '07-211-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:04', '2025-10-08 06:49:04'),
(72, '高雄女中', '高雄市', '前金區', '國民中學', 'KS002', '高雄市前金區五福三路122號', '07-211-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:04', '2025-10-08 06:49:04'),
(73, '鳳山國中', '高雄市', '鳳山區', '國民中學', 'KS003', '高雄市鳳山區光復路二段130號', '07-211-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:04', '2025-10-08 06:49:04'),
(74, '左營國中', '高雄市', '左營區', '國民中學', 'KS004', '高雄市左營區左營大路483號', '07-211-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:04', '2025-10-08 06:49:04'),
(75, '楠梓國中', '高雄市', '楠梓區', '國民中學', 'KS005', '高雄市楠梓區楠梓路262號', '07-211-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:04', '2025-10-08 06:49:04'),
(76, '宜蘭國中', '宜蘭縣', '宜蘭市', '國民中學', 'IL001', '宜蘭縣宜蘭市復興路二段77號', '03-932-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:04', '2025-10-08 06:49:04'),
(77, '羅東國中', '宜蘭縣', '羅東鎮', '國民中學', 'IL002', '宜蘭縣羅東鎮中正北路98號', '03-955-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:04', '2025-10-08 06:49:04'),
(78, '花蓮國中', '花蓮縣', '花蓮市', '國民中學', 'HL001', '花蓮縣花蓮市中山路440號', '03-822-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:04', '2025-10-08 06:49:04'),
(79, '台東國中', '台東縣', '台東市', '國民中學', 'TT001', '台東縣台東市中山路276號', '089-322-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:04', '2025-10-08 06:49:04'),
(80, '澎湖國中', '澎湖縣', '馬公市', '國民中學', 'PH001', '澎湖縣馬公市中正路7號', '06-927-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:04', '2025-10-08 06:49:04'),
(81, '金門國中', '金門縣', '金城鎮', '國民中學', 'KM001', '金門縣金城鎮珠浦北路38號', '082-325-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:04', '2025-10-08 06:49:04'),
(82, '連江國中', '連江縣', '南竿鄉', '國民中學', 'LC001', '連江縣南竿鄉介壽村76號', '0836-221-1234', '', NULL, 0, 0, NULL, 1, '備用資料', '2025-10-08 06:49:04', '2025-10-08 06:49:04'),
(83, '私立淡江高中附設國中部', '新北市', '', '國民中學', '011301', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:41', '2025-10-08 07:22:41'),
(84, '私立康橋實驗高中附設國中部', '新北市', '', '國民中學', '011302', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:41', '2025-10-08 07:22:41'),
(85, '私立金陵女中附設國中部', '新北市', '', '國民中學', '011306', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:41', '2025-10-08 07:22:41'),
(86, '私立裕德實驗高中附設國中部', '新北市', '', '國民中學', '011307', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:42', '2025-10-08 07:22:42'),
(87, '私立南山高中附設國中部', '新北市', '', '國民中學', '011309', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:42', '2025-10-08 07:22:42'),
(88, '私立恆毅高中附設國中部', '新北市', '', '國民中學', '011310', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:42', '2025-10-08 07:22:42'),
(89, '私立聖心女中附設國中部', '新北市', '', '國民中學', '011311', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:42', '2025-10-08 07:22:42'),
(90, '私立崇義高中附設國中部', '新北市', '', '國民中學', '011312', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:42', '2025-10-08 07:22:42'),
(91, '私立格致高中附設國中部', '新北市', '', '國民中學', '011316', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:42', '2025-10-08 07:22:42'),
(92, '私立醒吾高中附設國中部', '新北市', '', '國民中學', '011317', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:42', '2025-10-08 07:22:42'),
(93, '私立徐匯高中附設國中部', '新北市', '', '國民中學', '011318', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:42', '2025-10-08 07:22:42'),
(94, '私立崇光女中附設國中部', '新北市', '', '國民中學', '011322', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:42', '2025-10-08 07:22:42'),
(95, '私立光仁高中附設國中部', '新北市', '', '國民中學', '011323', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:42', '2025-10-08 07:22:42'),
(96, '私立竹林高中附設國中部', '新北市', '', '國民中學', '011324', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:42', '2025-10-08 07:22:42'),
(97, '私立及人高中附設國中部', '新北市', '', '國民中學', '011325', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:42', '2025-10-08 07:22:42'),
(98, '私立辭修高中附設國中部', '新北市', '', '國民中學', '011329', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:42', '2025-10-08 07:22:42'),
(99, '私立時雨高中附設國中部', '新北市', '', '國民中學', '011399', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:42', '2025-10-08 07:22:42'),
(100, '市立海山高中附設國中部', '新北市', '', '國民中學', '014302', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:42', '2025-10-08 07:22:42'),
(101, '市立三重高中附設國中部', '新北市', '', '國民中學', '014311', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:42', '2025-10-08 07:22:42'),
(102, '市立永平高中附設國中部', '新北市', '', '國民中學', '014315', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:42', '2025-10-08 07:22:42'),
(103, '市立樹林高中附設國中部', '新北市', '', '國民中學', '014322', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:42', '2025-10-08 07:22:42'),
(104, '市立明德高中附設國中部', '新北市', '', '國民中學', '014326', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:42', '2025-10-08 07:22:42'),
(105, '市立秀峰高中附設國中部', '新北市', '', '國民中學', '014332', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:42', '2025-10-08 07:22:42'),
(106, '市立金山高中附設國中部', '新北市', '', '國民中學', '014338', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:42', '2025-10-08 07:22:42'),
(107, '市立安康高中附設國中部', '新北市', '', '國民中學', '014343', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:42', '2025-10-08 07:22:42'),
(108, '市立雙溪高中附設國中部', '新北市', '', '國民中學', '014347', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:42', '2025-10-08 07:22:42'),
(109, '市立石碇高中附設國中部', '新北市', '', '國民中學', '014348', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:42', '2025-10-08 07:22:42'),
(110, '市立丹鳳高中附設國中部', '新北市', '', '國民中學', '014353', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:42', '2025-10-08 07:22:42'),
(111, '市立清水高中附設國中部', '新北市', '', '國民中學', '014356', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:42', '2025-10-08 07:22:42'),
(112, '市立三民高中附設國中部', '新北市', '', '國民中學', '014357', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:42', '2025-10-08 07:22:42'),
(113, '市立錦和高中附設國中部', '新北市', '', '國民中學', '014362', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:42', '2025-10-08 07:22:42'),
(114, '市立光復高中附設國中部', '新北市', '', '國民中學', '014363', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:42', '2025-10-08 07:22:42'),
(115, '市立竹圍高中附設國中部', '新北市', '', '國民中學', '014364', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:42', '2025-10-08 07:22:42'),
(116, '市立北大高中附設國中部', '新北市', '', '國民中學', '014381', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:42', '2025-10-08 07:22:42'),
(117, '市立樟樹國際實創高中附設國中部', '新北市', '', '國民中學', '014468', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:42', '2025-10-08 07:22:42'),
(118, '市立板橋國中', '新北市', '', '國民中學', '014501', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:42', '2025-10-08 07:22:42'),
(119, '市立重慶國中', '新北市', '', '國民中學', '014503', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:42', '2025-10-08 07:22:42'),
(120, '市立江翠國中', '新北市', '', '國民中學', '014504', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:42', '2025-10-08 07:22:42'),
(121, '市立中山國中', '新北市', '', '國民中學', '014505', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:42', '2025-10-08 07:22:42'),
(122, '市立新埔國中', '新北市', '', '國民中學', '014506', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:42', '2025-10-08 07:22:42'),
(123, '市立新莊國中', '新北市', '', '國民中學', '014507', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:42', '2025-10-08 07:22:42'),
(124, '市立新泰國中', '新北市', '', '國民中學', '014508', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:42', '2025-10-08 07:22:42'),
(125, '市立福營國中', '新北市', '', '國民中學', '014509', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:42', '2025-10-08 07:22:42'),
(126, '市立頭前國中', '新北市', '', '國民中學', '014510', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:42', '2025-10-08 07:22:42'),
(127, '市立光榮國中', '新北市', '', '國民中學', '014512', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:42', '2025-10-08 07:22:42'),
(128, '市立明志國中', '新北市', '', '國民中學', '014513', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:42', '2025-10-08 07:22:42'),
(129, '市立碧華國中', '新北市', '', '國民中學', '014514', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:42', '2025-10-08 07:22:42'),
(130, '市立永和國中', '新北市', '', '國民中學', '014516', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:42', '2025-10-08 07:22:42'),
(131, '市立福和國中', '新北市', '', '國民中學', '014517', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:42', '2025-10-08 07:22:42'),
(132, '市立中和國中', '新北市', '', '國民中學', '014518', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:42', '2025-10-08 07:22:42'),
(133, '市立積穗國中', '新北市', '', '國民中學', '014519', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:42', '2025-10-08 07:22:42'),
(134, '市立漳和國中', '新北市', '', '國民中學', '014520', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:42', '2025-10-08 07:22:42'),
(135, '市立鶯歌國中', '新北市', '', '國民中學', '014521', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:42', '2025-10-08 07:22:42'),
(136, '市立柑園國中', '新北市', '', '國民中學', '014523', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:42', '2025-10-08 07:22:42'),
(137, '市立土城國中', '新北市', '', '國民中學', '014524', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:42', '2025-10-08 07:22:42'),
(138, '市立三峽國中', '新北市', '', '國民中學', '014525', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:42', '2025-10-08 07:22:42'),
(139, '市立八里國中', '新北市', '', '國民中學', '014527', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:42', '2025-10-08 07:22:42'),
(140, '市立泰山國中', '新北市', '', '國民中學', '014528', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:42', '2025-10-08 07:22:42'),
(141, '市立五股國中', '新北市', '', '國民中學', '014529', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:42', '2025-10-08 07:22:42'),
(142, '市立蘆洲國中', '新北市', '', '國民中學', '014530', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:42', '2025-10-08 07:22:42'),
(143, '市立林口國中', '新北市', '', '國民中學', '014531', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:42', '2025-10-08 07:22:42'),
(144, '市立汐止國中', '新北市', '', '國民中學', '014533', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:42', '2025-10-08 07:22:42'),
(145, '市立淡水國中', '新北市', '', '國民中學', '014534', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:42', '2025-10-08 07:22:42'),
(146, '市立三芝國中', '新北市', '', '國民中學', '014536', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:42', '2025-10-08 07:22:42'),
(147, '市立石門國中', '新北市', '', '國民中學', '014537', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:42', '2025-10-08 07:22:42'),
(148, '市立萬里國中', '新北市', '', '國民中學', '014539', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:42', '2025-10-08 07:22:42'),
(149, '市立坪林國中', '新北市', '', '國民中學', '014540', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:42', '2025-10-08 07:22:42'),
(150, '市立文山國中', '新北市', '', '國民中學', '014541', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:42', '2025-10-08 07:22:42'),
(151, '市立五峰國中', '新北市', '', '國民中學', '014542', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:42', '2025-10-08 07:22:42'),
(152, '市立瑞芳國中', '新北市', '', '國民中學', '014544', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:42', '2025-10-08 07:22:42'),
(153, '市立欽賢國中', '新北市', '', '國民中學', '014545', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:42', '2025-10-08 07:22:42'),
(154, '市立貢寮國中', '新北市', '', '國民中學', '014546', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(155, '市立深坑國中', '新北市', '', '國民中學', '014549', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(156, '市立平溪國中', '新北市', '', '國民中學', '014550', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(157, '市立烏來國中(小)', '新北市', '', '國民中學', '014551', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(158, '市立溪崑國中', '新北市', '', '國民中學', '014552', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(159, '市立自強國中', '新北市', '', '國民中學', '014554', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(160, '市立中正國中', '新北市', '', '國民中學', '014555', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(161, '市立義學國中', '新北市', '', '國民中學', '014558', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(162, '市立中平國中', '新北市', '', '國民中學', '014559', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(163, '市立鳳鳴國中', '新北市', '', '國民中學', '014560', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(164, '市立三和國中', '新北市', '', '國民中學', '014561', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(165, '市立尖山國中', '新北市', '', '國民中學', '014565', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(166, '市立正德國中', '新北市', '', '國民中學', '014566', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(167, '市立安溪國中', '新北市', '', '國民中學', '014567', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(168, '市立育林國中', '新北市', '', '國民中學', '014569', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(169, '市立青山國中(小)', '新北市', '', '國民中學', '014570', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(170, '市立崇林國中', '新北市', '', '國民中學', '014571', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(171, '市立二重國中', '新北市', '', '國民中學', '014572', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(172, '市立大觀國中', '新北市', '', '國民中學', '014573', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(173, '市立三多國中', '新北市', '', '國民中學', '014574', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(174, '市立忠孝國中', '新北市', '', '國民中學', '014575', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(175, '市立鷺江國中', '新北市', '', '國民中學', '014576', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(176, '市立桃子腳國中(小)', '新北市', '', '國民中學', '014577', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(177, '市立豐珠國中(小)', '新北市', '', '國民中學', '014578', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(178, '市立佳林國中', '新北市', '', '國民中學', '014579', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(179, '市立達觀國中(小)', '新北市', '', '國民中學', '014580', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(180, '私立慧燈高中附設國中部', '宜蘭縣', '', '國民中學', '021301', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(181, '私立中道高中附設國中部', '宜蘭縣', '', '國民中學', '021310', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(182, '縣立南澳高中附設國中部', '宜蘭縣', '', '國民中學', '024322', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(183, '縣立慈心華德福教育實驗高中附設國中', '宜蘭縣', '', '國民中學', '024325', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(184, '縣立宜蘭國中', '宜蘭縣', '', '國民中學', '024501', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(185, '縣立中華國中', '宜蘭縣', '', '國民中學', '024502', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(186, '縣立復興國中', '宜蘭縣', '', '國民中學', '024503', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(187, '縣立羅東國中', '宜蘭縣', '', '國民中學', '024504', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(188, '縣立東光國中', '宜蘭縣', '', '國民中學', '024505', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(189, '縣立國華國中', '宜蘭縣', '', '國民中學', '024506', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(190, '縣立頭城國中', '宜蘭縣', '', '國民中學', '024507', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(191, '縣立蘇澳國中', '宜蘭縣', '', '國民中學', '024508', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(192, '縣立文化國中', '宜蘭縣', '', '國民中學', '024509', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(193, '縣立南安國中', '宜蘭縣', '', '國民中學', '024510', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(194, '縣立三星國中', '宜蘭縣', '', '國民中學', '024511', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(195, '縣立礁溪國中', '宜蘭縣', '', '國民中學', '024512', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(196, '縣立吳沙國中', '宜蘭縣', '', '國民中學', '024513', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(197, '縣立冬山國中', '宜蘭縣', '', '國民中學', '024514', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(198, '縣立順安國中', '宜蘭縣', '', '國民中學', '024515', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(199, '縣立五結國中', '宜蘭縣', '', '國民中學', '024516', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(200, '縣立興中國中', '宜蘭縣', '', '國民中學', '024517', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(201, '縣立利澤國中', '宜蘭縣', '', '國民中學', '024518', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(202, '縣立員山國中', '宜蘭縣', '', '國民中學', '024519', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(203, '縣立內城國中(小)', '宜蘭縣', '', '國民中學', '024520', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(204, '縣立壯圍國中', '宜蘭縣', '', '國民中學', '024521', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(205, '縣立大同國中', '宜蘭縣', '', '國民中學', '024523', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(206, '縣立凱旋國中', '宜蘭縣', '', '國民中學', '024524', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(207, '縣立人文國中(小)', '宜蘭縣', '', '國民中學', '024526', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(208, '私立桃園市漢英高中附設國中部', '桃園市', '', '國民中學', '031301', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(209, '私立六和高中附設國中部', '桃園市', '', '國民中學', '031310', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(210, '私立復旦高中附設國中部', '桃園市', '', '國民中學', '031311', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(211, '私立治平高中附設國中部', '桃園市', '', '國民中學', '031312', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(212, '私立振聲高中附設國中部', '桃園市', '', '國民中學', '031313', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(213, '私立清華高中附設國中部', '桃園市', '', '國民中學', '031319', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(214, '私立新興高中附設國中部', '桃園市', '', '國民中學', '031320', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(215, '私立大華高中附設國中部', '桃園市', '', '國民中學', '031326', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(216, '私立有得國中(小)', '桃園市', '', '國民中學', '031502', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(217, '私立康萊爾國中(小)', '桃園市', '', '國民中學', '031503', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(218, '市立觀音高中附設國中部', '桃園市', '', '國民中學', '034332', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(219, '市立新屋高中附設國中部', '桃園市', '', '國民中學', '034335', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(220, '市立永豐高中附設國中部', '桃園市', '', '國民中學', '034347', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(221, '市立桃園國中', '桃園市', '', '國民中學', '034501', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(222, '市立青溪國中', '桃園市', '', '國民中學', '034502', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(223, '市立文昌國中', '桃園市', '', '國民中學', '034503', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(224, '市立建國國中', '桃園市', '', '國民中學', '034504', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(225, '市立中興國中', '桃園市', '', '國民中學', '034505', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:43', '2025-10-08 07:22:43'),
(226, '市立南崁國中', '桃園市', '', '國民中學', '034506', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(227, '市立山腳國中', '桃園市', '', '國民中學', '034507', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(228, '市立大竹國中', '桃園市', '', '國民中學', '034508', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(229, '市立大園國中', '桃園市', '', '國民中學', '034509', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(230, '市立竹圍國中', '桃園市', '', '國民中學', '034510', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(231, '市立大溪國中', '桃園市', '', '國民中學', '034511', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(232, '市立仁和國中', '桃園市', '', '國民中學', '034513', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(233, '市立大崗國中', '桃園市', '', '國民中學', '034515', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(234, '市立八德國中', '桃園市', '', '國民中學', '034516', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(235, '市立大成國中', '桃園市', '', '國民中學', '034517', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(236, '市立中壢國中', '桃園市', '', '國民中學', '034518', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(237, '市立平南國中', '桃園市', '', '國民中學', '034520', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(238, '市立新明國中', '桃園市', '', '國民中學', '034521', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(239, '市立內壢國中', '桃園市', '', '國民中學', '034522', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(240, '市立大崙國中', '桃園市', '', '國民中學', '034523', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(241, '市立龍岡國中', '桃園市', '', '國民中學', '034524', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(242, '市立興南國中', '桃園市', '', '國民中學', '034525', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(243, '市立自強國中', '桃園市', '', '國民中學', '034526', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(244, '市立東興國中', '桃園市', '', '國民中學', '034527', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(245, '市立楊梅國中', '桃園市', '', '國民中學', '034528', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(246, '市立仁美國中', '桃園市', '', '國民中學', '034529', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(247, '市立富岡國中', '桃園市', '', '國民中學', '034530', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(248, '市立瑞原國中', '桃園市', '', '國民中學', '034531', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(249, '市立觀音國中', '桃園市', '', '國民中學', '034533', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(250, '市立草漯國中', '桃園市', '', '國民中學', '034534', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(251, '市立大坡國中', '桃園市', '', '國民中學', '034536', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(252, '市立永安國中', '桃園市', '', '國民中學', '034537', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(253, '市立龍潭國中', '桃園市', '', '國民中學', '034538', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(254, '市立凌雲國中', '桃園市', '', '國民中學', '034539', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(255, '市立石門國中', '桃園市', '', '國民中學', '034540', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(256, '市立介壽國中', '桃園市', '', '國民中學', '034541', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(257, '市立慈文國中', '桃園市', '', '國民中學', '034542', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(258, '市立平興國中', '桃園市', '', '國民中學', '034543', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(259, '市立楊明國中', '桃園市', '', '國民中學', '034544', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(260, '市立龍興國中', '桃園市', '', '國民中學', '034545', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(261, '市立福豐國中', '桃園市', '', '國民中學', '034546', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(262, '市立東安國中', '桃園市', '', '國民中學', '034549', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(263, '市立光明國中', '桃園市', '', '國民中學', '034550', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(264, '市立同德國中', '桃園市', '', '國民中學', '034551', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(265, '市立幸福國中', '桃園市', '', '國民中學', '034552', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(266, '市立大有國中', '桃園市', '', '國民中學', '034554', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(267, '市立龜山國中', '桃園市', '', '國民中學', '034555', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(268, '市立會稽國中', '桃園市', '', '國民中學', '034556', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(269, '市立楊光國中(小)', '桃園市', '', '國民中學', '034557', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(270, '市立迴龍國中(小)', '桃園市', '', '國民中學', '034559', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(271, '市立平鎮國中', '桃園市', '', '國民中學', '034560', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(272, '市立武漢國中', '桃園市', '', '國民中學', '034561', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(273, '市立經國國中', '桃園市', '', '國民中學', '034562', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(274, '市立過嶺國中', '桃園市', '', '國民中學', '034563', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(275, '市立瑞坪國中', '桃園市', '', '國民中學', '034564', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(276, '市立青埔國中', '桃園市', '', '國民中學', '034565', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(277, '私立義民高中附設國中部', '新竹縣', '', '國民中學', '041303', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(278, '私立忠信高中附設國中部', '新竹縣', '', '國民中學', '041305', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(279, '私立東泰中學附設國中部', '新竹縣', '', '國民中學', '041306', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(280, '私立仰德高中附設國中部', '新竹縣', '', '國民中學', '041307', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(281, '私立康乃薾國中(小)', '新竹縣', '', '國民中學', '041501', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(282, '縣立六家高中附設國中部', '新竹縣', '', '國民中學', '044311', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(283, '縣立湖口高中附設國中部', '新竹縣', '', '國民中學', '044320', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(284, '縣立竹東國中', '新竹縣', '', '國民中學', '044501', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(285, '縣立二重國中', '新竹縣', '', '國民中學', '044502', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(286, '縣立員東國中', '新竹縣', '', '國民中學', '044503', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(287, '縣立關西國中', '新竹縣', '', '國民中學', '044504', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(288, '縣立石光國中', '新竹縣', '', '國民中學', '044505', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(289, '縣立富光國中', '新竹縣', '', '國民中學', '044506', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(290, '縣立新埔國中', '新竹縣', '', '國民中學', '044507', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(291, '縣立照門國中', '新竹縣', '', '國民中學', '044508', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(292, '縣立竹北國中', '新竹縣', '', '國民中學', '044509', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(293, '縣立鳳岡國中', '新竹縣', '', '國民中學', '044510', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(294, '縣立芎林國中', '新竹縣', '', '國民中學', '044512', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(295, '縣立新豐國中', '新竹縣', '', '國民中學', '044513', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(296, '縣立精華國中', '新竹縣', '', '國民中學', '044514', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(297, '縣立橫山國中', '新竹縣', '', '國民中學', '044515', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(298, '縣立華山國中', '新竹縣', '', '國民中學', '044516', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(299, '縣立寶山國中', '新竹縣', '', '國民中學', '044517', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(300, '縣立北埔國中', '新竹縣', '', '國民中學', '044518', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(301, '縣立峨眉國中', '新竹縣', '', '國民中學', '044519', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(302, '縣立新湖國中', '新竹縣', '', '國民中學', '044521', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(303, '縣立中正國中', '新竹縣', '', '國民中學', '044522', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(304, '縣立五峰國中', '新竹縣', '', '國民中學', '044523', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(305, '縣立尖石國中', '新竹縣', '', '國民中學', '044524', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(306, '縣立忠孝國中', '新竹縣', '', '國民中學', '044525', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(307, '縣立博愛國中', '新竹縣', '', '國民中學', '044526', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(308, '縣立仁愛國中', '新竹縣', '', '國民中學', '044527', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(309, '縣立自強國中', '新竹縣', '', '國民中學', '044528', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(310, '縣立成功國中', '新竹縣', '', '國民中學', '044529', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(311, '縣立東興國中', '新竹縣', '', '國民中學', '044530', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(312, '國立卓蘭高中附設國中部', '苗栗縣', '', '國民中學', '050314', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(313, '私立君毅高中附設國中部', '苗栗縣', '', '國民中學', '051302', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(314, '私立建台高中附設國中部', '苗栗縣', '', '國民中學', '051306', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(315, '私立全人實驗高中附設國中部', '苗栗縣', '', '國民中學', '051307', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(316, '縣立三義高中附設國中部', '苗栗縣', '', '國民中學', '054308', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(317, '縣立苑裡高中附設國中部', '苗栗縣', '', '國民中學', '054309', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(318, '縣立興華高中附設國中部', '苗栗縣', '', '國民中學', '054317', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(319, '縣立大同高中附設國中部', '苗栗縣', '', '國民中學', '054333', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:44', '2025-10-08 07:22:44'),
(320, '縣立苗栗國中', '苗栗縣', '', '國民中學', '054501', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(321, '縣立大倫國中', '苗栗縣', '', '國民中學', '054502', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(322, '縣立明仁國中', '苗栗縣', '', '國民中學', '054503', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(323, '縣立頭屋國中', '苗栗縣', '', '國民中學', '054504', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(324, '縣立公館國中', '苗栗縣', '', '國民中學', '054505', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(325, '縣立鶴岡國中', '苗栗縣', '', '國民中學', '054506', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(326, '縣立文林國中', '苗栗縣', '', '國民中學', '054507', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(327, '縣立致民國中', '苗栗縣', '', '國民中學', '054510', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(328, '縣立通霄國中', '苗栗縣', '', '國民中學', '054511', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(329, '縣立南和國中', '苗栗縣', '', '國民中學', '054512', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(330, '縣立烏眉國中', '苗栗縣', '', '國民中學', '054513', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(331, '縣立啟新國中', '苗栗縣', '', '國民中學', '054514', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(332, '縣立西湖國中', '苗栗縣', '', '國民中學', '054515', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(333, '縣立頭份國中', '苗栗縣', '', '國民中學', '054516', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(334, '縣立文英國中', '苗栗縣', '', '國民中學', '054518', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(335, '縣立竹南國中', '苗栗縣', '', '國民中學', '054519', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(336, '縣立照南國中', '苗栗縣', '', '國民中學', '054520', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45');
INSERT INTO `school_data` (`id`, `name`, `city`, `district`, `type`, `school_code`, `address`, `phone`, `website`, `principal`, `student_count`, `teacher_count`, `established_year`, `is_active`, `data_source`, `last_updated`, `created_at`) VALUES
(337, '縣立三灣國中', '苗栗縣', '', '國民中學', '054521', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(338, '縣立南庄國中', '苗栗縣', '', '國民中學', '054522', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(339, '縣立造橋國中', '苗栗縣', '', '國民中學', '054523', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(340, '縣立大西國中', '苗栗縣', '', '國民中學', '054524', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(341, '縣立後龍國中', '苗栗縣', '', '國民中學', '054525', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(342, '縣立維真國中', '苗栗縣', '', '國民中學', '054526', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(343, '縣立大湖國中', '苗栗縣', '', '國民中學', '054527', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(344, '縣立南湖國中', '苗栗縣', '', '國民中學', '054528', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(345, '縣立獅潭國中', '苗栗縣', '', '國民中學', '054529', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(346, '縣立泰安國中(小)', '苗栗縣', '', '國民中學', '054531', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(347, '縣立建國國中', '苗栗縣', '', '國民中學', '054532', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(348, '縣立福興武術國中(小)', '苗栗縣', '', '國民中學', '054534', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(349, '縣立新港國中(小)', '苗栗縣', '', '國民中學', '054535', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(350, '國立中科實中附設國中部', '臺中市', '', '國民中學', '060323', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(351, '私立常春藤高中附設國中部', '臺中市', '', '國民中學', '061301', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(352, '私立大明高中附設國中部', '臺中市', '', '國民中學', '061310', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(353, '私立明道高中附設國中部', '臺中市', '', '國民中學', '061313', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(354, '私立僑泰中學附設國中部', '臺中市', '', '國民中學', '061314', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(355, '私立華盛頓高中附設國中部', '臺中市', '', '國民中學', '061315', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(356, '私立弘文高中附設國中部', '臺中市', '', '國民中學', '061317', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(357, '私立立人高中附設國中部', '臺中市', '', '國民中學', '061318', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(358, '市立善水國中(小)', '臺中市', '', '國民中學', '063501', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(359, '市立后綜高中附設國中部', '臺中市', '', '國民中學', '064308', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(360, '市立大里高中附設國中部', '臺中市', '', '國民中學', '064324', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(361, '市立新社高中附設國中部', '臺中市', '', '國民中學', '064328', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(362, '市立長億高中附設國中部', '臺中市', '', '國民中學', '064336', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(363, '市立中港高中附設國中部', '臺中市', '', '國民中學', '064342', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(364, '市立龍津高中附設國中部', '臺中市', '', '國民中學', '064350', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(365, '市立豐原國中', '臺中市', '', '國民中學', '064501', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(366, '市立豐東國中', '臺中市', '', '國民中學', '064502', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(367, '市立豐南國中', '臺中市', '', '國民中學', '064503', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(368, '市立潭子國中', '臺中市', '', '國民中學', '064504', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(369, '市立大雅國中', '臺中市', '', '國民中學', '064505', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(370, '市立神岡國中', '臺中市', '', '國民中學', '064506', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(371, '市立后里國中', '臺中市', '', '國民中學', '064507', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(372, '市立外埔國中', '臺中市', '', '國民中學', '064509', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(373, '市立大甲國中', '臺中市', '', '國民中學', '064510', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(374, '市立日南國中', '臺中市', '', '國民中學', '064511', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(375, '市立大安國中', '臺中市', '', '國民中學', '064512', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(376, '市立清水國中', '臺中市', '', '國民中學', '064513', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(377, '市立清泉國中', '臺中市', '', '國民中學', '064514', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(378, '市立沙鹿國中', '臺中市', '', '國民中學', '064515', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(379, '市立梧棲國中', '臺中市', '', '國民中學', '064516', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(380, '市立龍井國中', '臺中市', '', '國民中學', '064517', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(381, '市立四箴國中', '臺中市', '', '國民中學', '064518', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(382, '市立大道國中', '臺中市', '', '國民中學', '064519', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(383, '市立烏日國中', '臺中市', '', '國民中學', '064520', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(384, '市立溪南國中', '臺中市', '', '國民中學', '064521', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(385, '市立霧峰國中', '臺中市', '', '國民中學', '064522', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(386, '市立光復國中(小)', '臺中市', '', '國民中學', '064523', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(387, '市立太平國中', '臺中市', '', '國民中學', '064525', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(388, '市立中平國中', '臺中市', '', '國民中學', '064526', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(389, '市立石岡國中', '臺中市', '', '國民中學', '064527', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(390, '市立東勢國中', '臺中市', '', '國民中學', '064529', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(391, '市立東華國中', '臺中市', '', '國民中學', '064530', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(392, '市立東新國中', '臺中市', '', '國民中學', '064531', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(393, '市立成功國中', '臺中市', '', '國民中學', '064532', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(394, '市立和平國中', '臺中市', '', '國民中學', '064533', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(395, '市立北勢國中', '臺中市', '', '國民中學', '064534', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(396, '市立鹿寮國中', '臺中市', '', '國民中學', '064535', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(397, '市立光榮國中', '臺中市', '', '國民中學', '064537', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(398, '市立潭秀國中', '臺中市', '', '國民中學', '064538', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(399, '市立順天國中', '臺中市', '', '國民中學', '064539', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(400, '市立清海國中', '臺中市', '', '國民中學', '064540', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(401, '市立大華國中', '臺中市', '', '國民中學', '064541', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(402, '市立新光國中', '臺中市', '', '國民中學', '064543', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(403, '市立光正國中', '臺中市', '', '國民中學', '064544', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:45', '2025-10-08 07:22:45'),
(404, '市立豐陽國中', '臺中市', '', '國民中學', '064545', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(405, '市立光德國中', '臺中市', '', '國民中學', '064546', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(406, '市立立新國中', '臺中市', '', '國民中學', '064547', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(407, '市立爽文國中', '臺中市', '', '國民中學', '064548', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(408, '市立公明國中', '臺中市', '', '國民中學', '064549', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(409, '市立神圳國中', '臺中市', '', '國民中學', '064551', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(410, '市立梨山國中(小)', '臺中市', '', '國民中學', '064552', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(411, '私立精誠高中附設國中部', '彰化縣', '', '國民中學', '071311', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(412, '私立文興高中附設國中部', '彰化縣', '', '國民中學', '071317', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(413, '私立正德高中附設國中部', '彰化縣', '', '國民中學', '071318', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(414, '縣立彰化藝術高中附設國中部', '彰化縣', '', '國民中學', '074308', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(415, '縣立二林高中附設國中部', '彰化縣', '', '國民中學', '074313', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(416, '縣立和美高中附設國中部', '彰化縣', '', '國民中學', '074323', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(417, '縣立田中高中附設國中部', '彰化縣', '', '國民中學', '074328', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(418, '縣立成功高中附設國中部', '彰化縣', '', '國民中學', '074339', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(419, '縣立北斗國中', '彰化縣', '', '國民中學', '074501', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(420, '縣立鹿港國中', '彰化縣', '', '國民中學', '074502', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(421, '縣立鹿鳴國中', '彰化縣', '', '國民中學', '074503', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(422, '縣立線西國中', '彰化縣', '', '國民中學', '074504', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(423, '縣立陽明國中', '彰化縣', '', '國民中學', '074505', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(424, '縣立彰安國中', '彰化縣', '', '國民中學', '074506', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(425, '縣立彰德國中', '彰化縣', '', '國民中學', '074507', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(426, '縣立芬園國中', '彰化縣', '', '國民中學', '074509', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(427, '縣立員林國中', '彰化縣', '', '國民中學', '074510', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(428, '縣立明倫國中', '彰化縣', '', '國民中學', '074511', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(429, '縣立萬興國中', '彰化縣', '', '國民中學', '074512', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(430, '縣立竹塘國中', '彰化縣', '', '國民中學', '074514', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(431, '縣立大城國中', '彰化縣', '', '國民中學', '074515', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(432, '縣立草湖國中', '彰化縣', '', '國民中學', '074516', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(433, '縣立芳苑國中', '彰化縣', '', '國民中學', '074517', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(434, '縣立溪湖國中', '彰化縣', '', '國民中學', '074518', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(435, '縣立埔鹽國中', '彰化縣', '', '國民中學', '074519', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(436, '縣立埔心國中', '彰化縣', '', '國民中學', '074520', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(437, '縣立福興國中', '彰化縣', '', '國民中學', '074521', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(438, '縣立秀水國中', '彰化縣', '', '國民中學', '074522', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(439, '縣立伸港國中', '彰化縣', '', '國民中學', '074524', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(440, '縣立大村國中', '彰化縣', '', '國民中學', '074525', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(441, '縣立花壇國中', '彰化縣', '', '國民中學', '074526', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(442, '縣立永靖國中', '彰化縣', '', '國民中學', '074527', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(443, '縣立二水國中', '彰化縣', '', '國民中學', '074529', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(444, '縣立社頭國中', '彰化縣', '', '國民中學', '074530', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(445, '縣立田尾國中', '彰化縣', '', '國民中學', '074531', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(446, '縣立溪州國中', '彰化縣', '', '國民中學', '074532', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(447, '縣立溪陽國中', '彰化縣', '', '國民中學', '074533', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(448, '縣立埤頭國中', '彰化縣', '', '國民中學', '074534', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(449, '縣立和群國中', '彰化縣', '', '國民中學', '074535', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(450, '縣立大同國中', '彰化縣', '', '國民中學', '074536', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(451, '縣立原斗國中', '彰化縣', '', '國民中學', '074537', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(452, '縣立彰興國中', '彰化縣', '', '國民中學', '074538', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(453, '縣立彰泰國中', '彰化縣', '', '國民中學', '074540', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(454, '縣立信義國中(小)', '彰化縣', '', '國民中學', '074541', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(455, '私立三育高中附設國中部', '南投縣', '', '國民中學', '081312', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(456, '私立弘明實驗高中國中部', '南投縣', '', '國民中學', '081313', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(457, '私立普台中學國中部', '南投縣', '', '國民中學', '081314', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(458, '私立均頭國中(小)', '南投縣', '', '國民中學', '081502', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(459, '縣立旭光高中附設國中部', '南投縣', '', '國民中學', '084309', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(460, '縣立南投國中', '南投縣', '', '國民中學', '084501', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(461, '縣立南崗國中', '南投縣', '', '國民中學', '084502', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(462, '縣立中興國中', '南投縣', '', '國民中學', '084503', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(463, '縣立鳳鳴國中', '南投縣', '', '國民中學', '084504', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(464, '縣立埔里國中', '南投縣', '', '國民中學', '084505', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(465, '縣立大成國中', '南投縣', '', '國民中學', '084506', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(466, '縣立宏仁國中', '南投縣', '', '國民中學', '084507', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(467, '縣立草屯國中', '南投縣', '', '國民中學', '084508', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(468, '縣立日新國中', '南投縣', '', '國民中學', '084510', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(469, '縣立竹山國中', '南投縣', '', '國民中學', '084511', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(470, '縣立延和國中', '南投縣', '', '國民中學', '084512', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(471, '縣立社寮國中', '南投縣', '', '國民中學', '084513', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(472, '縣立瑞竹國中', '南投縣', '', '國民中學', '084514', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(473, '縣立集集國中', '南投縣', '', '國民中學', '084515', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(474, '縣立名間國中', '南投縣', '', '國民中學', '084516', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(475, '縣立三光國中', '南投縣', '', '國民中學', '084517', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(476, '縣立鹿谷國中', '南投縣', '', '國民中學', '084518', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(477, '縣立瑞峰國中', '南投縣', '', '國民中學', '084519', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:46', '2025-10-08 07:22:46'),
(478, '縣立中寮國中', '南投縣', '', '國民中學', '084520', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:47', '2025-10-08 07:22:47'),
(479, '縣立爽文國中', '南投縣', '', '國民中學', '084521', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:47', '2025-10-08 07:22:47'),
(480, '縣立魚池國中', '南投縣', '', '國民中學', '084522', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:47', '2025-10-08 07:22:47'),
(481, '縣立明潭國中', '南投縣', '', '國民中學', '084523', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:47', '2025-10-08 07:22:47'),
(482, '縣立國姓國中', '南投縣', '', '國民中學', '084524', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:47', '2025-10-08 07:22:47'),
(483, '縣立北梅國中', '南投縣', '', '國民中學', '084525', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:47', '2025-10-08 07:22:47'),
(484, '縣立北山國中', '南投縣', '', '國民中學', '084526', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:47', '2025-10-08 07:22:47'),
(485, '縣立水里國中', '南投縣', '', '國民中學', '084527', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:47', '2025-10-08 07:22:47'),
(486, '縣立民和國中', '南投縣', '', '國民中學', '084528', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:47', '2025-10-08 07:22:47'),
(487, '縣立信義國中', '南投縣', '', '國民中學', '084529', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:47', '2025-10-08 07:22:47'),
(488, '縣立同富國中', '南投縣', '', '國民中學', '084530', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:47', '2025-10-08 07:22:47'),
(489, '縣立仁愛國中', '南投縣', '', '國民中學', '084531', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:47', '2025-10-08 07:22:47'),
(490, '縣立營北國中', '南投縣', '', '國民中學', '084532', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:47', '2025-10-08 07:22:47'),
(491, '私立永年高中附設國中部', '雲林縣', '', '國民中學', '091307', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:47', '2025-10-08 07:22:47'),
(492, '私立正心高中附設國中部', '雲林縣', '', '國民中學', '091308', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:47', '2025-10-08 07:22:47'),
(493, '私立文生高中附設國中部', '雲林縣', '', '國民中學', '091311', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:47', '2025-10-08 07:22:47'),
(494, '私立揚子高中附設國中部', '雲林縣', '', '國民中學', '091316', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:47', '2025-10-08 07:22:47'),
(495, '私立維多利亞高中附設國中部', '雲林縣', '', '國民中學', '091320', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:47', '2025-10-08 07:22:47'),
(496, '私立淵明國中', '雲林縣', '', '國民中學', '091502', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:47', '2025-10-08 07:22:47'),
(497, '私立東南國中(代用)', '雲林縣', '', '國民中學', '091503', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:47', '2025-10-08 07:22:47'),
(498, '私立福智國中', '雲林縣', '', '國民中學', '091505', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:47', '2025-10-08 07:22:47'),
(499, '縣立斗南高中附設國中部', '雲林縣', '', '國民中學', '094301', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:47', '2025-10-08 07:22:47'),
(500, '縣立麥寮高中附設國中部', '雲林縣', '', '國民中學', '094307', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:47', '2025-10-08 07:22:47'),
(501, '縣立古坑華德福實驗高中附設國中部', '雲林縣', '', '國民中學', '094308', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:47', '2025-10-08 07:22:47'),
(502, '縣立東明國中', '雲林縣', '', '國民中學', '094502', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:47', '2025-10-08 07:22:47'),
(503, '縣立大埤國中', '雲林縣', '', '國民中學', '094503', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:47', '2025-10-08 07:22:47'),
(504, '縣立飛沙國中', '雲林縣', '', '國民中學', '094504', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:47', '2025-10-08 07:22:47'),
(505, '縣立四湖國中', '雲林縣', '', '國民中學', '094505', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:47', '2025-10-08 07:22:47'),
(506, '縣立水林國中', '雲林縣', '', '國民中學', '094506', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:47', '2025-10-08 07:22:47'),
(507, '縣立二崙國中', '雲林縣', '', '國民中學', '094508', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:47', '2025-10-08 07:22:47'),
(508, '縣立褒忠國中', '雲林縣', '', '國民中學', '094509', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:47', '2025-10-08 07:22:47'),
(509, '縣立莿桐國中', '雲林縣', '', '國民中學', '094510', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:47', '2025-10-08 07:22:47'),
(510, '縣立崙背國中', '雲林縣', '', '國民中學', '094511', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:47', '2025-10-08 07:22:47'),
(511, '縣立古坑國中(小)', '雲林縣', '', '國民中學', '094512', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:47', '2025-10-08 07:22:47'),
(512, '縣立東勢國中', '雲林縣', '', '國民中學', '094513', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:47', '2025-10-08 07:22:47'),
(513, '縣立元長國中', '雲林縣', '', '國民中學', '094514', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:47', '2025-10-08 07:22:47'),
(514, '縣立斗六國中', '雲林縣', '', '國民中學', '094515', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:47', '2025-10-08 07:22:47'),
(515, '縣立雲林國中', '雲林縣', '', '國民中學', '094516', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:47', '2025-10-08 07:22:47'),
(516, '縣立虎尾國中', '雲林縣', '', '國民中學', '094517', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:47', '2025-10-08 07:22:47'),
(517, '縣立崇德國中', '雲林縣', '', '國民中學', '094518', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:47', '2025-10-08 07:22:47'),
(518, '縣立西螺國中', '雲林縣', '', '國民中學', '094519', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:47', '2025-10-08 07:22:47'),
(519, '縣立北港國中', '雲林縣', '', '國民中學', '094520', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:47', '2025-10-08 07:22:47'),
(520, '縣立建國國中', '雲林縣', '', '國民中學', '094521', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:47', '2025-10-08 07:22:47'),
(521, '縣立宜梧國中', '雲林縣', '', '國民中學', '094522', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:47', '2025-10-08 07:22:47'),
(522, '縣立口湖國中', '雲林縣', '', '國民中學', '094523', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:47', '2025-10-08 07:22:47'),
(523, '縣立臺西國中', '雲林縣', '', '國民中學', '094524', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:47', '2025-10-08 07:22:47'),
(524, '縣立土庫國中', '雲林縣', '', '國民中學', '094525', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:47', '2025-10-08 07:22:47'),
(525, '縣立蔦松國中', '雲林縣', '', '國民中學', '094526', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:47', '2025-10-08 07:22:47'),
(526, '縣立東和國中', '雲林縣', '', '國民中學', '094527', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:47', '2025-10-08 07:22:47'),
(527, '縣立馬光國中', '雲林縣', '', '國民中學', '094528', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:47', '2025-10-08 07:22:47'),
(528, '縣立石榴國中', '雲林縣', '', '國民中學', '094529', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:47', '2025-10-08 07:22:47'),
(529, '縣立林內國中', '雲林縣', '', '國民中學', '094530', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:47', '2025-10-08 07:22:47'),
(530, '縣立東仁國中', '雲林縣', '', '國民中學', '094543', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:47', '2025-10-08 07:22:47'),
(531, '縣立樟湖生態國中(小)', '雲林縣', '', '國民中學', '094544', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:47', '2025-10-08 07:22:47'),
(532, '私立同濟高中附設國中部', '嘉義縣', '', '國民中學', '101303', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:47', '2025-10-08 07:22:47'),
(533, '私立協同高中附設國中部', '嘉義縣', '', '國民中學', '101304', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:47', '2025-10-08 07:22:47'),
(534, '縣立竹崎高中附設國中部', '嘉義縣', '', '國民中學', '104319', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:47', '2025-10-08 07:22:47'),
(535, '縣立永慶高中附設國中部', '嘉義縣', '', '國民中學', '104326', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:47', '2025-10-08 07:22:47'),
(536, '縣立朴子國中', '嘉義縣', '', '國民中學', '104501', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:47', '2025-10-08 07:22:47'),
(537, '縣立東石國中', '嘉義縣', '', '國民中學', '104502', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:47', '2025-10-08 07:22:47'),
(538, '縣立布袋國中', '嘉義縣', '', '國民中學', '104503', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:47', '2025-10-08 07:22:47'),
(539, '縣立過溝國中', '嘉義縣', '', '國民中學', '104504', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:47', '2025-10-08 07:22:47'),
(540, '縣立大林國中', '嘉義縣', '', '國民中學', '104505', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:47', '2025-10-08 07:22:47'),
(541, '縣立新港國中', '嘉義縣', '', '國民中學', '104506', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:47', '2025-10-08 07:22:47'),
(542, '縣立民雄國中', '嘉義縣', '', '國民中學', '104507', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:47', '2025-10-08 07:22:47'),
(543, '縣立大吉國中', '嘉義縣', '', '國民中學', '104508', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:48', '2025-10-08 07:22:48'),
(544, '縣立六嘉國中', '嘉義縣', '', '國民中學', '104509', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:48', '2025-10-08 07:22:48'),
(545, '縣立太保國中', '嘉義縣', '', '國民中學', '104511', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:48', '2025-10-08 07:22:48'),
(546, '縣立嘉新國中', '嘉義縣', '', '國民中學', '104512', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:48', '2025-10-08 07:22:48'),
(547, '縣立溪口國中', '嘉義縣', '', '國民中學', '104513', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:48', '2025-10-08 07:22:48'),
(548, '縣立鹿草國中', '嘉義縣', '', '國民中學', '104514', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:48', '2025-10-08 07:22:48'),
(549, '縣立東榮國中', '嘉義縣', '', '國民中學', '104515', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:48', '2025-10-08 07:22:48'),
(550, '縣立水上國中', '嘉義縣', '', '國民中學', '104516', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:48', '2025-10-08 07:22:48'),
(551, '縣立忠和國中', '嘉義縣', '', '國民中學', '104517', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:48', '2025-10-08 07:22:48'),
(552, '縣立中埔國中', '嘉義縣', '', '國民中學', '104518', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:48', '2025-10-08 07:22:48'),
(553, '縣立昇平國中', '嘉義縣', '', '國民中學', '104520', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:48', '2025-10-08 07:22:48'),
(554, '縣立義竹國中', '嘉義縣', '', '國民中學', '104521', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:48', '2025-10-08 07:22:48'),
(555, '縣立民和國中', '嘉義縣', '', '國民中學', '104522', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:48', '2025-10-08 07:22:48'),
(556, '縣立梅山國中', '嘉義縣', '', '國民中學', '104523', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:48', '2025-10-08 07:22:48'),
(557, '縣立大埔國中(小)', '嘉義縣', '', '國民中學', '104524', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:48', '2025-10-08 07:22:48'),
(558, '縣立阿里山國中(小)', '嘉義縣', '', '國民中學', '104526', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:48', '2025-10-08 07:22:48'),
(559, '縣立豐山實驗國中(小)', '嘉義縣', '', '國民中學', '104527', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:48', '2025-10-08 07:22:48'),
(560, '國立南科高級實驗中學附設國中部', '臺南市', '', '國民中學', '110328', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:48', '2025-10-08 07:22:48'),
(561, '私立南光高中附設國中', '臺南市', '', '國民中學', '111313', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:48', '2025-10-08 07:22:48'),
(562, '私立鳳和高中附設國中', '臺南市', '', '國民中學', '111318', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:48', '2025-10-08 07:22:48'),
(563, '私立港明高中附設國中', '臺南市', '', '國民中學', '111320', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:48', '2025-10-08 07:22:48'),
(564, '私立興國高中附設國中', '臺南市', '', '國民中學', '111321', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:48', '2025-10-08 07:22:48'),
(565, '私立明達高中附設國中', '臺南市', '', '國民中學', '111322', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:48', '2025-10-08 07:22:48'),
(566, '方濟會學校財團法人臺南市黎明高中附設國中部', '臺南市', '', '國民中學', '111323', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:48', '2025-10-08 07:22:48'),
(567, '私立城光國中', '臺南市', '', '國民中學', '111501', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:48', '2025-10-08 07:22:48'),
(568, '私立昭明國中(代用)', '臺南市', '', '國民中學', '111502', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:49', '2025-10-08 07:22:49'),
(569, '市立大灣高中附設國中', '臺南市', '', '國民中學', '114306', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:49', '2025-10-08 07:22:49'),
(570, '市立永仁高中附設國中部', '臺南市', '', '國民中學', '114307', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:49', '2025-10-08 07:22:49'),
(571, '市立仁德國中', '臺南市', '', '國民中學', '114501', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:49', '2025-10-08 07:22:49'),
(572, '市立仁德文賢國中', '臺南市', '', '國民中學', '114502', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:49', '2025-10-08 07:22:49'),
(573, '市立歸仁國中', '臺南市', '', '國民中學', '114503', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:49', '2025-10-08 07:22:49'),
(574, '市立關廟國中', '臺南市', '', '國民中學', '114504', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:49', '2025-10-08 07:22:49'),
(575, '市立永康國中', '臺南市', '', '國民中學', '114505', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:49', '2025-10-08 07:22:49'),
(576, '市立龍崎國中', '臺南市', '', '國民中學', '114508', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:49', '2025-10-08 07:22:49'),
(577, '市立新化國中', '臺南市', '', '國民中學', '114509', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:49', '2025-10-08 07:22:49'),
(578, '市立善化國中', '臺南市', '', '國民中學', '114510', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:49', '2025-10-08 07:22:49'),
(579, '市立玉井國中', '臺南市', '', '國民中學', '114511', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:49', '2025-10-08 07:22:49'),
(580, '市立山上國中', '臺南市', '', '國民中學', '114512', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:49', '2025-10-08 07:22:49'),
(581, '市立安定國中', '臺南市', '', '國民中學', '114513', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:49', '2025-10-08 07:22:49'),
(582, '市立楠西國中', '臺南市', '', '國民中學', '114514', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:49', '2025-10-08 07:22:49'),
(583, '市立新市國中', '臺南市', '', '國民中學', '114515', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:49', '2025-10-08 07:22:49'),
(584, '市立南化國中', '臺南市', '', '國民中學', '114516', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:49', '2025-10-08 07:22:49'),
(585, '市立左鎮國中', '臺南市', '', '國民中學', '114517', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:49', '2025-10-08 07:22:49'),
(586, '市立麻豆國中', '臺南市', '', '國民中學', '114518', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:49', '2025-10-08 07:22:49'),
(587, '市立下營國中', '臺南市', '', '國民中學', '114519', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:49', '2025-10-08 07:22:49'),
(588, '市立六甲國中', '臺南市', '', '國民中學', '114520', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:49', '2025-10-08 07:22:49'),
(589, '市立官田國中', '臺南市', '', '國民中學', '114521', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:49', '2025-10-08 07:22:49'),
(590, '市立大內國中', '臺南市', '', '國民中學', '114522', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:49', '2025-10-08 07:22:49'),
(591, '市立佳里國中', '臺南市', '', '國民中學', '114523', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:49', '2025-10-08 07:22:49'),
(592, '市立佳興國中', '臺南市', '', '國民中學', '114524', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:49', '2025-10-08 07:22:49'),
(593, '市立學甲國中', '臺南市', '', '國民中學', '114525', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:49', '2025-10-08 07:22:49'),
(594, '市立西港國中', '臺南市', '', '國民中學', '114526', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:49', '2025-10-08 07:22:49'),
(595, '市立將軍國中', '臺南市', '', '國民中學', '114527', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:50', '2025-10-08 07:22:50'),
(596, '市立後港國中', '臺南市', '', '國民中學', '114528', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:50', '2025-10-08 07:22:50'),
(597, '市立竹橋國中', '臺南市', '', '國民中學', '114529', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:50', '2025-10-08 07:22:50'),
(598, '市立北門國中', '臺南市', '', '國民中學', '114530', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:50', '2025-10-08 07:22:50'),
(599, '市立南新國中', '臺南市', '', '國民中學', '114531', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:50', '2025-10-08 07:22:50'),
(600, '市立太子國中', '臺南市', '', '國民中學', '114532', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:50', '2025-10-08 07:22:50'),
(601, '市立新東國中', '臺南市', '', '國民中學', '114533', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:50', '2025-10-08 07:22:50'),
(602, '市立鹽水國中', '臺南市', '', '國民中學', '114534', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:50', '2025-10-08 07:22:50'),
(603, '市立白河國中', '臺南市', '', '國民中學', '114535', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:50', '2025-10-08 07:22:50'),
(604, '市立柳營國中', '臺南市', '', '國民中學', '114536', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:50', '2025-10-08 07:22:50'),
(605, '市立東山國中', '臺南市', '', '國民中學', '114537', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:50', '2025-10-08 07:22:50'),
(606, '市立東原國中', '臺南市', '', '國民中學', '114538', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:50', '2025-10-08 07:22:50'),
(607, '市立後壁國中', '臺南市', '', '國民中學', '114539', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:50', '2025-10-08 07:22:50'),
(608, '市立菁寮國中', '臺南市', '', '國民中學', '114540', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:50', '2025-10-08 07:22:50'),
(609, '市立大橋國中', '臺南市', '', '國民中學', '114543', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:50', '2025-10-08 07:22:50'),
(610, '市立沙崙國中', '臺南市', '', '國民中學', '114544', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:50', '2025-10-08 07:22:50'),
(611, '私立普門高中附設國中部', '高雄市', '', '國民中學', '121307', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:50', '2025-10-08 07:22:50'),
(612, '私立正義高中附設國中部', '高雄市', '', '國民中學', '121318', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:50', '2025-10-08 07:22:50'),
(613, '私立義大國際高中附設國中部', '高雄市', '', '國民中學', '121320', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:50', '2025-10-08 07:22:50'),
(614, '市立文山高中附設國中部', '高雄市', '', '國民中學', '124302', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:50', '2025-10-08 07:22:50'),
(615, '市立林園高中附設國中部', '高雄市', '', '國民中學', '124311', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:50', '2025-10-08 07:22:50'),
(616, '市立仁武高中附設國中部', '高雄市', '', '國民中學', '124313', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:50', '2025-10-08 07:22:50'),
(617, '市立路竹高中附設國中部', '高雄市', '', '國民中學', '124322', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:50', '2025-10-08 07:22:50'),
(618, '市立六龜高中附設國中部', '高雄市', '', '國民中學', '124333', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:50', '2025-10-08 07:22:50'),
(619, '市立福誠高中附設國中部', '高雄市', '', '國民中學', '124340', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:50', '2025-10-08 07:22:50'),
(620, '市立鳳山國中', '高雄市', '', '國民中學', '124501', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:50', '2025-10-08 07:22:50'),
(621, '市立鳳西國中', '高雄市', '', '國民中學', '124503', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:50', '2025-10-08 07:22:50'),
(622, '市立五甲國中', '高雄市', '', '國民中學', '124504', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:50', '2025-10-08 07:22:50'),
(623, '市立鳳甲國中', '高雄市', '', '國民中學', '124505', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:50', '2025-10-08 07:22:50'),
(624, '市立忠孝國中', '高雄市', '', '國民中學', '124506', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:50', '2025-10-08 07:22:50'),
(625, '市立大寮國中', '高雄市', '', '國民中學', '124507', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:50', '2025-10-08 07:22:50'),
(626, '市立潮寮國中', '高雄市', '', '國民中學', '124508', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:50', '2025-10-08 07:22:50'),
(627, '市立大樹國中', '高雄市', '', '國民中學', '124509', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:50', '2025-10-08 07:22:50'),
(628, '市立溪埔國中', '高雄市', '', '國民中學', '124510', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:50', '2025-10-08 07:22:50'),
(629, '市立鳥松國中', '高雄市', '', '國民中學', '124512', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:50', '2025-10-08 07:22:50'),
(630, '市立大社國中', '高雄市', '', '國民中學', '124514', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:50', '2025-10-08 07:22:50'),
(631, '市立岡山國中', '高雄市', '', '國民中學', '124515', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:50', '2025-10-08 07:22:50'),
(632, '市立前峰國中', '高雄市', '', '國民中學', '124516', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:51', '2025-10-08 07:22:51'),
(633, '市立永安國中', '高雄市', '', '國民中學', '124517', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:51', '2025-10-08 07:22:51'),
(634, '市立橋頭國中', '高雄市', '', '國民中學', '124518', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:51', '2025-10-08 07:22:51'),
(635, '市立梓官國中', '高雄市', '', '國民中學', '124519', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:51', '2025-10-08 07:22:51'),
(636, '市立燕巢國中', '高雄市', '', '國民中學', '124520', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:51', '2025-10-08 07:22:51'),
(637, '市立阿蓮國中', '高雄市', '', '國民中學', '124521', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:51', '2025-10-08 07:22:51'),
(638, '市立湖內國中', '高雄市', '', '國民中學', '124523', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:51', '2025-10-08 07:22:51'),
(639, '市立茄萣國中', '高雄市', '', '國民中學', '124524', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:51', '2025-10-08 07:22:51'),
(640, '市立田寮國中', '高雄市', '', '國民中學', '124525', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:51', '2025-10-08 07:22:51'),
(641, '市立彌陀國中', '高雄市', '', '國民中學', '124526', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:51', '2025-10-08 07:22:51'),
(642, '市立旗山國中', '高雄市', '', '國民中學', '124527', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:51', '2025-10-08 07:22:51'),
(643, '市立圓富國中', '高雄市', '', '國民中學', '124528', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:51', '2025-10-08 07:22:51'),
(644, '市立大洲國中', '高雄市', '', '國民中學', '124529', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:51', '2025-10-08 07:22:51'),
(645, '市立美濃國中', '高雄市', '', '國民中學', '124530', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:51', '2025-10-08 07:22:51'),
(646, '市立南隆國中', '高雄市', '', '國民中學', '124531', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:51', '2025-10-08 07:22:51'),
(647, '市立龍肚國中', '高雄市', '', '國民中學', '124532', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:51', '2025-10-08 07:22:51'),
(648, '市立寶來國中', '高雄市', '', '國民中學', '124534', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:51', '2025-10-08 07:22:51'),
(649, '市立杉林國中', '高雄市', '', '國民中學', '124535', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:51', '2025-10-08 07:22:51'),
(650, '市立內門國中', '高雄市', '', '國民中學', '124536', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:51', '2025-10-08 07:22:51'),
(651, '市立甲仙國中', '高雄市', '', '國民中學', '124537', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:51', '2025-10-08 07:22:51'),
(652, '市立中芸國中', '高雄市', '', '國民中學', '124538', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:51', '2025-10-08 07:22:51'),
(653, '市立中庄國中', '高雄市', '', '國民中學', '124539', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:51', '2025-10-08 07:22:51'),
(654, '市立蚵寮國中', '高雄市', '', '國民中學', '124541', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:51', '2025-10-08 07:22:51'),
(655, '市立那瑪夏國中', '高雄市', '', '國民中學', '124542', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:51', '2025-10-08 07:22:51'),
(656, '市立青年國中', '高雄市', '', '國民中學', '124543', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:51', '2025-10-08 07:22:51'),
(657, '市立一甲國中', '高雄市', '', '國民中學', '124544', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:51', '2025-10-08 07:22:51'),
(658, '市立大灣國中', '高雄市', '', '國民中學', '124545', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:51', '2025-10-08 07:22:51'),
(659, '市立嘉興國中', '高雄市', '', '國民中學', '124546', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:51', '2025-10-08 07:22:51'),
(660, '市立茂林國中', '高雄市', '', '國民中學', '124547', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:51', '2025-10-08 07:22:51'),
(661, '市立桃源國中', '高雄市', '', '國民中學', '124548', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:51', '2025-10-08 07:22:51'),
(662, '市立中崙國中', '高雄市', '', '國民中學', '124549', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:51', '2025-10-08 07:22:51'),
(663, '市立鳳翔國中', '高雄市', '', '國民中學', '124550', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:51', '2025-10-08 07:22:51'),
(664, '私立陸興高中附設國中部', '屏東縣', '', '國民中學', '131308', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:51', '2025-10-08 07:22:51'),
(665, '私立美和高中附設國中部', '屏東縣', '', '國民中學', '131311', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:51', '2025-10-08 07:22:51'),
(666, '私立南榮國中(代用)', '屏東縣', '', '國民中學', '131501', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:51', '2025-10-08 07:22:51'),
(667, '縣立大同高中附設國中部', '屏東縣', '', '國民中學', '134304', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:51', '2025-10-08 07:22:51'),
(668, '縣立枋寮高中附設國中部', '屏東縣', '', '國民中學', '134321', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(669, '縣立東港高中附設國中部', '屏東縣', '', '國民中學', '134324', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(670, '縣立來義中學附設國中部', '屏東縣', '', '國民中學', '134334', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(671, '縣立明正國中', '屏東縣', '', '國民中學', '134501', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(672, '縣立中正國中', '屏東縣', '', '國民中學', '134502', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(673, '縣立公正國中', '屏東縣', '', '國民中學', '134503', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(674, '縣立鶴聲國中', '屏東縣', '', '國民中學', '134505', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(675, '縣立至正國中', '屏東縣', '', '國民中學', '134506', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(676, '縣立長治國中', '屏東縣', '', '國民中學', '134507', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(677, '縣立麟洛國中', '屏東縣', '', '國民中學', '134508', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(678, '縣立九如國中', '屏東縣', '', '國民中學', '134509', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52');
INSERT INTO `school_data` (`id`, `name`, `city`, `district`, `type`, `school_code`, `address`, `phone`, `website`, `principal`, `student_count`, `teacher_count`, `established_year`, `is_active`, `data_source`, `last_updated`, `created_at`) VALUES
(679, '縣立里港國中', '屏東縣', '', '國民中學', '134510', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(680, '縣立鹽埔國中', '屏東縣', '', '國民中學', '134511', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(681, '縣立高樹國中', '屏東縣', '', '國民中學', '134512', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(682, '縣立高泰國中', '屏東縣', '', '國民中學', '134513', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(683, '縣立內埔國中', '屏東縣', '', '國民中學', '134514', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(684, '縣立崇文國中', '屏東縣', '', '國民中學', '134515', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(685, '縣立竹田國中', '屏東縣', '', '國民中學', '134516', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(686, '縣立潮州國中', '屏東縣', '', '國民中學', '134517', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(687, '縣立光春國中', '屏東縣', '', '國民中學', '134518', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(688, '縣立萬巒國中', '屏東縣', '', '國民中學', '134519', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(689, '縣立新埤國中', '屏東縣', '', '國民中學', '134520', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(690, '縣立萬丹國中', '屏東縣', '', '國民中學', '134522', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(691, '縣立新園國中', '屏東縣', '', '國民中學', '134523', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(692, '縣立林邊國中', '屏東縣', '', '國民中學', '134525', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(693, '縣立南州國中', '屏東縣', '', '國民中學', '134526', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(694, '縣立佳冬國中', '屏東縣', '', '國民中學', '134527', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(695, '縣立琉球國中', '屏東縣', '', '國民中學', '134528', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(696, '縣立車城國中', '屏東縣', '', '國民中學', '134530', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(697, '縣立恆春國中', '屏東縣', '', '國民中學', '134531', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(698, '縣立滿州國中', '屏東縣', '', '國民中學', '134532', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(699, '縣立瑪家國中', '屏東縣', '', '國民中學', '134533', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(700, '縣立泰武國中', '屏東縣', '', '國民中學', '134535', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(701, '縣立牡丹國中', '屏東縣', '', '國民中學', '134536', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(702, '縣立獅子國中', '屏東縣', '', '國民中學', '134537', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(703, '縣立東新國中', '屏東縣', '', '國民中學', '134538', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(704, '縣立萬新國中', '屏東縣', '', '國民中學', '134542', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(705, '私立均一高中附設國中部', '臺東縣', '', '國民中學', '141301', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(706, '私立育仁高中附設國中部', '臺東縣', '', '國民中學', '141307', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(707, '縣立蘭嶼高中附設國中部', '臺東縣', '', '國民中學', '144322', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(708, '縣立新生國中', '臺東縣', '', '國民中學', '144501', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(709, '縣立東海國中', '臺東縣', '', '國民中學', '144502', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(710, '縣立寶桑國中', '臺東縣', '', '國民中學', '144503', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(711, '縣立卑南國中', '臺東縣', '', '國民中學', '144504', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(712, '縣立豐田國中', '臺東縣', '', '國民中學', '144505', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(713, '縣立知本國中', '臺東縣', '', '國民中學', '144506', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(714, '縣立初鹿國中', '臺東縣', '', '國民中學', '144507', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(715, '縣立鹿野國中', '臺東縣', '', '國民中學', '144508', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(716, '縣立瑞源國中', '臺東縣', '', '國民中學', '144509', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(717, '縣立關山國中', '臺東縣', '', '國民中學', '144510', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(718, '縣立池上國中', '臺東縣', '', '國民中學', '144511', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(719, '縣立大王國中', '臺東縣', '', '國民中學', '144512', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(720, '縣立賓茂國中', '臺東縣', '', '國民中學', '144513', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(721, '縣立大武國中', '臺東縣', '', '國民中學', '144514', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(722, '縣立都蘭國中', '臺東縣', '', '國民中學', '144515', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(723, '縣立泰源國中', '臺東縣', '', '國民中學', '144516', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(724, '縣立新港國中', '臺東縣', '', '國民中學', '144517', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(725, '縣立長濱國中', '臺東縣', '', '國民中學', '144518', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(726, '縣立桃源國中', '臺東縣', '', '國民中學', '144519', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(727, '縣立海端國中', '臺東縣', '', '國民中學', '144520', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(728, '縣立綠島國中', '臺東縣', '', '國民中學', '144521', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(729, '私立海星高中附設國中部', '花蓮縣', '', '國民中學', '151306', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(730, '私立慈大附中附設國中部', '花蓮縣', '', '國民中學', '151312', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(731, '縣立南平中學附設國中部', '花蓮縣', '', '國民中學', '154399', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(732, '縣立玉里國中', '花蓮縣', '', '國民中學', '154501', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(733, '縣立玉東國中', '花蓮縣', '', '國民中學', '154502', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(734, '縣立三民國中', '花蓮縣', '', '國民中學', '154503', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(735, '縣立美崙國中', '花蓮縣', '', '國民中學', '154504', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(736, '縣立花崗國中', '花蓮縣', '', '國民中學', '154505', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(737, '縣立國風國中', '花蓮縣', '', '國民中學', '154506', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(738, '縣立秀林國中', '花蓮縣', '', '國民中學', '154507', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(739, '縣立新城國中', '花蓮縣', '', '國民中學', '154508', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(740, '縣立吉安國中', '花蓮縣', '', '國民中學', '154509', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:52', '2025-10-08 07:22:52'),
(741, '縣立宜昌國中', '花蓮縣', '', '國民中學', '154510', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(742, '縣立壽豐國中', '花蓮縣', '', '國民中學', '154511', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(743, '縣立平和國中', '花蓮縣', '', '國民中學', '154512', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(744, '縣立光復國中', '花蓮縣', '', '國民中學', '154513', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(745, '縣立富源國中', '花蓮縣', '', '國民中學', '154514', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(746, '縣立鳳林國中', '花蓮縣', '', '國民中學', '154515', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(747, '縣立萬榮國中', '花蓮縣', '', '國民中學', '154516', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(748, '縣立富里國中', '花蓮縣', '', '國民中學', '154517', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(749, '縣立富北國中', '花蓮縣', '', '國民中學', '154518', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(750, '縣立豐濱國中', '花蓮縣', '', '國民中學', '154519', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(751, '縣立瑞穗國中', '花蓮縣', '', '國民中學', '154520', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(752, '縣立東里國中', '花蓮縣', '', '國民中學', '154521', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(753, '縣立自強國中', '花蓮縣', '', '國民中學', '154522', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(754, '縣立化仁國中', '花蓮縣', '', '國民中學', '154523', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(755, '縣立馬公國中', '澎湖縣', '', '國民中學', '164501', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(756, '縣立中正國中', '澎湖縣', '', '國民中學', '164502', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(757, '縣立澎南國中', '澎湖縣', '', '國民中學', '164503', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(758, '縣立湖西國中', '澎湖縣', '', '國民中學', '164504', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(759, '縣立志清國中', '澎湖縣', '', '國民中學', '164505', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(760, '縣立鎮海國中', '澎湖縣', '', '國民中學', '164506', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(761, '縣立白沙國中', '澎湖縣', '', '國民中學', '164507', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(762, '縣立吉貝國中', '澎湖縣', '', '國民中學', '164508', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(763, '縣立西嶼國中', '澎湖縣', '', '國民中學', '164509', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(764, '縣立望安國中', '澎湖縣', '', '國民中學', '164510', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(765, '縣立將澳國中', '澎湖縣', '', '國民中學', '164511', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(766, '縣立七美國中', '澎湖縣', '', '國民中學', '164512', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(767, '縣立文光國中', '澎湖縣', '', '國民中學', '164513', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(768, '縣立鳥嶼國中', '澎湖縣', '', '國民中學', '164514', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(769, '私立二信高中附設國中部', '基隆市', '', '國民中學', '171306', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(770, '私立聖心高中附設國中部', '基隆市', '', '國民中學', '171308', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(771, '市立中山高中附設國中部', '基隆市', '', '國民中學', '173304', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(772, '市立安樂高中附設國中部', '基隆市', '', '國民中學', '173306', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(773, '市立暖暖高中附設國中部', '基隆市', '', '國民中學', '173307', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(774, '市立八斗高中附設國中部', '基隆市', '', '國民中學', '173314', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(775, '市立明德國中', '基隆市', '', '國民中學', '173501', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(776, '市立銘傳國中', '基隆市', '', '國民中學', '173502', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(777, '市立信義國中', '基隆市', '', '國民中學', '173503', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(778, '市立中正國中', '基隆市', '', '國民中學', '173505', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(779, '市立南榮國中', '基隆市', '', '國民中學', '173508', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(780, '市立成功國中', '基隆市', '', '國民中學', '173509', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(781, '市立正濱國中', '基隆市', '', '國民中學', '173510', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(782, '市立建德國中', '基隆市', '', '國民中學', '173512', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(783, '市立百福國中', '基隆市', '', '國民中學', '173513', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(784, '市立碇內國中', '基隆市', '', '國民中學', '173515', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(785, '市立武崙國中', '基隆市', '', '國民中學', '173516', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(786, '國立科學工業園區高中附設國中部', '新竹市', '', '國民中學', '180301', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(787, '私立光復高中附設國中部', '新竹市', '', '國民中學', '181305', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(788, '私立曙光女中附設國中部', '新竹市', '', '國民中學', '181306', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(789, '私立磐石高中附設國中部', '新竹市', '', '國民中學', '181307', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(790, '私立新竹市康橋國中(小)', '新竹市', '', '國民中學', '181502', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(791, '市立成德高中附設國中部', '新竹市', '', '國民中學', '183306', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(792, '市立香山高中附設國中部', '新竹市', '', '國民中學', '183307', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(793, '市立建功高中附設國中部', '新竹市', '', '國民中學', '183313', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(794, '市立建華國中', '新竹市', '', '國民中學', '183501', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(795, '市立培英國中', '新竹市', '', '國民中學', '183502', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(796, '市立光華國中', '新竹市', '', '國民中學', '183503', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(797, '市立育賢國中', '新竹市', '', '國民中學', '183504', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(798, '市立光武國中', '新竹市', '', '國民中學', '183505', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(799, '市立南華國中', '新竹市', '', '國民中學', '183508', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(800, '市立富禮國中', '新竹市', '', '國民中學', '183509', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(801, '市立三民國中', '新竹市', '', '國民中學', '183510', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(802, '市立內湖國中', '新竹市', '', '國民中學', '183511', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(803, '市立虎林國中', '新竹市', '', '國民中學', '183512', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(804, '市立新科國中', '新竹市', '', '國民中學', '183514', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(805, '市立竹光國中', '新竹市', '', '國民中學', '183515', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(806, '私立東大附屬實驗高中附設國中部', '臺中市', '', '國民中學', '191301', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(807, '私立葳格高中附設國中部', '臺中市', '', '國民中學', '191302', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(808, '私立新民高中附設國中部', '臺中市', '', '國民中學', '191305', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(809, '私立宜寧高中附設國中部', '臺中市', '', '國民中學', '191308', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(810, '私立明德高中附設國中部', '臺中市', '', '國民中學', '191309', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:53', '2025-10-08 07:22:53'),
(811, '私立衛道高中附設國中部', '臺中市', '', '國民中學', '191311', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:54', '2025-10-08 07:22:54'),
(812, '私立曉明女中附設國中部', '臺中市', '', '國民中學', '191313', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:54', '2025-10-08 07:22:54'),
(813, '私立嶺東高中附設國中部', '臺中市', '', '國民中學', '191314', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:54', '2025-10-08 07:22:54'),
(814, '私立磊川華德福實驗教育學校國中部', '臺中市', '', '國民中學', '191315', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:54', '2025-10-08 07:22:54'),
(815, '私立麗喆國中(小)', '臺中市', '', '國民中學', '191503', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:54', '2025-10-08 07:22:54'),
(816, '市立忠明高中附設國中部', '臺中市', '', '國民中學', '193303', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:54', '2025-10-08 07:22:54'),
(817, '市立西苑高中附設國中部', '臺中市', '', '國民中學', '193313', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:54', '2025-10-08 07:22:54'),
(818, '市立東山高中附設國中部', '臺中市', '', '國民中學', '193315', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:54', '2025-10-08 07:22:54'),
(819, '市立惠文高中附設國中部', '臺中市', '', '國民中學', '193316', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:54', '2025-10-08 07:22:54'),
(820, '市立居仁國中', '臺中市', '', '國民中學', '193501', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:54', '2025-10-08 07:22:54'),
(821, '市立雙十國中', '臺中市', '', '國民中學', '193502', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:54', '2025-10-08 07:22:54'),
(822, '市立崇倫國中', '臺中市', '', '國民中學', '193504', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:54', '2025-10-08 07:22:54'),
(823, '市立大德國中', '臺中市', '', '國民中學', '193505', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:54', '2025-10-08 07:22:54'),
(824, '市立北新國中', '臺中市', '', '國民中學', '193506', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:54', '2025-10-08 07:22:54'),
(825, '市立東峰國中', '臺中市', '', '國民中學', '193507', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:54', '2025-10-08 07:22:54'),
(826, '市立黎明國中', '臺中市', '', '國民中學', '193508', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:54', '2025-10-08 07:22:54'),
(827, '市立光明國中', '臺中市', '', '國民中學', '193509', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:54', '2025-10-08 07:22:54'),
(828, '市立向上國中', '臺中市', '', '國民中學', '193510', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:54', '2025-10-08 07:22:54'),
(829, '市立育英國中', '臺中市', '', '國民中學', '193511', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:54', '2025-10-08 07:22:54'),
(830, '市立四育國中', '臺中市', '', '國民中學', '193512', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:54', '2025-10-08 07:22:54'),
(831, '市立五權國中', '臺中市', '', '國民中學', '193514', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:54', '2025-10-08 07:22:54'),
(832, '市立中山國中', '臺中市', '', '國民中學', '193516', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:54', '2025-10-08 07:22:54'),
(833, '市立崇德國中', '臺中市', '', '國民中學', '193517', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:54', '2025-10-08 07:22:54'),
(834, '市立立人國中', '臺中市', '', '國民中學', '193518', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:54', '2025-10-08 07:22:54'),
(835, '市立漢口國中', '臺中市', '', '國民中學', '193519', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:54', '2025-10-08 07:22:54'),
(836, '市立安和國中', '臺中市', '', '國民中學', '193520', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:54', '2025-10-08 07:22:54'),
(837, '市立至善國中', '臺中市', '', '國民中學', '193521', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:54', '2025-10-08 07:22:54'),
(838, '市立萬和國中', '臺中市', '', '國民中學', '193522', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:54', '2025-10-08 07:22:54'),
(839, '市立大業國中', '臺中市', '', '國民中學', '193523', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:54', '2025-10-08 07:22:54'),
(840, '市立三光國中', '臺中市', '', '國民中學', '193524', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:54', '2025-10-08 07:22:54'),
(841, '市立四張犁國中', '臺中市', '', '國民中學', '193525', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:54', '2025-10-08 07:22:54'),
(842, '市立福科國中', '臺中市', '', '國民中學', '193526', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:54', '2025-10-08 07:22:54'),
(843, '市立大墩國中', '臺中市', '', '國民中學', '193527', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:54', '2025-10-08 07:22:54'),
(844, '私立興華高中附設國中部', '嘉義市', '', '國民中學', '201304', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:54', '2025-10-08 07:22:54'),
(845, '私立嘉華高中附設國中部', '嘉義市', '', '國民中學', '201310', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:54', '2025-10-08 07:22:54'),
(846, '私立輔仁高中附設國中部', '嘉義市', '', '國民中學', '201312', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:54', '2025-10-08 07:22:54'),
(847, '私立宏仁女中附設國中部', '嘉義市', '', '國民中學', '201313', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:54', '2025-10-08 07:22:54'),
(848, '市立大業國中', '嘉義市', '', '國民中學', '203501', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:54', '2025-10-08 07:22:54'),
(849, '市立北興國中', '嘉義市', '', '國民中學', '203502', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:54', '2025-10-08 07:22:54'),
(850, '市立嘉義國中', '嘉義市', '', '國民中學', '203503', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:54', '2025-10-08 07:22:54'),
(851, '市立南興國中', '嘉義市', '', '國民中學', '203504', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:54', '2025-10-08 07:22:54'),
(852, '市立民生國中', '嘉義市', '', '國民中學', '203505', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:54', '2025-10-08 07:22:54'),
(853, '市立玉山國中', '嘉義市', '', '國民中學', '203506', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:54', '2025-10-08 07:22:54'),
(854, '市立蘭潭國中', '嘉義市', '', '國民中學', '203507', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:54', '2025-10-08 07:22:54'),
(855, '市立北園國中', '嘉義市', '', '國民中學', '203508', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:54', '2025-10-08 07:22:54'),
(856, '私立長榮高中附設國中部', '臺南市', '', '國民中學', '211301', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:54', '2025-10-08 07:22:54'),
(857, '私立聖功女中附設國中部', '臺南市', '', '國民中學', '211304', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:54', '2025-10-08 07:22:54'),
(858, '臺南光華學校財團法人臺南市光華高中附設國中', '臺南市', '', '國民中學', '211310', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:54', '2025-10-08 07:22:54'),
(859, '私立瀛海高中附設國中部', '臺南市', '', '國民中學', '211315', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:54', '2025-10-08 07:22:54'),
(860, '私立崑山高中附設國中部', '臺南市', '', '國民中學', '211317', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:54', '2025-10-08 07:22:54'),
(861, '私立德光高中附設國中部', '臺南市', '', '國民中學', '211318', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:54', '2025-10-08 07:22:54'),
(862, '慈濟學校財團法人臺南市私立慈濟高級中學附設國中部', '臺南市', '', '國民中學', '211320', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:54', '2025-10-08 07:22:54'),
(863, '市立南寧高中附設國中部', '臺南市', '', '國民中學', '213303', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:54', '2025-10-08 07:22:54'),
(864, '市立土城高中附設國中部', '臺南市', '', '國民中學', '213316', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:54', '2025-10-08 07:22:54'),
(865, '市立後甲國中', '臺南市', '', '國民中學', '213501', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:54', '2025-10-08 07:22:54'),
(866, '市立忠孝國中', '臺南市', '', '國民中學', '213502', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:54', '2025-10-08 07:22:54'),
(867, '市立大成國中', '臺南市', '', '國民中學', '213504', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:54', '2025-10-08 07:22:54'),
(868, '市立金城國中', '臺南市', '', '國民中學', '213505', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:54', '2025-10-08 07:22:54'),
(869, '市立民德國中', '臺南市', '', '國民中學', '213506', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:54', '2025-10-08 07:22:54'),
(870, '市立成功國中', '臺南市', '', '國民中學', '213507', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:54', '2025-10-08 07:22:54'),
(871, '市立延平國中', '臺南市', '', '國民中學', '213508', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:54', '2025-10-08 07:22:54'),
(872, '市立建興國中', '臺南市', '', '國民中學', '213509', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:54', '2025-10-08 07:22:54'),
(873, '市立中山國中', '臺南市', '', '國民中學', '213510', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:55', '2025-10-08 07:22:55'),
(874, '市立安平國中', '臺南市', '', '國民中學', '213511', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:55', '2025-10-08 07:22:55'),
(875, '市立安南國中', '臺南市', '', '國民中學', '213512', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:55', '2025-10-08 07:22:55'),
(876, '市立安順國中', '臺南市', '', '國民中學', '213513', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:55', '2025-10-08 07:22:55'),
(877, '市立復興國中', '臺南市', '', '國民中學', '213514', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:55', '2025-10-08 07:22:55'),
(878, '市立新興國中', '臺南市', '', '國民中學', '213515', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:55', '2025-10-08 07:22:55'),
(879, '市立文賢國中', '臺南市', '', '國民中學', '213517', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:55', '2025-10-08 07:22:55'),
(880, '市立崇明國中', '臺南市', '', '國民中學', '213518', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:55', '2025-10-08 07:22:55'),
(881, '市立和順國中', '臺南市', '', '國民中學', '213519', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:55', '2025-10-08 07:22:55'),
(882, '市立海佃國中', '臺南市', '', '國民中學', '213520', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:55', '2025-10-08 07:22:55'),
(883, '市立西松高中附設國中部', '臺北市', '', '國民中學', '313301', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:55', '2025-10-08 07:22:55'),
(884, '市立中崙高中附設國中部', '臺北市', '', '國民中學', '313302', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:55', '2025-10-08 07:22:55'),
(885, '市立介壽國中', '臺北市', '', '國民中學', '313501', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:55', '2025-10-08 07:22:55'),
(886, '市立民生國中', '臺北市', '', '國民中學', '313502', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:55', '2025-10-08 07:22:55'),
(887, '市立中山國中', '臺北市', '', '國民中學', '313504', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:55', '2025-10-08 07:22:55'),
(888, '市立敦化國中', '臺北市', '', '國民中學', '313505', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:55', '2025-10-08 07:22:55'),
(889, '市立興雅國中', '臺北市', '', '國民中學', '323502', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:55', '2025-10-08 07:22:55'),
(890, '市立永吉國中', '臺北市', '', '國民中學', '323503', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:55', '2025-10-08 07:22:55'),
(891, '市立瑠公國中', '臺北市', '', '國民中學', '323504', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:55', '2025-10-08 07:22:55'),
(892, '市立信義國中', '臺北市', '', '國民中學', '323505', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:55', '2025-10-08 07:22:55'),
(893, '私立延平中學附設國中部', '臺北市', '', '國民中學', '331301', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:55', '2025-10-08 07:22:55'),
(894, '私立復興高中(國中)', '臺北市', '', '國民中學', '331304', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:55', '2025-10-08 07:22:55'),
(895, '私立立人國中(小)', '臺北市', '', '國民中學', '331502', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:55', '2025-10-08 07:22:55'),
(896, '市立和平高中附設國中部', '臺北市', '', '國民中學', '333301', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:55', '2025-10-08 07:22:55'),
(897, '市立仁愛國中', '臺北市', '', '國民中學', '333501', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:55', '2025-10-08 07:22:55'),
(898, '市立大安國中', '臺北市', '', '國民中學', '333502', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:55', '2025-10-08 07:22:55'),
(899, '市立芳和國中', '臺北市', '', '國民中學', '333504', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:55', '2025-10-08 07:22:55'),
(900, '市立金華國中', '臺北市', '', '國民中學', '333505', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:55', '2025-10-08 07:22:55'),
(901, '市立懷生國中', '臺北市', '', '國民中學', '333506', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:55', '2025-10-08 07:22:55'),
(902, '市立民族國中', '臺北市', '', '國民中學', '333507', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:55', '2025-10-08 07:22:55'),
(903, '市立龍門國中', '臺北市', '', '國民中學', '333508', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:55', '2025-10-08 07:22:55'),
(904, '市立大同高中附設國中部', '臺北市', '', '國民中學', '343302', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:55', '2025-10-08 07:22:55'),
(905, '市立大直高中附設國中部', '臺北市', '', '國民中學', '343303', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:55', '2025-10-08 07:22:55'),
(906, '市立長安國中', '臺北市', '', '國民中學', '343502', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:55', '2025-10-08 07:22:55'),
(907, '市立北安國中', '臺北市', '', '國民中學', '343504', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:55', '2025-10-08 07:22:55'),
(908, '市立新興國中', '臺北市', '', '國民中學', '343505', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:55', '2025-10-08 07:22:55'),
(909, '市立五常國中', '臺北市', '', '國民中學', '343506', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:55', '2025-10-08 07:22:55'),
(910, '市立濱江國中', '臺北市', '', '國民中學', '343507', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:55', '2025-10-08 07:22:55'),
(911, '市立螢橋國中', '臺北市', '', '國民中學', '353501', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:55', '2025-10-08 07:22:55'),
(912, '市立古亭國中', '臺北市', '', '國民中學', '353502', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:55', '2025-10-08 07:22:55'),
(913, '市立南門國中', '臺北市', '', '國民中學', '353503', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:55', '2025-10-08 07:22:55'),
(914, '市立弘道國中', '臺北市', '', '國民中學', '353504', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:55', '2025-10-08 07:22:55'),
(915, '市立中正國中', '臺北市', '', '國民中學', '353505', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:55', '2025-10-08 07:22:55'),
(916, '私立靜修女中附設國中部', '臺北市', '', '國民中學', '361301', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:55', '2025-10-08 07:22:55'),
(917, '市立成淵高中附設國中部', '臺北市', '', '國民中學', '363302', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:55', '2025-10-08 07:22:55'),
(918, '市立建成國中', '臺北市', '', '國民中學', '363501', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:55', '2025-10-08 07:22:55'),
(919, '市立忠孝國中', '臺北市', '', '國民中學', '363502', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:55', '2025-10-08 07:22:55'),
(920, '市立民權國中', '臺北市', '', '國民中學', '363504', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:55', '2025-10-08 07:22:55'),
(921, '市立蘭州國中', '臺北市', '', '國民中學', '363506', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:55', '2025-10-08 07:22:55'),
(922, '市立重慶國中', '臺北市', '', '國民中學', '363507', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:55', '2025-10-08 07:22:55'),
(923, '私立立人高中附設國中部', '臺北市', '', '國民中學', '371301', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:55', '2025-10-08 07:22:55'),
(924, '市立大理高中附設國中部', '臺北市', '', '國民中學', '373302', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:55', '2025-10-08 07:22:55'),
(925, '市立萬華國中', '臺北市', '', '國民中學', '373501', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:55', '2025-10-08 07:22:55'),
(926, '市立雙園國中', '臺北市', '', '國民中學', '373503', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:55', '2025-10-08 07:22:55'),
(927, '市立龍山國中', '臺北市', '', '國民中學', '373505', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:55', '2025-10-08 07:22:55'),
(928, '私立東山中學附設國中部', '臺北市', '', '國民中學', '381301', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:55', '2025-10-08 07:22:55'),
(929, '私立再興中學附設國中部', '臺北市', '', '國民中學', '381304', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:55', '2025-10-08 07:22:55'),
(930, '私立景文高中附設國中部', '臺北市', '', '國民中學', '381305', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:55', '2025-10-08 07:22:55'),
(931, '私立靜心國中', '臺北市', '', '國民中學', '381501', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:55', '2025-10-08 07:22:55'),
(932, '市立萬芳高中附設國中部', '臺北市', '', '國民中學', '383302', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:55', '2025-10-08 07:22:55'),
(933, '市立木柵國中', '臺北市', '', '國民中學', '383501', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:55', '2025-10-08 07:22:55'),
(934, '市立實踐國中', '臺北市', '', '國民中學', '383502', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:55', '2025-10-08 07:22:55'),
(935, '市立北政國中', '臺北市', '', '國民中學', '383503', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:55', '2025-10-08 07:22:55'),
(936, '市立景美國中', '臺北市', '', '國民中學', '383504', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:55', '2025-10-08 07:22:55'),
(937, '市立興福國中', '臺北市', '', '國民中學', '383505', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:56', '2025-10-08 07:22:56'),
(938, '市立景興國中', '臺北市', '', '國民中學', '383507', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:56', '2025-10-08 07:22:56'),
(939, '市立南港高中附設國中部', '臺北市', '', '國民中學', '393301', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:56', '2025-10-08 07:22:56'),
(940, '市立誠正國中', '臺北市', '', '國民中學', '393501', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:56', '2025-10-08 07:22:56'),
(941, '市立成德國中', '臺北市', '', '國民中學', '393503', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:56', '2025-10-08 07:22:56'),
(942, '國立臺灣戲曲學院(國中)', '臺北市', '', '國民中學', '400144', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:56', '2025-10-08 07:22:56'),
(943, '私立方濟中學附設國中部', '臺北市', '', '國民中學', '401302', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:56', '2025-10-08 07:22:56'),
(944, '私立達人女中附設國中部', '臺北市', '', '國民中學', '401303', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:56', '2025-10-08 07:22:56'),
(945, '市立內湖國中', '臺北市', '', '國民中學', '403501', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:56', '2025-10-08 07:22:56'),
(946, '市立麗山國中', '臺北市', '', '國民中學', '403502', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:56', '2025-10-08 07:22:56'),
(947, '市立三民國中', '臺北市', '', '國民中學', '403503', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:56', '2025-10-08 07:22:56'),
(948, '市立西湖國中', '臺北市', '', '國民中學', '403504', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:56', '2025-10-08 07:22:56'),
(949, '市立東湖國中', '臺北市', '', '國民中學', '403505', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:56', '2025-10-08 07:22:56'),
(950, '市立明湖國中', '臺北市', '', '國民中學', '403506', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:56', '2025-10-08 07:22:56'),
(951, '私立泰北高中附設國中部', '臺北市', '', '國民中學', '411301', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:56', '2025-10-08 07:22:56'),
(952, '私立衛理女中附設國中部', '臺北市', '', '國民中學', '411302', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:56', '2025-10-08 07:22:56'),
(953, '私立華興中學附設國中部', '臺北市', '', '國民中學', '411303', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:56', '2025-10-08 07:22:56'),
(954, '市立士林國中', '臺北市', '', '國民中學', '413501', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:56', '2025-10-08 07:22:56'),
(955, '市立蘭雅國中', '臺北市', '', '國民中學', '413502', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:56', '2025-10-08 07:22:56'),
(956, '市立至善國中', '臺北市', '', '國民中學', '413504', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:56', '2025-10-08 07:22:56'),
(957, '市立格致國中', '臺北市', '', '國民中學', '413505', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:56', '2025-10-08 07:22:56'),
(958, '市立福安國中', '臺北市', '', '國民中學', '413506', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:56', '2025-10-08 07:22:56'),
(959, '市立天母國中', '臺北市', '', '國民中學', '413508', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:56', '2025-10-08 07:22:56'),
(960, '私立薇閣高中附設國中部', '臺北市', '', '國民中學', '421301', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:56', '2025-10-08 07:22:56'),
(961, '私立奎山實驗高中附設國中部', '臺北市', '', '國民中學', '421303', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:56', '2025-10-08 07:22:56'),
(962, '市立北投國中', '臺北市', '', '國民中學', '423501', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:56', '2025-10-08 07:22:56'),
(963, '市立新民國中', '臺北市', '', '國民中學', '423502', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:56', '2025-10-08 07:22:56'),
(964, '市立明德國中', '臺北市', '', '國民中學', '423503', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:56', '2025-10-08 07:22:56'),
(965, '市立桃源國中', '臺北市', '', '國民中學', '423504', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:56', '2025-10-08 07:22:56'),
(966, '市立石牌國中', '臺北市', '', '國民中學', '423505', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:56', '2025-10-08 07:22:56'),
(967, '市立關渡國中', '臺北市', '', '國民中學', '423506', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:56', '2025-10-08 07:22:56'),
(968, '市立鹽埕國中', '高雄市', '', '國民中學', '513501', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:56', '2025-10-08 07:22:56'),
(969, '私立明誠高中附設國中部', '高雄市', '', '國民中學', '521301', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:56', '2025-10-08 07:22:56'),
(970, '私立大榮高中附設國中部', '高雄市', '', '國民中學', '521303', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:56', '2025-10-08 07:22:56'),
(971, '市立鼓山高中附設國中部', '高雄市', '', '國民中學', '523301', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:56', '2025-10-08 07:22:56'),
(972, '市立壽山國中', '高雄市', '', '國民中學', '523502', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:56', '2025-10-08 07:22:56'),
(973, '市立明華國中', '高雄市', '', '國民中學', '523503', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:56', '2025-10-08 07:22:56'),
(974, '市立七賢國中', '高雄市', '', '國民中學', '523504', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:56', '2025-10-08 07:22:56'),
(975, '市立左營國中', '高雄市', '', '國民中學', '533501', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:56', '2025-10-08 07:22:56'),
(976, '市立大義國中', '高雄市', '', '國民中學', '533502', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:56', '2025-10-08 07:22:56'),
(977, '市立立德國中', '高雄市', '', '國民中學', '533503', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:56', '2025-10-08 07:22:56'),
(978, '市立龍華國中', '高雄市', '', '國民中學', '533504', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:56', '2025-10-08 07:22:56'),
(979, '市立福山國中', '高雄市', '', '國民中學', '533505', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:56', '2025-10-08 07:22:56'),
(980, '市立文府國中', '高雄市', '', '國民中學', '533506', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:56', '2025-10-08 07:22:56'),
(981, '市立楠梓國中', '高雄市', '', '國民中學', '543501', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:56', '2025-10-08 07:22:56'),
(982, '市立右昌國中', '高雄市', '', '國民中學', '543502', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:56', '2025-10-08 07:22:56'),
(983, '市立後勁國中', '高雄市', '', '國民中學', '543503', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:56', '2025-10-08 07:22:56'),
(984, '市立國昌國中', '高雄市', '', '國民中學', '543504', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:56', '2025-10-08 07:22:56'),
(985, '市立翠屏國中(小)', '高雄市', '', '國民中學', '543505', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:56', '2025-10-08 07:22:56'),
(986, '私立立志高中附設國中部', '高雄市', '', '國民中學', '551301', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:56', '2025-10-08 07:22:56'),
(987, '私立南海月光實驗教育學校國中部', '高雄市', '', '國民中學', '551303', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:56', '2025-10-08 07:22:56'),
(988, '市立鼎金國中', '高雄市', '', '國民中學', '553501', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:56', '2025-10-08 07:22:56'),
(989, '市立三民國中', '高雄市', '', '國民中學', '553502', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:56', '2025-10-08 07:22:56'),
(990, '市立民族國中', '高雄市', '', '國民中學', '553503', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:56', '2025-10-08 07:22:56'),
(991, '市立陽明國中', '高雄市', '', '國民中學', '553504', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:56', '2025-10-08 07:22:56'),
(992, '市立正興國中', '高雄市', '', '國民中學', '553505', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:56', '2025-10-08 07:22:56'),
(993, '市立新興高中附設國中部', '高雄市', '', '國民中學', '563301', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:56', '2025-10-08 07:22:56'),
(994, '市立前金國中', '高雄市', '', '國民中學', '573501', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:56', '2025-10-08 07:22:56'),
(995, '國立高師附中附設國中部', '高雄市', '', '國民中學', '580301', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:56', '2025-10-08 07:22:56'),
(996, '私立復華高中附設國中部', '高雄市', '', '國民中學', '581301', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:56', '2025-10-08 07:22:56'),
(997, '私立道明中學附設國中部', '高雄市', '', '國民中學', '581302', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:56', '2025-10-08 07:22:56'),
(998, '市立中正高中附設國中部', '高雄市', '', '國民中學', '583301', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:56', '2025-10-08 07:22:56'),
(999, '市立苓雅國中', '高雄市', '', '國民中學', '583501', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:56', '2025-10-08 07:22:56'),
(1000, '市立五福國中', '高雄市', '', '國民中學', '583502', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:56', '2025-10-08 07:22:56'),
(1001, '市立大仁國中', '高雄市', '', '國民中學', '583503', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:56', '2025-10-08 07:22:56'),
(1002, '市立英明國中', '高雄市', '', '國民中學', '583505', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:56', '2025-10-08 07:22:56'),
(1003, '市立瑞祥高中附設國中部', '高雄市', '', '國民中學', '593302', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:56', '2025-10-08 07:22:56'),
(1004, '市立獅甲國中', '高雄市', '', '國民中學', '593501', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:57', '2025-10-08 07:22:57'),
(1005, '市立前鎮國中', '高雄市', '', '國民中學', '593502', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:57', '2025-10-08 07:22:57'),
(1006, '市立瑞豐國中', '高雄市', '', '國民中學', '593503', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:57', '2025-10-08 07:22:57'),
(1007, '市立光華國中', '高雄市', '', '國民中學', '593504', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:57', '2025-10-08 07:22:57'),
(1008, '市立興仁國中', '高雄市', '', '國民中學', '593505', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:57', '2025-10-08 07:22:57'),
(1009, '市立旗津國中', '高雄市', '', '國民中學', '603501', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:57', '2025-10-08 07:22:57'),
(1010, '國立高雄餐旅大學附屬餐旅高中附設國中部', '高雄市', '', '國民中學', '610405', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:57', '2025-10-08 07:22:57'),
(1011, '市立小港國中', '高雄市', '', '國民中學', '613501', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:57', '2025-10-08 07:22:57'),
(1012, '市立鳳林國中', '高雄市', '', '國民中學', '613502', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:57', '2025-10-08 07:22:57'),
(1013, '市立中山國中', '高雄市', '', '國民中學', '613503', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:57', '2025-10-08 07:22:57'),
(1014, '市立明義國中', '高雄市', '', '國民中學', '613504', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:57', '2025-10-08 07:22:57'),
(1015, '縣立金城國中', '金門縣', '', '國民中學', '714501', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:57', '2025-10-08 07:22:57'),
(1016, '縣立金湖國中', '金門縣', '', '國民中學', '714502', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:57', '2025-10-08 07:22:57'),
(1017, '縣立金寧國中(小)', '金門縣', '', '國民中學', '714503', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:57', '2025-10-08 07:22:57'),
(1018, '縣立金沙國中', '金門縣', '', '國民中學', '714504', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:57', '2025-10-08 07:22:57'),
(1019, '縣立烈嶼國中', '金門縣', '', '國民中學', '714505', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:57', '2025-10-08 07:22:57'),
(1020, '縣立介壽國中(小)', '連江縣', '', '國民中學', '724501', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:57', '2025-10-08 07:22:57');
INSERT INTO `school_data` (`id`, `name`, `city`, `district`, `type`, `school_code`, `address`, `phone`, `website`, `principal`, `student_count`, `teacher_count`, `established_year`, `is_active`, `data_source`, `last_updated`, `created_at`) VALUES
(1021, '縣立中正國中(小)', '連江縣', '', '國民中學', '724502', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:57', '2025-10-08 07:22:57'),
(1022, '縣立中山國中', '連江縣', '', '國民中學', '724503', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:57', '2025-10-08 07:22:57'),
(1023, '縣立敬恆國中(小)', '連江縣', '', '國民中學', '724504', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:57', '2025-10-08 07:22:57'),
(1024, '縣立東引國中(小)', '連江縣', '', '國民中學', '724505', NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '教育部統計處(CSV)', '2025-10-08 07:22:57', '2025-10-08 07:22:57');

-- --------------------------------------------------------

--
-- 資料表結構 `senior_messages`
--

CREATE TABLE `senior_messages` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL COMMENT '留言標題',
  `content` text NOT NULL COMMENT '留言內容',
  `author_name` varchar(100) NOT NULL COMMENT '學長姐姓名',
  `author_email` varchar(255) NOT NULL COMMENT '學長姐Email',
  `author_department` varchar(100) DEFAULT NULL COMMENT '學長姐科系',
  `author_grade` varchar(50) DEFAULT NULL COMMENT '學長姐年級',
  `author_contact` varchar(100) DEFAULT NULL COMMENT '聯絡方式',
  `message_type` enum('經驗分享','學習建議','生活指南','就業資訊','其他') DEFAULT '經驗分享' COMMENT '留言類型',
  `is_published` tinyint(1) DEFAULT 1 COMMENT '是否發布',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT '創建時間',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新時間',
  `view_count` int(11) DEFAULT 0 COMMENT '瀏覽次數',
  `like_count` int(11) DEFAULT 0 COMMENT '點讚次數',
  `author_grade_year` int(11) DEFAULT NULL COMMENT '入學年份（用於權限控制）'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='學長姐留言表';

--
-- 傾印資料表的資料 `senior_messages`
--

INSERT INTO `senior_messages` (`id`, `title`, `content`, `author_name`, `author_email`, `author_department`, `author_grade`, `author_contact`, `message_type`, `is_published`, `created_at`, `updated_at`, `view_count`, `like_count`, `author_grade_year`) VALUES
(1, '歡迎來到康寧大學！', '各位學弟妹大家好！我是資訊管理系三年級的學長。大學生活真的很精彩，建議大家要好好把握時間，多參加社團活動，也要認真學習專業知識。有任何問題都可以找我聊聊！', '張小明', 'zhangxiaoming@stu.ukn.edu.tw', '資訊管理系', '三年級', 'line: xiaoming123', '經驗分享', 1, '2025-10-23 05:44:38', '2025-11-21 01:47:54', 132, 3, 109),
(2, '選課經驗分享', '學弟妹們，選課真的很重要！建議大家要提前了解各科系的課程內容，多聽學長姐的建議。有些通識課程很有趣，可以拓展視野。記住，不要只選好過的課，要選對自己有用的課！', '李小華', 'lihua@stu.ukn.edu.tw', '商務管理系', '四年級', 'email: lihua@example.com', '學習建議', 1, '2025-10-23 05:44:38', '2025-11-21 01:47:54', 132, 2, 108),
(3, '宿舍生活小貼士', '宿舍生活是大學很重要的一部分！建議大家要和室友好好相處，互相尊重。宿舍的公共區域要保持整潔，晚上不要太吵鬧。如果有問題可以找宿舍管理員或學長姐幫忙。', '王大偉', 'wangdawei@stu.ukn.edu.tw', '護理系', '二年級', 'phone: 0912-345-678', '生活指南', 1, '2025-10-23 05:44:38', '2025-11-21 01:47:54', 132, 0, 110),
(4, '實習經驗分享', '實習是連接學校和職場的重要橋樑。建議大家要主動爭取實習機會，多學習實務經驗。實習期間要認真學習，多問問題，建立良好的人際關係。這些經驗對未來就業很有幫助！', '陳小美', 'chenxiaomei@stu.ukn.edu.tw', '幼兒保育系', '五年級', 'line: chenmei456', '就業資訊', 1, '2025-10-23 05:44:38', '2025-11-21 01:47:54', 186, 0, 107),
(5, '社團活動推薦', '大學除了學習，社團活動也很重要！我參加了攝影社和志工社，學到很多課本以外的東西。建議大家可以選擇1-2個自己感興趣的社團參加，但要平衡時間，不要影響學業。', '林小強', 'linxiaoqiang@stu.ukn.edu.tw', '餐飲管理系', '三年級', 'email: linqiang@example.com', '經驗分享', 1, '2025-10-23 05:44:38', '2025-10-30 04:47:28', 77, 0, 109),
(6, '123', '213432535', '尤世全', '尤世全110534225', '資訊管理科', '5年級', '123', '學習建議', 1, '2025-10-29 01:30:20', '2025-11-21 01:47:54', 58, 1, 110);

-- --------------------------------------------------------

--
-- 資料表結構 `student`
--

CREATE TABLE `student` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `student_id` varchar(50) DEFAULT NULL,
  `department` varchar(255) DEFAULT NULL,
  `grade` varchar(50) DEFAULT NULL,
  `class_name` varchar(100) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `student`
--

INSERT INTO `student` (`id`, `user_id`, `name`, `student_id`, `department`, `grade`, `class_name`, `email`, `phone`, `created_at`, `updated_at`) VALUES
(2, 3, 'student', 'S002', '企業管理學系', '二年級', '企業管理學系二年級甲', 'student@example.com', '0993553177', '2025-10-03 02:04:41', '2025-10-03 02:04:41'),
(3, 7, '尤世全110534225', 'S003', '外國語文學系', '三年級', '外國語文學系三年級甲', '尤世全110534225@example.com', '0981379292', '2025-10-03 02:04:41', '2025-10-03 02:04:41'),
(4, 43, '1111', NULL, NULL, NULL, NULL, '1@gm', NULL, '2025-11-20 03:24:34', '2025-11-20 03:24:34'),
(5, 44, '44', NULL, NULL, NULL, NULL, '4@4gm.co', NULL, '2025-11-20 03:45:00', '2025-11-20 03:45:00'),
(6, 49, '7', NULL, NULL, NULL, NULL, '7@77', NULL, '2025-11-20 06:35:43', '2025-11-20 06:35:43');

-- --------------------------------------------------------

--
-- 資料表結構 `student_assignments`
--

CREATE TABLE `student_assignments` (
  `id` int(11) NOT NULL,
  `student_user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('pending','submitted','graded') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `student_normalized`
--

CREATE TABLE `student_normalized` (
  `user_id` int(11) NOT NULL COMMENT '關聯到 user 表（主鍵）',
  `name` varchar(255) NOT NULL,
  `student_id` varchar(50) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL COMMENT '關聯到 departments 表',
  `grade_id` int(11) DEFAULT NULL COMMENT '關聯到 grades 表',
  `class_name` varchar(100) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='正規化後的學生表';

--
-- 傾印資料表的資料 `student_normalized`
--

INSERT INTO `student_normalized` (`user_id`, `name`, `student_id`, `department_id`, `grade_id`, `class_name`, `email`, `phone`, `created_at`, `updated_at`) VALUES
(3, 'student', 'S002', NULL, 11, '企業管理學系二年級甲', 'student@example.com', '0993553177', '2025-10-03 02:04:41', '2025-10-03 02:04:41'),
(7, '尤世全110534225', 'S003', NULL, 12, '外國語文學系三年級甲', '尤世全110534225@example.com', '0981379292', '2025-10-03 02:04:41', '2025-10-03 02:04:41');

-- --------------------------------------------------------

--
-- 替換檢視表以便查看 `student_view`
-- (請參考以下實際畫面)
--
CREATE TABLE `student_view` (
`id` int(11)
,`user_id` int(11)
,`name` varchar(255)
,`student_id` varchar(50)
,`department` varchar(255)
,`grade` varchar(50)
,`class_name` varchar(100)
,`email` varchar(255)
,`phone` varchar(50)
,`created_at` timestamp
,`updated_at` timestamp
);

-- --------------------------------------------------------

--
-- 資料表結構 `teacher`
--

CREATE TABLE `teacher` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  `department` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `teacher`
--

INSERT INTO `teacher` (`id`, `user_id`, `name`, `department`, `phone`) VALUES
(1, 13, '周建羽', '資訊管理科', '0900000000'),
(3, 14, '嚴竹華', '資訊管理科', NULL),
(4, 18, '李岳倫', '資訊管理科', NULL),
(5, 16, '英英', '應用外語科', '909000000'),
(11, 30, '謝樹明', '資訊管理科', '000000000'),
(12, 15, ':D', '應用外語科', NULL),
(13, 19, '10', '資訊管理科', NULL),
(14, 21, '1', '資訊管理科', NULL),
(15, 27, '88', '資訊管理科', NULL),
(16, 28, 'ww', '資訊管理科', NULL),
(17, 29, 't', '資訊管理科', NULL),
(18, 50, '', '企業管理科', '232');

-- --------------------------------------------------------

--
-- 資料表結構 `teacher_activity_notifications`
--

CREATE TABLE `teacher_activity_notifications` (
  `id` int(11) NOT NULL,
  `teacher_name` varchar(50) DEFAULT NULL COMMENT '老師姓名',
  `teacher_email` varchar(120) DEFAULT NULL COMMENT '老師Email',
  `subject` varchar(200) NOT NULL COMMENT '郵件主旨',
  `content` text NOT NULL COMMENT '郵件內容(純文字或HTML)',
  `event_date` date DEFAULT NULL COMMENT '活動日期',
  `link` varchar(300) DEFAULT NULL COMMENT '活動連結',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `teacher_activity_recipients`
--

CREATE TABLE `teacher_activity_recipients` (
  `id` int(11) NOT NULL,
  `notification_id` int(11) NOT NULL,
  `contact_id` int(11) DEFAULT NULL,
  `email` varchar(120) NOT NULL,
  `status` enum('queued','sent','failed') DEFAULT 'queued',
  `sent_at` datetime DEFAULT NULL,
  `error_message` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `teacher_normalized`
--

CREATE TABLE `teacher_normalized` (
  `user_id` int(11) NOT NULL COMMENT '關聯到 user 表（主鍵）',
  `name` varchar(255) NOT NULL,
  `department_id` int(11) DEFAULT NULL COMMENT '關聯到 departments 表',
  `phone` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='正規化後的老師表';

--
-- 傾印資料表的資料 `teacher_normalized`
--

INSERT INTO `teacher_normalized` (`user_id`, `name`, `department_id`, `phone`, `created_at`, `updated_at`) VALUES
(13, '周建羽', 5, '0900000000', '2025-11-04 07:01:50', '2025-11-04 07:01:50'),
(14, '嚴竹華', 5, NULL, '2025-11-04 07:01:50', '2025-11-04 07:01:50'),
(15, ':D', 7, NULL, '2025-11-04 07:01:50', '2025-11-04 07:01:50'),
(16, '英英', 7, '909000000', '2025-11-04 07:01:50', '2025-11-04 07:01:50'),
(18, '李岳倫', 5, NULL, '2025-11-04 07:01:50', '2025-11-04 07:01:50'),
(19, '10', 5, NULL, '2025-11-04 07:01:50', '2025-11-04 07:01:50'),
(21, '1', 5, NULL, '2025-11-04 07:01:50', '2025-11-04 07:01:50'),
(27, '88', 5, NULL, '2025-11-04 07:01:50', '2025-11-04 07:01:50'),
(28, 'ww', 5, NULL, '2025-11-04 07:01:50', '2025-11-04 07:01:50'),
(29, 't', 5, NULL, '2025-11-04 07:01:50', '2025-11-04 07:01:50'),
(30, '謝樹明', 5, '000000000', '2025-11-04 07:01:50', '2025-11-04 07:01:50');

-- --------------------------------------------------------

--
-- 替換檢視表以便查看 `teacher_view`
-- (請參考以下實際畫面)
--
CREATE TABLE `teacher_view` (
`id` int(11)
,`user_id` int(11)
,`name` varchar(255)
,`department` varchar(255)
,`phone` varchar(50)
,`created_at` timestamp
,`updated_at` timestamp
);

-- --------------------------------------------------------

--
-- 資料表結構 `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `username_changed` tinyint(1) DEFAULT 0 COMMENT '是否已修改過系統生成的帳號（0=未修改，1=已修改）',
  `role` varchar(50) NOT NULL,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `google_id` varchar(255) DEFAULT NULL,
  `profile_picture` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `user`
--

INSERT INTO `user` (`id`, `username`, `password`, `name`, `email`, `username_changed`, `role`, `status`, `created_at`, `google_id`, `profile_picture`) VALUES
(3, 'student', '123456', '林奕廷', 'a@stu.ukn.edu.tw', 0, '學生', 1, '2025-09-26 07:53:26', NULL, NULL),
(7, '尤世全110534225', '', '尤世全110534225', '110534225@stu.ukn.edu.tw', 0, '學生', 1, '2025-09-30 05:49:33', '112378961613864724137', 'https://lh3.googleusercontent.com/a/ACg8ocIt4T0KJcOyblrcluZ3qMJZ4auIjBRJxU7l9J3hhAvJVz7uOQ=s96-c'),
(11, 'admin1', '$2y$10$exebfcnm8ShshvPMUuXRHuKP7INGhOHCoeC5/zS7szh/cyBbc26Y6', '行政人員', 'b@stu.ukn.edu.tw', 0, '學校行政人員', 1, '2025-10-02 16:01:55', NULL, NULL),
(13, 'assistant2', '123456', '林科助', 'c@stu.ukn.edu.tw', 0, '老師', 1, '2025-10-03 00:44:20', NULL, NULL),
(14, 'assistant1', '123', 'Assistant Teacher 1', 'assistant1@example.com', 0, '老師', 1, '2025-10-03 01:44:47', NULL, NULL),
(15, ':D', '', ':D', 'ssps101064@gmail.com', 0, '老師', 1, '2025-10-03 02:20:03', '115051183411602907900', 'https://lh3.googleusercontent.com/a/ACg8ocLseZfFgRCSFX6dQWikmy90fLl8jvkvaY1nkO_p4MKloXXbXcUQ=s96-c'),
(16, '123', '123', '123', '123@a.l', 0, '老師', 1, '2025-10-03 05:28:22', NULL, NULL),
(17, 'kurumi', '12', '時崎狂三', '110534201@stu.ukn.edu.tw', 0, '學生', 1, '2025-10-07 01:08:12', NULL, NULL),
(18, 'assistant3', '123456', '李岳倫', 'd@stu.ukn.edu.tw', 0, '老師', 1, '2025-10-08 08:11:41', NULL, NULL),
(19, '10', '10', '10', '10@gm.a', 0, '老師', 1, '2025-10-09 03:54:39', NULL, NULL),
(20, 'admin', '123456', 'admin', NULL, 0, 'admin', 1, '2025-10-17 04:40:13', NULL, NULL),
(21, '1', '1', '1', '1@q', 0, '老師', 1, '2025-10-21 01:51:36', NULL, NULL),
(22, '2', '2', '2', '2@2', 0, '學生', 1, '2025-10-21 01:51:59', NULL, NULL),
(25, 'oo', 'o', 'oo', 'o@o', 0, '學生', 1, '2025-10-21 01:58:09', NULL, NULL),
(26, 'IMD', '$2y$10$Ihks/Jfs.8nkJOl0baecxuJwKqOmUOTA7nai/.cDNYbYw3D/Uqjo.', '嚴竹華', 'a123@ukn.edu.tw', 0, '學校行政人員', 1, '2025-10-22 01:06:13', NULL, NULL),
(27, '88', '8', '巴巴', 'w@w', 0, '老師', 1, '2025-10-22 06:13:19', NULL, NULL),
(28, 'ww', 'w', 'ww', 'ww@e', 0, '老師', 1, '2025-10-22 06:17:55', NULL, NULL),
(29, 't', 't', 't', 't@t', 0, '老師', 1, '2025-10-22 07:44:17', NULL, NULL),
(30, 'assistant4', '123456', '謝樹明', 'e@ukn.edu.tw', 0, '老師', 0, '2025-10-22 07:46:37', NULL, NULL),
(31, 'l', 'l', 'l', 'l@l', 0, '學生', 1, '2025-10-22 08:39:50', NULL, NULL),
(32, 'FLD', '123456', '應外科主任', 'FLD@ukn.edu.tw', 0, '學校行政人員', 1, '2025-10-29 06:44:59', NULL, NULL),
(37, '110534200@stu.ukn.edu.tw', '123', '一二三', '110534200@stu.ukn.edu.tw', 0, 'student', 1, '2025-10-30 05:37:11', NULL, NULL),
(38, 'staff_3638', '$2y$10$kRDuIkP9GC4f8IapbKnu2enJGqx2a1pu4sIkqdk7NirrDyyblOJD2', '', '', 0, '學校行政人員', 1, '2025-11-13 06:18:22', NULL, NULL),
(40, 'teacher_3538', '$2y$10$WHhG06N7WTnRLacdolnUAe6DB/w8/tNGOwLfS2rN/.iGrOWpAXb2m', NULL, NULL, 0, '老師', 1, '2025-11-19 08:31:44', NULL, NULL),
(42, 'teacher_9459', '$2y$10$FphEHlrpJkSgbFGYhzrqeOt2g/8GwWkGit6DZFeFP.GwxKRmc32Ye', '', 'xuan05180518@gmail.com', 0, '老師', 1, '2025-11-19 09:09:50', NULL, NULL),
(43, '11111111111', '1', '1111', '1@gm', 0, '學生', 1, '2025-11-20 03:24:34', NULL, NULL),
(44, '44444', '4', '44', '4@4gm.co', 0, '學生', 1, '2025-11-20 03:45:00', NULL, NULL),
(45, 'admin_8359', '$2y$10$od5hb0iyX6aiprlZPNlt5usqqYgqXCkCDQzYJDTIRo0cSblwuI/V2', '', '110534235@stu.ukn.eedu.tw', 0, 'admin', 1, '2025-11-20 03:57:15', NULL, NULL),
(46, 'admin_7019', '$2y$10$x5efBnCvhFUtOrOXchYQz.qkJooJtaCGxn7OGTZBhKnwVvl2i75jG', '', '110511114@stu.ukn.eedu.tw', 0, 'admin', 1, '2025-11-20 03:58:25', NULL, NULL),
(47, 'admin_7170', '$2y$10$wxz4QYCT3wxb.4zRW7zX1.LEvByyKZVIHSWPuXTk.CmPHciUBktUS', '', '1105114@stu.ukn.edu.tw', 0, 'admin', 1, '2025-11-20 03:59:39', NULL, NULL),
(48, 'staff_1722', '$2y$10$h85HFlIqmIqrJYuSTdS9l.FEYv/ZSkzpMTpjBjLlYkQuPYBytz4.S', '', '1105@stu.ukn.edu.tw', 0, '學校行政人員', 1, '2025-11-20 04:15:14', NULL, NULL),
(49, '77', '7', '7', '7@77', 0, '學生', 1, '2025-11-20 06:35:43', NULL, NULL),
(50, '444444', '$2y$10$f07R9z7LwnSCppYbj.zixev6TGoZHzkLSqbdilfGIKrPaRVCQYapu', '', '12@stu.ukn.edu.tw', 1, '老師', 1, '2025-11-20 06:36:42', NULL, NULL);

-- --------------------------------------------------------

--
-- 檢視表結構 `chat_groups_view`
--
DROP TABLE IF EXISTS `chat_groups_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `chat_groups_view`  AS SELECT `cg`.`id` AS `id`, `cg`.`group_name` AS `group_name`, `u`.`username` AS `created_by`, `d`.`name` AS `department`, `cg`.`description` AS `description`, `cg`.`created_at` AS `created_at` FROM ((`chat_groups_normalized` `cg` join `user` `u` on(`cg`.`created_by_user_id` = `u`.`id`)) left join `departments` `d` on(`cg`.`department_id` = `d`.`id`)) ;

-- --------------------------------------------------------

--
-- 檢視表結構 `cooperation_applications_view`
--
DROP TABLE IF EXISTS `cooperation_applications_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `cooperation_applications_view`  AS SELECT `ca`.`id` AS `id`, `ca`.`teacher_id` AS `teacher_id`, coalesce((select `u`.`username` from (`user` `u` join `teacher` `t` on(`u`.`id` = `t`.`user_id`)) where `t`.`user_id` = `ca`.`teacher_id` limit 1),(select `user`.`username` from `user` where `user`.`id` = `ca`.`teacher_id` limit 1),'') AS `teacher_username`, `ca`.`application_date` AS `application_date`, `ca`.`approval_number` AS `approval_number`, `d`.`name` AS `department`, `ca`.`principal_investigator` AS `principal_investigator`, CASE WHEN `ca`.`regulations_read` THEN 'yes' ELSE 'no' END AS `regulations_read`, group_concat(`cac`.`category_name` separator ', ') AS `application_categories`, `ca`.`project_amount` AS `project_amount`, `ca`.`admin_fee_percentage` AS `admin_fee_percentage`, `c`.`name` AS `company_name`, `c`.`contact_person` AS `company_contact`, `c`.`phone` AS `company_phone`, `ca`.`project_title` AS `project_title`, `ca`.`expected_outcomes` AS `expected_outcomes`, `ca`.`project_timeline` AS `project_timeline`, CASE WHEN `ca`.`has_intellectual_property` THEN 'yes' ELSE 'no' END AS `has_intellectual_property`, `ca`.`contract_file_path` AS `contract_file_path`, `ca`.`proposal_file_path` AS `proposal_file_path`, `ast`.`name` AS `status`, `ca`.`admin_comment` AS `admin_comment`, `ca`.`review_date` AS `review_date`, `ca`.`created_at` AS `created_at`, `ca`.`updated_at` AS `updated_at` FROM (((((`cooperation_applications_normalized` `ca` left join `companies` `c` on(`ca`.`company_id` = `c`.`id`)) left join `departments` `d` on(`ca`.`department_id` = `d`.`id`)) left join `application_statuses` `ast` on(`ca`.`status_id` = `ast`.`id`)) left join `cooperation_application_categories` `cac` on(`ca`.`id` = `cac`.`cooperation_application_id`)) left join `teacher` `t` on(`ca`.`teacher_id` = `t`.`user_id`)) GROUP BY `ca`.`id` ;

-- --------------------------------------------------------

--
-- 檢視表結構 `enrollment_applications_view`
--
DROP TABLE IF EXISTS `enrollment_applications_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `enrollment_applications_view`  AS SELECT `ea`.`id` AS `id`, `ea`.`username` AS `username`, `ea`.`name` AS `name`, `i`.`name` AS `identity`, `g`.`name` AS `gender`, `ea`.`phone1` AS `phone1`, `ea`.`phone2` AS `phone2`, `ea`.`email` AS `email`, max(case when `ep`.`preference_order` = 1 then `d`.`name` end) AS `intention1`, max(case when `ep`.`preference_order` = 1 then `es`.`name` end) AS `system1`, max(case when `ep`.`preference_order` = 1 then `d`.`name` end) AS `department1`, max(case when `ep`.`preference_order` = 2 then `d`.`name` end) AS `intention2`, max(case when `ep`.`preference_order` = 2 then `es`.`name` end) AS `system2`, max(case when `ep`.`preference_order` = 2 then `d`.`name` end) AS `department2`, max(case when `ep`.`preference_order` = 3 then `d`.`name` end) AS `intention3`, max(case when `ep`.`preference_order` = 3 then `es`.`name` end) AS `system3`, max(case when `ep`.`preference_order` = 3 then `d`.`name` end) AS `department3`, coalesce(`s`.`name`,'') AS `junior_high`, `gr`.`name` AS `current_grade`, `ea`.`line_id` AS `line_id`, `ea`.`facebook` AS `facebook`, `ea`.`remarks` AS `remarks`, `ast`.`name` AS `status`, `ea`.`admin_comment` AS `admin_comment`, `ea`.`created_at` AS `created_at`, `ea`.`updated_at` AS `updated_at` FROM ((((((((`enrollment_applications_normalized` `ea` left join `identities` `i` on(`ea`.`identity_id` = `i`.`id`)) left join `genders` `g` on(`ea`.`gender_id` = `g`.`id`)) left join `schools` `s` on(`ea`.`junior_high_school_id` = `s`.`id`)) left join `grades` `gr` on(`ea`.`current_grade_id` = `gr`.`id`)) left join `application_statuses` `ast` on(`ea`.`status_id` = `ast`.`id`)) left join `enrollment_preferences` `ep` on(`ea`.`id` = `ep`.`enrollment_application_id`)) left join `departments` `d` on(`ep`.`department_id` = `d`.`id`)) left join `education_systems` `es` on(`ep`.`education_system_id` = `es`.`id`)) GROUP BY `ea`.`id` ;

-- --------------------------------------------------------

--
-- 檢視表結構 `group_members_view`
--
DROP TABLE IF EXISTS `group_members_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `group_members_view`  AS SELECT `gm`.`id` AS `id`, `gm`.`group_id` AS `group_id`, `u`.`username` AS `username`, `rt`.`name` AS `role`, `gm`.`joined_at` AS `joined_at` FROM ((`group_members_normalized` `gm` join `user` `u` on(`gm`.`user_id` = `u`.`id`)) join `role_types` `rt` on(`gm`.`role_type_id` = `rt`.`id`)) ;

-- --------------------------------------------------------

--
-- 檢視表結構 `group_messages_view`
--
DROP TABLE IF EXISTS `group_messages_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `group_messages_view`  AS SELECT `gm`.`id` AS `id`, `gm`.`group_id` AS `group_id`, `u`.`username` AS `from_user`, `gm`.`message` AS `message`, `mt`.`name` AS `message_type`, `gm`.`created_at` AS `timestamp` FROM ((`group_messages_normalized` `gm` join `user` `u` on(`gm`.`from_user_id` = `u`.`id`)) left join `message_types` `mt` on(`gm`.`message_type_id` = `mt`.`id`)) ;

-- --------------------------------------------------------

--
-- 檢視表結構 `private_chat_history_view`
--
DROP TABLE IF EXISTS `private_chat_history_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `private_chat_history_view`  AS SELECT `p`.`id` AS `id`, `u1`.`username` AS `from_user`, `u2`.`username` AS `to_user`, `p`.`message` AS `message`, `mt`.`name` AS `message_type`, `p`.`created_at` AS `timestamp` FROM (((`private_chat_history_normalized` `p` join `user` `u1` on(`p`.`from_user_id` = `u1`.`id`)) join `user` `u2` on(`p`.`to_user_id` = `u2`.`id`)) left join `message_types` `mt` on(`p`.`message_type_id` = `mt`.`id`)) ;

-- --------------------------------------------------------

--
-- 檢視表結構 `student_view`
--
DROP TABLE IF EXISTS `student_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `student_view`  AS SELECT `s`.`user_id` AS `id`, `s`.`user_id` AS `user_id`, `s`.`name` AS `name`, `s`.`student_id` AS `student_id`, coalesce(`d`.`name`,'') AS `department`, coalesce(`g`.`name`,'') AS `grade`, `s`.`class_name` AS `class_name`, `s`.`email` AS `email`, `s`.`phone` AS `phone`, `s`.`created_at` AS `created_at`, `s`.`updated_at` AS `updated_at` FROM ((`student_normalized` `s` left join `departments` `d` on(`s`.`department_id` = `d`.`id`)) left join `grades` `g` on(`s`.`grade_id` = `g`.`id`)) ;

-- --------------------------------------------------------

--
-- 檢視表結構 `teacher_view`
--
DROP TABLE IF EXISTS `teacher_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `teacher_view`  AS SELECT `t`.`user_id` AS `id`, `t`.`user_id` AS `user_id`, `t`.`name` AS `name`, coalesce(`d`.`name`,'') AS `department`, `t`.`phone` AS `phone`, `t`.`created_at` AS `created_at`, `t`.`updated_at` AS `updated_at` FROM (`teacher_normalized` `t` left join `departments` `d` on(`t`.`department_id` = `d`.`id`)) ;

--
-- 已傾印資料表的索引
--

--
-- 資料表索引 `activity_records`
--
ALTER TABLE `activity_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_teacher_id` (`teacher_id`),
  ADD KEY `idx_activity_date` (`activity_date`);

--
-- 資料表索引 `admission_applications`
--
ALTER TABLE `admission_applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_session_id` (`session_id`),
  ADD KEY `idx_session_choice` (`session_choice`),
  ADD KEY `idx_email` (`email`);

--
-- 資料表索引 `admission_courses`
--
ALTER TABLE `admission_courses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_department_id` (`department_id`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_sort` (`sort_order`);

--
-- 資料表索引 `admission_departments`
--
ALTER TABLE `admission_departments`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `admission_grades`
--
ALTER TABLE `admission_grades`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `admission_recommendations`
--
ALTER TABLE `admission_recommendations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_recommender` (`recommender_student_id`),
  ADD KEY `idx_student_email` (`student_email`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- 資料表索引 `admission_sessions`
--
ALTER TABLE `admission_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_date` (`session_date`);

--
-- 資料表索引 `ai_chat_history`
--
ALTER TABLE `ai_chat_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- 資料表索引 `application_statuses`
--
ALTER TABLE `application_statuses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `idx_code` (`code`);

--
-- 資料表索引 `assignment_logs`
--
ALTER TABLE `assignment_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_teacher_id` (`teacher_id`);

--
-- 資料表索引 `chat_groups_normalized`
--
ALTER TABLE `chat_groups_normalized`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_created_by` (`created_by_user_id`),
  ADD KEY `idx_department_id` (`department_id`);

--
-- 資料表索引 `chat_history`
--
ALTER TABLE `chat_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_timestamp` (`timestamp`);

--
-- 資料表索引 `continued_admission`
--
ALTER TABLE `continued_admission`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_number` (`id_number`),
  ADD UNIQUE KEY `apply_no` (`apply_no`),
  ADD KEY `idx_id_number` (`id_number`),
  ADD KEY `idx_exam_no` (`exam_no`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- 資料表索引 `continued_admission_choices`
--
ALTER TABLE `continued_admission_choices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_application_id` (`application_id`),
  ADD KEY `idx_department_code` (`department_code`),
  ADD KEY `idx_choice_order` (`choice_order`);

--
-- 資料表索引 `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `idx_code` (`code`),
  ADD KEY `idx_name` (`name`);

--
-- 資料表索引 `department_quotas`
--
ALTER TABLE `department_quotas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_department_name` (`department_name`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- 資料表索引 `education_systems`
--
ALTER TABLE `education_systems`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `idx_code` (`code`);

--
-- 資料表索引 `enrollment_applications`
--
ALTER TABLE `enrollment_applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_identity` (`identity`);

--
-- 資料表索引 `enrollment_applications_normalized`
--
ALTER TABLE `enrollment_applications_normalized`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_status_id` (`status_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_identity_id` (`identity_id`),
  ADD KEY `idx_junior_high_school_id` (`junior_high_school_id`),
  ADD KEY `fk_enrollment_gender` (`gender_id`),
  ADD KEY `fk_enrollment_grade` (`current_grade_id`);

--
-- 資料表索引 `enrollment_contact_logs`
--
ALTER TABLE `enrollment_contact_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_teacher_id` (`teacher_id`);

--
-- 資料表索引 `enrollment_intention`
--
ALTER TABLE `enrollment_intention`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `enrollment_preferences`
--
ALTER TABLE `enrollment_preferences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_application_preference` (`enrollment_application_id`,`preference_order`),
  ADD KEY `idx_enrollment_application_id` (`enrollment_application_id`),
  ADD KEY `idx_department_id` (`department_id`),
  ADD KEY `idx_education_system_id` (`education_system_id`);

--
-- 資料表索引 `genders`
--
ALTER TABLE `genders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- 資料表索引 `grades`
--
ALTER TABLE `grades`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `idx_code` (`code`);

--
-- 資料表索引 `group_chat_members`
--
ALTER TABLE `group_chat_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_group_member` (`group_id`,`username`),
  ADD KEY `idx_group_id` (`group_id`),
  ADD KEY `idx_username` (`username`);

--
-- 資料表索引 `group_chat_messages`
--
ALTER TABLE `group_chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_group_id` (`group_id`),
  ADD KEY `idx_from_user` (`from_user`),
  ADD KEY `idx_timestamp` (`timestamp`);

--
-- 資料表索引 `group_info`
--
ALTER TABLE `group_info`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_created_by` (`created_by`);

--
-- 資料表索引 `group_members_normalized`
--
ALTER TABLE `group_members_normalized`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_group_member` (`group_id`,`user_id`),
  ADD KEY `idx_group_id` (`group_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `role_type_id` (`role_type_id`);

--
-- 資料表索引 `group_messages_normalized`
--
ALTER TABLE `group_messages_normalized`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_group_id` (`group_id`),
  ADD KEY `idx_from_user` (`from_user_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_message_type` (`message_type_id`);

--
-- 資料表索引 `identities`
--
ALTER TABLE `identities`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- 資料表索引 `ip_rights`
--
ALTER TABLE `ip_rights`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cooperation_application_id` (`cooperation_application_id`),
  ADD KEY `idx_ip_type` (`ip_type`);

--
-- 資料表索引 `junior_school_recruitment_applications`
--
ALTER TABLE `junior_school_recruitment_applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_school_name` (`school_name`),
  ADD KEY `idx_city` (`city`),
  ADD KEY `idx_district` (`district`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_contact_email` (`contact_email`),
  ADD KEY `idx_preferred_date` (`preferred_date`);

--
-- 資料表索引 `message_read_status`
--
ALTER TABLE `message_read_status`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_message_reader` (`message_id`,`reader_username`),
  ADD KEY `idx_message_id` (`message_id`),
  ADD KEY `idx_reader_username` (`reader_username`),
  ADD KEY `idx_read_at` (`read_at`);

--
-- 資料表索引 `message_types`
--
ALTER TABLE `message_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `idx_code` (`code`);

--
-- 資料表索引 `notification_logs`
--
ALTER TABLE `notification_logs`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `page_content`
--
ALTER TABLE `page_content`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `page_key` (`page_key`);

--
-- 資料表索引 `private_chat_history`
--
ALTER TABLE `private_chat_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_from_user` (`from_user`),
  ADD KEY `idx_to_user` (`to_user`),
  ADD KEY `idx_timestamp` (`timestamp`);

--
-- 資料表索引 `private_chat_history_normalized`
--
ALTER TABLE `private_chat_history_normalized`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_from_user` (`from_user_id`),
  ADD KEY `idx_to_user` (`to_user_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_message_type` (`message_type_id`);

--
-- 資料表索引 `qa`
--
ALTER TABLE `qa`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_active` (`is_active`);

--
-- 資料表索引 `role_types`
--
ALTER TABLE `role_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `idx_code` (`code`);

--
-- 資料表索引 `schools`
--
ALTER TABLE `schools`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_name` (`name`),
  ADD KEY `idx_city` (`city`),
  ADD KEY `idx_district` (`district`),
  ADD KEY `idx_city_district` (`city`,`district`);

--
-- 資料表索引 `schools_contacts`
--
ALTER TABLE `schools_contacts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_email_school` (`email`,`school_name`),
  ADD KEY `idx_school_id` (`school_id`),
  ADD KEY `idx_school_name` (`school_name`),
  ADD KEY `idx_city` (`city`),
  ADD KEY `idx_district` (`district`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_email` (`email`);

--
-- 資料表索引 `school_data`
--
ALTER TABLE `school_data`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_name` (`name`),
  ADD KEY `idx_city` (`city`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_active` (`is_active`);

--
-- 資料表索引 `senior_messages`
--
ALTER TABLE `senior_messages`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `student`
--
ALTER TABLE `student`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_department` (`department`),
  ADD KEY `idx_name` (`name`);

--
-- 資料表索引 `student_assignments`
--
ALTER TABLE `student_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student` (`student_user_id`),
  ADD KEY `idx_due` (`due_date`);

--
-- 資料表索引 `student_normalized`
--
ALTER TABLE `student_normalized`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `student_id` (`student_id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_department_id` (`department_id`),
  ADD KEY `idx_grade_id` (`grade_id`);

--
-- 資料表索引 `teacher`
--
ALTER TABLE `teacher`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- 資料表索引 `teacher_activity_notifications`
--
ALTER TABLE `teacher_activity_notifications`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `teacher_activity_recipients`
--
ALTER TABLE `teacher_activity_recipients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notification` (`notification_id`),
  ADD KEY `idx_contact` (`contact_id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_sent_at` (`sent_at`);

--
-- 資料表索引 `teacher_normalized`
--
ALTER TABLE `teacher_normalized`
  ADD PRIMARY KEY (`user_id`),
  ADD KEY `idx_department_id` (`department_id`);

--
-- 資料表索引 `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `google_id` (`google_id`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_google_id` (`google_id`),
  ADD KEY `idx_email` (`email`);

--
-- 在傾印的資料表使用自動遞增(AUTO_INCREMENT)
--

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `activity_records`
--
ALTER TABLE `activity_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `admission_applications`
--
ALTER TABLE `admission_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `admission_courses`
--
ALTER TABLE `admission_courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `admission_departments`
--
ALTER TABLE `admission_departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `admission_grades`
--
ALTER TABLE `admission_grades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `admission_recommendations`
--
ALTER TABLE `admission_recommendations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `admission_sessions`
--
ALTER TABLE `admission_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `ai_chat_history`
--
ALTER TABLE `ai_chat_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `application_statuses`
--
ALTER TABLE `application_statuses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `assignment_logs`
--
ALTER TABLE `assignment_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `chat_groups_normalized`
--
ALTER TABLE `chat_groups_normalized`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `chat_history`
--
ALTER TABLE `chat_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `continued_admission`
--
ALTER TABLE `continued_admission`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `continued_admission_choices`
--
ALTER TABLE `continued_admission_choices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `department_quotas`
--
ALTER TABLE `department_quotas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `education_systems`
--
ALTER TABLE `education_systems`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `enrollment_applications`
--
ALTER TABLE `enrollment_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `enrollment_applications_normalized`
--
ALTER TABLE `enrollment_applications_normalized`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `enrollment_contact_logs`
--
ALTER TABLE `enrollment_contact_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `enrollment_intention`
--
ALTER TABLE `enrollment_intention`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `enrollment_preferences`
--
ALTER TABLE `enrollment_preferences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `genders`
--
ALTER TABLE `genders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `grades`
--
ALTER TABLE `grades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `group_chat_members`
--
ALTER TABLE `group_chat_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `group_chat_messages`
--
ALTER TABLE `group_chat_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `group_info`
--
ALTER TABLE `group_info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `group_members_normalized`
--
ALTER TABLE `group_members_normalized`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `group_messages_normalized`
--
ALTER TABLE `group_messages_normalized`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `identities`
--
ALTER TABLE `identities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `ip_rights`
--
ALTER TABLE `ip_rights`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `junior_school_recruitment_applications`
--
ALTER TABLE `junior_school_recruitment_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '申請編號', AUTO_INCREMENT=5;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `message_read_status`
--
ALTER TABLE `message_read_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `message_types`
--
ALTER TABLE `message_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `notification_logs`
--
ALTER TABLE `notification_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `page_content`
--
ALTER TABLE `page_content`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `private_chat_history`
--
ALTER TABLE `private_chat_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `private_chat_history_normalized`
--
ALTER TABLE `private_chat_history_normalized`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `qa`
--
ALTER TABLE `qa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `role_types`
--
ALTER TABLE `role_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `schools`
--
ALTER TABLE `schools`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `schools_contacts`
--
ALTER TABLE `schools_contacts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `school_data`
--
ALTER TABLE `school_data`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1025;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `senior_messages`
--
ALTER TABLE `senior_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `student`
--
ALTER TABLE `student`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `student_assignments`
--
ALTER TABLE `student_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `teacher`
--
ALTER TABLE `teacher`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `teacher_activity_notifications`
--
ALTER TABLE `teacher_activity_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `teacher_activity_recipients`
--
ALTER TABLE `teacher_activity_recipients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- 已傾印資料表的限制式
--

--
-- 資料表的限制式 `activity_records`
--
ALTER TABLE `activity_records`
  ADD CONSTRAINT `fk_activity_user` FOREIGN KEY (`teacher_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- 資料表的限制式 `admission_applications`
--
ALTER TABLE `admission_applications`
  ADD CONSTRAINT `fk_applications_session` FOREIGN KEY (`session_id`) REFERENCES `admission_sessions` (`id`) ON DELETE SET NULL;

--
-- 資料表的限制式 `admission_courses`
--
ALTER TABLE `admission_courses`
  ADD CONSTRAINT `fk_courses_department` FOREIGN KEY (`department_id`) REFERENCES `admission_departments` (`id`) ON DELETE SET NULL;

--
-- 資料表的限制式 `chat_groups_normalized`
--
ALTER TABLE `chat_groups_normalized`
  ADD CONSTRAINT `chat_groups_normalized_ibfk_1` FOREIGN KEY (`created_by_user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chat_groups_normalized_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL;

--
-- 資料表的限制式 `continued_admission_choices`
--
ALTER TABLE `continued_admission_choices`
  ADD CONSTRAINT `continued_admission_choices_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `continued_admission` (`id`) ON DELETE CASCADE;

--
-- 資料表的限制式 `enrollment_applications_normalized`
--
ALTER TABLE `enrollment_applications_normalized`
  ADD CONSTRAINT `enrollment_applications_normalized_ibfk_1` FOREIGN KEY (`status_id`) REFERENCES `application_statuses` (`id`),
  ADD CONSTRAINT `enrollment_applications_normalized_ibfk_2` FOREIGN KEY (`junior_high_school_id`) REFERENCES `schools` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_enrollment_gender` FOREIGN KEY (`gender_id`) REFERENCES `genders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_enrollment_grade` FOREIGN KEY (`current_grade_id`) REFERENCES `grades` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_enrollment_identity` FOREIGN KEY (`identity_id`) REFERENCES `identities` (`id`);

--
-- 資料表的限制式 `group_chat_members`
--
ALTER TABLE `group_chat_members`
  ADD CONSTRAINT `fk_group_member_group` FOREIGN KEY (`group_id`) REFERENCES `group_info` (`id`) ON DELETE CASCADE;

--
-- 資料表的限制式 `schools_contacts`
--
ALTER TABLE `schools_contacts`
  ADD CONSTRAINT `schools_contacts_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE SET NULL;

--
-- 資料表的限制式 `teacher_activity_recipients`
--
ALTER TABLE `teacher_activity_recipients`
  ADD CONSTRAINT `teacher_activity_recipients_ibfk_1` FOREIGN KEY (`notification_id`) REFERENCES `teacher_activity_notifications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_activity_recipients_ibfk_2` FOREIGN KEY (`contact_id`) REFERENCES `schools_contacts` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
