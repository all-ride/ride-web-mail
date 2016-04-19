<?php

namespace ride\web\mail\orm\entry;

use ride\application\orm\entry\MailAddressEntry as OrmMailAddressEntry;

/**
 * Class MailAddressEntry
 */
class MailAddressEntry extends OrmMailAddressEntry {

    /**
     * Get the display name
     *
     * @return string
     */
    public function getDisplayName() {
        if (!parent::getDisplayName()) {
            return $this->getEmail();
        }

        return parent::getDisplayName();
    }

    /**
     * Get a parsed email string
     *
     * @return string
     */
    public function __toString() {
        return $this->getDisplayName() . ' <' . $this->getEmail() . '>';
    }

}
