<?php
/*
Plugin Name: Favorite Plugins
Plugin URI: http://japh.wordpress.com/plugins/favorite-plugins
Description: Quickly and easily access and install your favorited plugins from WordPress.org, right from your dashboard.
Version: 0.5
Author: Japh
Author URI: http://japh.wordpress.com
License: GPL2
*/

/*  Copyright 2012  Japh  (email : wordpress@japh.com.au)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
 * Favorite Plugins
 *
 * Quickly and easily access and install your favorited plugins from
 * WordPress.org, right from your dashboard.
 *
 * @package JaphFavoritePlugins
 * @author Japh <wordpress@japh.com.au>
 * @copyright 2012 Japh
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt GPL2
 * @version 0.5
 * @link http://japh.wordpress.com/plugins/favorite-plugins
 * @since 0.1
 */

// Plugin folder URL
if ( ! defined( 'JFP_PLUGIN_URL' ) ) {
	define( 'JFP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

// Plugin folder path
if ( ! defined(' JFP_PLUGIN_DIR' ) ) {
	define( 'JFP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

// Plugin base file
if ( ! defined( 'JFP_PLUGIN_FILE' ) ) {
	define( 'JFP_PLUGIN_FILE', __FILE__ );
}

/**
 * Main class for the Favourite Plugins plugin
 *
 * @package JaphFavoritePlugins
 * @copyright 2012 Japh
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt GPL2
 * @version 0.5
 * @since 0.1
 */
class Japh_Favorite_Plugins {

	public $version = '0.5';
	public $username = null;

	/**
	 * Constructor for the plugin's main class
	 *
	 * @since 0.1
	 */
	function __construct() {

		$current_version = get_option( 'jfp_favourite_plugins_version' );

		if ( $current_version != $this->version ) {
			update_option( 'jfp_favourite_plugins_version', $this->do_update( $current_version ) );
		}

		add_action( 'init', array( &$this, 'textdomain' ) );
		add_filter( 'install_plugins_tabs', array( &$this, 'add_favorites_tab' ) );

		add_action( 'install_plugins_pre_favorites', array( &$this, 'do_favorites_tab' ) );
		add_action( 'install_plugins_favorites', array( &$this, 'install_plugins_favorites' ), 10, 1 );
		add_action( 'install_plugins_favorites', 'display_plugins_table');

		$this->username = get_option( 'jfp_favorite_user' );

	}

	/**
	 * Housekeeping things for plugin activation
	 *
	 * @since 0.1
	 */
	function activate() {

		add_option( 'jfp_favorite_user' );

	}

	/**
	 * Housekeeping things for plugin deactivation
	 *
	 * @since 0.1
	 */
	function deactivate() {

		delete_option( 'jfp_favorite_user' );

	}

	/**
	 * Add a Favorites tab to the install plugins tabs
	 *
	 * This method also checks if there is already a Favorites tab,
	 * which is potentially coming to WordPress core in 3.5
	 *
	 * @since 0.1
	 * @param array $tabs The array of existing install plugins tabs
	 * @return array The new array of install plugins tabs
	 */
	function add_favorites_tab( $tabs ) {

		$tabs['favorites'] = __( 'Favorites', 'jfp' );
		return $tabs;

	}

	/**
	 * Output contents of the Favorites tab
	 *
	 * Props @Otto42 : http://core.trac.wordpress.org/ticket/22002
	 * Any code here from Otto is used with permission.
	 *
	 * @since 0.1
	 * @param array $paged The current page for the tab
	 * @return void
	 */
	function do_favorites_tab() {
		global $wp_list_table;

		$this->username = isset( $_REQUEST['user'] ) ? stripslashes( $_REQUEST['user'] ) : $this->username;
		update_option( 'jfp_favorite_user', $this->username );

		$args = array( 'user' => $this->username );
		$api = plugins_api( 'query_plugins', $args );

		$wp_list_table->items = $api->plugins;
		$wp_list_table->set_pagination_args(
			array(
				'total_items' => $api->info['results'],
				'per_page' => 10,
			)
		);
	}

	/**
	 * Output username form at the top of the favorite plugins table
	 *
	 * Props @Otto42 : http://core.trac.wordpress.org/ticket/22002
	 * Any code here from Otto is used with permission.
	 *
	 * @since 0.5
	 * @param int $page Current pagination number
	 * @return void
	 */
	function install_plugins_favorites( $page = 1 ) {
		$this->username = isset( $_REQUEST['user'] ) ? stripslashes( $_REQUEST['user'] ) : $this->username;
		?>
			<h4><?php _e( 'Find Favorite Plugins for a WordPress.org username:' ); ?></h4>
			<form method="post" enctype="multipart/form-data" action="<?php echo self_admin_url( 'plugin-install.php?tab=favorites' ); ?>">
				<label class="screen-reader-text" for="user"><?php _e( 'WordPress.org username' ); ?></label>
				<input type="search" id="user" name="user" value="<?php echo esc_attr( $this->username ); ?>" />
				<input type="submit" class="button" value="<?php esc_attr_e( 'Find Favorites' ); ?>" />
			</form>
		<?php
	}

	/**
	 * Loads the plugin's translations
	 *
	 * @since 0.1
	 * @return void
	 */
	function textdomain() {

		// Setup plugin's language directory and filter
		$jfp_language_directory = dirname( plugin_basename( JFP_PLUGIN_FILE ) ) . DIRECTORY_SEPARATOR . 'languages' . DIRECTORY_SEPARATOR;
		$jfp_language_directory = apply_filters( 'jfp_language_directory', $jfp_language_directory );

		// Load translations
		load_plugin_textdomain( 'jfp', false, $jfp_language_directory );

	}

	/**
	 * A simple function to handle any cleanup during an update
	 *
	 * @since 0.2
	 * @param string $current_version Provides the current version installed for comparison
	 * @return void
	 */
	function do_update( $current_version ) {

		switch ( $current_version ) {
			case '0.1':
				delete_option( 'jfp_favorite_plugins' );
		}

		return $this->version;
	}

}

// Kick everything into action...
$japh_favorite_plugins = new Japh_Favorite_Plugins();
