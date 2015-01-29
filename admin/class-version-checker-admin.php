<?php

/**
 * The dashboard-specific functionality of the plugin.
 *
 * @since      1.0.0
 *
 * @package    Version_Checker
 * @subpackage Version_Checker/admin
 */

/**
 * The dashboard-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the dashboard-specific stylesheet and JavaScript.
 *
 * @package    Version_Checker
 * @subpackage Version_Checker/admin
 * @author     Ben Alderson <bennyalderson@gmail.com>
 */
class Version_Checker_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @var      string    $plugin_name       The name of this plugin.
	 * @var      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the Dashboard.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/version-checker-admin.css', array(), $this->version, 'all' );

	}


	/**
	 * Construct a settings field for setting the list of sites
	 *
	 * @since    1.0.0
	 */
	function site_json_setting_callback() {

		echo('<textarea name="version_checker_site_json" id="version_checker_site_json" rows=10 cols=60>' .
				get_option( 'version_checker_site_json', "[\n  \"http://example.com\"\n]" ) . '</textarea><br /><br />');
	}

	/**
	 * Output the plugin's admin page.
	 *
	 * @since    1.0.0
	 */
	function render_page() {

		// Output main checker onto page
		?>
			<h2>Version Checker</h2>
			The detected latest version is: &quot;<?PHP echo(get_core_updates()[0]->current); ?>&quot;<br>
			<br/> Press <b>"Check!"</b> to check the sites.</p><hr>
			<a id="refresh" class="button postfix success">Check!</a><hr><table id="checker-table" width=100%>
		      <thead>
		        <tr>
		          <th><a class="sort" by="site">URL</a></th>
		          <th width=100><a class="sort" by="type" reverse="true">Site Type</a></th>
		          <th><a class="sort" by="version" reverse="true">Version</a></th>
		          <th width="10"></th>
		        </tr>
		      </thead>
		      <tbody id="resultList">

		      </tbody>
		    </table>
		    <script type="text/template" id="website-row">
		      <td><a target="_blank" href="<%- site %>"><%- site %></a></td>
		      <td><%- type %></td>
		      <td><%- version %></td>
		      <td><a class="refresh">&#x21bb;</a></td>
		    </script><br><hr>
		<?PHP

		// Output settings
		echo('<div class="wrap">');
		echo('<form method="post" action="options.php">');
			settings_fields( 'version_checker' );
			do_settings_sections( 'version_checker_page' );
			submit_button();
		echo('</form>');
		echo('</div>');


		// Queue Scripts and Styles
		wp_enqueue_script( 'underscore', plugin_dir_url( __FILE__ ) . 'js/underscore.min.js', array( 'jquery' ), $this->version, false );
		wp_enqueue_script( 'backbone', plugin_dir_url( __FILE__ ) . 'js/backbone.min.js', array( 'jquery', 'underscore' ), $this->version, false );
		wp_enqueue_script( "version_checker", plugin_dir_url( __FILE__ ) . 'js/version-checker-admin.js', array( 'jquery', 'underscore', 'backbone' ), $this->version, false );

		wp_enqueue_style( 'version-checker-admin' , plugin_dir_url( __FILE__ ) . 'css/version-checker-admin.css', false, $this->version );

	}

	/**
	 * Register our settings.
	 *
	 * @since    1.0.0
	 */
	function register_settings() {

		// Create the checker section
		add_settings_section(
			'version_checker_settings',
			'Settings',
			null,
			'version_checker_page'
		);

		//Add a field for editing the list of sites
		add_settings_field(
			'version_checker_site_json',
			'List of Sites',
			array($this, 'site_json_setting_callback'),
			'version_checker_page',
			'version_checker_settings'
		);

		// Register the setting
		register_setting( 'version_checker', 'version_checker_site_json' );

	}

	/**
	 * Add the admin menu for our page
	 *
	 * @since    1.0.0
	 */
	function add_menu() {

		add_menu_page('Version Checker', 'Version Checker', 'read', 'version_checker_page', array($this, 'render_page'));

	}

	/**
	 * Handle AJAX requests to lookup a site from our javascript
	 *
	 * @since 1.0.0
	 */
	function check_site() {

		// Default to error if nothing is found
		$siteType = 'Error';
		$siteStyles = "other";
		$siteVersion = '?';

		ini_set( 'error_reporting', E_ERROR ); // Disable warnings
		ini_set( 'default_socket_timeout' , 20 ); // Set the timeout to 20s (By default this is a minute)

		// Send response packet to display "Loading" instead of "Waiting..."
		echo( ' ' );
		flush();

		// Chop off trailing slashes
		if( isset( $_POST['site'] ) )
			$url = rtrim( $_POST['site'], '/' );
		else
			$url = rtrim( $argv[1], '/' );

		// Make sure the file starts with "http://". Otherwise we might read from disk
		if( substr( $url, 0, strlen('http://') ) !== 'http://') {

			echo( '{ "url":"' . $url . '", "styles": "other", "type": "Error", "version": "The url must begin with http://"}' );
			wp_die();

		}

		// Load the readme file of the site
		$readme = file_get_contents( $url . '/readme.html' );
		if( $http_response_header[0] == 'HTTP/1.1 200 OK' ){

			$version = explode( 'Version', $readme ); // The version number is after the first occurance of "Version"
			$version = explode( '<', $version[1] )[0]; // The version number is before the next xml tag
			$version = preg_replace( "/[^0-9\.]/", "", $version ); // Remove all non-numeric characters

			$siteType = "WordPress";
			$siteVersion = $version;

			if( $version == get_core_updates()[0]->current )
			{
				$siteStyles = "current";
			}
			else
			{
				$siteStyles = "old";
			}

		}

		// Make sure we got a version
		if( $siteVersion == "" ){

			$siteType = "Unknown";
			$siteStyles = "other";
			$siteVersion = "?";

		}

		// If we didn't find the type
		if( $siteType == "Error" ){

			// Check if the server is alive by trying to load the homepage
			file_get_contents( $url );
			if( $http_response_header[0] == "HTTP/1.1 200 OK" ){

				// This site was not identified, but is alive
				$siteType = "Unknown";
				$siteStyles = "other";
				$siteVersion = $http_response_header[0];

			} else if( $http_response_header[0] != "" ) {

				// This site didn't load correctly
				$siteType = "Unknown";
				$siteStyles = "other";
				$siteVersion = $http_response_header[0];

			} else {

				// No response header was returned
				$siteType = "Unknown";
				$siteStyles = "other";
				$siteVersion = "Unknown Error";

			}

		}

		// Return the response
		echo( json_encode( ["url" => $url, "styles" => $siteStyles, "type" => $siteType, "version" => $siteVersion] ) );

		// Reload the default settings
		ini_restore( 'error_reporting' );
		ini_restore( 'default_socket_timeout' );

		// End output
		wp_die();
	}

}
