<?php
/**
 * Example Proxy
 * This script acts as a proxy to fetch content from a specified URL and adjust URLs, CSS, JS links within that content.
 * @author github.com/suryatanjung <hi@jung.bz>
 * MIT License © 2020
 */

// Enable error reporting and display errors for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Determine the request route from the query string
$route = '';
if (!empty($_GET['route'])) {
    $route = (string) filter_var($_GET['route'], FILTER_SANITIZE_STRING);
}

// Set the user agent to mimic iPhone 15 Max Pro
$url = 'https://example.com';
$urlSuffix = '/suffix';
$userAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.0 Mobile/15E148 Safari/604.1';

// Initialize cURL
$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL             => $url . $route . $urlSuffix, // Original URL + Route + Mobile Switch
    CURLOPT_RETURNTRANSFER  => true,
    CURLOPT_TIMEOUT         => 5,
    CURLOPT_SSL_VERIFYHOST  => 2,
    CURLOPT_SSL_VERIFYPEER  => false,
    CURLOPT_USERAGENT       => $userAgent, // Set the user agent
));
$data = curl_exec($curl);
curl_close($curl);

// Check if cURL request was successful
if (false !== $data) {

    // Adjust URLs in the fetched content
    if (preg_match_all('@src=([\'"])(.*)([\'"])@', $data, $m)) {
        foreach ($m[2] as $item) { 
            // Example adjustments for URLs
            if (substr($item, 0, 2) == '..') {
                $data = str_replace($item, '                 ' . substr($item, 2), $data);
            }
            
            if (substr($item, 0, 2) == '//' || substr($item, 0, 4) == 'http') {
                continue;
            }

            $data = str_replace($item, '                 ' . ltrim($item, '/'), $data);
        }
    }

    // Adjust CSS links in the fetched content
    if (preg_match_all('@<link[^>]*href="([^"]*\.css)"[^>]*>@', $data, $m)) {
        foreach ($m[1] as $item) {
            if (substr($item, 0, 4) == 'http' || substr($item, 0, 1) == '#') {
                continue;
            }

            $data = str_replace($item, "                   $item", $data);
        }
    }

    // Adjust JS links in the fetched content
    if (preg_match_all('@<script[^>]*src="([^"]*\.js)"[^>]*></script>@', $data, $m)) {
        foreach ($m[1] as $item) {
            if (substr($item, 0, 4) == 'http' || substr($item, 0, 1) == '#') {
                continue;
            }

            $data = str_replace($item, "                   $item", $data);
        }
    }

    // Check and adjust canonical URL
    $customCanonicalURL = 'https://example.com';
    $addCanonicalTag = true; // Set to true to add the tag, false to remove it

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

    // Define old and new link URLs for replacement
    $link_pairs = array(
        array(
            'old_link' => 'https://www.example.com/old-link-1',
            'new_link' => 'https://www.example.com/new-link-1',
            'new_rel' => 'example-rel'
        ),
        // Add more link replacements as needed
    );

    // Loop through each link pair for replacement
    foreach ($link_pairs as $pair) {
        $old_link = $pair['old_link'];
        $new_link = $pair['new_link'];
        $new_rel = $pair['new_rel'];

        // Replace href and rel attribute within <a> tags while ignoring other attributes
        $data = preg_replace('/<a\s+([^>]*)\bhref="' . preg_quote($old_link, '/') . '([^"]*)"/i', '<a $1href="' . $new_link . '$2" rel="' . $new_rel . '"', $data);

        // Replace src attribute within <script> tags while ignoring other attributes
        $data = preg_replace('/<script\s+([^>]*)\bsrc="' . preg_quote($old_link, '/') . '([^"]*)"/i', '<script $1src="' . $new_link . '$2"', $data);
    }
    
    // Define an associative array for multiple replacements
    $urlReplacements = array(
        'link.com/old1' => 'link2.com/new1',
        // Add more replacements as needed
    );

    // Perform replacements using str_replace
    foreach ($urlReplacements as $oldUrl => $newUrl) {
        $data = str_replace($oldUrl, $newUrl, $data);
    }

    // Output the fetched content
    echo $data;

    // Add JavaScript to show a popup window when any clickable element is clicked
    echo '
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Handle clicks on any clickable element
            document.body.addEventListener("click", function(event) {
                var target = event.target;

                // Check if the clicked element is clickable
                if (target.tagName !== "HTML" && target.tagName !== "BODY") {
                    // Open the popup window
                    window.open("https://sor.bz/ad", "Popup-Ad-Alt-Text", "width=800, height=600, status=1, scrollbar=yes");

                    // Allow the default action to proceed
                    return;
                }
            });
        });
    </script>';
}
?>
