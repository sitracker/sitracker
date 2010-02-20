<?php

# SiT! forms

/**
* SiT! Form class
*/
class Form
{
    var $formheading;
    var $row = array();
    var $name;
    var $submitLabell;
    var $tableName;
    var $type; // UPDATE, ADD
    var $returnURLSuccess;
    var $returnURLFailure;
    var $keyField;
    var $keyValue;
    var $debug = false;

    public function __construct($name, $submitLabel, $tableName, $type, $formheading)
    {
        $this->name = $name;
        $this->submitLabel = $submitLabel;
        $this->tableName = $tableName;
        $this->type = $type;
        $this->formheading = $formheading;
        
        $this->returnURLSuccess = $_SERVER['PHP_SELF'];
        $this->returnURLFailure = $_SERVER['PHP_SELF'];
    }


    public function addRow(/*Row*/ $row)
    {
        $this->row[] = $row;
    }


    public function setReturnURLSuccess($returnURL)
    {
    	$this->returnURLSuccess = $returnURL;
    }

    
    public function setReturnURLFailure($returnURL)
    {
        $this->returnURLFailure = $returnURL;
    }


    public function setDebug($debug)
    {
    	$this->debug = $debug;
    }
    
    
    public function setKey($keyField, $keyValue)
    {
    	$this->keyField = $keyField;
        $this->keyValue = $keyValue;
    }
    

    private function generateHTML()
    {
        global $strSubmit;

        echo "<h2>{$this->formheading}</h2>";

        echo "<form action='{$_SERVER['PHP_SELF']}' id='{$this->name}' name='{$this->name}' method='post'>";
        echo "<table class='vertical'>";
        foreach($this->row AS $r)
        {
            echo $r->generateHTML();
        }
        echo "</table>";
        echo "<p align='center'><input type='submit' id='{$this->name}submit' name='submit' value='{$this->submitLabel}' /></p>";
        echo "</form>";
    }


    private function processForm()
    {
        global $_REQUEST;
        $toReturn = array();
        foreach ($this->row AS $r)
        {
            $toReturn = array_merge ($toReturn, $r->getDB());
        }
        
    //    print_r($toReturn);
        switch ($this->type)
        {
            case 'insert':
                $sql = "INSERT INTO `{$this->tableName}` ";
                if (count($toReturn) > 0)
                {
                    $sql .= " (";
                    foreach ($toReturn AS $d)
                    {
                        $a[] = "{$d->field}";
                    }
                    $sql .= implode(",", $a);

                    unset($a);
                    $sql .= ") VALUES (";
                    foreach ($toReturn AS $d)
                    {
                        $v = cleanvar($_REQUEST[$d->name]);
                        $a[] = "'{$v}'";
                    }
                    $sql .= implode(",", $a);
                    $sql .= ")";
                }

                break;
                
            case 'update':
                $sql = "UPDATE `{$this->tableName}` ";
                if (count($toReturn) > 0)
                {
                    $sql .= " SET ";
                    foreach ($toReturn AS $d)
                    {
                        $v = cleanvar($_REQUEST[$d->name]);
                        $a[] .= "{$d->field} = '{$v}'";
                    }
                    $sql .= implode(", ", $a);

                    $sql .= "WHERE {$this->keyField} = '{$this->keyValue}'";
                }

                break;
        }
        
        if ($this->debug) echo $sql;
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);
        if (mysql_affected_rows() <= 0)
        {
        	html_redirect($this->returnURLFailure, FALSE);
            exit;
        }
        else
        {
        	html_redirect($this->returnURLSuccess, TRUE);
            exit;
        }
    }


    public function run()
    {
        global $_REQUEST;

        $submit = cleanvar($_REQUEST['submit']);

        if (empty($submit))
        {
            echo $this->generateHTML();
        }
        else
        {
            echo $this->processForm();
        }
    }
}


abstract class Component
{
    var $name;
    var $value;
    var $dbFieldName;
    var $mandatory;
    abstract function generateHTML();
    abstract function getDB(); // Returns array
    function isMandatory($mandatory)
    {
    	$this->mandatory = $mandatory; // Boolean
    }
}


class db
{
    var $name;
    var $field;

    public function __construct($name, $field)
    {
        $this->name = $name;
        $this->field = $field;
    }
}


class Row extends Component
{
    var $components;

    public function addComponent(/*Component*/ $component)
    {
        $this->components[] = $component;
    }


