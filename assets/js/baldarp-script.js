var markersArray = [];
var bounds;
var mainJson;
jQuery(document).ready(function() {
    if(jQuery('body').hasClass('woocommerce-cart')) {
        resetCargo();
    }
    if(jQuery('body').hasClass('woocommerce-checkout')) {
        //jQuery('#order_review').prepend('<div id="overlay" style="display:none;"><div class="spinner"></div></div>');
        jQuery('#order_review').prepend('<div class="blockUI" style=""></div>');
        jQuery("div.blockUI").attr('id','overlay')
        jQuery("div.blockUI").addClass('blockOverlay');
        jQuery("#mapbutton").parent('li').css("margin-bottom","auto");
    }
    
    jQuery.ajax({
        type: "post",
        url: baldarp_obj.ajaxurl,
        data: {action:"get_delivery_location"},
        success: function(msg){
            jQuery("div.blockUI").removeClass('blockOverlay');
            jQuery("div.blockUI").removeAttr('id')
            jQuery("div.blockUI").hide();

            var msgnew = JSON.parse(msg);
            if(msgnew.data == 0) {
                //alert("Something went wrong. Please refresh the page");
                return false;
            }
            mainJson = JSON.parse(msgnew.dataval);
            jQuery.each(mainJson, function(index) {
                if(mainJson[index].DistributionPointID == Cookies.get('cargoPointID')) {
                    jQuery('#DistributionPointID').val(mainJson[index].DistributionPointID);
                    jQuery('#DistributionPointName').val(mainJson[index].DistributionPointName);
                    jQuery('#CityName').val(mainJson[index].CityName);
                    jQuery('#StreetName').val(mainJson[index].StreetName);
                    jQuery('#StreetNum').val(mainJson[index].StreetNum);
                    jQuery('#Comment').val(mainJson[index].Comment);
                    jQuery('#cargoPhone').val(mainJson[index].Phone);
                    jQuery('#Latitude').val(mainJson[index].Latitude);
                    jQuery('#Longitude').val(mainJson[index].Longitude);
                }
            });
            // jQuery('#mapbutton').css('pointer-events','all');
            jQuery('#mapbutton').show()
            if(jQuery('#mapmodelcargo').is(":hidden")){
                if(jQuery("#shipping_method").length) {
                    addLocationSection(msgnew.shippingMethod);
                }
            }
        }
    });
    setTimeout(function(){
        if(jQuery("div.blockUI").hasClass('blockOverlay')) {
            jQuery("div.blockUI").removeClass('blockOverlay');
            jQuery("div.blockUI").removeAttr('id')
            jQuery("div.blockUI").hide();
        }
    },5000);
   // addLocationSection();
    
});

jQuery(document).on('click','.marker-click',function(){
    var markerID = jQuery(this).data('id');
    google.maps.event.trigger(markersArray[markerID], 'click');
});
function addLocationSection(shippingMethod){
   // console.log("Shipping value ",jQuery('input[name="shipping_method[0]"]:checked').val());
    if(shippingMethod.split(':')[0] == 'woo-baldarp-pickup') {
            if( Cookies.get('cargoPointID') != null ) {
                jQuery("#selected_cargo").html(decodeURIComponent(escape(atob(Cookies.get('fullAddress')))));
            } else {
                if (jQuery('#mapmodelcargo').is(":hidden")) {
                    checkLocationSet(shippingMethod);
                } else {
                    jQuery("#modal-close").trigger('click');
                }
            }

    }
}

