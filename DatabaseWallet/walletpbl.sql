CREATE DATABASE digital_wallet;

USE digital_wallet;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    contact VARCHAR(15),
    balance DECIMAL(10, 2)
);

select * from users;
SHOW TABLES;
select * from transactions;