    public function generateHTML()
    {
        $toReturn = "<tr>";

        foreach ($this->components AS $comp)
        {
            $toReturn .= $comp->generateHTML();
        }

        return $toReturn."</tr>";
    }


    public function getDB()
    {
        $toReturn = array();
        foreach ($this->components AS $comp)
        {
            $toReturn = array_merge($toReturn, $comp->getDB());
        }

        return $toReturn;
    }
}// ROW


class HiddenRow extends Component
{
    public function addComponent(/*Component*/ $component)
    {
        $this->components[] = $component;
    }


    public function generateHTML()
    {
        foreach ($this->components AS $comp)
        {
            $toReturn .= $comp->generateHTML();
        }

        return $toReturn;
    }


    public function getDB()
    {
        $toReturn = array();
        foreach ($this->components AS $comp)
        {
            $toReturn = array_merge($toReturn, $comp->getDB());
        }

        return $toReturn;
    }	
}


class Cell extends Component
{
    var $components = array();
    var $isHeader = false;

    public function addComponent(/*component*/ $component)
    {
        $this->components[] = $component;
    }

    public function setIsHeader($header = TRUE)
    {
        $this->isHeader = $header;
    }


    public function generateHTML()
    {
        $toReturn = "";
        foreach ($this->components AS $component)
        {
            $toReturn .= $component->generateHTML();
        }

        if ($this->isHeader) $toReturn = "<th>{$toReturn}</th>";
        else $toReturn = "<td>{$toReturn}</td>";

        return $toReturn;
    }


    public function getDB()
    {
        $toReturn = array();
        foreach ($this->components AS $comp)
        {
        $toReturn =  array_merge($toReturn, $comp->getDB());
        }

        return $toReturn;
    }
}


class Label extends Component
{
    var $label = "";
    public function __construct($label = "")
    {
        $this->label = $label;
    }

    public function generateHTML()
    {
        return "{$this->label}";
    }

    public function getDB()
    {
        return array();
    }
} // LABEL


class SingleLineEntry extends Component
{
    var $size = 30;

    public function __construct($name = "text", $size = 30, $dbField, $value='')
    {
        $this->name = $name;
        $this->value = $value;
        $this->size = $size;
        $this->dbFieldName = $dbField;
    }


    public function generateHTML()
    {
        return "<input type='text' id='{$this->name}' name='{$this->name}' size='{$this->size}' value='{$this->value}' />";
    }


    public function getDB()
    {
        $db = new db($this->name, $this->dbFieldName);

        return array($db);
    }
}


class HiddenEntry extends Component
{
    public function __construct($name = "text", $dbField, $value)
    {
        $this->name = $name;
        $this->value = $value;
        $this->dbFieldName = $dbField;
    }


    public function generateHTML()
    {
    	return "<input type='hidden' id='{$this->name}' name='{$this->name}' value='{$this->value}' />";
    }


    /**
     * Returns the DB array or an empty array if dbFieldName is empty this allows for fields to control other behaviours rather than just BD Input
     */
    public function getDB()
    {
    	if (empty($this->dbFieldName))
        {
        	return array();
        }
        else
        {
            $db = new db($this->name, $this->dbFieldName);
    
            return array($db);
        }
    }
}


class DatePicker extends Component
{
    var $name;

    public function __construct($name)
    {
        $this->name = $name;
    }


    public function generateHTML()
    {
        global $CONFIG, $iconset;

        $divid = "datediv".str_replace('.','',$this->name);
        $html = "<img src='{$CONFIG['application_webpath']}images/icons/{$iconset}/16x16/pickdate.png' ";
        $html .= "onmouseup=\"toggleDatePicker('$divid','{$this->name}')\" width='16' height='16' alt='date picker' style='cursor: pointer; vertical-align: bottom;' />";
        $html .= "\n<div id='$divid' style='position: absolute;'></div>\n";
        return $html;
    }


    public function getDB()
    {
        return array();
    }
}


class DateC extends Component
{
    var $components = array();
    public function __construct($name)
    {
        $this->components[] = new SingleLineEntry(name,10, "test");
        $this->components[] = new DatePicker("{$name}picker");
    }


    public function generateHTML()
    {
        $toReturn = "";
        foreach ($this->components AS $component) $toReturn .= $component->generateHTML();
        return $toReturn;
    }


    public function getDB()
    {
        return array();
    }
}

?>