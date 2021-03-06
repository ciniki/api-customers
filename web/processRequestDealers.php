<?php
//
// Description
// -----------
// This function will generate the dealers page for the tenant.
//
// The dealer page can be referenced multiple ways depending on how th user arrives at the page.
// /dealers/dealer-permalink
// /dealers/location/country/province/state/dealer-permalink
// /dealers/category/cat-permalink/dealer-permalink
// /dealers/search/string/dealer-permalink
// 
//
// Arguments
// ---------
// ciniki:
// settings:        The web settings structure, similar to ciniki variable but only web specific information.
//
// Returns
// -------
//
function ciniki_customers_web_processRequestDealers(&$ciniki, $settings, $tnid, $args) {

    $uri_split = $args['uri_split'];
    
    //
    // Store the content created by the page
    // Make sure everything gets generated ok before returning the content
    //
    $content = '';
    $page_content = '';
    $page = array(
        'title'=>$args['page_title'],
        'breadcrumbs'=>$args['breadcrumbs'],
        'blocks'=>array(),
        );
    $base_url = $args['base_url'];
    $display_categories = 'no';
    $display_category = 'no';
    $display_locations = 'no';
    $display_location = 'no';
    $display_map = 'yes';
    $display_list = 'no';
    $display_profile = 'no';
    $maps = array();
    if( isset($settings['page-dealers-locations-map-names']) && $settings['page-dealers-locations-map-names'] == 'yes' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'locationNameMaps');
        $rc = ciniki_web_locationNameMaps($ciniki);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $maps = $rc['maps'];
    }

    //
    // Check if we are to display a dealer
    //
    if( isset($uri_split[0]) && $uri_split[0] != '' && $uri_split[0] != 'location' && $uri_split[0] != 'category' ) {
        $display_profile = 'yes';
        $dealer_permalink = $uri_split[0];
        $base_url .= '/' . $dealer_permalink;
        // Check for gallery image
        if( isset($uri_split[1]) && $uri_split[1] == 'gallery' && $uri_split[2] != '') {
            $image_permalink = $uri_split[2];
        }
    }

    //
    // Check if we are to display a dealer
    //
    elseif( isset($uri_split[0]) && $uri_split[0] == 'category' 
        && isset($uri_split[1]) && $uri_split[1] == '' 
        && isset($uri_split[2]) && $uri_split[2] == '' 
        ) {
        $display_profile = 'yes';
        $category = $uri_split[1];
        $dealer_permalink = $uri_split[2];
        $base_url .= "/category/$category/$dealer_permalink";
        // Check for gallery image
        if( isset($uri_split[3]) && $uri_split[3] == 'gallery' && isset($uri_split[4]) && $uri_split[4] != '') {
            $image_permalink = $uri_split[4];
            $ciniki['response']['head']['links'][] = array('rel'=>'canonical', 'href'=>$ciniki['request']['domain_base_url'] . '/dealers/' . $dealer_permalink);
        } else {
            $ciniki['response']['head']['links'][] = array('rel'=>'canonical',
                'href'=>$ciniki['request']['domain_base_url'] . '/dealers/' . $dealer_permalink
                );
        }
    }

    //
    // Check if we are to display a dealer
    //
