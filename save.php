<?php
include 'db.php';
session_start();

// 1. Security: Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    
    // Sanitize and collect all inputs
    // Use the null coalescing operator (??) to prevent "Undefined index" warnings
    $word = trim($_POST['word'] ?? '');
    $ipa = trim($_POST['ipa'] ?? '');
    $form = trim($_POST['word_form'] ?? ''); 
    $level = trim($_POST['level'] ?? ''); 
    $defEn = trim($_POST['definition_en'] ?? '');
    $defVi = trim($_POST['definition_vi'] ?? '');
    $sentence = trim($_POST['example_sentence'] ?? '');
    $synonyms = trim($_POST['synonyms'] ?? '');
    $antonyms = trim($_POST['antonyms'] ?? '');
    
    // Fix for the category_id warning
    $category_raw = $_POST['category_id'] ?? 'null';
    $category_id = ($category_raw === 'null' || empty($category_raw)) ? null : $category_raw;

    if (empty($word)) {
        die("Empty Word");
    }

    // 2. DUPLICATE CHECK: Prevent adding the same word twice for the same user
    $check_stmt = $conn->prepare("SELECT id FROM vocabularies WHERE user_id = ? AND word = ?");
    $check_stmt->bind_param("is", $user_id, $word);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        echo "Duplicate";
        exit();
    }
    $check_stmt->close();

    /**
     * 3. INSERT NEW WORD
     * SQL updated to include synonyms and antonyms columns.
     */
    $sql = "INSERT INTO vocabularies 
            (user_id, word, ipa, word_form, level, definition_en, definition_vi, example_sentence, synonyms, antonyms, category_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    
    /**
     * Bind parameters: 
     * i = integer (user_id)
     * sssssssss = 9 strings (word, ipa, form, level, defEn, defVi, sentence, synonyms, antonyms)
     * i = integer/null (category_id)
     */
    $stmt->bind_param("isssssssssi", 
        $user_id, 
        $word, 
        $ipa, 
        $form, 
        $level, 
        $defEn, 
        $defVi, 
        $sentence,
        $synonyms,
        $antonyms,
        $category_id
    );

    if ($stmt->execute()) {
        // --- DOPAMINE SYSTEM: REWARD XP ---
        // Reward user with +10 XP for expanding their vault
        $xp_reward = 10;
        $xp_stmt = $conn->prepare("UPDATE users SET xp = xp + ? WHERE id = ?");
        $xp_stmt->bind_param("ii", $xp_reward, $user_id);
        $xp_stmt->execute();
        $xp_stmt->close();

        echo "Success";
    } else {
        echo "Error: " . $conn->error;
    }

    $stmt->close();
    $conn->close();
}
?>