# Room 911 API

**Versión:** 1.0.0  
**Fecha:** Marzo 29, 2025  
**Autor:** Javier Varon

**Descripción:** Room 911 Backend es una API RESTful desarrollada en Laravel 11 para gestionar empleados, departamentos de producción, intentos de acceso y la generación de reportes en PDF. Este proyecto incluye autenticación JWT, importación masiva de empleados vía CSV, y tareas programadas para limpieza de archivos.

## Tabla de Contenidos

- [Características](#características)
- [Requisitos](#requisitos)
- [Instalación](#instalación)
- [Configuración](#configuración)
- [Estructura del Proyecto](#estructura-del-proyecto)
- [Uso de la API](#uso-de-la-api)
- [Tareas Programadas](#tareas-programadas)
- [Pruebas](#pruebas)
- [Depuración y Logs](#depuración-y-logs)
- [Despliegue](#despliegue)
- [Contribuciones](#contribuciones)
- [Licencia](#licencia)

## Características

- **Gestión de Empleados:** CRUD para empleados con fotos y departamentos asociados.
- **Importación Masiva:** Carga de empleados desde archivos CSV con validación detallada.
- **Intentos de Acceso:** Registro y consulta de intentos de acceso con filtros por fecha.
- **Generación de PDFs:** Reportes de historial de acceso generados dinámicamente y almacenados en storage.
- **Autenticación:** JWT para proteger las rutas de la API.
- **Tareas Programadas:** Limpieza automática de PDFs antiguos.
- **Almacenamiento:** Uso de storage/app/public con URLs públicas para descargas.

## Requisitos

- **PHP:** 8.1 o superior
- **Composer:** 2.x
- **MySQL:** 5.7 o superior (o cualquier base de datos compatible con Laravel)
- **Laravel:** 11.x
- **Dependencias:**
  - maatwebsite/excel para importación de CSV
  - tymon/jwt-auth para autenticación JWT
  - barryvdh/laravel-dompdf para generación de PDFs
- **Sistema Operativo:** Windows (Laragon recomendado), Linux, o macOS
- **Cron:** Para tareas programadas (Linux) o Task Scheduler (Windows)

## Instalación

### 1. Clonar el Repositorio

```bash
git clone https://github.com/JavierVaronBueno/room-911.git
cd room-911
```

### 2. Instalar Dependencias

```bash
composer install
```

### 3. Configurar el Entorno

Copia el archivo .env.example a .env:

```bash
cp .env.example .env
```

Edita .env con tus configuraciones:

```
APP_NAME="Room 911 Backend"
APP_ENV=local
APP_KEY=base64:[genera-una-clave-con-php-artisan-key:generate]
APP_DEBUG=true
APP_URL=http://localhost/room-911-backend/public

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=room_911
DB_USERNAME=root
DB_PASSWORD=

JWT_SECRET=[genera-una-clave-con-php-artisan-jwt:secret]
```

Genera la clave de la aplicación y JWT:

```bash
php artisan key:generate
php artisan jwt:secret
```

### 4. Configurar la Base de Datos

Crea la base de datos room_911 en MySQL.
Ejecuta las migraciones:

```bash
php artisan migrate
```

### 5. Configurar Almacenamiento

Crea el enlace simbólico para storage:

```bash
php artisan storage:link
```

Crea el directorio para PDFs:

```bash
mkdir storage/app/public/access_histories
chmod -R 775 storage
```

### 6. Iniciar el Servidor

Usa el servidor integrado de Laravel:

```bash
php artisan serve
```

O configura Laragon para apuntar a room-911-backend/public.

## Configuración

### Autenticación JWT

- **Middleware:** Las rutas están protegidas con jwt.auth (ver app/Http/Middleware/JwtMiddleware.php).
- **Generar Token:** Usa el endpoint /api/auth/login con credenciales válidas.

### Almacenamiento

**Disco Público:** Configurado en config/filesystems.php:

```php
'public' => [
    'driver' => 'local',
    'root' => storage_path('app/public'),
    'url' => env('APP_URL').'/storage',
    'visibility' => 'public',
],
```

### Tareas Programadas

- **Comando:** app:clean-old-pdfs elimina PDFs de más de 7 días.
- **Frecuencia:** Configurado para ejecutarse cada minuto (ver app/Console/Kernel.php).

## Estructura del Proyecto

```
room-911-backend/
├── app/
│   ├── Console/
│   │   └── Commands/
│   │       └── CleanOldPdfs.php       # Comando para limpiar PDFs antiguos
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AccessAttemptController.php  # Gestión de intentos de acceso
│   │   │   ├── EmployeeController.php       # CRUD de empleados
│   │   │   └── ProductionDepartmentController.php  # CRUD de departamentos
│   │   └── Middleware/
│   │       └── JwtMiddleware.php     # Middleware de autenticación JWT
│   ├── Imports/
│   │   └── EmployeesImport.php       # Importación masiva de empleados
│   └── Models/
│       ├── Employee.php              # Modelo de empleado
│       ├── ProductionDepartment.php  # Modelo de departamento
│       └── AccessAttempt.php         # Modelo de intento de acceso
├── config/
│   ├── app.php                      # Configuración general
│   └── filesystems.php              # Configuración de almacenamiento
├── database/
│   ├── migrations/                  # Migraciones de base de datos
│   └── seeders/                     # Seeders (opcional)
├── public/
│   └── storage/                     # Enlace simbólico a storage/app/public
├── resources/
│   └── views/                       # Vistas (no usadas en API actualmente)
├── routes/
│   └── api.php                      # Definición de rutas API
├── storage/
│   ├── app/
│   │   └── public/
│   │       └── access_histories/    # Directorio para PDFs generados
│   └── logs/
│       └── laravel.log             # Logs de la aplicación
├── .env                             # Variables de entorno
└── composer.json                    # Dependencias del proyecto
```

## Uso de la API

### Endpoints Principales

- **Base URL:** http://localhost/room-911-backend/public/api/v1
- **Autenticación:** Todas las rutas requieren un token JWT en el encabezado `Authorization: Bearer <token>`.

### Empleados

- **Crear Empleado:** POST /employees
  - Body: `{ "internal_id": "EMP001", "first_name": "Juan", "last_name": "Pérez", "production_department_id": 1, "photo": [archivo] }`
- **Actualizar Empleado:** PUT /employees/{id}
- **Importar CSV:** POST /employees/bulk
  - Body: Form-data con csv: [archivo.csv]

### Intentos de Acceso

- **Historial:** GET /access-attempts/employee/{employee_id}
  - Query: ?start_date=2025-03-28&end_date=2025-03-28
- **Descargar PDF:** GET /access-attempts/employee/{employee_id}/pdf
  - Query: ?start_date=2025-03-28&end_date=2025-03-28
  - Respuesta: `{ "download_url": "http://localhost/room-911-backend/public/storage/access_histories/..." }`

### Departamentos

- **Crear Departamento:** POST /production-departments
  - Body: `{ "name": "Producción A" }`

### Ejemplo de Solicitud

```bash
curl -X GET "http://localhost/room-911-backend/public/api/v1/access-attempts/employee/4/pdf?start_date=2025-03-28&end_date=2025-03-28" \
-H "Authorization: Bearer <token>"
```

## Tareas Programadas

### Limpieza de PDFs

- **Comando:** `php artisan app:clean-old-pdfs`
- **Frecuencia:** Cada minuto (configurable en app/Console/Kernel.php).
- **Lógica:** Elimina archivos en storage/app/public/access_histories con más de 7 días de antigüedad.

### Configuración del Cron

**En Linux:**

```bash
* * * * * cd /path/to/room-911-backend && php artisan schedule:run >> /dev/null 2>&1
```

**En Windows (Laragon):**
Usa el "Task Scheduler" para ejecutar `php artisan schedule:run` cada minuto.

## Pruebas

### Pruebas Manuales

Iniciar el Servidor:

```bash
php artisan serve --port=8000
```

Probar Endpoints con Postman o cURL.

### Pruebas Unitarias (Opcional)

Crea pruebas en tests/Feature/:

```bash
php artisan make:test EmployeeTest --unit
```

Ejecuta:

```bash
php artisan test
```

## Depuración y Logs

- **Logs:** Revisa storage/logs/laravel.log para errores y mensajes de tareas programadas.
- **Debugging:** Habilita APP_DEBUG=true en .env para ver detalles de errores en desarrollo.

## Despliegue

### En un Servidor Linux

**Subir Archivos:**
Usa Git o FTP para subir el proyecto a /var/www/room-911-backend.

**Configurar el Servidor Web (Nginx/Apache):**
Apunta el dominio al directorio /var/www/room-911-backend/public.

Ejemplo de configuración Nginx:

```nginx
server {
    listen 80;
    server_name tu-dominio.com;
    root /var/www/room-911-backend/public;
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

**Permisos:**

```bash
chown -R www-data:www-data /var/www/room-911-backend
chmod -R 775 /var/www/room-911-backend/storage
```

**Configurar Cron:**
Como se mencionó en [Tareas Programadas](#tareas-programadas).

### En Producción

Cambia .env:

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com
```

## Contribuciones

1. Haz un fork del repositorio.
2. Crea una rama: `git checkout -b feature/nueva-funcionalidad`.
3. Commitea tus cambios: `git commit -m "Agrega nueva funcionalidad"`.
4. Sube la rama: `git push origin feature/nueva-funcionalidad`.
5. Abre un Pull Request.

## Licencia

Este proyecto está licenciado bajo la Licencia MIT (LICENSE).
