<!DOCTYPE html>

<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="HandheldFriendly" content="true" />
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
  <title>Error [<?php echo $response->getStatusCode(); ?> - <?php echo $response->getStatusReason(); ?>]</title>
  <link rel="stylesheet" type="text/css" href="css/default.css">
</head>

<body>
  <div id="wrapper">
      <div id="container">
        <h3>Error [<?php echo $response->getStatusCode(); ?> - <?php echo $response->getStatusReason(); ?>]</h3>

        <p>A fatal error occurred!</p>
        <div class="errorBox">
          <?php echo $e->getMessage(); ?>
        </div>
      </div><!-- /container -->
  </div><!-- /wrapper -->
</body>
</html>
