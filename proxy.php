<?php

/**
 * This file is part of Cuyahoga-Proxy.
 *
 * Cuyahoga-Proxy is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Cuyahoga-Proxy is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * Enables or disables filtering for cross domain requests.
 * Recommended value: true
 */
define('CSAJAX_FILTERS', true);

/**
 * If set to true, $valid_requests should hold only domains i.e. a.example.com, b.example.com, usethisdomain.com
 * If set to false, $valid_requests should hold the whole URL ( without the parameters ) i.e. http://example.com/this/is/long/url/
 * Recommended value: false (for security reasons - do not forget that anyone can access your proxy)
 */
define('CSAJAX_FILTER_DOMAIN', false);

/**
 * Set debugging to true to receive additional messages - really helpful on development
 */
define('CSAJAX_DEBUG', false);

/**
 * A set of valid cross domain requests
 */
$valid_requests = array(
    'https://httpbin.org/anything'
);
/**
 * Set extra multiple options for cURL
 * Could be used to define CURLOPT_SSL_VERIFYPEER & CURLOPT_SSL_VERIFYHOST for HTTPS
 * Also to overwrite any other options without changing the code
 * See http://php.net/manual/en/function.curl-setopt-array.php
 */
$curl_options = array(
    // CURLOPT_SSL_VERIFYPEER => false,
    // CURLOPT_SSL_VERIFYHOST => 2,
);

/* * * STOP EDITING HERE UNLESS YOU KNOW WHAT YOU ARE DOING * * */

// allow any origin to access the server
header('Access-Control-Allow-Origin: *');

if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
    // preflight request

    // cache preflight results
    header('Access-Control-Max-Age: 86400');
    // allow normal http methods
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
        // allow all custom headers
        header('Access-Control-Allow-Headers: ' . $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']);
    }

    return;
}

$request_method = $_GET['method'];

if (isset($_GET['body'])) {
    $request_body = base64_decode($_GET['body']);
}

$request_headers_map = array();
parse_str($_GET['headers'], $request_headers_map);

//convert request headers to key: value format
$request_headers = array();
foreach($request_headers_map as $key => $value) {
    $request_headers[] = "$key: $value";
}

$request_url = $_GET['url'];
$p_request_url = parse_url($request_url);

// check against valid requests
if (CSAJAX_FILTERS) {
    $parsed = $p_request_url;
    if (CSAJAX_FILTER_DOMAIN) {
        if (!in_array($parsed['host'], $valid_requests)) {
            csajax_debug_message('Invalid domain - ' . $parsed['host'] . ' is not included in valid requests');
            exit;
        }
    } else {
        $check_url = isset($parsed['scheme']) ? $parsed['scheme'] . '://' : '';
        $check_url .= isset($parsed['user']) ? $parsed['user'] . ($parsed['pass'] ? ':' . $parsed['pass'] : '') . '@' : '';
        $check_url .= isset($parsed['host']) ? $parsed['host'] : '';
        $check_url .= isset($parsed['port']) ? ':' . $parsed['port'] : '';
        $check_url .= isset($parsed['path']) ? $parsed['path'] : '';
        if (!in_array($check_url, $valid_requests)) {
            csajax_debug_message('Invalid URL - ' . $request_url . ' is not included in valid requests');
            exit;
        }
    }
}

// let the request begin
$ch = curl_init($request_url);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request_method); // set http method
curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers); // (re-)send headers
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // return response
curl_setopt($ch, CURLOPT_HEADER, true); // enabled response headers
// add request body
if (isset($request_body)) {
    csajax_debug_message($request_body);
    curl_setopt($ch, CURLOPT_POSTFIELDS,  $request_body);
}

// Set multiple options for curl according to configuration
if (is_array($curl_options) && 0 <= count($curl_options)) {
    curl_setopt_array($ch, $curl_options);
}

// retrieve response (headers and content)
$response = curl_exec($ch);
curl_close($ch);

// split response to header and content
list($response_headers, $response_content) = preg_split('/(\r\n){2}/', $response, 2);

// (re-)send the headers
$response_headers = preg_split('/(\r\n){1}/', $response_headers);
foreach ($response_headers as $key => $response_header) {
    // Rewrite the `Location` header, so clients will also use the proxy for redirects.
    if (preg_match('/^Location:/i', $response_header)) {
        list($header, $value) = preg_split('/: /', $response_header, 2);
        $response_header = 'Location: ' . $_SERVER['REQUEST_URI'] . '?csurl=' . urlencode($value);
    }
    if (!preg_match('/^(Transfer-Encoding:)|(Access-Control)/i', $response_header)) {
        header($response_header, false);
    }
}

// finally, output the content
print($response_content);

function csajax_debug_message($message)
{
    if (true == CSAJAX_DEBUG) {
        $out = fopen('php://stdout', 'w');
        fwrite($out, '[DEBUG]: ' . $message . PHP_EOL);
        fclose($out);
    }
}
