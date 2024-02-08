<?php 
/**
* Example Proxy
* This script acts as a proxy to fetch content from a specified URL and adjust URLs and CSS links within that content.
* @author github.com/suryatanjung <hi@jung.bz>
*/

/**
* Determine request
*/
$route = '';
if ( ! empty( $_GET['route'] ) ) 
{
      $route = (string) filter_var( $_GET['route'], FILTER_SANITIZE_STRING ); 
}

/**
* Proxy
*/
$curl = curl_init();
curl_setopt_array( $curl, array( 
   CURLOPT_URL              => 'https://example.com' . $route . '/route',      // Original URL + Route
   CURLOPT_RETURNTRANSFER   => true,
   CURLOPT_TIMEOUT          => 5, 
   CURLOPT_SSL_VERIFYHOST   => 5, 
   CURLOPT_SSL_VERIFYPEER   => true, 
)); 
$data = curl_exec( $curl ); 
curl_close( $curl );

if (false !== $data ) {
  
    /* Adjust URLs */
    if ( preg_match_all( '@src=([\'"])(.*)([\'"])@', $data, $m ) )
    {
         foreach ( $m[2] as $item ) 
         { 
             if ( substr( $item, 0, 2 ) == '..' )
                  $data = str_replace( $item, '                 ' . substr( $item, 2 ), $data );
            
             if ( substr( $item, 0, 2 ) == '//' || 
                  substr( $item, 0, 4 ) == 'http' )
                  continue;

             $data = str_replace( $item, '                 ' . ltrim( $item, '/' ), $data );
         }
    }

    /* Adjust CSS links */
    if ( preg_match_all( '@<link[^>]*href="([^"]*\.css)"[^>]*>@', $data, $m ) )
    {
         foreach ( $m[1] as $item )
         {
             if ( substr( $item, 0, 4 ) == 'http' || 
                 substr( $item, 0, 1 ) == '#' )
                 continue;
    
             $data = str_replace( $item, "                   $item", $data );
         }
    }

      /* Replace specific href anchor links */
      $data = str_replace('https://example.com/old-link-1', 'https://example.com/new-link-1', $data);      // Replace URL 1
      $data = str_replace('https://example.com/old-link-2', 'https://example.com/new-link-2', $data);      // Replace URL 2
      $data = str_replace('https://example.com/old-link-3', 'https://example.com/new-link-3', $data);      // Replace URL 3

      /* Replace all href anchor links with one new link */
      $data = preg_replace('/href="[^"]+"/', 'href="https://example.com/new-link-for-the-rest"', $data);      // Replace All The Rest Link
      
    echo $data;
}
