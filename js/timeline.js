$(document).ready(function() {
    $("#map-container").hide();
    sizeWindowObjects();
    //displayMonthsInTimeline();
    initializeCanvas("drawing-canvas");
    buildLocationArray();
});
$(window).resize(function() {
    sizeWindowObjects();
});
function sizeWindowObjects() {
    var viewportHeight = $(window).height();
    viewportWidth = $(window).width();
    var dollarHeight = $("#dollar-amount").outerHeight();
    var participantHeight = $("#participant-icons").outerHeight();
    mapHeight = viewportHeight - dollarHeight - participantHeight;
    $("#map-container").css({
        'width': viewportWidth + 'px',
        'height': mapHeight + 'px',
        'top': dollarHeight + 'px',
        'position': 'absolute'
    });
    $("#drawing-canvas").attr("width", viewportWidth);
    $("#drawing-canvas").attr("height", mapHeight);
    $("#drawing-canvas").css({
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
                                creatorRadius = 5;
                            } else if (creatorFee > 10 && creatorFee <= 100) {
                                creatorRadius = 10;
                            } else if (creatorFee > 100) {
                                creatorRadius = 25;
                            }
                            if (creatorAddress != null && creatorRadius != 0) {
                                var markerPixel = overlay.getProjection().fromLatLngToContainerPixel(LatLngGoogle);
                                var markerPixelX = (parseFloat(markerPixel.x)).toFixed(0);
                                var markerPixelY = (parseFloat(markerPixel.y)).toFixed(0);
                                animateCanvasCircle(markerPixelX,markerPixelY,creatorRadius,"#E61111");
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
                                runnerRadius = 5;
                            } else if (runnerFee > 10 && runnerFee <= 100) {
                                runnerRadius = 10;
                            } else if (runnerFee > 100) {
                                runnerRadius = 25;
                            }
                            if (runnerAddress != null && runnerRadius != 0) {
                                var markerPixel = overlay.getProjection().fromLatLngToContainerPixel(LatLngGoogle);
                                var markerPixelX = (parseFloat(markerPixel.x)).toFixed(0);
                                var markerPixelY = (parseFloat(markerPixel.y)).toFixed(0);
                                animateCanvasCircle(markerPixelX,markerPixelY,runnerRadius,"#F8B900");
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
                                mechanicRadius = 5;
                            } else if (mechanicFee > 10 && mechanicFee <= 100) {
                                mechanicRadius = 10;
                            } else if (mechanicFee > 100) {
                                mechanicRadius = 25;
                            }
                            if (mechanicAddress != null && mechanicRadius != 0) {
                                var markerPixel = overlay.getProjection().fromLatLngToContainerPixel(LatLngGoogle);
                                var markerPixelX = (parseFloat(markerPixel.x)).toFixed(0);
                                var markerPixelY = (parseFloat(markerPixel.y)).toFixed(0);
                                animateCanvasCircle(markerPixelX,markerPixelY,mechanicRadius,"#F79125");
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
function initializeCanvas(canvas_id) {
    var drawingCanvas = document.getElementById(canvas_id);
    context = drawingCanvas.getContext("2d");
}
function animateCanvasCircle(positionX, positionY, finalSize, colorCode) {
    counter = 1;
    animationInterval = setInterval(function() {
        if (counter < finalSize) {
            context.clearRect(0,0,viewportWidth,mapHeight);
            context.strokeStyle = colorCode;
            context.fillStyle = colorCode;
            context.beginPath();
            context.arc(positionX,positionY,counter,0,Math.PI*2,false);
            context.closePath();
            context.stroke();
            context.fill();
            counter++
        }
        else {
            clearInterval(animationInterval);
        }
    },20);
}