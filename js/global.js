/* global $*/
/* global Awesomplete*/
$(document).ready(function() {

    /* TODO: features:
        - Visual UI/UX design!
        - Scrape real-time train delay/cancellation notices
        - More options: to be able to choose custom date/time
        - Option to choose to sort results by time of arrival at destination
        - Xhosa translation
        - Button to switch dest/loc stations
        - On event 'change in input.typeahead', check if input.typeahead.val() is in stations, and alert user with green/red if it is/isn't

        design:
        - Add responsive design

    */

    var line = "Southern";

    function checkTime(i) {
        return (i < 10) ? "0" + i : i;
    }

    function getTime() {
        var today = new Date(),
            h = checkTime(today.getHours()),
            m = checkTime(today.getMinutes());
        return h + ":" + m;
    }

    var stations = [
        'Cape Town',
        'Woodstock',
        'Salt River',
        'Observatory',
        'Mowbray',
        'Rosebank',
        'Rondebosch',
        'Newlands',
        'Claremont',
        'Harfield Road',
        'Kenilworth',
        'Wenelworth', // For more information on this station please message me
        'Wynberg',
        'Wittebome',
        'Plumstead',
        'Steurhof',
        'Dieprivier',
        'Heathfield',
        'Retreat',
        'Steenberg',
        'Lakeside',
        'False Bay',
        'Muizenberg',
        'St. James',
        'Kalk Bay',
        'Fish Hoek',
        'Sunny Cove',
        'Glencairn',
        "Simon's Town"

    ];

    $("input#submit").on('click', function() {

        // $(".load-anim").toggle();

        var loc = "";
        var dest = "";

        if ($("input#location").val() == 'Wenelworth') {
            loc = 'Kenilworth';
            dest = $("input#destination").val();
            $.cookie("wenelworth", true);
        }
        else if ($("input#destination").val() == 'Wenelworth') {
            dest = 'Kenilworth';
            loc = $("input#location").val();
            $.cookie("wenelworth", true);
        }
        else {
            loc = $("input#location").val();
            dest = $("input#destination").val();
        }

        var day = new Date().getDay(); // TODO: allow user to select arbitrary date (date is default today) (API can handle arbitrary dates/times)
        var time_now = getTime(); // TODO: allow user to select abitrary time (time is default time now)
        var direction = "";


        // Get time and format it properly
        time_now = getTime();

        // // Input precalculation //

        loc = $.trim(loc);
        dest = $.trim(dest);

        // Validation
        if (loc != "" && dest != "") {

            switch (dest.toLowerCase()) {
                case "capetown":
                    dest = "Cape Town";
                    break;
                case "saltriver":
                case "saltrivier":
                    dest = "Salt River";
                    break;
                case "mont":
                    dest = "Claremont";
                    break;
                case "harfieldroad":
                case "harfieldrd":
                case "harfield rd":
                case "harfield":
                    dest = "Harfield Road";
                    break;
                case "diep rivier":
                case "diep river":
                case "diepriver":
                    dest = "Dieprivier";
                    break;
                case "falsebay":
                    dest = "False Bay";
                    break;
                case "st james":
                case "stjames":
                    dest = "St. James";
                    break;
                case "fishhoek":
                    dest = "Fish Hoek";
                    break;
                case "simons town":
                case "simonstown":
                case "simon'stown":
                case "idolophu kasimon":
                    dest = "Simon's Town";
                    break;
            }

            switch (loc.toLowerCase()) {
                case "capetown":
                    loc = "Cape Town";
                    break;
                case "saltriver":
                case "saltrivier":
                    loc = "Salt River";
                    break;
                case "mont":
                    loc = "Claremont";
                    break;
                case "harfieldroad":
                case "harfieldrd":
                case "harfield rd":
                case "harfield":
                    loc = "Harfield Road";
                    break;
                case "diep rivier":
                case "diep river":
                case "diepriver":
                    loc = "Dieprivier";
                    break;
                case "falsebay":
                    loc = "False Bay";
                    break;
                case "st james":
                case "stjames":
                    loc = "St. James";
                    break;
                case "fishhoek":
                    loc = "Fish Hoek";
                    break;
                case "simons town":
                case "simonstown":
                case "simon'stown":
                case "idolophu kasimon":
                    loc = "Simon's Town";
                    break;
            }

            var locno = $.inArray(loc, stations);
            var destno = $.inArray(dest, stations);

            if (locno != -1 && destno != -1) {
                if (locno - destno != 0) {
                    if (locno - destno > 0) {
                        direction = "north";
                    }
                    else {
                        direction = "south";
                    }

                    // Checks passed; input is valid. Continue:
                    var data_to_send = {
                        "dest": dest,
                        "loc": loc,
                        "direction": direction,
                        "time_now": time_now,
                        "day": day
                    };
                    // console.log("Sending data: " + data_to_send);

                    // Finally, POST the data
                    $.post("../ajax/timetable_processor.php", data_to_send, function(data) {
                        processResults(data);
                    });

                    $.get({
                        url: "http://gometroapp.com/?updates+commuter",
                        global: false,
                        success: addUdates
                    });


                }
                else {
                    alert("Please enter different station names");
                }

            }
            else {
                // Input does not correspond to a station in stations[]
                alert("Please input correct station names.");
            }

        }
        else {
            alert("Please enter in both a destination station and the station you are traveling from.");
        }


        // Don't do default form submit action after all this above!
        return false;
    });

    function processResults(data) {
        if ($.cookie("wenelworth")) {
            data = data.toString().replace(/(Kenilworth)/ig, "Wenelworth");
        }

        console.log("Raw data: " + data.toString());
        data = JSON.parse(data);

        console.log("JSON data: " + data);

        $("#debug").html('<p class="debug">' + data["results_count"] + ' results</p>');
        $(".results").text("");

        if (data["error"]) {

            $(".results").html('<p class="error">ERROR: ' + data["error"] + '</p>');

        }
        else if (data) {
            for (var i = 0; i < data["trains"].length; i++) {
                // Output these nicely :)
                $(".results").append('<p class="train">TRAIN NO. ' + data["trains"][i]["trainno"] + " departs from " + data["departure_station"] + " at " + data["trains"][i]["departure"] + " and arrives at " + data["arrival_station"] + " at " + data["trains"][i]["arrival"] + "</p>");
            }

        }
        else {
            $("#debug").text('No data received.');
        }


    }

    $(document).bind("ajaxSend", function() {
        $(".load-anim").show();
        console.log("ajaxSend called: Showing .load-anim");
    }).bind("ajaxComplete", function() {
        $(".load-anim").hide();
        console.log("ajaxComplete called: Hiding .load-anim");
    });

    function addUdates(data) {
        console.log(data);
    }

    // - - - - - - -

    // Implementing the input box autocomplete

    var location = document.getElementById("location");
    var destination = document.getElementById("destination");
    
    function myLocSorter(a, b) {
  var searchQuery = location.value;
  if ((searchQuery == a.slice(0, searchQuery.length)) && (searchQuery != b.slice(0, searchQuery.length))) { // only a begins with query
    return -1;
  }
  else if ((searchQuery == b.slice(0, searchQuery.length)) && (searchQuery != a.slice(0, searchQuery.length))) { // only b begins with query
    return 1;
  } else { // neither or both begin with query
    return 0;
  }
}

    function myDestSorter(a, b) {
  var searchQuery = destination.value;
  if ((searchQuery == a.slice(0, searchQuery.length)) && (searchQuery != b.slice(0, searchQuery.length))) { // only a begins with query
    return -1;
  }
  else if ((searchQuery == b.slice(0, searchQuery.length)) && (searchQuery != a.slice(0, searchQuery.length))) { // only b begins with query
    return 1;
  } else { // neither or both begin with query
    return 0;
  }
}
    
    new Awesomplete(location, {
        list: stations,
        minChars: 1,
        maxItems: 15,
        autoFirst: true,
        sort: myLocSorter
    });


    new Awesomplete(destination, {
        list: stations,
        minChars: 1,
        maxItems: 15,
        autoFirst: true,
        sort: myDestSorter
    });

    $("#location").attr("autofocus", "true");

});