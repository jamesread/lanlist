<?php

use libAllure\Inflector;
use libAllure\SchedulerTask;

require_once __DIR__ . '/../functionality/site_checks.php';
require_once __DIR__ . '/../functionality/moderator_metrics.php';

class ScheduledTaskNewsletter extends SchedulerTask
{
    private $dateFormat = 'Y-m-d H:i';
    private $subjectDateFormat = 'D j M';
    /** @var array{updateCount: int, emailSent: bool} */
    private $lastRunSummary = ['updateCount' => 0, 'emailSent' => false];

    public function execute()
    {
        $panel = lanlistFetchModeratorPanelData();
        $numUpdates = lanlistModeratorPanelIssueCount($panel);

        $this->lastRunSummary = [
            'updateCount' => $numUpdates,
            'emailSent' => false,
        ];

        if ($numUpdates > 0) {
            $sentCount = sendModeratorNewsletter(
                $this->getContent($panel),
                'Moderator newsletter for ' . date($this->subjectDateFormat)
                    . ', ' . $numUpdates . ' ' . Inflector::quantify('item', $numUpdates)
            );
            $this->lastRunSummary['emailSent'] = $sentCount > 0;
        }
    }

    /**
     * @return array{updateCount: int, emailSent: bool}
     */
    public function getLastRunSummary(): array
    {
        return $this->lastRunSummary;
    }

    /**
     * @param array{
     *     eventsWithIssues: array<int, array<string, mixed>>,
     *     eventsWithSilencedTicketWarning: array<int, array<string, mixed>>,
     *     unpublishedOrganizers: array<int, array<string, mixed>>,
     *     organizersWithNoEvents: array<int, array<string, mixed>>,
     * } $panel
     */
    private function getContent(array $panel): string
    {
        global $tpl;

        $tpl->assign('newsletterFinishDate', date($this->dateFormat));
        $tpl->assign('listEventsWithIssues', $panel['eventsWithIssues']);
        $tpl->assign('listEventsWithSilencedTicketWarning', $panel['eventsWithSilencedTicketWarning']);
        $tpl->assign('listUnpublishedOrganizers', $panel['unpublishedOrganizers']);
        $tpl->assign('listOrganizers', $panel['organizersWithNoEvents']);
        $tpl->assign('moderatorImpact', lanlistFetchModeratorImpactMetrics(null, null, $panel));
        $tpl->assign('siteBaseUrl', SITE_BASE_URL);
        $tpl->assign('siteTitle', SITE_TITLE);

        return $tpl->fetch('newsletter.tpl');
    }
}
