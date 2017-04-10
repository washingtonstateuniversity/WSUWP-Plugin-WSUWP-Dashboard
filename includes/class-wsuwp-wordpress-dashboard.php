<?php

class WSUWP_WordPress_Dashboard {
	/**
	 * @var WSUWP_WordPress_Dashboard
	 */
	private static $instance;

	/**
	 * Maintain and return the one instance. Initiate hooks when
	 * called the first time.
	 *
	 * @since 0.0.1
	 *
	 * @return \WSUWP_WordPress_Dashboard
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new WSUWP_WordPress_Dashboard();
			self::$instance->setup_hooks();
		}
		return self::$instance;
	}

	/**
	 * Setup hooks to include.
	 *
	 * @since 0.0.1
	 */
	public function setup_hooks() {
		add_action( 'wp_dashboard_setup', array( $this, 'remove_dashboard_widgets' ) );
		add_action( 'wp_network_dashboard_setup', array( $this, 'remove_network_dashboard_widgets' ) );
		add_filter( 'admin_footer_text', array( $this, 'admin_footer_text' ), 11 );
		add_action( 'in_admin_footer', array( $this, 'display_shield_in_footer' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_dashboard_stylesheet' ) );
	}


	/**
	 * Enqueue styles specific to the network admin dashboard.
	 */
	public function enqueue_dashboard_stylesheet() {
		if ( 'dashboard-network' === get_current_screen()->id ) {
			wp_enqueue_style( 'wsuwp-dashboard-style', plugins_url( '/css/dashboard-network.css', __DIR__ ), array(), wsuwp_global_version() );
		}
	}

	/**
	 * Remove all of the dashboard widgets and panels when a user logs
	 * in except for the Right Now area.
	 */
	public function remove_dashboard_widgets() {
		remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
		remove_meta_box( 'dashboard_incoming_links' , 'dashboard', 'normal' );
		remove_meta_box( 'tribe_dashboard_widget'   , 'dashboard', 'normal' );
		remove_meta_box( 'dashboard_plugins'        , 'dashboard', 'normal' );
		remove_meta_box( 'dashboard_primary'        , 'dashboard', 'side' );
		remove_meta_box( 'dashboard_secondary'      , 'dashboard', 'side' );
		remove_meta_box( 'dashboard_quick_press'    , 'dashboard', 'side' );
		remove_meta_box( 'dashboard_recent_drafts'  , 'dashboard', 'side' );

		remove_action( 'welcome_panel', 'wp_welcome_panel' );
	}

	/**
	 * Remove all default widgets from the network dashboard.
	 */
	public function remove_network_dashboard_widgets() {
		remove_meta_box( 'dashboard_plugins'          , 'dashboard-network', 'normal' );
		remove_meta_box( 'dashboard_primary'          , 'dashboard-network', 'side' );
		remove_meta_box( 'dashboard_secondary'        , 'dashboard-network', 'side' );

		if ( get_main_network_id() == get_current_network_id() ) { // @codingStandardsIgnoreLine
			$count_title = 'WSUWP Platform Counts';
		} else {
			$network_name = get_site_option( 'site_name' );
			$count_title = esc_html( $network_name ) . ' Counts';
		}
		wp_add_dashboard_widget( 'dashboard_wsuwp_counts', $count_title, array( $this, 'network_dashboard_counts' ) );

		if ( get_main_network_id() == get_current_network_id() ) { // @codingStandardsIgnoreLine
			wp_add_dashboard_widget( 'dashboard_wsuwp_memcached', 'Global Memcached Usage', array( $this, 'global_memcached_stats' ) );
		}
	}

	/**
	 * Provide a widget that displays the counts for networks, sites, and users
	 * when viewing the network administration dashboard.
	 */
	public function network_dashboard_counts() {
		if ( get_current_network_id() == get_main_network_id() ) { // @codingStandardsIgnoreLine
			?>
			<h4>Global</h4>
			<ul class="wsuwp-platform-counts wsuwp-count-above wsuwp-count-thirds">
				<li id="dash-global-networks"><a href="<?php echo esc_url( network_admin_url( 'sites.php?display=network' ) ); ?>"><?php echo absint( wsuwp_network_count() ); ?></a></li>
				<li id="dash-global-sites"><a href="<?php echo esc_url( network_admin_url( 'sites.php' ) ); ?>"><?php echo absint( wsuwp_global_site_count() ); ?></a></li>
				<li id="dash-global-users"><a href="<?php echo esc_url( network_admin_url( 'users.php' ) ); ?>"><?php echo absint( wsuwp_global_user_count() ); ?></a></li>
			</ul>
			<?php
		}
		?>
		<h4>Network</h4>
		<ul class="wsuwp-platform-counts">
			<li id="dash-network-sites"><a href="<?php echo esc_url( network_admin_url( 'sites.php' ) ); ?>"><?php echo esc_html( get_site_option( 'blog_count' ) ); ?></a></li>
			<li id="dash-network-users"><a href="<?php echo esc_url( network_admin_url( 'users.php' ) ); ?>"><?php echo absint( wsuwp_network_user_count( get_current_network_id() ) ); ?></a></li>
		</ul>
		<?php
	}

	/**
	 * Display a dashboard widget with statistics from the Memcached service.
	 */
	public function global_memcached_stats() {
		$a = new Memcached();
		$a->addServer( 'localhost', 11211 );
		$stats = $a->getStats();
		$stats = $stats['localhost:11211'];

		$cache_hits = $stats['get_hits'];
		$cache_per = number_format( 100 * ( $cache_hits / $stats['cmd_get'] ), 0 );

		// Format cache hits to show thousands or millions rather than the long version.
		if ( $cache_hits >= 1000000 ) {
			$cache_hits = number_format( $cache_hits / 1000000, 0 ) . 'M';
		} elseif ( $cache_hits >= 1000 ) {
			$cache_hits = number_format( $cache_hits / 1000, 0 ) . 'k';
		}
		?>
		<h4>Cache Data</h4>
		<ul class="wsuwp-platform-counts wsuwp-count-above">
			<li id="dash-memcached-written"><?php echo esc_html( size_format( $stats['bytes_written'] ) ); ?></li>
			<li id="dash-memcached-read"><?php echo esc_html( size_format( $stats['bytes_read'] ) ); ?></li>
		</ul>

		<h4>Cache Hits</h4>
		<ul class="wsuwp-platform-counts wsuwp-count-above">
			<li id="dash-memcached-gets"><?php echo esc_html( $cache_hits ); ?></li>
			<li id="dash-memcached-getsperc"><?php echo esc_html( $cache_per ); ?>%</li>
		</ul>
		<p>The memcached service has been running for <strong><?php echo esc_html( human_time_diff( time() - $stats['uptime'], time() ) ); ?></strong> and
			has handled <strong><?php echo absint( $stats['total_items'] ); ?> items</strong> over <strong><?php echo absint( $stats['total_connections'] ); ?> connections</strong>.</p>
		<p>Currently, <strong><?php echo absint( $stats['curr_connections'] ); ?> connections</strong> are in use and memcached is storing <strong><?php echo absint( $stats['curr_items'] ); ?>
				items</strong> totalling <strong><?php echo esc_html( size_format( $stats['bytes'] ) ); ?></strong>.</p>
		<?php
	}

	/**
	 * Customize the general footer text in the admin.
	 *
	 * @return string
	 */
	public function admin_footer_text() {
		$wp_text = sprintf( 'Thank you for creating with <a href="%1$s">WordPress</a> at <a href="%2$s">Washington State University</a>.', 'https://wordpress.org/', 'https://wsu.edu' );
		$text = '<span id="footer-thankyou">' . $wp_text . '</span>';

		return $text;
	}

	/**
	 * Display the WSU shield in the footer.
	 */
	public function display_shield_in_footer() {
		echo '<img style="float:left; margin-right:5px;" height="20" src="' . esc_url( plugins_url( '/images/wsu-shield.png', __DIR__ ) ) . '" />';
	}
}
