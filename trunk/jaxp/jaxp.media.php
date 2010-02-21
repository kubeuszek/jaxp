<?php
/******************************************************************************
 * Handles media audio, video and images.
 *
 * @package  Jaxp.Media
 * @author   Joel A. Villarreal Bertoldi <design@joelalejandro.com>
 * @version  1.0
 ******************************************************************************/

/**
 * Constant list for media formats.
 */
define("JAXP_MEDIA_TYPE_PHOTO", 1);
define("JAXP_MEDIA_TYPE_VIDEO", 2);
define("JAXP_MEDIA_TYPE_AUDIO", 3);

class JaxpMedia
{
    static function GetMediaObjectType($obj)
    {
        if ($obj instanceof JaxpMediaPhoto)
        {
            $type = JAXP_MEDIA_TYPE_PHOTO;
        }
        else if ($obj instanceof JaxpMediaAudio)
        {
            $type = JAXP_MEDIA_TYPE_AUDIO;
        }
        else if ($obj instanceof JaxpMediaVideo)
        {
            $type = JAXP_MEDIA_TYPE_VIDEO;
        }
        return $type;
    } // GetMediaType()
} // Jaxp.Media

/**
 * Represents a generic structure for Media elements.
 *
 * @uses        JaxpObject
 * @abstract
 * @package     Jaxp.Media
 * @subpackage  Element
 * @since       1.0
 */

abstract class JaxpMediaElement extends JaxpObject
{
    /**
     * @var int Media unique id#.
     * @access public
     */
    public $ElementId;

    /**
     * @var string Media title.
     * @access public
     */
    public $Title;

    /**
     * @var string Media description.
     * @access public
     */
    public $Description;

    /**
     * @var string Media filename.
     * @access public
     */
    public $SourceFile;

    /**
     * Constructor method.
     *
     * @access  public
     * @return  void
     * @since   1.0
     */
    function __construct()
    {
        parent::__construct();
    } // __construct()
} // Jaxp.Media.Element

/**
 * Represents a set of media components.
 *
 * @uses        JaxpElement
 * @package     Jaxp.Media
 * @subpackage  Gallery
 * @since       1.0
 */

class JaxpMediaGallery extends JaxpMediaElement
{
    /**
     * @var JaxpMediaPhoto[] Collection of images.
     * @access public
     */
    public $Photo;

    /**
     * @var JaxpMediaVideo[] Collection of video.
     * @access public
     */
    public $Video;

    /**
     * @var JaxpMediaAudio[] Collection of audio files.
     * @access public
     */
    public $Audio;

    /**
     * Constructor method. Parameters are self-explanatory.
     *
     * @param   int     $id
     * @param   string  $title
     * @param   string  $description
     * @param   string  $database_name  MySQL Source.
     *
     * @access  public
     * @return  void
     * @since   1.0
     */
    function __construct($id, $title, $description, $database_name)
    {
        $this->ElementId = $id;
        $this->Title = $title;
        $this->Description = $description;

        $this->Photo = array();
        $this->Video = array();
        $this->Audio = array();

        parent::__construct();
        $this->MySqlHandler->SelectDatabase($database_name);

        unset($this->SourceFile);
    } // __construct()

    function GetMediaById($id)
    {
        switch ($this->GetMediaType($id))
        {
            case JAXP_MEDIA_TYPE_PHOTO:
                $media = $this->Photo[$id];
            break;
            case JAXP_MEDIA_TYPE_AUDIO:
                $media = $this->Audio[$id];
            break;
            case JAXP_MEDIA_TYPE_VIDEO:
                $media = $this->Video[$id];
            break;
        }
        return $media;
    } // GetMediaById()

    function GetMediaType($id)
    {
        if (array_key_exists($id, $this->Photo))
        {
            $type = JAXP_MEDIA_TYPE_PHOTO;
        }
        else if (array_key_exists($id, $this->Audio))
        {
            $type = JAXP_MEDIA_TYPE_AUDIO;
        }
        else if (array_key_exists($id, $this->Video))
        {
            $type = JAXP_MEDIA_TYPE_VIDEO;
        }
        return $type;
    } // GetMediaType()

