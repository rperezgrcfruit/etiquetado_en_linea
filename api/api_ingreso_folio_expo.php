<?php
/**
 * @deprecated REPLACED BY FolioRepository::insertExportacion()
 * Was: session check stub. No longer needed.
 * All backend files now use FolioRepository directly.
 */
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: /packing/index');
    exit();
}