<?php
include 'db.php';
$word = $_GET['word'] ?? '';
$res = $conn->query("SELECT level FROM word_levels WHERE word = '$word'");
if($row = $res->fetch_assoc()) {
    echo $row['level'];
} else {
    echo "C1+";
}
?>