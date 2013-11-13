<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
xmlns:content="http://purl.org/rss/1.0/modules/content/"
xmlns="http://www.w3.org/1999/xhtml">

<xsl:output method="html"/>

<xsl:template match="/">
<html>
  <head>
    <title>{{tpl:lang Subscribe to}} <xsl:value-of select="/rss/channel/title"/></title>
    <style type="text/css">
    <![CDATA[
    body {
      font: 80% Verdana,Arial,sans-serif;
      margin: 20px 0;
      padding: 0 0;
      background: #fff;
      color: #000;
    }
    a { color: #039; }
    h1, h2 { font-family: Arial,sans-serif; }
    h1 { font-size: 160%; margin: 0; }
    h2 { font-size: 140%; font-weight: bold; margin: 0.5em 0 0.2em 0; }
    h2 a { text-decoration: none; }
    p { margin: 0 0 0.5em 0; }
    #page {
      margin: 0 80px;
    }
    #top {
      background: #036;
      padding : 1em;
      color: #fff;
    }
    #top a {
      color: #fff;
      text-decoration: none;
    }
    #what {
      padding: 1em;
      background: #eee;
      border-bottom: 1px solid #000;
      font-size: 80%;
    }
    #what p {
      margin: 0 0 0.5em 0;
    }
    #footer {
      border-top: 1px solid #000;
    }
    #items {
      background: #fff;
      color: inherit;
    }
    #items div {
      margin: 0 0 1em 0;
    }
    ]]>
    </style>
    <script type="text/javascript"><![CDATA[
    window.onload = function() {
      document.getElementById('feedurl').value = window.location.href;
      
      // Ugly but works ;)
      var c = document.getElementsByTagName('div');
      var t = '';
      for (var i=0; c.length-i != 0; i++) {
        if (c[i].className == 'item-content') {
          if (c[i].textContent) {
            t = c[i].textContent;
          } else if (c[i].innerText) {
            t = c[i].innerText;
          } else {
            t = '';
          }
          
          if (t) { c[i].innerHTML = t; }
        }
      }
    };
    ]]></script>
  </head>
  <body>
    <div id="page">
      <div id="top">
        <h1><a href="{/rss/channel/link}"><xsl:value-of select="/rss/channel/title"/></a></h1>
        <p><xsl:value-of select="/rss/channel/description"/></p>
      </div>
      <div id="what">
      <h3>{{tpl:lang What is an RSS feed?}}</h3>
      <p>{{tpl:lang RSS feed is a free blog summary. It provides content
      (either posts or comments) or  summaries of content, together with links
      to the full versions, and other metadata. The last published items may
      then be read by your favorite RSS
      <a href="http://en.wikipedia.org/wiki/Aggregator">aggregator</a>.}}</p>
      <h3>{{tpl:lang Subscribe}}</h3>
      <p>{{tpl:lang Simply copy the following URL into your aggregator:}}</p>
      <p><input type="text" size="60" value="" id="feedurl" /></p>
      </div>
      <div id="items">
        <xsl:apply-templates select="//item"/>
      </div>
      <div id="footer">
        <p><xsl:value-of select="/rss/channel/copyright"/></p>
      </div>
    </div>
  </body>
</html>
</xsl:template>

<!-- Item template -->
<xsl:template match="item">
  <div>
    <h2><a href="{link}"><xsl:value-of select="title"/></a></h2>
    <div class="item-content"><xsl:value-of select="description" /></div>
  </div>
</xsl:template>

</xsl:stylesheet>