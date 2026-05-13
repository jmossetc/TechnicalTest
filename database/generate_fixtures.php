<?php

declare(strict_types=1);

/**
 * Generates fixtures.sql for stress-testing the application.
 *
 * Scale:
 *   -    5 admin users
 *   -   50 companies  (5 soft-deleted)
 *   -  350 shops       (7 per company, 30 soft-deleted)
 *   -   90 company managers (2 per active company = 2×45)
 *   -  640 shop managers    (2 per active shop    = 2×320)
 *   -  300 plain users      (no role, 20 soft-deleted)
 *   Total users ≈ 1 035
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

function sq(string $s): string
{
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

const PASSWORD_HASH = '$2y$10$nEt6UajoUSGFN11kFgHOrO5GnrA.7AxlEtEa0os3qTepwgGqV61Ci';

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
$adminRoleRows   = [];
$companyRows     = [];
$shopRows        = [];
$companyRoleRows = [];
$shopRoleRows    = [];

$emailSeq = 0;

function nextEmail(array $firstNames, array $lastNames, int &$seq, string $tag = ''): string
{
    $fn  = $firstNames[$seq % count($firstNames)];
    $ln  = $lastNames[intdiv($seq, count($firstNames)) % count($lastNames)];
    $n   = $seq + 1;
    $seq++;
    $sfx = $tag !== '' ? ".{$tag}" : '';
    return strtolower("{$fn}.{$ln}{$sfx}.{$n}@fixture.test");
}

// ── Admin users (5) ───────────────────────────────────────────────────────────

for ($i = 1; $i <= 5; $i++) {
    $uid   = uuid4();
    $email = "admin{$i}@fixture.test";
    $userRows[]      = "(" . ubin($uid) . ", " . sq($email) . ", " . sq(PASSWORD_HASH) . ", NULL)";
    $adminRoleRows[] = "(" . ubin($uid) . ")";
}

// ── Companies + shops + role-holders ─────────────────────────────────────────

$companyIds  = [];
$shopGlobal  = 0; // global shop counter for soft-delete threshold

foreach ($companyNames as $ci => $companyName) {
    $cid         = uuid4();
    $companyIds[] = $cid;
    $isDeletedCo = ($ci < 5);
    $coDeleted   = $isDeletedCo ? "NOW() - INTERVAL " . ($ci + 1) . " DAY" : 'NULL';
    $companyRows[] = "(" . ubin($cid) . ", " . sq($companyName) . ", {$coDeleted})";

    // Company managers (only for active companies)
    if (!$isDeletedCo) {
        for ($m = 0; $m < 2; $m++) {
            $uid   = uuid4();
            $email = nextEmail($firstNames, $lastNames, $emailSeq, 'cm');
            $userRows[]        = "(" . ubin($uid) . ", " . sq($email) . ", " . sq(PASSWORD_HASH) . ", NULL)";
            $companyRoleRows[] = "(" . ubin($uid) . ", " . ubin($cid) . ")";
        }
    }

    // 7 shops per company
    for ($si = 0; $si < 7; $si++) {
        $sid         = uuid4();
        $loc         = $cities[($ci * 7 + $si) % count($cities)];
        $suffix      = $shopSuffixes[$si];
        $street      = sprintf($loc['street_fmt'], ($ci * 7 + $si + 1) * 3);
        $isDeletedSh = ($shopGlobal < 30);
        $shDeleted   = $isDeletedSh ? "NOW() - INTERVAL " . ($shopGlobal + 1) . " HOUR" : 'NULL';
        $shopGlobal++;

        $shopRows[] = "(" . ubin($sid) . ", " . ubin($cid) . ", "
            . sq($suffix) . ", " . sq($street) . ", "
            . sq($loc['city']) . ", " . sq($loc['zip']) . ", "
            . sq($loc['country']) . ", {$shDeleted})";

        // Shop managers only for active shops
        if (!$isDeletedSh) {
            for ($sm = 0; $sm < 2; $sm++) {
                $uid   = uuid4();
                $email = nextEmail($firstNames, $lastNames, $emailSeq, 'sm');
                $userRows[]     = "(" . ubin($uid) . ", " . sq($email) . ", " . sq(PASSWORD_HASH) . ", NULL)";
                $shopRoleRows[] = "(" . ubin($uid) . ", " . ubin($sid) . ")";
            }
        }
    }
}

// ── Plain users (300, first 20 soft-deleted) ──────────────────────────────────

for ($i = 0; $i < 300; $i++) {
    $uid       = uuid4();
    $email     = nextEmail($firstNames, $lastNames, $emailSeq);
    $isDeleted = ($i < 20);
    $deleted   = $isDeleted ? "NOW() - INTERVAL " . ($i + 1) . " DAY" : 'NULL';
    $userRows[] = "(" . ubin($uid) . ", " . sq($email) . ", " . sq(PASSWORD_HASH) . ", {$deleted})";
}

// ── Render SQL ────────────────────────────────────────────────────────────────

$tu = count($userRows);
$tc = count($companyRows);
$ts = count($shopRows);
$tcm = count($companyRoleRows);
$tsm = count($shopRoleRows);

echo <<<SQL
-- =============================================================================
--  fixtures.sql  –  Stress-test dataset
--
--  Users:            {$tu}   (5 admins · {$tcm} company managers · {$tsm} shop managers · plain)
--  Companies:        {$tc}  (5 soft-deleted)
--  Shops:            {$ts} (7 per company, 30 soft-deleted)
--  company_manager:  {$tcm}  role grants
--  shop_manager:     {$tsm}  role grants
--
--  All passwords: Secret1234!
-- =============================================================================

SET foreign_key_checks = 0;

TRUNCATE TABLE user_shop_roles;
TRUNCATE TABLE user_company_roles;
TRUNCATE TABLE user_admin_roles;
TRUNCATE TABLE shops;
TRUNCATE TABLE companies;
TRUNCATE TABLE users;

SET foreign_key_checks = 1;


SQL;

insertChunked('users', '(id, email, password_hash, deleted_at)', $userRows, 50);
insertChunked('user_admin_roles', '(user_id)', $adminRoleRows, 50);
insertChunked('companies', '(id, name, deleted_at)', $companyRows, 50);
insertChunked('shops', '(id, company_id, name, street, city, zip, country, deleted_at)', $shopRows, 50);

if ($companyRoleRows !== []) {
    insertChunked('user_company_roles', '(user_id, company_id)', $companyRoleRows, 100);
}
if ($shopRoleRows !== []) {
    insertChunked('user_shop_roles', '(user_id, shop_id)', $shopRoleRows, 100);
}

echo "-- Done.\n";
