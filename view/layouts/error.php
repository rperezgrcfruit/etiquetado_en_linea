<?php

$mensaje = $_GET['mensaje'] ?? 'Ocurrió un error inesperado.';


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GrcFruit SPA - Error</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8d7da;
            color: #721c24;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }
        h1 {
            font-size: 2em;
        }
        p {
            font-size: 1.2em;
        }
        a {
            margin-top: 20px;
            text-decoration: none;
            color: #721c24;
            border: 1px solid #721c24;
            padding: 10px 20px;
            border-radius: 5px;
        }
        a:hover {
            background-color: #721c24;
            color: #fff;
        }
    </style>
</head>
<body>
    <h1>Error</h1>
    <p><?php echo htmlspecialchars($mensaje); ?></p>
    <a href="/">Volver al inicio</a>
    
</body>
</html>