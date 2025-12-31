<?php
namespace Beer_Supa_Widgets\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Widget_Dashboard extends \Elementor\Widget_Base {

	public function get_name() {
		return 'beer_supa_dashboard';
	}

	public function get_title() {
		return esc_html__( 'Beer Hub Dashboard', 'beer-supa-widgets' );
	}

	public function get_icon() {
		return 'eicon-dashboard';
	}

	public function get_categories() {
		return [ 'general' ];
	}

	protected function render() {
		?>
		<div id="beer-supa-widgets-wrapper">
			<div class="beer-hub-root">
				<!-- React will mount here -->
				<div class="text-center p-4">Loading Beer Hub...</div>
			</div>
		</div>
		<?php
	}
}
