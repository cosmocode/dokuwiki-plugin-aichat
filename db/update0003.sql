ALTER TABLE embeddings ADD COLUMN lang NOT NULL DEFAULT '';
CREATE INDEX embeddings_lang_idx ON embeddings (lang);

DROP TABLE clusters;
CREATE TABLE clusters
(
    cluster   INTEGER   NOT NULL PRIMARY KEY AUTOINCREMENT,
    lang      TEXT      NOT NULL DEFAULT '',
    centroid  BLOB      NOT NULL,
    created   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX clusters_lang_idx ON clusters (lang);
