-- Database initialization script for GitHub Smart App
-- This script is automatically executed when the MySQL container starts

-- Create database if it doesn't exist (should already exist from MYSQL_DATABASE env var)
CREATE DATABASE IF NOT EXISTS project_management;
USE project_management;

-- Create gh_issues table (first, as it's referenced by other tables)
CREATE TABLE IF NOT EXISTS gh_issues (
    id INT AUTO_INCREMENT PRIMARY KEY,
    gh_id INT NOT NULL,
    gh_node_id VARCHAR(255) NOT NULL,
    gh_id_url TEXT NOT NULL,
    repo VARCHAR(255) NOT NULL,
    repo_url TEXT NOT NULL,
    gh_project_url TEXT,
    issue_text TEXT NOT NULL,
    client VARCHAR(255),
    assigned_date DATE NOT NULL,
    target_date DATE NULL,
    gh_json LONGTEXT NOT NULL,
    assignee VARCHAR(255) NOT NULL DEFAULT 'UNASSIGNED',
    gh_project VARCHAR(255),
    gh_project_title VARCHAR(255),
    last_updated_at DATETIME NOT NULL,
    closed_at DATETIME NULL,
    gh_state VARCHAR(50) NOT NULL DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_gh_id (gh_id),
    UNIQUE KEY uk_gh_node_id (gh_node_id),
    INDEX idx_assignee (assignee),
    INDEX idx_gh_state (gh_state),
    INDEX idx_assigned_date (assigned_date),
    INDEX idx_gh_project (gh_project)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create gh_projects table
CREATE TABLE IF NOT EXISTS gh_projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    gh_id VARCHAR(255) NOT NULL,
    title VARCHAR(500) NOT NULL,
    closed VARCHAR(10) DEFAULT 'false',
    count_of_issues INT DEFAULT 0,
    url TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_gh_id (gh_id),
    INDEX idx_title (title)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create gh_issue_tags table (without foreign key initially)
CREATE TABLE IF NOT EXISTS gh_issue_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    gh_node_id VARCHAR(255) NOT NULL,
    tag VARCHAR(255) NOT NULL,
    color VARCHAR(7) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_gh_node_id (gh_node_id),
    INDEX idx_tag (tag)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create gh_pinned_issues table (without foreign key initially)
CREATE TABLE IF NOT EXISTS gh_pinned_issues (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    gh_node_id VARCHAR(255) NOT NULL,
    bucket INT DEFAULT 1,
    is_deleted ENUM('YES', 'NO') DEFAULT 'NO',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_gh_node_id (gh_node_id),
    INDEX idx_bucket (bucket)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create additional tables that might be referenced in the application
CREATE TABLE IF NOT EXISTS expense_perm_matrix (
    id INT AUTO_INCREMENT PRIMARY KEY,
    emp_id INT NOT NULL,
    view_perm TINYINT(1) DEFAULT 0,
    create_perm TINYINT(1) DEFAULT 0,
    edit_perm TINYINT(1) DEFAULT 0,
    pay_perm TINYINT(1) DEFAULT 0,
    auth_perm TINYINT(1) DEFAULT 0,
    del_perm TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_emp_id (emp_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS crux_auth (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asset VARCHAR(255) NOT NULL,
    role VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_asset (asset),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create gh_audit table for tracking API operations
CREATE TABLE IF NOT EXISTS gh_audit (
    id INT AUTO_INCREMENT PRIMARY KEY,
    action VARCHAR(255) NOT NULL,
    start_time DATETIME,
    end_time DATETIME,
    status VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_action (action),
    INDEX idx_end_time (end_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create gh_issue_project_status table for storing project board status
CREATE TABLE IF NOT EXISTS gh_issue_project_status (
    id INT AUTO_INCREMENT PRIMARY KEY,
    gh_node_id VARCHAR(255) NOT NULL,
    project_id VARCHAR(255) NOT NULL,
    project_title VARCHAR(500) NOT NULL,
    project_url TEXT,
    status_field_id VARCHAR(255),
    status_field_name VARCHAR(255),
    status_value VARCHAR(255),
    status_color VARCHAR(7),
    item_id VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_gh_node_id (gh_node_id),
    INDEX idx_project_id (project_id),
    INDEX idx_status_field_name (status_field_name),
    INDEX idx_status_value (status_value),
    UNIQUE KEY uk_issue_project (gh_node_id, project_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert some sample data for testing (only if table is empty)
INSERT IGNORE INTO expense_perm_matrix (emp_id, view_perm, create_perm, edit_perm, pay_perm, auth_perm, del_perm) 
VALUES (0, 1, 1, 1, 1, 1, 1);

-- Add foreign key constraints after all tables are created (optional, can be added later)
-- ALTER TABLE gh_issue_tags ADD CONSTRAINT fk_gh_issue_tags_gh_node_id 
--     FOREIGN KEY (gh_node_id) REFERENCES gh_issues(gh_node_id) ON DELETE CASCADE;
-- ALTER TABLE gh_pinned_issues ADD CONSTRAINT fk_gh_pinned_issues_gh_node_id 
--     FOREIGN KEY (gh_node_id) REFERENCES gh_issues(gh_node_id) ON DELETE CASCADE;

-- Show table creation results
SELECT 'Database initialization completed successfully' as status;
SHOW TABLES; 