    /**
     * Look up all media belonging to this gallery and organize it
     * into the pertinent object collections.
     *
     * @uses    JaxpMySqlHandler
     * @uses    JaxpMySqlConditions
     * @uses    JaxpJournalMediaPhoto
     * @uses    JaxpJournalMediaVideo
     * @uses    JaxpJournalMediaAudio
     *
     * @param   string $source_table_name   Table which contains media data.
     *
     * @access  public
     * @return  void
     * @since   1.0
     */
    function LoadMedia($source_table_name)
    {
        # Shortcut to the database object.
        $db = $this->MySqlHandler->Database;

        # Instantiate the Media table.
        $journal_media = $db->LoadTable($source_table_name);

        # Create a filter to locate media that belongs to this gallery.
        # The filter uses the gallery id as a relation key.
        $media_match = new JaxpMySqlConditions();
        $media_match->AddCondition(
            $journal_media->Columns["gallery_id"],
            $this->ElementId,
            JAXP_MYSQL_MATCH_EQUAL
        );

        # Execute the filter.
        $gallery_media = $this->MySqlHandler->Filter(
            $journal_media,
            $media_match
        );

        # If there are any media contents found...
        if (count($gallery_media->Rows))
        {
            # ...iterate through them...
            foreach ($gallery_media->Rows as $r)
            {
                # ...get current media's id...
                $rId = $r->Columns["id"]->Value;

                # ...determine the media type...
                switch ($r->Columns["media_type"]->Value)
                {
                    # Is it a photo?
                    case JAXP_MEDIA_TYPE_PHOTO:
                        # Add it to the Photo collection.
                        $this->Photo[$rId] = new JaxpMediaPhoto(
                            $rId,
                            $r->Columns["title"]->Value,
                            $r->Columns["description"]->Value,
                            $r->Columns["source_file_name"]->Value,
                            $this->ElementId
                        );
                    break;

                    # Is it a video?
                    case JAXP_MEDIA_TYPE_VIDEO:
                        # Add it to the Video collection.
                        $this->Video[$rId] = new JaxpMediaVideo(
                            $rId,
                            $r->Columns["title"]->Value,
                            $r->Columns["description"]->Value,
                            $r->Columns["source_file_name"]->Value,
                            $this->ElementId
                        );
                    break;

                    # Is it audio?
                    case JAXP_MEDIA_TYPE_AUDIO:
                        # Add it to the Audio collection.
                        $this->Audio[$rId] = new JaxpMediaAudio(
                            $rId,
                            $r->Columns["title"]->Value,
                            $r->Columns["description"]->Value,
                            $r->Columns["source_file_name"]->Value,
                            $this->ElementId
                        );
                    break;
                }
            }
        }
    } // LoadMedia()
} // Jaxp.Media.Gallery

/**
 * Represents a Photo in the Media Gallery.
 *
 * @uses        JaxpMediaElement
 * @package     Jaxp.Media
 * @subpackage  Photo
 * @since       1.0
 */

class JaxpMediaPhoto extends JaxpMediaElement
{
    public $Width;
    public $Height;

    public $FullPath;

    public $Thumbnail;

    /**
     * Constructor method. Parameters are self-explanatory.
     *
     * @uses    JaxpMediaElement
     *
     * @param   string $title
     * @param   string $description
     * @param   string $source_file
     *
     * @access  public
     * @return  void
     * @since   1.0
     */
    function __construct($id, $title, $description, $source_file, $gallery_id)
    {
        $this->ElementId = $id;
        $this->GalleryId = $gallery_id;
        $this->Title = $title;
        $this->Description = $description;
        $this->SourceFile = ($source_file);

        parent::__construct();

        $this->FullPath = $this->PlatformSettings->BaseUrl
                         . "/" . $this->PlatformSettings->MediaPath
                         . "/photo/" . $source_file;
        
        $image_data = getimagesize($this->FullPath);

        $this->Width = $image_data[0];
        $this->Height = $image_data[1];
        $this->MimeType = $image_data[2];

    } // __construct()

