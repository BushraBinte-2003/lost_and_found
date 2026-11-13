-- ============================================================
-- Lost & Found Portal - database structure
-- Import this file in phpMyAdmin (Import tab).
-- It creates the database "lost_found_portal" and 3 tables.
-- No admin account or sample reports are inserted here:
-- the first admin is created safely by setup/create_admin.php
-- ============================================================

CREATE DATABASE IF NOT EXISTS lost_found_portal
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE lost_found_portal;

-- ---------- users (normal users AND admins) ----------
CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name     VARCHAR(100) NOT NULL,
    student_id    VARCHAR(30)  NULL,
    email         VARCHAR(150) NOT NULL,
    phone         VARCHAR(20)  NULL,
    login_id      VARCHAR(30)  NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role          ENUM('USER','ADMIN')       NOT NULL DEFAULT 'USER',
    status        ENUM('ACTIVE','SUSPENDED') NOT NULL DEFAULT 'ACTIVE',
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_student_id (student_id),
    UNIQUE KEY uq_users_email (email),
    UNIQUE KEY uq_users_login_id (login_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- reports (lost and found reports) ----------
CREATE TABLE IF NOT EXISTS reports (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id       INT UNSIGNED NOT NULL,
    item_name     VARCHAR(100) NOT NULL,
    report_type   ENUM('LOST','FOUND') NOT NULL,
    category      VARCHAR(50)  NOT NULL,
    description   TEXT         NOT NULL,
    location      VARCHAR(150) NOT NULL,
    event_date    DATE         NOT NULL,          -- date lost OR date found
    image_path    VARCHAR(255) NULL,              -- NULL allowed (lost items may have no photo)
    contact_name  VARCHAR(100) NOT NULL,
    contact_phone VARCHAR(20)  NOT NULL,
    contact_email VARCHAR(150) NULL,
    status        ENUM('PENDING','APPROVED','REJECTED','MATCHED') NOT NULL DEFAULT 'PENDING',
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_reports_status (status),
    KEY idx_reports_type (report_type),
    KEY idx_reports_user (user_id),
    CONSTRAINT fk_reports_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- claims (someone says "this found item is mine") ----------
CREATE TABLE IF NOT EXISTS claims (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    report_id    INT UNSIGNED NOT NULL,
    claimant_id  INT UNSIGNED NOT NULL,
    description  TEXT NOT NULL,                  -- why the item belongs to the claimant
    proof_detail VARCHAR(255) NOT NULL,          -- private identifying detail
    status       ENUM('PENDING','APPROVED','REJECTED') NOT NULL DEFAULT 'PENDING',
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_at  TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_claim_once (report_id, claimant_id),   -- one claim per user per item
    KEY idx_claims_status (status),
    CONSTRAINT fk_claims_report   FOREIGN KEY (report_id)   REFERENCES reports (id) ON DELETE CASCADE,
    CONSTRAINT fk_claims_claimant FOREIGN KEY (claimant_id) REFERENCES users (id)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
