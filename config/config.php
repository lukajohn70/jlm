<?php
$envLoaded = false;
if (!function_exists('jlm_load_env_file')) {
    function jlm_load_env_file($path) {
        if (!is_file($path) || !is_readable($path)) {
            return false;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return false;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0) {
                continue;
            }

            $parts = explode('=', $line, 2);
            if (count($parts) !== 2) {
                continue;
            }

            $key = trim($parts[0]);
            $value = trim($parts[1]);

            if ($value !== '' && (($value[0] === '"' && substr($value, -1) === '"') || ($value[0] === "'" && substr($value, -1) === "'"))) {
                $value = substr($value, 1, -1);
            } else {
                $value = preg_replace('/\s+#.*$/', '', $value);
                $value = trim((string) $value);
            }

            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
            putenv($key . '=' . $value);
        }

        return true;
    }
}

$envLoaded = jlm_load_env_file(__DIR__ . '/../.env');
if (!$envLoaded && !getenv('JLM_DB_HOST')) {
    error_log("JLM Config Error: .env file not found or not readable at " . realpath(__DIR__ . '/../.env'));
}

$httpHost = $_SERVER['HTTP_HOST'] ?? '';
$isDevelopment = $httpHost === 'localhost'
    || $httpHost === '127.0.0.1'
    || strpos($httpHost, 'localhost:') === 0
    || strpos($httpHost, '127.0.0.1:') === 0
    || php_sapi_name() === 'cli'
    || empty($httpHost);

$forceDev = getenv('JLM_FORCE_DEVELOPMENT');
$forceProd = getenv('JLM_FORCE_PRODUCTION');
if ($forceDev) {
    $isDevelopment = true;
} elseif ($forceProd) {
    $isDevelopment = false;
}

// Optional local overrides (e.g. JLM_DB_USER=root) — never commit .env.local.
if ($isDevelopment && is_file(__DIR__ . '/../.env.local')) {
    jlm_load_env_file(__DIR__ . '/../.env.local');
}

define('DEVELOPMENT_MODE', $isDevelopment);

// Set default timezone matching application local timezone
date_default_timezone_set('Africa/Lagos');

require_once __DIR__ . '/../vendor/autoload.php';

if (!defined('DB_HOST')) {
    define('DB_HOST', getenv('JLM_DB_HOST') ?: ($isDevelopment ? '127.0.0.1' : 'localhost'));
}
if (!defined('DB_PORT')) {
    define('DB_PORT', (int) (getenv('JLM_DB_PORT') ?: 3306));
}
if (!defined('DB_SOCKET')) {
    define('DB_SOCKET', getenv('JLM_DB_SOCKET') ?: '');
}
if (!defined('DB_NAME')) {
    define('DB_NAME', getenv('JLM_DB_NAME') ?: 'gwylvxeo_jlm');
}
if (!defined('DB_USER')) {
    define('DB_USER', getenv('JLM_DB_USER') ?: '');
}
if (!defined('DB_PASS')) {
    define('DB_PASS', getenv('JLM_DB_PASS') ?: '');
}

$db_connection_error = '';

$connectionCandidates = [];
if (DB_SOCKET !== '' && is_file(DB_SOCKET)) {
    $connectionCandidates[] = [
        'dsn' => 'mysql:unix_socket=' . DB_SOCKET . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        'user' => DB_USER,
        'pass' => DB_PASS,
        'label' => 'primary_socket',
    ];
}
$dsnHost = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
if (DB_PORT > 0) {
    $dsnHost .= ';port=' . DB_PORT;
}
$connectionCandidates[] = [
    'dsn' => $dsnHost,
    'user' => DB_USER,
    'pass' => DB_PASS,
    'label' => 'primary_host',
];

