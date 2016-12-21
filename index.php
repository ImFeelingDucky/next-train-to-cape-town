<!DOCTYPE html>
<html lang="en">

  <head>
    <title>Train Times</title>
    <link rel="stylesheet" href="css/style.css" type="text/css" />
    <link rel="stylesheet" href="awesomplete/awesomplete.css" type="text/css" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
  </head>

  <body>
    <div class="mega-container search-screen screen">
      <h1>
            next train to...
        </h1>
      <form method="post">

        <div class="field-group location-group">
          <label for="location">departing from:</label>
          <input type="text" name="location" class="suggest" id="location" placeholder="Simon's Town" />
          <div class="validation-hint"></div>
        </div>

        <div class="field-group destination-group">
          <label for="destination">and arriving at:</label>
          <input type="text" name="destination" class="suggest" id="destination" placeholder="Cape Town" />
          <div class="validation-hint"></div>
        </div>

        <input type="submit" id="submit" value="Get train times" />
      </form>
    </div>

    <div class="results-screen results screen">
      <div class="load-anim"></div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-cookie/1.4.1/jquery.cookie.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/awesomplete/1.1.1/awesomplete.min.js"></script>
    <script src="js/global.js"></script>
  </body>

</html>
