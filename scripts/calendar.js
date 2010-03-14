// This JavaScript date picker  came from an article at
// http://www.dagblastit.com/~tmcclure/dhtml/calendar.html
// The website states
// "You may use the strategies and code in these articles license and royalty free unless otherwise directed.
// "If I helped you build something cool I'd like to hear about it. Drop me a line at tom@dagblastit.com."
// some parts of tjmlib.js were merged with this file and the file was tweaked a bit

// Modified for SiT by Ivan Lucas <ivanlucas[at]users.sourceforge.net>

// how reliable is this test?
isIE = (document.all ? true : false);
isDOM = (document.getElementById ? true : false);

// Initialize arrays.
var months = new Array(strJanAbbr, strFebAbbr, strMarAbbr, strAprAbbr, strMayAbbr,
                    strJunAbbr, strJulAbbr, strAugAbbr, strSepAbbr, strOctAbbr,
                    strNovAbbr, strDecAbbr);
var daysInMonth = new Array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
var displayMonth = new Date().getMonth();
var displayYear = new Date().getFullYear();
var displayDivName;
var displayElement;

function getDays(month, year)
{
    // Test for leap year when February is selected.
    if (1 == month)
        return ((0 == year % 4) && (0 != (year % 100))) ||
            (0 == year % 400) ? 29 : 28;
    else
        return daysInMonth[month];
}

function getToday()
{
    // Generate today's date.
    this.now = new Date();
    this.year = this.now.getFullYear();
    this.month = this.now.getMonth();
    this.day = this.now.getDate();
}

// Start with a calendar for today.
//today = new getToday();

function newCalendar(eltName,attachedElement)
{
    if (attachedElement)
    {
        if (displayDivName && displayDivName != eltName) hideElement(displayDivName);
        displayElement = attachedElement;
    }
    displayDivName = eltName;
    today = new getToday();
    var parseYear = parseInt(displayYear + '');
    var newCal = new Date(parseYear,displayMonth,1);
    var day = -1;
    var startDayOfWeek = newCal.getDay();
    if ((today.year == newCal.getFullYear()) &&
       (today.month == newCal.getMonth()))
    {
        day = today.day;
    }
    var intDaysInMonth = getDays(newCal.getMonth(), newCal.getFullYear());
    var daysGrid = makeDaysGrid(startDayOfWeek,day,intDaysInMonth,newCal,eltName)
    if (isIE)
    {
        var elt = document.all[eltName];
        elt.innerHTML = daysGrid;
    }
    else if (isDOM)
    {
        var elt = document.getElementById(eltName);
        elt.innerHTML = daysGrid;
    }
    else
    {
        var elt = document.layers[eltName].document;
        elt.open();
        elt.write(daysGrid);
        elt.close();
    }
}

function incMonth(delta,eltName)
{
    displayMonth += delta;
    if (displayMonth >= 12)
    {
        displayMonth = 0;
        incYear(1,eltName);
    }
    else if (displayMonth <= -1)
    {
        displayMonth = 11;
        incYear(-1,eltName);
    }
    else
    {
        newCalendar(eltName);
    }
}

function incYear(delta,eltName)
{
    displayYear = parseInt(displayYear + '') + delta;
    newCalendar(eltName);
}

function PadDigits(n, totalDigits)
{
    n = n.toString();
    var pd = '';
    if (totalDigits > n.length)
    {
        for (i=0; i < (totalDigits-n.length); i++)
        {
            pd += '0';
        }
    }
    return pd + n.toString();
}

