-- Uppercase all user emails. Run once in MySQL/MariaDB.
UPDATE usuarios SET email = UPPER(email);
