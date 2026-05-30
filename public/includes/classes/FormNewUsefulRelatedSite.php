<?php

require_once 'includes/classes/FormHelpers.php';

use libAllure\Form;
use libAllure\Session;
use libAllure\ElementHtml;
use libAllure\ElementInput;
use libAllure\ElementNumeric;
use libAllure\ElementTextbox;
use libAllure\Logger;

class FormNewUsefulRelatedSite extends Form
{
    public function __construct()
    {
        Session::requirePriv('MANAGE_LINKS');

        parent::__construct('newUsefulRelatedSite', 'New related site link');

        $this->addElement(new ElementInput('title', 'Title'));
        $this->addElement(new ElementInput('url', 'URL'));
        $this->getElement('url')->setMinMaxLengths(1, 512);
        $this->addElement(new ElementTextbox(
            'description',
            'Brief description',
            null,
            'Shown after the link title on the useful related sites page.'
        ));
        $this->addElement(new ElementNumeric('sortOrder', 'Sort order', 0, 'Lower numbers appear first.'));
        $this->addElement(new ElementTextbox(
            'countries',
            'Countries served',
            '',
            'Optional. One country name per line, using the same names as venue countries (see the country dropdown when editing a venue). Leave blank for links that are not country-specific.'
        ));
        $this->addDefaultButtons('Create link');
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

        $sql = 'INSERT INTO useful_related_sites (url, title, description, sortOrder)
                VALUES (:url, :title, :description, :sortOrder)';
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':url', trim((string) $this->getElementValue('url')));
        $stmt->bindValue(':title', trim((string) $this->getElementValue('title')));
        $stmt->bindValue(':description', trim((string) $this->getElementValue('description')));
        $stmt->bindValue(':sortOrder', (int) $this->getElementValue('sortOrder'));
        $stmt->execute();

        $siteId = (int) $db->lastInsertId();
        $countries = lanlistParseUsefulRelatedSiteCountriesText((string) $this->getElementValue('countries'));
        lanlistReplaceUsefulRelatedSiteCountries($siteId, $countries);

        Logger::messageAudit(
            'Related site link "' . $this->getElementValue('title') . '" (' . $siteId . ') created by '
            . Session::getUser()->getUsername(),
            'CREATE_USEFUL_RELATED_SITE'
        );

        redirect('listUsefulRelatedSites.php', 'Related site link created.');
    }
}
