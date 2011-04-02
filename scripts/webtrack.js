// webtrack.js - Main SiT javascript library

// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Javascript/ECMAscript Functions for SiT (previously known as Webtrack) by Ivan Lucas
// Since v3.30 this requires prototype.js

var popwin;
dashletrefresh = new Array();
var isIE = /*@cc_on!@*/false;
var mainframe = '50%';

/**
 * Open a popup window to show incident details
 * @author Ivan Lucas
 * @param string incidentid. The ID of the incident to display
 * @param string win. Window reference
 * @param rtn. Whether to return a refernce to the window object which has just been opened
 */
function incident_details_window(incidentid, win, rtn)
{
    // URL = "incident.php?popup=yes&id=" + incidentid;
    // URL = application_webpath + "incident_details.php?id=" + incidentid + "&win=" + win;
    URL = "incident_details.php?id=" + incidentid + "&win=" + win;
    if (win == 'sit_popup' && popwin)
    {
        popwin.close();
    }
    popwin = window.open(URL, win, "toolbar=yes,status=yes,menubar=no,scrollbars=yes,resizable=yes,width=700,height=600");
    if (rtn == true) return popwin;
}


/**
 * Open a popup window
 * @author Ivan Lucas
 * @param string url. The URL to open in the popup window
 * @param string mini. set to 'mini' to open a compact window
 */
function wt_winpopup(url, mini)
{
    if (mini == 'mini')
    {
        window.open(url, "sit_minipopup", "toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=700,height=600");
    }
    else
    {
        window.open(url, "sit_popup", "toolbar=yes,status=yes,menubar=no,scrollbars=yes,resizable=yes,width=700,height=600");
    }
}


/**
 * Yes/No dialog
 * @param msg string - A message to display
 * @param del - Set to true when the action involves deleting data
 * @returns bool TRUE or false, depending on which button was pressed, yes = true, false = no
 */
function confirm_action(msg, del)
{
    if (del == true && show_confirmation_delete != 'TRUE') return true;
    if (del != true && show_confirmation_caution != 'TRUE') return true;
    if (msg == '') msg = 'Are you sure?';
    return window.confirm(msg);
}


/**
 * @author Tom Gerrard
 * @param int id
 * @note Related to the calendar
 */
function appointment(id)
{

    if ($(id).style.visibility == 'visible')
    {
        $(id).style.visibility = 'hidden';
        $(id).style.display = 'none';
    }
    else
    {
        var parent = $(id).ancestors();
        parent[0].makePositioned();
        $(id).style.visibility = 'visible';
        $(id).style.display = 'block';
    }
}


/**
 * Get some text and display it
 * @author Paul Heaney, Ivan Lucas
 * @param string page
 * @param string component
 * @param string update
 */
function get_and_display(page, component, update)
{
    // Do certain special things for dashlets
    if (component.substr(0,3) == 'win')
    {
        // Get the ID for the refresh icon so we can replace it, store the original first
        var refreshicon = component.replace(/win/, "refresh");
        var origicon = '';
        if (refreshicon != null)
        {
            if ($(refreshicon)) origicon = $(refreshicon).src;
        }

        // If the dashlet content is blank, set a loading image
        var loaderimg = "<p align='center'><img src='"+ application_webpath +"images/ajax-loader.gif' alt=\"{$strLoading}\" /></p>";
        if ($(component).innerHTML.substr(0,7) == '<script') $(component).innerHTML = loaderimg + $(component).innerHTML
    }

    if (update == true)
    {
        if (dashletrefresh[component] != null) dashletrefresh[component].stop();
        dashletrefresh[component] = new Ajax.PeriodicalUpdater(component, page, {
            method: 'get', frequency: 30, decay: 1.25,
            onCreate: function(){
                if (refreshicon != null)
                {
                    $(refreshicon).src = application_webpath + 'images/dashlet-ajax-loader.gif';
                }
            },
            onComplete: function(){
                if (refreshicon != null) $(refreshicon).src = origicon;
            },
            onLoaded: function(){
                if (refreshicon != null) $(refreshicon).src = origicon;
            }
        });
    }
    else
    {
        if (component.substr(0,3) == 'win') dashletrefresh[component].stop();
        new Ajax.Updater(component, page, {
            method: 'get',
            onFailure: function() {
                $(component).innerHTML = 'Error: could not load data: ' + url;
            },
            onCreate: function() {
                if (refreshicon != null)
                {
                    $(refreshicon).src = application_webpath + 'images/dashlet-ajax-loader.gif';
                }
            },
            onComplete: function() {
                if (refreshicon != null) $(refreshicon).src = origicon;
            },
            onLoaded: function() {
                if (refreshicon != null) $(refreshicon).src = origicon;
            }
        });
    }
}


/**
 * @author Unknown ???
 * @param string page
 * @param string component
 */
function ajax_save(page, component)
{
    new Ajax.Request(page, {
    	parameters: $(component).serialize(true)
    });
    $(component).innerHTML = strSaved;
}


/**
 * Delete an option from a HTML select tag
 * @author Unknown ???
 * @note This Javascript code placed in the public domain
          at http://www.irt.org/script/1265.htm
          "Code examples on irt.org can be freely copied and used."
 */
function deleteOption(object,index)
{
    object.options[index] = null;
}


/**
 * Add an option to a HTML select tag
 * @author Unknown ???
 * @note This Javascript code placed in the public domain
          at http://www.irt.org/script/1265.htm
          "Code examples on irt.org can be freely copied and used."
 */
function addOption(object,text,value)
{
    var defaultSelected = true;
    var selected = true;
    var optionName = new Option(text, value, defaultSelected, selected)
    object.options[object.length] = optionName;
}


