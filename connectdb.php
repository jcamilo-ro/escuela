<?php

function loadLocalEnvFile(string $filePath): void
{
    if (!is_file($filePath)) {
        return;
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '#') || !str_contains($trimmed, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $trimmed, 2);
        $key = trim($key);
        $value = trim($value);

        if ($key === '') {
            continue;
        }

        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $value = substr($value, 1, -1);
        }

        if (getenv($key) === false) {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

loadLocalEnvFile(__DIR__ . '/.env');

// Esta conexion centraliza el acceso a MySQL para todo el proyecto.
// Asi evitamos repetir credenciales y mantenemos el mismo archivo util
// tanto para XAMPP como para Docker.
$hostDB = getenv('DB_HOST') ?: '127.0.0.1';
$portDB = getenv('DB_PORT') ?: '3306';
$nameDB = getenv('DB_NAME') ?: 'escuela_db';
$userDB = getenv('DB_USER') ?: 'escuela_user';
$passDB = getenv('DB_PASS') ?: '123456';

try {
    // Se usa PDO porque facilita el manejo de errores y las sentencias preparadas.
    $dsn = "mysql:host=$hostDB;port=$portDB;dbname=$nameDB;charset=utf8";
    $pdo = new PDO($dsn, $userDB, $passDB);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Si no hay conexion, se detiene el flujo para no seguir con un estado inconsistente.
    die("Connection failed: " . $e->getMessage());
}

?>
