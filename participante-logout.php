<?php
require_once 'participante-auth.php';

$auth = new ParticipanteAuth();
$auth->logout();

// Redirigir al login
header('Location: participante-login.php');
exit();
?>