function resetCargo() {
    Cookies.set("cargoLatitude","",{expires: -1,path: '/'});
    Cookies.set("cargoLongitude","",{expires: -1,path: '/'});
    Cookies.set("cargoPointID","",{expires: -1,path: '/'});
    Cookies.set("fullAddress","",{expires: -1,path: '/'});
    Cookies.set('cargoPointName',"",{expires: -1,path: '/'});
    Cookies.set('CargoCityName',"",{expires: -1,path: '/'});
    Cookies.set('cargoStreetName',"",{expires: -1,path: '/'});
    Cookies.set('cargoStreetNum',"",{expires: -1,path: '/'});
    Cookies.set('cargoComment',"",{expires: -1,path: '/'});
}
function changeShippimh(){
    setTimeout(function(){
        if(Cookies.get('cargoPointID') != null) {
            jQuery("#selected_cargo").html(decodeURIComponent(escape(atob(Cookies.get('fullAddress')))));
        }else{
            if(jQuery('#mapmodelcargo').is(":hidden")){
                checkLocationSet();
            }else{
                jQuery("#modal-close").trigger('click');
            }
        }
        jQuery('#mapbutton').css('pointer-events','all');
        //jQuery('#mapbutton').show();
        jQuery("div.blockUI").removeClass('blockOverlay');
        jQuery("div.blockUI").removeAttr('id');
        jQuery("div.blockUI").hide();
        jQuery("#mapbutton").parent('li').css("margin-bottom","auto");
    },5000);
}
function checkLocationSet(shippingMethod = '') {
    if(jQuery("#shipping_method").length) {
        if(shippingMethod != '') {
            if(shippingMethod.split(':')[0] == 'woo-baldarp-pickup') {
                if(jQuery('#mapmodelcargo').is(":hidden")){
                    if(Cookies.get('cargoPointID') == null) {
                       //setTimeout(function(){ 
                            //jQuery("#mapbutton").show();
                            jQuery('#mapbutton').css('pointer-events','all');
                            jQuery('#mapbutton').css('display','block');
                            jQuery('.wc_card_shipping_header_cargo').css('display','block');
                            // jQuery("#mapbutton").trigger('click')
                       //},2000); 

                    }
                }
               // jQuery('#mapbutton').show()
                jQuery('#mapbutton').css('pointer-events','all');
                jQuery("#mapbutton").parent('li').css("margin-bottom","auto");

            }
        } else {
            if(jQuery('input[name="shipping_method[0]"]:checked').val().split(':')[0] == 'woo-baldarp-pickup') {
                if(jQuery('#mapmodelcargo').is(":hidden")){
                    if(Cookies.get('cargoPointID') == null) {
                       //setTimeout(function(){ 
                            //jQuery("#mapbutton").show();
                            jQuery('#mapbutton').css('pointer-events','all');
                            jQuery('#mapbutton').css('display','block');
                            jQuery('.wc_card_shipping_header_cargo').css('display','block');
                        // jQuery("#mapbutton").trigger('click')
                       //},2000); 

                    }
                }
               // jQuery('#mapbutton').show()
                jQuery('#mapbutton').css('pointer-events','all');
                jQuery("#mapbutton").parent('li').css("margin-bottom","auto");

            }
        }
    }
}
jQuery(document).on('click','#modal-close',function () {
    jQuery('.modal').hide();
    if(Cookies.get('cargoPointID') == null) {
        // jQuery('.shipping_method').each(function(){
        //     if(jQuery(this).val().split(':')[0] != 'woo-baldarp-pickup'){
        //         jQuery(this).trigger('click');
        //     }   
        // });
    }
});
jQuery(document).on("click",'#modal-close-desc',function () {
     jQuery('.descript').hide();
});
jQuery(document).on('click','#mapbutton',function(e){
    e.preventDefault();
    google.maps.event.trigger(map, 'resize');
    initMap();
    jQuery('.modal').each(function(){
        if(!jQuery(this).hasClass('descript')){
            initAutocomplete();
            jQuery("#mapmodelcargo").show();
        }
    });
});
jQuery(document).on('click','.open-how-it-works',function(){
    jQuery(".descript").show();
});

jQuery(document).on('click','.tack-order-cus',function(){

    var orderID = jQuery(this).data('id');
    var customerId = '3175';
    jQuery.ajax({
        type: "post",
        url: baldarp_obj.ajaxurl,
        data: {action:"get_order_tracking_details",orderID : orderID,customerId:customerId},
        success: function(msg){
            jQuery('.order-details-ajax').html(msg);       
            jQuery('.order-tracking-model').show();
        }
    });
    
})


