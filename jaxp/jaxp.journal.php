<?php
/******************************************************************************
 * Newspaper extension, enables news posts & comments.
 *
 * @package  Jaxp.Journal
 * @author   Joel A. Villarreal Bertoldi <design@joelalejandro.com>
 * @version  1.3
 ******************************************************************************/

################################################################################
#  CONSTANTS
################################################################################

/**
 * Constant list for advertisement formats.
 */
define("JAXP_JOURNAL_FLV_AD", 1);
define("JAXP_JOURNAL_JPEG_AD", 2);
define("JAXP_JOURNAL_GIF_AD", 3);
define("JAXP_JOURNAL_PNG_AD", 4);
define("JAXP_JOURNAL_SWF_AD", 5);

/**
 * Constant list for frontend element types.
 */
define("JAXP_JOURNAL_ELEMENT_NOTE", 1);
define("JAXP_JOURNAL_ELEMENT_NOTE_COMMENT", 2);
define("JAXP_JOURNAL_ELEMENT_NOTE_GROUP", 3);
define("JAXP_JOURNAL_ELEMENT_MEDIA_GALLERY", 4);
define("JAXP_JOURNAL_ELEMENT_MEDIA_ITEM", 8);
define("JAXP_JOURNAL_ELEMENT_WEATHER", 5);
define("JAXP_JOURNAL_ELEMENT_RATES", 6);
define("JAXP_JOURNAL_ELEMENT_AD", 7);
define("JAXP_JOURNAL_ELEMENT_SECTION", 3);

/**
 * Constant list for element display modes.
 */
define("JAXP_JOURNAL_NOTE_DISPLAY_STANDARD", 1);
define("JAXP_JOURNAL_NOTE_DISPLAY_FEATURED", 2);
define("JAXP_JOURNAL_NOTE_DISPLAY_WITH_MEDIA", 3);
define("JAXP_JOURNAL_NOTE_DISPLAY_BRIEF", 4);
define("JAXP_JOURNAL_NOTE_DISPLAY_DUAL_IMAGE", 5);

/**
 * Constant list for permalink types.
 */
define("JAXP_JOURNAL_PERMALINK_SHORT", 1);
define("JAXP_JOURNAL_PERMALINK_DESCRIPTIVE", 2);
define("JAXP_JOURNAL_PERMALINK_DEFAULT", 3);

################################################################################
#  MAIN CLASS: JaxpJournal
################################################################################

/**
 * JaxpJournal
 *
 * @package     Jaxp.Journal
 * @uses        JaxpModule
 * @since       1.0
 */

class JaxpJournal extends JaxpModule
{
    /**
     * Sections array, groups notes.
     * @access public
     * @var    JaxpJournalSection[]
     */
    public $Sections;

    /**
     * Notes array.
     * @access public
     * @var    JaxpJournalNote[]
     */
    public $Notes;

    /**
     * Frontend reference.
     * @access public
     * @var    JaxpJournalFrontend
     */
    public $Frontend;

    /**
     * RSS channel.
     * @access public
     * @var    JaxpRssFeed
     */
    public $RssFeed;

    /**
     * Media collection.
     * @access public
     * @var    JaxpMediaGallery
     * @since  1.3
     */
    public $MediaGalleries;
    
    /**
     * Constructor
     * @return void
     */
    function __construct()
    {
        # Set-up module properties, handled by the Jaxp core.
        $this->ModuleId = "jaxp.journal";
        $this->ModuleName = "jaXP Journal";
        $this->ModuleDescription = "Newspaper extension, "
                                 . "enables news posts & comments.";
        $this->ModuleVersion = "1.2";
        parent::__construct();
    } // __construct()

