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
var priority_time;
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
    'Harfield Rd',
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
            case "harfield":
            case "harfield road":
                return "Harfield Rd";
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
            case "the hood":
                return "Kenilworth";
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
    var validationHint = $("." + field.slice(1,field.length) + "-group").find(".validation-hint");

    if ($.inArray(fieldVal, stations) != -1) {
        validationHint.removeClass("invalid-station");
        validationHint.addClass("valid-station");
        return true;
    } else if (!fieldVal) {
        // Allow an empty field (this will be interpreted as the placeholder value for that field)
        validationHint.removeClass("invalid-station");
        validationHint.addClass("valid-station");
        return true;
    } else {
        validationHint.removeClass("valid-station");
        validationHint.addClass("invalid-station");
        return false;
    }
    
}

$("#location").on('focusout keyup', function() {feedbackInput("#location")});
$("#destination").on('focusout keyup', function() {feedbackInput("#destination")});

// --------------- ON SUBMIT -------------
$("input#submit").on('click', function() {
    
    document.querySelector(".results").style.height = "100vh";
    
    var loc, dest;

    if ($("input#location").val() == 'Wenelworth') {
        loc = 'Kenilworth';
        dest = squeezeNames($.trim($("input#destination").val()));
        $.cookie("wenelworth", true);
    } else if ($("input#destination").val() == 'Wenelworth') {
        dest = 'Kenilworth';
        loc = squeezeNames($.trim($("input#location").val()));
        $.cookie("wenelworth", true);
    } else {
        loc = squeezeNames($.trim($("input#location").val()));
        dest = squeezeNames($.trim($("input#destination").val()));
    }
    
    loc = loc ? loc : "Simon's Town";
    dest = dest ? dest : "Cape Town";
    
    var day = new Date().getDay(); // TODO: allow user to select arbitrary date (date is default today) (API can handle arbitrary dates/times)
    if (!priority_time) {
        var time_now = getTime(); // TODO: allow user to select abitrary time (time is default time now)
    }
    
    var direction = "";

    // Get time and format it properly
    time_now = getTime();

    // // Input precalculation //

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
                "time": time_now,
                "day": day,
                "line":"southern" // TODO: add other lines too
            };
            
            
            $('html, body').animate({
                scrollTop: $(".results").offset().top
            }, 350);
            
            // Finally, POST the data
            $.post("../ajax/timetable_processor.php", data_to_send, function(data) {
                processResults(data);
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
    
    
    // Don't do default form submit action after all this above!
    return false;
});

function processResults(data) {
    if ($.cookie("wenelworth")) {
        data = data.toString().replace(/(Kenilworth)/ig, "Wenelworth");
    }

    console.log("Raw data: " + data.toString());
    try {
        data = JSON.parse(data);
    } catch (e) {
        // Should actually just try to remove the PHP debug notice (<xdebug> or whatever) and display the stuff without it
        $(".results").html('<p class="error">ERROR: Couldnt\' parse JSON...'  + data["error"] + '</p>');
    }
    console.log("JSON data: " + data);

    $("#debug").html('<p class="debug">' + data["results_count"] + ' results</p>');
    $(".results").text("");

    if (data["error"]) {
        $(".results").html('<p class="error">ERROR: ' + data["error"] + '</p>');
    }
    if (data) {
        if (data.trains.length > 0) {
            for (var i = 0; i < data["trains"].length; i++) {
                // Output these nicely :)
                var train = data["trains"][i];
                
                var trainno = train.trainno;
                var trainStatus = train["status"] ? train["status"] : (data['status'].other_trains ? data['status'].other_trains : "UNKNOWN"); // Note here bracket notation is used as 'status' is a DOM API reserved word
                var departure = data.departure_station;
                var arrival = data.arrival_station;
                var departureTime = train.departure;
                var arrivalTime = train.arrival;
                
                $(".results").append(dropInHtmlTrainMediaObject(trainno, trainStatus, departure, arrival, departureTime, arrivalTime));
                
            }
        } else {
            $(".results").append(dropInHtmlAlert(true, "No trains are running now. Try again at another time."));
        }

    }
    else {
        $("#debug").text('No data received.');
    }
}

function dropInHtmlTrainMediaObject(trainno, trainStatus, departure, arrival, departureTime, arrivalTime) {
    if (trainStatus.toUpperCase() === "ON TIME") {
        return '<div class="train on-time"> <div class="status-line">TRAIN '+ trainno + ' - ' + trainStatus +'</div> <table style=" width: 100%; "> <thead> <tr> <th>DEPARTING FROM<p class="station-name">'+ departure +'</p></th> <th>ARRIVING IN<p class="station-name">'+ arrival +'</p></th> </tr> </thead> <tbody> <tr> <td>'+ departureTime +'</td> <td>'+ arrivalTime +'</td> </tr> </tbody> </table> </div>';
    } else if (trainStatus.toUpperCase() === "CANCELLED") {
        return '<div class="train cancelled"> <div class="status-line">TRAIN '+ trainno + ' - ' + trainStatus +'</div> <table style=" width: 100%; "> <thead> <tr> <th>DEPARTING FROM<p class="station-name">'+ departure +'</p></th> <th>ARRIVING IN<p class="station-name">'+ arrival +'</p></th> </tr> </thead> <tbody> <tr> <td>'+ departureTime +'</td> <td>'+ arrivalTime +'</td> </tr> </tbody> </table> </div>';
    } else if (trainStatus.toUpperCase() === "UNKNOWN" || !trainStatus) {
        return '<div class="train unknown"> <div class="status-line">TRAIN '+ trainno + ' - ' +'UNKNOWN STATUS</div> <table style=" width: 100%; "> <thead> <tr> <th>DEPARTING FROM<p class="station-name">'+ departure +'</p></th> <th>ARRIVING IN<p class="station-name">'+ arrival +'</p></th> </tr> </thead> <tbody> <tr> <td>'+ departureTime +'</td> <td>'+ arrivalTime +'</td> </tr> </tbody> </table> </div>';
    } else {
        var delay = /([0-9]\+?)+/g.exec(trainStatus)[0];
        return '<div class="train late"> <div class="status-line">TRAIN '+ trainno + ' - ' + trainStatus +'</div> <table style=" width: 100%; "> <thead> <tr> <th>DEPARTING FROM<p class="station-name">'+ departure +'</p></th> <th>ARRIVING IN<p class="station-name">'+ arrival +'</p></th> </tr> </thead> <tbody> <tr> <td>'+ departureTime +'</td> <td>'+ arrivalTime +'</td> </tr> <tr class="new"><td>'+ calculateNewTime(departureTime, delay) +'</td><td>'+ calculateNewTime(arrivalTime, delay) +'</td></tr> </tbody> </table> </div>';
    }
}

function calculateNewTime(oldTime, delay) {
    var preString = "&plusmn;";
    if (delay.charAt(delay.length - 1) === "+") {
        delay.replace("+", "");
        preString = "AFTER ";
    }
    var oldDateObj = new Date('1-1-2000 ' + oldTime.toString());
    var newDateObj = new Date(oldDateObj.getTime() + delay*60000);
    var hours = newDateObj.getHours().toString();
    var mins = newDateObj.getMinutes().toString();
    
    return preString + (hours.length < 2 ? ('0' + hours) : hours) + ':' + (mins.length < 2 ? ('0' + mins) : mins);
}

function dropInHtmlAlert(isFatal, message) {
    return '<div class="alert'+ (isFatal ? ' fatal' : '') +'">'+ message +'</div>';
}

$(document).bind("ajaxSend", function() {
    $(".load-anim").show();
    $(".results").text("");
    $(".debug").text("");
}).bind("ajaxComplete", function() {
    $(".load-anim").hide();
});

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