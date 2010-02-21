<?php
define("JAXP_FILE_PLAIN_READ", 1);
define("JAXP_FILE_BINARY_READ", 2);

class JaxpFileSystem extends JaxpSimpleModule
{
    function __construct()
    {
        unset($this->FileSystem);
        
        $this->ModuleId = "jaxp.filesystem";
        $this->ModuleName = "jaXP File System";
        $this->ModuleDescription = "Handles I/O file operations.";
        $this->ModuleVersion = "1.0";
    }
    
    function OpenFile($file_name)
    {
        return new JaxpFile($file_name);
    }
}

class JaxpFile
{
    public $Name;
    public $Extension;
    public $MimeType;
    public $Path;
    public $Size;
    public $Url;
    
    function __construct($file_name)
    {
        $this->Name = pathinfo($file_name, PATHINFO_FILENAME);
        $this->Extension = pathinfo($file_name, PATHINFO_EXTENSION);
        $this->MimeType = @mime_content_type($file_name);
        $this->Path = pathinfo($file_name, PATHINFO_DIRNAME);
        $this->Size = filesize($file_name);
        $this->Url = $file_name;
    }
    
    function HttpReadFile($mode = JAXP_FILE_BINARY_READ, $as_download = false)
    {
        header("Content-Type: " . $this->MimeType);
        header("Content-Disposition: " .
               $as_download ? "attachment; filename=\"" . $this->Name . "\";"
                            : "inline");
        header("Content-Length: " . $this->Size);
        header("Content-MD5: " . md5_file($this->Url));
        readfile($this->Url);
    }
}
?>