    /**
     * Starts up the Journal engine.
     *
     * @uses    JaxpMySqlHandler    -- MySQL package
     * @uses    JaxpJournalFrontend -- Frontend handling
     * @uses    JaxpRssFeed         -- RSS Syndicate
     * @uses    JaxpJournalSection  -- Section definitions
     * @uses    JaxpJournalNote     -- Journal posts
     * @uses    JaxpMediaGallery    -- Media galleries
     *
     * @access  public
     * @return  void
     * @since   1.0
     */
    function Initialize()
    {
        # Define a local instance of PlatformSettings->Journal for abbreviation
        # purposes.
        $cfg = $this->PlatformSettings->Journal;

        # Connect to the database, using preset configuration.
        $this->MySqlHandler->Connect();
        $this->MySqlHandler->SelectDatabase($cfg->DatabaseName);

        # Define a local instance of MySqlHandler->Database for abbreviation
        # purposes.
        $db = $this->MySqlHandler->Database;

        # Instantiate the Frontend, with a specified number of columns.
        $this->Frontend = new JaxpJournalFrontend
        (
            $this->PlatformSettings->Journal->FrontendColumns
        );

        # Instantiate the RSS Feed, with no data.
        $this->RssFeed = new JaxpRssFeed("", "", "");

        # Load required tables (notes, sections, media, frontend).
        $journal_notes = $db->LoadTable("journal_notes");
        $journal_sections = $db->LoadTable("journal_sections");
        $gallery_table = $db->LoadTable("journal_media_galleries");
        $journal_frontend = $db->LoadTable("journal_frontend");

        # Cancel initialization if there's no data.
        if (!$journal_sections->Rows && !$journal_notes->Rows)
        {
            return false;
        }

        # Instantiate all defined sections.
        foreach ($journal_sections->Rows as $s)
        {
            $new_section = new JaxpJournalSection
            (
                $s->Columns["id"]->Value,
                $s->Columns["title"]->Value,
                $s->Columns["colour"]->Value
            );
            $this->Sections[$s->Columns["id"]->Value] = $new_section;
        }

        # Abort if there's sections but no notes.
        if (!$journal_notes->Rows) return false;

        # Populate RSS Feed with notes.
        $this->RssFeed->LoadFromTable
        (
            $journal_notes,                             # Data source.
            "title", "summary", "date_posted", "id",    # Field names.
            $cfg->RSSLinkFormat                         # Link structure.
        );

        # Instantiate all published notes.
        foreach ($journal_notes->Rows as $n)
        {
            /**
             * @todo    Commment support.
             */
            /**
            if ($n->Columns["allow_comments"]->Value)
            {
                $note_comments = $db->Tables["journal_notes_comments"];
                if (count($note_comments->Rows))
                {
                    foreach ($note_comments->Rows as $c)
                    {
                        $comments[] = new JaxpJournalNoteComment(
                            $c->Columns["id"]->Value,
                            $c->Columns["note_id"]->Value,
                            $c->Columns["user"]->Value,
                            $c->Columns["ip"]->Value,
                            $c->Columns["text"]->Value
                        );
                    }
                }
            }
            **/

            $new_note = new JaxpJournalNote(
                $n->Columns["id"]->Value,
                $n->Columns["title"]->Value,
                $this->Sections[$n->Columns["section_id"]->Value],
                $n->Columns["summary"]->Value,
                $n->Columns["body"]->Value,
                $n->Columns["author"]->Value,
                $comments ? $comments : null,
                $n->Columns["allow_comments"]->Value,
                new JaxpDate
                (
                    JAXP_DATE_FROM_TIMESTAMP,
                    $n->Columns["date_posted"]->Value
                ),
                $n->Columns["page_views"]->Value,
                $n->Columns["required_media"]->Value
            );

            # Add this note to the collection, and as a reference inside
            # the corresponding section.
            $nId = $new_note->ElementId;
            $this->Notes[$nId] = $new_note;
        } # End loading notes.

        # Since 1.3:
        # Instantiate Media Gallery objects.
        if ($gallery_table->Rows)
        {
            foreach ($gallery_table->Rows as $gallery)
            {
                $id = $gallery->Columns["id"]->Value;
                $title = $gallery->Columns["title"]->Value;
                $description = $gallery->Columns["description"]->Value;
                $this->MediaGalleries[$id] = new JaxpMediaGallery
                (
                    $id, $title, $description,
                    $this->PlatformSettings->Journal->DatabaseName
                );
                $this->MediaGalleries[$id]->LoadMedia("journal_media");
            }
        }

        # Link notes to belonging sections.
        # Link media to belonging notes.
        foreach ($this->Sections as $s)
        {
            foreach ($this->Notes as $n)
            {
                if ($n->Section->ElementId == $s->ElementId)
                {
                    $s->Notes[$n->ElementId] = $n;
                    $required_media = $n->GetRequiredMedia();
                    if ($required_media)
                    {
                        foreach (explode(";", $required_media) as $media)
                        {
                            list($media_type, $folder, $m_id) = explode("/", $media);
                            array_push
                            (
                                $n->Media,
                                $this->MediaGalleries[$folder]->GetMediaById($m_id)
                            );
                        }
                    }
                }
            }
        } # End linking notes & media.
        
        # If there are any items loaded in the Frontend,
        # instantiate the proper references.
        if ($journal_frontend->Rows)
        {
            foreach ($journal_frontend->Rows as $frontend_item)
            { # Begin building frontend.
                $element_type = $frontend_item->Columns["element_type"]->Value;
                $element_id = $frontend_item->Columns["element_id"]->Value;
                $cell_id = $frontend_item->Columns["cell_id"]->Value;
                $display_mode = $frontend_item->Columns["display_mode"]->Value;

                $element = $this->GetElementByType($element_type, $element_id);
                
                $this->Frontend->CreateCell
                (
                    $cell_id, $element, $display_mode
                );
            } # End building frontend.
        }

    } // Initialize();

