<?php

namespace ride\web\mail\orm\entry;

use ride\application\orm\entry\MailEntry as OrmMailEntry;

use ride\library\orm\entry\GenericEntry;
use ride\library\system\file\File;

/**
 * Class MailEntry
 */
class MailEntry extends OrmMailEntry {

    protected $attachments = array();

    /**
     * Gets the name of this mail
     * @return string
     */
    public function getName() {
        $title = $this->getTitle();
        if ($title) {
            return $title;
        }

        return $this->getSubject();
    }

    /**
     * Get some info
     *
     * @return string
     */
    public function getInfo() {
        $info = '';
        if ($title = $this->getTitle()) {
            $info .= $title . ' - ';
        }
        $info .= $this->getSubject() . ' - ' . $this->getSender()->getDisplayName();

        return $info;
    }

    /**
     * @param File $file
     */
    public function addAttachment(File $file) {
        $this->attachments[] = $file;
    }

    public function getAttachments() {
        return $this->attachments;
    }

}
