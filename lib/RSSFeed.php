<?php 
class RSSFeed {
// VARIABLES
    // channel vars
    var $channel_url='';
    var $channel_title='';
    var $channel_description='';
    var $channel_lang='';
    var $channel_copyright='';
    var $channel_date='';
    var $channel_creator='';
    var $channel_subject='';
    // image
    var $image_url;
    // items
    var $items = array();
    var $nritems=0;

    /**
     * Set RSS channel
     * @param $url Channel url
     * @param $title Title
     * @param $description Description
     * @param $lang Language of the feed channel
     * @param $copyright Copyright notice
     * @param $creator Creator of feed channel
     * @param $subject Subject of channel
     * @return void
     */
    function setChannel($url, $title, $description, $lang, $copyright, $creator, $subject) {
        $this->channel_url=$url;
        $this->channel_title=$title;
        $this->channel_description=$description;
        $this->channel_lang=$lang;
        $this->channel_copyright=$copyright;
        $this->channel_date=date("c");
        $this->channel_creator=$creator;
        $this->channel_subject=$subject;
    }
    
    /**
     * Set the channel image
     * @param $url URL to image
     * @return void
     */
    function setImage($url) {
        $this->image_url=$url;  
    }
    
    /**
     * Register new feeditem
     * @param $url Reference URL
     * @param $title Title of item
     * @param $description Item description
     * @return void
     */
    function setItem($url, $title, $description) {
        $this->items[$this->nritems]['url']=$url;
        $this->items[$this->nritems]['title']=$title;
        $this->items[$this->nritems]['description']=$description;
        $this->nritems++;
    }
    
    /**
     * outputs the feed
     * @return string RSS feed
     */
    function output() {
        global $CONFIG;
        $output =  '<?xml version="1.0" encoding="'.$CONFIG->Site->charset.'"?>'."\n";
        $output .= '<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns="http://purl.org/rss/1.0/" xmlns:slash="http://purl.org/rss/1.0/modules/slash/" xmlns:taxo="http://purl.org/rss/1.0/modules/taxonomy/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:syn="http://purl.org/rss/1.0/modules/syndication/" xmlns:admin="http://webns.net/mvcb/" xmlns:feedburner="http://rssnamespace.org/feedburner/ext/1.0">'."\n";
        $output .= '<channel rdf:about="'.$this->channel_url.'">'."\n";
        $output .= '<title>'.$this->channel_title.'</title>'."\n";
        $output .= '<link>'.$this->channel_url.'</link>'."\n";
        $output .= '<description>'.$this->channel_description.'</description>'."\n";
        $output .= '<dc:language>'.$this->channel_lang.'</dc:language>'."\n";
        $output .= '<dc:rights>'.$this->channel_copyright.'</dc:rights>'."\n";
        $output .= '<dc:date>'.$this->channel_date.'</dc:date>'."\n";
        $output .= '<dc:creator>'.$this->channel_creator.'</dc:creator>'."\n";
        $output .= '<dc:subject>'.$this->channel_subject.'</dc:subject>'."\n";

        $output .= '<items>'."\n";
        $output .= '<rdf:Seq>';
        for($k=0; $k<$this->nritems; $k++) {
            $output .= '<rdf:li rdf:resource="'.$this->items[$k]['url'].'"/>'."\n"; 
        };    
        $output .= '</rdf:Seq>'."\n";
        $output .= '</items>'."\n";
        $output .= '<image rdf:resource="'.$this->image_url.'"/>'."\n";
        $output .= '</channel>'."\n";
        for($k=0; $k<$this->nritems; $k++) {
            $output .= '<item rdf:about="'.$this->items[$k]['url'].'">'."\n";
            $output .= '<title>'.$this->items[$k]['title'].'</title>'."\n";
            $output .= '<link>'.$this->items[$k]['url'].'</link>'."\n";
            $output .= '<description>'.$this->items[$k]['description'].'</description>'."\n";
            $output .= '<feedburner:origLink>'.$this->items[$k]['url'].'</feedburner:origLink>'."\n";
            $output .= '</item>'."\n";  
        };
        $output .= '</rdf:RDF>'."\n";
        return $output;
    }
};
?>