/**
 * Copy selected options from one HTML select tag to another
 * @author Unknown ???
 * @note This Javascript code placed in the public domain
          at http://www.irt.org/script/1265.htm
          "Code examples on irt.org can be freely copied and used."
 */
function copySelected(fromObject,toObject)
{
    for (var i = 0, l = fromObject.options.length; i < l; i++)
    {
        if (fromObject.options[i].selected)
        {
            addOption(toObject,fromObject.options[i].text, fromObject.options[i].value);
        }
    }
    for (var i = fromObject.options.length - 1; i > -1; i--)
    {
        if (fromObject.options[i].selected) deleteOption(fromObject,i);
    }
}


/**
 * Copy all options from one HTML select tag to another
 * @author Unknown ???
 * @note This Javascript code placed in the public domain
          at http://www.irt.org/script/1265.htm
          "Code examples on irt.org can be freely copied and used."
 */
function copyAll(fromObject, toObject)
{
    for (var i = 0, l = fromObject.options.length; i < l; i++)
    {
        addOption(toObject, fromObject.options[i].text, fromObject.options[i].value);
    }
    for (var i = fromObject.options.length - 1; i > -1; i--)
    {
        deleteOption(fromObject, i);
    }
}

/**
 * @author Unknown ???
 * @note This Javascript code placed in the public domain
          at http://www.irt.org/script/1265.htm
          "Code examples on irt.org can be freely copied and used."
 */
function populateHidden(fromObject,toObject)
{
    var output = '';
    for (var i = 0, l = fromObject.options.length; i < l; i++)
    {
        output += escape(fromObject.name) + '=' + escape(fromObject.options[i].value) + '&';
    }
    // alert(output);
    toObject.value = output;
}


/**
 * Check or uncheck all checkboxes on a form
 * @author Ivan Lucas
 */
function checkAll(formid, checkstatus)
{
    var form = $(formid);
    checkboxes = form.getInputs('checkbox');
    checkboxes.each(function(e) { e.checked = checkstatus});
}


/**
 * Return a random number
 * @author Ivan Lucas
 * @retval int Random number
 */
function get_random()
{
    var ranNum = Math.floor(Math.random() * 1000000000000);
    return ranNum;
}


/**
 * Display/Hide the time to next action fields
 * @author Ivan Lucas
 *
 */
function update_ttna() {
    if ($('ttna_time').checked)
    {
        $('ttnacountdown').show();
        $('timetonextaction_days').focus();
        $('timetonextaction_days').select();
        $('ttnadate').hide();
    }

    if ($('ttna_date').checked)
    {
        $('ttnacountdown').hide();
        $('ttnadate').show();
        $('timetonextaction_date').focus();
        $('timetonextaction_date').select();
    }

    if ($('ttna_none').checked)
    {
        $('ttnacountdown').hide();
        $('ttnadate').hide();
    }
}


/**
 * Check whether a service level is timed when adding a contract
 * @author Unknown ???
 *
 */
function addcontract_sltimed(servicelevel)
{
    new Ajax.Request(application_webpath + 'ajaxdata.php',
        {
            method:'get',
            parameters: {action: 'servicelevel_timed', servicelevel: servicelevel, rand: get_random()},
            onSuccess: function(transport)
            {
                var response = transport.responseText || "no response text";
                if (transport.responseText)
                {
                    if (response == 'TRUE')
                    {
                        $('hiddentimed').show();
                        $('timed').value = 'yes';
                    }
                    else
                    {
                         $('hiddentimed').hide();
                         $('timed').value = 'no';
                    }
                }
            },
            onFailure: function(){ alert('Something went wrong...') }
        });
}


/**
 * @author Paul Heaney
 */
function addservice_showbilling(form)
{
    /*var a = $('billtype');
    alert("A: "+a.value);*/

    var typeValue = Form.getInputs(form,'radio','billtype').find(function(radio) { return radio.checked; }).value;
    // alert("B: "+typeValue);
    if (typeValue == 'billperunit' || typeValue == 'billperincident')
    {
    	if ($('billingsection') != null)
    	{
    		$('billingsection').show();
    	}
        if (typeValue == 'billperunit') $('unitratesection').show();
        else $('unitratesection').hide();
        if (typeValue == 'billperincident') $('incidentratesection').show();
        else $('incidentratesection').hide();
    }
    else
    {
        $('billingsection').hide();
    }
}


/**
 * Hide context help [?] popups
 * @author Ivan Lucas
 */
function hidecontexthelp(event)
{
    var element = event.element();
    if (element.up(1).hasClassName('helplink'))
    {
        element.style.display = 'none';
    }
    else
    {
        element.firstDescendant().style.display = 'none';
    }

    element.stopObserving('blur', hidecontexthelp);
    element.stopObserving('click', hidecontexthelp);
}


/**
 * find the real position of an element
 * @author http://www.quirksmode.org/js/findpos.html
 */
function findPos(obj) {
    var curleft = curtop = 0;
    if (obj.offsetParent) {
        do {
            curleft += obj.offsetLeft;
            curtop += obj.offsetTop;

        } while (obj = obj.offsetParent);
    }
    return [curleft, curtop];
}


/**
 * Show context help [?] popups
 * @author Ivan Lucas
 */
