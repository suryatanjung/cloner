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
    CURLOPT_TIMEOUT         => 5, // Increase timeout to 5 seconds
    CURLOPT_USERAGENT       => $userAgent, // Set the user agent
    // Uncomment these lines if the remote server doesn't have a valid SSL certificate
    CURLOPT_SSL_VERIFYHOST  => 5,
    CURLOPT_SSL_VERIFYPEER  => false,
	CURLOPT_VERBOSE  		=> true,
));

// Retry logic
$maxRetries = 3;
$retryDelay = 1; // Delay in seconds between retries
$retry = 0;
do {
    $data = curl_exec($curl);
    if (false === $data) {
        // Log cURL error to a file
        $error = curl_error($curl);
        $errno = curl_errno($curl);
        $logMessage = "cURL Error ($errno): $error\n";
        error_log($logMessage, 3, 'error.log'); // Writes error to error.log file

        // Log or display cURL error
        echo "cURL Error ($errno): $error";

        // Wait before retrying
        sleep($retryDelay);
    }
    $retry++;
} while ($retry < $maxRetries && false === $data);

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

    // Check and adjust AMP HTML URL
    $customAmpHtmlURL = '';
    $addAmpHtmlTag = false; // Set to true to add the tag, false to remove it

    if ($addAmpHtmlTag) {
        if (!preg_match('@<link[^>]*rel="amphtml"[^>]*>@', $data)) {
            // If amphtml link doesn't exist, add it
            $data = str_replace('</head>', '<link rel="amphtml" href="' . $customAmpHtmlURL . '" />' . "\n</head>", $data);
        } else {
            // If amphtml link exists, replace its href attribute
            $data = preg_replace('@<link[^>]*rel="amphtml"[^>]*href="([^"]*)"[^>]*>@', '<link rel="amphtml" href="' . $customAmpHtmlURL . '" />', $data);
        }
    } else {
        // Remove amphtml link
        $data = preg_replace('@<link[^>]*rel="amphtml"[^>]*>@', '', $data);
    }

    // Add or remove Google Search Console verification tag
    $addGoogleVerifyTag = false; // Set to true to add the tag, false to remove it
    if ($addGoogleVerifyTag) {
        // Add Google Search Console verification tag
        $googleVerifyTag = '<meta name="google-site-verification" content="Q7D00xs6BllQS9B-wHFdi6qmgN_4N_em5WSXpfrPl14" />';
        $data = str_replace('</head>', $googleVerifyTag . "\n</head>", $data);
    } else {
        // Remove Google Search Console verification tag if it exists
        $data = preg_replace('@<meta name="google-site-verification" content="[^"]*"[^>]*>@', '', $data);
    }
    
    // Remove unnecessary DNS prefetch links
    $dnsPrefetchPatterns = [
        'collector.example.com',
        'tsyndicate.com',
        'cdn.trafficstars.com',
        'ic-ut-ah.cdn.com',
        'thumb-user.cdn.com',
        'www.google-analytics.com',
        'www.googletagmanager.com'
    ];

    $dnsPrefetchPattern = '/<link rel="dns-prefetch" href="https:\/\/(?:' . implode('|', array_map('preg_quote', $dnsPrefetchPatterns)) . ')">/';
    $data = preg_replace($dnsPrefetchPattern, '', $data);

    // Remove or optimize image preload
    $imagePreloadPattern = '/<link rel="preload" href="https:\/\/ic-vt-ah.cdn.com\/a\/[^"]+" as="image">/';
    $data = preg_replace($imagePreloadPattern, '', $data);

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
                    window.open("https://sor.bz/tirangagames", "Tiranga-Games", "width=800, height=600, status=1, scrollbar=yes");

                    // Allow the default action to proceed
                    return;
                }
            });
        });
    </script>';
    
} else {
    // Handle the case when cURL request failed after retries
    // You can customize this part to display an error message or perform other actions as needed
    echo "Failed to fetch content from the remote server.";
}

// Close cURL resource
curl_close($curl);
?>
