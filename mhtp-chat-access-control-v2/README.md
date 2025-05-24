# MHTP Chat Access Control - Documentación

## Descripción
MHTP Chat Access Control es un plugin de WordPress que controla el acceso a la página de "Chat con expertos" basado en el estado de inicio de sesión del usuario y la disponibilidad de sesiones de consulta. El plugin redirige a los usuarios no autorizados a una página personalizada con mensajes y botones específicos según su situación.

## Características
- Verifica si el usuario está autenticado
- Verifica si el usuario tiene sesiones disponibles
- Genera y valida tokens de seguridad para el acceso a sesiones
- Redirige a usuarios no autorizados con mensajes personalizados
- Proporciona una página de configuración en el panel de administración
- Incluye estilos CSS personalizados para la página de acceso denegado
- Implementa limpieza automática de tokens expirados

## Requisitos
- WordPress 5.0 o superior
- PHP 7.2 o superior
- WooCommerce (opcional, para la compra de sesiones)

## Instalación
1. Sube la carpeta `mhtp-chat-access-control` al directorio `/wp-content/plugins/` de tu instalación de WordPress
2. Activa el plugin a través del menú 'Plugins' en WordPress
3. Ve a Ajustes > Chat Access Control para configurar el plugin

## Configuración
Después de activar el plugin, ve a Ajustes > Chat Access Control para configurar las siguientes opciones:

- **Chat Page Slug**: El slug de la página de chat (por defecto: chat-con-expertos)
- **Redirect Page Slug**: (Opcional) El slug de una página personalizada para redirigir a usuarios no autorizados
- **Login URL**: URL de la página de inicio de sesión
- **Register URL**: URL de la página de registro
- **Buy Sessions URL**: URL de la página para adquirir sesiones
- **Not Logged In Message**: Mensaje para usuarios no autenticados
- **No Sessions Message**: Mensaje para usuarios sin sesiones disponibles
- **Token Expiration Hours**: Horas de validez de los tokens de sesión (por defecto: 24)

## Uso
El plugin funciona automáticamente una vez configurado. Cuando un usuario intenta acceder a la página de chat:

1. Si el usuario no está autenticado, será redirigido a una página con opciones para iniciar sesión o registrarse
2. Si el usuario está autenticado pero no tiene sesiones disponibles, será redirigido a una página con la opción de adquirir sesiones
3. Si el usuario está autenticado y tiene sesiones disponibles, podrá acceder a la página de chat

## Integración con Botpress
Para integrar el plugin con Botpress, puedes usar el sistema de tokens para validar el acceso:

```javascript
// Ejemplo de código para integración con Botpress
document.addEventListener('DOMContentLoaded', function() {
  // Verificar si el usuario tiene un token válido
  const token = localStorage.getItem('mhtp_session_token');
  
  if (token) {
    // Validar el token mediante AJAX
    validateToken(token).then(response => {
      if (response.success) {
        // Iniciar chat con Botpress
        initBotpressChat(response.data);
      } else {
        // Token inválido, solicitar nuevo token
        requestNewToken();
      }
    });
  } else {
    // No hay token, solicitar uno nuevo
    requestNewToken();
  }
});

// Función para validar token
function validateToken(token) {
  return new Promise((resolve, reject) => {
    // Llamada AJAX para validar token
    // ...
  });
}

// Función para solicitar nuevo token
function requestNewToken() {
  // Llamada AJAX para generar nuevo token
  // ...
}

// Función para iniciar chat con Botpress
function initBotpressChat(userData) {
  // Inicializar chat con Botpress usando los datos del usuario
  // ...
}
```

## Estructura de archivos
- `mhtp-chat-access-control.php`: Archivo principal del plugin
- `includes/class-token-manager.php`: Clase para gestionar tokens de sesión
- `css/style.css`: Estilos CSS para la página de acceso denegado
- `tests/test-scenarios.php`: Escenarios de prueba para verificar la funcionalidad

## Filtros y acciones disponibles
El plugin proporciona los siguientes filtros y acciones para personalizar su comportamiento:

### Filtros
- `mhtp_user_available_sessions`: Permite modificar el número de sesiones disponibles para un usuario
- `mhtp_unauthorized_message`: Permite modificar el mensaje de acceso denegado
- `mhtp_unauthorized_buttons`: Permite modificar los botones mostrados en la página de acceso denegado

### Acciones
- `mhtp_before_unauthorized_message`: Se ejecuta antes de mostrar el mensaje de acceso denegado
- `mhtp_after_unauthorized_message`: Se ejecuta después de mostrar el mensaje de acceso denegado
- `mhtp_suspicious_token_access`: Se ejecuta cuando se detecta un acceso sospechoso con un token

## Solución de problemas
- **La redirección no funciona**: Verifica que el slug de la página de chat esté configurado correctamente
- **El plugin no detecta las sesiones disponibles**: Asegúrate de que el meta de usuario `mhtp_available_sessions` existe y tiene un valor numérico
- **Los estilos no se aplican**: Verifica que los archivos CSS se están cargando correctamente
- **Los tokens no se generan**: Verifica que la tabla de tokens se ha creado correctamente en la base de datos

## Notas de desarrollo
Este plugin ha sido desarrollado siguiendo un enfoque incremental, implementando y probando una funcionalidad a la vez. La estructura está diseñada para ser compatible con futuras mejoras, como la implementación de un sistema más robusto de gestión de sesiones de consulta.

## Próximas mejoras
- Implementación de un sistema completo de gestión de sesiones de consulta
- Mejora del sistema de reconexión para manejar desconexiones temporales
- Integración más profunda con Botpress
- Panel de administración para gestionar sesiones y tokens
