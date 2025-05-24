# MHTP Test Sessions Manager - Diseño del Plugin

## Descripción General
Plugin para WordPress que permite gestionar sesiones gratuitas para usuarios de prueba, con integración con WooCommerce y funcionalidades avanzadas de gestión.

## Características Principales

### 1. Categorización de Sesiones
- Diferenciar entre sesiones normales (compradas) y sesiones de prueba/gratuitas
- Etiquetas visuales diferentes para cada tipo de sesión
- Filtrado de sesiones por categoría en el panel de administración
- Posibilidad de crear categorías personalizadas (ej: "Demo", "Promocional", "Cortesía", etc.)

### 2. Caducidad Automática
- Establecer fecha de expiración para sesiones gratuitas
- Configuración de caducidad por defecto (ej: 7 días, 30 días)
- Caducidad personalizada para sesiones específicas
- Notificaciones automáticas de caducidad próxima (opcional)
- Limpieza automática de sesiones caducadas

### 3. Asignación Masiva
- Interfaz para añadir sesiones a múltiples usuarios simultáneamente
- Selección de usuarios por rol, fecha de registro, o compras previas
- Importación de lista de usuarios desde CSV
- Programación de asignaciones futuras
- Historial de asignaciones masivas realizadas

### 4. Estadísticas de Uso
- Dashboard con métricas clave de uso de sesiones gratuitas
- Tasa de conversión (usuarios que compran después de usar sesiones gratuitas)
- Tiempo promedio entre asignación y uso de sesiones
- Exportación de datos a CSV/Excel
- Gráficos visuales de tendencias de uso

## Integración con WooCommerce

### Gestión de Sesiones
- Reconocimiento de productos WooCommerce específicos como "sesiones"
- Compatibilidad con los productos existentes (IDs: 483, 486, 487)
- Diferenciación clara entre sesiones compradas y gratuitas

### Interfaz de Usuario
- Panel en Mi Cuenta para que los usuarios vean sus sesiones gratuitas
- Indicadores visuales de tipo de sesión y fecha de caducidad
- Historial de uso de sesiones

### Administración
- Página de configuración en el panel de administración
- Gestión de usuarios y sus sesiones
- Herramientas de asignación individual y masiva
- Visualización de estadísticas y reportes

## Estructura de Datos

### Tablas Personalizadas
- `mhtp_test_sessions`: Almacena información de sesiones gratuitas
  - `id`: ID único de la sesión
  - `user_id`: ID del usuario
  - `category`: Categoría de la sesión (ej: "test", "promo")
  - `quantity`: Número de sesiones asignadas
  - `expiry_date`: Fecha de caducidad
  - `created_at`: Fecha de creación
  - `created_by`: ID del administrador que creó la sesión
  - `status`: Estado (activa, usada, caducada)
  - `notes`: Notas adicionales

- `mhtp_session_usage`: Registro de uso de sesiones
  - `id`: ID único del registro
  - `session_id`: ID de la sesión relacionada
  - `user_id`: ID del usuario
  - `used_at`: Fecha y hora de uso
  - `expert_id`: ID del experto consultado (si aplica)
  - `duration`: Duración de la sesión (si aplica)

### Metadatos de Usuario
- Uso de metadatos de usuario para compatibilidad con el sistema actual
- Sincronización entre tablas personalizadas y metadatos

## Seguridad y Rendimiento
- Validación y sanitización de datos
- Capacidad para manejar grandes cantidades de usuarios
- Optimización de consultas a la base de datos
- Registro de actividad para auditoría

## Compatibilidad
- WordPress 5.8+
- WooCommerce 5.0+
- PHP 7.4+
- Compatibilidad con el tema actual y otros plugins relevantes
