-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 21, 2022 at 03:48 PM
-- Server version: 10.4.21-MariaDB
-- PHP Version: 8.0.12

-- Modifying this
-- utf8mb4_unicode_ci = case-insensitive


SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40101 SET DEFAULT_STORAGE_ENGINE = InnoDB */;
--
-- Database: `astoncv`
--
CREATE DATABASE IF NOT EXISTS `astoncv` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `astoncv`;

-- --------------------------------------------------------

--
-- Table structure for table `cvs`
--

-- Seperating users, cvs, and profiles into their own respective tables
-- One phone number needed per user for now, uniqueness not enforced.

CREATE TABLE IF NOT EXISTS users (
  id bigint(20) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
  name varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  email varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  password varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  phone varchar(25) COLLATE utf8mb4_unicode_ci NOT NULL,
    CONSTRAINT unique_email UNIQUE (email)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci; 

-- Users can have empty profiles when they sign up so profile text not needed, but on signup a profile will be created.

CREATE TABLE IF NOT EXISTS profiles (
    id bigint(20) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    user_id bigint(20) UNSIGNED NOT NULL,
    profile_text TEXT COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    CONSTRAINT unique_uid UNIQUE (user_id),
    CONSTRAINT fk_p_uid FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE
)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS cvs (
    id bigint(20) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    user_id bigint(20) UNSIGNED NOT NULL,
    education_summary TEXT COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    personal_statement TEXT COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    skills TEXT COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    key_programming_language TEXT COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    CONSTRAINT unique_cvs_uid UNIQUE (user_id),
    CONSTRAINT fk_c_uid FOREIGN KEY (user_id) REFERENCES users(id)
                               ON DELETE CASCADE
)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;

-- cv_socials does not need a FK relationship to users, it will have a fk to cvs to enforce a many relation
-- need to cascade in case users delete their current cv and make a new one, same thing fro profiles

CREATE TABLE IF NOT EXISTS cv_socials (
    id bigint(20) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    cv_id bigint(20) UNSIGNED NOT NULL,
    url VARCHAR(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    CONSTRAINT unique_cv_url UNIQUE (cv_id, url),
    CONSTRAINT fk_cv_id FOREIGN KEY (cv_id) REFERENCES cvs(id)
                                      ON DELETE CASCADE

)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;


/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

# INSERT INTO users (name, email, password, phone) VALUES ('Hoffman', 'Hoffman123@gmail.com', 'password123', '07889231223');


COMMIT;
