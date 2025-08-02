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
 
### User Creation

To test confirmation email, install mailhog:

```
wget https://github.com/mailhog/MailHog/releases/download/v1.0.1/MailHog_linux_amd64
chmod +x MailHog_linux_amd64
sudo mv MailHog_linux_amd64 /usr/local/bin/mailhog
```
Then run it alongside the server
```
mailhog
```
MailHog starts an SMTP server on port 1025 and a web UI on http://localhost:8025.
