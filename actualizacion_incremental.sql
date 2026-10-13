-- Actualización incremental de la base de datos en InfinityFree.
-- Cubre: préstamos con persona institucional opcional (buscada) o externa
-- (nombre libre). NO borra nada existente.
--
-- El rediseño de gastos compartidos (participantes como cuentas reales,
-- invitación/aceptación, pagos por cuotas) ya quedó aplicado en producción
-- — este script ya NO lo incluye, para no repetir un ALTER TABLE sobre
-- columnas que ya existen (eso da el error "#1091 - No puedo eliminar
-- COLUMN is_paid"). Este es el ÚNICO script incremental vigente.
--
-- CÓMO USARLO:
-- 1. En phpMyAdmin: pestaña "Exportar" de tu base de datos completa y
--    descarga una copia primero (red de seguridad, 10 segundos).
-- 2. Pestaña "SQL" de phpMyAdmin (NO "Importar") y pega todo este archivo.
-- 3. Ejecútalo UNA SOLA VEZ.

-- Préstamos: la otra persona puede ser una cuenta real de KashaFin
-- (buscada) o alguien externo sin cuenta (queda NULL, se sigue usando el
-- nombre libre que ya existía en `person_name`).
ALTER TABLE `loans`
  ADD COLUMN `person_user_id` bigint(20) unsigned DEFAULT NULL AFTER `person_name`,
  ADD KEY `loans_person_user_id_foreign` (`person_user_id`),
  ADD CONSTRAINT `loans_person_user_id_foreign` FOREIGN KEY (`person_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
