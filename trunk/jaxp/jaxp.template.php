<?php
/**
 * Serves contents through simple tag replacement.
 *
 * @package  Jaxp.Template
 * @author   Joel A. Villarreal Bertoldi <design@joelalejandro.com>
 * @version  1.0
 */

/**
 * Generic template parser, serves templates from files or strings.
 *
 * @abstract
 * @package     Jaxp.Template
 * @subpackage  Parser
 * @version     1.1
 */
abstract class JaxpTemplateParser
{
    /**
     * Loads a template from a file.
     *
     * @static
     *
     * @param   string $filename    The template file.

     * @access  public
     * @return  JaxpTemplate        The template object.
     * @since   1.0
     */
    static function Load($filename)
    {
        # Create a template object from the filename.
        return new JaxpTemplate($filename);
    } // Load()

    /**
     * Loads a template from a string.
     *
     * @static
     *
     * @param   string $string  Template code to parse.
     *
     * @access  public
     * @return  JaxpTemplate    The template object.
     * @since   1.1
     */
    static function FromString($string)
    {
        # Create an empty template object.
        $t = new JaxpTemplate();

        # Set template code.
        $t->SetTemplateString($string);

        # Return the template object.
        return $t;
    } // FromString()
} // Jaxp.Template.Parser

/**
 * Represents a template.
 *
 * @uses    JaxpSimpleModule
 * @package Jaxp.Template
 * @version 1.1
 */
class JaxpTemplate extends JaxpSimpleModule
{
    /**
     * @var string If loaded from a file, contains the template's filename.
     * @access public
     */
    public  $Filename;

    /**
     * @var string Holds template code.
     * @access private
     */
    private $_Template;

    /**
     * @var string[] Holds tag start & end markers.
     * @access private
     */
    private $_Markings;

    /**
     * Constructor method.
     *
     * @param   string $filename    Optional. Specifies a template filename.
     *
     * @access  public
     * @return  void
     * @since   1.0
     */
    function __construct($filename = "")
    {
        # Set-up module parameters handled by the core.
        $this->ModuleId = "jaxp.template";
        $this->ModuleName = "Jaxp Template";
        $this->ModuleDescription = "Serves contents through simple tag replacement.";
        $this->ModuleVersion = "1.1";

        # Set-up array slots for markings.
        $this->_Markings = array("Start" => "", "End" => "");

        # If there's a file specified...
        if ($filename)
        {
            # ...keep the filename...
            $this->Filename = $filename;

            # ...and get the template code from there.
            $this->_Template = file_get_contents($filename);
        }
    } // __construct()

    /**
     * Places code into the template buffer. Used when parsing
     * templates from strings.
     *
     * @param   string $template_string     The template code
     *
     * @access  public
     * @return  void
     * @since   1.1
     */
    function SetTemplateString($template_string)
    {
        $this->_Template = $template_string;
    } // SetTemplateString()

    /**
     * Sets the tag wrappers.
     *
     * @param   string $start   Start wrapper (i.e. "{").
     * @param   string $end     End wrapper   (i.e. "}").
     *
     * @access  public
     * @return  void
     * @since   1.1
     */
    function SetTagMarking($start, $end)
    {
        $this->_Markings["Start"] = $start;
        $this->_Markings["End"] = $end;
    } // SetTagMarking()

    /**
     * Processes the template with a given tag data array
     * and prints or returns the resulting code.
     *
     * @param   mixed[] $tagData         Array with tag names and values.
     * @param   bool    $asReturnValue   A value of true will return the data,
     *                                   while false will echo it.
     *
     * @access  public
     * @return  string | void
     */
    function Display($tagData, $asReturnValue = false)
    {
        # Create a local instance of the template code.
        $displayed = $this->_Template;

        # Loop through the tag data array. For each tag, get the value.
        foreach ($tagData as $tag => $value)
        {
            # Replace the tag with the value.
            $displayed = str_replace(
                $this->_Markings["Start"] . $tag . $this->_Markings["End"],
                $value,
                $displayed
            );
        }

        # Return or echo the value, according to $asReturnValue.
        if ($asReturnValue) return $displayed; else echo $displayed;
    } // Display()
} // Jaxp.Template
?>