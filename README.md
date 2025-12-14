# login-back

Servicio Symfony 8 dockerizado con PostgreSQL que expone tres endpoints b?sicos: registro, login y logout. Incluye un `openapi.yaml` listo para importar en Backstage.

## Requisitos
- Docker y Docker Compose

## Puesta en marcha
1) Construir y levantar servicios (PHP-FPM, Nginx y Postgres):
```bash
docker compose up -d --build
```
2) Ejecutar migraciones para crear la tabla de usuarios:
```bash
docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction
```
3) La API queda disponible en `http://localhost:8080`.

## Endpoints
- `POST /api/register` ? JSON `{ "email", "password", "fullName" }`
- `POST /api/login` ? JSON `{ "email", "password" }` (genera cookie de sesi?n)
- `POST /api/logout`

## OpenAPI / Backstage
- El contrato est? en `openapi.yaml`. Importe ese archivo en Backstage como componente o API.
- Servidor definido: `http://localhost:8080`.

## Notas
- Credenciales por defecto Postgres: usuario `symfony`, password `symfony`, base `symfony` (ver `docker-compose.yml` y `.env`).
- Para desarrollo local, los archivos del repo se montan dentro del contenedor PHP (`/var/www/html`).