// Development fallback for common MAMP/local setups.
if ($isDevelopment) {
    $nameCandidates = array_values(array_unique(array_filter([
        getenv('JLM_DB_NAME_LOCAL') ?: '',
        DB_NAME,
        'jlm',
        'jlm_db',
        'gwylvxeo_jlm',
    ])));
    foreach ($nameCandidates as $localDbName) {
        $connectionCandidates[] = [
            'dsn' => 'mysql:unix_socket=/Applications/MAMP/tmp/mysql/mysql.sock;dbname=' . $localDbName . ';charset=utf8mb4',
            'user' => 'root',
            'pass' => 'root',
            'label' => 'mamp_socket_root_' . $localDbName,
        ];
        $connectionCandidates[] = [
            'dsn' => 'mysql:host=127.0.0.1;port=8889;dbname=' . $localDbName . ';charset=utf8mb4',
            'user' => 'root',
            'pass' => 'root',
            'label' => 'mamp_port_root_' . $localDbName,
        ];
        $connectionCandidates[] = [
            'dsn' => 'mysql:host=127.0.0.1;port=3306;dbname=' . $localDbName . ';charset=utf8mb4',
            'user' => 'root',
            'pass' => '',
            'label' => 'local_empty_pass_' . $localDbName,
        ];
    }
}

$pdo = null;
$lastException = null;
foreach ($connectionCandidates as $candidate) {
    try {
        $pdo = new PDO($candidate['dsn'], $candidate['user'], $candidate['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        break;
    } catch (PDOException $e) {
        $lastException = $e;
        continue;
    }
}

if (!$pdo && $isDevelopment) {
    $serverCandidates = [
        [
            'dsn' => 'mysql:unix_socket=/Applications/MAMP/tmp/mysql/mysql.sock;charset=utf8mb4',
            'user' => 'root',
            'pass' => 'root',
        ],
        [
            'dsn' => 'mysql:host=127.0.0.1;port=8889;charset=utf8mb4',
            'user' => 'root',
            'pass' => 'root',
        ],
        [
            'dsn' => 'mysql:host=127.0.0.1;port=3306;charset=utf8mb4',
            'user' => 'root',
            'pass' => '',
        ],
    ];

    foreach ($serverCandidates as $serverCandidate) {
        try {
            $serverPdo = new PDO($serverCandidate['dsn'], $serverCandidate['user'], $serverCandidate['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            // Find a likely local DB first.
            $existingDb = '';
            $stmt = $serverPdo->query("SHOW DATABASES");
            $dbRows = $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
            if (!empty($dbRows)) {
                foreach ($dbRows as $dbName) {
                    $dbName = (string) $dbName;
                    if (stripos($dbName, 'jlm') !== false) {
                        $existingDb = $dbName;
                        break;
                    }
                }
            }

            if ($existingDb === '') {
                $existingDb = getenv('JLM_DB_NAME_LOCAL') ?: 'jlm';
                $serverPdo->exec("CREATE DATABASE IF NOT EXISTS `" . str_replace('`', '``', $existingDb) . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            }

            $dbDsn = $serverCandidate['dsn'];
            if (strpos($dbDsn, ';charset=utf8mb4') !== false) {
                $dbDsn = str_replace(';charset=utf8mb4', ';dbname=' . $existingDb . ';charset=utf8mb4', $dbDsn);
            } else {
                $dbDsn .= ';dbname=' . $existingDb;
            }

            $pdo = new PDO($dbDsn, $serverCandidate['user'], $serverCandidate['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            break;
        } catch (PDOException $e) {
            $lastException = $e;
            continue;
        }
    }
}

if (!$pdo) {
    $db_connection_error = $lastException ? $lastException->getMessage() : 'Unknown database connection error.';
    if ($isDevelopment) {
        error_log('Database connection failed: ' . $db_connection_error);
    } else {
        echo 'Database connection failed: ' . $db_connection_error;
        exit;
    }
}

// So code inside functions (e.g. require_admin_auth) can read the connection after a file-scope include.
$GLOBALS['pdo'] = $pdo;
$GLOBALS['db_connection_error'] = $db_connection_error;

function jlm_password_hash($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}
