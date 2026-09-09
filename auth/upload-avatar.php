<?php

session_start();

header("Content-Type: application/json");

require_once __DIR__ . "/../config/database.php";


/* ==============================
   CEK LOGIN
============================== */

if (!isset($_SESSION['id'])) {

    echo json_encode([
        "success" => false,
        "message" => "Anda belum login."
    ]);

    exit;
}


$user_id = (int) $_SESSION['id'];


/* ==============================
   CEK FILE
============================== */

if (!isset($_FILES['avatar'])) {

    echo json_encode([
        "success" => false,
        "message" => "Foto belum dipilih."
    ]);

    exit;
}


$file = $_FILES['avatar'];


/* cek error upload */

if ($file['error'] !== UPLOAD_ERR_OK) {

    echo json_encode([
        "success" => false,
        "message" => "Upload foto gagal."
    ]);

    exit;
}


/* ==============================
   BATAS UKURAN
============================== */

if ($file['size'] > 5 * 1024 * 1024) {

    echo json_encode([
        "success" => false,
        "message" => "Ukuran foto maksimal 5 MB."
    ]);

    exit;
}


/* ==============================
   CEK MIME
============================== */

$finfo = new finfo(FILEINFO_MIME_TYPE);

$mime = $finfo->file($file['tmp_name']);


$allowed = [

    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp'

];


if (!isset($allowed[$mime])) {

    echo json_encode([
        "success" => false,
        "message" => "Format foto tidak didukung."
    ]);

    exit;
}


/* ==============================
   FOLDER
============================== */

$uploadDir =
    __DIR__ . "/../uploads/avatars/";


if (!is_dir($uploadDir)) {

    if (!mkdir($uploadDir, 0755, true)) {

        echo json_encode([
            "success" => false,
            "message" => "Folder upload tidak dapat dibuat."
        ]);

        exit;
    }
}


/* ==============================
   NAMA FILE
============================== */

$extension = $allowed[$mime];

$filename =
    "avatar_" .
    $user_id .
    "_" .
    bin2hex(random_bytes(8)) .
    "." .
    $extension;


$target =
    $uploadDir . $filename;


/* ==============================
   SIMPAN FILE
============================== */

if (!move_uploaded_file(
    $file['tmp_name'],
    $target
)) {

    echo json_encode([
        "success" => false,
        "message" => "Gagal menyimpan foto."
    ]);

    exit;
}


/* ==============================
   AMBIL AVATAR LAMA
============================== */

$stmt = $conn->prepare("
    SELECT avatar
    FROM users
    WHERE id = ?
");

$stmt->bind_param(
    "i",
    $user_id
);

$stmt->execute();

$result = $stmt->get_result();

$oldAvatar = null;

if ($row = $result->fetch_assoc()) {

    $oldAvatar = $row['avatar'];

}


/* ==============================
   UPDATE DATABASE
============================== */

$stmt = $conn->prepare("
    UPDATE users
    
    SET avatar = ?
    WHERE id = ?
");

$stmt->bind_param(
    "si",
    $filename,
    $user_id
);


if (!$stmt->execute()) {

    /* hapus file baru jika database gagal */

    if (file_exists($target)) {
        unlink($target);
    }


    echo json_encode([
        "success" => false,
        "message" => "Database gagal diperbarui."
    ]);

    exit;
}


/* ==============================
   HAPUS AVATAR LAMA
============================== */

if (
    !empty($oldAvatar) &&
    $oldAvatar !== $filename
) {

    $oldPath =
        $uploadDir . basename($oldAvatar);

    if (file_exists($oldPath)) {
        unlink($oldPath);
    }

}


/* ==============================
   CATAT AKTIVITAS
============================== */

$activity_icon = "📷";
$activity_text = "Mengubah foto profil";

$logActivity = $conn->prepare("
    INSERT INTO user_activity (user_id, icon, activity, created_at)
    VALUES (?, ?, ?, NOW())
");

$logActivity->bind_param(
    "iss",
    $user_id,
    $activity_icon,
    $activity_text
);

$logActivity->execute();


/* ==============================
   RESPONSE
============================== */

$avatar_url = "../uploads/avatars/" . $filename;

echo json_encode([
    "success"     => true,
    "message"     => "Foto profil berhasil diperbarui.",
    "avatar_url"  => $avatar_url,
    "activity"    => [
        "icon"       => $activity_icon,
        "activity"   => $activity_text,
        "created_at" => date("d M Y, H:i")
    ]
]);

exit;