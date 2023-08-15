CREATE TABLE clusters
(
    cluster   INTEGER   NOT NULL PRIMARY KEY,
    centroid  BLOB      NOT NULL,
    created   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE embeddings ADD COLUMN cluster INTEGER REFERENCES clusters(cluster);

