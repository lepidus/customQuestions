<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

// Provision only the disposable database used by schema tests in the CI MariaDB service.
$host = getenv('MARIADB_HOST');
$password = getenv('MARIADB_ROOT_PASSWORD');
if (!$host || $password === false) {
    throw new RuntimeException('MARIADB_HOST and MARIADB_ROOT_PASSWORD are required for schema tests.');
}

$config = [
    'driver' => 'mysql',
    'host' => $host,
    'port' => (int) (getenv('MARIADB_PORT') ?: 3306),
    'database' => 'custom_questions_schema_test',
    'username' => 'root',
    'password' => $password,
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
];

$pdo = new PDO(
    'mysql:host=' . $config['host'] . ';port=' . $config['port'] . ';charset=utf8mb4',
    $config['username'],
    $config['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
// Refuse to reuse an existing database: schema tests create and drop their fixture tables.
$pdo->exec('CREATE DATABASE custom_questions_schema_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

echo json_encode($config, JSON_THROW_ON_ERROR) . PHP_EOL;
