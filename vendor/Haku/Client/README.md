# Haku\Client

Client package has simple functionality to detect non-identifiable client platform information based on user agent. While it isn't a reliable way to detect clients it is convenient.

---

## Functions

**`detectClientOperatingSystem(string $userAgent): string`**

Returns the operating system from user agent, if no known operating system is detected it returns "Unknown". Can be one of "iPadOS", "iOS", "macOS", "Windows", "Android" and "Linux". Does not return the version.

**`detectClientBrowser(string $userAgent): string`**

Attempts to detect client browser, returns one of the ten most common browsers as of 2025.

**`detectClientDevice(string $userAgent): string`**

Returns either "Desktop", "Tablet" or "Mobile".
