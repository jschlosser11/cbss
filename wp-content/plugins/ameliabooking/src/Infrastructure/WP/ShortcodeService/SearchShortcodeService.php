<?php
/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\WP\ShortcodeService;

/**
 * Class SearchShortcodeService
 *
 * @package AmeliaBooking\Infrastructure\WP\ShortcodeService
 */
class SearchShortcodeService extends AmeliaShortcodeService
{
    /**
     * @return string
     */
    public static function shortcodeHandler($atts)
    {
        $atts = shortcode_atts(
            [
                'counter' => self::$counter,
                'today'   => null,
            ],
            $atts
        );

        self::prepareScriptsAndStyles();

        ob_start();
        include AMELIA_PATH . '/view/frontend/search.inc.php';
        $html = ob_get_contents();
        ob_end_clean();

        return $html;
    }
}
