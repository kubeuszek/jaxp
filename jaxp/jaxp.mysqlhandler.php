<?php
/**
 * Handles MySQL operations.
 *
 * @package  Jaxp.MySql
 * @author   Joel A. Villarreal Bertoldi <design@joelalejandro.com>
 * @version  1.3.5
 */

/**
 * Constant for returning results as a JaxpMySqlTable object.
 */
define("JAXP_MYSQL_RETURN_AS_TABLE", 1);

/**
 * Constant for returning results as an array.
 */
define("JAXP_MYSQL_RETURN_AS_ARRAY", 2);

/**
 * Constant list for the following SQL operators:
 * <>, =, <, >, <=, >=, LIKE '%...', LIKE '...%', LIKE '%...%'
 */
define("JAXP_MYSQL_MATCH_DISTINCT", 1001);
define("JAXP_MYSQL_MATCH_EQUAL", 1002);
define("JAXP_MYSQL_MATCH_LESS_THAN", 1003);
define("JAXP_MYSQL_MATCH_GREATER_THAN", 1004);
define("JAXP_MYSQL_MATCH_LESS_OR_EQUAL_THAN", 1005);
define("JAXP_MYSQL_MATCH_GREATER_OR_EQUAL_THAN", 1006);
define("JAXP_MYSQL_MATCH_STARTS_WITH", 1007);
define("JAXP_MYSQL_MATCH_ENDS_WITH", 1008);
define("JAXP_MYSQL_MATCH_CONTAINS", 1009);

/**
 * JaxpMySqlHandler
 *
 * @package     Jaxp.MySql
 * @uses        JaxpModule
 * @since       1.0
 */

class JaxpMySqlHandler extends JaxpModule
{
    /**
     * Variable that holds a JaxpMySqlDatabase object
     * @access public
     * @var    JaxpMySqlDatabase
     */
    public $Database;

    /**
     * Variable that holds a JaxpMySqlConnectionSettings object
     * @access public
     * @var    JaxpMySqlDatabase
     */
    public $ConnectionSettings;
    
    /**
     * Constructor
     * @return void
     */
    function __construct()
    {
        # Since this *is* an instance of JaxpMySqlHandler,
        # we don't want to sub-instantiate it as the default
        # JaxpModule does.
        unset($this->MySqlHandler);
		
        # Set-up module properties, handled by the core.
        $this->ModuleId = "jaxp.mysqlhandler";
        $this->ModuleName = "jaXP MySQL Handler";
        $this->ModuleDescription = "Handles MySQL operations.";
        $this->ModuleVersion = "1.3";
        
        # Instantiate a structure for holding connection settings,
        $this->ConnectionSettings = new JaxpMySqlConnectionSettings();
    } // __construct()
    
    /**
     * Connects to the MySQL server using the stored settings.
     * @uses    ConnectionSettings
     * @access  public
     * @return  void
     * @since   1.0
     */
    function Connect()
    {
        mysql_connect
        (
            $this->ConnectionSettings->Server,
            $this->ConnectionSettings->Username,
            $this->ConnectionSettings->Password
        );
    } // Connect()
    
    /**
     * Selects a data repository from the MySQL server.
     * @param   string  $mysql_database  Name of the database.
     * @uses    JaxpMySqlDatabase
     * @return  void
     * @access  public
     * @since   1.0
     */
    function SelectDatabase($mysql_database)
    {
        mysql_select_db($mysql_database);
        $this->Database = new JaxpMySqlDatabase($mysql_database);
    } // SelectDatabase()

