<?php
require_once('../config/config.php');

if (isset($_GET['especie']) && isset($_GET['empresa'])) {
    $especie = $_GET['especie'];
    $empresa = $_GET['empresa'];

    try {
        $sql = 'SELECT codigo as codigo_embalaje FROM embalajes  WHERE especie = :especie AND empresa = :empresa AND estado = :estado';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':especie' => $especie,
            ':empresa' => $empresa,
            ':estado' => 'HABILITADO'
        ]);
        $embalajes = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        error_log("obtener_embalaje_calibre error (embalajes): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Error al obtener embalajes. Contacte al administrador."]);
        exit;
    }

    try {
        $sql = 'SELECT calibre FROM calibres  WHERE especie = :especie AND empresa = :empresa';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':especie' => $especie,
            ':empresa' => $empresa
        ]);
        $calibres = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        error_log("obtener_embalaje_calibre error (calibres): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Error al obtener calibres. Contacte al administrador."]);
        exit;
    }

    try {
        $sql = 'SELECT codigo FROM categoria  WHERE especie = :especie AND empresa = :empresa';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':especie' => $especie,
            ':empresa' => $empresa
        ]);
        $categorias = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        error_log("obtener_embalaje_calibre error (categorias): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Error al obtener categorías. Contacte al administrador."]);
        exit;
    }

    echo json_encode([
        'embalaje' => $embalajes,
        'calibre' => $calibres,
        'categoria' => $categorias
    ]);

} else {
    http_response_code(400);
    echo json_encode(["error" => "Parámetros 'especie' y 'empresa' son requeridos."]);
}