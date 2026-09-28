<?php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');

// Every custom header any client sends has to be listed here, or the browser refuses the request
// before it is ever made and the app sees a bare network failure with no status code to explain it.
//
// `X-Device-Id` is the one that matters: the student app sends it on EVERY request for the device
// limit, which makes every request non-simple and preflighted. Leaving it out took the whole
// student dashboard offline against a deployed backend - not one endpoint, all of them.
//
// The language headers are here for the same reason. `Accept-Language` is technically safelisted,
// but the backend also reads `Lang` / `X-Lang` / `Language`, and those are not.
// `Accept` and `Accept-Language` are safelisted and would pass without being named, but listing
// everything the clients actually send means the list can be checked against them rather than
// reasoned about.
header('Access-Control-Allow-Headers: Content-Type, Authorization, Accept, X-Requested-With, X-Device-Id, Accept-Language, Lang, X-Lang, Language, X-HTTP-Method-Override');

// Without this the browser preflights again for every single request, doubling the round trips on
// a connection that is often a phone on mobile data. 24 hours is the maximum Chrome honours.
header('Access-Control-Max-Age: 86400');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}