function contexthelp(elem, context, auth)
{
    var epos = findPos(elem);
    span = elem.getElementsByTagName('span');
    span = span[0];
    $(span);
    $(elem);
    span.style.display = 'block';

    var vwidth = document.viewport.getWidth();
    var vheight = document.viewport.getHeight();

    if (epos[0] + 135 > vwidth)
    {
        span.style.left = '-125px';
    }
    else if (epos[1] + 150 > vheight)
    {
        span.style.top = '-20px';
        span.style.left = '5px';
        span.style.width = '250px';
    }
    else
    {
        $(span).style.top = '1em';
        $(span).style.left = '1em';
    }
    if (span.innerHTML == '')
    {
        new Ajax.Request(application_webpath + 'ajaxdata.php',
            {
                method:'get',
                parameters: {action: 'contexthelp', context: context, rand: get_random(), auth: auth},
                onSuccess: function(transport)
                {
                    var response = transport.responseText || "no response text";
                    if (transport.responseText)
                    {
                        span.innerHTML = transport.responseText;
                    }
                },
                onFailure: function(){ alert('Context Help Error\nSorry, we could not retrieve the help tip') }
            });
    }
    span.observe('mouseout', hidecontexthelp);
    span.observe('click', hidecontexthelp);
    elem.observe('mouseout', hidecontexthelp);
    elem.observe('click', hidecontexthelp);
}


/**
 * Open an incident window for the incident number specified in the 'jump to' search field
 */
function jumpto()
{
    incident_details_window($('incident').value, 'incident'+$('incident').value);
}


/**
 * Clear/reset the 'jump to' search field
 */
function clearjumpto()
{
    $('searchfield').value = "";
}


function email_window(incidentid)
{
    URL = application_webpath + "incident_email.php?menu=hide&id=" + incidentid;
    window.open(URL, "email_window", "toolbar=yes,status=yes,menubar=no,scrollbars=yes,resizable=yes,width=700,height=600");
}

function close_window(incidentid)
{
    URL = application_webpath + "incident_close.php?menu=hide&id=" + incidentid;
    window.open(URL, "email_window", "toolbar=yes,status=yes,menubar=no,scrollbars=yes,resizable=yes,width=700,height=600");
}

// INL - switch tab, see php function draw_tabs_submit()
function gotab(tab) {
    document.actiontabs.action.value = tab;
    document.actiontabs.submit();
}

function close_page_redirect(url)
{
    window.opener.location = url;
    window.close();
}


/**
 * Below used for selecting GroupMembership from a select field
 */
function doSelect(select, element)
{
    var includes = $(element);
    for(i = 0; i < includes.length; i++)
    {
        includes[i].selected = select;
    }
}

function groupMemberSelect(group, clear)
{
    if (clear.toUpperCase() == "TRUE")
    {
        doSelect(false, 'include');
    }

    var includes = $('include');
    $a = $(group);
    for(i = 0; i < includes.length; i++)
    {
        if(includes[i].text.indexOf("("+group+")") > -1)
        {
             if ($a.checked == true)
             {
                includes[i].selected = true;
             }
             else
             {
                includes[i].selected = false;
             }
        }
    }
}

function togglePlusMinus(div)
{
    if ($(div).innerHTML == "[+]")
    {
        $(div).innerHTML = '[-]';
    }
    else
    {
        $(div).innerHTML = '[+]';
    }
}


/**
 * Collapses or expands kb article sections as needed during edit
 * Requires scriptaculous/effects.js
 * @author Ivan Lucas
 */
function kbSectionCollapse()
{
    var sections = ['summary', 'symptoms', 'cause', 'question', 'answer', 'solution',
                    'workaround', 'status', 'additionalinformation', 'references'];

    for (var i = 0; i < sections.length; i++)
    {
        var span = sections[i] + 'span';
        var section = sections[i] + 'section';

        if ($(sections[i]).value.length > 0)
        {
            if ($(section).display != 'block') Effect.BlindDown(section, { duration: 0.2 });
            $(span).innerHTML = '[-]';
        }
        else
        {
            //$(section).hide();
            if ($(section).display != 'none') Effect.BlindUp(section, { duration: 0.2 });
            $(span).innerHTML = '[+]';
        }
    }
}


/**
 * Insert BBCode to a textarea or input at the caret point or around current
 * selection
 * @author Ivan Lucas
 * @param string element. ID of the HTML input or textarea
 * @param string The tag to insert
 * @param string the end tag to insert
 */
function insertBBCode(element, tag, endtag)
{
    if (element.length > 0)
    {
        var start = $(element).selectionStart;
        var end = $(element).selectionEnd;

        if ($(element).readAttribute('readonly') != 'readonly')
        {
            $(element).value = $(element).value.substring(0, start) + tag + $(element).value.substring(start, end) + endtag + $(element).value.substring(end, $(element).textLength);
        }
    }
    $(element).focus();
    var caret = end + tag.length + endtag.length;
    $(element).selectionStart = caret;
    $(element).selectionEnd = caret;
}


/**
  * Dismiss a notice without refreshing the page
  * @author Ivan Lucas
  * @param int noticeid. The ID of the notice to dismiss
  * @param int userid The current user ID
**/
function dismissNotice(noticeid, userid)
{
    if (noticeid == 'all') var div = 'noticearea';
    else var div = 'notice' + noticeid;

    new Ajax.Request(application_webpath + 'ajaxdata.php',
            {
                method:'get',
                parameters: {action: 'dismiss_notice', noticeid: noticeid, userid: userid, rand: get_random()},
                onSuccess: function(transport)
                {
                    $(div).fade();
                    $(div).removeClassName('noticebar');
                    if ($$('.noticebar').length < 2) $('dismissall').fade();
                },
                onFailure: function(){ alert('Notice Error\nSorry, we could not dismiss the notice.') }
            });
}


/**
  * Toggle the display of an experimental menu panel
  * @author Ivan Lucas
  * FIXME experimental
*/
function toggleMenuPanel()
{
    alert('hello');
    $('menupanel').toggle();
/*    if ($('menupanel').style.display == 'block')
    {
//         $('mainframe').style.width = mainframe;
        $('menupanel').fade();
//         $('menupanel').style.display = 'none';
    }
    else
    {
//         mainframe = $('mainframe').style.width;
//         $('mainframe').style.width = '80%';
//         $('menupanel').style.display = 'block';
        $('menupanel').appear();
        $('menupanel').style.zindex = 99;
    }*/
}


