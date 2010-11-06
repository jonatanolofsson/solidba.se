<?php
/**
 * @author Kalle Karlsson [kakar]
 * @version 1.0
 * @package Templates
 *
 *
 * Yweb Template - 1 column
 */
?>
<?='<?xml version="1.0" encoding="utf-8"?>' ?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="sv" lang="sv">

<head>
    <title><?=$SITE->Name?></title>
    <meta http-equiv="content-type" content="text/html;charset=utf-8" />
    <meta http-equiv="Content-Style-Type" content="text/css" />
    <meta name="description" content="<?=$PAGE->description?>" />
    <link rel="shortcut icon" href="<?php echo $this->webdir; ?>images/favicon.ico" />
    <link href="<?php echo $this->webdir; ?>style.css" type="text/css" rel="stylesheet" />
</head>

<body class="dark">
<div id="wrapper">
    <?php include('elements/header.php'); ?>

    <div id="content">
    <!-- begin content -->

        <div class="cols">
            <div class="col four first">
                <?php new Section('menu'); ?>
            </div>
            <div class="col twelve">
                <!-- begin subcontent -->
                <?php new Section('main'); ?>
                <!-- end subcontent -->
            </div>
        </div>

    <!-- end content -->
    </div>
    <?php include('elements/footer.php'); ?>
</div>
<?php Flash::display(); ?>
</body>
</html>
<?php
    Head::add('templates/yweb/js/subnav.js','js-url');
?>
