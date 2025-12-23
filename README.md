# DLog - A basic data logger and analytics application

A lightweight web application for tracking and managing basic data objects and analytics.
Built using PHP Slim Framework 4, a responsive Bootstrap-based UI and data visualization with D3.js visualizations.

## Features

- **User Authentication**: Secure login/signup with bcrypt password hashing
- **Session Management**: UUID-based user identification and session handling
- **Module Organization**: Group data objects under logical modules
- **Data Object Management**: Create, read, update, and delete data objects
- **Responsive Design**: Mobile-friendly Bootstrap UI

## Tech Stack

- **Programming Language**: PHP
- **Framework**: Slim 4 (PHP microframework)
- **Templating**: Twig 3+
- **Database**: MySQL/MariaDB
- **Dependency Injection**: PHP-DI
- **Frontend**: Bootstrap 3, D3.js
- **Server**: Apache/XAMPP/Nginx

## Requirements

- PHP 7.4 or higher
- MySQL/MariaDB 5.7 or higher
- Apache with mod_rewrite enabled
- Composer (for dependency management)

## Installation

### 1. Clone/Download the Project

```bash
cd /<source-path>/
```

### 2. Install Dependencies

```bash
cd source
composer install
```

### 3. Database Setup

1. Import the database schema:
```bash
mysql -u root -p dlog < ../db/dlog.sql
```

2. Or manually create the database:
```sql
CREATE DATABASE dlog;
USE dlog;
-- Import dlog.sql
```

### 4. Configure Environment

Create/update `.env` file in the `source/` directory:

```env
DB_DRIVER=mysql
DB_HOST=localhost
DB_PORT=3306
DB_NAME=dlog
DB_USER=dlog_app_user
DB_PASSWORD=
```

