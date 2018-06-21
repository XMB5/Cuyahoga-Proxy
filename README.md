<!---
This file is part of Cuyahoga-Proxy.

Cuyahoga-Proxy is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

Cuyahoga-Proxy is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.                                                                         
-->
# Cuyahoga-Proxy

*Fork of [PHP CORS Proxy](https://github.com/softius/php-cross-domain-proxy)*

Cuyahoga-Proxy is a simple php script that allows cross domain requests. It can be used to access resources from third party websites when it's not possible to enable CORS on target website i.e. when you don't own that website.

**Note**: Please check whether this solution is indeed necessary by having a look on [how you can enable CORS on your server](http://enable-cors.org/server.html).

## Overview


### Features

* Acts as a reverse proxy: request headers and data are propagated from proxy to server. Similarly, response headers and data are propagated from proxy to client.
* The proxy can be accessed from any origin (CORS is enabled)
* Provides support for all HTTP methods
* Works with HTTPS
* Requests can be filtered against a list of trusted domains or URLs.

### Requirements

Cuyahoga-Proxy requires PHP 5.3+ or above.

### Authors

- [Iacovos Constantinou][link-author]  - Original Creator
- [Sam Foxman](https://github.com/XMB5)  - Forked Version Creator
- Other [contributors][link-contributors]


### License

Cuyahoga-Proxy is licensed under GPL-3.0. See `LICENCE.txt` file for further details.


## Installation

**Using composer**

```
composer require XMB5/Cuyahoga-Proxy
```

**Manual installation**

The proxy is intentionally limited to a single file. All you have to do is to place `proxy.php` under the public folder of your application. 

### Configuration

For security reasons, don't forget to define all the trusted domains / URLs into top section of `proxy.php` file:

``` PHP
$valid_requests = array(
    'http://www.domainA.com/',
    'http://www.domainB.com/path-to-services/service-a'
);
```

## Usage

All request data is sent through query parameters:
- `url`
  - the target url
- `method`
  - the http method
- `headers` (optional)
  - the http headers
  - query string format (key=value&k=v)
- `body` (optional)
  - the request body
  - base64 encoded

Full-featured JavaScript example ([jsfiddle](https://jsfiddle.net/4ypa01vd/54/))
``` JavaScript
//change an object {key:'value'} to a query string 'key=value'
function objectToQueryString (object) {
    let queryString = '';
    let firstParam = true;
    for (let key in object) {
        if (object.hasOwnProperty(key)) {
            if (firstParam) {
                firstParam = false;
            } else {
                queryString += '&';
            }
            queryString += encodeURIComponent(key) + '=' + encodeURIComponent(object[key]);
        }
    }
    return queryString;
}

/*
Construct a URL with all the properly encoded query parameters
opts: {
  proxyUrl: string,
  targetUrl: string,
  method: string,
  headers: {
    key: 'value'
  }
  body: string or plain object
}
*/
function buildUrl (opts) {
    let url = opts.proxyUrl + '?url=' + encodeURIComponent(opts.targetUrl);
    url += '&method=' + encodeURIComponent(opts.method);
    url += '&headers=' + encodeURIComponent(objectToQueryString(opts.headers));
    if (opts.body) {
        if (opts.body.constructor === Object) {
            opts.body = objectToQueryString(opts.body);
        }
        url += '&body=' + encodeURIComponent(btoa(opts.body));
    }
    return url;
}

var url = buildUrl({
  proxyUrl: 'https://shrekismyidol.000webhostapp.com/',
  targetUrl: 'https://httpbin.org/anything?param=value',
  method: 'POST',
  headers: {
  	'User-Agent': 'Forge 8.8',
  	'Cookie': 'Monster',
    'Content-Type': 'text/plain'
  },
  body: 'lots of data'
});


fetch(url, {
	method: 'POST'
}).then(res => {
	return res.text();
}).then(text => {
	alert(text);
});
```

[link-author]: https://github.com/softius
[link-contributors]: https://github.com/XMB5/Cuyahoga-Proxy/graphs/contributors
