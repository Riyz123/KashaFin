-- Actualización incremental de la base de datos en InfinityFree.
--
-- Qué hace:
-- 1) Agrega `users.last_seen_at`, para mostrar en el panel de admin qué
--    usuario está en línea y cuándo fue la última vez activo.
-- 2) Crea la tabla `activity_log` (paquete spatie/laravel-activitylog),
--    que registra cada vez que se crea o se borra un ingreso, gasto,
--    presupuesto, meta, préstamo o gasto compartido — la "Trazabilidad"
--    nueva, tanto el recuadro por sección como la página global.
-- 3) Amplía `support_requests.status` y `chat_messages.role` (eran enums
--    de 2 valores) a texto libre, para el nuevo estado "en_atencion" y el
--    nuevo rol "advisor" del soporte en vivo — un admin/decano ya puede
--    responderle a un estudiante dentro de su mismo chat. Agrega
--    `chat_messages.support_request_id` para enlazar esos mensajes con la
--    solicitud de soporte a la que pertenecen.
--
-- Verificado localmente contra una copia real de kashafin.sql en
-- XAMPP/MariaDB antes de entregar esto: no borra ni modifica ningún dato
-- existente, solo agrega columnas/tablas nuevas y amplía el tipo de dos
-- columnas existentes.
--
-- CÓMO USARLO:
-- 1. En phpMyAdmin: pestaña "Exportar" de tu base de datos completa y
--    descarga una copia primero (red de seguridad, 10 segundos).
-- 2. Pestaña "SQL" de phpMyAdmin (NO "Importar") y pega todo este archivo.
-- 3. Ejecútalo UNA SOLA VEZ.
-- 4. Avísame cuando lo hayas corrido para vaciar este archivo de nuevo.

ALTER TABLE `users`
  ADD `last_seen_at` TIMESTAMP NULL DEFAULT NULL AFTER `approved_at`;

CREATE TABLE `activity_log` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `log_name` VARCHAR(255) DEFAULT NULL,
  `description` TEXT NOT NULL,
  `subject_type` VARCHAR(255) DEFAULT NULL,
  `event` VARCHAR(255) DEFAULT NULL,
  `subject_id` BIGINT(20) UNSIGNED DEFAULT NULL,
  `causer_type` VARCHAR(255) DEFAULT NULL,
  `causer_id` BIGINT(20) UNSIGNED DEFAULT NULL,
  `properties` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`properties`)),
  `batch_uuid` CHAR(36) DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subject` (`subject_type`,`subject_id`),
  KEY `causer` (`causer_type`,`causer_id`),
  KEY `activity_log_log_name_index` (`log_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `support_requests`
  MODIFY `status` VARCHAR(30) NOT NULL DEFAULT 'pendiente';

ALTER TABLE `chat_messages`
  MODIFY `role` VARCHAR(20) NOT NULL;

ALTER TABLE `chat_messages`
  ADD `support_request_id` BIGINT(20) UNSIGNED DEFAULT NULL AFTER `role`;

ALTER TABLE `chat_messages`
  ADD KEY `chat_messages_support_request_id_foreign` (`support_request_id`),
  ADD CONSTRAINT `chat_messages_support_request_id_foreign` FOREIGN KEY (`support_request_id`) REFERENCES `support_requests` (`id`) ON DELETE SET NULL;
