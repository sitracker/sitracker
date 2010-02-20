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
  * @param rtn. Decides whether the window is unique
  * FIXME I can't remember how the 'rtn' param works, you'll have to figure it
  * out yourself, sorry.
**/
function incident_details_window(incidentid, win, rtn)
{
    // URL = "incident.php?popup=yes&id=" + incidentid;
    // URL = application_webpath + "incident_details.php?id=" + incidentid + "&win=" + win;
    URL = "incident_details.php?id=" + incidentid + "&win=" + win;
    if (popwin)
    {
        popwin.close();
    }
    popwin = window.open(URL, "sit_popup", "toolbar=yes,status=yes,menubar=no,scrollbars=yes,resizable=yes,width=700,height=600");
    if (rtn == true) return popwin;
}


/**
  * Open a popup window
  * @author Ivan Lucas
  * @param string url. The URL to open in the popup window
  * @param string mini. set to 'mini' to open a compact window
**/
function wt_winpopup(url, mini)
{
    if (mini=='mini')
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
  * @returns bool TRUE or false, depending on which button was pressed, yes = true, false = no
**/
function confirm_action(msg)
{
    if (msg == '') msg = 'Are you sure?';
    return window.confirm(msg);
}


/**
  * Open a popup window showing help
  * @author Ivan Lucas
  * @param int helpid. The help ID to display
**/
function help_window(helpid)
{
    URL = application_webpath + "help.php?id=" + helpid;
    window.open(URL, "help_window", "toolbar=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=500,height=500");
}


/**
  *
  * @author Tom Gerrard
  * @param int id
  * @note Related to the calendar
**/
function appointment(id)
{

    if ($(id).style.visibility=='visible')
    {
        $(id).style.visibility='hidden';
        $(id).style.display='none';
    }
    else
    {
        var parent = $(id).ancestors();
        parent[0].makePositioned();
        $(id).style.visibility='visible';
        $(id).style.display='block';
    }
}


/**
  * Get some text and display it
  * @author Paul Heaney, Ivan Lucas
  * @param string page
  * @param string component
  * @param string update
**/
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
  *
  * @author Unknown ???
  * @param string page
  * @param string component
**/
function ajax_save(page, component)
{
    new Ajax.Request(page, {
    parameters: $(component).serialize(true)
    });
    $(component).innerHTML = 'Saved';
}


/**
  * Delete an option from a HTML select tag
  * @author Unknown ???
  * @note This Javascript code placed in the public domain
          at http://www.irt.org/script/1265.htm
          "Code examples on irt.org can be freely copied and used."
**/
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
**/
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
**/
function copySelected(fromObject,toObject)
{
    for (var i=0, l=fromObject.options.length;i < l;i++)
    {
        if (fromObject.options[i].selected)
        {
            addOption(toObject,fromObject.options[i].text,fromObject.options[i].value);
        }
    }
    for (var i=fromObject.options.length-1;i >-1;i-- )
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
**/
function copyAll(fromObject,toObject)
{
    for (var i=0, l=fromObject.options.length;i < l;i++)
    {
        addOption(toObject,fromObject.options[i].text,fromObject.options[i].value);
    }
    for (var i=fromObject.options.length-1;i > -1;i--)
    {
        deleteOption(fromObject,i);
    }
}

/**
  * @author Unknown ???
  * @note This Javascript code placed in the public domain
          at http://www.irt.org/script/1265.htm
          "Code examples on irt.org can be freely copied and used."
**/
function populateHidden(fromObject,toObject)
{
    var output = '';
    for (var i=0, l=fromObject.options.length;i < l;i++)
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


var MIN_ROWS = 3 ;
var MAX_ROWS = 10 ;
var MIN_COLS = 40 ;
var MAX_COLS = 80 ;


/**
  * Change the length of a text area
  * @author Unknown ???
**/
function changeTextAreaLength( e )
{
    var txtLength = e.value.length;
    var numRows = 0 ;
    var arrNewLines = e.value.split("\n");

    for(var i=0; i<=arrNewLines.length-1; i++)
    {
        numRows++;
        if (arrNewLines[i].length > MAX_COLS-5)
        {
            numRows += Math.floor(arrNewLines[i].length/MAX_COLS)
        }
    }

    if (txtLength == 0)
    {
        e.cols = MIN_COLS ;
        e.rows = MIN_ROWS ;
    } else
    {
        if (numRows <= 1)
        {
            e.cols = (txtLength % MAX_COLS) + 1 >= MIN_COLS ? ((txtLength % MAX_COLS) + 1) : MIN_COLS ;
        }
        else
        {
            e.cols = MAX_COLS ;
            e.rows = numRows > MAX_ROWS ? MAX_ROWS : numRows ;
        }
    }
}

/**
  * @author Unknown ???
**/
function resetTextAreaLength ( e )
{
    e.cols = MIN_COLS ;
    e.rows = MIN_ROWS ;
}

/**
  * Return a random number
  * @author Ivan Lucas
  * @retval int Random number
**/
function get_random()
{
    var ranNum= Math.floor(Math.random()*1000000000000);
    return ranNum;
}


/**
  * Display/Hide the time to next action fields
  * @author Ivan Lucas
  *
**/
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
**/
function addcontract_sltimed(servicelevel)
{
    new Ajax.Request(application_webpath + 'ajaxdata.php?action=servicelevel_timed&servicelevel=' + servicelevel + '&rand=' + get_random(),
        {
            method:'get',
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
**/
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
**/
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
    //element.addClassName('active');
    //   alert(element);
    element.stopObserving('blur', hidecontexthelp);
    element.stopObserving('click', hidecontexthelp);
}


/**
  * find the real position of an element
  * @author http://www.quirksmode.org/js/findpos.html
**/
function findPos(obj) {
    var curleft = curtop = 0;
    if (obj.offsetParent) {
        do {
            curleft += obj.offsetLeft;
            curtop += obj.offsetTop;

        } while (obj = obj.offsetParent);
    }
    return [curleft,curtop];
}


/**
  * Show context help [?] popups
  * @author Ivan Lucas
**/
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
        new Ajax.Request(application_webpath + 'ajaxdata.php?action=contexthelp&context=' + context + '&rand=' + get_random() + '&auth=' + auth,
        //new Ajax.Request('ajaxdata.php?action=contexthelp&context=' + context + '&rand=' + get_random(),
            {
                method:'get',
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
    incident_details_window(document.jumptoincident.incident.value, 'incident'+document.jumptoincident.incident.value);
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

function help_window(helpid)
{
  URL = application_webpath + "help.php?id=" + helpid;
  window.open(URL, "help_window", "toolbar=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=500,height=500");
}

// INL - switch tab, see php function draw_tabs_submit()
function gotab(tab) {
    document.actiontabs.action.value=tab;
    document.actiontabs.submit();
}

function close_page_redirect(url)
{
    window.opener.location=url;
    window.close();
}


/**
 * Below used for selecting GroupMembership from a select field
*/

function doSelect(select, element)
{
    var includes = document.getElementById(element);
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

    var includes = document.getElementById('include');
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
**/
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
**/
function insertBBCode(element, tag, endtag)
{
    if (element.length > 0)
    {
        var start = $(element).selectionStart;
        var end = $(element).selectionEnd;
        //             alert('start:' + start + '  end: ' + end + 'len: ' + $(element).textLength);
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

    new Ajax.Request(application_webpath + 'ajaxdata.php?action=dismiss_notice&noticeid=' + noticeid + '&userid=' + userid + '&rand=' + get_random(),
    {
        method:'get',
            onSuccess: function(transport)
            {
                $(div).hide();
                $(div).removeClassName('noticebar');
                if ($$('.noticebar').length < 2) $('dismissall').hide();
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
    if ($('menupanel').style.display == 'block')
    {
        $('mainframe').style.width = mainframe;
        $('menupanel').style.display = 'none';
    }
    else
    {
        mainframe = $('mainframe').style.width;
        $('mainframe').style.width = '80%';
        $('menupanel').style.display = 'block';
    }
}

function resizeTextarea(t)
{
    a = t.value.split('\n');
    b=1;
    for (x=0;x < a.length; x++)
    {
        if (a[x].length >= t.cols)
        {
            b+= Math.floor(a[x].length/t.cols);
        }
    }
    b+= a.length;
    if (b > t.rows) t.rows = b;
}

function enableBillingPeriod()
{
    if ($('timed').checked==true)
    {
        $('engineerBillingPeriod').show();
        $('customerBillingPeriod').show();
        $('limit').show();
        $('allow_reopen').checked=false;
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