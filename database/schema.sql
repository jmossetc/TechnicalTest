SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS shops;
DROP TABLE IF EXISTS companies;
SET FOREIGN_KEY_CHECKS = 1;

-- ─────────────────────────────────────────────────────────────────────────────
--  Companies
-- ─────────────────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS companies
(
    id             BINARY(16)   NOT NULL,
    name           VARCHAR(255) NOT NULL,
    email          VARCHAR(255) NULL UNIQUE,
    phone_number   VARCHAR(20)  NULL,
    website        VARCHAR(255) NULL,
    address_line_1 VARCHAR(255) NULL,
    address_line_2 VARCHAR(255) NULL,
    city           VARCHAR(100) NULL,
    postal_code    VARCHAR(20)  NULL,
    country        VARCHAR(100) NULL,
    is_active      BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at     TIMESTAMP    NULL     DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY companies_name_idx (name),
    KEY idx_companies_city (city),
    KEY idx_companies_country (country),
    KEY idx_companies_created_at (created_at),
    KEY idx_companies_updated_at (updated_at),
    KEY idx_companies_deleted_at (deleted_at)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────────────────────
--  Shops
-- ─────────────────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS shops
(
    id             BINARY(16)   NOT NULL,
    company_id     BINARY(16)   NOT NULL,
    name           VARCHAR(255) NOT NULL,
    email          VARCHAR(255) NULL,
    phone_number   VARCHAR(20)  NULL,
    address_line_1 VARCHAR(255) NULL,
    address_line_2 VARCHAR(255) NULL,
    city           VARCHAR(100) NULL,
    postal_code    VARCHAR(20)  NULL,
    country        VARCHAR(100) NULL,
    latitude       decimal      NULL,
    longitude      decimal      NULL,
    is_digital     BOOLEAN      NOT NULL DEFAULT FALSE,
    is_active      BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at     TIMESTAMP    NULL     DEFAULT NULL,
    PRIMARY KEY (id),
    -- Two shops in the same company may not share a name.
    UNIQUE KEY shops_company_name_idx (company_id, name),
    KEY idx_shops_city (city),
    KEY idx_shops_country (country),
    KEY idx_shops_created_at (created_at),
    KEY idx_shops_is_active  (is_active),
    KEY idx_shops_is_digital (is_digital),
    KEY idx_shops_updated_at (updated_at),
    KEY idx_shops_deleted_at (deleted_at),
    CONSTRAINT fk_shops_company
        FOREIGN KEY (company_id) REFERENCES companies (id)
            ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────────────────────
--  Users
-- ─────────────────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS users
(
    id            BINARY(16)                                                  NOT NULL,
    email         VARCHAR(255)                                                NOT NULL UNIQUE,
    first_name    VARCHAR(255)                                                NOT NULL,
    last_name     VARCHAR(255)                                                NOT NULL,
    phone_number  VARCHAR(20)                                                 NULL,
    role          ENUM ('admin', 'company_admin', 'shop_manager', 'employee') NOT NULL,
    company_id    BINARY(16)                                                  NULL,
    shop_id       BINARY(16)                                                  NULL,
    is_active     BOOLEAN                                                     NOT NULL DEFAULT TRUE,
    last_login_at TIMESTAMP                                                   NULL,
    password_hash VARCHAR(255)                                                NOT NULL,
    created_at    TIMESTAMP                                                   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP                                                   NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at    TIMESTAMP                                                   NULL     DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY users_email_idx (email),
    KEY idx_users_company_id (company_id),
    KEY idx_users_shop_id (shop_id),
    KEY idx_users_role (role),
    KEY idx_users_is_active (is_active),
    KEY idx_users_created_at (created_at),
    KEY idx_users_updated_at (updated_at),
    KEY idx_users_deleted_at (deleted_at),
    CONSTRAINT fk_users_company
        FOREIGN KEY (company_id) REFERENCES companies (id),
    CONSTRAINT fk_users_shop
        FOREIGN KEY (shop_id) REFERENCES shops (id)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;
