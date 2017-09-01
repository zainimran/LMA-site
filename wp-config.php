<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'lma');

/** MySQL database username */
define('DB_USER', 'lma_admin');

/** MySQL database password */
define('DB_PASSWORD', 'lma2016');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8mb4');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         ' JzMAJy-,sUm<OTB+?;fO.[3lN;C;VTEA3NPE&`6nDm]+DV2j5Vc+#Yg2v$]x={s');
define('SECURE_AUTH_KEY',  '}:IMeZVc7O-XlwJBnc:ic:_;=goJ@=&:=#xh,9#:Ab0yRf?Xm;>BB1HepNc:+IsD');
define('LOGGED_IN_KEY',    '#V)(^n7sbGb0Q0oK0>ibt(bf3J$D0oY_oceoSX`L1(8 4[Bk)2ek6;|!08FNR[)y');
define('NONCE_KEY',        '_b7p{U{0c@* 83tD*5%my^Q|=N`[|r%/#GM 72.7;S<qaD@tFb,55yHddC{jMc^d');
define('AUTH_SALT',        'Cg%8D,x-5u6po;o^s4cSWKxqpKz&o%<:}]FQnI /LYXZq_*zlfXQ9}ka4A$OdvMb');
define('SECURE_AUTH_SALT', 'eV&:+(=d~-5%A-?)->E[)J6XT(i[W#gSqq;n*2},hV87BlWCs9%CHMD;i#X[bsH[');
define('LOGGED_IN_SALT',   '*gH/}A}1{a2-85AV2Ke#DS3*(=;Bh*f`0|MfO]U~e<gXV36b*avr[YY.KkNmJBz/');
define('NONCE_SALT',       'c-B=!NXC4paIq1W-<P~s23|;T2i5E3}*Vmq(A+sx*vE1VKH.q,~27g^yr=+,K(^9');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
