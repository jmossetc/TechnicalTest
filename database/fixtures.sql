-- ─────────────────────────────────────────────────────────────────────────────
--  Fixture data
--
--  Passwords are all bcrypt hashes of "password123".
--  UUIDs are inserted via UUID_TO_BIN() for BINARY(16) storage.
-- ─────────────────────────────────────────────────────────────────────────────

-- ── Users ────────────────────────────────────────────────────────────────────

INSERT INTO users (id, email, password_hash) VALUES
    (UUID_TO_BIN('a1b2c3d4-e5f6-4a7b-8c9d-0e1f2a3b4c5d'), 'admin@example.com',           '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
    (UUID_TO_BIN('b2c3d4e5-f6a7-4b8c-9d0e-1f2a3b4c5d6e'), 'company.manager@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
    (UUID_TO_BIN('c3d4e5f6-a7b8-4c9d-0e1f-2a3b4c5d6e7f'), 'shop.manager@example.com',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
    (UUID_TO_BIN('d4e5f6a7-b8c9-4d0e-1f2a-3b4c5d6e7f80'), 'user@example.com',            '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
    (UUID_TO_BIN('e5f6a7b8-c9d0-4e1f-2a3b-4c5d6e7f8091'), 'deleted@example.com',         '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Soft-delete one user
UPDATE users SET deleted_at = '2025-01-15 10:00:00'
WHERE id = UUID_TO_BIN('e5f6a7b8-c9d0-4e1f-2a3b-4c5d6e7f8091');

-- ── Companies ────────────────────────────────────────────────────────────────

INSERT INTO companies (id, name) VALUES
    (UUID_TO_BIN('11111111-1111-4111-8111-111111111111'), 'Acme Corporation'),
    (UUID_TO_BIN('22222222-2222-4222-8222-222222222222'), 'Globex Industries'),
    (UUID_TO_BIN('33333333-3333-4333-8333-333333333333'), 'Initech Ltd');

-- Soft-delete one company
UPDATE companies SET deleted_at = '2025-03-01 08:30:00'
WHERE id = UUID_TO_BIN('33333333-3333-4333-8333-333333333333');

-- ── Shops ────────────────────────────────────────────────────────────────────

INSERT INTO shops (id, company_id, name, street, city, zip, country) VALUES
    (UUID_TO_BIN('aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa'), UUID_TO_BIN('11111111-1111-4111-8111-111111111111'), 'Acme Downtown',    '123 Main Street',    'New York',  '10001', 'US'),
    (UUID_TO_BIN('aaaa2222-aaaa-4aaa-8aaa-aaaaaaaaaaaa'), UUID_TO_BIN('11111111-1111-4111-8111-111111111111'), 'Acme Uptown',      '456 Park Avenue',    'New York',  '10022', 'US'),
    (UUID_TO_BIN('bbbb1111-bbbb-4bbb-8bbb-bbbbbbbbbbbb'), UUID_TO_BIN('22222222-2222-4222-8222-222222222222'), 'Globex Berlin',    '10 Friedrichstraße', 'Berlin',    '10117', 'DE'),
    (UUID_TO_BIN('bbbb2222-bbbb-4bbb-8bbb-bbbbbbbbbbbb'), UUID_TO_BIN('22222222-2222-4222-8222-222222222222'), 'Globex Munich',    '5 Marienplatz',      'Munich',    '80331', 'DE'),
    (UUID_TO_BIN('cccc1111-cccc-4ccc-8ccc-cccccccccccc'), UUID_TO_BIN('33333333-3333-4333-8333-333333333333'), 'Initech HQ',       '1 Office Park',      'Austin',    '73301', 'US');

-- Soft-delete one shop
UPDATE shops SET deleted_at = '2025-03-01 08:30:00'
WHERE id = UUID_TO_BIN('cccc1111-cccc-4ccc-8ccc-cccccccccccc');

-- ── Role assignments ─────────────────────────────────────────────────────────

-- admin@example.com is a global admin
INSERT INTO user_admin_roles (user_id) VALUES
    (UUID_TO_BIN('a1b2c3d4-e5f6-4a7b-8c9d-0e1f2a3b4c5d'));

-- company.manager@example.com manages Acme Corporation and Globex Industries
INSERT INTO user_company_roles (user_id, company_id) VALUES
    (UUID_TO_BIN('b2c3d4e5-f6a7-4b8c-9d0e-1f2a3b4c5d6e'), UUID_TO_BIN('11111111-1111-4111-8111-111111111111')),
    (UUID_TO_BIN('b2c3d4e5-f6a7-4b8c-9d0e-1f2a3b4c5d6e'), UUID_TO_BIN('22222222-2222-4222-8222-222222222222'));

-- shop.manager@example.com manages Acme Downtown and Globex Berlin
INSERT INTO user_shop_roles (user_id, shop_id) VALUES
    (UUID_TO_BIN('c3d4e5f6-a7b8-4c9d-0e1f-2a3b4c5d6e7f'), UUID_TO_BIN('aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa')),
    (UUID_TO_BIN('c3d4e5f6-a7b8-4c9d-0e1f-2a3b4c5d6e7f'), UUID_TO_BIN('bbbb1111-bbbb-4bbb-8bbb-bbbbbbbbbbbb'));