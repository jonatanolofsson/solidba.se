<?PHP
class MailTools {
    function template($content) {
        return '
        <html>
        <body>
            <table width="98%" background="http://www.ysektionen.se/templates/yweb/images/bg_dark_mail.png" style="background-repeat: repeat-x"><tr><td>
                <table cellpadding="0" cellspacing="0" border="0" width="100%">
                    <tr><td colspan="3">&nbsp;</td></tr>
                    <tr>
                        <td colspan="3" height="85px" align="left" valign="top"><a href="http://www.ysektionen.se"><img src="http://www.ysektionen.se/templates/yweb/images/logo_banner_dark.png" height="85" width="600" alt="Teknisk fysik och elektroteknik Link&ouml;ping" border="0" /></a></td>
                    </tr>
                    <tr><td colspan="3">&nbsp;</td></tr>
                    <tr>
                        <td width="10%">&nbsp;</td>
                        <td style="font-size: 9pt; color:#323232;">
                            '.$content.'
                        </td>
                        <td width="20%">&nbsp;</td>
                    </tr>
                    <tr>
                        <td colspan="3">
                            <hr size="1" color="#ddd" />
                        </td>
                    </tr>
                    <tr>
                        <td colspan="3" style="font-size: 0.7em; color: #aaa;" align="center">
                            Du f&aring;r detta mail f&ouml;r att du &auml;r medlem i Link&ouml;pings Y-teknologsektion. Har du n&aring;gra fr&aring;gor, kontakta oss via <a href="http://www.ysektionen.se">hemsidan</a> eller p&aring; <a href="mailto:webmaster@y.lintek.liu.se">webmaster@y.lintek.liu.se</a>
                        </td>
                    </tr>
                </table>
            </td></tr></table>
        </body>
        </html>';
    }

    function headers($from, $title) {
        $hdrs['From'] = $from;
        $hdrs['Subject'] = $title;

        return $hdrs;
    }
}
?>
