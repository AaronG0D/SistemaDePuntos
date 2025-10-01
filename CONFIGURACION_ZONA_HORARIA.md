# Configuración de Zona Horaria para Bolivia

## Problema
La zona horaria no se aplica correctamente porque falta configuración en el archivo `.env`

## Solución

### 1. Edita tu archivo `.env` y agrega estas líneas:

```env
APP_LOCALE=es
APP_FALLBACK_LOCALE=es
APP_FAKER_LOCALE=es_ES
APP_TIMEZONE=America/La_Paz
```

### 2. Actualiza el archivo `config/app.php` (línea 68):

Cambia:
```php
'timezone' => 'America/La_Paz',
```

Por:
```php
'timezone' => env('APP_TIMEZONE', 'America/La_Paz'),
```

### 3. Limpia la caché de configuración:

```bash
php artisan config:clear
php artisan cache:clear
php artisan config:cache
```

### 4. Reinicia el servidor de desarrollo

Si estás usando `php artisan serve`, detén el servidor (Ctrl+C) y vuelve a iniciarlo.

## Verificación

Para verificar que la zona horaria está correcta, puedes crear una ruta de prueba:

```php
Route::get('/test-timezone', function () {
    return [
        'timezone' => config('app.timezone'),
        'current_time' => now()->format('Y-m-d H:i:s'),
        'timezone_offset' => now()->format('P')
    ];
});
```

Deberías ver:
- `timezone`: "America/La_Paz"
- `timezone_offset`: "-04:00"
- `current_time`: La hora actual de Bolivia

## Cambios en Base de Datos

Se agregaron dos nuevos campos a la tabla `deposito`:
- `idPeriodo`: ID del período académico (relación con tabla periodo_academico)
- `puntajeTipoBasura`: Puntaje del tipo de basura en el momento del depósito

Para aplicar estos cambios, ejecuta:
```bash
php artisan migrate
```
