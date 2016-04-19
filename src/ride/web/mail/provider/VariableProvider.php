<?php

namespace ride\web\mail\provider;

/**
 * The variable provider interface
 */
interface VariableProvider {

    /**
     * Return the model which can be parsed with this provider
     *
     * @return string
     */
    public function getModel();

    /**
     * Get variables which can be parsed with this provider
     *
     * @return array
     */
    public function getAvailableVariables();

    /**
     * Decorate an entry and return its variable array
     *
     * @return array
     */
    public function decorate($entry);

}