    /**
     * Performs a filtered query on a given table.
     *
     * @param   $table          JaxpMySqlTable
     *          A table object.
     *
     * @param   $conditions     JaxpMySqlConditions
     *          List of matches to meet.
     *
     * @param   $return_format  int
     *          How to deploy results. Defauls to Table.
     *          
     * @return  mixed
     *          An object or an array, according to return format.
     *
     * @uses    JaxpMySqlTable, JaxpMySqlConditions, JAXP_MYSQL_RETURN_AS_TABLE
     *          JAXP_MYSQL_RETURN_AS_ARRAY
     * @access  public
     * @since   1.2
     */
    function Filter(JaxpMySqlTable $table, JaxpMySqlConditions $conditions, $return_format = JAXP_MYSQL_RETURN_AS_TABLE)
    {
        if (!$table->Rows) return new JaxpMySqlTable("FilterResults", false);

        # Loop through the table rows, comparing if the row meets
        # the required conditions. If so, copy the object to a list
        # of filtered results.
        foreach ($table->Rows as $r)
        {
            $match_results = true;
            foreach ($conditions->Matches as $m)
            {
                $this_match = $m->MatchAgainst($r->Columns[$m->Column->Name]->Value);
                $match_results = $match_results && $this_match;
            }
            if ($match_results)
            {
                $filtered_results[] = $r;
            }
        }
        
        # If no rows has been found, then leave, silently.
        if (!$filtered_results) return false;
        
        # Given a return format, transform the data accordingly.
        switch ($return_format)
        {
            case JAXP_MYSQL_RETURN_AS_TABLE:
                $return_object = new JaxpMySqlTable("FilterResults", false);
                
                foreach ($filtered_results as $fr)
                {
                    $return_object->Rows[] = $fr;
                }
            break;
            case JAXP_MYSQL_RETURN_AS_ARRAY:
                $return_object = $filtered_results;
            break;
        }
        
        # Return the data.
        return $return_object;
    } // Filter()
    
    /**
     * Executes a SQL query against the database. Alias for Select().
     *
     * @see     Select
     * @param   $sql_query      string
     *          A valid SQL string.
     *
     * @param   $return_format  int
     *          How to deploy the results. Default: Table object.
     *
     * @return  mixed
     *          An object or an array, according to return format.
     *
     * @uses    JaxpMySqlTable, JAXP_MYSQL_RETURN_AS_TABLE, JAXP_MYSQL_RETURN_AS_ARRAY
     * @access  public
     * @since   1.3.1
     */
    function Query($sql_query, $return_format = JAXP_MYSQL_RETURN_AS_TABLE)
    {
        return $this->Select($sql_query, $return_format);
    }


    /**
     * Executes a SQL query against the database.
     *
     * @param   $sql_query      string
     *          A valid SQL string.
     *
     * @param   $return_format  int
     *          How to deploy the results. Default: Table object.
     *          
     * @return  mixed
     *          An object or an array, according to return format.
     *
     * @uses    JaxpMySqlTable, JAXP_MYSQL_RETURN_AS_TABLE, JAXP_MYSQL_RETURN_AS_ARRAY
     * @access  public
     * @since   1.0
     */
 
    function Select($sql_query, $return_format = JAXP_MYSQL_RETURN_AS_TABLE)
    {
        # Create a Table object, empty, to hold the query results.
        $table = new JaxpMySqlTable("QueryResults", false);
        
        # Execute the SQL query.
        $table->GetTable($sql_query);
        
        # Given a result format, transform the data accordingly.
        # If using default format, simply assign $table to another variable.
        switch ($return_format)
        {
            case JAXP_MYSQL_RETURN_AS_TABLE:
                $return_object = $table;
            break;
            case JAXP_MYSQL_RETURN_AS_ARRAY:
                foreach ($table->Rows as $i => $r)
                {
                    foreach ($r->GetColumnNames() as $j => $c)
                    {
                        $return_object[$i][$j] = $table->GetValue($i, $j);
                    }
                }
            break;
        }
        
        return $return_object;
    } // Select()
    
    /**
     * Loads new data into a table.
     *
     * @param   $row                JaxpMySqlRow
     *          A row with the data to insert.
     *
     * @param   $table              JaxpMySqlTable
     *          Table where to add the row.
     *
     * @param   $return_insert_id   bool
     *          Should I get new row's id?
     *          
     * @return  int
     *          Last inserted id or -1.
     *          
     * @uses    JaxpMySqlRow, JaxpMySqlTable
     * @access  public
     * @since   1.1
     */
    function Insert(JaxpMySqlRow $row, JaxpMySqlTable $table, $return_insert_id = true)
    {
        $sql_query_template = "INSERT INTO %s (%s) VALUES(%s)";
        $destination_table =  $table->TableName;
        
        # Get all columns from the table structure, excluding auto-incremental ones.
        $field_list = implode(", ", $row->GetColumnNames(true));
        
        # Match values to columns.
        foreach ($row->GetColumnNames(true) as $column_name)
        {
            $c = $row->Columns[$column_name];
            $values[] = $c->HasQuotes() ? "'" . $c->Value . "'" : $c->Value;
        }
        
        $value_list = implode(", ", $values);
        
        $sql_query = sprintf($sql_query_template, $destination_table, $field_list, $value_list);
        
        # Execute insertion.
        mysql_query($sql_query);
        echo $sql_query;
        echo mysql_error();
        
        # Return new row's Id or nothing relevant, accordingly.
        if ($return_insert_id)
            return mysql_insert_id();
        else
            return -1;
    } // Insert()
    
