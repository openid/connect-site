Did you know that OpenID was last updated in 2007? Since then we've seen OAuth 1.0 and 2.0. Facebook Connect. OpenSocial. Google FriendConnect. Rich address book APIs. And more recently, Twitter @anywhere.

In 2005 I don't think that Brad Fitzpatrick or I could have imagined how successful OpenID would become. Today there are over 50,000 websites supporting it and that number grows into the millions if you include Google FriendConnect. There are over a billion OpenID enabled URLs and production implementations from the largest companies on the Internet.

But we as a community must be willing to take a step back and realize that there's still a long way to go. The early draft below is meant to inspire and help revitalize the OpenID community. It isn't perfect, but hopefully it's a real starting point. It is designed to be modern, removing support for features which haven't seen adoption and adding support for things like using your email address as your identity.

We've heard loud and clear that sites looking to adopt OpenID want more than just a unique URL; social sites need basic things like your name, photo, and email address. When Joseph Smarr and I [built the OpenID/OAuth hybrid](http://googledataapis.blogspot.com/2009/01/bringing-openid-and-oauth-together.html) we were looking for a way to provide that functionality, but it proved complex to implement. So now there's a simple JSON User Info API similar to those already offered by major social providers.

We have also heard that people want OpenID to be simple. I've heard story after story from developers implementing OpenID 2.0 who don't understand why it is so complex and inevitably forgot to do something. With OpenID Connect, discovery no longer takes [over 3,000 lines of PHP](http://github.com/openid/php-openid/tree/master/Auth/Yadis/) to implement correctly. Because it's built on top of [OAuth 2.0](http://tools.ietf.org/html/draft-ietf-oauth-v2), the whole spec is fairly short and technology easy to understand. Building on OAuth provides amazing side benefits such as potentially being the first version of OpenID to work natively with desktop applications and even on mobile phones.

Why the name "OpenID Connect"? I'm a geek which means that good branding (or good design) isn't my thing, but Chris Messina (who is good at branding and design) proposed it a few months ago. As [Chris said in January](http://factoryjoe.com/blog/2010/01/04/openid-connect/), "I want OpenID Connect to be what Facebook and Google and others implement that becomes the interoperable identity interchange protocol for the social web. But we're not quite there yet, though all the technology is on the verge of being...ready." To me, OpenID Connect captures both the product experience and technological evolution. Not to mention that "OpenID 3.0" just sounds like we're trying too hard.

So with that background, I hope you understand where this proposal came from. It was written in just a few days and I am really hoping that by sharing a technical proposal (along with a few bits of code) we can start having an actual conversation about the future of OpenID. **Want to discuss it, jump on [specs@openid.net](http://lists.openid.net/mailman/listinfo/openid-specs). Or see you in person at the [Internet Identity Workshop](http://www.internetidentityworkshop.com/).**

Thanks to a bunch of people who I've talked with about this over the past few months. I really can't claim credit for the idea, just writing down and gluing together good ideas. Specifically I'd like to call out [Eran Hammer-Lahav](http://hueniverse.com/) (who actually wrote some of the text!), [Allen Tom](http://twitter.com/atom), [Chris Messina](http://factoryjoe.com/), [Evan Gilbert](mailto:uidude@google.com), [Joseph Smarr](http://twitter.com/jsmarr), [Luke Shepard](http://www.sociallipstick.com/), and [Martin Atkins](http://martin.atkins.me.uk/about/) for their ideas and quick feedback!

*--David Recordon (May 15th, 2010)*

<table class="buttons"><tr><td><iframe src="http://www.facebook.com/plugins/like.php?href=http%253A%252F%252Fopenidconnect.com%252F&amp;layout=standard&amp;show_faces=true&amp;width=350&amp;action=like&amp;font&amp;colorscheme=light&amp;height=80" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:350px; height:80px;" allowTransparency="true"></iframe></td><td><a title="Post on Google Buzz" class="google-buzz-button" href="http://www.google.com/buzz/post" data-button-style="normal-button"></a><script type="text/javascript" src="http://www.google.com/buzz/api/button.js"></script></td><td><script type="text/javascript" src="http://tweetmeme.com/i/scripts/button.js"></script></td></table>

<a name="request"></a>

-----
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

<a name="tech_faqs"></a>

-----
Technical FAQs
--
**Q: Does OpenID Connect require clients pre-register with servers? Wouldn't that kill the decentralized nature of OpenID?**

A: OpenID Connect does not require that clients pre-register as that would be lame. Rather it allows for dynamic associations like OpenID 1.0 and 2.0, but recognizes that many identity services on the Web today require pre-registration in order to access anything beyond basic identity information.

**Q: Do user identifiers need to be served over SSL?**

A: Yes. The user identifier must be served over SSL to help protect the discovery process from man in the middle attacks. Note that the user's URL returned via the OpenID Connect API can be HTTP. Clients should link people to the user's URL unlike in OpenID 1.0 and 2.0 where the identifier was equivalent to the user's URL.

**Q: Why not just make the User Info API another request type on the user or token endpoints?**

A: OAuth 2.0 logically separates the Authorization server and Protected Resources. The user and token endpoints reside on the Authorization server whereas the User Info API is really a Protected Resource. They could even be on separate domains; Yahoo! may run a central OpenID Connect server and then issue an access token for a User Info API run on Flickr.

**Q: What is the signature used for in the response?**

A: The signature is designed for clients to verify that the server they thought sent the response actually sent it. This is achieved by them having a symmetric key; the client secret. It can also be directly placed into a cookie on the client's site to manage login state. The expiration of the cookie should be the `expires_in` value and with each request their backend server can verify the signature to make sure the user hasn't tampered with it. The client would set two cookies, one which contains the user identifier and the second which contains the signature.

**Q: What about delegation?**

A: The original need for delegation was in 2005 when identity on the web was only starting. There wasn't buy in from email providers, ISPs, let alone social providers. I think that a form of delegation could be supported, but not in the sense that your blog URL becomes the identifier itself. Rather delegation today allows you to have an OpenID server directly assert a domain you own as your user identifier.

**Q: Why a scope of "openid" versus something more generic like "identity"?**

A: We thought about using a more generic term, but can we really agree on what "identity" means? Didn't think so.

**Q: How does OpenID Connect work with web browsers, "active clients", etc?**

A: That's a bit unclear, but definitely needed! I'm hoping browser vendors can help us figure this out (such as what information a client could advertise to a browser).

**Q: Why didn't you use camelCase for parameter names in the User Info API?**

A: OpenID and OAuth have traditionally used underscores instead of camel case.

**Q: What about "directed identity" and pairwise pseudonymous identifiers?**

A: This still works. The client doesn't pass the user identifier into the request and the server could issue a different pseudonymous user identifier to each client. While the User Info API defines a named set of profile fields, the server (or user) could decide not to share this information with a given client.

**Q: How does an OpenID 2.0 Relying Party migrate users to OpenID Connect?**

A: This is still a bit unclear. OpenID Connect forces user identifiers to be SSL or email addresses while OpenID 1.0 and 2.0 allowed non-SSL identities. Adding the non-SSL OpenID 2.0 identity to the User Info API wouldn't be incredibly useful as the OpenID Connect client would have to implement OpenID 2.0 style discovery to verify it. Let's figure out how to solve this!

**Q: What about "display" and "language" parameters when making the OpenID request?**

A: Hopefully this will make it into OAuth 2.0 itself. If not, we'll add it here.

**Q: Why not build this off of Public/Private key pairs? Wouldn't that eliminate the need for the association request?**

A: We are still discussing the idea of using asymmetric secrets for signing request, similar to the Salmon protocol proposal. However, experience has shown that using cryptography is hard to develop and easy to get wrong. We feel it is better to start with something simpler (if possible).

<a name="other_faqs"></a>

-----
Other FAQs
--
**Q: Does OpenID Connect work outside of web browsers?**

A: Yes! This is one of the major benefits of building on top of OAuth 2.0. OpenID Connect works in web browsers (including via JavaScript), within desktop applications, on mobile phones, and even could work on your XBox. Given that new flows can be created for OAuth 2.0 as the Internet continues to evolve, hopefully we're helping to make OpenID future proof.

**Q: Why didn't you just use WebFinger?**

A: We sort of are! WebFinger is really a profile of host-meta and LRDD designed for email addresses. LRDD has rolled the ideas within WebFinger back into the spec which is what is being used here. WebFinger is certainly a better brand for discovery!

**Q: Why not Portable Contacts for the User Info API?**

A: While it's likely that developers will use OpenID Connect to discover a variety of standard APIs in the future, the industry isn't quite there yet. The most successful extension to OpenID is Simple Registration which defines under ten profile fields. There's been clear feedback from potential adopters of OpenID that they want a consistent way to request basic profile information. Thus standardizing an extremely simple – yet extensible – user information API is a valuable first step. There's nothing stopping an OpenID Connect server from adding Portable Contacts to their User Info API.

**Q: So why not use a subset of Portable Contacts?**

A: See the answer to the camelCase versus underscores question above. The User Info API does reuse common parameter names; `display_name` versus `displayName`.

**Q: How is this different from Google's "EasyHybrid" proposal?**

A: At the core they're pretty similar! This partially grew out of conversations around EasyHybrid but has a few technical differences around identifiers, server discovery, and dynamic associations. We're both working toward the same goal and actively discussing how to get there together.

**Q: Why did you call it "OpenID Connect"?**

A: A "1.0" technology is beta and cool, "2.0" feels refined and stable, but "3.0" just sounds like you're trying too hard. From a product perspective, "Connect" describes what the technology helps people do with websites. From a technology perspective, it illustrates how OpenID is modern and builds on top of technologies like OAuth.

**Q: Doesn't the OpenID Foundation have a process for creating new specs?**

A: Yes. You might not believe me, but we wrote this in just a few days. Over the past few years I've found that the best way to create new technologies is by actually making technical proposals (ideally with a bit of code too) that others can evaluate.

**Q: Haven't there been OpenID Foundation Working Group proposals for OpenID v.Next?**

A: Yes, there have been proposals for working groups on discovery and attributes over the past month (and talk about doing so for almost a year). Combined they have over a dozen new features and there hasn't even been a proposal for the core OpenID Authentication spec yet. OpenID needs to be driven by simplicity and elegance, not by support for over a dozen new features.

**Q: Will you turn this into an OpenID Foundation Working Group?**

A: Sure, if the community thinks it's a good idea.
