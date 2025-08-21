<?php
// Redirigir al sistema TCPDF configurable
if (isset($_GET['id'])) {
    header("Location: tcpdf-certificate-configurable.php?id=" . intval($_GET['id']));
    exit();
} else {
    die("Error: ID de documento no proporcionado");
}
?>
