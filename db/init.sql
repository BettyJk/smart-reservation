-- MySQL initialization script for classroom reservation system
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('student', 'teacher', 'admin') NOT NULL DEFAULT 'student'
);

CREATE TABLE IF NOT EXISTS classrooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255)
);

CREATE TABLE IF NOT EXISTS seats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    classroom_id INT NOT NULL,
    seat_number VARCHAR(10) NOT NULL,
    pos_x INT,
    pos_y INT,
    FOREIGN KEY (classroom_id) REFERENCES classrooms(id)
);

CREATE TABLE IF NOT EXISTS reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    seat_id INT NOT NULL,
    reservation_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    status ENUM('pending', 'approved', 'cancelled') NOT NULL DEFAULT 'pending',
    confirmation_code VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (seat_id) REFERENCES seats(id)
);

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Ensure classroom_id=1 exists
INSERT IGNORE INTO classrooms (id, name, description) VALUES (1, 'Main Classroom', 'Default classroom with 45 seats');

-- Insert 45 seats for classroom_id=1
INSERT IGNORE INTO seats (classroom_id, seat_number, pos_x, pos_y) VALUES
(1, '1', 1, 1),
(1, '2', 2, 1),
(1, '3', 3, 1),
(1, '4', 4, 1),
(1, '5', 5, 1),
(1, '6', 6, 1),
(1, '7', 7, 1),
(1, '8', 8, 1),
(1, '9', 9, 1),
(1, '10', 10, 1),
(1, '11', 1, 2),
(1, '12', 2, 2),
(1, '13', 3, 2),
(1, '14', 4, 2),
(1, '15', 5, 2),
(1, '16', 6, 2),
(1, '17', 7, 2),
(1, '18', 8, 2),
(1, '19', 9, 2),
(1, '20', 10, 2),
(1, '21', 1, 3),
(1, '22', 2, 3),
(1, '23', 3, 3),
(1, '24', 4, 3),
(1, '25', 5, 3),
(1, '26', 6, 3),
(1, '27', 7, 3),
(1, '28', 8, 3),
(1, '29', 9, 3),
(1, '30', 10, 3),
(1, '31', 1, 4),
(1, '32', 2, 4),
(1, '33', 3, 4),
(1, '34', 4, 4),
(1, '35', 5, 4),
(1, '36', 6, 4),
(1, '37', 7, 4),
(1, '38', 8, 4),
(1, '39', 9, 4),
(1, '40', 10, 4),
(1, '41', 1, 5),
(1, '42', 2, 5),
(1, '43', 3, 5),
(1, '44', 4, 5),
(1, '45', 5, 5);
