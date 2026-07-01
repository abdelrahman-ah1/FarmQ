-- FarmQ SQLite schema (local dev fallback)

CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    full_name TEXT NOT NULL,
    role TEXT NOT NULL DEFAULT 'operator' CHECK(role IN ('operator', 'agronomist', 'owner', 'admin')),
    locale TEXT NOT NULL DEFAULT 'ar' CHECK(locale IN ('en', 'ar')),
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS farms (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    owner_user_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    region TEXT NOT NULL CHECK(region IN ('delta', 'upper_egypt', 'reclaimed_desert')),
    governorate TEXT NULL,
    polygon_geojson TEXT NULL,
    tier TEXT NOT NULL DEFAULT 'free' CHECK(tier IN ('free', 'paid')),
    tier_expires_at TEXT NULL,
    selected_crop_code TEXT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS soil_samples (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    farm_id INTEGER NOT NULL,
    sample_date TEXT NOT NULL,
    npk_n REAL NULL,
    npk_p REAL NULL,
    npk_k REAL NULL,
    ph REAL NULL,
    salinity_ec REAL NULL,
    source_csv_filename TEXT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (farm_id) REFERENCES farms(id)
);

CREATE TABLE IF NOT EXISTS crop_baselines (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    crop_code TEXT NOT NULL UNIQUE,
    name_en TEXT NOT NULL,
    name_ar TEXT NOT NULL,
    region_tags TEXT NOT NULL,
    npk_targets TEXT NOT NULL,
    arc_reference_note TEXT NULL,
    micronutrient_notes TEXT NULL
);

CREATE TABLE IF NOT EXISTS fertilization_plans (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    farm_id INTEGER NOT NULL,
    crop_code TEXT NOT NULL,
    soil_sample_id INTEGER NOT NULL,
    plan_json TEXT NOT NULL,
    tier_scope TEXT NOT NULL DEFAULT 'soil_only' CHECK(tier_scope IN ('soil_only', 'full')),
    generated_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (farm_id) REFERENCES farms(id),
    FOREIGN KEY (soil_sample_id) REFERENCES soil_samples(id)
);

CREATE TABLE IF NOT EXISTS geospatial_jobs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    farm_id INTEGER NOT NULL,
    job_type TEXT NOT NULL CHECK(job_type IN ('sentinel_fetch', 'ndvi_process', 'deficiency_map')),
    status TEXT NOT NULL DEFAULT 'queued' CHECK(status IN ('queued', 'processing', 'completed', 'failed')),
    payload TEXT NULL,
    result_json TEXT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    completed_at TEXT NULL,
    FOREIGN KEY (farm_id) REFERENCES farms(id)
);

CREATE TABLE IF NOT EXISTS irrigation_schedules (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    farm_id INTEGER NOT NULL,
    crop_code TEXT NULL,
    week_start TEXT NOT NULL,
    schedule_json TEXT NOT NULL,
    generated_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (farm_id) REFERENCES farms(id)
);

CREATE TABLE IF NOT EXISTS farm_access (
    farm_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    access_role TEXT NOT NULL DEFAULT 'consultant',
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (farm_id, user_id),
    FOREIGN KEY (farm_id) REFERENCES farms(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS billing_transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    farm_id INTEGER NULL,
    amount_egp REAL NOT NULL,
    payment_rail TEXT NOT NULL CHECK(payment_rail IN ('fawry', 'vodafone_cash', 'meeza', 'instapay', 'card')),
    gateway_reference TEXT NULL,
    status TEXT NOT NULL DEFAULT 'pending' CHECK(status IN ('pending', 'paid', 'failed', 'refunded')),
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (farm_id) REFERENCES farms(id)
);

INSERT OR IGNORE INTO crop_baselines (crop_code, name_en, name_ar, region_tags, npk_targets, arc_reference_note) VALUES
('cotton', 'Cotton', 'قطن', '["delta"]', '{"n": 120, "p": 60, "k": 80}', 'Delta alluvial baseline'),
('wheat', 'Wheat', 'قمح', '["delta", "upper_egypt"]', '{"n": 140, "p": 70, "k": 60}', 'Winter crop — shatawi season'),
('rice', 'Rice', 'أرز', '["delta"]', '{"n": 100, "p": 50, "k": 40}', 'Northern Delta salinity-aware'),
('maize', 'Maize / Corn', 'ذرة', '["delta", "upper_egypt"]', '{"n": 160, "p": 80, "k": 70}', 'Summer crop — seifi season'),
('sugarcane', 'Sugarcane', 'قصب سكر', '["upper_egypt"]', '{"n": 200, "p": 90, "k": 120}', 'Canal-rotation scheduling'),
('citrus', 'Citrus', 'حمضيات', '["reclaimed_desert"]', '{"n": 180, "p": 70, "k": 150}', 'Sandy soil fertigation'),
('grapes', 'Grapes', 'عنب', '["reclaimed_desert"]', '{"n": 90, "p": 40, "k": 120}', 'Export horticulture'),
('strawberries', 'Strawberries', 'فراولة', '["reclaimed_desert"]', '{"n": 110, "p": 50, "k": 140}', 'Precision irrigation dependent');
