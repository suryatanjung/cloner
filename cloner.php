<?php 
/**
* Example Proxy
* This script acts as a proxy to fetch content from a specified URL and adjust URLs and CSS links within that content.
* @author github.com/suryatanjung <hi@jung.bz>
* MIT License © 2020
*/

// Determine request
$route = '';
if (!empty($_GET['route'])) {
    $route = (string) filter_var($_GET['route'], FILTER_SANITIZE_STRING); 
}

// Proxy
$curl = curl_init();
curl_setopt_array($curl, array( 
   CURLOPT_URL              => 'https://www.example.com' . $route . '/example-route',      // Original URL + Route
   CURLOPT_RETURNTRANSFER   => true,
   CURLOPT_TIMEOUT          => 5, 
   CURLOPT_SSL_VERIFYHOST   => 5, 
   CURLOPT_SSL_VERIFYPEER   => true, 
)); 
$data = curl_exec($curl); 
curl_close($curl);

if (false !== $data) {
  
    // Adjust URLs
    if (preg_match_all('@src=([\'"])(.*)([\'"])@', $data, $m)) {
        foreach ($m[2] as $item) { 
            if (substr($item, 0, 2) == '..') {
                $data = str_replace($item, '                 ' . substr($item, 2), $data);
            }
            
            if (substr($item, 0, 2) == '//' || substr($item, 0, 4) == 'http') {
                continue;
            }

            $data = str_replace($item, '                 ' . ltrim($item, '/'), $data);
        }
    }

    // Adjust CSS links
    if (preg_match_all('@<link[^>]*href="([^"]*\.css)"[^>]*>@', $data, $m)) {
        foreach ($m[1] as $item) {
            if (substr($item, 0, 4) == 'http' || substr($item, 0, 1) == '#') {
                continue;
            }

            $data = str_replace($item, "                   $item", $data);
        }
    }

    // Check and adjust canonical URL
    $customCanonicalURL = 'https://www.example.com';
    $addCanonicalTag = false; // Set to true to add the tag, false to remove it

    if ($addCanonicalTag) {
        if (!preg_match('@<link[^>]*rel="canonical"[^>]*>@', $data)) {
            // If canonical link doesn't exist, add it
            $data = str_replace('</head>', '<link rel="canonical" href="' . $customCanonicalURL . '" />' . "\n</head>", $data);
        } else {
            // If canonical link exists, replace its href attribute
            $data = preg_replace('@<link[^>]*rel="canonical"[^>]*href="([^"]*)"[^>]*>@', '<link rel="canonical" href="' . $customCanonicalURL . '" />', $data);
        }
    } else {
        // Remove canonical link
        $data = preg_replace('@<link[^>]*rel="canonical"[^>]*>@', '', $data);
    }

    // Add or remove Google Search Console verification tag
    $addGoogleVerifyTag = false; // Set to true to add the tag, false to remove it
    if ($addGoogleVerifyTag) {
        // Add Google Search Console verification tag
        $googleVerifyTag = '<meta name="google-site-verification" content="YOUR-VERIFICATION-CODE" />';
        $data = str_replace('</head>', $googleVerifyTag . "\n</head>", $data);
    } else {
        // Remove Google Search Console verification tag if it exists
        $data = preg_replace('@<meta name="google-site-verification" content="[^"]*"[^>]*>@', '', $data);
    }

    // Define old and new link URLs
    $link_pairs = array(
        array(
            'old_link' => 'https://www.example.com/old-link-1',
            'new_link' => 'https://www.example.com/new-link-1',
            'new_rel' => 'example-rel'
        ),
        array(
            'old_link' => 'https://www.example.com/old-link-2',
            'new_link' => 'https://www.example.com/new-link-2',
            'new_rel' => 'example-rel'
        ),
        array(
            'old_link' => 'https://www.example.com/old-link-3',
            'new_link' => 'https://www.example.com/new-link-3',
            'new_rel' => 'example-rel'
        ),
        // Add more link replacements as needed
    );

    // Loop through each link pair for replacement
    foreach ($link_pairs as $pair) {
        $old_link = $pair['old_link'];
        $new_link = $pair['new_link'];
        $new_rel = $pair['new_rel'];

        // Replace href attribute within <a> tags while ignoring other attributes
        $data = preg_replace('/<a\s+([^>]*)\bhref="' . preg_quote($old_link, '/') . '([^"]*)"/i', '<a $1href="' . $new_link . '$2" rel="' . $new_rel . '"', $data);
    }

    echo $data;
}
