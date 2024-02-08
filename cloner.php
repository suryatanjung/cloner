<?php 
/**
* @author github.com/suryatanjung <hi@jung.bz>
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
   CURLOPT_URL              => '                  ' . $route . '/',
   CURLOPT_RETURNTRANSFER   => true,
   CURLOPT_TIMEOUT          => 5, 
   CURLOPT_SSL_VERIFYHOST   => 5, 
   CURLOPT_SSL_VERIFYPEER   => 0, 
)); 
$data = curl_exec( $curl ); 
curl_close( $curl );

if (false !== $data ) {
  
    if ( preg_match_all( '@src=([\'"])(.*)([\'"])@', $data, $m ) )
    {
         foreach ( $m[2] as $item ) 
         { 
             if ( substr( $item, 0, 2 ) == '..' )
                  $data = str_replace( $item, '                 ' . substr( $item, 2 ), $data );
            
             if ( substr( $item, 0, 2 ) == '//' || 
                  substr( $item, 0, 4 ) == 'http' )
                  continue;

             $data = str_replace($item, '                ' . ltrim( $item, '/' ), $data );
         }
    }

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

    echo $data;
}
