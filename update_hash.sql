UPDATE abdm_hospital_users SET password_hash = '$2y$10$wfxbSz7jhY1CG4ejFqkRpeyKX95o7TQmnwnKwdbWYQ4lB9/58zFS6' WHERE username = 'admin';
SELECT id, username, role FROM abdm_hospital_users WHERE username = 'admin';
