<?php

declare(strict_types=1);

/**
 * libAllure ErrorHandler listener: notify via the local apprise CLI.
 *
 * Callback signature: ($trigger, $message, $code, $file, $line, $stacktrace)
 */
function notifyErrorViaApprise($trigger, $message, $code = null, $file = null, $line = null, $stacktrace = null): void
{
    $bin = (defined('APPRISE_BIN') && (string) APPRISE_BIN !== '')
        ? (string) APPRISE_BIN
        : 'apprise';

    $title = '[lanlist] ' . (string) $trigger;

    $body = (string) $message;
    if ($code !== null && $code !== '') {
        $body .= "\nCode: " . $code;
    }
    if ($file !== null && $file !== '') {
        $body .= "\n" . $file . ':' . $line;
    }
    if (isset($_SERVER['REQUEST_URI'])) {
        $body .= "\nURI: " . $_SERVER['REQUEST_URI'];
    }
    if (!empty($_SERVER['HTTP_REFERER'])) {
        $body .= "\nReferrer: " . $_SERVER['HTTP_REFERER'];
    }

    $username = null;
    $userId = null;
    try {
        if (class_exists(\libAllure\Session::class, false) && \libAllure\Session::isLoggedIn()) {
            $user = \libAllure\Session::getUser();
            $username = $user->getUsername();
            $userId = $user->getId();
        }
    } catch (\Throwable $e) {
        // Session may be unavailable during early bootstrap errors.
    }
    if ($username !== null && $username !== '') {
        $body .= "\nUser: " . $username;
        if ($userId !== null) {
            $body .= ' (id=' . $userId . ')';
        }
    } else {
        $body .= "\nUser: guest";
    }

    $shortTrace = formatAppriseShortStacktrace($stacktrace);
    if ($shortTrace !== '') {
        $body .= "\nTrace:\n" . $shortTrace;
    }

    $cmd = [$bin, '-n', 'failure', '-t', $title, '-b', $body];

    if (defined('APPRISE_CONFIG') && (string) APPRISE_CONFIG !== '') {
        $cmd[] = '-c';
        $cmd[] = (string) APPRISE_CONFIG;
    }

    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $proc = @proc_open($cmd, $descriptors, $pipes, null, null, ['bypass_shell' => true]);
    if (!is_resource($proc)) {
        error_log('notifyErrorViaApprise: failed to start apprise');

        return;
    }

    fclose($pipes[0]);
    stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    $exit = proc_close($proc);
    if ($exit !== 0) {
        error_log('notifyErrorViaApprise: apprise exited ' . $exit . ' stderr=' . trim((string) $stderr));
    }
}

/**
 * Format a compact top-of-stack for notifications (no args; max 5 frames).
 */
function formatAppriseShortStacktrace($stacktrace, int $maxFrames = 5): string
{
    if ($stacktrace === null || $stacktrace === '') {
        return '';
    }

    if (!is_array($stacktrace)) {
        $lines = preg_split("/\r\n|\n|\r/", (string) $stacktrace) ?: [];
        $lines = array_values(array_filter($lines, static fn ($line) => trim((string) $line) !== ''));

        return implode("\n", array_slice($lines, 0, $maxFrames));
    }

    if ($stacktrace === []) {
        return '';
    }

    $frames = [];
    foreach (array_slice($stacktrace, 0, $maxFrames) as $i => $point) {
        if (!is_array($point)) {
            continue;
        }

        $file = isset($point['file']) ? (string) $point['file'] : '(none)';
        $line = isset($point['line']) ? (string) $point['line'] : '?';
        $call = isset($point['function']) ? (string) $point['function'] : '(unknown)';
        if (isset($point['class']) && $point['class'] !== '') {
            $type = isset($point['type']) ? (string) $point['type'] : '::';
            $call = $point['class'] . $type . $call;
        }

        $frames[] = '#' . $i . ' ' . $file . ':' . $line . ' ' . $call . '()';
    }

    return implode("\n", $frames);
}
