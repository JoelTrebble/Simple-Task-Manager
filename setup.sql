-- Create the tasks database
CREATE DATABASE IF NOT EXISTS task_manager;

-- Use the tasks database
USE task_manager;

-- Create the tasks table
CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    completed BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Some default values
INSERT INTO tasks (title, completed) VALUES
    ('Practice JavaScript', FALSE),
    ('Build a portfolio project', FALSE),
    ('Prepare for interview', FALSE);