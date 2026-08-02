<?php

use libAllure\Form;
use libAllure\Session;
use libAllure\Logger;
use libAllure\ElementInput;
use libAllure\ElementDate;
use libAllure\ElementFile;
use libAllure\ElementCheckbox;
use libAllure\ElementHidden;
use libAllure\ElementTextbox;

class FormEditOrganizer extends Form
{
    public function __construct()
    {
        parent::__construct('formEditOrganizer', 'Edit Organizer');

        if (!Session::isLoggedIn()) {
            redirect('login.php', 'You need to login before editing an organizer.');
        }

        require_once __DIR__ . '/../functionality/inline_edit.php';
        require_once __DIR__ . '/../functionality/lpps.php';

        $organizer = $this->getOrganizer();

        if (!lanlistUserCanEditOrganizer((int) $organizer['id'])) {
            throw new libAllure\exceptions\SimpleFatalError('You do not have permission to edit this organization.');
        }

        if (Session::getUser()->hasPriv('PUBLISH_ORGANIZERS')) {
            $this->addElement(new ElementCheckbox('published', 'Published', $organizer['published']));
        }

        $this->addElement(new ElementInput('title', 'Title', $organizer['title']));
        $this->addElement(new ElementHidden('id', null, $organizer['id']));
        $this->addElement(new ElementInput('websiteUrl', 'Website', $organizer['websiteUrl']));
        $this->getElement('websiteUrl')->setMinMaxLengths(0, 255);
        $this->addElement(new ElementDate('assumedStale', 'Assumed stale since', $organizer['assumedStale']));
        $this->addElement(new ElementInput('genericEmail', 'Generic email', $organizer['genericEmail']));
        $this->addElement(new ElementInput('steamGroupUrl', 'Steam group URL', htmlify($organizer['steamGroupUrl'])));
        $this->getElement('steamGroupUrl')->setMinMaxLengths(0, 255);
        $this->addElement(new ElementInput('discordInviteUrl', 'Discord invite URL', htmlify($organizer['discordInviteUrl'])));
        $this->getElement('discordInviteUrl')->setMinMaxLengths(0, 255);

        $this->addElement(new ElementInput(
            'lppsUrl',
            'LPPS feed URL',
            htmlify($organizer['lppsUrl'] ?? ''),
            'Optional. URL of your <a href="' . LANLIST_LPPS_STANDARD_URL . '" target="_blank" rel="noopener noreferrer">LPPS v2</a> JSON feed — see <a href="' . lanlistLppsInfoPagePath() . '">what LPPS is</a>. Leave blank to manage events only on lanlist. When set, lanlist can crawl this periodically (when the crawl job is enabled).'
        ));
        $this->getElement('lppsUrl')->setMinMaxLengths(0, 512);

        if (lanlistUserCanAdministerOrganizerLpps()) {
            $this->addElement(new ElementCheckbox(
                'lppsAdminDisabled',
                'Disable LPPS crawl (admin)',
                !empty($organizer['lppsAdminDisabled'] ?? 0),
                'When checked, automated LPPS crawls skip this organizer even if a feed URL is set.'
            ));
        }

        $this->addElement(new ElementTextbox('blurb', 'Blurb', $organizer['blurb']));
        $this->addElement(new ElementFile('banner', 'Banner image', null, 'Your organizer banner image. Preferably a PNG. Maximum size after upload is 810×306 (larger images are scaled). You can pick a file above or paste from the clipboard using the box below.'));
        $this->getElement('banner')->tempDir = UPLOAD_TEMP_DIR;
        $this->getElement('banner')->destinationDir = 'resources/images/organizer-logos/';
        $this->getElement('banner')->destinationFilename = $organizer['id'] . '.jpg';
        $this->getElement('banner')->setMaxImageBounds(810, 306);

        $this->addElement(new ElementCheckbox('useFavicon', 'Use site favicon', $organizer['useFavicon'], 'Favicons are collected periodically (about once per day). You can see which favicon the site collected for you at this URL: <a href = "resources/images/organizer-favicons/' . $organizer['id'] . '.png">HERE</a>'));
        $this->addElement(new ElementCheckbox('faviconRefetch', 'Refetch favicon on next crawl', !empty($organizer['faviconRefetch'] ?? 0), 'If checked: the nightly favicon job deletes the downloaded icon for this organizer and downloads it again. This flag turns off automatically after a successful fetch.'));

        $this->addDefaultButtons('Save');

        $this->addScript(<<<'JS'
(function () {
    function findFileInput() {
        var form = document.getElementById('formEditOrganizer');
        if (!form) {
            return null;
        }
        return form.querySelector('input[type="file"][name="formEditOrganizer-banner"]');
    }

    function injectPasteUi(input) {
        var fieldset = input.closest('fieldset');
        if (!fieldset || fieldset.querySelector('#formEditOrganizer-bannerPasteZone')) {
            return;
        }
        fieldset.classList.add('organizer-banner-fieldset');
        var wrap = document.createElement('div');
        wrap.className = 'organizer-banner-paste-wrap';
        wrap.innerHTML = '' +
            '<p class = "description"><img src = "resources/images/icons/help.png" class = "imageIcon" alt = "" /> Paste from clipboard: click in the dashed box, then press <kbd>Ctrl+V</kbd> (Windows/Linux) or <kbd>Cmd+V</kbd> (Mac). The image is attached to the banner file field so you can submit the form as usual—no need to save a file first.</p>' +
            '<div id = "formEditOrganizer-bannerPasteZone" class = "organizer-banner-paste-zone" tabindex = "0" role = "region" aria-label = "Paste organizer banner image from clipboard"><span class = "organizer-banner-paste-hint">Click here, then paste your image</span></div>' +
            '<p id = "formEditOrganizer-bannerPasteStatus" class = "organizer-banner-paste-status" hidden = "hidden"></p>' +
            '<img id = "formEditOrganizer-bannerPastePreview" class = "organizer-banner-paste-preview" alt = "Banner preview after paste" />';
        fieldset.appendChild(wrap);
    }

    function init() {
        var input = findFileInput();
        if (!input) {
            return;
        }
        injectPasteUi(input);

        var zone = document.getElementById('formEditOrganizer-bannerPasteZone');
        var preview = document.getElementById('formEditOrganizer-bannerPastePreview');
        var statusEl = document.getElementById('formEditOrganizer-bannerPasteStatus');
        if (!zone) {
            return;
        }

        var lastPreviewUrl = null;

        function setStatus(msg, isError) {
            if (!statusEl) {
                return;
            }
            if (!msg) {
                statusEl.textContent = '';
                statusEl.hidden = true;
                statusEl.classList.remove('organizer-banner-paste-status--error');
                return;
            }
            statusEl.textContent = msg;
            statusEl.hidden = false;
            if (isError) {
                statusEl.classList.add('organizer-banner-paste-status--error');
            } else {
                statusEl.classList.remove('organizer-banner-paste-status--error');
            }
        }

        zone.addEventListener('paste', function (e) {
            var cd = e.clipboardData;
            if (!cd) {
                return;
            }
            var file = null;
            var i;
            if (cd.files && cd.files.length) {
                for (i = 0; i < cd.files.length; i++) {
                    if (cd.files[i].type.indexOf('image/') === 0) {
                        file = cd.files[i];
                        break;
                    }
                }
            }
            if (!file && cd.items && cd.items.length) {
                for (i = 0; i < cd.items.length; i++) {
                    if (cd.items[i].kind === 'file' && cd.items[i].type.indexOf('image/') === 0) {
                        file = cd.items[i].getAsFile();
                        break;
                    }
                }
            }
            if (!file) {
                setStatus('No image found in the clipboard—copy an image first, then try again.', true);
                return;
            }

            e.preventDefault();
            e.stopPropagation();

            var dt = new DataTransfer();
            var name = file.name || 'clipboard-image';
            if (name.indexOf('.') === -1) {
                var ext = '';
                if (file.type === 'image/png') {
                    ext = '.png';
                } else if (file.type === 'image/jpeg' || file.type === 'image/jpg') {
                    ext = '.jpg';
                } else if (file.type === 'image/gif') {
                    ext = '.gif';
                } else if (file.type === 'image/webp') {
                    ext = '.webp';
                }
                try {
                    name = 'clipboard-' + Date.now() + ext;
                } catch (err) {
                    name = 'clipboard' + ext;
                }
                file = new File([file], name, { type: file.type });
            }
            dt.items.add(file);

            try {
                input.files = dt.files;
            } catch (err) {
                setStatus('Could not attach the pasted image in this browser. Please use Choose file instead.', true);
                return;
            }

            setStatus('Image pasted—will upload when you save.', false);

            if (preview) {
                if (lastPreviewUrl) {
                    URL.revokeObjectURL(lastPreviewUrl);
                }
                try {
                    lastPreviewUrl = URL.createObjectURL(file);
                    preview.src = lastPreviewUrl;
                    preview.style.display = 'block';
                } catch (err2) {
                    preview.style.display = 'none';
                }
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
JS);
    }

    /**
     * @return array<string, mixed>
     */
    private function getOrganizer(): array
    {
        // GET uses ?formEditOrganizer-id=…; POST reuses the same key from the hidden id field.
        $id = 0;
        if (isset($_REQUEST['formEditOrganizer-id']) && $_REQUEST['formEditOrganizer-id'] !== '') {
            $id = (int) $_REQUEST['formEditOrganizer-id'];
        }

        if ($id <= 0) {
            throw new libAllure\exceptions\SimpleFatalError(
                'Organizer id is required to edit an organizer. Open edit from the organizer page.'
            );
        }

        return fetchOrganizer($id);
    }

    public function process()
    {
        global $db;

        require_once __DIR__ . '/../functionality/edit_notifications.php';

        $organizerId = (int) $this->getElementValue('id');
        $before = fetchOrganizer($organizerId);
        $bannerUploaded = $this->getElement('banner')->wasAnythingUploaded();

        require_once __DIR__ . '/../functionality/lpps.php';

        $sql = 'UPDATE organizers SET published = :published, title = :title, websiteUrl = :websiteUrl, assumedStale = :assumedStale, genericEmail = :genericEmail, steamGroupUrl = :steamGroupUrl, discordInviteUrl = :discordInviteUrl, lppsUrl = :lppsUrl, blurb = :blurb, useFavicon = :useFavicon, faviconRefetch = :faviconRefetch';
        if (lanlistUserCanAdministerOrganizerLpps()) {
            $sql .= ', lppsAdminDisabled = :lppsAdminDisabled';
        }
        $sql .= ' WHERE id = :id LIMIT 1';
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', $this->getElementValue('id'));
        $stmt->bindValue(':title', $this->getElementValue('title'));
        $stmt->bindValue(':websiteUrl', $this->getElementValue('websiteUrl'));
        $stmt->bindValue(':assumedStale', $this->getElementValue('assumedStale'));
        $stmt->bindValue(':genericEmail', $this->getElementValue('genericEmail'));
        $stmt->bindValue(':steamGroupUrl', $this->getElementValue('steamGroupUrl'));
        $stmt->bindValue(':discordInviteUrl', $this->getElementValue('discordInviteUrl'));
        $stmt->bindValue(':lppsUrl', trim((string) $this->getElementValue('lppsUrl')) ?: null);
        $stmt->bindValue(':blurb', $this->getElementValue('blurb'));
        $stmt->bindValue(':useFavicon', $this->getElementValue('useFavicon'));
        $stmt->bindValue(':faviconRefetch', $this->getElementValue('faviconRefetch'));

        if (Session::getUser()->hasPriv('PUBLISH_ORGANIZERS')) {
            $stmt->bindValue(':published', $this->getElementValue('published'));
        } else {
            $stmt->bindValue(':published', 0);
        }

        if (lanlistUserCanAdministerOrganizerLpps()) {
            $stmt->bindValue(':lppsAdminDisabled', $this->getElementValue('lppsAdminDisabled') ? 1 : 0);
        }

        $stmt->execute();

        $this->saveOrganizerBanner();

        $after = fetchOrganizer($organizerId);
        $changes = lanlistCollectOrganizerEditChanges($before, $after);
        if ($bannerUploaded) {
            $changes[] = [
                'label' => 'Banner image',
                'old' => '(previous image)',
                'new' => 'Updated',
            ];
        }
        lanlistSendOrganizerEditNotifications(
            $organizerId,
            Session::getUser()->getUsername(),
            $changes
        );

        Logger::messageAudit('Organizer ' . $this->getElementValue('title') . ' (' . $this->getElementValue('id') . ') edited by: ' . Session::getUser()->getUsername(), 'EDIT_ORGANIZER');
        redirect('viewOrganizer.php?id=' . $this->getElementValue('id'), 'Organizer updated.');
    }

    private function saveOrganizerBanner(): void
    {
        $banner = $this->getElement('banner');
        if (!$banner->wasAnythingUploaded()) {
            return;
        }

        $organizerId = (int) $this->getElementValue('id');
        $destDir = $banner->destinationDir;
        $destPath = $destDir . DIRECTORY_SEPARATOR . $banner->destinationFilename;
        $logMeta = ['relatedOrganizer' => $organizerId];
        $user = Session::getUser()->getUsername();

        if (!is_dir($destDir)) {
            Logger::messageWarning(
                'Organizer banner save failed: destination directory does not exist: '
                . $destDir . ' (organizer ' . $organizerId . ', user ' . $user . ')',
                'ORGANIZER_BANNER_SAVE',
                $logMeta
            );
            return;
        }

        if (!is_writable($destDir)) {
            Logger::messageWarning(
                'Organizer banner save failed: permission denied writing to '
                . $destDir . ' (organizer ' . $organizerId . ', user ' . $user . ')',
                'ORGANIZER_BANNER_SAVE',
                $logMeta
            );
            return;
        }

        $phpError = null;
        set_error_handler(static function ($severity, $message) use (&$phpError) {
            $phpError = $message;
            return true;
        });
        try {
            $banner->savePng();
        } finally {
            restore_error_handler();
        }

        clearstatcache(true, $destPath);

        if ($phpError !== null || !is_file($destPath)) {
            $detail = $phpError ?? 'file was not written (possible disk full or permission denied)';
            Logger::messageWarning(
                'Organizer banner save failed: ' . $detail
                . ' (path ' . $destPath . ', organizer ' . $organizerId . ', user ' . $user . ')',
                'ORGANIZER_BANNER_SAVE',
                $logMeta
            );
            return;
        }

        Logger::messageNormal(
            'Organizer banner saved to ' . $destPath
            . ' (organizer ' . $organizerId . ', user ' . $user . ')',
            'ORGANIZER_BANNER_SAVE',
            $logMeta
        );

        global $db;
        $stmt = $db->prepare('UPDATE organizers SET validBanner = 1 WHERE id = :id LIMIT 1');
        $stmt->bindValue(':id', $organizerId, \libAllure\Database::PARAM_INT);
        $stmt->execute();
    }
}