function enableBillingPeriod()
{
    if ($('timed').checked == true)
    {
        $('engineerBillingPeriod').show();
        $('customerBillingPeriod').show();
        $('limit').show();
        $('allow_reopen').checked = false;
        $('allow_reopen').disable();
    }
    else
    {
        $('engineerBillingPeriod').hide();
        $('customerBillingPeriod').hide();
        $('allow_reopen').enable();
        $('limit').hide();
    }
}


/*  ResizeableTextarea for the Prototype JavaScript framework, version 0.2
 *  (c) 2006 Bermi Ferrer <info -a-t bermi org>
 *
 *  ResizeableTextarea is freely distributable under the terms of an MIT-style license.
 *
 *  Requirements: Prototype JS framework http://prototypejs.org/
 *  Ussage: Add this attribute to the textarea you want to resize
 *
 *    onfocus="new ResizeableTextarea(this);"
 *
/*--------------------------------------------------------------------------*/
ResizeableTextarea = Class.create();
ResizeableTextarea.prototype = {
    initialize: function(element, options) {
        this.element = $(element);
        this.size = parseFloat(this.element.getStyle('height') || '100');
        this.options = Object.extend({
            inScreen: true,
            resizeStep: 10,
            minHeight: this.size
        }, options || {});
        Event.observe(this.element, "keyup", this.resize.bindAsEventListener(this));
        if ( !this.options.inScreen ) {
            this.element.style.overflow = 'hidden';
        }
        this.element.setAttribute("wrap","virtual");
        this.resize();
    },
    resize : function(){
        this.shrink();
        this.grow();
    },
    shrink : function(){
        if ( this.size <= this.options.minHeight ){
            return;
        }
        if ( this.element.scrollHeight <= this.element.clientHeight) {
            this.size -= this.options.resizeStep;
            this.element.style.height = this.size+'px';
            this.shrink();
        }
    },
    grow : function(){
        if ( this.element.scrollHeight > this.element.clientHeight ) {
            if ( this.options.inScreen && (20 + this.element.offsetTop + this.element.clientHeight) > document.body.clientHeight ) {
                return;
            }
            this.size += (this.element.scrollHeight - this.element.clientHeight) + this.options.resizeStep;
            this.element.style.height = this.size+'px';
            this.grow();
        }
    }
}


/**
 * Toggle the enabled/disabled (read-only) state of a multi-select
 * @author Ivan Lucas
 */
function toggle_multiselect(elem)
{
    if ($(elem).disabled)
    {
        alert('enable');
        $(elem).enable();
    }
    else
    {
        $(elem).disable();
    }
}


/**
 * Toggle the checkboxes in a table by clicking on the parent table cell
 * and toggle highlight table rows by clicking on a row cell
 * (only cells without a checkbox)
 * @author Ivan Lucas
 * @param e event
 * @note example: <tr ondblclick='trow(event);'>
 */
function trow(e)
{
    var e = e || window.event;
    var t = e.target || e.srcElement;
    // t is the element that was clicked on

    if ($(t).down(0) && $(t).down(0).type == 'checkbox')
    {
        if (t.down(0).disabled == false)
        {
            if (t.down(0).checked == true) t.down(0).checked = false;
            else t.down(0).checked = true;
        }
    }
    else
    {
        if (t.up(0).hasClassName('shade1') || t.up(0).hasClassName('shade2'))
        {
            t.up(0).toggleClassName('notice');
        }
    }
}


/**
 * Enable/Disable the contact address fields
 * @author Ivan Lucas
 */
function togglecontactaddress()
{
    var setting = false;
    if ($('usesiteaddress').checked == true)
    {
        setting = false;
    }
    else
    {
        setting = true;
    }
    $('address1').disabled = setting;
    $('address2').disabled = setting;
    $('city').disabled = setting;
    $('county').disabled = setting;
    $('country').disabled = setting;
    $('postcode').disabled = setting;
}


function show_status_drop_down()
{
    $('userstatus').hide();
    $('status_drop_down').show();
    $('userstatus_dropdown').focus();
}


function hide_status_drop_down()
{
    $('userstatus').appear();
    $('status_drop_down').hide();
}


function set_user_status()
{
    var userstatus = $('userstatus_dropdown').value;
    new Ajax.Request(application_webpath + 'ajaxdata.php',
            {
                method:'get',
                parameters: {action: 'set_user_status', userstatus: userstatus, rand: get_random()},
                onSuccess: function(transport)
                {
                    var response = transport.responseText || "no response text";
                    if (transport.responseText)
                    {
                        if (response != 'FALSE')
                        {
                            $('userstatus_summaryline').innerHTML = response;
                            // hide_status_drop_down();
                            $('userstatus_dropdown').blur();
                        }
                    }
                },
                onFailure: function()
                {
                    alert('Something went wrong...');
                }
            });
}


function attach_another_file(element)
{
    var max = 0;
    var attachments = $(element).childNodes;
    for ( i = 0; i < attachments.length; i++)
    {
        node = attachments[i];
        if (node instanceof HTMLInputElement)
        {
            var id = node.id;
            var n = parseInt(id.split("_")[1]);
            if (n > max) max = n;
        }
    }
    var next_attach_number = (max+1);
    var br = new Element('br');
    var name = "attachment_"+next_attach_number;
    var input = new Element('input', {'type': 'file', 'id': name, 'name': name, 'size': '40'});
    $(element).appendChild(br);
    $(element).appendChild(input);
}


