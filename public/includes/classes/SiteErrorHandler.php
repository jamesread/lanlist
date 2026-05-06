<?php

use libAllure\Logger;

class SiteErrorHandler extends \libAllure\ErrorHandler
{
    public function renderSfe(\Throwable $e)
    {
        $this->logClientFacingException($e, 'SimpleFatalError');

        require_once 'includes/widgets/header.minimal.php';

        if ($this->exposeExceptionDetailsPublicly()) {
            echo '<p><span class = "karmaBad">Error: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</span></p>';
            echo '<p>' . htmlspecialchars($e->getFile() . ':' . $e->getLine(), ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</p>';
        } else {
            echo '<p><span class = "karmaBad">Something went wrong while handling your request.</span></p>';
        }

        echo '<p>You can try using your browser\'s back button and submitting again. If the problem persists, please <a href = "contact.php">contact us</a> and explain what you were trying to do.</p>';

        require_once 'includes/widgets/footer.minimal.php';

        exit;
    }

    protected function exposeExceptionDetailsPublicly(): bool
    {
        return defined('DEBUG_MODE') && DEBUG_MODE === true;
    }

    protected function logClientFacingException(\Throwable $e, string $context): void
    {
        $metadata = '';

        if (isset($_SERVER['REQUEST_URI'])) {
            $metadata .= ' Request URI: ' . $_SERVER['REQUEST_URI'];
        }

        if (isset($_SERVER['REMOTE_ADDR'])) {
            $metadata .= ' Remote addr: ' . $_SERVER['REMOTE_ADDR'];
        }

        if (
            class_exists(\libAllure\Session::class, false)
            && class_exists(\libAllure\User::class, false)
            && \libAllure\Session::isLoggedIn()
        ) {
            $metadata .= ' User:' . \libAllure\Session::getUser()->getUsername();
        }

        error_log(sprintf(
            '%s: %s at %s:%d%s',
            $context,
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $metadata
        ));

        if (class_exists(\libAllure\Logger::class, false)) {
            Logger::messageWarning(
                $context . '; '
                . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine()
                . $metadata
                . ' Trace: '
                . $e->getTraceAsString()
            );
        }
    }
}