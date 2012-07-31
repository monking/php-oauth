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

The call:

    POST /php-oauth/api.php/authorizations/
    Authorization: Bearer xyz

    { 'client_id': 'democlient', 'scope': 'read write' }

The response:

    HTTP/1.1 201 Created

Example using cURL:

    curl -X POST -d '{"client_id":"example", "scope": "read write"}' 
    -H "Content-Type: application/json" 
    -H "Authorization: Bearer 8d93c2365812c64094e6c0946501e472" 
    -v http://localhost/php-oauth/api.php/authorizations/

## Getting an Authorization

The call:

    GET /php-oauth/api.php/authorizations/democlient
    Authorization: Bearer xyz

The response:

    HTTP/1.1 200 OK
    Content-Type: application/json

    { 'client_id': 'democlient', 'scope': 'read write' }

## Listing Authorizations

The call:

    GET /php-oauth/api.php/authorizations/
    Authorization: Bearer xyz

The response:

    HTTP/1.1 200 OK
    Content-Type: application/json

    [ { 'client_id': 'democlient', 'scope': 'read write' }, 
      { 'client_id': 'otherclient', 'scope': 'admin' } 
    ]

## Deleting Authorizations

The call:

    DELETE /php-oauth/api.php/authorizations/democlient
    Authorization: Bearer xyz

The response:

    HTTP/1.1 200 OK


