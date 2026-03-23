<?php
// ============================================================
//  API de Pacientes
//  GET  /api/pacientes.php       → Listar pacientes
//  POST /api/pacientes.php       → Crear paciente
// ============================================================

require_once __DIR__ . '/config.php';

$pdo    = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $pdo->query(
        'SELECT id, nombre, apellido, telefono, email, fecha_nacimiento
         FROM pacientes
         ORDER BY nombre ASC'
    );
    echo json_encode($stmt->fetchAll());
    exit;
}

if ($method === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true);

    $body['nombre'] = trim($body['nombre'] ?? '');
    $body['apellido'] = trim($body['apellido'] ?? '');

    if (empty($body['nombre']) || empty($body['apellido'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Nombre y apellido son requeridos']);
        exit;
    }

    // --- VALIDACIÓN DE NOMBRE (sin números) ---
    if (preg_match('/[0-9]/', $body['nombre'])) {
        http_response_code(400);
        echo json_encode(['error' => 'El nombre no puede contener números']);
        exit;
    }

    // --- VALIDACIÓN DE APELLIDOS (sin números y con espacio) ---
    if (preg_match('/[0-9]/', $body['apellido'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Los apellidos no pueden contener números']);
        exit;
    }

    if (strpos($body['apellido'], ' ') === false) {
        http_response_code(400);
        echo json_encode(['error' => 'Debe ingresar ambos apellidos separados por un espacio (ej: Lopez Vasquez)']);
        exit;
    }

    // --- VALIDACIÓN DE TELÉFONO ---
    if (!empty($body['telefono'])) {
        // Eliminar espacios, guiones, etc. para validar solo los números
        $telefonoLimpio = preg_replace('/[^0-9]/', '', $body['telefono']);
        
        if (strlen($telefonoLimpio) !== 10) {
            http_response_code(400);
            echo json_encode(['error' => 'El número de teléfono debe tener exactamente 10 dígitos']);
            exit;
        }
        $body['telefono'] = $telefonoLimpio;
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'El número de teléfono es obligatorio']);
        exit;
    }

    // --- VALIDACIÓN DE EMAIL ---
    if (!empty($body['email'])) {
        $email = strtolower(trim($body['email']));
        $dominiosPermitidos = ['gmail.com', 'hotmail.com', 'outlook.com', 'yahoo.com', 'icloud.com', 'live.com', 'outlook.es', 'hotmail.es'];
        $partes = explode('@', $email);
        $dominio = $partes[1] ?? '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !in_array($dominio, $dominiosPermitidos)) {
            http_response_code(400);
            echo json_encode(['error' => 'El correo debe ser de un proveedor conocido (gmail, hotmail, etc.)']);
            exit;
        }
        $body['email'] = $email;
    }

    // --- VALIDACIÓN DE FECHA DE NACIMIENTO (no futura) ---
    if (!empty($body['fecha_nacimiento'])) {
        $fechaNac = $body['fecha_nacimiento'];
        $hoy = date('Y-m-d');
        if ($fechaNac > $hoy) {
            http_response_code(400);
            echo json_encode(['error' => 'La fecha de nacimiento no puede ser una fecha futura']);
            exit;
        }
    }

    // --- VERIFICACIÓN DE DUPLICADOS ---
    // 1. Verificar por teléfono (debe ser único)
    if (!empty($body['telefono'])) {
        $stmtTel = $pdo->prepare('SELECT id FROM pacientes WHERE telefono = :tel');
        $stmtTel->execute([':tel' => $body['telefono']]);
        if ($stmtTel->fetch()) {
            http_response_code(409);
            echo json_encode(['error' => 'Ya existe un paciente con este número de teléfono']);
            exit;
        }
    }

    // 2. Verificar por email (opcional pero recomendado)
    if (!empty($body['email'])) {
        $stmtEmail = $pdo->prepare('SELECT id FROM pacientes WHERE email = :email');
        $stmtEmail->execute([':email' => $body['email']]);
        if ($stmtEmail->fetch()) {
            http_response_code(409);
            echo json_encode(['error' => 'Ya existe un paciente con este correo electrónico']);
            exit;
        }
    }

    // 3. Verificación legacy por nombre y apellido (opcional)
    // $sqlCheck = 'SELECT id FROM pacientes WHERE nombre = :nombre AND apellido = :apellido';
    // ...
    // ---------------------------------

    $stmt = $pdo->prepare(
        'INSERT INTO pacientes (nombre, apellido, telefono, email, fecha_nacimiento, direccion, notas)
         VALUES (:nombre, :apellido, :telefono, :email, :fecha_nacimiento, :direccion, :notes)'
    );
    $stmt->execute([
        ':nombre'           => $body['nombre'],
        ':apellido'         => $body['apellido'],
        ':telefono'         => $body['telefono']          ?? null,
        ':email'            => $body['email']             ?? null,
        ':fecha_nacimiento' => $body['fecha_nacimiento']  ?? null,
        ':direccion'        => $body['direccion']         ?? null,
        ':notes'            => $body['notas']             ?? null,
    ]);

    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Método no permitido']);
