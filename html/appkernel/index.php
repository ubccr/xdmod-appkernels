<?php

require_once('../../configuration/linker.php');

// Set REST cookies.
\xd_rest\setCookies();

?>

<html>
<head>  
  <title>App Kernel Explorer</title>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <link href="/gui/lib/extjs/resources/css/ext-all.css" type="text/css" rel="stylesheet">
  <script type="text/javascript" src="/gui/lib/extjs/adapter/ext/ext-base.js"></script>
  <script type="text/javascript" src="/gui/lib/extjs/ext-all.js"></script>
  <script type="text/javascript" src="/gui/js/StringExtensions.js"></script>
  <script type="text/javascript" src="/gui/js/CCR.js"></script>
  <script language="javascript" src="/gui/js/RESTProxy.js"></script>
  <script language="javascript" src="/gui/js/RESTTree.js"></script>
  <script type="text/javascript">
     <?php \xd_rest\printJavascriptVariables(); ?>
  </script>
  <script type="text/javascript" src="/gui/js/REST.js"></script>
  <script language="javascript" src="js/akexplorer.js"></script>
<!-- Styles used by the app kernel explorer that might override extjs defaults -->
<style>
.refresh {
    background-image: url(../gui/images/refresh.png) !important;
}

.x-tree-node .status-failure a span { color: red; }
.x-tree-node .status-error a span { color: chocolate; }
.x-tree-node .status-queued a span { color: gold; }
</style>
</head>
<body>
<div id="akexplorer"></div>
</body>
</html>
