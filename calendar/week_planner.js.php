<?php
// week_planner.js.php
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Tom Gerrard <tom.gerrard[at]salfordsoftware.co.uk>

$permission = 27; // View your calendar
require ('../core.php');
$headerdisplayed = 1;
require (APPLICATION_LIBPATH . 'functions.inc.php');
require (APPLICATION_LIBPATH . 'auth.inc.php');

header('Content-Type: text/javascript');

foreach (array('user') as $var)
    eval("\$$var=cleanvar(\$_REQUEST['$var']);");

if ( ($user == '') || (($user != $_SESSION['userid']) && (!user_permission($_SESSION['userid'], 50))))
    $user = $_SESSION['userid'];

echo "var user = '$user';\n\n";
?>

/************************************************************************************************************
Some of this Javascript is Based on
DHTML Week Planner
Copyright (C) 2007  DTHMLGoodies.com, Alf Magne Kalleland

This library is free software; you can redistribute it and/or
modify it under the terms of the GNU Lesser General Public
License as published by the Free Software Foundation; either
version 2.1 of the License, or (at your option) any later version.

This library is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public
License along with this library; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA

Dhtmlgoodies.com., hereby disclaims all copyright interest in this script
written by Alf Magne Kalleland.

Alf Magne Kalleland, 2007
Owner of DHTMLgoodies.com
http://www.dhtmlgoodies.com/forum/viewtopic.php?t=2320&highlight=licence+license
************************************************************************************************************/


/* User variables */
var headerDateFormat = 'd/m/y';	// Format of day, month in header, i.e. at the right of days
var instantSave = true;	// Save items to server every time something has been changed (i.e. moved, resized or text changed) - NB! New items are not saved until a description has been written?
var externalSourceFile_items = '<?php echo $CONFIG['application_webpath']?>calendar/planner_schedule_getItems.php';	// File called by ajax when changes are loaded from the server(by Ajax).
var externalSourceFile_save = '<?php echo $CONFIG['application_webpath']?>calendar/planner_schedule_save.php';	// File called by ajax when changes are made to an element
var externalSourceFile_delete = '<?php echo $CONFIG['application_webpath']?>calendar/planner_schedule_delete.php';	// File called by ajax when an element is deleted. Input to this file is the variable "eventToDeleteId=<id>"
var popupWindowUrl = false;

var txt_deleteEvent = 'Click OK to delete this event';	// Text in dialog box - confirm before deleting an event

var appointmentMarginSize = 5;	// Margin at the left and right of appointments;
var initTopHour = 8;	// Initially auto scroll scheduler to the position of this hour
var initMinutes = 15;	// Used to auto set start time. Example: 15 = auto set start time to 0,15,30 or 45. It all depends on where the mouse is located ondragstart
var snapToMinutes = 15;	// Snap to minutes, example: 5 = allow minute 0,5,10,15,20,25,30,35,40,45,50,55
var weekplannerStartHour=0;	// If you don't want to display all hours from 0, but start later, example: 8am


var inlineTextAreaEnabled = true;	// Edit events from inline textarea?

/* End user variables */

var weekScheduler_container = false;
var weekScheduler_appointments = false;

var newAppointmentCounter = -1;
var moveAppointmentCounter = -1;
var resizeAppointmentCounter = -1;
var resizeAppointmentInitHeight = false;

var el_x;	// x position of element
var el_y;	// y position of element
var mouse_x;
var mouse_y;
var elWidth;

var currentAppointmentDiv = false;
var currentAppointmentContentDiv = false;
var currentTimeDiv = false;

var appointmentsOffsetTop = false;
var appointmentsOffsetLeft = false;

var currentZIndex = 20000;

var dayPositionArray = new Array();
var dayDateArray = new Array();

var weekSchedule_ajaxObjects = new Array();

var dateStartOfWeek = false;
var newAppointmentWidth = false;

var startIdOfNewItems = 500000000;
var contentEditInProgress = false;
var toggleViewCounter = -1;
var objectToToggle = false;
var currentEditableTextArea = false;

var appointmentProperties = new Array();	// Array holding properties of appointments/events.
var opera = navigator.userAgent.toLowerCase().indexOf('opera')>=0?true:false;

var activeEventObj;	// Reference to element currently active, i.e. with yellow header;

var activityEntries = new Array();

var activityCount = 0;

function trimString(sInString) {
sInString = sInString.replace( /^\s+/g, "" );
return sInString.replace( /\s+$/g, "" );
}

function editEventWindow(e,inputDiv)
{
    if (!inputDiv)inputDiv = this;
    if (!popupWindowUrl)return;
    if (inputDiv.id.indexOf('new_')>=0)return;



    var editEvent = window.open(popupWindowUrl + '?id=' + inputDiv.id,'editEvent','width=500,height=500,status=no');
    editEvent.focus();
}


function setElementActive(e,inputDiv)
{
    if (!inputDiv)inputDiv = this;
    var subDivs = inputDiv.getElementsByTagName('DIV');
    for (var no=0; no < subDivs.length;no++){
        if (subDivs[no].className=='weekScheduler_appointment_header'){
            subDivs[no].className = 'weekScheduler_appointment_headerActive';
        }
    }

    if (activeEventObj && activeEventObj!=inputDiv){
        setElementInactive(activeEventObj);
    }
    activeEventObj = inputDiv;
}
/* updating content - this function is called from popup window */
function setElement_txt(id,text)
{
    var ta = $(id).getElementsByTagName('SELECT')[0]
    ta.value = text;
    transferTextAreaContent(false,ta);
}
// update bg color - this function is called from popup window */
function setElement_color(id,color)
{
    $(id).style.backgroundColor=color;
    appointmentProperties[id]['bgColorCode'] = color;
}

function setElementInactive(inputDiv)
{
    var subDivs = inputDiv.getElementsByTagName('DIV');
    for (var no=0; no < subDivs.length; no++){
        if (subDivs[no].className=='weekScheduler_appointment_headerActive'){
            subDivs[no].className = 'weekScheduler_appointment_header';
        }
    }
}

