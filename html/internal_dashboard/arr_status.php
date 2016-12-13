<?php

require_once 'user_check.php';

?>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title>XDMoD Internal Dashboard</title>
  <link rel="stylesheet" type="text/css" href="css/management.css">
  <link rel="stylesheet" type="text/css" href="css/AdminPanel.css" />

  <?php ExtJS::loadSupportScripts('../gui/lib'); ?>

  <!-- Non-GUI JS Class Definitions -->
  <script type="text/javascript" src="../js_classes/DateUtilities.js"></script>
  <script type="text/javascript" src="../js_classes/StringUtilities.js"></script>

  <script type="text/javascript" src="js/DashboardStore.js"></script>

  <script type="text/javascript" src="../gui/lib/MessageWindow.js"></script>
  <script type="text/javascript" src="../gui/js/CCR.js"></script>
  <script type="text/javascript" src="../gui/js/RESTProxy.js"></script>
  <script type="text/javascript" src="../gui/js/LoginPrompt.js"></script>
  <script type="text/javascript" src="../gui/js/ContainerMask.js"></script>
  <script type="text/javascript" src="../gui/js/TGUserDropDown.js"></script>
  <script type="text/javascript" src="../gui/lib/CheckColumn.js"></script>
  <script type="text/javascript" src="../gui/js/BufferView.js"></script>

  <!-- Admin Panel -->
  <script type="text/javascript" src="js/admin_panel/RoleGrid.js"></script>
  <script type="text/javascript" src="js/admin_panel/SectionNewUser.js"></script>
  <script type="text/javascript" src="js/admin_panel/SectionExistingUsers.js"></script>
  <script type="text/javascript" src="js/admin_panel/AdminPanel.js"></script>

  <script type="text/javascript" src="js/CommentEditor.js"></script>
  <script type="text/javascript" src="js/AccountRequests.js"></script>

  <script type="text/javascript" src="js/RecipientVerificationPrompt.js"></script>
  <script type="text/javascript" src="js/BatchMailClient.js"></script>
  <script type="text/javascript" src="js/CurrentUsers.js"></script>

  <script type="text/javascript" src="js/ExceptionLister.js"></script>
  <script type="text/javascript" src="js/IngestionLogClient.js"></script>

  <script type="text/javascript">
    var dashboard_user_full_name = '<?php print $user->getFormalName(); ?>';
  </script>

  <script type="text/javascript" src="js/ArrStatus/SummaryStore.js"></script>
  <script type="text/javascript" src="js/ArrStatus/ActiveTasksStore.js"></script>
  <script type="text/javascript" src="js/ArrStatus/ActiveTasksGrid.js"></script>
  <script type="text/javascript" src="js/ArrStatus/Viewport.js"></script>
  <script type="text/javascript" src="js/arr_status.js"></script>
</head>
<body></body>
</html>
