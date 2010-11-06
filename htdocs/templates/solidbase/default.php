<?php 
/**
 * @package Templates
 * @license http://creativecommons.org/licenses/by-nc/3.0/ Creative Commons Attribution-Noncommercial 3.0 Unported License
 */
?>
<?='<?xml version="1.0" encoding="utf-8"?>'?>
<?php 
googleLoad('jquery', '1');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

    <head>
        <title>solidba.se</title>
        <meta http-equiv="content-type"	content="text/html;charset=utf-8" />
        <meta http-equiv="Content-Style-Type" content="text/css" />
        <link href="templates/solidbase/style.css" type="text/css" rel="STYLESHEET" />
    </head>
    <body>
        <div class="header"><h1><a href="<?=$SITE->URL?>"><?=$SITE->Name?></a></h1></div>
        <div class="menudiv">
            <?php 
                new Menu('main_menu');
            ?>
        </div>
        <div class="body">
            <div class="left">
                <div class="box">
                    <?php 
                        new Section('left');
                    ?>
                </div>
            </div>
            <div class="right">
                <div class="image">
                    <?php 
                        new Section('image');
                    ?>
                </div>
                <div class="box">
                    <?php 
                        new Section('right');
                    ?>
                </div>
            </div>
        </div>
        <div id="footer">
            &copy;2007-<?=date('Y')?> | Design by Hanna Nyqvist and Jonatan Olofsson | <a href="?id=admin_area"><?=_('Administration area')?></a><?php new Box('toolbox');?>
            <?php 
                if($USER->ID === NOBODY) Head::add('/templates/solidbase/js/loginbox.js','js-url');
                new Box('login');
                new footer();
            ?>
        </div>
    </body>
</html>