# MHTP Session History Reset

Plugin sencillo para borrar el historial de sesiones de todos los usuarios.

## Descripción

Este plugin proporciona una herramienta administrativa simple para borrar el historial de sesiones de todos los usuarios de WordPress. Está diseñado específicamente para trabajar con el plugin MHTP Current Sessions v9.1, eliminando los datos almacenados en el meta de usuario con la clave `mhtp_session_history`.

## Características

- Interfaz administrativa sencilla
- Confirmación doble para evitar borrados accidentales
- Elimina solo los datos de historial de sesiones, sin afectar otros datos
- Operación rápida y eficiente

## Instalación

1. Sube el archivo zip a tu sitio de WordPress
2. Ve a Plugins > Añadir nuevo > Subir plugin
3. Selecciona el archivo y haz clic en "Instalar ahora"
4. Activa el plugin

## Uso

1. Ve a Herramientas > MHTP Borrar Historial
2. Marca la casilla de confirmación
3. Haz clic en "Borrar Historial de Sesiones"
4. Confirma la acción en el diálogo emergente

## Requisitos

- WordPress 5.0 o superior
- Permisos de administrador para ejecutar la herramienta

## Seguridad

Este plugin incluye:
- Verificación de nonce para prevenir ataques CSRF
- Comprobación de permisos de usuario
- Confirmación doble antes de ejecutar la acción
