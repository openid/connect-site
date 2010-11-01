<?php
require_once('../lib/markdown.php');

// http://no-www.org/
if ($_SERVER['HTTP_HOST'] != 'openidconnect.com') {
  header("Location: http://openidconnect.com/", false, "301");
  exit;
}

// this is a giant hack...don't like it then install a CMS
$pages = array(
  'About',
  'FAQ'
);
$current_page = 'index';
$markdown = @file_get_contents('../pages/index.markdown');

foreach ($pages as $page) {
  if (stristr($_SERVER['REQUEST_URI'], $page)) {
    $markdown = @file_get_contents("../pages/$page.markdown");
    $current_page = $page;
    break;
  }
}

if (!$markdown) {
  header("HTTP/1.1 500 Internal Server Error");
  exit;
}

$html = Markdown($markdown);

?>
<!DOCTYPE html>
<html>
<head>
  <script type="text/javascript">var _sf_startpt=(new Date()).getTime()</script>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
  <title>OpenID Connect</title>
  <link rel="stylesheet" href="/static/base.css" type="text/css"/>
  <meta property="og:title" content="OpenID Connect" />
  <meta property="og:type" content="website" />
  <meta property="og:url" content="http://openidconnect.com/" />
  <meta property="og:image" content="http://openidconnect.com/static/openid_connect.png" />
  <meta property="fb:admins" content="24400320" />
</head>
<body>
  <div id="body">
    <div id="header">
      <a href="/"><img src="/static/openid_connect.png"  /></a>
    </div>
    <div id="nav" class="column span-18 append-1 prepend-1">
      <ul class="navigation">
<? // hate using PHP this way
foreach ($pages as $page) {
  if ($page == $current_page) {
    $class = ' class="selected"';
  } else {
    $class = '';
  }
  echo "        <li><a href='/" . strtolower($page) . "/'$class>$page</a></li>\n";
}
?>
      </ul>
    </div>
    <div id="content">
      <br />
      <?= $html ?>
    </div>
    <div id="footer">
      <hr />
      <a href="http://github.com/openid">GitHub</a> |
      <a href="http://lists.openid.net/mailman/listinfo/openid-specs-connect">Mailing List</a> |
      <a href="http://openid.net/foundation/">OpenID Foundation</a> © 2010
    </div>
    <script type="text/javascript">
    var _sf_async_config={uid:1415,domain:"openidconnect.com"};
    (function(){
      function loadChartbeat() {
        window._sf_endpt=(new Date()).getTime();
        var e = document.createElement('script');
        e.setAttribute('language', 'javascript');
        e.setAttribute('type', 'text/javascript');
        e.setAttribute('src',
        (("https:" == document.location.protocol) ? "https://s3.amazonaws.com/" : "http://") +
        "static.chartbeat.com/js/chartbeat.js");
        document.body.appendChild(e);
      }
      var oldonload = window.onload;
      window.onload = (typeof window.onload != 'function') ?
      loadChartbeat : function() { oldonload(); loadChartbeat(); };
      })();

      </script>
  </body>
</html>