    /**
     * Removes data from a table.
     * 
     * @param   $table      JaxpMySqlTable
     *          Affected table.
     *
     * @param   $conditions JaxpMySqlConditions
     *          Parameters for deletion.
     *
     * @return  int
     *          Number of affected rows.
     *          
     * @uses    JaxpMySqlTable, JaxpMySqlConditions
     * @access  public
     * @since   1.1
     */
    function Delete(JaxpMySqlTable $table, JaxpMySqlConditions $conditions)
    {
        $sql_query_template = "DELETE FROM %s WHERE %s";
        $destination_table =  $table->TableName;
        # Build SQL query for deletion.
        foreach ($table->GetColumnNames(true) as $column_name)
        {
            $c = $table->Columns[$column_name];
        }
        
        $where = $conditions->ParseToStringList();
        
        $sql_query = sprintf($sql_query_template, $destination_table, $where);
        
        # Execute query.
        mysql_query('SET NAMES utf8');
        mysql_query($sql_query);
        
        # Return the number of affected rows.
        return mysql_affected_rows();
    } // Delete()

    function Truncate(JaxpMySqlTable $table)
    {
        $sql_query = "TRUNCATE " . $table->TableName;
        mysql_query($sql_query);
        
        return mysql_affected_rows();
    } // Truncate()

    /**
     * Updates data in a table.
     *
     * @param   $table      JaxpMySqlTable
     *          Affected table.
     *          
     * @param   $row        JaxpMySqlRow
     *          Modified records.
     *          
     * @param   $conditions JaxpMySqlConditions
     *          Matches for original records. If null, it'll overwrite all data.
     *          
     * @return  int
     *          Number of affected rows.
     *          
     * @uses    JaxpMySqlTable, JaxpMySqlRow, JaxpMySqlConditions
     * @access  public
     * @since   1.1
     *
     * @internal    2010.01.03 -- fixed error with partial row updates.
     */
    function Update(JaxpMySqlTable $table, JaxpMySqlRow $row, JaxpMySqlConditions $conditions = null)
    {
        $sql_query_template = "UPDATE %s SET %s" . ($conditions ? " WHERE %s" : "");
        $destination_table = $table->TableName;
        
        # Get all fields from table, except auto-incremental ones.
        $fields = $row->GetColumnNames(true);
        
        # Match values with fields.
        foreach ($fields as $i => $f)
        {
            if ($row->Columns[$f]->Value)
            {
                $v = $row->Columns[$f]->HasQuotes()
                    ? ("'" . $row->Columns[$f]->Value . "'")
                    : $row->Columns[$f]->Value;
                $update_sentences[] = "$f = $v";
            }
        }

        
        $update_list = implode(", ", $update_sentences);
        
        # If update conditions were specified, place them in the query.
        # Otherwise, build query without conditions.
        $sql_query =
            $conditions
                ? sprintf($sql_query_template, $destination_table, $update_list, $conditions->ParseToStringList())
                : sprintf($sql_query_template, $destination_table, $update_list);
         
        # Execute query.
        mysql_query('SET NAMES utf8');
        mysql_query($sql_query);
        #echo $sql_query;
        
        # Return number of affected rows.
        return mysql_affected_rows();
    } // Update()
    
    /**
     * Creates a blank row from a table's structure.
     * 
     * @param   $table_template     JaxpMySqlTable
     *          Table to use as reference.
     *          
     * @return  JaxpMySqlRow
     *          A table row.
     *          
     * @uses    JaxpMySqlTable, JaxpMySqlRow
     * @access  public
     * @since   1.1
     */
    function CreateRow(JaxpMySqlTable $table_template)
    {
        $row = new JaxpMySqlRow();
        $row->Columns = $table_template->Columns;
        foreach ($row->Columns as $c)
        {
            unset($c->Value);
        }
        return $row;
    } // CreateRow()
} // Jaxp.MySqlHandler