jQuery(document).on('updated_checkout', function() {
    jQuery('#cargo_city').select2();
    jQuery('#cargo_pickup_point').select2({minimumResultsForSearch: -1});
})
jQuery(document).on('change','.shipping_method', function() {
    var shippingMethod  = jQuery(this).val().split(':');

    if(shippingMethod[0] == 'woo-baldarp-pickup') {
        jQuery("div.blockUI").attr('id','overlay')
        jQuery("div.blockUI").addClass('blockOverlay');      
        jQuery("div.blockUI").show();
        // if(!jQuery('#mapbutton').length){
        // 	jQuery(this).parent('li').append('<span class="baldrap-btn" id="mapbutton" style="pointer-events: all;"> בחירת נקודה </span><div id="selected_cargo"></div><input type="hidden" id="DistributionPointID" name="DistributionPointID" value=""><input type="hidden" id="DistributionPointName" name="DistributionPointName" value=""><input type="hidden" id="CityName" name="CityName" value=""><input type="hidden" id="StreetName" name="StreetName" value=""><input type="hidden" id="StreetNum" name="StreetNum" value=""><input type="hidden" id="Comment" name="Comment" value=""><input type="hidden" id="cargoPhone" name="cargoPhone" value=""><input type="hidden" id="Latitude" name="Latitude" value=""><input type="hidden" id="Longitude" name="Longitude" value="">')
        // }

        jQuery("#mapbutton").show();
        jQuery("#selected_cargo").show();
        changeShippimh();
    } else {
        jQuery("#mapbutton").hide();
        jQuery("#selected_cargo").hide();
    }
});
jQuery(document).on('change', '#cargo_city', function() {
    Cookies.set('CargoCityName', jQuery(this).val(), {expires: 10,path: '/'})
    setTimeout(function() {
        jQuery( document.body ).trigger( 'update_checkout' );
    }, 100)

    // jQuery.ajax({
    //     type: "post",
    //     url: "https://api.carg0.co.il/Webservice/getPickUpPoints",
    //     processData: false,
    //     contentType: 'application/json',
    //     data: JSON.stringify({city : jQuery(this).val()}),
    //     beforeSend: function() {
    //       jQuery('#cargo_pickup_point').parent().hide();
    //     },
    //     success: function(response){
    //         console.log(response);
    //
    //         if ( response.Result === 'OK' ) {
    //             let html = '';
    //             response.PointsDetails.forEach(option => {
    //                 html += `<option value="${option.DistributionPointID}">${option.DistributionPointName}, ${option.StreetName} ${option.StreetNum}</option>`;
    //             })
    //             jQuery('#cargo_pickup_point').empty().html(html);
    //             jQuery('#cargo_pickup_point').select2({minimumResultsForSearch: -1});
    //             jQuery('#cargo_pickup_point').parent().show();
    //         }
    //     },
    //     error: function(x,r,c) {
    //         alert(r);
    //     }
    // });
})
jQuery(document).on('change', '#cargo_pickup_point', function() {
    Cookies.set('cargoPointID', jQuery(this).val(), {expires: 10,path: '/'})
    Cookies.set('CargoCityName', jQuery('#cargo_city option:selected').attr('value'), {expires: 10,path: '/'})
    setTimeout(function() {
        jQuery(document.body).trigger('update_checkout');
    }, 100);

    // jQuery.ajax({
    //     type: "post",
    //     url: "https://api.carg0.co.il/Webservice/getPickUpPoints",
    //     processData: false,
    //     contentType: 'application/json',
    //     data: JSON.stringify({pointId : jQuery(this).val()}),
    //     beforeSend: function() {
    //     },
    //     success: function(response){
    //         console.log(response);
    //
    //         if ( response.Result === 'OK' ) {
    //             jQuery('#DistributionPointID').val(response.DistributionPointID);
    //             jQuery('#DistributionPointName').val(response.DistributionPointName);
    //             jQuery('#CityName').val(response.CityName);
    //             jQuery('#StreetName').val(response.StreetName);
    //             jQuery('#StreetNum').val(response.StreetNum);
    //             jQuery('#Comment').val(response.Comment);
    //             jQuery('#cargoPhone').val(response.Phone);
    //             jQuery('#Latitude').val(response.Latitude);
    //             jQuery('#Longitude').val(response.Longitude);
    //             Cookies.set('cargoLatitude', response.Latitude, {expires: 10,path: '/'});
    //             Cookies.set('cargoLongitude', response.Longitude,{expires: 10,path: '/'});
    //             Cookies.set('cargoPointID', response.DistributionPointID, {expires: 10,path: '/'})
    //             Cookies.set('cargoPointName', response.DistributionPointName, {expires: 10,path: '/'})
    //             Cookies.set('CargoCityName', response.CityName, {expires: 10,path: '/'})
    //             Cookies.set('cargoStreetName', response.StreetName, {expires: 10,path: '/'})
    //             Cookies.set('cargoStreetNum', response.StreetNum, {expires: 10,path: '/'})
    //             Cookies.set('cargoComment', response.Comment, {expires: 10,path: '/'})
    //             Cookies.set('cargoPhone', response.Phone, {expires: 10,path: '/'})
    //             Cookies.set('fullAddress', response.StreetName,{expires: 10,path: '/'})
    //         } else {
    //             alert(response.error_msg);
    //         }
    //     },
    //     error: function(x,r,c) {
    //         console.log(x);
    //         console.log(r);
    //         console.log(c);
    //     }
    // });

})


