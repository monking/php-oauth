# Introduction

This service provides a REST API to manage "authorizations", i.e.: allow for
adding and deleting authorized applications. This is helpful for applications
that are designed to manage the OAuth server in which users are able to revoke
authorizations.

# API

This section describes the API to add and remove authorizations. However, the 
application managing this should also be authorized to do this. A scope of 
"oauth_authorizations" can be requested by the client. 

Not all clients should be allowed to do this, only particular clients after the 
resource owner was authenticated and authorized the client. All authorization
registrations through the API are bound to the authenticated resource owner.

## Adding Authorizations

This adds an authorization for a specific `client_id` with some `scope` for
the authenticated resource owner. The `client_id` needs to be registered, and
no existing authorization can exist for the `client_id` and resource owner.

The parameters `client_id` and `scope` are required. The resource owner is 
determined through the OAuth access token for which the resource owner gave
consent.

### Request

    POST /php-oauth/api.php/authorizations/ HTTP/1.1
    Authorization: Bearer xyz
    Content-Type: application/json

    {"client_id":"democlient","scope":"read write"}

### Response

    HTTP/1.1 201 Created

### cURL Example

    $ curl -v -X POST -d '{"client_id":"democlient","scope":"read write"}' \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer 8d93c2365812c64094e6c0946501e472" \
    http://localhost/php-oauth/api.php/authorizations/

## Getting an Authorization

### Request

    GET /php-oauth/api.php/authorizations/democlient HTTP/1.1
    Authorization: Bearer xyz

### Response

    HTTP/1.1 200 OK
    Content-Type: application/json

    { 'client_id': 'democlient', 'scope': 'read write' }

## Listing Authorizations

### Request

    GET /php-oauth/api.php/authorizations/ HTTP/1.1
    Authorization: Bearer xyz

### Response

    HTTP/1.1 200 OK
    Content-Type: application/json

    [{"client_id":"authorization_manager","scope":"authorizations"},{"client_id":"democlient","scope":"read write"}]

## Deleting Authorizations

### Request

    DELETE /php-oauth/api.php/authorizations/democlient HTTP/1.1
    Authorization: Bearer xyz

### Response

    HTTP/1.1 200 OK

# Error Handling

If a resource does not exist (in `GET` and `DELETE` requests) within a 
collection the `HTTP/1.1 404 Not Found` error code MUST be returned.

If the authorization fails, "OAuth 2.0 Authorization Framework: Bearer Token
Usage" error handling (Section 3.1) should be followed.

If something goes wrong at the server side an 
`HTTP/1.1 500 Internal Server Error` should be returned.

The error should be indicated through the HTTP status code as well as through
JSON in the body of the response. For example:

    HTTP/1.1 400 Bad Request
    Content-Type: application/json

    {"error":"invalid_request","error_description":"authorization already exists for this client and resource owner"}


