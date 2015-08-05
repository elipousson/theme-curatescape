<?php
// Build some custom data for Facebook Open Graph, Twitter Cards, general SEO, etc...

// SEO Page description
function mh_seo_pagedesc($item=null,$tour=null,$file=null){
	if($item != null){
		$itemdesc=snippet(mh_the_text($item),0,500,"...");
		return strip_tags($itemdesc);
	}elseif($tour != null){
		$tourdesc=snippet(tour('Description'),0,500,"...");
		return strip_tags($tourdesc);
	}elseif($file != null){
		$filedesc=snippet(metadata('file',array('Dublin Core', 'Description')),0,500,"...");
		return strip_tags($filedesc);
	}else{
		return mh_seo_sitedesc();
	}
}

// SEO Site description
function mh_seo_sitedesc(){
	return mh_about() ? strip_tags(mh_about()) : strip_tags(option('description'));
}

// SEO Page Title
function mh_seo_pagetitle($title,$item){
	$subtitle=$item ? (mh_the_subtitle($item) ? ' - '.mh_the_subtitle($item) : null) : null;
	$pt = $title ? $title.$subtitle.' | '.option('site_title') : option('site_title');
	return strip_tags($pt);
}

// SEO Page image
function mh_seo_pageimg($item=null,$file=null){
	if($item){
		if(metadata($item, 'has thumbnail')){
			$itemimg=item_image('square_thumbnail') ;
			preg_match('/<img(.*)src(.*)=(.*)"(.*)"/U', $itemimg, $result);
			$itemimg=array_pop($result);
		}
	}elseif($file){
		if($itemimg=file_image('square_thumbnail') ){
			preg_match('/<img(.*)src(.*)=(.*)"(.*)"/U', $itemimg, $result);
			$itemimg=array_pop($result);
		}
	}
	return isset($itemimg) ? $itemimg : mh_lg_logo_url();
}

// Get theme CSS link with version number
function mh_theme_css($media='all'){
	$themeName = Theme::getCurrentThemeName();
	$theme = Theme::getTheme($themeName);
	return '<link href="'.WEB_PUBLIC_THEME.'/'.$themeName.'/css/screen.css?v='.$theme->version.'" media="'.$media.'" rel="stylesheet" type="text/css" >';
}

function mh_item_label_option($which=null){
	if($which=='singular'){
		return ($singular=get_theme_option('item_label_singular')) ? $singular : __('Story');
	}
	elseif($which=='plural'){
		return ($plural=get_theme_option('item_label_plural')) ? $plural : __('Stories');
	}
}

function mh_tour_label_option($which=null){
	if($which=='singular'){
		return ($singular=get_theme_option('tour_label_singular')) ? $singular : __('Tour');
	}
	elseif($which=='plural'){
		return ($plural=get_theme_option('tour_label_plural')) ? $plural : __('Tours');
	}
}

/*
** Item Labels
*/
function mh_item_label($which=null){
	if($which=='plural'){
		return mh_item_label_option('plural');
	}else{
		return mh_item_label_option('singular');
	}
}

/*
** Tour Labels
*/
function mh_tour_label($which=null){
	if($which=='plural'){
		return mh_tour_label_option('plural');
	}else{
		return mh_tour_label_option('singular');
	}
}

/*
** Tour Header on homepage
*/
function mh_tour_header(){
	if($text=get_theme_option('tour_header')){
		return $text;
	}else{
		return __('Take a %s', mh_tour_label_option('singular'));
	}
}
/*
** Global navigation
*/
function mh_global_nav(){
	$curatenav=get_theme_option('default_nav');
	if( $curatenav==1 || !isset($curatenav) ){
		return nav(array(
				array('label'=>__('Home'),'uri' => url('/')),
				array('label'=>mh_item_label('plural'),'uri' => url('items/browse')),
				array('label'=>mh_tour_label('plural'),'uri' => url('tours/browse/')),
				array('label'=>__('About'),'uri' => url('about/')),
			));
	}else{
		return public_nav_main();
	}
}

/*
** Get the correct logo for the page
** uses body class to differentiate between home, stealth-home, and other
*/
function mh_the_logo(){
	if ( ($bodyid='home') && ($bodyclass='public') ) {
		return '<img src="'.mh_lg_logo_url().'" class="home" id="logo-img" alt="'.option('site_title').'"/>';
	}elseif( ($bodyid='home') && ($bodyclass='stealth-mode') ){
		return '<img src="'.mh_stealth_logo_url().'" class="stealth" id="logo-img" alt="'.option('site_title').'"/>';
	}else{
		return '<img src="'.mh_med_logo_url().'" class="inner" id="logo-img" alt="'.option('site_title').'"/>';
	}
}

/*
** Link to Random item
*/

function random_item_link($text=null,$class='show'){
	if(!$text){
		$text= __('View a Random %s', mh_item_label('singular'));
	}

	$link = '';
	$randitems = get_records('Item', array( 'sort_field' => 'random', 'hasImage' => true), 1);
	$linkclass = 'random-story-link ' . $class;
	
	if( count( $randitems ) > 0 ){
		$link = link_to( $randitems[0], 'show', $text,
			array( 'class' => $linkclass ) );
	}else{
		$link = link_to( '/', 'show', __('Publish some items to activate this link'),
			array( 'class' => $linkclass ) );
	}
	return $link;

}


/*
** Global header
** includes nav, logo, search bar
** site title h1 is visually hidden but included for semantic purposes and screen readers
*/
function mh_global_header($html=null){
	$html.= '<div id="mobile-menu-button"><a class="icon-reorder"><span class="visuallyhidden"> '.__('Menu').'</span></a></div>';
	$html.= link_to_home_page(mh_the_logo(),array('class'=>'home-link'));
	$html.= '<div class="menu" role="menu">'.mh_simple_search($formProperties=array('id'=>'header-search')).'<nav role="navigation">'.mh_global_nav().random_item_link().'</nav></div>';

	return $html;

}

/*
** Removes pagination query param from request URI
** Prevents empty map results on non-first search result pages
** Usage: query_param_minus_pagination($_SERVER['REQUEST_URI'])
** See: mh_display_map()
*/
function query_param_minus_pagination($s=null,$n=null){

	$s=explode('&',$s);
	foreach($s as $key => $value){
		if( (substr( $value, 0, 5 ) === "page=") == false ){ 
			$n[]=$value;
	    }
	}
	return implode('&',$n);    
	
}	

/*
** Tour JSON
** simple JSON array for use in front-end map-building, etc...
*/
function mh_get_tour_json($tour=null){
			
		if($tour){
			
			$tourItems=array();
			
			foreach($tour->Items as $item){
				$location = get_db()->getTable( 'Location' )->findLocationByItem( $item, true );
				$address = ( element_exists('Item Type Metadata','Street Address') ) 
			? metadata( $item, array( 'Item Type Metadata','Street Address' )) : null;
				if($location){
					$tourItems[] = array(
						'id'		=> $item->id,
						'title'		=> addslashes(metadata($item,array('Dublin Core','Title'))),
						'address'	=> addslashes($address),
						'latitude'	=> $location[ 'latitude' ],
						'longitude'	=> $location[ 'longitude' ],
						);
					}
			}
		    
			$tourMetadata = array(
			     'id'           => $tour->id,
			     'items'        => $tourItems,
			     );
				 
			return json_encode($tourMetadata);
		
		}	
}


/*
** Item JSON	
** simple JSON array for use in front-end map-building, etc...
*/
function mh_get_item_json($item=null){
			
		if($item){
		
			$location = get_db()->getTable( 'Location' )->findLocationByItem( $item, true );
			
			$address= ( element_exists('Item Type Metadata','Street Address') ) 
			? metadata( 'item', array( 'Item Type Metadata','Street Address' )) : null;
			
			$title=html_entity_decode( strip_formatting( metadata( 'item', array( 'Dublin Core', 'Title' ))));
			
			if(metadata($item, 'has thumbnail')){
				$thumbnail = (preg_match('/<img(.*)src(.*)=(.*)"(.*)"/U', item_image('square_thumbnail'), $result)) 
				? array_pop($result) : null;
				}else{$thumbnail=null;}
							
			if($location){
				$itemMetadata = array(
					'id'          => $item->id,
					'featured'    => $item->featured,
					'latitude'    => $location[ 'latitude' ],
					'longitude'   => $location[ 'longitude' ],
					'title'       => addslashes($title),
					'address'	  => addslashes($address),
					'thumbnail'   => $thumbnail,
				);		
				
				return json_encode($itemMetadata);

			}	
		}	
}

/*
** Current Item View JSON 
** simple JSON array for use in front-end map-building, etc...
** NOT CURRENTLY USED
*/
function mh_get_multiple_items_json(){
	
	$results=has_loop_records('items') ? get_loop_records('items') : null;
	
	if($results){
		$i=0;
		$itemResults=array();
		foreach ($results as $item){
			
			$location = get_db()->getTable( 'Location' )->findLocationByItem( $item, true );
			
			$address= ( element_exists('Item Type Metadata','Street Address') ) 
			? metadata( $item , array( 'Item Type Metadata','Street Address' )) : null;
			
			if(metadata($item, 'has thumbnail')){
				$thumbnail = preg_match('/<img(.*)src(.*)=(.*)"(.*)"/U', record_image($item,'square_thumbnail'), $result)
				? array_pop($result)
				: null;
			}else{
				$thumbnail=null;
				}
				
			if($location){
				$itemResults[]=array(
					'id'=>$item->id,
					'featured'    => $item->featured,
					'latitude'    => $location[ 'latitude' ],
					'longitude'   => $location[ 'longitude' ],				
					'thumbnail'=>record_image($item,'square_thumbnail'),
					'address'=>$address,
				);	
			}	
			$i++;	
		
		}
		
		$resultsMeta=array(
			'total'=>$i,
			'items'=>$itemResults,
			);
		
		return json_encode($resultsMeta);
	}
	
}

/*
** Decide which content to display in hero container in header.php
** Uses variable set in page templates via head() function
*/
function mh_which_content($maptype='none',$item=null,$tour=null){

	$loading = '<img id="hero_loading" src="'.img('map_loading.gif').'">';

	if ($maptype == 'focusarea') {
		return $loading.mh_display_map('focusarea',null,null);
	}
	elseif ($maptype == 'story') {
		return $loading.mh_display_map('story',$item,null,null);
	}
	elseif ($maptype == 'queryresults') {
		return $loading.mh_display_map('queryresults',null,null);
	}
	elseif ($maptype == 'tour') {
		return $loading.mh_display_map('tour',null,$tour);
	}
	elseif ($maptype == 'none') {
		return null;
	}
	else {
		return null;
	}
}