/**
 * Holds data for connection settings.
 *
 * @package     Jaxp.MySql
 * @subpackage  ConnectionSettings
 * @since       1.0
 */

class JaxpMySqlConnectionSettings
{
    /**
     * MySQL server name.
     * @access  public
     * @var     string
     */
    public $Server;

    /**
     * MySQL username.
     * @access  public
     * @var     string
     */
    public $Username;

    /**
     * MySQL password
     * @access  public
     * @var     string
     */
    public $Password;

    /**
     * Instantiate a connection settings structure.
     * 
     * @param   $server     string
     *          Server name.
     *          
     * @param   $username   string
     *          User name.
     *          
     * @param   $password   string
     *          Password.
     */
    function __construct($server = "", $username = "", $password = "")
    {
        $this->Server = $server;
        $this->Username = $username;
        $this->Password = $password;
    } // __construct()
} // Jaxp.MySqlHandler.ConnectionSettings

/**
 * Represents a MySQL database
 *
 * @package     Jaxp.MySql
 * @subpackage  Database
 * @since       1.0
 */
class JaxpMySqlDatabase
{
    /**
     * Collection of tables.
     * @access  public
     * @var     JaxpMySqlTable[]
     */
    public $Tables;
    
    /**
     * Database name.
     * @access  public
     * @var     string
     */
    public $Name;
    
    /**
     * Constructor.
     */
    function __construct($database_name)
    {
        $this->Name = $database_name;
    } // __construct()
    
    /**
     * Gets all tables and records linked to these.
     * @access  public
     * @uses    JaxpMySqlTable
     * @return  void
     * @since   1.1
     */
    function LoadAllTables()
    {
        # Sends a SQL query to the server, requesting all tables available.
        mysql_query('SET NAMES utf8');
        $result = mysql_query("SHOW TABLES");
        
        # For each table, instantiate a JaxpMySqlTable object and put it
        # in the Tables array, associatively.
        for ($t = 0; $t < mysql_num_rows($result); $t++)
        {
            $table_name = mysql_result($result, $t);
            $this->Tables[$table_name] = new JaxpMySqlTable($table_name);
        }
    } // LoadAllTables()
    
    /**
     * Gets a table and its records.
     * 
     * @param   string  $table_name
     *          Name of the requested table.
     *
     * @access  public
     * @uses    JaxpMySqlTable
     * @return  JaxpMySqlTable  Requested table.
     * @since   1.0
     */
    function LoadTable($table_name)
    {
        # Creates an instance of JaxpMySqlTable and assigns it
        # to the Tables array, associatively; then, return it.
        $this->Tables[$table_name] = new JaxpMySqlTable($table_name);
        return $this->Tables[$table_name];
    } // LoadTable()
} // Jaxp.MySql.Database

/**
 * Represents a table with its columns and rows.
 *
 * @package     Jaxp.MySql
 * @subpackage  Table
 * @since       1.0
 */

class JaxpMySqlTable
{
    /**
     * Holds all the columns' data.
     * @access  public
     * @var     JaxpMySqlColumn[]
     */
    public $Columns;
    
    /**
     * Holds all rows.
     * @access  public
     * @var     JaxpMySqlRow[]
     */
    public $Rows;
    
    /**
     * Holds the table's name.
     * @access  public
     * @var     string
     */
    public $TableName;
    
    /**
     * Instantiates the table.
     * 
     * @param   string  $table_name
     *          Name of the table to create or read.
     *          
     * @param   bool    $autoload
     *          If table exists, fetch records on load?
     */
    function __construct($table_name = "", $autoload = true)
    {
        $this->TableName = $table_name;
        
        # If the auto-load feature is enabled, fetch all records
        # contained on the table upon instantiation.
        if ($autoload) $this->GetTable();
    } // __construct()
    
