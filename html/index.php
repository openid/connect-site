<?php
require_once('../lib/markdown.php');

if ($_SERVER['HTTP_HOST'] != 'openidconnect.com') {
	header("Location: http://openidconnect.com/", false, "301");
	exit;
}

$markdown = @file_get_contents('../pages/index.markdown');

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
		<link rel="stylesheet" href="base.css" type="text/css"/>
		<meta property="og:title" content="OpenID Connect" />
		<meta property="og:type" content="website" />
		<meta property="og:url" content="http://openidconnect.com/" />
    <meta property="og:image" content="http://openidconnect.com/static/openid_connect.png" />
    <meta property="fb:admins" content="24400320" />
  </head>
	<body>
		<div id="body">
		<div id="header">
			<img src="static/openid_connect.png"  />
			<h2 style="margin-top:2em; text-align: left;">A strawman...</h2>
		</div>
		<div id="content">
			<?= $html ?>
		</div>
		<div id="footer">
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
