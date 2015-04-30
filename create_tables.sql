SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- --------------------------------------------------------

--
-- Table structure for table `allowed`
--

DROP TABLE IF EXISTS `allowed`;
CREATE TABLE IF NOT EXISTS `allowed` (
  `course_id` char(10) NOT NULL,
  `batch_name` varchar(30) NOT NULL,
  `batch_dept` char(5) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `batches`
--

DROP TABLE IF EXISTS `batches`;
CREATE TABLE IF NOT EXISTS `batches` (
  `batch_name` varchar(30) NOT NULL,
  `batch_dept` char(5) NOT NULL,
  `size` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `config`
--

DROP TABLE IF EXISTS `config`;
CREATE TABLE IF NOT EXISTS `config` (
  `Name` varchar(30) NOT NULL,
  `value` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

DROP TABLE IF EXISTS `courses`;
CREATE TABLE IF NOT EXISTS `courses` (
  `course_id` char(10) NOT NULL,
  `course_name` varchar(100) NOT NULL,
  `fac_id` char(25) NOT NULL,
  `allow_conflict` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `depts`
--

DROP TABLE IF EXISTS `depts`;
CREATE TABLE IF NOT EXISTS `depts` (
  `dept_code` char(5) NOT NULL,
  `dept_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `faculty`
--

DROP TABLE IF EXISTS `faculty`;
CREATE TABLE IF NOT EXISTS `faculty` (
  `uName` char(25) NOT NULL,
  `fac_name` varchar(50) NOT NULL,
  `pswd` char(64) NOT NULL,
  `level` enum('dean','hod','faculty','') NOT NULL DEFAULT 'faculty',
  `dept_code` char(5) NOT NULL,
  `dateRegd` char(25) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

DROP TABLE IF EXISTS `rooms`;
CREATE TABLE IF NOT EXISTS `rooms` (
  `room_name` varchar(25) NOT NULL,
  `capacity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `slots`
--

DROP TABLE IF EXISTS `slots`;
CREATE TABLE IF NOT EXISTS `slots` (
  `table_name` varchar(30) NOT NULL,
  `day` int(1) unsigned NOT NULL,
  `slot_num` int(2) unsigned NOT NULL,
  `state` enum('active','disabled') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `slot_allocs`
--

DROP TABLE IF EXISTS `slot_allocs`;
CREATE TABLE IF NOT EXISTS `slot_allocs` (
  `table_name` varchar(30) NOT NULL,
  `day` int(1) unsigned NOT NULL,
  `slot_num` int(2) unsigned NOT NULL,
  `room` varchar(25) NOT NULL,
  `course_id` char(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `timetables`
--

DROP TABLE IF EXISTS `timetables`;
CREATE TABLE IF NOT EXISTS `timetables` (
  `table_name` varchar(30) NOT NULL,
  `days` int(11) NOT NULL DEFAULT '5',
  `slots` int(11) NOT NULL DEFAULT '0',
  `duration` int(11) NOT NULL DEFAULT '90',
  `start_hr` char(2) NOT NULL DEFAULT '08',
  `start_min` char(2) NOT NULL DEFAULT '30',
  `start_mer` enum('AM','PM') NOT NULL DEFAULT 'AM',
  `allowConflicts` tinyint(1) NOT NULL DEFAULT '0',
  `frozen` tinyint(1) NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `allowed`
--
ALTER TABLE `allowed`
  ADD PRIMARY KEY (`course_id`,`batch_name`,`batch_dept`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `batch_name` (`batch_name`,`batch_dept`);

--
-- Indexes for table `batches`
--
ALTER TABLE `batches`
  ADD PRIMARY KEY (`batch_name`,`batch_dept`),
  ADD KEY `batches_department` (`batch_dept`);

--
-- Indexes for table `config`
--
ALTER TABLE `config`
  ADD PRIMARY KEY (`Name`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`course_id`),
  ADD KEY `fac_id` (`fac_id`);

--
-- Indexes for table `depts`
--
ALTER TABLE `depts`
  ADD PRIMARY KEY (`dept_code`);

--
-- Indexes for table `faculty`
--
ALTER TABLE `faculty`
  ADD PRIMARY KEY (`uName`),
  ADD KEY `dept_code` (`dept_code`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`room_name`);

--
-- Indexes for table `slots`
--
ALTER TABLE `slots`
  ADD PRIMARY KEY (`table_name`,`day`,`slot_num`);

--
-- Indexes for table `slot_allocs`
--
ALTER TABLE `slot_allocs`
  ADD PRIMARY KEY (`table_name`,`day`,`slot_num`,`room`),
  ADD KEY `fk_course_id` (`course_id`),
  ADD KEY `fk_room` (`room`),
  ADD KEY `fk_slot` (`day`,`slot_num`);

--
-- Indexes for table `timetables`
--
ALTER TABLE `timetables`
  ADD PRIMARY KEY (`table_name`);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `allowed`
--
ALTER TABLE `allowed`
  ADD CONSTRAINT `batch` FOREIGN KEY (`batch_name`, `batch_dept`) REFERENCES `batches` (`batch_name`, `batch_dept`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `batches`
--
ALTER TABLE `batches`
  ADD CONSTRAINT `batches_department` FOREIGN KEY (`batch_dept`) REFERENCES `depts` (`dept_code`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`fac_id`) REFERENCES `faculty` (`uName`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `faculty`
--
ALTER TABLE `faculty`
  ADD CONSTRAINT `faculty_ibfk_1` FOREIGN KEY (`dept_code`) REFERENCES `depts` (`dept_code`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `slots`
--
ALTER TABLE `slots`
  ADD CONSTRAINT `fk_timetable` FOREIGN KEY (`table_name`) REFERENCES `timetables` (`table_name`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `slot_allocs`
--
ALTER TABLE `slot_allocs`
  ADD CONSTRAINT `fk_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_room` FOREIGN KEY (`room`) REFERENCES `rooms` (`room_name`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_slot` FOREIGN KEY (`table_name`, `day`, `slot_num`) REFERENCES `slots` (`table_name`, `day`, `slot_num`) ON DELETE CASCADE ON UPDATE CASCADE;