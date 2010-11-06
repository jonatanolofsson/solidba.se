<div class="header">
    <?php /* new Box('login'); */ ?>
    <div id="breadcrumb">
        <span>Du &auml;r h&auml;r:</span>
        <?php new Breadcrumbs(); ?>
    </div>
    <!-- NEW LOGINBOX -->
    <?php /* new Box('newLogin'); */ ?>

    <div id="banner"><a href="<?=$SITE->URL ?>"><img src="/templates/yweb/images/logo_banner_dark.png" alt="<?=$SITE->Name?>" /></a></div>
    <div id="globalsearch">
            <form id="gs-form" action="<?=url(array('id' => 'search'), false, false, false, true); ?>" method="get">
                <fieldset>
                    <label for="gs-searchtext"></label>
                    <input name="q" type="text" id="gs-searchtext" title="<?=_('Search')?>" />
                </fieldset>
            </form>
            <div id="gs-action"></div>
            <div id="gs-results">
                <div id="inside"></div>
            </div>
        </div>

    <div class="topnav">
    <?php
        new Menu('main_menu', 1, false, false, false, true);
        new Box('newLogin');
/* 		if($PAGE->mayI(EDIT)) echo '<div class="admin"><a href="'.url(array('id' => 'admin_area')).'">Admin<span>av sidan</span></a></div>';  */
    ?>
    </div>
    <div class="login_test">test</div>

</div>

<?php 
	new Box('feedback'); 
/* $Controller->newObj('Report')->setAlias('Report'); */
?>

<?php
    JS::loadjQuery(false);
    JS::lib('jquery/jquery.keynav.1.1');
    JS::lib('globalsearch');
/* 	JS::lib('jquery/jquery.backgroundPosition'); */
    JS::lib('jquery/jquery.lavalamp-1.3.4b2');
    JS::lib('jquery/jquery.easing.1.3');

/* 	Head::add('$(document).ready(function() { $("#username").focus();});','js-raw'); */

    /*
    JS::raw('
        $(".topnav li a, .topnav div.admin a")
            .css({backgroundPosition:"0 -50px"})
            .mouseover(function() {
                $(this).stop(true)
                    .animate({backgroundPosition:"0 0"},"fast")
                })
            .mouseout(function() {
                $(this).stop(true)
                    .animate({backgroundPosition:"0 -50px"},"fast")
                });
        ');
*/
    JS::raw('$(function(){ $(".topnav ul.menu").lavaLamp({speed:300}); });');


?>
