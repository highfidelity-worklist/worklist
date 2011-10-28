$(document).ready(function() {
    $("#map-container").hide();
    sizeWindowObjects();
    displayMonthsInTimeline();
    buildLocationArray();
});
$(window).resize(function() {
    sizeWindowObjects();
});
function sizeWindowObjects() {
    var viewportHeight = $(window).height();
    var viewportWidth = $(window).width();
    var dollarHeight = $("#dollar-amount").outerHeight();
    var participantHeight = $("#participant-icons").outerHeight();
    var mapHeight = viewportHeight - dollarHeight - participantHeight;
    $("#map-container").css({
        'width': viewportWidth + 'px',
        'height': mapHeight + 'px',
        'top': dollarHeight + 'px',
        'position': 'absolute'
    });
}
function collectData() {
    $.ajax({
        type: "POST",
        data: "action=getHistoricalData",
        dataType: "json",
        url: "timeline.php",
        success: function(data) {
            var latlng = new google.maps.LatLng(40, -30);
            var myOptions = {
                navigationControl: false,
                mapTypeControl: false,
                scaleControl: false,
                draggable: false,
                scrollwheel: false,
                disableDefaultUI: true,
                disableDoubleClickZoom: true,
                zoom: 3,
                center: latlng,
                mapTypeId: google.maps.MapTypeId.ROADMAP
            };
            map = new google.maps.Map(document.getElementById("map-container"), myOptions);
            totalRecords = data.length;
            delayPerSet = 20000 / totalRecords;
            markerCounter = 0;
            markerCreationLoop = setInterval(function() {
                timeInMotion(markerCounter, totalRecords, "timeline-bullet");
                if (markerCounter < totalRecords) {
                    
                    var creatorFee = data[markerCounter].creator_fee;
                    addToTicker(creatorFee);
                    var creatorAddress = data[markerCounter].creator_address;
                    if (creatorAddress != null) {
                        var creatorInArray = jQuery.inArray(creatorAddress, addressGeoAddress);
                        if (creatorLocation) {
                            var creatorLocation = addressGeoLatLong[creatorInArray];
                            var creatorCenter = creatorLocation.replace(/[\(\)\s]/g, "");
                            var LatLong = creatorCenter.split(",");
                            var Lat = parseFloat(LatLong[0]);
                            var Long = parseFloat(LatLong[1]);
                            var LatFixed = (Lat).toFixed(6);
                            var LongFixed = (Long).toFixed(6);
                            var LatLngGoogle = new google.maps.LatLng(LatFixed,LongFixed);
                            var creatorRadius = creatorFee * 500;
                            if (creatorAddress != null && creatorRadius != 0) {
                                var creatorOptions = {
                                    strokeColor: "#FF0000",
                                    strokeOpacity: 1,
                                    strokeWeight: 2,
                                    map: map,
                                    center: LatLngGoogle,
                                    radius: Math.round(creatorRadius)
                                };
                                var creatorCircle = new google.maps.Circle(creatorOptions);
                            }
                        }
                    }
                    var runnerFee = data[markerCounter].runner_fee;
                    addToTicker(runnerFee);
                    var runnerAddress = data[markerCounter].runner_address;
                    if (runnerAddress != null) {
                        var runnerInArray = jQuery.inArray(runnerAddress, addressGeoAddress);
                        var runnerLocation = addressGeoLatLong[runnerInArray];
                        if (runnerLocation) {
                            var runnerCenter = runnerLocation.replace(/[\(\)\s]/g, "");
                            var LatLong = runnerCenter.split(",");
                            var Lat = parseFloat(LatLong[0]);
                            var Long = parseFloat(LatLong[1]);
                            var LatFixed = (Lat).toFixed(6);
                            var LongFixed = (Long).toFixed(6);
                            var LatLngGoogle = new google.maps.LatLng(LatFixed,LongFixed);
                            var runnerRadius = runnerFee * 500;
                            if (runnerAddress != null && runnerRadius != 0) {
                                var runnerOptions = {
                                    strokeColor: "#80C908",
                                    strokeOpacity: 1,
                                    strokeWeight: 2,
                                    map: map,
                                    center: LatLngGoogle,
                                    radius: Math.round(runnerRadius)
                                };
                                var runnerCircle = new google.maps.Circle(runnerOptions);
                            }
                        }
                    }
                    var mechanicFee = data[markerCounter].mechanic_fee;
                    addToTicker(mechanicFee);
                    var mechanicAddress = data[markerCounter].mechanic_address;
                    if (mechanicAddress != null) {
                        var mechanicInArray = jQuery.inArray(mechanicAddress, addressGeoAddress);
                        var mechanicLocation = addressGeoLatLong[mechanicInArray];
                        if (mechanicLocation) {
                            var mechanicCenter = mechanicLocation.replace(/[\(\)\s]/g, "");
                            var LatLong = mechanicCenter.split(",");
                            var Lat = parseFloat(LatLong[0]);
                            var Long = parseFloat(LatLong[1]);
                            var LatFixed = (Lat).toFixed(6);
                            var LongFixed = (Long).toFixed(6);
                            var LatLngGoogle = new google.maps.LatLng(LatFixed,LongFixed);
                            var mechanicRadius = mechanicFee * 500;
                            if (mechanicAddress != null && mechanicRadius != 0) {
                                var mechanicOptions = {
                                    strokeColor: "#0EB3F1",
                                    strokeOpacity: 1,
                                    strokeWeight: 2,
                                    map: map,
                                    center: LatLngGoogle,
                                    radius: Math.round(mechanicRadius)
                                };
                                var mechanicCircle = new google.maps.Circle(mechanicOptions);
                            }
                        }
                    }
                    markerCounter++;
                } else {
                    alert("done");
                    clearInterval(markerCreationLoop);
                }
            }, delayPerSet);
        }
    });
}
function getLatLong() {
    $.ajax({
        type: "POST",
        data: "action=getDistinctLocations",
        dataType: "json",
        url: "timeline.php",
        success: function(data) {
            addressGeoLatLong = new Array();
            addressGeoAddress = new Array();
            counter = 0;
            total = data.length;
            geoCodeLoop = setInterval(function() {
                if (counter < total) {
                    var this_address = data[counter].address;
                    var this_location = geoCode(this_address);
                    $.ajax({
                        type: "POST",
                        data: "action=storeLatLong&location=" + this_address + "&latlong=" + this_location,
                        dataType: "json",
                        url: "timeline.php"
                    })
                    /* */ 
                    counter++;
                } else {
                    clearInterval(geoCodeLoop);
                    $("#map-container").show();
                    collectData();
                }
            }, 1000);
        }
    });
}

