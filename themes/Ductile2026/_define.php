<?php

/**
 * @brief Ductile 2026, Refresh of ductile Dotclear 2 theme
 *
 * @package Dotclear
 * @subpackage Themes
 *
 * @copyright Kozlika, Franck Paul and contributors
 * @copyright GPL-2.0-only
 */
if (isset($this) && is_object($this) && method_exists($this, 'registerModule') && isset($this->id) && is_string($this->id)) {
    $this->registerModule(
        'Ductile 2026',
        'Refreshing Ductile',
        'Kozlika',
        '1.0',
        [
            'requires' => [['core', '2.38']],
            'type'     => 'theme',
        ]
    );
}
