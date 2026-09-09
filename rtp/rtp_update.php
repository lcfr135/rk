<?php

require_once __DIR__ . "/../config/database.php";

$now = date("Y-m-d H:i:s");

// Ambil semua game
$result = $conn->query("SELECT * FROM rtp_live");

while ($row = $result->fetch_assoc()) {

    if (strtotime($now) >= strtotime($row['next_update'])) {

        $old = (float)$row['rtp'];

        // Perubahan RTP (-3.00% s/d +3.00%)
        $change = rand(-300, 300) / 100;

        // RTP baru
        $new = $old + $change;

        // Batas minimum 40%
        if ($new < 40) {
            $new = 40;
        }

        // Batas maksimum 100%
        if ($new > 100) {
            $new = 100;
        }

        $new = round($new, 2);

        // Status berdasarkan RTP
        if ($new >= 96) {
            $status = "HOT";
        } elseif ($new >= 75) {
            $status = "NORMAL";
        } else {
            $status = "COLD";
        }

        // Update berikutnya 30-60 menit lagi
        $minutes = rand(30, 60);

        $next = date("Y-m-d H:i:s", strtotime("+{$minutes} minutes"));

        $stmt = $conn->prepare("
            UPDATE rtp_live
            SET
                rtp = ?,
                trend = ?,
                status = ?,
                last_update = ?,
                next_update = ?
            WHERE id = ?
        ");

        $stmt->bind_param(
            "ddsssi",
            $new,
            $change,
            $status,
            $now,
            $next,
            $row['id']
        );

        $stmt->execute();
        $stmt->close();
    }
}