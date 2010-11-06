function IELoader()
{
    document.writeln('<object classid="clsid:8AD9C840-044E-11D1-B3E9-00805F499D93"');
    document.writeln('      width= "290" height= "290"');
    document.writeln('      codebase="http://java.sun.com/update/1.5.0/jinstall-1_5-windows-i586.cab#version=1,4,1">');
    document.writeln('<param name="archive" value="dndlite.jar">');
    document.writeln('<param name="code" value="com.radinks.dnd.DNDAppletLite">');
    document.writeln('<param name="name" value="Rad Upload Lite">');
}