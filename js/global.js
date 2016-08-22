/* global $*/
/* global Awesomplete*/
$(document).ready(function () {

/* TODO: features:
    - On event 'change in input.typeahead', check if input.typeahead.val() is in stations, and alert user with green/red if it is/isn't
    - More options... to be able to choose custom date/time
    - Option to choose to sort results by time of arrival at destination
    - More lenient validity checking to allow for Capetown/Simons town/St James/Diep Rivier/etc
    - Visual UI/UX design!

*/

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
    'Wenelworth', // Cheeky inside joke ya dingus
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
    
    var loc = "";
    var dest = "";
    
    if ($("input#location").val() == 'Wenelworth') {
        loc = 'Kenilworth';
        dest = $("input#destination").val();
        $.cookie("wenelworth", true);
    } else if ($("input#destination").val() == 'Wenelworth') {
        dest = 'Kenilworth';
        loc = $("input#location").val();
        $.cookie("wenelworth", true);
    } else {
        loc = $("input#location").val();
        dest = $("input#destination").val();
    }
    
    var day = new Date().getDay(); // TODO: allow user to select arbitrary date (date is default today)
    var time_now = getTime(); // TODO: allow user to select abitrary time (time is default time now)
    var direction = "";
    
    
    // Get time and format it properly
    time_now = getTime();
    
    // // Input precalculation //
    
    // Validation
    if ($.trim(loc) != "" && $.trim(dest) != "") {
    
        // Determine direction
        var locno = $.inArray(loc, stations);
        var destno = $.inArray(dest, stations);
        
        if (locno != -1 && destno != -1) {
            if (locno - destno != 0) {
                if (locno - destno > 0) {
                    direction = "north";
                } else {
                    direction = "south";
                }
            
            // Checks passed; input is valid. Continue:
            
            var data_to_send = {"dest": dest, "loc": loc, "direction": direction, "time_now": time_now, "day": day};
        
            // Finally, POST the data
            $.post("../ajax/timetable_processor.php", data_to_send, function(data) {
                processResults(data);
            });
            
                
            } else {
                alert("Please enter different station names");
            }
            
        } else {
            // Input does not correspond to a station in stations[]
            alert("Please input correct station names.");
        }
        
    } else {
        alert("Please enter in both a destination station and the station you are traveling from.");
    }
    
    
    // Don't do default action after all this above!
    return false;
});

function processResults(data) {
    if ($.cookie("wenelworth")) {
         data = data.toString().replace(/(Kenilworth)/ig, "Wenelworth");
    }
    
    console.log("Raw data: " + data.toString());
    data = JSON.parse(data);
    
    console.log("JSON data: " + data);
    
    document.getElementById("debug").innerHTML = '<p class="debug">' + data["results_count"] + ' results</p>';
    document.getElementById("results").innerHTML = "";
    
    if (data["error"]) {
        
        document.getElementById("results").innerHTML = '<p class="error">ERROR: ' + data["error"] + '</p>';
        
    } else {
        /* TODO: make this work */ 
        for (var i = 0; i < data["trains"].length; i++) {
        // Output these nicely :)
        $("#results").append('<p class="train">TRAIN NO. ' + data["trains"][i]["trainno"] + " departs from " + data["departure_station"] +" at " + data["trains"][i]["departure"] + " and arrives at " + data["arrival_station"] + " at " + data["trains"][i]["arrival"] + "</p>");
        }
        
    }
    
    
}

// - - - - - - -

// Implementing the input box autocomplete

var location = document.getElementById("location");
var destination = document.getElementById("destination");

new Awesomplete(location, {
	list: stations,
	minChars: 1,
	maxItems: 15,
	autoFirst: true
});


new Awesomplete(destination, {
	list: stations,
	minChars: 1,
	maxItems: 15,
	autoFirst: true
});

});