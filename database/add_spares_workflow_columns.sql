-- Run once in phpMyAdmin > stedman_diagnostics > SQL.
-- Adds separate, auditable answers for the two spares workflow questions.
ALTER TABLE service_calls
    ADD COLUMN spares_required ENUM('Yes','No') NULL AFTER actions_taken,
    ADD COLUMN spares_replaced ENUM('Yes','No') NULL AFTER spares_required;
