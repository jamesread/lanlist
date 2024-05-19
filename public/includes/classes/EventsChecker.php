<?php

use libAllure\DatabaseFactory;

class EventsChecker
{
    private $eventsList;
    private $countProblems = 0;

    public function __construct(array $eventsList = null)
    {
        if (empty($eventsList)) {
            $this->eventsList = $this->getInitialEventsList();
        } else {
            $this->eventsList = $eventsList;
        }
    }

    public function getInitialEventsList()
    {
        $sql = 'SELECT e.*, o.id AS organizerId, o.title AS organizerTitle FROM events e LEFT JOIN organizers o ON e.organizer = o.id WHERE e.dateStart > now()';
        $stmt = DatabaseFactory::getInstance()->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getCount()
    {
        return $this->countProblems;
    }

    public function checkAllEvents()
    {
        foreach ($this->eventsList as &$event) {
            try {
                $this->checkPublished($event);
                $this->checkEventWebsite($event);
                $this->checkHasOrganizer($event);
                $this->checkTicketPrices($event);
                $this->checkDurationIsntShort($event);
            } catch (Exception $e) {
                $event['issueDescription'] = $e->getMessage();
                $this->countProblems++;
            }
        }
    }

    private function checkPublished(&$event)
    {
        if ($event['published'] == 0) {
            throw new Exception('Event not published');
        }
    }

    private function checkDurationIsntShort(&$event)
    {
        $diff = (strtotime($event['dateFinish']) - strtotime($event['dateStart']));

        if ($diff <= 0) {
            throw new Exception('Duration of event is 0 minutes:' . $diff);
        }
    }

    public function checkHasOrganizer(&$event)
    {
        if (empty($event['organizer'])) {
            throw new Exception('No organizer.');
        }
    }

    public function checkTicketPrices(&$event)
    {
        if (empty($event['priceInAdv'])) {
            throw new Exception('No cost for tickets in advance');
        }
    }

    public function getEventsList()
    {
        $ret = array();

        foreach ($this->eventsList as $event) {
            if (!empty($event['issueDescription'])) {
                $ret[] = $event;
            }
        }

        return $ret;
    }

    private function checkEventWebsite(&$event)
    {
        if (empty($event['website'])) {
            $event['issueDescription'] = 'Event specific website URL is blank';
        }
    }
}
