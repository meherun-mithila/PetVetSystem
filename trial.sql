-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 03, 2025 at 10:13 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `trial`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `admin_id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`admin_id`, `name`, `email`, `password`) VALUES
(1, 'Adeeb', '12adeeb@gmail.com', 'adeeb123'),
(2, 'Mithila', 'meherunmithila1@gmail.com', 'Mithila12');

-- --------------------------------------------------------

--
-- Table structure for table `adoptionlistings`
--

CREATE TABLE `adoptionlistings` (
  `listing_id` int(11) NOT NULL,
  `posted_by` int(11) DEFAULT NULL,
  `animal_name` varchar(100) DEFAULT NULL,
  `species` varchar(50) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('Available','Adopted','Pending') DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `adoptionlistings`
--

INSERT INTO `adoptionlistings` (`listing_id`, `posted_by`, `animal_name`, `species`, `age`, `description`, `status`, `approved_by`) VALUES
(1, 1, 'Teddy', 'Dog', 2, 'Small Shih Tzu, affectionate and house-trained.', 'Available', 1),
(2, 2, 'Sushi', 'Cat', 3, 'Curious Siamese cat, very vocal.', 'Pending', 2),
(3, 3, 'Choco', 'Rabbit', 4, 'Loves to hop around and chew cardboard.', 'Adopted', 1),
(4, 4, 'Kiwi', 'Bird', 1, 'Tiny lovebird, active and colorful.', 'Available', NULL),
(5, 5, 'Diesel', 'Dog', 7, 'Strong Rottweiler, trained and disciplined.', 'Adopted', 2),
(6, 6, 'Maple', 'Cat', 5, 'Quiet and gentle, enjoys cozy places.', 'Available', 2),
(7, 7, 'Flash', 'Turtle', 10, 'Very calm, ideal for kids.', 'Pending', 2),
(8, 8, 'Rolo', 'Hamster', 1, 'Cute Syrian hamster, loves the wheel.', 'Available', NULL),
(9, 9, 'Tiger', 'Dog', 6, 'Mixed breed, loyal and brave.', 'Available', 1),
(10, 10, 'Minty', 'Bird', 2, 'Budgerigar that whistles when happy.', 'Adopted', 2);

-- --------------------------------------------------------

--
-- Table structure for table `adoptionrequests`
--

CREATE TABLE `adoptionrequests` (
  `request_id` int(11) NOT NULL,
  `listing_id` int(11) DEFAULT NULL,
  `requested_by` int(11) DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT NULL,
  `date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `adoptionrequests`
--

INSERT INTO `adoptionrequests` (`request_id`, `listing_id`, `requested_by`, `status`, `date`) VALUES
(1, 1, 1, 'Pending', '2025-07-01'),
(2, 2, 2, 'Approved', '2025-07-05'),
(3, 3, 3, 'Rejected', '2025-07-10'),
(4, 4, 1, 'Pending', '2025-07-12'),
(5, 5, 4, 'Approved', '2025-07-15'),
(6, 6, 5, 'Rejected', '2025-07-18'),
(7, 7, 2, 'Approved', '2025-07-20'),
(8, 8, 6, 'Pending', '2025-07-25'),
(9, 9, 3, 'Approved', '2025-07-27'),
(10, 10, 7, 'Rejected', '2025-07-30');

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `appointment_id` int(11) NOT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `time` time DEFAULT NULL,
  `status` enum('Scheduled','Completed','Cancelled') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`appointment_id`, `doctor_id`, `patient_id`, `date`, `time`, `status`) VALUES
(1, 11, 20, '2025-08-01', '10:00:00', 'Scheduled'),
(2, 12, 2, '2025-08-01', '11:30:00', 'Completed'),
(3, 3, 13, '2025-08-02', '09:45:00', 'Cancelled'),
(4, 4, 14, '2025-08-02', '14:00:00', 'Scheduled'),
(5, 15, 21, '2025-08-03', '15:30:00', 'Scheduled'),
(6, 1, 6, '2025-08-04', '13:15:00', 'Completed'),
(7, 12, 17, '2025-08-04', '10:30:00', 'Cancelled'),
(8, 3, 8, '2025-08-05', '16:00:00', 'Scheduled'),
(9, 14, 19, '2025-08-06', '12:00:00', 'Completed'),
(10, 5, 22, '2025-08-07', '11:00:00', 'Scheduled');