    /**
     * Locates a Journal element by its type and id.
     * 
     * @param   int      $type      Element type. Use JAXP_JOURNAL_* constants.
     * @param   int      $id        Element id.
     * @access  private
     * @return  JaxpJournalElement
     */
    private function GetElementByType($type, $id)
    {
        # Determine element type.
        switch ($type)
        {
            # It's a note.
            case JAXP_JOURNAL_ELEMENT_NOTE:
                # Return a note.
                $element = $this->Notes[$id];
            break;

            # It's a section.
            case JAXP_JOURNAL_ELEMENT_SECTION:
                # Return a section.
                $element = $this->Sections[$id];
            break;
        }
        return $element;
    } // GetElementByType()

    /**
     * Inserts a section into the Journal's database.
     *
     * @uses    JaxpMySqlDatabase
     * @uses    JaxpColourRgb
     *
     * @param   string          $name       Section's name.
     * @param   JaxpColourRgb   $colour     Section's strip colour.
     *
     * @access  public
     * @since   1.0
     *
     * @return  int                         Section id.
     */
    function CreateSection($name, JaxpColourRgb $colour)
    {
        # Define a local instance of MySqlHandler->Database for abbreviation
        # purposes.
        $db = $this->MySqlHandler->Database;

        # Define a local instance of the sections' table.
        $journal_sections = $db->Tables["journal_sections"];

        # Create a new record and transfer assigned values.
        $section = $this->MySqlHandler->CreateRow($journal_sections);
        $section->Columns["title"]->Value = $name;
        $section->Columns["colour"]->Value = $colour->ToHex();

        # Insert the new record into the database, and return its id.
        return $this->MySqlHandler->Insert($section, $journal_sections);
    } // CreateSection()

    /**
     * Inserts a note into the Journal's Database.
     *
     * @uses    JaxpMySqlHandler
     * @uses    JaxpDate
     *
     * @param   int         $section_id      Section id.
     * @param   string      $title           The title of the note.
     * @param   string      $summary         A brief description of the note.
     * @param   string      $body            The note text itself.
     * @param   string      $author          Who wrote the note?
     * @param   JaxpDate    $date_posted     Date (and time) of publishing.
     * @param   bool        $allow_comments  Can users comment this note?
     * @param   string      $required_media  Serialized list of media to use.
     *
     * @access  public
     * @since   1.0
     * @return  int         Note id.
     */
    function CreateNote(
        $section_id,
        $title, $summary, $body, $author,
        JaxpDate $date_posted, $required_media, $allow_comments = 1
    )
    {
        # Define a local instance of MySqlHandler->Database for abbreviation
        # purposes.
        $db = $this->MySqlHandler->Database;

        # Define a local instance of the notes' table.
        $journal_notes = $db->Tables["journal_notes"];

        # Create a new record and assing proper values.
        # Date is converted to an adequate storage format using
        # JaxpDate->ToTimestamp().
        $note = $this->MySqlHandler->CreateRow($journal_notes);
        $note->Columns["section_id"]->Value = $section_id;
        $note->Columns["title"]->Value = $title;
        $note->Columns["summary"]->Value = $summary;
        $note->Columns["body"]->Value = $body;
        $note->Columns["author"]->Value = $author;
        $note->Columns["allow_comments"]->Value = $allow_comments;
        $note->Columns["date_posted"]->Value = $date_posted->ToTimestamp();
        $note->Columns["page_views"]->Value = 0;
        $note->Columns["required_media"]->Value = $required_media;

        # Insert thew new record and return its id.
        return $this->MySqlHandler->Insert($note, $journal_notes);
    } // CreateNote()

    /**
     * Places an item into the frontend.
     *
     * @uses    JaxpMySqlHandler
     *
     * @param   JaxpJournalElement  $item           Any JaxpJournal object.
     * @param   int                 $display_mode   How should object be shown.
     *                                              Changes according to object.
     * @param   int                 $column_id      Cell id.
     *
     * @access  public
     * @return  int                 Item-frontend relation id.
     * @since   1.2
     */
    function AddItemToFrontend(
        JaxpJournalElement $item,
        $display_mode,
        $column_id
    )
    {
        # Clear cell (single element container).
        $this->ClearCellFromFrontend($column_id);

        # Define a local instance of MySqlHandler->Database for abbreviation
        # purposes.
        $db = $this->MySqlHandler->Database;

        # Define a local instance of the frontend table.
        $journal_frontend = $db->Tables["journal_frontend"];
        
        # Create a new record and place the proper values.
        $fi = $this->MySqlHandler->CreateRow($journal_frontend);
        $fi->Columns["cell_id"]->Value = $column_id;
        $fi->Columns["element_id"]->Value = $item->ElementId;
        $fi->Columns["element_type"]->Value = $item->ElementType;
        $fi->Columns["display_mode"]->Value = $display_mode;

        # Insert the new record and return its id.
        return $this->MySqlHandler->Insert($fi, $journal_frontend);
    } // AddItemToFrontend()

