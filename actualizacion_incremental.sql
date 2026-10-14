-- Actualización incremental de la base de datos en InfinityFree.
--
-- Qué hace: agrega `users.last_seen_at`, para mostrar en el panel de admin
-- qué usuario está en línea y cuándo fue la última vez activo. (La parte de
-- préstamos de la ronda anterior ya la corriste, así que este archivo solo
-- trae lo nuevo.)
--
-- Verificado localmente contra una copia real de kashafin.sql en
-- XAMPP/MariaDB antes de entregar esto: no borra ni modifica ningún dato
-- existente, solo agrega una columna nueva.
--
-- CÓMO USARLO:
-- 1. En phpMyAdmin: pestaña "Exportar" de tu base de datos completa y
--    descarga una copia primero (red de seguridad, 10 segundos).
-- 2. Pestaña "SQL" de phpMyAdmin (NO "Importar") y pega todo este archivo.
-- 3. Ejecútalo UNA SOLA VEZ.
-- 4. Avísame cuando lo hayas corrido para vaciar este archivo de nuevo.

ALTER TABLE `users`
  ADD `last_seen_at` TIMESTAMP NULL DEFAULT NULL AFTER `approved_at`;
