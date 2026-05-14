<?php
$password = 'test';
$hash = '$2y$10$gKHnUdXwNqL5nzc7v7L3wuVjwQhZ5dUg8K1k5M9pZ8dW3F7rX9Y0C';
echo "Testing password verification:\n";
echo "Password: " . $password . "\n";
echo "Hash: " . $hash . "\n";
echo "Result: " . (password_verify($password, $hash) ? "VALID" : "INVALID") . "\n";
