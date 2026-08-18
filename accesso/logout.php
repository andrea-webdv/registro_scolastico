<?php
session_start();
if ( !empty($_SESSION)){
    $_SESSION = array();
}
echo "<h1>benvenuto al logout</h1>";
header("Location: ../index.php");
?>