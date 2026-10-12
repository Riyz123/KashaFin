-- Actualización incremental de la base de datos en InfinityFree.
-- NO borra nada existente (proveedores de IA, usuarios, gastos, etc.) —
-- solo agrega las tablas/columnas nuevas desde el último despliegue.
--
-- CÓMO USARLO:
-- 1. Antes que nada, en phpMyAdmin: pestaña "Exportar" de tu base de datos
--    completa y descarga una copia (por si algo saliera mal, poder volver
--    atrás). Esto toma 10 segundos y es tu red de seguridad.
-- 2. Pestaña "SQL" de phpMyAdmin (NO "Importar" — este script va en la
--    caja de texto de SQL directamente) y pega todo este archivo.
-- 3. Ejecútalo UNA SOLA VEZ. Si lo corres dos veces, las líneas CREATE
--    TABLE y las dos últimas INSERT fallarán (ya existen) — eso es
--    normal y no daña nada, solo ignóralo.

-- 1) Tabla nueva: prompt editable del asistente (admin/ia)
CREATE TABLE `ai_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `system_prompt` text NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2) Columna nueva: idioma preferido del asistente, por estudiante
ALTER TABLE `user_settings`
  ADD COLUMN `language` VARCHAR(2) NOT NULL DEFAULT 'es' AFTER `theme`;

-- 3) Tabla nueva: facultades
CREATE TABLE `faculties` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `faculties` (`name`, `created_at`, `updated_at`) VALUES
  ('Ingeniería', NOW(), NOW()),
  ('Ciencias de la Salud', NOW(), NOW()),
  ('Negocios', NOW(), NOW()),
  ('Comunicaciones', NOW(), NOW()),
  ('Derecho', NOW(), NOW()),
  ('Arquitectura', NOW(), NOW());

-- 4) users: nuevo rol "decano" en el enum
ALTER TABLE `users`
  MODIFY `role` ENUM('estudiante','decano','admin') NOT NULL DEFAULT 'estudiante';

-- 5) users: facultad, aprobación de solicitud y cambio de contraseña forzado
ALTER TABLE `users`
  ADD COLUMN `faculty_id` bigint unsigned NULL AFTER `role`,
  ADD COLUMN `approved_at` timestamp NULL DEFAULT NULL AFTER `faculty_id`,
  ADD COLUMN `must_change_password` tinyint(1) NOT NULL DEFAULT 0 AFTER `approved_at`;

ALTER TABLE `users`
  ADD CONSTRAINT `users_faculty_id_foreign`
  FOREIGN KEY (`faculty_id`) REFERENCES `faculties` (`id`) ON DELETE SET NULL;

-- 6) Importante: tus cuentas de ESTUDIANTE que ya existían quedan con
-- faculty_id = NULL (no se pierden, pero ningún decano las verá hasta
-- que tengan una facultad). Esta línea les asigna la primera facultad
-- (Ingeniería) como valor por defecto, para que no queden "huérfanas".
-- Si prefieres dejarlas sin facultad y asignarlas tú mismo luego a mano
-- en phpMyAdmin (tabla users, columna faculty_id), borra o comenta esta
-- línea antes de ejecutar el script.
UPDATE `users` SET `faculty_id` = 1 WHERE `role` = 'estudiante' AND `faculty_id` IS NULL;

-- Y marca aprobadas a tus cuentas de estudiante que ya existían (si no,
-- el panel las mostraría como "Pendiente de aprobación" sin necesitarlo).
UPDATE `users` SET `approved_at` = NOW() WHERE `role` = 'estudiante' AND `approved_at` IS NULL;
