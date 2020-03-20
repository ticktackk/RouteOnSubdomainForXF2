CHANGELOG
==========================

## 1.0.1 (`1000170`)

- **Fix:** AJAX fails on Internet Explorer (#10)

## 1.0.0 (`1000070`)

* **Misc:** When `AccessControlAllowOriginHeaderAlreadySetException` exception is thrown, the existing value can now be read using the new `getExistingValue()` method
* **Misc:** Use a protected method to return the `\XF\App` instance
* **Fixed:** Updated doc blocks

## 1.0.0 Beta 1 (`1000031`)

* **Changed:** Check if the referrer in the header is part of current installation and if it is then check if the route is set to be on a sub-domain
* **Fixed:** Links generated using router do not respect subDomainSupportEnabled flag (#8)
* **Fixed:** `Access-Control-Allow-Credentials` header not being set if index page is not on route (#9)

## 1.0.0 Alpha 3 (`1000013`)

* **Fixed:** Cookies are not passed when making request to a sub-domain or vice versa (#6)
* **Changed:** Now only the cookie domain needs to be updated

**Note:** Make sure your cookie domain starts with a `.` (period).

## 1.0.0 Alpha 2 (`1000012`)

* **New:** Added support for route filters (#4)
* **Fixed:** Added missing phrase
* **Fixed:** Made compatible with SEO friendly urls turned on (#3)

## 1.0.0 Alpha 1 (`1000011`)

Initial release