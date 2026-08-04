-- Resident Profiling Migration
ALTER TABLE residents ADD COLUMN IF NOT EXISTS philsys_pcn VARCHAR(19) NULL AFTER id;
ALTER TABLE residents ADD COLUMN IF NOT EXISTS first_name VARCHAR(100) NULL AFTER philsys_pcn;
ALTER TABLE residents ADD COLUMN IF NOT EXISTS middle_name VARCHAR(100) NULL AFTER first_name;
ALTER TABLE residents ADD COLUMN IF NOT EXISTS last_name VARCHAR(100) NULL AFTER middle_name;
ALTER TABLE residents ADD COLUMN IF NOT EXISTS suffix VARCHAR(10) NULL AFTER last_name;
ALTER TABLE residents ADD COLUMN IF NOT EXISTS birthplace VARCHAR(255) NULL AFTER birth_date;
ALTER TABLE residents ADD COLUMN IF NOT EXISTS citizenship VARCHAR(100) NULL AFTER civil_status;
ALTER TABLE residents ADD COLUMN IF NOT EXISTS religion VARCHAR(100) NULL AFTER citizenship;
ALTER TABLE residents ADD COLUMN IF NOT EXISTS ethnicity VARCHAR(100) NULL AFTER religion;
ALTER TABLE residents ADD COLUMN IF NOT EXISTS household_members INT DEFAULT 1 AFTER household_number;
ALTER TABLE residents ADD COLUMN IF NOT EXISTS house_number VARCHAR(50) NULL AFTER household_members;
ALTER TABLE residents ADD COLUMN IF NOT EXISTS street_name VARCHAR(100) NULL AFTER house_number;
ALTER TABLE residents ADD COLUMN IF NOT EXISTS purok_sitio_id INT NULL AFTER household_members;
ALTER TABLE residents ADD COLUMN IF NOT EXISTS housing_material VARCHAR(50) NULL AFTER purok_sitio_id;
ALTER TABLE residents ADD COLUMN IF NOT EXISTS tenure_status VARCHAR(20) NULL AFTER housing_material;
ALTER TABLE residents ADD COLUMN IF NOT EXISTS drinking_water_source VARCHAR(50) NULL AFTER tenure_status;
ALTER TABLE residents ADD COLUMN IF NOT EXISTS toilet_facility_type VARCHAR(50) NULL AFTER drinking_water_source;
ALTER TABLE residents ADD COLUMN IF NOT EXISTS educational_attainment VARCHAR(50) NULL AFTER education;
ALTER TABLE residents ADD COLUMN IF NOT EXISTS primary_occupation VARCHAR(100) NULL AFTER educational_attainment;
ALTER TABLE residents ADD COLUMN IF NOT EXISTS employment_status VARCHAR(20) NULL AFTER primary_occupation;
ALTER TABLE residents ADD COLUMN IF NOT EXISTS monthly_household_income DECIMAL(12,2) NULL AFTER employment_status;
ALTER TABLE residents ADD COLUMN IF NOT EXISTS is_senior_citizen TINYINT(1) DEFAULT 0 AFTER resident_type;
ALTER TABLE residents ADD COLUMN IF NOT EXISTS is_pwd TINYINT(1) DEFAULT 0 AFTER is_senior_citizen;
ALTER TABLE residents ADD COLUMN IF NOT EXISTS pwd_disability_type VARCHAR(100) NULL AFTER is_pwd;
ALTER TABLE residents ADD COLUMN IF NOT EXISTS is_solo_parent TINYINT(1) DEFAULT 0 AFTER pwd_disability_type;
ALTER TABLE residents ADD COLUMN IF NOT EXISTS is_ofw TINYINT(1) DEFAULT 0 AFTER is_solo_parent;
ALTER TABLE residents ADD COLUMN IF NOT EXISTS is_indigent TINYINT(1) DEFAULT 0 AFTER is_ofw;

