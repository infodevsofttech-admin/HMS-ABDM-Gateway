<?php
$password = 'test';
$hash = '$2y$10$wfxbSz7jhY1CG4ejFqkRpeyKX95o7TQmnwnKwdbWYQ4lB9/58zFS6';
echo password_verify($password, $hash) ? 'VALID' : 'INVALID';
echo PHP_EOL;
