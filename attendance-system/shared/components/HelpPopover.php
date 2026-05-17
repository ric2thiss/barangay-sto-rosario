<?php

declare(strict_types=1);

/**
 * Hover/focus help popover (same visual pattern as Attendance Analytics info icons).
 *
 * @param string $title Short heading shown in the panel
 * @param string $body Explanation (escaped as plain text)
 * @param string|null $suffix Optional stable suffix for aria id (letters, numbers, hyphen)
 */
function help_popover(string $title, string $body, ?string $suffix = null): string
{
    static $seq = 0;
    $seq++;
    $safeSuffix = ($suffix !== null && $suffix !== '')
        ? preg_replace('/[^a-zA-Z0-9_-]/', '', $suffix)
        : 'x';
    $uid = $safeSuffix . '-' . $seq;
    $bodyId = 'help-pop-desc-' . $uid;

    $t = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $b = htmlspecialchars($body, ENT_QUOTES, 'UTF-8');
    $ariaLabel = htmlspecialchars('More information: ' . $title, ENT_QUOTES, 'UTF-8');
    $idEsc = htmlspecialchars($bodyId, ENT_QUOTES, 'UTF-8');

    return '<span class="help-popover-anchor">'
        . '<button type="button" class="help-popover-btn" aria-describedby="' . $idEsc . '" aria-label="' . $ariaLabel . '">i</button>'
        . '<div class="help-popover-panel" role="tooltip">'
        . '<p class="help-popover-panel-title">' . $t . '</p>'
        . '<p id="' . $idEsc . '" class="help-popover-panel-body">' . $b . '</p>'
        . '</div></span>';
}
