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

var line = "Southern"; // TODO: other lines

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

function titleCase(str) {
  var newstr = str.split(" ");
  for(var i=0;i<newstr.length;i++){
    if(newstr[i] == "") continue;
    var copy = newstr[i].substring(1).toLowerCase();
    newstr[i] = newstr[i][0].toUpperCase() + copy;
  }
   newstr = newstr.join(" ");
   return newstr;
}

function squeezeNames(stationName) {
    switch (stationName.toLowerCase()) {
            case "capetown":
            case "cape town":
                return "Cape Town";
            case "saltriver":
            case "saltrivier":
                return "Salt River";
            case "mont":
            case "claremont":
                return "Claremont";
            case "harfieldroad":
            case "harfieldrd":
            case "harfield rd":
            case "harfield":
                return "Harfield Road";
            case "ew":
            case "ww":
            case "east side":
            case "west side":
            case "my hood":
            case "u kno where":
            case "u know where":
            case "the streets of wenelworth":
            case "our hood":
            case "tha hood":
                return "Wenelworth";
            case "diep rivier":
            case "diep river":
            case "diepriver":
                return "Dieprivier";
            case "falsebay":
                return "False Bay";
            case "st james":
            case "stjames":
                return "St. James";
            case "fishhoek":
                return "Fish Hoek";
            case "simons town":
            case "simonstown":
            case "simon'stown":
            case "idolophu kasimon":
                return "Simon's Town";
        }
        return titleCase(stationName);
}

function feedbackInput(field) {
    var fieldVal = squeezeNames($(field).val());

    if ($.inArray(fieldVal, stations) != -1) {
        $(field).addClass("valid-station");
        return true;
    } else {
        $(field).addClass("invalid-station");
        return false;
    }
    
}

// $("#location").on('keyup', function() {feedbackInput("#location")});
// $("#destination").on('keyup', function() {feedbackInput("#destination")});
$("#location").on('focusout', function() {feedbackInput("#location")});
$("#destination").on('focusout', function() {feedbackInput("#destination")});

// --------------- ON SUBMIT -------------
$("input#submit").on('click', function() {

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
        
        loc = squeezeNames(loc);
        dest = squeezeNames(dest);

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
                
                // Finally, POST the data
                $.post("../ajax/timetable_processor.php", data_to_send, function(data) {
                    processResults(data);
                });

                $.get({
                    url: "http://gometroapp.com/?updates+commuter",
                    global: false,
                    success: addUpdates
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
    $(".results").text("");
    $(".debug").text("");
}).bind("ajaxComplete", function() {
    $(".load-anim").hide();
    console.log("ajaxComplete called: Hiding .load-anim");
});

function addUpdates(data) {
    console.log(data);
}

// -------------------- AUTOCOMPLETE ------------------
// Implementing the input boxes' autocomplete

var location = document.getElementById("location");
var destination = document.getElementById("destination");

function myLocSorter(a, b) {
    var searchQuery = location.value.toLowerCase();
    if ((searchQuery == a.toLowerCase().slice(0, searchQuery.length)) && (searchQuery != b.toLowerCase().slice(0, searchQuery.length))) { // only a begins with query
        return -1;
    }
    else if ((searchQuery == b.toLowerCase().slice(0, searchQuery.length)) && (searchQuery != a.toLowerCase().slice(0, searchQuery.length))) { // only b begins with query
        return 1;
    }
    else { // neither or both begin with query
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
    }
    else { // neither or both begin with query
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