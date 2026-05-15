<?php

declare(strict_types=1);

/**
 * Generates fixtures.sql for stress-testing the application.
 * Matches the schema in database/schema.sql.
 *
 * Scale:
 *   -       5 admin users
 *   -   1,000 companies  (50 soft-deleted)
 *   -  10,000 shops       (10 per company, 300 soft-deleted)
 *   -   1,900 company admins (2 per active company = 2×950)
 *   -  19,400 shop managers    (2 per active shop    = 2×9700)
 *   -   1,000 employees      (arbitrary, 100 soft-deleted)
 *
 * All passwords: password123
 */

// ── Helpers ───────────────────────────────────────────────────────────────────

function uuid4(): string
{
    $data    = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function esc(string $s): string
{
    return str_replace(["\\", "'"], ["\\\\", "\\'"], $s);
}

function sq(?string $s): string
{
    if ($s === null) {
        return "NULL";
    }
    return "'" . esc($s) . "'";
}

function ubin(string $uuid): string
{
    return "UUID_TO_BIN('{$uuid}')";
}

function insertChunked(string $table, string $columns, array $rows, int $chunkSize = 50): void
{
    foreach (array_chunk($rows, $chunkSize) as $chunk) {
        echo "INSERT INTO {$table} {$columns} VALUES\n  ";
        echo implode(",\n  ", $chunk);
        echo ";\n\n";
    }
}

// ── Reference data ────────────────────────────────────────────────────────────

const PASSWORD_HASH = '$2y$10$hUvNr0XIewZXEXVsRM4.GO6PbUw8DzE4nIctVWMjgp6kZtqo6j/VG';

$companyNames = [
    'Acme Corporation','Globex Inc.','Initech','Umbrella Corp','Soylent Corp',
    'Stark Industries','Wayne Enterprises','Oscorp','LexCorp','Cyberdyne Systems',
    'Weyland-Yutani','Aperture Science','Black Mesa Research','Tyrell Corporation',
    'Massive Dynamic','Abstergo Industries','Veridian Dynamics','Vought International',
    'Pearson Specter','Hooli','Pied Piper','Raviga Capital','Blippitech','Gavin Belson AI',
    'Nucleus Cloud','Endframe','C-Span Industries','CodeMonkey Systems','ByteDance EMEA',
    'QuantumLeap Ltd','NovaTech Solutions','PrimeLabs','ZenithSoft','AlphaGrid',
    'OmegaWave','NexGen Dynamics','FusionCore','SilverBullet Inc.','IronForge Ltd',
    'CopperHead Tech','BronzeAge Digital','TitaniumWorks','PlatinumEdge','GoldRush AI',
    'DiamondCut Corp','EmeraldSky Ltd','SapphireNet','RubyRidge Inc.','TopazCloud',
    'OpalStream',
];

$shopSuffixes = [
    'Flagship Store','North Branch','South Branch','East Branch','West Branch',
    'City Centre','Airport Outlet',
];

$cities = [
    ['street_fmt' => '%d Avenue de l\'Opéra',  'city' => 'Paris',     'zip' => '75001', 'country' => 'France'],
    ['street_fmt' => '%d Regent Street',        'city' => 'London',    'zip' => 'W1B 3BR','country' => 'United Kingdom'],
    ['street_fmt' => '%d Unter den Linden',     'city' => 'Berlin',    'zip' => '10115', 'country' => 'Germany'],
    ['street_fmt' => '%d Gran Via',             'city' => 'Madrid',    'zip' => '28013', 'country' => 'Spain'],
    ['street_fmt' => '%d Via del Corso',        'city' => 'Rome',      'zip' => '00186', 'country' => 'Italy'],
    ['street_fmt' => '%d Damrak',               'city' => 'Amsterdam', 'zip' => '1012 LM','country' => 'Netherlands'],
    ['street_fmt' => '%d Rue Neuve',            'city' => 'Brussels',  'zip' => '1000',  'country' => 'Belgium'],
    ['street_fmt' => '%d Mariahilfer Strasse',  'city' => 'Vienna',    'zip' => '1060',  'country' => 'Austria'],
    ['street_fmt' => '%d Nowy Swiat',           'city' => 'Warsaw',    'zip' => '00-001','country' => 'Poland'],
    ['street_fmt' => '%d Rua Augusta',          'city' => 'Lisbon',    'zip' => '1100-048','country' => 'Portugal'],
    ['street_fmt' => '%d Drottninggatan',       'city' => 'Stockholm', 'zip' => '111 51','country' => 'Sweden'],
    ['street_fmt' => '%d Karl Johans Gate',     'city' => 'Oslo',      'zip' => '0159',  'country' => 'Norway'],
    ['street_fmt' => '%d Bahnhofstrasse',       'city' => 'Zurich',    'zip' => '8001',  'country' => 'Switzerland'],
    ['street_fmt' => '%d Vaclavske Namesti',    'city' => 'Prague',    'zip' => '110 00','country' => 'Czech Republic'],
    ['street_fmt' => '%d Vaci Utca',            'city' => 'Budapest',  'zip' => '1052',  'country' => 'Hungary'],
];

$firstNames = [
    'Alice','Bob','Charlie','Diana','Eve','Frank','Grace','Henry','Irene','Jack',
    'Karen','Leo','Mia','Noah','Olivia','Paul','Quinn','Rachel','Sam','Tina',
    'Uma','Victor','Wendy','Xavier','Yara','Zoe','Aaron','Bella','Carl','Dora',
    'Eli','Faye','George','Hannah','Ivan','Julia','Kevin','Laura','Mike','Nina',
    'Oscar','Petra','Quentin','Rosa','Steve','Tara','Ulric','Vera','Will','Xena',
];

$lastNames = [
    'Smith','Jones','Williams','Brown','Taylor','Davies','Evans','Wilson','Thomas',
    'Roberts','Johnson','Lee','Walker','Hall','Allen','Young','King','Wright',
    'Scott','Green','Baker','Adams','Nelson','Carter','Mitchell','Perez','Turner',
    'Phillips','Campbell','Parker','Edwards','Collins','Stewart','Morris','Rogers',
    'Reed','Cook','Morgan','Bell','Murphy','Bailey','Rivera','Cooper','Richardson',
    'Cox','Howard','Ward','Torres','Peterson','Gray',
];

// ── State ─────────────────────────────────────────────────────────────────────

$userRows        = [];
$companyRows     = [];
$shopRows        = [];

$emailSeq = 0;

function nextIdentity(array $firstNames, array $lastNames, int &$seq): array
{
    $fn  = $firstNames[$seq % count($firstNames)];
    $ln  = $lastNames[intdiv($seq, count($firstNames)) % count($lastNames)];
    $seq++;
    return [$fn, $ln];
}

function nextEmail(string $fn, string $ln, int $n, string $tag = ''): string
{
    $sfx = $tag !== '' ? ".{$tag}" : '';
    return strtolower("{$fn}.{$ln}{$sfx}.{$n}@fixture.test");
}

// ── Admin users (5) ───────────────────────────────────────────────────────────

for ($i = 1; $i <= 5; $i++) {
    $uid   = uuid4();
    $email = "admin{$i}@fixture.test";
    [$fn, $ln] = nextIdentity($firstNames, $lastNames, $emailSeq);
    $userRows[] = "(" . ubin($uid) . ", " . sq($email) . ", " . sq($fn) . ", " . sq($ln) . ", NULL, 'admin', NULL, NULL, 1, NULL, " . sq(PASSWORD_HASH) . ", NULL)";
}

// ── Companies + shops + role-holders ─────────────────────────────────────────

$shopGlobal  = 0;

for ($ci = 0; $ci < 1000; $ci++) {
    $companyBaseName = $companyNames[$ci % count($companyNames)];
    $companyName = ($ci < count($companyNames)) ? $companyBaseName : "{$companyBaseName} #{$ci}";
    $cid         = uuid4();
    $isDeletedCo = ($ci < 50);
    $coDeleted   = $isDeletedCo ? "NOW() - INTERVAL " . ($ci + 1) . " DAY" : 'NULL';
    $companyRows[] = "(" . ubin($cid) . ", " . sq($companyName) . ", " . sq(strtolower(str_replace([' ', '#'], ['.', ''], $companyName)) . "@fixture.test") . ", NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, {$coDeleted})";

    // Company admins (only for active companies)
    if (!$isDeletedCo) {
        for ($m = 0; $m < 2; $m++) {
            $uid   = uuid4();
            [$fn, $ln] = nextIdentity($firstNames, $lastNames, $emailSeq);
            $email = nextEmail($fn, $ln, $emailSeq, 'ca');
            $userRows[] = "(" . ubin($uid) . ", " . sq($email) . ", " . sq($fn) . ", " . sq($ln) . ", NULL, 'company_admin', " . ubin($cid) . ", NULL, 1, NULL, " . sq(PASSWORD_HASH) . ", NULL)";
        }
    }

    // 10 shops per company
    for ($si = 0; $si < 10; $si++) {
        $sid         = uuid4();
        $loc         = $cities[($ci * 10 + $si) % count($cities)];
        $suffix      = $shopSuffixes[$si % count($shopSuffixes)];
        $shopName    = "{$suffix} #{$si}";
        $street      = sprintf($loc['street_fmt'], ($ci * 10 + $si + 1) * 3);
        $isDeletedSh = ($shopGlobal < 300);
        $shDeleted   = $isDeletedSh ? "NOW() - INTERVAL " . ($shopGlobal + 1) . " HOUR" : 'NULL';
        $shopGlobal++;

        $shopRows[] = "(" . ubin($sid) . ", " . ubin($cid) . ", "
            . sq($shopName) . ", NULL, NULL, " . sq($street) . ", NULL, "
            . sq($loc['city']) . ", " . sq($loc['zip']) . ", "
            . sq($loc['country']) . ", NULL, NULL, 0, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, {$shDeleted})";

        // Shop managers only for active shops
        if (!$isDeletedSh) {
            for ($sm = 0; $sm < 2; $sm++) {
                $uid   = uuid4();
                [$fn, $ln] = nextIdentity($firstNames, $lastNames, $emailSeq);
                $email = nextEmail($fn, $ln, $emailSeq, 'sm');
                $userRows[] = "(" . ubin($uid) . ", " . sq($email) . ", " . sq($fn) . ", " . sq($ln) . ", NULL, 'shop_manager', " . ubin($cid) . ", " . ubin($sid) . ", 1, NULL, " . sq(PASSWORD_HASH) . ", NULL)";
            }
        }
    }
}

// ── Employees (1000, first 100 soft-deleted) ──────────────────────────────────

for ($i = 0; $i < 1000; $i++) {
    $uid       = uuid4();
    [$fn, $ln] = nextIdentity($firstNames, $lastNames, $emailSeq);
    $email     = nextEmail($fn, $ln, $emailSeq);
    $isDeleted = ($i < 100);
    $deleted   = $isDeleted ? "NOW() - INTERVAL " . ($i + 1) . " DAY" : 'NULL';
    $userRows[] = "(" . ubin($uid) . ", " . sq($email) . ", " . sq($fn) . ", " . sq($ln) . ", NULL, 'employee', NULL, NULL, 1, NULL, " . sq(PASSWORD_HASH) . ", {$deleted})";
}

// ── Render SQL ────────────────────────────────────────────────────────────────

$tu = count($userRows);
$tc = count($companyRows);
$ts = count($shopRows);

echo <<<SQL
    -- =============================================================================
    --  fixtures.sql  –  Stress-test dataset
    --
    --  Users:            {$tu}
    --  Companies:        {$tc}  (50 soft-deleted)
    --  Shops:            {$ts} (10 per company, 300 soft-deleted)
    --
    --  All passwords: password123
    -- =============================================================================

    SET FOREIGN_KEY_CHECKS = 0;

    TRUNCATE TABLE users;
    TRUNCATE TABLE shops;
    TRUNCATE TABLE companies;

    SET FOREIGN_KEY_CHECKS = 1;


    SQL;

insertChunked('companies', '(id, name, email, phone_number, website, address_line_1, address_line_2, city, postal_code, country, is_active, created_at, updated_at, deleted_at)', $companyRows, 50);
insertChunked('shops', '(id, company_id, name, email, phone_number, address_line_1, address_line_2, city, postal_code, country, latitude, longitude, is_digital, is_active, created_at, updated_at, deleted_at)', $shopRows, 50);
insertChunked('users', '(id, email, first_name, last_name, phone_number, role, company_id, shop_id, is_active, last_login_at, password_hash, deleted_at)', $userRows, 50);

echo "-- Done.\n";
