<?php


use \libAllure\SchedulerTask;
use \libAllure\Inflector;

require_once 'includes/classes/EventsChecker.php';

class ScheduledTaskNewsletter extends SchedulerTask
{
    private $dateFormat = 'Y-m-d H:i';
    private $newUsers = [];
    private $newEvents = [];
    private $newOrganizers = [];
    private $issuesList = [];
    private $joinRequests = [];


    public function execute()
    {
        $this->newUsers = $this->getNewUsers();
        $this->newEvents = $this->getNewEvents();
        $this->newOrganizers = $this->getNewOrganizers();
        $this->issuesList = $this->getIssuesList();
        $this->joinRequests = $this->getJoinRequests();

        $numUpdates = (count($this->newUsers) + count($this->newEvents) + count($this->newOrganizers) + count($this->issuesList) + count($this->joinRequests));

        if ($numUpdates > 0) {
            sendEmailToAdmins($this->getContent(), 'Admin newsletter for ' . date($this->dateFormat) . ', ' . $numUpdates . ' ' . Inflector::quantify('update', $numUpdates));
        }
    }

    private function getContent()
    {
        global $tpl;
        $tpl->assign('newsletterStartDate', date($this->dateFormat, strtotime($this->lastExecuted)));
        $tpl->assign('newsletterFinishDate', date($this->dateFormat));

        $tpl->assign('listNewUsers', $this->newUsers);
        $tpl->assign('listNewEvents', $this->newEvents);
        $tpl->assign('listNewOrganizers', $this->newOrganizers);
        $tpl->assign('listJoinRequests', $this->joinRequests);

        $tpl->assign('issuesList', $this->issuesList);

        return $tpl->fetch('newsletter.tpl');
    }

    private function getIssuesList()
    {
        global $db;

        $sql = 'SELECT e.id, e.title, o.title AS organizerTitle, e.* FROM events e LEFT JOIN organizers o ON e.organizer = o.id WHERE e.dateFinish > now() ';
        $result = $db->query($sql);
        $events = $result->fetchAll();

        $checker = new EventsChecker($events);
        $checker->checkAllEvents();
        $events = $checker->getEventsList();

        return $events;
    }

    private function getJoinRequests()
    {
        global $db;

        $sql = 'SELECT r.id, u.username, o.title AS organizerName FROM organization_join_requests r JOIN users u ON r.user = u.id JOIN organizers o ON r.organizer = o.id ';
        $stmt = $db->query($sql);

        return $stmt->fetchAll();
    }

    private function getNewUsers()
    {
        global $db;

        $sql = 'SELECT u.username, u.id FROM users u WHERE u.registered > :lastUpdated ';
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':lastUpdated', $this->lastExecuted);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    private function getNewEvents()
    {
        global $db;

        $sql = 'SELECT e.id, e.title, u.username AS createdBy FROM events e JOIN users u ON e.createdBy = u.id WHERE e.createdDate > :lastUpdated ';
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':lastUpdated', $this->lastExecuted);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    private function getNewOrganizers()
    {
        global $db;

        $sql = 'SELECT o.id, o.title FROM organizers o WHERE o.created > :lastUpdated';
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':lastUpdated', $this->lastExecuted);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
