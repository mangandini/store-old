<?php
/** 
 * Configuración básica de WordPress.
 *
 * Este archivo contiene las siguientes configuraciones: ajustes de MySQL, prefijo de tablas,
 * claves secretas, idioma de WordPress y ABSPATH. Para obtener más información,
 * visita la página del Codex{@link http://codex.wordpress.org/Editing_wp-config.php Editing
 * wp-config.php} . Los ajustes de MySQL te los proporcionará tu proveedor de alojamiento web.
 *
 * This file is used by the wp-config.php creation script during the
 * installation. You don't have to use the web site, you can just copy this file
 * to "wp-config.php" and fill in the values.
 *
 * @package WordPress
 */

// ** Ajustes de MySQL. Solicita estos datos a tu proveedor de alojamiento web. ** //
/** El nombre de tu base de datos de WordPress */
define('DB_NAME', 'woocommerce');

/** Tu nombre de usuario de MySQL */
define('DB_USER', 'root');

/** Tu contraseña de MySQL */
define('DB_PASSWORD', '');

/** Host de MySQL (es muy probable que no necesites cambiarlo) */
define('DB_HOST', 'localhost');

/** Codificación de caracteres para la base de datos. */
define('DB_CHARSET', 'utf8mb4');

/** Cotejamiento de la base de datos. No lo modifiques si tienes dudas. */
define('DB_COLLATE', '');

/**#@+
 * Claves únicas de autentificación.
 *
 * Define cada clave secreta con una frase aleatoria distinta.
 * Puedes generarlas usando el {@link https://api.wordpress.org/secret-key/1.1/salt/ servicio de claves secretas de WordPress}
 * Puedes cambiar las claves en cualquier momento para invalidar todas las cookies existentes. Esto forzará a todos los usuarios a volver a hacer login.
 *
 * @since 2.6.0
 */
define('AUTH_KEY', 'ED_qR-sgHArpa0&6z%p^6|Y8.85F,W><C-Z4Y.6)*NHqKw6xXb]DHf?zq)08G56U');
define('SECURE_AUTH_KEY', 'eY}ZiP?mAf*}e!,zjcP@j>:B,#oFabG6Vh`?<nk0WS/E/(m9aFpw0Y(*n%`m;y<>');
define('LOGGED_IN_KEY', '2Yla`#./k*VT5HpTtbn(I(w!yN]DtBue]@GCj6[hw+pQ+SC]3{w,z5QM*uK9D+{y');
define('NONCE_KEY', '%b#n,NL>:<T01>ZKv?H)THO)i1<Y:4JxI6Z*{ lrN<QYbbma}.MLHSYoaoaylFtm');
define('AUTH_SALT', 'RUw*}AZE3G2rYdSPL%eHfp>IXkd|n$&Oz`xf;*4DLz.=Ma}D/isfIHw[V<Q&#3aG');
define('SECURE_AUTH_SALT', '-~0Qdf!N+G2#S!&xi3}6@[u4C>kq4?%O5V23~O(1,*ND}l>5[(AbFF=^:lp4U9g3');
define('LOGGED_IN_SALT', 'yJfh5Vo:B0wWAB^_dKJfOKC*b1!Vw&4;9Jq{;{u57h=Pv0-mdxc${WZ;f8s>1EZi');
define('NONCE_SALT', 'HKxd)^u=cVuX,XNjlgf/3ZBn:@J:3gd-}Vm;ijv`{=t;kI/d59jHJr7n,9I Pbfh');

/**#@-*/

/**
 * Prefijo de la base de datos de WordPress.
 *
 * Cambia el prefijo si deseas instalar multiples blogs en una sola base de datos.
 * Emplea solo números, letras y guión bajo.
 */
$table_prefix  = 'wp_';


/**
 * Para desarrolladores: modo debug de WordPress.
 *
 * Cambia esto a true para activar la muestra de avisos durante el desarrollo.
 * Se recomienda encarecidamente a los desarrolladores de temas y plugins que usen WP_DEBUG
 * en sus entornos de desarrollo.
 */
define('WP_DEBUG', false);

/* ¡Eso es todo, deja de editar! Feliz blogging */

/** WordPress absolute path to the Wordpress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');

