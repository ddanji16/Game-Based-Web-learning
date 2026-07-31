<?php

    session_start();
    session_destroy();
    header("Location: Form-folder/login.php");
    exit;
    
?>
