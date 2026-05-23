<?php

use libAllure\ElementHidden;
use libAllure\ElementHtml;
use libAllure\Form;
use libAllure\Logger;
use libAllure\Session;

class FormDeleteVenue extends Form
{
    public function __construct()
    {
        parent::__construct('formDeleteVenue', 'Delete venue?');

        requirePriv('SUPERUSER');

        $venue = $this->getVenue();
        $eventCount = $this->countEventsAtVenue((int) $venue['id']);

        $this->addElement(new ElementHidden('id', null, $venue['id']));
        $this->addElementReadOnly('Venue', $venue['title'], 'title');

        if ($eventCount > 0) {
            $this->addElement(new ElementHtml(
                'msg',
                null,
                'This venue is used by ' . $eventCount . ' event(s) and cannot be deleted. Remove or reassign those events first.'
            ));
        } else {
            $this->addElement(new ElementHtml('msg', null, 'Sure? This cannot be undone.'));
            $this->addDefaultButtons('Delete venue');
        }
    }

    private function getVenue()
    {
        if (isset($_REQUEST['formDeleteVenue-id'])) {
            $id = (int) $_REQUEST['formDeleteVenue-id'];
        } else {
            $id = (int) $this->getElementValue('id');
        }

        return fetchVenue($id);
    }

    private function countEventsAtVenue(int $venueId): int
    {
        global $db;

        $sql = 'SELECT COUNT(*) FROM events WHERE venue = :id';
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', $venueId, \libAllure\Database::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    public function process()
    {
        global $db;

        requirePriv('SUPERUSER');

        $venueId = (int) $this->getElementValue('id');
        $venue = fetchVenue($venueId);

        if ($this->countEventsAtVenue($venueId) > 0) {
            redirect(
                'viewVenue.php?id=' . $venueId,
                'Venue cannot be deleted while events still reference it.'
            );
        }

        $sql = 'DELETE FROM venues WHERE id = :id LIMIT 1';
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', $venueId, \libAllure\Database::PARAM_INT);
        $stmt->execute();

        Logger::messageAudit(
            'Venue ' . $venue['title'] . ' (' . $venueId . ') deleted by ' . Session::getUser()->getUsername(),
            'DELETE_VENUE'
        );

        redirect('listVenues.php', 'Venue deleted.');
    }
}