    /**
     * Gets all records within a table.
     * 
     * @param   string  $base_query
     *          If specified, reads the table using a specific
     *          SQL query. Otherwise, it reads all rows.
     *          
     * @access  public
     * @uses    JaxpMySqlRow, JaxpMySqlColumn
     * @return  void
     * @since   1.0
     *
     * @internal    2010.02.03 -- fixed customized SQL query bug.
     * @internal    2010.02.03 -- function exits when query isn't SELECT-like.
     */
    function GetTable($base_query = "")
    {
        # Execute selection query.
        mysql_query('SET NAMES utf8');
        $result = mysql_query(
                !$base_query ? ("SELECT * FROM " . $this->TableName)
                             : $base_query
        );

        if (!is_resource($result)) return;

        # Load columns into structure.
        for ($c = 0; $c < mysql_num_fields($result); $c++)
        {
            $new_col = new JaxpMySqlColumn(
                mysql_field_name($result, $c),
                mysql_field_type($result, $c),
                mysql_field_flags($result, $c)
            );
            
            $this->Columns[$c] = $new_col;
            $this->Columns[mysql_field_name($result, $c)] = $new_col;
        }
        
        # For every row in the table, instantiate a JaxpMySqlRow object.
        # For every field associated to the table, create a JaxpMySqlColumn
        # instance and link it to the row via the Columns array associatively
        # and place the values.
        for ($r = 0; $r < mysql_num_rows($result); $r++)
        {
            $new_row = new JaxpMySqlRow();
            for ($c = 0; $c < mysql_num_fields($result); $c++)
            {
                $field = array(
                    "name" => mysql_field_name($result, $c),
                    "type" => mysql_field_type($result, $c),
                    "flags" => mysql_field_flags($result, $c)
                );
                $new_row->Columns[$c] = new JaxpMySqlColumn(
                    $field["name"], $field["type"], $field["flags"]
                );
                $new_row->Columns[$c]->Value = mysql_result($result, $r, $c);
                $new_row->Columns[$field["name"]] = $new_row->Columns[$c];
            }
            
            $this->Rows[$r] = $new_row;
            $new_row = null;
        }
    } // GetTable()
    
    /**
     * Reads a value from a specific cell.
     *
     * @param   int     $row_index
     *          Cell's row number.
     *          
     * @param   mixed   $col_index
     *          Cell's column index. Can be number or name.
     *          
     * @access  public
     * @return  mixed
     *          Cell's value.
     * @since   1.0
     */
    function GetValue($row_index, $col_index)
    {
        return $this->Rows[$row_index]->Columns[$col_index]->Value;
    } // GetValue()

    /**
     * Get all field names.
     *
     * @param  bool        $exclude_auto_increment
     *         Ignore auto_increment fields. Defaults to false.
     *         
     * @access public
     * @return string[]
     *         Array of column names.
     * @since  1.3.1
     */
    function GetColumnNames($exclude_auto_increment = false)
    {
        # Define an empty array.
        $column_names = array();

        # Loop through the columns list.
        foreach ($this->Columns as $i => $col)
        {
            # If the column hasn't been registered yet...
            if (!in_array($col->Name, $column_names))
            {
                # ...and if we should exclude auto incremental fields
                # (which implies checking if the field is a primary key)
                # or we should include them...
                $is_primary_key = in_array("primary_key", $col->Flags);
                if (
                    ($exclude_auto_increment == true && !$is_primary_key)
                    ||
                    $exclude_auto_increment == false
                )
                {
                    # ...add the field to the column names list.
                    $column_names[] = $col->Name;
                }
            }
        }

        # Return the list.
        return $column_names;
    } // GetColumnNames()
   
    /**
     * Sorts the table's rows.
     * 
     * @param   $column            JaxpMySqlColumn
     *          Column to sort.
     *
     * @param   $reverse_order     bool
     *          Should we use ascendant or descendant order?
     *
     * @uses    JaxpMySqlColumn
     * @access  public
     * @since   1.3.2
     */
    function SortBy(JaxpMySqlColumn $column, $reverse_order = false)
    {
        # Load all rows' values into an array.
        foreach ($this->Rows as $index => $r)
        {
            $column_data[$index] = $r->Columns[$column->Name]->Value;
        }

        # Decide if we should call asort() or arsort(),
        # according to the ascendant or descendant order
        # and then, sort.
        $function = "a" . ($reverse_order ? "r" : "") . "sort";
        $function($column_data);

        # Populate an array containing the collection
        # of sorted rows.
        foreach ($column_data as $index => $c)
        {
            $this->SortedRows[] = $this->Rows[$index];
        }
    } // SortBy()
} // Jaxp.MySql.Table

