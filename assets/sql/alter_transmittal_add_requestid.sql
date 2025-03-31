-- Add a requestID column to the transmittal table to track which branch request it's associated with
ALTER TABLE transmittal ADD COLUMN requestID INT NULL;

-- Add an index for efficient lookups
CREATE INDEX idx_transmittal_request ON transmittal(requestID);
