<?php

/**
 * Plugin Name: Asi Nazionale
 * Description: Un plugin per integrare il sistema di tesseramento di Asi Nazionale con WordPress e permettere agli atleti di scaricare la propria tessera semplicemente inserendo il proprio codice fiscale. Per configurarlo basta inserire le credenziali di accesso al portale di Asi Nazionale nelle impostazioni del plugin.
 * Version: 1.0.0
 * Author: Lorenzo Mazza
 * Author URI: https://mazzalorenzo.com
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: asinazionale
 */

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

define( 'ASINAZIONALE_PLUGIN_PATH', plugin_dir_path(__FILE__));
define( 'ASINAZIONALE_PLUGIN_URL', plugin_dir_url(__FILE__));
define( 'ASINAZIONALE_PLUGIN', plugin_basename(__FILE__));

require_once dirname(__FILE__) . '/includes/Core/Autoloader.php';

Asinazionale\Core\Autoloader::register();

/*require_once plugin_dir_path(__FILE__) . 'includes/class-asinazionaleWebsite.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-asinazionaleLogin.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-asinazionaleSearch.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-asinazionaleDownload.php';
*/

new Asinazionale\Core\Enqueue();
new Asinazionale\Core\ErrorHandler();
new Asinazionale\Shortcode();

if ( is_admin() ) {
    // we are in admin mode
    (new Asinazionale\Pages\Admin())->init();
}