    /**
     * Clears all contents from a frontend cell.
     *
     * @uses    JaxpMySqlHandler
     * @uses    JaxpMySqlConditions
     *
     * @param   int     $column_id      Cell to clear.
     *
     * @access  public
     * @return  void
     * @since   1.2
     */
    function ClearCellFromFrontend($column_id)
    {
        # Define a local instance of MySqlHandler->Database for abbreviation
        # purposes.
        $db = $this->MySqlHandler->Database;

        # Define a local instance of the frontend table.
        $journal_frontend = $db->Tables["journal_frontend"];

        # Locate the cell.
        $match = new JaxpMySqlConditions();
        $match->AddCondition(
            $journal_frontend->Columns["cell_id"],
            $column_id,
            JAXP_MYSQL_MATCH_EQUAL
        );

        # Delete the linked cell element.
        $this->MySqlHandler->Delete($journal_frontend, $match);
    } // ClearCelLFromFrontend()

    /**
     * Moves an element linked in a cell to another cell.
     *
     * @uses    JaxpMySqlHandler
     * @uses    JaxpMySqlConditions
     *
     * @param   string $from        Source cell's id.
     * @param   string $to          Target cell's id.
     *
     * @access  public
     * @return  void
     * @since   1.2
     */
    function MoveFrontendCell($from, $to)
    {
        # Define a local instance of MySqlHandler->Database for abbreviation
        # purposes.
        $db = $this->MySqlHandler->Database;

        # Define a local instance of the frontend table.
        $journal_frontend = $db->Tables["journal_frontend"];
        $c = $this->MySqlHandler->CreateRow($journal_frontend);
        $c->Columns["cell_id"]->Value = $to;

        # Locate source cell.
        $match = new JaxpMySqlConditions();
        $match->AddCondition(
            $journal_frontend->Columns["cell_id"],
            $from,
            JAXP_MYSQL_MATCH_EQUAL
        );

        # Move to destination cell.
        $this->MySqlHandler->Update($journal_frontend, $c, $match);
    } // MoveFrontendCell()

    /**
     * Updates Journal data to the database.
     * 
     * @uses    JaxpMySqlHandler
     * @uses    JaxpMySqlConditions
     * 
     * @param   JaxpJournalElement $obj  Element to update.
     * 
     * @access  public
     * @return  void
     * @since   1.1
     */
    function CommitChanges(JaxpJournalElement $obj)
    {
        # Define a local instance of MySqlHandler->Database for abbreviation
        # purposes.
        $db = $this->MySqlHandler->Database;
        
        # Updating a section.
        if ($obj instanceof JaxpJournalSection)
        {
            $table = $db->Tables["journal_sections"];
            $row = $this->MySqlHandler->CreateRow($table);
            $row->Columns["title"]->Value = $obj->Name;
            $row->Columns["colour"]->Value = $obj->Colour->ToHex();
        }

        # Updating a note.
        if ($obj instanceof JaxpJournalNote)
        {
            $table = $db->Tables["journal_notes"];
            $row = $this->MySqlHandler->CreateRow($table);
            $row->Columns["section_id"]->Value = $obj->Section->ElementId;
            $row->Columns["title"]->Value = $obj->Title;
            $row->Columns["summary"]->Value = $obj->Summary;
            $row->Columns["body"]->Value = $obj->Body;
            $row->Columns["author"]->Value = $obj->Author;
            $row->Columns["allow_comments"]->Value = $obj->AllowComments;
            $row->Columns["date_posted"]->Value =
                $obj->DatePosted->ToTimestamp();
            $row->Columns["required_media"]->Value = $obj->GetRequiredMedia();
        }

        # Send data to the database.
        $match = new JaxpMySqlConditions();
        $match->AddCondition(
            $row->Columns["id"],
            $obj->ElementId,
            JAXP_MYSQL_MATCH_EQUAL
        );

        $this->MySqlHandler->Update($table, $row, $match);
    } // CommitChanges()

