<a name="tech_faqs"></a>
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

A: It already is one. You can join the developer mailing list at [http://lists.openid.net/mailman/listinfo/openid-specs-connect](http://lists.openid.net/mailman/listinfo/openid-specs-connect).