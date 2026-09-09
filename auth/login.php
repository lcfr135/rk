<?php

session_start();

include "../config/database.php";

$login    = $_POST['login'] ?? '';
$password = $_POST['password'] ?? '';

// Cari user berdasarkan username atau email
$stmt = $conn->prepare("
    SELECT * FROM users
    WHERE username = ? OR email = ?
");

$stmt->bind_param("ss", $login, $login);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows > 0) {

    $user = $result->fetch_assoc();

    if (password_verify($password, $user['password'])) {

        // Update waktu login terakhir
        $stmtUpdate = $conn->prepare("
            UPDATE users
            SET last_login = NOW()
            WHERE id = ?
        ");
        $stmtUpdate->bind_param("i", $user['id']);
        $stmtUpdate->execute();
        $stmtUpdate->close();

        // Simpan session
        $_SESSION['login']    = true;
        $_SESSION['id']       = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email']    = $user['email'];

        // Redirect ke halaman member
        header("Location: /casino/member/member.php");
        exit;

    } else {

        echo "
        <script>
            alert('Password salah');
            history.back();
        </script>
        ";

    }

} else {

    echo "
    <script>
        alert('Username atau Email tidak ditemukan');
        history.back();
    </script>
    ";

}

$stmt->close();
$conn->close();

?>