    /**
     * Deletes a Journal element from the database.
     *
     * @uses    JaxpMySqlHandler
     * @uses    JaxpMySqlConditions
     *
     * @param   JaxpJournalElement  $object
     *
     * @access  public
     * @return  void
     * @since   1.1
     */
    function DeleteElement(JaxpJournalElement $object)
    {
        if ($object instanceof JaxpJournalSection)
        {
            $table = $this->MySqlHandler->Database->Tables["journal_sections"];
            $notes = $this->MySqlHandler->Database->Tables["journal_notes"];

            $match = new JaxpMySqlConditions();
            $match->AddCondition(
                $notes->Columns["section_id"],
                $object->ElementId,
                JAXP_MYSQL_MATCH_EQUAL
            );

            $this->MySqlHandler->Delete($notes, $match);
        }

        if ($object instanceof JaxpJournalNote)
        {
            $table = $this->MySqlHandler->Database->Tables["journal_notes"];
        }

        $match = new JaxpMySqlConditions();
        $match->AddCondition(
            $table->Columns["id"],
            $object->ElementId,
            JAXP_MYSQL_MATCH_EQUAL
        );

        $this->MySqlHandler->Delete($table, $match);
    } // DeleteElement()

    /**
     * Clears all frontend cells.
     *
     * @uses   JaxpMySqlHandler
     *
     * @return void
     * @access public
     * @since  1.2
     */
    function ClearFrontend()
    {
        $table = $this->MySqlHandler->Database->Tables["journal_frontend"];
        $this->MySqlHandler->Truncate($table);
    } // ClearFrontend()

    /**
     * Loads into the database a new ad file.
     *
     * @uses   JaxpMySqlHandler
     *
     * @param  string $file_name   Name of the uploaded ad file.
     *
     * @return void
     * @access public
     *
     * @internal    Still untested.
     */
    function RegisterAdFile($file_name)
    {
        $path = $this->PlatformSettings->Journal->BaseUrl . "/"
              . $this->PlatformSettings->Journal->AdPath . "/";
        $file = new JaxpString($file_name);

        $ad_table = $this->MySqlHandler->Database->LoadTable(
            "journal_advertisements"
        );

        $ad_row = $this->MySqlHandler->CreateRow($ad_table);
        if (!$file->Contains(".flv"))
        {
            list($width, $height, $type) = getimagesize($path . $file_name);
            $ad_row->Columns["width"]->Value = $width;
            $ad_row->Columns["height"]->Value = $height;
            switch ($type)
            {
                case IMAGETYPE_JPEG: $jaxp_type = JAXP_JOURNAL_JPEG_AD; break;
                case IMAGETYPE_GIF: $jaxp_type = JAXP_JOURNAL_GIF_AD; break;
                case IMAGETYPE_PNG: $jaxp_type = JAXP_JOURNAL_PNG_AD; break;
                case IMAGETYPE_SWF: $jaxp_type = JAXP_JOURNAL_SWF_AD; break;
            }
        }
        else
        {
            $width = 0;
            $height = 0;
            $jaxp_type = JAXP_JOURNAL_FLV_AD;
        }
        $ad_row->Columns["width"]->Value = $width;
        $ad_row->Columns["height"]->Value = $height;
        $ad_row->Columns["ad_type"]->Value = $jaxp_type;
        $ad_row->Columns["source_file"]->Value = $file_name;
        return $this->MySqlHandler->Insert($ad_row, $ad_table);
    } // RegisterAdFile()

    /**
     * Locates a note using permalink data.
     *
     * @uses    JaxpDate
     * @uses    JaxpMySqlHandler
     * @uses    JaxpMySqlConditions
     *
     * @access  public
     * @return  JaxpJournalNote     An object representing the note.
     * @since   1.2.1
     */
    function GetNoteFromPermalink()
    {
        # Parse the URL address and take the last two parts
        # (i.e. from "/uri/a/b/c/", take 'b' and 'c', unslashed.
        list($date, $title) = array_slice
        (
           explode("/", $_SERVER["REQUEST_URI"]),
           2, 2
        );

        # Convert date string to an usable object.
        $datePosted = new JaxpDate(JAXP_DATE_FROM_STRING, $date);

        # Reformat the title passed by GET.
        # Punctuation characters mutate to wildcards for LIKE-comparison.
        $title = str_replace("-", " ", $title);
        $title = str_replace("_", "%", $title);

        # Instantiate the Notes table.
        $journal_notes = $this->MySqlHandler->Database->Tables["journal_notes"];

        # Create a dual-condition filter, to locate by title and by date.
        $filter = new JaxpMySqlConditions();
        $filter->AddCondition
        (
           $journal_notes->Columns["title"],
           $title,
           JAXP_MYSQL_MATCH_CONTAINS
        );
        $filter->AddCondition
        (
           $journal_notes->Columns["date_posted"],
           $datePosted->ToTimestamp(),
           JAXP_MYSQL_MATCH_GREATER_OR_EQUAL_THAN
        );

        # Apply the filter.
        $note = $this->MySqlHandler->Select(
           "SELECT * FROM journal_notes WHERE " . $filter->ParseToStringList()
        );

        # Since the filter ensures only one entry will be retrieved,
        # return the first element of the resulting array.
        return $this->Notes[$note->Rows[0]->Columns["id"]->Value];
    } // GetNoteFromPermalink()

