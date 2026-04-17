FROM mysql:8.0

# Seed database from repository schema + migrations + sample data
COPY sql/database.sql /docker-entrypoint-initdb.d/01-schema.sql
COPY sql/migrations/*.sql /docker-entrypoint-initdb.d/
COPY sql/dummy_data.sql /docker-entrypoint-initdb.d/99-sample-data.sql
