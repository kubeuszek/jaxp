<?php
class JaxpColour extends JaxpSimpleModule
{
    static function FromHex($hex_string)
    {
        if (is_object($hex_string)) return false;
        $hex_string = str_replace("#", "", $hex_string);
        $r = hexdec(substr($hex_string, 0, 2));
        $g = hexdec(substr($hex_string, 2, 2));
        $b = hexdec(substr($hex_string, 4, 2));
        
        return new JaxpColourRgb($r, $g, $b);
    }
}

class JaxpColourRgb extends JaxpSimpleModule
{
    public $R;
    public $G;
    public $B;
    
    function __construct($r = 0, $g = 0, $b = 0)
    {
        $this->ModuleId = "jaxp.colour.rgb";
        $this->ModuleName = "jaXP Colour RGB";
        $this->ModuleDescription = "Structure for storing RGB colours.";
        $this->ModuleVersion = "1.0";
        
        $this->R = $r;
        $this->G = $g;
        $this->B = $b;

        parent::__construct();
        
        unset($this->MySqlHandler);
        unset($this->FileSystem);
        unset($this->PlatformSettings);
    }
    
    function ToPercentages()
    {
        return array
        (
            "r" => $this->R / 255 * 100,
            "g" => $this->G / 255 * 100,
            "b" => $this->B / 255 * 100
        );
    }
    
    function ToHex()
    {
        $hex_template = "#%02x%02x%02x";
        return sprintf(
            $hex_template, $this->R, $this->G, $this->B
        );
    }
    
    function GetReadableForegroundColor()
    {
        $r = $this->R / 255;
        $g = $this->G / 255;
        $b = $this->B / 255;
        
        return 0.213 * $r + 0.715 * $g + 0.072 * $b < 0.5 ? "FFFFFF" : "000000";       
    }
}
?>