/*  elseif( isset($uri_split[0]) && $uri_split[0] == 'location' 
        && isset($uri_split[1]) && $uri_split[1] != '' 
        && isset($uri_split[2]) && $uri_split[2] != '' 
        && isset($uri_split[3]) && $uri_split[3] != '' 
        && isset($uri_split[4]) && $uri_split[4] != '' 
        ) {
        $display_profile = 'yes';
        $country = $uri_split[1];
        $province = $uri_split[2];
        $state = $uri_split[3];
        $dealer_permalink = $uri_split[4];
        $base_url .= "/location/$country/$province/$state/$dealer_permalink";
        // Check for gallery image
        if( isset($uri_split[5]) && $uri_split[5] == 'gallery' 
            && isset($uri_split[6]) && $uri_split[6] != ''
            ) {
            $image_permalink = $uri_split[6];
            $ciniki['response']['head']['links'][] = array('rel'=>'canonical',
                'href'=>$ciniki['request']['domain_base_url'] . '/dealers/' . $dealer_permalink 
                    . '/gallery/' . $image_permalink
                );
            $base_url .= "/gallery/$image_permalink";
        } else {
            $ciniki['response']['head']['links'][] = array('rel'=>'canonical',
                'href'=>$ciniki['request']['domain_base_url'] . '/dealers/' . $dealer_permalink
                );
        }
    } */

    //
    // Display location information
    //
    elseif( isset($uri_split[0]) && $uri_split[0] == 'location' 
        && isset($uri_split[1]) && $uri_split[1] != '' 
        ) {
        $country_permalink = $uri_split[1];
        $country_name = rawurldecode($country_permalink);
        $country_print_name = (isset($maps[strtolower($country_name)]['name'])?$maps[strtolower($country_name)]['name']:$country_name);
        $base_url .= '/location/' . $country_permalink;
        $page['breadcrumbs'][] = array('name'=>$country_print_name, 'url'=>$base_url);
        $display_locations = 'yes';
        $display_map = 'yes';
        if( isset($uri_split[2]) && $uri_split[2] != '' ) {
            $province_permalink = $uri_split[2];
            $province_name = rawurldecode($province_permalink);
            $province_print_name = (isset($maps[strtolower($country_name)]['provinces'][strtolower($province_name)]['name'])?$maps[strtolower($country_name)]['provinces'][strtolower($province_name)]['name']:$province_name);
            $base_url .= '/' . $province_permalink;
            if( $province_permalink != '-' ) {
                $page['breadcrumbs'][] = array('name'=>$province_print_name, 'url'=>$base_url);
            }
            $display_map = 'yes';
            // Check if there is a city specified
            if( isset($uri_split[3]) && $uri_split[3] != '' ) {
                $city_permalink = $uri_split[3];
                $city_name = rawurldecode($city_permalink);
                $city_print_name = rawurldecode($city_permalink);
                $base_url .= '/' . $city_permalink;
                if( $city_permalink != '-' ) {
                    $page['breadcrumbs'][] = array('name'=>$city_print_name, 'url'=>$base_url);
                }
                $display_location = 'yes';
                $display_locations = 'no';
                $display_map = 'yes';
                $display_list = 'yes';
                //
                // Check if dealer specified
                //
                if( isset($uri_split[4]) && $uri_split[4] != '' ) {
                    $display_profile = 'yes';
                    $dealer_permalink = $uri_split[4];
                    $base_url .= "/$dealer_permalink";
                    //
                    // Check for gallery image
                    //
                    if( isset($uri_split[5]) && $uri_split[5] == 'gallery' 
                        && isset($uri_split[6]) && $uri_split[6] != ''
                        ) {
                        $image_permalink = $uri_split[6];
                        $ciniki['response']['head']['links'][] = array('rel'=>'canonical', 'href'=>$ciniki['request']['domain_base_url'] . '/dealers/' . $dealer_permalink );
                    } else {
                        $ciniki['response']['head']['links'][] = array('rel'=>'canonical', 'href'=>$ciniki['request']['domain_base_url'] . '/dealers/' . $dealer_permalink);
                    }
                }
            }
        }
    }

    //
    // Display the list of dealers if a specific one isn't selected
    //
    else {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processContent');

        //
        // Should the dealer categories be displayed
        //
        if( isset($settings['page-dealers-categories-display']) 
            && ($settings['page-dealers-categories-display'] == 'wordlist'
                || $settings['page-dealers-categories-display'] == 'wordcloud' )
            && isset($ciniki['tenant']['modules']['ciniki.customers']['flags']) 
            && ($ciniki['tenant']['modules']['ciniki.customers']['flags']&0x20) > 0 
            ) {
            $display_categories = 'yes';
        }
        //
        // Should the dealer locations be displayed
        //
        if( isset($settings['page-dealers-locations-display']) 
            && ($settings['page-dealers-locations-display'] == 'wordlist'
                || $settings['page-dealers-locations-display'] == 'wordcloud' )
            && isset($ciniki['tenant']['modules']['ciniki.customers']['flags']) 
            && ($ciniki['tenant']['modules']['ciniki.customers']['flags']&0x10) > 0 
            ) {
            $display_locations = 'yes';
            $base_url .= '/location';
        }
    }

    //
    // Generate the map data.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'web', 'dealersMapMarkers');
    $rc = ciniki_customers_web_dealersMapMarkers($ciniki, $settings, $ciniki['request']['tnid'], array(
        'country'=>(isset($country_name)?$country_name:'')));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['markers']) ) {
        if( isset($rc['country']) && (!isset($country_name) || $country_name == '') ) {
            $country_name = $rc['country'];
        }
        if( isset($rc['province']) && (!isset($province_name) || $province_name == '') ) {
            $province_name = $rc['province'];
        }
        $json = 'var gmap_data = ' . json_encode($rc['markers']) . ';';
        // Removed cache map data file so it can be broken down by country
/*      $filename = '/' . sprintf('%02d', ($ciniki['request']['tnid']%100)) . '/'
            . sprintf('%07d', $ciniki['request']['tnid'])
            . '/dealers/gmap_data.js';
        $data_filename = $ciniki['request']['cache_dir'] . $filename;
        if( !file_exists(dirname($data_filename)) ) {
            mkdir(dirname($data_filename), 0755, true);
        }
        file_put_contents($data_filename, $json);
        $ciniki['response']['head']['scripts'][] = array('src'=>$ciniki['request']['cache_url'] . $filename, 
            'type'=>'text/javascript'); */
        $ciniki['request']['inline_javascript'] .= '<script type="text/javascript">' . $json . '</script>';
    }

    //
    // Get the content for the page
    //

    //
    // Display the dealer profile page
    //
    if( $display_profile == 'yes' ) {
        $display_categories = 'no';
        $display_category = 'no';
        $display_locations = 'no';
        $display_location = 'no';
        $display_map = 'no';
        $display_list = 'no';
        
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'web', 'dealerDetails');
        ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processURL');

        //
        // Get the dealer information
        //
        $rc = ciniki_customers_web_dealerDetails($ciniki, $settings, $ciniki['request']['tnid'], $dealer_permalink);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $dealer = $rc['dealer'];
        $page['title'] = $dealer['dealer'];
        $page['container_class'] = 'dealer-page';
        $page['breadcrumbs'][] = array('name'=>$dealer['dealer'], 'url'=>$base_url);

        if( isset($dealer['synopsis']) && $dealer['synopsis'] != '' ) {
            $ciniki['response']['head']['og']['description'] = strip_tags($dealer['synopsis']);
        } elseif( isset($dealer['description']) && $dealer['description'] != '' ) {
            $ciniki['response']['head']['og']['description'] = strip_tags($dealer['description']);
        }

        $page_title = $dealer['name'];
        if( isset($image_permalink) && $image_permalink != '' ) {
            $page['title'] = "<a href='$base_url'>" . $dealer['name'] . "</a>";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'galleryFindNextPrev');
            $rc = ciniki_web_galleryFindNextPrev($ciniki, $dealer['images'], $image_permalink);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( $rc['img'] == NULL ) {
                $page['blocks'][] = array('type'=>'message', 'content'=>"I'm sorry, but we can't seem to find the image you requested.");
            } else {
                $page['breadcrumbs'][] = array('name'=>$rc['img']['title'], 'url'=>$base_url . '/gallery/' . $image_permalink);
                if( $rc['img']['title'] != '' ) {
                    $page['title'] .= ' - ' . $rc['img']['title'];
                }
                $block = array('type'=>'galleryimage', 'section'=>'gallery-primary-image', 'primary'=>'yes', 'image'=>$rc['img']);
                if( $rc['prev'] != null ) {
                    $block['prev'] = array('url'=>$base_url . '/gallery/' . $rc['prev']['permalink'], 'image_id'=>$rc['prev']['image_id']);
                }
                if( $rc['next'] != null ) {
                    $block['next'] = array('url'=>$base_url . '/gallery/' . $rc['next']['permalink'], 'image_id'=>$rc['next']['image_id']);
                }
                $page['blocks'][] = $block;
                $page['blocks'][] = array('type'=>'gallery', 'title'=>'Additional Images', 'section'=>'gallery-images', 'base_url'=>$base_url . '/gallery', 'images'=>$dealer['images']);
            }
        } else {
            $aside_display = 'block';
            if( isset($dealer['image_id']) && $dealer['image_id'] > 0 ) {
                $page['blocks'][] = array('type'=>'asideimage', 'section'=>'primary-image', 'id'=>'aside-image', 'primary'=>'yes', 'image_id'=>$dealer['image_id'], 'caption'=>$dealer['image_caption']);
                $aside_display = 'none';
            }
            if( isset($dealer['latitude']) && $dealer['latitude'] != 0 && isset($dealer['longitude']) && $dealer['longitude'] != 0 ) {
                if( isset($dealer['image_id']) && $dealer['image_id'] > 0 ) {
                    $toggle_map = "<a href='javascript: toggleMap();'>map</a>";
                }
                if( !isset($ciniki['request']['inline_javascript']) ) {
                    $ciniki['request']['inline_javascript'] = '';
                }
                $ciniki['request']['inline_javascript'] .= ''
                    . '<script type="text/javascript">'
                    . 'function toggleMap() {'
                        . "var i = document.getElementById('aside-image');\n"
                        . "var m = document.getElementById('aside-map');\n"
                        . "if(i!=null){"
                            . "if(i.style.display!='none') {i.style.display='none';m.style.display='block'; loadMap();"
                            . "} else {i.style.display='block';m.style.display='none'; "
                            . "}\n"
                        . "}"
                    . '};'
                    . ((!isset($dealer['image_id']) || $dealer['image_id'] == 0)?'window.onload=loadMap;':'')
                    . '</script>';
                $page['blocks'][] = array('type'=>'map', 'section'=>'primary-map', 'id'=>'aside-map', 'aside'=>'yes', 'display'=>$aside_display, 'latitude'=>$dealer['latitude'], 'longitude'=>$dealer['longitude']);
            }

                
            //
            // Add description
            //
            $description = '';
            if( isset($dealer['description']) && $dealer['description'] != '' ) {
                $description = strip_tags($dealer['description']);
                $description = $dealer['description'];
//            } elseif( isset($dealer['synopsis']) && $dealer['synopsis'] != '' ) {
//                $description = strip_tags($dealer['synopsis']);
            }
            $page['blocks'][] = array('type'=>'content', 'section'=>'content', 'title'=>'', 'content'=>$description);

            //
            // Add contact_info
            //
            $cinfo = '';
            if( isset($dealer['addresses']) ) {
                foreach($dealer['addresses'] as $address) {
                    $addr = '';
                    if( $address['address1'] != '' ) {
                        $addr .= ($addr!=''?'<br/>':'') . $address['address1'];
                    }
                    if( $address['address2'] != '' ) {
                        $addr .= ($addr!=''?'<br/>':'') . $address['address2'];
                    }
                    if( $address['city'] != '' ) {
                        $addr .= ($addr!=''?'<br/>':'') . $address['city'];
                    }
                    if( $address['province'] != '' ) {
                        $addr .= ($addr!=''?', ':'') . $address['province'];
                    }
                    if( $address['postal'] != '' ) {
                        $addr .= ($addr!=''?'  ':'') . $address['postal'];
                    }
                    if( $addr != '' ) {
                        $cinfo .= ($cinfo!=''?'<br/>':'') . "$addr";
                    }
                }
            }
            if( isset($dealer['phones']) ) {
                foreach($dealer['phones'] as $phone) {
                    if( $phone['phone_label'] != '' && $phone['phone_number'] != '' ) {
                        $cinfo .= ($cinfo!=''?'<br/>':'') . $phone['phone_label'] . ': ' . $phone['phone_number'];
                    } elseif( $phone['phone_number'] != '' ) {
                        $cinfo .= ($cinfo!=''?'<br/>':'') . $phone['phone_number'];
                    }
                }
            }
            if( isset($dealer['emails']) ) {
                foreach($dealer['emails'] as $email) {
                    if( $email['email'] != '' ) {
                        $cinfo .= ($cinfo!=''?'<br/>':'') . '<a href="mailto:' . $email['email'] . '">' . $email['email'] . '</a>';
                    }
                }
            }
            if( isset($toggle_map) && $toggle_map != '' ) {
                $cinfo .= "<br/>" . ($toggle_map!=''?"(" . $toggle_map . ")":'');
            }

            if( $cinfo != '' ) {
                $page['blocks'][] = array('type'=>'content', 'section'=>'dealer-contact', 'title'=>'Contact Info', 'content'=>$cinfo);
            }

            if( isset($dealer['links']) ) {
                $page['blocks'][] = array('type'=>'links', 'section'=>'dealer-links', 'title'=>'Website' . (count($dealer['links']) > 1 ? 's' : ''), 'links'=>$dealer['links']);
            }
            // Add gallery
            if( isset($dealer['images']) && count($dealer['images']) > 0 ) {
                $page['blocks'][] = array('type'=>'gallery', 'title'=>'Additional Images', 'section'=>'additional-images', 'base_url'=>$base_url . '/gallery', 'images'=>$dealer['images']);
            }
        }
    } 

    //
    // Display a location
    //
    if( $display_location == 'yes' ) {
        
    }
        
    //
    // Display the list of categories
    //
    if( $display_categories == 'yes' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'web', 'dealerTagCloud');
        $rc = ciniki_customers_web_tagCloud($ciniki, $settings, $ciniki['request']['tnid'], 60);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }

        //
        // Process the tags
        //
        if( $settings['page-dealers-categories-display'] == 'wordlist' ) {
            if( isset($rc['tags']) && count($rc['tags']) > 0 ) {
                $page['blocks'][] = array('type'=>'taglist', 'section'=>'dealer-categories', 'base_url'=>$base_url, 'tags'=>$rc['tags']);
            } else {
                $page['blocks'][] = array('type'=>'content', 'content'=>"I'm sorry, there are no dealers found");
            }
        } elseif( $settings['page-dealers-categories-display'] == 'wordcloud' ) {
            if( isset($rc['tags']) && count($rc['tags']) > 0 ) {
                $page['blocks'][] = array('type'=>'tagcloud', 'section'=>'dealer-categories', 'base_url'=>$base_url, 'tags'=>$rc['tags']);
            } else {
                $page['blocks'][] = array('type'=>'content', 'content'=>"I'm sorry, there are no dealers found");
            }
        }
    }

    //
    // Display the list of countries/provinces/cities
    //
    if( $display_locations == 'yes' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'web', 'dealerLocationTagCloud');
        $rc = ciniki_customers_web_dealerLocationTagCloud($ciniki, $settings, 
            $ciniki['request']['tnid'], array(
                'country'=>(isset($country_name)?$country_name:''),
                'province'=>(isset($province_name)?$province_name:''),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }

        if( isset($rc['countries']) ) {
            $tags = $rc['countries'];
            foreach($tags as $tid => $tag) {
                $tags[$tid]['permalink'] = rawurlencode($tag['name']);
                if( $tag['num_tags'] < 10 ) {
                    $tags[$tid]['permalink'] .= '/-/-';
                }
                if( isset($maps[strtolower($tag['name'])]['name']) ) {
                    $tags[$tid]['name'] = $maps[strtolower($tag['name'])]['name'];
                }
            }
            if( !isset($settings['page-dealers-location-countries-display'])
                || $settings['page-dealers-location-countries-display'] == 'wordcloud' ) {
                $page['blocks'][] = array('type'=>'tagcloud', 'section'=>'dealer-countries', 'base_url'=>$base_url, 'tags'=>$tags);
            } elseif( $settings['page-dealers-location-countries-display'] == 'wordlist' ) {
                $page['blocks'][] = array('type'=>'taglist', 'section'=>'dealer-countries', 'base_url'=>$base_url, 'tags'=>$tags);
            }
        } elseif( isset($rc['provinces']) ) {
            $tags = $rc['provinces'];
            foreach($tags as $tid => $tag) {
                $tags[$tid]['permalink'] = rawurlencode($tag['name']);
                if( $tag['num_tags'] < 10 ) {
                    $tags[$tid]['permalink'] .= '/-';
                }
                // Map provinces/states to full names
                if( isset($maps[strtolower($country_name)]['provinces'][strtolower($tag['name'])]['name']) ) {
                    $tags[$tid]['name'] = $maps[strtolower($country_name)]['provinces'][strtolower($tag['name'])]['name'];
                }
            }
            if( !isset($settings['page-dealers-location-provinces-display'])
                || $settings['page-dealers-location-provinces-display'] == 'wordcloud' ) {
                $page['blocks'][] = array('type'=>'tagcloud', 'section'=>'dealer-provinces', 'base_url'=>$base_url, 'tags'=>$tags);
            } elseif( $settings['page-dealers-location-provinces-display'] == 'wordlist' ) {
                $page['blocks'][] = array('type'=>'taglist', 'section'=>'dealer-provinces', 'base_url'=>$base_url, 'tags'=>$tags);
            }
        } elseif( isset($rc['cities']) ) {
            $tags = $rc['cities'];
            if( !isset($settings['page-dealers-location-cities-display'])
                || $settings['page-dealers-location-cities-display'] == 'wordcloud' ) {
                $page['blocks'][] = array('type'=>'tagcloud', 'section'=>'dealer-cities', 'base_url'=>$base_url, 'tags'=>$tags);
            } elseif( $settings['page-dealers-location-cities-display'] == 'wordlist' ) {
                $page['blocks'][] = array('type'=>'taglist', 'section'=>'dealer-cities', 'base_url'=>$base_url, 'tags'=>$tags);
            }
        } else {
            return array('stat'=>'404', 'err'=>array('code'=>'ciniki.customers.212', 'msg'=>'No dealers found for this .'));
        }
    } 

    //
    // Get the list of dealers
    //
    if( $display_map == 'yes' || $display_list == 'yes' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'web', 'dealerList');
        $rc = ciniki_customers_web_dealerList($ciniki, $settings, $ciniki['request']['tnid'], 
            array('format'=>'2dlist', 
                'country'=>(isset($country_name)?$country_name:''),
                'province'=>(isset($province_name)?$province_name:''),
                'city'=>(isset($city_name)?$city_name:''),
                ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $dealers = $rc['dealers'];
    }

    //
    // Display the map of the dealers 
    //
    if( $display_map == 'yes' && isset($dealers) ) {
        // 
        // Setup the javascript to display the map
        //
        $center_addr = '';
        $center_zoom = 2;
        $map_content = '';
        if( isset($country_name) && $country_name != '' ) {
            $center_addr = $country_name;
            $center_zoom = 3;
            foreach($maps as $country) {
                if( strtolower($country['name']) == strtolower($country_name) ) {
                    $map_country = $country['provinces'];
                    break;
                }
            }
            if( isset($province_name) && $province_name != '' && $province_name != '-' ) {
                if( isset($maps[strtolower($country_name)]['provinces'][strtolower($province_name)]['google']) ) {
                    $center_addr = $maps[strtolower($country_name)]['provinces'][strtolower($province_name)]['google'] . ', ' . $center_addr;
                } elseif( isset($map_country) && isset($map_country[strtolower($province_name)]['code']) ) {
                    $center_addr = $map_country[strtolower($province_name)]['code'] . ', ' . $center_addr;
                } else {
                    $center_addr = $province_name . ', ' . $center_addr;
                }
                $center_zoom = 5;
            } 
            if( isset($city_name) && $city_name != '' && $city_name != '-' ) {
                $center_addr = $city_name . ', ' . $center_addr;
                $center_zoom = 7;
            }
        }
        $ciniki['request']['inline_javascript'] .= ''
            . '<script type="text/javascript">'
            . 'var map;'
            . 'function gmap_start() {';
        if( $center_addr != '' ) {
            $ciniki['request']['inline_javascript'] .= 'var geocoder = new google.maps.Geocoder();'
                .  'geocoder.geocode({"address":"' . $center_addr . '"}, function(results, status) {'
                    . 'if(status==google.maps.GeocoderStatus.OK){'
                        . 'gmap_initialize(results[0].geometry.location.lat(), results[0].geometry.location.lng(),' . $center_zoom . ',results[0].geometry.viewport);'
                    . '}'
                . '});';
        } else {
            $ciniki['request']['inline_javascript'] .= 'gmap_initialize(20,0,2);';
        }
        $ciniki['request']['inline_javascript'] .= ''
            . '};'
            . 'function gmap_initialize(lat,lng,z,v) {'
                    . 'var myLatLng = new google.maps.LatLng(lat,lng);'
                    . 'var mapOptions = {'
                    . 'zoom: z,'
                    . 'center: myLatLng,'
                    . 'panControl: false,'
                    . 'zoomControl: true,'
                    . 'scaleControl: true,'
                    . 'mapTypeId: google.maps.MapTypeId.ROADMAP'
                . '};'
                . 'map = new google.maps.Map(document.getElementById("googlemap"), mapOptions);'
                . 'if(v!=null){map.fitBounds(v);};'
                . 'gmap_refresh();'
            . '};'
            . "\n"
            . 'function gmap_refresh() {'
                . 'var markers=[];'
                . 'for(i in gmap_data) {'
                    . 'gmap_showMarker(gmap_data[i].y,gmap_data[i].x,gmap_data[i].t,'
                        . '"<p><b>"+gmap_data[i].t+"</b></p><p>"+gmap_data[i].c+"</p>");'
                . '}'
            . '};'
            . 'function gmap_showMarker(y,x,t,c) {'
                . 'var latLng = new google.maps.LatLng(y, x);'
                . 'var marker = new google.maps.Marker({'
                    . 'position:latLng,'
                    . 'map: map,'
                    . 'title: t,'
                . '});'
                . 'var infowindow = new google.maps.InfoWindow({'
                    . 'content:c'
                . '});'
                . 'google.maps.event.addListener(marker, "click", function() { infowindow.open(map, marker);});'
            . '}'
            . "\n"
            . 'function loadMap() {'
                . 'var script = document.createElement("script");'
                . 'script.type = "text/javascript";'
                . 'script.src = "' . ($ciniki['request']['ssl']=='yes'?'https':'http') . '://maps.googleapis.com/maps/api/js?key=' . $ciniki['config']['ciniki.web']['google.maps.api.key'] . '&callback=gmap_start";'
                . 'document.body.appendChild(script);'
            . '};'
            . "\n"
            . 'window.onload = loadMap;'
            . "\n"
            . '</script>'
            . '';
        $map_content .= '<div class="googlemap" id="googlemap"></div>';
        $page['blocks'][] = array('type'=>'content', 'section'=>'dealer-google-map', 'html'=>$map_content);
    }

    if( $display_list == 'yes' && isset($dealers) ) {
        if( count($dealers) > 0 ) {
            $page['blocks'][] = array('type'=>'cilist', 'section'=>'dealer-list', 'base_url'=>$base_url, 'notitle'=>'yes', 'categories'=>$dealers);
        } else {
            $page['blocks'][] = array('type'=>'content', 'section'=>'dealer-list', 'content'=>"No dealers found for this area");
        }
    }

    return array('stat'=>'ok', 'page'=>$page);
}
?>
