-- ─────────────────────────────────────────────────────────────────────────────
--  Users
-- ─────────────────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS users (
    id            BINARY(16)   NOT NULL,
    email         VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at    TIMESTAMP        NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY users_email_idx (email),
    KEY idx_users_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────────────────────
--  Companies
-- ─────────────────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS companies (
    id         BINARY(16)   NOT NULL,
    name       VARCHAR(255) NOT NULL,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP        NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY companies_name_idx (name),
    KEY idx_companies_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────────────────────
--  Shops  (many-to-one with Company)
--
--  A company can own any number of shops.
--  Deleting a company cascades to all its shops.
-- ─────────────────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS shops (
    id         BINARY(16)   NOT NULL,
    company_id BINARY(16)   NOT NULL,
    name       VARCHAR(255) NOT NULL,
    street     VARCHAR(255)     NULL,
    city       VARCHAR(100)     NULL,
    zip        VARCHAR(20)      NULL,
    country    VARCHAR(100)     NULL,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP        NULL DEFAULT NULL,
    PRIMARY KEY (id),
    -- Two shops in the same company may not share a name.
    UNIQUE KEY shops_company_name_idx (company_id, name),
    KEY idx_shops_city (city),
    KEY idx_shops_country (country),
    KEY idx_shops_deleted_at (deleted_at),
    CONSTRAINT fk_shops_company
        FOREIGN KEY (company_id) REFERENCES companies (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────────────────────
--  Role assignments
--
--  Three separate tables model the three role scopes without relying on
--  nullable FK columns, which would introduce NULL-in-UNIQUE-index ambiguity
--  that MySQL cannot enforce cleanly in a single polymorphic table.
--
--  Role            Table                  Scope               Access
--  ─────────────   ─────────────────────  ──────────────────  ───────────────────────────────────────────
--  admin           user_admin_roles       global (no scope)   All companies, shops and users
--  company_manager user_company_roles     per company         Own company record + all its shops
--  shop_manager    user_shop_roles        per shop            Own shop record only
--
--  A user may hold multiple roles simultaneously, e.g.
--    company_manager for company A  AND  shop_manager for shop B.
-- ─────────────────────────────────────────────────────────────────────────────

-- Admin: unrestricted write access to every resource.
-- Primary key enforces at most one admin entry per user.
CREATE TABLE IF NOT EXISTS user_admin_roles (
    user_id    BINARY(16) NOT NULL,
    granted_at TIMESTAMP  NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id),
    CONSTRAINT fk_user_admin_roles_user
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Company Manager: can create / update / delete their own company record
-- and all shops that belong to it.
-- Composite PK prevents granting the same company twice to the same user.
CREATE TABLE IF NOT EXISTS user_company_roles (
    user_id    BINARY(16) NOT NULL,
    company_id BINARY(16) NOT NULL,
    granted_at TIMESTAMP  NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, company_id),
    -- Secondary index so "which users manage company X?" is efficient.
    KEY idx_user_company_roles_company (company_id),
    CONSTRAINT fk_user_company_roles_user
        FOREIGN KEY (user_id)    REFERENCES users     (id) ON DELETE CASCADE,
    CONSTRAINT fk_user_company_roles_company
        FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Shop Manager: can create / update / delete their own shop record only.
-- Composite PK prevents granting the same shop twice to the same user.
CREATE TABLE IF NOT EXISTS user_shop_roles (
    user_id    BINARY(16) NOT NULL,
    shop_id    BINARY(16) NOT NULL,
    granted_at TIMESTAMP  NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, shop_id),
    -- Secondary index so "which users manage shop X?" is efficient.
    KEY idx_user_shop_roles_shop (shop_id),
    CONSTRAINT fk_user_shop_roles_user
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_user_shop_roles_shop
        FOREIGN KEY (shop_id) REFERENCES shops (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