/*
** Render the map using Google Maps API via jQuery-UI-Map http://code.google.com/p/jquery-ui-map/
** Source feed generated from Mobile JSON plugin
** Location data (LatLon and Zoom) created and stored in Omeka using stock Geolocation plugin
** Per_page limits overridden, set to 999
*/
function mh_display_map($type=null,$item=null,$tour=null){
	$pluginlng=get_option( 'geolocation_default_longitude' );
	$pluginlat=get_option( 'geolocation_default_latitude' );
	$plugincenter = $pluginlat .','. $pluginlng;
	$zoom=(get_option('geolocation_default_zoom_level')) ? get_option('geolocation_default_zoom_level') : 12;

	switch($type){

	case 'focusarea':
		/* all stories, map is centered on focus area (plugin center) */
		$json_source=WEB_ROOT.'/items/browse?output=mobile-json&per_page=999';
		break;

	case 'global':
		/* all stories, map is bounded according to content */
		$json_source=WEB_ROOT.'/items/browse?output=mobile-json&per_page=999';
		break;

	case 'queryresults':
		/* browsing by tags, subjects, search results, etc, map is bounded according to content */
		$uri=$_SERVER['REQUEST_URI'];
		$uri=$uri.'&output=mobile-json&per_page=999';
		$json_source=WEB_ROOT.query_param_minus_pagination($uri);
		break;		

	case 'story':
		/* single story */
		$json_source = ($item) ? mh_get_item_json($item) : null;
		break;

	case 'tour':
		/* single tour, map is bounded according to content  */
		$json_source= ($tour) ? mh_get_tour_json($tour) : null;
		break;

	default:
		$json_source=WEB_ROOT.'/items/browse?output=mobile-json&per_page=999';
	}

	if(get_theme_option('custom_marker')){
		$marker='/files/theme_uploads/'.get_theme_option('custom_marker');
	}else{
		$marker='/themes/curatescape/images/marker.png';
	}
?>
		<script type="text/javascript">

		var type =  '<?php echo $type ;?>';
		var mapstyle = '<?php echo 'google.maps.MapTypeId.'.get_theme_option('map_style') ;?>';

		var root = '<?php echo WEB_ROOT ;?>';
		var source ='<?php echo $json_source ;?>';
		
		var center ='<?php echo $plugincenter ;?>';
		var zoom = <?php echo $zoom ;?>;

		var marker = root+"<?php echo $marker ;?>";

		var fallbacklat='<?php echo $pluginlat ;?>';
		var fallbacklng='<?php echo $pluginlng ;?>';
		var fallbackmarker=null;
		var use_featured_marker=<?php echo get_theme_option('featured_marker') ? 'true' : 'false';?>;
		var featured_marker=root+"<?php echo '/files/theme_uploads/'.get_theme_option('featured_marker');?>";

		jQuery(document).ready(function() {

		jQuery('#hero_loading').fadeIn('slow');

		/* setup the default map */
		jQuery('#map_canvas').gmap({
			'center': center,
			'zoom': zoom,
			'mapTypeId': eval(mapstyle),
			'disableDefaultUI':false,
			'zoomControl': true,
			'zoomControlOptions': {
			  'style': google.maps.ZoomControlStyle.SMALL,
			  'position': google.maps.ControlPosition.TOP_RIGHT
			},
		    'streetViewControl': true,
		    'streetViewControlOptions': {
			  'style': google.maps.ZoomControlStyle.SMALL,
			  'position': google.maps.ControlPosition.TOP_RIGHT
		    }
		}).bind('init', function() {

			if(type == 'story'){
			
				var makemap=function(source) {
					    
					    var data = jQuery.parseJSON(source);
					    
						var lat=data.latitude;
						var lng=data.longitude;
						var thumbnail=data.thumbnail ? '<a class="item-media" href="#item-media"><img style="width:7em;height:7em;" src="'+data.thumbnail+'"></a>' : '';
						var iw_address = data.address ? data.address : null;
						var iw_location= iw_address ? iw_address.substring(0,30)+'&hellip;' : lat+','+lng;
						var access_info=(jQuery('#access-info h3').length) ? '<a class="access-anchor" href="#access-info"><span class="icon-exclamation-circle" aria-hidden="true"></span> <?php echo __('Access Information');?></a> ' : '';
						
						var map_faq='<span class="faq"><span class="icon-question-circle" aria-hidden="true"></span> <a href="#map-faq" class="fancybox">'+(access_info ? '<?php echo __('Map FAQ');?>' : '<?php echo __('Read the Map FAQ');?>')+'</a></span><br>';
											
						var infowindow_footer='<div class="infowindow_footer"><small>'+access_info+map_faq+'<span class="truncate coords"><span class="icon-map-marker" aria-hidden="true"></span> '+iw_location+'</span></small></div>';
						
						var marker_html = '<div class="marker-content ' + ( (data.thumbnail) ? 'has-image' : '' ) + '"><div class="marker-title">'+thumbnail+data.title+'</div>'+infowindow_footer+'</div>';
	
						
						if( (!lat) || (!lng) ){
							lat= fallbacklat;
							lng= fallbacklng;
							marker= fallbackmarker;
							jQuery('body').addClass('no-location');
						};
						
	
						var newmarker = jQuery('#map_canvas').gmap('addMarker', {
							'id':'single',
							'position': new google.maps.LatLng(lat, lng),
							'bounds': true,
							'icon': new google.maps.MarkerImage(marker),				
						});
						
						
						newmarker.click(function() {
							jQuery('#map_canvas').gmap('openInfoWindow', { 'content': marker_html }, this);
						});
						

					};
								
					jQuery.when(makemap(source)).done(function() {
						jQuery('#map_canvas').gmap('option', 'zoom', 15);						
					});
			
			}else if(type == 'tour'){

	
				var bounds = true;
				var path=window.location.pathname;
				var n=0;
				
				function makemap(source) {

					var data = jQuery.parseJSON(source);
							
					jQuery.each( data.items, function(i, item) {
					
						var lat=item.latitude;
						var lng=item.longitude;
						var iw_address = item.address ? item.address : null;
						var iw_location= iw_address ? iw_address.substring(0,30)+'&hellip;' : lat+','+lng;
											
						var tour_stop_num = '<span class="number">'+(n+1)+'</span> ';
						var tour_nav_append_to_link = '?tour='+data.id+'&index='+n;
						n++;
	
						var item_href=root + '/items/show/' + item.id;
						
						var infowindow_footer='<div class="infowindow_footer"><small><span class="faq"><span class="icon-question-circle" aria-hidden="true"></span> <a href="#map-faq" class="fancybox"><?php echo __('Read the Map FAQ');?></a></span><br><span class="truncate coords"><span class="icon-map-marker" aria-hidden="true"></span> '+iw_location+'</span></small></div>';
						
						var marker_html='<div class="marker-content"><div class="marker-title"><a href="' + item_href + tour_nav_append_to_link + '">'+ tour_stop_num + item.title + '</a></div>'+infowindow_footer+'</div>';
	
						jQuery('#map_canvas').gmap('addMarker', {
							'position': new google.maps.LatLng(item.latitude, item.longitude),
							'bounds': bounds,
							'icon': new google.maps.MarkerImage(marker),
							
						}).click(function() {
							jQuery('#map_canvas').gmap('openInfoWindow', { 'content': marker_html }, this);
						});
					});
				} makemap(source);




				
				}else{
					
	
					var bounds = (type == 'focusarea') ? false : true;
					var makemap = jQuery.getJSON( source, function(data) {
				


						jQuery.each( data.items, function(i, item) {
							
	
						if( (use_featured_marker==true) && (item.featured == 1) ){
							var browse_marker= featured_marker;
							var featured_icon='<span class="featured"><span class="icon-star" aria-hidden="true"></span> Featured</span><br>' ;
						}else{
							var browse_marker=marker;
							var featured_icon='';
						}
						var item_href=root + '/items/show/' + item.id;
						var marker_subtitle = item.subtitle ? '<br><div class="marker-subtitle">'+item.subtitle+'</div>' : '';
						var thumbnail=item.thumbnail ? '<a class="item-media" href="'+item_href+'"><img style="width:7em;height:7em;" src="'+item.thumbnail+'"></a>' : '';
						
						var iw_address = item.address ? item.address : null;
						var iw_location= iw_address ? iw_address.substring(0,30)+'&hellip;' : item.latitude+','+item.longitude;					
						var infowindow_footer='<div class="infowindow_footer"><small>' + featured_icon + '<span class="faq"><span class="icon-question-circle" aria-hidden="true"></span> <a href="#map-faq" class="fancybox"><?php echo __('Read the Map FAQ');?></a></span><br><span class="truncate coords"><span class="icon-map-marker" aria-hidden="true"></span> '+iw_location+'</span></small></div>';
						
						var marker_html='<div class="marker-content '+ ( item.thumbnail ? 'has-image' : '' ) +'"><div class="marker-title">'+thumbnail+'<a href="' + item_href + '">' + item.title + '</a></div>'+infowindow_footer+'</div>'
	
						jQuery('#map_canvas').gmap('addMarker', {
							'position': new google.maps.LatLng(item.latitude, item.longitude),
							'bounds': bounds,
							'icon': new google.maps.MarkerImage(browse_marker),
							
						}).click(function() {
							jQuery('#map_canvas').gmap('openInfoWindow', { 'content': marker_html }, this);
						});
						
						});
						


					});
					
							
				}
			
			var map=jQuery('#map_canvas').gmap('get', 'map');
			google.maps.event.addListener(map,'geolocation', function() {
				// Close the infoWindow(s) when map bounds change for user geolocation
				jQuery('#map_canvas').gmap('closeInfoWindow');
			});					
			google.maps.event.addListenerOnce(map,'idle', function() {
				// Fade the loading image as soon as the map data has been delivered
				jQuery('#hero_loading').fadeOut('slow');
				google.maps.event.addListenerOnce(map,'tilesloaded', function() {
					// Open the single item infoWindow after the map is done loading
					jQuery('.big #map_canvas').gmap('find', 'markers', { 'property': 'id', 'value': 'single' }, 
					function(marker, found) {
						if(marker.id=='single'){
							jQuery(marker).triggerEvent('click');
							}
					});
				});
			});	
		});
		});

        </script>
		<div id="hm-map">
			<div id="map_canvas">
			</div>
		</div>
<?php }

