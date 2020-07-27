<?php
/**
 * La configuration de base de votre installation WordPress.
 *
 * Ce fichier est utilisé par le script de création de wp-config.php pendant
 * le processus d’installation. Vous n’avez pas à utiliser le site web, vous
 * pouvez simplement renommer ce fichier en « wp-config.php » et remplir les
 * valeurs.
 *
 * Ce fichier contient les réglages de configuration suivants :
 *
 * Réglages MySQL
 * Préfixe de table
 * Clés secrètes
 * Langue utilisée
 * ABSPATH
 *
 * @link https://fr.wordpress.org/support/article/editing-wp-config-php/.
 *
 * @package WordPress
 */

// ** Réglages MySQL - Votre hébergeur doit vous fournir ces informations. ** //
/** Nom de la base de données de WordPress. */
define( 'DB_NAME', 'fans-club' );

/** Utilisateur de la base de données MySQL. */
define( 'DB_USER', 'alphabalde' );

/** Mot de passe de la base de données MySQL. */
define( 'DB_PASSWORD', 'E8ny08hJEq4LlDpF' );

/** Adresse de l’hébergement MySQL. */
define( 'DB_HOST', 'localhost:3308' );

/** Jeu de caractères à utiliser par la base de données lors de la création des tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/**
 * Type de collation de la base de données.
 * N’y touchez que si vous savez ce que vous faites.
 */
define( 'DB_COLLATE', '' );

/**#@+
 * Clés uniques d’authentification et salage.
 *
 * Remplacez les valeurs par défaut par des phrases uniques !
 * Vous pouvez générer des phrases aléatoires en utilisant
 * {@link https://api.wordpress.org/secret-key/1.1/salt/ le service de clés secrètes de WordPress.org}.
 * Vous pouvez modifier ces phrases à n’importe quel moment, afin d’invalider tous les cookies existants.
 * Cela forcera également tous les utilisateurs à se reconnecter.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         ';89.F289=3a|EtC9t %3MUg$6t`HPMx4@7A[IWKY)l?.0}}K`e^c`5[V-LDni% 5' );
define( 'SECURE_AUTH_KEY',  'sWdRuWA8W$>gyl9}BX8<Nb<d^qX|()rWSavQDDn6%Y-;%UDn-w/8$9vL^%bV//Ou' );
define( 'LOGGED_IN_KEY',    '8Ns8-MdKo$<Cq!+l#2SuX?Hrn>uE9#[OjFu4geE:m]S]HI>C,7] OJC u,WT_X/D' );
define( 'NONCE_KEY',        'S%u[=%=cnwCRz}(W;G|fK<pX{S/jFl2@%,xk^D+E3HGgW$Z)1 L.Q0d2GyUE<9O.' );
define( 'AUTH_SALT',        'E{e&?fXX!hlivR|%P|P9ksJ~_L*+Ql%V[9bcLW+v0Jy>(nHkXK17H<6j4B:W9D{N' );
define( 'SECURE_AUTH_SALT', 'krmE1!*Pt?JuHHfJo o~w`])|^)ZuVR%5_$LE*Gou(:`8F3R(KtPFlXNP!;gL-1u' );
define( 'LOGGED_IN_SALT',   ' k&Ksl.9ktm9!9lJy^_G6X:^HYkMXSn=jPg8^Rkh|!zKrBhHOy.1u?m1$8w$)rIe' );
define( 'NONCE_SALT',       '2Ucd7!T8FI Ky7aM<*cfKMm*kbK5q}{Tv(9c?dz6t<:oYb4mY^$hz[Pv(_n%iD}+' );
/**#@-*/

/**
 * Préfixe de base de données pour les tables de WordPress.
 *
 * Vous pouvez installer plusieurs WordPress sur une seule base de données
 * si vous leur donnez chacune un préfixe unique.
 * N’utilisez que des chiffres, des lettres non-accentuées, et des caractères soulignés !
 */
$table_prefix = 'wp_';

/**
 * Pour les développeurs : le mode déboguage de WordPress.
 *
 * En passant la valeur suivante à "true", vous activez l’affichage des
 * notifications d’erreurs pendant vos essais.
 * Il est fortemment recommandé que les développeurs d’extensions et
 * de thèmes se servent de WP_DEBUG dans leur environnement de
 * développement.
 *
 * Pour plus d’information sur les autres constantes qui peuvent être utilisées
 * pour le déboguage, rendez-vous sur le Codex.
 *
 * @link https://fr.wordpress.org/support/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );

/* C’est tout, ne touchez pas à ce qui suit ! Bonne publication. */

/** Chemin absolu vers le dossier de WordPress. */
if ( ! defined( 'ABSPATH' ) )
  define( 'ABSPATH', dirname( __FILE__ ) . '/' );

/** Réglage des variables de WordPress et de ses fichiers inclus. */
require_once( ABSPATH . 'wp-settings.php' );
