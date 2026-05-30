<?php

use libAllure\SchedulerTask;

require_once __DIR__ . '/../functionality/post_event_reminders.php';

class ScheduledTaskPostEventReminders extends SchedulerTask
{
    /** @var array{eventsConsidered: int, organizersEmailed: int, emailsSent: int, skippedHasUpcoming: int, skippedNoRecipients: int, skippedUserCap: int} */
    private $lastRunSummary = [
        'eventsConsidered' => 0,
        'organizersEmailed' => 0,
        'emailsSent' => 0,
        'skippedHasUpcoming' => 0,
        'skippedNoRecipients' => 0,
        'skippedUserCap' => 0,
    ];

    public function execute()
    {
        $this->lastRunSummary = lanlistRunPostEventReminders();
    }

    /**
     * @return array{eventsConsidered: int, organizersEmailed: int, emailsSent: int, skippedHasUpcoming: int, skippedNoRecipients: int, skippedUserCap: int}
     */
    public function getLastRunSummary(): array
    {
        return $this->lastRunSummary;
    }
}