    /**
     * Increments the note's pageviews count.
     *
     * @uses    JaxpMySqlHandler
     *
     * @param   JaxpJournalNote $note     Note being read.
     *
     * @access  public
     * @return  void
     * @since   1.3
     */
    function UpdateNotePageviews(JaxpJournalNote $note)
    {
        $this->MySqlHandler->Select
        (
            "UPDATE journal_notes SET page_views = page_views + 1 "
          . "WHERE id = {$note->ElementId}"
        );
    }
     //// UpdateNotePageviews()

} // Jaxp.Journal

################################################################################
#  ABSTRACT CLASSES. Used as foundation for other classes.
################################################################################

/**
 * Represents a generic structure for Journal elements.
 *
 * @abstract
 * @package     Jaxp.Journal
 * @subpackage  Element
 * @since       1.2
 *
 * @internal    2010.02.17: Now supports inherited JaxpMySqlHandler.
 */

abstract class JaxpJournalElement extends JaxpObject
{
    /**
     * @var int Element's id.
     * @access public
     */
    public $ElementId;

    /**
     * @var int Element type.
     * @access public
     */
    public $ElementType;

    function __construct()
    {
        parent::__construct();
        $this->MySqlHandler->SelectDatabase($this->PlatformSettings->Journal->DatabaseName);
    } // __construct();
} // Jaxp.Journal.Element

################################################################################
#  SECTIONS & NOTES CLASSES
################################################################################

/**
 * Represents a section.
 *
 * @uses        JaxpJournalElement
 * @package     Jaxp.Journal
 * @subpackage  Section
 * @since       1.1
 */

class JaxpJournalSection extends JaxpJournalElement
{
    /**
     * @var string Section's name.
     * @access public
     */
    public $Name;

    /**
     * @var string Hexadecimal representation of the strip's colour.
     * @access public
     */
    public $Colour;

    /**
     * @var JaxpJournalNote[] Array of notes.
     * @access public
     */
    public $Notes;
    
    /**
     * Constructor, creates a Section object.
     * 
     * @uses    JaxpColour
     * 
     * @param   int     $section_id
     * @param   string  $name
     * @param   string  $colour 
     * 
     * @access  public
     * @return  void
     */
    function __construct($section_id, $name, $colour)
    {
        $this->ElementId = $section_id;
        $this->ElementType = JAXP_JOURNAL_ELEMENT_SECTION;
        $this->Name = $name;
        $this->Colour = JaxpColour::FromHex($colour);

        parent::__construct();
    } // __construct()
} // Jaxp.Journal.Section

/**
 * Represents a Note from the Journal.
 *
 * @uses        JaxpJournalElement
 * @package     Jaxp.Journal
 * @subpackage  Note
 * @since       1.0
 */

class JaxpJournalNote extends JaxpJournalElement
{
    /**
     * @var string Note title.
     * @access public
     */
    public $Title;

    /**
     * @var JaxpJournalSection Contains a reference to the note's section.
     * @access public
     */
    public $Section;

    /**
     * @var string Brief description of the note's contents.
     * @access public
     */
    public $Summary;

    /**
     * @var string Note text.
     * @access public
     */
    public $Body;

    /**
     * @var string Who wrote the note. Also usable as source.
     * @access public
     */
    public $Author;

    /**
     * @var JaxpJournalNoteComment[] Array of comments belonging to the note.
     * @access public
     */
    public $Comments;

    /**
     * @var JaxpJournalMediaElement[] Array of Media components.
     * @access public
     */
    public $Media;

    /**
     * @var JaxpDatePosted Object containg information about publishing date.
     * @access public
     */
    public $DatePosted;

    /**
     * @var bool True if the note allows comments, false otherwise.
     * @access public
     */
    public $AllowComments;

    /**
     * @var int Amount of times this note has been read.
     * @access public
     */
    public $PageViews;

    /**
     * @var string Serialized format of needed media (folder/id;folder/id;...).
     * @access private
     */
    private $_RequiredMedia;

