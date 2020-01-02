<?php
/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\WP\ShortcodeService;

/**
 * Class EventsShortcodeService
 *
 * @package AmeliaBooking\Infrastructure\WP\ShortcodeService
 */
class EventsShortcodeService extends AmeliaShortcodeService
{
	/**
	 * @return string
	 */
	public static function shortcodeHandler($atts)
	{
		$atts = shortcode_atts(
			[
				'counter'       => self::$counter,
                'event'         => null,
                'recurring'     => null,
                'tag'           => null,
				'today'         => null,
			],
			$atts
		);

		self::prepareScriptsAndStyles();

		ob_start();
		include AMELIA_PATH . '/view/frontend/events.inc.php';
		$html = ob_get_contents();
		ob_end_clean();

		return $html;
	}
}
