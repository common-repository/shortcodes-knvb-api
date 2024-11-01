<?php
// enqueue style sheet
wp_enqueue_style( 'knvb-api-style', plugins_url('shortcode-knvb-api-style.css', __FILE__ ) );

// include the RainTPL class
include "inc/rain.tpl.class.php";

class KnvbClient {
  const BASEURI = 'http://api.knvbdataservice.nl/api';

  public $session_id;

  protected $apiKey;
  protected $apiPath;
  public    $clubName;

  public function __construct($apiKey, $apiPath, $clubName = null) {
    $this->apiKey = $apiKey;
    $this->apiPath = $apiPath;
    // $this->clubName = $clubName;

    $init_path = '/initialisatie/'.$this->apiPath;

    // initialiseer api request voor sessie-ID
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'HTTP_X_APIKEY' => $this->apiKey,
      'Content-Type' => 'application/json'
    ));
    curl_setopt($ch, CURLOPT_URL, KnvbClient::BASEURI."$init_path");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    $result = curl_exec($ch);
    curl_close($ch);

    $json_data = json_decode($result);

    // verzamel benodigde parameters
    $this->session_id = $json_data->List[0]->PHPSESSID;
    $this->clubName = ( !empty($clubName) ? $clubName : $json_data->List[0]->clubnaam );
  }

  public function doRequest($url_path = '/teams', $extra = NULL, $get_status = false) {

    $hash = md5($this->apiKey.'#'.$url_path.'#'.$this->session_id);

    // voer de 'echte' request uit
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'HTTP_X_APIKEY' => $this->apiKey,
      'Content-Type' => 'application/json'
    ));
    curl_setopt($ch,
                CURLOPT_URL,
                KnvbClient::BASEURI.$url_path.'?PHPSESSID='.$this->session_id.'&hash='.$hash.'&'.$extra);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 12);

    $result = curl_exec($ch);
    curl_close($ch);

    $json_data = json_decode($result);

    if ( $get_status ) {
      return $json_data;
    }


    if(isset($json_data) && property_exists($json_data, 'List')) {
      if(property_exists($json_data->List[0], 'Datum') &&
         property_exists($json_data->List[0], 'Tijd') &&
         isset($json_data->List[0]->Datum) &&
         isset($json_data->List[0]->Tijd)) {
        usort($json_data->List, function($a, $b) {
          $dt_a = str_replace('-', '', $a->Datum) . $a->Tijd;
          $dt_b = str_replace('-', '', $b->Datum) . $b->Tijd;

          return strcmp($dt_a, $dt_b);
        });
      }
      else if( is_array( $json_data->List ) && count( $json_data->List ) > 1
               && $json_data->List[0] instanceof stdClass
               && property_exists($json_data->List[0], 'pouleid') && property_exists($json_data->List[0], '0')
      ) { // if multiple poules are returned

        $NewList[] = 'multipoules';
        foreach( $json_data->List as $key => $value) {
          $PouleId = $value->pouleid;
          $oPoule     = $json_data->List[$key];
          $arrNewList = array();
          $nIt        = 0;
          while ( property_exists( $oPoule, $nIt ) ) {
            $oPoule->$nIt->pouleid = $PouleId;
            $arrNewList[] = $oPoule->$nIt;
            $nIt ++;
          }
          $NewList[$PouleId] = $arrNewList;
        }
        $json_data->List = $NewList;
      }
    return $json_data->List;
    }

    return NULL;
  }

  public function getStatus() {
    $request = $this->doRequest('/teams', NULL, true);

    if ( empty($request) ) {
      $result['apiStatus'] = false;
      $result['connectedStatus'] = false;
    }
    elseif ( !empty($request) && $request->errorcode != 1000 ) {
      $result['apiStatus'] = true;
      $result['connectedStatus'] = false;
    }
    elseif ( !empty($request) && $request->errorcode == 1000 ) {
      $result['apiStatus'] = true;
      $result['connectedStatus'] = true;
    }
    return $result;
  }

  public function getData($url_path = '/teams', $extra = NULL, $template_file = NULL, $fields = NULL) {
    // timestamp
    $timestamp = current_time('timestamp', 0);
    // create array with extra parameters
    if( isset($extra) && !empty($extra) ) {
      $parameters = array();
      $exploded = explode('&', $extra);
      foreach( $exploded as $key => $parameterstring ) {
        $parameterexploded = explode('=', $parameterstring);
        $parameters[$parameterexploded[0]] = $parameterexploded[1];
      }
    }
    // create array with fields parameters
    if( isset($fields) && !empty($fields) ) {
      $dontshow = explode('|', $fields);
    }

    if(
      isset($parameters) &&
      array_key_exists('weeknummer', $parameters) &&
      ($parameters['weeknummer'] === 'P' || $parameters['weeknummer'] === 'C' || $parameters['weeknummer'] === 'N')
    ) {
      $wn_current = ltrim(date('W', $timestamp), '0');
      $wn_previous = ltrim(date('W', strtotime('-7 days', $timestamp)), '0');
      $wn_next = ltrim(date('W', strtotime('+7 days', $timestamp)), '0');
      $extra = str_replace(array('weeknummer=C',
                                 'weeknummer=P',
                                 'weeknummer=N'),
                           array('weeknummer='.$wn_current,
                                 'weeknummer='.$wn_previous,
                                 'weeknummer='.$wn_next),
                           $extra);
      if( $parameters['weeknummer'] === 'P' ) { $parameters['weeknummer'] = $wn_previous; }
      elseif( $parameters['weeknummer'] === 'C' ) { $parameters['weeknummer'] = $wn_current; }
      elseif( $parameters['weeknummer'] === 'N' ) { $parameters['weeknummer'] = $wn_next; }
    }

    $pluginFolder = dirname(__FILE__);

    if(!isset($template_file) || $template_file == 'template') {
      if ( strpos($url_path, 'wedstrijd/') ) {
        $template_file = 'wedstrijd';
      }
      else {
        $template_file = basename($url_path);
      }

      if(strpos($extra, 'slider=1') > -1) {
        // logica voor de slider: 'slider=1'
        $template_file = $template_file.'_slider';
      }
    }

    RainTPL::configure('base_url', NULL);
    RainTPL::configure('tpl_dir', $pluginFolder.'/templates/');
    RainTPL::configure('cache_dir', ABSPATH . 'wp-content/uploads/shortcode-knvb-api/cache/');
    RainTPL::configure('path_replace', false);

    $tpl = new RainTPL;

    // if using cache (15 min)
    $cache_key = sanitize_file_name($url_path.'_'.$extra);
    $cachetime = esc_attr(get_option('knvb_api_cachetime')) * 60;
    if( $cachetime && $cachetime > 0 && $cache = $tpl->cache($template_file, $expire_time = $cachetime, $cache_id = $cache_key) ) {
      return $cache;
    }
    // als het niet in de cache zit
    else {
      /* Club kan als weeknummer parameter C, P, N meegeven, maar ook een combinatie daarvan. Omdat we aan de API de resultaten van maar 1 week tegelijk
      ** kunnen vragen moeten er meerdere calls gedaan worden. */
      if(
        isset($parameters) &&
        array_key_exists('weeknummer', $parameters) &&
        ($parameters['weeknummer'] === 'PCN' || $parameters['weeknummer'] === 'PC' || $parameters['weeknummer'] === 'PN' || $parameters['weeknummer'] === 'CN')
      ) {
        // define weeknumer number
        $wn_current = ltrim(date('W', $timestamp), '0');
        $wn_previous = ltrim(date('W', strtotime('-7 days', $timestamp)), '0');
        $wn_next = ltrim(date('W', strtotime('+7 days', $timestamp)), '0');
        // make some api calls
        switch ($parameters['weeknummer']) {
          case 'PCN':
            // setup calls for Previous, Current & Next week
            $extra_previous = str_replace('weeknummer=PCN', 'weeknummer='.$wn_previous, $extra);
            $extra_current = str_replace('weeknummer=PCN', 'weeknummer='.$wn_current, $extra);
            $extra_next = str_replace('weeknummer=PCN', 'weeknummer='.$wn_next, $extra);
            // make calls for Previous, Current & Next week
            $list_previous = $this->doRequest($url_path, $extra_previous);
            $list_current = $this->doRequest($url_path, $extra_current);
            $list_next = $this->doRequest($url_path, $extra_next);
            // collect output
            $list_big = array(
              'P' => $list_previous,
              'C' => $list_current,
              'N' => $list_next
            );
            break;
          case 'PC':
            // setup calls for Previous & Current week
            $extra_previous = str_replace('weeknummer=PC', 'weeknummer='.$wn_previous, $extra);
            $extra_current = str_replace('weeknummer=PC', 'weeknummer='.$wn_current, $extra);
            // make calls for Previous & Current week
            $list_previous = $this->doRequest($url_path, $extra_previous);
            $list_current = $this->doRequest($url_path, $extra_current);
            // collect output
            $list_big = array(
              'P' => $list_previous,
              'C' => $list_current
            );
            break;
          case 'PN':
            // setup calls for Previous & Next week
            $extra_previous = str_replace('weeknummer=PN', 'weeknummer='.$wn_previous, $extra);
            $extra_next = str_replace('weeknummer=PN', 'weeknummer='.$wn_next, $extra);
            // make calls for Previous & Next week
            $list_previous = $this->doRequest($url_path, $extra_previous);
            $list_next = $this->doRequest($url_path, $extra_next);
            // collect output
            $list_big = array(
              'P' => $list_previous,
              'N' => $list_next
            );
            break;
          case 'CN':
            // setup calls for Current & Next week
            $extra_current = str_replace('weeknummer=CN', 'weeknummer='.$wn_current, $extra);
            $extra_next = str_replace('weeknummer=CN', 'weeknummer='.$wn_next, $extra);
            // make calls for Current & Next week
            $list_current = $this->doRequest($url_path, $extra_current);
            $list_next = $this->doRequest($url_path, $extra_next);
            // collect output
            $list_big = array(
              'C' => $list_current,
              'N' => $list_next
            );
            break;
        }
        // define list
        if( isset($list_big) && !empty($list_big) ) {
          foreach( $list_big as $weekletter => $matches ) {
            if( !empty($matches) ) {
              foreach( $matches as $key => $match ) {
                $list_multiweek[] = $match;
              }
            }
          }
        }
      }
      /* Om op de instellingen pagina een voorbeeld van alle shortcodes te kunnen
      ** geven moet er meer dan 1 API call gedaan worden */
      if( $extra == 'settings_screen' ) {
        /* Als de url_path general shortcodes, parameter shortcodes of example shortcodes is, dan hoeven we geen data uit de API te halen.
        ** Dit zijn statische templates */
        if ( $url_path != '/settings_screen_general_shortcodes' && $url_path != '/settings_screen_parameter_shortcodes' && $url_path != '/settings_screen_example_shortcodes') {
          // haal alle teams op
          $teams = $this->doRequest('/teams');
          // haal alle wedstrijden op
          $wedstrijden = $this->doRequest('/wedstrijden', 'weeknummer=A');
          // haal alle competities op
          $competities = $this->doRequest('/competities');

          // voeg de competies per team toe aan de team details
          foreach ($teams as $key => $teamdetails) {
            $teamid = $teamdetails->teamid;
            foreach ($competities as $key => $competitiedetails) {
              if( $competitiedetails->TeamId == $teamid ){
                $teamdetails->competities[] = $competitiedetails;
              }
            }
          }

          // voeg alle data toe aan het tpl object zodat we deze in het template kunnen gebruiken
          $tpl->assign('teams',$teams);
          $tpl->assign('wedstrijden',$wedstrijden);
          $tpl->assign('competities',$competities);
        }
      }
      else { /* Als het een normale shortcode betreft (1 API call) */
        // Even checken er al een list is met resultaten van Previous en/of Current en/og Next week
        if( isset($list_multiweek) && !empty($list_multiweek) ){
          $list = $list_multiweek;
        }
        // Als er nog geen list is met resultaten van Previous en/of Current en/og Next week; doe normale API call
        else {
          $list = $this->doRequest($url_path, $extra);
        }

        $tpl->assign('clubname', $this->clubName);
        $tpl->assign('logo', strpos($extra, 'logo=1') > -1);
        $tpl->assign('thuisonly', strpos($extra, 'thuisonly=1') > -1);
        $tpl->assign('uitonly', strpos($extra, 'uitonly=1') > -1);
        $tpl->assign('showheaders', strpos($extra, 'headers=1') > -1);
        $tpl->assign('reverse', strpos($extra, 'reverse=1') > -1);

        // precho($list);

        /*
        ** Sorteren van de data de terug komt uit de API en in $list zit
        */
        // Als de call resultaten betreft, reverse order zodat laatst gespeelde wedstrijden bovenaan komen te staan
        if(isset($list) && basename($url_path) == 'results') {
          $list = array_reverse($list);
        }
        // Als de call wedstrijden betreft en de paramter ... is mee gegeven; sorteer de output per datum op team van hoog naar laag (default API sortering is per datum op aanvangsttijd)
        if( isset($list) && basename($url_path) == 'wedstrijden' && strpos($extra, 'sorterenopteam=1') ) {
          $teamscall = $this->doRequest('/teams');
          // precho($teamscall);
          if( empty($teamscall) ) {
            // We kunnen niet op team sorteren als de teams call leeg terug komt :(. Helaas.
          }
          else {
            // eerst een lijstje maken met alle speeldata erin
            $matchesperdateperteam = array();
            foreach( $list as $key => $match ) {
              $matchesperdateperteam[$match->Datum] = '';
              // per speeldag lijstje van teams van hoog naar laag toevoegen
              foreach( $teamscall as $key => $team ) {
                $matchesperdateperteam[$match->Datum][$team->teamid] = '';
              }
            }
            // door de wedstrijden heen loopen en ze op de juiste plek in de matchesperdateperteam array plaatsen
            foreach( $list as $key => $match ) {
              // als de wedstrijd datum in de $matchesperdateperteam voor komt; uitzoeken welk team op die datum speelt
              if( array_key_exists($match->Datum, $matchesperdateperteam) ) {
                // als het thuisteam speelt op die datum; plaats wedstrijd onder dat team op die datum in de $matchesperdateperteam array
                if( array_key_exists($match->ThuisTeamId, $matchesperdateperteam[$match->Datum]) ) {
                  $matchesperdateperteam[$match->Datum][$match->ThuisTeamId][] = $match;
                }
                // als het uitteam speelt op die datum; plaats wedstrijd onder dat team op die datum in de $matchesperdateperteam array
                elseif( array_key_exists($match->UitTeamId, $matchesperdateperteam[$match->Datum]) ) {
                  $matchesperdateperteam[$match->Datum][$match->UitTeamId][] = $match;
                }
              }
            }
            // alle wedstrijden staan nu per datum per team in de $matchesperdateperteam. Nu kunnen we door $matchesperdateperteam heen loopen en alle wedstrijden achter mekaar in $list zetten ($list word in het $tpl object gezet)
            $list = array();
            foreach( $matchesperdateperteam as $date => $team ) {
              foreach( $team as $teamid => $matches ) {
                // check of team of deze datum wel wedstrijden heeft
                if( !empty($matches) ) {
                  foreach( $matches as $key => $match ) {
                    $list[] = $match;
                  }
                }
              }
            }
          }
        }
        // Als de parameter reverse word mee gegeven, draai volgorde API output om.
        if(isset($list) && strpos($extra, 'reverse=1') > -1) {
          $list = array_reverse($list);
        }
        // Als de parameter maxresults word meegegeven, beperkt het aantal resultaten dat getoond word
        if( isset($list) && isset($parameters) && array_key_exists('resultaten', $parameters) && is_numeric($parameters['resultaten']) ) {
          $maxresults = (int) $parameters['resultaten'];
          $newList = array();
          $counter = 0;
          foreach( $list as $key => $match ) {
            if( $counter < $maxresults ) {
              $newList[] = $match;
              $counter++;
            }
          }
          if( !empty($newList) ) { $list = $newList; }
        }
        // Als parameter thuis word mee gegeven, herschik api output met eerst thuis wedstrijden en dan uit wedstrijden
        if(isset($list) && strpos($extra, 'thuis=1') > -1) {
          // logica voor thuisclub eerst in overzichten als 'thuis=1' in $extra zit
          if(strpos($extra, 'uitonly=1') === false) {
            $thuis = array_filter($list, function($row) {
              $length = strlen($this->clubName);
              return (isset($row->ThuisClub) && substr($row->ThuisClub, 0, $length) === $this->clubName);
            });

            if(count($thuis) > 0) {
              $tpl->assign('thuis', $thuis);
            }
          }

          if(strpos($extra, 'thuisonly=1') === false) {
            $uit = array_filter($list, function($row) {
              $length = strlen($this->clubName);
              return (isset($row->ThuisClub) && substr($row->UitClub, 0, $length) === $this->clubName);
            });

            if(count($uit) > 0) {
              $tpl->assign('uit', $uit);
            }
          }
        }
        // Als data niet herschikt hoeft te worden
        else {
          $tpl->assign('data', $list);
        }
        /*
        ** Toevoegingen
        */
        /* Als headers 1 is, voegen we de headers toe aan het tpl object */
        if( isset($list) ) {
          $headers = array();
          if( $list[0] == 'multipoules' )
          {
            foreach ($list as $pouleid => $matches)
            {
              if( $pouleid != 0 )
              {
                foreach ($matches[0] as $header => $matchdetail)
                {
                  $headerstr = lcfirst($header);
                  $headerstr = preg_replace('/(?<!\ )[A-Z]/', ' $0', $headerstr); // Add space before each capitol letter, `CompType` becomes `Comp Type`
                  $headerstr = ucfirst($headerstr);
                  $headers[$header] = $headerstr;
                }
                break;
              }
            }
          }
          else
          {
            foreach ($list[0] as $header => $matchdetail)
            {
              $headerstr = lcfirst($header);
              $headerstr = preg_replace('/(?<!\ )[A-Z]/', ' $0', $headerstr); // Add space before each capitol letter, `CompType` becomes `Comp Type`
              $headerstr = ucfirst($headerstr);
              $headers[$header] = $headerstr;
            }
          }
          if(strpos($extra, 'headers=1') > -1) {
            $tpl->assign('headers', $headers);
          }
          $tpl->assign('data', $list);
        }
        /* Als shortheaders 1 is, voegen we de shortheaders toe aan het tpl object */
        if(isset($list) && strpos($extra, 'shortheaders=1') > -1) {
          $shortheaders = array(
            'Gespeeld'  => 'G',
            'Gewonnen'  => 'W',
            'Gelijk'    => 'GL',
            'Verloren'  => 'V',
            'Punten'    => 'P'
          );
          $headers = array();
          if( $list[0] == 'multipoules' )
          {
            foreach ($list as $pouleid => $matches)
            {
              if( $pouleid != 0 )
              {
                foreach ($matches[0] as $header => $matchdetail)
                {
                  if( array_key_exists($header, $shortheaders) ) {
                    $headers[$header] = $shortheaders[$header];
                  }
                  else {
                    $headerstr = lcfirst($header);
                    $headerstr = preg_replace('/(?<!\ )[A-Z]/', ' $0', $headerstr); // Add space before each capitol letter, `CompType` becomes `Comp Type`
                    $headerstr = ucfirst($headerstr);
                    $headers[$header] = $headerstr;
                  }
                }
                break;
              }
            }
          }
          else
          {
            foreach ($list[0] as $header => $matchdetail)
            {
              if( array_key_exists($header, $shortheaders) ) {
                $headers[$header] = $shortheaders[$header];
              }
              else {
                $headerstr = lcfirst($header);
                $headerstr = preg_replace('/(?<!\ )[A-Z]/', ' $0', $headerstr); // Add space before each capitol letter, `CompType` becomes `Comp Type`
                $headerstr = ucfirst($headerstr);
                $headers[$header] = $headerstr;
              }
            }
          }
          $tpl->assign('headers', $headers);
          $tpl->assign('data', $list);
        }
      }
      // Als de fields parameter is meegeven dan moeten die velden verwijderd worden uit de output zodat ze niet tonen
      if(isset($dontshow) && !empty($dontshow)) {
        // remove headers that dont need to show
        if( isset($headers) && !empty($headers) ) {
          // loop through the headers
          foreach( $headers as $name => $value ) {
            // case insensitive in_array() does not exists in php; therefore use preg_grep()
            if( preg_grep( '/'.$value.'/i', $dontshow ) ) {
              // if the header exists in the dontshow list; remove it from the headers array
              unset($headers[$name]);
              // add name to array so we can use that to filter entries out of data array
              $datanames[] = $name;
            }
          }
        }
        // remove api data that doesn't need to show
        if( isset($list) && !empty($list) ) {
          // loop trough each data entry
          foreach( $list as $key => $details ) {
            // loop through the details
            foreach( $details as $name => $value ) {
              // if the name exists in the datanames array created above; remove the data entry
              if( in_array($name, $datanames) ) {
                unset($list[$key]->$name);
              }
            }
          }
        }
        // rearange dontshow array
        $fields = array();
        foreach( $dontshow as $key => $column ) {
          $fields[strtolower($column)] = true;
        }
        // assign the new headers and list to the tpl object
        $tpl->assign('headers', $headers);
        $tpl->assign('data', $list);
        $tpl->assign('fields', $fields);
      }
      return $tpl->draw($template_file, $return_string = true);
    }
  }
}

function precho( $var ){
  echo "<pre>";
  print_r( $var );
  echo "</pre>";
}

?>
