<?php
// soap_types.inc.php - The types used by SIT! soap implementation
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2014 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Paul Heaney <paul[at]sitracker.org>

require (APPLICATION_LIBPATH . 'soap_error_definitions.inc.php');

$server->wsdl->addComplexType('status_value',
                                        'complexType',
                                        'struct',
                                        'all',
                                        '',
                                        array ('value' => array ('name' => 'value', 'type' => 'xsd:int'),
                                                'name' => array ('name' => 'name', 'type' => 'xsd:string'),
                                                'description' => array ('name' => 'description', 'type' => 'xsd:string'))
                                        );

$server->wsdl->addComplexType('login_response',
                                        'complexType',
                                        'struct',
                                        'all',
                                        '',
                                        array('sessionid' => array('name' => 'sessionid', 'type' => 'xsd:string'),
                                                'status' => array('name' => 'status', 'type' => 'tns:status_value'))
                                    );

$server->wsdl->addComplexType('logout_response',
                                        'complexType',
                                        'struct',
                                        'all',
                                        '',
                                        array('status' => array('name' => 'status', 'type' => 'tns:status_value'))
                                    );

/**
 * The class which represents the status which SiT! always returns when using the SOAP API
 * @author Paul Heaney
 */
class SoapStatus
{
    var $value;
    var $name;
    var $description;

    /**
     * Creates a new SoapStatus object.
     * @author Paul Heaney
     */
    function __construct()
    {
        $this->set_error('no_error');
    }

    /**
     * Sets the error code for this object
     * @param string $name. Name of the error as defined in soap_error_definitions
     * @author Paul Heaney
     */
    function set_error($name)
    {
        global $soap_errors;
        if (isset($soap_errors[$name]))
        {
            $this->value = $soap_errors[$name]['value'];
            $this->name = $soap_errors[$name]['name'];
            $this->description = $soap_errors[$name]['description'];
        }
        else
        {
            $this->value = -1;
            $this->name = "Undefined error {$name} occured";
            $this->description = "Undefined error {$name} occured";
        }
    }

    /**
     * Generate the array to be returned by nusoap
     * @return array. Status array
     * @author Paul Heaney
     */
    function getSOAPArray()
    {
        return array('value' => $this->value, 'name' => $this->name, 'description' => $this->description);
    }
}

?>
