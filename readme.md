User entity:
- first-name
- last_name
- email
- password
- created
- role (enum)

creating admin: php bin/console app:create-admin

### Updating Schema

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

### Database reset and Load Fixtures

In case of issues with the database or migrations being annoying, here's a quick way to reset the database and make a fresh new migration:

```
rm -rf migrations/*
php bin/console doctrine:database:drop --force
php bin/console doctrine:database:create
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

To load the fixtures (the available projects in the intranet, otherwise the project page will appear empty)
Also need to run this command every time more fixtures are added.

```
php bin/console doctrine:fixtures:load --append
```

Create a new admin once it's all done

```
php bin/console app:create-admin
```