/**
 * Represents a table row.
 *
 * @package     Jaxp.MySql
 * @subpackage  Row
 * @since       1.0
 */
class JaxpMySqlRow 
{
    /**
     * Holds data about the row's fields.
     * 
     * @access  public
     * @var     JaxpMySqlColumn[]
     */
    public $Columns;
    
    /**
     * Gets a value from a specified field.
     * 
     * @param   mixed   $index
     *          Field index. Can be number or name.
     *          
     * @access  public
     * @return  mixed
     *          Cell's value.
     * @since   1.0
     */
    function GetColumnValue($index)
    {
        return $this->Columns[$index]->Value;
    } // GetColumnValue()
    
    /**
     * Gets all values from the row.
     * 
     * @param   bool    $quote_check
     *          Check if the value needs surrounding quotes,
     *          based on the field's type. Defaults to false.
     *
     * @uses    JaxpMySqlColumn
     * @access  public
     * @return  mixed[] Array of values.
     * @since   1.0
     */
    function GetColumnValues($quote_check = false)
    {
        # Loop through the columns.
        foreach ($this->Columns as $i => $col)
        {
            # If we're not required to check if the column value
            # must use surrounding quotes...
            if (!$quote_check)
            {
                # ...just get the value.
                $values[$i] = $col->Value;
            }
            else
            {
                # In other case, decide if the surrounding quotes
                # are actually required by the field's type.
                $values[$i] = $col->HasQuotes()
                            ? ("'" . $col->Value . "'")
                            : $col->Value;
            }
        }

        # Return all values.
        return $values;
    } // GetColumnValues()
    
    /**
     * Get all field names.
     * Duplicate of Jaxp.MySql.Table.GetColumnNames()
     *
     * @param  bool        $exclude_auto_increment
     *         Ignore auto_increment fields. Defaults to false.
     *
     * @uses   JaxpMySqlColumn
     * @access public
     * @return string[]
     *         Array of column names.
     * @since  1.1
     */
    function GetColumnNames($exclude_auto_increment = false)
    {
        # Define an empty array.
        $column_names = array();

        # Loop through the columns list.
        foreach ($this->Columns as $i => $col)
        {
            # If the column hasn't been registered yet...
            if (!in_array($col->Name, $column_names))
            {
                # ...and if we should exclude auto incremental fields
                # (which implies checking if the field is a primary key)
                # or we should include them...
                $is_primary_key = in_array("primary_key", $col->Flags);
                if (
                    ($exclude_auto_increment == true && !$is_primary_key)
                    ||
                    $exclude_auto_increment == false
                )
                {
                    # ...add the field to the column names list.
                    $column_names[] = $col->Name;
                }
            }
        }

        # Return the list.
        return $column_names;
    } // GetColumnNames()
} // Jaxp.MySql.Row

/**
 * Represents a table's column.
 *
 * @package     Jaxp.MySql
 * @subpackage  Column
 * @since       1.0
 */

class JaxpMySqlColumn
{
    /**
     * Field name.
     * @access  public
     * @var     string
     */
    public $Name;
    
    /**
     * Field type.
     * @access  public
     * @var     string
     */
    public $Type;
    
    /**
     * Field flags (not null, primary key, auto_increment, etc.)
     * @access  public
     * @var     string[]
     */
    public $Flags;
    
    /**
     * Field value (only used when instance of column belongs to a JaxpMySqlRow.)
     * @access  public
     * @var     mixed
     */
    public $Value;
    
    /**
     * Constructor.
     * @param   string      $name
     * @param   string      $type
     * @param   string[]    $flags
     */
    function __construct($name, $type, $flags)
    {
        $this->Name = $name;
        $this->Type = $type;
        $this->Flags = explode(" ", $flags);
    } // __construct()
    
