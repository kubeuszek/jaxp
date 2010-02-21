<?php
define("JAXP_DATE_FROM_STRING", 1);
define("JAXP_DATE_FROM_ARRAY", 2);
define("JAXP_DATE_FROM_TIMESTAMP", 3);
define("JAXP_DATE_NOW", 4);

define("JAXP_DATE_FORMAT_SHORT", "d/m/Y");
define("JAXP_DATE_FORMAT_DATETIME", "d/m/Y H:i:s");
define("JAXP_DATE_FORMAT_TIME", "H:i:s");

class JaxpDate extends JaxpModule
{
    public $Day;
    public $Month;
    public $Year;
    public $Hour;
    public $Minute;
    public $Second;
    
    private $timestamp;
    
    function __construct($mode, $date_source = "")
    {
        parent::__construct();
        unset($this->FileSystem, $this->MySqlHandler);
        
        date_default_timezone_set($this->PlatformSettings->DefaultTimeZone);
        
        switch ($mode)
        {
            case JAXP_DATE_FROM_STRING:
                $this->timestamp = strtotime($date_source);
                $this->Day = date("d", $this->timestamp);
                $this->Month = date("m", $this->timestamp);
                $this->Year = date("Y", $this->timestamp);
                $this->Hour = date("H", $this->timestamp);
                $this->Minute = date("i", $this->timestamp);
                $this->Second = date("s", $this->timestamp);                
            break;
            case JAXP_DATE_FROM_ARRAY:
                list(
                    $this->Day,
                    $this->Month,
                    $this->Year,
                    $this->Hour,
                    $this->Month,
                    $this->Second,
                ) = $date_source;
            break;
            case JAXP_DATE_FROM_TIMESTAMP:
                $this->Day = date("d", $date_source);
                $this->Month = date("m", $date_source);
                $this->Year = date("Y", $date_source);
                $this->Hour = date("H", $date_source);
                $this->Minute = date("i", $date_source);
                $this->Second = date("s", $date_source);
            break;
            case JAXP_DATE_NOW:
                $this->Day = date("d");
                $this->Month = date("m");
                $this->Year = date("Y");
                $this->Hour = date("H");
                $this->Minute = date("i");
                $this->Second = date("s");
            break;
        }
        $this->timestamp = mktime($this->Hour, $this->Minute, $this->Second,
                                  $this->Month, $this->Day, $this->Year);

        unset($this->PlatformSettings);
    }
    
    function ToTimestamp()
    {
        return $this->timestamp;
    }
    
    function ToFormattedString($format)
    {
        return date($format, $this->timestamp);
    }
}
?>