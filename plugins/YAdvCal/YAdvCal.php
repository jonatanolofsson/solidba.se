<?php
class YAdvCal extends Page {
    private $year = 2009;

    function __construct($id, $lang=false) {
        parent::__construct($id, $lang);
        $this->alias = 'yulkalender';
        $this->suggestName('Yulkalender', 'sv');
    }

    function icon() {
        if(date('m') != 12) return;
        Head::add('$("#yulkalender").click(function(){window.open(\''.url(array('id' => $this->alias)).'\', \'yulkalendern\', \'toolbar=0,scrollbars=1,location=0,statusbar=0,menubar=0,resizable=1,width=800,height=450\');return false;});','js-raw');
        return '<a href="#" id="yulkalender" title="Öppna Yulkalendern">'
            .'<img src="templates/yweb/images/julkalender_banner.png" /></a>';
    }

    function run() {
        global $Templates;
        $_REQUEST->setType('day', 'numeric');
        if($_REQUEST['day'] && time()>=mktime(0,0,0,12,$_REQUEST['day'],$this->year))
            $this->{'day_'.$_REQUEST['day']}();
        else $this->setContent('main', $this->calendar());
        $Templates->yweb('empty_red')->render();
    }

    function calendar() {
        $r = '<map id="imgmap_yulkalender" name="imgmap_yulkalender">';
        $now = time();
        if($now >= mktime(0,0,0,12,1,$this->year)) $r .= '<area shape="rect" alt="1" title="1" coords="346,176,380,210" href="'.url(array('day' => '1'), 'id').'" />';
        if($now >= mktime(0,0,0,12,2,$this->year)) $r .= '<area shape="rect" alt="2" title="2" coords="158,264,187,297" href="'.url(array('day' => '2'), 'id').'" />';
        if($now >= mktime(0,0,0,12,3,$this->year)) $r .= '<area shape="rect" alt="3" title="3" coords="406,133,431,160" href="'.url(array('day' => '3'), 'id').'" />';
        if($now >= mktime(0,0,0,12,4,$this->year)) $r .= '<area shape="rect" alt="4" title="4" coords="36,65,56,95" href="'.url(array('day' => '4'), 'id').'" />';
        if($now >= mktime(0,0,0,12,5,$this->year)) $r .= '<area shape="rect" alt="5" title="5" coords="711,299,746,334" href="'.url(array('day' => '5'), 'id').'" />';
        if($now >= mktime(0,0,0,12,6,$this->year)) $r .= '<area shape="rect" alt="6" title="6" coords="86,152,121,194" href="'.url(array('day' => '6'), 'id').'" />';
        if($now >= mktime(0,0,0,12,7,$this->year)) $r .= '<area shape="rect" alt="7" title="7" coords="243,174,285,210" href="'.url(array('day' => '7'), 'id').'" />';
        if($now >= mktime(0,0,0,12,8,$this->year)) $r .= '<area shape="rect" alt="8" title="8" coords="407,167,432,194" href="'.url(array('day' => '8'), 'id').'" />';
        if($now >= mktime(0,0,0,12,9,$this->year)) $r .= '<area shape="rect" alt="9" title="9" coords="506,176,529,202" href="'.url(array('day' => '9'), 'id').'" />';
        if($now >= mktime(0,0,0,12,10,$this->year)) $r .= '<area shape="rect" alt="10" title="10" coords="121,93,161,119" href="'.url(array('day' => '10'), 'id').'" />';
        if($now >= mktime(0,0,0,12,11,$this->year)) $r .= '<area shape="rect" alt="11" title="11" coords="680,397,739,433" href="'.url(array('day' => '11'), 'id').'" />';
        if($now >= mktime(0,0,0,12,12,$this->year)) $r .= '<area shape="rect" alt="12" title="12" coords="459,252,506,307" href="'.url(array('day' => '12'), 'id').'" />';
        if($now >= mktime(0,0,0,12,13,$this->year)) $r .= '<area shape="rect" alt="13" title="13" coords="581,176,628,213" href="'.url(array('day' => '13'), 'id').'" />';
        if($now >= mktime(0,0,0,12,14,$this->year)) $r .= '<area shape="rect" alt="14" title="14" coords="98,313,144,353" href="'.url(array('day' => '14'), 'id').'" />';
        if($now >= mktime(0,0,0,12,15,$this->year)) $r .= '<area shape="rect" alt="15" title="15" coords="689,207,742,253" href="'.url(array('day' => '15'), 'id').'" />';
        if($now >= mktime(0,0,0,12,16,$this->year)) $r .= '<area shape="rect" alt="16" title="16" coords="505,52,554,99" href="'.url(array('day' => '16'), 'id').'" />';
        if($now >= mktime(0,0,0,12,17,$this->year)) $r .= '<area shape="rect" alt="17" title="17" coords="598,226,655,287" href="'.url(array('day' => '17'), 'id').'" />';
        if($now >= mktime(0,0,0,12,18,$this->year)) $r .= '<area shape="rect" alt="18" title="18" coords="708,18,769,55" href="'.url(array('day' => '18'), 'id').'" />';
        if($now >= mktime(0,0,0,12,19,$this->year)) $r .= '<area shape="rect" alt="19" title="19" coords="456,6,531,52" href="'.url(array('day' => '19'), 'id').'" />';
        if($now >= mktime(0,0,0,12,20,$this->year)) $r .= '<area shape="rect" alt="20" title="20" coords="9,166,75,225" href="'.url(array('day' => '20'), 'id').'" />';
        if($now >= mktime(0,0,0,12,21,$this->year)) $r .= '<area shape="rect" alt="21" title="21" coords="344,15,391,70" href="'.url(array('day' => '21'), 'id').'" />';
        if($now >= mktime(0,0,0,12,22,$this->year)) $r .= '<area shape="rect" alt="22" title="22" coords="636,329,706,376" href="'.url(array('day' => '22'), 'id').'" />';
        if($now >= mktime(0,0,0,12,23,$this->year)) $r .= '<area shape="rect" alt="23" title="23" coords="296,377,361,437" href="'.url(array('day' => '23'), 'id').'" />';
        if($now >= mktime(0,0,0,12,24,$this->year)) $r .= '<area shape="rect" alt="24" title="24" coords="245,252,297,313" href="'.url(array('day' => '24'), 'id').'" />';
        $r .= '</map>';
        $r .= '<img src="templates/yweb/images/julkalender.jpg" usemap="#imgmap_yulkalender">';
        return $r;
    }

