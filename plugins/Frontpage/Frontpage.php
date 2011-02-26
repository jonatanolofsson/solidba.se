<?php
class Frontpage extends Page {

    function __construct($id, $language=false) {
        parent::__construct($id, $language);
    }

    function run() {
        global $Templates, $CONFIG, $DB, $Controller;

        $_REQUEST->setType('flash','any');
        if($_REQUEST->valid('flash')){
            Flash::create($_REQUEST['flash'].'_flash_1',$_REQUEST['flash']);
        }

        $_REQUEST->setType('addToConfig','any');
        if($_REQUEST->valid('addToConfig')){
            $CONFIG->Frontpage->setType('NewsItems', 'text');
            $CONFIG->Frontpage->setDescription('NewsItems','Number of news items displayed');
            $CONFIG->Frontpage->NewsItems = 5;
        }

        $content = '';

        $newsNum = $CONFIG->Frontpage->NewsItems;
        if(!is_numeric($newsNum) || $newsNum<1 || $newsNum>30) $newsNum = 5;
		$newsNum = 3;
        /* Retrive news objects */
        if($newsNum > 0) {
            $newsObj = Flow::retrieve('News', $newsNum, false, false, false, 0, true);
        }

        /* <<< New flowing design >>> */
        foreach($newsObj as $news){
            $content .= $news->display('new');
        }
       	
       	$mlForm = new Form('mailListForm');
       	$ml = $mlForm->quick(null,__('Send'),
       			new Input('Email','mlmail')
       		);
        $r = '<div id="intro">
        	<div class="lcol"><img src="'.$Templates->current->webdir.'images/intro/IMG_0817.jpg" width="400" alt="Lihkoren" /></div>
	        <div class="rcol"><p>Link&ouml;pings Students&aring;ngarf&ouml;rening Lihk&ouml;ren &auml;r en mansk&ouml;r som verkar vid Link&ouml;pings universitet, under ledning av director musices Hans Lundgren. K&ouml;ren bildades 1972 av studenter vid d&aring;varande Link&ouml;pings H&ouml;gskola.
Lihk&ouml;ren uttalas som det smakar. K&ouml;ren framf&ouml;r fr&auml;mst nordisk och europeisk musik, delvis fr&aring;n den traditionella mansk&ouml;rsrepertoaren men &auml;ven nyskriven musik. Glimten i &ouml;gat och den goda kontakten med publiken pr&auml;glar konserterna.<br>V&auml;l m&ouml;tt.</p></div>
			<img src="'.$Templates->current->webdir.'images/rand_top.png" alt="pagesplit" class="pagesplit" />
		</div>
		<div id="fbottom">
			<div class="lcol">
				<div class="lbox coming"><h1 class="icn-hdr"><span class="icn icn-coming"></span>'.__('Kommande h&auml;ndelser').'</h1>
					<p>Kommande h&auml;ndelser i kalendern.</p>
				</div>
				<div class="lbox maillist"><h1 class="icn-hdr"><span class="icn icn-mail"></span>'.__('Nyhetsbrev').'</h1>
					<p class="pre">Vill du f&aring; information om kommande konserter och andra arrangemang med Lihk&ouml;ren?</p>
					'.$ml.'
					<p>Du kommer d&aring; att f&aring; ett e-brev som du m&aring;ste svara p&aring; f&ouml;r att bekr&auml;fta att du vill att informationen ska skickas till dig</p>
				</div>
			</div>
        	<div class="rbox news"><h1 class="icn-hdr"><span class="icn icn-news"></span>'.__('Nyheter').'</h1>'.$content.'<a href="/flowView?q=News">'.__('View all news').'</a></div>
        </div>';
/*         dump($Templates->current->webdir); */
        
        $this->setContent('main', $r);
        $Templates->render();
    }
}
?>
