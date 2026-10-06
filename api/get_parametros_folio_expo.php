<?php
//session_start();
include_once $_SERVER['DOCUMENT_ROOT'] . '/packing/config/db.php';

function GetEmbalaje($especie, $empresa)
{
    global $pdo;

    $query = "SELECT * FROM embalajes WHERE especie = :especie AND empresa = :empresa and estado = 'HABILITADO'";
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        'especie' => $especie,
        'empresa' => $empresa
    ]);
    $embalajes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return $embalajes;

    
}

function GetCalibres($especie, $empresa)
{
    global $pdo;

    $query = "SELECT * FROM calibres WHERE especie = :especie AND empresa = :empresa";
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        'especie' => $especie,
        'empresa' => $empresa
    ]);
    $calibres = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $calibres;
}

function GetEtiquetas($especie, $empresa)
{
    global $pdo;

    $query = "SELECT * FROM etiqueta WHERE especie = :especie AND empresa = :empresa";
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        'especie' => $especie,
        'empresa' => $empresa
    ]);
    $etiquetas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $etiquetas;
}

function GetCategorias($especie, $empresa)
{
    global $pdo;

    $query = "SELECT * FROM categoria WHERE especie = :especie AND empresa = :empresa";
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        'especie' => $especie,
        'empresa' => $empresa
    ]);
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $categorias;
}

function GetPlu($especie, $empresa)
{
    global $pdo;

    $query = "SELECT * FROM plu WHERE especie = :especie AND empresa = :empresa";
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        'especie' => $especie,
        'empresa' => $empresa
    ]);
    $plu = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $plu;
}

function GetVariedades($especie, $empresa)
{
    global $pdo;

    $query = "SELECT * FROM variedad WHERE especie = :especie";
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        'especie' => $especie
    ]);
    $variedades = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $variedades;
}

function GetProductores($empresa)
{
    global $pdo;

    // Para reembalaje: listar TODOS los productores de la empresa (tabla productores)
    // No filtrar por recepcion_granel porque en reembalaje el productor puede ser cualquiera
    $query = "SELECT codigo, r_social 
              FROM productores 
              WHERE empresa = :empresa 
              ORDER BY r_social";
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        'empresa' => $empresa
    ]);
    $productores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $productores;
}



