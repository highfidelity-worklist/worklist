$(document).ready(function() {
    getLatLong();
});
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
                    alert("done");
                }
            }, 1000);
        }
    });
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