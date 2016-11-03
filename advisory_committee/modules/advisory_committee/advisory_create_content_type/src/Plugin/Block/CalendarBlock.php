<?php
/**
* Creates the year links for advisory committee calendar pages
*
* @Block(
*	id = "advisory_committee_calendar_block",
*	admin_label = @Translation("Advisory Committee year sidebar"),
* )
*/

namespace Drupal\advisory__create_content_type\Plugin\Block;
use Drupal\Core\Block\BlockBase;
use Drupal;

class CalendarBlock extends BlockBase {
	/**
	* {@inheritdoc}
	*/
	public function build() {
		return array(
			'#markup' => 'calendar block',
		);
	}
}

?>
