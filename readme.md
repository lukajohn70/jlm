# JLM Custom Web App (PHP + MySQL)

**Tagline:** Bringing your ideas to life

---

## Project Structure

```
/jlm/
  index.php          # Main entry point
  /assets/
    /images/
  /src/              # PHP logic (controllers, models, views)
    /controllers/
    /models/
    /views/
    /includes/
  /config/
    config.php       # DB connection, settings
  /sql/
    schema.sql       # MySQL schema
  .htaccess          # For routing all requests to index.php
```

## Setup Instructions

1. **Clone or copy the project into your web root (e.g., `/wamp64/www/jlm/`).**
2. **Create a MySQL database (e.g., `jlm`).**
3. **Import the schema:**
   - Open phpMyAdmin or use the MySQL CLI.
   - Run the SQL in `/sql/schema.sql` to create tables.
4. **Configure the database connection:**
   - Edit `/config/config.php` with your DB credentials.
5. **Start your local server (WAMP/XAMPP).**
6. **Visit [http://localhost/jlm/](http://localhost/jlm/) in your browser.**

## Features
- Pixel-perfect UI (based on Figma/screenshots)
- Custom login/register for Admins and Freelancers
- Portfolio, Testimonials, Blog, Job Board, Contact/Booking forms
- Admin and Freelancer dashboards (Admin can post/manage blog posts)

## Default Admin Account
- After setup, register a new user and set their role to 'admin' in the database, or use a provided SQL insert.

---
**JLM – Bringing your ideas to life** 