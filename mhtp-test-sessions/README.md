# MHTP Test Sessions Manager

Plugin para gestionar sesiones de prueba para usuarios en WordPress con integración con WooCommerce.

## Descripción

MHTP Test Sessions Manager es un plugin que permite a los administradores asignar sesiones gratuitas o de prueba a los usuarios de WordPress. Estas sesiones pueden ser utilizadas para acceder a servicios premium o contenido restringido sin necesidad de realizar una compra.

El plugin se integra perfectamente con WooCommerce y permite gestionar tanto las sesiones gratuitas como las sesiones adquiridas a través de productos WooCommerce.

## Características principales

- **Gestión de sesiones**: Añadir, editar y eliminar sesiones de prueba para usuarios.
- **Lista de usuarios**: Ver todos los usuarios de WordPress/WooCommerce y asignarles sesiones directamente.
- **Categorización**: Clasificar las sesiones en diferentes categorías (prueba, promoción, demo, etc.).
- **Caducidad automática**: Establecer fechas de expiración para las sesiones de prueba.
- **Asignación masiva**: Añadir sesiones a múltiples usuarios simultáneamente.
- **Estadísticas de uso**: Seguimiento detallado de cómo se utilizan las sesiones de prueba.
- **Integración con WooCommerce**: Compatibilidad total con productos de WooCommerce.

## Requisitos

- WordPress 5.0 o superior
- PHP 7.2 o superior
- WooCommerce 4.0 o superior (opcional, para integración completa)

## Instalación

1. Sube el archivo `mhtp-test-sessions.zip` a tu sitio WordPress.
2. Activa el plugin desde el panel de administración de WordPress.
3. Configura las opciones del plugin en "Sesiones de Prueba > Configuración".

## Uso

### Añadir sesiones a un usuario individual

1. Ve a "Sesiones de Prueba > Gestionar Sesiones".
2. Selecciona un usuario de la lista desplegable.
3. Introduce la cantidad de sesiones, categoría y fecha de caducidad.
4. Haz clic en "Añadir Sesiones".

### Ver lista de usuarios y asignar sesiones

1. Ve a "Sesiones de Prueba > Lista de Usuarios".
2. Utiliza los filtros para encontrar usuarios específicos.
3. Haz clic en "Añadir Sesiones" junto al usuario deseado.
4. Completa el formulario en la ventana modal y haz clic en "Añadir Sesiones".

### Asignación masiva de sesiones

1. Ve a "Sesiones de Prueba > Asignación Masiva".
2. Selecciona los criterios para filtrar usuarios (rol, fecha de registro, etc.).
3. Introduce la cantidad de sesiones, categoría y fecha de caducidad.
4. Haz clic en "Asignar Sesiones".

### Ver estadísticas

1. Ve a "Sesiones de Prueba > Estadísticas".
2. Selecciona el período de tiempo para ver las estadísticas.
3. Explora los diferentes gráficos y métricas disponibles.

## Integración con WooCommerce

El plugin está diseñado para funcionar con los siguientes productos de WooCommerce:

- ID 483: Producto de 1 sesión
- ID 486: Producto de 5 sesiones
- ID 487: Producto de 10 sesiones

Estos IDs pueden ser modificados en la página de configuración del plugin.

## Solución de problemas

### La página se queda en blanco al añadir sesiones

Si experimentas problemas al añadir sesiones y la página se queda en blanco, verifica lo siguiente:

1. Asegúrate de que el usuario seleccionado existe y está activo.
2. Verifica que la cantidad de sesiones es un número positivo.
3. Comprueba los logs de error de PHP para identificar posibles problemas.

### No se muestran todos los usuarios

Si no se muestran todos los usuarios en la lista:

1. Verifica los filtros aplicados en la página de lista de usuarios.
2. Comprueba que los usuarios tienen el rol correcto.
3. Utiliza la función de búsqueda para encontrar usuarios específicos.

## Personalización

El plugin puede ser personalizado mediante filtros y acciones de WordPress. Consulta la documentación técnica para más detalles.

## Soporte

Para soporte técnico, por favor contacta con el equipo de desarrollo a través de [soporte@gabinetedeorientacion.com](mailto:soporte@gabinetedeorientacion.com).

## Licencia

Este plugin es propiedad de Gabinete de Orientación y su uso está restringido a los sitios autorizados.

---

© 2025 Araujo Innovations. Todos los derechos reservados.