    function day_1() {
        $this->external('http://www.litheblas.org/kalender/index.php');
        return false;
    }

    function day_2() {
        $this->internal('N&auml;ringslivsutskottet drar i tr&aring;darna',
                        '/index.php?id=15534&w=400',
                        'N&auml;ringslivsutskottet drar i tr&aring;darna',
                        'http://fora.tv/2009/11/08/Science_Laughs_Science_Comedian_Brian_Malow#chapter_07');
    }

    function day_3() {
        $this->external('http://www.mai.liu.se/~gofor/jul09/godjul.html');
    }

    function day_4() {
        $this->internal('Kassörer och revisorer har koll på julens utgifter...',
                        array(	'/index.php?id=15542&w=600',
                                '/index.php?id=15543&w=600',
                                '/index.php?id=15544&w=600'));
    }

    function day_5() {
        $this->internal('...eller?',
                        array(	'/index.php?id=15545&w=600',
                                '/index.php?id=15546&w=600',
                                '/index.php?id=15548&w=600',
                                '/index.php?id=15549&w=600'));
    }

    function day_6() {
        $this->internal('AktU önskar alla en God Jul!',
            '/index.php?id=15551&w=600',
            false,
            'http://www.youtube.com/watch?v=9dnKKcPncQ0');
    }

    function day_7() {
        $this->internal("",
            '/index.php?id=15553&w=600',
            false,
            'http://www.youtube.com/watch?v=F3oKjPT5Khg',
            'Något ur det stora arkivet som kallas internet');
    }

    function day_8() {
        $this->internal("",
            '/index.php?id=15555&w=600',
            false,
            'http://www.weebls-stuff.com/toons/Shrooms/');
    }