    /**
     * Checks the field type to determine if surrounding quotes are
     * needed when using the value on a SQL query.
     *
     * @access  public
     * @return  bool    true if quotes are needed, false otherwise.
     * @since   1.1
     */
    function HasQuotes()
    {        
        switch ($this->Type)
        {
            case "string":
            case "blob":
            case "date":
            case "time":
                $has_quotes = true;
            break;
            default:
                $has_quotes = false;
            break;
        }
        return $has_quotes;
    } // HasQuotes()
} // Jaxp.MySql.Column

/**
 * Represents a WHERE clause.
 *
 * @package     Jaxp.MySql
 * @subpackage  Conditions
 * @since       1.1
 */
class JaxpMySqlConditions
{
    /**
     * Holds a collection of JaxpMySqlMatch objects.
     * @access  public
     * @var     JaxpMySqlMatch[]
     */
    public $Matches;
    
    /**
     * Creates a new condition for the clause.
     *
     * @param   JaxpMySqlColumn $column
     *          Comparison subject.
     *          
     * @param   mixed           $value
     *          Value to compare.
     *          
     * @param   int             $comparison_type
     *          Logical operator to use.
     *          
     * @access  public
     * @uses    JaxpMySqlColumn, JaxpMySqlMatch
     * @return  void
     * @since   1.1
     */
    function AddCondition(JaxpMySqlColumn $column, $value, $comparison_type)
    {
        $this->Matches[] = new JaxpMySqlMatch(
            $column, $value, $comparison_type
        );
    } // AddCondition()
    
    /**
     * Removes a condition from the clause.
     *
     * @param   JaxpMySqlMatch  $match
     *          The condition object to delete.
     *
     * @access  public
     * @return  void
     * @since   1.1
     */
    function DeleteCondition(JaxpMySqlMatch $match)
    {
        # Loop the matches collection until the match to delete
        # is found. Then unset it and re-index the Matches collection.
        foreach ($this->Matches as $m)
        {
            if ($m == $match)
            {
                unset($m);
            }
        }
        sort($this->Matches);
    } // DeleteCondition()
    
    /**
     * Converts the Matches collection to a SQL-WHERE clause parameters list.
     *
     * @param   string  $boolean_condition
     *          The logical operator binding the conditions.
     *          Defaults to "AND". Can also be "OR".
     *
     * @return  string  A logop-delimited list of conditions.
     * @access  public
     * @since   1.1
     */
    function ParseToStringList($boolean_condition = "AND")
    {
        # If the boolean condition is allowed, return a list of parameters.
        if ($boolean_condition == "AND" || $boolean_condition == "OR")
        {
            return implode(" $boolean_condition ", $this->Matches);
        }
    } // ParseToStringList()
} // Jaxp.MySql.Conditions

/**
 * Represents a condition for the WHERE clause.
 *
 * @package     Jaxp.MySql
 * @subpackage  Match
 * @since       1.1
 */
class JaxpMySqlMatch
{
    /**
     * Column to match.
     * @access  public
     * @var     JaxpMySqlColumn
     */
    public $Column;
    
    /**
     * Value to match.
     * @access  public
     * @var     mixed
     */
    public $Value;
    
    /**
     * Comparison operator.
     * @access  public
     * @var     int
     */
    public $ComparisonType;
    
    /**
     * Constructor.
     * 
     * @param   JaxpMySqlColumn $column
     *          Comparison subject.
     *          
     * @param   mixed           $value
     *          Value to compare.
     *          
     * @param   int             $comparison_type
     *          Logical operator to use.
     */
    function __construct(JaxpMySqlColumn $column, $value, $comparison_type)
    {
        $this->Column = $column;
        $this->ComparisonType = $comparison_type;
        $this->Value = $value;
    } // __construct()
    
    /**
     * Converts the comparison type to a PHP logic operator.
     * 
     * @access  private
     * @return  string      Logic operator.
     * @since   1.2
     */
    private function ComparisonTypeToLogicOperator()
    {
        switch ($this->ComparisonType)
        {
            case JAXP_MYSQL_MATCH_DISTINCT:              $op = "!="; break;
            case JAXP_MYSQL_MATCH_EQUAL:                 $op = "=="; break;
            case JAXP_MYSQL_MATCH_GREATER_OR_EQUAL_THAN: $op = ">="; break;
            case JAXP_MYSQL_MATCH_GREATER_THAN:          $op = ">";  break;
            case JAXP_MYSQL_MATCH_LESS_OR_EQUAL_THAN:    $op = "<="; break;
            case JAXP_MYSQL_MATCH_LESS_THAN:             $op = "<";  break;
            default:                                     $op = null; break;
        }
        
        return $op;
    } // ComparisonTypeToLogicOperator()
    
