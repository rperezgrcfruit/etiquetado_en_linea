<?php

require_once __DIR__ . '/config/security_headers.php';

session_start();
session_destroy();
header("Location: index.php");