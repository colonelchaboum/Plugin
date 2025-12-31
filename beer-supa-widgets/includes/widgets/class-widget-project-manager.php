<?php
namespace Beer_Supa_Widgets\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Widget_Project_Manager extends \Elementor\Widget_Base {

	public function get_name() {
		return 'beer_supa_project_manager';
	}

	public function get_title() {
		return esc_html__( 'Beer Project Manager', 'beer-supa-widgets' );
	}

	public function get_icon() {
		return 'eicon-folder-o';
	}

	public function get_categories() {
		return [ 'general' ];
	}

	protected function render() {
		?>
		<div id="beer-supa-widgets-wrapper">
			<div class="project-manager-root">
				<!-- React will mount here -->
				<div class="text-center p-4">Loading Projects...</div>
			</div>
		</div>
		<?php
	}
}