function ignore_pending_reassignments(incidentid, originalowner)
{
    new Ajax.Request(application_webpath + 'ajaxdata.php',
            {
                method: 'get',
                parameters: {action: 'delete_temp_assign', incidentid: incidentid, originalowner: originalowner},
                onSuccess: function(transport)
                {
                    var response = transport.responseText || "no response text";
                    if (transport.responseText)
                    {
                        if (response == 'OK')
                        {
                            Element.remove('incident'+incidentid);
                        }
                        else if (response == 'NOPERMISSION')
                        {
                            alert('No Permission to ignore incident reassignment');
                        }
                        else
                        {
                            alert ('Something went wrong ignoring reassignment');
                        }
                    }
                },
                onFailure: function()
                {
                    alert('Error ignoring reassignment');
                }
            });
}


function submit_form(form)
{
    $(form).submit();
}


function ldap_browse_window(base, field)
{
    URL = "ldap_browse.php?base=" + base + "&field=" + field;
    window.open(URL, 'ldap_browse', "toolbar=yes,status=yes,menubar=no,scrollbars=yes,resizable=yes,width=300,height=400");
}


function ldap_browse_update_group(dn, fieldName)
{
    field = window.opener.parent.document.getElementById(fieldName); 
    field.value = dn;
    window.close();
}


function ldap_browse_select_container(ldap_base, field)
{
    ldap_type = window.opener.parent.document.getElementById('ldap_type').value;
    ldap_host = window.opener.parent.document.getElementById('ldap_host').value;
    ldap_port = window.opener.parent.document.getElementById('ldap_port').value;
    ldap_protocol = window.opener.parent.document.getElementById('ldap_protocol').value;
    ldap_security = window.opener.parent.document.getElementById('ldap_security').value;
    ldap_bind_user = window.opener.parent.document.getElementById('ldap_bind_user').value;
    ldap_bind_pass = window.opener.parent.document.getElementById('cfgldap_bind_pass').value;

    new Ajax.Request(application_webpath + 'ajaxdata.php',
            {
                method: 'POST', 
                parameters: {action: 'ldap_browse_groups', base: ldap_base, ldap_type: ldap_type, ldap_host: ldap_host, ldap_port: ldap_port,
                ldap_protocol: ldap_protocol, ldap_security: ldap_security, ldap_bind_user: ldap_bind_user, ldap_bind_pass: ldap_bind_pass},
                onCreate: function()
                {
                    $('ldap_browse_contents').innerHTML = "<p align='center'<img src='"+application_webpath + "images/ajax-loader.gif' /><br />Loading</p>";
                },
                onSuccess: function(transport)
                {
                    var response = transport.responseText || "no response text";
                    if (transport.responseText)
                    {
                        var html = 'Current Level: '
    
                            if (ldap_base.length > 0) html += ldap_base;
                            else html += '[root]';
    
                        html += '<table>';
    
                        if (ldap_base.length > 0)
                        {
                            if (ldap_base.indexOf(',') == -1)
                            {
                                parent = '';
                            }
                            else
                            {
                                parent = ldap_base.substring(ldap_base.indexOf(',')+1);
                            }
    
                            html += "<tr><td><a onclick=\"ldap_browse_select_container('"+parent+"', '"+field+"');\" href='javascript:void(0)'>"+icon_navup+"</a></td><td>..</td>";
                            html += "<td><a onclick=\"ldap_browse_select_container('"+parent+"', '"+field+"');\" href='javascript:void(0)'>"+strUp+"</a></td>";
                            html += "</tr>";
                        }
    
                        var data = response.evalJSON();
                        if (data.length == 0)
                        {
                            html += "<tr><td colspan='3'>ERROR</td></tr>";
                        }
                        else
                        {
                            if (data[0].status == 'ok')
                            {
                                for (i = 1; i < data.length; i++)
                                {
                                    html += '<tr>';		            		

                                    if (data[i].type == 'container')
                                    {
                                        html += "<td><a onclick=\"ldap_browse_select_container('"+data[i].dn+"', '"+field+"');\" href='javascript:void(0)'>"+icon_navdown+"</a></td>";
                                        html += "<td><a onclick=\"ldap_browse_select_container('"+data[i].dn+"', '"+field+"');\" href='javascript:void(0)'>"+icon_ldap_container+"</a></td>";
                                        html += "<td><a onclick=\"ldap_browse_select_container('"+data[i].dn+"', '"+field+"');\" href='javascript:void(0)'>"+data[i].cn+"</a></td>";
                                    }
                                    else if (data[i].type == 'group')
                                    {
                                        html += "<td></td>";
                                        html += "<td><a onclick=\"ldap_browse_update_group('"+data[i].dn+"', '"+field+"');\" href='javascript:void(0)'>"+icon_ldap_group+"</a></td>";
                                        html += "<td><a onclick=\"ldap_browse_update_group('"+data[i].dn+"', '"+field+"');\" href='javascript:void(0)'>"+data[i].cn+"</a></td>";
                                    }
    
                                    html += '</tr>';
                                }
                            }
                            else if (data[0].status == 'connectfailed')
                            {
                                html += "<tr><td colspan='3'>"+strLDAPTestFailed+"</td></tr>";
                            }
                            else
                            {
                                html += "<tr><td colspan='3'>"+data[0].status+"</td></tr>";
                            }
                        }
    
                        html += '</table>';
    
                        $('ldap_browse_contents').innerHTML = html;
                    }
                },
                onFailure: function()
                {
                    alert('Error browsing LDAP');
                }
            });
}


