-- Create notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('order', 'product', 'review', 'harvest', 'trust', 'complaint', 'verification', 'promotion', 'system') DEFAULT 'order',
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    icon VARCHAR(50) DEFAULT 'bi-bell',
    color VARCHAR(20) DEFAULT 'primary',
    url VARCHAR(255) NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Add index for faster queries
CREATE INDEX idx_user_id ON notifications(user_id);
CREATE INDEX idx_type ON notifications(type);
CREATE INDEX idx_is_read ON notifications(is_read);
CREATE INDEX idx_created_at ON notifications(created_at);