    /**
     * Executes the match against a needle (second comparison subject).
     *
     * @param   mixed   $needle
     *          Value to compare with.
     *          
     * @access  public
     * @uses    JaxpString
     * @return  bool    True if both values match, false otherwise.
     * @since   1.1
     */
    function MatchAgainst($needle)
    {
        # Convert the needle value to a JaxpString object,
        # in case the required comparison type involves text
        # operations.
        $text_needle = new JaxpString($needle);
        
        switch ($this->ComparisonType)
        {
            case JAXP_MYSQL_MATCH_CONTAINS:
                $result = $text_needle->Contains($this->Value);
            break;
            case JAXP_MYSQL_MATCH_STARTS_WITH:
                $result = $text_needle->StartsWith($this->Value);
            break;
            case JAXP_MYSQL_MATCH_ENDS_WITH:
                $result = $text_needle->EndsWith($this->Value);
            break;
            case JAXP_MYSQL_MATCH_DISTINCT:
            case JAXP_MYSQL_MATCH_EQUAL:
            case JAXP_MYSQL_MATCH_GREATER_OR_EQUAL_THAN:
            case JAXP_MYSQL_MATCH_GREATER_THAN:
            case JAXP_MYSQL_MATCH_LESS_OR_EQUAL_THAN:
            case JAXP_MYSQL_MATCH_LESS_THAN:
                $match_template = "$" . "result = $" . "needle %s %s;";
                $code = sprintf(
                             $match_template,
                             $this->ComparisonTypeToLogicOperator(),
                             $this->Column->HasQuotes()
                                ? "'" . $this->Value . "'"
                                : $this->Value
                            );
                eval($code);
            break;
        }
        
        return $result;
    } // MatchAgainst()
    
    /**
     * __toString() - Converts the match to SQL-WHERE arguments.
     *
     * @return  string
     *          SQL string.
     * @since   1.1
     */
    function __toString()
    {
        $sql_match_template = "%s %s";
        
        $column_name = $this->Column->Name;

        # Determine if surrounding quotes are needed.
        switch ($this->Column->Type)
        {
            case "string":
            case "blob":
            case "string":
            case "date":
            case "time":
                $has_quotes = true;
            break;
            default:
                $has_quotes = false;
            break;
        }
        
        # Determine the required operator.
        switch ($this->ComparisonType)
        {
            case JAXP_MYSQL_MATCH_CONTAINS:
                if ($has_quotes)
                {
                    $match = "LIKE '%" . $this->Value . "%'";
                }
            break;
            case JAXP_MYSQL_MATCH_STARTS_WITH:
                if ($has_quotes)
                {
                    $match = "LIKE '" . $this->Value . "%'";
                }
            break;
            case JAXP_MYSQL_MATCH_ENDS_WITH:
                if ($has_quotes)
                {
                    $match = "LIKE '%" . $this->Value . "'";
                }
            break;
            case JAXP_MYSQL_MATCH_DISTINCT:
            case JAXP_MYSQL_MATCH_GREATER_OR_EQUAL_THAN:
            case JAXP_MYSQL_MATCH_GREATER_THAN:
            case JAXP_MYSQL_MATCH_LESS_OR_EQUAL_THAN:
            case JAXP_MYSQL_MATCH_LESS_THAN:
                $match = $has_quotes
                    ? $this->ComparisonTypeToLogicOperator() . " '" . $this->Value . "'"
                    : $this->ComparisonTypeToLogicOperator() . " " . $this->Value;
            break;
            case JAXP_MYSQL_MATCH_EQUAL:
                $match = $has_quotes
                    ? "= '" . $this->Value . "'"
                    : "= " . $this->Value;
            break;
        }
        
        # Build the SQL argument and return it.
        return sprintf($sql_match_template, $column_name, $match);
    } // __toString()
} // Jaxp.MySql.Match

// Jaxp.MySql
?>