/**
 * Check the LDAP details entered and display the results
 * @author Paul heaney
 * @param string statusfield element ID of the DIV that will contain the status text
*/
function checkLDAPDetails(statusfield)
{
   $(statusfield).innerHTML = '<strong>'+strCheckingDetails+'</strong>';

   var server = $('ldap_host').value;
   var port = $('ldap_port').value;
   var type = $('ldap_type').options[$('ldap_type').selectedIndex].value;
   var protocol = $('ldap_protocol').options[$('ldap_protocol').selectedIndex].value;
   var security = $('ldap_security').options[$('ldap_security').selectedIndex].value;
   var user = $('ldap_bind_user').value;
   var password = $('cfgldap_bind_pass').value;
   var userBase = $('ldap_user_base').value;
   var adminGrp = $('ldap_admin_group').value;
   var managerGrp = $('ldap_manager_group').value;
   var userGrp = $('ldap_user_group').value;
   var customerGrp = $('ldap_customer_group').value;

   new Ajax.Request(application_webpath + 'ajaxdata.php',
           {
               method: 'POST', 
               parameters: {action: 'checkldap', ldap_host: server, ldap_type: type, ldap_port: port, ldap_protocol: protocol, ldap_security: security, 
                               ldap_bind_user: user, ldap_bind_pass: password, ldap_user_base: userBase, 
                               ldap_admin_group: adminGrp, ldap_manager_group: managerGrp, ldap_user_group: userGrp, ldap_customer_group: customerGrp},
               onSuccess: function(transport)
               {
                   var response = transport.responseText || "no response text";
                   if (transport.responseText == LDAP_PASSWORD_INCORRECT)
                   {
                       $(statusfield).innerHTML = '<strong>'+strPasswordIncorrect+'</strong>';
                   }
                   else if (transport.responseText == LDAP_BASE_INCORRECT)
                   {
                       $(statusfield).innerHTML = '<strong>'+strLDAPUserBaseDNIncorrect+'</strong>';
                   }
                   else if (transport.responseText == LDAP_ADMIN_GROUP_INCORRECT)
                   {
                       $(statusfield).innerHTML = '<strong>'+strLDAPAdminGroupIncorrect+'</strong>';
                   }
                   else if (transport.responseText == LDAP_MANAGER_GROUP_INCORRECT)
                   {
                       $(statusfield).innerHTML = '<strong>'+strLDAPManagerGroupIncorrect+'</strong>';
                   }
                   else if (transport.responseText == LDAP_USER_GROUP_INCORRECT)
                   {
                       $(statusfield).innerHTML = '<strong>'+strLDAPUserGroupIncorrect+'</strong>';
                   }
                   else if (transport.responseText == LDAP_CUSTOMER_GROUP_INCORRECT)
                   {
                       $(statusfield).innerHTML = '<strong>'+strLDAPCustomerGroupIncorrect+'</strong>';
                   }
                   else if (transport.responseText == LDAP_CORRECT)
                   {
                       $(statusfield).innerHTML = '<strong>'+strLDAPTestSucessful+'</strong>';
                   }
                   else
                   {
                       $(statusfield).innerHTML = '<strong>'+strLDAPTestFailed+'</strong>';
                   }
               },
               onFailure: function()
               {
                   $(statusfield).innerHTML = '<strong>'+strLDAPTestFailed+'</strong>';
               }
           });
}


/**
 * Display/Hide contents of a password field
 * (converts from a password to text field and back)
 * @author Ivan Lucas
 * @param string elem. The ID of the password input HTML element
**/
function password_reveal(elem)
{
   var elemlink = 'link' + elem;
   if ($(elem).type == 'password')
   {
       $(elem).type = 'text';
       $(elemlink).innerHTML = strHide;
   }
   else
   {
       $(elem).type = 'password';
       $(elemlink).innerHTML = strReveal;
   }
}


/**
 * Function to save a draft
 * @param incidentid Incident ID draft is for
 * @param type The type of draft to save, valid options are email or update
 * @author Paul Heaney
 */
function save_draft(incidentid, type){
    var draftid = $('draftid').value;
    var toPass = '';

    if (type == 'update')
    {
        var meta = $('target').value+"|"+$('updatetype').value+"|"+$('cust_vis').checked+"|";
        meta += $('priority').value+"|"+$('newstatus').value+"|"+$('nextaction').value+"|";
        
        toPass = $('updatelog').value;
    }
    else if (type == 'email')
    {
        var meta = $('emailtype').value+"|"+$('newincidentstatus').value+"|"+$('timetonextaction_none').value+"|";
        meta = meta+$('timetonextaction_days').value+"|"+$('timetonextaction_hours').value+"|";
        meta = meta+$('timetonextaction_minutes').value+"||||";
        meta = meta+$('target').value+"|"+$('chase_customer').value+"|";
        meta = meta+$('chase_manager').value+"|"+$('fromfield').value+"|"+$('replytofield').value+"|";
        meta = meta+$('ccfield').value+"|"+$('bccfield').value+"|"+$('tofield').value+"|";
        meta = meta+$('subjectfield').value+"|"+$('bodytext').value+"|"
        meta = meta+$('date').value+"|"+$('time_picker_hour').value+"|"+$('time_picker_minute').value+"|"+$('timetonextaction').value;
        
        toPass = $('bodytext').value;
    }

    if (toPass != '')
    {
        new Ajax.Request(application_webpath + 'ajaxdata.php',
                {
                    method: 'POST', 
                    parameters: {action: 'auto_save', type: type, incidentid: incidentid, draftid: draftid, meta: meta, content: toPass},
                    onSuccess: function(transport)
                    {
                        var response = transport.responseText || "no response text";
                        if (response.responseText != '')
                        {
                            if (draftid == -1)
                            {
                                draftid = response.responseText;
                            }
                            var currentTime = new Date();
                            var hours = currentTime.getHours();
                            var minutes = currentTime.getMinutes();
                            if (minutes < 10)
                            {
                                minutes = "0" + minutes;
                            }
                            var seconds = currentTime.getSeconds();
                            if (seconds < 10)
                            {
                                seconds = "0" + seconds;
                            }
                            $('updatestr').innerHTML = "<a href=\"javascript:save_draft('"+incidentid+"', '"+type+"');\">"+save_icon+"</a> "+info_icon+" " + hours + ':' + minutes + ':' + seconds;
                            $('draftid').value = draftid;
                        }
                    }
                });
    }
}


