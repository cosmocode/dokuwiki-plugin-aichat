CREATE TABLE embeddings
(
    id        INTEGER   NOT NULL PRIMARY KEY,
    page      TEXT      NOT NULL,
    embedding BLOB      NOT NULL,
    chunk     TEXT      NOT NULL,
    created   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_embeddings_page ON embeddings (page);