jQuery(document).on('submit','form.checkout', function(event) {
    jQuery('.shipping_method').each(function(){
        if(jQuery('input[name="shipping_method[0]"]:checked').val().split(':')[0] == 'woo-baldarp-pickup') {
            if(Cookies.get('cargoPointID') == null) {
                return false;
            }else{
                alert("click");
                return false;
            }
        }
    });
});
function fillInAddress() {
    // Get the place details from the autocomplete object.
    var place = autocomplete.getPlace();
    var lat = place.geometry.location.lat();
    var lng = place.geometry.location.lng();
    var R = 6371;
    var distances = [];
    var closest = -1;
    for( i=0;i<markersArray.length; i++ ) {
        var mlat = markersArray[i].position.lat();
        var mlng = markersArray[i].position.lng();
        //return false;
        var dLat  = toRad(mlat - lat);
        var dLong = toRad(mlng - lng);
        var a = Math.sin(dLat/2) * Math.sin(dLat/2) +
            Math.cos(toRad(lat)) * Math.cos(toRad(lat)) * Math.sin(dLong/2) * Math.sin(dLong/2);
        var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        var d = R * c;
        distances[i] = d;
        if ( closest == -1 || d < distances[closest] ) {
            closest = i;
        }
    }
    google.maps.event.trigger(markersArray[closest], 'click');
  }
  function toRad(Value) 
  {
      return Value * Math.PI / 180;
  }
function initAutocomplete() {
    // Create the autocomplete object, restricting the search to geographical
    // location types.
    autocomplete = new google.maps.places.Autocomplete((document.getElementById('search-input-cus')),
        {types: ['geocode']}
    );

    autocomplete.addListener('place_changed', fillInAddress);
  }
