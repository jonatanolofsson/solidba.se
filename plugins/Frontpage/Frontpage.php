<?php
class Frontpage extends Page {

    function __construct($id, $language=false) {
        parent::__construct($id, $language);
        /*global $SITE;
        $this->title = $SITE->name;*/
    }

    function run() {
        global $Templates, $CONFIG, $DB, $Controller;

        $_REQUEST->setType('flash','any');
        if($_REQUEST->valid('flash')){
            Flash::create($_REQUEST['flash'].'_flash_1',$_REQUEST['flash']);
/* 			Flash::create($_REQUEST['flash'].'_flash_2',$_REQUEST['flash']); */
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

        /* Retrive news objects */
        if($newsNum > 0) {
            $newsObj = Flow::retrieve('News', $newsNum, false, false, false, 0, true);
            $n = count($newsObj);
            if($n<$newsNum) {
                $newsObj = array_merge(
                    $newsObj,
                    Flow::retrieve('News', $newsNum-$n, false, false, false, 0, false)
                );
            }
        }

        /* <<< New flowing design >>> */
        foreach($newsObj as $news){
            $content .= $news->display('new');
        }

        // Test box
        $sideBox = Design::column('test',4,true);

        $r = Design::row(array(Design::column($content, 10, true), Design::column(Companies::viewAds(), 4, false, false, false, false, 'right')));
        $r .= '<a href="/flowView?q=News">'.__('View all news').'</a>';
        JS::loadjQuery(false);
/* 		JS::lib('jquery/jquery.qtip-1.0.0-rc3'); */
        if($this->mayI(EDIT)) {
            //FIXME: Make into jQuery extension
            JS::raw('
            $(document).ready(function() {
                $(".articleInfo .edit a").each(function () {
                    // options
                    var time = 250;
                    var hideDelay = 500;

                    var hideDelayTimer = null;

                    // tracker
                    var beingShown = false;
                    var shown = false;

                    // target data
                    var id = $(this).attr("id");
                    var pos = $(this).offset();
                    var offset = $(this).closest(".col").offset();
                    var target_width = $(this).width();

                    if($(this).hasClass("locked")){
                        var lock = \' style="display:none"\';
                        var unlock =\'\';
                    } else {
                        var lock = \'\';
                        var unlock =\' style="display:none"\';
                    }

                    var content = \'<ul><li class="edit"><a href="eventAdmin?edit=\' + id + \'&view=content&lang=sv">'.icon('large/edit-32').'<span class="desc">Edit</span></a></li><li class="lock"\'+lock+\'><a href="javascript:;" onclick="lockPosition(\' + id + \');">'.icon('large/encrypted-32').'<span class="desc">'.__('Lock position').'</span></a></li><li class="unlock"\'+unlock+\'><a href="javascript:;" onclick="lockPosition(\' + id + \');">'.icon('large/decrypted-32').'<span class="desc">'.__('Unlock position').'</span></a></li></ul>\';

                    $("#content").after(\'<div id="toolbox-\' + id + \'" class="toolbox"><div class="toolbox-body"><div class="toolbox-content">\' + content + \'</div></div><div class="toolbox-arrow"></div></div>\');

                    $("#toolbox-"+id).css("display", "block");

                    var content_width = $("#toolbox-"+id).find(".toolbox-content").width();
                    var content_padding = $("#toolbox-"+id).find(".toolbox-content").css

                    $("#toolbox-"+id).css({
                        "top": "-100px",
                        "left": (pos.left-offset.left+Math.round(target_width/2)-Math.round(content_width/2)+5),
                        "opacity": 0
                    });
                    $("#toolbox-"+id).children(".toolbox-arrow").css({"left":Math.round(content_width/2)+5});

                    var trigger = $(this);
                    var toolbox = $("#toolbox-"+id).children(".toolbox-body");

                    $([toolbox.get(0), trigger.get(0)]).mouseover(function() {
                        if(hideDelayTimer) clearTimeout(hideDelayTimer);

                        if(beingShown || shown) {
                            return;
                        } else {
                            beingShown = true;

                            $("#toolbox-"+id).css({
                                display: "block",
                                top: $(this).offset().top-4
                            })
                            .animate({
                                opacity: 1
                            }, time, "linear", function() {
                                beingShown = false;
                                shown = true;
                            });
                        }
                    }).mouseout(function () {
                        if (hideDelayTimer) clearTimeout(hideDelayTimer);

                        hideDelayTimer = setTimeout(function () {
                            hideDelayTimer = null;
                            $("#toolbox-"+id).animate({
                                opacity: 0
                            }, time, "linear", function () {
                                shown = false;
                                $("#toolbox-"+id).css("display", "none");
                            });
                        }, hideDelay);
                    });
                });
            });

            function lockPosition(id){
                $.post("/"+id,{ action: "lockpos" },
                    function(data){
                        $("#toolbox-"+id+" .lock").toggle();
                        $("#toolbox-"+id+" .unlock").toggle();
                        alert(data);
                    }
                );
            }');

/*
            JS::raw('$(".articleInfo .edit a").hover(
                        function() {
                            var id = $(this).attr("id");
                            var pos = $(this).offset();
                            var offset = $(this).closest(".col").offset();
                            var target_width = $(this).width();
                            $("#content").after(\'<div id="toolbox-\'+id+\'" class="toolbox"><div class="toolbox-body"><div class="toolbox-content"><ul><li><a href="'.$this->_edit_link.'">'.icon('large/edit-32','Edit').'<span class="desc">Edit</span></a></li><li><a href="">'.icon('large/encrypted-32','Lock position').'<span class="desc">Lock position</span></a></li></ul></div></div><div class="toolbox-arrow"></div></div>\');
                            var content_width = $("#toolbox-"+id).children(".toolbox-body").children(".toolbox-content").width();
                            $("#toolbox-"+id).css("top",pos.top+"px")
                            .css("left",(pos.left-offset.left+Math.round(target_width/2)-Math.round(content_width/2)+10)+"px")
                            .children(".toolbox-arrow").css("left",(content_width/2)+"px");
                        },function() {
                            var id = $(this).attr("id");
                            $("#toolbox-"+id).remove();
                        });
                    ');
*/
                }

/*
        JS::raw('$(function(){
                    $(".author a").hover(
                        function(){
                            var pos = $(this).offset();
                            var offset = $(this).closest(".col").offset();
                            $("#content").after(\'<div class="toolbox" style="top:\'+(pos.top)+\'px; left:\'+(pos.left-offset.left+10)+\'px;"><div class="toolbox-body"><div class="toolbox-content">Lorem Ipsum Dolore Sit Amet</div></div><div class="toolbox-arrow" style="left:10px;"></div></div>\');
                        },
                        function(){
                            $("body").find(".toolbox").remove();
                        });
                    });
                ');
*/
/* 							$("#content").after(\'<div class="popbox down authorInfo" style="top:\'+(pos.top+10)+\'px; left:\'+(pos.left-offset.left+20)+\'px;"><div class="loader"><img src="templates/yweb/images/spinner_dark.gif" /></div></div>\'); */


        $this->setContent('main', $r);
        $Templates->render();
    }
}
?>