function makeDaysGrid(startDay,day,intDaysInMonth,newCal,eltName)
{
    var daysGrid;
    var month = newCal.getMonth();
    var year = newCal.getFullYear();
    var isThisYear = (year == new Date().getFullYear());
    var isThisMonth = (day > -1)
    //daysGrid = '<table class="datepicker"><tr><td nowrap="nowrap">';
    daysGrid = '<div class="datepicker">';
    //daysGrid += '<font face="courier new, courier" size=2>';
    daysGrid += '<a href="javascript:hideElement(\'' + eltName + '\')" title="Close">x</a>';
    daysGrid += '&nbsp;&nbsp;';
    daysGrid += '<a href="javascript:incMonth(-1,\'' + eltName + '\')">&laquo; </a>';

    //daysGrid += '<strong>';
    if (isThisMonth) { daysGrid += '<span style="color:red">' + months[month] + '</span>'; }
    else { daysGrid += months[month]; }
    //daysGrid += '</strong>';

    daysGrid += '<a href="javascript:incMonth(1,\'' + eltName + '\')"> &raquo;</a>';
    daysGrid += '&nbsp;&nbsp;&nbsp;';
    daysGrid += '<a href="javascript:incYear(-1,\'' + eltName + '\')">&laquo; </a>';

    //daysGrid += '<strong>';
    if (isThisYear) { daysGrid += '<span style="color:red">' + year + '</span>'; }
    else { daysGrid += ''+year; }
    //daysGrid += '</strong>';

    daysGrid += '<a href="javascript:incYear(1,\'' + eltName + '\')"> &raquo;</a><br />';
    daysGrid += '&nbsp;' + strSundayAbbr + ' ' + strMondayAbbr + ' ' +
                strTuesdayAbbr + ' ' + strWednesdayAbbr + ' ' + strThursdayAbbr +
                ' ' + strFridayAbbr + ' ' + strSaturdayAbbr + '&nbsp;<br />&nbsp;';
    var dayOfMonthOfFirstSunday = (7 - startDay + 1);
    for (var intWeek = 0; intWeek < 6; intWeek++)
    {
        var dayOfMonth;
        for (var intDay = 0; intDay < 7; intDay++)
        {
            dayOfMonth = (intWeek * 7) + intDay + dayOfMonthOfFirstSunday - 7;
            if (dayOfMonth <= 0)
            {
                daysGrid += "&nbsp;&nbsp; ";
            }
            else if (dayOfMonth <= intDaysInMonth)
            {
                var color = "blue";
                if (day > 0 && day == dayOfMonth) color="red";
                daysGrid += '<a href="javascript:setDay(';
                daysGrid += dayOfMonth + ',\'' + eltName + '\')" '
                daysGrid += 'style="font-weight: normal; color:' + color + '">';
                var dayString = dayOfMonth + "</a> ";
                if (dayString.length == 6) dayString = '0' + dayString;
                daysGrid += dayString;
            }
        }
        if (dayOfMonth < intDaysInMonth) daysGrid += "<br />&nbsp;";
    }
    daysGrid += "</div>";
    //daysGrid += "</td></tr></table>";
    return daysGrid;
}

function setDay(day,eltName)
{
    // displayElement.value = (displayMonth + 1) + "/" + day + "/" + displayYear;
    // displayElement.value = day + "-" + (displayMonth + 1) + "-" + displayYear;
    // Y-m-d (ISO 8601, international standard) date format
    displayElement.value = displayYear + "-" + PadDigits((displayMonth + 1),2) + "-" + PadDigits(day,2);
    hideElement(eltName);
}

function toggleDatePicker(eltName,formElt)
{
    var x = formElt.indexOf('.');
    var formName = formElt.substring(0,x);
    var formEltName = formElt.substring(x+1);
    newCalendar(eltName,document.forms[formName].elements[formEltName]);
    toggleVisible(eltName);
}


function getDivStyle(divname)
{
    var style;
    if (isDOM) { style = document.getElementById(divname).style; }
    else { style = isIE ? document.all[divname].style
                     : document.layers[divname]; } // NS4
    return style;
}

function hideElement(divname)
{
    getDivStyle(divname).visibility = 'hidden';
}

function toggleVisible(divname)
{
    divstyle = getDivStyle(divname);
    if (divstyle.visibility == 'visible' || divstyle.visibility == 'show')
    {
        divstyle.visibility = 'hidden';
    }
    else
    {
        //fixPosition(divname);
        divstyle.visibility = 'visible';
    }
}
