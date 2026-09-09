<?php

session_start();

require_once "../config/database.php";


if (!isset($_SESSION['id'])) {
    header("Location: /casino/auth/login.php");
    exit;
}


$user_id = $_SESSION['id'];


$old_password     = $_POST['old_password'] ?? '';
$new_password     = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';



// Validasi kosong
if ($old_password == '' || $new_password == '' || $confirm_password == '') {

    $_SESSION['toast'] = [
        "title" => "Gagal",
        "message" => "Semua password wajib diisi.",
        "type" => "error"
    ];

    header("Location: /casino/member/profile.php");
    exit;

}



// Validasi password baru
if ($new_password !== $confirm_password) {
    $_SESSION['toast'] = [
        'title' => 'Gagal',
        'message' => 'Password baru dan konfirmasi password tidak sama.',
        'type' => 'error'
    ];

    header("Location: /casino/member/profile.php");
    exit;
}



// Ambil password lama user
$stmt = $conn->prepare("
    SELECT password 
    FROM users 
    WHERE id = ?
");

$stmt->bind_param("i", $user_id);

$stmt->execute();

$result = $stmt->get_result();

$user = $result->fetch_assoc();



if (!$user) {

    session_destroy();

    header("Location: /casino/auth/login.php");
    exit;

}



// Cek password lama
if (!password_verify($old_password, $user['password'])) {


    $_SESSION['toast'] = [
        "title" => "Gagal",
        "message" => "Password lama salah.",
        "type" => "error"
    ];


    header("Location: /casino/member/profile.php");
    exit;

}



// Buat password baru
$new_hash = password_hash(
    $new_password,
    PASSWORD_DEFAULT
);



// Update password
$stmt = $conn->prepare("
    UPDATE users
    SET password = ?, password_updated_at = NOW()
    WHERE id = ?
");


$stmt->bind_param(
    "si",
    $new_hash,
    $user_id
);



if($stmt->execute()){


    $log = $conn->prepare("
        INSERT INTO user_activity
        (user_id, activity, icon)
        VALUES (?, ?, ?)
    ");


    $activity = "Password berhasil diubah";
    $icon = "🔑";


    $log->bind_param(
        "iss",
        $user_id,
        $activity,
        $icon
    );


    $log->execute();



    $_SESSION['toast'] = [
        "title"=>"Berhasil",
        "message"=>"Password berhasil diperbarui",
        "type"=>"success"
    ];


}



header("Location: /casino/member/profile.php");
exit;