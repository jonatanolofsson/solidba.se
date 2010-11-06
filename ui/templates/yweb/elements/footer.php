<div id="footer">
<div class="cols">
        <div class="col four first">
            <div class="padded">
                <h3>Spr&aring;k / Language</h3>
                <?php new Box('selectLanguage') ?>
            </div>
        </div>
        <div class="col three">
            <p><b>Besöksadress</b></p>
            <p>Y-sektionen<br />
            Cybercom, Kårallen<br />
            581 82 Linköping</p>
        </div>
        <div class="col three">
            <p><b>Telefon</b></p>
            <p>013-123956</p>
        </div>
        <div class="col four right">
            <ul>
                <li>&copy; 2008-<?=date('Y')?> Link&ouml;pings Y-teknologsektion</li>
                <li><a href="mailto:<?=$Controller->{(string)ADMIN_GROUP}(OVERRIDE)->getEmail() ?>">Webmaster</a></li>
            </ul>
            <?php new Box('toolbox'); ?>
        </div>
    </div>
    <?php if($USER->memberOf(ADMIN_GROUP) || $_REQUEST->raw('force_dump') >= 1) new footer(); ?>
</div>
