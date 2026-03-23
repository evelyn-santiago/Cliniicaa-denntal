<?php
// ================================================================
//  Script de instalación de base de datos - SOLO USO ÚNICO
//  Accede a: /api/setup_db.php para inicializar la base de datos
//  IMPORTANTE: Elimina o bloquea este archivo después de usar
// ================================================================

require_once __DIR__ . '/config.php';

// Verificar token de seguridad para evitar ejecución accidental
$token = $_GET['token'] ?? '';
if ($token !== 'SETUP_CLINICA_2026') {
    http_response_code(403);
    echo json_encode(['error' => 'Token de seguridad requerido. Agrega ?token=SETUP_CLINICA_2026 a la URL']);
    exit;
}

$pdo = getDB();
$results = [];

$queries = [
    "SET FOREIGN_KEY_CHECKS = 0",
    "DROP TABLE IF EXISTS facturas",
    "DROP TABLE IF EXISTS citas",
    "DROP TABLE IF EXISTS pacientes",
    "DROP TABLE IF EXISTS usuarios",
    "SET FOREIGN_KEY_CHECKS = 1",

    "CREATE TABLE usuarios (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        usuario     VARCHAR(50)  NOT NULL UNIQUE,
        password    VARCHAR(255) NOT NULL,
        nombre      VARCHAR(100) NOT NULL,
        rol         ENUM('admin','odontologo','recepcionista') NOT NULL DEFAULT 'odontologo',
        avatar_url  VARCHAR(255) DEFAULT NULL,
        activo      TINYINT(1)   NOT NULL DEFAULT 1,
        creado_en   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
    )",

    "CREATE TABLE pacientes (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        nombre          VARCHAR(100) NOT NULL,
        apellido        VARCHAR(100) NOT NULL,
        telefono        VARCHAR(20)  DEFAULT NULL,
        email           VARCHAR(100) DEFAULT NULL,
        fecha_nacimiento DATE         DEFAULT NULL,
        direccion       VARCHAR(255) DEFAULT NULL,
        notas           TEXT         DEFAULT NULL,
        creado_en       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
    )",

    "CREATE TABLE citas (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        paciente_id     INT          NOT NULL,
        odontologo_id   INT          NOT NULL,
        fecha           DATE         NOT NULL,
        hora            TIME         NOT NULL,
        procedimiento   VARCHAR(100) NOT NULL,
        estado          ENUM('pendiente','confirmada','completada','cancelada') NOT NULL DEFAULT 'pendiente',
        notas           TEXT         DEFAULT NULL,
        creado_en       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (paciente_id)   REFERENCES pacientes(id) ON DELETE CASCADE,
        FOREIGN KEY (odontologo_id) REFERENCES usuarios(id)  ON DELETE CASCADE
    )",

    "CREATE TABLE facturas (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        cita_id         INT          NOT NULL,
        monto           DECIMAL(10,2) NOT NULL,
        metodo_pago     ENUM('efectivo','tarjeta','transferencia') NOT NULL DEFAULT 'efectivo',
        estado_pago     ENUM('pagado','pendiente') NOT NULL DEFAULT 'pagado',
        fecha_emision   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (cita_id) REFERENCES citas(id) ON DELETE CASCADE
    )",

    "INSERT INTO usuarios (usuario, password, nombre, rol, avatar_url) VALUES
        ('naye',   MD5('1234'),      'Dr. Juan Perez',  'odontologo',    'https://randomuser.me/api/portraits/men/32.jpg'),
        ('admin',  MD5('admin123'),  'Administrador',   'admin',         'https://randomuser.me/api/portraits/men/1.jpg'),
        ('recep',  MD5('recep123'),  'Laura Gómez',     'recepcionista', 'https://randomuser.me/api/portraits/women/44.jpg')",

    "INSERT INTO pacientes (nombre, apellido, telefono, email, fecha_nacimiento) VALUES
        ('María',  'González',  '5551001111', 'maria.gonzalez@email.com',   '1990-05-12'),
        ('Carlos', 'Rodríguez', '5551002222', 'carlos.rodriguez@email.com', '1985-08-23'),
        ('Ana',    'Martínez',  '5551003333', 'ana.martinez@email.com',     '1995-03-15'),
        ('Luis',   'Fernández', '5551004444', 'luis.fernandez@email.com',   '1978-11-30'),
        ('Sofía',  'Ramírez',   '5551005555', 'sofia.ramirez@email.com',    '2000-07-04'),
        ('Jorge',  'Sánchez',   '5551006666', 'jorge.sanchez@email.com',    '1992-09-18')",

    "INSERT INTO citas (paciente_id, odontologo_id, fecha, hora, procedimiento, estado) VALUES
        (1, 1, '2026-04-05', '10:00:00', 'Limpieza Dental', 'confirmada'),
        (2, 1, '2026-04-05', '11:30:00', 'Extracción',      'confirmada'),
        (3, 1, '2026-04-05', '14:00:00', 'Ortodoncia',      'pendiente'),
        (4, 1, '2026-04-03', '09:00:00', 'Blanqueamiento',  'completada'),
        (5, 1, '2026-04-02', '10:30:00', 'Revisión',        'completada'),
        (6, 1, '2026-04-01', '15:00:00', 'Carilla',         'completada')",

    "INSERT INTO facturas (cita_id, monto, metodo_pago, estado_pago) VALUES
        (4, 1500.00, 'tarjeta',       'pagado'),
        (5,  500.00, 'efectivo',      'pagado'),
        (6, 3500.00, 'transferencia', 'pagado')"
];

foreach ($queries as $sql) {
    try {
        $pdo->exec($sql);
        $results[] = ['ok' => true, 'sql' => substr($sql, 0, 60) . '...'];
    } catch (PDOException $e) {
        $results[] = ['ok' => false, 'sql' => substr($sql, 0, 60) . '...', 'error' => $e->getMessage()];
    }
}

$errors = array_filter($results, fn($r) => !$r['ok']);

echo json_encode([
    'success'  => count($errors) === 0,
    'message'  => count($errors) === 0
        ? '✅ Base de datos inicializada correctamente. ¡Elimina este archivo ahora!'
        : '⚠️ Hubo algunos errores. Revisa los detalles.',
    'results'  => $results
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