    /**
     * Constructor method. Paramaters are self-explanatory.
     *
     * @uses    JaxpJournalSection
     *
     * @param   int                    $id
     * @param   string                 $title
     * @param   JaxpJournalSection     $section
     * @param   string                 $summary
     * @param   string                 $body
     * @param   string                 $author
     * @param   JaxpJournalComments[]  $comments
     * @param   bool                   $allow_comments
     * @param   JaxpDate               $date_posted
     * @param   int                    $page_views
     *
     * @access  public
     * @return  void
     * @since   1.0
     */
    function __construct(
        $id, $title, JaxpJournalSection $section, $summary,
        $body, $author, $comments, $allow_comments, JaxpDate $date_posted,
        $page_views, $required_media
    )
    {
        # Prevent infinite recursion by supressing the Notes collection
        # of the section linked to this note.
        unset($section->Notes);

        # Assign element data: id and type. This is a *note*.
        $this->ElementId = $id;
        $this->ElementType = JAXP_JOURNAL_ELEMENT_NOTE;

        # Assign note properties and link the section to the note.
        $this->Title = $title;
        $this->Section = new JaxpJournalSection
        (
            $section->ElementId,
            $section->Name,
            $section->Colour->ToHex()
        );
        $this->Summary = $summary;
        $this->Body = $body;
        $this->Author = $author;
        $this->Comments = $comments;
        $this->AllowComments = $allow_comments;
        $this->DatePosted = $date_posted;
        $this->PageViews = $page_views;

        $this->Media = array();

        $this->_RequiredMedia = $required_media;

        # Eliminate unnecessary objects from the modular inheritance.
        unset($this->MySqlHandler, $this->FileSystem);
    } // __construct()

    /**
     * Returns a friendly-URL encoded version of the note's title.
     *
     * @uses    JaxpString
     *
     * @access  public
     * @return  string  The encoded title.
     * @since   1.2.1
     */
    function GetTitleAsFriendlyUrl()
    {
        # Convert the title to a string object.
        $str = new JaxpString($this->Title);

        # Return the encoded text.
        return $str->ToFriendlyUrlText();
    } // GetTitleAsFriendlyUrl()

    function GetPhotos()
    {
        foreach ($this->Media as $media)
        {
            if (JaxpMedia::GetMediaObjectType($media) == JAXP_MEDIA_TYPE_PHOTO)
            {
                $photos[] = $media;
            }
        }
        return $photos;
    } // GetPhotos()

    function GetRequiredMedia()
    {
        return $this->_RequiredMedia;
    }

    function SetRequiredMedia($required_media)
    {
        $this->_RequiredMedia = $required_media;
    }
    
    /**
     * Creates a permalink HREF for this note.
     *
     * @uses    JaxpTemplateParser
     * @uses    JaxpTemplate
     *
     * @param   string $base_url        Main domain (http:// ...)
     * @param   string $notes_app_path  Sub-address component (as in /notes/).
     * @param   int    $mode            Permalink style: short (only note ID),
     *                                  descriptive (note date + title) or
     *                                  default (read from settings file).
     *
     * @access  public
     * @return  string
     * @since   1.2.1
     */
    function GetPermalink($base_url, $notes_app_path,
                          $mode = JAXP_JOURNAL_PERMALINK_DEFAULT)
    {
        # If the mode is set to default, select the permalink style
        # from the settings.
        if ($mode == JAXP_JOURNAL_PERMALINK_DEFAULT)
        {
            $mode = $this->PlatformSettings->PermalinkMode;
        }

        # Which mode are we using?
        switch ($mode)
        {
            # It's a short permalink...
            case JAXP_JOURNAL_PERMALINK_SHORT:

                # ...then we create this template.
                $permalink = JaxpTemplateParser::FromString
                (
                    "%DOMAIN%/%NOTES_APP%/%ID%/"
                );

                # These markings wrap the tags we want to replace with data.
                $permalink->SetTagMarking("%", "%");

                # Replace the tags with the proper content.
                $link = $permalink->Display(
                    array
                    (
                        "DOMAIN"    => $base_url,
                        "NOTES_APP" => $notes_app_path,
                        "ID"        => $this->ElementId
                    ),
                    true
                );
            break;

            # It's a descriptive permalink...
            case JAXP_JOURNAL_PERMALINK_DESCRIPTIVE:

                # ...then we create this (longer) template.
                $permalink = JaxpTemplateParser::FromString
                (
                    "%DOMAIN%/%NOTES_APP%/%YEAR%-%MONTH%-%DAY%/%TITLE%/"
                );

                # Use the same tag wrappers as the brief permalink.
                $permalink->SetTagMarking("%", "%");

                # Replace the tags with the proper content.
                $link = $permalink->Display(
                    array
                    (
                        "DOMAIN"    => $base_url,
                        "NOTES_APP" => $notes_app_path,
                        "YEAR"      => $this->DatePosted->Year,
                        "MONTH"     => $this->DatePosted->Month,
                        "DAY"       => $this->DatePosted->Day,
                        "TITLE"     => $this->GetTitleAsFriendlyUrl()
                    ),
                    true
                );
            break;
        }

        # Return the permalink.
        return $link;
    } // GetPermalink()
} // Jaxp.Journal.Note

