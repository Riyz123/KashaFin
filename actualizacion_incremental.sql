-- Actualización incremental de la base de datos en InfinityFree.
--
-- Sigue pendiente lo de la ronda anterior (préstamos) más lo nuevo de esta
-- ronda (estado en línea). Si ya corriste la parte de préstamos antes,
-- avísame para separar esto en dos entregas — por ahora asumo que no, así
-- que este archivo trae las dos cosas juntas, en orden.
--
-- 1) Amplía `loans.status` de un enum de 2 valores ('pendiente','pagado') a
--    texto libre de hasta 30 caracteres, para los 3 estados nuevos del
--    flujo de confirmación de préstamos (pendiente_confirmacion, rechazado,
--    pago_pendiente_confirmacion), y agrega `counterpart_loan_id` que enlaza
--    cada préstamo con su espejo en la otra cuenta una vez confirmado.
-- 2) Agrega `users.last_seen_at`, para mostrar en el panel de admin qué
--    usuario está en línea y cuándo fue la última vez activo.
--
-- Verificado localmente contra una copia real de kashafin.sql en
-- XAMPP/MariaDB antes de entregar esto: no borra ni modifica ningún dato
-- existente, solo amplía/agrega columnas.
--
-- CÓMO USARLO:
-- 1. En phpMyAdmin: pestaña "Exportar" de tu base de datos completa y
--    descarga una copia primero (red de seguridad, 10 segundos).
-- 2. Pestaña "SQL" de phpMyAdmin (NO "Importar") y pega todo este archivo.
-- 3. Ejecútalo UNA SOLA VEZ.
-- 4. Avísame cuando lo hayas corrido para vaciar este archivo de nuevo.

ALTER TABLE `loans`
  MODIFY `status` VARCHAR(30) NOT NULL DEFAULT 'pendiente';

ALTER TABLE `loans`
  ADD `counterpart_loan_id` BIGINT(20) UNSIGNED DEFAULT NULL AFTER `status`;

ALTER TABLE `loans`
  ADD KEY `loans_counterpart_loan_id_foreign` (`counterpart_loan_id`),
  ADD CONSTRAINT `loans_counterpart_loan_id_foreign` FOREIGN KEY (`counterpart_loan_id`) REFERENCES `loans` (`id`) ON DELETE SET NULL;

ALTER TABLE `users`
  ADD `last_seen_at` TIMESTAMP NULL DEFAULT NULL AFTER `approved_at`;