    function day_9() {
        $this->internal("En stabil jul önskas er alla!",
            '/index.php?id=15558&w=600',
            false,
            false,
            false,
            "Hoppas ni får en kausal och stabil Jul så att alla era klappar förblir överraskningar och alla polare håller sig i vänster halvplan. Må Julens impulssvar ha stor utsträckning så att dess signalenergi värmer även över nyår!

Med de varmaste tillönskningar om en God Jul och Ett Gott Nytt År!
Jonas - Er lokale 'Signaler och System'-guru.");
    }

    function day_10() {
        $this->internal("God jul från SNY!",
            array(	'/index.php?id=15560&w=600',
                    '/index.php?id=15561&w=600'),false, false, false,
            "Oj! Studienämnden behöver hjälp! Vår maskot lemuren har gömt alla
våra Y-arens guide till galaxen. Hur många kan du hitta i bilden? Ett
hemligt pris lottas ut bland dem som hittar rätt antal! Mejla ditt
svar till snordf@y.lintek.liu.se. ");
    }

    function day_11() {
        $this->internal("God Yul önskar Y6!",
                '/index.php?id=15566&w=600',false,
                array('http://www.youtube.com/watch?v=epkopHo3WwM', 'http://www.youtube.com/watch?v=qVd14YHKvPw'),
                array('Ett', 'Två'),
            "Pssst.... du missar väl inte Yulbordet på söndag?
            Y6 ska göra sitt yttersta för att leverera ett Yulbord med allt det goda som hör julen till! =)
            För att komma in i rätt stämmning vill vi återuppliva dessa gamla klassiker:");
    }
    
    function day_12() {
        $this->internal("God Jul önskar Yvette!",
                '/index.php?id=15567&w=600',false,
                'http://www.youtube.com/watch?v=bX1hDJBwoPc');
    }
    
    function day_13() {
        $this->internal("God Jul och Lucia önskar StYret!",
                '/index.php?id=15568&w=600',false,
                'http://www.youtube.com/watch?v=DO3CV6cFm6Y');
    }
    
    function day_14() {
        $this->internal("Kurt önskar dig en fortsatt periodiskt återkommande jul!",
                '/index.php?id=15574&w=600',false,
                'http://www.todaysbigthing.com/2009/12/08','Dagens videolänk, som egentligen inte har med något alls här att göra...',
            "- Cauchy allsin dar, sitter ni här och Fourier kaffe?
- Ja, du får Laplace du mä.
- Nä tack, jag Dirac just en kopp.");
    }
    
    function day_15() {
        $this->setContent('main', /*'<h1>God Jul &ouml;nskar K&aring;rrullen</h1>'.*/
        '<div class="images"><img src="/index.php?id=15571&w=600" alt="God Jul &ouml;nskar K&aring;rrullen" /></div>
<div class="images"><h2>I v&auml;ntan p&aring; v&aring;rens filmer bjuder <a href="http://www.karrullen.nu">K&aring;rrullen</a> p&aring; julpyssel. Fyll i filmtitlarna i krysset.<br />
Skicka era svar till ordforande@karrullen.nu f&ouml;r en chans att vinna terminskort till v&aring;rens filmer.</h2></div>
<div class="images"><img src="/index.php?id=15572&w=600" alt="Filmbilder" /><br />
<img src="/index.php?id=15573&w=600" alt="kryss" /></div>
<div class="youtube">V&aring;rterminen b&ouml;rjar den 18 januari med <a href="http://www.youtube.com/watch?v=M-cIjPOJdFM">Zombieland</a></div>');
Head::add('h1 {font-size: 3em; color: #000;} div {text-align: center;} .images {position: relative; left: 600px; margin: 0 0 0 -525px; width: 600px;} .youtube {font-size: 2em;} .wrapper {border: 10px solid red;}', 'css-raw');
    }
    
    function day_16() {
        $this->internal("YF önskar er en mastodontlik jul!",
                '/index.php?id=15577&w=600',false,
                'ftp://ysektionen:julen@ingalunda.myftp.org/YFJul.mpg','årets julklapp',
                "YF önskar er alla en riktigt mastodontlik jul och vill samtidigt tipsa om ...");
    }
    
    function day_17() {
        $this->external('http://www.mai.liu.se/~thkar/kurser/julkalender092.html');
    }
    
    function day_18() {
        $this->internal('<img src="/15582?w=600" />',
                '/15580?w=600','Lösningen finns på nattredax sida på hemsidan!',
                false,false,
                "God jul önskar Nattredax!");
    }
    
    function day_19() {
        $this->internal('Kommendören hälsar God Jul!',
                '/15583?w=600','Mer information finns på hemsidan!',
                'http://www.youtube.com/watch?v=Nmy6OaZEAhE',false,
                false);
    }
    
    function day_20() {
        $this->internal(false,
                '/15584?w=600',false,
                '/15586','Förra årets julklappstest duger även i år! Spana in vilken klapp som är bäst för dig!',
                'God Jul på dig!');
    }
    function day_21() {
        $this->internal('brb',
                false,false,
                false,false,
                'På grund av  oförutsedda olyckor är luckan något försenad. Kolla in lite senare!');
    }
    function day_22() {
        $this->internal('God Jul önskar Mr. President',
                '<object id="A116859" quality="high" data="http://aka.zero.jibjab.com/client/zero/ClientZero_EmbedViewer.swf?external_make_id=AWdFSFMNT1cp09Zk&service=elfyourself.jibjab.com&partnerID=ElfYourself" pluginspage="http://www.macromedia.com/go/getflashplayer" type="application/x-shockwave-flash" wmode="transparent" height="319" width="425"><param name="wmode" value="transparent"></param><param name="movie" value="http://aka.zero.jibjab.com/client/zero/ClientZero_EmbedViewer.swf?external_make_id=AWdFSFMNT1cp09Zk&service=elfyourself.jibjab.com&partnerID=ElfYourself"></param><param name="scaleMode" value="showAll"></param><param name="quality" value="high"></param><param name="allowNetworking" value="all"></param><param name="allowFullScreen" value="true" /><param name="FlashVars" value="external_make_id=AWdFSFMNT1cp09Zk&service=elfyourself.jibjab.com&partnerID=ElfYourself"></param><param name="allowScriptAccess" value="always"></param></object>',false,
                false,false,
                'Jag hoppas ni alla får en bra jul så ses vi säkert någon gång nästa decennium!
                
                <b>God Jul</b> önskar Mr. President');
    }
    
    function day_23() {
        $this->internal("Pubgruppen önskar också en <u>God</u> Jul!",
                '/15588?w=600',false, 
                false, false,
                false);
    }
    
    function day_24() {}

    function external($where) {
        redirect($where);
    }

    function internal($title, $image, $alt='', $youtube=false, $youtubetext = false, $comment=false) {
        if(!$youtubetext) $youtubetext = 'Dagens videolänk';
        if(is_array($image))
        {
            JS::loadjQuery();
            JS::lib('jquery/jquery.cycle.all.2.72');
            JS::raw("$('.images').cycle({fx:'fade'});");
        } else $image = array($image);
        Head::add('h1 {font-size: 3em; color: #000;} div {text-align: center;} .images {position: relative; left: 600px; margin: 0 0 0 -525px; width: 600px;} .youtube {font-size: 2em;} .wrapper {border: 10px solid red;}', 'css-raw');
        $r = '';
        if($title) $r .= '<h1>'.$title.'</h1>';
        $r .= '<div class="images">';
        foreach($image as $img) {
            if($img[0] == '<') $r .= $img;
            else $r .= '<img src="'.$img.'" alt="'.$alt.'" title="'.$alt.'" />';	
        }
        $r.='</div>';
        if($comment) $r .= '<div class="comment">'.nl2br($comment).'</div>';
        if($youtube) {
            $youtube = (array)$youtube;
            $youtubetext = (array)$youtubetext;
            foreach($youtube as $i => $yt)
                if(isset($youtubetext[$i]))
                    $r .= '<div class="youtube"><a href="'.$yt.'" target="_blank">'.$youtubetext[$i].'</a></div>';
        }
        $this->setContent('main', $r);
    }

}
