# Introduction

This service provides a REST API to manage "authorizations", i.e.: allow for
adding and deleting authorized applications. This is helpful for applications
that are designed to manage the OAuth server in which users are able to revoke
authorizations.

# API

This section describes the API to add and remove authorizations.

## Adding Authorizations

The call:

    POST /php-oauth/api.php/authorizations/
    
    { 'client_id': 'democlient', 'scope': 'read write' }

The response:

    HTTP/1.1 201 Created

## Getting an Authorization

The call:

    GET /php-oauth/api.php/authorizations/democlient

The response:

    HTTP/1.1 200 OK
    Content-Type: application/json

    { 'client_id': 'democlient', 'scope': 'read write' }

## Listing Authorizations

The call:

    GET /php-oauth/api.php/authorizations/

The response:

    HTTP/1.1 200 OK
    Content-Type: application/json

    [ { 'client_id': 'democlient', 'scope': 'read write' }, 
      { 'client_id': 'otherclient', 'scope': 'admin' } 
    ]

## Deleting Authorizations

The call:

    DELETE /php-oauth/api.php/authorizations/democlient

The response:

    HTTP/1.1 200 OK


