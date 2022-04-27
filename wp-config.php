<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'vaniday_signle' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', '127.0.0.1' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'A/g|@h]Q^6l0!@B%TN_I*!OeWL[Eo%%[#qS8$uUBT/R]+]a~j[}XJX&Y(^x|?n/!' );
define( 'SECURE_AUTH_KEY',  'I$xi-(^J$DTWKS]1d,Ua:bm4kW!L`Q@e2G+9aZI)UH+(RTD~JCUWa,T-03+>G6Aq' );
define( 'LOGGED_IN_KEY',    '#@*As&>!&GC$TN3(?Aw%[vn)y*|%s;K:(gJT>^^(1N^Mce;:fV88}WqzvATM_{*K' );
define( 'NONCE_KEY',        'bm)Jl;q3N)Ik(d5VwV(aCn{oSQ@a@npp)CT&n3-VjV)A^EZDj*|CIO!9JzuX{xjO' );
define( 'AUTH_SALT',        'k!swx+pM98,fgZv*m?ob *SBfnttm~e{!u.6!I]9CeP:G;F@yEuX81C+6<h-B<7*' );
define( 'SECURE_AUTH_SALT', ';hdt:ht{x9yL@F5Vf7.W.iDuPP^B=l*fS)L%3r?IvbKzTpM<?m3fj~0blun.`ag4' );
define( 'LOGGED_IN_SALT',   'Z,H6?c5e!rv;Q{s#1$O^-6kVlphXC|t]9KDqib1`e`o7[,O]oI:n?hS)4e>XI;p.' );
define( 'NONCE_SALT',       '`C~eQ>xp}4gcOAA.DI0Nuw}$%rY:lf;K]TVmSw nr*?qZEx^zEU{cB`ZPLa?b;wk' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