/**
 * Represents a note comment.
 *
 * @uses        JaxpJournalElement
 * @package     Jaxp.Journal
 * @subpackage  NoteComment
 * @since       1.0
 *
 * @internal    Structurally implemented only. Non-functional.
 */

class JaxpJournalNoteComment extends JaxpJournalElement
{
    /**
     * @var int Note's id.
     * @access public
     */
    public $NoteId;

    /**
     * @var string Comment author.
     * @access public
     */
    public $Author;

    /**
     * @var string Originating ip.
     * @access public
     */
    public $Ip;

    /**
     * @var string Comment text.
     * @access public
     */
    public $Text;

    /**
     * Constructor method. Parameters are self-explanatory.
     *
     * @param   int     $id
     * @param   int     $note_id
     * @param   string  $author
     * @param   string  $ip
     * @param   string  $text
     *
     * @access  public
     * @return  void
     * @since   1.0
     */
    function __construct($id, $note_id, $author, $ip, $text)
    {
        $this->ElementId = $id;
        $this->ElementType = JAXP_JOURNAL_ELEMENT_NOTE_COMMENT;
        $this->NoteId = $note_id;
        $this->Autor = $author;
        $this->Ip = $ip;
        $this->Text = $text;
    } // __construct()
} // Jaxp.Journal.NoteComment

################################################################################
# FRONTEND CLASSES
################################################################################

/**
 * Represents the Frontend of the Journal.
 * 
 * @package     Jaxp.Journal
 * @subpackage  Frontend
 * @since       1.1
 */

class JaxpJournalFrontend
{
    /**
     * @var JaxpJournalElement[] Array of cells containing Elements.
     * @access public
     */
    public $Cells = array();

    /**
     * Creates a new cell instance.
     *
     * @uses    JaxpJournalFrontendCell
     *
     * @param   int     $index          Column number.
     *
     * @access  public
     * @return  void
     * @since   1.1
     */
    function CreateCell($cell_id, $element, $displayMode)
    {
        # Instantiate the frontend cell with the given cell id.
        $this->Cells[$cell_id] = new JaxpJournalFrontendCell($cell_id);

        # Link the given element with its display mode to this cell.
        $this->Cells[$cell_id]->SetElement($element, $displayMode);
    } // CreateCell()
} // Jaxp.Journal.Frontend

/**
 * Represents a cell from the Frontend.
 *
 * @package     Jaxp.Journal
 * @subpackage  FrontendCell
 * @since       1.2
 *
 * @internal    2010.02.14: Deprecated JaxpJournalFrontendColumn.
 */

class JaxpJournalFrontendCell
{
    /**
     * @var JaxpJournalElement A reference to the contained element.
     * @access public
     */
    public $Element;

    /**
     * @var string This cell's id.
     * @access public
     */
    public $CellId;

    /**
     * @var int Element's display mode. May vary according to element type.
     * @access public
     */
    public $DisplayMode;

    /**
     * Constructor method.
     *
     * @param   string $cell_id     Unique identifier for the cell.
     *
     * @access  public
     * @return  void
     * @since   1.2
     */
    function __construct($cell_id)
    {
        $this->CellId = $cell_id;
    } // __construct()

    /**
     * Places a journal element inside the cell.
     *
     * @param   JaxpJournalElement  $element        The journal element to put.
     * @param   int                 $displayMode    Element display mode.
     *                                              Set according to its type.
     *
     * @access  public
     * @return  void
     * @since   1.2.1
     */
    function SetElement($element, $displayMode)
    {
        $this->Element = $element;
        $this->DisplayMode = $displayMode;
    } // SetElement()

    /**
     * Cleans the cell's contents (on memory, not in database).
     *
     * @access  public
     * @return  void
     * @since   1.2.1
     */
    function UnsetElement()
    {
        $this->Element = null;
        $this->DisplayMode = null;
    } // UnsetElement()
} // Jaxp.FrontendCell

################################################################################
#  ADVERTISEMENT
################################################################################

class JaxpJournalAdvertisement extends JaxpJournalElement
{
    public $Width;
    public $Height;
    public $SourceFileName;
    public $Format;
    
    function __construct($width, $height, $source_file)
    {
        $this->Width = $width;
        $this->Height = $height;
        $this->SourceFileName = $source_file;
        $this->ElementType = JAXP_JOURNAL_ELEMENT_AD;

        $this->set_ad_type();
    }
    
    private function set_ad_type()
    {
        $source_file_name = new JaxpString($this->SourceFile);
        foreach ($this->PlatformSettings->Journal->AllowedAdFileFormats
                 as
                 $format)
        {
            list($ff, $i) = explode(":", $format);
            if ($source_file->EndsWith($ff))
            {
                $this->Format = $i;
            }            
        }
    }
}
?>