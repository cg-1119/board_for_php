CREATE TABLE attachments
(
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    file_name VARCHAR(255),
    file_path VARCHAR(255),
    FOREIGN KEY (post_id) REFERENCES posts (id) ON DELETE CASCADE
) DEFAULT CHARSET=utf8;