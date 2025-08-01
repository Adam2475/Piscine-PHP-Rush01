User entity:
- first-name
- last_name
- email
- password
- created
- role (enum)

creating admin: php bin/console app:create-admin

@ when updating entities

- php bin/console doctrine:schema:validate
    - check if the schema is valid before creating
        the new migration
- php bin/console doctrine:migrations:diff
    - creates a new migration file with the diff
        of your last changes
- php bin/console doctrine:migrations:migrate
    - update the database based on migration files