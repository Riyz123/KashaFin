-- Actualización incremental de la base de datos en InfinityFree.
-- Cubre el rediseño de gastos compartidos: participantes como cuentas reales
-- de KashaFin (con invitación/aceptación) y pagos por cuotas con
-- trazabilidad de deuda. NO borra nada existente.
--
-- Este es el ÚNICO script incremental vigente — los anteriores quedaron
-- incorporados al `kashafin.sql` completo (que ya se volvió a subir con
-- todo, incluido esto). Solo hace falta correr este si tu base de datos
-- ya tenía todo lo anterior (facultades, decanos, soporte, préstamos,
-- recordatorios, etc.) y únicamente le falta el rediseño de gastos
-- compartidos.
--
-- CÓMO USARLO:
-- 1. En phpMyAdmin: pestaña "Exportar" de tu base de datos completa y
--    descarga una copia primero (red de seguridad, 10 segundos).
-- 2. Pestaña "SQL" de phpMyAdmin (NO "Importar") y pega todo este archivo.
-- 3. Ejecútalo UNA SOLA VEZ.

-- 1) shared_expense_participants: ahora el participante es una cuenta real
--    (user_id), con estado de invitación y cuota diaria opcional.
ALTER TABLE `shared_expense_participants`
  ADD COLUMN `user_id` bigint(20) unsigned DEFAULT NULL AFTER `shared_expense_id`,
  ADD COLUMN `status` ENUM('pendiente','aceptado','rechazado') NOT NULL DEFAULT 'pendiente' AFTER `amount_owed`,
  ADD COLUMN `daily_commitment` decimal(10,2) DEFAULT NULL AFTER `status`,
  ADD COLUMN `committed_at` timestamp NULL DEFAULT NULL AFTER `daily_commitment`,
  DROP COLUMN `is_paid`;

ALTER TABLE `shared_expense_participants`
  ADD KEY `shared_expense_participants_user_id_foreign` (`user_id`),
  ADD CONSTRAINT `shared_expense_participants_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

-- 2) Nueva tabla: el historial de pagos que cada participante va
--    registrando hacia su parte (reemplaza el toggle "pagado/no pagado").
CREATE TABLE `shared_expense_contributions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `participant_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `date` date NOT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `shared_expense_contributions_participant_id_foreign` (`participant_id`),
  CONSTRAINT `shared_expense_contributions_participant_id_foreign` FOREIGN KEY (`participant_id`) REFERENCES `shared_expense_participants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Nota: si ya tenías gastos compartidos creados con el formato viejo
-- (participantes por nombre libre, sin cuenta), esas filas se quedan
-- intactas — su `user_id` queda en NULL y la app sigue mostrando el
-- `name` guardado como respaldo. Simplemente no podrán aceptar/rechazar
-- ni registrar pagos nuevos hasta que se vuelvan a crear como gastos
-- compartidos con una cuenta real.
