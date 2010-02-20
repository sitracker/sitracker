<?php

header('Content-Type: text/css');
$rowHeight = 30;

echo "

ul
{
  padding: 6px 0 0 10px; margin:0;
}

li
{
    list-style-type: none;
    border: 1px solid #448;
    background-color: #ccf;
    margin-left: 0px;
    padding: 2px;
    left: 0px;
}

ul#dropsources li li:hover
{
    background: yellow;
    cursor: move;
}

#menu li
{
    cursor: normal;
    border: 0px;
    background-color: #fff;
}

fieldset
{
    width: 986px;
    margin: 0;
    padding: 0;
    border: 1px solid #ccc;
}

table.timesheet th
{
    text-align: center;
    width: 50px;
}

table.timesheet td
{
    text-align: left;
}

.push
{
   height: 150px;
   float: right;
   width: 1px;
}

.floor
{
    clear: both;
    height: 1px;
    overflow: hidden;
}

.weekButton
{
	width:90px;
    padding: 0px;
    height: 18px;
    font-size: 12px;
}

.weekButton br
{
    height: 5px;
}

#weekScheduler_container
{
	border:1px solid #CCC;
	width:986px;
}

.weekScheduler_appointments_day
{	/* Column for each day */
	width:130px;
	float:left;
	border-right:1px solid #CCC;
	position:relative;
}

#weekScheduler_top
{
    font-size: 14px;
	height:20px;
	border-bottom:1px solid #CCC;
}
.spacer
{
    width: 50px;
    text-align: center;
}
.calendarContentTime
{
	text-align:center;
	line-height: {$rowHeight}px;
	height: {$rowHeight}px;
	border-right:1px solid #CCC;
	width:50px;
}

.weekScheduler_appointmentHour
{	/* Small squares for each hour inside the appointment div */
	height: {$rowHeight}px;
	border-bottom:1px solid #CCC;
}

.spacer
{
	height:20px;
	float:left;
}

#weekScheduler_hours
{
	width:50px;
	float:left;
}

.calendarContentTime
{
	border-bottom:1px solid #CCC;
    font-size: 14px;
}

#weekScheduler_appointments
{	/* Big div for appointments */
	width:917px;
	float:left;
}
.calendarContentTime .content_hour
{
	font-size:12px;
	text-decoration:superscript;
	vertical-align:top;
	line-height:{$rowHeight}px;
}

#weekScheduler_top
{
	position:relative;
	clear:both;
}

#weekScheduler_content
{
	clear:both;
	height:310px;
	position:relative;
	overflow:auto;
}

.days div
{
	width:130px;
	float:left;
	text-align:center;
	font-family:arial;
	height:20px;
	line-height:20px;
	border-right:1px solid #CCC;
	font-size: 12px;
}

.weekScheduler_anAppointment
{
	position:absolute;
	background-color:#FFF;
	border:1px solid #000;
	z-index:1000;
	overflow:hidden;
    font-size: 15px;
}

.weekScheduler_appointment_header
{	/* Appointment header row */
	height:4px;
	background-color:#DDF;
}

.weekScheduler_appointment_headerActive
{ /* Appointment header row  - when active*/
	height:4px;
	background-color:#FF0;
}

.weekScheduler_appointment_txt
{
	font-family:arial;
	padding:2px;
	padding-top:5px;
	overflow:hidden;
}

.weekScheduler_appointment_footer
{
	position:absolute;
	bottom:-1px;
	border-top:1px solid #000;
	height:4px;
	width:100%;
	background-color:#000;
}

.weekScheduler_appointment_time
{
	position:absolute;
	border:1px solid #000;
	right:0px;
	top:5px;
	width: 60px;
	height:12px;
	z-index:100000;
	padding:1px;
	background-color:#F6DBA2;
    font-size: 11px;
}

.eventIndicator
{
	background-color:#00F;
	z-index:50;
	display:none;
	position:absolute;
}
";

?>
