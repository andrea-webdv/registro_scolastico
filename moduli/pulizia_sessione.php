<?php
function Pulizia_sessione(){
    session_start();
    if (!empty($_SESSION)) {
        $_SESSION = array();
    }
    session_destroy();
    session_start();
    echo 'sessione pulita';
}
?>