function buildLocationArray() {
    $.ajax({
        type: "POST",
        data: "action=getLatLong",
        dataType: "json",
        url: "timeline.php",
        success: function(data) {
            addressGeoLatLong = new Array();
            addressGeoAddress = new Array();
            counter = 0;
            total = data.length;
            while (counter < total) {
                var this_address = data[counter].location;
                var this_location = data[counter].latlong;
                addressGeoLatLong.push(this_location);
                addressGeoAddress.push(this_address);
                counter++;
            }
            $("#loading-indicator").hide();
            $("#map-container").show();
            collectData();
        }
    })
}

function geoCode(markerAddress) {
    var geocoder = new google.maps.Geocoder();
    geocoder.geocode({
        'address': markerAddress
    }, function(results, status) {
        if (status == "OK" && !!results[0]) {
            geo = results[0].geometry.location;
        }
    });
    return geo;
}
function addToTicker(value) {
    if (value != null) {
        var currentValue = $("#dollar-figure").text();
        currentValue = currentValue.replace(/,/, "");
        currentValue = parseFloat(currentValue);
        var includeValue = parseFloat(value);
        var newValue = currentValue + includeValue;
        $("#dollar-figure").html(formatDollar(newValue));
    }
}
function formatDollar(num) {
    var p = num.toFixed(2).split(".");
    return p[0].split("").reverse().reduce(function(acc, num, i, orig) {
        return  num + (i && !(i % 3) ? "," : "") + acc;
    }, "");
}
function timeInMotion(currentPosition,totalPositions,jqSelector) {
    var positionPercentage = (currentPosition * 100) / totalPositions;
    var newLocation = (parseFloat(positionPercentage)).toFixed(0);
    if (newLocation > 99) {
        newLocation = 99;
    }
    $("#" + jqSelector).css("left", newLocation + "%");
}
function displayMonthsInTimeline() {
    $.ajax({
        type: "POST",
        data: "action=getListOfMonths",
        dataType: "json",
        url: "timeline.php",
        success: function(data) {
            var totalMonths = data.length;
            var viewportWidth = $(window).width();
            var horizontalOffset = viewportWidth / (totalMonths + 1)
            for (i=0;i<totalMonths;i++) {
                $("#container").append("<div class=\"month-class\" style=\"left: " + parseFloat(horizontalOffset * (i * 1)).toFixed(0) + "px\">" + data[i] + "</div>");
            }
        }
    })
}