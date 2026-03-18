# Deploy on Railway

This project can be deployed as a Dockerized PHP app with a Railway MySQL service.

## 1. Push the project to GitHub

Push the contents of this folder to a GitHub repository.

## 2. Create a Railway project

Create a new Railway project and add:

- one service from your GitHub repo
- one MySQL service

## 3. Generate a public URL

In the web service settings, open `Networking` and generate a public domain.

## 4. Set environment variables for the web service

Use Railway's MySQL service values:

- `DB_HOST=${{MySQL.MYSQLHOST}}`
- `DB_USER=${{MySQL.MYSQLUSER}}`
- `DB_PASS=${{MySQL.MYSQLPASSWORD}}`
- `DB_NAME=${{MySQL.MYSQLDATABASE}}`

Also set:

- `APP_NAME=Student Registration`
- `APP_TIMEZONE=Africa/Nairobi`
- `SESSION_SAVE_PATH=/var/www/html/storage/sessions`

## 5. Initialize the database

Open phpMyAdmin, Adminer, or connect with a MySQL client, then import:

- `init.sql` for a fresh setup

or

- `migrations.sql` if you already have an older database

## 6. Share the public URL

Once deployed, share the generated Railway domain with your group members so they can register, log in, and test the system.

## Notes

- Uploaded files are stored in the app container unless you add persistent storage.
- For a class/demo deployment, Railway is fine. For long-term use, add managed backups and persistent file storage.
