-- MySQL schema and sample data for a new, empty task_management database.
-- Demo credentials: admin@gmail.com / admin123; test@example.com / password.
-- Password values are bcrypt hashes. Change demo credentials before deployment.

CREATE TABLE IF NOT EXISTS `users` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` VARCHAR(255) NOT NULL DEFAULT 'user',
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `users_email_unique` (`email`),
    KEY `users_role_index` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tasks` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `status` VARCHAR(255) NOT NULL DEFAULT 'pending',
    `priority` VARCHAR(255) NOT NULL DEFAULT 'medium',
    `assigned_user_id` BIGINT UNSIGNED NULL,
    `created_by` BIGINT UNSIGNED NOT NULL,
    `due_date` DATETIME NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `tasks_status_index` (`status`),
    KEY `tasks_priority_index` (`priority`),
    KEY `tasks_assigned_user_id_index` (`assigned_user_id`),
    KEY `tasks_created_by_index` (`created_by`),
    KEY `tasks_due_date_index` (`due_date`),
    CONSTRAINT `tasks_assigned_user_id_foreign`
        FOREIGN KEY (`assigned_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `tasks_created_by_foreign`
        FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `task_attachments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `task_id` BIGINT UNSIGNED NOT NULL,
    `file_name` VARCHAR(255) NOT NULL,
    `file_path` TEXT NOT NULL,
    `file_size` BIGINT UNSIGNED NOT NULL,
    `mime_type` VARCHAR(255) NOT NULL,
    `uploaded_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `task_attachments_task_id_index` (`task_id`),
    CONSTRAINT `task_attachments_task_id_foreign`
        FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `task_comments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `task_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `comment` TEXT NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `task_comments_task_id_user_id_index` (`task_id`, `user_id`),
    KEY `task_comments_user_id_index` (`user_id`),
    CONSTRAINT `task_comments_task_id_foreign`
        FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
    CONSTRAINT `task_comments_user_id_foreign`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`, `updated_at`) VALUES
(1, 'Test User', 'test@example.com', '$2y$10$93QH/HyIdnQry84e2UN1NubKMNuepbnKyR9fq2lRgi0vcG7nrB106', 'user', '2026-09-29 09:00:00', '2026-09-29 09:00:00'),
(2, 'Admin', 'admin@gmail.com', '$2y$10$VplxRr.YXHUMW.nP18ADjOJg21fQNNXPqs7sCPVXI68Ku9a9EVBUS', 'admin', '2026-09-29 09:00:00', '2026-09-29 09:00:00'),
(3, 'Avery Johnson', 'avery@example.com', '$2y$10$93QH/HyIdnQry84e2UN1NubKMNuepbnKyR9fq2lRgi0vcG7nrB106', 'user', '2026-09-29 09:00:00', '2026-09-29 09:00:00'),
(4, 'Jordan Lee', 'jordan@example.com', '$2y$10$93QH/HyIdnQry84e2UN1NubKMNuepbnKyR9fq2lRgi0vcG7nrB106', 'user', '2026-09-29 09:00:00', '2026-09-29 09:00:00'),
(5, 'Morgan Chen', 'morgan@example.com', '$2y$10$93QH/HyIdnQry84e2UN1NubKMNuepbnKyR9fq2lRgi0vcG7nrB106', 'user', '2026-09-29 09:00:00', '2026-09-29 09:00:00');

