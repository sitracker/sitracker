/**
  * @author Paul Heaney
**/
function Activity()
{
    var id;
    var start;
}

var dataArray = new Array();
var count = 0;
var closedDuration = 0;

/**
  * @author Paul Heaney
**/
function addActivity(act)
{
    dataArray[count] = act;
    count++;
}

/**
  * @author Paul Heaney
**/
function setClosedDuration(closed)
{
    closedDuration = closed;
}

/**
  * @author Paul Heaney
**/
function formatSeconds(secondsOpen)
{
    var str = '';
    if (secondsOpen >= 86400)
    {   //days
        var days = Math.floor(secondsOpen/86400);
        if (days < 10)
        {
            str += "0"+days;
        }
        else
        {
            str += days;
        }
        secondsOpen-=(days*86400);
    }
    else
    {
        str += "00";
    }

    str += ":";

    if (secondsOpen >= 3600)
    {   //hours
        var hours = Math.floor(secondsOpen/3600);
        if (hours < 10)
        {
            str += "0"+hours;
        }
        else
        {
            str += hours;
        }
        secondsOpen-=(hours*3600);
    }
    else
    {
        str += "00";
    }

    str += ":";

    if (secondsOpen > 60)
    {   //minutes
        var minutes = Math.floor(secondsOpen/60);
        if (minutes < 10)
        {
            str += "0"+minutes;
        }
        else
        {
            str += minutes;
        }
        secondsOpen-=(minutes*60);
    }
    else
    {
        str +="00";
    }

    str += ":";

    if (secondsOpen > 0)
    {  // seconds
        if (secondsOpen < 10)
        {
            str += "0"+secondsOpen;
        }
        else
        {
            str += secondsOpen;
        }
    }
    else
    {
        str += "00";
    }

    return str;
}

/**
  * @author Paul Heaney
**/
function countUp()
{
    var now = new Date();

    var sinceEpoch = Math.round(new Date().getTime()/1000.0);

    var closed = closedDuration;

    var i = 0;
    for(i=0; i < dataArray.length; i++)
    {
        var secondsOpen = sinceEpoch-dataArray[i].start;

        closed += secondsOpen;

        var str = formatSeconds(secondsOpen);

        $("duration"+dataArray[i].id).innerHTML = "<em>"+str+"</em>";
    }

    if ($('totalduration') != null)
    {
        $('totalduration').innerHTML = formatSeconds(closed);
     }
}
