<?php

require_once 'includes/classes/FormHelpers.php';

use libAllure\Form;
use libAllure\Session;
use libAllure\ElementHidden;
use libAllure\ElementInput;
use libAllure\ElementNumeric;
use libAllure\ElementTextbox;
use libAllure\Logger;

class FormEditUsefulRelatedSite extends Form
{
    public function __construct()
    {
        Session::requirePriv('MANAGE_LINKS');

        parent::__construct('formEditUsefulRelatedSite', 'Edit related site link');

        $site = $this->getSite();

        $this->addElement(new ElementHidden('id', null, $site['id']));
        $this->addElement(new ElementInput('title', 'Title', $site['title']));
        $this->addElement(new ElementInput('url', 'URL', $site['url']));
        $this->getElement('url')->setMinMaxLengths(1, 512);
        $this->addElement(new ElementTextbox(
            'description',
            'Brief description',
            $site['description'],
            'Shown after the link title on the useful related sites page.'
        ));
        $this->addElement(new ElementNumeric('sortOrder', 'Sort order', $site['sortOrder'], 'Lower numbers appear first.'));
        $this->addElement(new ElementTextbox(
            'countries',
            'Countries served',
            lanlistUsefulRelatedSiteCountriesToText($site['countries']),
            'Optional. One country name per line, using the same names as venue countries. Leave blank for links that are not country-specific.'
        ));
        $this->addDefaultButtons('Save link');
    }

    /**
     * @return array<string, mixed>
     */
    private function getSite(): array
    {
        if (isset($_REQUEST['formEditUsefulRelatedSite-id'])) {
            $id = (int) $_REQUEST['formEditUsefulRelatedSite-id'];
        } else {
            $id = (int) $this->getElementValue('id');
        }

        return lanlistFetchUsefulRelatedSite($id);
    }

    public function validateExtended()
    {
        $url = trim((string) $this->getElementValue('url'));
        if ($url !== '' && !preg_match('#^https?://#i', $url)) {
            $this->getElement('url')->setValidationError('URL must start with http:// or https://');
        }
    }

    public function process()
    {
        global $db;

        $siteId = (int) $this->getElementValue('id');

        $sql = 'UPDATE useful_related_sites
                SET url = :url, title = :title, description = :description, sortOrder = :sortOrder
                WHERE id = :id LIMIT 1';
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':url', trim((string) $this->getElementValue('url')));
        $stmt->bindValue(':title', trim((string) $this->getElementValue('title')));
        $stmt->bindValue(':description', trim((string) $this->getElementValue('description')));
        $stmt->bindValue(':sortOrder', (int) $this->getElementValue('sortOrder'));
        $stmt->bindValue(':id', $siteId, \libAllure\Database::PARAM_INT);
        $stmt->execute();

        $countries = lanlistParseUsefulRelatedSiteCountriesText((string) $this->getElementValue('countries'));
        lanlistReplaceUsefulRelatedSiteCountries($siteId, $countries);

        Logger::messageAudit(
            'Related site link "' . $this->getElementValue('title') . '" (' . $siteId . ') updated by '
            . Session::getUser()->getUsername(),
            'EDIT_USEFUL_RELATED_SITE'
        );

        redirect('listUsefulRelatedSites.php', 'Related site link updated.');
    }
}