/**
 * Function to save the layout of the dahboard
 * @author Paul Heaney
 */
function save_dashboard_layout(){
    var toPass = '';
    for (var i = 0; i < 3; i++)
    {
        colid = 'col' + i;
        var col = $(colid).childNodes;
        var s = '';

        for (var x = 0; x < col.length; x++){
            s = s+i+"-"+col.item(x).id.substr(5)+",";
        }
        toPass = toPass+s.substr(0,s.length-1)+",";
    }

    new Ajax.Request(application_webpath + 'ajaxdata.php',
            {
                method: 'POST', 
                parameters: {action: 'storedashboard', val: toPass},
            });

    $('savelayout').style.display='none';
}


//The target drop area contains a snippet of instructional
//text that we want to remove when the first item
//is dropped into it.
function moveItem(draggable, droparea){
    droparea.appendChild(draggable);
    save_dashboard_layout();
}


/**
 * @author Kieran Hogg?
 */
function get_checks()
{
    $('checksbox').show();
    var triggertype = $('triggertype').value;

    new Ajax.Request(application_webpath + 'ajaxdata.php',
            {
                method: 'POST', 
                parameters: {action: 'checkhtml', triggertype: triggertype},
                onSuccess: function(transport)
                {
                    var response = transport.responseText || "no response text";
                    if (response.responseText != '')
                    {
                        $("checkshtml").update(response.responseText);
                    }
                }
            });
}


/**
 * @author Kieran Hogg?
 */
function switch_template()
{
    get_checks();
    //FIXME functionise the js here
    if ($('new_action').value == 'ACTION_NOTICE')
    {
        $('noticetemplatesbox').show();
        var triggertype = $('triggertype').value;
        var triggeraction = $('new_action').value;

        new Ajax.Request(application_webpath + 'ajaxdata.php',
                {
                    method: 'POST', 
                    parameters: {action: 'triggerpairmatch', triggertype: triggertype, triggeraction: triggeraction},
                    onSuccess: function(transport)
                    {
                        var response = transport.responseText || "no response text";
                        if (response.responseText != '')
                        {
                            $(response.responseText).selected = true;
                        }
                    }
                });
        
        $('emailtemplatesbox').hide();
        $('parametersbox').show();
        $('journalbox').hide();
        $('none').hide();
        $('rulessection').show();
    }
    else if ($('new_action').value == 'ACTION_EMAIL')
    {
        $('noticetemplatesbox').hide();
        $('emailtemplatesbox').show();

        var triggertype = $('triggertype').value;
        var triggeraction = $('new_action').value;

        new Ajax.Request(application_webpath + 'ajaxdata.php',
                {
                    method: 'POST', 
                    parameters: {action: 'triggerpairmatch', triggertype: triggertype, triggeraction: triggeraction},
                    onSuccess: function(transport)
                    {
                        var response = transport.responseText || "no response text";
                        if (response.responseText != '')
                        {
                            $(response.responseText).selected = true;
                        }
                    }
                });
        
        $('parametersbox').show();
        $('journalbox').hide();
        $('none').hide();
        $('rulessection').show();

    }
    else if ($('new_action').value == 'ACTION_JOURNAL')
    {
        $('parametersbox').show();
        $('journalbox').show();
        $('emailtemplatesbox').hide();
        $('noticetemplatesbox').hide();
        $('none').hide();
    }
    else
    {
        $('noticetemplatesbox').hide();
        $('emailtemplatesbox').hide();
        $('parametersbox').hide();
        $('journalbox').hide();
        $('none').show();
        $('rulessection').hide();
    }
}


function set_terminated()
{
    if ($('productonly').checked == true)
    {
        $('terminated').disabled = true;
        $('terminated').checked = true;
    }
    else
    {
        $('terminated').disabled = false;
        $('terminated').checked = false;
    }
}


function validate_field(field, error)
{
    if ($(field).value == '')
    {
        alert(error);
        $(field).focus( );
        return false;
    }
}


/**
 * 
 * @returns {Boolean}
 * @author Paul Heaney
 */
function process_billable_incidents_form()
{
    var approval = $('approvalpage');
    var invoice = $('invoicepage');

    var enddate = $('enddate').value;

    var toReturn = true;

    if (invoice.checked)
    {
        toReturn = confirm_action(strAreYouSureUpdateLastBilled);
    }

    return toReturn;
}


/**
 * @author Ivan Lucas
 */
function recordFocusElement(element)
{
   $('focuselement').value = element.identify();
   $('templatevariables').show();
}


/**
 * @author Ivan Lucas
 */
function clearFocusElement()
{
   $('focuselement').value = '';
   $('templatevariables').hide();
}


/**
 * @author Ivan Lucas
 */
function insertTemplateVar(tvar)
{
   var element = $('focuselement').value;
   if (element.length > 0)
   {
       var start = $(element).selectionStart;
       var end = $(element).selectionEnd;
       // alert('start:' + start + '  end: ' + end + 'len: ' + $(element).textLength);
       if ($(element).readAttribute('readonly') != 'readonly')
       {
           $(element).value = $(element).value.substring(0, start) + tvar + $(element).value.substring(end, $(element).textLength);
       }
   }
   else
   {
       alert(strSelectAFieldForTemplates);
   }
}


