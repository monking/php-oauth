<!DOCTYPE html>

<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="HandheldFriendly" content="true" />
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
  <title>Authorization</title>
  <script src="js/askAuthorization.js" type="text/javascript"></script>
  <link rel="stylesheet" type="text/css" href="css/default.css">
</head>

<body>
  <div id="wrapper">
      <div id="container">
        <form method="post" action="">
          <h1><?php echo $config->getValue('serviceName'); ?></h1>

          <p><strong><?php echo $result->getClient()->getName(); ?></strong> wants to
          access your
          <strong><?php echo $config->getValue('serviceResources'); ?></strong>.</p>

          <table id="detailsTable">
            <tr>
              <th>Application Identifier</th>

              <td><?php echo $result->getClient()->getId(); ?></td>
            </tr>

            <tr>
              <th>Description</th>

              <td><span><?php echo $result->getClient()->getDescription(); ?></span></td>
            </tr>

            <tr>
              <th>Requested Permission(s)</th>

              <?php if(0 === count($result->getScope()->getScopeAsArray())) { ?>
              <td><em>None</em></td>
              <?php } else { ?>
              <td>
                <?php if($config->getValue('allowResourceOwnerScopeFiltering')) { ?><?php foreach($result->getScope()->getScopeAsArray() as $s) { ?><label><input type="checkbox"
                checked="checked" name="scope[]" value=
                "<?php echo $s; ?>"> <?php echo $s; ?></label>
                <?php } ?> <?php if($config->getValue('allowResourceOwnerScopeFiltering')) { ?>

                <div class="warnBox">
                  By removing permissions, the application may not work
                  as expected!
                </div><?php } ?><?php } else { ?>

                <ul>
                  <?php foreach($result->getScope()->getScopeAsArray() as $s) { ?>

                  <li><?php echo $s; ?></li>

                  <li style="list-style: none"><input type="hidden"
                  name="scope[]" value="<?php echo $s; ?>">
                  <?php } ?></li>
                </ul><?php } ?>
              </td>
              <?php } ?>

            </tr>

            <tr>
              <th>Redirect URI</th>

              <td><?php echo $result->getClient()->getRedirectUri(); ?></td>
            </tr>
          </table><button id="showDetails" type=
          "button">Details</button> <input type="submit" class=
          "formButton" name="approval" value="Approve"> <input type=
          "submit" class="formButton" name="approval" value="Reject">
        </form>
      </div><!-- /container -->
  </div><!-- /wrapper -->
</body>
</html>
