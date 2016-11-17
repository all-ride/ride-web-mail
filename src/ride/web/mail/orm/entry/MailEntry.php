<?php

namespace ride\web\mail\orm\entry;

use ride\application\orm\entry\MailEntry as OrmMailEntry;

use ride\library\orm\entry\GenericEntry;

/**
 * Class MailEntry
 */
class MailEntry extends OrmMailEntry {

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

}
