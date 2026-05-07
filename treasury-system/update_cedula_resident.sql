-- Add resident_id to cedula table for resident edit linkage
ALTER TABLE cedula
    ADD COLUMN resident_id INT DEFAULT NULL AFTER remarks;

CREATE INDEX idx_cedula_resident_id ON cedula (resident_id);
