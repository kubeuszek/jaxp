<?php
define("JAXP_RSS_HEADER",
<<<CODE
<?xml version="1.0" encoding="utf-8"?>
<rss version="2.0">
<channel>

CODE
);

define("JAXP_RSS_CHANNEL",
<<<CODE
<title>%s</title>
<description>%s</description>
<link>%s</link>

CODE
);

define("JAXP_RSS_ITEM",
<<<CODE
<item>
    <title>%s</title>
    <description>%s</description>
    <link>%s</link>
</item>

CODE
);

define("JAXP_RSS_FOOTER",
<<<CODE
</channel>
</rss>
CODE
);

class JaxpRssFeed extends JaxpModule
{
    public $Channel;

    function __construct($channel_name, $channel_description, $channel_link)
    {
        $this->Channel = new JaxpRssChannel($channel_name, $channel_description, $channel_link);
        parent::__construct();  
    }
    
    function LoadFromTable(
        JaxpMySqlTable $table_source,
        $title_field,
        $value_field,
        $date_field,
        $id_field,
        $link_template
    )
    {
        $table_source->SortBy($table_source->Columns[$date_field], true);
        
        foreach ($table_source->SortedRows as $sr)
        {
            $date = new JaxpDate(JAXP_DATE_FROM_TIMESTAMP, $sr->Columns[$date_field]->Value);
            $link = $link_template;
            $link = str_replace("%DOMAIN%", $this->PlatformSettings->BaseUrl, $link);
            $link = str_replace("%YEAR%", $date->Year, $link);
            $link = str_replace("%MONTH%", $date->Month, $link);
            $link = str_replace("%DAY%", $date->Day, $link);
            $link = str_replace("%TITLE%", (ToJaxpString($sr->Columns[$title_field]->Value)->ToFriendlyUrlText()), $link);
            $link = str_replace("%ID%", $sr->Columns[$id_field]->Value, $link);
            $this->Channel->AddItem(
                new JaxpRssFeedItem(
                    $date->ToFormattedString("d/m/Y H:i") . " - " . $sr->Columns[$title_field]->Value,
                    $sr->Columns[$value_field]->Value,
                    $link,
                    $date
                )
            );
        }
    }
    
    function LoadFromArray($items, $link_template)
    {
        foreach ($items as $item)
        {
            $date = new JaxpDate(JAXP_DATE_FROM_TIMESTAMP, $item["date"]);
            $link = str_replace("%DOMAIN%", $this->PlatformSettings->BaseUrl, $link);
            $link = str_replace("%YEAR%", $date->Year, $link);
            $link = str_replace("%MONTH%", $date->Month, $link);
            $link = str_replace("%DAY%", $date->Day, $link);
            $link = str_replace("%TITLE%", ToJaxpString($item["title"])->ToFriendlyUrlText(), $link);
            $link = str_replace("%ID%", $item["id"], $link);
            $this->Channel->AddItem(
                new JaxpRssFeedItem(
                    $date->ToFormattedString("d/m/Y H:i") . " - " . $item["title"],
                    $item["description"],
                    $link,
                    $date
                )
            );
        }
    }
    
    function Syndicate()
    {
        header("Content-Type: text/xml; charset=utf-8");
        $rss = JAXP_RSS_HEADER;
        $rss .= sprintf(JAXP_RSS_CHANNEL, $this->Channel->Name, $this->Channel->Description, $this->Channel->Link);
        foreach ($this->Channel->Items as $i)
        {
            $rss .= sprintf(JAXP_RSS_ITEM, $i->Name, $i->Description, utf8_encode($i->Link));
        }
        $rss .= JAXP_RSS_FOOTER;
        echo $rss;
        exit();
    }
}

class JaxpRssFeedItem
{
    public $Name;
    public $Description;
    public $Link;
    public $DatePosted;
    
    function __construct($name = "", $description = "", $link = "", JaxpDate $date_posted = null)
    {
        $this->Name = $name;
        $this->Description = $description;
        $this->Link = $link;
        $this->DatePosted = $date_posted;
    }
}

class JaxpRssChannel
{
    public $Items;

    function __construct($name = "", $description = "", $link = "")
    {
        $this->Name = $name;
        $this->Description = $description;
        $this->Link = $link;
    }
    
    function AddItem(JaxpRssFeedItem $item)
    {
        $this->Items[] = $item;
    }
}

?>