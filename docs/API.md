# Introduction

This service provides a REST API to manage "authorizations", i.e.: allow for
adding and deleting authorized applications. This is helpful for applications
that are designed to manage the OAuth server in which users are able to revoke
authorizations.

# User Info API

This API call can be used by the authorized applications to retrieve some 
information about the resource owner that granted the authorization.

The calls are under the `resource_owner` path and the following attributes can
be retrieved: 

* `id` - get the persistent, possibly opague, identifier of the resource owner
* `entitlement` - get the entitlements granted to the resource owner, as an 
  array.

### Request `id`

    GET /php-oauth/api.php/resource_owner/id
    Authorization: Bearer xyz

### Response `id`

    HTTP/1.1 200 OK
    Content-Type: application/json

    {"id":"fkooman"}

This information is meant to improve the user experience. For example, to alter 
the user interface based on entitlements.

### Request `entitlement`

    GET /php-oauth/api.php/resource_owner/entitlement
    Authorization: Bearer xyz

### Response `entitlement`

    HTTP/1.1 200 OK
    Content-Type: application/json

    {"entitlement":["applications","administration"]}

# Authorizations API

This section describes the API to add and remove authorizations. However, the 
application managing this should also be authorized to do this. A scope of 
"authorizations" can be requested by the client. 

An "authorization" in this sense is an indicator that a resource owner allows
a client to act on its behalf. Typically whenever a registered clients starts
the OAuth dance it will trigger a confirmation dialog for the resource owner
to either allow or deny this request. Using this API a privileged client can
register these authorizations out-of-band. This will optimize the flow when
a client wants to access the protected resources: the resource owner is no 
longer prompted for consent.

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

The parameter `refresh_token` is optional. If set it should be a boolean 
indicating whether or not a refresh token should be generated.

You cannot request a scope that is not listed in the `allowed_scope` attribute
for the client.
 
### Request

    POST /php-oauth/api.php/authorizations/ HTTP/1.1
    Authorization: Bearer xyz
    Content-Type: application/json

    {"client_id":"democlient","scope":"read write","refresh_token":false}

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

    {"client_id":"democlient","scope":"read write"}

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

# Applications API

The API also provides functionality to manage applications, i.e.: client 
registrations. The following functionality is exposed:

* Add a new application (`POST /php-oauth/api.php/applications/`)
* Update an application (`PUT /php-oauth/api.php/applications/democlient`)
* Delete an application (`DELETE /php-oauth/api.php/applications/democlient`)
* List applications (`GET /php-oauth/api.php/applications/`)
* Get an application (`GET /php-oauth/api.php/applications/democlient`)

The API works the same as for the authorizations. For adding a new application
the following JSON parameters are required in the POST body:

* `id`
* `name`
* `description`
* `secret` (only for `web_application` type)
* `type` (`web_application`, `user_agent_based_application` or `native_application`)
* `redirect_uri`
* `icon` (full absolute URL to icon)
* `allowed_scope` (scopes the client is able to request, space separated)

For updating an application the same parameters are required, except `id` as 
that is specified in the URL directly.
