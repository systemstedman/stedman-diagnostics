-- Stedman Diagnostics
-- Add username of the user who logged the service call.
-- Run once against the existing service_calls table.

ALTER TABLE service_calls
ADD COLUMN logged_by VARCHAR(200) NULL AFTER logged_datetime;
