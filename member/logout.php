<?php
session_start();
require_once "../config/database.php";

if(isset($_SESSION['id'])){

    $stmt = $conn->prepare("
        UPDATE users
        SET last_active = NOW()
        WHERE id = ?
    ");

    $stmt->bind_param("i", $_SESSION['id']);
    $stmt->execute();
}

session_destroy();

$online = false;

if(!empty($user['last_seen'])){

    $diff = time() - strtotime($user['last_seen']);

    if($diff <= 60){

        $online = true;

        $last_active = "Aktif sekarang";

    }elseif($diff < 3600){

        $last_active = "Aktif ".floor($diff/60)." menit lalu";

    }elseif($diff < 86400){

        $last_active = "Aktif ".floor($diff/3600)." jam lalu";

    }else{

        $last_active = "Aktif ".floor($diff/86400)." hari lalu";

    }

}else{

    $last_active = "Belum ada aktivitas";

}

header("Location: /casino/beranda.html");
exit;