<?php
// FILE: tests/db_smoke_test.php

require_once __DIR__ . '/../includes/Database.php';

try {
    $db = new Database();

    // Create scratch table
    $db->execute("DROP TABLE IF EXISTS test_table");
    $db->execute("CREATE TABLE test_table (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL)");

    // Insert
    $inserted = $db->execute("INSERT INTO test_table (name) VALUES (?)", ['test_name']);
    if ($inserted !== 1) {
        echo "FAIL: Insert did not return 1 affected row.\n";
        exit(1);
    }
    $id = $db->insertId();
    if (!$id) {
        echo "FAIL: insertId() returned false or zero.\n";
        exit(1);
    }

    // Select fetchRow
    $row = $db->fetchRow("SELECT * FROM test_table WHERE id = ?", [$id]);
    if (!$row || $row['name'] !== 'test_name') {
        echo "FAIL: fetchRow did not return correct row.\n";
        exit(1);
    }

    // Select fetchAll
    $all = $db->fetchAll("SELECT * FROM test_table WHERE id = ?", [$id]);
    if (count($all) !== 1 || $all[0]['name'] !== 'test_name') {
        echo "FAIL: fetchAll did not return correct rows.\n";
        exit(1);
    }

    // Select fetchColumn
    $name = $db->fetchColumn("SELECT name FROM test_table WHERE id = ?", [$id]);
    if ($name !== 'test_name') {
        echo "FAIL: fetchColumn did not return correct value.\n";
        exit(1);
    }

    // Update
    $updated = $db->execute("UPDATE test_table SET name = ? WHERE id = ?", ['updated_name', $id]);
    if ($updated !== 1) {
        echo "FAIL: Update did not return 1 affected row.\n";
        exit(1);
    }
    $row = $db->fetchRow("SELECT * FROM test_table WHERE id = ?", [$id]);
    if ($row['name'] !== 'updated_name') {
        echo "FAIL: Update did not actually change name.\n";
        exit(1);
    }

    // Delete
    $deleted = $db->execute("DELETE FROM test_table WHERE id = ?", [$id]);
    if ($deleted !== 1) {
        echo "FAIL: Delete did not return 1 affected row.\n";
        exit(1);
    }
    $row = $db->fetchRow("SELECT * FROM test_table WHERE id = ?", [$id]);
    if ($row !== false) {
        echo "FAIL: Delete did not remove row.\n";
        exit(1);
    }

    // Cleanup
    $db->execute("DROP TABLE test_table");

    echo "PASS: All smoke tests passed.\n";
    exit(0);
} catch (Exception $e) {
    echo "FAIL: Exception during smoke test: " . $e->getMessage() . "\n";
    exit(1);
}