function enablekb()
{
    var fields = ['kbtitle', 'incsymptoms', 'symptoms', 'inccause', 'cause', 'incquestion', 'question', 'incanswer', 'answer', 'incworkaround', 'workaround', 'incstatus', 'status', 'incadditional', 'additional', 'increferences', 'references'];
    var rows = ['titlerow', 'distributionrow', 'symptomsrow', 'causerow', 'questionrow', 'answerrow', 'workaroundrow', 'statusrow', 'inforow', 'referencesrow'];
    
    if ($('kbtitle').disabled == true)
    {
        // Enable KB
        for (i = 0; i < fields.length; i++)
        {
            $(fields[i]).disabled = false;
        }
        $('helptext').innerHTML = strSelectKBSections;
        $('helptext').innerHTML = $('helptext').innerHTML + "<br /><strong>" + strKnowledgeBaseArticle + "</strong>:";

        // Show the table rows for KB article
        for (i = 0; i < rows.length; i++)
        {
            $(rows[i]).show();
        }
    }
    else
    {
        // Disable KB
        for (i = 0; i < fields.length; i++)
        {
            $(fields[i]).disabled = true;
            
            
            if ($(fields[i]) instanceof HTMLInputElement)
            {
                $(fields[i]).checked = false;
            }
        }
        
        $('helptext').innerHTML = strEnterDetailsAboutIncidentToBeStoredInLog;
        $('helptext').innerHTML = $('helptext').innerHTML + ' '  + strSummaryOfProblemAndResolution;
        $('helptext').innerHTML = $('helptext').innerHTML + "<br /><strong>" + strFinalUpdate + "</strong>:";

        // Hide the table rows for KB article
        for (i = 0; i < rows.length; i++)
        {
            $(rows[i]).hide();
        }
    }
}


function revealTextAreaIncidentClose(checkbox, textarea)
{
    if ($(checkbox).checked)
    {
        $(textarea).disabled = false;
        $(textarea).style.display = ''
    }
    else
    {
        $(textarea).disabled = true;
        $(textarea).style.display = 'none'
    }
}


/*
    MOVED FROM incident_update.php
    TODO still needs some work
*/

<!--
function slaChange(sla)
{
    if (sla == 'none')
    {
        notarget();
    }
    else if (sla == 'initialresponse')
    {
        initialresponse();
    }
    else if (sla == 'probdef')
    {
        probdef();
    }
    else if (sla == 'actionplan')
    {
        actionplan();
    }
    else if (sla == 'solution')
    {
        reprioritise();
    }
}

function notarget()
{
    // remove last option
    var length = $('updatetype').length;
    if (length > 6)
    {
        $('updatetype').selectedIndex = 6;
        var Current = $('updatetype').selectedIndex;
        $('updatetype').options[Current] = null;
    }
    $('priority').value = $('storepriority').value;
    //object.priority.disabled=true;
    $('priority').disabled = false;
    $('updatetype').selectedIndex = 0;
    $('updatetype').disabled = false;
}


function initialresponse()
{
    // remove last option
    var length = $('updatetype').length;
    if (length > 6)
    {
        $('updatetype').selectedIndex = 6;
        var Current = $('updatetype').selectedIndex;
        $('updatetype').options[Current] = null;
    }
    $('priority').value = $('storepriority').value;
    $('priority').disabled = true;
    $('updatetype').selectedIndex = 0;
    $('updatetype').disabled = false;
}


function actionplan()
{
    // remove last option
    var length = $('updatetype').length;
    if (length > 6)
    {
        $('updatetype').selectedIndex = 6;
        var Current = $('updatetype').selectedIndex;
        $('updatetype').options[Current] = null;
    }
    var defaultSelected = true;
    var selected = true;
    var optionName = new Option('Action Plan', 'actionplan', defaultSelected, selected)
    var length = $('updatetype').length;
    $('updatetype').options[length] = optionName;
    $('priority').value = $('storepriority').value;
    $('priority').disabled = true;
    $('updatetype').disabled = true;
}

function reprioritise()
{
    // remove last option
    var length = $('updatetype').length;
    if (length > 6)
    {
        $('updatetype').selectedIndex = 6;
        var Current = $('updatetype').selectedIndex;
        $('updatetype').options[Current] = null;
    }
    // add new option
    var defaultSelected = true;
    var selected = true;
    var optionName = new Option('Reprioritise', 'solution', defaultSelected, selected)
    var length = $('updatetype').length;
    $('updatetype').options[length] = optionName;
    $('priority').disabled = false;
    $('updatetype').disabled = true;
}

function probdef()
{
    // remove last option
    var length = $('updatetype').length;
    if (length > 6)
    {
        $('updatetype').selectedIndex = 6;
        var Current = $('updatetype').selectedIndex;
        $('updatetype').options[Current] = null;
    }

    var defaultSelected = true;
    var selected = true;
    var optionName = new Option('Problem Definition', 'probdef', defaultSelected, selected)
    var length = $('updatetype').length;
    $('updatetype').options[length] = optionName;
    $('priority').value = $('storepriority').value;
    $('priority').disabled = true;
    $('updatetype').disabled = true;
}


/**
 * Sets visibility of an element
 * 
 * @param element String of the element to show/hide
 * @param visibility boolean whether to show or hide
 * @author Paul Heaney
 */
function set_object_visibility(element, visibility)
{
    if (visibility == true)
    {
        $(element).hide();
    }
    else
    {
        $(element).show();
    }
}