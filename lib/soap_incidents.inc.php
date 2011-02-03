<?php
// soap_incidents.inc.php - SOAP functions for incidents
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Paul Heaney <paul[at]sitracker.org>

$server->wsdl->addComplexType('incident',
                                        'complexType',
                                        'struct',
                                        'all',
                                        '',
                                        array('incidentid' => array ('name' => 'incidentid', 'type' => 'xsd:int'),
                                                'title' => array ('name' => 'title', 'type' => 'xsd:string'),
                                                'ownerid' => array('name' => 'ownerid', 'type' => 'xsd:int'),
                                                'townerid' => array('name' => 'townerid', 'type' => 'xsd:int'),
                                                'owner' => array('name' => 'owner', 'type' => 'xsd:string'),
                                                'towner' => array('name' => 'towner', 'type' => 'xsd:string'),
                                                'skillid' => array('name' => 'skillid', 'type' => 'xsd:int'),
                                                'skill' => array('name' => 'skill', 'type' => 'xsd:string'),
                                                'maintenanceid' => array('name' => 'maintenanceid', 'type' => 'xsd:int'),
                                                'maintenance' => array('name' => 'maintenance', 'type' => 'xsd:string'),
                                                'priorityid' => array('name' => 'priorityid', 'type' => 'xsd:int'),
                                                'priority' => array('name' => 'priority', 'type' => 'xsd:string'),
                                                'currentstatusid' => array('name' => 'currentstatusid', 'type' => 'xsd:int'),
                                                'currentstatusinternal' => array('name' => 'currentstatusinternal', 'type' => 'xsd:string'),
                                                'currentstatusexternal' => array('name' => 'currentstatusexternal', 'type' => 'xsd:string'),
                                                'servicelevel' => array('name' => 'servicelevel', 'type' => 'xsd:string')
                                            )
                                    );

$server->wsdl->addComplexType('incident_list',
                                            'complexType',
                                            'array',
                                            '',
                                            'SOAP-ENC:Array',
                                            array(),
                                            array( array ('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:incident[]')),
                                            'tns:incident'
                                        );

$server->wsdl->addComplexType('incident_list_response',
                                            'complexType',
                                            'struct',
                                            'all',
                                            '',
                                            array ('incidents' => array('name' => 'incidents', 'type' => 'tns:incident_list'),
                                                    'status' => array('name' => 'status', 'type' => 'tns:status_value')
                                            )
                                        );

$server->register('list_incidents',
        array('sessionid' => 'xsd:string', 'owner' => 'xsd:int', 'type' => 'xsd:int'), // Input
        array('return'  => 'tns:incident_list_response'), // return
        $soap_namespace);


/**
 * Lists a set of incidents
 * @author Paul Heaney
 * @param string $sessionid - the sessionid
 * @param int $owner - List incidents of this owner (0 for all)
 * @param int $status - 0 for ALL, 1 for all Active, 2 for all open
 * @return Array - array of incidents[] Status
 */
function list_incidents($sessionid, $owner=0, $status=1)
{
    global $sit;
    $status = new SoapStatus();

    $incidents = array();

    if (!empty($sessionid) AND validate_session($sessionid))
    {
        if (user_permission($sit[2], 6))
        {         
            /*
             * SELECT i.*, uTOwner.realname AS townerName FROM `users` AS uo, `incidents` AS i  LEFT JOIN `users` AS uTOwner ON uTOwner.id = i.towner WHERE i.owner = uo.id
    
             */
            $sql = "SELECT i.*, uOwner.realname AS ownerName, uTOwner.realname AS townerName, p.name AS priorityName, ";
            $sql .= "s.name AS skill, ist.name AS statusNameInternal, ist.ext_name AS statusNameExternal ";
            $sql .= "FROM `{$GLOBALS['dbIncidentStatus']}` AS ist,  `{$GLOBALS['dbUsers']}` AS uOwner, `{$GLOBALS['dbPriority']}` AS p, ";
            $sql .= "`{$GLOBALS['dbIncidents']}` AS i LEFT JOIN `{$GLOBALS['dbUsers']}` AS uTOwner  ON uTOwner.id = i.towner ";
            $sql .= "LEFT JOIN `{$GLOBALS['dbSoftware']}` AS s ON s.id = i.softwareid ";
            $sql .= " WHERE i.owner = uOwner.id AND i.priority = p.id AND i.status = ist.id ";
            if ($owner > 0) $sql .= "AND (i.owner = {$owner} OR i.towner = {$owner}) ";
    
            switch ($status)
            {
                case 1: $sql .= "AND i.status = ".STATUS_ACTIVE." ";
                    break;
                case 2: $sql .= "AND (i.status != ".STATUS_CLOSED." AND i.status !=  ".STATUS_UNASSIGNED.") ";
                    break;
            }
            debug_log("SQL: {$sql}");
            $result = mysql_query($sql);
            if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
            if (mysql_num_rows($result) > 0)
            {
            	while ($obj = mysql_fetch_object($result))
                {
                	$incident = new Incident();
                    $incident->incidentid = $obj->id;
                    $incident->title = $obj->title;
                    $incident->owner = $obj->ownerName;
                    $incident->ownerid= $obj->owner;
                    $incident->towner = $obj->townerName;
                    $incident->townerid = $obj->towner;
                    $incident->priority = $obj->priorityName;
                    $incident->priorityid = $obj->priority;
                    $incident->currentstatusid = $obj->status;
                    $incident->currentstatusinternal = $GLOBALS[$obj->statusNameInternal];
                    $incident->currentstatusexternal = $GLOBALS[$obj->statusNameExternal];
                    $incident->skill = $obj->skill;
                    $incident->skillid = $obj->softwareid;
                    $incident->maintenanceid = $obj->maintenanceid;
                    $incident->servicelevel = $obj->servicelevel;
                    
                    $incidents[] = $incident;
                }
            }
        }
        else
        {
        	$status->set_error('no_access');
        }
    }
    else
    {
    	$status->set_error('session_not_valid');
    }

    return array('incidents' => $incidents, 'status' => $status->getSOAPArray());
}

?>