/* Init Google map on the modal popup */
function initMap() {
    if(Cookies.get('cargoPointID') != null) {
        var myloc = new google.maps.LatLng(Cookies.get('cargoLatitude'),Cookies.get('cargoLongitude'));
        jQuery("#selected_cargo").html(decodeURIComponent(escape(atob(Cookies.get('fullAddress')))));
        jQuery("#FlyingCargo_loc_name").html(decodeURIComponent(escape(atob(Cookies.get('fullAddress')))));
        jQuery("#selected_cargo").show();

    }else{
        var myloc = new google.maps.LatLng(32.4631, 35.0433);
    }
    var mapOptions = {
        zoom: 10,
        center: myloc,
        scrollwheel: false,
        gestureHandling:'greedy'
    };
    const styledMapType = new google.maps.StyledMapType(
        [
        {
            "featureType": "water",
            "stylers": [
                {
                    "saturation": 43
                },
                {
                    "lightness": -11
                },
                {
                    "hue": "#0088ff"
                }
            ]
        },
        {
            "featureType": "road",
            "elementType": "geometry.fill",
            "stylers": [
                {
                    "hue": "#ff0000"
                },
                {
                    "saturation": -100
                },
                {
                    "lightness": 99
                }
            ]
        },
        {
            "featureType": "road",
            "elementType": "geometry.stroke",
            "stylers": [
                {
                    "color": "#808080"
                },
                {
                    "lightness": 54
                }
            ]
        },
        {
            "featureType": "landscape.man_made",
            "elementType": "geometry.fill",
            "stylers": [
                {
                    "color": "#ece2d9"
                }
            ]
        },
        {
            "featureType": "poi.park",
            "elementType": "geometry.fill",
            "stylers": [
                {
                    "color": "#ccdca1"
                }
            ]
        },
        {
            "featureType": "road",
            "elementType": "labels.text.fill",
            "stylers": [
                {
                    "color": "#767676"
                }
            ]
        },
        {
            "featureType": "road",
            "elementType": "labels.text.stroke",
            "stylers": [
                {
                    "color": "#ffffff"
                }
            ]
        },
        {
            "featureType": "poi",
            "stylers": [
                {
                    "visibility": "off"
                }
            ]
        },
        {
            "featureType": "landscape.natural",
            "elementType": "geometry.fill",
            "stylers": [
                {
                    "visibility": "on"
                },
                {
                    "color": "#dde3e3"
                }
            ]
        },
        {
            "featureType": "poi.park",
            "stylers": [
                {
                    "visibility": "on"
                }
            ]
        },
        {
            "featureType": "poi.sports_complex",
            "stylers": [
                {
                    "visibility": "on"
                }
            ]
        },
        {
            "featureType": "poi.medical",
            "stylers": [
                {
                    "visibility": "on"
                }
            ]
        },
        {
            "featureType": "poi.business",
            "stylers": [
                {
                    "visibility": "simplified"
                }
            ]
        }
    ],
     { name: "Styled Map" }
    );
    var map = new google.maps.Map(document.getElementById('map'),mapOptions);
    
    var infowindow = new google.maps.InfoWindow();
    var marker, i;
    map.mapTypes.set("styled_map", styledMapType);
    map.setMapTypeId("styled_map");

    var geocoder = new google.maps.Geocoder();
    var address = "Harimon Tirat Yehuda 7317500";

    geocoder.geocode( { 'address': address}, function(results, status) {

        if (status == google.maps.GeocoderStatus.OK) {
            var latitude = results[0].geometry.location.lat();
            var longitude = results[0].geometry.location.lng();
        } 
    });
    jQuery.each(mainJson, function(index) {
        marker = new google.maps.Marker({
            map: map,
            position: new google.maps.LatLng(mainJson[index].Latitude, mainJson[index].Longitude),
        });
        icon = {
            url: jQuery('#default_markers').val(), // url
            scaledSize: new google.maps.Size(60, 60), // scaled size                       
        };     
        marker.setIcon(icon);
        markersArray.push(marker);
        google.maps.event.addListener(marker, 'click', (function(marker,i)  {
            return function() {
                infowindow.close();
                clearSelectedMarker();

                infowindow = new google.maps.InfoWindow({content: '<div id="content'+mainJson[index].DistributionPointID+'" style="dir:rtl; text-align:right;max-width:240px;margin-right:10px;">'+
                '<div id="siteNotice">'+
                '</div>'+
                '<h1 id="firstHeading" class="firstHeading"><h5>'+mainJson[index].DistributionPointName+'</h5>'+
                '<div id="bodyContent">'+
                '<p>'+mainJson[index].StreetNum+' , '+mainJson[index].StreetName+' '+mainJson[index].CityName+' '+mainJson[index].Phone+' '+mainJson[index].Comment+' </p>'+
                '</div>'+
                '<button type="button" class="selected-location" id="FlyingCargo_confirm" data-lat="'+mainJson[index].Latitude+'" data-long="'+mainJson[index].Longitude+'" data-fulladd="'+btoa(unescape(encodeURIComponent('<div>'+mainJson[index].DistributionPointName+'</div> <div>'+mainJson[index].StreetNum+' , '+mainJson[index].StreetName+' '+mainJson[index].CityName+' '+mainJson[index].Phone+' '+mainJson[index].Comment+'</div>')))+'" data-disctipointid="'+mainJson[index].DistributionPointID+'" data-pointname="'+mainJson[index].DistributionPointName+'" data-city="'+mainJson[index].CityName+'" data-street="'+mainJson[index].StreetName+'" data-streetnum="'+mainJson[index].StreetNum+'" data-comment="'+mainJson[index].Comment+'" data-locationname="'+mainJson[index].DistributionPointName+'" data-cargoph="'+mainJson[index].Phone+'">בחירה וסיום</button>'+
                '</div>' });
                infowindow.open(map,marker);
                const icon = {
                    url: jQuery('#selected_marker').val(), // url
                    scaledSize: new google.maps.Size(60, 60), // scaled size                       
                };

                marker.setIcon(icon); 
                map.setZoom(12);
                map.setCenter(marker.getPosition());
                // jQuery('#FlyingCargo_footer').show();
                // jQuery("#FlyingCargo_confirm").attr("data-lat",mainJson[index].Latitude);
                // jQuery("#FlyingCargo_confirm").attr("data-long",mainJson[index].Longitude);
                // jQuery("#FlyingCargo_confirm").attr("data-fullAdd",btoa(unescape(encodeURIComponent('<div>'+mainJson[index].DistributionPointName+'</div> <div>'+mainJson[index].StreetNum+' , '+mainJson[index].StreetName+' '+mainJson[index].CityName+' '+mainJson[index].Phone+' '+mainJson[index].Comment+'</div>'))));
                // jQuery("#FlyingCargo_confirm").attr("data-disctiPointID",mainJson[index].DistributionPointID);
                // jQuery("#FlyingCargo_confirm").attr("data-pointName",mainJson[index].DistributionPointName);
                // jQuery("#FlyingCargo_confirm").attr("data-city",mainJson[index].CityName);
                // jQuery("#FlyingCargo_confirm").attr("data-street",mainJson[index].StreetName);
                // jQuery("#FlyingCargo_confirm").attr("data-streetNum",mainJson[index].StreetNum);
                // jQuery("#FlyingCargo_confirm").attr("data-comment",mainJson[index].Comment);
                // jQuery("#FlyingCargo_confirm").attr("data-cargoPh",mainJson[index].Phone);
                // jQuery("#FlyingCargo_confirm").attr("data-locationName",mainJson[index].DistributionPointName);
                // jQuery('#FlyingCargo_loc_name').html('<div>'+mainJson[index].DistributionPointName+'</div> <div>'+mainJson[index].StreetNum+' , '+mainJson[index].StreetName+' '+mainJson[index].CityName+' '+mainJson[index].Phone+' '+mainJson[index].Comment+'</div>');
            }
        })(marker, i));
       // console.log("Marker Array",markersArray);
        if(Cookies.get('cargoPointID') != null) {
            if(mainJson[index].DistributionPointID == Cookies.get('cargoPointID')) {
                icon = {
                    url: jQuery('#selected_marker').val(), // url
                    scaledSize: new google.maps.Size(60, 60), // scaled size                       
                };
                marker.setIcon(icon);
            }
        }
        
    })  
}
/*Chaneg all the maker after select */
function clearSelectedMarker() {
    //console.log(markersArray);
    markersArray.forEach(function(marker) {
        //console.log('dsads');
        const icon = {
            url: jQuery('#default_markers').val(), // url
            scaledSize: new google.maps.Size(60, 60), // scaled size                       
        };
        marker.setIcon(icon);
    });
}
/*insert value after click on the confirm location*/
jQuery(document).on('click','#FlyingCargo_confirm',function () {
    jQuery('#FlyingCargo_loc_name').html(jQuery(this).attr("data-locationName"));
    jQuery('#selected_cargo').html(decodeURIComponent(escape(atob(jQuery(this).attr("data-fullAdd")))));
    jQuery('#DistributionPointID').val(jQuery(this).attr("data-disctiPointID"));
    jQuery('#DistributionPointName').val(jQuery(this).attr("data-pointName"));
    jQuery('#CityName').val(jQuery(this).attr("data-city"));
    jQuery('#StreetName').val(jQuery(this).attr("data-street"));
    jQuery('#StreetNum').val(jQuery(this).attr("data-streetNum"));
    jQuery('#Comment').val(jQuery(this).attr("data-comment"));
    jQuery('#cargoPhone').val(jQuery(this).attr("data-cargoPh"));
    jQuery('#Latitude').val(jQuery(this).attr("data-lat"));
    jQuery('#Longitude').val(jQuery(this).attr("data-long"));
    Cookies.set('cargoLatitude', jQuery(this).attr("data-lat"), {expires: 10,path: '/'});
    Cookies.set('cargoLongitude', jQuery(this).attr("data-long"),{expires: 10,path: '/'});
    Cookies.set('cargoPointID',jQuery(this).attr("data-disctiPointID"),{expires: 10,path: '/'})
    Cookies.set('cargoPointName',jQuery(this).attr("data-pointName"),{expires: 10,path: '/'})
    Cookies.set('CargoCityName',jQuery(this).attr("data-city"),{expires: 10,path: '/'})
    Cookies.set('cargoStreetName',jQuery(this).attr("data-street"),{expires: 10,path: '/'})
    Cookies.set('cargoStreetNum',jQuery(this).attr("data-streetNum"),{expires: 10,path: '/'})
    Cookies.set('cargoComment',jQuery(this).attr("data-comment"),{expires: 10,path: '/'})
    Cookies.set('cargoPhone',jQuery(this).attr("data-cargoPh"),{expires: 10,path: '/'})
    Cookies.set('fullAddress',jQuery(this).attr("data-fullAdd"),{expires: 10,path: '/'})
    jQuery('#selected_cargo').show();
    jQuery('.modal').hide();
    jQuery('#mapbutton').css('pointer-events','all');
   //jQuery('#mapbutton').show();
});