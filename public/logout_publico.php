<?php
session_start();

unset($_SESSION['public_acesso'], $_SESSION['public_planilha_id'], $_SESSION['public_comum_id'], $_SESSION['public_comum']);
header('Location: ../login.php');
exit;
