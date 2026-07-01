-- FarmQ MVP schema — Egypt market edition
-- MySQL 8.0+

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    role ENUM('operator', 'agronomist', 'owner', 'admin') NOT NULL DEFAULT 'operator',
    locale ENUM('en', 'ar') NOT NULL DEFAULT 'ar',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS farms (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    owner_user_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    region ENUM('delta', 'upper_egypt', 'reclaimed_desert') NOT NULL,
    governorate VARCHAR(64) NULL,
    polygon_geojson JSON NULL,
    tier ENUM('free', 'paid') NOT NULL DEFAULT 'free',
    tier_expires_at TIMESTAMP NULL,
    selected_crop_code VARCHAR(64) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS soil_samples (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    farm_id BIGINT UNSIGNED NOT NULL,
    sample_date DATE NOT NULL,
    npk_n DECIMAL(8,2) NULL,
    npk_p DECIMAL(8,2) NULL,
    npk_k DECIMAL(8,2) NULL,
    ph DECIMAL(4,2) NULL,
    salinity_ec DECIMAL(6,2) NULL,
    source_csv_filename VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (farm_id) REFERENCES farms(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS crop_baselines (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    crop_code VARCHAR(64) NOT NULL UNIQUE,
    name_en VARCHAR(128) NOT NULL,
    name_ar VARCHAR(128) NOT NULL,
    region_tags JSON NOT NULL,
    npk_targets JSON NOT NULL,
    arc_reference_note VARCHAR(512) NULL,
    micronutrient_notes JSON NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS fertilization_plans (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    farm_id BIGINT UNSIGNED NOT NULL,
    crop_code VARCHAR(64) NOT NULL,
    soil_sample_id BIGINT UNSIGNED NOT NULL,
    plan_json JSON NOT NULL,
    tier_scope ENUM('soil_only', 'full') NOT NULL DEFAULT 'soil_only',
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (farm_id) REFERENCES farms(id),
    FOREIGN KEY (soil_sample_id) REFERENCES soil_samples(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS geospatial_jobs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    farm_id BIGINT UNSIGNED NOT NULL,
    job_type ENUM('sentinel_fetch', 'ndvi_process', 'deficiency_map') NOT NULL,
    status ENUM('queued', 'processing', 'completed', 'failed') NOT NULL DEFAULT 'queued',
    payload JSON NULL,
    result_json JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (farm_id) REFERENCES farms(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS irrigation_schedules (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    farm_id BIGINT UNSIGNED NOT NULL,
    crop_code VARCHAR(64) NULL,
    week_start DATE NOT NULL,
    schedule_json JSON NOT NULL,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (farm_id) REFERENCES farms(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS farm_access (
    farm_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    access_role ENUM('consultant') NOT NULL DEFAULT 'consultant',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (farm_id, user_id),
    FOREIGN KEY (farm_id) REFERENCES farms(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS billing_transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    farm_id BIGINT UNSIGNED NULL,
    amount_egp DECIMAL(10,2) NOT NULL,
    payment_rail ENUM('fawry', 'vodafone_cash', 'meeza', 'instapay', 'card') NOT NULL,
    gateway_reference VARCHAR(255) NULL,
    status ENUM('pending', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (farm_id) REFERENCES farms(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO crop_baselines (crop_code, name_en, name_ar, region_tags, npk_targets, arc_reference_note) VALUES
('cotton', 'Cotton', 'قطن', '["delta"]', '{"n": 120, "p": 60, "k": 80}', 'Delta alluvial baseline'),
('wheat', 'Wheat', 'قمح', '["delta", "upper_egypt"]', '{"n": 140, "p": 70, "k": 60}', 'Winter crop — shatawi season'),
('rice', 'Rice', 'أرز', '["delta"]', '{"n": 100, "p": 50, "k": 40}', 'Northern Delta salinity-aware'),
('maize', 'Maize / Corn', 'ذرة', '["delta", "upper_egypt"]', '{"n": 160, "p": 80, "k": 70}', 'Summer crop — seifi season'),
('sugarcane', 'Sugarcane', 'قصب سكر', '["upper_egypt"]', '{"n": 200, "p": 90, "k": 120}', 'Canal-rotation scheduling'),
('citrus', 'Citrus', 'حمضيات', '["reclaimed_desert"]', '{"n": 180, "p": 70, "k": 150}', 'Sandy soil fertigation'),
('grapes', 'Grapes', 'عنب', '["reclaimed_desert"]', '{"n": 90, "p": 40, "k": 120}', 'Export horticulture'),
('strawberries', 'Strawberries', 'فراولة', '["reclaimed_desert"]', '{"n": 110, "p": 50, "k": 140}', 'Precision irrigation dependent');

SET FOREIGN_KEY_CHECKS = 1;