-- --------------------------------------------------------

--
-- Table structure for table `appointmentservices`
--

CREATE TABLE `appointmentservices` (
  `appointment_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointmentservices`
--

INSERT INTO `appointmentservices` (`appointment_id`, `service_id`, `quantity`, `notes`) VALUES
(1, 10, 1, 'Standard blood test'),
(1, 12, 2, 'X-ray for left arm and chest'),
(2, 3, 1, 'ECG performed'),
(3, 11, 1, 'Urgent test requested'),
(4, 8, 1, 'Ultrasound for abdomen'),
(5, 7, 1, NULL),
(6, 9, 1, 'Routine checkup'),
(7, 13, 2, 'Repeat test'),
(8, 1, 1, NULL),
(9, 6, 1, 'Follow-up imaging required');

-- --------------------------------------------------------

--
-- Table structure for table `articles`
--

CREATE TABLE `articles` (
  `article_id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `author_id` int(11) DEFAULT NULL,
  `date_posted` date DEFAULT NULL,
  `tags` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `articles`
--

INSERT INTO `articles` (`article_id`, `title`, `content`, `author_id`, `date_posted`, `tags`) VALUES
(1, 'Understanding Pet Nutrition', 'Proper nutrition is vital for a healthy pet life...', 10, '2025-07-10', 'pets,health,nutrition'),
(2, 'Top 5 Dog Breeds for Families', 'Choosing the right dog breed for your family is important...', 2, '2025-07-12', 'dogs,breeds,family'),
(3, 'How to Handle Pet Anxiety', 'Anxiety in pets can manifest in many ways...', 1, '2025-07-14', 'pets,anxiety,behavior'),
(4, 'Benefits of Regular Vet Checkups', 'Routine vet visits can detect problems early...', 13, '2025-07-16', 'health,checkups,veterinary'),
(5, 'Pet Adoption: What You Need to Know', 'Adopting a pet is a rewarding experience...', 4, '2025-07-18', 'adoption,pets,guide'),
(6, 'Essential Vaccines for Cats', 'Vaccines protect your cat from serious illnesses...', 10, '2025-07-20', 'cats,vaccines,health'),
(7, 'Common Myths About Pet Food', 'Let’s debunk the most common myths surrounding pet food...', 11, '2025-07-21', 'myths,food,nutrition'),
(8, 'Creating a Pet-Friendly Home', 'Make your home safe and comfortable for your furry friend...', 14, '2025-07-22', 'home,safety,pets'),
(9, 'Training Your Puppy 101', 'Start training early to avoid behavioral issues...', 15, '2025-07-25', 'training,puppy,behavior'),
(10, 'How to Socialize Your Cat', 'Cats need socialization too! Here’s how to help...', 13, '2025-07-28', 'cats,socialization,training');

-- --------------------------------------------------------

--
-- Table structure for table `doctors`
--

CREATE TABLE `doctors` (
  `doctor_id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `area` varchar(100) DEFAULT NULL,
  `contact` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `location_id` int(11) DEFAULT NULL,
  `availability` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctors`
--

INSERT INTO `doctors` (`doctor_id`, `name`, `specialization`, `area`, `contact`, `address`, `latitude`, `longitude`, `location_id`, `availability`) VALUES
(1, 'Dr. Amina Rahman', 'General Medicine', 'Dhaka', '01711223344', NULL, NULL, NULL, NULL, 'Available'),
(2, 'Dr. Karim Hossain', 'Immunology', 'Chittagong', '01811224455', NULL, NULL, NULL, NULL, 'Available'),
(3, 'Dr. Nusrat Jahan', 'Parasitology', 'Sylhet', '01911335566', NULL, NULL, NULL, NULL, 'Available'),
(4, 'Dr. Shakil Ahmed', 'Surgery', 'Khulna', '01711446677', NULL, NULL, NULL, NULL, 'Busy'),
(5, 'Dr. Mita Sultana', 'Dentistry', 'Barisal', '01811557788', NULL, NULL, NULL, NULL, 'Available'),
(6, 'Dr. Asif Mahmud', 'Radiology', 'Dhaka', '01911668899', NULL, NULL, NULL, NULL, 'Available'),
(7, 'Dr. Rafiq Islam', 'Ultrasound', 'Comilla', '01711779900', NULL, NULL, NULL, NULL, 'On Leave'),
(8, 'Dr. Tahmina Akter', 'Surgery', 'Narayanganj', '01811880011', NULL, NULL, NULL, NULL, 'Available'),
(9, 'Dr. Farhana Huda', 'Emergency Medicine', 'Rajshahi', '01911991122', NULL, NULL, NULL, NULL, 'Busy'),
(10, 'Dr. Mahfuzur Rahman', 'Grooming', 'Mymensingh', '01712002233', NULL, NULL, NULL, NULL, 'Available'),
(11, 'Dr. Nadia Chowdhury', 'Pathology', 'Dhaka', '01812113344', NULL, NULL, NULL, NULL, 'Available'),
(12, 'Dr. Rakib Hasan', 'Microchipping', 'Chittagong', '01912224455', NULL, NULL, NULL, NULL, 'Available'),
(13, 'Dr. Sharmeen Nahar', 'Boarding & Care', 'Khulna', '01712335566', NULL, NULL, NULL, NULL, 'Available'),
(14, 'Dr. Towhid Alam', 'Nutrition', 'Barisal', '01812446677', NULL, NULL, NULL, NULL, 'Busy'),
(15, 'Dr. Nigar Sultana', 'Behavioral Therapy', 'Sylhet', '01912557788', NULL, NULL, NULL, NULL, 'Available'),
(16, 'Dr. Hasanuzzaman', 'Surgery', 'Rangpur', '01712668899', NULL, NULL, NULL, NULL, 'On Leave'),
(17, 'Dr. Moumita Das', 'Ultrasound', 'Dhaka', '01812779900', NULL, NULL, NULL, NULL, 'Available'),
(18, 'Dr. Tanvir Hossain', 'Radiology', 'Rajshahi', '01912880011', NULL, NULL, NULL, NULL, 'Available'),
(19, 'Dr. Lamia Haque', 'Dentistry', 'Comilla', '01712991122', NULL, NULL, NULL, NULL, 'Busy'),
(20, 'Dr. Jahidul Islam', 'Parasitology', 'Mymensingh', '01813002233', NULL, NULL, NULL, NULL, 'Available');

-- --------------------------------------------------------

--
-- Table structure for table `inquiries`
--

CREATE TABLE `inquiries` (
  `inquiry_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `status` enum('Pending','Replied','Closed') DEFAULT 'Pending',
  `timestamp` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inquiries`
--

INSERT INTO `inquiries` (`inquiry_id`, `user_id`, `subject`, `message`, `status`, `timestamp`) VALUES
(1, 10, 'Adoption Process', 'Can you guide me through the pet adoption process?', 'Pending', '2025-07-25 10:15:00'),
(2, 21, 'Vaccination Info', 'What vaccinations are needed before adopting a cat?', 'Replied', '2025-07-25 11:20:00'),
(3, 9, 'Available Pets', 'Are there any puppies available for adoption?', 'Closed', '2025-07-26 09:45:00'),
(4, 8, 'Pet Health Record', 'Can I get the health report of the pet I’m adopting?', 'Replied', '2025-07-27 14:30:00'),
(5, 25, 'Volunteer Opportunities', 'How can I volunteer at your animal shelter?', 'Pending', '2025-07-27 16:10:00'),
(6, 20, 'Follow-up on Inquiry', 'I didn’t receive a response to my last inquiry.', 'Pending', '2025-07-28 08:25:00'),
(7, 19, 'Pet Behavior Issue', 'My adopted dog is showing aggression. What should I do?', 'Replied', '2025-07-28 13:55:00'),
(8, 17, 'Pet Food Recommendations', 'What food brand do you suggest for senior dogs?', 'Closed', '2025-07-29 17:40:00'),
(9, 20, 'Shelter Visit', 'Can I visit the shelter without an appointment?', 'Pending', '2025-07-29 19:00:00'),
(10, 19, 'Lost Pet Report', 'I lost my adopted cat. How do I report it?', 'Replied', '2025-07-30 07:50:00');

-- --------------------------------------------------------

--
-- Table structure for table `locations`
--

CREATE TABLE `locations` (
  `location_id` int(11) NOT NULL,
  `city` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `locations`
--

INSERT INTO `locations` (`location_id`, `city`) VALUES
(1, 'Gulshan'),
(2, 'Banani'),
(3, 'Dhanmondi'),
(4, 'Uttara'),
(5, 'Mirpur'),
(6, 'Mohammadpur'),
(7, 'Badda'),
(8, 'Tejgaon'),
(9, 'Shahbagh'),
(10, 'Motijheel');

-- --------------------------------------------------------

--
-- Table structure for table `medicalrecords`
--

CREATE TABLE `medicalrecords` (
  `record_id` int(11) NOT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `diagnosis` text DEFAULT NULL,
  `treatment` text DEFAULT NULL,
  `date` date DEFAULT NULL,
  `bills` double(8,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medicalrecords`
--

INSERT INTO `medicalrecords` (`record_id`, `patient_id`, `doctor_id`, `diagnosis`, `treatment`, `date`, `bills`) VALUES
(1, 16, 8, 'Pet identification request', 'Implanted microchip successfully.', '2025-05-19', 3765.49),
(2, 20, 2, 'Boarding for travel', 'Checked in with dietary instructions.', '2024-08-06', 3395.48),
(3, 22, 14, 'Aggressive behavior', 'Scheduled behavioral training sessions.', '2025-01-22', 3745.71),
(4, 25, 5, 'Minor surgical wound', 'Cleaned and sutured the area.', '2025-04-30', 1903.68),
(5, 9, 13, 'Routine health checkup', 'Prescribed vitamins and routine monitoring.', '2025-02-21', 1235.17),
(6, 29, 14, 'Minor surgical wound', 'Cleaned and sutured the area.', '2024-12-24', 2179.83),
(7, 12, 6, 'Boarding for travel', 'Checked in with dietary instructions.', '2025-01-12', 3093.33),
(8, 15, 5, 'Spay/Neuter needed', 'Scheduled for sterilization surgery.', '2024-09-03', 1347.61),
(9, 30, 1, 'Vaccination required', 'Administered core vaccines.', '2024-09-12', 1321.01),
(10, 13, 3, 'Pet identification request', 'Implanted microchip successfully.', '2025-06-14', 4950.60),
(11, 25, 8, 'Abnormal lab results', 'Further blood tests recommended.', '2024-10-10', 1301.25),
(12, 8, 13, 'Intestinal parasites detected', 'Deworming medication administered.', '2024-10-12', 2466.49),
(13, 15, 4, 'Intestinal parasites detected', 'Deworming medication administered.', '2025-05-31', 4555.88),
(14, 18, 3, 'Boarding for travel', 'Checked in with dietary instructions.', '2025-04-02', 1233.57),
(15, 23, 10, 'Minor surgical wound', 'Cleaned and sutured the area.', '2024-08-06', 4484.46),
(16, 21, 8, 'Aggressive behavior', 'Scheduled behavioral training sessions.', '2024-08-20', 3393.04),
(17, 3, 1, 'Boarding for travel', 'Checked in with dietary instructions.', '2025-02-14', 988.02),
(18, 14, 7, 'Abnormal lab results', 'Further blood tests recommended.', '2024-12-29', 2149.78),
(19, 2, 7, 'Overgrown nails and matted fur', 'Performed grooming and hygiene treatment.', '2025-03-28', 4028.63),
(20, 26, 10, 'Minor surgical wound', 'Cleaned and sutured the area.', '2025-05-23', 3488.52),
(21, 27, 10, 'Suspected fracture', 'Conducted X-Ray and immobilized limb.', '2025-06-01', 3520.66),
(22, 8, 6, 'Pet identification request', 'Implanted microchip successfully.', '2025-02-20', 3848.90),
(23, 19, 2, 'Minor surgical wound', 'Cleaned and sutured the area.', '2025-02-26', 1632.50),
(24, 24, 8, 'Pet identification request', 'Implanted microchip successfully.', '2025-02-22', 2616.23),
(25, 19, 11, 'Emergency injury', 'Stabilized and monitored vitals.', '2024-10-15', 1703.09),
(26, 24, 11, 'Overgrown nails and matted fur', 'Performed grooming and hygiene treatment.', '2025-04-09', 2583.67),
(27, 7, 4, 'Vaccination required', 'Administered core vaccines.', '2025-06-12', 832.17),
(28, 3, 7, 'Abdominal issue', 'Performed ultrasound to assess organs.', '2025-04-17', 1146.81),
(29, 11, 13, 'Spay/Neuter needed', 'Scheduled for sterilization surgery.', '2024-11-17', 1290.80),
(30, 22, 13, 'Vaccination required', 'Administered core vaccines.', '2025-07-01', 2701.42),
(31, 17, 13, 'Abnormal lab results', 'Further blood tests recommended.', '2024-08-25', 953.65),
(32, 1, 15, 'Abnormal lab results', 'Further blood tests recommended.', '2024-12-13', 3506.43),
(33, 28, 4, 'Routine health checkup', 'Prescribed vitamins and routine monitoring.', '2025-04-02', 1529.29),
(34, 6, 5, 'Spay/Neuter needed', 'Scheduled for sterilization surgery.', '2024-11-13', 3037.84),
(35, 8, 12, 'Dental plaque buildup', 'Performed full dental cleaning.', '2025-03-27', 2381.82),
(36, 26, 8, 'Boarding for travel', 'Checked in with dietary instructions.', '2025-06-14', 4806.71),
(37, 30, 4, 'Vaccination required', 'Administered core vaccines.', '2025-02-05', 2354.35),
(38, 8, 11, 'Suspected fracture', 'Conducted X-Ray and immobilized limb.', '2025-04-14', 1487.76),
(39, 28, 7, 'Pet identification request', 'Implanted microchip successfully.', '2025-04-04', 4872.40),
(40, 4, 5, 'Emergency injury', 'Stabilized and monitored vitals.', '2025-02-06', 3543.79),
(41, 1, 7, 'Intestinal parasites detected', 'Deworming medication administered.', '2025-02-11', 1430.78),
(42, 17, 6, 'Abdominal issue', 'Performed ultrasound to assess organs.', '2025-05-23', 3929.86),
(43, 25, 6, 'Emergency injury', 'Stabilized and monitored vitals.', '2025-05-05', 4689.11),
(44, 1, 15, 'Aggressive behavior', 'Scheduled behavioral training sessions.', '2024-09-25', 2236.65),
(45, 2, 3, 'Weight issues', 'Diet and exercise plan created.', '2025-04-02', 4781.02),
(46, 16, 4, 'Vaccination required', 'Administered core vaccines.', '2025-03-14', 3860.21),
(47, 24, 10, 'Abnormal lab results', 'Further blood tests recommended.', '2025-02-24', 4599.10),
(48, 11, 6, 'Emergency injury', 'Stabilized and monitored vitals.', '2025-07-24', 1807.68),
(49, 29, 1, 'Weight issues', 'Diet and exercise plan created.', '2025-04-04', 3847.89),
(50, 3, 10, 'Overgrown nails and matted fur', 'Performed grooming and hygiene treatment.', '2025-02-22', 919.60);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `timestamp` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `patient_id` int(11) NOT NULL,
  `owner_id` int(11) DEFAULT NULL,
  `animal_name` varchar(100) DEFAULT NULL,
  `species` varchar(50) DEFAULT NULL,
  `breed` varchar(100) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `gender` enum('Male','Female') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`patient_id`, `owner_id`, `animal_name`, `species`, `breed`, `age`, `gender`) VALUES
(1, 1, 'Buddy', 'Dog', 'Labrador', 3, 'Male'),
(2, 2, 'Mittens', 'Cat', 'Persian', 2, 'Female'),
(3, 3, 'Charlie', 'Dog', 'Beagle', 4, 'Male'),
(4, 4, 'Bella', 'Dog', 'Poodle', 5, 'Female'),
(5, 5, 'Luna', 'Cat', 'Siamese', 3, 'Female'),
(6, 6, 'Max', 'Dog', 'Bulldog', 6, 'Male'),
(7, 7, 'Rocky', 'Dog', 'German Shepherd', 2, 'Male'),
(8, 8, 'Daisy', 'Cat', 'Ragdoll', 1, 'Female'),
(9, 9, 'Coco', 'Dog', 'Chihuahua', 4, 'Female'),
(10, 10, 'Shadow', 'Cat', 'Maine Coon', 7, 'Male'),
(11, 11, 'Milo', 'Dog', 'Golden Retriever', 3, 'Male'),
(12, 12, 'Nala', 'Cat', 'Bengal', 2, 'Female'),
(13, 13, 'Oscar', 'Dog', 'Boxer', 5, 'Male'),
(14, 14, 'Chloe', 'Cat', 'Sphynx', 3, 'Female'),
(15, 15, 'Jack', 'Dog', 'Husky', 6, 'Male'),
(16, 16, 'Lily', 'Cat', 'Scottish Fold', 4, 'Female'),
(17, 17, 'Teddy', 'Dog', 'Cocker Spaniel', 3, 'Male'),
(18, 18, 'Cleo', 'Cat', 'British Shorthair', 2, 'Female'),
(19, 19, 'Buster', 'Dog', 'Dachshund', 5, 'Male'),
(20, 20, 'Sophie', 'Cat', 'Abyssinian', 4, 'Female'),
(21, 21, 'Zeus', 'Dog', 'Doberman', 6, 'Male'),
(22, 22, 'Mochi', 'Cat', 'Oriental', 3, 'Female'),
(23, 23, 'Rex', 'Dog', 'Great Dane', 5, 'Male'),
(24, 24, 'Pumpkin', 'Cat', 'Turkish Van', 2, 'Female'),
(25, 25, 'Bailey', 'Dog', 'Pomeranian', 4, 'Female'),
(26, 26, 'Leo', 'Cat', 'Chartreux', 3, 'Male'),
(27, 27, 'Zoe', 'Dog', 'Shih Tzu', 2, 'Female'),
(28, 28, 'Simba', 'Cat', 'Himalayan', 6, 'Male'),
(29, 29, 'Duke', 'Dog', 'Akita', 5, 'Male'),
(30, 30, 'Peach', 'Cat', 'Balinese', 3, 'Female');

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `service_id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`service_id`, `name`, `description`, `price`) VALUES
(1, 'General Checkup', 'Routine health examination for pets.', 500.00),
(2, 'Vaccination', 'Immunization against common pet diseases.', 700.00),
(3, 'Deworming', 'Treatment to eliminate internal parasites.', 400.00),
(4, 'Spaying/Neutering', 'Surgical sterilization of pets.', 2500.00),
(5, 'Dental Cleaning', 'Professional cleaning of pet teeth.', 1200.00),
(6, 'X-Ray', 'Radiographic examination for diagnosis.', 1500.00),
(7, 'Ultrasound', 'Ultrasonography for internal organs.', 1800.00),
(8, 'Surgery', 'Minor or major surgical procedures.', 5000.00),
(9, 'Emergency Care', 'Immediate treatment for critical cases.', 3000.00),
(10, 'Grooming', 'Bathing, nail trimming, and fur care.', 800.00),
(11, 'Lab Testing', 'Blood, urine, and fecal sample analysis.', 1000.00),
(12, 'Microchipping', 'Implanting a chip for pet identification.', 900.00),
(13, 'Pet Boarding', 'Short-term stay with medical supervision.', 2000.00),
(14, 'Nutritional Consultation', 'Diet plan and nutrition advice.', 600.00),
(15, 'Behavioral Therapy', 'Training and behavioral correction.', 1100.00);

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `staff_id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `role` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`staff_id`, `name`, `role`, `email`, `password`) VALUES
(1, 'Alice Rahman', 'manager', 'alice.rahman@gmail.com', 'password123'),
(2, 'Bilal Hossain', 'Assistant', 'bilal.hossain@gmail.com', 'pass456'),
(3, 'Chen Lee', 'Receptionist', 'chen.lee@gmail.com', 'admin789'),
(4, 'Deborah Sultana', 'Groomer', 'deborah.sultana@gmail.com', 'groomer321'),
(5, 'Elias Kabir', 'Cleaner', 'elias.kabir@gmail.com', 'cleanme!123');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `name`, `email`, `phone`, `password`, `address`) VALUES
(1, 'Arif Hossain', 'arif1@email.com', '01711000001', 'pass123', 'Dhaka'),
(2, 'Tania Rahman', 'tania2@email.com', '01711000002', 'pass123', 'Chittagong'),
(3, 'Sajib Ahmed', 'sajib3@email.com', '01711000003', 'pass123', 'Sylhet'),
(4, 'Nusrat Jahan', 'nusrat4@email.com', '01711000004', 'pass123', 'Barisal'),
(5, 'Rashed Karim', 'rashed5@email.com', '01711000005', 'pass123', 'Rajshahi'),
(6, 'Rima Akter', 'rima6@email.com', '01711000006', 'pass123', 'Khulna'),
(7, 'Imran Hossain', 'imran7@email.com', '01711000007', 'pass123', 'Narayanganj'),
(8, 'Sadia Binte', 'sadia8@email.com', '01711000008', 'pass123', 'Comilla'),
(9, 'Ovi Rahman', 'ovi9@email.com', '01711000009', 'pass123', 'Mymensingh'),
(10, 'Jannat Nahar', 'jannat10@email.com', '01711000010', 'pass123', 'Gazipur'),
(11, 'Sabbir Hossain', 'sabbir11@email.com', '01711000011', 'pass123', 'Tangail'),
(12, 'Maliha Sultana', 'maliha12@email.com', '01711000012', 'pass123', 'Dhaka'),
(13, 'Bashir Uddin', 'bashir13@email.com', '01711000013', 'pass123', 'Sylhet'),
(14, 'Khadija Amin', 'khadija14@email.com', '01711000014', 'pass123', 'Cox\'s Bazar'),
(15, 'Farid Islam', 'farid15@email.com', '01711000015', 'pass123', 'Khulna'),
(16, 'Tuhin Ahmed', 'tuhin16@email.com', '01711000016', 'pass123', 'Jessore'),
(17, 'Lamia Islam', 'lamia17@email.com', '01711000017', 'pass123', 'Barisal'),
(18, 'Shafin Reza', 'shafin18@email.com', '01711000018', 'pass123', 'Rangpur'),
(19, 'Tanvir Chowdhury', 'tanvir19@email.com', '01711000019', 'pass123', 'Feni'),
(20, 'Fariha Noor', 'fariha20@email.com', '01711000020', 'pass123', 'Dhaka'),
(21, 'Mizanur Rahman', 'mizan21@email.com', '01711000021', 'pass123', 'Chandpur'),
(22, 'Sajeda Akter', 'sajeda22@email.com', '01711000022', 'pass123', 'Noakhali'),
(23, 'Riyad Islam', 'riyad23@email.com', '01711000023', 'pass123', 'Narsingdi'),
(24, 'Fahmida Khatun', 'fahmida24@email.com', '01711000024', 'pass123', 'Bogra'),
(25, 'Hasibul Hasan', 'hasib25@email.com', '01711000025', 'pass123', 'Jamalpur'),
(26, 'Monira Jahan', 'monira26@email.com', '01711000026', 'pass123', 'Sirajganj'),
(27, 'Samiul Huda', 'samiul27@email.com', '01711000027', 'pass123', 'Kushtia'),
(28, 'Shaila Rafi', 'shaila28@email.com', '01711000028', 'pass123', 'Bhola'),
(29, 'Azizur Rahman', 'aziz29@email.com', '01711000029', 'pass123', 'Dinajpur'),
(30, 'Mahinur Akter', 'mahinur30@email.com', '01711000030', 'pass123', 'Pabna');

