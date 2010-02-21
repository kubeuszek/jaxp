<?php
class JaxpCore
{
    public $LoadedModules;
    public $LoadedExtensions;
    public $Settings;
    
    function __construct()
    {
        $this->Settings = json_decode(file_get_contents("./settings.conf"));
    }
    
    function Initialize()
    {
        foreach ($this->Settings->InitialExtensions as $extension)
            $this->LoadExtension($extension);
    }
    
    function LoadExtension($module_id)
    {
        try
        {
            require_once($this->Settings->BasePath . "/" . $module_id . ".php");
            $this->LoadedExtensions[] = $module_id;
        }
        catch (Exception $ex)
        {
            echo "Error: " . $ex->getMessage();
        }
    }

    function LoadModule($module_id)
    {
        try
        {
            require_once($this->Settings->BasePath . "/" . $module_id . ".php");
            $this->LoadedModules[] = $module_id;
        }
        catch (Exception $ex)
        {
            echo "Error: " . $ex->getMessage();
        }
    }
    
    function IsModuleLoaded($module_id)
    {
        return in_array($module_id, $this->LoadedModules);
    }
}

class JaxpObject
{
    public $MySqlHandler;
    public $PlatformSettings;
    public $FileSystem;

    function __construct()
    {
        $this->PlatformSettings = json_decode(file_get_contents("./settings.conf"));
        $this->MySqlHandler = new JaxpMySqlHandler();
        $this->MySqlHandler->ConnectionSettings->Username = $this->PlatformSettings->MySQL->Username;
        $this->MySqlHandler->ConnectionSettings->Password = $this->PlatformSettings->MySQL->Password;
        $this->MySqlHandler->ConnectionSettings->Server = $this->PlatformSettings->MySQL->Server;
        $this->FileSystem = new JaxpFileSystem();
    }
}

class JaxpModule extends JaxpObject
{
    public $ModuleId;
    public $ModuleName;
    public $ModuleDescription;
    public $ModuleVersion;

    function __construct()
    {
        parent::__construct();
    }
}

class JaxpSimpleModule
{
    public $ModuleId;
    public $ModuleName;
    public $ModuleDescription;
    public $ModuleVersion;

    function __construct()
    {
        $this->FileSystem = new JaxpFileSystem();
    }
}
?>