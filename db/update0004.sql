DELETE FROM embeddings;
ALTER TABLE embeddings ADD COLUMN binary BLOB NOT NULL;
