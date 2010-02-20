<?php
// soap_users.inc.php - SOAP functions for users
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Paul Heaney <paul[at]sitracker.org>

$server->wsdl->addComplexType('user',
                                        'complexType',
                                        'struct',
                                        'all',
                                        '',
                                        array('userid' => array ('name' => 'userid', 'type' => 'xsd:int'),
                                                'username' => array ('name' => 'username', 'type' => 'xsd:string'),
                                                'realname' => array ('name' => 'realname', 'type' => 'xsd:string'),
                                                'roleid' => array('name' => 'roleid', 'type' => 'xsd:int'),
                                                'group' => array('name' => 'group', 'type' => 'tns:group'),
                                                'jobtitle' => array ('name' => 'jobtitle', 'type' => 'xsd:string'),
                                                'email' => array ('name' => 'email', 'type' => 'xsd:string'),
                                                'phone' => array ('name' => 'phone', 'type' => 'xsd:string'),
                                                'mobile' => array ('name' => 'mobile', 'type' => 'xsd:string'),
                                                'fax' => array ('name' => 'fax', 'type' => 'xsd:string'),
                                                'source' => array ('name' => 'source', 'type' => 'xsd:string'),  // Should we reveal this?
                                                'signature' => array ('name' => 'signature', 'type' => 'xsd:string'),
                                                'status' => array ('name' => 'status', 'type' => 'xsd:int'),
                                                'message' => array ('name' => 'message', 'type' => 'xsd:string'),
                                                'qualifications' => array ('name' => 'qualifications', 'type' => 'xsd:string'),
                                                'accepting' => array ('name' => 'accepting', 'type' => 'xsd:boolean'),
                                                'holidayentitlement' => array ('name' => 'holidayentitlement', 'type' => 'xsd:float'),
                                                'incident_refresh' => array ('name' => 'incident_refresh', 'type' => 'xsd:int'),
                                                'updateorder' => array ('name' => 'updateorder', 'type' => 'xsd:string'),
                                                'numupdatesview' => array ('name' => 'numupdatesview', 'type' => 'xsd:int'),
                                                'style' => array ('name' => 'stle', 'type' => 'xsd:int'),
                                                'i18n' => array ('name' => 'i18n', 'type' => 'xsd:string')
                                            )
                                    );
                                    
$server->wsdl->addComplexType('user_list',
                                            'complexType',
                                            'array',
                                            '',
                                            'SOAP-ENC:Array',
                                            array(),
                                            array( array ('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:user[]')),
                                            'tns:incident'
                                        );

$server->wsdl->addComplexType('user_list_response',
                                            'complexType',
                                            'struct',
                                            'all',
                                            '',
                                            array ('users' => array('name' => 'users', 'type' => 'tns:user_list'),
                                                    'status' => array('name' => 'status', 'type' => 'tns:status_value')
                                            )
                                        );

$server->wsdl->addComplexType('add_user_response',
                                            'complexType',
                                            'struct',
                                            'all',
                                            '',
                                            array ('userid' => array('name' => 'userid', 'type' => 'xsd:int'),
                                                    'status' => array('name' => 'status', 'type' => 'tns:status_value')
                                            )
                                        );

$server->wsdl->addComplexType('group',
                                        'complexType',
                                        'struct',
                                        'all',
                                        '',
                                        array('groupid' => array ('name' => 'groupid', 'type' => 'xsd:int'),
                                                'name' => array ('name' => 'name', 'type' => 'xsd:string'),
                                                'url' => array('name' => 'url', 'type' => 'xsd:string')
                                            )
                                    );

$server->register('add_user',
        array('sessionid' => 'xsd:string',  'user' => 'tns:user'), // Input
        array('return'  => 'tns:add_user_response'), // return
        $soap_namespace);


?>