-- --------------------------------------------------------

--
-- Table structure for table `vaccinationrecords`
--

CREATE TABLE `vaccinationrecords` (
  `vaccine_id` int(11) NOT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `vaccine_name` varchar(100) DEFAULT NULL,
  `date_given` date DEFAULT NULL,
  `next_due_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `price` double(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vaccinationrecords`
--

INSERT INTO `vaccinationrecords` (`vaccine_id`, `patient_id`, `vaccine_name`, `date_given`, `next_due_date`, `notes`, `price`) VALUES
(1, 21, 'Rabies', '2025-07-01', '2026-07-01', 'First rabies shot', 500.00),
(2, 19, 'Distemper', '2025-07-03', '2025-10-03', 'Puppy booster', 700.00),
(3, 13, 'Parvovirus', '2025-07-05', '2026-07-05', 'Annual parvo vaccine', 800.00),
(4, 7, 'Hepatitis', '2025-07-07', '2026-07-07', 'Given with core vaccines', 600.00),
(5, 9, 'Leptospirosis', '2025-07-08', '2026-01-08', 'Mild reaction noted', 750.00),
(6, 11, 'Bordetella', '2025-07-10', '2026-07-10', 'Kennel cough prevention', 450.00),
(7, 3, 'Canine Influenza', '2025-07-12', '2026-07-12', 'Required for boarding', 950.00),
(8, 17, 'Feline Leukemia', '2025-07-13', '2026-07-13', 'Outdoor cat – yearly dose', 990.00),
(9, 20, 'FVRCP', '2025-07-14', '2026-07-14', 'Combo vaccine for cats', 880.00),
(10, 19, 'Rabies', '2025-07-15', '2026-07-15', 'Booster dose', 500.00);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `adoptionlistings`
--
ALTER TABLE `adoptionlistings`
  ADD PRIMARY KEY (`listing_id`),
  ADD KEY `posted_by` (`posted_by`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Indexes for table `adoptionrequests`
--
ALTER TABLE `adoptionrequests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `listing_id` (`listing_id`),
  ADD KEY `requested_by` (`requested_by`);

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`appointment_id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `appointmentservices`
--
ALTER TABLE `appointmentservices`
  ADD PRIMARY KEY (`appointment_id`,`service_id`),
  ADD KEY `service_id` (`service_id`);

--
-- Indexes for table `articles`
--
ALTER TABLE `articles`
  ADD PRIMARY KEY (`article_id`),
  ADD KEY `author_id` (`author_id`);

--
-- Indexes for table `doctors`
--
ALTER TABLE `doctors`
  ADD PRIMARY KEY (`doctor_id`);

--
-- Indexes for table `inquiries`
--
ALTER TABLE `inquiries`
  ADD PRIMARY KEY (`inquiry_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`location_id`);

--
-- Indexes for table `medicalrecords`
--
ALTER TABLE `medicalrecords`
  ADD PRIMARY KEY (`record_id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `doctor_id` (`doctor_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`patient_id`),
  ADD KEY `owner_id` (`owner_id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`service_id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`staff_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `vaccinationrecords`
--
ALTER TABLE `vaccinationrecords`
  ADD PRIMARY KEY (`vaccine_id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `adoptionlistings`
--
ALTER TABLE `adoptionlistings`
  MODIFY `listing_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `adoptionrequests`
--
ALTER TABLE `adoptionrequests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `appointment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `articles`
--
ALTER TABLE `articles`
  MODIFY `article_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `doctors`
--
ALTER TABLE `doctors`
  MODIFY `doctor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `inquiries`
--
ALTER TABLE `inquiries`
  MODIFY `inquiry_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `locations`
--
ALTER TABLE `locations`
  MODIFY `location_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `medicalrecords`
--
ALTER TABLE `medicalrecords`
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `patient_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `service_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `staff_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `vaccinationrecords`
--
ALTER TABLE `vaccinationrecords`
  MODIFY `vaccine_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `adoptionlistings`
--
ALTER TABLE `adoptionlistings`
  ADD CONSTRAINT `adoptionlistings_ibfk_1` FOREIGN KEY (`posted_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `adoptionlistings_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `admin` (`admin_id`);

--
-- Constraints for table `adoptionrequests`
--
ALTER TABLE `adoptionrequests`
  ADD CONSTRAINT `adoptionrequests_ibfk_1` FOREIGN KEY (`listing_id`) REFERENCES `adoptionlistings` (`listing_id`),
  ADD CONSTRAINT `adoptionrequests_ibfk_2` FOREIGN KEY (`requested_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`),
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`);

--
-- Constraints for table `appointmentservices`
--
ALTER TABLE `appointmentservices`
  ADD CONSTRAINT `appointmentservices_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`appointment_id`),
  ADD CONSTRAINT `appointmentservices_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`);

--
-- Constraints for table `articles`
--
ALTER TABLE `articles`
  ADD CONSTRAINT `articles_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `inquiries`
--
ALTER TABLE `inquiries`
  ADD CONSTRAINT `inquiries_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `medicalrecords`
--
ALTER TABLE `medicalrecords`
  ADD CONSTRAINT `medicalrecords_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`),
  ADD CONSTRAINT `medicalrecords_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `patients`
--
ALTER TABLE `patients`
  ADD CONSTRAINT `patients_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `vaccinationrecords`
--
ALTER TABLE `vaccinationrecords`
  ADD CONSTRAINT `vaccinationrecords_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
