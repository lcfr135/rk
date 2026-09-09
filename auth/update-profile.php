<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

require_once __DIR__ . "/../config/database.php";


if (!isset($_SESSION['id'])) {

    header("Location: /casino/auth/login.php");
    exit;

}


$id = $_SESSION['id'];


$fullname = trim($_POST['fullname'] ?? "");



if ($fullname === "") {

    $_SESSION['toast'] = [
        "title" => "Gagal",
        "message" => "Nama lengkap tidak boleh kosong.",
        "type" => "error"
    ];

    header("Location: /casino/member/profile.php");
    exit;

}



// Update database
$stmt = $conn->prepare("
    UPDATE users 
    SET fullname = ?
    WHERE id = ?
");


$stmt->bind_param(
    "si",
    $fullname,
    $id
);



if ($stmt->execute()) {


    /*
        Simpan aktivitas user
    */

    $activity = "Profil berhasil diperbarui";
    $icon = "👤";


    $log = $conn->prepare("
        INSERT INTO user_activity
        (
            user_id,
            activity,
            icon
        )
        VALUES (?,?,?)
    ");


    $log->bind_param(
        "iss",
        $id,
        $activity,
        $icon
    );


    $log->execute();



    /*
        Update session
    */

    $_SESSION['fullname'] = $fullname;



    $_SESSION['toast'] = [
        "title" => "Berhasil",
        "message" => "Profil berhasil diperbarui.",
        "type" => "success"
    ];



    header(
        "Location: /casino/member/profile.php"
    );

    exit;



} else {


    $_SESSION['toast'] = [
        "title" => "Gagal",
        "message" => "Profil gagal diperbarui.",
        "type" => "error"
    ];


    header(
        "Location: /casino/member/profile.php"
    );

    exit;

}