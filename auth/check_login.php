<?php

session_start();


if(!isset($_SESSION['login'])){

header("Location: /casino/auth/login.php");
exit;

}

?>