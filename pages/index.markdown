<a name="request"></a>
Making an OpenID Connect request
--
In order for the client to make an OpenID Connect request it needs to have the following information about the server:

 * `client identifier` - An unique identifier issued to the client to identify itself to the authorization server.
 * `client secret` - A shared secret established between the authorization server and client.
 * `end-user endpoint` - The authorization server's HTTP endpoint capable of authenticating the end-user and obtaining authorization.
 * `token endpoint` - The authorization server's HTTP endpoint capable of issuing tokens.
 * `user info endpoint` - A protected resource that when presented with a token by the client returns authorized information about the current user.

This information is either obtained by the client developer having read the server's documentation and pre-registered their application, or by performing [Discovery](#discovery) and a [Dynamic Association](#associations).

The client constructs a regular [OAuth 2.0](http://tools.ietf.org/html/draft-ietf-oauth-v2) request to obtain an access token.

To turn an OAuth 2.0 request into an OpenID Connect request, simply include `openid` as one of the requested scopes. The `openid` scope means that the client is requesting an identifier for the user as well as the user's profile URL, name, and picture. The server (and user) may choose to make more or less profile information available to the client. If the client also wants the user's email address, it should include the scope `email`.

The client MAY include a `user_id` parameter set to the normalized user input or the user identifier of the currently logged in user (when using immediate mode). If this parameter is included and the user signed into the server differs from the user signed into the client, the server MUST return an error when using immediate mode.

For example (line breaks added for display purposes):

    GET /authorize?type=web_server&scope=openid&
    client_id=s6BhdRkqt3&redirect_uri=https%3A%2F%2Fclient%2Eexample%2Ecom%2Fcb HTTP/1.1
    Host: server.example.com

*While pre-registering your client with servers is not required, it's likely that servers will have varying policies and requirements placed upon clients when it comes to accessing user information.*

<a name="response"></a>

-----
Receiving an OpenID Connect response
--
Assuming the user authorized the client's request, the following additional parameters are included in the [OAuth 2.0 access token response](http://tools.ietf.org/html/draft-ietf-oauth-v2-05#section-3.3.2) from the server:

 * `user_id` - A unique HTTPS URI of the currently signed in user. e.g. "https://example-server.com/08lnwon1n21" or "https://graph.facebook.com/24400320"
 * `issued_at` - A unix timestamp of when this signature was created.
 * `signature` - HMAC-SHA256 with the key being the client Secret and the text being the access token, expires in, issued at, and user identifier.

Note that unlike OpenID 1.0 and 2.0, the user identifier is different from the user's profile URL. This allows the identifier (and thus discovery) to be over SSL while not requiring that profile pages also be hosted via SSL.

The client MUST verify that the server's token endpoint is authoritative to issue assertions about the user identifier. If the domain (including sub-domain) of the user identifier matches the domain of the server's token endpoint URI then this verification is complete. If they do not match, the client MUST verify the assertion via [Discovery](#discovery).

The client SHOULD verify the signature. If the client does not verify the signature, it MUST make a [User Info API](#API) request and include its client identifier when doing so.

*(Code sample goes here!)*

<a name="API"></a>

-----
Accessing user information
--
OpenID Connect user identifiers return a basic JSON document when fetched via HTTP. They are OAuth 2.0 protected resources which means that more information is included in the response when the client presents an access token. The client constructs a HTTPS "GET" request to the user identifier returned in the OpenID response and includes the access token as a parameter (or header).

Clients SHOULD include a `client_id` parameter and MUST do so if they do not verify the signature within the [response](#response). If this parameter is included and the access token was issued to a different client, the server MUST return an error.

The response is a JSON object which contains some (or all) of the following reserved keys:

 * `user_id` - e.g. "https://graph.facebook.com/24400320"
 * `asserted_user` - true if the access token presented was issued by this user, false if it is for a different user
 * `profile_urls` - an array of URLs that belong to the user
 * `display_name` - e.g. "David Recordon"
 * `given_name` - e.g. "David"
 * `family_name` - e.g. "Recordon"
 * `email` - e.g. "recordond@gmail.com"
 * `picture` - e.g. "http://graph.facebook.com/davidrecordon/picture"

The server is free to add additional data to this response (such as [Portable Contacts](http://portablecontacts.net/draft-spec.html)) so long as they do not change the reserved OpenID Connect keys.

For example, the [Simple Registration extension](http://openid.net/specs/openid-simple-registration-extension-1_0.html) could be updated to define a new scope to request birthday, gender, postal code, language, etc as well as the parameter names for this API. (It could also make sense to define a list of links which servers can populate with things such as the user's Activity Streams endpoint, but this information might fit better within the discovery process.)

*We should add some sort of `openid2_url` field to this response to provide an upgrade path from non-SSL identifiers.*

<a name="discovery"></a>

-----
Discovery
--
When using OpenID Connect, it's likely that the client will have both buttons for popular servers as well as a text field for user entry of an URL or email address. OpenID Connect does not directly solve the "NASCAR" problem.

The goal of the discovery and association phase is for the client to have the server's user and token endpoint URLs, a client identifier, a client secret, and user info API endpoint URL. If the client has pre-registered with the server then this information will already be known. Otherwise the client will have to discover the server's token endpoint URL and request an identifier, secret, and the user and token endpoint URLs. 

 1. The user clicks a button on the client to select a server. In this case the client will have selected a set of preferred servers and thus already know their token endpoint URLs (among possibly other things). The client may or may not be pre-registered though.
 2. The user (or a User-Agent acting on their behalf) enters a URL or email address. In this case, the client needs to perform discovery and determine if there is a valid server token endpoint URL.

*It's worth noting that there are a few different variations on this discovery process that are all viable in one form or another. This is meant to be a starting point and will hopefully lead to good discussion within the OpenID, host-meta, LRDD, and WebFinger communities.*

**Steps:**

**1)** Parse the user input so that you have the various URI parts (scheme, domain, port and path) separated. It it's an email, then the scheme is `acct`. If no scheme, assume `http`.

<script src="http://gist.github.com/398239.js"></script>
<br />

**2)** Create a canonicalized identifier by reconstructing the various parts. For example:

> https://recordond.myopenid.com -> https://recordond.myopenid.com/<br />
> facebook.com -> http://facebook.com/<br />
> recordond@gmail.com -> acct:recordond@gmail.com

<script src="http://gist.github.com/398238.js"></script>
<br />

**3)** Extract the domain and fetch it's host-meta file looking for the OpenID token endpoint URI. The client should first make a SSL request and then fall back to HTTP if it failed. The client must not fallback to HTTP if the user inputed scheme was "https" or if the SSL request resulted in a certificate error. Cache the resulting document.

<pre><code>  GET /.well-known/host-meta?format=json HTTP/1.1
  Host: david.server.com</code></pre>

**3b)** Parse the returned [JRD document](http://hueniverse.com/2010/05/jrd-the-other-resource-descriptor/). (Assuming a JSON format was requested)

Look for a `link` array, find the element with a `rel` value of `openid`, and extract the value of `href`. This is the server's token endpoint URL. If you're familiar with OAuth 2.0, you'll know that it also has an end-user endpoint. The server tells the client about the end-user endpoint and User Info API during the dynamic association response.

<script src="http://gist.github.com/400268.js"></script>
<br />

**4)** The client will have discovered the server's token endpoint URL or failed.

Note that HTML based discovery hasn't gone away, but is just encompassed within the [LRDD](http://tools.ietf.org/html/draft-hammer-discovery) spec. To simplify OpenID Connect client implementations, the server's LRDD processor takes care of both host-meta and HTML based discovery.

Changes from host-meta and LRDD:

 * Uses a simple JSON document ("JRD") version of XRD

Changes from OpenID 2.0 discovery:

 * Much simpler technically. Places the work on the server versus the client having to try multiple different URLs, parse HTML, etc.
 * Requires that the domain support OpenID. Given that OpenID Connect separates the user identifier from the user's profile URL this should be okay. I could use MyOpenID as my OpenID server with a user identifier on their domain, but have my profile URL set to http://david.mybloghost.com/.
 * Only allows one OpenID server per domain (or sub-domain).

<a name="associations"></a>

-----
Unregistered clients and dynamic associations
--
Regardless of the discovery mechanism used, the client may or may not already be registered with the server. Servers may have different policies around what data clients can access based on if they've pre-registered (which generally includes agreeing to ToS) versus using a dynamic association.

If the client does not have a valid client identifier and secret, it shall make the following HTTPS "POST" request to the server's token endpoint URL (see [Discovery](#discovery)) with the following REQUIRED parameters:

 * `type` - "client_associate"
 * `redirect_uri` - The URI the client wishes to register with the server for receiving OpenID responses.

For example (line breaks added for display purposes):

    GET /oauth/token?type=client_associate&
    redirect_uri=https%3A%2F%2Fclient%2Eexample%2Ecom%2callback HTTP/1.1
    Host: server.example.com

Before responding, the server should check to see if the redirect URL is pre-registered outside of this OpenID flow. If so an error response should be sent. Servers will need to develop a policy to handle what happens when a redirect URL is pre-registered by a developer but has already been used to create dynamic associations. Most likely this means that new dynamic associations with that redirect URL will result in an error but requests using existing dynamic associations continue working until they expire.

*Maybe add client discovery here if the server wants to verify the redirect URL exists and is valid for the domain. Thinking you fetch https://domain/.well-known/host-meta and look for openid:redirect_uri. Also useful to get the client's display name and logo which the server can display to the user. The client would also use host-meta to advertise information needed for web browsers to help manage identity.*

To issue a dynamic association, the server shall include the following response parameters:

 * `client_id` - The client identifier. This could change with each response, up to the server.
 * `client_secret` - The client secret. This should change with each response.
 * `expires_in` - The number of seconds that this id and secret are good for or "0" if it does not expire.
 * `flows_supported` - A comma separated list of the OAuth 2.0 flows which this server supports. The server MUST support the Web server (`web_server`) and User-Agent (`user_agent`) flows.
 * `user_endpoint_url` - The URL of the server's user endpoint.

The client should store their dynamic associations based off of the server's token endpoint URL. With each dynamic association the client will store the client identifier, client secret, expiration time, user endpoint URL, supported flows, and User Info API endpoint URL. The expiration time should be stored as an absolute time or null if it lasts forever.