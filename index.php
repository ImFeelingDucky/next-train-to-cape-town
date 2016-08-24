<!-- Max Tyrrell -->
<html>

<head>
    <title>Train Times</title>
    <link rel="stylesheet" href="css/style.css" type="text/css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/awesomplete/1.1.1/awesomplete.min.css" type="text/css" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>

<body>
    <div class="mega-container">
        
        <form method="post">
            <label for="location">Where are you now?<br>
                    <input type="text" name="location" class="suggest" id="location" placeholder="E.g. Kenilworth" />
                </label><br>
            <label for="destination">Where would you like to go?<br>
                    <input type="text" name="destination" class="suggest" id="destination" placeholder="E.g. Cape Town" />
                </label><br>
            <input type="submit" id="submit" value="Get train times" />
        </form>

        <!-- <div class="load-anim">
            <div class="ball"></div>
            <div class="ball"></div>
            <div class="ball"></div>
            <div class="ball"></div>
            <div class="ball"></div>
            <div class="ball"></div>
            <div class="ball"></div>
            <div class="ball"></div>
        </div> -->

        <div class="results"></div>

        <div id="debug"></div>

    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-cookie/1.4.1/jquery.cookie.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/awesomplete/1.1.1/awesomplete.min.js"></script>
    <script src="js/global.js"></script>
</body>

</html>