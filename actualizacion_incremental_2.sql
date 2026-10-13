-- Segunda actualización incremental de la base de datos en InfinityFree.
-- Cubre TODO lo agregado después de `actualizacion_incremental.sql`: el
-- chat con un asesor, los recordatorios, préstamos, gastos compartidos,
-- el tour de bienvenida y el tamaño de texto. NO borra nada existente.
--
-- CÓMO USARLO:
-- 1. En phpMyAdmin: pestaña "Exportar" de tu base de datos completa y
--    descarga una copia primero (red de seguridad, 10 segundos).
-- 2. Pestaña "SQL" de phpMyAdmin (NO "Importar") y pega todo este archivo.
-- 3. Ejecútalo UNA SOLA VEZ. Si ya corriste `actualizacion_incremental.sql`
--    antes, esto es seguro de aplicar encima. Si lo corres dos veces, las
--    líneas CREATE TABLE y ADD COLUMN fallarán la segunda vez (ya existen)
--    — eso es normal y no daña nada, solo ignóralo.

-- 1) Chat con un asesor / reporte de incidencias (sección "Ayuda y
--    soporte" y el chatbot: "hablar con un asesor")
CREATE TABLE `support_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `type` enum('asesor','incidencia') NOT NULL,
  `message` text NOT NULL,
  `status` enum('pendiente','atendido') NOT NULL DEFAULT 'pendiente',
  `attended_by_id` bigint(20) unsigned DEFAULT NULL,
  `attended_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `support_requests_user_id_foreign` (`user_id`),
  KEY `support_requests_attended_by_id_foreign` (`attended_by_id`),
  CONSTRAINT `support_requests_attended_by_id_foreign` FOREIGN KEY (`attended_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `support_requests_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2) Notificaciones en la app (recordatorios de inactividad, confirmación
--    de ingreso fijo, y los avisos de soporte para decano/admin) — tabla
--    estándar del sistema de notificaciones de Laravel.
CREATE TABLE `notifications` (
  `id` char(36) NOT NULL,
  `type` varchar(255) NOT NULL,
  `notifiable_type` varchar(255) NOT NULL,
  `notifiable_id` bigint(20) unsigned NOT NULL,
  `data` text NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3) Préstamos entre personas (/prestamos)
CREATE TABLE `loans` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `person_name` varchar(255) NOT NULL,
  `direction` enum('prestado','recibido') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('pendiente','pagado') NOT NULL DEFAULT 'pendiente',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `loans_user_id_foreign` (`user_id`),
  CONSTRAINT `loans_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4) Gastos compartidos (/gastos-compartidos) y sus participantes
CREATE TABLE `shared_expenses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `category_id` bigint(20) unsigned DEFAULT NULL,
  `expense_id` bigint(20) unsigned DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `my_share` decimal(10,2) NOT NULL DEFAULT 0.00,
  `date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `shared_expenses_user_id_foreign` (`user_id`),
  KEY `shared_expenses_category_id_foreign` (`category_id`),
  KEY `shared_expenses_expense_id_foreign` (`expense_id`),
  CONSTRAINT `shared_expenses_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `shared_expenses_expense_id_foreign` FOREIGN KEY (`expense_id`) REFERENCES `expenses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `shared_expenses_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `shared_expense_participants` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `shared_expense_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `amount_owed` decimal(10,2) NOT NULL,
  `is_paid` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `shared_expense_participants_shared_expense_id_foreign` (`shared_expense_id`),
  CONSTRAINT `shared_expense_participants_shared_expense_id_foreign` FOREIGN KEY (`shared_expense_id`) REFERENCES `shared_expenses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5) user_settings: toggles de recordatorios, tour de bienvenida y
--    tamaño de texto.
ALTER TABLE `user_settings`
  ADD COLUMN `font_size` VARCHAR(255) NOT NULL DEFAULT 'normal' AFTER `theme`,
  ADD COLUMN `notify_inactivity_by_email` TINYINT(1) NOT NULL DEFAULT 1 AFTER `notify_low_liquidity_by_email`,
  ADD COLUMN `notify_income_confirmation_by_email` TINYINT(1) NOT NULL DEFAULT 1 AFTER `notify_inactivity_by_email`,
  ADD COLUMN `notify_weekly_summary_by_email` TINYINT(1) NOT NULL DEFAULT 1 AFTER `notify_income_confirmation_by_email`,
  ADD COLUMN `onboarding_completed_at` TIMESTAMP NULL DEFAULT NULL AFTER `starting_balance`;

-- 6) Opcional: si no quieres que tus estudiantes YA existentes vean el
-- tour de bienvenida de nuevo (está pensado para cuentas nuevas), marca
-- a todos como que ya lo completaron. Si prefieres que lo vean una vez,
-- borra o comenta esta línea antes de ejecutar el script.
UPDATE `user_settings` SET `onboarding_completed_at` = NOW() WHERE `onboarding_completed_at` IS NULL;
