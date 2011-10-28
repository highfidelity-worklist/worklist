$(document).ready(function() {
    sizeWindowObjects();
    //displayMonthsInTimeline();
    initializeCanvases("drawing-canvas-1", "drawing-canvas-2", "drawing-canvas-3");
    initializeMap();
    buildLocationArray();
});
$(window).resize(function() {
    sizeWindowObjects();
});
function sizeWindowObjects() {
    viewportHeight = $(window).height();
    viewportWidth = $(window).width();
    dollarHeight = $("#dollar-amount").outerHeight();
    participantHeight = $("#participant-icons").outerHeight();
    mapHeight = viewportHeight - dollarHeight - participantHeight;
    $("#map-container").css({
        'width': viewportWidth + 'px',
        'height': mapHeight + 'px',
        'top': dollarHeight + 'px',
        'position': 'absolute'
    });
    $("#drawing-canvas-1, #drawing-canvas-2, #drawing-canvas-3").attr("width", viewportWidth);
    $("#drawing-canvas-1, #drawing-canvas-2, #drawing-canvas-3").attr("height", mapHeight);
    $("#drawing-canvas-1, #drawing-canvas-2, #drawing-canvas-3").css({
        'top': dollarHeight + 'px',
        'position': 'absolute'
    });
    initializeMap();
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
                zoom: 2,
                center: latlng,
                mapTypeId: google.maps.MapTypeId.ROADMAP
            };
            map = new google.maps.Map(document.getElementById("map-container"), myOptions);
            overlay = new google.maps.OverlayView();
            overlay.draw = function() {};
            overlay.setMap(map);
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
                        var creatorLocation = addressGeoLatLong[creatorInArray];
                        if (creatorLocation) {
                            var creatorCenter = creatorLocation.replace(/[\(\)\s]/g, "");
                            var LatLong = creatorCenter.split(",");
                            var Lat = parseFloat(LatLong[0]);
                            var Long = parseFloat(LatLong[1]);
                            var LatFixed = (Lat).toFixed(6);
                            var LongFixed = (Long).toFixed(6);
                            var LatLngGoogle = new google.maps.LatLng(LatFixed,LongFixed);
                            if (creatorFee <= 10) {
                                creatorRadius = 20;
                            } else if (creatorFee > 10 && creatorFee <= 100) {
                                creatorRadius = 50;
                            } else if (creatorFee > 100) {
                                creatorRadius = 100;
                            }
                            if (creatorAddress != null && creatorRadius != 0) {
                                var markerPixel = overlay.getProjection().fromLatLngToContainerPixel(LatLngGoogle);
                                var markerPixelX = (parseFloat(markerPixel.x)).toFixed(0);
                                var markerPixelY = (parseFloat(markerPixel.y)).toFixed(0);
                                animateCanvasCircle(markerPixelX,markerPixelY,creatorRadius,"#E61111","1");
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
                            if (runnerFee <= 10) {
                                runnerRadius = 20;
                            } else if (runnerFee > 10 && runnerFee <= 100) {
                                runnerRadius = 50;
                            } else if (runnerFee > 100) {
                                runnerRadius = 100;
                            }
                            if (runnerAddress != null && runnerRadius != 0) {
                                var markerPixel = overlay.getProjection().fromLatLngToContainerPixel(LatLngGoogle);
                                var markerPixelX = (parseFloat(markerPixel.x)).toFixed(0);
                                var markerPixelY = (parseFloat(markerPixel.y)).toFixed(0);
                                animateCanvasCircle(markerPixelX,markerPixelY,runnerRadius,"#3AC115","2");
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
                            if (mechanicFee <= 10) {
                                mechanicRadius = 20;
                            } else if (mechanicFee > 10 && mechanicFee <= 100) {
                                mechanicRadius = 50;
                            } else if (mechanicFee > 100) {
                                mechanicRadius = 100;
                            }
                            if (mechanicAddress != null && mechanicRadius != 0) {
                                var markerPixel = overlay.getProjection().fromLatLngToContainerPixel(LatLngGoogle);
                                var markerPixelX = (parseFloat(markerPixel.x)).toFixed(0);
                                var markerPixelY = (parseFloat(markerPixel.y)).toFixed(0);
                                animateCanvasCircle(markerPixelX,markerPixelY,mechanicRadius,"#F79125","3");
                            }
                        }
                    }
                    markerCounter++;
                } else {
                    clearInterval(markerCreationLoop);
                }
            }, 100);
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
function initializeCanvases(canvas_id1, canvas_id2, canvas_id3) {
    var drawingCanvas1 = document.getElementById(canvas_id1);
    context1 = drawingCanvas1.getContext("2d");
    var drawingCanvas2 = document.getElementById(canvas_id2);
    context2 = drawingCanvas2.getContext("2d");
    var drawingCanvas3 = document.getElementById(canvas_id3);
    context3 = drawingCanvas3.getContext("2d");
}
function animateCanvasCircle(positionX, positionY, finalSize, colorCode, canvas_id) {
    counter = 1;
    if (canvas_id == "1") {
    animationInterval = setInterval(function() {
        if (counter < finalSize) {
            context1.clearRect(0,0,viewportWidth,mapHeight);
            context1.strokeStyle = colorCode;
            context1.fillStyle = colorCode;
            context1.beginPath();
            context1.arc(positionX,positionY,counter,0,Math.PI*2,false);
            context1.closePath();
            context1.stroke();
            context1.fill();
            counter++
        }
        else {
            clearInterval(animationInterval);
        }
    },5000);
    } else if (canvas_id == "2") {
        animationInterval = setInterval(function() {
        if (counter < finalSize) {
            context2.clearRect(0,0,viewportWidth,mapHeight);
            context2.strokeStyle = colorCode;
            context2.fillStyle = colorCode;
            context2.beginPath();
            context2.arc(positionX,positionY,counter,0,Math.PI*2,false);
            context2.closePath();
            context2.stroke();
            context2.fill();
            counter++
        }
        else {
            clearInterval(animationInterval);
        }
    },5000);    
    } else if (canvas_id == "3") {
        animationInterval = setInterval(function() {
        if (counter < finalSize) {
            context3.clearRect(0,0,viewportWidth,mapHeight);
            context3.strokeStyle = colorCode;
            context3.fillStyle = colorCode;
            context3.beginPath();
            context3.arc(positionX,positionY,counter,0,Math.PI*2,false);
            context3.closePath();
            context3.stroke();
            context3.fill();
            counter++
        }
        else {
            clearInterval(animationInterval);
        }
    },5000);    
    }
}
function initializeMap() {
    var latlng = new google.maps.LatLng(40, -30);
    var myOptions = {
        navigationControl: false,
        mapTypeControl: false,
        scaleControl: false,
        draggable: false,
        scrollwheel: false,
        disableDefaultUI: true,
        disableDoubleClickZoom: true,
        zoom: 2,
        center: latlng,
        mapTypeId: google.maps.MapTypeId.ROADMAP
    };
    map = new google.maps.Map(document.getElementById("map-container"), myOptions);
    overlay = new google.maps.OverlayView();
    overlay.draw = function() {};
    overlay.setMap(map);
}