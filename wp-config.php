<?php
/**
 * The base configurations of the WordPress.
 *
 * This file has the following configurations: MySQL settings, Table Prefix,
 * Secret Keys, WordPress Language, and ABSPATH. You can find more information
 * by visiting {@link http://codex.wordpress.org/Editing_wp-config.php Editing
 * wp-config.php} Codex page. You can get the MySQL settings from your web host.
 *
 * This file is used by the wp-config.php creation script during the
 * installation. You don't have to use the web site, you can just copy this file
 * to "wp-config.php" and fill in the values.
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'wpforwoocomm');

/** MySQL database username */
define('DB_USER', 'root');

/** MySQL database password */
define('DB_PASSWORD', '');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');

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
define('AUTH_KEY',         '_(oSqU]j[7yM+O[E63Il5<}x*31rkofj+=knmePz$uTi.#Xs8^?R~K&Eq*|a!v*l');
define('SECURE_AUTH_KEY',  'Nlo-`shc%%_Zq7o&.I>7yeDS!V?!YGT%S|kgX iwf=s8>*YKE{e9T.V>7Y-`S2#2');
define('LOGGED_IN_KEY',    '.OGQyvP|HaGtAIa8%k)lOv]:oPHS5k<L}l=vO(V@[m0#ELJ9hpaUgV?|!K8b>sJ}');
define('NONCE_KEY',        'G/&&9z*)rC:l/uN10IcIM0n0cxmU:M2WIymbH6/P?wtEq(W9u6]8icPH`CM5/V]*');
define('AUTH_SALT',        '1)e-=[OKv_ .2:RM|ou.11*- 9&=Fb5(dwH%F9qcr?T~3^u.KE,B=Be3svD)z=Vn');
define('SECURE_AUTH_SALT', '}E+fS^e_%-(A-Th8[al`sf/SrQD.VxS0&wc#7)v ]9!H>LZ7dD}qG$b&yG6ZHO!=');
define('LOGGED_IN_SALT',   'hF~eO_3)w!j|Qr<:d1y<Za{Y&3]$CaD(RNnS~VR<2uz7nxn($B@KU+)(C}($;jZ[');
define('NONCE_SALT',       '/3v12bWN~)a6NwQ:R/XP$s^R-Vqa-ItS%@+WJ9*z0GTo,2 eZ,mBL0nTh=(NRW=b');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each a unique
 * prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wpcomm_';

/**
 * WordPress Localized Language, defaults to English.
 *
 * Change this to localize WordPress. A corresponding MO file for the chosen
 * language must be installed to wp-content/languages. For example, install
 * de_DE.mo to wp-content/languages and set WPLANG to 'de_DE' to enable German
 * language support.
 */
define('WPLANG', '');

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 */
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