function parseItemsFromServer(ajaxIndex)
{
    var itemsToBeCreated = new Array();
    var items = weekSchedule_ajaxObjects[ajaxIndex].response.split(/<item>/g);
    weekSchedule_ajaxObjects[ajaxIndex] = false;
    for (var no = 1; no < items.length; no++){
        var lines = items[no].split(/\n/g);
        itemsToBeCreated[no] = new Array();
        for (var no2 = 0; no2 < lines.length; no2 ++){
            var key = lines[no2].replace(/<([^>]+)>.*/g,'$1');
            if (key)key = trimString(key);
            var pattern = new RegExp("<\/?" + key + ">","g");
            var value = lines[no2].replace(pattern,'');
            value = trimString(value);
            if (key=='eventStartDate' || key=='eventEndDate'){
                var d = new Date(value);
                value = d;
            }

            itemsToBeCreated[no][key] = value;
        }
        if (itemsToBeCreated[no]['id']){
            var dayDiff = itemsToBeCreated[no]['eventStartDate'].getTime() - dateStartOfWeek.getTime();
            dayDiff = Math.floor(dayDiff / (1000*60*60*24));
            el_x = dayPositionArray[dayDiff];
            topPos = getYPositionFromTime(itemsToBeCreated[no]['eventStartDate'].getHours(),itemsToBeCreated[no]['eventStartDate'].getMinutes());

            var elHeight = (itemsToBeCreated[no]['eventEndDate'].getTime() - itemsToBeCreated[no]['eventStartDate'].getTime()) / (60 * 60*1000);
            elHeight = Math.round((elHeight * (itemRowHeight + 1)) - 2);

            var readonly = 0;
            if (itemsToBeCreated[no]['completion'] != 0) readonly = 1;
            currentAppointmentDiv = createNewAppointmentDiv((el_x - appointmentsOffsetLeft),topPos,(newAppointmentWidth-(appointmentMarginSize*2)),itemsToBeCreated[no]['description'],elHeight,readonly);
            currentAppointmentDiv.id = itemsToBeCreated[no]['id'];
            currentZIndex = currentZIndex + 1;
            currentAppointmentDiv.style.zIndex = currentZIndex;
            currentTimeDiv = getCurrentTimeDiv(currentAppointmentDiv);
            currentTimeDiv.style.display='block';

            if (itemsToBeCreated[no]['bgColorCode'] && itemsToBeCreated[no]['bgColorCode'].match(/^#[0-9A-F]{6}$/)){
                currentAppointmentDiv.style.backgroundColor = itemsToBeCreated[no]['bgColorCode'];
            }

            currentAppointmentContentDiv  = getCurrentAppointmentContentDiv(currentAppointmentDiv);
            currentAppointmentContentDiv.style.height = (elHeight-20) + 'px';

            currentTimeDiv.innerHTML = '<span>' + getTime(currentAppointmentDiv) + '</span>';
            autoResizeAppointment();

            currentAppointmentDiv = false;
            currentTimeDiv = false;

            var newIndex = itemsToBeCreated[no]['id'];
            appointmentProperties[newIndex] = new Array();
            appointmentProperties[newIndex]['id'] = itemsToBeCreated[no]['id'];
            appointmentProperties[newIndex]['description'] = itemsToBeCreated[no]['description'];
            appointmentProperties[newIndex]['bgColorCode'] = itemsToBeCreated[no]['bgColorCode'];
            appointmentProperties[newIndex]['eventStartDate'] = itemsToBeCreated[no]['eventStartDate'];
            appointmentProperties[newIndex]['eventEndDate'] = itemsToBeCreated[no]['eventEndDate'];
            appointmentProperties[newIndex]['completion'] = itemsToBeCreated[no]['completion'];
            appointmentProperties[newIndex]['object'] = currentAppointmentDiv;
        }
    }
}

/* Update date and hour properties for an appointment after move or drag */

function updateAppointmentProperties(id)
{
    var obj = $(id);
    var timeArray = getTimeAsArray(obj);
    var startDate = getAppointmentDate(obj);
    var endDate = new Date();
    endDate.setTime(startDate.getTime());

    startDate.setHours(timeArray[0]);
    startDate.setMinutes(timeArray[1]);

    endDate.setHours(timeArray[2]);
    endDate.setMinutes(timeArray[3]);

    /*
    var startDateString = startDate.toGMTString().replace('UTC','GMT');
    var endDateString = endDate.toGMTString().replace('UTC','GMT');
    */
    appointmentProperties[obj.id]['eventStartDate'] = startDate;
    appointmentProperties[obj.id]['eventEndDate'] = endDate;

    if (instantSave && appointmentProperties[obj.id]['description'].length>0){
        saveAnItemToServer(obj.id);
    }


}

function getYPositionFromTime(hour,minute){
    return Math.floor((hour - weekplannerStartHour) * (itemRowHeight+1) + (minute/60 * (itemRowHeight+1)));
}

function getItemsFromServer()
{
    if (dateStartOfWeek != false)
    {
        var ajaxIndex = weekSchedule_ajaxObjects.length;
        weekSchedule_ajaxObjects[ajaxIndex] = new sack();
        weekSchedule_ajaxObjects[ajaxIndex].requestFile = externalSourceFile_items  + '?year=' + dateStartOfWeek.getFullYear() + '&month=' + (dateStartOfWeek.getMonth()/1+1) + '&day=' + dateStartOfWeek.getDate() + '&user=' + user;	// Specifying which file to get
        weekSchedule_ajaxObjects[ajaxIndex].onCompletion = function(){ parseItemsFromServer(ajaxIndex); };	// Specify function that will be executed after file has been found
        weekSchedule_ajaxObjects[ajaxIndex].runAJAX();		// Execute AJAX function
    }
}

function getCurrentTimeDiv(inputObj)
{
    var subDivs = inputObj.getElementsByTagName('DIV');
    for (var no=0; no < subDivs.length; no++){
        if (subDivs[no].className=='weekScheduler_appointment_time'){
            return subDivs[no];
        }
    }
}

function getCurrentAppointmentContentDiv(inputDiv)
{
    var divs = inputDiv.getElementsByTagName('DIV');
    for (var no = 0;no < divs.length; no++){
        if (divs[no].className=='weekScheduler_appointment_txt')return divs[no];
    }
}

function getAppointmentDate(inputObj)
{
    var leftPos = getLeftPos(inputObj);

    var d = new Date();
    var tmpTime = dateStartOfWeek.getTime();
    tmpTime = tmpTime + (1000*60*60*24 * Math.floor((leftPos-appointmentsOffsetLeft) / (dayPositionArray[1] - dayPositionArray[0])));
    d.setTime(tmpTime);
    return d;


}

function getTimeAsArray(inputObj)
{
    var startTime = (getTopPos(inputObj) - appointmentsOffsetTop) / (itemRowHeight+1) + weekplannerStartHour;
    if (startTime>23)startTime = startTime - 24;
    var startHour = Math.floor(startTime);
    var startMinute = Math.floor((startTime - startHour) *60);
    var endTime = (getTopPos(inputObj) + inputObj.offsetHeight - appointmentsOffsetTop) / (itemRowHeight+1) + weekplannerStartHour;
    if (endTime>23)endTime = endTime - 24;
    var endHour = Math.floor(endTime);
    var endMinute = Math.floor((endTime - endHour) *60);
    return Array(startHour,startMinute,endHour,endMinute);
}


function getTime(inputObj)
{
    var startTime = (getTopPos(inputObj) - appointmentsOffsetTop) / (itemRowHeight+1) + weekplannerStartHour;

    if (startTime>23) startTime = startTime - 24;
    var startHour = Math.floor(startTime);
    var hourPrefix = '';
    if (startHour< 10) hourPrefix = "0";
    var startMinute = Math.floor((startTime - startHour) *60);
    var startMinutePrefix = '';
    if (startMinute < 10) startMinutePrefix="0";

    var endTime = (getTopPos(inputObj) + inputObj.offsetHeight - appointmentsOffsetTop) / (itemRowHeight+1) + weekplannerStartHour;
    if (endTime > 23) endTime = endTime - 24;
    var endHour = Math.floor(endTime);

    var endHourPrefix = '';
    if (endHour < 10) endHourPrefix = "0";
    var endMinute = Math.floor((endTime - endHour) * 60);
    var endMinutePrefix = '';
    if (endMinute < 10) endMinutePrefix = "0";


    return hourPrefix + startHour + ':' + startMinutePrefix + "" + startMinute + '-' + endHourPrefix + endHour + ':' + endMinutePrefix + "" +  endMinute;

}

function initNewAppointment(e,inputObj)
{
    if (document.all)e = event;
    if (!inputObj)inputObj = this;
    newAppointmentCounter = 0;
    el_x = getLeftPos(inputObj);
    el_y = getTopPos(inputObj);
    elWidth = inputObj.offsetWidth;

    mouse_x = e.clientX;
    mouse_y = e.clientY;
    timerNewAppointment();

    return false;
}

function timerNewAppointment()
{
    if (newAppointmentCounter >= 0 && newAppointmentCounter < 10)
    {
        newAppointmentCounter = newAppointmentCounter + 1;
        setTimeout('timerNewAppointment()', 30);
        return;
    }
    if (newAppointmentCounter == 10)
    {
        if (initMinutes)
        {
            var topPos = mouse_y - appointmentsOffsetTop + document.documentElement.scrollTop + $('weekScheduler_content').scrollTop;
            topPos = topPos - (getMinute(topPos) % initMinutes);
            var rest = (getMinute(topPos) % initMinutes);
            if (rest!=0) topPos = topPos - (getMinute(topPos) % initMinutes);
        }
        else
        {
            var topPos = (el_y - appointmentsOffsetTop);
        }

        currentAppointmentDiv = createNewAppointmentDiv((el_x - appointmentsOffsetLeft),topPos,(elWidth-(appointmentMarginSize*2)),'');
        currentAppointmentDiv.id = 'new_' + startIdOfNewItems;
        appointmentProperties[currentAppointmentDiv.id] = new Array();
        appointmentProperties[currentAppointmentDiv.id]['description'] = '';
        appointmentProperties[currentAppointmentDiv.id]['object'] = currentAppointmentDiv;
        appointmentProperties[currentAppointmentDiv.id]['id'] = currentAppointmentDiv.id;
        startIdOfNewItems++;
        currentAppointmentContentDiv  = getCurrentAppointmentContentDiv(currentAppointmentDiv);
        currentZIndex = currentZIndex + 1;
        currentAppointmentDiv.style.zIndex = currentZIndex;
        currentAppointmentDiv.style.height='20px';
        currentTimeDiv = getCurrentTimeDiv(currentAppointmentDiv);
        currentTimeDiv.style.display='block';
    }
}

function initResizeAppointment(e)
{
    if (document.all)e = event;
    currentAppointmentDiv = this.parentNode;
    currentAppointmentContentDiv  = getCurrentAppointmentContentDiv(currentAppointmentDiv);
    currentZIndex = currentZIndex + 1;
    currentAppointmentDiv.style.zIndex = currentZIndex;
    resizeAppointmentCounter = 0;
    el_x = getLeftPos(currentAppointmentDiv);
    el_y = getTopPos(currentAppointmentDiv);
    mouse_x = e.clientX;
    mouse_y = e.clientY;
    resizeAppointmentInitHeight = currentAppointmentDiv.style.height.replace('px','')/1
    timerResizeAppointment();
    return false;
}

function timerResizeAppointment()
{
    if (resizeAppointmentCounter >=0 && resizeAppointmentCounter < 10)
    {
        resizeAppointmentCounter = resizeAppointmentCounter + 1;
        setTimeout('timerResizeAppointment()',10);
        return;
    }

    if (resizeAppointmentCounter==10)
    {
        currentTimeDiv = getCurrentTimeDiv(currentAppointmentDiv);
        currentTimeDiv.style.display='block';
    }
}

function initMoveAppointment(e,inputObj)
{
    if (document.all)e = event;
    if (!inputObj)inputObj = this.parentNode;
    currentAppointmentDiv = inputObj;
    currentAppointmentContentDiv = getCurrentAppointmentContentDiv(currentAppointmentDiv);
    currentZIndex = currentZIndex + 1;
    currentAppointmentDiv.style.zIndex = currentZIndex;
    moveAppointmentCounter = 0;
    el_x = getLeftPos(inputObj);
    el_y = getTopPos(inputObj);
    elWidth = inputObj.offsetWidth;
    mouse_x = e.clientX;
    mouse_y = e.clientY;
    timerMoveAppointment();
    return false;
}

function timerMoveAppointment()
{
    if (moveAppointmentCounter >= 0 && moveAppointmentCounter < 10)
    {
        moveAppointmentCounter = moveAppointmentCounter + 1;
        setTimeout('timerMoveAppointment()',10);
        return;
    }

    if (moveAppointmentCounter==10)
    {
        currentTimeDiv = getCurrentTimeDiv(currentAppointmentDiv);
        currentTimeDiv.style.display='block';
    }
}

function getMinute(topPos)
{
    var time = (topPos) / (itemRowHeight+1);
    var hour = Math.floor(time);
    var minute = Math.floor((time - hour) *60);
    return minute;
}


function schedulerMouseMove(e)
{
    if (document.all)e = event;

    if (newAppointmentCounter == 10)
    {
        if (!currentAppointmentDiv) return;
        var tmpHeight = e.clientY - mouse_y;
        currentAppointmentDiv.style.height = Math.max(20,tmpHeight) + 'px';
        currentTimeDiv.innerHTML = '<span>' + getTime(currentAppointmentDiv) + '</span>';
    }

    if (moveAppointmentCounter == 10)
    {
        var topPos = (e.clientY - mouse_y + el_y - appointmentsOffsetTop);
        currentAppointmentDiv.style.top = topPos + 'px';
        var destinationLeftPos = false;
        for (var no = 0;no < dayPositionArray.length; no ++)
        {
            if (e.clientX>dayPositionArray[no])destinationLeftPos = dayPositionArray[no];
        }

        // currentAppointmentDiv.style.left = (destinationLeftPos + appointmentMarginSize -2) + 'px';
        currentAppointmentDiv.style.left = (destinationLeftPos + appointmentMarginSize - 17) + 'px';

        currentTimeDiv.innerHTML = '<span>' + getTime(currentAppointmentDiv) + '</span>';
    }

    if (resizeAppointmentCounter==10){
        currentAppointmentContentDiv.style.height = (Math.max((resizeAppointmentInitHeight + e.clientY - mouse_y),10)-8) + 'px';
        currentAppointmentDiv.style.height = Math.max((resizeAppointmentInitHeight + e.clientY - mouse_y),10) + 'px';
        currentTimeDiv.innerHTML = '<span>' + getTime(currentAppointmentDiv) + '</span>';
    }

}

function repositionFooter(inputDiv)
{
    var subDivs = inputDiv.getElementsByTagName('DIV');
    for (var no = 0; no < subDivs.length; no ++){
        if (subDivs[no].className=='weekScheduler_appointment_footer')
        {
            subDivs[no].style.bottom = '-1px';
        }
    }
}


/* This function copies content from ta to the span element */

function transferTextAreaContent(e,inputObj,discardContentUpdate)
{
    if (!inputObj) inputObj = this;
    inputObj.style.display='none';
    var spans = inputObj.parentNode.getElementsByTagName('DIV');
    for (var no = 0; no < spans.length; no ++){
        if (spans[no].className == 'weekScheduler_appointment_txt')
        {
            if ( (!discardContentUpdate) )
            {
            //alert('value is ' + inputObj.options[inputObj.selectedIndex].value);
            //alert('text is ' + inputObj.options[inputObj.selectedIndex].text);
                appointmentProperties[inputObj.parentNode.id]['name'] = inputObj.value;
                appointmentProperties[inputObj.parentNode.id]['description'] = inputObj.options[inputObj.selectedIndex].text;
                spans[no].innerHTML = '<span style="float:right;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp&nbsp;&nbsp;&nbsp;</span>' + inputObj.options[inputObj.selectedIndex].text;
            }
            spans[no].style.display='block';
        }

        if (spans[no].className=='weekScheduler_appointment_footer')
        {
            spans[no].style.display='block';
        }

        if (spans[no].className=='weekScheduler_appointment_time')
        {
            spans[no].style.display='block';
        }
    }
    contentEditInProgress = false;
    currentEditableTextArea = false;
    repositionFooter(inputObj.parentNode);

    if (instantSave && appointmentProperties[inputObj.parentNode.id]['description'].length > 0)
    {
        saveAnItemToServer(inputObj.parentNode.id);
    }

}

function saveAnItemToServer_complete(index,oldId)
{
    if (oldId.indexOf('new_') >= 0)
    {
        appointmentProperties[oldId]['id'] = weekSchedule_ajaxObjects[index].response;
        appointmentProperties[oldId]['object'].id = weekSchedule_ajaxObjects[index].response.replace(/\s/g,'');
        appointmentProperties[weekSchedule_ajaxObjects[index].response] = appointmentProperties[oldId];
        weekSchedule_ajaxObjects[index] = false;
        if (!inlineTextAreaEnabled){
            editEventWindow(false,appointmentProperties[oldId]['object']);
        }
    }
}

function clearAppointments()
{
    for (var prop in appointmentProperties)
    {
        if (appointmentProperties[prop]['id'])
        {
            if ($(appointmentProperties[prop]['id']))
            {
                var obj = $(appointmentProperties[prop]['id']);
                obj.parentNode.removeChild(obj);
            }
            appointmentProperties[prop]['id'] = false;
        }
    }
    appointmentProperties = new Array();
}

function saveAnItemToServer(inputId)
{
    if (appointmentProperties[inputId]['completion'] === undefined || appointmentProperties[inputId]['completion'] < 2)
    {
        if (!appointmentProperties[inputId]['description']) appointmentProperties[inputId]['description']='';
        if (!appointmentProperties[inputId]['bgColorCode']) appointmentProperties[inputId]['bgColorCode']='';
        if (!appointmentProperties[inputId]['eventStartDate']) updateAppointmentProperties(inputId);

        var saveString = "?saveAnItem=true&id=" + appointmentProperties[inputId]['id']
        + '&name=' + escape(appointmentProperties[inputId]['name'])
        + '&description=' + escape(appointmentProperties[inputId]['description'])
        + '&eventStartDate=' + appointmentProperties[inputId]['eventStartDate'].toGMTString().replace('UTC','GMT')
        + '&eventEndDate=' + appointmentProperties[inputId]['eventEndDate'].toGMTString().replace('UTC','GMT')
        + '&user=' + user;

        if (appointmentProperties[inputId]['id'].indexOf('new_') >= 0){
            saveString = saveString + '&newItem=1';
        }

        var ajaxIndex = weekSchedule_ajaxObjects.length;
        weekSchedule_ajaxObjects[ajaxIndex] = new sack();
        weekSchedule_ajaxObjects[ajaxIndex].requestFile = externalSourceFile_save  + saveString;
        weekSchedule_ajaxObjects[ajaxIndex].onCompletion = function(){ saveAnItemToServer_complete(ajaxIndex,appointmentProperties[inputId]['id']); };	// Specify function that will be executed after file has been found
        weekSchedule_ajaxObjects[ajaxIndex].runAJAX();
    }
}

function ffEndEdit(e)
{
    if (!currentEditableTextArea)return;
    if (e.target) source = e.target;
        else if (e.srcElement) source = e.srcElement;
        if (source.nodeType == 3) // defeat Safari bug
            source = source.parentNode;
    if (source.tagName.toLowerCase()!='select')currentEditableTextArea.blur();
}

function initToggleView(e)
{
    if (document.all) e = event;
    if (e.target) source = e.target;
    else if (e.srcElement) source = e.srcElement;
    if (source.nodeType == 3) source = source.parentNode;

    if (source.className && source.className != 'weekScheduler_appointment_txt' && source.className != 'weekScheduler_anAppointment') return;

    toggleViewCounter = 0;
    objectToToggle = this;
    timerToggleView();
}

function timerToggleView()
{
    if (toggleViewCounter >= 0 && toggleViewCounter < 10)
    {
        toggleViewCounter = toggleViewCounter + 1;
        setTimeout('timerToggleView()',50);
    }
    if (toggleViewCounter == 10)
    {
        toggleViewCounter = -1;
        toggleAppointmentView(false,objectToToggle);

    }
}

function copyOptions(elselect)
{
    while (elselect.length > 0)
    {
        elselect.remove(0);
    }
    appendOption(elselect, '', '');

    for (var i = 1; i <= activitycount; i ++)
    {
        appendOption(elselect, activityEntries[i].innerHTML, activityEntries[i].id);
    }
}

function toggleAppointmentView(e,inputObj)
{
    if (document.all) e = event;

    if (!inlineTextAreaEnabled) return;

    if (!inputObj) inputObj = this;

    if (e)
    {
        if (e.target) source = e.target;
        else if (e.srcElement) source = e.srcElement;

        if (source.nodeType == 3) source = source.parentNode;
        if (source.tagName.toLowerCase() == 'select') return;
        if (contentEditInProgress && source.tagName == 'DIV')
        {
            transferTextAreaContent(false, currentAppointmentDiv.getElementsByTagName('SELECT')[0]);
            return;
        }
        if (source.className && source.className != 'weekScheduler_anAppointment' && source.className != 'weekScheduler_appointment_txt') return;
    }


    currentAppointmentDiv = inputObj;
    var spans = inputObj.getElementsByTagName('DIV');
    var tmpValue = '';
    for (var no=0; no < spans.length; no ++){
        if (spans[no].className=='weekScheduler_appointment_txt')
        {
            spans[no].style.display='none';
            tmpValue = appointmentProperties[inputObj.id]['description'];
        }
        if (spans[no].className=='weekScheduler_appointment_footer')
        {
            spans[no].style.display='none';
        }
        if (spans[no].className=='weekScheduler_appointment_time'){
            spans[no].style.display='none';
        }
    }

    var ta = currentAppointmentDiv.getElementsByTagName('SELECT')[0];
    ta.style.width = (currentAppointmentDiv.clientWidth - 6) + 'px';
//	ta.style.height = (currentAppointmentDiv.offsetHeight-14) + 'px';
    ta.style.top = '400px';
    copyOptions(ta);
    ta.style.display='inline';
    ta.value = tmpValue;
    contentEditInProgress = true;
    currentEditableTextArea = ta;
    ta.focus();
}

function keyboardEventTextarea(e)
{
    if (document.all)e = event;
    if (e.keyCode==27){	// Escape key
        transferTextAreaContent(false,this,true);
    }
}

function createNewAppointmentDiv(leftPos, topPos, width, contentHTML, height, readonly)
{
    var div = document.createElement('DIV');
    div.onclick = setElementActive;
    if (!readonly) div.ondblclick = initToggleView;
    div.className='weekScheduler_anAppointment';
    div.style.left = leftPos + 'px';
    div.style.top = topPos + 'px';
    div.style.width = width + 'px';
    if (!readonly) div.onmousedown = initToggleView;
    if (height)div.style.height = height + 'px';
    var timeDiv = document.createElement('DIV');
    timeDiv.className='weekScheduler_appointment_time';
    timeDiv.innerHTML = '<span></span>';
    div.appendChild(timeDiv);
    var header = document.createElement('DIV');
    header.className= 'weekScheduler_appointment_header';
    header.innerHTML = '<span></span>';
    if (!readonly) header.onmousedown = initMoveAppointment;
    header.style.cursor = 'move';
    div.appendChild(header);
    var span = document.createElement('DIV');
    var innerSpan = document.createElement('SPAN');
    innerSpan.innerHTML = '<span style="float:right;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp&nbsp;&nbsp;&nbsp;</span>' + contentHTML;
    span.appendChild(innerSpan);
    span.className = 'weekScheduler_appointment_txt';
    div.appendChild(span);
    var textarea = document.createElement('SELECT');
    copyOptions(textarea);
    textarea.className='weekScheduler_appointment_textarea';
    textarea.style.height='16px';
    textarea.style.display='none';
    textarea.onblur = transferTextAreaContent;
    textarea.onchange = transferTextAreaContent;
    textarea.onkeyup = keyboardEventTextarea;
    div.appendChild(textarea);
    var colorCodeDiv = document.createElement('DIV');
    colorCodeDiv.className='weekScheduler_appointment_colorCodes';
    div.appendChild(colorCodeDiv);
    var footerDiv = document.createElement('DIV');
    footerDiv.className='weekScheduler_appointment_footer';
    footerDiv.style.cursor = 'n-resize';
    footerDiv.innerHTML = '<span></span>';
    if (!readonly) footerDiv.onmousedown = initResizeAppointment;
    div.appendChild(footerDiv);
    weekScheduler_appointments.appendChild(div);
    return div;
}



function schedulerMouseUp()
{
    if (newAppointmentCounter >= 0)
    {
        if (newAppointmentCounter == 10)
        {
            if (!currentAppointmentDiv) return;
            if (inlineTextAreaEnabled)
            {
                var spans = currentAppointmentDiv.getElementsByTagName('DIV');
                for (var no = 0;no < spans.length; no++)
                {
                    if (spans[no].className == 'weekScheduler_appointment_txt')
                    {
                        spans[no].style.display='none';
                    }

                    if (spans[no].className == 'weekScheduler_appointment_footer')
                    {
                        spans[no].style.display='none';
                    }

                    if (spans[no].className == 'weekScheduler_appointment_time')
                    {
                        spans[no].style.display='none';
                    }
                }
                var ta = currentAppointmentDiv.getElementsByTagName('SELECT')[0];
                ta.style.width = (currentAppointmentDiv.clientWidth - 6) + 'px';
                ta.style.height = (currentAppointmentDiv.offsetHeight-14) + 'px';
                ta.style.display='inline';
                ta.focus();
            }
            else
            {
                saveAnItemToServer(currentAppointmentDiv.id);
            }
        }
    }
    if (snapToMinutes && currentAppointmentDiv && moveAppointmentCounter == 10)
    {
        topPos = getTopPos(currentAppointmentDiv) - appointmentsOffsetTop;

        var minute = getMinute(topPos);
        var rest = (minute % snapToMinutes);
        if (rest>(snapToMinutes/2)){
            topPos = topPos + (snapToMinutes/60*(itemRowHeight+1)) - ((rest/60)*(itemRowHeight+1));
        }else{
            topPos = topPos - ((rest/60)*(itemRowHeight+1));
        }
        var minute = getMinute(topPos);
        var rest = (minute % snapToMinutes);
        if (rest!=0){
            topPos = topPos - ((rest/60)*(itemRowHeight+1));
        }

        var minute = getMinute(topPos);
        var rest = (minute % snapToMinutes);
        if (rest!=0){
            topPos = topPos - ((rest/60)*(itemRowHeight+1));
        }
        currentAppointmentDiv.style.top = topPos + 'px';
        currentTimeDiv.innerHTML = '<span>' + getTime(currentAppointmentDiv) + '</span>';
    }

    if (currentAppointmentDiv && snapToMinutes && (resizeAppointmentCounter==10 || newAppointmentCounter)){
        autoResizeAppointment();
    }


    if (currentAppointmentDiv && !contentEditInProgress){
        repositionFooter(currentAppointmentDiv);
        updateAppointmentProperties(currentAppointmentDiv.id);
    }
    //if (currentTimeDiv)currentTimeDiv.style.display='none';



    currentAppointmentDiv = false;
    currentTimeDiv = false;
    moveAppointmentCounter = -1;
    resizeAppointmentCounter = -1;
    newAppointmentCounter = -1;
    toggleViewCounter = -1;
}

function autoResizeAppointment()
{
    var tmpPos = getTopPos(currentAppointmentDiv) - appointmentsOffsetTop + currentAppointmentDiv.offsetHeight;
    var startPos = tmpPos;

    var minute = getMinute(tmpPos);

    var rest = (minute % snapToMinutes);
    var height = currentAppointmentDiv.style.height.replace('px','')/1;

    if (rest>(snapToMinutes/2)){
        tmpPos = tmpPos + snapToMinutes - (minute % snapToMinutes);
    }else{
        tmpPos = tmpPos - (minute % snapToMinutes);
    }

    var minute = getMinute(tmpPos);
    if ((minute % snapToMinutes)!=0){
        tmpPos = tmpPos - (minute % snapToMinutes);
    }
    var minute = getMinute(tmpPos);
    if ((minute % snapToMinutes)!=0){
        tmpPos = tmpPos - (minute % snapToMinutes);
    }

    currentAppointmentDiv.style.height = (height + tmpPos - startPos) + 'px';
    currentTimeDiv.innerHTML = '<span>' + getTime(currentAppointmentDiv) + '</span>';

}

function deleteEventFromView(index)
{
    if (weekSchedule_ajaxObjects[index].response=='OK'){
        activeEventObj.parentNode.removeChild(activeEventObj);
        activeEventObj = false;
    }else{
        // Error handling - event not deleted

        alert('Could not confirm that event has been deleted. Make sure that the script is configured correctly');
    }
}


function schedulerKeyboardEvent(e){
    if (document.all)e = event;
    // TODO: check if the appointment can be cancelled or not (holidays, training, etc. cannot be cancelled from here)
    if (e.keyCode==46 && activeEventObj)
    {
        if (confirm(txt_deleteEvent))
        {
            var ajaxIndex = weekSchedule_ajaxObjects.length;
            weekSchedule_ajaxObjects[ajaxIndex] = new sack();
            weekSchedule_ajaxObjects[ajaxIndex].requestFile = externalSourceFile_delete  + '?eventToDeleteId=' + activeEventObj.id;
            weekSchedule_ajaxObjects[ajaxIndex].onCompletion = function(){ deleteEventFromView(ajaxIndex); };	// Specify function that will be executed after file has been found
            weekSchedule_ajaxObjects[ajaxIndex].runAJAX();		// Execute AJAX function
        }
    }
}


function getTopPos(inputObj)
{
var returnValue = inputObj.offsetTop;
while((inputObj = inputObj.offsetParent) != null){
    if (inputObj.tagName!='HTML')returnValue += inputObj.offsetTop;
}
return returnValue;
}

function getLeftPos(inputObj)
{
var returnValue = inputObj.offsetLeft;
while((inputObj = inputObj.offsetParent) != null){
    if (inputObj.tagName!='HTML')returnValue += inputObj.offsetLeft;
}
return returnValue;
}


function cancelSelectionEvent(e)
{
    if (document.all)e = event;

    if (e.target) source = e.target;
        else if (e.srcElement) source = e.srcElement;
        if (source.nodeType == 3) // defeat Safari bug
            source = source.parentNode;
    if (source.tagName.toLowerCase()=='input' || source.tagName.toLowerCase()=='select')return true;

    return false;

}
function initWeekScheduler()
{
    weekScheduler_container = $('weekScheduler_container');
    if (!document.all)weekScheduler_container.onclick = ffEndEdit;
    weekScheduler_appointments = $('weekScheduler_appointments');
    var subDivs = weekScheduler_appointments.getElementsByTagName('DIV');
    for (var no = 0; no < subDivs.length; no ++){
        if (subDivs[no].className=='weekScheduler_appointmentHour')
        {
            subDivs[no].onmousedown = initNewAppointment;

            if (!newAppointmentWidth)newAppointmentWidth = subDivs[no].offsetWidth;
        }
        if (subDivs[no].className=='weekScheduler_appointments_day'){
            dayPositionArray[dayPositionArray.length] = getLeftPos(subDivs[no]);
        }

    }
    if (initTopHour > weekplannerStartHour) $('weekScheduler_content').scrollTop = ((initTopHour - weekplannerStartHour)*(itemRowHeight+1));

    //	initTopHour
    appointmentsOffsetTop = getTopPos(weekScheduler_appointments);
    // appointmentsOffsetLeft = 2 - appointmentMarginSize;
    appointmentsOffsetLeft = 17 - appointmentMarginSize; // not sure why this has changed?

    document.documentElement.onmousemove = schedulerMouseMove;
    document.documentElement.onselectstart = cancelSelectionEvent;
    document.documentElement.onmouseup = schedulerMouseUp;
    document.documentElement.onkeydown = schedulerKeyboardEvent;

    var tmpDate = new Date();
    var dateItems = initDateToShow.split(/\-/g);
    tmpDate.setFullYear(dateItems[0]);
    tmpDate.setDate(dateItems[2]/1);
    tmpDate.setMonth(dateItems[1]/1-1);
    tmpDate.setHours(1);
    tmpDate.setMinutes(0);
    tmpDate.setSeconds(0);

    var day = tmpDate.getDay();
    if (day==0)day=7;
    if (day>1){
        var time = tmpDate.getTime();
        time = time - (1000*60*60*24) * (day-1);
        tmpDate.setTime(time);
    }
    dateStartOfWeek = new Date(tmpDate);

    updateHeaderDates();

    if (externalSourceFile_items){
        getItemsFromServer();
    }
}

function displayPreviousWeek()
{
    var tmpTime = dateStartOfWeek.getTime();
    tmpTime = tmpTime - (1000*60*60*24*7);
    dateStartOfWeek.setTime(tmpTime);

    updateHeaderDates();
    clearAppointments();
    getItemsFromServer();
}


function refreshAppointments()
{
    clearAppointments();
    getItemsFromServer();
}


function displayNextWeek()
{
    var tmpTime = dateStartOfWeek.getTime();
    tmpTime = tmpTime + (1000*60*60*24*7);
    dateStartOfWeek.setTime(tmpTime);
    updateHeaderDates();
    clearAppointments();
    getItemsFromServer();
}

function updateHeaderDates()
{
    var weekScheduler_dayRow = $('weekScheduler_dayRow');
    var subDivs = weekScheduler_dayRow.getElementsByTagName('DIV');
    var tmpDate2 = new Date(dateStartOfWeek);


    for (var no=0;no < subDivs.length;no++){
        var year = tmpDate2.getFullYear(); // + 1900;
        var month = tmpDate2.getMonth()/1 + 1;
        var date = tmpDate2.getDate();
        var tmpHeaderFormat = " " + headerDateFormat;
        tmpHeaderFormat = tmpHeaderFormat.replace('d',date);
        tmpHeaderFormat = tmpHeaderFormat.replace('m',month);
        tmpHeaderFormat = tmpHeaderFormat.replace('y',year);

        subDivs[no].getElementsByTagName('SPAN')[0].innerHTML = tmpHeaderFormat;

        dayDateArray[no] = month + '|' + date;

        var time = tmpDate2.getTime();
        time = time + (1000*60*60*24);
        tmpDate2.setTime(time);
    }
}

function initialise() {
    var ds = $('dropsources');
    var s = ds.getElementsByTagName('li');
    for (var x = 0; x < s.length; x++)
    {
        new dojo.dnd.HtmlDragSource(s[x], 'p');
    }

    var tg;
    var t = new Array();
    for (x = 1; x <= 7; x++)
    {
        tg = $('drop' + x);
        t[x] = new salford.dnd.DestDropTarget(tg, ['p']);
    }
}

function getSelectedActivity()
{
    var data = '';
    for (var i = 0; i < 4; i ++)
    {
        data = data + $('newactivity' + i).value + '|';
    }
    return data;
}

function showhideActivityRows(start, show)
{
    if (show) display = 'table-row';
    else display = 'none';

    for (var i = start / 1; i < 4; i ++)
    {
    $('addactivitydescription' + i).parentNode.parentNode.style.display = display;
    $('addactivitydescription' + i).innerHTML = '';
    $('newactivity' + i).value = '';
    }
}

function addActivity(id, name, optionvalue, editvalue)
{
    activitycount ++;
    if (name == 0) name = getSelectedActivity();
    if (id == 0) id = $('newactivityalias').value;
    var newtr = document.createElement('tr');
    var newtd1 = document.createElement('td');
    var newtd2 = document.createElement('td');
    var newtd3 = document.createElement('td');
    var newli = document.createElement('li');
    var newselect = document.createElement('select');
    var newoption1 = document.createElement('option');
    var newoption2 = document.createElement('option');
    var newoption3 = document.createElement('option');
    var newedit = document.createElement('input');

    newli.id = name;
    newli.innerHTML = id;
    new dojo.dnd.HtmlDragSource(newli, 'p');
    activityEntries[activitycount] = newli;

    newtd1.appendChild(newli);

    newoption1.value = '-1';
    if (optionvalue == '-1') newoption1.selected = true;
    newoption1.innerHTML = 'Behind schedule';
    newoption2.value = '0';
    if (optionvalue == '0') newoption2.selected = true;
    newoption2.innerHTML = 'On schedule';
    newoption3.value = '1';
    if (optionvalue == '1') newoption3.selected = true;
    newoption3.innerHTML = 'Ahead of schedule';

    newselect.id = 'activityschedule' + activitycount;
    newselect.appendChild(newoption1);
    newselect.appendChild(newoption2);
    newselect.appendChild(newoption3);

    newtd2.appendChild(newselect);

    newedit.type='text';
    newedit.id='activityedit' + activitycount;
    newedit.width=255;
    newedit.style.width = '400px';

    newtd3.appendChild(newedit);

    newtr.appendChild(newtd1);
    newtr.appendChild(newtd2);
    newtr.appendChild(newtd3);

    var activitytable = $('activitytable');
    var activitytbody = activitytable.getElementsByTagName('tbody')[0];
    activitytbody.appendChild(newtr);
    showhideActivityRows(1, 0);
    $('addactivityselect0').selectedIndex=0;
    repositionFieldset();
}

function repositionFieldset()
{
    var newPos = 0;
    el_id = $('leftdiv');
    while( el_id != null )
    {
        newPos += el_id.offsetTop;
        el_id = el_id.offsetParent;
    }
    $('addremove').style.top = (newPos + $('leftdiv').offsetHeight - 7) + 'px';
    $('addremove').style.left = ($('leftdiv').offsetLeft + 400) + 'px';
    $('addremove').style.width = ($('leftdiv').offsetWidth - 400) + 'px';
    appointmentsOffsetTop = getTopPos(weekScheduler_appointments);
}

function toggleMode()
{
    repositionFieldset();
    $('addremove').toggle();
    if ($('addtogbutton').value == '<?php echo $strAdd?>' + ' ⇓')
    {
        $('addtogbutton').value = '<?php echo $strAdd?>' + ' ⇑';
    }
    else
    {
        $('addtogbutton').value = '<?php echo $strAdd?>' + ' ⇓';
    }
}

function appendOption(el, text, value)
{
    var newoption = document.createElement('option');
    newoption.text = text;
    newoption.value = value;
    try
    {
        el.add(newoption, null);
    }
    catch(e)
    {
        el.add(newoption);
    }
}

function activityChange(level, type)
{
    showhideActivityRows(level + 1, 0)
    if (type == 'select')
    {
        for (var i = (level/1 + 1); i < 4; i ++)
        {
            while ($('addactivityselect' + i).length > 0)
            {
                $('addactivityselect' + i).remove(0);
            }
            appendOption($('addactivityselect' + i), '', '');
        }
        //alert($('addactivityselect' + level).options[$('addactivityselect' + level).selectedIndex].value);
        $('newactivity' + level).value = $('addactivityselect' + level).options[$('addactivityselect' + level).selectedIndex].value;
        if ($('addactivityselect' + level).value != '')
        {
            activityTypes[$('addactivityselect0').value](level);
        }
    }
    else
    {
        // TODO edit boxes
    }
}

function submitTimesheet()
{
    var data = '';
    for ( var i = 1; i <= activitycount; i ++ )
    {
        if ($('activityschedule' + i).value == -1)
        {
            if ($('activityedit' + i).value == '')
            {
                alert('You must fill in the comments field for behind schedule entries');
                return false;
            }
        }
        data = data + activityEntries[i].id + '~';
        data = data + $('activityschedule' + i).value + '~';
        data = data + dateStartOfWeek.getTime() + '~';
        data = data + $('activityedit' + i).value + '^';
    }
    var ajaxIndex = weekSchedule_ajaxObjects.length;
    weekSchedule_ajaxObjects[ajaxIndex] = new sack();
    weekSchedule_ajaxObjects[ajaxIndex].requestFile = 'calendar/planner_schedule_submit.php?data=' + data + '&user=' + user;
    weekSchedule_ajaxObjects[ajaxIndex].onCompletion = function(){ refreshAppointments(); };
    weekSchedule_ajaxObjects[ajaxIndex].runAJAX();
}

function retrievePreviousActivities()
{
    var ajaxIndex = weekSchedule_ajaxObjects.length;
    weekSchedule_ajaxObjects[ajaxIndex] = new sack();
    weekSchedule_ajaxObjects[ajaxIndex].requestFile = 'calendar/planner_schedule_getprevious.php' + '?user=' + user;
    weekSchedule_ajaxObjects[ajaxIndex].onCompletion = function(){ refreshAppointments(retrievePreviousActivitiesCallback(ajaxIndex)); };
    weekSchedule_ajaxObjects[ajaxIndex].runAJAX();
}

function retrievePreviousActivitiesCallback(newid)
{
    var items = weekSchedule_ajaxObjects[newid].response.split(/<item>/g);
    weekSchedule_ajaxObjects[newid] = false;
    activities = new Array();
    for (var i = 1; i < items.length; i ++)
    {
        var lines = items[i].split(/\n/g);
        //alert(lines);
        for (var j = 0; j < lines.length; j ++)
        {
            var key = lines[j].replace(/<([^>]+)>.*/g,'$1');
            if (key) key = trimString(key);
            var pattern = new RegExp('<\\/?' + key + '>', 'g');
            var value = lines[j].replace(pattern,'');
            value = trimString(value);
            activities[key] = value;
        }
        if (activities['id'] != '')
            addActivity(activities['name'], activities['id'], activities['optionvalue'], activities['editvalue']);
    }
}

window.onload = initWeekScheduler;
