<!DOCTYPE html>

<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="HandheldFriendly" content="true" />
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
  <title>Error <?php echo $code; ?> - <?php echo $reason; ?></title>
  <link rel="stylesheet" type="text/css" href="css/default.css">
</head>

<body>
  <div id="wrapper">
      <div id="container">
        <h3>Error [<?php echo $code; ?> - <?php echo $reason; ?>]</h3>

        <p>A fatal error occurred!</p>
        <div class="errorBox">
          <?php echo $error; ?>
        </div>
      </div><!-- /container -->
  </div><!-- /wrapper -->
</body>
</html>
