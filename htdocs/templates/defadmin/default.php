<?php 
/**
 * @package Templates
 * @license http://creativecommons.org/licenses/by-nc/3.0/ Creative Commons Attribution-Noncommercial 3.0 Unported License
 */
?>
<?='<?xml version="1.0" encoding="utf-8"?>' ?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<head>
    <title><?=$PAGE->Name ?></title>
    <meta http-equiv="content-type"
        content="text/html;charset=utf-8" />
    <meta http-equiv="Content-Style-Type" content="text/css" />
    <meta name="description" content="<?=$PAGE->description ?>" />
    <link href="templates/defadmin/style.css" type="text/css" rel="STYLESHEET" />
</head>

<body>
<div id="container">
    <div id="header"><h1><a href="<?=$SITE->URL ?>"><?=$SITE->Name?></a></h1></div>
    <div id="leftBar">
        <div id="menus">
            <?PHP
                new Box('adminMenu');
                new Menu('main_menu');
            ?>
        </div>
        <?php
        new Box('login');
        ?>
        <center>
            <a rel="license" href="http://creativecommons.org/licenses/by-nc/3.0/"><img alt="Creative Commons License" style="border-width:0" src="http://i.creativecommons.org/l/by-nc/3.0/80x15.png" /></a>
        </center>
    </div>
    <?PHP Flash::display(); ?>
    <div id="content"><h1>
        <?php
            new Section('header');
        ?></h1>
        <?php
            new Section('main');
        ?>
    </div>
    <div id="footer">
        <?php
            new footer();
        ?>
    </div>
</div>
</body>
</html>