<?php

use libAllure\Form;
use libAllure\Session;
use libAllure\ElementHidden;
use libAllure\ElementHtml;
use libAllure\Logger;

class FormDeleteUsefulRelatedSite extends Form
{
    public function __construct()
    {
        Session::requirePriv('MANAGE_LINKS');

        parent::__construct('formDeleteUsefulRelatedSite', 'Delete related site link?');

        $site = $this->getSite();

        $this->addElement(new ElementHidden('id', null, $site['id']));
        $this->addElementReadOnly('Title', $site['title'], 'title');
        $this->addElement(new ElementHtml('msg', null, 'Sure? This cannot be undone.'));
        $this->addDefaultButtons('Delete link');
    }

    /**
     * @return array<string, mixed>
     */
    private function getSite(): array
    {
        if (isset($_REQUEST['formDeleteUsefulRelatedSite-id'])) {
            $id = (int) $_REQUEST['formDeleteUsefulRelatedSite-id'];
        } else {
            $id = (int) $this->getElementValue('id');
        }

        return lanlistFetchUsefulRelatedSite($id);
    }

    public function process()
    {
        global $db;

        Session::requirePriv('MANAGE_LINKS');

        $siteId = (int) $this->getElementValue('id');
        $site = lanlistFetchUsefulRelatedSite($siteId);

        lanlistReplaceUsefulRelatedSiteCountries($siteId, []);

        $sql = 'DELETE FROM useful_related_sites WHERE id = :id LIMIT 1';
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', $siteId, \libAllure\Database::PARAM_INT);
        $stmt->execute();

        Logger::messageAudit(
            'Related site link "' . $site['title'] . '" (' . $siteId . ') deleted by '
            . Session::getUser()->getUsername(),
            'DELETE_USEFUL_RELATED_SITE'
        );

        redirect('listUsefulRelatedSites.php', 'Related site link deleted.');
    }
}