INSERT INTO `tasks` (`id`, `title`, `description`, `status`, `priority`, `assigned_user_id`, `created_by`, `due_date`, `created_at`, `updated_at`) VALUES
(1, 'Sample task 01', 'Sample task 1 for the task management platform.', 'pending', 'low', 1, 2, '2026-09-30 09:00:00', '2026-09-29 09:00:00', '2026-09-29 09:00:00'),
(2, 'Sample task 02', 'Sample task 2 for the task management platform.', 'in_progress', 'medium', 2, 2, '2026-10-01 09:00:00', '2026-09-29 09:00:00', '2026-09-29 09:00:00'),
(3, 'Sample task 03', 'Sample task 3 for the task management platform.', 'completed', 'high', 3, 2, '2026-10-02 09:00:00', '2026-09-29 09:00:00', '2026-09-29 09:00:00'),
(4, 'Sample task 04', 'Sample task 4 for the task management platform.', 'pending', 'low', 4, 2, '2026-10-03 09:00:00', '2026-09-29 09:00:00', '2026-09-29 09:00:00'),
(5, 'Sample task 05', 'Sample task 5 for the task management platform.', 'in_progress', 'medium', 5, 2, '2026-10-04 09:00:00', '2026-09-29 09:00:00', '2026-09-29 09:00:00'),
(6, 'Sample task 06', 'Sample task 6 for the task management platform.', 'completed', 'high', 1, 2, '2026-10-05 09:00:00', '2026-09-29 09:00:00', '2026-09-29 09:00:00'),
(7, 'Sample task 07', 'Sample task 7 for the task management platform.', 'pending', 'low', 2, 2, '2026-10-06 09:00:00', '2026-09-29 09:00:00', '2026-09-29 09:00:00'),
(8, 'Sample task 08', 'Sample task 8 for the task management platform.', 'in_progress', 'medium', 3, 2, '2026-10-07 09:00:00', '2026-09-29 09:00:00', '2026-09-29 09:00:00'),
(9, 'Sample task 09', 'Sample task 9 for the task management platform.', 'completed', 'high', 4, 2, '2026-10-08 09:00:00', '2026-09-29 09:00:00', '2026-09-29 09:00:00'),
(10, 'Sample task 10', 'Sample task 10 for the task management platform.', 'pending', 'low', 5, 2, '2026-10-09 09:00:00', '2026-09-29 09:00:00', '2026-09-29 09:00:00'),
(11, 'Sample task 11', 'Sample task 11 for the task management platform.', 'in_progress', 'medium', 1, 2, '2026-10-10 09:00:00', '2026-09-29 09:00:00', '2026-09-29 09:00:00'),
(12, 'Sample task 12', 'Sample task 12 for the task management platform.', 'completed', 'high', 2, 2, '2026-10-11 09:00:00', '2026-09-29 09:00:00', '2026-09-29 09:00:00'),
(13, 'Sample task 13', 'Sample task 13 for the task management platform.', 'pending', 'low', 3, 2, '2026-10-12 09:00:00', '2026-09-29 09:00:00', '2026-09-29 09:00:00'),
(14, 'Sample task 14', 'Sample task 14 for the task management platform.', 'in_progress', 'medium', 4, 2, '2026-10-13 09:00:00', '2026-09-29 09:00:00', '2026-09-29 09:00:00'),
(15, 'Sample task 15', 'Sample task 15 for the task management platform.', 'completed', 'high', 5, 2, '2026-10-14 09:00:00', '2026-09-29 09:00:00', '2026-09-29 09:00:00');

INSERT INTO `task_comments` (`id`, `task_id`, `user_id`, `comment`, `created_at`) VALUES
(1, 1, 1, 'Sample comment 1 for Sample task 01.', '2026-09-29 09:00:00'),
(2, 2, 2, 'Sample comment 2 for Sample task 02.', '2026-09-29 09:00:00'),
(3, 3, 3, 'Sample comment 3 for Sample task 03.', '2026-09-29 09:00:00'),
(4, 4, 4, 'Sample comment 4 for Sample task 04.', '2026-09-29 09:00:00'),
(5, 5, 5, 'Sample comment 5 for Sample task 05.', '2026-09-29 09:00:00'),
(6, 6, 1, 'Sample comment 6 for Sample task 06.', '2026-09-29 09:00:00'),
(7, 7, 2, 'Sample comment 7 for Sample task 07.', '2026-09-29 09:00:00'),
(8, 8, 3, 'Sample comment 8 for Sample task 08.', '2026-09-29 09:00:00'),
(9, 9, 4, 'Sample comment 9 for Sample task 09.', '2026-09-29 09:00:00'),
(10, 10, 5, 'Sample comment 10 for Sample task 10.', '2026-09-29 09:00:00');