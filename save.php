<?php

// =========================================================
// save.php
// Vocab AI Pro - Save Vocabulary
//
// Chức năng:
// - Lưu từ vựng vào database
// - Chống duplicate
// - Hỗ trợ category
// - Hỗ trợ nuance + collocations
// - Reward XP
//
// NOTE:
// File này được dùng bởi:
// - index.php
// - essay_extract.php
//
// process_essay.php hiện là flow cũ / legacy
// =========================================================

// =========================================================
// LOAD DATABASE
// =========================================================

include 'db.php';

session_start();

// =========================================================
// CHECK LOGIN
// =========================================================

if (!isset($_SESSION['user_id'])) {

    die("Unauthorized");
}

// =========================================================
// CHỈ CHO PHÉP POST
// =========================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    die("Method not allowed");
}

// =========================================================
// USER INFO
// =========================================================

$user_id = (int) $_SESSION['user_id'];

// =========================================================
// HELPER FUNCTION
// =========================================================

/**
 * Hàm làm sạch text input.
 * Giúp tránh:
 * - khoảng trắng thừa
 * - null
 * - lỗi warning
 */
function clean_input($value) {

    return trim((string) ($value ?? ''));
}

// =========================================================
// LẤY DỮ LIỆU
// =========================================================

$word = clean_input($_POST['word']);

$ipa = clean_input($_POST['ipa']);

$word_form = clean_input($_POST['word_form']);

$level = clean_input($_POST['level']);

$definition_en = clean_input($_POST['definition_en']);

$definition_vi = clean_input($_POST['definition_vi']);

$example_sentence = clean_input($_POST['example_sentence']);

$synonyms = clean_input($_POST['synonyms']);

$antonyms = clean_input($_POST['antonyms']);

$nuance = trim($_POST['nuance'] ?? '');

$collocations = trim($_POST['collocations'] ?? '');

// =========================================================
// CATEGORY
// =========================================================

/*
|--------------------------------------------------------------------------
| category_id:
| - "null" => NULL
| - "" => NULL
| - số => category_id
|--------------------------------------------------------------------------
*/

$category_raw = $_POST['category_id'] ?? 'null';

$category_id =
    ($category_raw === 'null' || $category_raw === '')
    ? null
    : (int) $category_raw;

// =========================================================
// VALIDATE WORD
// =========================================================

if ($word === '') {

    die("Empty Word");
}

// =========================================================
// DEFAULT WORD FORM
// =========================================================

/*
|--------------------------------------------------------------------------
| Tránh lưu rỗng để dễ debug dữ liệu AI
|--------------------------------------------------------------------------
*/

if ($word_form === '') {

    $word_form = 'unknown';
}

// =========================================================
// DUPLICATE CHECK
// =========================================================

/*
|--------------------------------------------------------------------------
| Không cho cùng 1 user lưu trùng từ
|--------------------------------------------------------------------------
*/

$check_sql = "
    SELECT id
    FROM vocabularies
    WHERE user_id = ?
    AND word = ?
";

$check_stmt = $conn->prepare($check_sql);

$check_stmt->bind_param(
    "is",
    $user_id,
    $word
);

$check_stmt->execute();

$check_result = $check_stmt->get_result();

// =========================================================
// DUPLICATE FOUND
// =========================================================

if ($check_result->num_rows > 0) {

    echo "Duplicate";

    exit();
}

$check_stmt->close();

// =========================================================
// INSERT VOCABULARY
// =========================================================

/*
|--------------------------------------------------------------------------
| Đã hỗ trợ:
| - nuance
| - collocations
|--------------------------------------------------------------------------
*/

$insert_sql = "
    INSERT INTO vocabularies (

        user_id,
        word,
        ipa,
        word_form,
        level,

        definition_en,
        definition_vi,

        nuance,
        collocations,

        example_sentence,

        synonyms,
        antonyms,

        category_id

    )

    VALUES (

        ?, ?, ?, ?, ?,
        ?, ?,
        ?, ?,
        ?,
        ?, ?,
        ?

    )
";

// =========================================================
// PREPARE STATEMENT
// =========================================================

$stmt = $conn->prepare($insert_sql);

// =========================================================
// BIND PARAMETERS
// =========================================================

/*
|--------------------------------------------------------------------------
| Types:
| i = integer
| s = string
|--------------------------------------------------------------------------
*/

$stmt->bind_param(

    "isssssssssssi",

    $user_id,

    $word,
    $ipa,
    $word_form,
    $level,

    $definition_en,
    $definition_vi,

    $nuance,
    $collocations,

    $example_sentence,

    $synonyms,
    $antonyms,

    $category_id
);

// =========================================================
// EXECUTE INSERT
// =========================================================

if ($stmt->execute()) {

    // =====================================================
    // REWARD XP
    // =====================================================

    /*
    |--------------------------------------------------------------------------
    | Reward user khi thêm từ mới
    |--------------------------------------------------------------------------
    */

    $xp_reward = 10;

    $xp_sql = "
        UPDATE users
        SET xp = xp + ?
        WHERE id = ?
    ";

    $xp_stmt = $conn->prepare($xp_sql);

    $xp_stmt->bind_param(
        "ii",
        $xp_reward,
        $user_id
    );

    $xp_stmt->execute();

    $xp_stmt->close();

    // =====================================================
    // SUCCESS
    // =====================================================

    echo "Success";

} else {

    // =====================================================
    // DATABASE ERROR
    // =====================================================

    echo "Error: " . $conn->error;
}

// =========================================================
// CLOSE CONNECTIONS
// =========================================================

$stmt->close();

$conn->close();

?>
