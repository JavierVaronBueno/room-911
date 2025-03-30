# Room 911 API

**Version:** 1.0.0  
**Date:** March 29, 2025  
**Author:** Javier Varon

**Description:** Room 911 Backend is a RESTful API developed in Laravel 11 for managing employees, production departments, access attempts, and PDF report generation. This project includes JWT authentication, bulk employee import via CSV, and scheduled tasks for file cleanup.

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Project Structure](#project-structure)
- [API Usage](#api-usage)
- [Scheduled Tasks](#scheduled-tasks)
- [Testing](#testing)
- [Debugging and Logs](#debugging-and-logs)
- [Deployment](#deployment)
- [Contributions](#contributions)
- [License](#license)

## Features

- **Employee Management:** CRUD operations for employees with photos and associated departments.
- **Bulk Import:** Loading employees from CSV files with detailed validation.
- **Access Attempts:** Recording and querying access attempts with date filters.
- **PDF Generation:** Access history reports dynamically generated and stored in storage.
- **Authentication:** JWT to protect API routes.
- **Scheduled Tasks:** Automatic cleanup of old PDFs.
- **Storage:** Use of storage/app/public with public URLs for downloads.

## Requirements

- **PHP:** 8.1 or higher
- **Composer:** 2.x
- **MySQL:** 5.7 or higher (or any database compatible with Laravel)
- **Laravel:** 11.x
- **Dependencies:**
  - maatwebsite/excel for CSV import
  - tymon/jwt-auth for JWT authentication
  - barryvdh/laravel-dompdf for PDF generation
- **Operating System:** Windows (Laragon recommended), Linux, or macOS
- **Cron:** For scheduled tasks (Linux) or Task Scheduler (Windows)

## Installation

### 1. Clone the Repository

```bash
git clone https://github.com/JavierVaronBueno/room-911.git
cd room-911
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Configure the Environment

Copy the .env.example file to .env:

```bash
cp .env.example .env
```

Edit .env with your settings:

```
APP_NAME="Room 911 Backend"
APP_ENV=local
APP_KEY=base64:[generate-a-key-with-php-artisan-key:generate]
APP_DEBUG=true
APP_URL=http://localhost/room-911/public

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=room_911
DB_USERNAME=root
DB_PASSWORD=

JWT_SECRET=[generate-a-key-with-php-artisan-jwt:secret]
```

Generate application and JWT keys:

```bash
php artisan key:generate
php artisan jwt:secret
```

### 4. Configure the Database

Create the room_911 database in MySQL.
Run migrations:

```bash
php artisan migrate
```

### 5. Configure Storage

Create the symbolic link for storage:

```bash
php artisan storage:link
```

Create the directory for PDFs:

```bash
mkdir storage/app/public/access_histories
chmod -R 775 storage
```

### 6. Start the Server

Use Laravel's built-in server:

```bash
php artisan serve
```

Or configure Laragon to point to room-911/public.

## Configuration

### JWT Authentication

- **Middleware:** Routes are protected with jwt.auth (see app/Http/Middleware/JwtMiddleware.php).
- **Generate Token:** Use the /api/auth/login endpoint with valid credentials.

### Storage

**Public Disk:** Configured in config/filesystems.php:

```php
'public' => [
    'driver' => 'local',
    'root' => storage_path('app/public'),
    'url' => env('APP_URL').'/storage',
    'visibility' => 'public',
],
```

### Scheduled Tasks

- **Command:** app:clean-old-pdfs removes PDFs older than 7 days.
- **Frequency:** Configured to run every minute (see app/Console/Kernel.php).

## Project Structure

```
room-911/
├── app/
│   ├── Console/
│   │   └── Commands/
│   │       └── CleanOldPdfs.php       # Command to clean old PDFs
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AccessAttemptController.php  # Access attempt management
│   │   │   ├── EmployeeController.php       # Employee CRUD
│   │   │   └── ProductionDepartmentController.php  # Department CRUD
│   │   └── Middleware/
│   │       └── JwtMiddleware.php     # JWT authentication middleware
│   ├── Imports/
│   │   └── EmployeesImport.php       # Bulk employee import
│   └── Models/
│       ├── Employee.php              # Employee model
│       ├── ProductionDepartment.php  # Department model
│       └── AccessAttempt.php         # Access attempt model
├── config/
│   ├── app.php                      # General configuration
│   └── filesystems.php              # Storage configuration
├── database/
│   ├── migrations/                  # Database migrations
│   └── seeders/                     # Seeders (optional)
├── public/
│   └── storage/                     # Symbolic link to storage/app/public
├── resources/
│   └── views/                       # Views (not used in API currently)
├── routes/
│   └── api.php                      # API route definitions
├── storage/
│   ├── app/
│   │   └── public/
│   │       └── access_histories/    # Directory for generated PDFs
│   └── logs/
│       └── laravel.log             # Application logs
├── .env                             # Environment variables
└── composer.json                    # Project dependencies
```

## API Usage

### Main Endpoints

- **Base URL:** http://localhost/room-911/public/api/v1
- **Authentication:** All routes require a JWT token in the header `Authorization: Bearer <token>`.

### Employees

- **Create Employee:** POST /employees
  - Body: `{ "internal_id": "EMP001", "first_name": "John", "last_name": "Doe", "production_department_id": 1, "photo": [file] }`
- **Update Employee:** PUT /employees/{id}
- **Import CSV:** POST /employees/bulk
  - Body: Form-data with csv: [file.csv]

### Access Attempts

- **History:** GET /access-attempts/employee/{employee_id}
  - Query: ?start_date=2025-03-28&end_date=2025-03-28
- **Download PDF:** GET /access-attempts/employee/{employee_id}/pdf
  - Query: ?start_date=2025-03-28&end_date=2025-03-28
  - Response: `{ "download_url": "http://localhost/room-911/public/storage/access_histories/..." }`

### Departments

- **Create Department:** POST /production-departments
  - Body: `{ "name": "Production A" }`

### Request Example

```bash
curl -X GET "http://localhost/room-911/public/api/v1/access-attempts/employee/4/pdf?start_date=2025-03-28&end_date=2025-03-28" \
-H "Authorization: Bearer <token>"
```

## Scheduled Tasks

### PDF Cleanup

- **Command:** `php artisan app:clean-old-pdfs`
- **Frequency:** Every minute (configurable in app/Console/Kernel.php).
- **Logic:** Removes files in storage/app/public/access_histories older than 7 days.

### Cron Configuration

**On Linux:**

```bash
* * * * * cd /path/to/room-911 && php artisan schedule:run >> /dev/null 2>&1
```

**On Windows (Laragon):**
Use the "Task Scheduler" to run `php artisan schedule:run` every minute.

## Testing

### Manual Testing

Start the Server:

```bash
php artisan serve --port=8000
```

Test Endpoints with Postman or cURL.

### Unit Tests (Optional)

Create tests in tests/Feature/:

```bash
php artisan make:test EmployeeTest --unit
```

Run:

```bash
php artisan test
```

## Debugging and Logs

- **Logs:** Check storage/logs/laravel.log for errors and scheduled task messages.
- **Debugging:** Enable APP_DEBUG=true in .env to see error details in development.

## Deployment

### On a Linux Server

**Upload Files:**
Use Git or FTP to upload the project to /var/www/room-911.

**Configure Web Server (Nginx/Apache):**
Point the domain to the /var/www/room-911/public directory.

Nginx configuration example:

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/room-911/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

**Permissions:**

```bash
chown -R www-data:www-data /var/www/room-911
chmod -R 775 /var/www/room-911/storage
```

**Configure Cron:**
As mentioned in [Scheduled Tasks](#scheduled-tasks).

### In Production

Change .env:

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
```

## Contributions

1. Fork the repository.
2. Create a branch: `git checkout -b feature/new-feature`.
3. Commit your changes: `git commit -m "Add new feature"`.
4. Push the branch: `git push origin feature/new-feature`.
5. Open a Pull Request.

## License

This project is licensed under the MIT License (LICENSE).
