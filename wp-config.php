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
define( 'DB_NAME', 'wp_surfschoollocal' );

/** MySQL database username */
define( 'DB_USER', 'root' );

/** MySQL database password */
define( 'DB_PASSWORD', 'root' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'afm:iDp%WqD+AMD1v -nT~lOrlL@h&A{[w[`1<w0!e>6nUI<5XUl;r)#TwZs{gIk' );
define( 'SECURE_AUTH_KEY',  'U},fndlQn-t)v-L_3x`@ }c(0qwvde$!PJgS5X}L@!W.gh/BSrm@3DYa+i(6/nJj' );
define( 'LOGGED_IN_KEY',    '9-Cp9*$cvx{y4@6)@V_TlV cwcIf)fu0iq$%0C=?+5Rm^]?r2a0tNK3e3=L@O#%,' );
define( 'NONCE_KEY',        'wt=UzpNqktS0yu,OP_eTi.9<t@QyB$:`6;Fy<9d OTnz$h[g`ViYcX7PD:6;&<4B' );
define( 'AUTH_SALT',        'XuEdcBy-4XEyXdK$rS,BHDInR6{*u[s4])22Sq6qdIq9{u)=.T?>3>~G;557&>+c' );
define( 'SECURE_AUTH_SALT', 'E=)Cnd}`4 y^Q]M[OmfgbD00QY95QEd=5]imH8iy6FJL9*K]T,yPW0Y[oymJ^2X9' );
define( 'LOGGED_IN_SALT',   'wfgnU3+^[M:E.bO`qaZch8q&cfC,F4i(l}yPb=37n>-M;y{1G]6x|~&[YMPk<V>N' );
define( 'NONCE_SALT',       '}GP=gs8$Zy0LBJq@>f>RY8_N@q|!IcI$e1Q0hm;HPb3f0OmsOy0I*,6N%Mjql._`' );

/**#@-*/

/**
 * WordPress Database Table prefix.
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
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define( 'WP_DEBUG', true );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __FILE__ ) . '/' );
}

/** Sets up WordPress vars and included files. */
require_once( ABSPATH . 'wp-settings.php' );
