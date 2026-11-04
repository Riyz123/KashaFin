-- Actualización incremental de la base de datos en InfinityFree.
--
-- Qué hace:
-- 1) Gastos fijos y variables (igual que ya existía para ingresos): agrega
--    `expenses.type`, `expenses.frequency`, `expenses.next_occurrence_date`
--    y `expenses.parent_expense_id`, para que un gasto recurrente (alquiler,
--    suscripciones) se genere solo cada periodo en vez de tener que
--    registrarlo a mano cada vez.
-- 2) Amortización de deuda por plazos: agrega `loans.paid_amount` (cuánto
--    se ha pagado de la deuda hasta ahora) y `loans.last_overdue_notified_at`
--    (para no mandar el recordatorio de "deuda vencida" más de una vez
--    cada 3 días), y crea la tabla `loan_amortizations` (cada solicitud de
--    pago parcial que un deudor manda y el acreedor confirma o rechaza).
-- 3) Apartados del sistema: crea la tabla `feature_flags`, que le permite
--    al admin master activar o desactivar secciones completas de la app
--    (Ingresos, Gastos, Presupuesto, Metas, Préstamos, Gastos compartidos,
--    Asistente IA) sin borrar ningún dato — solo bloquea el acceso
--    mientras está apagada. Se inserta una fila por cada sección, todas
--    activas por defecto (nada cambia para los estudiantes hasta que el
--    admin apague algo a propósito).
--
-- Verificado localmente contra una copia real de kashafin.sql en
-- XAMPP/MariaDB 10.4 antes de entregar esto: no borra ni modifica ningún
-- dato existente, solo agrega columnas/tablas nuevas.
--
-- CÓMO USARLO:
-- 1. En phpMyAdmin: pestaña "Exportar" de tu base de datos completa y
--    descarga una copia primero (red de seguridad, 10 segundos).
-- 2. Pestaña "SQL" de phpMyAdmin (NO "Importar") y pega todo este archivo.
-- 3. Ejecútalo UNA SOLA VEZ.
-- 4. Avísame cuando lo hayas corrido para vaciar este archivo de nuevo.

-- 1) Gastos fijos y variables -------------------------------------------

ALTER TABLE `expenses`
  ADD `parent_expense_id` BIGINT(20) UNSIGNED DEFAULT NULL AFTER `category_id`;

ALTER TABLE `expenses`
  ADD `type` ENUM('fijo','variable') NOT NULL DEFAULT 'variable' AFTER `amount`;

ALTER TABLE `expenses`
  ADD `frequency` ENUM('semanal','quincenal','mensual') DEFAULT NULL AFTER `type`;

ALTER TABLE `expenses`
  ADD `next_occurrence_date` DATE DEFAULT NULL AFTER `frequency`;

ALTER TABLE `expenses`
  ADD KEY `expenses_parent_expense_id_foreign` (`parent_expense_id`),
  ADD CONSTRAINT `expenses_parent_expense_id_foreign` FOREIGN KEY (`parent_expense_id`) REFERENCES `expenses` (`id`) ON DELETE SET NULL;

ALTER TABLE `expenses`
  ADD KEY `expenses_type_next_occurrence_date_index` (`type`,`next_occurrence_date`);

-- 2) Amortización de deuda por plazos -------------------------------------

ALTER TABLE `loans`
  ADD `paid_amount` DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER `amount`;

ALTER TABLE `loans`
  ADD `last_overdue_notified_at` TIMESTAMP NULL DEFAULT NULL AFTER `due_date`;

CREATE TABLE `loan_amortizations` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `loan_id` BIGINT(20) UNSIGNED NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `status` ENUM('pendiente','aceptado','rechazado') NOT NULL DEFAULT 'pendiente',
  `resolved_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `loan_amortizations_loan_id_foreign` (`loan_id`),
  CONSTRAINT `loan_amortizations_loan_id_foreign` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3) Apartados del sistema (activar/desactivar secciones) ----------------

CREATE TABLE `feature_flags` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `key` VARCHAR(255) NOT NULL,
  `label` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `is_enabled` TINYINT(1) NOT NULL DEFAULT 1,
  `disabled_at` TIMESTAMP NULL DEFAULT NULL,
  `disabled_by` BIGINT(20) UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `feature_flags_key_unique` (`key`),
  KEY `feature_flags_disabled_by_foreign` (`disabled_by`),
  CONSTRAINT `feature_flags_disabled_by_foreign` FOREIGN KEY (`disabled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `feature_flags` (`key`, `label`, `description`, `is_enabled`, `created_at`, `updated_at`) VALUES
('incomes', 'Ingresos', 'Registrar y ver ingresos (fijos y variables).', 1, NOW(), NOW()),
('expenses', 'Gastos', 'Registrar y ver gastos (fijos y variables).', 1, NOW(), NOW()),
('budgets', 'Presupuesto', 'Definir y ver presupuestos por categoría.', 1, NOW(), NOW()),
('savings_goals', 'Metas', 'Crear metas de ahorro y registrar aportes.', 1, NOW(), NOW()),
('loans', 'Préstamos', 'Registrar préstamos, confirmaciones y amortizaciones de deuda.', 1, NOW(), NOW()),
('shared_expenses', 'Gastos compartidos', 'Dividir gastos con otros estudiantes.', 1, NOW(), NOW()),
('ai_chat', 'Asistente IA', 'El chat/voz con IA (incluye todas sus acciones: agregar gasto, ingreso, préstamo, etc. por chat).', 1, NOW(), NOW());
