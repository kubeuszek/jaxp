<?php
/**
 * Performs checkings on text strings and some additional operations
 * using implementations of the php core.
 *
 * @package  Jaxp.String
 * @author   Joel A. Villarreal Bertoldi <design@joelalejandro.com>
 * @version  1.0
 * @uses     JaxpModule
 */

class JaxpString extends JaxpModule
{
  /**
   * Holds the text to be checked.
   * @access  private
   * @var     string
   */
  private $_text;

  /**
   * Constructor.
   * @param   $text   A string value. Defaults to empty.
   */
  function __construct($text = "")
  {
    $this->ModuleId = "jaxp.string";
    $this->ModuleName = "jaXP Strings";
    $this->ModuleDescription = "Handles text strings.";
    $this->ModuleVersion = "1.0";
    $this->_text = $text;
  }

  /**
   * __toString() - Returns the original text.
   * 
   * @return  string
   */
  function __toString()
  {
    return $this->_text;
  }

  /**
   * Counts all words from the text.
   * 
   * @access  public
   * @return  int
   */
  function WordCount()
  {
    return count(explode(" ", $this->_text));
  }

  /**
   * Checks if the text begins with a given string value.
   *
   * @param   string  $needle           A string value to match with.
   * @param   bool    $case_sensitive   Use text (false) or binary (true) comparison.
   * @return  bool    True if match is found, false otherwise.
   * @access  public
   */
  function StartsWith($needle, $case_sensitive = false)
  {
    return $case_sensitive
      ? (substr($this->_text, 0, strlen($needle)) == $needle)
      : (!(strcasecmp(substr($this->_text, 0, strlen($needle)), $needle)) ? true : false);
  }

  /**
   * Checks if the text ends with a given string value.
   *
   * @param   string  $needle           A string value to match with.
   * @param   bool    $case_sensitive   Use text (false) or binary (true) comparison.
   * @return  bool    True if match is found, false otherwise.
   * @access  public
   */
  function EndsWith($needle, $case_sensitive = false)  
  {
    return $case_sensitive
      ? (substr($this->_text, -(strlen($needle))) == $needle)
      : (!(strcasecmp(substr($this->_text, -(strlen($needle))), $needle)) ? true : false);
  }

  /**
   * Checks if the text contains a given string value.
   *
   * @param   string  $needle           A string value to match with.
   * @param   bool    $case_sensitive   Use text (false) or binary (true) comparison.
   * @return  bool    True if match is found, false otherwise.
   * @access  public
   */
  function Contains($needle, $case_sensitive = false)
  {
    $command = "str" . ($case_sensitive ? "" : "i") . "pos";
    return ($command($this->_text, $needle) !== false ? true : false);
  }

  /**
   * Reads a file.
   *
   * @param   string  $path           File to read.
   * @access  public
   */
  function FromFile($path)
  {
    if (function_exists("file_get_contents"))
    {
      $this->_text = file_get_contents($path);
    }
    else
    {
      $this->_text = implode("", file($path));
    }
  }

  /**
   * Reads the text in search of a specified string value.
   *
   * @param   string      $needle           A string value to match with.
   * @param   bool        $case_sensitive   Use text (false) or binary (true) comparison.
   * @param   bool        $first_ocurrence  True to match only first ocurrence, false to find all.
   * @return  int         if $first_ocurrence is true, first matching position
   *          int[]       if $first_ocurrence is false, all matching positions
   * @access  public
   */
  function Search($needle, $case_sensitive = false, $first_ocurrence = false)
  {
    $command = "str" . ($case_sensitive ? "" : "i") . "pos";

    if ($first_ocurrence)
    {
      return $command($this->_text, $needle);
    }
    else
    {
      for ($i = 0; $i < strlen($this->_text); $i++)
      {
        $match = $command($this->_text, $needle, $i);
        if ($match !== false)
        {
          $matched_positions[] = $match;
          $i = $match;
        }
      }
      
      return $matched_positions;
    }
  }
  
  /**
   * Clears the text buffer.
   * 
   * @access  public
   */
  function SetEmpty()
  {
    $this->_text = "";
  }
  
  /**
   * Joins string values consecutively.
   *
   * @param   string[]  $strings    Array of string values.
   * @param   string    $separator  Char or text to seperate the joint values.
   * @access  public
   */
  function Concatenate($strings, $separator = "")
  {
    $this->_text .= implode($strings, $separator);
  }
  
  /**
   * Remove a list of given chars.
   *
   * @param   string    $char1, $char2, ..., $charN   Char to remove.
   * @access  public
   */
  function RemoveChars()
  {
    $chars = func_get_args();
    foreach ($chars as $c)
    {
      $this->_text = str_replace($c, "", $this->_text);
    }
  }
  
  function ToHex()
  {
    for ($i = 0; $i < strlen($this->_text); $i++)
    {
      $hex[] = sprintf("%02d", hexdec(substr($this->_text, $i, 1)));
    }
    return implode("", $hex);
  }
  
  function FromHex()
  {
    for ($i = 0; $i < strlen($this->_text); $i+=2)
    {
      $char[] = chr(hexdec(substr($this->_text, $i, 2)));
    }
    return implode("", $char);
  }

  function ToFriendlyUrlText()
  {
    $t = $this->_text;
    
    $bad_chars = array(
        "á", "é", "í", "ó", "ú", "ñ", "ä", "ë", "ï", "ö", "ü", "-", " ",
        '"', "'", "Á", "É", "Í", "Ó", "Ú", "Ä", "Ë", "Ï", "Ö", "Ü", "Ñ",
        ":", ",", "“", "”"
    );
    $gud_chars = array(
        "a", "e", "i", "o", "u", "n", "a", "e", "i", "o", "u", "_", "-",
        '' , "" , "a", "e", "i", "o", "u", "a", "e", "i", "o", "u", "n",
        "_", "_", "" , ""
    );

    foreach ($bad_chars as $i => $b)
    {
      $t = str_replace($b, $gud_chars[$i], $t);
    }

    $t = strtolower($t);
    return $t;
  }
}

function ToJaxpString($text)
{
  return new JaxpString($text);
}
?>