/*
** Add the map actions toolbar
*/
function mh_map_actions($item=null,$tour=null,$saddr='current',$coords=null){
	
		$show_directions=null;
		$street_address=null;
		
		if($item!==null){
			
			// get the destination coordinates for the item
			$location = get_db()->getTable('Location')->findLocationByItem($item, true);
			$coords=$location[ 'latitude' ].','.$location[ 'longitude' ];
			$street_address=mh_street_address($item,false);
			
			$show_directions = true;
		
		}elseif($tour!==null){
			
			// get the waypoint coordinates for the tour
			$coords = array();
			foreach( $tour->Items as $item ){
				
				set_current_record( 'item', $item );
				$location = get_db()->getTable('Location')->findLocationByItem($item, true);							$street_address=mh_street_address($item,false);
				$coords[] = $street_address ? urlencode($street_address) : $location['latitude'].','.$location['longitude'];
			}
			
			$daddr=end($coords);
			reset($coords);
			$waypoints=array_pop($coords);		
			$waypoints=implode('+to:', $coords);
			$coords=$daddr.'+to:'.$waypoints;	
			
			$show_directions=get_theme_option('show_tour_dir');
			
		}
	
	?>
	
	<div class="map-actions clearfix">
		

		<!-- Fullscreen -->
		<a onclick="toggle_fullscreen();" class="fullscreen"><span class="icon-expand" aria-hidden="true"></span> <span class="label"><?php echo __('Fullscreen Map');?></span><span class="alt"><?php echo __('Map');?></span></a>
		
				
		<!-- Geolocation -->
		<a onclick="get_user_location();" class="location"><span class="icon-location-arrow" aria-hidden="true"></span> <span class="label"><?php echo __('Show Current Location');?></span><span class="alt"><?php echo __('My Location');?></span></a> 
		
		<!-- Directions link -->
		<?php
		$directions_link= ($show_directions==1) ? '<a onclick="jQuery(\'body\').removeClass(\'fullscreen-map\')" class="directions" title="'.__('Get Directions on Google Maps').'" target="_blank" href="https://maps.google.com/maps?saddr='.$saddr.'+location&daddr='.($street_address ? urlencode($street_address) : $coords).'"><span class="icon-external-link-square" aria-hidden="true"></span> <span class="label">'.__('Get Directions').'</span><span class="alt">'.__('Directions').'</span></a> ' : null;	
		echo ( $coords && ($item || $tour) ) ? $directions_link : null;	
		?>
		
	
	</div>
	
	<script>

		function toggle_fullscreen(){	
			jQuery('#slider').slideToggle('fast', 'linear');
			jQuery('#swipenav').slideToggle('fast', 'linear');	
			jQuery('.small #map_canvas').toggle(); // in case it's hidden by checkwidth.js
			jQuery("body").toggleClass("fullscreen-map");
			jQuery(".map-actions a.fullscreen i").toggleClass('icon-expand').toggleClass('icon-compress');
			var map = jQuery('#map_canvas').gmap('get', 'map');
			var center = map.getCenter();
			google.maps.event.trigger(map, "resize");
			map.panTo(center);		
		}
	
	
	
	
		function get_user_location(){	

			var options = {
			  enableHighAccuracy: true,
			  timeout: 15000,
			  maximumAge: 30000
			};
		
				
			function error(err) {
			  				  
				  console.warn(err.message);
				  
				  alert('Oops! Something went wrong.\n\n'+err.message);
				  
				  jQuery('.map-actions a.location i').removeClass('working')
				  	.toggleClass('icon-location-arrow').toggleClass('icon-spinner'); 
			  
			};
		
			function success(position) {
										
				var newLatLng = new google.maps.LatLng(position.coords.latitude , position.coords.longitude); 
				
				
				var marker = new google.maps.Marker({
				    position: newLatLng,
				    map: map,
				    icon: {
				      path: google.maps.SymbolPath.CIRCLE,
				      scale: 10,
				      strokeColor: '#FFFFFF',
				      strokeOpacity: 1.0,
				      strokeWeight: 4,
				      fillColor: '#4285F4',
				      fillOpacity: 1.0,
				    },
		
				});		
							
				google.maps.event.trigger(map, "geolocation");
				marker.setPosition(newLatLng);
				map.panTo(newLatLng);
				var zoom = map.getZoom();
				var is_query=jQuery('body.queryresults').length;
				var is_item=jQuery('body#items.show').length;
				var is_tour=jQuery('body#tours.show').length;
				if ( !(is_item||is_tour||is_query) ){
					map.setZoom(14);
				}else{
					var bounds = new google.maps.LatLngBounds();
					bounds.extend(oldLatLng);
					bounds.extend(newLatLng);
					map.fitBounds(bounds);					
				}
				
				jQuery('.map-actions a.location i').removeClass('working')
					.toggleClass('icon-location-arrow').toggleClass('icon-spinner'); 	
						
			}	
							
			if(navigator.geolocation) {
		
				jQuery('.map-actions a.location i').addClass('working')
					.toggleClass('icon-location-arrow').toggleClass('icon-spinner');
				
				var map = jQuery('#map_canvas').gmap('get', 'map');
				var oldLatLng = map.getCenter();	
				
				navigator.geolocation.getCurrentPosition(success,error,options);		
			
			
				}
			}

		jQuery( "body" ).keypress(function(event) {
			if ( event.keyCode == 27 ){
				jQuery("body").removeClass("fullscreen-map");
				return false;
			}
		});
		
	</script>
	
	<?php	
}


/*
** Modified search form
** Adds HTML "placeholder" attribute
** Adds HTML "type" attribute
*/

function mh_simple_search($formProperties=array(), $uri = null){
	// Always post the 'items/browse' page by default (though can be overridden).
	if (!$uri) {
		$uri = url('items/browse?sort_field=relevance');
	}

	$searchQuery = array_key_exists('search', $_GET) ? $_GET['search'] : '';
	$formProperties['action'] = $uri;
	$formProperties['method'] = 'get';
	$html = '<form ' . tag_attributes($formProperties) . '>' . "\n";
	$html .= '<fieldset>' . "\n\n";
	$html .= get_view()->formText('search', $searchQuery, array('name'=>'search','class'=>'textinput','placeholder'=>__('Search %s',mh_item_label('plural'))));
	$html .= '</fieldset>' . "\n\n";

	// add hidden fields for the get parameters passed in uri
	$parsedUri = parse_url($uri);
	if (array_key_exists('query', $parsedUri)) {
		parse_str($parsedUri['query'], $getParams);
		foreach($getParams as $getParamName => $getParamValue) {
			$html .= get_view()->formHidden($getParamName, $getParamValue);
		}
	}

	$html .= '</form>';
	return $html;
}


