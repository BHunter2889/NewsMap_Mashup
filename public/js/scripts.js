/* global google */
/* global _ */
/* global $*/
/**
 * scripts.js
 *
 * Computer Science 50
 * Problem Set 8
 *
 * Global JavaScript.
 */

// Google Map
var map;

// markers for map
var markers = [];

// info window
var info = new google.maps.InfoWindow();

// execute when the DOM is fully loaded
$(function() {

    // styles for map
    // https://developers.google.com/maps/documentation/javascript/styling
    var styles = [

        // hide Google's labels
        {
            featureType: "all",
            elementType: "labels",
            stylers: [
                {visibility: "off"}
            ]
        },

        // hide roads
        {
            featureType: "road",
            elementType: "geometry",
            stylers: [
                {visibility: "simplified"}
            ]
        }

    ];

    // options for map
    // https://developers.google.com/maps/documentation/javascript/reference#MapOptions
    var options = {
        center: {lat: 38.6273, lng: -90.1979}, // St. Louis, MO
        disableDefaultUI: true,
        mapTypeId: google.maps.MapTypeId.ROADMAP,
        maxZoom: 14,
        panControl: true,
        styles: styles,
        zoom: 13,
        zoomControl: true
    };

    // get DOM node in which map will be instantiated
    var canvas = $("#map-canvas").get(0);

    // instantiate map
    map = new google.maps.Map(canvas, options);

    // configure UI once Google Map is idle (i.e., loaded)
    google.maps.event.addListenerOnce(map, "idle", configure);

});

/**
 * Adds marker for place to map.
 */
function addMarker(place)
{
    //var coords = {lat: place.latitude, lng: place.longitude};
    
    
    //per: https://developers.google.com/maps/documentation/javascript/markers#marker_labels
    var image = {
        url: 'https://maps.google.com/mapfiles/kml/pal2/icon31.png',
        size: new google.maps.Size(32, 32),
        labelOrigin: new google.maps.Point(0, 40),
        origin: new google.maps.Point(0, 0),
        //anchor: new google.maps.Point(17, 34),
    };
    
    var marker = new MarkerWithLabel({
        position: {lat: Number(place.latitude), lng: Number(place.longitude)},
        map: map,
        icon: image,
        labelContent: place.place_name + ', ' + place.admin_name1,
        labelAnchor: new google.maps.Point(50, 0),
        labelClass: "label",
        title: place.place_name + ', ' + place.admin_name1
    });
    
    marker.addListener('click', function(){
        
        //initialize content to add to
        var content = "<ul>";
        
        //get articles for marker
        var parameters = {
            geo: place.postal_code
        };
        
        //console.log(place.postal_code);
        
        $.getJSON("articles.php", parameters)
        .done(function(data, textStatus, jqXHR) {
            // set unordered list for articles
            for (var i = 0; i < data.length; i++)
            {
               content += "<li><a href=" + data[i].link + ">" + data[i].title + "</a></li>";
            }
            
            content += "</ul>";
            
            //open InfoWindow
            showInfo(marker, content);
            //console.log(content);
         })
         .fail(function(jqXHR, textStatus, errorThrown) {
    
             // log error to browser's console
             console.log(errorThrown.toString());
         });
    });

    
    //var place_label = new MarkerLabel_(marker, 'https://maps.google.com/mapfiles/kml/pal2/icon31.png', 'https://maps.gstatic.com/mapfiles/openhand_8_8.cur');
    
    markers.push(marker);

}

/**
 * Configures application.
 */
function configure()
{
    // update UI after map has been dragged
    google.maps.event.addListener(map, "dragend", function() {
        update();
    });

    // update UI after zoom level changes
    google.maps.event.addListener(map, "zoom_changed", function() {
        update();
    });

    // remove markers whilst dragging
    google.maps.event.addListener(map, "dragstart", function() {
        removeMarkers();
    });

    // configure typeahead
    // https://github.com/twitter/typeahead.js/blob/master/doc/jquery_typeahead.md
    $("#q").typeahead({
        autoselect: true,
        highlight: true,
        minLength: 1
    },
    {
        source: search,
        //displayKey: {<%- place_name %>, <%- admin_name1 %>},
        templates: {
            empty: "no places found yet",
            suggestion: _.template('<p><%- place_name %>, <%- admin_name1 %></p> <p style="display:inline; color:LightGray;"><%- postal_code %></p>')
            
            
            
        }
    });
    

    // re-center map after place is selected from drop-down
    $("#q").on("typeahead:selected", function(eventObject, suggestion, name) {

        // ensure coordinates are numbers
        var latitude = (_.isNumber(suggestion.latitude)) ? suggestion.latitude : parseFloat(suggestion.latitude);
        var longitude = (_.isNumber(suggestion.longitude)) ? suggestion.longitude : parseFloat(suggestion.longitude);

        // set map's center
        map.setCenter({lat: latitude, lng: longitude});

        // update UI
        update();
    });

    // hide info window when text box has focus
    $("#q").focus(function(eventData) {
        hideInfo();
    });

    // re-enable ctrl- and right-clicking (and thus Inspect Element) on Google Map
    // https://chrome.google.com/webstore/detail/allow-right-click/hompjdfbfmmmgflfjdlnkohcplmboaeo?hl=en
    document.addEventListener("contextmenu", function(event) {
        event.returnValue = true; 
        event.stopPropagation && event.stopPropagation(); 
        event.cancelBubble && event.cancelBubble();
    }, true);

    // update UI
    update();

    // give focus to text box
    $("#q").focus();
}

/**
 * Hides info window.
 */
function hideInfo()
{
    info.close();
}

/**
 * Removes markers from map.
 */
function removeMarkers()
{
    for(var i=0; i < markers.length; i++){
       markers[i].setMap(null);
     }
     markers.length = 0;
}

/**
 * Searches database for typeahead's suggestions.
 */
function search(query, cb)
{
    // get places matching query (asynchronously)
    var parameters = {
        geo: query
    };
    $.getJSON("search.php", parameters)
    .done(function(data, textStatus, jqXHR) {

        // call typeahead's callback with search results (i.e., places)
        cb(data);
    })
    .fail(function(jqXHR, textStatus, errorThrown) {

        // log error to browser's console
        console.log(errorThrown.toString());
    });
}

/**
 * Shows info window at marker with content.
 */
function showInfo(marker, content)
{
    //console.log(content);
    // start div
    var div = "<div id='info'>";
    if (typeof(content) === "undefined")
    {
        // http://www.ajaxload.info/
        div += "<img alt='loading' src='img/ajax-loader.gif'/>";
    }
    else
    {
        div += content;
    }

    // end div
    div += "</div>";

    // set info window's content
    info.setContent(div);

    // open info window (if not already open)
    info.open(map, marker);
}

/**
 * Updates UI's markers.
 */
function update() 
{
    // get map's bounds
    var bounds = map.getBounds();
    var ne = bounds.getNorthEast();
    var sw = bounds.getSouthWest();

    // get places within bounds (asynchronously)
    var parameters = {
        ne: ne.lat() + "," + ne.lng(),
        q: $("#q").val(),
        sw: sw.lat() + "," + sw.lng()
    };
    $.getJSON("update.php", parameters)
    .done(function(data, textStatus, jqXHR) {

        // remove old markers from map
        removeMarkers();

        // add new markers to map
        for (var i = 0; i < data.length; i++)
        {
            addMarker(data[i]);
        }
     })
     .fail(function(jqXHR, textStatus, errorThrown) {

         // log error to browser's console
         console.log(errorThrown.toString());
     });
}