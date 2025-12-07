# Manual de Instalación y Despliegue - Sistema de Gestión Catastral

Este documento detalla los pasos necesarios para instalar, configurar y ejecutar el sistema de catastro en un entorno local (Windows).

## 1. Requisitos Previos del Sistema

Para ejecutar este proyecto, el equipo debe tener instalado el siguiente software. Se recomienda incluir los instaladores en la carpeta `Instaladores/` del CD/DVD para facilitar la labor.

*   **PHP**: Versión 8.2 o superior.
*   **Composer**: Gestor de dependencias de PHP.
*   **Node.js y NPM**: Versión 18 o superior (para compilar los estilos y scripts).
*   **PostgreSQL**: Versión 13 o superior.
*   **PostGIS**: Extensión espacial para PostgreSQL (IMPRESCINDIBLE para la gestión de mapas).
*   **Python**: (Si se requiere para la API de ML) Versión 3.9+.

---

## 2. Preparación de la Base de Datos (PostgreSQL)

1.  Abra **pgAdmin** o su herramienta de administración de base de datos preferida.
2.  Cree una nueva base de datos llamada `catastro_db` (o el nombre que prefiera).
3.  **IMPORTANTE:** Debe habilitar la extensión PostGIS en la base de datos creada. Ejecute el siguiente comando SQL en la nueva base de datos:

    ```sql
    CREATE EXTENSION postgis;
    ```
    *Si este paso se omite, el sistema fallará al intentar guardar geometrías.*

---

## 3. Instalación del Proyecto Laravel

1.  Copie la carpeta del proyecto (`projectcatastro`) desde el CD/DVD a su disco duro (ej. `C:\projectcatastro`).
2.  Abra una terminal (CMD o PowerShell) y navegue hasta la carpeta del proyecto.

### 3.1 Instalación de Dependencias

Ejecute los siguientes comandos para descargar las librerías necesarias:

```bash
# Instalar dependencias de Backend (Laravel)
composer install

# Instalar dependencias de Frontend (Vite/Tailwind/Bootstrap)
npm install
```

### 3.2 Configuración del Entorno

1.  Duplique el archivo `.env.example` y renómbrelo a `.env`.
2.  Abra el archivo `.env` y configure las credenciales de la base de datos:

    ```ini
    DB_CONNECTION=pgsql
    DB_HOST=127.0.0.1
    DB_PORT=5432
    DB_DATABASE=catastro_db
    DB_USERNAME=postgres
    DB_PASSWORD=su_contraseña_aqui
    ```

3.  Genere la clave de encriptación de la aplicación:

    ```bash
    php artisan key:generate
    ```

4.  Cree el enlace simbólico para el almacenamiento de archivos:

    ```bash
    php artisan storage:link
    ```

### 3.3 Base de Datos: Dos Opciones

Puede elegir una de las dos siguientes opciones para poblar la base de datos.

#### Opción A: Vía Migraciones (Estándar Laravel)
Esta opción construye la estructura tabla por tabla y genera datos de prueba aleatorios.

```bash
php artisan migrate --seed
```

#### Opción B: Restaurar Copia de Seguridad (Recomendado para ver datos reales)
Si prefiere cargar la base de datos exactamente como fue entregada en el proyecto final (con los datos de demostración exactos):

1.  Localice el archivo `database_backup.sql` en la raíz del proyecto o en el CD.
2.  Abra su administrador de base de datos (ej. pgAdmin, DBeaver) o terminal.
3.  Asegúrese de que la base de datos `catastro_db` esté creada y con PostGIS activado, pero **vacía** de tablas.
4.  Importe/Restaure el archivo SQL.

    *Si usa terminal:*
    ```bash
    psql -U postgres -d catastro_db -f database_backup.sql
    ```

---

## 4. Compilación de Assets (Frontend)

Para generar los archivos CSS y JS finales, ejecute:

```bash
npm run build
```

---

## 5. Ejecución del Proyecto

### 5.1 Iniciar el Servidor Laravel

En la terminal, ejecute:

```bash
php artisan serve
```

El sistema estará accesible en: `http://127.0.0.1:8000`

### 5.2 Credenciales de Acceso (Admin)

*   **Usuario/Correo:** `admin@admin.com` (Verificar en `DatabaseSeeder.php` si es diferente)
*   **Contraseña:** `password`

---

## 6. Configuración de la API de Machine Learning (Python)

*(Nota para el usuario: Ajuste esta sección según dónde decida ubicar la carpeta del proyecto Python)*

1.  Localice la carpeta `proyecto_ml` incluida en el CD.
2.  Asegúrese de tener Python instalado.
3.  Instale las dependencias:
    ```bash
    pip install -r requirements.txt
    ```
4.  Ejecute el servidor Flask:
    ```bash
    python app.py
    ```
    *Asegúrese de que el puerto coincida con lo configurado en el archivo `.env` de Laravel.*

---

## 7. Solución de Problemas Comunes

*   **Error "Driver not found":** Asegúrese de haber descomentado `extension=pdo_pgsql` y `extension=pgsql` en su archivo `php.ini`.
*   **Error de Geometría:** Verifique que ejecutó `CREATE EXTENSION postgis;` en la base de datos.