/*
** App Store links on homepage
*/
function mh_appstore_downloads(){
	if (get_theme_option('enable_app_links')){

		echo '<div>';
		echo '<h2 class="hidden">Downloads</h2>';

		$ios_app_id = get_theme_option('ios_app_id');
		echo ($ios_app_id ?
			'<a id="apple" class="app-store" href="https://itunes.apple.com/us/app/'.$ios_app_id.'"><span class="icon-apple" aria-hidden="true"></span>
		'.__('App Store').'
		</a> ':'<a id="apple" class="app-store" href="#"><span class="icon-apple" aria-hidden="true"></span>
		'.__('iPhone App Coming Soon').'
		</a> ');

		$android_app_id = get_theme_option('android_app_id');
		echo ($android_app_id ?
			'<a id="android" class="app-store" href="http://play.google.com/store/apps/details?id='.$android_app_id.'"><span class="icon-android" aria-hidden="true"></span>
		'.__('Google Play').'
		</a> ':'<a id="android" class="app-store" href="#"><span class="icon-android" aria-hidden="true"></span>
		'.__('Android App Coming Soon').'
		</a> ');
		echo '</div>';

	}
}


/*
** App Store links in footer
*/
function mh_appstore_footer(){
	if (get_theme_option('enable_app_links')){

		$ios_app_id = get_theme_option('ios_app_id');
		$android_app_id = get_theme_option('android_app_id');
		if (($ios_app_id != false) && ($android_app_id == false)) {
			echo '<a id="apple-text-link" class="app-store-footer" href="https://itunes.apple.com/us/app/'.$ios_app_id.'">'.__('Get the app for iPhone').'</a>';
		}
		elseif (($ios_app_id == false) && ($android_app_id != false)) {
			echo '<a id="android-text-link" class="app-store-footer" href="http://play.google.com/store/apps/details?id='.$android_app_id.'">'.__('Get the app for Android').'</a>';

		}
		elseif (($ios_app_id != false)&&($android_app_id != false)) {
			$iphone='<a id="apple-text-link" class="app-store-footer" href="https://itunes.apple.com/us/app/'.$ios_app_id.'">'.__('iPhone').'</a>';
			$android='<a id="android-text-link" class="app-store-footer" href="http://play.google.com/store/apps/details?id='.$android_app_id.'">'.__('Android').'</a>';
			echo __('Get the app for %1$s and %2$s', $iphone, $android);
		}
		else{
			echo __('iPhone + Android Apps Coming Soon!');
		}
	}
}


/*
** Map FAQ
** used for item map marker onclick
** may be customized by site owner
*/
function mh_mapfaq(){
	$emailincl=($email=get_theme_option('contact_email')) ? 'at <a href="mailto:'.$email.'">'.$email.'</a> ' : '';
	$html ='';
	$html .='<div style="display: none"><div id="map-faq"><div id="map-faq-inner">';
	$html .='<h2>'.__('Frequently Asked Questions <span>about the map</span><').'/h2>';
	if((!get_theme_option('map_faq'))){
		$html .=__('<h3><a>Are all the locations on %s publicly accessible?</a></h3>',option('site_title'));
		$html .=__('<p>Not necessarily. It is up to you to determine if any given location is one you can physically visit.</p>');
		$html .=__('<h3><a>How do you choose locations for each %s?</a> <span>or</span> <a>The location is wrong!</a></h3>',strtolower(mh_item_label()));
		$html .=__('<p>Placing historical %1$s on a map can be tricky. We choose locations based on what we think makes the most sense. Sometimes we get it wrong (and sometimes there is no "right" answer). Feel free to email us %2$s with suggestions for improvement.</p>',strtolower(mh_item_label('plural')),$emailincl);
		
		$html .=__('<h3><a>The &#8220;show current location&#8221; button does not work.</a> <span>or</span> <a>The map displays an incorrect position for my current location.</a></h3>',option('site_title'));
		$html .=__('<p>Browser geolocation is a relatively new technology. Some older web browsers may not be able to use this feature. Sometimes even the best, newest web browsers fail to load a correct location (or any location at all). Functionality depends on a number of additional factors, including the presence of conflicting browser plugins, network conditions, privacy settings, and more.</p>');

	}else{
		$html .=get_theme_option('map_faq');
	}
	$html.='</div></div></div>';

	return $html;

}

/*
** Replace BR tags, wrapping text in P tags instead
*/
function replace_br($data) {
    $data = preg_replace('#(?:<br\s*/?>\s*?){2,}#', '</p><p>', $data);
    return "<p>$data</p>";
}

/*
** primary item text  
*/

function mh_the_text($item='item',$options=array()){
	
	$dc_desc = metadata($item, array('Dublin Core', 'Description'),$options);
	$primary_text = element_exists('Item Type Metadata','Story') ? metadata($item,array('Item Type Metadata', 'Story'),$options) : null;
	
	return $primary_text ? replace_br($primary_text) : ($dc_desc ? replace_br($dc_desc) : null);
}


/*
** Subtitle 
*/

function mh_the_subtitle($item=null){

	$dc_title2 = metadata($item, array('Dublin Core', 'Title'), array('index'=>1));
	$subtitle=element_exists('Item Type Metadata','Subtitle') ? metadata($item,array('Item Type Metadata', 'Subtitle')) : null;
	
	return  $subtitle ? $subtitle : ($dc_title2!=='[Untitled]' ? $dc_title2 : null);
}


/*
** lede  
*/
function mh_the_lede($item='item'){
	if (element_exists('Item Type Metadata','Lede')){
		$lede=metadata($item,array('Item Type Metadata', 'Lede'));
		return  $lede ? '<div id="item-lede">'.$lede.'</div>' : null;
	}
		
}


/*
** sponsor for use in item byline 
*/
function mh_the_sponsor($item='item'){

	if (element_exists('Item Type Metadata','Sponsor')){
		$sponsor=metadata($item,array('Item Type Metadata','Sponsor'));
		return $sponsor ? '<span class="sponsor"> with research support from '.$sponsor.'</span>' : null;	
	} 
	
}

/*
** access info  
*/
function mh_the_access_information($item='item'){
	if (element_exists('Item Type Metadata','Access Information')){
		$access_info=metadata($item,array('Item Type Metadata', 'Access Information'));
		return  $access_info ? '<h3>'.__('Access Information: ').'</h3>'.$access_info : null;
	}
		
}

/*
** author byline for the item
*/
function mh_the_byline($itemObj='item',$include_sponsor=false,$include_edit_link=false){
	if ((get_theme_option('show_author') == true)){
		$html='<span class="story-meta byline">'.__('By ');

		if(metadata($itemObj,array('Dublin Core', 'Creator'))){
			$authors=metadata($itemObj,array('Dublin Core', 'Creator'), array('all'=>true));
			$total=count($authors);
			$index=1;
			$authlink=get_theme_option('link_author');

			foreach ($authors as $author){
				if($authlink==1){
					$href='/items/browse?search=&advanced[0][element_id]=39&advanced[0][type]=is+exactly&advanced[0][terms]='.$author;
					$author='<a href="'.$href.'">'.$author.'</a>';
				}

				switch ($index){
				case ($total):
					$delim ='';
					break;

				case ($total-1):
					$delim =' <span class="amp">&amp;</span> ';
					break;

				default:
					$delim =', ';
					break;
				}


				$html .= $author.$delim;
				$index++;
			}
		}else{
			$html .= __('The %s team', option('site_title'));
		}
		
		$html .= (($include_sponsor) && (mh_the_sponsor($itemObj)!==null ))? ''.mh_the_sponsor($itemObj) : null;
		
		$html .=($include_edit_link ? link_to_item_edit($itemObj,' ') : null).'</span>';

		return $html;
	}
}


/*
** Finds URLs in a given $string and
** wraps them in an HTML span, to which we can apply CSS word-wrap in the stylesheet
** This allows the long URLs to wrap more efficiently
** Handy for when URLs are breaking responsive page design
** Indended use: mh_wrappable_link(html_entity_decode(metadata('item', 'citation')))
*/
function mh_wrappable_link($string){

	$result = '';

	/* Find a URL in the $string and build the replacement */
	preg_match('/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/',$string, $matches);
	if( count( $matches ) > 0 ){
		$origURL = $matches[0];
		$newURL='<span class="citation-url">'.$origURL.'</span>'; 

		/* Apply the replacement URL to the original string */
		$result=preg_replace('/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/',$newURL, $string);
	}

	return $result;
}

function mh_post_date(){

	if(get_theme_option('show_datestamp')==1){
		$a=format_date(metadata('item', 'added'));
		$m=format_date(metadata('item', 'modified'));	
	
		return '<span class="post-date">'.__('Published on %s.', $a ).( ($a!==$m) ? ' '.__('Last updated on %s.', $m ) : null ).'</span>';	
	}
}


/*
** Custom item citation
** Optionally, set $wrappableDomain to true to allow domain names to wrap lines
** Helpful for long (sub)domains like name-of-project.department.university.co.uk
*/
function mh_item_citation($wrappableDomain=true){
	
	$header='<h3>'.__('Cite this Page: ').'</h3>';
	
	if($wrappableDomain==true){
		return $header.mh_wrappable_link(html_entity_decode(metadata('item', 'citation')));
	}else{
		return $header.html_entity_decode(metadata('item', 'citation'));
	}
}

/*
** Build caption from description, source, and creator
*/
function mh_file_caption($file,$inlineTitle=true){

	$caption=array();

	if( $inlineTitle !== false ){
		$title = metadata( $file, array( 'Dublin Core', 'Title' ) ) ? '<span class="title">'.metadata( $file, array( 'Dublin Core', 'Title' ) ).'</span>' : null;
	}

	$description = metadata( $file, array( 'Dublin Core', 'Description' ) );
	if( $description ) {
		$caption[]= $description;
	}

	$source = metadata( $file, array( 'Dublin Core', 'Source' ) );
	if( $source ) {
		$caption[]= __('Source: %s',$source);
	}


	$creator = metadata( $file, array( 'Dublin Core', 'Creator' ) );
	if( $creator ) {
		$caption[]= __('Creator: %s', $creator);
	}

	if( count($caption) ){
		return ($inlineTitle ? $title.': ' : null).implode(" | ", $caption);
	}else{
		return $inlineTitle ? $title : null;
	}
}


function mh_footer_scripts_init(){
			
			//===========================// ?>
			<script>
			// the fancybox caption minimize/expand button
			function toggleText(){
				var link = jQuery('a.fancybox-hide-text');
				jQuery(".fancybox-title span.main").slideToggle(function(){
		            		            		            
		            if (jQuery(this).is(":visible")) {
		                 link.html('<span class="icon-close" aria-hidden="true"></span> Hide Caption').addClass('active');
		            } else {
		                 link.html('<span class="icon-chevron-up" aria-hidden="true"></span> Show Caption').addClass('active');
		            }
		            
				});
			}
			
			
			loadCSS('<?php echo WEB_ROOT;?>/themes/curatescape/javascripts/fancybox/source/jquery.fancybox.css');
			loadJS('<?php echo WEB_ROOT;?>/themes/curatescape/javascripts/fancybox/source/jquery.fancybox.pack.js', function(){
				// checkWidth.js sets 'big' and 'small' body classes
				// FancyBox is used only when the body class is 'big' or with the 'map faq' link at any size
				jQuery("body.big .fancybox, body.small .faq .fancybox").fancybox({
			        beforeLoad: function() {
			            this.title = jQuery(this.element).attr('data-caption');
			        },
			        beforeShow: function () {
			            if (this.title) {
			                // Add caption close button
			                this.title += '<a class="fancybox-hide-text active" onclick="toggleText()"><span class="icon-chevron-up" aria-hidden="true"></span> Show Caption</a> ';
			            }
			        },
			        padding:3,
				    helpers : {
				         title: {
				            type: 'over'
				        },
				         overlay : {
				         	locked : true
				        },
				    }
				});				
			});
			


			// Animated scrolling
			jQuery( document ).ready(function() {
				jQuery(function() {				   
				  jQuery(document.body).on('click','a[href*=#]:not([href=#]):not(.fancybox)',function() {
				    if (location.pathname.replace(/^\//,'') == this.pathname.replace(/^\//,'') && location.hostname == this.hostname) {
				      var target = jQuery(this.hash);
				      target = target.length ? target : jQuery('[name=' + this.hash.slice(1) +']');
				      if (target.length) {
				      	jQuery(target).addClass("target");

				        jQuery('html,body').animate({
				          scrollTop: target.offset().top
				        }, 1000,'swing',function(){jQuery(target).removeClass("target");});
				        
				      	jQuery('body.fullscreen-map #map_canvas').hide();
				      	jQuery('body').removeClass("fullscreen-map");
				      					        
				        return false;
				      }
				    }
				  });
				});	
			});
			</script>
			<?php //========================//
			
}


/*
** Loop through and display image files
*/
function mh_item_images($item,$index=0,$html=null){

	foreach (loop('files', $item->Files) as $file){
		$img = array('image/jpeg','image/jpg','image/png','image/jpeg','image/gif');
		$mime = metadata($file,'MIME Type');
		

		if(in_array($mime,$img)) {
			if($index==0) {
				$html .= '<h3><span class="icon-camera-retro" aria-hidden="true"></span>Images </span></h3>';


				
			}	
			$filelink=link_to($file,'show', '<span class="view-file-link"><span class="icon-file" aria-hidden="true"></span> '.__('View File Details Page').'</span>',array('class'=>'view-file-record','rel'=>'nofollow'));
			$photoDesc = mh_normalize_special_characters(
				strip_tags( mh_file_caption($file,false ),
				'<a><strong><em><i><b><span>') 
				);
			$photoTitle = mh_normalize_special_characters(metadata($file,array('Dublin Core', 'Title')));

			if($photoTitle){
				$fancyboxCaption= mh_normalize_special_characters(mh_file_caption($file,true));
				$fancyboxCaption = '<span class="main"><div class="caption-inner">'.strip_tags($fancyboxCaption,'<a><strong><em><i><b><span>').'</div></span>'.$filelink;
			}else{
				$fancyboxCaption = '<span class="main">Image '.($index+1).'</span>'.$filelink;
			}
						
			$html .= '<div class="item-file-container">';

			$html .= file_markup($file, array('imageSize' => 'fullsize','linkAttributes'=>array('data-caption'=>$fancyboxCaption,'title'=>$photoTitle, 'class'=>'fancybox', 'rel'=>'group'),'imgAttributes'=>array('alt'=>$photoTitle) ) );

			$html .= ($photoTitle) ? '<h4 class="title image-title">'.$photoTitle.'</h4>' : '';
			$html .= '<p class="description image-description">'.( ($photoDesc) ? $photoDesc : '');
			$html .= link_to($file,'show', '<span class="view-file-link"><span class="icon-file" aria-hidden="true"></span> '.__('View File Details Page').'</span>',array('class'=>'view-file-record','rel'=>'nofollow')).'</p></div>';

			//echo $html;
			$index++;

		}
		
		
	}
	echo ($html !== null) ? '<figure id="item-photos">'.$html.'</figure>' : null;
}


/*
** Loop through and display audio files
** FYI: adding "controls" to html <audio> tag causes a
** display error when used in combination w/ Fancybox
** image viewer
*/
function mh_audio_files($item,$index=0,$html=null){
	if (!$item){
		$item=set_loop_records('files',$item);
	}
	$audioTypes = array('audio/mpeg');
	foreach (loop('files', $item->Files) as $file):
		$audioDesc = strip_tags(mh_file_caption($file,false),'<span>');
		$audioTitle = metadata($file,array('Dublin Core','Title')) ? metadata($file,array('Dublin Core','Title')) : 'Audio File '.($index+1);
		$mime = metadata($file,'MIME Type');

	if ( array_search($mime, $audioTypes) !== false ) {

		if ($index==0){ ?>
		<h3><span class="icon-volume-up" aria-hidden="true"></span>Audio </span></h3>
		
		<script>
		jQuery.ajaxSetup({
			cache: true
		});
		var audioTagSupport = !!(document.createElement('audio').canPlayType);
		if (Modernizr.audio) {
		   var myAudio = document.createElement('audio');
		   // Currently canPlayType(type) returns: "", "maybe" or "probably" 
		   var canPlayMp3 = !!myAudio.canPlayType && "" != myAudio.canPlayType('audio/mpeg');
		}
		if(!canPlayMp3){
			loadJS("/themes/curatescape/javascripts/audiojs/audiojs/audio.min.js", function(){
				audiojs.events.ready(function() {
				var as = audiojs.createAll();				
				});
			});  
		}   
		</script>
		
		<?php }
		$index++;

		$html .= '<div class="item-file-container">';
		$html .= '<audio controls><source src="'.file_display_url($file,'original').'" type="audio/mpeg" /><h5 class="no-audio"><strong>'.__('Download Audio').':</strong><a href="'.file_display_url($file,'original').'">MP3</a></h5></audio>';
		$html .= ($audioTitle) ? '<h4 class="title audio-title sib">'.$audioTitle.' <span class="icon-info-sign" aria-hidden="true"></span></h4>' : '';
		$html .= '<p class="description audio-description sib">'.( ($audioDesc) ? $audioDesc : '');
		$html .= link_to($file,'show', '<span class="view-file-link"><span class="icon-file" aria-hidden="true"></span> '.__('View File Details Page').'</span>',array('class'=>'view-file-record','rel'=>'nofollow')).'</p></div>';

	}

	endforeach;
	
	echo ($html !== null ) ? '<figure id="item-audio">'.$html.'</figure>' : null;
}



/*
** Loop through and display video files
** Please use H.264 video format
** Browsers that do not support H.264 will fallback to Flash
** We accept multiple H.264-related MIME-types because Omeka MIME detection is sometimes spotty
** But in the end, we always tell the browser they're looking at "video/mp4"
** Opera and Firefox are currently the key browsers that need flash here, but that may change
*/
function mh_video_files($item,$html=null) {
	if (!$item){
		$item=set_loop_records('files',$item);
	}
	$videoIndex = 0;
	$localVid=0;
	$videoTypes = array('video/mp4','video/mpeg','video/quicktime');
	$videoPoster = mh_poster_url();


	foreach (loop('files', $item->Files) as $file):
		$videoMime = metadata($file,'MIME Type');
	if ( in_array($videoMime,$videoTypes) ){

		$videoFile = file_display_url($file,'original');
		$videoTitle = metadata($file,array('Dublin Core', 'Title'));
		$videoClass = (($videoIndex==0) ? 'first' : 'not-first');
		$videoDesc = mh_file_caption($file,false);
		$videoTitle = metadata($file,array('Dublin Core','Title')) ? metadata($file,array('Dublin Core','Title')) : 'Video File '.($videoIndex+1);
		$embeddable=embeddableVersion($file,$videoTitle,$videoDesc);
		if($embeddable){
			// If a video has an embeddable streaming version, use it.
			$html.= $embeddable;
			$videoIndex++;
			//break;
		}else{

			$html .= '<div class="item-file-container">';
			$html .= '<video width="725" height="410" id="video-'.$localVid.'" class="'.$videoClass.' video-js vjs-default-skin" controls poster="'.$videoPoster.'" preload="auto" data-setup="{}">';
			$html .= '<source src="'.$videoFile.'" type="video/mp4">';
			$html .= '</video>';
			$html .= ($videoTitle) ? '<h4 class="title video-title sib">'.$videoTitle.' <span class="icon-info-sign" aria-hidden="true"></span></h4>' : '';
			$html .= '<p class="description video-description sib">'.( ($videoDesc) ? $videoDesc : '');
			$html .= link_to($file,'show', '<span class="view-file-link"><span class="icon-file" aria-hidden="true"></span> '.__('View File Details Page').'</span>',array('class'=>'view-file-record','rel'=>'nofollow')).'</p></div>';
			$localVid++;
			$videoIndex++;
		}
	}
	endforeach;
	if ($videoIndex > 0) {
		
		?>
		<script>
			loadCSS('http://vjs.zencdn.net/4.3/video-js.css');
			loadJS('http://vjs.zencdn.net/4.3/video.js');
		</script>	
		<?php 
		
		echo '<figure id="item-video">';
		echo '<h3><span class="icon-film" aria-hidden="true"></span>'.(($videoIndex > 1) ? __('Video ') : __('Video ')).'</span></h3>';
		echo $html;
		echo '</figure>';
	}
}



/*
** display single file in FILE TEMPLATE
*/

function mh_single_file_show($file=null){
		
		$mime = metadata($file,'MIME Type');
		$img = array('image/jpeg','image/jpg','image/png','image/jpeg','image/gif');
		$audioTypes = array('audio/mpeg');
		$videoTypes = array('video/mp4','video/mpeg','video/quicktime');
		
		
		// SINGLE AUDIO FILE
		if ( array_search($mime, $audioTypes) !== false ){
			
			?>
			
			<script>
			jQuery.ajaxSetup({
				cache: true
			});
			var audioTagSupport = !!(document.createElement('audio').canPlayType);
			if (Modernizr.audio) {
			   var myAudio = document.createElement('audio');
			   // Currently canPlayType(type) returns: "", "maybe" or "probably" 
			   var canPlayMp3 = !!myAudio.canPlayType && "" != myAudio.canPlayType('audio/mpeg');
			}
			if(!canPlayMp3){
				loadJS("/themes/curatescape/javascripts/audiojs/audiojs/audio.min.js", function(){
					audiojs.events.ready(function() {
					var as = audiojs.createAll();				
					});
				});  
			}  
			</script>
			
			<?php
			
			$html = '<audio controls ><source src="'.file_display_url($file,'original').'" type="audio/mpeg" /><h5 class="no-audio"><strong>'.__('Download Audio').':</strong><a href="'.file_display_url($file,'original').'">MP3</a></h5></audio>';
			
			return $html;
		
		// SINGLE VIDEO FILE	
		}elseif(array_search($mime, $videoTypes) !== false){
			$html=null;
			$videoIndex = 0;
			$localVid=0;
			$videoTypes = array('video/mp4','video/mpeg','video/quicktime');
			$videoPoster = mh_poster_url();			
			$videoFile = file_display_url($file,'original');
			$videoTitle = metadata($file,array('Dublin Core', 'Title'));
			$videoClass = (($videoIndex==0) ? 'first' : 'not-first');
			$videoDesc = mh_file_caption($file,false);
			$videoTitle = metadata($file,array('Dublin Core','Title'));
			$embeddable=embeddableVersion($file,$videoTitle,$videoDesc,array('Dublin Core','Relation'),false);
			if($embeddable){
				// If a video has an embeddable streaming version, use it.
				$html.= $embeddable;
				$videoIndex++;
				//break;
			}else{
				?>
				<script>
					loadCSS('http://vjs.zencdn.net/4.3/video-js.css');
					loadJS('http://vjs.zencdn.net/4.3/video.js');
				</script>	
				<?php 	
				$html .= '<div class="item-file-container">';
				$html .= '<video width="725" height="410" id="video-'.$localVid.'" class="'.$videoClass.' video-js vjs-default-skin" controls poster="'.$videoPoster.'" preload="auto" data-setup="{}">';
				$html .= '<source src="'.$videoFile.'" type="video/mp4">';
				$html .= '</video>';

			}	
					
			return $html;
		
		// SINGLE IMAGE OR OTHER FILE	
		}else{
			return file_markup($file, array('imageSize'=>'fullsize'));
		}
}

/*
** Checks file metadata record for embeddable version of video file
** Because YouTube and Vimeo have better compression, etc.
** returns string $html | false
*/
function embeddableVersion($file,$title=null,$desc=null,$field=array('Dublin Core','Relation'),$caption=true){

	$youtube= (strpos(metadata($file,$field), 'youtube.com')) ? metadata($file,$field) : false;
	$youtube_shortlink= (strpos(metadata($file,$field), 'youtu.be')) ? metadata($file,$field) : false;
	$vimeo= (strpos(metadata($file,$field), 'vimeo.com')) ? metadata($file,$field) : false;

	if($youtube) {
		// assumes YouTube links look like https://www.youtube.com/watch?v=NW03FB274jg where the v query contains the video identifier
		$url=parse_url($youtube);
		$id=str_replace('v=','',$url['query']);
		$html= '<div class="embed-container youtube" id="v-streaming" style="position: relative;padding-bottom: 56.25%;height: 0; overflow: hidden;"><iframe style="position: absolute;top: 0;left: 0;width: 100%;height: 100%;" src="//www.youtube.com/embed/'.$id.'" frameborder="0" width="725" height="410" allowfullscreen></iframe></div>';
		if($caption==true){
			$html .= ($title) ? '<h4 class="title video-title sib">'.$title.' <span class="icon-info-sign" aria-hidden="true"></span></h4>' : '';
			$html .= ($desc) ? '<p class="description video-description sib">'.$desc.link_to($file,'show', '<span class="view-file-link"><span class="icon-file" aria-hidden="true"></span> '.__('View File Details Page').'</span>',array('class'=>'view-file-record','rel'=>'nofollow')).'</p>' : '';
		}
		return '<div class="item-file-container">'.$html.'</div>';
	}
	elseif($youtube_shortlink) {
		// assumes YouTube links look like https://www.youtu.be/NW03FB274jg where the path string contains the video identifier
		$url=parse_url($youtube_shortlink);
		$id=$url['path'];
		$html= '<div class="embed-container youtube" id="v-streaming" style="position: relative;padding-bottom: 56.25%;height: 0; overflow: hidden;"><iframe style="position: absolute;top: 0;left: 0;width: 100%;height: 100%;" src="//www.youtube.com/embed/'.$id.'" frameborder="0" width="725" height="410" allowfullscreen></iframe></div>';
		if($caption==true){
			$html .= ($title) ? '<h4 class="title video-title sib">'.$title.' <span class="icon-info-sign" aria-hidden="true"></span></h4>' : '';
			$html .= ($desc) ? '<p class="description video-description sib">'.$desc.link_to($file,'show', '<span class="view-file-link"><span class="icon-file" aria-hidden="true"></span> '.__('View File Details Page').'</span>',array('class'=>'view-file-record','rel'=>'nofollow')).'</p>' : '';
		}
		return '<div class="item-file-container">'.$html.'</div>';
	}
	elseif($vimeo) {
		// assumes the Vimeo links look like http://vimeo.com/78254514 where the path string contains the video identifier
		$url=parse_url($vimeo);
		$id=$url['path'];
		$html= '<div class="embed-container vimeo" id="v-streaming" style="padding-top:0; height: 0; padding-top: 25px; padding-bottom: 67.5%; margin-bottom: 10px; position: relative; overflow: hidden;"><iframe style=" top: 0; left: 0; width: 100%; height: 100%; position: absolute;" src="//player.vimeo.com/video'.$id.'?color=333" width="725" height="410" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe></div>';
		if($caption==true){
			$html .= ($title) ? '<h4 class="title video-title sib">'.$title.' <span class="icon-info-sign" aria-hidden="true"></span></h4>' : '';
			$html .= ($desc) ? '<p class="description video-description sib">'.$desc.link_to($file,'show', '<span class="view-file-link"><span class="icon-file" aria-hidden="true"></span> '.__('View File Details Page').'</span>',array('class'=>'view-file-record','rel'=>'nofollow')).'</p>' : '';
		}
		return '<div class="item-file-container">'.$html.'</div>';
	}
	else{
		return false;
	}
}


/*
** Display subjects as links
** These links are hard to validate via W3 for some reason
*/
function mh_subjects(){
	$subjects = metadata('item',array('Dublin Core', 'Subject'), 'all');
	if (count($subjects) > 0){

		echo '<h3>'.__('Subjects').'</h3>';
		echo '<ul>';
		foreach ($subjects as $subject){
			$link = WEB_ROOT;
			$link .= htmlentities('/items/browse?term=');
			$link .= rawurlencode($subject);
			$link .= htmlentities('&search=&advanced[0][element_id]=49&advanced[0][type]=contains&advanced[0][terms]=');
			$link .= urlencode(str_replace('&amp;','&',$subject));
			echo '<li><a href="'.$link.'">'.$subject.'</a></li> ';
		}
		echo '</ul>';

	}
}

function mh_subjects_string(){
	$subjects = metadata('item',array('Dublin Core', 'Subject'), 'all');
	if (count($subjects) > 0){
		$html=array();

		foreach ($subjects as $subject){
			$link = WEB_ROOT;
			$link .= htmlentities('/items/browse?term=');
			$link .= rawurlencode($subject);
			$link .= htmlentities('&search=&advanced[0][element_id]=49&advanced[0][type]=contains&advanced[0][terms]=');
			$link .= urlencode(str_replace('&amp;','&',$subject));
			$html[]= '<a href="'.$link.'">'.$subject.'</a>';
		}

		echo '<div class="item-subjects"><p><span>'.__('Subjects: ').'</span>'.implode(", ", $html).'</p></div>';
	}
}


/*
Display nav items for Simple Pages sidebar
** (not currently very useful, but we might add some novel content later)
*/
function mh_sidebar_nav(){

	return mh_global_nav();

}


/*
** Display the item tags
*/
function mh_tags(){
	if (metadata('item','has tags')):

		echo '<h3>'.__('Tags').'</h3>';
		echo tag_cloud('item','items/browse');
		
	endif;
}

/*
** Display the official website
*/
function mh_official_website($item='item'){

	if (element_exists('Item Type Metadata','Official Website')){
		$website=metadata($item,array('Item Type Metadata','Official Website'));
		return $website ? '<h3>'.__('Official Website: ').'</h3>'.$website : null;	
	} 

}

/*
** Display the street address
*/
function mh_street_address($item='item',$formatted=true){

	if (element_exists('Item Type Metadata','Street Address')){
		$address=metadata($item,array('Item Type Metadata','Street Address'));
		$map_link='<a target="_blank" href="https://maps.google.com/maps?saddr=current+location&daddr='.urlencode($address).'">map</a>';
		return $address ? ( $formatted ? '<h3>'.__('Street Address: ').'</h3>'.$address.' ['.$map_link.']' : $address ) : null;	
	} 

}

/*
** Display the factoid
*/
function mh_factoid($item='item',$html=null){

	if (element_exists('Item Type Metadata','Factoid')){
		$factoids=metadata($item,array('Item Type Metadata','Factoid'),array('all'=>true));
		if($factoids){
			
			foreach($factoids as $factoid){
				$html.='<div class="factoid"><span class="icon-lightbulb" aria-hidden="true"></span> <span class="fi">'.$factoid.'</span></div>';
			}
			
			return $html;
		}
	} 

}

/*
** Display related links
*/
function mh_related_links(){
	$dc_relations_field = metadata('item',array('Dublin Core', 'Relation'), array('all' => true));
	
	$related_resources = element_exists('Item Type Metadata','Related Resources') ? metadata('item',array('Item Type Metadata', 'Related Resources'), array('all' => true)) : null;
	
	$relations = $related_resources ? $related_resources : $dc_relations_field;
	
	if ($relations){
		echo '<h3>'.__('Related Sources').'</h3><ul>';
		foreach ($relations as $relation) {
			echo "<li>$relation</li>";
		}
		echo '</ul>';
	}
}

/*
** Display the AddThis social sharing widgets
** www.addthis.com
*/
function mh_share_this($type='Page'){
	$addthis = get_theme_option('Add This') ? '#pubid='.get_theme_option('Add This') : null;
	$tracking= ($addthis && get_theme_option('track_address_bar')) ? '"data_track_addressbar":true' : null;

	$html = '<h3>'.__('Share this %s',$type).'</h3>';
	$html .= '<!-- AddThis Button BEGIN -->
<div class="addthis_toolbox addthis_default_style addthis_32x32_style">
<a class="addthis_button_twitter"></a>
<a class="addthis_button_facebook"></a>
<a class="addthis_button_pinterest_share"></a>
<a class="addthis_button_email"></a>
<a class="addthis_button_compact"></a>
</div>
<script type="text/javascript">var addthis_config = {'.$tracking.'};</script>
<script type="text/javascript" src="//s7.addthis.com/js/300/addthis_widget.js'.$addthis.'"></script>
<script type="text/javascript">
// Alert a message when the AddThis API is ready
function addthisReady(evt) {
    jQuery(\'#share-this\').addClass(\'api-loaded\');
}

// Listen for the ready event
addthis.addEventListener(\'addthis.ready\', addthisReady);
</script>
<!-- AddThis Button END -->';


	return $html;
}

/*
** DISQUS COMMENTS
** disqus.com
*/
function mh_disquss_comments(){
	$shortname=get_theme_option('comments_id');
	if ($shortname){
?>
    <div id="disqus_thread"></div>
    <script type="text/javascript">
        
        var disqus_shortname = '<?php echo $shortname;?>'; 

        (function() {
            var dsq = document.createElement('script'); dsq.type = 'text/javascript'; dsq.async = true;
            dsq.src = '//' + disqus_shortname + '.disqus.com/embed.js';
            (document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(dsq);
        })();
        
    </script>
    
    <noscript><?php echo __('Please enable JavaScript to view the <a href="http://disqus.com/?ref_noscript">comments powered by Disqus.</a>');?></noscript>
    
    <a href="http://disqus.com" class="dsq-brlink"><?php echo __('comments powered by <span class="logo-disqus">Disqus</span>');?></a>
    
	<?php
	}
}


/*
** Subnavigation for items/browse pages
*/

function mh_item_browse_subnav(){
	echo nav(array(
			array('label'=>'All' ,'uri'=> url('items/browse')),
			array('label'=>'Tags', 'uri'=> url('items/tags')),
		));
}


/*
** See where you're at in a loop and conditionally load content
** This quirky little function is used mainly on items/browse,
** where we need to output all item records (making for one hell of a page load when you have 500+ items)
** NOTE that you can only use this function within loops where $index is defined and incremented
** The +1 allows the index arg to be set at 1, which allows us to use 0 for the second arg
*/
function mh_reducepayload($index,$showThisMany){
	$showThisMany = ($index) ? ($index < ($showThisMany+1)) : true;
	return $showThisMany;
}

/*
** Display the Tours list
*/
function mh_display_homepage_tours($num=7, $scope='random'){
	
	$scope=get_theme_option('homepage_tours_scope') ? get_theme_option('homepage_tours_scope') : $scope;
	
	// Get the database.
	$db = get_db();

	// Get the Tour table.
	$table = $db->getTable('Tour');

	// Build the select query.
	$select = $table->getSelect();
	$select->where('public = 1');
	
	// Get total count
	$public = $table->fetchObjects($select);		
	
	// Continue, get scope
	switch($scope){
		case 'random':
			$select->from(array(), 'RAND() as rand');
			break;
		case 'featured':
			$select->where('featured = 1');
			break;
	}
	

	// Fetch some items with our select.
	$items = $table->fetchObjects($select);
	if($scope=='random') shuffle($items);
	$num = (count($items)<$num)? count($items) : $num;
	$html=null;
	
	if($items){
		$html .= '<h2><a href="'.WEB_ROOT.'/tours/browse/">'.mh_tour_header().'</a></h2>';
	
		for ($i = 0; $i < $num; $i++) {
			$html .= '<article class="item-result">';
			$html .= '<h3 class="home-tour-title"><a href="' . WEB_ROOT . '/tours/show/'. $items[$i]['id'].'">' . $items[$i]['title'] . '</a></h3>';
			$html .= '</article>';
		}
		if(count($public)>1){
			$html .= '<p class="view-more-link"><a href="'.WEB_ROOT.'/tours/browse/">'.__('Browse all <span>%1$s %2$s</span>', count($public), mh_tour_label('plural')).'</a></p>';
		}
	}else{
		$html .= '<article class="home-tour-result none">';
		$html .= '<p>'.__('No tours are available.').'</p>';
		$html .= '</article>';
	}
	
	return $html;

}



/*
** Display random featured item
** Used on homepage
*/
function mh_display_random_featured_item($withImage=false,$num=1)
{
	$featuredItem = get_random_featured_items($num,$withImage);
	$html = '<h2 class="hidden">'.__('Featured %s', mh_item_label()).'</h2>';
	$class=get_theme_option('featured_tint')==1 ? 'tint' : 'no-tint';
	
	if ($featuredItem) {
	
	foreach($featuredItem as $item):

			$itemTitle = metadata($item, array('Dublin Core', 'Title'));
			$itemDescription = mh_the_text($item,array('snippet'=>200));
			
	
			if (metadata($item, 'has thumbnail') ) {
			
				$img_markup=item_image('fullsize',array(),0, $item);
				preg_match('/<img(.*)src(.*)=(.*)"(.*)"/U', $img_markup, $result);
				$img_url = array_pop($result);				
				
				$html .= '<div class="'.$class.'">';
					$html .= '<article class="featured-story-result">';
					$html .= '<div class="featured-decora-outer">' ;
						$html .= '<div class="featured-decora-bg" style="background-image:url('.$img_url.')"></div>' ;
						$html .= '<div class="featured-decora-img"'.link_to_item(item_image('square_thumbnail',array(),0, $item), array(), 'show', $item).'</div>';
					
						$html .= '<div class="featured-decora-text"><div class="featured-decora-text-inner">';
							$html .= '<header><h3>' . link_to_item($itemTitle, array(), 'show', $item) . '<span class="featured-item-author">'.mh_the_byline($item,false).'</span></h3></header>';
						if ($itemDescription) {
							$html .= '<div class="item-description">' . strip_tags($itemDescription) . '</div>';
							}else{
							$html .= '<div class="item-description">'.__('Preview text not available.').'</div>';
							$html .= '<p class="view-more-link">'. link_to_item(__('Continue reading <span>%s</span>', $itemTitle), array(), 'show', $item) .'</p>';
						}
	
						$html .= '</div></div>' ;
					
					$html .= '</div>' ;
					$html .= '</article>';
				$html .= '</div>';
			}
			
	endforeach;		
			
	}else {
		$html .= '<article class="featured-story-result none">';
		$html .= '<div class="item-thumb clearfix"></div><div class="item-description"><p>'.__('No featured items are available.').'</p></div>';
		$html .= '</article>';
	}
	
	

	return $html;
}


/*
** Display the customizable "About" content on homepage
** also sets content for mobile slideshow, via mh_random_or_recent()
*/
function mh_home_about($length=530,$html=null){

	$html .= '<div class="about-text">';
		$html .= '<article>';
			
			$html .= '<header>';
				$html .= '<h2>'.option('site_title').'</h2>';
				$html .= '<span class="find-us">'.__('A project by %s', mh_owner_link()).'</span>';
			$html .= '</header>';
		
			$html .= '<div class="about-main">';
				$html .= substr(mh_about(),0,$length);
				$html .= ($length < strlen(mh_about())) ? '...' : null;
				$html .= '<p class="view-more-link"><a href="'.url('about').'">'.__('Read more <span>About Us</span>').'</a></p>';
			$html .= '</div>';
	
		$html .= '</article>';
	$html .= '</div>';
	
	$html .= '<div class="home-about-links">';
		$html .= '<aside>';
		$html .= mh_homepage_find_us();
		$html .= '</aside>';
	$html .= '</div>';

	return $html;
}

/*
** Tag cloud for homepage
*/
function mh_home_popular_tags($num=50){
	
	$tags=get_records('Tag',array('sort_field' => 'count', 'sort_dir' => 'd'),$num);
	
	return '<div id="home-tags" class="browse tags">'.tag_cloud($tags,url('items/browse')).'<p class="view-more-link"><a href="'.url('items/tags').'">'.__('View all <span>%s Tags</span>',total_records('Tags')).'</a></p></div>';
	
}

	

/*
** List of recent or random items for homepage
** Listed in inline homepage section and used in the slider at mobile viewport sizes
*/
function mh_home_item_list($html=null){
	$html.= '<div id="rr_home-items" class="">';
	$html.=  mh_random_or_recent( ($mode=get_theme_option('random_or_recent')) ? $mode : 'recent' );
	$html.=  '</div>';	
	
	return $html;
}

/*
** Build an array of social media links (including icons) from theme settings
*/
function mh_social_array(){
	$services=array();
	($twitter=get_theme_option('twitter_username')) ? array_push($services,'<a class="ext-social-link twitter" href="https://twitter.com/'.$twitter.'"><span class="icon-twitter" aria-hidden="true"></span><span class="social_label"> Twitter</span></a>') : null;
	($pinterest=get_theme_option('pinterest_username')) ? array_push($services,'<a class="ext-social-link pinterest" href="http://www.pinterest.com/'.$pinterest.'"><span class="icon-pinterest" aria-hidden="true"></span><span class="social_label"> Pinterest</span></a>') : null;	
	($facebook=get_theme_option('facebook_link')) ? array_push($services,'<a class="ext-social-link facebook" href="'.$facebook.'"><span class="icon-facebook" aria-hidden="true"></span><span class="social_label"> Facebook</span></a>') : null;
	($youtube=get_theme_option('youtube_username')) ? array_push($services,'<a class="ext-social-link youtube" href="http://www.youtube.com/user/'.$youtube.'"><span class="icon-youtube-play" aria-hidden="true"></span><span class="social_label"> Youtube</span></a>') : null;	
	($email=get_theme_option('contact_email')) ? array_push($services,'<a class="ext-social-link email" href="mailto:'.$email.'"><span class="icon-envelope" aria-hidden="true"></span><span class="social_label"> Email Us</span></a>') : null;		

	if(count($services)>0){
		return $services;
	}else{
		return false;
	}	
}

/*
** Build a series of social media link for the footer
*/
function mh_footer_find_us($separator=' '){
	if( $services=mh_social_array() ){
		return '<span id="find-us-footer">'.join($separator,$services).'</span>';
	}
}

/*
** Build a series of social media link for the footer
*/
function mh_homepage_find_us($separator=' '){
	if( $services=mh_social_array() ){
		return '<span class="find-us-homepage">'.join($separator,$services).'</span>';
	}
}


/*
** Build a link for the footer copyright statement and the fallback credit line on homepage
** see: mh_home_find_us()
*/
function mh_owner_link(){

	$authname_fallback=(option('author')) ? option('author') : option('site_title');

	$authname=(get_theme_option('sponsor_name')) ? get_theme_option('sponsor_name') : $authname_fallback;

	return $authname;
}


/*
** Build HTML content for homepage widget sections
** Each widget can be used ONLY ONCE
** The "Random or Recent" widget is always used since it's req. for the mobile slider
** If the admin user chooses not to use it, it's included in a hidden container
*/

function homepage_widget_1($content='featured'){
	
	get_theme_option('widget_section_1') ? $content=get_theme_option('widget_section_1') : null;
	
	return $content;
}

function homepage_widget_2($content='tours'){
	
	get_theme_option('widget_section_2') ? $content=get_theme_option('widget_section_2') : null;
	
	return $content;	
}

function homepage_widget_3($content='recent_or_random'){
	
	get_theme_option('widget_section_3') ? $content=get_theme_option('widget_section_3') : null;
	
	return $content;	
}

function homepage_widget_sections($html=null){
		
		$recent_or_random_isset=0; 
		$tours_isset=0;
		$featured_isset=0;
		$popular_tags=0;
		
		foreach(array(homepage_widget_1(),homepage_widget_2(),homepage_widget_3()) as $setting){
			
			switch ($setting) {
			    case 'featured':
			        $html.= ($featured_isset==0) ? '<section id="featured-story">'.mh_display_random_featured_item(true,3).'</section>' : null;
			        $featured_isset++;
			        break;
			    case 'tours':
			        $html.= ($tours_isset==0) ? '<section id="home-tours">'.mh_display_homepage_tours().'</section>' : null;
			        $tours_isset++;
			        break;
			    case 'recent_or_random':
			        $html.= ($recent_or_random_isset==0) ? '<section id="home-item-list">'.mh_home_item_list().'</section>' : null;
			        $recent_or_random_isset++;
			        break;
			    case 'popular_tags':
			        $html.= ($popular_tags==0) ? '<section id="home-popular-tags">'.mh_home_popular_tags().'</section>' : null;
			        $popular_tags++;
			        break;

			    default:
			    	$html.=null;
			}
			
		}
		
		// we need to use this one at least once for the mobile slider. if it's unused, we'll include it in a hidden div
		$html.= ($recent_or_random_isset==0) ? '<section class="hidden" id="home-item-list">'.mh_home_item_list().'</section>' : null;
		
		return $html;


}



/*
** Get recent/random items for use in mobile slideshow on homepage
*/
function mh_random_or_recent($mode='recent',$num=4){
	
	switch ($mode){
	
	case 'random':
		$items=get_random_featured_items($num,true);
		$param="Random";
		break;
	case 'recent':
		$items=get_records('Item', array('hasImage'=>true,'sort_field' => 'added', 'sort_dir' => 'd'), $num);
		$param="Recent";
		break;
		
	}

	
	set_loop_records('items',$items);

	$html=null;
	$labelcount='<span>'.total_records('Item').' '.mh_item_label('plural').'</span>';
		
	if (has_loop_records('items')){
			
		$html.=($num <=1) ? '<h2>'.__('%s1 %s2', $param, mh_item_label()).'</h2>' : '<h2>'.__('%1s %2s', $param, mh_item_label('plural')).'</h2>';
		
		$html.= '<div class="rr-results">';	
			
		foreach (loop('items') as $item){
			$html.= '<article class="item-result has-image">';

			$html.= '<h3>'.link_to_item(metadata($item,array('Dublin Core','Title')),array('class'=>'permalink')).'</h3>';

			$hasImage=metadata($item, 'has thumbnail');
			if ($hasImage){
				preg_match('/<img(.*)src(.*)=(.*)"(.*)"/U', item_image('fullsize'), $result);
				$item_image = array_pop($result);
			}

			$html.= isset($item_image) ? link_to_item('<span class="item-image" style="background-image:url('.$item_image.');"></span>') : null;


			if($desc = mh_the_text($item,array('snippet'=>200))){
				$html.= '<div class="item-description">'.$desc.'</div>';
			}else{
				$html.= '<div class="item-description">'.__('Text preview unavailable.').'</div>';
			}

			$html.= '</article>';

		}
		$html.= '</div>';	
		$html.= '<p class="view-more-link">'.link_to_items_browse(__('Browse all %s',$labelcount)).'</p>';

		
	}else{
		$html .= '<article class="recent-random-result none">';
		$html .= '<p>'.__('No %s items are available.',$mode).'</p>';
		$html .= '</article><div class="clearfix"></div>';
	}

	
	return $html;	
	
}

/*
** Csutom CSS
*/
function mh_custom_css(){
	$bg_url=mh_bg_url();
	$color_primary=mh_link_color();
	$color_secondary=mh_secondary_link_color();
	$user_css= get_theme_option('custom_css') ? '/* Theme Option CSS */ '.get_theme_option('custom_css') : null;
	return '<style type="text/css">
	body{
		background:url('.$bg_url.') repeat-x fixed right top #CCCCCC;
		background-size:cover;
		}
	.big #hero{
		background:url('.$bg_url.') repeat-x fixed center top #CCCCCC;
		box-shadow:none;
	}
	.look-at-me{
		border-color:'.$color_secondary.';
	}
	.vjs-default-skin .vjs-play-progress,.vjs-default-skin .vjs-volume-level,
	#swipenav #position li.current, .random-story-link.big-button,#home-tours h2,.tint .featured-decora-outer,a.edit,a.access-anchor:hover,header.main .random-story-link.show,ul.pagination a:hover,.show #tags li a,.show #tour-for-item li a:hover{
		background-color:'.$color_primary.' !important;
		}
	.show #tags li a:hover{
		background-color:'.$color_secondary.' !important;
		}	
	#home-tours h2:after,#home-tours h2{
		border-color: '.$color_primary.' transparent;
		}
	a,.fancybox-opened a.fancybox-hide-text:hover{
		color:'.$color_primary.'
		}
	#home-tours article:hover:after{
		background: #333333;
		background: -moz-linear-gradient(left, #333333 15%, '.$color_secondary.' 45%, #fff 55%, #333333 85%);
		background: -webkit-gradient(linear, left top, right top, color-stop(15%,#333333), color-stop(45%,'.$color_secondary.'), color-stop(55%,'.$color_secondary.'), color-stop(85%,#333333));
		background: -webkit-linear-gradient(left, #333333 15%,'.$color_secondary.' 45%,'.$color_secondary.' 55%,#333333 85%);
		background: -o-linear-gradient(left, #333333 15%,'.$color_secondary.' 45%,'.$color_secondary.' 55%,#333333 85%);
		background: -ms-linear-gradient(left, #333333 15%,'.$color_secondary.' 45%,'.$color_secondary.' 55%,#333333 85%);
		background: linear-gradient(to right, #333333 15%,'.$color_secondary.' 45%,'.$color_secondary.' 55%,#333333 85%);
	}		
	@media only screen and (max-width:50em){
		body footer.main .navigation a,body footer.main p a{
			color:'.$color_secondary.';
		}
	}
	a:hover,#items #tour-nav-links a,#home-tours .view-more-link a,.fancybox-opened a.view-file-record:hover{
		color:'.$color_secondary.'
		}
	@media only screen and (min-width: 60em){
			#featured-story .view-more-link a{
			color:'.$color_secondary.'
			}
		}
	nav.secondary-nav ul li.current{
		border-bottom-color:'.$color_primary.'
		}
	.tint .featured-decora-img{
		box-shadow:0em -1em .5em 0em '.$color_primary.'
		}	
	.tint .featured-story-result:nth-child(odd) .featured-decora-outer .featured-decora-img{
		box-shadow:0em -1em .5em 0em '.$color_secondary. '!important;
		}	
	.tint .featured-story-result:nth-child(odd) .featured-decora-outer{
		background-color:'.$color_secondary.' !important;
	}'.$user_css.'	
		</style>';
}


/*
** Which fonts/service to use?
** Typekit, FontDeck, Monotype or fallback to defaults using Google Fonts
*/
function mh_font_config(){
	if($tk=get_theme_option('typekit')){
		$config="typekit: { id: '".$tk."' }";
	}elseif($fd=get_theme_option('fontdeck')){
		$config="fontdeck: { id: '".$fd."' }";
	}elseif($fdc=get_theme_option('fonts_dot_com')){
		$config="monotype: { projectId: '".$fdc."' }";
	}else{
		$config="google: { families: [ 'Droid+Serif:400,700:latin', 'PT+Serif:400:latin' ] }";
	}
	return $config;
}


/*
** Web Font Loader async script
** https://developers.google.com/fonts/docs/webfont_loader
** see also screen.css
*/
function mh_web_font_loader(){ ?>
	<script type="text/javascript">
		WebFontConfig = {
			<?php echo mh_font_config(); ?>
		};
		(function() {
			var wf = document.createElement('script');
			wf.src = ('https:' == document.location.protocol ? 'https' : 'http') +
			'://ajax.googleapis.com/ajax/libs/webfont/1/webfont.js';
			wf.type = 'text/javascript';
			wf.async = 'true';
			var s = document.getElementsByTagName('script')[0];
			s.parentNode.insertBefore(wf, s);
		})(); 
	</script>	
<?php }


/*
** About text
** Used on homepage (stealth and public)
*/
function mh_about($text=null){
	if (!$text) {
		// If the 'About Text' option has a value, use it. Otherwise, use default text
		$text =
			get_theme_option('about') ?
			get_theme_option('about') :
			__('%s is powered by <a href="http://omeka.org/">Omeka</a> + <a href="http://curatescape.org/">Curatescape</a>, a humanities-centered web and mobile framework available for both Android and iOS devices.',option('site_title'));
	}
	return $text;
}

/*
**
*/
function mh_license(){
	$cc_license=get_theme_option('cc_license');
	$cc_version=get_theme_option('cc_version');
	$cc_jurisdiction=get_theme_option('cc_jurisdiction');
	$cc_readable=array(
		'1'=>'1.0',
		'2'=>'2.0',
		'2-5'=>'2.5',
		'3'=>'3.0',
		'4'=>'4.0',
		'by'=>'Attribution',
		'by-sa'=>'Attribution-ShareAlike',
		'by-nd'=>'Attribution-NoDerivs',
		'by-nc'=>'Attribution-NonCommercial',
		'by-nc-sa'=>'Attribution-NonCommercial-ShareAlike',
		'by-nc-nd'=>'Attribution-NonCommercial-NoDerivs'
	);
	$cc_jurisdiction_readable=array(
		'intl'=>'International',
		'ca'=>'Canada',
		'au'=>'Australia',
		'uk'=>'United Kingdom (England and Whales)',
		'us'=>'United States'
	);
	if($cc_license != 'none'){
		return __('This work is licensed by '.mh_owner_link().' under a <a rel="license" href="http://creativecommons.org/licenses/'.$cc_license.'/'.$cc_readable[$cc_version].'/'.($cc_jurisdiction !== 'intl' ? $cc_jurisdiction : null).'">Creative Commons '.$cc_readable[$cc_license].' '.$cc_readable[$cc_version].' '.$cc_jurisdiction_readable[$cc_jurisdiction].' License</a>.');
	}else{
		return __('&copy; %1$s %2$s', date('Y'), mh_owner_link() );
	}
}



/*
** Google Analytics
*/
function mh_google_analytics($webPropertyID=null){
	$webPropertyID= get_theme_option('google_analytics');
	if ($webPropertyID!=null){
		echo "<script type=\"text/javascript\">

	  var _gaq = _gaq || [];
	  _gaq.push(['_setAccount', '".$webPropertyID."']);
	  _gaq.push(['_trackPageview']);

	  (function() {
	    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
	    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
	    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
	  })();

	</script>";
	}
}

/*
** Edit item link
*/
function link_to_item_edit($item=null,$pre=null,$post=null)
{
	if (is_allowed($item, 'edit')) {
		return $pre.'<a class="edit" href="'. html_escape(url('admin/items/edit/')).metadata('item','ID').'">'.__('Edit Item').'</a>'.$post;
	}
}

/*
** Display notice to admins if item is private
*/
function item_is_private($item=null){
	if(is_allowed($item, 'edit') && ($item->public)==0){
		return '<div class="item-is-private">This item is private.</div>';
	}else{
		return null;
	}
}

/*
** File item link
*/
function link_to_file_edit($file=null,$pre=null,$post=null)
{
	if (is_allowed($file, 'edit')) {
		return $pre.'<a class="edit" href="'. html_escape(url('admin/files/edit/')).metadata('file','ID').'">'.__('Edit File Details').'</a>'.$post;
	}
}


/*
** <video> placeholder image
*/
function mh_poster_url()
{
	$poster = get_theme_option('poster');

	$posterimg = $poster ? WEB_ROOT.'/files/theme_uploads/'.$poster : img('poster.png');

	return $posterimg;
}



/*
** Main logo
*/
function mh_lg_logo_url()
{
	$lg_logo = get_theme_option('lg_logo');

	$logo_img = $lg_logo ? WEB_ROOT.'/files/theme_uploads/'.$lg_logo : img('hm-logo.png');

	return $logo_img;
}




/*
** Icon file for mobile devices
** Used when the user saves the website to their device homescreen
** May also be used by other apps, including a few RSS Readers
*/
function mh_apple_icon_logo_url()
{
	$apple_icon_logo = get_theme_option('apple_icon_144');

	$logo_img = $apple_icon_logo ? WEB_ROOT.'/files/theme_uploads/'.$apple_icon_logo : img('Icon.png');

	return $logo_img;
}


/*
** Background image (home)
*/
function mh_bg_url()
{
	$bg_image = get_theme_option('bg_img');

	$img_url = $bg_image ? WEB_ROOT.'/files/theme_uploads/'.$bg_image : img('bg-home.png');

	return $img_url;
}



/*
** Custom link CSS colors
*/
function mh_link_color()
{
	$color = get_theme_option('link_color');

	if ( ($color) && (preg_match('/^#[a-f0-9]{6}$/i', $color)) ){
		return $color;
	}
}

function mh_secondary_link_color()
{
	$color = get_theme_option('secondary_link_color');

	if ( ($color) && (preg_match('/^#[a-f0-9]{6}$/i', $color)) ){
		return $color;
	}
}

/*
** iOS App ID
** see mh_ios_smartbanner()
*/
function mh_app_id()
{
	$appID = (get_theme_option('ios_app_id')) ? get_theme_option('ios_app_id') : false;

	return $appID;
}

/*
** iOS Smart Banner
** Shown not more than once per day
*/
function mh_ios_smart_banner(){
	// show the iOS Smart Banner once per day if the app ID is set
	if (mh_app_id()!=false){
		$AppBanner = 'Curatescape_AppBanner_'.mh_app_id();
		$numericID=str_replace('id', '', mh_app_id());
		if (!isset($_COOKIE[$AppBanner])){
			echo '<meta name="apple-itunes-app" content="app-id='.$numericID.'">';
			setcookie($AppBanner, true,  time()+86400); // 1 day
		}
	}
}


/*
** display an external RSS feed on a page using Javascript
*/
function mh_display_external_feed($feed_url=null,$excerpt=true){
?>
	<div id="feed-container"></div>
	<script type="text/javascript" src="https://www.google.com/jsapi"></script>
	<script type="text/javascript">google.load("feeds", "1");</script>		    
	<script>
	function stripHTML(dirtyString) {
	    var container = document.createElement('div');
	    container.innerHTML = dirtyString;
	    return container.textContent || container.innerText;
	}
	jQuery(jQuery(window)).load(function(){
		var feed = new google.feeds.Feed('<?php echo $feed_url;?>');
		feed.load(function (data) {
		    //console.dir(data);
		    var excerpt=<?php echo $feed_url;?>;
		    var html='<h2 class="feed-title">Latest news</h2>';	
		    jQuery.each( data.feed.entries, function(i, entry) {
				html+='<h3 class="feed-item-title"><a target="_blank" href="'+entry.link+'">'+entry.title+'</a></h3>';	
				html+='<div class="feed-item-auth"> by: '+(entry.author ? entry.author : feed_title)+'</div>';	
				html+='<div class="feed-item-content">'+(excerpt ? stripHTML(entry.content).substring(0,500)+' <a target="_blank" href="'+entry.link+'">Read more...<a>' : entry.content)+'</div>';		    
		    });
			jQuery('#feed-container').html(html);
		});
	});
	</script>	
<?php	
}



/*
** Character normalization
** Used to strip away unwanted or problematic formatting
*/
function mh_normalize_special_characters( $str )
{
	# Quotes cleanup
	$str = str_replace( chr(ord("`")), "'", $str );        # `
	$str = str_replace( chr(ord("´")), "'", $str );        # ´
	$str = str_replace( chr(ord("`")), "'", $str );        # `
	$str = str_replace( chr(ord("´")), "'", $str );        # ´
	$str = str_replace( chr(ord("´")), "'", $str );        # ´

	# Bullets, dashes, and trademarks
	$str = str_replace( chr(149), "&#8226;", $str );    # bullet ?
	$str = str_replace( chr(150), "&ndash;", $str );    # en dash
	$str = str_replace( chr(151), "&mdash;", $str );    # em dash
	$str = str_replace( chr(153), "&#8482;", $str );    # trademark
	$str = str_replace( chr(169), "&copy;", $str );    # copyright mark
	$str = str_replace( chr(174), "&reg;", $str );        # registration mark
	$str = str_replace( "&quot;", "\"", $str );        # "
	$str = str_replace( "&apos;", "\'", $str );        # '
	$str = str_replace( "&#039;", "'", $str );        # '
	$str = str_replace( "£", "&#163;", $str );        # pounds £ '

	$unwanted_array = array(    '?'=>'S', '?'=>'s', '?'=>'Z', '?'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
		'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U',
		'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss', 'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c',
		'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o',
		'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y');

	$str = strtr( $str, $unwanted_array );

	#For reasons yet unknown, only some servers may require an additional $unwanted_array item: 'height'=>'h&#101;ight'

	return $str;
}

?>