    function CreateThumbnail($max_width, $max_height)
    {
        switch ($this->MimeType)
        {
            case IMAGETYPE_JPEG:
                $imagecreate = "imagecreatefromjpeg";
            break;
            case IMAGETYPE_GIF:
                $imagecreate = "imagecreatefromgif";
            break;
            case IMAGETYPE_PNG:
                $imagecreate = "imagecreatefrompng";
            break;
        }

        $source = $imagecreate($this->FullPath);
        $width = $this->Width;
        $height = $this->Height;

        /**
         * @link http://ar2.php.net/manual/en/function.getimagesize.php#82343
         */
        $x_ratio = $max_width / $width;
        $y_ratio = $max_height / $height;

        if ($width <= $max_width && $height <= $max_height)
        {
            $thumb_width = $width;
            $thumb_height = $height;
        }
        else if ($x_ratio * $height < $max_height)
        {
            $thumb_height = ceil($x_ratio * $height);
            $thumb_width = $max_width;
        }
        else
        {
            $thumb_width = ceil($y_ratio * width);
            $thumb_height = $max_height;
        }

        $thumbnail = imagecreatetruecolor($thumb_width, $thumb_height);
        imagecopyresampled
        (
            $thumbnail, $source,
            0, 0,
            0, 0,
            $thumb_width, $thumb_height,
            $width, $height
        );

        $tmp_name = tempnam("/tmp", "");
        imagejpeg($thumbnail, $tmp_name, 90);

        $this->Thumbnail = base64_encode(file_get_contents($tmp_name));

        unlink($tmp_name);

        imagedestroy($thumbnail);
        imagedestroy($source);
    } // GetThumbnail()
} // Jaxp.Media.Photo

/**
 * Represents a Video in the Media Gallery.
 *
 * @uses        JaxpJournalMediaElement
 * @package     Jaxp.Journal
 * @subpackage  MediaVideo
 * @since       1.0
 */

class JaxpMediaVideo extends JaxpMediaElement
{
    /**
     * Constructor method. Parameters are self-explanatory.
     *
     * @uses    JaxpMediaElement
     *
     * @param   string $title
     * @param   string $description
     * @param   string $source_file
     *
     * @access  public
     * @return  void
     * @since   1.0
     */
    function __construct($id, $title, $description, $source_file, $gallery_id)
    {
        $this->ElementId = $id;
        $this->GalleryId = $gallery_id;
        $this->Title = $title;
        $this->Description = $description;
        $this->SourceFile = ($source_file);

        parent::__construct();

        $this->FullPath = $this->PlatformSettings->BaseUrl
                         . "/" . $this->PlatformSettings->MediaPath
                         . "/video/" . $source_file;

    } // __construct()
} // Jaxp.Media.Video

/**
 * Represents an Audio file in the Media Gallery.
 *
 * @uses        JaxpJournalMediaElement
 * @package     Jaxp.Journal
 * @subpackage  MediaAudio
 * @since       1.0
 */

class JaxpMediaAudio extends JaxpMediaElement
{
    /**
     * Constructor method. Parameters are self-explanatory.
     *
     * @uses    JaxpMediaElement
     *
     * @param   string $title
     * @param   string $description
     * @param   string $source_file
     *
     * @access  public
     * @return  void
     * @since   1.0
     */
    function __construct($id, $title, $description, $source_file, $gallery_id)
    {
        $this->ElementId = $id;
        $this->GalleryId = $gallery_id;
        $this->Title = $title;
        $this->Description = $description;
        $this->SourceFile = ($source_file);

        parent::__construct();

        $this->FullPath = ""
                          . "/" . $this->PlatformSettings->MediaPath
                          . "/audio/" . $source_file;
    } // __construct()
} // Jaxp.Media.Audio
?>
